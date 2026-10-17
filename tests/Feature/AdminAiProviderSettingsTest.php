<?php

namespace Tests\Feature;

use App\Livewire\Admin\AiProviderSettings;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAiProviderSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_persists_provider_settings(): void
    {
        Livewire::test(AiProviderSettings::class)
            ->set('openrouterEnabled', false)
            ->set('sumopodEnabled', true)
            ->set('sumopodApiKey', 'sk-new-key')
            ->set('sumopodModel', 'sumo-model-1')
            ->set('sumopodBaseUrl', 'https://ai.sumopod.com/')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse(filter_var(Setting::get('ai_openrouter_enabled'), FILTER_VALIDATE_BOOLEAN));
        $this->assertTrue(filter_var(Setting::get('ai_sumopod_enabled'), FILTER_VALIDATE_BOOLEAN));
        $this->assertSame('sk-new-key', Setting::get('ai_sumopod_api_key'));
        $this->assertSame('sumo-model-1', Setting::get('ai_sumopod_model'));
        $this->assertSame('https://ai.sumopod.com', Setting::get('ai_sumopod_base_url'));
    }

    public function test_enabling_sumopod_without_key_or_model_is_rejected(): void
    {
        Livewire::test(AiProviderSettings::class)
            ->set('sumopodEnabled', true)
            ->call('save')
            ->assertHasErrors('sumopodApiKey');

        $this->assertFalse(filter_var(Setting::get('ai_sumopod_enabled', false), FILTER_VALIDATE_BOOLEAN));
    }

    public function test_empty_key_field_keeps_the_stored_key(): void
    {
        Setting::set('ai_sumopod_api_key', 'sk-stored', 'string', 'ai');
        Setting::set('ai_sumopod_model', 'sumo-model-1', 'string', 'ai');

        Livewire::test(AiProviderSettings::class)
            ->set('sumopodEnabled', true)
            ->set('sumopodApiKey', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('sk-stored', Setting::get('ai_sumopod_api_key'));
    }

    public function test_connection_test_reports_available_models(): void
    {
        Http::fake([
            'ai.sumopod.com/v1/models' => Http::response([
                'data' => [['id' => 'a'], ['id' => 'b'], ['id' => 'c']],
            ], 200),
        ]);

        Livewire::test(AiProviderSettings::class)
            ->set('sumopodApiKey', 'sk-probe')
            ->call('testConnection')
            ->assertSet('testOk', true)
            ->assertSee('3');
    }

    public function test_connection_test_reports_failure(): void
    {
        Http::fake([
            'ai.sumopod.com/v1/models' => Http::response(['error' => 'nope'], 401),
        ]);

        Livewire::test(AiProviderSettings::class)
            ->set('sumopodApiKey', 'sk-wrong')
            ->call('testConnection')
            ->assertSet('testOk', false);
    }
}
