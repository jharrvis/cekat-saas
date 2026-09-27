<?php

namespace Tests\Feature;

use App\Models\AiAgent;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\KnowledgeBase;
use App\Models\Plan;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeStack(array $overrides = []): array
    {
        $plan = Plan::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'max_messages_per_month' => 100,
            'ai_tier' => 'basic',
        ]);

        $user = User::create(array_filter([
            'name' => 'Owner',
            'email' => 'owner@test.id',
            'password' => 'secret123',
            'plan_id' => $plan->id,
            'monthly_message_used' => $overrides['used'] ?? 0,
            // status defaults to 'active' via migration; only override when set
            'status' => $overrides['status'] ?? null,
        ], fn ($v) => ! is_null($v)));

        $agent = AiAgent::create([
            'user_id' => $user->id,
            'name' => 'CS Agent',
            'slug' => 'cs-agent',
        ]);

        KnowledgeBase::create([
            'ai_agent_id' => $agent->id,
            'company_name' => 'Toko Uji',
        ]);

        $widget = Widget::create([
            'user_id' => $user->id,
            'ai_agent_id' => $agent->id,
            'name' => 'Widget Uji',
            'slug' => 'w-uji',
            'settings' => $overrides['settings'] ?? [],
        ]);

        return compact('plan', 'user', 'agent', 'widget');
    }

    private function fakeOpenRouter(string $reply = 'Halo kak!'): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [['message' => ['content' => $reply]]],
                'usage' => ['total_tokens' => 42],
            ], 200),
        ]);
    }

    public function test_chat_success_path_persists_session_and_consumes_quota(): void
    {
        ['user' => $user, 'agent' => $agent, 'widget' => $widget] = $this->makeStack();
        $this->fakeOpenRouter('Silakan order di https://toko.test/order kak');

        $response = $this->postJson('/api/chat', [
            'message' => 'mau order kak',
            'widgetId' => 'w-uji',
            'history' => [],
            'sessionId' => 'sess_test_1',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('sessionId', 'sess_test_1')
            ->assertJsonPath('response', 'Silakan order di https://toko.test/order kak')
            ->assertJsonPath('meta.tokens_used', 42)
            ->assertJsonStructure(['meta' => ['model', 'tokens_used']]);

        $this->assertSame(1, $user->fresh()->monthly_message_used);

        $session = ChatSession::where('visitor_uuid', 'sess_test_1')->first();
        $this->assertNotNull($session);
        $this->assertSame($widget->id, $session->widget_id);
        $this->assertSame($agent->id, $session->current_agent_id);

        $this->assertSame(2, ChatMessage::where('session_id', $session->id)->count());
        $this->assertSame(
            0,
            ChatMessage::where('session_id', $session->id)->whereNull('ai_agent_id')->count()
        );
    }

    public function test_chat_quota_exceeded_returns_429(): void
    {
        \Illuminate\Support\Facades\Event::fake([\App\Events\QuotaExceeded::class]);

        $this->makeStack(['used' => 100]);

        $response = $this->postJson('/api/chat', [
            'message' => 'halo',
            'widgetId' => 'w-uji',
            'sessionId' => 'sess_q',
        ]);

        $response->assertStatus(429)
            ->assertJsonPath('error', 'quota_exceeded')
            ->assertJsonPath('error_code', 'quota_exceeded');
        $this->assertSame(0, ChatSession::count());

        \Illuminate\Support\Facades\Event::assertDispatched(
            \App\Events\QuotaExceeded::class,
            fn ($e) => $e->used === 100 && $e->limit === 100
        );
    }

    public function test_chat_domain_blocked_returns_403(): void
    {
        $this->makeStack(['settings' => ['allowed_domains' => 'toko-resmi.id']]);

        $response = $this->postJson('/api/chat', [
            'message' => 'halo',
            'widgetId' => 'w-uji',
            'sessionId' => 'sess_d',
        ], ['Origin' => 'https://toko-palsu.test']);

        $response->assertForbidden()
            ->assertJsonPath('error', 'Domain not allowed')
            ->assertJsonPath('error_code', 'domain_blocked');
        $this->assertSame(0, ChatSession::count());
    }

    public function test_chat_suspended_owner_returns_403(): void
    {
        $this->makeStack(['status' => 'suspended']);

        $response = $this->postJson('/api/chat', [
            'message' => 'halo',
            'widgetId' => 'w-uji',
            'sessionId' => 'sess_s',
        ]);

        $response->assertForbidden()->assertJsonPath('error', 'Widget temporarily unavailable');
    }

    public function test_chat_unknown_widget_returns_404(): void
    {
        // The 404 branch only runs when the demo JSON fallback is absent,
        // so hide it for the duration of this test and restore afterwards.
        $demo = storage_path('app/data/knowledge-base.json');
        $backup = storage_path('app/data/knowledge-base.json.bak');
        $moved = file_exists($demo) && rename($demo, $backup);

        try {
            $response = $this->postJson('/api/chat', [
                'message' => 'halo',
                'widgetId' => 'tidak-ada',
                'sessionId' => 'sess_x',
            ]);

            $response->assertNotFound()->assertJsonPath('error', 'Widget not found');
        } finally {
            if ($moved) {
                rename($backup, $demo);
            }
        }
    }
}
