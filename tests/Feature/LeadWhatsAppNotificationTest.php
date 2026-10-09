<?php

namespace Tests\Feature;

use App\Events\LeadCaptured;
use App\Models\ChatSession;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use App\Models\Widget;
use App\Models\WhatsAppDevice;
use App\Services\WhatsApp\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * WhatsApp lead notifications: when a visitor becomes a lead, the number
 * configured on the channel's Lead tab gets one WhatsApp ping per session,
 * sent via Fonnte from the platform notification device (or the owner's
 * own connected device as fallback), gated behind the Leads plan feature
 * and the WhatsApp module switch.
 */
class LeadWhatsAppNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Http::fake([
            'api.fonnte.com/*' => Http::response(['status' => true], 200),
        ]);

        Setting::set('whatsapp_module_enabled', true, 'boolean', 'whatsapp');
        Setting::set('whatsapp_lead_notif_device_token', 'platform-device-token', 'string', 'whatsapp');
    }

    private function makeStack(bool $leadsFeature, array $widgetSettings = []): array
    {
        $plan = Plan::create([
            'name' => $leadsFeature ? 'Pro' : 'Free',
            'slug' => $leadsFeature ? 'pro-wa' : 'free-wa',
            'max_messages_per_month' => 1000,
            'can_export_leads' => $leadsFeature,
        ]);

        $owner = User::create([
            'name' => 'WA Owner',
            'email' => 'owner-wa@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
            'plan_id' => $plan->id,
        ]);

        $widget = Widget::create([
            'user_id' => $owner->id,
            'name' => 'Widget WA',
            'slug' => 'w-wa-' . uniqid(),
            'status' => 'active',
            'is_active' => true,
            'settings' => array_merge([
                'lead_wa_notif_enabled' => true,
                'lead_wa_notif' => '0812-3456-7890',
                'lead_wa_new_lead' => true,
            ], $widgetSettings),
        ]);

        $session = ChatSession::create([
            'widget_id' => $widget->id,
            'visitor_uuid' => 'sess-wa-' . uniqid(),
            'started_at' => now(),
        ]);

        return [$owner, $widget, $session];
    }

    private function capture(Widget $widget, ChatSession $session, array $lead): void
    {
        event(new LeadCaptured(
            $widget->slug,
            array_keys($lead),
            $session->visitor_uuid,
            $lead,
        ));

        // The listener defers the Fonnte call to app termination so the
        // visitor's chat response is never blocked by it.
        $this->app->terminate();
    }

    public function test_whatsapp_ping_is_sent_for_a_new_lead(): void
    {
        [, $widget, $session] = $this->makeStack(true);

        $this->capture($widget, $session, ['name' => 'Budi Santoso', 'phone' => '081298765432']);

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'api.fonnte.com/send')
                && $request->header('Authorization')[0] === 'platform-device-token'
                && $request['target'] === '6281234567890'
                && str_contains($request['message'], 'Budi Santoso')
                && str_contains($request['message'], 'Widget WA')
                && str_contains($request['message'], '081298765432');
        });
    }

    public function test_only_one_ping_per_session(): void
    {
        [, $widget, $session] = $this->makeStack(true);

        // Two captures inside one request lifecycle (contact data often
        // arrives in pieces), then a single termination: exactly one
        // deferred send may be registered.
        event(new LeadCaptured($widget->slug, ['name'], $session->visitor_uuid, ['name' => 'Budi']));
        event(new LeadCaptured($widget->slug, ['email'], $session->visitor_uuid, ['email' => 'budi@example.com']));
        $this->app->terminate();

        Http::assertSentCount(1);
    }

    public function test_no_ping_when_channel_toggle_is_off(): void
    {
        [, $widget, $session] = $this->makeStack(true, ['lead_wa_notif_enabled' => false]);

        $this->capture($widget, $session, ['name' => 'Budi']);

        Http::assertNothingSent();
    }

    public function test_no_ping_without_leads_plan_feature(): void
    {
        [, $widget, $session] = $this->makeStack(false);

        $this->capture($widget, $session, ['name' => 'Budi']);

        Http::assertNothingSent();
    }

    public function test_no_ping_when_module_is_disabled(): void
    {
        Setting::set('whatsapp_module_enabled', false, 'boolean', 'whatsapp');

        [, $widget, $session] = $this->makeStack(true);

        $this->capture($widget, $session, ['name' => 'Budi']);

        Http::assertNothingSent();
    }

    public function test_falls_back_to_owners_connected_device(): void
    {
        Setting::set('whatsapp_lead_notif_device_token', '', 'string', 'whatsapp');

        [$owner, $widget, $session] = $this->makeStack(true);

        WhatsAppDevice::create([
            'user_id' => $owner->id,
            'device_name' => 'Owner Device',
            'phone_number' => '628111111111',
            'fonnte_device_token' => 'owner-device-token',
            'status' => 'connected',
        ]);

        $this->capture($widget, $session, ['name' => 'Budi']);

        Http::assertSent(fn (Request $request) => $request->header('Authorization')[0] === 'owner-device-token');
    }

    public function test_no_ping_without_any_sender_device(): void
    {
        Setting::set('whatsapp_lead_notif_device_token', '', 'string', 'whatsapp');

        [, $widget, $session] = $this->makeStack(true);

        $this->capture($widget, $session, ['name' => 'Budi']);

        Http::assertNothingSent();
    }

    public function test_falls_back_to_account_number_when_channel_number_is_empty(): void
    {
        [$owner, $widget, $session] = $this->makeStack(true, ['lead_wa_notif' => '']);
        $owner->update(['whatsapp_number' => '0857-1111-2222']);

        $this->capture($widget, $session, ['name' => 'Budi']);

        Http::assertSent(fn (Request $request) => $request['target'] === '6285711112222');
    }

    public function test_channel_number_wins_over_account_number(): void
    {
        [$owner, $widget, $session] = $this->makeStack(true);
        $owner->update(['whatsapp_number' => '6285711112222']);

        $this->capture($widget, $session, ['name' => 'Budi']);

        Http::assertSent(fn (Request $request) => $request['target'] === '6281234567890');
    }

    public function test_no_ping_when_channel_and_account_numbers_are_empty(): void
    {
        [, $widget, $session] = $this->makeStack(true, ['lead_wa_notif' => '']);

        $this->capture($widget, $session, ['name' => 'Budi']);

        Http::assertNothingSent();
    }

    public function test_phone_number_normalization(): void
    {
        $this->assertSame('6281234567890', PhoneNumber::normalizeId('0812-3456-7890'));
        $this->assertSame('6281234567890', PhoneNumber::normalizeId('+62 812 3456 7890'));
        $this->assertSame('6281234567890', PhoneNumber::normalizeId('81234567890'));
        $this->assertNull(PhoneNumber::normalizeId(''));
        $this->assertNull(PhoneNumber::normalizeId('12345'));
        $this->assertNull(PhoneNumber::normalizeId('+1 555 123 4567'));
    }
}
