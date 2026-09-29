<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanFeatureGateTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWith(array $planAttrs, string $role = 'user', array $userAttrs = []): User
    {
        static $seq = 0;
        $seq++;

        $plan = Plan::create(array_merge([
            'name' => 'Plan ' . $seq,
            'slug' => 'plan-' . $seq . '-' . uniqid(),
        ], $planAttrs));

        return User::create(array_merge([
            'name' => 'User ' . $seq,
            'email' => 'user-' . $seq . '-' . uniqid() . '@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
            'role' => $role,
            'plan_id' => $plan->id,
        ], $userAttrs));
    }

    public function test_starter_user_sees_lead_lock_page(): void
    {
        $user = $this->makeUserWith([
            'price' => 0,
            'can_export_leads' => false,
            'can_use_whatsapp' => false,
        ]);

        $this->actingAs($user)->get('/leads')
            ->assertOk()
            ->assertSee('Lead Collection Locked');

        $this->actingAs($user)->get('/leads/export')
            ->assertOk()
            ->assertSee('Lead Collection Locked');
    }

    public function test_pro_user_can_access_leads(): void
    {
        $user = $this->makeUserWith([
            'price' => 299000,
            'can_export_leads' => true,
            'can_use_whatsapp' => true,
        ]);

        $this->actingAs($user)->get('/leads')
            ->assertOk()
            ->assertDontSee('Lead Collection Locked');
    }

    public function test_admin_bypasses_lead_lock(): void
    {
        $user = $this->makeUserWith([
            'price' => 0,
            'can_export_leads' => false,
            'can_use_whatsapp' => false,
        ], 'admin');

        $this->actingAs($user)->get('/leads')
            ->assertOk()
            ->assertDontSee('Lead Collection Locked');
    }

    public function test_starter_user_sees_whatsapp_lock_page(): void
    {
        $user = $this->makeUserWith([
            'price' => 0,
            'can_export_leads' => false,
            'can_use_whatsapp' => false,
        ]);

        $this->actingAs($user)->get('/whatsapp')
            ->assertOk()
            ->assertSee('WhatsApp Gateway Locked');
    }

    public function test_starter_user_cannot_post_to_whatsapp(): void
    {
        $user = $this->makeUserWith([
            'price' => 0,
            'can_export_leads' => false,
            'can_use_whatsapp' => false,
        ]);

        $this->actingAs($user)->post('/whatsapp/create', [
            'device_name' => 'D',
            'phone_number' => '08120000000',
            'widget_id' => null,
        ])->assertRedirect(route('whatsapp.index'));
    }

    public function test_pro_user_is_not_locked_out_of_whatsapp(): void
    {
        $user = $this->makeUserWith([
            'price' => 299000,
            'can_export_leads' => true,
            'can_use_whatsapp' => true,
        ]);

        // Module is disabled in tests, but the plan lock must not be shown.
        $this->actingAs($user)->get('/whatsapp')
            ->assertOk()
            ->assertDontSee('WhatsApp Gateway Locked');
    }
}
