<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Plan;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Chat PII is encrypted at rest (new rows) while legacy plaintext rows
 * stay readable - only new data is encrypted; old rows age out via
 * chat:purge. Accessors must also apply on Eloquent pluck() because
 * TopicAnalyzerService feeds provider analysis via pluck('content').
 */
class ChatCipherTest extends TestCase
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

        $user = User::create([
            'name' => 'Owner',
            'email' => 'owner-cipher@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
            'plan_id' => $plan->id,
        ]);

        $widget = Widget::create([
            'user_id' => $user->id,
            'name' => 'Widget Cipher',
            'slug' => 'w-cipher',
            'status' => 'active',
        ]);

        return compact('plan', 'user', 'widget');
    }

    private function makeSession(Widget $widget): ChatSession
    {
        return ChatSession::create([
            'widget_id' => $widget->id,
            'visitor_uuid' => 'sess_cipher_1',
            'started_at' => now(),
        ]);
    }

    public function test_message_content_is_encrypted_at_rest(): void
    {
        ['widget' => $widget] = $this->makeStack();
        $session = $this->makeSession($widget);

        $message = ChatMessage::create([
            'session_id' => $session->id,
            'role' => 'user',
            'content' => 'Halo, saya Budi 081234567890',
        ]);

        $raw = DB::table('chat_messages')->where('id', $message->id)->value('content');
        $this->assertStringNotContainsString('Halo, saya Budi', (string) $raw);
        $this->assertStringNotContainsString('081234567890', (string) $raw);

        $this->assertSame('Halo, saya Budi 081234567890', $message->fresh()->content);
    }

    public function test_legacy_plaintext_message_still_readable(): void
    {
        ['widget' => $widget] = $this->makeStack();
        $session = $this->makeSession($widget);

        DB::table('chat_messages')->insert([
            'session_id' => $session->id,
            'role' => 'user',
            'content' => 'pesan lama sebelum enkripsi',
            'created_at' => now()->subDays(30),
            'updated_at' => now()->subDays(30),
        ]);

        $message = ChatMessage::where('session_id', $session->id)->first();
        $this->assertSame('pesan lama sebelum enkripsi', $message->content);
    }

    public function test_session_visitor_fields_and_summary_encrypted_at_rest(): void
    {
        ['widget' => $widget] = $this->makeStack();

        $session = ChatSession::create([
            'widget_id' => $widget->id,
            'visitor_uuid' => 'sess_cipher_2',
            'visitor_name' => 'Budi Santoso',
            'visitor_email' => 'budi@contoh.id',
            'visitor_phone' => '081234567890',
            'summary' => 'Budi tanya harga paket Pro',
            'started_at' => now(),
        ]);

        $raw = DB::table('chat_sessions')->where('id', $session->id)->first();
        $this->assertStringNotContainsString('Budi Santoso', (string) $raw->visitor_name);
        $this->assertStringNotContainsString('budi@contoh.id', (string) $raw->visitor_email);
        $this->assertStringNotContainsString('081234567890', (string) $raw->visitor_phone);
        $this->assertStringNotContainsString('harga paket Pro', (string) $raw->summary);

        $fresh = $session->fresh();
        $this->assertSame('Budi Santoso', $fresh->visitor_name);
        $this->assertSame('budi@contoh.id', $fresh->visitor_email);
        $this->assertSame('081234567890', $fresh->visitor_phone);
        $this->assertSame('Budi tanya harga paket Pro', $fresh->summary);
    }

    public function test_eloquent_pluck_applies_the_decrypting_accessor(): void
    {
        ['widget' => $widget] = $this->makeStack();
        $session = $this->makeSession($widget);

        ChatMessage::create(['session_id' => $session->id, 'role' => 'user', 'content' => 'satu dua tiga']);
        ChatMessage::create(['session_id' => $session->id, 'role' => 'assistant', 'content' => 'empat lima enam']);

        $plucked = ChatMessage::where('session_id', $session->id)->pluck('content');

        $this->assertTrue($plucked->contains('satu dua tiga'));
        $this->assertTrue($plucked->contains('empat lima enam'));
        $this->assertFalse($plucked->contains(fn ($c) => str_starts_with((string) $c, 'eyJpdiI')));
    }
}
