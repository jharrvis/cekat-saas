<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('llm_models', function (Blueprint $table) {
            $table->id();
            $table->string('model_id')->unique(); // e.g., openai/gpt-4o
            $table->string('name'); // e.g., GPT-4o
            $table->string('provider'); // e.g., OpenAI
            $table->text('description')->nullable();
            $table->decimal('input_price', 10, 6)->default(0); // per 1M tokens
            $table->decimal('output_price', 10, 6)->default(0); // per 1M tokens
            $table->integer('context_length')->default(4096);
            $table->json('allowed_tiers')->nullable(); // ['starter', 'pro', 'business']
            $table->boolean('is_active')->default(true);
            $table->integer('popularity')->default(0); // 0-100
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('llm_models');
    }
};
