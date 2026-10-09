<?php

namespace Tests\Feature;

use App\Livewire\Admin\WhatsAppSettings;
use App\Models\AiAgent;
use App\Models\Setting;
use App\Models\User;
use App\Models\WhatsAppDevice;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Admin linking for platform devices: an imported account device has
 * no widget, so incoming messages can never reach an AI agent, and
 * its Fonnte webhook does not point at Cekat (it was born in Fonnte).
 * Linking from Device Monitoring sets the widget and installs the
 * webhook with the same device configuration tenant devices get.
 */
class AdminPlatformLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('fonnte_account_token', 'account-token-test');
        Http::fake([
            'https://api.fonnte.com/*' => Http::response(['status' => true], 200),
        ]);
    }

    private function makeWidget(): Widget
    {
        $owner = User::create([
            'name' => 'Widget Owner',
            'email' => 'widget-owner@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
        ]);

        $agent = AiAgent::create([
            'user_id' => $owner->id,
            'name' => 'CS Agent',
            'slug' => 'cs-link',
        ]);

        return Widget::create([
            'user_id' => $owner->id,
            'ai_agent_id' => $agent->id,
            'name' => 'Widget Utama',
            'slug' => 'w-link',
        ]);
    }

    private function platformDevice(array $overrides = []): WhatsAppDevice
    {
        return WhatsAppDevice::create(array_merge([
            'user_id' => null,
            'is_platform' => true,
            'fonnte_device_token' => 'dev-token-1',
            'phone_number' => '6285172238819',
            'device_name' => 'BMP',
            'status' => 'connected',
            'plan' => 'lite',
        ], $overrides));
    }

    public function test_linking_sets_widget_and_installs_webhook(): void
    {
        $widget = $this->makeWidget();
        $device = $this->platformDevice();

        Livewire::test(WhatsAppSettings::class)
            ->call('openLink', $device->id)
            ->assertSet('linkDeviceId', $device->id)
            ->set('linkWidgetId', $widget->id)
            ->call('saveLink')
            ->assertSet('linkDeviceId', null);

        $this->assertSame($widget->id, $device->fresh()->widget_id);

        Http::assertSent(function (Request $request) use ($device) {
            return str_contains($request->url(), 'api.fonnte.com/update-device')
                && str_ends_with($request['webhook'] ?? '', '/api/whatsapp/webhook/' . $device->id)
                && str_ends_with($request['webhookconnect'] ?? '', '/api/whatsapp/webhook/' . $device->id);
        });
    }

    public function test_unlink_clears_widget(): void
    {
        $widget = $this->makeWidget();
        $device = $this->platformDevice(['widget_id' => $widget->id]);

        Livewire::test(WhatsAppSettings::class)->call('unlinkDevice', $device->id);

        $this->assertNull($device->fresh()->widget_id);
    }

    public function test_tenant_device_cannot_be_linked_from_admin(): void
    {
        $widget = $this->makeWidget();
        $device = $this->platformDevice(['user_id' => $widget->user_id, 'is_platform' => false]);

        Livewire::test(WhatsAppSettings::class)
            ->call('openLink', $device->id)
            ->assertSet('linkDeviceId', null);

        $this->assertNull($device->fresh()->widget_id);
    }
}
