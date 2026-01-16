<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\LlmModel;
use Illuminate\Support\Facades\Http;

class ModelsManager extends Component
{
    public $models = [];
    public $editingModel = null;
    public $showForm = false;

    // Form fields
    public $model_id = '';
    public $name = '';
    public $provider = '';
    public $description = '';
    public $input_price = 0;
    public $output_price = 0;
    public $context_length = 4096;
    public $allowed_tiers = [];
    public $is_active = true;
    public $popularity = 50;

    protected $rules = [
        'model_id' => 'required|string',
        'name' => 'required|string|max:255',
        'provider' => 'required|string|max:100',
        'description' => 'nullable|string',
        'input_price' => 'required|numeric|min:0',
        'output_price' => 'required|numeric|min:0',
        'context_length' => 'required|integer|min:1024',
        'allowed_tiers' => 'array',
        'is_active' => 'boolean',
        'popularity' => 'required|integer|min:0|max:100',
    ];

    public function mount()
    {
        $this->loadModels();
    }

    public function loadModels()
    {
        $this->models = LlmModel::orderBy('popularity', 'desc')->get();
    }

    public function create()
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit($id)
    {
        $model = LlmModel::find($id);
        if ($model) {
            $this->editingModel = $id;
            $this->model_id = $model->model_id;
            $this->name = $model->name;
            $this->provider = $model->provider;
            $this->description = $model->description;
            $this->input_price = $model->input_price;
            $this->output_price = $model->output_price;
            $this->context_length = $model->context_length;
            $this->allowed_tiers = $model->allowed_tiers ?? [];
            $this->is_active = $model->is_active;
            $this->popularity = $model->popularity;
            $this->showForm = true;
        }
    }

    public function save()
    {
        $this->validate();

        $data = [
            'model_id' => $this->model_id,
            'name' => $this->name,
            'provider' => $this->provider,
            'description' => $this->description,
            'input_price' => $this->input_price,
            'output_price' => $this->output_price,
            'context_length' => $this->context_length,
            'allowed_tiers' => $this->allowed_tiers,
            'is_active' => $this->is_active,
            'popularity' => $this->popularity,
        ];

        if ($this->editingModel) {
            LlmModel::find($this->editingModel)->update($data);
            session()->flash('message', 'Model updated successfully!');
        } else {
            LlmModel::create($data);
            session()->flash('message', 'Model added successfully!');
        }

        $this->resetForm();
        $this->loadModels();
    }

    public function delete($id)
    {
        LlmModel::find($id)?->delete();
        session()->flash('message', 'Model deleted!');
        $this->loadModels();
    }

    public function toggleActive($id)
    {
        $model = LlmModel::find($id);
        if ($model) {
            $model->update(['is_active' => !$model->is_active]);
            $this->loadModels();
        }
    }

    public function resetForm()
    {
        $this->editingModel = null;
        $this->showForm = false;
        $this->model_id = '';
        $this->name = '';
        $this->provider = '';
        $this->description = '';
        $this->input_price = 0;
        $this->output_price = 0;
        $this->context_length = 4096;
        $this->allowed_tiers = [];
        $this->is_active = true;
        $this->popularity = 50;
    }

    public function fetchFromOpenRouter()
    {
        try {
            $response = Http::get('https://openrouter.ai/api/v1/models');

            if ($response->successful()) {
                $apiModels = $response->json()['data'] ?? [];
                $imported = 0;

                foreach (array_slice($apiModels, 0, 20) as $apiModel) { // Import top 20
                    $existingModel = LlmModel::where('model_id', $apiModel['id'])->first();

                    if (!$existingModel) {
                        LlmModel::create([
                            'model_id' => $apiModel['id'],
                            'name' => $apiModel['name'] ?? $apiModel['id'],
                            'provider' => explode('/', $apiModel['id'])[0] ?? 'Unknown',
                            'description' => $apiModel['description'] ?? null,
                            'input_price' => ($apiModel['pricing']['prompt'] ?? 0) * 1000000,
                            'output_price' => ($apiModel['pricing']['completion'] ?? 0) * 1000000,
                            'context_length' => $apiModel['context_length'] ?? 4096,
                            'allowed_tiers' => ['business'],
                            'is_active' => false,
                            'popularity' => 50,
                        ]);
                        $imported++;
                    }
                }

                session()->flash('message', "Imported {$imported} new models from OpenRouter!");
                $this->loadModels();
            } else {
                session()->flash('error', 'Failed to fetch from OpenRouter API');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Fetch single model info from OpenRouter API based on model_id
     */
    public function fetchModelInfo()
    {
        if (empty($this->model_id)) {
            session()->flash('error', 'Please enter Model ID first');
            return;
        }

        try {
            $response = Http::get('https://openrouter.ai/api/v1/models');

            if ($response->successful()) {
                $apiModels = $response->json()['data'] ?? [];
                $found = null;

                foreach ($apiModels as $apiModel) {
                    if ($apiModel['id'] === $this->model_id) {
                        $found = $apiModel;
                        break;
                    }
                }

                if ($found) {
                    $this->name = $found['name'] ?? $found['id'];
                    $this->provider = explode('/', $found['id'])[0] ?? 'Unknown';
                    $this->description = $found['description'] ?? '';
                    $this->input_price = ($found['pricing']['prompt'] ?? 0) * 1000000;
                    $this->output_price = ($found['pricing']['completion'] ?? 0) * 1000000;
                    $this->context_length = $found['context_length'] ?? 4096;

                    session()->flash('message', 'Model info fetched successfully!');
                } else {
                    session()->flash('error', 'Model not found in OpenRouter. Please check the Model ID.');
                }
            } else {
                session()->flash('error', 'Failed to connect to OpenRouter API');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Error fetching model: ' . $e->getMessage());
        }
    }

    /**
     * Test if the model works by sending a simple request
     */
    public function testModel($modelId = null)
    {
        $testModelId = $modelId ?? $this->model_id;

        if (empty($testModelId)) {
            session()->flash('error', 'No model ID to test');
            return;
        }

        try {
            $apiKey = config('services.openrouter.api_key');
            if (empty($apiKey)) {
                session()->flash('error', 'OpenRouter API key not configured');
                return;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'HTTP-Referer' => config('app.url'),
                'X-Title' => 'Cekat.biz.id Model Test',
            ])->timeout(30)->post('https://openrouter.ai/api/v1/chat/completions', [
                        'model' => $testModelId,
                        'messages' => [
                            ['role' => 'user', 'content' => 'Say "Hello, I am working!" in one sentence.']
                        ],
                        'max_tokens' => 50,
                    ]);

            if ($response->successful()) {
                $result = $response->json();
                $reply = $result['choices'][0]['message']['content'] ?? 'No response';
                session()->flash('test_result', [
                    'success' => true,
                    'model' => $testModelId,
                    'response' => $reply,
                ]);
            } else {
                $error = $response->json()['error']['message'] ?? 'Unknown error';
                session()->flash('test_result', [
                    'success' => false,
                    'model' => $testModelId,
                    'error' => $error,
                ]);
            }
        } catch (\Exception $e) {
            session()->flash('test_result', [
                'success' => false,
                'model' => $testModelId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.admin.models-manager');
    }
}
