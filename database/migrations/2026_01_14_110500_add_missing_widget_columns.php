<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('widgets', 'display_name')) {
            Schema::table('widgets', function (Blueprint $table) {
                $table->string('display_name')->nullable()->after('name');
            });
        }

        if (!Schema::hasColumn('widgets', 'description')) {
            Schema::table('widgets', function (Blueprint $table) {
                $table->text('description')->nullable()->after('display_name');
            });
        }

        if (!Schema::hasColumn('widgets', 'status')) {
            Schema::table('widgets', function (Blueprint $table) {
                $table->string('status')->default('active')->after('is_active');
            });
        }
    }

    public function down(): void
    {
        Schema::table('widgets', function (Blueprint $table) {
            $table->dropColumn(['display_name', 'description', 'status']);
        });
    }
};
