<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Services\Billing\PlanLimitService;
use Database\Seeders\DefaultPlansSeeder;
use Database\Seeders\UpdateExistingPlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the canonical plan attributes (remediation T-01 / finding F-01).
 *
 * Production plan data once drifted from the product promise: paying Pro
 * users could not open Leads, stayed on the basic AI tier, and kept only
 * 7 days of chat history. This test builds the database the same way a
 * real install is reconciled (DefaultPlansSeeder, then the idempotent
 * UpdateExistingPlansSeeder) and asserts the attributes the owner has
 * fixed as canonical. If someone edits the seeders, this test fails
 * before production drifts again.
 */
class PlanIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function seedCanonicalPlans(): void
    {
        $this->seed(DefaultPlansSeeder::class);
        $this->seed(UpdateExistingPlansSeeder::class);
    }

    public function test_canonical_plan_attributes_after_reconciliation(): void
    {
        $this->seedCanonicalPlans();

        $starter = Plan::where('slug', 'starter')->firstOrFail();
        $this->assertFalse((bool) $starter->can_export_leads);
        $this->assertSame('basic', $starter->ai_tier);
        $this->assertSame(7, (int) $starter->chat_history_days);
        $this->assertFalse($starter->features['api_access'] ?? true);

        $pro = Plan::where('slug', 'pro')->firstOrFail();
        $this->assertSame(99000, (int) $pro->price);
        $this->assertTrue((bool) $pro->can_export_leads);
        $this->assertSame('advanced', $pro->ai_tier);
        $this->assertSame(30, (int) $pro->chat_history_days);
        $this->assertTrue($pro->features['api_access'] ?? false);

        $business = Plan::where('slug', 'business')->firstOrFail();
        $this->assertSame(199000, (int) $business->price);
        $this->assertTrue((bool) $business->can_export_leads);
        $this->assertSame('premium', $business->ai_tier);
        $this->assertSame(90, (int) $business->chat_history_days);
        $this->assertTrue($business->features['api_access'] ?? false);
    }

    public function test_reconciliation_seeder_is_idempotent(): void
    {
        $this->seedCanonicalPlans();

        // Running the reconciliation again must not change anything.
        $this->seed(UpdateExistingPlansSeeder::class);

        $pro = Plan::where('slug', 'pro')->firstOrFail();
        $this->assertSame(99000, (int) $pro->price);
        $this->assertTrue((bool) $pro->can_export_leads);
        $this->assertSame('advanced', $pro->ai_tier);
        $this->assertSame(30, (int) $pro->chat_history_days);

        $business = Plan::where('slug', 'business')->firstOrFail();
        $this->assertSame(199000, (int) $business->price);
        $this->assertSame(90, (int) $business->chat_history_days);
    }

    public function test_pro_plan_opens_leads_feature_gate(): void
    {
        $this->seedCanonicalPlans();

        $limits = app(PlanLimitService::class);

        $pro = Plan::where('slug', 'pro')->firstOrFail();
        $this->assertTrue($limits->feature($pro, 'leads'));

        $starter = Plan::where('slug', 'starter')->firstOrFail();
        $this->assertFalse($limits->feature($starter, 'leads'));
    }
}
