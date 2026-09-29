<?php

namespace App\Listeners;

use App\Mail\AdminNewSignup;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Tells ADMIN_NOTIFY_EMAIL (fallback: first admin) about each newly
 * verified account so sign-ups can be followed up promptly.
 */
class NotifyAdminNewSignup
{
    public function handle(Verified $e): void
    {
        $to = config('mail.admin_notify')
            ?: User::where('role', 'admin')->orderBy('id')->value('email');

        if (! $to) {
            return;
        }

        try {
            Mail::to($to)->send(new AdminNewSignup($e->user));
        } catch (\Throwable $ex) {
            Log::error('Failed to send new signup notice', [
                'user_id' => $e->user->id,
                'error' => $ex->getMessage(),
            ]);
        }
    }
}
