<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppDevice;
use App\Models\WhatsAppMessage;
use App\Models\Widget;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * WhatsApp Module Manager
 * 
 * This service manages the WhatsApp integration module including:
 * - Device provisioning and management
 * - Message processing with AI
 * - Webhook handling
 * 
 * The module can be enabled/disabled by admin via Settings.
 */
class WhatsAppManager
{
    private FonnteService $fonnte;

    public function __construct()
    {
        $this->fonnte = new FonnteService();
    }

    /**
     * Check if WhatsApp module is enabled globally.
     */
    public static function isEnabled(): bool
    {
        return (bool) Setting::get('whatsapp_module_enabled', false);
    }

    /**
     * Check if WhatsApp module is ready (enabled + configured).
     */
    public static function isReady(): bool
    {
        return self::isEnabled() && FonnteService::isConfigured();
    }

    /**
     * Get module status info.
     */
    public static function getStatus(): array
    {
        $enabled = self::isEnabled();
        $configured = FonnteService::isConfigured();

        return [
            'enabled' => $enabled,
            'configured' => $configured,
            'ready' => $enabled && $configured,
            'provider' => 'fonnte',
            'warning' => 'Fonnte uses unofficial WhatsApp API. Risk of account ban exists.',
        ];
    }

    /**
     * Create a new WhatsApp device for a user.
     * 
     * @param int $userId User ID
     * @param int|null $widgetId Widget to link (optional)
     * @param string $deviceName Display name for the device
     * @param string $phoneNumber User's WhatsApp phone number (with country code, e.g. 628123456789)
     * @return WhatsAppDevice
     */
    public function createDevice(int $userId, ?int $widgetId = null, string $deviceName = '', string $phoneNumber = ''): WhatsAppDevice
    {
        if (!self::isReady()) {
            throw new \Exception('WhatsApp module is not enabled or configured');
        }

        // Generate device name if not provided
        if (empty($deviceName)) {
            $deviceName = 'Cekat-User-' . $userId . '-' . time();
        }

        // Validate phone number
        if (empty($phoneNumber)) {
            throw new \Exception('Phone number is required');
        }

        // Create device in Fonnte with user's phone number
        $fonnteResult = $this->fonnte->addDevice($deviceName, $phoneNumber);

        if (!isset($fonnteResult['token'])) {
            Log::error('Fonnte addDevice failed - no token in response', ['result' => $fonnteResult]);
            throw new \Exception('Failed to create device: No token received');
        }

        // Create local device record
        $device = WhatsAppDevice::create([
            'user_id' => $userId,
            'widget_id' => $widgetId,
            'fonnte_device_id' => $fonnteResult['device'] ?? null,
            'fonnte_device_token' => $fonnteResult['token'],
            'phone_number' => $phoneNumber,
            'device_name' => $deviceName,
            'status' => 'pending',
            'plan' => 'free',
            'is_active' => true,
        ]);

        // Configure device settings via Fonnte API (webhooks, autoread, etc.)
        $webhookUrl = url('/api/whatsapp/webhook/' . $device->id);
        try {
            $this->fonnte->updateDevice($fonnteResult['token'], [
                // All webhook URLs point to our unified endpoint
                'webhook' => $webhookUrl,           // Incoming messages
                'webhookconnect' => $webhookUrl,    // Connection status
                'webhookstatus' => $webhookUrl,     // Message delivery status
                // Autoread settings for chatbot mode
                'autoread' => true,                 // Enable autoread (chatbot mode)
                'personal' => true,                 // Reply to personal chats
                'group' => false,                   // Don't reply to groups by default
                'quick' => false,                   // Don't reply to self messages
            ]);

            Log::info('Fonnte device configured via API', [
                'device_id' => $device->id,
                'webhook_url' => $webhookUrl,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to configure device settings via Fonnte API', [
                'device_id' => $device->id,
                'error' => $e->getMessage(),
            ]);

            // Fallback: try the old setWebhook method
            try {
                $this->fonnte->setWebhook($fonnteResult['token'], $webhookUrl);
            } catch (\Exception $e2) {
                Log::warning('Fallback setWebhook also failed', [
                    'device_id' => $device->id,
                    'error' => $e2->getMessage(),
                ]);
            }
        }

        Log::info('WhatsApp device created', [
            'device_id' => $device->id,
            'user_id' => $userId,
            'phone_number' => $phoneNumber,
            'fonnte_device_id' => $fonnteResult['device'] ?? null,
        ]);

        return $device;
    }

    /**
     * Get QR code for device connection.
     * 
     * @param WhatsAppDevice $device
     * @return array QR data
     */
    public function getDeviceQR(WhatsAppDevice $device): array
    {
        if (!$device->fonnte_device_token) {
            throw new \Exception('Device token not found');
        }

        $qrData = $this->fonnte->getQR($device->fonnte_device_token);

        // If device is already connected
        if (isset($qrData['status']) && $qrData['status'] === 'connected') {
            $device->update(['status' => 'connected', 'connected_at' => now()]);
            return ['status' => 'connected', 'message' => 'Device already connected'];
        }

        // Update device status to connecting
        if ($device->status !== 'connecting') {
            $device->update(['status' => 'connecting']);
        }

        return $qrData;
    }

