<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Index for the scheduled chat:purge retention sweep
     * (delete sessions created_at < per-plan cutoff).
     */
    public function up(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->index('created_at', 'chat_sessions_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->dropIndex('chat_sessions_created_at_index');
        });
    }
};
