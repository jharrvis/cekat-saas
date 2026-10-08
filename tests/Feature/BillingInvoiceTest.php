<?php

namespace Tests\Feature;

use App\Mail\PaymentSuccess;
use App\Models\Plan;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Billing invoices + Quick Actions honesty: every successful payment
 * yields a downloadable PDF invoice (also attached to the success
 * email), and the billing page must not contain dead "#" actions.
 */
class BillingInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private function successfulTx(User $user, array $overrides = []): Transaction
    {
        $plan = Plan::where('slug', 'pro')->first() ?? Plan::create([
            'name' => 'Pro', 'slug' => 'pro', 'price' => 99000,
        ]);

        return Transaction::create(array_merge([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'order_id' => 'CEKAT-TEST-' . uniqid(),
            'amount' => 99000,
            'payment_type' => 'bank_transfer',
            'status' => 'success',
            'paid_at' => now(),
        ], $overrides));
    }

    public function test_owner_can_download_own_invoice_pdf(): void
    {
        $user = User::factory()->create();
        $tx = $this->successfulTx($user);

        $response = $this->actingAs($user)->get(route('billing.invoice.download', $tx));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_invoice_download_hides_other_users_and_unpaid_transactions(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $tx = $this->successfulTx($owner);
        $pending = $this->successfulTx($owner, ['status' => 'pending', 'paid_at' => null]);

        $this->actingAs($stranger)->get(route('billing.invoice.download', $tx))->assertNotFound();
        $this->actingAs($owner)->get(route('billing.invoice.download', $pending))->assertNotFound();
    }

    public function test_download_all_invoices_combines_successful_ones(): void
    {
        $user = User::factory()->create();
        $this->successfulTx($user);
        $this->successfulTx($user);

        $response = $this->actingAs($user)->get(route('billing.invoices.download_all'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_download_all_without_invoices_redirects_with_message(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('billing.invoices.download_all'))
            ->assertRedirect(route('billing'))
            ->assertSessionHas('error');
    }

    public function test_payment_success_email_carries_invoice_attachment(): void
    {
        $user = User::factory()->create();
        $tx = $this->successfulTx($user);

        $mail = new PaymentSuccess($user, $tx);
        $attachments = $mail->attachments();

        $this->assertCount(1, $attachments);
    }

    public function test_billing_page_has_no_dead_quick_actions(): void
    {
        $user = User::factory()->create();
        $this->successfulTx($user);

        $response = $this->actingAs($user)->get(route('billing'));

        $response->assertOk();
        $response->assertDontSee('Kelola Metode Bayar');
        $response->assertDontSee('Batalkan Langganan');
        $response->assertSee(route('billing.invoices.download_all'), false);
        $response->assertSee('Unduh Invoice');
    }

    public function test_invoice_number_is_derived_from_order_id(): void
    {
        $user = User::factory()->create();
        $tx = $this->successfulTx($user, ['order_id' => 'CEKAT-20261007-E7C6FF']);

        $this->assertStringEndsWith('/CEKAT-20261007-E7C6FF', \App\Services\Billing\InvoiceService::number($tx));
    }
}
