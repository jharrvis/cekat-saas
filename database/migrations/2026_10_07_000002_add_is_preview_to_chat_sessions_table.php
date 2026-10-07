<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T-07: mark chat sessions that come from owner test surfaces (the agent
 * "Uji Coba" panel and the widget customizer preview). Preview sessions
 * never consume the owner's monthly quota and are excluded from the
 * customer-facing chat history, so tests stop polluting real data.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('chat_sessions', 'is_preview')) {
            Schema::table('chat_sessions', function (Blueprint $table) {
                $table->boolean('is_preview')->default(false)->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('chat_sessions', 'is_preview')) {
            Schema::table('chat_sessions', function (Blueprint $table) {
                $table->dropColumn('is_preview');
            });
        }
    }
};
