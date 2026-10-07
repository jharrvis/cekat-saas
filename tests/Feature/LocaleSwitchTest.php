<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Quick language switcher (header): POST /locale persists to users.locale
 * for signed-in users and to the session for guests; SetLocale applies it
 * from the very next request.
 */
class LocaleSwitchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.locale' => 'id', 'app.fallback_locale' => 'id']);
        app()->setLocale('id');
    }

    public function test_signed_in_user_switch_persists_and_applies(): void
    {
        $user = User::create([
            'name' => 'Switch', 'email' => 'switch-' . uniqid() . '@test.id',
            'password' => 'secret123', 'email_verified_at' => now(), 'locale' => 'id',
        ]);

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('locale.switch'), ['locale' => 'en'])
            ->assertRedirect(route('dashboard'));

        $this->assertSame('en', $user->fresh()->locale);
        $this->assertSame('en', session('locale'));

        $html = $this->actingAs($user)->get(route('dashboard'))->getContent();
        $this->assertStringContainsString('>Channels<', $html);
        $this->assertStringNotContainsString('>Saluran<', $html);
    }

    public function test_guest_switch_uses_session_and_repaints_landing(): void
    {
        $this->post(route('locale.switch'), ['locale' => 'en'])
            ->assertRedirect();
        $this->assertSame('en', session('locale'));

        $html = $this->get('/')->getContent();
        // Landing renders in English for the guest after switching.
        $this->assertStringContainsString('>Feature<', $html);
        $this->assertStringNotContainsString('>Fitur<', $html);
    }

    public function test_invalid_locale_is_rejected_and_keeps_current(): void
    {
        $user = User::create([
            'name' => 'Stay', 'email' => 'stay-' . uniqid() . '@test.id',
            'password' => 'secret123', 'email_verified_at' => now(), 'locale' => 'id',
        ]);

        $this->actingAs($user)
            ->post(route('locale.switch'), ['locale' => 'fr'])
            ->assertSessionHasErrors('locale');

        $this->assertSame('id', $user->fresh()->locale);
    }

    public function test_switcher_component_is_present_in_dashboard_header_and_landing(): void
    {
        $user = User::create([
            'name' => 'Head', 'email' => 'head-' . uniqid() . '@test.id',
            'password' => 'secret123', 'email_verified_at' => now(),
        ]);

        $dash = $this->actingAs($user)->get(route('dashboard'))->getContent();
        $this->assertStringContainsString(route('locale.switch'), $dash);
        $this->assertStringContainsString('Bahasa Indonesia', $dash);

        $landing = $this->get('/')->getContent();
        $this->assertStringContainsString(route('locale.switch'), $landing);
    }
}
