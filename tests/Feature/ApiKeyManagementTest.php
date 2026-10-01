<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dashboard API key management: create (secret shown once), revoke
 * (instant effect), ownership, and the plan lock page for Free users.
 */
class ApiKeyManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(bool $apiAccess): User
    {
        $plan = Plan::create([
            'name' => $apiAccess ? 'Pro Mgmt' : 'Free Mgmt',
            'slug' => ($apiAccess ? 'pro-mgmt-' : 'free-mgmt-') . uniqid(),
            'max_messages_per_month' => 1000,
            'can_export_leads' => true,
            'features' => ['api_access' => $apiAccess],
        ]);

        return User::create([
            'name' => 'Mgmt User',
            'email' => 'mgmt-' . uniqid() . '@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
            'plan_id' => $plan->id,
        ]);
    }

    public function test_create_key_shows_secret_exactly_once(): void
    {
        $user = $this->makeUser(true);

        $this->actingAs($user)
            ->post(route('api-keys.store'), ['name' => 'Zapier'])
            ->assertRedirect(route('api-keys.index'));

        $secret = session('plain_key');
        $this->assertNotNull($secret);
        $this->assertStringStartsWith('ck_live_', $secret);

        // First GET shows it once...
        $this->actingAs($user)->get(route('api-keys.index'))
            ->assertStatus(200)
            ->assertSee($secret, false)
            ->assertSee('Zapier');

        // ...the next one never does.
        $this->actingAs($user)->get(route('api-keys.index'))
            ->assertStatus(200)
            ->assertDontSee($secret, false);
    }

    public function test_create_requires_name(): void
    {
        $user = $this->makeUser(true);

        $this->actingAs($user)
            ->from(route('api-keys.index'))
            ->post(route('api-keys.store'), ['name' => ''])
            ->assertRedirect(route('api-keys.index'))
            ->assertSessionHasErrors('name');
    }

    public function test_revoke_disables_key_immediately(): void
    {
        $user = $this->makeUser(true);
        [$key, $secret] = ApiKey::generate($user, 'To Revoke');

        $this->getJson('/api/v1/stats', ['Authorization' => 'Bearer ' . $secret])
            ->assertStatus(200);

        $this->actingAs($user)
            ->delete(route('api-keys.destroy', $key))
            ->assertRedirect();

        $this->assertNotNull($key->fresh()->revoked_at);

        $this->getJson('/api/v1/stats', ['Authorization' => 'Bearer ' . $secret])
            ->assertStatus(401);
    }

    public function test_cannot_revoke_another_users_key(): void
    {
        $owner = $this->makeUser(true);
        $attacker = $this->makeUser(true);
        [$key] = ApiKey::generate($owner, 'Victim');

        $this->actingAs($attacker)
            ->delete(route('api-keys.destroy', $key))
            ->assertStatus(404);

        $this->assertNull($key->fresh()->revoked_at);
    }

    public function test_free_user_sees_plan_lock_page(): void
    {
        $user = $this->makeUser(false);

        $this->actingAs($user)
            ->get(route('api-keys.index'))
            ->assertStatus(200)
            ->assertViewIs('user.plan-locked');
    }

    public function test_free_user_cannot_create_key(): void
    {
        $user = $this->makeUser(false);

        $this->actingAs($user)
            ->post(route('api-keys.store'), ['name' => 'Nope'])
            ->assertRedirect();

        $this->assertSame(0, ApiKey::where('user_id', $user->id)->count());
    }
}
