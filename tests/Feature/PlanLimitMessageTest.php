<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Services\Billing\PlanLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Remediation T-05 / finding F-04 (message side): every plan-limit denial
 * uses one Indonesian pattern naming the plan and the limit, carried by the
 * 'plan_limit_error' flash key and rendered by the shared banner component.
 */
class PlanLimitMessageTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $planAttrs): User
    {
        $plan = Plan::create(array_merge([
            'name' => 'Starter',
            'slug' => 'starter-' . uniqid(),
        ], $planAttrs));

        return User::create([
            'name' => 'Pemilik',
            'email' => 'pemilik-' . uniqid() . '@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
            'role' => 'user',
            'plan_id' => $plan->id,
        ]);
    }

    public function test_limit_message_follows_the_indonesian_pattern(): void
    {
        $user = $this->makeUser(['max_widgets' => 1, 'max_agents' => 1]);

        $limits = app(PlanLimitService::class);

        $this->assertSame(
            'Paket Starter Anda terbatas 1 channel. Tingkatkan paket untuk menambah.',
            $limits->limitMessage($user, 'total_channels')
        );
        $this->assertSame(
            'Paket Starter Anda terbatas 1 agen. Tingkatkan paket untuk menambah.',
            $limits->limitMessage($user, 'total_agents')
        );
    }

    public function test_channel_denial_carries_the_patterned_message(): void
    {
        $user = $this->makeUser(['max_widgets' => 1]);
        $user->widgets()->create([
            'name' => 'Satu',
            'slug' => 'satu-' . uniqid(),
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('channels.store'), ['display_name' => 'Kedua'])
            ->assertRedirect(route('channels.index'));

        $this->assertSame(
            'Paket Starter Anda terbatas 1 channel. Tingkatkan paket untuk menambah.',
            session('plan_limit_error')
        );
    }

    public function test_lang_files_have_identical_keys(): void
    {
        $id = require lang_path('id/plans.php');
        $en = require lang_path('en/plans.php');

        $this->assertSame(array_keys($id), array_keys($en));
    }
}
