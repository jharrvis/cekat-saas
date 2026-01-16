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
        Schema::create('knowledge_bases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('widget_id')->constrained()->onDelete('cascade');
            $table->string('company_name')->nullable();
            $table->text('company_description')->nullable();
            $table->string('persona_name')->nullable();
            $table->string('persona_tone')->nullable();
            $table->text('custom_instructions')->nullable();
            $table->timestamps();
        });

        Schema::create('knowledge_faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_base_id')->constrained()->onDelete('cascade');
            $table->text('question');
            $table->text('answer');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_base_id')->constrained()->onDelete('cascade');
            $table->string('filename')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->enum('file_type', ['pdf', 'docx', 'txt', 'url']);
            $table->enum('status', ['pending', 'processing', 'ready', 'failed'])->default('pending');
            $table->longText('content_text')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_documents');
        Schema::dropIfExists('knowledge_faqs');
        Schema::dropIfExists('knowledge_bases');
    }
};
