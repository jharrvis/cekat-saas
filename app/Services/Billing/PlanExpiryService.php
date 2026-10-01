<?php

namespace App\Services\Billing;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Central logic for paid-plan expiry: detection, downgrade to the free
 * plan, and deactivation of the user's channels. Used by the daily
 * `plans:check-expiry` command and lazily by the `user.status` middleware
 * (so an expired user is downgraded on their next request/login, not up
 * to 24h later).
 */
class PlanExpiryService
{
    /**
     * Whether the user's paid plan has passed its expiry date.
     */
    public static function isExpired(User $user): bool
    {
        if (! $user->plan_expires_at) {
            return false;
        }

        if (! $user->plan_expires_at->isPast()) {
            return false;
        }

        return (bool) ($user->plan && $user->plan->price > 0);
    }

    /**
     * Downgrade an expired paid user to the free plan and deactivate all
     * of their channels. Returns true when a downgrade was performed.
     */
    public static function downgrade(User $user): bool
    {
        if (! self::isExpired($user)) {
            return false;
        }

        $freePlan = Plan::where('price', 0)->orderBy('id')->first();

        $user->update([
            'plan_id' => $freePlan?->id,
            'plan_expires_at' => null,
            'monthly_message_used' => 0,
        ]);

        $deactivated = $user->widgets()->update([
            'status' => 'inactive',
            'is_active' => false,
        ]);

        Log::info('plan.downgraded', [
            'user_id' => $user->id,
            'free_plan_id' => $freePlan?->id,
            'channels_deactivated' => $deactivated,
        ]);

        return true;
    }
}
