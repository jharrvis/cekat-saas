<?php

namespace App\Services\WhatsApp;

use App\Models\Setting;
use App\Models\WhatsAppDevice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Fonnte WhatsApp API Service
 * 
 * This service handles all interactions with Fonnte API.
 * Fonnte is an UNOFFICIAL WhatsApp API - there's risk of account bans.
 * 
 * @see https://docs.fonnte.com
 */
class FonnteService
{
    private const API_BASE_URL = 'https://api.fonnte.com';

    private ?string $accountToken;

    public function __construct()
    {
        $this->accountToken = Setting::get('fonnte_account_token');
    }

    /**
     * Check if Fonnte integration is enabled and configured.
     */
    public static function isEnabled(): bool
    {
        return (bool) Setting::get('whatsapp_module_enabled', false);
    }

    /**
     * Check if Fonnte is properly configured.
     */
    public static function isConfigured(): bool
    {
        $token = Setting::get('fonnte_account_token');
        return !empty($token);
    }

    /**
     * Get all devices registered under the account.
     * Uses Account Token for management operations.
     */
    public function getDevices(): array
    {
        if (!$this->accountToken) {
            throw new \Exception('Fonnte account token not configured');
        }

        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => $this->accountToken,
            ])
            ->post(self::API_BASE_URL . '/get-devices');

        $data = $response->json();

        Log::info('Fonnte getDevices response', ['data' => $data]);

        if (isset($data['status']) && $data['status'] === false) {
            throw new \Exception($data['reason'] ?? 'Failed to get devices');
        }

        return $data['data'] ?? [];
    }

    /**
     * Add a new device.
     * 
     * @param string $name Device name for identification (2-30 chars)
     * @param string $deviceNumber A unique phone number identifier (8-15 digits, doesn't need to be real WA number)
     * @return array Device info including token
     */
    public function addDevice(string $name, string $deviceNumber = ''): array
    {
        if (!$this->accountToken) {
            throw new \Exception('Fonnte account token not configured');
        }

        // Ensure name is between 2-30 characters
        $name = substr(trim($name), 0, 30);
        if (strlen($name) < 2) {
            $name = 'Device-' . time();
        }

        // Generate a unique device number if not provided (8-15 digits required by Fonnte)
        // This is just an identifier, NOT the actual WhatsApp number
        // The real WhatsApp number is set when user scans the QR code
        if (empty($deviceNumber)) {
            // Generate a truly unique identifier: random prefix + microtime + random suffix
            // Format: 08XXYYYYYYYZ where XX=random, YYYYYY=time-based, Z=random
            $deviceNumber = '08' . rand(10, 99) . substr(str_replace('.', '', microtime(true)), -6) . rand(10, 99);
        }

        // Ensure device number is 8-15 characters (only digits)
        $deviceNumber = preg_replace('/[^0-9]/', '', $deviceNumber);
        $deviceNumber = substr($deviceNumber, 0, 15);
        if (strlen($deviceNumber) < 8) {
            $deviceNumber = str_pad($deviceNumber, 8, '0', STR_PAD_LEFT);
        }

        Log::info('Fonnte addDevice request', [
            'name' => $name,
            'device' => $deviceNumber,
        ]);

        // Send as form data (not JSON) - this is required by Fonnte API
        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => $this->accountToken,
            ])
            ->post(self::API_BASE_URL . '/add-device', [
                'name' => $name,
                'device' => $deviceNumber,
                'autoread' => 'false',
                'personal' => 'false',
                'group' => 'false',
            ]);

        $data = $response->json();

        Log::info('Fonnte addDevice response', ['name' => $name, 'data' => $data]);

        if (isset($data['status']) && $data['status'] === false) {
            throw new \Exception($data['reason'] ?? 'Failed to add device');
        }

        return $data;
    }

    /**
     * Delete a device.
     * 
     * @param string $deviceToken The device's token
     */
    public function deleteDevice(string $deviceToken): bool
    {
        if (!$this->accountToken) {
            throw new \Exception('Fonnte account token not configured');
        }

        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => $this->accountToken,
            ])
            ->post(self::API_BASE_URL . '/delete-device', [
                'token' => $deviceToken,
            ]);

        $data = $response->json();

        Log::info('Fonnte deleteDevice response', ['data' => $data]);

        return $data['status'] ?? false;
    }

    /**
     * Get QR code for device connection.
     * 
     * @param string $deviceToken The device's token
     * @return array QR data including URL
     */
    public function getQR(string $deviceToken): array
    {
        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => $deviceToken,
            ])
            ->post(self::API_BASE_URL . '/qr');

        $data = $response->json();

        Log::info('Fonnte getQR response', ['data' => $data]);

        if (isset($data['status']) && $data['status'] === false) {
            // Device might already be connected
            if (str_contains($data['reason'] ?? '', 'connected')) {
                return ['status' => 'connected', 'reason' => $data['reason']];
            }
            throw new \Exception($data['reason'] ?? 'Failed to get QR code');
        }

        return $data;
    }

    /**
     * Get device connection status.
     * 
     * @param string $deviceToken The device's token
     */
    public function getDeviceStatus(string $deviceToken): array
    {
        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => $deviceToken,
            ])
            ->post(self::API_BASE_URL . '/device');

        $data = $response->json();

        return $data;
    }

    /**
     * Disconnect a device from WhatsApp.
     * 
     * @param string $deviceToken The device's token
     */
    public function disconnectDevice(string $deviceToken): bool
    {
        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => $deviceToken,
            ])
            ->post(self::API_BASE_URL . '/disconnect');

        $data = $response->json();

        Log::info('Fonnte disconnect response', ['data' => $data]);

        return $data['status'] ?? false;
    }

    /**
     * Send a text message.
     * 
     * @param string $deviceToken Device token
     * @param string $target Target phone number (with country code, e.g., 628123456789)
     * @param string $message Message content
     * @param array $options Additional options (delay, schedule, etc)
     */
    public function sendMessage(string $deviceToken, string $target, string $message, array $options = []): array
    {
        $payload = array_merge([
            'target' => $target,
            'message' => $message,
            'countryCode' => '62', // Indonesia default
        ], $options);

        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => $deviceToken,
            ])
            ->post(self::API_BASE_URL . '/send', $payload);

        $data = $response->json();

        Log::info('Fonnte sendMessage response', [
            'target' => $target,
            'status' => $data['status'] ?? null,
        ]);

        if (isset($data['status']) && $data['status'] === false) {
            throw new \Exception($data['reason'] ?? 'Failed to send message');
        }

        return $data;
    }

    /**
     * Send message with media attachment.
     * 
     * @param string $deviceToken Device token
     * @param string $target Target phone number
     * @param string $message Caption/message
     * @param string $url URL of media file
     * @param string $filename Optional filename
     */
    public function sendMedia(string $deviceToken, string $target, string $message, string $url, string $filename = ''): array
    {
        $payload = [
            'target' => $target,
            'message' => $message,
            'url' => $url,
            'countryCode' => '62',
        ];

        if ($filename) {
            $payload['filename'] = $filename;
        }

        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => $deviceToken,
            ])
            ->post(self::API_BASE_URL . '/send', $payload);

        $data = $response->json() ?? [];

        Log::info('Fonnte sendMedia response', ['target' => $target, 'data' => $data]);

        if (isset($data['status']) && $data['status'] === false) {
            throw new \Exception($data['reason'] ?? 'Failed to send media');
        }

        return $data;
    }

    /**
     * Validate if a phone number is registered on WhatsApp.
     * 
     * @param string $deviceToken Device token
     * @param string $target Target phone number
     */
    public function validateNumber(string $deviceToken, string $target): array
    {
        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => $deviceToken,
            ])
            ->post(self::API_BASE_URL . '/validate', [
                'target' => $target,
                'countryCode' => '62',
            ]);

        return $response->json() ?? [];
    }

    /**
     * Get chat history with a specific number.
     * 
     * @param string $deviceToken Device token
     * @param string $target Target phone number
     * @param int $count Number of messages to retrieve
     */
    public function getChats(string $deviceToken, string $target, int $count = 20): array
    {
        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => $deviceToken,
            ])
            ->post(self::API_BASE_URL . '/get-chats', [
                'target' => $target,
                'count' => $count,
            ]);

        return $response->json() ?? [];
    }

    /**
     * Order/upgrade device plan.
     * 
     * @param string $deviceToken Device token
     * @param string $plan Plan name (lite, regular, super, etc)
     */
    public function orderPlan(string $deviceToken, string $plan): array
    {
        if (!$this->accountToken) {
            throw new \Exception('Fonnte account token not configured');
        }

        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => $this->accountToken,
            ])
            ->post(self::API_BASE_URL . '/order', [
                'token' => $deviceToken,
                'package' => $plan,
            ]);

        $data = $response->json() ?? [];

        Log::info('Fonnte orderPlan response', ['plan' => $plan, 'data' => $data]);

        return $data;
    }

    /**
     * Set webhook URL for a device.
     * 
     * @param string $deviceToken Device token
     * @param string $webhookUrl URL to receive webhook notifications
     */
    public function setWebhook(string $deviceToken, string $webhookUrl): array
    {
        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => $deviceToken,
            ])
            ->post(self::API_BASE_URL . '/set-webhook', [
                'url' => $webhookUrl,
            ]);

        $data = $response->json() ?? [];

        Log::info('Fonnte setWebhook response', ['url' => $webhookUrl, 'data' => $data]);

        return $data;
    }

    /**
     * Normalize phone number to international format.
     * 
     * @param string $phone Phone number
     * @return string Normalized phone (e.g., 628123456789)
     */
    public static function normalizePhone(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Handle Indonesian numbers
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (str_starts_with($phone, '+')) {
            $phone = substr($phone, 1);
        } elseif (!str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }

        return $phone;
    }
}
