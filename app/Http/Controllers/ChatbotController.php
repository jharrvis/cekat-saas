<?php

namespace App\Http\Controllers;

use App\Models\Widget;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    public function index()
    {
        $chatbots = auth()->user()->widgets()->with('knowledgeBase')->get();
        $plan = auth()->user()->plan;

        return view('chatbots.index', compact('chatbots', 'plan'));
    }

    public function create()
    {
        $user = auth()->user();
        $plan = $user->plan;

        // Check plan limits
        if ($plan && $user->widgets()->count() >= $plan->max_widgets) {
            return redirect()->route('chatbots.index')
                ->with('error', 'You have reached your plan limit. Upgrade to create more chatbots.');
        }

        return view('chatbots.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'display_name' => 'required|max:255',
            'description' => 'nullable|max:500',
        ]);

        $user = auth()->user();
        $plan = $user->plan;

        // Check plan limits again
        if ($plan && $user->widgets()->count() >= $plan->max_widgets) {
            return redirect()->route('chatbots.index')
                ->with('error', 'You have reached your plan limit.');
        }

        // Create widget
        $widget = $user->widgets()->create([
            'name' => Str::slug($request->display_name),
            'display_name' => $request->display_name,
            'description' => $request->description,
            'slug' => 'widget-' . $user->id . '-' . Str::random(8),
            'is_active' => true,
            'status' => 'draft',
        ]);

        // Create knowledge base
        $widget->knowledgeBase()->create([
            'company_name' => $request->display_name,
            'persona_name' => 'AI Assistant',
            'persona_tone' => 'friendly',
        ]);

        return redirect()->route('chatbots.edit', $widget->id)
            ->with('success', 'Chatbot created successfully! Now configure your chatbot.');
    }

    public function edit($chatbotId, $tab = 'general')
    {
        $chatbot = auth()->user()->widgets()->with('knowledgeBase')->findOrFail($chatbotId);
        $validTabs = ['general', 'knowledge', 'model', 'widget', 'lead', 'analytics', 'embed'];

        if (!in_array($tab, $validTabs)) {
            $tab = 'general';
        }

        return view('chatbots.edit', compact('chatbot', 'tab'));
    }

    public function update(Request $request, $chatbotId)
    {
        $chatbot = auth()->user()->widgets()->findOrFail($chatbotId);
        $tab = $request->input('tab', 'general');

        // Handle Model Selection from model tab (settings[model])
        if ($request->has('settings.model') || $request->has('settings')) {
            $settings = $chatbot->settings ?? [];
            $inputSettings = $request->input('settings', []);

            if (isset($inputSettings['model'])) {
                $settings['model'] = $inputSettings['model'];
                $chatbot->update(['settings' => $settings]);

                return redirect()->back()->with('success', 'Model LLM berhasil dipilih!');
            }
        }

        // Handle Lead Collection settings
        if ($tab === 'lead') {
            $settings = $chatbot->settings ?? [];

            // Strategy 1: Prompt Engineering
            $settings['lead_prompt_enabled'] = $request->has('lead_prompt_enabled');
            $settings['lead_ask_name'] = $request->has('lead_ask_name');
            $settings['lead_ask_email'] = $request->has('lead_ask_email');
            $settings['lead_ask_phone'] = $request->has('lead_ask_phone');

            // Strategy 2: Trigger System
            $settings['lead_trigger_enabled'] = $request->has('lead_trigger_enabled');
            $settings['lead_trigger_after_message'] = (int) $request->input('lead_trigger_after_message', 3);
            $settings['lead_trigger_keywords'] = $request->input('lead_trigger_keywords', '');

            // Strategy 3: Pre-chat Form
            $settings['lead_form_enabled'] = $request->has('lead_form_enabled');
            $settings['lead_form_require_name'] = $request->has('lead_form_require_name');
            $settings['lead_form_require_email'] = $request->has('lead_form_require_email');
            $settings['lead_form_require_phone'] = $request->has('lead_form_require_phone');

            $chatbot->update(['settings' => $settings]);

            return redirect()->back()->with('success', 'Lead collection settings saved!');
        }

        // Default: General tab
        $request->validate([
            'display_name' => 'required|max:255',
            'description' => 'nullable|max:500',
            'allowed_domains' => 'nullable|string',
        ]);

        $settings = $chatbot->settings ?? [];
        $settings['allowed_domains'] = $request->input('allowed_domains');

        $chatbot->update([
            'display_name' => $request->display_name,
            'description' => $request->description,
            'status' => $request->status ?? $chatbot->status,
            'settings' => $settings,
        ]);

        return redirect()->back()->with('success', 'Chatbot updated successfully!');
    }

    public function destroy($chatbotId)
    {
        $chatbot = auth()->user()->widgets()->findOrFail($chatbotId);
        $chatbot->delete();

        return redirect()->route('chatbots.index')
            ->with('success', 'Chatbot deleted successfully!');
    }
}
