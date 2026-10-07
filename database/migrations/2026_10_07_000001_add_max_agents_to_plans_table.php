<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the per-plan AI agent quota (remediation T-02 / finding F-02).
     * Until now nothing limited how many agents a plan could create.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (! Schema::hasColumn('plans', 'max_agents')) {
                $table->unsignedInteger('max_agents')->default(1)->after('max_widgets');
            }
        });

        // Backfill canonical quotas per plan slug (owner decisions, remediation plan Bab 2).
        \DB::table('plans')->whereIn('slug', ['free', 'starter'])->update(['max_agents' => 1]);
        \DB::table('plans')->where('slug', 'pro')->update(['max_agents' => 3]);
        \DB::table('plans')->where('slug', 'business')->update(['max_agents' => 10]);
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'max_agents')) {
                $table->dropColumn('max_agents');
            }
        });
    }
};
