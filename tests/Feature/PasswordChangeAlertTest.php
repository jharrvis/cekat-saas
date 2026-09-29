<?php

namespace Tests\Feature;

use App\Mail\PasswordChanged;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Security alert: the account owner is emailed whenever the password
 * changes - both from Settings and from the forgot-password flow.
 */
class PasswordChangeAlertTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::create([
            'name' => 'Pwd Tester',
            'email' => 'pwd@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
        ]);
    }

    public function test_changing_password_from_settings_sends_alert(): void
    {
        Mail::fake();

        $user = $this->makeUser();

        $response = $this->actingAs($user)->put('/settings/password', [
            'current_password' => 'secret123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));

        Mail::assertSent(PasswordChanged::class, fn ($m) => $m->hasTo('pwd@test.id'));
    }

    public function test_wrong_current_password_sends_no_alert(): void
    {
        Mail::fake();

        $user = $this->makeUser();

        $this->actingAs($user)->put('/settings/password', [
            'current_password' => 'not-the-one',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('secret123', $user->fresh()->password));
        Mail::assertNotSent(PasswordChanged::class);
    }

    public function test_reset_via_forgot_password_sends_alert(): void
    {
        Mail::fake();

        $user = $this->makeUser();

        $token = \Illuminate\Support\Facades\Password::broker()->createToken($user);

        $this->post('/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'brandnew123',
            'password_confirmation' => 'brandnew123',
        ])->assertStatus(302);

        $this->assertTrue(Hash::check('brandnew123', $user->fresh()->password));

        Mail::assertSent(PasswordChanged::class, fn ($m) => $m->hasTo('pwd@test.id'));
    }
}
