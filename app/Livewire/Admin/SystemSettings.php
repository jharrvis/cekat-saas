<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Setting;

class SystemSettings extends Component
{
    public $settings = [];
    public $activeTab = 'general';

    // Settings by group
    public $generalSettings = [];
    public $apiSettings = [];
    public $limitsSettings = [];

    public function mount()
    {
        $this->loadSettings();
    }

    public function loadSettings()
    {
        $allSettings = Setting::all();

        foreach ($allSettings as $setting) {
            $this->settings[$setting->key] = Setting::get($setting->key);
        }

        $this->generalSettings = Setting::getByGroup('general');
        $this->apiSettings = Setting::getByGroup('api');
        $this->limitsSettings = Setting::getByGroup('limits');
    }

    public function saveSettings($group)
    {
        $settingsToSave = match ($group) {
            'general' => $this->generalSettings,
            'api' => $this->apiSettings,
            'limits' => $this->limitsSettings,
            default => [],
        };

        // Define types for each setting key
        $settingTypes = [
            // General
            'site_name' => 'string',
            'site_url' => 'string',
            'support_email' => 'string',
            'allow_registration' => 'boolean',
            'maintenance_mode' => 'boolean',
            // API
            'openrouter_api_key' => 'string',
            'default_ai_model' => 'string',
            'api_timeout' => 'number',
            // Limits
            'max_upload_size_mb' => 'number',
            'session_timeout_minutes' => 'number',
            'chat_retention_days' => 'number',
        ];

        foreach ($settingsToSave as $key => $value) {
            $type = $settingTypes[$key] ?? 'string';
            Setting::set($key, $value, $type, $group);
        }

        session()->flash('message', ucfirst($group) . ' settings saved successfully!');
        $this->loadSettings();
    }

    public function render()
    {
        return view('livewire.admin.system-settings')
            ->extends('layouts.dashboard')
            ->section('content');
    }
}
