<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Remediation T-09 / finding F-10: unknown URLs must render the branded
 * Indonesian 404 (previously Laravel's plain default page), and the
 * natural /api-keys URL must land on the real API keys settings page.
 */
class BrandedNotFoundTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_urls_render_the_branded_404(): void
    {
        foreach (['/pricing', '/docs', '/blog', '/url-acak-xyz'] as $url) {
            $response = $this->get($url);
            $response->assertNotFound();
            $response->assertSee('Halaman tidak ditemukan');
            $response->assertSee('Ke Beranda');
            $response->assertSee('Cekat');
        }
    }

    public function test_api_keys_shortcut_redirects_permanently_to_settings(): void
    {
        $response = $this->get('/api-keys');
        $response->assertStatus(301);
        $response->assertRedirect('/settings/api-keys');
    }

    public function test_api_keys_target_works_after_login(): void
    {
        $plan = Plan::create(['name' => 'Starter', 'slug' => 'starter', 'price' => 0]);
        $user = User::create([
            'name' => 'Pemilik', 'email' => 'pemilik-' . uniqid() . '@test.id',
            'password' => 'secret123', 'email_verified_at' => now(), 'plan_id' => $plan->id,
        ]);

        $this->actingAs($user)->get('/api-keys')->assertRedirect('/settings/api-keys');
        $this->actingAs($user)->get('/settings/api-keys')->assertOk();
    }
}
