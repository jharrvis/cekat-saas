<?php

namespace App\Services\WhatsApp;

/**
 * Indonesian phone-number normalization for WhatsApp targets.
 *
 * Fonnte accepts a full international number as the send target; users
 * type local formats (08xx, +62 8xx, 628xx). Normalize everything to the
 * canonical digits-only 62xx form, or null when the input cannot be an
 * Indonesian mobile number.
 */
class PhoneNumber
{
    public static function normalizeId(?string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $raw);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62' . $digits;
        }

        if (! str_starts_with($digits, '62')) {
            return null;
        }

        $length = strlen($digits);

        if ($length < 10 || $length > 15) {
            return null;
        }

        return $digits;
    }
}
