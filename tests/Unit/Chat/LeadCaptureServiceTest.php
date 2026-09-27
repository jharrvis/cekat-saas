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
        $this->assertNull($this->service->triggerInstruction([], array_fill(0, 10, []), 'halo'));
    }

    public function test_message_count_trigger(): void
    {
        $settings = ['lead_trigger_enabled' => true, 'lead_trigger_after_message' => 3];

        $this->assertNull($this->service->triggerInstruction($settings, [['role' => 'user']], 'halo'));
        $instruction = $this->service->triggerInstruction(
            $settings,
            [['role' => 'user'], ['role' => 'assistant']],
            'halo lagi'
        );
        $this->assertNotNull($instruction);
        $this->assertStringContainsString('nama/email/telepon', $instruction);
    }

    public function test_keyword_trigger_is_case_insensitive(): void
    {
        $settings = [
            'lead_trigger_enabled' => true,
            'lead_trigger_after_message' => 99,
            'lead_trigger_keywords' => 'harga, order',
        ];

        $this->assertNotNull($this->service->triggerInstruction($settings, [], 'Berapa HARGA nya kak?'));
        $this->assertNull($this->service->triggerInstruction($settings, [], 'halo kak'));
    }
}
