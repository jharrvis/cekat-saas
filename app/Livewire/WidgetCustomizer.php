<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Widget;
use Livewire\WithFileUploads;

class WidgetCustomizer extends Component
{
    use WithFileUploads;
    public $widget;

    // Widget Settings
    public $name = '';
    public $primaryColor = '#0f172a';
    public $greeting = '';
    public $position = 'bottom-right';

    // Avatar Settings
    public $avatarType = 'icon'; // 'icon' or 'image'
    public $avatarIcon = 'robot';
    public $avatarUrl = '';
    public $avatarUpload;

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
                'display_name' => 'My Widget',
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

        $this->avatarType = $settings['avatar_type'] ?? 'icon';
        $this->avatarIcon = $settings['avatar_icon'] ?? 'robot';
        $this->avatarUrl = $settings['avatar_url'] ?? '';
    }

    public function saveSettings()
    {
        $this->validate([
            'name' => 'required|max:255',
            'primaryColor' => 'required',
            'greeting' => 'required|max:500',
            'position' => 'required|in:bottom-right,bottom-left,top-right,top-left',
            'avatarUpload' => 'nullable|image|max:1024', // 1MB Max
        ]);

        // Handle Avatar Upload
        if ($this->avatarUpload) {
            $path = $this->avatarUpload->store('avatars', 'public');
            $this->avatarUrl = '/storage/' . $path;
            $this->avatarType = 'image';
            // Reset so a later Save (e.g. after switching back to an icon)
            // does not re-store the same file and force type=image again.
            $this->avatarUpload = null;
        }

        // Merge - never replace. Rebuilding the array from scratch used to wipe
        // allowed_domains (domain restriction silently fail-opened), subtitle,
        // placeholder, lead_* and webhook_* on every save.
        $settings = $this->widget->settings ?? [];
        $settings['color'] = $this->primaryColor;
        $settings['greeting'] = $this->greeting;
        $settings['position'] = $this->position;
        $settings['avatar_type'] = $this->avatarType;
        $settings['avatar_icon'] = $this->avatarIcon;
        $settings['avatar_url'] = $this->avatarUrl;
        $settings['model'] = $settings['model'] ?? config('services.openrouter.default_model');

        $this->widget->update([
            'name' => $this->name,
            'settings' => $settings,
        ]);

        $this->generateEmbedCode();
        session()->flash('message', __('livewire.s.flash_widget_settings_saved'));
    }

    public function generateEmbedCode()
    {
        $url = config('app.url');
        $widgetSlug = $this->widget->slug;

        $this->embedCode = <<<HTML
<!-- Cekat.biz.id Chatbot Widget -->
<script>
  window.CSAIConfig = {
    widgetId: '{$widgetSlug}'
  };
</script>
<script src="{$url}/widget/widget.min.js?v=20260930-p6" async></script>
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
