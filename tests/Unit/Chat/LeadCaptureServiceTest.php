<?php

namespace Tests\Unit\Chat;

use App\Services\Chat\LeadCaptureService;
use PHPUnit\Framework\TestCase;

class LeadCaptureServiceTest extends TestCase
{
    private LeadCaptureService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LeadCaptureService;
    }

    public function test_disabled_trigger_returns_null(): void
    {
        $this->assertNull($this->service->triggerInstruction([], 'halo', 1));
        $this->assertNull($this->service->triggerInstruction(['lead_trigger_enabled' => false], 'halo', 99));
    }

    public function test_message_count_trigger_uses_server_turn(): void
    {
        $settings = ['lead_trigger_enabled' => true, 'lead_trigger_after_message' => 3];

        $this->assertNull($this->service->triggerInstruction($settings, 'halo', 1));
        $this->assertNull($this->service->triggerInstruction($settings, 'halo lagi', 2));
        $instruction = $this->service->triggerInstruction($settings, 'halo lagi', 3);
        $this->assertNotNull($instruction);
        $this->assertStringContainsString('nama/email/telepon', $instruction);

        // Below threshold remains silent even with a long (forged) history
        // on the wire - the count comes from the DB transcript only.
        $this->assertNull($this->service->triggerInstruction($settings, 'paket apa saja?', 2));
    }

    public function test_keyword_trigger_is_case_insensitive_and_word_bounded(): void
    {
        $settings = [
            'lead_trigger_enabled' => true,
            'lead_trigger_after_message' => 99,
            'lead_trigger_keywords' => 'harga, order',
        ];

        $this->assertNotNull($this->service->triggerInstruction($settings, 'Berapa HARGA nya kak?', 1));
        $this->assertNotNull($this->service->triggerInstruction($settings, 'saya mau order paket Pro', 1));
        $this->assertNull($this->service->triggerInstruction($settings, 'halo kak', 1));
        // "harga" must not fire inside an unrelated word
        $this->assertNull($this->service->triggerInstruction($settings, 'saya menghargai penjelasannya', 1));
        // below the threshold stays silent without a keyword hit
        $this->assertNull($this->service->triggerInstruction($settings, 'gimana kabarnya?', 1));
    }

    public function test_threshold_below_one_is_clamped(): void
    {
        $settings = ['lead_trigger_enabled' => true, 'lead_trigger_after_message' => 0];

        $this->assertNotNull($this->service->triggerInstruction($settings, 'halo', 1));
    }

    public function test_extract_lead_from_message_full_contact(): void
    {
        $lead = $this->service->extractLeadFromMessage(
            'Nama saya Budi Gunawan, email bdgwn@yahoo.co.id, telepon 0812345464458'
        );

        $this->assertNotNull($lead);
        $this->assertSame('save_lead', $lead['action']);
        $this->assertSame('Budi Gunawan', $lead['name']);
        $this->assertSame('bdgwn@yahoo.co.id', $lead['email']);
        $this->assertSame('0812345464458', $lead['phone']);
    }

    public function test_extract_lead_stops_name_at_contact_keyword(): void
    {
        $lead = $this->service->extractLeadFromMessage(
            'Nama saya Ahmad Fauzi, email ahmad@test.id ya kak'
        );

        $this->assertSame('Ahmad Fauzi', $lead['name'] ?? null);
        $this->assertSame('ahmad@test.id', $lead['email'] ?? null);
    }

    public function test_extract_lead_accepts_email_or_phone_only(): void
    {
        $emailOnly = $this->service->extractLeadFromMessage('kirim ke budi@gmail.com ya');
        $this->assertNull($emailOnly['name'] ?? null);
        $this->assertSame('budi@gmail.com', $emailOnly['email']);

        $phoneOnly = $this->service->extractLeadFromMessage('chat wa +6281234567890');
        $this->assertSame('6281234567890', $phoneOnly['phone']);
        $this->assertArrayNotHasKey('name', $phoneOnly);
    }

    public function test_extract_lead_requires_email_or_phone(): void
    {
        $this->assertNull($this->service->extractLeadFromMessage('nama saya Budi, saya mau tanya harga'));
        $this->assertNull($this->service->extractLeadFromMessage('halo kak berapa harganya?'));
    }
}
