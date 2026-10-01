<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ai_agents', function (Blueprint $table) {
            // Greeting widget diatur per-Channel (widget settings['greeting']).
            // Field agent ini dead code - tak pernah dibaca renderer mana pun.
            $table->dropColumn('greeting_message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_agents', function (Blueprint $table) {
            $table->text('greeting_message')->nullable()->after('fallback_message');
        });
    }
};
