<?php

namespace Tests\Feature;

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Remediation T-16: `plans:audit` is the operational anti-drift guard for
 * the plans table (root cause of F-01). It must pass on canonical data
 * and fail loudly (exit 1 + the drifted field) on drifted data.
 */
class PlansAuditTest extends TestCase
{
    use RefreshDatabase;

    private function seedCanonicalPlans(): void
    {
        Plan::create([
            'name' => 'Starter', 'slug' => 'starter', 'price' => 0, 'sort_order' => 1,
            'can_export_leads' => false, 'ai_tier' => 'basic', 'chat_history_days' => 7,
            'features' => ['custom_branding' => false, 'analytics' => 'basic', 'priority_support' => false, 'api_access' => false],
        ]);
        Plan::create([
            'name' => 'Pro', 'slug' => 'pro', 'price' => 99000, 'sort_order' => 2,
            'can_export_leads' => true, 'ai_tier' => 'advanced', 'chat_history_days' => 30,
            'features' => ['custom_branding' => true, 'analytics' => 'advanced', 'priority_support' => false, 'api_access' => true],
        ]);
        Plan::create([
            'name' => 'Business', 'slug' => 'business', 'price' => 199000, 'sort_order' => 3,
            'can_export_leads' => true, 'ai_tier' => 'premium', 'chat_history_days' => 90,
            'features' => ['custom_branding' => true, 'analytics' => 'advanced', 'priority_support' => true, 'api_access' => true, 'white_label' => true],
        ]);
    }

    public function test_audit_passes_on_canonical_plans(): void
    {
        $this->seedCanonicalPlans();

        $exit = Artisan::call('plans:audit');
        $this->assertSame(0, $exit);
        $this->assertStringContainsString('OK', Artisan::output());
    }

    public function test_audit_fails_and_names_the_drift(): void
    {
        $this->seedCanonicalPlans();
        // Recreate the F-01 drift: Pro leads silently off + wrong price.
        Plan::where('slug', 'pro')->update(['can_export_leads' => false, 'price' => 198996]);

        $exit = Artisan::call('plans:audit');
        $this->assertSame(1, $exit);
        $output = Artisan::output();
        $this->assertStringContainsString('pro.can_export_leads', $output);
        $this->assertStringContainsString('pro.price', $output);
    }
}
