<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Widget;

class WidgetCustomizer extends Component
{
    public $widget;

    // Widget Settings
    public $name = '';
    public $primaryColor = '#0f172a';
    public $greeting = '';
    public $position = 'bottom-right';

    // Embed Code
    public $embedCode = '';

    public $widgetId = null;

    public function mount($widgetId = null)
    {
        $this->widgetId = $widgetId;

        // If widgetId is provided, use that widget
        if ($widgetId) {
            $this->widget = Widget::find($widgetId);
        } else {
            // Fallback to user's first widget
            $this->widget = auth()->user()->widgets()->first();
        }

        if (!$this->widget) {
            $this->widget = auth()->user()->widgets()->create([
                'name' => 'My Widget',
                'slug' => 'widget-' . auth()->id(),
                'is_active' => true,
            ]);
        }

        $this->loadWidget();
        $this->generateEmbedCode();
    }

    public function loadWidget()
    {
        $this->name = $this->widget->name;
        $settings = $this->widget->settings ?? [];

        $this->primaryColor = $settings['color'] ?? '#0f172a';
        $this->greeting = $settings['greeting'] ?? 'Halo! 👋 Ada yang bisa saya bantu?';
        $this->position = $settings['position'] ?? 'bottom-right';
    }

    public function saveSettings()
    {
        $this->validate([
            'name' => 'required|max:255',
            'primaryColor' => 'required',
            'greeting' => 'required|max:500',
            'position' => 'required|in:bottom-right,bottom-left,top-right,top-left',
        ]);

        $this->widget->update([
            'name' => $this->name,
            'settings' => [
                'color' => $this->primaryColor,
                'greeting' => $this->greeting,
                'position' => $this->position,
                // Model is handled separately in Model Selection tab
                'model' => $this->widget->settings['model'] ?? 'nvidia/llama-3.1-nemotron-70b-instruct:free',
            ],
        ]);

        $this->generateEmbedCode();
        session()->flash('message', 'Widget settings saved successfully!');
    }

    public function generateEmbedCode()
    {
        $url = config('app.url');
        $widgetSlug = $this->widget->slug;

        $this->embedCode = <<<HTML
<!-- Cekat.biz.id Widget -->
<script>
  window.CSAIConfig = {
    widgetId: '{$widgetSlug}',
    apiUrl: '{$url}/api/chat',
    position: '{$this->position}',
    primaryColor: '{$this->primaryColor}',
    title: '{$this->name}',
    greeting: '{$this->greeting}',
    showBranding: true
  };
</script>
<script src="{$url}/widget/widget.min.js"></script>
HTML;
    }

    public function updatedPrimaryColor()
    {
        $this->generateEmbedCode();
    }

    public function updatedPosition()
    {
        $this->generateEmbedCode();
    }

    public function updatedGreeting()
    {
        $this->generateEmbedCode();
    }

    public function render()
    {
        return view('livewire.widget-customizer');
    }
}
