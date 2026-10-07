<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Remediation T-11 / finding F-14 (code part): until the merchant display
 * name in the Midtrans dashboard is aligned with the brand, the billing
 * page must disclose the name buyers will see in the Snap popup so the
 * mismatch never surprises a paying customer.
 */
class BillingMerchantNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_page_discloses_midtrans_merchant_name(): void
    {
        $plan = Plan::create(['name' => 'Starter', 'slug' => 'starter', 'price' => 0, 'is_active' => true]);
        $user = User::create([
            'name' => 'Pemilik', 'email' => 'pemilik-' . uniqid() . '@test.id',
            'password' => 'secret123', 'email_verified_at' => now(), 'plan_id' => $plan->id,
        ]);

        $this->actingAs($user)
            ->get(route('billing'))
            ->assertOk()
            ->assertSee('Pembayaran diproses dengan aman oleh Midtrans atas nama MCImedia.');
    }
}
