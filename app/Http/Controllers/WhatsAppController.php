<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWhatsAppDeviceRequest;
use App\Http\Requests\UpdateWhatsAppDeviceRequest;
use App\Models\WhatsAppDevice;
use App\Services\WhatsApp\WhatsAppManager;
use App\Services\WhatsApp\FonnteService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp Controller
 * 
 * Handles user-facing WhatsApp device management.
 */
class WhatsAppController extends Controller
{
    protected WhatsAppManager $manager;

    public function __construct()
    {
        $this->manager = new WhatsAppManager();
    }

    /**
     * Display WhatsApp integration page.
     */
    public function index()
    {
        // Check if module is enabled
        if (!WhatsAppManager::isEnabled()) {
            return view('whatsapp.disabled');
        }

        $user = auth()->user();
        $devices = WhatsAppDevice::where('user_id', $user->id)
            ->with('widget')
            ->orderBy('created_at', 'desc')
            ->get();

        $widgets = $user->widgets()->get();

        return view('whatsapp.index', [
            'devices' => $devices,
            'widgets' => $widgets,
            'moduleStatus' => WhatsAppManager::getStatus(),
        ]);
    }

    /**
     * Create a new WhatsApp device.
     */
    public function create(StoreWhatsAppDeviceRequest $request)
    {
        if (!WhatsAppManager::isReady()) {
            return back()->with('error', 'WhatsApp module is not available.');
        }

        // Check user's plan device limit (PlanLimitService -> plans.max_whatsapp_devices)
        $user = auth()->user();
        $check = app(\App\Services\Billing\PlanLimitService::class)->check($user, 'whatsapp_devices', [
            'used' => WhatsAppDevice::where('user_id', $user->id)->count(),
        ]);

        if (! $check['allowed']) {
            return back()->with('plan_limit_error', app(\App\Services\Billing\PlanLimitService::class)->limitMessage($user, 'whatsapp_devices'));
        }

        // Normalize phone number to include country code
        $phoneNumber = $request->input('phone_number');
        // Remove leading 0 if present
        $phoneNumber = ltrim($phoneNumber, '0');
        // Add Indonesia country code
        $phoneNumber = '62' . $phoneNumber;

        try {
            $device = $this->manager->createDevice(
                $user->id,
                $request->input('widget_id'),
                $request->input('device_name'),
                $phoneNumber
            );

            return redirect()->route('whatsapp.connect', $device->id)
                ->with('success', 'Device berhasil dibuat. Silakan scan QR code untuk menghubungkan.');

        } catch (\Exception $e) {
            Log::error('Failed to create WhatsApp device', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal membuat device: ' . $e->getMessage());
        }
    }

    /**
     * Show QR code page for device connection.
     */
    public function connect(WhatsAppDevice $device)
    {
        Gate::authorize('view', $device);

        if (!WhatsAppManager::isReady()) {
            return redirect()->route('whatsapp.index')
                ->with('error', 'WhatsApp module is not available.');
        }

        return view('whatsapp.connect', [
            'device' => $device,
        ]);
    }

    /**
     * Get QR code for device connection (AJAX).
     */
    public function getQR(WhatsAppDevice $device)
    {
        if (Gate::denies('view', $device)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $qrData = $this->manager->getDeviceQR($device);

            return response()->json([
                'success' => true,
                'data' => $qrData,
                'device_status' => $device->fresh()->status,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refresh device status (AJAX).
     */
    public function refreshStatus(WhatsAppDevice $device)
    {
        if (Gate::denies('view', $device)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $statusData = $this->manager->refreshDeviceStatus($device);

            return response()->json([
                'success' => true,
                'data' => $statusData,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update device settings.
     */
    public function update(UpdateWhatsAppDeviceRequest $request, WhatsAppDevice $device)
    {
        $device->update($request->validated());

        return back()->with('success', 'Device berhasil diperbarui.');
    }

    /**
     * Disconnect device from WhatsApp.
     */
    public function disconnect(WhatsAppDevice $device)
    {
        Gate::authorize('update', $device);

        try {
            $fonnte = new FonnteService();
            $fonnte->disconnectDevice($device->fonnte_device_token);

            $device->update([
                'status' => 'disconnected',
                'disconnected_at' => now(),
            ]);

            return back()->with('success', 'Device berhasil di-disconnect.');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal disconnect: ' . $e->getMessage());
        }
    }

    /**
     * Delete device.
     */
    public function destroy(WhatsAppDevice $device)
    {
        Gate::authorize('delete', $device);

        try {
            $this->manager->deleteDevice($device);
            return redirect()->route('whatsapp.index')
                ->with('success', 'Device berhasil dihapus.');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus device: ' . $e->getMessage());
        }
    }

    /**
     * Show message history for a device.
     */
    public function messages(WhatsAppDevice $device)
    {
        Gate::authorize('view', $device);

        $messages = $device->messages()
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('whatsapp.messages', [
            'device' => $device,
            'messages' => $messages,
        ]);
    }
}
