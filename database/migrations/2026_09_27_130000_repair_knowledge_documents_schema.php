<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * knowledge_documents was created twice with two different shapes:
 * - 2026_01_13_121646 (via knowledge_bases) => filename/file_type/content_text,
 *   status enum(pending|processing|ready|failed)
 * - 2026_01_13_152828 => name/type/content/chunks, status enum with 'completed',
 *   but it skips itself when the table already exists.
 *
 * The model (KnowledgeDocument) expects the second shape, so fresh installs
 * break on document upload. This migration repairs the table in place,
 * preserving existing rows.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('knowledge_documents')) {
            return;
        }

        $driver = DB::getDriverName();

        // Already the model shape: only the status vocabulary may lag behind.
        if (Schema::hasColumn('knowledge_documents', 'name')) {
            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE knowledge_documents MODIFY status ENUM('pending','processing','ready','completed','failed') DEFAULT 'processing'");
            }

            return;
        }

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE knowledge_documents ADD COLUMN name VARCHAR(255) NULL AFTER knowledge_base_id');
            DB::statement('ALTER TABLE knowledge_documents ADD COLUMN type VARCHAR(10) NULL AFTER name');
            DB::statement('ALTER TABLE knowledge_documents ADD COLUMN url VARCHAR(500) NULL AFTER file_path');
            DB::statement('ALTER TABLE knowledge_documents ADD COLUMN content LONGTEXT NULL');
            DB::statement('ALTER TABLE knowledge_documents ADD COLUMN chunks JSON NULL');
            DB::statement("ALTER TABLE knowledge_documents MODIFY status ENUM('pending','processing','ready','completed','failed') DEFAULT 'processing'");
            DB::statement('UPDATE knowledge_documents SET name = filename, type = file_type, content = content_text');
            DB::statement('ALTER TABLE knowledge_documents DROP COLUMN filename, DROP COLUMN file_type, DROP COLUMN content_text');

            return;
        }

        // SQLite / Postgres: portable rebuild that keeps existing rows.
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE document_chunks DROP CONSTRAINT IF EXISTS document_chunks_knowledge_document_id_foreign');
        }

        $rows = DB::table('knowledge_documents')->get()->map(fn ($r) => (array) $r)->all();

        Schema::dropIfExists('knowledge_documents_rebuild');

        Schema::create('knowledge_documents_rebuild', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_base_id')->constrained('knowledge_bases')->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('type')->nullable();
            $table->string('file_path')->nullable();
            $table->string('url')->nullable();
            $table->longText('content')->nullable();
            $table->json('chunks')->nullable();
            $table->string('status')->default('processing');
            $table->timestamps();
        });

        foreach ($rows as $row) {
            DB::table('knowledge_documents_rebuild')->insert([
                'id' => $row['id'],
                'knowledge_base_id' => $row['knowledge_base_id'],
                'name' => $row['filename'] ?? null,
                'type' => $row['file_type'] ?? null,
                'file_path' => $row['file_path'] ?? null,
                'url' => $row['url'] ?? null,
                'content' => $row['content_text'] ?? null,
                'chunks' => null,
                // legacy 'ready' == model 'completed'
                'status' => ($row['status'] ?? null) === 'ready' ? 'completed' : ($row['status'] ?? 'processing'),
                'created_at' => $row['created_at'] ?? null,
                'updated_at' => $row['updated_at'] ?? null,
            ]);
        }

        Schema::drop('knowledge_documents');
        Schema::rename('knowledge_documents_rebuild', 'knowledge_documents');

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE document_chunks ADD CONSTRAINT document_chunks_knowledge_document_id_foreign FOREIGN KEY (knowledge_document_id) REFERENCES knowledge_documents(id) ON DELETE CASCADE');
        }
    }

    public function down(): void
    {
        // Repair is intentionally not reversible (it would drop document data).
    }
};
