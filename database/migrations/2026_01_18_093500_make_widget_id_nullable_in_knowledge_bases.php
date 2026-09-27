<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Make widget_id nullable in knowledge_bases to support AI Agent-based knowledge bases
     */
    public function up(): void
    {
        // Make widget_id nullable to support AI Agent-based knowledge bases (without widget)
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE knowledge_bases MODIFY widget_id BIGINT UNSIGNED NULL');
            return;
        }

        // Postgres: native ALTER COLUMN preserves existing values.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE knowledge_bases ALTER COLUMN widget_id DROP NOT NULL');
            return;
        }

        // SQLite fallback: rebuild the column as nullable (no doctrine/dbal needed).
        // Existing widget_id values are preserved across the rebuild so knowledge
        // bases stay linked to their widgets on dev/staging databases with data.
        if (Schema::hasColumn('knowledge_bases', 'widget_id')) {
            $existingLinks = DB::table('knowledge_bases')->pluck('widget_id', 'id')->all();

            Schema::table('knowledge_bases', function (Blueprint $table) {
                try {
                    $table->dropForeign(['widget_id']);
                } catch (\Throwable $e) {
                    // SQLite ignores dropForeign; MySQL path returns earlier.
                }
            });
            Schema::table('knowledge_bases', function (Blueprint $table) {
                $table->dropColumn('widget_id');
            });
            Schema::table('knowledge_bases', function (Blueprint $table) {
                $table->unsignedBigInteger('widget_id')->nullable()->after('id');
                $table->foreign('widget_id')->references('id')->on('widgets')->onDelete('cascade');
            });

            foreach ($existingLinks as $id => $widgetId) {
                DB::table('knowledge_bases')->where('id', $id)->update(['widget_id' => $widgetId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not reversing as it would break existing data
    }
};
