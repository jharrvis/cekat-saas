<?php

namespace Tests\Feature;

use App\Events\LeadCaptured;
use App\Mail\NewLead;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Plan;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Lead capture: the session is marked as a lead with the visitor's contact
 * data (encrypted at rest) and the owner is emailed once per session,
 * gated behind the Leads plan feature.
 */
class LeadNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeStack(bool $leadsFeature): array
    {
        $plan = Plan::create([
            'name' => $leadsFeature ? 'Pro' : 'Free',
            'slug' => $leadsFeature ? 'pro-lead' : 'free-lead',
            'max_messages_per_month' => 1000,
            'can_export_leads' => $leadsFeature,
        ]);

        $owner = User::create([
            'name' => 'Lead Owner',
            'email' => 'owner-lead@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
            'plan_id' => $plan->id,
        ]);

        $widget = Widget::create([
            'user_id' => $owner->id,
            'name' => 'Widget Lead',
            'slug' => 'w-lead-' . uniqid(),
            'status' => 'active',
            'is_active' => true,
        ]);

        $session = ChatSession::create([
            'widget_id' => $widget->id,
            'visitor_uuid' => 'sess-lead-' . uniqid(),
            'started_at' => now(),
        ]);

        return [$owner, $widget, $session];
    }

    public function test_lead_is_persisted_and_owner_is_notified(): void
    {
        Mail::fake();

        [$owner, $widget, $session] = $this->makeStack(true);

        event(new LeadCaptured(
            $widget->slug,
            ['name', 'email'],
            $session->visitor_uuid,
            ['name' => 'Budi Santoso', 'email' => 'budi@example.com'],
        ));

        $fresh = $session->fresh();
        $this->assertTrue($fresh->is_lead);
        $this->assertSame('Budi Santoso', $fresh->visitor_name);
        $this->assertSame('budi@example.com', $fresh->visitor_email);

        Mail::assertSent(NewLead::class, fn ($m) => $m->hasTo($owner->email));
    }

    public function test_free_plan_owner_gets_no_lead_email_but_lead_is_kept(): void
    {
        Mail::fake();

        [$owner, $widget, $session] = $this->makeStack(false);

        event(new LeadCaptured(
            $widget->slug,
            ['name'],
            $session->visitor_uuid,
            ['name' => 'Freebie'],
        ));

        $fresh = $session->fresh();
        $this->assertTrue($fresh->is_lead);
        $this->assertSame('Freebie', $fresh->visitor_name);

        Mail::assertNotSent(NewLead::class);
    }

    public function test_owner_is_notified_once_per_session(): void
    {
        Mail::fake();

        [$owner, $widget, $session] = $this->makeStack(true);

        $payload = fn () => event(new LeadCaptured(
            $widget->slug,
            ['name', 'email'],
            $session->visitor_uuid,
            ['name' => 'Budi', 'email' => 'budi@example.com'],
        ));

        $payload();
        $payload();

        Mail::assertSent(NewLead::class, 1);
    }

    public function test_lead_email_works_even_without_webhook_configured(): void
    {
        Mail::fake();

        [$owner, $widget, $session] = $this->makeStack(true);

        $this->assertEmpty($widget->settings['webhook_url'] ?? null);

        event(new LeadCaptured(
            $widget->slug,
            ['name'],
            $session->visitor_uuid,
            ['name' => 'No Webhook'],
        ));

        Mail::assertSent(NewLead::class, fn ($m) => $m->hasTo($owner->email));
    }

    public function test_lead_email_contains_summary_generated_before_send(): void
    {
        Mail::fake();
        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [['message' => [
                    'content' => 'Customer bertanya tentang paket Pro dan meminta penjelasan fitur '
                        .'sebelum memutuskan pembelian. Tim kami menawarkan demo gratis minggu depan.',
                ]]],
            ], 200),
        ]);

        [$owner, $widget, $session] = $this->makeStack(true);

        ChatMessage::create([
            'session_id' => $session->id,
            'role' => 'user',
            'content' => 'Halo, saya tertarik dengan paket Pro. Berapa harganya?',
        ]);
        ChatMessage::create([
            'session_id' => $session->id,
            'role' => 'assistant',
            'content' => 'Paket Pro tersedia dan menawarkan fitur lengkap. Mau saya jelaskan?',
        ]);

        event(new LeadCaptured(
            $widget->slug,
            ['name', 'email'],
            $session->visitor_uuid,
            ['name' => 'Budi', 'email' => 'budi@example.com'],
        ));

        // The summary runs deferred (after the chat response); flush it.
        $this->app->terminate();

        Mail::assertSent(NewLead::class, 1);

        $fresh = $session->fresh();
        $this->assertNotNull($fresh->summary);

        Mail::assertSent(NewLead::class, function (NewLead $m) use ($fresh) {
            $html = $m->render();

            return str_contains($html, 'Ringkasan Percakapan')
                && str_contains($html, $fresh->summary)
                && str_contains($html, 'Budi');
        });
    }

    public function test_lead_email_falls_back_to_excerpt_when_summary_unavailable(): void
    {
        Mail::fake();
        Http::fake(); // summary LLM call fails validation -> excerpt path

        [$owner, $widget, $session] = $this->makeStack(true);

        ChatMessage::create([
            'session_id' => $session->id,
            'role' => 'user',
            'content' => 'Saya mau tanya soal billing langganan saya.',
        ]);
        ChatMessage::create([
            'session_id' => $session->id,
            'role' => 'assistant',
            'content' => 'Baik, silakan sebutkan nomor invoice Anda.',
        ]);

        event(new LeadCaptured(
            $widget->slug,
            ['name', 'email'],
            $session->visitor_uuid,
            ['name' => 'Siti', 'email' => 'siti@example.com'],
        ));

        $this->app->terminate();

        Mail::assertSent(NewLead::class, 1);
        $this->assertNull($session->fresh()->summary);

        Mail::assertSent(NewLead::class, function (NewLead $m) {
            $html = $m->render();

            return str_contains($html, 'Potongan Percakapan')
                && str_contains($html, 'billing langganan');
        });
    }
}

