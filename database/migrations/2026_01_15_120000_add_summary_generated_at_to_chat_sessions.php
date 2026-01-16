<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('chat_sessions', 'summary_generated_at')) {
                $table->timestamp('summary_generated_at')->nullable()->after('summary');
            }
            if (!Schema::hasColumn('chat_sessions', 'session_id')) {
                $table->string('session_id')->nullable()->after('id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->dropColumn(['summary_generated_at', 'session_id']);
        });
    }
};
