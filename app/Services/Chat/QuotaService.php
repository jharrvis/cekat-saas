<?php

namespace App\Services\Chat;

use App\Models\User;

/**
 * Enforces monthly message quota and account-status gates for chat.
 *
 * Extracted from Api\ChatController. Behavior preserved:
 * - the landing-page widget bypasses all checks,
 * - missing owner => 404, suspended/banned => 403,
 * - exhausted quota => 429 with the legacy payload shape.
 */
class QuotaService
{
    public const LANDING_SLUG = 'landing-page-default';

    /**
     * @return array{status:int,body:array}|null Null when the request may proceed.
     */
    public function check(?User $user, string $widgetSlug): ?array
    {
        if ($widgetSlug === self::LANDING_SLUG) {
            return null;
        }

        if (! $user) {
            return [
                'status' => 404,
                'body' => ['success' => false, 'error' => 'Widget owner not found'],
            ];
        }

        if (in_array($user->status, ['suspended', 'banned'], true)) {
            return [
                'status' => 403,
                'body' => ['success' => false, 'error' => 'Widget temporarily unavailable'],
            ];
        }

        $plan = $user->plan;
        if ($plan && $user->monthly_message_used >= $plan->max_messages_per_month) {
            return [
                'status' => 429,
                'body' => [
                    'success' => false,
                    'error' => 'quota_exceeded',
                    'message' => 'Maaf, kuota pesan bulanan telah habis. Silakan hubungi pemilik website.',
                    'quota' => [
                        'used' => $user->monthly_message_used,
                        'limit' => $plan->max_messages_per_month,
                        'reset_date' => now()->startOfMonth()->addMonth()->format('d M Y'),
                    ],
                ],
            ];
        }

        return null;
    }

    public function consume(?User $user, string $widgetSlug): void
    {
        if ($user && $widgetSlug !== self::LANDING_SLUG) {
            $user->increment('monthly_message_used');
        }
    }
}
