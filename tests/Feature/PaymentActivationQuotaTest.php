<?php

namespace Tests\Feature;

use App\Http\Controllers\PaymentController;
use App\Models\Plan;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Remediation T-04 / finding F-04: activating a new plan mid-cycle must not
 * reset the monthly message counter. Previously activatePlan() zeroed
 * monthly_message_used, so upgrading handed out a fresh full quota.
 * The only legitimate reset is the monthly ResetMonthlyQuota schedule.
 */
class PaymentActivationQuotaTest extends TestCase
{
    use RefreshDatabase;

    private function activate(Transaction $transaction): void
    {
        $controller = app(PaymentController::class);
        $method = new \ReflectionMethod($controller, 'activatePlan');
        $method->setAccessible(true);
        $method->invoke($controller, $transaction);
    }

    public function test_upgrade_does_not_reset_monthly_message_usage(): void
    {
        $starter = Plan::create(['name' => 'Starter', 'slug' => 'starter', 'price' => 29000]);
        $pro = Plan::create(['name' => 'Pro', 'slug' => 'pro', 'price' => 99000]);

        $user = User::create([
            'name' => 'Pembeli',
            'email' => 'pembeli-' . uniqid() . '@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
            'plan_id' => $starter->id,
            'monthly_message_used' => 1500,
        ]);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'plan_id' => $pro->id,
            'order_id' => 'CEKAT-TEST-' . uniqid(),
            'amount' => 99000,
            'status' => 'pending',
        ]);

        $this->activate($transaction);

        $user->refresh();
        $this->assertSame($pro->id, $user->plan_id);
        $this->assertSame(1500, (int) $user->monthly_message_used, 'Kuota terpakai tidak boleh ter-reset saat upgrade');
        $this->assertNotNull($user->plan_expires_at);
        $this->assertSame('success', $transaction->fresh()->status);
    }

    public function test_renewal_extends_expiry_and_keeps_usage(): void
    {
        $pro = Plan::create(['name' => 'Pro', 'slug' => 'pro', 'price' => 99000]);

        $user = User::create([
            'name' => 'Langganan',
            'email' => 'langganan-' . uniqid() . '@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
            'plan_id' => $pro->id,
            'plan_expires_at' => now()->addDays(10),
            'monthly_message_used' => 700,
        ]);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'plan_id' => $pro->id,
            'order_id' => 'CEKAT-TEST-' . uniqid(),
            'amount' => 99000,
            'status' => 'pending',
        ]);

        $this->activate($transaction);

        $user->refresh();
        $this->assertSame(700, (int) $user->monthly_message_used);
        $this->assertTrue($user->plan_expires_at->greaterThan(now()->addDays(35)), 'Perpanjangan harus menambah masa aktif dari tanggal kedaluwarsa lama');
    }
}
