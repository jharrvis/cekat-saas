<?php

namespace Tests\Feature;

use App\Livewire\Admin\WhatsAppSettings;
use App\Models\Setting;
use App\Models\User;
use App\Models\WhatsAppDevice;
use App\Services\WhatsApp\FonnteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Admin Fonnte sync: previously update-only, so devices that existed
 * only in the Fonnte account could never appear locally (the table
 * stayed empty and monitoring showed nothing). Sync now imports
 * unknown account devices as platform devices, refreshes known ones
 * without touching ownership, and retires vanished platform devices.
 */
class FonnteDeviceSyncTest extends TestCase
{
    use RefreshDatabase;

    private function fakeFonnteDevices(array $devices): void
    {
        Http::fake([
            'https://api.fonnte.com/get-devices' => Http::response([
                'status' => true,
                'data' => $devices,
            ], 200),
        ]);
    }

    private function fonnteDevice(array $overrides = []): array
    {
        return array_merge([
            'token' => 'dev-token-1',
            'device' => '6285172238819',
            'name' => 'Device Utama',
            'status' => 'connect',
            'package' => 'Lite',
            'expired' => 1791763200,
        ], $overrides);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('fonnte_account_token', 'account-token-test');
    }

    public function test_sync_imports_unknown_devices_as_platform_devices(): void
    {
        $this->fakeFonnteDevices([
            $this->fonnteDevice(),
            $this->fonnteDevice(['token' => 'dev-token-2', 'device' => '6281200000000', 'status' => 'disconnect', 'package' => 'Regular']),
        ]);

        Livewire::test(WhatsAppSettings::class)->call('syncDevices');

        $this->assertDatabaseCount('whatsapp_devices', 2);

        $first = WhatsAppDevice::where('fonnte_device_token', 'dev-token-1')->first();
        $this->assertNotNull($first);
        $this->assertNull($first->user_id);
        $this->assertTrue($first->is_platform);
        $this->assertSame('connected', $first->status);
        $this->assertSame('6285172238819', $first->phone_number);
        $this->assertSame('lite', $first->plan);
        $this->assertSame('2026-10-12', $first->plan_expires_at?->format('Y-m-d'));

        $second = WhatsAppDevice::where('fonnte_device_token', 'dev-token-2')->first();
        $this->assertSame('disconnected', $second->status);
        $this->assertSame('regular', $second->plan);
    }

    public function test_sync_updates_known_tenant_device_without_touching_ownership(): void
    {
        $owner = User::create([
            'name' => 'Tenant',
            'email' => 'tenant@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
        ]);

        WhatsAppDevice::create([
            'user_id' => $owner->id,
            'fonnte_device_token' => 'dev-token-1',
            'phone_number' => '6285172238819',
            'status' => 'disconnected',
        ]);

        $this->fakeFonnteDevices([$this->fonnteDevice()]);

        Livewire::test(WhatsAppSettings::class)->call('syncDevices');

        $this->assertDatabaseCount('whatsapp_devices', 1);
        $device = WhatsAppDevice::first();
        $this->assertSame('connected', $device->status);
        $this->assertSame($owner->id, $device->user_id);
        $this->assertFalse($device->is_platform);
    }

    public function test_repeated_sync_does_not_duplicate_imported_devices(): void
    {
        $this->fakeFonnteDevices([$this->fonnteDevice()]);

        Livewire::test(WhatsAppSettings::class)->call('syncDevices');
        Livewire::test(WhatsAppSettings::class)->call('syncDevices');

        $this->assertDatabaseCount('whatsapp_devices', 1);
    }

    public function test_platform_device_missing_from_fonnte_is_marked_disconnected(): void
    {
        WhatsAppDevice::create([
            'user_id' => null,
            'is_platform' => true,
            'fonnte_device_token' => 'gone-token',
            'phone_number' => '6281100000000',
            'status' => 'connected',
        ]);

        $this->fakeFonnteDevices([$this->fonnteDevice()]);

        Livewire::test(WhatsAppSettings::class)->call('syncDevices');

        $this->assertSame('disconnected', WhatsAppDevice::where('fonnte_device_token', 'gone-token')->first()->status);
    }

    public function test_log_redaction_hides_tokens_at_any_depth(): void
    {
        $redacted = FonnteService::redactForLog([
            'data' => [
                ['token' => 'dev-token-1', 'device' => '6285172238819', 'status' => 'connect'],
                ['nested' => ['device_token' => 'abc', 'Authorization' => 'xyz', 'name' => 'Utama']],
            ],
            'status' => true,
        ]);

        $this->assertSame('[redacted]', $redacted['data'][0]['token']);
        $this->assertSame('[redacted]', $redacted['data'][1]['nested']['device_token']);
        $this->assertSame('[redacted]', $redacted['data'][1]['nested']['Authorization']);
        $this->assertSame('6285172238819', $redacted['data'][0]['device']);
        $this->assertSame('Utama', $redacted['data'][1]['nested']['name']);
        $this->assertTrue($redacted['status']);
    }
}
