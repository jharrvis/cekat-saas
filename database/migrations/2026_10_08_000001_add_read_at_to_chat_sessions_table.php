<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Unread tracking for the member sidebar counters: a session is
 * "belum dibuka" while read_at is null. Opening the session detail
 * (chats.show) stamps read_at; a new visitor exchange clears it.
 * Existing history is backfilled as already-read so the counters
 * start at zero and only new activity lights them up.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->timestamp('read_at')->nullable()->after('is_preview');
        });

        DB::table('chat_sessions')->whereNull('read_at')->update([
            'read_at' => DB::raw('created_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->dropColumn('read_at');
        });
    }
};
