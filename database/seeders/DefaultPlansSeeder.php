<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;
use App\Models\Setting;

class DefaultPlansSeeder extends Seeder
{
    public function run(): void
    {
        // Create default plans
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Perfect for trying out Cekat',
                'price' => 0,
                'billing_period' => 'monthly',
                'max_widgets' => 1,
                'max_messages_per_month' => 100,
                'max_documents' => 3,
                'max_file_size_mb' => 5,
                'max_faqs' => 10,
                'chat_history_days' => 7,
                'can_export_leads' => false,
                'can_use_whatsapp' => false,
                'ai_tier' => 'basic',
                'allowed_models' => ['nvidia/nemotron-3-nano-30b-a3b:free'],
                'features' => [
                    'custom_branding' => false,
                    'analytics' => 'basic',
                    'priority_support' => false,
                    'api_access' => false,
                ],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'For growing businesses',
                'price' => 299000,
                'billing_period' => 'monthly',
                'max_widgets' => 3,
                'max_messages_per_month' => 2000,
                'max_documents' => 20,
                'max_file_size_mb' => 10,
                'max_faqs' => 50,
                'chat_history_days' => 30,
                'can_export_leads' => true,
                'can_use_whatsapp' => false,
                'ai_tier' => 'advanced',
                'allowed_models' => [
                    'nvidia/nemotron-3-nano-30b-a3b:free',
                    'openai/gpt-4o-mini',
                ],
                'features' => [
                    'custom_branding' => true,
                    'analytics' => 'advanced',
                    'priority_support' => false,
                    'api_access' => false,
                ],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'description' => 'For enterprises',
                'price' => 799000,
                'billing_period' => 'monthly',
                'max_widgets' => 10,
                'max_messages_per_month' => 10000,
                'max_documents' => 100,
                'max_file_size_mb' => 20,
                'max_faqs' => 999,
                'chat_history_days' => 90,
                'can_export_leads' => true,
                'can_use_whatsapp' => true,
                'ai_tier' => 'premium',
                'allowed_models' => [
                    'nvidia/nemotron-3-nano-30b-a3b:free',
                    'openai/gpt-4o-mini',
                    'openai/gpt-4o',
                    'anthropic/claude-3.5-sonnet',
                ],
                'features' => [
                    'custom_branding' => true,
                    'analytics' => 'advanced',
                    'priority_support' => true,
                    'api_access' => true,
                    'white_label' => true,
                ],
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $planData) {
            Plan::create($planData);
        }

        // Create default settings
        $settings = [
            // API Settings
            ['key' => 'openrouter_api_key', 'value' => config('services.openrouter.api_key', ''), 'type' => 'string', 'group' => 'api', 'description' => 'OpenRouter API Key'],
            ['key' => 'default_ai_model', 'value' => 'nvidia/nemotron-3-nano-30b-a3b:free', 'type' => 'string', 'group' => 'api', 'description' => 'Default AI Model'],
            ['key' => 'api_timeout', 'value' => '30', 'type' => 'number', 'group' => 'api', 'description' => 'API Timeout (seconds)'],

            // General Settings
            ['key' => 'site_name', 'value' => 'Cekat.biz.id', 'type' => 'string', 'group' => 'general', 'description' => 'Site Name'],
            ['key' => 'site_url', 'value' => config('app.url'), 'type' => 'string', 'group' => 'general', 'description' => 'Site URL'],
            ['key' => 'support_email', 'value' => 'support@cekat.biz.id', 'type' => 'string', 'group' => 'general', 'description' => 'Support Email'],
            ['key' => 'allow_registration', 'value' => '1', 'type' => 'boolean', 'group' => 'general', 'description' => 'Allow New Registrations'],
            ['key' => 'maintenance_mode', 'value' => '0', 'type' => 'boolean', 'group' => 'general', 'description' => 'Maintenance Mode'],

            // Limits
            ['key' => 'max_upload_size_mb', 'value' => '10', 'type' => 'number', 'group' => 'limits', 'description' => 'Max Upload Size (MB)'],
            ['key' => 'session_timeout_minutes', 'value' => '120', 'type' => 'number', 'group' => 'limits', 'description' => 'Session Timeout (minutes)'],
            ['key' => 'chat_retention_days', 'value' => '90', 'type' => 'number', 'group' => 'limits', 'description' => 'Chat History Retention (days)'],
        ];

        foreach ($settings as $settingData) {
            Setting::create($settingData);
        }

        $this->command->info('✅ Default plans and settings created successfully!');
    }
}
