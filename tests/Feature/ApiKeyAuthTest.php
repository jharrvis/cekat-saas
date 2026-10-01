<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\ChatSession;
use App\Models\Plan;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Public read API authentication (/api/v1): bearer API key resolution,
 * revocation/expiry, and the plan api_access gate.
 */
class ApiKeyAuthTest extends TestCase
{
    use RefreshDatabase;

    private function makeStack(bool $apiAccess): array
    {
        $plan = Plan::create([
            'name' => $apiAccess ? 'Pro API' : 'Free API',
            'slug' => $apiAccess ? 'pro-api' : 'free-api',
            'max_messages_per_month' => 1000,
            'can_export_leads' => true,
            'features' => ['api_access' => $apiAccess],
        ]);

        $owner = User::create([
            'name' => 'API Owner',
            'email' => 'owner-api@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
            'plan_id' => $plan->id,
        ]);

        $widget = Widget::create([
            'user_id' => $owner->id,
            'name' => 'Widget API',
            'slug' => 'w-api-' . uniqid(),
            'status' => 'active',
            'is_active' => true,
        ]);

        $session = ChatSession::create([
            'widget_id' => $widget->id,
            'visitor_uuid' => 'sess-api-' . uniqid(),
            'started_at' => now(),
            'visitor_name' => 'Budi API',
        ]);

        [$key, $secret] = ApiKey::generate($owner, 'Test Key');

        return [$owner, $widget, $session, $key, $secret];
    }

    public function test_missing_key_returns_401_json(): void
    {
        $response = $this->getJson('/api/v1/leads');

        $response->assertStatus(401)
            ->assertJson(['error_code' => 'unauthenticated']);
    }

    public function test_invalid_key_returns_401(): void
    {
        $this->getJson('/api/v1/leads', ['Authorization' => 'Bearer ck_live_totally-wrong-key'])
            ->assertStatus(401)
            ->assertJson(['error_code' => 'unauthenticated']);
    }

    public function test_valid_key_authenticates_and_returns_leads(): void
    {
        [, , , , $secret] = $this->makeStack(true);

        $this->getJson('/api/v1/leads', ['Authorization' => 'Bearer ' . $secret])
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_x_api_key_header_is_accepted(): void
    {
        [, , , , $secret] = $this->makeStack(true);

        $this->getJson('/api/v1/stats', ['X-API-Key' => $secret])
            ->assertStatus(200);
    }

    public function test_revoked_key_returns_401(): void
    {
        [, , , $key, $secret] = $this->makeStack(true);

        $key->forceFill(['revoked_at' => now()])->save();

        $this->getJson('/api/v1/leads', ['Authorization' => 'Bearer ' . $secret])
            ->assertStatus(401);
    }

    public function test_expired_key_returns_401(): void
    {
        [, , , $key, $secret] = $this->makeStack(true);

        $key->forceFill(['expires_at' => now()->subDay()])->save();

        $this->getJson('/api/v1/leads', ['Authorization' => 'Bearer ' . $secret])
            ->assertStatus(401);
    }

    public function test_free_plan_key_returns_403_feature_locked(): void
    {
        [, , , , $secret] = $this->makeStack(false);

        $this->getJson('/api/v1/leads', ['Authorization' => 'Bearer ' . $secret])
            ->assertStatus(403)
            ->assertJson(['error_code' => 'feature_locked']);
    }

    public function test_last_used_at_is_recorded(): void
    {
        [, , , $key, $secret] = $this->makeStack(true);

        $this->getJson('/api/v1/stats', ['Authorization' => 'Bearer ' . $secret]);

        $this->assertNotNull($key->fresh()->last_used_at);
    }

    public function test_secret_is_stored_hashed_never_plaintext(): void
    {
        [, , , $key, $secret] = $this->makeStack(true);

        $this->assertNotSame($secret, $key->key_hash);
        $this->assertSame(hash('sha256', $secret), $key->key_hash);
        $this->assertSame(substr($secret, 0, 12), $key->key_prefix);
    }
}
