<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class WhatsAppSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // WhatsApp Module Settings
        $settings = [
            [
                'key' => 'whatsapp_module_enabled',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'whatsapp',
                'description' => 'Enable or disable WhatsApp integration module',
                'is_public' => false,
            ],
            [
                'key' => 'fonnte_account_token',
                'value' => '',
                'type' => 'string',
                'group' => 'whatsapp',
                'description' => 'Fonnte Account Token for device management',
                'is_public' => false,
            ],
            [
                'key' => 'whatsapp_fallback_message',
                'value' => 'Maaf, saya sedang mengalami gangguan teknis. Silakan coba lagi nanti.',
                'type' => 'string',
                'group' => 'whatsapp',
                'description' => 'Fallback message when AI fails to respond',
                'is_public' => false,
            ],
            [
                'key' => 'whatsapp_auto_reply_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'whatsapp',
                'description' => 'Enable automatic AI replies for WhatsApp messages',
                'is_public' => false,
            ],
            [
                'key' => 'whatsapp_max_devices_per_user',
                'value' => '1',
                'type' => 'number',
                'group' => 'whatsapp',
                'description' => 'Maximum number of WhatsApp devices per user (free plan)',
                'is_public' => false,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('WhatsApp settings seeded successfully!');
    }
}
