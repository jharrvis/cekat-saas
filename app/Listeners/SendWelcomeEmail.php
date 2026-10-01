<?php

namespace App\Listeners;

use App\Mail\WelcomeUser;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sends the welcome email the moment an account's email is verified
 * (covers both the sign-up verification link and any later re-verification).
 */
class SendWelcomeEmail
{
    public function handle(Verified $e): void
    {
        try {
            Mail::to($e->user->email)->send(new WelcomeUser($e->user));
        } catch (\Throwable $ex) {
            Log::error('Failed to send welcome email', [
                'user_id' => $e->user->id,
                'error' => $ex->getMessage(),
            ]);
        }
    }
}
