<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Setting;
use App\Models\WhatsAppDevice;
use App\Services\WhatsApp\FonnteService;
use App\Services\WhatsApp\WhatsAppManager;

/**
 * Admin WhatsApp Module Manager
 * 
 * Allows admin to:
 * - Enable/disable WhatsApp module globally
 * - Configure Fonnte account token
 * - View all devices across users
 * - Monitor usage statistics
 */
class WhatsAppSettings extends Component
{
    // Module Settings
    public bool $moduleEnabled = false;
    public string $fonnteAccountToken = '';
    public string $fallbackMessage = '';
    public bool $autoReplyEnabled = true;
    public int $maxDevicesPerUser = 1;

    // Statistics
    public int $totalDevices = 0;
    public int $connectedDevices = 0;
    public int $totalMessagesSent = 0;
    public int $totalMessagesReceived = 0;

    // UI State
    public string $activeTab = 'settings';
    public bool $showTokenAlert = false;
    public string $testResult = '';

    // Devices list (for monitor tab)
    public $devices = [];

    public function mount()
    {
        $this->loadSettings();
        $this->loadStatistics();
    }

    public function loadSettings()
    {
        $this->moduleEnabled = (bool) Setting::get('whatsapp_module_enabled', false);
        $this->fonnteAccountToken = Setting::get('fonnte_account_token', '');
        $this->fallbackMessage = Setting::get(
            'whatsapp_fallback_message',
            'Maaf, saya sedang mengalami gangguan teknis. Silakan coba lagi nanti.'
        );
        $this->autoReplyEnabled = (bool) Setting::get('whatsapp_auto_reply_enabled', true);
        $this->maxDevicesPerUser = (int) Setting::get('whatsapp_max_devices_per_user', 1);
    }

    public function loadStatistics()
    {
        $this->totalDevices = WhatsAppDevice::count();
        $this->connectedDevices = WhatsAppDevice::where('status', 'connected')->count();
        $this->totalMessagesSent = WhatsAppDevice::sum('messages_sent');
        $this->totalMessagesReceived = WhatsAppDevice::sum('messages_received');
    }

    public function loadDevices()
    {
        $this->devices = WhatsAppDevice::with(['user', 'widget'])
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();
    }

    /**
     * Toggle module enabled/disabled.
     */
    public function toggleModule()
    {
        $this->moduleEnabled = !$this->moduleEnabled;
        Setting::set('whatsapp_module_enabled', $this->moduleEnabled, 'boolean', 'whatsapp');

        session()->flash('message', $this->moduleEnabled
            ? 'WhatsApp module enabled!'
            : 'WhatsApp module disabled!');
    }

    /**
     * Save all settings.
     */
    public function saveSettings()
    {
        $this->validate([
            'fonnteAccountToken' => 'required_if:moduleEnabled,true|string|max:500',
            'fallbackMessage' => 'required|string|max:500',
            'maxDevicesPerUser' => 'required|integer|min:1|max:100',
        ]);

        // Save settings
        Setting::set('whatsapp_module_enabled', $this->moduleEnabled, 'boolean', 'whatsapp');
        Setting::set('fonnte_account_token', $this->fonnteAccountToken, 'string', 'whatsapp');
        Setting::set('whatsapp_fallback_message', $this->fallbackMessage, 'string', 'whatsapp');
        Setting::set('whatsapp_auto_reply_enabled', $this->autoReplyEnabled, 'boolean', 'whatsapp');
        Setting::set('whatsapp_max_devices_per_user', $this->maxDevicesPerUser, 'number', 'whatsapp');

        session()->flash('message', 'WhatsApp settings saved successfully!');
    }

    /**
     * Test Fonnte connection.
     */
    public function testConnection()
    {
        if (empty($this->fonnteAccountToken)) {
            $this->testResult = 'error:Please enter Fonnte Account Token first.';
            return;
        }

        try {
            // Temporarily set the token
            Setting::set('fonnte_account_token', $this->fonnteAccountToken, 'string', 'whatsapp');

            $fonnte = new FonnteService();
            $devices = $fonnte->getDevices();

            $deviceCount = count($devices);
            $this->testResult = "success:Connection successful! Found {$deviceCount} device(s) in your Fonnte account.";

        } catch (\Exception $e) {
            $this->testResult = 'error:' . $e->getMessage();
        }
    }

    /**
     * Sync devices from Fonnte.
     */
    public function syncDevices()
    {
        try {
            $fonnte = new FonnteService();
            $fonnteDevices = $fonnte->getDevices();

            $synced = 0;
            foreach ($fonnteDevices as $fDevice) {
                // Check if device exists locally
                $localDevice = WhatsAppDevice::where('fonnte_device_token', $fDevice['token'] ?? null)->first();

                if ($localDevice) {
                    // Update status
                    $localDevice->update([
                        'status' => $fDevice['status'] === 'connect' ? 'connected' : 'disconnected',
                        'phone_number' => $fDevice['device'] ?? $localDevice->phone_number,
                    ]);
                    $synced++;
                }
            }

            session()->flash('message', "Synced {$synced} device(s) from Fonnte.");
            $this->loadStatistics();
            $this->loadDevices();

        } catch (\Exception $e) {
            session()->flash('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    /**
     * Force disconnect all devices.
     */
    public function disconnectAllDevices()
    {
        $fonnte = new FonnteService();
        $devices = WhatsAppDevice::where('status', 'connected')->get();

        $disconnected = 0;
        foreach ($devices as $device) {
            try {
                if ($device->fonnte_device_token) {
                    $fonnte->disconnectDevice($device->fonnte_device_token);
                }
                $device->update([
                    'status' => 'disconnected',
                    'disconnected_at' => now(),
                ]);
                $disconnected++;
            } catch (\Exception $e) {
                // Continue with next device
            }
        }

        session()->flash('message', "Disconnected {$disconnected} device(s).");
        $this->loadStatistics();
        $this->loadDevices();
    }

    /**
     * Disconnect a single device (admin).
     */
    public function disconnectDevice(int $deviceId)
    {
        $device = WhatsAppDevice::find($deviceId);
        if (!$device) {
            session()->flash('error', 'Device not found.');
            return;
        }

        try {
            $fonnte = new FonnteService();
            if ($device->fonnte_device_token) {
                $fonnte->disconnectDevice($device->fonnte_device_token);
            }
            $device->update([
                'status' => 'disconnected',
                'disconnected_at' => now(),
            ]);
            session()->flash('message', "Device '{$device->device_name}' disconnected successfully.");
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to disconnect: ' . $e->getMessage());
        }

        $this->loadStatistics();
        $this->loadDevices();
    }

    /**
     * Delete a device permanently (admin).
     */
    public function deleteDevice(int $deviceId)
    {
        $device = WhatsAppDevice::find($deviceId);
        if (!$device) {
            session()->flash('error', 'Device not found.');
            return;
        }

        try {
            $manager = new WhatsAppManager();
            $manager->deleteDevice($device);
            session()->flash('message', "Device '{$device->device_name}' deleted successfully.");
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete: ' . $e->getMessage());
        }

        $this->loadStatistics();
        $this->loadDevices();
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;

        if ($tab === 'monitor') {
            $this->loadDevices();
        }
    }

    public function render()
    {
        return view('livewire.admin.whatsapp-settings')
            ->extends('layouts.dashboard')
            ->section('content');
    }
}
