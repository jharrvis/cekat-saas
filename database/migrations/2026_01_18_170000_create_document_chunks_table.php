<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('document_chunks')) {
            return;
        }

        Schema::create('document_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_document_id')->constrained('knowledge_documents')->onDelete('cascade');
            $table->unsignedInteger('chunk_index');
            $table->text('content');
            $table->unsignedInteger('token_count')->default(0);
            $table->binary('embedding')->nullable(); // For future vector search
            $table->timestamps();

            $table->index(['knowledge_document_id', 'chunk_index'], 'idx_doc_chunk');
        });

        // Add additional columns to knowledge_bases for quota management
        if (!Schema::hasColumn('knowledge_bases', 'max_documents')) {
            Schema::table('knowledge_bases', function (Blueprint $table) {
                $table->unsignedInteger('max_documents')->default(10);
                $table->unsignedInteger('max_file_size')->default(10485760); // 10MB
                $table->unsignedBigInteger('total_storage_used')->default(0);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_chunks');

        if (Schema::hasColumn('knowledge_bases', 'max_documents')) {
            Schema::table('knowledge_bases', function (Blueprint $table) {
                $table->dropColumn(['max_documents', 'max_file_size', 'total_storage_used']);
            });
        }
    }
};
