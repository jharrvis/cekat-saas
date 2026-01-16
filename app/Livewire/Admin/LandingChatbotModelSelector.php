<?php

namespace App\Livewire\Admin;

use App\Models\LlmModel;
use App\Models\Widget;
use Livewire\Component;
use Illuminate\Support\Facades\Http;

class LandingChatbotModelSelector extends Component
{
    public $widget;
    public $selectedModel = '';
    public $testResult = null;

    public function mount(Widget $widget)
    {
        $this->widget = $widget;
        $settings = $widget->settings ?? [];
        $this->selectedModel = $settings['model'] ?? 'openai/gpt-4o-mini';
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

        session()->flash('model_saved', 'Model saved successfully!');
    }

    public function testModel()
    {
        if (empty($this->selectedModel)) {
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
                        'model' => $this->selectedModel,
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
                    'model' => $this->selectedModel,
                    'response' => $reply,
                ];
            } else {
                $error = $response->json()['error']['message'] ?? 'Unknown error';
                $this->testResult = [
                    'success' => false,
                    'model' => $this->selectedModel,
                    'error' => $error,
                ];
            }
        } catch (\Exception $e) {
            $this->testResult = [
                'success' => false,
                'model' => $this->selectedModel,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function render()
    {
        $models = LlmModel::where('is_active', true)
            ->orderBy('popularity', 'desc')
            ->get();

        return view('livewire.admin.landing-chatbot-model-selector', [
            'models' => $models,
        ]);
    }
}
