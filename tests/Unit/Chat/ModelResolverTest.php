<?php

namespace Tests\Unit\Chat;

use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use App\Models\Widget;
use App\Services\Chat\ModelResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelResolverTest extends TestCase
{
    use RefreshDatabase;

    private ModelResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        cache()->flush();
        $this->resolver = new ModelResolver;
    }

    private function makePlan(string $tier): Plan
    {
        return Plan::create([
            'name' => 'Plan '.ucfirst($tier),
            'slug' => 'plan-'.$tier.'-'.uniqid(),
            'price' => 0,
            'billing_period' => 'monthly',
            'max_widgets' => 1,
            'max_messages_per_month' => 100,
            'ai_tier' => $tier,
            'is_active' => true,
        ]);
    }

    private function makeWidgetFor(?Plan $plan, array $settings = []): Widget
    {
        $user = User::factory()->create([
            'plan_id' => $plan?->id,
            'status' => 'active',
        ]);

        return Widget::create([
            'user_id' => $user->id,
            'name' => 'Test Widget',
            'slug' => 'test-widget-'.uniqid(),
            'settings' => $settings,
            'is_active' => true,
            'status' => 'active',
        ]);
    }

    public function test_null_widget_returns_configured_default(): void
    {
        $this->assertSame(
            config('services.openrouter.default_model'),
            $this->resolver->forWidget(null),
        );
    }

    public function test_landing_widget_uses_its_own_configured_model(): void
    {
        $widget = Widget::create([
            'user_id' => null,
            'name' => 'Landing Widget',
            'slug' => \App\Services\Chat\QuotaService::LANDING_SLUG,
            'settings' => ['model' => 'openai/gpt-4o'],
            'is_active' => true,
            'status' => 'active',
        ]);

        $this->assertSame('openai/gpt-4o', $this->resolver->forWidget($widget));
    }

    public function test_plan_ai_tier_mapping_from_settings_wins(): void
    {
        Setting::set('ai_tier_mapping', [
            'basic' => 'openrouter/free',
            'standard' => 'openai/gpt-4o-mini',
        ], 'json', 'api');

        $widget = $this->makeWidgetFor($this->makePlan('standard'));

        $this->assertSame('openai/gpt-4o-mini', $this->resolver->forWidget($widget));
    }

    public function test_basic_tier_falls_back_to_free_router_without_mapping(): void
    {
        Setting::where('key', 'ai_tier_mapping')->delete();
        cache()->flush();

        $widget = $this->makeWidgetFor($this->makePlan('basic'));

        $this->assertSame(ModelResolver::FALLBACK_MODEL, $this->resolver->forWidget($widget));
    }

    public function test_user_without_plan_uses_fallback_tier_mapping(): void
    {
        Setting::set('ai_tier_mapping', [
            'basic' => 'fallback/model:free',
        ], 'json', 'api');
        cache()->flush();

        $widget = $this->makeWidgetFor(null);

        $this->assertSame('fallback/model:free', $this->resolver->forWidget($widget));
    }

    public function test_user_without_plan_and_without_mapping_returns_default(): void
    {
        Setting::where('key', 'ai_tier_mapping')->delete();
        cache()->flush();

        $widget = $this->makeWidgetFor(null);

        $this->assertSame(
            config('services.openrouter.default_model'),
            $this->resolver->forWidget($widget),
        );
    }

    public function test_unknown_tier_returns_default_model(): void
    {
        $widget = $this->makeWidgetFor($this->makePlan('custom-tier'));

        $this->assertSame(
            config('services.openrouter.default_model'),
            $this->resolver->forWidget($widget),
        );
    }
}
