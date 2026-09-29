<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Users registered before email verification existed are trusted -
     * mark them verified so only NEW registrations must verify. Runs in
     * the same deploy that enables the 'verified' middleware (no lockout).
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // Intentionally irreversible: re-nulling would lock out existing users.
    }
};
