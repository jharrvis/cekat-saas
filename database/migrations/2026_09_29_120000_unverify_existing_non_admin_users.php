<?php

use Illuminate\Database\Migrations\Migration;

/**
 * The verification flow now requires every non-admin account to enter an
 * OTP, so existing (backfilled-verified) users are set back to unverified
 * and get the blocking verify modal on the dashboard.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('role', '!=', 'admin')
            ->update(['email_verified_at' => null]);
    }

    public function down(): void
    {
        // Restore the previous state (all accounts were verified).
        DB::table('users')
            ->where('role', '!=', 'admin')
            ->update(['email_verified_at' => now()]);
    }
};
