<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Force make widget_id nullable again
        // This is needed because the previous migration might have been marked as run 
        // while it was commented out in the codebase.
        if (Schema::hasColumn('knowledge_bases', 'widget_id')) {
            DB::statement('ALTER TABLE knowledge_bases MODIFY widget_id BIGINT UNSIGNED NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We don't want to make it not-null again as it breaks the app
    }
};
