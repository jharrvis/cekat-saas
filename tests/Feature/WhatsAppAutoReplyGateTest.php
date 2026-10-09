<?php

namespace Tests\Feature;

use App\Models\AiAgent;
use App\Models\Setting;
use App\Models\User;
use App\Models\WhatsAppDevice;
use App\Models\WhatsAppMessage;
use App\Models\Widget;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The admin "Enable Auto Reply with AI" switch used to be saved but
 * never read: AI replies were gated only by the device having a
 * linked widget. The switch is now honored in processIncomingMessage.
 */
class WhatsAppAutoReplyGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_reply_off_saves_message_but_never_calls_ai_or_sends(): void
    {
        Setting::set('whatsapp_auto_reply_enabled', false);
        Setting::set('fonnte_account_token', 'account-token-test');
        Http::fake(['*' => Http::response(['status' => true], 200)]);

        $owner = User::create([
            'name' => 'Owner',
            'email' => 'gate-owner@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
        ]);

        $agent = AiAgent::create([
            'user_id' => $owner->id,
            'name' => 'CS Agent',
            'slug' => 'cs-gate',
        ]);

        $widget = Widget::create([
            'user_id' => $owner->id,
            'ai_agent_id' => $agent->id,
            'name' => 'Widget Gate',
            'slug' => 'w-gate',
        ]);

        $device = WhatsAppDevice::create([
            'user_id' => null,
            'is_platform' => true,
            'widget_id' => $widget->id,
            'fonnte_device_token' => 'dev-token-1',
            'phone_number' => '6285172238819',
            'status' => 'connected',
            'is_active' => true,
        ]);

        $result = (new WhatsAppManager())->processIncomingMessage($device, '6281100000000', 'Halo, ada yang bisa dibantu?', 'Pengirim');

        $this->assertSame('inbound', $result->direction);
        $this->assertSame(1, WhatsAppMessage::where('whatsapp_device_id', $device->id)->count());

        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'openrouter.ai')
            || str_contains($request->url(), 'api.fonnte.com/send'));
    }
}
