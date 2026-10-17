<?php

namespace Tests\Unit\Chat;

use App\Models\Setting;
use App\Services\Chat\LlmProviderChain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LlmProviderChainTest extends TestCase
{
    use RefreshDatabase;

    private function enableSumopod(): void
    {
        Setting::set('ai_sumopod_enabled', true, 'boolean', 'ai');
        Setting::set('ai_sumopod_api_key', 'sk-test-key', 'string', 'ai');
        Setting::set('ai_sumopod_model', 'sumo-model-1', 'string', 'ai');
    }

    public function test_default_attempts_are_tier_model_then_free_router(): void
    {
        $attempts = app(LlmProviderChain::class)->attemptsFor('some/model');

        $this->assertSame([
            ['provider' => 'openrouter', 'model' => 'some/model'],
            ['provider' => 'openrouter', 'model' => 'openrouter/free'],
        ], $attempts);
    }

    public function test_free_router_tier_model_is_not_duplicated(): void
    {
        $attempts = app(LlmProviderChain::class)->attemptsFor('openrouter/free');

        $this->assertSame([
            ['provider' => 'openrouter', 'model' => 'openrouter/free'],
        ], $attempts);
    }

    public function test_sumopod_is_cross_fallback_for_openrouter_targets(): void
    {
        $this->enableSumopod();

        $attempts = app(LlmProviderChain::class)->attemptsFor('some/model');

        $this->assertSame([
            ['provider' => 'openrouter', 'model' => 'some/model'],
            ['provider' => 'sumopod', 'model' => 'sumo-model-1'],
            ['provider' => 'openrouter', 'model' => 'openrouter/free'],
        ], $attempts);
    }

    public function test_sumopod_tier_target_goes_first(): void
    {
        Setting::set('ai_sumopod_enabled', true, 'boolean', 'ai');
        Setting::set('ai_sumopod_api_key', 'sk-test-key', 'string', 'ai');

        $attempts = app(LlmProviderChain::class)->attemptsFor('sumopod:sumo-model-9');

        $this->assertSame([
            ['provider' => 'sumopod', 'model' => 'sumo-model-9'],
            ['provider' => 'openrouter', 'model' => 'openrouter/free'],
        ], $attempts);
    }

    public function test_sumopod_tier_target_uses_global_model_as_within_provider_fallback(): void
    {
        $this->enableSumopod();

        $attempts = app(LlmProviderChain::class)->attemptsFor('sumopod:sumo-model-9');

        $this->assertSame([
            ['provider' => 'sumopod', 'model' => 'sumo-model-9'],
            ['provider' => 'sumopod', 'model' => 'sumo-model-1'],
            ['provider' => 'openrouter', 'model' => 'openrouter/free'],
        ], $attempts);
    }

    public function test_sumopod_tier_target_without_ready_provider_goes_straight_to_free_router(): void
    {
        $attempts = app(LlmProviderChain::class)->attemptsFor('sumopod:sumo-model-9');

        $this->assertSame([
            ['provider' => 'openrouter', 'model' => 'openrouter/free'],
        ], $attempts);
    }

    public function test_parse_target_distinguishes_providers(): void
    {
        $chain = app(LlmProviderChain::class);

        $this->assertSame(['sumopod', 'kimi-k2-5-260127'], $chain->parseTarget('sumopod:kimi-k2-5-260127'));
        $this->assertSame(['openrouter', 'google/gemma-3-4b-it:free'], $chain->parseTarget('google/gemma-3-4b-it:free'));
    }

    public function test_sumopod_without_key_or_model_is_skipped(): void
    {
        Setting::set('ai_sumopod_enabled', true, 'boolean', 'ai');

        $attempts = app(LlmProviderChain::class)->attemptsFor('some/model');

        $this->assertSame('openrouter', $attempts[0]['provider']);
    }

    public function test_all_providers_off_still_ends_with_free_router(): void
    {
        Setting::set('ai_openrouter_enabled', false, 'boolean', 'ai');
        Setting::set('ai_sumopod_enabled', false, 'boolean', 'ai');

        $attempts = app(LlmProviderChain::class)->attemptsFor('some/model');

        $this->assertSame([
            ['provider' => 'openrouter', 'model' => 'openrouter/free'],
        ], $attempts);
    }

    public function test_openrouter_off_with_sumopod_on_skips_tier_model(): void
    {
        Setting::set('ai_openrouter_enabled', false, 'boolean', 'ai');
        $this->enableSumopod();

        $attempts = app(LlmProviderChain::class)->attemptsFor('some/model');

        $this->assertSame([
            ['provider' => 'sumopod', 'model' => 'sumo-model-1'],
            ['provider' => 'openrouter', 'model' => 'openrouter/free'],
        ], $attempts);
    }

    public function test_post_targets_sumopod_endpoint_with_its_key(): void
    {
        $this->enableSumopod();
        Http::fake(['*' => Http::response(['choices' => []], 200)]);

        app(LlmProviderChain::class)->post(
            ['provider' => 'sumopod', 'model' => 'sumo-model-1'],
            [['role' => 'user', 'content' => 'hi']],
        );

        Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://ai.sumopod.com/v1/chat/completions')
            && $request->hasHeader('Authorization', 'Bearer sk-test-key')
            && $request['model'] === 'sumo-model-1');
    }

    public function test_post_targets_openrouter_endpoint(): void
    {
        Http::fake(['*' => Http::response(['choices' => []], 200)]);

        app(LlmProviderChain::class)->post(
            ['provider' => 'openrouter', 'model' => 'some/model'],
            [['role' => 'user', 'content' => 'hi']],
        );

        Http::assertSent(fn (Request $request) => $request->url() === 'https://openrouter.ai/api/v1/chat/completions'
            && $request['model'] === 'some/model');
    }
}
