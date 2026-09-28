<?php

namespace Tests\Unit\Billing;

use App\Models\Plan;
use App\Models\User;
use App\Services\Billing\PlanLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PlanLimitServiceTest extends TestCase
{
    use RefreshDatabase;

    private PlanLimitService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PlanLimitService;
    }

    private function makePlan(array $attrs = []): Plan
    {
        static $seq = 0;
        $seq++;

        return Plan::create(array_merge([
            'name' => 'Plan '.$seq,
            'slug' => 'plan-'.$seq.'-'.uniqid(),
        ], $attrs));
    }

    private function makeUser(?Plan $plan, array $attrs = []): User
    {
        static $seq = 0;
        $seq++;

        return User::create(array_merge([
            'name' => 'User '.$seq,
            'email' => 'pluser-'.$seq.'-'.uniqid().'@test.id',
            'password' => 'secret123',
            'role' => 'user',
            'plan_id' => $plan?->id,
        ], $attrs));
    }

    public function test_reads_all_limits_from_plans_table(): void
    {
        $plan = $this->makePlan([
            'max_widgets' => 4,
            'max_messages_per_month' => 777,
            'max_documents' => 9,
            'max_file_size_mb' => 11,
            'max_faqs' => 22,
            'chat_history_days' => 45,
        ]);
        $user = $this->makeUser($plan);

        $expected = [
            'active_channels' => 4,
            'monthly_messages' => 777,
            'knowledge_documents' => 9,
            'file_size_mb' => 11,
            'faqs' => 22,
            'chat_history_days' => 45,
        ];

        foreach ($expected as $key => $value) {
            $this->assertSame($value, $this->service->limit($plan, $key), $key);
            $this->assertSame($value, $this->service->limit($user, $key), $key);
        }
    }

    public function test_unknown_limit_key_throws(): void
    {
        $plan = $this->makePlan();

        $this->expectException(InvalidArgumentException::class);
        $this->service->limit($plan, 'does_not_exist');
    }

    public function test_planless_user_falls_back_to_active_free_plan(): void
    {
        $this->makePlan(['price' => 299000, 'is_active' => true, 'sort_order' => 1, 'max_widgets' => 99]);
        $free = $this->makePlan([
            'price' => 0,
            'is_active' => true,
            'sort_order' => 5,
            'max_widgets' => 2,
            'max_messages_per_month' => 321,
        ]);
        $this->makePlan(['price' => 0, 'is_active' => false, 'sort_order' => 0, 'max_widgets' => 77]);

        $user = $this->makeUser(null);

        $plan = $this->service->planFor($user);

        $this->assertTrue($plan->is($free));
        $this->assertSame(2, $this->service->limit($user, 'active_channels'));
        $this->assertSame(321, $this->service->limit($user, 'monthly_messages'));
    }

    public function test_planless_user_without_any_plan_uses_schema_defaults(): void
    {
        $user = $this->makeUser(null);

        $this->assertSame(100, $this->service->limit($user, 'monthly_messages'));
        $this->assertSame(1, $this->service->limit($user, 'active_channels'));
        $this->assertSame(3, $this->service->limit($user, 'knowledge_documents'));
        $this->assertSame('basic', $this->service->aiTier($user));
        $this->assertFalse($this->service->feature($user, 'leads'));
    }

    public function test_feature_boolean_reads_columns_and_json(): void
    {
        $plan = $this->makePlan([
            'can_export_leads' => true,
            'can_use_whatsapp' => false,
            'features' => [
                'custom_branding' => true,
                'priority_support' => false,
                'api_access' => true,
                'white_label' => null,
                'analytics' => 'advanced',
            ],
        ]);
        $user = $this->makeUser($plan);

        $this->assertTrue($this->service->feature($user, 'leads'));
        $this->assertFalse($this->service->feature($user, 'whatsapp'));
        $this->assertTrue($this->service->feature($plan, 'custom_branding'));
        $this->assertFalse($this->service->feature($plan, 'priority_support'));
        $this->assertTrue($this->service->feature($plan, 'api_access'));
        $this->assertFalse($this->service->feature($plan, 'white_label'));
        $this->assertTrue($this->service->feature($plan, 'analytics'));
    }

    public function test_analytics_feature_requires_advanced_level(): void
    {
        $basic = $this->makePlan(['features' => ['analytics' => 'basic']]);
        $missing = $this->makePlan(['features' => []]);
        $boolean = $this->makePlan(['features' => ['analytics' => true]]);
        $disabled = $this->makePlan(['features' => ['analytics' => false]]);

        $this->assertFalse($this->service->feature($basic, 'analytics'));
        $this->assertFalse($this->service->feature($missing, 'analytics'));
        $this->assertTrue($this->service->feature($boolean, 'analytics'));
        $this->assertFalse($this->service->feature($disabled, 'analytics'));
    }

    public function test_feature_value_returns_raw_value(): void
    {
        $plan = $this->makePlan(['can_export_leads' => true, 'features' => ['analytics' => 'basic']]);
        $user = $this->makeUser($plan);

        $this->assertSame('basic', $this->service->featureValue($user, 'analytics'));
        $this->assertTrue($this->service->featureValue($user, 'leads'));
        $this->assertNull($this->service->featureValue($plan, 'white_label'));
    }

    public function test_admin_gets_leads_and_whatsapp_without_changing_data(): void
    {
        $plan = $this->makePlan(['can_export_leads' => false, 'can_use_whatsapp' => false]);
        $admin = $this->makeUser($plan, ['role' => 'admin']);
        $member = $this->makeUser($plan);

        $this->assertTrue($this->service->feature($admin, 'leads'));
        $this->assertTrue($this->service->feature($admin, 'whatsapp'));
        $this->assertFalse($this->service->feature($member, 'leads'));
        $this->assertFalse($this->service->feature($plan, 'leads'));

        $fresh = Plan::find($plan->id);
        $this->assertFalse((bool) $fresh->can_export_leads);
        $this->assertFalse((bool) $fresh->can_use_whatsapp);
    }

    public function test_ai_tier_and_allowed_models(): void
    {
        $plan = $this->makePlan([
            'ai_tier' => 'advanced',
            'allowed_models' => ['openrouter/free', 'nvidia/nemotron-3-super-120b-a12b:free'],
        ]);
        $user = $this->makeUser($plan);

        $this->assertSame('advanced', $this->service->aiTier($user));
        $this->assertSame('advanced', $this->service->aiTier($plan));
        $this->assertTrue($this->service->allowsModel($user, 'openrouter/free'));
        $this->assertFalse($this->service->allowsModel($user, 'openai/gpt-4o'));

        $tierless = $this->makePlan();
        $tierless->ai_tier = null;
        $this->assertSame('basic', $this->service->aiTier($tierless));
    }

    public function test_check_returns_consistent_shape_for_limits(): void
    {
        $plan = $this->makePlan(['max_messages_per_month' => 5]);
        $user = $this->makeUser($plan, ['monthly_message_used' => 5]);

        $denied = $this->service->check($user, 'monthly_messages');

        $this->assertSame(
            ['allowed', 'code', 'message', 'used', 'limit', 'remaining', 'plan'],
            array_keys($denied),
        );
        $this->assertFalse($denied['allowed']);
        $this->assertSame('limit_exceeded', $denied['code']);
        $this->assertSame(5, $denied['used']);
        $this->assertSame(5, $denied['limit']);
        $this->assertSame(0, $denied['remaining']);
        $this->assertSame($plan->slug, $denied['plan']);

        $user->update(['monthly_message_used' => 4]);
        $allowed = $this->service->check($user->fresh(), 'monthly_messages');

        $this->assertTrue($allowed['allowed']);
        $this->assertSame('allowed', $allowed['code']);
        $this->assertSame(1, $allowed['remaining']);
    }

    public function test_check_for_features_and_ai_summarize(): void
    {
        $free = $this->makePlan(['price' => 0, 'can_export_leads' => false]);
        $paid = $this->makePlan(['price' => 1000, 'can_export_leads' => true]);

        $locked = $this->service->check($this->makeUser($free), 'leads');
        $this->assertFalse($locked['allowed']);
        $this->assertSame('feature_locked', $locked['code']);
        $this->assertNull($locked['used']);
        $this->assertNull($locked['limit']);

        $open = $this->service->check($this->makeUser($paid), 'leads');
        $this->assertTrue($open['allowed']);
        $this->assertSame('allowed', $open['code']);

        $freeSummary = $this->service->check($this->makeUser($free), 'ai_summarize');
        $this->assertFalse($freeSummary['allowed']);
        $this->assertSame('paid_required', $freeSummary['code']);

        $paidSummary = $this->service->check($this->makeUser($paid), 'ai_summarize');
        $this->assertTrue($paidSummary['allowed']);
    }

    public function test_check_uses_context_for_active_channels_and_documents(): void
    {
        $plan = $this->makePlan(['max_widgets' => 2, 'max_documents' => 1]);
        $user = $this->makeUser($plan);

        $this->assertTrue($this->service->check($user, 'active_channels', ['used' => 1])['allowed']);
        $this->assertFalse($this->service->check($user, 'active_channels', ['used' => 2])['allowed']);
        $this->assertTrue($this->service->check($user, 'knowledge_documents', ['used' => 0])['allowed']);
        $this->assertFalse($this->service->check($user, 'knowledge_documents', ['used' => 1])['allowed']);
    }

    public function test_file_size_check_is_inclusive_of_the_limit(): void
    {
        $plan = $this->makePlan(['max_file_size_mb' => 2]);
        $user = $this->makeUser($plan);

        $this->assertTrue($this->service->check($user, 'file_size_mb', ['size_bytes' => 2 * 1024 * 1024])['allowed']);
        $this->assertFalse($this->service->check($user, 'file_size_mb', ['size_bytes' => (2 * 1024 * 1024) + 1])['allowed']);
    }

    public function test_unknown_ability_throws(): void
    {
        $user = $this->makeUser($this->makePlan());

        $this->expectException(InvalidArgumentException::class);
        $this->service->check($user, 'not_an_ability');
    }

    public function test_usage_returns_key_used_limit_and_remaining(): void
    {
        $plan = $this->makePlan(['max_messages_per_month' => 10]);
        $user = $this->makeUser($plan, ['monthly_message_used' => 12]);

        $usage = $this->service->usage($user, 'monthly_messages');

        $this->assertSame('monthly_messages', $usage['key']);
        $this->assertSame(12, $usage['used']);
        $this->assertSame(10, $usage['limit']);
        $this->assertSame(0, $usage['remaining']);
        $this->assertSame($plan->slug, $usage['plan']);
    }

    public function test_service_calls_never_mutate_plans_table(): void
    {
        $plan = $this->makePlan([
            'can_export_leads' => false,
            'features' => ['analytics' => 'basic'],
            'max_widgets' => 3,
        ]);
        $admin = $this->makeUser($plan, ['role' => 'admin']);

        $before = Plan::orderBy('id')->get()->toArray();

        $this->service->planFor($admin);
        $this->service->limit($admin, 'active_channels');
        $this->service->feature($admin, 'leads');
        $this->service->featureValue($admin, 'analytics');
        $this->service->aiTier($admin);
        $this->service->check($admin, 'active_channels', ['used' => 1]);
        $this->service->usage($admin, 'monthly_messages');

        $this->assertSame($before, Plan::orderBy('id')->get()->toArray());
    }
}
