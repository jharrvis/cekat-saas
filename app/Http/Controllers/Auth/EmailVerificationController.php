<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\EmailOtpService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

/**
 * Email verification via 6-digit OTP (EmailOtpService + EmailOtp mailable).
 * The UI is the blocking modal in the dashboard layout; these are its
 * endpoints (verify code / resend code).
 */
class EmailVerificationController extends Controller
{
    public function __construct(private EmailOtpService $otp) {}

    /**
     * Legacy notice route - verification now happens in the dashboard modal.
     */
    public function notice(Request $request)
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        return redirect()->route('dashboard');
    }

    /**
     * Verify the submitted OTP (POST, route name verification.verify).
     */
    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        if (! $this->otp->verify($user, $request->input('code'))) {
            return back()->withErrors([
                'code' => 'Kode salah atau kedaluwarsa. Periksa email Anda atau minta kode baru.',
            ]);
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return redirect()->route('dashboard')
            ->with('success', 'Email berhasil diverifikasi! Selamat datang di Cekat.');
    }

    /**
     * (Re)generate and send the OTP (POST, route name verification.resend).
     */
    public function resend(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send verification code', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'code' => 'Gagal mengirim kode. Silakan coba lagi beberapa saat lagi.',
            ]);
        }

        return back()->with('otp_success', 'Kode verifikasi baru dikirim ke ' . $user->email . '.');
    }
}
