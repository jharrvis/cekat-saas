<?php

namespace Tests\Feature;

use App\Livewire\Admin\WhatsAppSettings;
use App\Models\Setting;
use App\Models\WhatsAppDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fonnte reports each device's remaining message quota on every
 * getDevices call (field "quota"), but Cekat never stored it, so the
 * Device Monitor could not show it. The sync now persists it (and
 * refreshes it, with plan expiry, for known devices), and the Plan
 * cell renders quota + expiry.
 */
class FonnteQuotaSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('fonnte_account_token', 'account-token-test');
    }

    private function fakeDevices(array $devices): void
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
            'name' => 'BMP',
            'status' => 'connect',
            'package' => 'Lite',
            'expired' => 1791763200, // 12 Oct 2026
            'quota' => '844',
        ], $overrides);
    }

    public function test_sync_import_stores_quota_and_expiry(): void
    {
        $this->fakeDevices([$this->fonnteDevice()]);

        Livewire::test(WhatsAppSettings::class)->call('syncDevices');

        $device = WhatsAppDevice::first();
        $this->assertSame(844, $device->quota_remaining);
        $this->assertSame('2026-10-12', $device->plan_expires_at->format('Y-m-d'));
    }

    public function test_resync_refreshes_quota_on_known_device(): void
    {
        $device = WhatsAppDevice::create([
            'user_id' => null,
            'is_platform' => true,
            'fonnte_device_token' => 'dev-token-1',
            'phone_number' => '6285172238819',
            'status' => 'connected',
            'plan' => 'lite',
            'quota_remaining' => 900,
        ]);

        $this->fakeDevices([$this->fonnteDevice(['quota' => '801'])]);

        Livewire::test(WhatsAppSettings::class)->call('syncDevices');

        $fresh = $device->fresh();
        $this->assertSame(801, $fresh->quota_remaining);
        $this->assertSame('2026-10-12', $fresh->plan_expires_at->format('Y-m-d'));
    }

    public function test_monitor_renders_quota_and_expiry(): void
    {
        WhatsAppDevice::create([
            'user_id' => null,
            'is_platform' => true,
            'fonnte_device_token' => 'dev-token-1',
            'phone_number' => '6285172238819',
            'device_name' => 'BMP',
            'status' => 'connected',
            'plan' => 'lite',
            'quota_remaining' => 844,
            'plan_expires_at' => '2026-10-12 00:00:00',
        ]);

        $expectedDate = now()->parse('2026-10-12')->translatedFormat('d M Y');

        Livewire::test(WhatsAppSettings::class)
            ->call('setTab', 'monitor')
            ->assertSee('844')
            ->assertSee($expectedDate);
    }
}
