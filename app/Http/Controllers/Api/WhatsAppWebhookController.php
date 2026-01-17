<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppDevice;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\WhatsAppManager;
use App\Services\WhatsApp\FonnteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp Webhook Controller
 * 
 * Handles incoming webhooks from Fonnte.
 * Endpoint: POST /api/whatsapp/webhook/{device_id}
 */
class WhatsAppWebhookController extends Controller
{
    /**
     * Handle incoming webhook from Fonnte.
     * 
     * Fonnte sends webhooks for:
     * - Incoming messages
     * - Message status updates
     * - Device status changes
     */
    public function handle(Request $request, int $deviceId)
    {
        Log::info('WhatsApp Webhook received', [
            'device_id' => $deviceId,
            'payload' => $request->all(),
        ]);

        // Check if module is enabled
        if (!WhatsAppManager::isEnabled()) {
            Log::warning('WhatsApp webhook received but module is disabled');
            return response()->json(['status' => 'module_disabled'], 503);
        }

        // Find device
        $device = WhatsAppDevice::find($deviceId);
        if (!$device) {
            Log::warning('WhatsApp webhook for unknown device', ['device_id' => $deviceId]);
            return response()->json(['status' => 'device_not_found'], 404);
        }

        // Check if device is active
        if (!$device->is_active) {
            Log::info('WhatsApp webhook for inactive device', ['device_id' => $deviceId]);
            return response()->json(['status' => 'device_inactive'], 200);
        }

        // Determine webhook type
        $message = $request->input('message');
        $sender = $request->input('sender');
        $status = $request->input('status');

        // Handle message status update
        if ($status && !$message) {
            return $this->handleStatusUpdate($request, $device);
        }

        // Handle incoming message
        if ($message && $sender) {
            return $this->handleIncomingMessage($request, $device);
        }

        // Handle device status change
        if ($request->has('device_status')) {
            return $this->handleDeviceStatusChange($request, $device);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Handle incoming message from WhatsApp.
     */
    private function handleIncomingMessage(Request $request, WhatsAppDevice $device)
    {
        $sender = $request->input('sender');
        $message = $request->input('message');
        $senderName = $request->input('name') ?? $request->input('pushname');
        $messageType = $this->determineMessageType($request);

        Log::info('WhatsApp incoming message', [
            'device_id' => $device->id,
            'sender' => $sender,
            'message_type' => $messageType,
        ]);

        // Skip if it's a group message (optional - can be configured)
        $isGroup = $request->input('isGroup', false);
        $settings = $device->settings ?? [];
        if ($isGroup && !($settings['respond_to_groups'] ?? false)) {
            Log::info('Skipping group message', ['device_id' => $device->id]);
            return response()->json(['status' => 'group_ignored']);
        }

        // Skip if it's from the device itself (echo)
        if ($sender === $device->phone_number) {
            return response()->json(['status' => 'self_message_ignored']);
        }

        // Process the message
        try {
            $manager = new WhatsAppManager();

            // For non-text messages, we can add handling later
            if ($messageType !== 'text') {
                // Save the message but don't process with AI
                WhatsAppMessage::create([
                    'whatsapp_device_id' => $device->id,
                    'widget_id' => $device->widget_id,
                    'sender_phone' => $sender,
                    'sender_name' => $senderName,
                    'direction' => 'inbound',
                    'message' => $message ?? '[Media message]',
                    'message_type' => $messageType,
                    'media_url' => $request->input('url'),
                    'status' => 'delivered',
                ]);

                // Send acknowledgment for media messages
                $manager->sendMessage(
                    $device,
                    $sender,
                    'Terima kasih, saya menerima file/media dari Anda. Saat ini saya hanya bisa memproses pesan teks. 😊'
                );

                return response()->json(['status' => 'media_received']);
            }

            // Process text message with AI
            $responseMessage = $manager->processIncomingMessage($device, $sender, $message, $senderName);

            return response()->json([
                'status' => 'processed',
                'message_id' => $responseMessage->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process WhatsApp message', [
                'device_id' => $device->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle message status update (sent, delivered, read).
     */
    private function handleStatusUpdate(Request $request, WhatsAppDevice $device)
    {
        $fonnteMessageId = $request->input('id');
        $status = $request->input('status'); // sent, delivered, read, failed

        if (!$fonnteMessageId) {
            return response()->json(['status' => 'ok']);
        }

        // Find and update message
        $message = WhatsAppMessage::where('fonnte_message_id', $fonnteMessageId)->first();
        if ($message) {
            $message->update([
                'status' => $this->mapFonnteStatus($status),
            ]);

            Log::info('WhatsApp message status updated', [
                'message_id' => $message->id,
                'status' => $status,
            ]);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Handle device status change (connected, disconnected).
     */
    private function handleDeviceStatusChange(Request $request, WhatsAppDevice $device)
    {
        $deviceStatus = $request->input('device_status');

        if ($deviceStatus === 'connected') {
            $device->update([
                'status' => 'connected',
                'connected_at' => now(),
                'phone_number' => $request->input('device') ?? $device->phone_number,
            ]);
        } elseif ($deviceStatus === 'disconnected') {
            $device->update([
                'status' => 'disconnected',
                'disconnected_at' => now(),
            ]);
        }

        Log::info('WhatsApp device status changed', [
            'device_id' => $device->id,
            'status' => $deviceStatus,
        ]);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Determine message type from webhook payload.
     */
    private function determineMessageType(Request $request): string
    {
        if ($request->has('url')) {
            $url = $request->input('url');
            if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $url)) {
                return 'image';
            }
            if (preg_match('/\.(mp4|mov|avi|mkv)$/i', $url)) {
                return 'video';
            }
            if (preg_match('/\.(mp3|ogg|opus|wav)$/i', $url)) {
                return 'audio';
            }
            if (preg_match('/\.(pdf|doc|docx|xls|xlsx)$/i', $url)) {
                return 'document';
            }
        }

        if ($request->has('location')) {
            return 'location';
        }

        if ($request->has('vcard')) {
            return 'contact';
        }

        return 'text';
    }

    /**
     * Map Fonnte status to our status.
     */
    private function mapFonnteStatus(string $status): string
    {
        return match ($status) {
            'sent', 'pending' => 'sent',
            'delivered' => 'delivered',
            'read' => 'read',
            'failed', 'error' => 'failed',
            default => 'pending',
        };
    }
}
