<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Plan;
use App\Models\User;
use App\Models\Widget;
use App\Services\Chat\SessionIdService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Visitor conversation close (inactivity auto-close & the manual
 * "Tutup percakapan" button): marks the session ended, generates the AI
 * summary inline, idempotent, same ownership gate as the DSR delete.
 */
class WidgetSessionCloseTest extends TestCase
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
            'email' => 'owner-cls@test.id',
            'password' => 'secret123',
            'plan_id' => $plan->id,
        ]);

        $widget = Widget::create([
            'user_id' => $owner->id,
            'name' => 'Widget Close',
            'slug' => 'w-close',
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

    private function fakeSummaryProvider(): void
    {
        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Customer menanyakan harga paket. Dijelaskan pilihan paket.']],
                ],
            ], 200),
        ]);
    }

    public function test_visitor_can_close_own_active_session_with_summary(): void
    {
        ['widget' => $widget] = $this->makeStack();
        $sid = $this->signedId();
        $session = $this->makeSession($widget, $sid);
        ChatMessage::create(['session_id' => $session->id, 'role' => 'user', 'content' => 'Berapa harganya?']);
        ChatMessage::create(['session_id' => $session->id, 'role' => 'assistant', 'content' => 'Paket mulai dari Rp0.']);
        $this->fakeSummaryProvider();

        $this->withHeaders(['User-Agent' => 'PHPUnit'])
            ->postJson('/api/widget/session/close', ['widgetId' => 'w-close', 'sessionId' => $sid])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('summary', 'Customer menanyakan harga paket. Dijelaskan pilihan paket.');

        $session = $session->fresh();
        $this->assertSame('ended', $session->status);
        $this->assertNotNull($session->ended_at);
        $this->assertNotNull($session->summary);
        $this->assertNotNull($session->summary_generated_at);
        $this->assertSame(2, $session->messages()->count());
    }

    public function test_close_is_idempotent_when_already_ended(): void
    {
        ['widget' => $widget] = $this->makeStack();
        $sid = $this->signedId();
        $session = $this->makeSession($widget, $sid, [
            'status' => 'ended',
            'ended_at' => now()->subMinute(),
            'summary' => 'Ringkasan lama.',
        ]);
        ChatMessage::create(['session_id' => $session->id, 'role' => 'user', 'content' => 'halo']);
        Http::fake();

        $this->withHeaders(['User-Agent' => 'PHPUnit'])
            ->postJson('/api/widget/session/close', ['widgetId' => 'w-close', 'sessionId' => $sid])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('summary', 'Ringkasan lama.');

        Http::assertNothingSent();
        $this->assertSame('Ringkasan lama.', $session->fresh()->summary);
    }

    public function test_close_without_session_is_a_noop(): void
    {
        $this->makeStack();
        Http::fake();

        $this->withHeaders(['User-Agent' => 'PHPUnit'])
            ->postJson('/api/widget/session/close', ['widgetId' => 'w-close', 'sessionId' => $this->signedId()])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('noop', true)
            ->assertJsonPath('summary', null);

        Http::assertNothingSent();
    }

    public function test_close_without_messages_ends_session_without_llm_call(): void
    {
        ['widget' => $widget] = $this->makeStack();
        $sid = $this->signedId();
        $session = $this->makeSession($widget, $sid);
        Http::fake();

        $this->withHeaders(['User-Agent' => 'PHPUnit'])
            ->postJson('/api/widget/session/close', ['widgetId' => 'w-close', 'sessionId' => $sid])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('summary', null);

        Http::assertNothingSent();
        $session = $session->fresh();
        $this->assertSame('ended', $session->status);
        $this->assertNull($session->summary);
    }

    public function test_close_rejects_unsigned_session_id(): void
    {
        ['widget' => $widget] = $this->makeStack();
        $session = $this->makeSession($widget, 'sess_unsigned_close');

        $this->withHeaders(['User-Agent' => 'PHPUnit'])
            ->postJson('/api/widget/session/close', ['widgetId' => 'w-close', 'sessionId' => 'sess_unsigned_close'])
            ->assertForbidden()
            ->assertJsonPath('error_code', 'invalid_session');

        $this->assertSame('active', $session->fresh()->status);
    }

    public function test_close_forbidden_on_fingerprint_mismatch(): void
    {
        ['widget' => $widget] = $this->makeStack();
        $sid = $this->signedId();
        $session = $this->makeSession($widget, $sid, ['ip_address' => '10.11.12.13']);

        $this->withHeaders(['User-Agent' => 'PHPUnit'])
            ->postJson('/api/widget/session/close', ['widgetId' => 'w-close', 'sessionId' => $sid])
            ->assertForbidden()
            ->assertJsonPath('error_code', 'fingerprint_mismatch');

        $this->assertSame('active', $session->fresh()->status);
    }

    public function test_close_unknown_widget_returns_404(): void
    {
        $this->makeStack();

        $this->withHeaders(['User-Agent' => 'PHPUnit'])
            ->postJson('/api/widget/session/close', ['widgetId' => 'tidak-ada', 'sessionId' => $this->signedId()])
            ->assertNotFound();
    }

    public function test_summary_job_accepts_a_bare_session_id(): void
    {
        ['widget' => $widget] = $this->makeStack();
        $session = $this->makeSession($widget, $this->signedId());
        ChatMessage::create(['session_id' => $session->id, 'role' => 'user', 'content' => 'halo']);
        $this->fakeSummaryProvider();

        \App\Jobs\GenerateChatSummary::dispatchSync($session->id);

        $this->assertNotNull($session->fresh()->summary);
    }
}
