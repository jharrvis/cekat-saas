<?php

namespace Tests\Feature;

use App\Models\AiAgent;
use App\Models\KnowledgeBase;
use App\Models\Plan;
use App\Models\User;
use App\Models\Widget;
use App\Services\Chat\WebhookActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Fase B: real-time WooCommerce order-status checks inside the chat.
 * The LLM emits a check_status action; the orchestrator calls the
 * store webhook synchronously with the visitor's captured contact for
 * ownership verification, and the store's answer becomes the reply.
 */
class OrderStatusChatTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_URL = 'https://toko.test/wp-json/cekat/v1/webhook';

    private function makeStack(): Widget
    {
        $plan = Plan::create([
            'name' => 'Pro',
            'slug' => 'pro-order',
            'max_messages_per_month' => 1000,
            'ai_tier' => 'basic',
            'can_export_leads' => true,
        ]);

        $user = User::create([
            'name' => 'Owner',
            'email' => 'owner-order@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
            'plan_id' => $plan->id,
        ]);

        $agent = AiAgent::create([
            'user_id' => $user->id,
            'name' => 'CS Agent',
            'slug' => 'cs-order',
        ]);

        KnowledgeBase::create([
            'ai_agent_id' => $agent->id,
            'company_name' => 'Toko Uji',
        ]);

        return Widget::create([
            'user_id' => $user->id,
            'ai_agent_id' => $agent->id,
            'name' => 'Widget Uji',
            'slug' => 'w-order',
            'settings' => [
                'webhook_url' => self::WEBHOOK_URL,
                'webhook_secret' => 'whsec-test',
            ],
        ]);
    }

    /**
     * Route all outbound HTTP through one fake: the LLM replays the
     * given replies in order; the store webhook answers with $webhookBody.
     */
    private function fakeAll(array $llmReplies, array|int $webhookBody): void
    {
        $turn = 0;

        Http::fake(function (Request $request) use (&$turn, $llmReplies, $webhookBody) {
            if (str_contains($request->url(), 'openrouter.ai')) {
                $reply = $llmReplies[min($turn, count($llmReplies) - 1)];
                $turn++;

                return Http::response([
                    'choices' => [['message' => ['content' => $reply]]],
                    'usage' => ['total_tokens' => 10],
                ], 200);
            }

            if (str_contains($request->url(), 'toko.test')) {
                return is_int($webhookBody)
                    ? Http::response(['error' => 'boom'], $webhookBody)
                    : Http::response($webhookBody, 200);
            }

            return Http::response([], 404);
        });
    }

    private function chat(string $message, string $sessionId): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/chat', [
            'message' => $message,
            'widgetId' => 'w-order',
            'history' => [],
            'sessionId' => $sessionId,
        ], ['Origin' => 'https://toko.test']);
    }

    private function orderPayload(): array
    {
        return [
            'success' => true,
            'order' => [
                'id' => 123,
                'number' => '123',
                'status' => 'processing',
                'date' => '9 Okt 2026 10:15',
                'items' => [
                    ['name' => 'Kopi Arabika 200g', 'qty' => 2, 'total' => 'Rp170.000'],
                ],
                'total' => 'Rp185.000',
                'payment_method' => 'Transfer Bank',
                'paid' => true,
                'tracking' => 'JNE-8839201',
            ],
        ];
    }

    public function test_order_status_is_answered_with_live_store_data(): void
    {
        Mail::fake();
        $this->makeStack();

        $this->fakeAll([
            'Siap Kak, datanya saya catat ya.',
            '{"action": "check_status", "reference_id": "123"}',
        ], $this->orderPayload());

        // Turn 1: the visitor shares their contact (captured as lead).
        // The widget continues with the session id the API hands back.
        $first = $this->chat('Halo, email saya budi@test.id, HP 081298765432', 'sess-order-1');
        $first->assertOk();
        $sessionId = $first->json('sessionId') ?: 'sess-order-1';

        // Turn 2: order check - the store's live answer becomes the reply.
        $response = $this->chat('Cek status pesanan 123 dong', $sessionId);

        $response->assertOk();
        $reply = $response->json('response');
        $this->assertStringContainsString('Status pesanan #123', $reply);
        $this->assertStringContainsString('Sedang diproses', $reply);
        $this->assertStringContainsString('Kopi Arabika 200g', $reply);
        $this->assertStringContainsString('Rp185.000', $reply);
        $this->assertStringContainsString('JNE-8839201', $reply);
        $this->assertStringNotContainsString('check_status', $reply);

        // The store received the verification contact from the session.
        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'toko.test')
                && $request['action'] === 'check_status'
                && $request['reference_id'] === '123'
                && $request['email'] === 'budi@test.id';
        });
    }

    public function test_verification_required_asks_for_checkout_contact(): void
    {
        Mail::fake();
        $this->makeStack();

        $this->fakeAll([
            '{"action": "check_status", "reference_id": "123"}',
        ], ['success' => false, 'error' => 'verification_required']);

        $response = $this->chat('Cek pesanan 123', 'sess-order-2');

        $response->assertOk();
        $this->assertStringContainsString('email atau nomor HP', $response->json('response'));
    }

    public function test_verification_failed_uses_privacy_safe_message(): void
    {
        Mail::fake();
        $this->makeStack();

        $this->fakeAll([
            '{"action": "check_status", "reference_id": "123"}',
        ], ['success' => false, 'error' => 'verification_failed']);

        $response = $this->chat('Cek pesanan 123', 'sess-order-3');

        $response->assertOk();
        $reply = $response->json('response');
        $this->assertStringContainsString('tidak ditemukan', $reply);
        $this->assertStringNotContainsString('Kopi', $reply);
    }

    public function test_webhook_failure_falls_back_to_generic_message(): void
    {
        Mail::fake();
        $this->makeStack();

        $this->fakeAll([
            '{"action": "check_status", "reference_id": "123"}',
        ], 500);

        $response = $this->chat('Cek pesanan 123', 'sess-order-4');

        $response->assertOk()
            ->assertJsonPath('response', 'Data berhasil diproses.');
    }

    public function test_check_status_without_webhook_url_returns_null(): void
    {
        $widget = $this->makeStack();
        $widget->update(['settings' => []]);

        $service = app(WebhookActionService::class);

        $this->assertNull($service->dispatchCheckStatus(
            $widget,
            ['action' => 'check_status', 'reference_id' => '123'],
            'budi@test.id',
            null,
        ));
    }
}
