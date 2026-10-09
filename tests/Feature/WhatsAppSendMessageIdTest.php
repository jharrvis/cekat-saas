<?php

namespace Tests\Feature;

use App\Models\WhatsAppDevice;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Regression: Fonnte's /send response carries the message id as an
 * array (one per target). WhatsAppManager stored it raw into the
 * string fonnte_message_id column, so the outbound record insert
 * crashed with "Array to string conversion" AFTER delivery - and
 * processIncomingMessage then sent its fallback text as a second
 * message. Users saw every AI reply followed by a bogus
 * "technical difficulties" notice.
 */
class WhatsAppSendMessageIdTest extends TestCase
{
    use RefreshDatabase;

    private function device(): WhatsAppDevice
    {
        return WhatsAppDevice::create([
            'user_id' => null,
            'is_platform' => true,
            'fonnte_device_token' => 'dev-token-1',
            'phone_number' => '6285172238819',
            'status' => 'connected',
            'is_active' => true,
        ]);
    }

    public function test_array_message_id_from_fonnte_is_normalized(): void
    {
        Http::fake([
            'https://api.fonnte.com/send' => Http::response([
                'status' => true,
                'id' => ['184773736'],
                'target' => '6281100000000',
            ], 200),
        ]);

        $message = (new WhatsAppManager())->sendMessage($this->device(), '6281100000000', 'Halo dari AI', true);

        $this->assertSame('184773736', $message->fonnte_message_id);
        $this->assertSame('sent', $message->status);
        $this->assertTrue($message->is_ai_response);
    }

    public function test_string_message_id_is_preserved(): void
    {
        Http::fake([
            'https://api.fonnte.com/send' => Http::response([
                'status' => true,
                'id' => '99887766',
            ], 200),
        ]);

        $message = (new WhatsAppManager())->sendMessage($this->device(), '6281100000000', 'Halo');

        $this->assertSame('99887766', $message->fonnte_message_id);
    }
}
