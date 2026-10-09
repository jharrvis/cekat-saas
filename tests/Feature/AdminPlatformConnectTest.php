<?php

namespace Tests\Feature;

use App\Livewire\Admin\WhatsAppSettings;
use App\Models\Setting;
use App\Models\User;
use App\Models\WhatsAppDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Admin reconnect for platform devices: the imported account device
 * has no tenant owner, so the tenant QR page (owner-gated policy) can
 * never serve it. The monitoring tab now hosts the QR flow itself:
 * connectDevice fetches the Fonnte QR, the panel polls until the
 * phone scan lands.
 */
class AdminPlatformConnectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('fonnte_account_token', 'account-token-test');
    }

    private function platformDevice(array $overrides = []): WhatsAppDevice
    {
        return WhatsAppDevice::create(array_merge([
            'user_id' => null,
            'is_platform' => true,
            'fonnte_device_token' => 'dev-token-1',
            'phone_number' => '6285172238819',
            'device_name' => 'BMP',
            'status' => 'disconnected',
            'plan' => 'lite',
        ], $overrides));
    }

    private function fakeFonnte(array $deviceStatus): void
    {
        Http::fake([
            'https://api.fonnte.com/qr' => Http::response(['status' => true, 'url' => 'QRPAYLOAD'], 200),
            'https://api.fonnte.com/device' => Http::response($deviceStatus, 200),
        ]);
    }

    public function test_connect_shows_qr_and_marks_device_connecting(): void
    {
        $device = $this->platformDevice();
        $this->fakeFonnte(['status' => true, 'device_status' => 'disconnect', 'device' => '6285172238819']);

        Livewire::test(WhatsAppSettings::class)
            ->call('connectDevice', $device->id)
            ->assertSet('connectState', 'waiting')
            ->assertSet('connectQrImage', 'QRPAYLOAD')
            ->assertSet('connectDeviceId', $device->id);

        $this->assertSame('connecting', $device->fresh()->status);
    }

    public function test_polling_flips_to_connected_after_scan(): void
    {
        $device = $this->platformDevice();
        $this->fakeFonnte(['status' => true, 'device_status' => 'connect', 'device' => '6285172238819']);

        Livewire::test(WhatsAppSettings::class)
            ->call('connectDevice', $device->id)
            ->assertSet('connectState', 'waiting')
            ->call('refreshConnectStatus')
            ->assertSet('connectState', 'connected');

        $this->assertSame('connected', $device->fresh()->status);
        $this->assertNotNull($device->fresh()->connected_at);
    }

    public function test_already_connected_device_resolves_immediately(): void
    {
        $device = $this->platformDevice();

        Http::fake([
            'https://api.fonnte.com/qr' => Http::response(['status' => false, 'reason' => 'device already connected'], 200),
        ]);

        Livewire::test(WhatsAppSettings::class)
            ->call('connectDevice', $device->id)
            ->assertSet('connectState', 'connected');

        $this->assertSame('connected', $device->fresh()->status);
    }

    public function test_tenant_device_is_refused(): void
    {
        $owner = User::create([
            'name' => 'Tenant',
            'email' => 'tenant-connect@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
        ]);

        $device = $this->platformDevice(['user_id' => $owner->id, 'is_platform' => false]);
        $this->fakeFonnte(['status' => true, 'device_status' => 'disconnect', 'device' => '6285172238819']);

        Livewire::test(WhatsAppSettings::class)
            ->call('connectDevice', $device->id)
            ->assertSet('connectDeviceId', null)
            ->assertSet('connectState', '');

        $this->assertSame('disconnected', $device->fresh()->status);
    }
}
