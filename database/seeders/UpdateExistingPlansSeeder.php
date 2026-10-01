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

        // Update Starter plan
        Plan::where('slug', 'starter')->update([
            'chat_history_days' => 7,
            'can_export_leads' => false,
            'can_use_whatsapp' => false,
            'max_whatsapp_devices' => 1,
            'ai_tier' => 'basic',
        ]);
        $this->command->info('✅ Starter plan updated');

        // Update Pro plan
        Plan::where('slug', 'pro')->update([
            'chat_history_days' => 30,
            'can_export_leads' => true,
            'can_use_whatsapp' => true,
            'max_whatsapp_devices' => 3,
            'ai_tier' => 'advanced',
        ]);
        $this->command->info('✅ Pro plan updated');

        // Update Business plan
        Plan::where('slug', 'business')->update([
            'chat_history_days' => 90,
            'can_export_leads' => true,
            'can_use_whatsapp' => true,
            'max_whatsapp_devices' => 10,
            'ai_tier' => 'premium',
        ]);
        $this->command->info('✅ Business plan updated');

        $this->command->info('✅ All plans updated successfully!');
    }
}
