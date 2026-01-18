<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Link widgets to ai_agents and move knowledge_bases relationship
     */
    public function up(): void
    {
        // Add ai_agent_id to widgets
        if (!Schema::hasColumn('widgets', 'ai_agent_id')) {
            Schema::table('widgets', function (Blueprint $table) {
                $table->foreignId('ai_agent_id')->nullable()->after('user_id')->constrained('ai_agents')->onDelete('set null');
            });
        }

        // Add ai_agent_id to knowledge_bases (will migrate from widget_id)
        if (!Schema::hasColumn('knowledge_bases', 'ai_agent_id')) {
            Schema::table('knowledge_bases', function (Blueprint $table) {
                $table->foreignId('ai_agent_id')->nullable()->after('id')->constrained('ai_agents')->onDelete('cascade');
            });
        }

        // Add current_agent_id to chat_sessions for tracking
        if (!Schema::hasColumn('chat_sessions', 'current_agent_id')) {
            Schema::table('chat_sessions', function (Blueprint $table) {
                $table->foreignId('current_agent_id')->nullable()->after('widget_id')->constrained('ai_agents')->onDelete('set null');
            });
        }

        // Add ai_agent_id to chat_messages for tracking which agent responded
        if (!Schema::hasColumn('chat_messages', 'ai_agent_id')) {
            Schema::table('chat_messages', function (Blueprint $table) {
                $table->foreignId('ai_agent_id')->nullable()->after('session_id')->constrained('ai_agents')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropForeign(['ai_agent_id']);
            $table->dropColumn('ai_agent_id');
        });

        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->dropForeign(['current_agent_id']);
            $table->dropColumn('current_agent_id');
        });

        Schema::table('knowledge_bases', function (Blueprint $table) {
            $table->dropForeign(['ai_agent_id']);
            $table->dropColumn('ai_agent_id');
        });

        Schema::table('widgets', function (Blueprint $table) {
            $table->dropForeign(['ai_agent_id']);
            $table->dropColumn('ai_agent_id');
        });
    }
};
