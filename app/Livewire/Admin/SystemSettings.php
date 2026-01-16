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

        foreach ($settingsToSave as $key => $value) {
            $setting = Setting::where('key', $key)->first();
            if ($setting) {
                Setting::set($key, $value, $setting->type, $setting->group);
            }
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
