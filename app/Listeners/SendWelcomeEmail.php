<?php

namespace App\Listeners;

use App\Mail\WelcomeUser;
use App\Services\Email\EmailSender;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Log;

/**
 * Sends the welcome email the moment an account's email is verified
 * (covers both the sign-up verification link and any later re-verification).
 */
class SendWelcomeEmail
{
    public function handle(Verified $e): void
    {
        try {
            EmailSender::send($e->user->email, new WelcomeUser($e->user), 'welcome', [
                'user_id' => $e->user->id,
            ]);
        } catch (\Throwable $ex) {
            Log::error('Failed to send welcome email', [
                'user_id' => $e->user->id,
                'error' => $ex->getMessage(),
            ]);
        }
    }
}
