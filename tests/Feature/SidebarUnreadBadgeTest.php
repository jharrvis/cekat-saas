<?php

namespace Tests\Feature;

use App\Models\ChatSession;
use App\Models\Plan;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sidebar unread counters ("belum dibuka") for Kotak Masuk + Leads:
 * a session counts while read_at is null; opening its detail page
 * (chats.show — used by both lists) marks it read; new visitor
 * activity marks it unread again. Preview sessions never count and
 * other users' sessions never count.
 */
class SidebarUnreadBadgeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Widget $widget;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::create([
            'name' => 'Pro', 'slug' => 'pro-badge',
            'price' => 99000, 'max_messages_per_month' => 3000,
            'can_export_leads' => true,
            'features' => ['leads' => true],
        ]);

        $this->user = User::create([
            'name' => 'Badge Owner',
            'email' => 'badge-' . uniqid() . '@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
            'role' => 'user',
            'plan_id' => $plan->id,
            'plan_expires_at' => now()->addMonth(),
        ]);

        $this->widget = Widget::create([
            'user_id' => $this->user->id,
            'name' => 'w-badge',
            'slug' => 'w-badge-' . uniqid(),
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    private function makeSession(array $overrides = []): ChatSession
    {
        return ChatSession::create(array_merge([
            'widget_id' => $this->widget->id,
            'visitor_uuid' => 'sess_' . uniqid(),
            'is_preview' => false,
            'read_at' => null,
        ], $overrides));
    }

    private function badgeHtml(int $count): string
    {
        return 'rounded-full">' . $count . '</span>';
    }

    public function test_dashboard_sidebar_shows_unread_counts_for_inbox_and_leads(): void
    {
        $this->makeSession();                                                  // unread, no contact
        $this->makeSession(['visitor_email' => 'lead@test.id']);                // unread lead
        $this->makeSession(['visitor_name' => 'Sudah Dibaca', 'read_at' => now()]); // read lead
        $this->makeSession(['is_preview' => true]);                             // preview: never counts

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        // Inbox unread = 2 (plain + lead); leads unread = 1.
        $response->assertSee($this->badgeHtml(2), false);
        $response->assertSee($this->badgeHtml(1), false);
    }

    public function test_badges_hidden_when_nothing_unread(): void
    {
        $this->makeSession(['read_at' => now()]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('bg-red-500 text-white', false);
    }

    public function test_opening_session_detail_marks_it_read_and_drops_counters(): void
    {
        $lead = $this->makeSession(['visitor_phone' => '08123456789']);

        $this->actingAs($this->user)->get(route('chats.show', $lead->id))->assertOk();
        $this->assertNotNull($lead->fresh()->read_at);

        $response = $this->actingAs($this->user)->get(route('dashboard'));
        $response->assertDontSee('bg-red-500 text-white', false);
    }

    public function test_mark_as_unread_reactivates_the_counter(): void
    {
        $session = $this->makeSession(['read_at' => now()]);

        $session->markAsUnread();

        $this->assertNull($session->fresh()->read_at);
        $response = $this->actingAs($this->user)->get(route('dashboard'));
        $response->assertSee($this->badgeHtml(1), false);
    }

    public function test_other_users_sessions_never_count(): void
    {
        $otherWidget = Widget::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'w-other', 'slug' => 'w-other-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);
        ChatSession::create([
            'widget_id' => $otherWidget->id,
            'visitor_uuid' => 'sess_' . uniqid(),
            'visitor_email' => 'asing@test.id',
            'is_preview' => false,
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));
        $response->assertDontSee('bg-red-500 text-white', false);
    }
}
