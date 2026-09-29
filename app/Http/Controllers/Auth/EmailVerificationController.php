<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EmailVerificationController extends Controller
{
    /**
     * Show the verification notice page ("check your inbox").
     */
    public function notice(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        return view('auth.verify-email');
    }

    /**
     * Mark the user's email as verified (signed link from the email).
     */
    public function verify(Request $request)
    {
        $user = User::find($request->route('id'));

        if (! $user
            || ! hash_equals((string) sha1($user->getEmailForVerification()), (string) $request->route('hash'))) {
            return redirect()->route('login')->withErrors([
                'email' => 'Link verifikasi tidak valid atau sudah kedaluwarsa. Silakan login lalu kirim ulang link verifikasi.',
            ]);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard'))->with('success', 'Email Anda sudah terverifikasi.');
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Email berhasil diverifikasi! Selamat datang di Cekat.');
    }

    /**
     * Resend the verification link (throttled by the route).
     */
    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            Log::error('Failed to send verification email', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'email' => 'Gagal mengirim email verifikasi. Silakan coba lagi beberapa saat lagi.',
            ]);
        }

        return back()->with('success', 'Link verifikasi baru telah dikirim ke ' . $request->user()->email . '.');
    }
}
