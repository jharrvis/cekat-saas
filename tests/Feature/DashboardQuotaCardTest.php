<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The dashboard already computed quota usage for its warning
 * banners, but never showed the remaining quota as a plain number
 * in the normal state, nor the subscription end date at all. The
 * subscription summary card covers both.
 */
class DashboardQuotaCardTest extends TestCase
{
    use RefreshDatabase;

    private function userOnPlan(array $planAttrs, array $userAttrs = []): User
    {
        $plan = Plan::create(array_merge([
            'name' => 'Pro',
            'slug' => 'pro-' . uniqid(),
            'price' => 99000,
            'max_messages_per_month' => 500,
            'max_agents' => 3,
            'max_documents' => 30,
        ], $planAttrs));

        return User::create(array_merge([
            'name' => 'Tenant',
            'email' => 'tenant-' . uniqid() . '@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
            'plan_id' => $plan->id,
            'monthly_message_used' => 120,
        ], $userAttrs));
    }

    public function test_dashboard_shows_remaining_quota_and_expiry_date(): void
    {
        $user = $this->userOnPlan([], ['plan_expires_at' => '2026-11-07 10:00:00']);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Pro')
            ->assertSee('380') // 500 - 120 remaining
            ->assertSee('7 November 2026');
    }

    public function test_dashboard_without_expiry_shows_no_end_date(): void
    {
        $user = $this->userOnPlan(['name' => 'Starter', 'price' => 0]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee(__('general.s.tidak_ada_tanggal_berakhir'));
    }
}
