<?php

namespace Tests\Unit\Chat;

use App\Services\Chat\ChatOrchestrator;
use App\Services\Chat\DomainAccessService;
use App\Services\Chat\LeadCaptureService;
use App\Services\Chat\ModelResolver;
use App\Services\Chat\PromptBuilder;
use App\Services\Chat\QuotaService;
use App\Services\Chat\SessionIdService;
use App\Services\Chat\WebhookActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatOrchestratorFallbackTest extends TestCase
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
        return [
            'choices' => [
                ['message' => ['role' => 'assistant', 'content' => $content]],
            ],
            'usage' => ['total_tokens' => 7],
        ];
    }

    public function test_retired_model_is_retried_with_free_router(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::sequence()
                ->push(['error' => ['message' => 'Model not found']], 404)
                ->push(self::completion('Halo!'), 200),
        ]);

        $result = $this->orchestrator()->callPublic('system', [['role' => 'user', 'content' => 'hi']], 'retired/model:free');

        $this->assertSame(ModelResolver::FALLBACK_MODEL, $result['model']);
        $this->assertSame('Halo!', $result['body']['choices'][0]['message']['content']);

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request['model'] === ModelResolver::FALLBACK_MODEL);
    }

    public function test_auth_errors_do_not_trigger_model_fallback(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response(['error' => ['message' => 'Invalid API key']], 401),
        ]);

        $result = $this->orchestrator()->callPublic('system', [], 'openrouter/free');

        $this->assertSame('openrouter/free', $result['model']);
        $this->assertArrayHasKey('error', $result['body']);
        Http::assertSentCount(1);
    }

    public function test_empty_content_is_retried_once(): void
    {
        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::sequence()
                ->push(self::completion(''), 200)
                ->push(self::completion('Halo!'), 200),
        ]);

        $result = $this->orchestrator()->callPublic('system', [], 'openrouter/free');

        $this->assertSame('Halo!', $result['body']['choices'][0]['message']['content']);
        Http::assertSentCount(2);
    }
}
