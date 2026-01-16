<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Widget;
use Illuminate\Support\Str;

class CreateChatbot extends Component
{
    public $display_name = '';
    public $description = '';

    protected $rules = [
        'display_name' => 'required|max:255',
        'description' => 'nullable|max:500',
    ];

    public function create()
    {
        $this->validate();

        // Check plan limits
        $user = auth()->user();
        $plan = $user->plan;

        if (!$plan || $user->widgets()->count() >= $plan->max_widgets) {
            session()->flash('error', 'You have reached your plan limit. Upgrade to create more chatbots.');
            return;
        }

        // Create widget
        $widget = $user->widgets()->create([
            'name' => Str::slug($this->display_name),
            'display_name' => $this->display_name,
            'description' => $this->description,
            'slug' => 'widget-' . $user->id . '-' . Str::random(8),
            'is_active' => true,
            'status' => 'draft',
        ]);

        // Create knowledge base
        $widget->knowledgeBase()->create([
            'company_name' => $this->display_name,
            'persona_name' => 'AI Assistant',
            'persona_tone' => 'friendly',
        ]);

        session()->flash('message', 'Chatbot created successfully!');

        return redirect()->route('chatbots.knowledge', $widget->id);
    }

    public function render()
    {
        return view('livewire.create-chatbot');
    }
}
