<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Models\Widget;
use App\Services\Billing\PlanExpiryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanExpiryTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    private function makePlans(): array
    {
        $free = Plan::create([
            'name' => 'Starter',
            'slug' => 'starter-' . uniqid(),
            'price' => 0,
            'max_widgets' => 1,
            'max_messages_per_month' => 100,
        ]);

        $paid = Plan::create([
            'name' => 'Pro',
            'slug' => 'pro-' . uniqid(),
            'price' => 299000,
            'max_widgets' => 3,
            'max_messages_per_month' => 2000,
        ]);

        return compact('free', 'paid');
    }

    private function makeUser(Plan $plan, array $attrs = []): User
    {
        $this->seq++;

        return User::create(array_merge([
            'name' => 'U' . $this->seq,
            'email' => "u{$this->seq}-" . uniqid() . '@test.id',
            'password' => 'secret123',
            'plan_id' => $plan->id,
        ], $attrs));
    }

    public function test_expired_user_is_downgraded_and_channels_deactivated_on_next_request(): void
    {
        ['free' => $free, 'paid' => $paid] = $this->makePlans();
        $user = $this->makeUser($paid, [
            'plan_expires_at' => now()->subDay(),
            'monthly_message_used' => 50,
        ]);
        $w1 = Widget::create(['user_id' => $user->id, 'name' => 'W1', 'slug' => 'w-exp-1', 'status' => 'active']);
        $w2 = Widget::create(['user_id' => $user->id, 'name' => 'W2', 'slug' => 'w-exp-2', 'status' => 'active']);

        $this->actingAs($user)->get('/dashboard')
            ->assertRedirect(route('channels.index'));

        $user->refresh();
        $this->assertSame($free->id, $user->plan_id);
        $this->assertNull($user->plan_expires_at);
        $this->assertSame(0, $user->monthly_message_used);
        $this->assertSame('inactive', $w1->fresh()->status);
        $this->assertSame('inactive', $w2->fresh()->status);
        $this->assertFalse((bool) $w1->fresh()->is_active);
        $this->assertFalse((bool) $w2->fresh()->is_active);
    }

    public function test_channels_page_shows_activation_prompt_after_downgrade(): void
    {
        ['free' => $free, 'paid' => $paid] = $this->makePlans();
        $user = $this->makeUser($paid, ['plan_expires_at' => now()->subDay()]);
        Widget::create(['user_id' => $user->id, 'name' => 'W1', 'slug' => 'w-exp-3', 'status' => 'active']);

        $this->actingAs($user)->get('/channels')
            ->assertOk()
            ->assertSee('Aktifkan Channel Anda')
            ->assertSee('Aktifkan', false);
    }

    public function test_expired_user_without_channels_lands_on_dashboard(): void
    {
        ['paid' => $paid] = $this->makePlans();
        $user = $this->makeUser($paid, ['plan_expires_at' => now()->subDay()]);

        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->assertNull($user->fresh()->plan_expires_at);
    }

    public function test_active_subscription_is_not_downgraded(): void
    {
        ['paid' => $paid] = $this->makePlans();
        $user = $this->makeUser($paid, ['plan_expires_at' => now()->addDays(10)]);
        $widget = Widget::create(['user_id' => $user->id, 'name' => 'W1', 'slug' => 'w-live-1', 'status' => 'active']);

        $this->actingAs($user)->get('/dashboard')->assertOk();

        $user->refresh();
        $this->assertSame($paid->id, $user->plan_id);
        $this->assertNotNull($user->plan_expires_at);
        $this->assertSame('active', $widget->fresh()->status);
    }

    public function test_free_user_with_expiry_date_is_untouched(): void
    {
        ['free' => $free] = $this->makePlans();
        $user = $this->makeUser($free, ['plan_expires_at' => now()->subDay()]);

        $this->assertFalse(PlanExpiryService::isExpired($user));
        $this->assertFalse(PlanExpiryService::downgrade($user));
        $this->assertSame($free->id, $user->fresh()->plan_id);
    }

    public function test_activate_channel_respects_free_plan_limit(): void
    {
        ['free' => $free] = $this->makePlans();
        $user = $this->makeUser($free);
        $w1 = Widget::create(['user_id' => $user->id, 'name' => 'W1', 'slug' => 'w-act-1', 'status' => 'inactive']);
        $w2 = Widget::create(['user_id' => $user->id, 'name' => 'W2', 'slug' => 'w-act-2', 'status' => 'inactive']);

        // First activation is allowed (0 active < max_widgets 1)
        $this->actingAs($user)->post("/channels/{$w1->id}/activate")
            ->assertRedirect(route('channels.index'));
        $this->assertSame('active', $w1->fresh()->status);

        // Second activation is blocked by the plan limit
        $this->actingAs($user)->from('/channels')->post("/channels/{$w2->id}/activate")
            ->assertRedirect(route('channels.index'))
            ->assertSessionHas('error');
        $this->assertSame('inactive', $w2->fresh()->status);
    }
}
