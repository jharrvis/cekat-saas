<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\Chat\ApiLookupService;
use App\Services\Chat\ChatOrchestrator;
use App\Services\Chat\DomainAccessService;
use App\Services\Chat\LeadCaptureService;
use App\Services\Chat\ModelResolver;
use App\Services\Chat\PromptBuilder;
use App\Services\Chat\QuotaService;
use App\Services\Chat\SessionIdService;
use App\Services\Chat\WebhookActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Provider failover at the orchestrator level: SumoPod first when
 * enabled, OpenRouter tier model next, openrouter/free always last.
 */
class ChatProviderFailoverTest extends TestCase
{
    use RefreshDatabase;

    private function orchestrator(): ChatOrchestrator
    {
        return new class (
            app(DomainAccessService::class),
            app(QuotaService::class),
            app(PromptBuilder::class),
            app(ModelResolver::class),
            app(LeadCaptureService::class),
            app(WebhookActionService::class),
            app(ApiLookupService::class),
            app(SessionIdService::class),
        ) extends ChatOrchestrator {
            public function callPublic(string $prompt, array $messages, string $model): array
            {
                return $this->callOpenRouter($prompt, $messages, $model);
            }
        };
    }

    private static function completion(string $content): array
    {
        return ['choices' => [['message' => ['role' => 'assistant', 'content' => $content]]]];
    }

    private function enableSumopod(): void
    {
        Setting::set('ai_sumopod_enabled', true, 'boolean', 'ai');
        Setting::set('ai_sumopod_api_key', 'sk-test-key', 'string', 'ai');
        Setting::set('ai_sumopod_model', 'sumo-model-1', 'string', 'ai');
    }

    public function test_sumopod_answers_first_and_openrouter_is_never_called(): void
    {
        $this->enableSumopod();
        Http::fake([
            'ai.sumopod.com/*' => Http::response(self::completion('Dari SumoPod'), 200),
            'openrouter.ai/*' => Http::response(self::completion('Dari OpenRouter'), 200),
        ]);

        $result = $this->orchestrator()->callPublic('system', [['role' => 'user', 'content' => 'hi']], 'tier/model');

        $this->assertSame('sumo-model-1', $result['model']);
        $this->assertSame('Dari SumoPod', $result['body']['choices'][0]['message']['content']);
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'openrouter.ai'));
    }

    public function test_sumopod_server_error_fails_over_to_openrouter_tier_model(): void
    {
        $this->enableSumopod();
        Http::fake([
            'ai.sumopod.com/*' => Http::response(['error' => ['message' => 'boom']], 500),
            'openrouter.ai/*' => Http::response(self::completion('Dari OpenRouter'), 200),
        ]);

        $result = $this->orchestrator()->callPublic('system', [['role' => 'user', 'content' => 'hi']], 'tier/model');

        $this->assertSame('tier/model', $result['model']);
        $this->assertSame('Dari OpenRouter', $result['body']['choices'][0]['message']['content']);
    }

    public function test_sumopod_auth_error_fails_over_to_openrouter(): void
    {
        $this->enableSumopod();
        Http::fake([
            'ai.sumopod.com/*' => Http::response(['error' => ['message' => 'invalid key']], 401),
            'openrouter.ai/*' => Http::response(self::completion('Dari OpenRouter'), 200),
        ]);

        $result = $this->orchestrator()->callPublic('system', [['role' => 'user', 'content' => 'hi']], 'tier/model');

        $this->assertSame('tier/model', $result['model']);
    }

    public function test_all_providers_off_goes_straight_to_free_router(): void
    {
        Setting::set('ai_openrouter_enabled', false, 'boolean', 'ai');
        Setting::set('ai_sumopod_enabled', false, 'boolean', 'ai');
        Http::fake([
            'openrouter.ai/*' => Http::response(self::completion('Dari Free Router'), 200),
        ]);

        $result = $this->orchestrator()->callPublic('system', [['role' => 'user', 'content' => 'hi']], 'tier/model');

        $this->assertSame(ModelResolver::FALLBACK_MODEL, $result['model']);
        Http::assertSentCount(1);
    }

    public function test_openrouter_auth_error_still_stops_the_chain(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response(['error' => ['message' => 'bad key']], 401),
        ]);

        $result = $this->orchestrator()->callPublic('system', [['role' => 'user', 'content' => 'hi']], 'tier/model');

        $this->assertSame('tier/model', $result['model']);
        $this->assertSame('bad key', $result['body']['error']['message']);
        Http::assertSentCount(1);
    }
}
