<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Widget;

class FixWidgetDisplayNamesSeeder extends Seeder
{
    public function run(): void
    {
        $widgets = Widget::whereNull('display_name')->get();

        foreach ($widgets as $widget) {
            $widget->update([
                'display_name' => ucfirst(str_replace('-', ' ', $widget->name)),
                'status' => 'active'
            ]);
        }

        $this->command->info("✅ Updated {$widgets->count()} widgets with display names");
    }
}
