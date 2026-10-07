<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Remediation T-02 / finding F-02: the per-plan agent quota must be enforced.
 * Before this, a Starter account could create unlimited agents.
 */
class AgentLimitTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    private function makeUser(int $maxAgents): User
    {
        $this->seq++;

        $plan = Plan::create([
            'name' => 'Plan ' . $this->seq,
            'slug' => 'plan-' . $this->seq . '-' . uniqid(),
            'max_agents' => $maxAgents,
        ]);

        return User::create([
            'name' => 'User ' . $this->seq,
            'email' => 'user-' . $this->seq . '-' . uniqid() . '@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
            'role' => 'user',
            'plan_id' => $plan->id,
        ]);
    }

    private function agentPayload(string $name): array
    {
        return [
            'name' => $name,
            'personality' => 'friendly',
            'ai_temperature' => 0.7,
        ];
    }

    public function test_user_at_agent_limit_cannot_create_another_agent(): void
    {
        $user = $this->makeUser(1);

        $this->actingAs($user)
            ->post(route('agents.store'), $this->agentPayload('Agen Pertama'))
            ->assertRedirect();

        $this->assertSame(1, $user->aiAgents()->count());

        $response = $this->actingAs($user)
            ->post(route('agents.store'), $this->agentPayload('Agen Kedua'));

        $response->assertRedirect(route('agents.index'));
        $response->assertSessionHas('error');
        $this->assertSame(1, $user->aiAgents()->count());
    }

    public function test_user_can_create_agents_up_to_plan_limit(): void
    {
        $user = $this->makeUser(3);

        foreach (['Satu', 'Dua', 'Tiga'] as $name) {
            $this->actingAs($user)
                ->post(route('agents.store'), $this->agentPayload('Agen ' . $name))
                ->assertRedirect();
        }

        $this->assertSame(3, $user->aiAgents()->count());

        $this->actingAs($user)
            ->post(route('agents.store'), $this->agentPayload('Agen Empat'))
            ->assertSessionHas('error');

        $this->assertSame(3, $user->aiAgents()->count());
    }

    public function test_agents_have_their_own_quota_independent_of_channels(): void
    {
        // A user who already owns channels/widgets must still be able to
        // create their first agent: the quotas are independent (T-02).
        $user = $this->makeUser(1);
        $user->widgets()->create([
            'name' => 'Widget Lama',
            'slug' => 'widget-' . uniqid(),
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('agents.store'), $this->agentPayload('Agen Pertama'))
            ->assertRedirect();

        $this->assertSame(1, $user->aiAgents()->count());
    }
}
