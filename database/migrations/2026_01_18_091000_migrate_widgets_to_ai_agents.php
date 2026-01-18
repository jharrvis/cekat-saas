<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration {
    /**
     * Run the migrations.
     * Migrate existing widgets to use ai_agents
     */
    public function up(): void
    {
        // Get all widgets with their settings (only those with valid user_id)
        $widgets = DB::table('widgets')->whereNotNull('user_id')->get();

        foreach ($widgets as $widget) {
            // Decode settings if JSON
            $settings = is_string($widget->settings) ? json_decode($widget->settings, true) : ($widget->settings ?? []);

            // Generate unique slug
            $baseSlug = Str::slug($widget->name . ' agent');
            $slug = $baseSlug;
            $counter = 1;
            while (DB::table('ai_agents')->where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            // Create AI Agent from widget settings
            $agentId = DB::table('ai_agents')->insertGetId([
                'user_id' => $widget->user_id,
                'name' => ($widget->display_name ?? $widget->name) . ' Agent',
                'slug' => $slug,
                'description' => $widget->description ?? null,
                'avatar_url' => $settings['avatar_url'] ?? null,
                'ai_model' => $settings['ai_model'] ?? 'google/gemini-2.0-flash-001',
                'ai_temperature' => $settings['ai_temperature'] ?? 0.7,
                'system_prompt' => $settings['system_prompt'] ?? null,
                'personality' => $settings['personality'] ?? 'friendly',
                'max_tokens' => $settings['max_tokens'] ?? 500,
                'language' => 'id',
                'greeting_message' => $settings['greeting'] ?? 'Halo! 👋 Ada yang bisa saya bantu?',
                'fallback_message' => 'Maaf, saya sedang mengalami gangguan teknis. Silakan coba lagi nanti.',
                'messages_used' => 0,
                'conversations_count' => 0,
                'settings' => json_encode([]),
                'is_active' => true,
                'created_at' => $widget->created_at ?? now(),
                'updated_at' => now(),
            ]);

            // Update widget to link to agent
            DB::table('widgets')
                ->where('id', $widget->id)
                ->update(['ai_agent_id' => $agentId]);

            // Update knowledge_base to link to agent
            DB::table('knowledge_bases')
                ->where('widget_id', $widget->id)
                ->update(['ai_agent_id' => $agentId]);

            // Update chat_sessions to set current_agent_id
            DB::table('chat_sessions')
                ->where('widget_id', $widget->id)
                ->update(['current_agent_id' => $agentId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear ai_agent_id references first
        DB::table('widgets')->update(['ai_agent_id' => null]);
        DB::table('knowledge_bases')->update(['ai_agent_id' => null]);
        DB::table('chat_sessions')->update(['current_agent_id' => null]);

        // Delete all ai_agents (they were auto-created from widgets)
        // Be careful: this will remove any manually created agents too!
        DB::table('ai_agents')->delete();
    }
};
