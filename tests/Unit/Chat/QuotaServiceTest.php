<?php

namespace Tests\Unit\Chat;

use App\Models\Plan;
use App\Models\User;
use App\Services\Chat\QuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotaServiceTest extends TestCase
{
    use RefreshDatabase;

    private QuotaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QuotaService;
    }

    private function makePlan(int $limit = 100): Plan
    {
        return Plan::create([
            'name' => 'Quota Plan',
            'slug' => 'quota-plan-'.uniqid(),
            'price' => 0,
            'billing_period' => 'monthly',
            'max_widgets' => 1,
            'max_messages_per_month' => $limit,
            'ai_tier' => 'basic',
            'is_active' => true,
        ]);
    }

    private function makeUser(int $used, int $limit, string $status = 'active'): User
    {
        $plan = $this->makePlan($limit);

        return User::factory()->create([
            'plan_id' => $plan->id,
            'status' => $status,
            'monthly_message_used' => $used,
        ]);
    }

    public function test_landing_widget_bypasses_all_checks(): void
    {
        $this->assertNull($this->service->check(null, QuotaService::LANDING_SLUG));
        $this->assertNull($this->service->check($this->makeUser(999, 100), QuotaService::LANDING_SLUG));
    }

    public function test_missing_owner_returns_404_owner_missing(): void
    {
        $denied = $this->service->check(null, 'some-widget');

        $this->assertSame(404, $denied['status']);
        $this->assertSame('owner_missing', $denied['body']['error_code']);
    }

    public function test_suspended_and_banned_accounts_are_blocked(): void
    {
        foreach (['suspended', 'banned'] as $status) {
            $denied = $this->service->check($this->makeUser(0, 100, $status), 'some-widget');

            $this->assertSame(403, $denied['status'], $status);
            $this->assertSame('account_suspended', $denied['body']['error_code'], $status);
        }
    }

    public function test_exhausted_quota_returns_429_with_usage_payload(): void
    {
        $denied = $this->service->check($this->makeUser(100, 100), 'some-widget');

        $this->assertSame(429, $denied['status']);
        $this->assertSame('quota_exceeded', $denied['body']['error_code']);
        $this->assertSame(100, $denied['body']['quota']['used']);
        $this->assertSame(100, $denied['body']['quota']['limit']);
        $this->assertArrayHasKey('reset_date', $denied['body']['quota']);
    }

    public function test_request_under_quota_is_allowed(): void
    {
        $this->assertNull($this->service->check($this->makeUser(99, 100), 'some-widget'));
    }

    public function test_user_without_plan_is_allowed(): void
    {
        $user = User::factory()->create(['plan_id' => null, 'status' => 'active']);

        $this->assertNull($this->service->check($user, 'some-widget'));
    }

    public function test_consume_increments_usage_except_for_landing_widget(): void
    {
        $user = $this->makeUser(0, 100);

        $this->service->consume($user, 'some-widget');
        $this->assertSame(1, $user->fresh()->monthly_message_used);

        $this->service->consume($user, QuotaService::LANDING_SLUG);
        $this->assertSame(1, $user->fresh()->monthly_message_used);

        $this->service->consume(null, 'some-widget');
        $this->assertSame(1, $user->fresh()->monthly_message_used);
    }
}
