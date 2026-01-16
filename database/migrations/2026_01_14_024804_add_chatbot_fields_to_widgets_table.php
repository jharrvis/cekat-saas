<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('widgets', function (Blueprint $table) {
            if (!Schema::hasColumn('widgets', 'display_name')) {
                $table->string('display_name')->after('name')->nullable();
            }
            if (!Schema::hasColumn('widgets', 'description')) {
                $table->text('description')->after('display_name')->nullable();
            }
            if (!Schema::hasColumn('widgets', 'status')) {
                $table->enum('status', ['active', 'inactive', 'draft'])->default('active')->after('is_active');
            }
        });

        // Update existing widgets to have display_name
        \DB::table('widgets')->whereNull('display_name')->update([
            'display_name' => \DB::raw('name'),
            'status' => 'active'
        ]);
    }

    public function down(): void
    {
        Schema::table('widgets', function (Blueprint $table) {
            $table->dropColumn(['display_name', 'description', 'status']);
        });
    }
};
