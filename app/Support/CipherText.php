<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;

/**
 * At-rest encryption for chat PII with legacy-plaintext tolerance.
 *
 * New writes are always encrypted. Reads accept both ciphertext (rows
 * written after the rollout) and plaintext (rows from before it) - only
 * new data is encrypted by decision; old rows age out via `chat:purge`
 * according to each tenant plan's chat_history_days.
 */
class CipherText
{
    public static function encrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return Crypt::encryptString($value);
    }

    public static function decrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            // Pre-rollout plaintext row: return as-is.
            return $value;
        }
    }
}
