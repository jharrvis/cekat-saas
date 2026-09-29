<?php

namespace Tests\Feature;

use App\Models\ChatSession;
use App\Models\User;
use App\Models\Widget;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The app stores AND renders timestamps in WIB (Asia/Jakarta).
 *
 * Historical context: everything used to be stored in UTC while the views
 * printed raw values and the emails labelled them "WIB" - a 7-hour skew
 * reported as "jam chat tidak sinkron". Migration
 * 2026_09_30_080000_shift_datetimes_from_utc_to_wib rewrote stored values;
 * APP_TIMEZONE=Asia/Jakarta keeps them aligned from then on.
 */
class TimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_runs_in_wib(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        $this->assertSame('Asia/Jakarta', now()->timezoneName);
        $this->assertSame('+07:00', now()->format('P'));
        // WIB wall clock sits exactly 7 hours ahead of the UTC wall clock
        $this->assertSame('+07:00', Carbon::now('Asia/Jakarta')->format('P'));
        $this->assertSame('+00:00', Carbon::now('UTC')->format('P'));
    }

    public function test_chat_detail_renders_stored_wall_clock_without_reconversion(): void
    {
        $user = User::create([
            'name' => 'TZ Owner',
            'email' => 'tz-owner-' . uniqid() . '@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
            'role' => 'user',
        ]);

        $widget = Widget::create([
            'user_id' => $user->id,
            'name' => 'w-tz',
            'slug' => 'w-tz-' . uniqid(),
            'status' => 'active',
            'is_active' => true,
        ]);

        $session = ChatSession::create([
            'widget_id' => $widget->id,
            'visitor_uuid' => 'sess_TzUuidExample123456789012',
            'started_at' => '2026-05-05 14:30:00',
        ]);

        // created_at is not mass assignable - set it directly
        $session->created_at = '2026-05-05 14:30:00';
        $session->save();

        $this->actingAs($user)
            ->get(route('chats.show', $session->id))
            ->assertOk()
            ->assertSee('05 May 2026 14:30');
    }
}
