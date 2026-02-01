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
        // Make widget_id nullable to support AI Agent-based knowledge bases (without widget)
        DB::statement('ALTER TABLE knowledge_bases MODIFY widget_id BIGINT UNSIGNED NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not reversing as it would break existing data
    }
};
