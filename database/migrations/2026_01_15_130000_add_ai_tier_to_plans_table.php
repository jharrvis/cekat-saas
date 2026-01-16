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
        Schema::table('plans', function (Blueprint $table) {
            if (!Schema::hasColumn('plans', 'ai_tier')) {
                $table->string('ai_tier')->default('basic')->after('features');
            }
            if (!Schema::hasColumn('plans', 'chat_history_days')) {
                $table->integer('chat_history_days')->default(7)->after('max_faqs');
            }
            if (!Schema::hasColumn('plans', 'can_export_leads')) {
                $table->boolean('can_export_leads')->default(false)->after('chat_history_days');
            }
            if (!Schema::hasColumn('plans', 'can_use_whatsapp')) {
                $table->boolean('can_use_whatsapp')->default(false)->after('can_export_leads');
            }
        });

        // Seed default AI tier mapping setting
        \App\Models\Setting::updateOrCreate(
            ['key' => 'ai_tier_mapping'],
            [
                'value' => json_encode([
                    'basic' => 'nvidia/nemotron-3-nano-30b-a3b:free',
                    'standard' => 'openai/gpt-4o-mini',
                    'advanced' => 'openai/gpt-4o',
                    'premium' => 'anthropic/claude-3.5-sonnet',
                ])
            ]
        );

        // Update existing plans with default ai_tier
        \DB::table('plans')->where('slug', 'free')->update(['ai_tier' => 'basic']);
        \DB::table('plans')->where('slug', 'starter')->update(['ai_tier' => 'standard']);
        \DB::table('plans')->where('slug', 'pro')->update(['ai_tier' => 'advanced']);
        \DB::table('plans')->where('slug', 'business')->update(['ai_tier' => 'premium']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['ai_tier', 'chat_history_days', 'can_export_leads', 'can_use_whatsapp']);
        });

        \App\Models\Setting::where('key', 'ai_tier_mapping')->delete();
    }
};
