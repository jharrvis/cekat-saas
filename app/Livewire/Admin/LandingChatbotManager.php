<?php

namespace App\Livewire\Admin;

use App\Models\LlmModel;
use App\Models\Widget;
use App\Models\Setting;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class LandingChatbotManager extends Component
{
    use WithFileUploads;

    public $widget;
    public $selectedModel = '';
    public $testResult = null;
    public $testLoading = false;

    // Widget Settings
    public $widgetName = '';
    public $greeting = '';
    public $primaryColor = '#0f172a';
    public $position = 'bottom-right';
    public $subtitle = '';
    public $placeholder = '';

    // Avatar Settings
    public $avatarType = 'icon';
    public $avatarIcon = 'robot';
    public $avatarUrl = '';
    public $avatarUpload = null;

    public function mount(Widget $widget)
    {
        $this->widget = $widget;

        $settings = $this->widget->settings ?? [];
        $this->selectedModel = $settings['model'] ?? 'openai/gpt-4o-mini';
        $this->widgetName = $this->widget->name;
        $this->greeting = $settings['greeting'] ?? 'Halo! 👋 Ada yang bisa saya bantu?';
        $this->primaryColor = $settings['primary_color'] ?? '#0f172a';
        $this->position = $settings['position'] ?? 'bottom-right';
        $this->subtitle = $settings['subtitle'] ?? 'Online • Reply cepat';
        $this->placeholder = $settings['placeholder'] ?? 'Ketik pesan...';
        $this->avatarType = $settings['avatar_type'] ?? 'icon';
        $this->avatarIcon = $settings['avatar_icon'] ?? 'robot';
        $this->avatarUrl = $settings['avatar_url'] ?? '';
    }

    public function selectModel($modelId)
    {
        $this->selectedModel = $modelId;
        $this->testResult = null;
    }

    public function saveModel()
    {
        $settings = $this->widget->settings ?? [];
        $settings['model'] = $this->selectedModel;
        $this->widget->update(['settings' => $settings]);

        session()->flash('message', 'Model saved successfully!');
    }

    public function uploadAvatar()
    {
        $this->validate([
            'avatarUpload' => 'image|max:2048', // 2MB max
        ]);

        if ($this->avatarUpload) {
            // Delete old avatar if exists
            if ($this->avatarUrl && Storage::disk('public')->exists(str_replace('/storage/', '', $this->avatarUrl))) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $this->avatarUrl));
            }

            $path = $this->avatarUpload->store('avatars', 'public');
            $this->avatarUrl = '/storage/' . $path;
            $this->avatarType = 'url';
            $this->avatarUpload = null;

            $this->saveSettings();
            session()->flash('message', 'Avatar uploaded successfully!');
        }
    }

    public function selectAvatarIcon($icon)
    {
        $this->avatarIcon = $icon;
        $this->avatarType = 'icon';
    }

    public function saveSettings()
    {
        $settings = $this->widget->settings ?? [];
        $settings['greeting'] = $this->greeting;
        $settings['primary_color'] = $this->primaryColor;
        $settings['position'] = $this->position;
        $settings['subtitle'] = $this->subtitle;
        $settings['placeholder'] = $this->placeholder;
        $settings['avatar_type'] = $this->avatarType;
        $settings['avatar_icon'] = $this->avatarIcon;
        $settings['avatar_url'] = $this->avatarUrl;

        $this->widget->update([
            'name' => $this->widgetName,
            'settings' => $settings,
        ]);

        session()->flash('message', 'Widget settings saved successfully!');
    }

    public function testModel($modelId = null)
    {
        $testModelId = $modelId ?? $this->selectedModel;

        if (empty($testModelId)) {
            $this->testResult = [
                'success' => false,
                'error' => 'No model selected',
            ];
            return;
        }

        try {
            $apiKey = config('services.openrouter.api_key');
            if (empty($apiKey)) {
                $this->testResult = [
                    'success' => false,
                    'error' => 'OpenRouter API key not configured',
                ];
                return;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'HTTP-Referer' => config('app.url'),
                'X-Title' => 'Cekat.biz.id Model Test',
            ])->timeout(30)->post('https://openrouter.ai/api/v1/chat/completions', [
                        'model' => $testModelId,
                        'messages' => [
                            ['role' => 'user', 'content' => 'Say "Hello, I am working!" in one short sentence.']
                        ],
                        'max_tokens' => 50,
                    ]);

            if ($response->successful()) {
                $result = $response->json();
                $reply = $result['choices'][0]['message']['content'] ?? 'No response';
                $this->testResult = [
                    'success' => true,
                    'model' => $testModelId,
                    'response' => $reply,
                ];
            } else {
                $error = $response->json()['error']['message'] ?? 'Unknown error';
                $this->testResult = [
                    'success' => false,
                    'model' => $testModelId,
                    'error' => $error,
                ];
            }
        } catch (\Exception $e) {
            $this->testResult = [
                'success' => false,
                'model' => $testModelId,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function render()
    {
        $models = LlmModel::where('is_active', true)
            ->orderBy('popularity', 'desc')
            ->get();

        return view('livewire.admin.landing-chatbot-manager', [
            'models' => $models,
        ]);
    }
}
