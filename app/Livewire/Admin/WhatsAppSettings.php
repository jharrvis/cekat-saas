<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Setting;
use App\Models\WhatsAppDevice;
use App\Models\Widget;
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
    public string $leadNotifDeviceToken = '';
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

    // Platform device widget-link panel state
    public ?int $linkDeviceId = null;
    public string $linkDeviceLabel = '';
    public ?int $linkWidgetId = null;
    public array $linkWidgetOptions = [];

    // Platform device connect (QR) panel state
    public ?int $connectDeviceId = null;
    public string $connectDeviceLabel = '';
    public string $connectQrImage = '';
    public string $connectState = '';
    public string $connectError = '';

    public function mount()
    {
        $this->loadSettings();
        $this->loadStatistics();
    }

    public function loadSettings()
    {
        $this->moduleEnabled = (bool) Setting::get('whatsapp_module_enabled', false);
        $this->fonnteAccountToken = Setting::get('fonnte_account_token', '');
        $this->leadNotifDeviceToken = Setting::get('whatsapp_lead_notif_device_token', '');
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

        \App\Events\AdminSettingsChanged::dispatch('whatsapp', auth()->id(), ['whatsapp_module_enabled']);

        session()->flash('message', $this->moduleEnabled
            ? __('admin.s.whatsapp_module_enabled')
            : __('admin.s.whatsapp_module_disabled'));
    }

    /**
     * Save all settings.
     */
    public function saveSettings()
    {
        $this->validate([
            'fonnteAccountToken' => 'required_if:moduleEnabled,true|string|max:500',
            'leadNotifDeviceToken' => 'nullable|string|max:500',
            'fallbackMessage' => 'required|string|max:500',
            'maxDevicesPerUser' => 'required|integer|min:1|max:100',
        ]);

        // Save settings
        Setting::set('whatsapp_module_enabled', $this->moduleEnabled, 'boolean', 'whatsapp');
        Setting::set('fonnte_account_token', $this->fonnteAccountToken, 'string', 'whatsapp');
        Setting::set('whatsapp_lead_notif_device_token', $this->leadNotifDeviceToken, 'string', 'whatsapp');
        Setting::set('whatsapp_fallback_message', $this->fallbackMessage, 'string', 'whatsapp');
        Setting::set('whatsapp_auto_reply_enabled', $this->autoReplyEnabled, 'boolean', 'whatsapp');
        Setting::set('whatsapp_max_devices_per_user', $this->maxDevicesPerUser, 'number', 'whatsapp');

        \App\Events\AdminSettingsChanged::dispatch('whatsapp', auth()->id(), [
            'whatsapp_module_enabled',
            'fonnte_account_token',
            'whatsapp_lead_notif_device_token',
            'whatsapp_fallback_message',
            'whatsapp_auto_reply_enabled',
            'whatsapp_max_devices_per_user',
        ]);

        session()->flash('message', __('admin.s.whatsapp_settings_saved'));
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
            $imported = 0;
            $seenTokens = [];

            foreach ($fonnteDevices as $fDevice) {
                $token = $fDevice['token'] ?? null;

                if (! $token) {
                    continue;
                }

                $seenTokens[] = $token;
                $status = ($fDevice['status'] ?? null) === 'connect' ? 'connected' : 'disconnected';
                $quotaRemaining = isset($fDevice['quota']) && is_numeric($fDevice['quota'])
                    ? (int) $fDevice['quota']
                    : null;
                $planExpiresAt = isset($fDevice['expired']) && is_numeric($fDevice['expired'])
                    ? now()->createFromTimestamp((int) $fDevice['expired'])
                    : null;
                $localDevice = WhatsAppDevice::where('fonnte_device_token', $token)->first();

                if ($localDevice) {
                    // Known device (tenant-owned or platform): refresh
                    // live state only, ownership is never reassigned.
                    // Quota + plan expiry are Fonnte-owned facts, safe
                    // to refresh alongside status.
                    $updates = [
                        'status' => $status,
                        'phone_number' => $fDevice['device'] ?? $localDevice->phone_number,
                    ];
                    if ($quotaRemaining !== null) {
                        $updates['quota_remaining'] = $quotaRemaining;
                    }
                    if ($planExpiresAt !== null) {
                        $updates['plan_expires_at'] = $planExpiresAt;
                    }
                    $localDevice->update($updates);
                    $synced++;
                } else {
                    // Account-level device the app has never seen: import
                    // it as a platform device (no tenant owner). The old
                    // update-only sync could never surface these, which
                    // left the monitoring tab permanently empty.
                    WhatsAppDevice::create([
                        'user_id' => null,
                        'is_platform' => true,
                        'fonnte_device_token' => $token,
                        'phone_number' => $fDevice['device'] ?? null,
                        'device_name' => $fDevice['name'] ?? null,
                        'status' => $status,
                        'plan' => $this->mapFonntePlan($fDevice['package'] ?? null),
                        'plan_expires_at' => $planExpiresAt,
                        'quota_remaining' => $quotaRemaining,
                        'is_active' => true,
                        'connected_at' => $status === 'connected' ? now() : null,
                    ]);
                    $imported++;
                }
            }

            // Platform devices that disappeared from the Fonnte account
            // no longer exist there; reflect that instead of showing
            // them as live forever. Tenant devices are left alone -
            // their lifecycle is managed from the tenant side.
            WhatsAppDevice::platform()
                ->whereNotIn('fonnte_device_token', $seenTokens ?: [''])
                ->where('status', '!=', 'disconnected')
                ->update(['status' => 'disconnected', 'disconnected_at' => now()]);

            if ($imported > 0) {
                session()->flash('message', __('admin.s.devices_synced_imported', ['synced' => $synced, 'imported' => $imported]));
            } else {
                session()->flash('message', __('admin.s.devices_synced', ['count' => $synced]));
            }

            $this->loadStatistics();
            $this->loadDevices();

        } catch (\Exception $e) {
            session()->flash('error', __('admin.s.sync_failed', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Open the widget-link panel for a platform device. Linking gives
     * the device the widget's agent + knowledge base for AI replies;
     * saving also installs the device's Fonnte webhook to Cekat (the
     * device was born in Fonnte, so nothing pointed here yet).
     */
    public function openLink(int $deviceId)
    {
        $device = WhatsAppDevice::find($deviceId);

        if (! $device || ! $device->is_platform) {
            session()->flash('error', __('admin.s.platform_device_only'));

            return;
        }

        $this->linkDeviceId = $device->id;
        $this->linkDeviceLabel = trim(($device->device_name ?: 'Device') . ' · ' . $device->formatted_phone);
        $this->linkWidgetId = $device->widget_id;
        $this->linkWidgetOptions = Widget::with('user')->orderBy('name')->take(200)->get()
            ->map(fn ($w) => ['id' => $w->id, 'label' => $w->name . ' — ' . ($w->user?->name ?? '-')])
            ->all();
    }

    public function saveLink()
    {
        $device = WhatsAppDevice::find($this->linkDeviceId);

        if (! $device || ! $device->is_platform || ! $this->linkWidgetId) {
            session()->flash('error', __('admin.s.platform_device_only'));

            return;
        }

        $device->update(['widget_id' => $this->linkWidgetId]);

        (new WhatsAppManager())->configureDeviceWebhooks($device);

        session()->flash('message', __('admin.s.device_linked'));
        $this->closeLink();
        $this->loadDevices();
    }

    public function unlinkDevice(int $deviceId)
    {
        $device = WhatsAppDevice::find($deviceId);

        if (! $device || ! $device->is_platform) {
            session()->flash('error', __('admin.s.platform_device_only'));

            return;
        }

        $device->update(['widget_id' => null]);
        session()->flash('message', __('admin.s.device_unlinked'));
        $this->loadDevices();
    }

    public function closeLink()
    {
        $this->reset(['linkDeviceId', 'linkDeviceLabel', 'linkWidgetId', 'linkWidgetOptions']);
    }

    /**
     * Open the QR connect panel for a platform device. Tenant devices
     * are reconnected by their owners from the tenant dashboard; the
     * platform device has no owner, so the admin reconnects it here
     * (the monitoring tab is where it is watched).
     */
    public function connectDevice(int $deviceId)
    {
        $device = WhatsAppDevice::find($deviceId);

        if (! $device || ! $device->is_platform) {
            session()->flash('error', __('admin.s.platform_device_only'));

            return;
        }

        $this->connectDeviceId = $device->id;
        $this->connectDeviceLabel = trim(($device->device_name ?: 'Device') . ' · ' . $device->formatted_phone);
        $this->connectQrImage = '';
        $this->connectError = '';
        $this->connectState = 'waiting';

        try {
            $result = (new WhatsAppManager())->getDeviceQR($device);

            if (($result['status'] ?? null) === 'connected') {
                $this->connectState = 'connected';
                $this->loadDevices();
                $this->loadStatistics();
            } elseif (! empty($result['url'])) {
                // Fonnte returns the QR as a base64 PNG in 'url'.
                $this->connectQrImage = $result['url'];
            } else {
                $this->connectState = 'error';
                $this->connectError = __('admin.s.qr_unavailable');
            }
        } catch (\Exception $e) {
            $this->connectState = 'error';
            $this->connectError = $e->getMessage();
        }
    }

    /**
     * Polled by the connect panel while waiting for the scan.
     */
    public function refreshConnectStatus()
    {
        if (! $this->connectDeviceId || $this->connectState !== 'waiting') {
            return;
        }

        $device = WhatsAppDevice::find($this->connectDeviceId);

        if (! $device) {
            return;
        }

        try {
            (new WhatsAppManager())->refreshDeviceStatus($device);
        } catch (\Exception $e) {
            return;
        }

        if ($device->fresh()->status === 'connected') {
            $this->connectState = 'connected';
            $this->loadDevices();
            $this->loadStatistics();
        }
    }

    public function closeConnect()
    {
        $this->reset(['connectDeviceId', 'connectDeviceLabel', 'connectQrImage', 'connectState', 'connectError']);
        $this->loadDevices();
    }

    /**
     * Map a Fonnte package label onto the local plan enum; unknown
     * labels keep the safe default.
     */
    private function mapFonntePlan(?string $package): string
    {
        $known = ['free', 'lite', 'regular', 'regular_pro', 'master', 'super', 'advanced', 'ultra'];
        $normalized = strtolower(str_replace([' ', '-'], '_', trim((string) $package)));

        return in_array($normalized, $known, true) ? $normalized : 'free';
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

        session()->flash('message', __('admin.s.devices_disconnected', ['count' => $disconnected]));
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
            session()->flash('error', __('admin.s.device_not_found'));
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
            session()->flash('message', __('admin.s.device_disconnected', ['name' => $device->device_name]));
        } catch (\Exception $e) {
            session()->flash('error', __('admin.s.failed_disconnect', ['error' => $e->getMessage()]));
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
            session()->flash('error', __('admin.s.device_not_found'));
            return;
        }

        try {
            $manager = new WhatsAppManager();
            $manager->deleteDevice($device);
            session()->flash('message', __('admin.s.device_deleted', ['name' => $device->device_name]));
        } catch (\Exception $e) {
            session()->flash('error', __('admin.s.failed_delete', ['error' => $e->getMessage()]));
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
