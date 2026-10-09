<?php

namespace Tests\Feature;

use App\Models\AiAgent;
use App\Models\KnowledgeBase;
use App\Models\Plan;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Fase C: creating a WooCommerce order from the chat. The LLM emits a
 * create_order action; the orchestrator gates on buyer identity
 * (action payload, pre-chat form, or session lead data), guards
 * against duplicate submissions, calls the store synchronously, and
 * answers with the order number and the store's payment link.
 */
class CreateOrderChatTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_URL = 'https://toko.test/wp-json/cekat/v1/webhook';

    private function makeStack(): Widget
    {
        $plan = Plan::create([
            'name' => 'Pro',
            'slug' => 'pro-create',
            'max_messages_per_month' => 1000,
            'ai_tier' => 'basic',
            'can_export_leads' => true,
        ]);

        $user = User::create([
            'name' => 'Owner',
            'email' => 'owner-create@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
            'plan_id' => $plan->id,
        ]);

        $agent = AiAgent::create([
            'user_id' => $user->id,
            'name' => 'CS Agent',
            'slug' => 'cs-create',
        ]);

        KnowledgeBase::create([
            'ai_agent_id' => $agent->id,
            'company_name' => 'Toko Uji',
        ]);

        return Widget::create([
            'user_id' => $user->id,
            'ai_agent_id' => $agent->id,
            'name' => 'Widget Uji',
            'slug' => 'w-create',
            'settings' => [
                'webhook_url' => self::WEBHOOK_URL,
                'webhook_secret' => 'whsec-test',
            ],
        ]);
    }

    private function fakeAll(array $llmReplies, array|int $webhookBody, ?int &$webhookCalls = null): void
    {
        $turn = 0;
        $webhookCalls = 0;

        Http::fake(function (Request $request) use (&$turn, &$webhookCalls, $llmReplies, $webhookBody) {
            if (str_contains($request->url(), 'openrouter.ai')) {
                $reply = $llmReplies[min($turn, count($llmReplies) - 1)];
                $turn++;

                return Http::response([
                    'choices' => [['message' => ['content' => $reply]]],
                    'usage' => ['total_tokens' => 10],
                ], 200);
            }

            if (str_contains($request->url(), 'toko.test')) {
                $webhookCalls++;

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
            'widgetId' => 'w-create',
            'history' => [],
            'sessionId' => $sessionId,
        ], ['Origin' => 'https://toko.test']);
    }

    private function successBody(): array
    {
        return [
            'success' => true,
            'order' => [
                'id' => 124,
                'number' => '124',
                'status' => 'pending',
                'total' => 'Rp170.000',
                'payment_url' => 'https://toko.test/checkout/order-pay/124/?key=wc_order_abc',
            ],
        ];
    }

    private function seedContact(string $sessionSeed): string
    {
        $first = $this->chat('Halo, nama saya Budi, email budi@test.id, HP 081298765432', $sessionSeed);
        $first->assertOk();

        return $first->json('sessionId') ?: $sessionSeed;
    }

    public function test_order_is_created_and_payment_link_is_answered(): void
    {
        Mail::fake();
        $this->makeStack();

        $this->fakeAll([
            'Siap Kak, datanya saya catat ya.',
            '{"action": "create_order", "items": [{"product": "Kopi Arabika 200g", "qty": 2}]}',
        ], $this->successBody());

        $sessionId = $this->seedContact('sess-create-1');

        $response = $this->chat('Ya, pesan 2 Kopi Arabika ya', $sessionId);

        $response->assertOk();
        $reply = $response->json('response');
        $this->assertStringContainsString('Pesanan #124 berhasil dibuat', $reply);
        $this->assertStringContainsString('Rp170.000', $reply);
        $this->assertStringContainsString('order-pay/124', $reply);
        $this->assertStringNotContainsString('create_order', $reply);

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'toko.test')
                && $request['action'] === 'create_order'
                && $request['customer']['email'] === 'budi@test.id'
                && $request['customer']['name'] === 'Budi'
                && $request['items'][0]['product'] === 'Kopi Arabika 200g'
                && $request['items'][0]['qty'] === 2;
        });
    }

    public function test_missing_buyer_data_asks_first_and_never_calls_the_store(): void
    {
        Mail::fake();
        $this->makeStack();

        $calls = null;
        $this->fakeAll([
            '{"action": "create_order", "items": [{"product": "Kopi Arabika 200g", "qty": 1}]}',
        ], $this->successBody(), $calls);

        $response = $this->chat('Pesan kopi satu ya', 'sess-create-2');

        $response->assertOk();
        $this->assertStringContainsString('nama Anda dan email atau nomor HP', $response->json('response'));
        $this->assertSame(0, $calls);
    }

    public function test_unavailable_product_is_reported_back(): void
    {
        Mail::fake();
        $this->makeStack();

        $this->fakeAll([
            'Siap Kak, datanya saya catat ya.',
            '{"action": "create_order", "items": [{"product": "Kopi Robusta", "qty": 1}]}',
        ], ['success' => false, 'error' => 'product_unavailable', 'detail' => 'Kopi Robusta']);

        $sessionId = $this->seedContact('sess-create-3');

        $response = $this->chat('Pesan Kopi Robusta satu', $sessionId);

        $response->assertOk();
        $reply = $response->json('response');
        $this->assertStringContainsString('tidak bisa dipesan', $reply);
        $this->assertStringContainsString('Kopi Robusta', $reply);
    }

    public function test_repeated_submission_does_not_create_a_second_order(): void
    {
        Mail::fake();
        $this->makeStack();

        $calls = null;
        $action = '{"action": "create_order", "items": [{"product": "Kopi Arabika 200g", "qty": 2}]}';
        $this->fakeAll(['Siap Kak, datanya saya catat ya.', $action, $action], $this->successBody(), $calls);

        $sessionId = $this->seedContact('sess-create-4');

        $first = $this->chat('Ya, pesan 2 Kopi Arabika ya', $sessionId);
        $second = $this->chat('Ya, pesan 2 Kopi Arabika ya', $sessionId);

        $this->assertStringContainsString('order-pay/124', $first->json('response'));
        $this->assertStringContainsString('order-pay/124', $second->json('response'));
        $this->assertSame(1, $calls);
    }

    public function test_webhook_failure_falls_back_to_generic_message(): void
    {
        Mail::fake();
        $this->makeStack();

        $this->fakeAll([
            'Siap Kak, datanya saya catat ya.',
            '{"action": "create_order", "items": [{"product": "Kopi Arabika 200g", "qty": 1}]}',
        ], 500);

        $sessionId = $this->seedContact('sess-create-5');

        $response = $this->chat('Pesan kopi satu', $sessionId);

        $response->assertOk()
            ->assertJsonPath('response', 'Data berhasil diproses.');
    }
}
