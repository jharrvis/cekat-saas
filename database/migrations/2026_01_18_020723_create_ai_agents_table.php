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
        Schema::create('ai_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Basic Info
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('avatar_url')->nullable();

            // AI Configuration
            $table->string('ai_model', 100)->default('google/gemini-2.0-flash-001');
            $table->decimal('ai_temperature', 2, 1)->default(0.7);
            $table->text('system_prompt')->nullable();
            $table->enum('personality', ['professional', 'friendly', 'casual', 'formal'])->default('friendly');

            // Behavior Settings
            $table->integer('max_tokens')->default(500);
            $table->string('language', 10)->default('id');
            $table->text('fallback_message')->nullable();

            // Greeting (moved from widget potentially)
            $table->text('greeting_message')->nullable();

            // Usage Stats
            $table->unsignedInteger('messages_used')->default(0);
            $table->unsignedInteger('conversations_count')->default(0);

            // Settings JSON for extensibility
            $table->json('settings')->nullable();

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_agents');
    }
};
