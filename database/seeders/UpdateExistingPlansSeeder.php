<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class UpdateExistingPlansSeeder extends Seeder
{
    /**
     * Update existing plans with missing fields
     */
    public function run(): void
    {
        $this->command->info('🔄 Updating existing plans...');

        // Reconcile a physical Free plan (if present) with PlanLimitService::SCHEMA_DEFAULTS.
        // No-op when the row does not exist. Price/features of Free are not touched.
        Plan::where('slug', 'free')->update([
            'chat_history_days' => 7,
            'can_export_leads' => false,
            'can_use_whatsapp' => false,
            'ai_tier' => 'basic',
        ]);
        $this->command->info('✅ Free plan reconciled (if present)');

        // Update Starter plan
        Plan::where('slug', 'starter')->update([
            'chat_history_days' => 7,
            'can_export_leads' => false,
            'can_use_whatsapp' => false,
            'max_whatsapp_devices' => 1,
            'ai_tier' => 'basic',
            'features' => [
                'custom_branding' => false,
                'analytics' => 'basic',
                'priority_support' => false,
                'api_access' => false,
            ],
        ]);
        $this->command->info('✅ Starter plan updated');

        // Update Pro plan
        // Price follows the live production price (Rp99.000) — owner decision, do not
        // revert to the DefaultPlansSeeder price. api_access is enabled because Pro
        // users already use API keys in production.
        Plan::where('slug', 'pro')->update([
            'price' => 99000,
            'chat_history_days' => 30,
            'can_export_leads' => true,
            'can_use_whatsapp' => true,
            'max_whatsapp_devices' => 3,
            'ai_tier' => 'advanced',
            'features' => [
                'custom_branding' => true,
                'analytics' => 'advanced',
                'priority_support' => false,
                'api_access' => true,
            ],
        ]);
        $this->command->info('✅ Pro plan updated');

        // Update Business plan
        // Price rounded to Rp199.000 per owner decision (production had 198.996).
        Plan::where('slug', 'business')->update([
            'price' => 199000,
            'chat_history_days' => 90,
            'can_export_leads' => true,
            'can_use_whatsapp' => true,
            'max_whatsapp_devices' => 10,
            'ai_tier' => 'premium',
            'features' => [
                'custom_branding' => true,
                'analytics' => 'advanced',
                'priority_support' => true,
                'api_access' => true,
                'white_label' => true,
            ],
        ]);
        $this->command->info('✅ Business plan updated');

        $this->command->info('✅ All plans updated successfully!');
    }
}
