<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Admin\ModelsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ModelsManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_fetch_from_openrouter_clamps_negative_sponsored_pricing(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response(['data' => [
                [
                    'id' => 'typesafe/jev-router',
                    'name' => 'Jev Router',
                    'description' => 'Sponsored model',
                    'pricing' => ['prompt' => '-1', 'completion' => '-1'],
                    'context_length' => 1000000,
                ],
                [
                    'id' => 'openai/gpt-test',
                    'name' => 'GPT Test',
                    'description' => 'Normal model',
                    'pricing' => ['prompt' => '0.0000015', 'completion' => '0.000006'],
                    'context_length' => 128000,
                ],
            ]]),
        ]);

        Livewire::test(ModelsManager::class)
            ->call('fetchFromOpenRouter')
            ->assertSee('Imported 2 new models from OpenRouter!');

        // Negative (provider-pays) pricing must be clamped to free, not stored.
        $this->assertDatabaseHas('llm_models', [
            'model_id' => 'typesafe/jev-router',
            'input_price' => 0,
            'output_price' => 0,
        ]);
        $this->assertDatabaseHas('llm_models', [
            'model_id' => 'openai/gpt-test',
            'input_price' => 1.5,
            'output_price' => 6.0,
        ]);
    }

    public function test_fetch_continues_past_broken_rows(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response(['data' => [
                ['name' => 'broken-row-without-id'],
                [
                    'id' => 'good/model',
                    'name' => 'Good',
                    'pricing' => ['prompt' => '0', 'completion' => '0'],
                    'context_length' => 8192,
                ],
            ]]),
        ]);

        Livewire::test(ModelsManager::class)
            ->call('fetchFromOpenRouter')
            ->assertSee('Imported 1 new models from OpenRouter!')
            ->assertSee('1 rows skipped');

        $this->assertDatabaseHas('llm_models', ['model_id' => 'good/model']);
    }

    public function test_fetch_model_info_clamps_negative_pricing(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/models' => Http::response(['data' => [
                [
                    'id' => 'typesafe/jev-router',
                    'name' => 'Jev Router',
                    'pricing' => ['prompt' => '-1', 'completion' => '-1'],
                    'context_length' => 1000000,
                ],
            ]]),
        ]);

        Livewire::test(ModelsManager::class)
            ->set('model_id', 'typesafe/jev-router')
            ->call('fetchModelInfo')
            ->assertSet('input_price', 0)
            ->assertSet('output_price', 0);
    }

    public function test_play_button_shows_responding_notification(): void
    {
        config(['services.openrouter.api_key' => 'test-key']);
        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => 'Hello, I am working!']]],
            ]),
        ]);

        // Same call as the per-row play button: testModel('<model_id>')
        Livewire::test(ModelsManager::class)
            ->call('testModel', 'openai/gpt-test')
            ->assertSee('active & responding')
            ->assertSee('Hello, I am working!');
    }

    public function test_play_button_shows_failure_notification(): void
    {
        config(['services.openrouter.api_key' => 'test-key']);
        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response(
                ['error' => ['message' => 'Model unavailable']],
                400
            ),
        ]);

        Livewire::test(ModelsManager::class)
            ->call('testModel', 'gone/model')
            ->assertSee('test failed, model not responding')
            ->assertSee('Model unavailable');
    }
}
