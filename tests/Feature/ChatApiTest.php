<?php

namespace Tests\Feature;

use App\Models\AiAgent;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Mail\NewLead;
use App\Models\KnowledgeBase;
use App\Models\Plan;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
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
            'email_verified_at' => now(),
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
        ], ['Origin' => 'https://toko.test']);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('response', 'Silakan order di https://toko.test/order kak')
            ->assertJsonPath('meta.tokens_used', 42)
            ->assertJsonStructure(['meta' => ['model', 'tokens_used']]);

        // Unsigned client-supplied ids are replaced by server-minted signed ones
        $sessionId = $response->json('sessionId');
        $this->assertNotSame('sess_test_1', $sessionId);
        $this->assertMatchesRegularExpression('/^sess_[A-Za-z0-9]{24}\.[0-9a-f]{64}$/', $sessionId);

        $this->assertSame(1, $user->fresh()->monthly_message_used);

        $session = ChatSession::where('visitor_uuid', $sessionId)->first();
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
        ], ['Origin' => 'https://toko.test']);

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
        ], ['Origin' => 'https://toko.test']);

        $response->assertForbidden()->assertJsonPath('error', 'Widget temporarily unavailable');
    }

    public function test_save_lead_reply_is_stripped_persists_lead_and_emails_owner_without_webhook(): void
    {
        Mail::fake();

        ['plan' => $plan, 'widget' => $widget] = $this->makeStack();
        $plan->update(['can_export_leads' => true]);
        $this->assertEmpty($widget->settings['webhook_url'] ?? null);

        $this->fakeOpenRouter(
            'Terima kasih Kak Budi! Data sudah saya catat lengkap ya. '
            . '{"action": "save_lead", "name": "Budi Gunawan", "email": "bdgwn@test.id", "phone": "0812345464458"}'
        );

        $response = $this->postJson('/api/chat', [
            'message' => 'Nama Budi, email bdgwn@test.id, HP 0812345464458',
            'widgetId' => 'w-uji',
            'history' => [],
            'sessionId' => 'sess_lead_e2e',
        ], ['Origin' => 'https://toko.test']);

        $response->assertOk();
        $reply = $response->json('response');
        $this->assertStringContainsString('Terima kasih Kak Budi', $reply);
        $this->assertStringNotContainsString('save_lead', $reply);
        $this->assertStringNotContainsString('{', $reply);

        $session = ChatSession::where('visitor_uuid', $response->json('sessionId'))->first();
        $this->assertNotNull($session);
        $this->assertTrue((bool) $session->is_lead);
        $this->assertSame('Budi Gunawan', $session->visitor_name);
        $this->assertSame('bdgwn@test.id', $session->visitor_email);
        $this->assertSame('0812345464458', $session->visitor_phone);

        Mail::assertSent(NewLead::class, fn ($m) => $m->hasTo('owner@test.id'));
    }

    public function test_strict_json_action_reply_without_webhook_returns_friendly_message(): void
    {
        $this->makeStack();

        $this->fakeOpenRouter('{"action": "save_lead", "name": "Budi"}');

        $response = $this->postJson('/api/chat', [
            'message' => 'simpan lead',
            'widgetId' => 'w-uji',
            'history' => [],
            'sessionId' => 'sess_strict_json',
        ], ['Origin' => 'https://toko.test']);

        $response->assertOk()
            ->assertJsonPath('response', 'Data berhasil diproses.');
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
            ], ['Origin' => 'https://toko.test']);

            $response->assertNotFound()->assertJsonPath('error', 'Widget not found');
        } finally {
            if ($moved) {
                rename($backup, $demo);
            }
        }
    }
}
