<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Email OTP for account verification: a 6-digit code is stored hashed in
 * cache (5 min TTL), with resend spacing and an attempt cap.
 */
class EmailOtpService
{
    public const TTL = 300;
    public const RESEND_DELAY = 60;
    public const MAX_ATTEMPTS = 5;

    private function key(User $user): string
    {
        return 'email_otp:' . $user->id;
    }

    /**
     * Create a fresh code for the user. Returns the plain code so callers
     * (mailable/tests) can use it - never log or expose it elsewhere.
     */
    public function generate(User $user): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put($this->key($user), [
            'code' => hash('sha256', $code),
            'expires_at' => now()->addSeconds(self::TTL)->getTimestamp(),
            'attempts' => 0,
            'sent_at' => now()->getTimestamp(),
        ], self::TTL);

        return $code;
    }

    /**
     * Whether a code was sent recently enough to not resend yet.
     */
    public function hasLiveCode(User $user): bool
    {
        $data = Cache::get($this->key($user));

        if (! $data) {
            return false;
        }

        return (now()->getTimestamp() - ($data['sent_at'] ?? 0)) < self::RESEND_DELAY;
    }

    /**
     * Validate a submitted code. Wrong/expired codes burn an attempt;
     * the code is invalidated after MAX_ATTEMPTS or on success.
     */
    public function verify(User $user, string $code): bool
    {
        $data = Cache::get($this->key($user));

        if (! $data || now()->getTimestamp() > $data['expires_at']) {
            Cache::forget($this->key($user));

            return false;
        }

        if ($data['attempts'] >= self::MAX_ATTEMPTS) {
            Cache::forget($this->key($user));

            return false;
        }

        if (! hash_equals($data['code'], hash('sha256', str_pad(trim($code), 6, '0', STR_PAD_LEFT)))) {
            $data['attempts']++;
            Cache::put($this->key($user), $data, self::TTL);

            return false;
        }

        Cache::forget($this->key($user));

        return true;
    }
}
