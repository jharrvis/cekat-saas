<?php

namespace Tests\Feature;

use App\Models\EmailLog;
use App\Models\Plan;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Remediation T-03 / finding F-03: plan activation must be driven by the
 * Midtrans webhook, not by the customer's browser returning to the finish
 * page. Hardening covered here: the webhook verifies signature_key (it
 * never did), processes the verified payload directly instead of the SDK
 * Notification round-trip (a live status API call whose failure used to
 * 500 the webhook and leave activation to the browser return), activation
 * is idempotent against duplicate notifications, and the billing page can
 * poll a transaction's status.
 */
class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SERVER_KEY = 'test-server-key';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.midtrans.server_key' => self::SERVER_KEY]);
    }

    private function makePendingPurchase(int $amount = 99000): array
    {
        $starter = Plan::create(['name' => 'Starter', 'slug' => 'starter-' . uniqid(), 'price' => 29000]);
        $pro = Plan::create(['name' => 'Pro', 'slug' => 'pro-' . uniqid(), 'price' => $amount]);

        $user = User::create([
            'name' => 'Pembeli',
            'email' => 'pembeli-' . uniqid() . '@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
            'plan_id' => $starter->id,
        ]);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'plan_id' => $pro->id,
            'order_id' => 'CEKAT-' . strtoupper(uniqid()),
            'amount' => $amount,
            'status' => 'pending',
        ]);

        return [$user, $pro, $transaction];
    }

    private function settlementPayload(Transaction $transaction, ?string $signature = null): array
    {
        $grossAmount = number_format((float) $transaction->amount, 2, '.', '');

        return [
            'order_id' => $transaction->order_id,
            'status_code' => '200',
            'gross_amount' => $grossAmount,
            'transaction_status' => 'settlement',
            'payment_type' => 'bank_transfer',
            'signature_key' => $signature ?? hash('sha512', $transaction->order_id . '200' . $grossAmount . self::SERVER_KEY),
        ];
    }

    public function test_webhook_settlement_activates_plan_without_browser_return(): void
    {
        [$user, $pro, $transaction] = $this->makePendingPurchase();

        $this->postJson('/api/payment/notification', $this->settlementPayload($transaction))
            ->assertOk()
            ->assertJson(['status' => 'success']);

        $this->assertSame('success', $transaction->fresh()->status);
        $user->refresh();
        $this->assertSame($pro->id, $user->plan_id);
        $this->assertNotNull($user->plan_expires_at);
        $this->assertTrue($user->plan_expires_at->isFuture());
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        [$user, $pro, $transaction] = $this->makePendingPurchase();

        $this->postJson('/api/payment/notification', $this->settlementPayload($transaction, 'signature-yang-salah'))
            ->assertForbidden();

        $this->assertSame('pending', $transaction->fresh()->status);
        $this->assertNotSame($pro->id, $user->fresh()->plan_id);
    }

    public function test_duplicate_settlement_webhook_activates_only_once(): void
    {
        [$user, $pro, $transaction] = $this->makePendingPurchase();
        $payload = $this->settlementPayload($transaction);

        $this->postJson('/api/payment/notification', $payload)->assertOk();
        $expiryAfterFirst = $user->fresh()->plan_expires_at;

        $this->postJson('/api/payment/notification', $payload)->assertOk();

        $user->refresh();
        $this->assertSame(
            $expiryAfterFirst->toDateTimeString(),
            $user->plan_expires_at->toDateTimeString(),
            'Webhook duplikat tidak boleh memperpanjang masa aktif dua kali'
        );
        $this->assertSame(1, EmailLog::where('recipient', $user->email)->where('category', 'payment')->count());
    }

    public function test_webhook_unknown_order_returns_404(): void
    {
        [, , $transaction] = $this->makePendingPurchase();
        $payload = $this->settlementPayload($transaction);
        $payload['order_id'] = 'CEKAT-TIDAK-ADA';
        $payload['signature_key'] = hash('sha512', 'CEKAT-TIDAK-ADA' . '200' . $payload['gross_amount'] . self::SERVER_KEY);

        $this->postJson('/api/payment/notification', $payload)->assertNotFound();
    }

    public function test_transaction_status_endpoint_is_owner_only(): void
    {
        [$user, , $transaction] = $this->makePendingPurchase();
        $stranger = User::create([
            'name' => 'Orang Lain',
            'email' => 'lain-' . uniqid() . '@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->getJson(route('billing.transaction.status', $transaction))
            ->assertOk()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('order_id', $transaction->order_id);

        $this->actingAs($stranger)
            ->getJson(route('billing.transaction.status', $transaction))
            ->assertForbidden();
    }
}
