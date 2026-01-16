<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('knowledge_documents')) {
            return; // Table already exists
        }

        Schema::create('knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_base_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['pdf', 'docx', 'txt', 'url']);
            $table->string('file_path')->nullable();
            $table->string('url')->nullable();
            $table->longText('content')->nullable();
            $table->json('chunks')->nullable();
            $table->enum('status', ['processing', 'completed', 'failed'])->default('processing');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_documents');
    }
};
