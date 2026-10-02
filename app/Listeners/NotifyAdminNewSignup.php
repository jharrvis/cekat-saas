<?php

namespace App\Listeners;

use App\Mail\AdminNewSignup;
use App\Models\User;
use App\Services\Email\EmailSender;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Log;

/**
 * Tells ADMIN_NOTIFY_EMAIL (fallback: first admin) about each newly
 * verified account so sign-ups can be followed up promptly.
 */
class NotifyAdminNewSignup
{
    public function handle(Verified $e): void
    {
        // Only brand-new signups: long-standing accounts (re-verifying
        // after the unverify migration) are not "pendaftar baru".
        if ($e->user->created_at?->lt(now()->subDay())) {
            return;
        }

        $to = config('mail.admin_notify')
            ?: User::where('role', 'admin')->orderBy('id')->value('email');

        if (! $to) {
            return;
        }

        try {
            EmailSender::send($to, new AdminNewSignup($e->user), 'admin-signup', [
                'user_id' => $e->user->id,
            ]);
        } catch (\Throwable $ex) {
            Log::error('Failed to send new signup notice', [
                'user_id' => $e->user->id,
                'error' => $ex->getMessage(),
            ]);
        }
    }
}
