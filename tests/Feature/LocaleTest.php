<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Remediation T-12 / finding F-12: full localization.
 * Default locale is Indonesian; a signed-in user's `users.locale` wins;
 * API consumers can steer via Accept-Language; lang/id and lang/en key
 * sets must stay identical so no raw keys ever render.
 */
class LocaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Pin the app default so the test does not depend on the ambient
        // .env of the machine running it (production default is 'id').
        config(['app.locale' => 'id', 'app.fallback_locale' => 'id']);
        app()->setLocale('id');
    }

    private function flatten(array $array, string $prefix = ''): array
    {
        $keys = [];
        foreach ($array as $key => $value) {
            $full = $prefix === '' ? $key : $prefix . '.' . $key;
            if (is_array($value)) {
                $keys = array_merge($keys, $this->flatten($value, $full));
            } else {
                $keys[] = $full;
            }
        }
        sort($keys);

        return $keys;
    }

    public function test_lang_id_and_en_key_sets_are_identical(): void
    {
        $idFiles = glob(base_path('lang/id/*.php'));
        $enFiles = glob(base_path('lang/en/*.php'));
        $this->assertSame(
            array_map(fn ($f) => basename($f), $idFiles),
            array_map(fn ($f) => basename($f), $enFiles),
            'lang/id dan lang/en harus punya berkas yang sama'
        );

        foreach ($idFiles as $file) {
            $name = basename($file);
            if ($name === 'plans.php') {
                continue; // owned by T-05's branch; parity kept there
            }
            $id = $this->flatten(require $file);
            $en = $this->flatten(require base_path('lang/en/' . $name));
            $this->assertSame($id, $en, "Kunci lang/en/{$name} harus identik dengan lang/id/{$name}");
        }
    }

    public function test_forgot_password_page_is_indonesian_by_default(): void
    {
        $response = $this->get('/forgot-password');
        $response->assertOk();
        $response->assertSee(__('auth.forgot_title', [], 'id'));
        $response->assertSee(__('auth.forgot_submit', [], 'id'));
        $response->assertDontSee('Send Reset Link');
    }

    public function test_user_with_english_locale_sees_english_auth_page(): void
    {
        $plan = Plan::create(['name' => 'Starter', 'slug' => 'starter', 'price' => 0]);
        $user = User::create([
            'name' => 'ENG User', 'email' => 'eng-' . uniqid() . '@test.id',
            'password' => 'secret123', 'email_verified_at' => now(),
            'plan_id' => $plan->id, 'locale' => 'en',
        ]);

        // users.locale wins over the default for signed-in pages the user
        // can visit; the forgot page itself is guest-facing, so assert via
        // the translator state after an authenticated request instead.
        $this->actingAs($user)->get(route('settings'))->assertOk();
        $this->assertSame('en', app()->getLocale());
    }

    public function test_api_error_uses_accept_language(): void
    {
        $widget = Widget::create([
            'user_id' => User::create([
                'name' => 'Owner', 'email' => 'own-' . uniqid() . '@test.id',
                'password' => 'secret123', 'email_verified_at' => now(),
            ])->id,
            'name' => 'W', 'slug' => 'w-loc', 'settings' => [], 'status' => 'inactive', 'is_active' => false,
        ]);

        $headers = ['Origin' => 'https://toko.test'];

        // Explicit id header (the bare test client otherwise defaults to en).
        $id = $this->postJson('/api/chat', ['message' => 'halo', 'widgetId' => 'w-loc'], $headers + ['Accept-Language' => 'id']);
        $id->assertStatus(404);
        $this->assertSame(__('api.widget_inactive', [], 'id'), $id->json('error'));

        $en = $this->postJson('/api/chat', ['message' => 'halo', 'widgetId' => 'w-loc'], $headers + ['Accept-Language' => 'en']);
        $en->assertStatus(404);
        $this->assertSame(__('api.widget_inactive', [], 'en'), $en->json('error'));
    }

    public function test_settings_language_picker_saves_locale(): void
    {
        $plan = Plan::create(['name' => 'Starter', 'slug' => 'starter', 'price' => 0]);
        $user = User::create([
            'name' => 'Pemilik', 'email' => 'pemilik-' . uniqid() . '@test.id',
            'password' => 'secret123', 'email_verified_at' => now(), 'plan_id' => $plan->id,
        ]);

        $this->actingAs($user)
            ->put(route('settings.update-profile'), ['name' => 'Pemilik', 'locale' => 'en'])
            ->assertRedirect();

        $this->assertSame('en', $user->fresh()->locale);
    }

    public function test_format_helper_localizes_numbers_and_durations(): void
    {
        $this->assertSame('3,2 dtk', \App\Support\Format::duration(3.24, 'id'));
        $this->assertSame('3.2 s', \App\Support\Format::duration(3.24, 'en'));
        $this->assertSame('1.234,5', \App\Support\Format::decimal(1234.5, 1, 'id'));
    }
}
