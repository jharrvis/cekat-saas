<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWidgetRequest;
use App\Http\Requests\UpdateWidgetRequest;
use App\Models\Widget;
use App\Models\Plan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ChannelController extends Controller
{
    public function index()
    {
        $chatbots = auth()->user()->widgets()->with('knowledgeBase')->get();
        $plan = auth()->user()->plan;

        return view('channels.index', compact('chatbots', 'plan'));
    }

    public function create()
    {
        $user = auth()->user();
        $plan = $user->plan;

        // Check plan limits
        if ($plan && $user->widgets()->count() >= $plan->max_widgets) {
            return redirect()->route('channels.index')
                ->with('error', 'You have reached your plan limit. Upgrade to create more channels.');
        }

        // Get user's AI Agents
        $aiAgents = $user->aiAgents()->where('is_active', true)->get();

        return view('channels.create', compact('aiAgents'));
    }

    public function store(StoreWidgetRequest $request)
    {
        $validated = $request->validated();

        $user = auth()->user();
        $plan = $user->plan;

        // Check plan limits again
        if ($plan && $user->widgets()->count() >= $plan->max_widgets) {
            return redirect()->route('channels.index')
                ->with('error', 'You have reached your plan limit.');
        }

        // The linked agent's ownership is enforced by StoreWidgetRequest.
        $aiAgentId = $validated['ai_agent_id'] ?? null;

        // Create widget
        $widget = $user->widgets()->create([
            'ai_agent_id' => $aiAgentId,
            'name' => Str::slug($validated['display_name']),
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
            'slug' => 'widget-' . $user->id . '-' . Str::random(8),
            'is_active' => true,
            'status' => 'draft',
        ]);

        // Create knowledge base (only if no AI Agent)
        // If linked to AI Agent, the agent's knowledge base will be used
        if (!$aiAgentId) {
            $widget->knowledgeBase()->create([
                'company_name' => $validated['display_name'],
                'persona_name' => 'AI Assistant',
                'persona_tone' => 'friendly',
            ]);
        }

        return redirect()->route('channels.edit', $widget->id)
            ->with('success', 'Chatbot created successfully! Now configure your chatbot.');
    }

    public function edit($chatbotId, $tab = 'general')
    {
        $chatbot = auth()->user()->widgets()->with('knowledgeBase')->findOrFail($chatbotId);
        Gate::authorize('view', $chatbot);
        // The 'knowledge' tab stays available for widgets with their own
        // (agent-less) knowledge base; linked widgets show the agent banner.
        $validTabs = ['general', 'knowledge', 'widget', 'lead', 'domains', 'webhook', 'analytics', 'embed'];

        if (!in_array($tab, $validTabs)) {
            $tab = 'general';
        }

        return view('channels.edit', compact('chatbot', 'tab'));
    }

    public function update(UpdateWidgetRequest $request, $chatbotId)
    {
        $chatbot = auth()->user()->widgets()->findOrFail($chatbotId);
        Gate::authorize('update', $chatbot);
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

        // Handle Allowed Domains settings (dedicated tab)
        if ($tab === 'domains') {
            $validated = $request->validated();
            $settings = $chatbot->settings ?? [];
            $settings['allowed_domains'] = $validated['allowed_domains'] ?? null;
            $chatbot->update(['settings' => $settings]);

            return redirect()->back()->with('success', 'Domain yang diizinkan berhasil disimpan!');
        }

        // Handle Webhook settings
        if ($tab === 'webhook') {
            $settings = $chatbot->settings ?? [];
            $settings['webhook_url'] = $request->input('webhook_url');
            $settings['webhook_secret'] = $request->input('webhook_secret');

            $chatbot->update(['settings' => $settings]);

            return redirect()->back()->with('success', 'Webhook settings saved!');
        }

        // Default: General tab (input validated by UpdateWidgetRequest,
        // including ownership of the linked agent)
        $validated = $request->validated();

        // Handle AI Agent change
        $newAgentId = $validated['ai_agent_id'] ?? null;
        $oldAgentId = $chatbot->ai_agent_id;

        // If unlinking agent (was linked, now null), create widget's own KB
        if ($oldAgentId && !$newAgentId && !$chatbot->knowledgeBase) {
            $chatbot->knowledgeBase()->create([
                'company_name' => $chatbot->display_name,
                'persona_name' => 'AI Assistant',
                'persona_tone' => 'friendly',
            ]);
        }

        $settings = $chatbot->settings ?? [];
        $settings['allowed_domains'] = $validated['allowed_domains'] ?? $settings['allowed_domains'] ?? null;

        $newStatus = $validated['status'] ?? $chatbot->status;

        // Plan limit: activating a channel cannot exceed plan->max_widgets active channels
        if ($newStatus === 'active' && $chatbot->status !== 'active') {
            if ($error = $this->guardActiveLimit($chatbot)) {
                return redirect()->back()->with('error', $error);
            }
        }

        $chatbot->update([
            'display_name' => $validated['display_name'] ?? $chatbot->display_name,
            'description' => $validated['description'] ?? $chatbot->description,
            'status' => $newStatus,
            'ai_agent_id' => $newAgentId,
            'settings' => $settings,
        ]);

        // Different success messages
        if ($oldAgentId !== $newAgentId) {
            if ($newAgentId) {
                $agentName = auth()->user()->aiAgents()->find($newAgentId)->name ?? 'Unknown';
                return redirect()->back()->with('success', "Widget berhasil dihubungkan ke AI Agent \"{$agentName}\"!");
            } else {
                return redirect()->back()->with('success', 'Widget sekarang menggunakan Knowledge Base sendiri.');
            }
        }

        return redirect()->back()->with('success', 'Chatbot updated successfully!');
    }


    public function destroy($chatbotId)
    {
        $chatbot = auth()->user()->widgets()->findOrFail($chatbotId);
        Gate::authorize('delete', $chatbot);
        $chatbot->delete();

        return redirect()->route('channels.index')
            ->with('success', 'Chatbot deleted successfully!');
    }

    /**
     * Reactivate an inactive channel (plan allows max_widgets active channels).
     */
    public function activate($chatbotId)
    {
        $chatbot = auth()->user()->widgets()->findOrFail($chatbotId);
        Gate::authorize('update', $chatbot);

        if ($chatbot->status === 'active') {
            return redirect()->route('channels.index')->with('success', 'Channel sudah aktif.');
        }

        if ($error = $this->guardActiveLimit($chatbot)) {
            return redirect()->route('channels.index')->with('error', $error);
        }

        $chatbot->update(['status' => 'active', 'is_active' => true]);

        return redirect()->route('channels.index')
            ->with('success', 'Channel "' . ($chatbot->display_name ?? $chatbot->name) . '" berhasil diaktifkan!');
    }

    /**
     * Enforce plan->max_widgets as the maximum number of ACTIVE channels.
     * Returns an error message when the limit would be exceeded, null otherwise.
     */
    private function guardActiveLimit(Widget $widget): ?string
    {
        $user = auth()->user();
        $plan = $user->plan;
        $maxActive = $plan?->max_widgets ?? 1;

        $otherActive = $user->widgets()
            ->where('status', 'active')
            ->where('id', '!=', $widget->id)
            ->count();

        if ($otherActive >= $maxActive) {
            return 'Paket ' . ($plan->name ?? 'Free') . " hanya mendukung {$maxActive} channel aktif. "
                . 'Nonaktifkan channel lain terlebih dahulu atau upgrade paket Anda.';
        }

        return null;
    }

    /**
     * Unlink AI Agent from widget and create its own knowledge base
     */
    public function unlinkAgent($chatbotId)
    {
        $chatbot = auth()->user()->widgets()->findOrFail($chatbotId);
        Gate::authorize('update', $chatbot);

        if (!$chatbot->ai_agent_id) {
            return redirect()->back()->with('error', 'Widget tidak terhubung ke AI Agent.');
        }

        // Unlink AI Agent
        $chatbot->update(['ai_agent_id' => null]);

        // Create widget's own knowledge base
        if (!$chatbot->knowledgeBase) {
            $chatbot->knowledgeBase()->create([
                'company_name' => $chatbot->display_name,
                'persona_name' => 'AI Assistant',
                'persona_tone' => 'friendly',
            ]);
        }

        return redirect()->route('channels.edit.tab', [$chatbot->id, 'knowledge'])
            ->with('success', 'Koneksi AI Agent berhasil diputus. Widget sekarang memiliki Knowledge Base sendiri.');
    }
}


