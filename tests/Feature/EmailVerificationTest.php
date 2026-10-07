<?php

namespace Tests\Feature;

use App\Mail\AdminNewSignup;
use App\Mail\EmailOtp;
use App\Mail\WelcomeUser;
use App\Models\User;
use App\Services\Auth\EmailOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Every registration (email form or Google) must enter a 6-digit OTP sent
 * to the inbox. The dashboard shows a blocking modal until the code is
 * accepted; a correct code verifies the account and fires welcome/admin
 * emails.
 */
class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUnverified(string $email): User
    {
        return User::create([
            'name' => 'OTP Tester',
            'email' => $email,
            'password' => 'secret123',
        ]);
    }

    private function wrongCode(string $real): string
    {
        return $real === '000000' ? '111111' : '000000';
    }

    public function test_register_redirects_to_dashboard_and_sends_otp(): void
    {
        Mail::fake();

        $response = $this->post('/register', [
            'name' => 'Vera Verifikasi',
            'email' => 'vera@test.id',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect(route('dashboard'));

        $user = User::where('email', 'vera@test.id')->firstOrFail();
        $this->assertNull($user->email_verified_at);

        Mail::assertSent(EmailOtp::class, function (EmailOtp $m) {
            return $m->hasTo('vera@test.id') && preg_match('/^\d{6}$/', $m->code) === 1;
        });
    }

    public function test_correct_code_verifies_and_sends_welcome_and_admin_notice(): void
    {
        config(['mail.admin_notify' => 'admin@test.id']);
        Mail::fake();

        $user = $this->makeUnverified('unv@test.id');
        $code = app(EmailOtpService::class)->generate($user);

        $this->actingAs($user)
            ->post(route('verification.verify'), ['code' => $code])
            ->assertRedirect(route('dashboard'));

        $this->assertNotNull($user->fresh()->email_verified_at);
        Mail::assertSent(WelcomeUser::class, fn ($m) => $m->hasTo('unv@test.id'));
        Mail::assertSent(AdminNewSignup::class, fn ($m) => $m->hasTo('admin@test.id'));
    }

    public function test_wrong_code_is_rejected(): void
    {
        Mail::fake();

        $user = $this->makeUnverified('wrong@test.id');
        $code = app(EmailOtpService::class)->generate($user);

        $this->actingAs($user)
            ->post(route('verification.verify'), ['code' => $this->wrongCode($code)])
            ->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->email_verified_at);
        Mail::assertNotSent(WelcomeUser::class);
    }

    public function test_resend_sends_a_fresh_code(): void
    {
        Mail::fake();

        $user = $this->makeUnverified('resend@test.id');
        $user->sendEmailVerificationNotification();

        $response = $this->actingAs($user)
            ->post(route('verification.resend'));
        $response->assertSessionHas('otp_success');

        // first code + resent code
        Mail::assertSent(EmailOtp::class, 2);
    }

    public function test_too_many_wrong_attempts_invalidates_the_code(): void
    {
        Mail::fake();

        $user = $this->makeUnverified('brute@test.id');
        $code = app(EmailOtpService::class)->generate($user);

        for ($i = 0; $i < EmailOtpService::MAX_ATTEMPTS; $i++) {
            $this->actingAs($user)
                ->post(route('verification.verify'), ['code' => $this->wrongCode($code)])
                ->assertSessionHasErrors('code');
        }

        // even the real code is dead now
        $this->actingAs($user)
            ->post(route('verification.verify'), ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_unverified_user_sees_blocking_modal_on_dashboard(): void
    {
        $user = $this->makeUnverified('modal@test.id');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Verifikasi Email Anda')
            ->assertSee('modal@test.id', false)
            // x-data param MUST be a quoted string - unquoted '{ idle }' is a
            // JS variable reference (ReferenceError) that kills the component.
            ->assertSee("verifyOtpModal('idle')", false)
            // live countdown UI: resend chip (RESEND_DELAY) + expiry line (TTL)
            ->assertSee('kirim ulang dalam', false)
            ->assertSee('Kode berlaku', false)
            // 6-digit segmented input (auto-submit) keeps its original id
            ->assertSee('id="otp-code"', false);
    }

    public function test_login_emails_the_otp_to_unverified_user(): void
    {
        Mail::fake();

        $user = $this->makeUnverified('login-otp@test.id');

        $this->post('/login', [
            'email' => 'login-otp@test.id',
            'password' => 'secret123',
        ])->assertRedirect('/dashboard');

        Mail::assertSent(EmailOtp::class, fn ($m) => $m->hasTo('login-otp@test.id'));
    }

    public function test_login_does_not_resend_while_a_code_is_live(): void
    {
        Mail::fake();

        $user = $this->makeUnverified('login-live@test.id');
        $user->sendEmailVerificationNotification();

        $this->post('/login', [
            'email' => 'login-live@test.id',
            'password' => 'secret123',
        ])->assertRedirect('/dashboard');

        // only the pre-login send (login itself must not duplicate it)
        Mail::assertSent(EmailOtp::class, 1);
    }

    public function test_login_of_verified_user_sends_no_code(): void
    {
        Mail::fake();

        $user = $this->makeUnverified('login-done@test.id');
        $user->forceFill(['email_verified_at' => now()])->save();

        $this->post('/login', [
            'email' => 'login-done@test.id',
            'password' => 'secret123',
        ])->assertRedirect('/dashboard');

        Mail::assertNotSent(EmailOtp::class);
    }

    public function test_verified_user_does_not_see_the_modal(): void
    {
        $user = $this->makeUnverified('done@test.id');
        $user->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Verifikasi Email Anda');
    }

    public function test_legacy_notice_route_redirects_to_dashboard(): void
    {
        $user = $this->makeUnverified('legacy@test.id');

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertRedirect(route('dashboard'));
    }
}
