<?php

namespace Tests\Feature;

use App\Mail\AdminNewSignup;
use App\Mail\VerifyEmail;
use App\Mail\WelcomeUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Every new registration must land on the verification notice and receive
 * a branded verification email; the signed link then verifies the account
 * and triggers the welcome + admin signup emails.
 */
class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_redirects_to_verification_notice_and_sends_email(): void
    {
        Mail::fake();

        $response = $this->post('/register', [
            'name' => 'Vera Verifikasi',
            'email' => 'vera@test.id',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'vera@test.id')->firstOrFail();
        $this->assertNull($user->email_verified_at);

        Mail::assertSent(VerifyEmail::class, fn ($m) => $m->hasTo('vera@test.id'));
    }

    public function test_signed_link_verifies_and_sends_welcome_and_admin_notice(): void
    {
        config(['mail.admin_notify' => 'admin@test.id']);
        Mail::fake();

        $user = User::create([
            'name' => 'Unverified',
            'email' => 'unv@test.id',
            'password' => 'secret123',
        ]);

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect(route('dashboard'));

        $this->assertNotNull($user->fresh()->email_verified_at);
        Mail::assertSent(WelcomeUser::class, fn ($m) => $m->hasTo('unv@test.id'));
        Mail::assertSent(AdminNewSignup::class, fn ($m) => $m->hasTo('admin@test.id'));
    }

    public function test_clicking_verification_link_twice_sends_welcome_only_once(): void
    {
        config(['mail.admin_notify' => 'admin@test.id']);
        Mail::fake();

        $user = User::create([
            'name' => 'Twice',
            'email' => 'twice@test.id',
            'password' => 'secret123',
        ]);

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->actingAs($user)->get($url);
        $this->actingAs($user->fresh())->get($url);

        Mail::assertSent(WelcomeUser::class, 1);
        Mail::assertSent(AdminNewSignup::class, 1);
    }

    public function test_tampered_hash_is_rejected(): void
    {
        Mail::fake();

        $user = User::create([
            'name' => 'Tamper',
            'email' => 'tamper@test.id',
            'password' => 'secret123',
        ]);

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1('wrong-input'),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect(route('login'));

        $this->assertNull($user->fresh()->email_verified_at);
        Mail::assertNotSent(WelcomeUser::class);
    }
}
