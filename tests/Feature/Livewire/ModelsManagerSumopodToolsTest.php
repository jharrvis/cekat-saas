<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Admin\ModelsManager;
use App\Models\LlmModel;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ModelsManagerSumopodToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_testing_a_sumopod_model_hits_sumopod_not_openrouter(): void
    {
        Setting::set('ai_sumopod_api_key', 'sk-test', 'string', 'ai');
        Http::fake([
            'ai.sumopod.com/*' => Http::response([
                'choices' => [['message' => ['role' => 'assistant', 'content' => 'Hello, I am working!']]],
            ], 200),
            'openrouter.ai/*' => Http::response(['error' => ['message' => 'wrong gateway']], 500),
        ]);

        Livewire::test(ModelsManager::class)
            ->call('testModel', 'kimi-k2-5-260127')
            ->assertSee('Hello, I am working!');

        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'openrouter.ai'));
    }

    public function test_testing_a_sumopod_model_without_key_reports_configuration_error(): void
    {
        Http::fake();

        Livewire::test(ModelsManager::class)
            ->call('testModel', 'kimi-k2-5-260127')
            ->assertSee('API key SumoPod belum diatur');

        Http::assertNothingSent();
    }

    public function test_fetch_from_sumopod_imports_the_accounts_catalogue(): void
    {
        Setting::set('ai_sumopod_api_key', 'sk-test', 'string', 'ai');
        Http::fake([
            'ai.sumopod.com/v1/models' => Http::response(['data' => [
                ['id' => 'new-model-1', 'name' => 'New Model One'],
                ['id' => 'kimi-k2-5-260127'], // already seeded as sumopod -> updated
                ['id' => 'openrouter/free'], // belongs to another provider -> skipped
            ]], 200),
        ]);

        Livewire::test(ModelsManager::class)
            ->call('fetchFromSumopod')
            ->assertSee('1 model SumoPod baru ditambahkan');

        $this->assertDatabaseHas('llm_models', [
            'model_id' => 'new-model-1',
            'provider' => 'sumopod',
            'is_active' => true,
        ]);
        // The colliding id keeps its original provider.
        $this->assertSame('OpenRouter', LlmModel::where('model_id', 'openrouter/free')->value('provider'));
    }

    public function test_fetch_from_sumopod_without_key_does_not_call_the_api(): void
    {
        Http::fake();

        Livewire::test(ModelsManager::class)
            ->call('fetchFromSumopod')
            ->assertSee('API key SumoPod belum diatur');

        Http::assertNothingSent();
    }

    public function test_search_and_provider_filter_narrow_the_list(): void
    {
        $component = Livewire::test(ModelsManager::class);

        $component->set('providerFilter', 'sumopod');
        $models = $component->get('models');
        $this->assertCount(6, $models);
        $this->assertTrue($models->every(fn ($m) => $m->provider === 'sumopod'));

        $component->set('providerFilter', '');
        $component->set('search', 'kimi');
        $models = $component->get('models');
        $this->assertGreaterThanOrEqual(1, $models->count());
        $this->assertTrue($models->every(fn ($m) => str_contains(strtolower($m->name.' '.$m->model_id), 'kimi')));
    }
}
