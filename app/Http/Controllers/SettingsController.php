<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEmailRequest;
use App\Mail\EmailChangeConfirm;
use App\Mail\EmailChangeDone;
use App\Mail\EmailChangeRequestAlert;
use App\Models\User;
use App\Services\Email\EmailSender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class SettingsController extends Controller
{
    /**
     * Start an email change: store pending_email, send a confirmation
     * link to the NEW address and an alert to the OLD address.
     */
    public function updateEmail(UpdateEmailRequest $request)
    {
        $user = $request->user();
        $newEmail = $request->validated()['email'];
        $oldEmail = $user->email;

        $user->update(['pending_email' => $newEmail]);

        try {
            EmailSender::send($newEmail, new EmailChangeConfirm($user, $oldEmail, $newEmail), 'email-change-confirm', ['user_id' => $user->id]);
            EmailSender::send($oldEmail, new EmailChangeRequestAlert($user, $oldEmail, $newEmail), 'email-change-request', ['user_id' => $user->id]);
        } catch (\Throwable $e) {
            Log::error('Failed to send email change confirmation', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'email' => 'Permintaan tersimpan, tetapi email konfirmasi gagal dikirim. Silakan coba lagi.',
            ]);
        }

        return back()->with(
            'success',
            "Link konfirmasi telah dikirim ke {$newEmail}. Email aktif setelah Anda mengklik link tersebut."
        );
    }

    /**
     * Confirm the pending change (signed link clicked from the NEW inbox).
     */
    public function confirmEmail(Request $request)
    {
        // Signed link carries id + hash as query parameters.
        $user = User::find($request->query('id'));
        $pending = $user?->pending_email;

        if (! $user
            || ! $pending
            || ! hash_equals(sha1($pending), (string) $request->query('hash'))) {
            return redirect()->route('settings')->withErrors([
                'email' => 'Link konfirmasi tidak valid atau sudah kedaluwarsa. Ajukan perubahan email baru.',
            ]);
        }

        if (User::where('email', $pending)->where('id', '!=', $user->id)->exists()) {
            $user->update(['pending_email' => null]);

            return redirect()->route('settings')->withErrors([
                'email' => 'Email baru sudah terdaftar di akun lain. Gunakan alamat lain.',
            ]);
        }

        $oldEmail = $user->email;

        $user->update([
            'email' => $pending,
            'pending_email' => null,
            'email_verified_at' => now(), // proven by clicking from the new inbox
        ]);

        try {
            EmailSender::send($oldEmail, new EmailChangeDone($user, $oldEmail, $pending), 'email-change-done', ['user_id' => $user->id]);
        } catch (\Throwable $e) {
            Log::error('Failed to send email change done alert', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('settings')
            ->with('success', __('settings.email_changed', ['email' => $user->email]));
    }

    /**
     * Signed confirmation URL for the pending change (used by the mailable).
     */
    public static function confirmationUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'settings.email.confirm',
            now()->addDay(),
            ['id' => $user->id, 'hash' => sha1($user->pending_email)],
        );
    }
}
