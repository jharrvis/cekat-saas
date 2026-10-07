<?php

namespace Tests\Feature;

use App\Mail\EmailOtp;
use App\Models\EmailLog;
use App\Models\User;
use App\Services\Email\EmailSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Remediation T-14 / finding F-15: the admin email log used to store the
 * FULL rendered body of OTP emails — a live 6-digit credential readable
 * long after delivery. OTP bodies are now masked at the logging point;
 * subject, recipient, and status stay recorded, and the mail itself is
 * still sent untouched.
 */
class EmailOtpLogMaskingTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::create([
            'name' => 'Penerima', 'email' => 'otp-' . uniqid() . '@test.id',
            'password' => 'secret123',
        ]);
    }

    public function test_otp_email_body_is_masked_in_email_logs(): void
    {
        Mail::fake();
        $user = $this->makeUser();

        $sent = EmailSender::send($user->email, new EmailOtp($user, '483920'), 'otp', ['user_id' => $user->id]);

        $this->assertTrue($sent);
        Mail::assertSent(EmailOtp::class);

        $log = EmailLog::latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('otp', $log->category);
        $this->assertSame('sent', $log->status);
        $this->assertSame('[konten disamarkan — email OTP]', $log->body);
        $this->assertStringNotContainsString('483920', (string) $log->body);
        $this->assertNotEmpty($log->subject);
    }

    public function test_non_otp_categories_keep_their_body(): void
    {
        Mail::fake();
        $user = $this->makeUser();

        EmailSender::send($user->email, new EmailOtp($user, '112233'), 'notification');

        $log = EmailLog::latest('id')->first();
        $this->assertNotNull($log);
        $this->assertNotSame('[konten disamarkan — email OTP]', $log->body);
        $this->assertStringContainsString('112233', (string) $log->body);
    }
}
