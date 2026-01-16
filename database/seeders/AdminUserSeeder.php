<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@cekat.biz.id',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'plan_tier' => 'business',
            'monthly_message_quota' => 10000,
            'monthly_message_used' => 0,
        ]);

        // Create widget for admin
        $widget = $admin->widgets()->create([
            'name' => 'Admin Widget',
            'slug' => 'admin-widget',
            'is_active' => true,
        ]);

        // Create knowledge base for admin widget
        $widget->knowledgeBase()->create([
            'company_name' => 'Cekat Admin',
            'persona_name' => 'Admin Assistant',
            'persona_tone' => 'professional',
        ]);

        $this->command->info('✅ Admin user created successfully!');
        $this->command->info('   Email: admin@cekat.biz.id');
        $this->command->info('   Password: admin123');
    }
}
