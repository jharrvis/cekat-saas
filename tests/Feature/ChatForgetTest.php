<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Plan;
use App\Models\User;
use App\Models\Widget;
use App\Services\Chat\SessionIdService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Data-subject requests: the visitor can wipe their conversation from
 * the widget (signed session id + IP/UA fingerprint), the tenant can
 * delete a chat/lead from the dashboard, guests cannot.
 */
class ChatForgetTest extends TestCase
{
    use RefreshDatabase;

    private function makeStack(): array
    {
        $plan = Plan::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'max_messages_per_month' => 100,
            'ai_tier' => 'basic',
        ]);

        $owner = User::create([
            'name' => 'Owner',
            'email' => 'owner-fgt@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
            'plan_id' => $plan->id,
        ]);

        $widget = Widget::create([
            'user_id' => $owner->id,
            'name' => 'Widget Forget',
            'slug' => 'w-fgt',
            'status' => 'active',
        ]);

        return compact('plan', 'owner', 'widget');
    }

    private function makeSession(Widget $widget, string $visitorUuid, array $overrides = []): ChatSession
    {
        return ChatSession::create(array_merge([
            'widget_id' => $widget->id,
            'visitor_uuid' => $visitorUuid,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'started_at' => now(),
        ], $overrides));
    }

    private function signedId(): string
    {
        return app(SessionIdService::class)->mint();
    }

    // --- Visitor forget (DELETE /api/widget/session) ---

    public function test_visitor_can_forget_own_session(): void
    {
        ['widget' => $widget] = $this->makeStack();
        $sid = $this->signedId();
        $session = $this->makeSession($widget, $sid);
        ChatMessage::create(['session_id' => $session->id, 'role' => 'user', 'content' => 'rahasia']);

        $this->withHeaders(['User-Agent' => 'PHPUnit'])
            ->deleteJson('/api/widget/session', ['widgetId' => 'w-fgt', 'sessionId' => $sid])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('chat_sessions', ['id' => $session->id]);
        $this->assertDatabaseMissing('chat_messages', ['session_id' => $session->id]);
    }

    public function test_forget_rejects_unsigned_session_id(): void
    {
        ['widget' => $widget] = $this->makeStack();
        $session = $this->makeSession($widget, 'sess_unsigned_legacy');

        $this->withHeaders(['User-Agent' => 'PHPUnit'])
            ->deleteJson('/api/widget/session', ['widgetId' => 'w-fgt', 'sessionId' => 'sess_unsigned_legacy'])
            ->assertForbidden()
            ->assertJsonPath('error_code', 'invalid_session');

        $this->assertDatabaseHas('chat_sessions', ['id' => $session->id]);
    }

    public function test_forget_forbidden_on_fingerprint_mismatch(): void
    {
        ['widget' => $widget] = $this->makeStack();
        $sid = $this->signedId();
        $session = $this->makeSession($widget, $sid, ['ip_address' => '10.11.12.13']);

        $this->withHeaders(['User-Agent' => 'PHPUnit'])
            ->deleteJson('/api/widget/session', ['widgetId' => 'w-fgt', 'sessionId' => $sid])
            ->assertForbidden()
            ->assertJsonPath('error_code', 'fingerprint_mismatch');

        $this->assertDatabaseHas('chat_sessions', ['id' => $session->id]);
    }

    public function test_forget_is_idempotent_without_a_session(): void
    {
        $this->makeStack();

        $this->withHeaders(['User-Agent' => 'PHPUnit'])
            ->deleteJson('/api/widget/session', ['widgetId' => 'w-fgt', 'sessionId' => $this->signedId()])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_forget_unknown_widget_returns_404(): void
    {
        $this->makeStack();

        $this->withHeaders(['User-Agent' => 'PHPUnit'])
            ->deleteJson('/api/widget/session', ['widgetId' => 'tidak-ada', 'sessionId' => $this->signedId()])
            ->assertNotFound();
    }

    // --- Tenant delete (dashboard) ---

    public function test_tenant_can_delete_own_chat_session(): void
    {
        ['owner' => $owner, 'widget' => $widget] = $this->makeStack();
        $session = $this->makeSession($widget, 'sess_tenant_del');
        ChatMessage::create(['session_id' => $session->id, 'role' => 'user', 'content' => 'isi chat']);

        $this->actingAs($owner)
            ->delete('/chats/'.$session->id)
            ->assertRedirect();

        $this->assertDatabaseMissing('chat_sessions', ['id' => $session->id]);
        $this->assertDatabaseMissing('chat_messages', ['session_id' => $session->id]);
    }

    public function test_tenant_can_delete_a_lead_via_leads_alias(): void
    {
        ['owner' => $owner, 'widget' => $widget] = $this->makeStack();
        $session = $this->makeSession($widget, 'sess_lead_del');

        $this->actingAs($owner)
            ->delete('/leads/'.$session->id)
            ->assertRedirect();

        $this->assertDatabaseMissing('chat_sessions', ['id' => $session->id]);
    }

    public function test_tenant_cannot_delete_foreign_session(): void
    {
        ['widget' => $widget] = $this->makeStack();
        $session = $this->makeSession($widget, 'sess_foreign');

        $plan = Plan::create(['name' => 'Other', 'slug' => 'other', 'max_messages_per_month' => 100]);
        $intruder = User::create([
            'name' => 'Intruder',
            'email' => 'intruder@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
            'plan_id' => $plan->id,
        ]);

        $this->actingAs($intruder)
            ->delete('/chats/'.$session->id)
            ->assertNotFound();

        $this->assertDatabaseHas('chat_sessions', ['id' => $session->id]);
    }

    public function test_guest_cannot_delete_session(): void
    {
        ['widget' => $widget] = $this->makeStack();
        $session = $this->makeSession($widget, 'sess_guest_del');

        $this->delete('/chats/'.$session->id)->assertRedirect('/login');

        $this->assertDatabaseHas('chat_sessions', ['id' => $session->id]);
    }
}