    /**
     * Refresh device connection status.
     * 
     * @param WhatsAppDevice $device
     * @return array Status info
     */
    public function refreshDeviceStatus(WhatsAppDevice $device): array
    {
        if (!$device->fonnte_device_token) {
            throw new \Exception('Device token not found');
        }

        $statusData = $this->fonnte->getDeviceStatus($device->fonnte_device_token);

        Log::info('Fonnte device status check', ['device_id' => $device->id, 'data' => $statusData]);

        // Fonnte returns device_status = "connect" or "disconnect"
        // status = true just means API call was successful
        $deviceStatus = $statusData['device_status'] ?? null;
        $isConnected = ($deviceStatus === 'connect');
        $phoneNumber = $statusData['device'] ?? null;

        if ($isConnected && $device->status !== 'connected') {
            $device->update([
                'status' => 'connected',
                'connected_at' => now(),
                'phone_number' => $phoneNumber,
            ]);
        } elseif (!$isConnected && $device->status === 'connected') {
            $device->update([
                'status' => 'disconnected',
                'disconnected_at' => now(),
            ]);
        }

        return [
            'connected' => $isConnected,
            'device_status' => $deviceStatus,
            'phone_number' => $phoneNumber,
            'status' => $device->fresh()->status,
        ];
    }

    /**
     * Disconnect and delete a device locally.
     * 
     * Note: Fonnte requires OTP to delete device, so we only disconnect.
     * The device will remain in Fonnte dashboard but will be disconnected.
     * 
     * @param WhatsAppDevice $device
     */
    public function deleteDevice(WhatsAppDevice $device): bool
    {
        // Try to disconnect from Fonnte first
        // Note: Fonnte requires OTP for deletion, so we only disconnect
        if ($device->fonnte_device_token) {
            try {
                $this->fonnte->disconnectDevice($device->fonnte_device_token);
                Log::info('Device disconnected from Fonnte', ['device_id' => $device->id]);
            } catch (\Exception $e) {
                Log::warning('Failed to disconnect device from Fonnte', [
                    'device_id' => $device->id,
                    'error' => $e->getMessage(),
                ]);
                // Continue with local deletion even if Fonnte disconnect fails
            }
        }

        // Delete local record and associated messages
        $device->messages()->delete();
        $device->delete();

        Log::info('WhatsApp device deleted locally', ['device_id' => $device->id]);

        return true;
    }

    /**
     * Send a message through a device.
     * 
     * @param WhatsAppDevice $device
     * @param string $to Target phone number
     * @param string $message Message content
     * @param bool $isAiResponse Whether this is an AI-generated response
     * @return WhatsAppMessage
     */
    public function sendMessage(WhatsAppDevice $device, string $to, string $message, bool $isAiResponse = false): WhatsAppMessage
    {
        if (!$device->isReady()) {
            throw new \Exception('Device is not ready for sending messages');
        }

        // Normalize phone number
        $normalizedPhone = FonnteService::normalizePhone($to);

        // Send via Fonnte
        $result = $this->fonnte->sendMessage($device->fonnte_device_token, $normalizedPhone, $message);

        // Create message record
        $waMessage = WhatsAppMessage::create([
            'whatsapp_device_id' => $device->id,
            'widget_id' => $device->widget_id,
            'sender_phone' => $device->phone_number ?? 'unknown',
            'direction' => 'outbound',
            'message' => $message,
            'message_type' => 'text',
            'status' => ($result['status'] ?? false) ? 'sent' : 'failed',
            'fonnte_message_id' => $result['id'] ?? null,
            'is_ai_response' => $isAiResponse,
            'error_message' => $result['reason'] ?? null,
        ]);

        // Update device stats
        $device->increment('messages_sent');

        return $waMessage;
    }

    /**
     * Process incoming message with AI.
     * 
     * @param WhatsAppDevice $device
     * @param string $from Sender phone number
     * @param string $message Message content
     * @param string|null $senderName Sender's push name
     * @param string|null $fonnteMessageId Fonnte's message ID for deduplication
     * @return WhatsAppMessage The AI response message
     */
    public function processIncomingMessage(
        WhatsAppDevice $device,
        string $from,
        string $message,
        ?string $senderName = null,
        ?string $fonnteMessageId = null
    ): WhatsAppMessage {
        // Save incoming message with Fonnte ID for deduplication
        $inboundMessage = WhatsAppMessage::create([
            'whatsapp_device_id' => $device->id,
            'widget_id' => $device->widget_id,
            'sender_phone' => $from,
            'sender_name' => $senderName,
            'direction' => 'inbound',
            'message' => $message,
            'message_type' => 'text',
            'status' => 'delivered',
            'fonnte_message_id' => $fonnteMessageId,
        ]);

        $device->increment('messages_received');

        // Check if device has linked widget for AI processing
        if (!$device->widget_id) {
            Log::info('No widget linked to device, skipping AI response', [
                'device_id' => $device->id,
            ]);
            return $inboundMessage;
        }

        // Get AI response
        try {
            $aiResponse = $this->getAIResponse($device, $from, $message);

            // Send AI response
            $responseMessage = $this->sendMessage($device, $from, $aiResponse['response'], true);
            $responseMessage->update([
                'ai_model_used' => $aiResponse['model'] ?? null,
                'tokens_used' => $aiResponse['tokens'] ?? 0,
            ]);

            return $responseMessage;
        } catch (\Exception $e) {
            Log::error('Failed to process AI response', [
                'device_id' => $device->id,
                'error' => $e->getMessage(),
            ]);

            // Send fallback message
            $fallbackMessage = Setting::get(
                'whatsapp_fallback_message',
                'Maaf, saya sedang mengalami gangguan teknis. Silakan coba lagi nanti.'
            );

            return $this->sendMessage($device, $from, $fallbackMessage, false);
        }
    }

