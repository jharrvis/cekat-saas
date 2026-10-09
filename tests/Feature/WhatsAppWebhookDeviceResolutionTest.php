<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\WhatsAppDevice;
use App\Models\WhatsAppMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Fonnte devices can hold a stale webhook URL whose local device id
 * no longer exists (production case: an imported platform device got
 * a new row id while Fonnte kept posting to the old one, and Fonnte
 * rejected the API attempt to rewrite the URL). Every payload carries
 * the device's own phone number, so the webhook resolves by phone
 * when the URL id misses.
 */
class WhatsAppWebhookDeviceResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('whatsapp_module_enabled', true);
        Http::fake(['*' => Http::response(['status' => true], 200)]);
    }

    private function device(array $overrides = []): WhatsAppDevice
    {
        return WhatsAppDevice::create(array_merge([
            'user_id' => null,
            'is_platform' => true,
            'fonnte_device_token' => 'dev-token-1',
            'phone_number' => '6285172238819',
            'device_name' => 'BMP',
            'status' => 'connected',
            'is_active' => true,
        ], $overrides));
    }

    private function messagePayload(array $overrides = []): array
    {
        return array_merge([
            'device' => '6285172238819',
            'sender' => '6281100000000',
            'message' => 'Halo, ini tes',
            'name' => 'Pengirim',
            'type' => 'text',
        ], $overrides);
    }

    public function test_stale_id_resolves_by_payload_phone_and_processes_message(): void
    {
        $device = $this->device();

        $response = $this->postJson('/api/whatsapp/webhook/999', $this->messagePayload());

        $response->assertOk();
        $this->assertSame(1, WhatsAppMessage::where('whatsapp_device_id', $device->id)->where('direction', 'inbound')->count());
        $this->assertSame(1, $device->fresh()->messages_received);
    }

    public function test_correct_id_still_works(): void
    {
        $device = $this->device();

        $response = $this->postJson('/api/whatsapp/webhook/' . $device->id, $this->messagePayload());

        $response->assertOk();
        $this->assertSame(1, WhatsAppMessage::where('whatsapp_device_id', $device->id)->count());
    }

    public function test_stale_id_with_unknown_phone_still_404(): void
    {
        $this->device();

        $response = $this->postJson('/api/whatsapp/webhook/999', $this->messagePayload(['device' => '6299999999999']));

        $response->assertStatus(404)->assertJson(['status' => 'device_not_found']);
        $this->assertSame(0, WhatsAppMessage::count());
    }

    public function test_id_match_wins_over_payload_phone(): void
    {
        $deviceA = $this->device();
        $deviceB = $this->device(['phone_number' => '6281100011111', 'fonnte_device_token' => 'dev-token-2', 'device_name' => 'Lain']);

        // URL names device B while the payload phone belongs to A:
        // the authoritative id must win.
        $response = $this->postJson('/api/whatsapp/webhook/' . $deviceB->id, $this->messagePayload());

        $response->assertOk();
        $this->assertSame(1, WhatsAppMessage::where('whatsapp_device_id', $deviceB->id)->count());
        $this->assertSame(0, WhatsAppMessage::where('whatsapp_device_id', $deviceA->id)->count());
    }
}
