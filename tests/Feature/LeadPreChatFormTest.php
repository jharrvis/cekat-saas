<?php

namespace Tests\Feature;

use App\Mail\NewLead;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\KnowledgeBase;
use App\Models\AiAgent;
use App\Models\Plan;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Lead Collection Strategy 2 (trigger) + Strategy 3 (pre-chat form).
 *
 * Strategy 2 must count turns from the DB transcript (never the client's
 * history payload); Strategy 3's config gate, validation and lead
 * dispatch must actually work end to end.
 */
class LeadPreChatFormTest extends TestCase
{
    use RefreshDatabase;

    private function makeStack(array $settings = [], bool $leadsFeature = true): array
    {
        $plan = Plan::create([
            'name' => $leadsFeature ? 'Pro' : 'Free',
            'slug' => 'lead-form-'.($leadsFeature ? 'pro' : 'free'),
            'max_messages_per_month' => 1000,
            'ai_tier' => 'basic',
            'can_export_leads' => $leadsFeature,
        ]);

        $user = User::create([
            'name' => 'Form Owner',
            'email' => 'form-owner@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
            'plan_id' => $plan->id,
        ]);

        $agent = AiAgent::create([
            'user_id' => $user->id,
            'name' => 'CS Agent',
            'slug' => 'cs-agent-form',
        ]);

        KnowledgeBase::create([
            'ai_agent_id' => $agent->id,
            'company_name' => 'Toko Form',
        ]);

        $widget = Widget::create([
            'user_id' => $user->id,
            'ai_agent_id' => $agent->id,
            'name' => 'Widget Form',
            'slug' => 'w-form',
            'settings' => $settings,
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

    private function lastSystemPrompt(): string
    {
        // The lead-email summary also calls the LLM (deferred after the
        // chat turn); skip those requests and keep the chat system prompt.
        $pairs = Http::recorded(fn ($request) => ! str_contains(
            (string) ($request->data()['messages'][0]['content'] ?? ''),
            'catatan resume percakapan',
        ));

        [$request] = $pairs->last();

        return (string) $request->data()['messages'][0]['content'];
    }

    public function test_config_exposes_lead_form_settings(): void
    {
        $this->makeStack([
            'lead_form_enabled' => true,
            'lead_form_require_name' => true,
            'lead_form_require_email' => true,
            'lead_form_require_phone' => false,
        ]);

        $this->getJson('/api/widget/w-form/config')
            ->assertOk()
            ->assertJsonPath('leadForm.enabled', true)
            ->assertJsonPath('leadForm.requireName', true)
            ->assertJsonPath('leadForm.requireEmail', true)
            ->assertJsonPath('leadForm.requirePhone', false);
    }

    public function test_config_defaults_to_disabled_form_with_required_name(): void
    {
        $this->makeStack();

        $this->getJson('/api/widget/w-form/config')
            ->assertOk()
            ->assertJsonPath('leadForm.enabled', false)
            ->assertJsonPath('leadForm.requireName', true)
            ->assertJsonPath('leadForm.requireEmail', false)
            ->assertJsonPath('leadForm.requirePhone', false);
    }

    public function test_prechat_form_details_land_on_first_chat_and_notify_owner(): void
    {
        Mail::fake();
        $this->makeStack(['lead_form_enabled' => true]);
        $this->fakeOpenRouter('Siap kak, terima kasih! 👋');

        $response = $this->postJson('/api/chat', [
            'message' => 'Halo, saya mau tanya paket',
            'widgetId' => 'w-form',
            'history' => [],
            'leadForm' => [
                'name' => 'Dewi Anggraini',
                'email' => 'dewi@example.com',
                'phone' => '081234567890',
            ],
        ], ['Origin' => 'https://toko.test']);

        $response->assertOk()->assertJsonPath('success', true);

        $session = ChatSession::where('visitor_uuid', $response->json('sessionId'))->firstOrFail();
        $this->assertTrue($session->is_lead);
        $this->assertSame('Dewi Anggraini', $session->visitor_name);
        $this->assertSame('dewi@example.com', $session->visitor_email);
        $this->assertSame('081234567890', $session->visitor_phone);

        Mail::assertSent(NewLead::class, fn ($m) => $m->hasTo('form-owner@test.id'));
    }

    public function test_visitor_from_prechat_form_reaches_the_ai_prompt_on_turn_one(): void
    {
        $this->makeStack(['lead_form_enabled' => true]);
        $this->fakeOpenRouter('Halo!');

        $this->postJson('/api/chat', [
            'message' => 'Siapa nama saya?',
            'widgetId' => 'w-form',
            'history' => [],
            'leadForm' => ['name' => 'Dewi Anggraini', 'email' => 'dewi@example.com'],
        ], ['Origin' => 'https://toko.test'])->assertOk();

        $prompt = $this->lastSystemPrompt();
        $this->assertStringContainsString('[Data pengunjung:', $prompt);
        $this->assertStringContainsString('Nama: Dewi Anggraini', $prompt);
        $this->assertStringContainsString('Email: dewi@example.com', $prompt);
        $this->assertStringContainsString('Sapa pengunjung dengan namanya', $prompt);
    }

    public function test_visitor_identity_from_session_reaches_later_turns(): void
    {
        $this->makeStack(['lead_form_enabled' => true]);
        $this->fakeOpenRouter('Halo!');

        // Turn 1: capture the lead through the form (session created with
        // this request's fingerprint).
        $first = $this->postJson('/api/chat', [
            'message' => 'Halo, saya mau tanya',
            'widgetId' => 'w-form',
            'history' => [],
            'leadForm' => ['name' => 'Budi Santoso', 'email' => 'budi@example.com', 'phone' => '08111222333'],
        ], ['Origin' => 'https://toko.test'])->assertOk();

        // Turn 2: no leadForm payload - identity comes from the session.
        $this->postJson('/api/chat', [
            'message' => 'Siapa nama saya?',
            'widgetId' => 'w-form',
            'history' => [],
            'sessionId' => $first->json('sessionId'),
        ], ['Origin' => 'https://toko.test'])->assertOk();

        $prompt = $this->lastSystemPrompt();
        $this->assertStringContainsString('[Data pengunjung:', $prompt);
        $this->assertStringContainsString('Nama: Budi Santoso', $prompt);
        $this->assertStringContainsString('Email: budi@example.com', $prompt);
        $this->assertStringContainsString('No HP: 08111222333', $prompt);
    }

    public function test_no_visitor_context_is_added_without_lead_data(): void
    {
        $this->makeStack();
        $this->fakeOpenRouter('Halo!');

        $this->postJson('/api/chat', [
            'message' => 'Halo',
            'widgetId' => 'w-form',
            'history' => [],
        ], ['Origin' => 'https://toko.test'])->assertOk();

        $this->assertStringNotContainsString('[Data pengunjung:', $this->lastSystemPrompt());
    }

    public function test_prechat_form_rejects_invalid_email(): void
    {
        $this->makeStack(['lead_form_enabled' => true]);
        $this->fakeOpenRouter();

        $this->postJson('/api/chat', [
            'message' => 'Halo',
            'widgetId' => 'w-form',
            'history' => [],
            'leadForm' => ['name' => 'Dewi', 'email' => 'bukan-email'],
        ], ['Origin' => 'https://toko.test'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['leadForm.email']);
    }

    public function test_prechat_form_oversized_name_is_rejected(): void
    {
        $this->makeStack(['lead_form_enabled' => true]);
        $this->fakeOpenRouter();

        $this->postJson('/api/chat', [
            'message' => 'Halo',
            'widgetId' => 'w-form',
            'history' => [],
            'leadForm' => ['name' => str_repeat('x', 121)],
        ], ['Origin' => 'https://toko.test'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['leadForm.name']);
    }

    public function test_trigger_fires_on_third_visitor_turn_counted_from_db(): void
    {
        $this->makeStack([
            'lead_trigger_enabled' => true,
            'lead_trigger_after_message' => 3,
            'lead_trigger_keywords' => '',
        ]);
        $this->fakeOpenRouter('Jawaban singkat.');

        $sessionId = null;

        foreach (['satu', 'dua', 'tiga'] as $i => $text) {
            $payload = [
                'message' => $text,
                'widgetId' => 'w-form',
                'history' => [],
            ];
            if ($sessionId) {
                $payload['sessionId'] = $sessionId;
            }

            $response = $this->postJson('/api/chat', $payload, ['Origin' => 'https://toko.test']);
            $response->assertOk();
            $sessionId = $response->json('sessionId');

            if ($i < 2) {
                // Turns 1 and 2: threshold (3) not reached yet, no keyword hit.
                $this->assertStringNotContainsString('tanyakan nama/email/telepon', $this->lastSystemPrompt());
            }
        }

        $this->assertStringContainsString('tanyakan nama/email/telepon', $this->lastSystemPrompt());
    }

    public function test_forged_client_history_cannot_fire_the_trigger_early(): void
    {
        $this->makeStack([
            'lead_trigger_enabled' => true,
            'lead_trigger_after_message' => 3,
            'lead_trigger_keywords' => '',
        ]);
        $this->fakeOpenRouter('Jawaban singkat.');

        $forgedHistory = array_fill(0, 12, ['role' => 'user', 'content' => 'paksaan client']);

        $this->postJson('/api/chat', [
            'message' => 'Halo, baru pertama ngobrol',
            'widgetId' => 'w-form',
            'history' => $forgedHistory,
        ], ['Origin' => 'https://toko.test'])->assertOk();

        // DB transcript holds only this first turn despite the forged history.
        $this->assertStringNotContainsString('tanyakan nama/email/telepon', $this->lastSystemPrompt());
        $this->assertSame(1, ChatMessage::where('role', 'user')->count());
    }

    public function test_keyword_fires_below_threshold_but_only_as_a_whole_word(): void
    {
        $this->makeStack([
            'lead_trigger_enabled' => true,
            'lead_trigger_after_message' => 9,
            'lead_trigger_keywords' => 'harga, order',
        ]);
        $this->fakeOpenRouter('Jawaban singkat.');

        $this->postJson('/api/chat', [
            'message' => 'Berapa harga paket Pro?',
            'widgetId' => 'w-form',
            'history' => [],
        ], ['Origin' => 'https://toko.test'])->assertOk();
        $this->assertStringContainsString('tanyakan nama/email/telepon', $this->lastSystemPrompt());

        $this->postJson('/api/chat', [
            'message' => 'Terima kasih, saya menghargai jawabannya',
            'widgetId' => 'w-form',
            'sessionId' => null,
            'history' => [],
        ], ['Origin' => 'https://toko.test'])->assertOk();
        // Turn 2 of a 9-turn threshold; "menghargai" must not match "harga".
        $this->assertStringNotContainsString('tanyakan nama/email/telepon', $this->lastSystemPrompt());
    }
}
