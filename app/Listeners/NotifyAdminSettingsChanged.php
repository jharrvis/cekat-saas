<?php

namespace App\Listeners;

use App\Events\AdminSettingsChanged;
use App\Mail\AdminSettingsChangedNotice;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Emails ADMIN_NOTIFY_EMAIL (fallback: first admin) whenever an admin
 * saves system/whatsapp settings. No secrets are included.
 */
class NotifyAdminSettingsChanged
{
    public function handle(AdminSettingsChanged $e): void
    {
        $to = config('mail.admin_notify')
            ?: User::where('role', 'admin')->orderBy('id')->value('email');

        if (! $to) {
            return;
        }

        try {
            Mail::to($to)->send(new AdminSettingsChangedNotice(
                $e->userId ? User::find($e->userId) : null,
                $e->group,
                $e->keys,
            ));
        } catch (\Throwable $ex) {
            Log::error('Failed to send admin settings change notice', [
                'group' => $e->group,
                'error' => $ex->getMessage(),
            ]);
        }
    }
}
