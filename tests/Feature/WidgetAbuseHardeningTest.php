<?php

namespace Tests\Feature;

use App\Events\DomainBlocked;
use App\Events\QuotaExceeded;
use App\Mail\WidgetAbuseAlert;
use App\Models\AiAgent;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Abuse hardening, two halves:
 *  - a spike of DomainBlocked / QuotaExceeded events emails the
 *    widget owner once per cooldown (never a flood);
 *  - the channels list badges active widgets whose allowed_domains
 *    is still empty, so the open configuration is visible without
 *    opening the Domains tab.
 */
class WidgetAbuseHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Mail::fake();
    }

    private function ownerWithWidget(array $settings = []): array
    {
        $owner = User::create([
            'name' => 'Owner',
            'email' => 'abuse-owner-' . uniqid() . '@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
        ]);

        $agent = AiAgent::create([
            'user_id' => $owner->id,
            'name' => 'CS Agent',
            'slug' => 'cs-abuse-' . uniqid(),
        ]);

        $widget = Widget::create([
            'user_id' => $owner->id,
            'ai_agent_id' => $agent->id,
            'name' => 'Widget Toko',
            'slug' => 'w-abuse-' . uniqid(),
            'status' => 'active',
            'is_active' => true,
            'settings' => $settings,
        ]);

        return [$owner, $widget];
    }

    public function test_domain_block_spike_emails_owner_once_per_cooldown(): void
    {
        [$owner, $widget] = $this->ownerWithWidget();

        for ($i = 0; $i < 19; $i++) {
            DomainBlocked::dispatch($widget->slug, 'https://evil.example');
        }
        Mail::assertNothingSent();

        DomainBlocked::dispatch($widget->slug, 'https://evil.example');
        Mail::assertSent(WidgetAbuseAlert::class, 1);
        Mail::assertSent(WidgetAbuseAlert::class, fn ($mail) => $mail->hasTo($owner->email) && $mail->type === 'domain');

        for ($i = 0; $i < 15; $i++) {
            DomainBlocked::dispatch($widget->slug, 'https://evil.example');
        }
        Mail::assertSent(WidgetAbuseAlert::class, 1); // cooldown: no flood
    }

    public function test_quota_spike_emails_owner_once(): void
    {
        [$owner, $widget] = $this->ownerWithWidget();

        for ($i = 0; $i < 49; $i++) {
            QuotaExceeded::dispatch($owner->id, $widget->slug, 500, 500);
        }
        Mail::assertNothingSent();

        QuotaExceeded::dispatch($owner->id, $widget->slug, 500, 500);
        Mail::assertSent(WidgetAbuseAlert::class, 1);
        Mail::assertSent(WidgetAbuseAlert::class, fn ($mail) => $mail->hasTo($owner->email) && $mail->type === 'quota');
    }

    public function test_unknown_widget_slug_is_ignored_quietly(): void
    {
        for ($i = 0; $i < 25; $i++) {
            DomainBlocked::dispatch('slug-tidak-ada', 'https://evil.example');
        }

        Mail::assertNothingSent();
    }

    public function test_channels_list_badges_unrestricted_active_widget(): void
    {
        [$owner] = $this->ownerWithWidget([]);

        $this->actingAs($owner)
            ->get(route('channels.index'))
            ->assertOk()
            ->assertSee(__('channels.s.domain_belum_dibatasi'));
    }

    public function test_channels_list_no_badge_when_domains_restricted(): void
    {
        [$owner] = $this->ownerWithWidget(['allowed_domains' => 'toko.id']);

        $this->actingAs($owner)
            ->get(route('channels.index'))
            ->assertOk()
            ->assertDontSee(__('channels.s.domain_belum_dibatasi'));
    }
}
