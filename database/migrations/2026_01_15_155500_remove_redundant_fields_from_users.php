<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * Remove redundant fields from users table:
     * - plan_tier: Now comes from plan.ai_tier
     * - monthly_message_quota: Now comes from plan.max_messages_per_month
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'plan_tier')) {
                $table->dropColumn('plan_tier');
            }
            if (Schema::hasColumn('users', 'monthly_message_quota')) {
                $table->dropColumn('monthly_message_quota');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('plan_tier')->nullable();
            $table->integer('monthly_message_quota')->default(100);
        });
    }
};
