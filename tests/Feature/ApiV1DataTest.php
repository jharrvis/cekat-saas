<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Plan;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Public read API data endpoints (/api/v1): resource shape, per-user
 * scoping, cursor pagination, and PII minimization.
 */
class ApiV1DataTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private string $secret;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::create([
            'name' => 'Pro API',
            'slug' => 'pro-api-data',
            'max_messages_per_month' => 1000,
            'can_export_leads' => true,
            'features' => ['api_access' => true],
        ]);

        $this->owner = User::create([
            'name' => 'Data Owner',
            'email' => 'owner-data@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
            'plan_id' => $plan->id,
        ]);

        [, $this->secret] = ApiKey::generate($this->owner, 'Data Key');
    }

    private function auth(): array
    {
        return ['Authorization' => 'Bearer ' . $this->secret];
    }

    private function widgetFor(User $owner): Widget
    {
        return Widget::create([
            'user_id' => $owner->id,
            'name' => 'Widget Data',
            'slug' => 'w-data-' . uniqid(),
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    private function sessionWithContact(Widget $widget, array $contact = []): ChatSession
    {
        return ChatSession::create(array_merge([
            'widget_id' => $widget->id,
            'visitor_uuid' => 'sess-data-' . uniqid(),
            'started_at' => now(),
        ], $contact));
    }

    public function test_leads_index_returns_only_own_leads_with_contact(): void
    {
        $widget = $this->widgetFor($this->owner);
        $lead = $this->sessionWithContact($widget, ['visitor_name' => 'Rinto', 'visitor_email' => 'r@test.id']);
        $this->sessionWithContact($widget); // no contact => not a lead

        // Another user's lead must never appear.
        $otherPlan = Plan::create(['name' => 'Other', 'slug' => 'other-api', 'max_messages_per_month' => 10, 'features' => ['api_access' => true]]);
        $other = User::create(['name' => 'Other', 'email' => 'other-api@test.id', 'password' => 'x', 'email_verified_at' => now(), 'plan_id' => $otherPlan->id]);
        $otherWidget = $this->widgetFor($other);
        $this->sessionWithContact($otherWidget, ['visitor_name' => 'Secret Other']);

        $response = $this->getJson('/api/v1/leads', $this->auth());

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertSame($lead->id, $response->json('data.0.id'));
        $this->assertSame('Rinto', $response->json('data.0.name'));
    }

    public function test_widget_id_filter_accepts_numeric_id_and_slug_and_rejects_unknown(): void
    {
        $widgetA = $this->widgetFor($this->owner);
        $widgetB = $this->widgetFor($this->owner);
        $leadA = $this->sessionWithContact($widgetA, ['visitor_name' => 'Lead A']);
        $this->sessionWithContact($widgetB, ['visitor_name' => 'Lead B']);

        // Numeric ID filters correctly.
        $res = $this->getJson('/api/v1/leads?widget_id=' . $widgetA->id, $this->auth());
        $res->assertStatus(200);
        $this->assertSame([$leadA->id], array_column($res->json('data'), 'id'));

        // Slug (as returned in widget.slug) filters the same way.
        $res = $this->getJson('/api/v1/leads?widget_id=' . $widgetA->slug, $this->auth());
        $res->assertStatus(200);
        $this->assertSame([$leadA->id], array_column($res->json('data'), 'id'));

        // Unknown value must be a 400, never silently ignored.
        $this->getJson('/api/v1/leads?widget_id=does-not-exist', $this->auth())
            ->assertStatus(400)
            ->assertJson(['error_code' => 'invalid_param']);

        $this->getJson('/api/v1/sessions?widget_id=does-not-exist', $this->auth())
            ->assertStatus(400)
            ->assertJson(['error_code' => 'invalid_param']);

        // Sessions endpoint accepts slug too.
        $res = $this->getJson('/api/v1/sessions?widget_id=' . $widgetB->slug, $this->auth());
        $res->assertStatus(200);
        $this->assertCount(1, $res->json('data'));
        $this->assertSame($widgetB->id, $res->json('data.0.widget.id'));
    }

    public function test_leads_show_includes_summary_and_cross_user_returns_404(): void
    {
        $widget = $this->widgetFor($this->owner);
        $lead = $this->sessionWithContact($widget, ['visitor_name' => 'Budi Santoso', 'summary' => 'Ringkasan tes']);

        $this->getJson('/api/v1/leads/' . $lead->id, $this->auth())
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Budi Santoso')
            ->assertJsonPath('data.summary', 'Ringkasan tes');

        $otherPlan = Plan::create(['name' => 'Other2', 'slug' => 'other2-api', 'max_messages_per_month' => 10, 'features' => ['api_access' => true]]);
        $other = User::create(['name' => 'Other2', 'email' => 'other2-api@test.id', 'password' => 'x', 'email_verified_at' => now(), 'plan_id' => $otherPlan->id]);
        [, $otherSecret] = ApiKey::generate($other, 'Other Key');
        $otherWidget = $this->widgetFor($other);
        $foreign = $this->sessionWithContact($otherWidget, ['visitor_name' => 'Foreign']);

        $this->getJson('/api/v1/leads/' . $foreign->id, ['Authorization' => 'Bearer ' . $otherSecret])
            ->assertStatus(200);

        // Our key must not see the foreign lead.
        $this->getJson('/api/v1/leads/' . $foreign->id, $this->auth())
            ->assertStatus(404)
            ->assertJson(['error_code' => 'not_found']);
    }

    public function test_sessions_index_filters_by_is_lead_and_decodes_visitor_fields(): void
    {
        $widget = $this->widgetFor($this->owner);
        $this->sessionWithContact($widget, ['visitor_name' => 'Nama Enkrip', 'is_lead' => true]);
        $this->sessionWithContact($widget);

        $response = $this->getJson('/api/v1/sessions?is_lead=1', $this->auth());

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Nama Enkrip', $response->json('data.0.name'));
        // Raw connection metadata must never be exposed.
        $this->assertArrayNotHasKey('ip_address', $response->json('data.0'));
        $this->assertArrayNotHasKey('user_agent', $response->json('data.0'));
    }

    public function test_session_messages_return_decrypted_content(): void
    {
        $widget = $this->widgetFor($this->owner);
        $session = $this->sessionWithContact($widget, ['visitor_name' => 'Pesan']);

        ChatMessage::create(['session_id' => $session->id, 'role' => 'user', 'content' => 'Halo kak']);
        ChatMessage::create(['session_id' => $session->id, 'role' => 'assistant', 'content' => 'Halo juga!']);

        $response = $this->getJson('/api/v1/sessions/' . $session->id . '/messages', $this->auth());

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
        $this->assertSame('Halo kak', $response->json('data.0.content'));
        $this->assertSame('assistant', $response->json('data.1.role'));
    }

    public function test_widgets_index_lists_own_widgets_only(): void
    {
        $this->widgetFor($this->owner);

        $response = $this->getJson('/api/v1/widgets', $this->auth());

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertArrayHasKey('slug', $response->json('data.0'));
    }

    public function test_stats_returns_conversion_shape(): void
    {
        $widget = $this->widgetFor($this->owner);
        $this->sessionWithContact($widget, ['visitor_name' => 'A', 'is_lead' => true]);
        $this->sessionWithContact($widget);

        $response = $this->getJson('/api/v1/stats', $this->auth());

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['total', 'this_month', 'this_week', 'conversion_rate', 'total_sessions']]);
        $this->assertSame(1, $response->json('data.total'));
        $this->assertSame(2, $response->json('data.total_sessions'));
    }

    public function test_cursor_pagination_walks_newest_first(): void
    {
        $widget = $this->widgetFor($this->owner);
        $first = $this->sessionWithContact($widget, ['visitor_name' => 'First']);
        $second = $this->sessionWithContact($widget, ['visitor_name' => 'Second']);

        $page1 = $this->getJson('/api/v1/leads?limit=1', $this->auth());
        $page1->assertStatus(200);
        $this->assertSame($second->id, $page1->json('data.0.id'));
        $this->assertNotNull($page1->json('meta.next_cursor'));

        $page2 = $this->getJson('/api/v1/leads?limit=1&cursor=' . $page1->json('meta.next_cursor'), $this->auth());
        $this->assertSame($first->id, $page2->json('data.0.id'));
        $this->assertNull($page2->json('meta.next_cursor'));
    }

    public function test_date_filters_apply(): void
    {
        $widget = $this->widgetFor($this->owner);
        $old = $this->sessionWithContact($widget, ['visitor_name' => 'Old']);
        $old->forceFill(['created_at' => now()->subDays(10)])->save();
        $this->sessionWithContact($widget, ['visitor_name' => 'New']);

        $response = $this->getJson('/api/v1/leads?since=' . now()->subDay()->toDateString(), $this->auth());

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('New', $response->json('data.0.name'));
    }
}
