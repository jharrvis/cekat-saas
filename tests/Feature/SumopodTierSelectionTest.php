<?php

namespace Tests\Feature;

use App\Livewire\Admin\AiTierManager;
use App\Models\LlmModel;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SumopodTierSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sumopod_models_are_seeded_into_the_catalogue(): void
    {
        $this->assertGreaterThanOrEqual(6, LlmModel::where('provider', 'sumopod')->count());
        $this->assertTrue(LlmModel::where('model_id', 'kimi-k2-5-260127')->where('provider', 'sumopod')->exists());
    }

    public function test_tier_manager_offers_sumopod_options_and_saves_prefixed_value(): void
    {
        Livewire::test(AiTierManager::class)
            ->assertSee('sumopod:kimi-k2-5-260127', false)
            ->set('tierMapping.standard', 'sumopod:kimi-k2-5-260127')
            ->call('save');

        $mapping = Setting::get('ai_tier_mapping');
        $mapping = is_array($mapping) ? $mapping : json_decode($mapping, true);

        $this->assertSame('sumopod:kimi-k2-5-260127', $mapping['standard']);
    }
}
