<?php

namespace Tests\Feature;

use App\Models\AiAgent;
use App\Models\ChatSession;
use App\Models\KnowledgeBase;
use App\Models\Plan;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Remediation T-07 / findings F-06 + F-07: owner test surfaces (agent test
 * panel, widget customizer) run in preview mode — they must not consume the
 * owner's monthly quota and must not pollute the customer chat history.
 */
class ChatPreviewTest extends TestCase
{
    use RefreshDatabase;

    private function makeStack(): array
    {
        $plan = Plan::create([
            'name' => 'Starter', 'slug' => 'starter',
            'max_messages_per_month' => 100, 'ai_tier' => 'basic',
        ]);
        $user = User::create([
            'name' => 'Owner', 'email' => 'owner@test.id', 'password' => 'secret123',
            'email_verified_at' => now(), 'plan_id' => $plan->id, 'monthly_message_used' => 0,
        ]);
        $agent = AiAgent::create(['user_id' => $user->id, 'name' => 'CS Agent', 'slug' => 'cs-agent']);
        KnowledgeBase::create(['ai_agent_id' => $agent->id, 'company_name' => 'Toko Uji']);
        $widget = Widget::create([
            'user_id' => $user->id, 'ai_agent_id' => $agent->id,
            'name' => 'Widget Uji', 'slug' => 'w-uji', 'settings' => [],
        ]);

        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [['message' => ['content' => 'Halo kak!']]],
                'usage' => ['total_tokens' => 42],
            ], 200),
        ]);

        return compact('plan', 'user', 'agent', 'widget');
    }

    public function test_preview_chat_does_not_consume_quota_and_is_flagged(): void
    {
        ['user' => $user] = $this->makeStack();

        $this->postJson('/api/chat', [
            'message' => 'Halo, ini uji coba',
            'widgetId' => 'w-uji',
            'preview' => true,
        ], ['Origin' => 'https://toko.test'])->assertOk();

        $this->assertSame(0, (int) $user->fresh()->monthly_message_used);
        $session = ChatSession::first();
        $this->assertNotNull($session);
        $this->assertTrue($session->is_preview);
    }

    public function test_normal_chat_consumes_quota_and_is_not_preview(): void
    {
        ['user' => $user] = $this->makeStack();

        $this->postJson('/api/chat', [
            'message' => 'Halo dari pengunjung',
            'widgetId' => 'w-uji',
        ], ['Origin' => 'https://toko.test'])->assertOk();

        $this->assertSame(1, (int) $user->fresh()->monthly_message_used);
        $this->assertFalse(ChatSession::first()->is_preview);
    }

    public function test_preview_sessions_are_excluded_from_history_listing(): void
    {
        ['user' => $user] = $this->makeStack();

        $this->postJson('/api/chat', ['message' => 'uji', 'widgetId' => 'w-uji', 'preview' => true], ['Origin' => 'https://toko.test'])->assertOk();
        $this->postJson('/api/chat', ['message' => 'asli', 'widgetId' => 'w-uji'], ['Origin' => 'https://toko.test'])->assertOk();

        $this->assertSame(2, ChatSession::count());
        $this->assertSame(1, ChatSession::real()->count());

        // The history page itself only surfaces the real session (stats total 1).
        $response = $this->actingAs($user)->get(route('chats.index'));
        $response->assertOk();
        $response->assertViewHas('sessions', fn ($sessions) => $sessions->total() === 1);
    }

    public function test_attach_default_widget_links_first_unlinked_widget(): void
    {
        $plan = Plan::create(['name' => 'Starter', 'slug' => 'starter', 'price' => 0]);
        $user = User::create([
            'name' => 'Owner', 'email' => 'o2@test.id', 'password' => 'secret123',
            'email_verified_at' => now(), 'plan_id' => $plan->id,
        ]);
        $agent = AiAgent::create(['user_id' => $user->id, 'name' => 'Agen', 'slug' => 'agen']);
        $widget = Widget::create([
            'user_id' => $user->id, 'ai_agent_id' => null,
            'name' => 'Widget Bawaan', 'slug' => 'w-bawaan', 'settings' => [],
        ]);

        $this->actingAs($user)
            ->post(route('agents.attach-default-widget', $agent))
            ->assertRedirect(route('agents.edit', $agent));

        $this->assertSame($agent->id, $widget->fresh()->ai_agent_id);
    }

    public function test_attach_default_widget_forbidden_for_other_users_agent(): void
    {
        $plan = Plan::create(['name' => 'Starter', 'slug' => 'starter', 'price' => 0]);
        $owner = User::create([
            'name' => 'Owner', 'email' => 'o3@test.id', 'password' => 'secret123',
            'email_verified_at' => now(), 'plan_id' => $plan->id,
        ]);
        $stranger = User::create([
            'name' => 'Orang', 'email' => 'o4@test.id', 'password' => 'secret123',
            'email_verified_at' => now(), 'plan_id' => $plan->id,
        ]);
        $agent = AiAgent::create(['user_id' => $owner->id, 'name' => 'Agen', 'slug' => 'agen-x']);
        Widget::create([
            'user_id' => $stranger->id, 'ai_agent_id' => null,
            'name' => 'W', 'slug' => 'w-x', 'settings' => [],
        ]);

        $this->actingAs($stranger)
            ->post(route('agents.attach-default-widget', $agent))
            ->assertForbidden();
    }
}
