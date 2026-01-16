<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Plan;
use App\Models\User;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * This migration fixes user plan_id relationships based on their existing plan_tier field.
     */
    public function up(): void
    {
        // Map plan_tier values to plan slugs
        $tierToSlug = [
            'starter' => 'starter',
            'pro' => 'pro',
            'business' => 'business',
        ];

        foreach ($tierToSlug as $tier => $slug) {
            $plan = Plan::where('slug', $slug)->first();
            if ($plan) {
                // Update users who have this tier but no plan_id
                User::where('plan_tier', $tier)
                    ->whereNull('plan_id')
                    ->update(['plan_id' => $plan->id]);
            }
        }

        // For users without any plan_tier, assign Starter plan as default
        $starterPlan = Plan::where('slug', 'starter')->first();
        if ($starterPlan) {
            User::whereNull('plan_id')
                ->update(['plan_id' => $starterPlan->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We can't easily reverse this migration
    }
};
