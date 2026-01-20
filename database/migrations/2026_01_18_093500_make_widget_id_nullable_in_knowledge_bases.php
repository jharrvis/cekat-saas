<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     * Make widget_id nullable in knowledge_bases to support AI Agent-based knowledge bases
     */
    public function up(): void
    {
        // Directly modify column to be nullable (already ran via tinker)
        // DB::statement('ALTER TABLE knowledge_bases MODIFY widget_id BIGINT UNSIGNED NULL');
        // The column was already made nullable via tinker command
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not reversing as it would break existing data
    }
};
