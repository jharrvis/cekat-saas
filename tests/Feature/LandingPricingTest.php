<?php

namespace Tests\Feature;

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Landing pricing must be rendered from the plans table (single source of
 * truth) - billing (PaymentController) charges plans.price directly, so the
 * marketing page must never diverge from it.
 */
class LandingPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_renders_active_plan_prices_from_plans_table(): void
    {
        Plan::create([
            'name' => 'StarterContract',
            'slug' => 'starter-contract',
            'price' => 0,
            'max_widgets' => 1,
            'max_messages_per_month' => 100,
            'max_documents' => 3,
            'max_faqs' => 10,
            'features' => ['analytics' => 'basic'],
            'is_active' => true,
            'sort_order' => 1,
        ]);
        Plan::create([
            'name' => 'ProContract',
            'slug' => 'pro-contract',
            'price' => 123456,
            'max_widgets' => 3,
            'max_messages_per_month' => 2000,
            'max_documents' => 20,
            'max_faqs' => 50,
            'features' => [
                'analytics' => true,
                'api_access' => true,
                'custom_branding' => true,
                'priority_support' => true,
            ],
            'is_active' => true,
            'sort_order' => 2,
        ]);
        Plan::create([
            'name' => 'HiddenContract',
            'slug' => 'hidden-contract',
            'price' => 999999,
            'is_active' => false,
            'sort_order' => 3,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('StarterContract');
        $response->assertSee('ProContract');
        $response->assertSee('Rp0');
        $response->assertSee('Rp123.456');
        $response->assertSee('100 Pesan / bulan');
        $response->assertSee('2.000 Pesan / bulan');
        $response->assertSee('Analitik Lanjutan');
        $response->assertSee('Akses API');
        $response->assertDontSee('HiddenContract');
        $response->assertDontSee('Rp999.999');
    }

    public function test_landing_price_follows_plan_price_change(): void
    {
        Plan::create([
            'name' => 'DynamicContract',
            'slug' => 'dynamic-contract',
            'price' => 50000,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->get('/')->assertOk()->assertSee('Rp50.000');

        Plan::where('slug', 'dynamic-contract')->update(['price' => 765432]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Rp765.432')
            ->assertDontSee('Rp50.000');
    }
}
