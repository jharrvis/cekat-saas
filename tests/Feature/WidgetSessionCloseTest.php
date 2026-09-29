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
            'email_verified_at' => now(),
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

    public function test_cross_origin_preflight_for_close_and_delete_is_answered(): void
    {
        // Regression: without OPTIONS companion routes the preflight hit the
        // router's 405 (text/html, no CORS headers) and the browser cancelled
        // the actual request with net::ERR_FAILED - observed live on bmp.net.id.
        $this->makeStack();

        $response = $this->call(
            'OPTIONS',
            '/api/widget/session/close',
            [],
            [],
            [],
            ['HTTP_ORIGIN' => 'http://bmp.net.id', 'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST']
        );

        $response->assertStatus(204);
        $response->assertHeader('Access-Control-Allow-Origin', 'http://bmp.net.id');
        $this->assertStringContainsString('POST', (string) $response->headers->get('Access-Control-Allow-Methods'));

        $response = $this->call(
            'OPTIONS',
            '/api/widget/session',
            [],
            [],
            [],
            ['HTTP_ORIGIN' => 'http://bmp.net.id', 'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'DELETE']
        );

        $response->assertStatus(204);
        $response->assertHeader('Access-Control-Allow-Origin', 'http://bmp.net.id');
        $this->assertStringContainsString('DELETE', (string) $response->headers->get('Access-Control-Allow-Methods'));
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

    public function test_summary_rejects_prompt_echo_and_retries(): void
    {
        ['widget' => $widget] = $this->makeStack();
        $session = $this->makeSession($widget, $this->signedId());
        ChatMessage::create(['session_id' => $session->id, 'role' => 'user', 'content' => 'Cara daftarnya bagaimana?']);
        ChatMessage::create(['session_id' => $session->id, 'role' => 'assistant', 'content' => 'Silakan isi formulir pendaftaran.']);

        Http::fake([
            'openrouter.ai/*' => Http::sequence()
                ->push(['choices' => [['message' => [
                    'content' => 'We need to produce a short summary in Indonesian, natural, professional, max 3 sentences covering the topic.',
                ]]]], 200)
                ->push(['choices' => [['message' => [
                    'content' => 'Customer menanyakan cara pendaftaran. Layanan customer service menjelaskan langkah verifikasi identitas lewat email.',
                ]]]], 200),
        ]);

        \App\Jobs\GenerateChatSummary::dispatchSync($session->id);

        $fresh = $session->fresh();
        $this->assertNotNull($fresh->summary, 'first response was junk, the retry must produce a usable summary');
        $this->assertStringContainsString('langkah verifikasi', $fresh->summary);
        $this->assertStringNotContainsString('We need to produce', $fresh->summary);
        Http::assertSentCount(2);
    }

    public function test_summary_stays_empty_when_provider_returns_only_junk(): void
    {
        ['widget' => $widget] = $this->makeStack();
        $session = $this->makeSession($widget, $this->signedId());
        ChatMessage::create(['session_id' => $session->id, 'role' => 'user', 'content' => 'halo']);

        Http::fake([
            'openrouter.ai/*' => Http::sequence()
                ->push(['choices' => [['message' => ['content' => 'User Safety: safe']]]], 200)
                ->push(['choices' => [['message' => [
                    'content' => 'Buatkan resume dari percakapan berikut: Customer: halo',
                ]]]], 200),
        ]);

        \App\Jobs\GenerateChatSummary::dispatchSync($session->id);

        $fresh = $session->fresh();
        $this->assertNull($fresh->summary, 'junk must never be persisted as a summary');
        $this->assertNull($fresh->summary_generated_at);
        Http::assertSentCount(2);
    }

    public function test_summary_validation_rejects_echo_and_meta_leaks(): void
    {
        $method = new \ReflectionMethod(\App\Jobs\GenerateChatSummary::class, 'isUsableSummary');
        $method->setAccessible(true);
        $job = new \App\Jobs\GenerateChatSummary(new \App\Models\ChatSession());

        $rejected = [
            'User Safety: safe',
            'We need to produce a short summary in Indonesian, natural, professional, max 3 sentences covering the topic.',
            'Buatkan resume dari percakapan berikut: Customer menanyakan harga paket lalu layanan customer service menjelaskan rinciannya kepada customer.',
            'Okay, let me tackle this request. The user wants me to create a resume of the customer conversation in Bahasa Indonesia covering topic, need, and outcome.',
            'Sure! Here is the summary: pelanggan bertanya lalu dijawab dengan lengkap oleh layanan customer service kami.',
        ];
        foreach ($rejected as $text) {
            $this->assertFalse($method->invoke($job, $text), "should reject: ".mb_substr($text, 0, 50));
        }

        $this->assertTrue(
            $method->invoke(
                $job,
                'Customer menanyakan cara pendaftaran. Layanan customer service menjelaskan langkah verifikasi identitas lewat email dan menawarkan bantuan lebih lanjut.'
            )
        );
    }
}