    /**
     * Get AI response for a message.
     * 
     * @param WhatsAppDevice $device
     * @param string $from Sender phone
     * @param string $message User message
     * @return array Response with 'response', 'model', 'tokens' keys
     */
    private function getAIResponse(WhatsAppDevice $device, string $from, string $message): array
    {
        $widget = $device->widget()->with(['knowledgeBase.faqs', 'user.plan'])->first();

        if (!$widget) {
            throw new \Exception('Widget not found');
        }

        // Get conversation history from recent messages
        $history = WhatsAppMessage::where('whatsapp_device_id', $device->id)
            ->where('sender_phone', $from)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->reverse()
            ->map(function ($msg) {
                return [
                    'role' => $msg->direction === 'inbound' ? 'user' : 'assistant',
                    'content' => $msg->message,
                ];
            })
            ->values()
            ->toArray();

        // Add current message
        $history[] = ['role' => 'user', 'content' => $message];

        // Call existing chat API logic or directly call OpenRouter
        // For simplicity, we'll call the OpenRouter directly here
        $response = $this->callChatAPI($widget, $history);

        return $response;
    }

    /**
     * Call the chat API (OpenRouter) directly.
     * Reuses the logic from ChatController.
     */
    private function callChatAPI(Widget $widget, array $history): array
    {
        // Build system prompt (simplified version)
        $kb = $widget->knowledgeBase;
        $systemPrompt = $this->buildSystemPrompt($kb);

        // Get model based on user's plan
        $model = $this->getModelForWidget($widget);

        $allMessages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ...$history
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.openrouter.api_key'),
            'HTTP-Referer' => config('app.url'),
            'X-Title' => 'Cekat SaaS WhatsApp',
        ])->timeout(60)->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model' => $model,
                    'messages' => $allMessages,
                    'temperature' => 0.7,
                    'max_tokens' => 1500,
                ]);

        $data = $response->json();

        return [
            'response' => $data['choices'][0]['message']['content'] ?? 'Maaf, tidak dapat memproses pesan.',
            'model' => $model,
            'tokens' => $data['usage']['total_tokens'] ?? 0,
        ];
    }

    /**
     * Build system prompt for AI.
     */
    private function buildSystemPrompt($kb): string
    {
        if (!$kb) {
            return 'Kamu adalah Customer Service yang ramah dan helpful. Jawab dalam Bahasa Indonesia.';
        }

        $prompt = "Kamu adalah {$kb->persona_name}, seorang Customer Service untuk {$kb->company_name}.\n";
        $prompt .= "Deskripsi: {$kb->company_description}\n\n";
        $prompt .= "Gunakan bahasa Indonesia yang santai dan ramah.\n";

        if ($kb->faqs && $kb->faqs->count() > 0) {
            $prompt .= "\n## FAQ\n";
            foreach ($kb->faqs as $faq) {
                $prompt .= "Q: {$faq->question}\nA: {$faq->answer}\n\n";
            }
        }

        if ($kb->custom_instructions) {
            $prompt .= "\n## Instruksi Tambahan\n{$kb->custom_instructions}\n";
        }

        return $prompt;
    }

    /**
     * Get AI model based on widget's user plan.
     */
    private function getModelForWidget(Widget $widget): string
    {
        $defaultModel = 'nvidia/nemotron-3-nano-30b-a3b:free';

        if (!$widget->user) {
            return $defaultModel;
        }

        $plan = $widget->user->plan;
        if (!$plan) {
            return $defaultModel;
        }

        $aiTier = $plan->ai_tier ?? 'basic';
        $mappingData = Setting::get('ai_tier_mapping');

        if ($mappingData) {
            $mapping = is_array($mappingData) ? $mappingData : json_decode($mappingData, true);
            if (is_array($mapping) && isset($mapping[$aiTier])) {
                return $mapping[$aiTier];
            }
        }

        $defaultMapping = [
            'basic' => 'nvidia/nemotron-3-nano-30b-a3b:free',
            'standard' => 'openai/gpt-4o-mini',
            'advanced' => 'openai/gpt-4o-mini',
            'premium' => 'openai/gpt-4o-mini',
        ];

        return $defaultMapping[$aiTier] ?? $defaultModel;
    }
}
