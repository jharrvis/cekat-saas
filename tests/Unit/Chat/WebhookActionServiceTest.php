<?php

namespace Tests\Unit\Chat;

use App\Models\Widget;
use App\Services\Chat\WebhookActionService;
use App\Services\WebhookService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebhookActionServiceTest extends TestCase
{
    public function test_extract_action_returns_null_without_json(): void
    {
        $service = new WebhookActionService(new WebhookService);

        $this->assertNull($service->extractAction('Halo kak, ada yang bisa dibantu?'));
    }

    public function test_extract_action_parses_action_block(): void
    {
        $service = new WebhookActionService(new WebhookService);

        $data = $service->extractAction('Terima kasih kak {"action": "save_lead", "name": "Budi"}');
        $this->assertSame('save_lead', $data['action'] ?? null);
        $this->assertSame('Budi', $data['name'] ?? null);
    }

    public function test_strip_action_json_removes_block(): void
    {
        $service = new WebhookActionService(new WebhookService);

        $cleaned = $service->stripActionJson('Terima kasih kak {"action": "save_lead"} sama-sama');
        $this->assertStringNotContainsString('save_lead', $cleaned);
        $this->assertStringContainsString('Terima kasih kak', $cleaned);
    }

    public function test_extract_action_parses_tool_call_block(): void
    {
        $service = new WebhookActionService(new WebhookService);

        // Format mentah yang dikeluarkan Gemini saat mengabaikan instruksi JSON
        // (insiden widget PA Salatiga 2026-10-15).
        $text = "<tool_call_start>\n[api_lookup(action='api_lookup', lookup='jadwal_sidang_perkara', params={'nomor': '51/Pdt.P/2026/PA.Sal'})]\n<tool_call_end>";

        $data = $service->extractAction($text);

        $this->assertSame('api_lookup', $data['action'] ?? null);
        $this->assertSame('jadwal_sidang_perkara', $data['lookup'] ?? null);
        $this->assertSame('51/Pdt.P/2026/PA.Sal', $data['params']['nomor'] ?? null);
    }

    public function test_extract_action_parses_tool_call_mixed_reply(): void
    {
        $service = new WebhookActionService(new WebhookService);

        $text = "Sebentar ya kak, saya cek dulu.\n<tool_call_start>[save_lead(action='save_lead', name='Budi', phone='08123456789')]<tool_call_end>";

        $data = $service->extractAction($text);

        $this->assertSame('save_lead', $data['action'] ?? null);
        $this->assertSame('Budi', $data['name'] ?? null);
        $this->assertSame('08123456789', $data['phone'] ?? null);
        $this->assertFalse($service->isStrictJson($text));

        $cleaned = $service->stripActionJson($text);
        $this->assertStringContainsString('Sebentar ya kak', $cleaned);
        $this->assertStringNotContainsString('tool_call_start', $cleaned);
    }

    public function test_extract_action_parses_tool_call_nested_items(): void
    {
        $service = new WebhookActionService(new WebhookService);

        $text = "<tool_call_start>[create_order(action='create_order', items=[{'product': 'Kopi Susu', 'qty': 2}], name='Ani', phone='08111')]<tool_call_end>";

        $data = $service->extractAction($text);

        $this->assertSame('create_order', $data['action'] ?? null);
        $this->assertSame('Kopi Susu', $data['items'][0]['product'] ?? null);
        $this->assertSame(2, $data['items'][0]['qty'] ?? null);
    }

    public function test_is_strict_json_true_for_pure_tool_call_block(): void
    {
        $service = new WebhookActionService(new WebhookService);

        $this->assertTrue($service->isStrictJson(
            "<tool_call_start>[api_lookup(action='api_lookup', lookup='x', params={})]<tool_call_end>"
        ));
    }

    public function test_extract_action_returns_null_for_malformed_tool_call(): void
    {
        $service = new WebhookActionService(new WebhookService);

        $this->assertNull($service->extractAction('<tool_call_start>[api_lookup(broken]<tool_call_end>'));
    }

    public function test_dispatch_sends_webhook_and_returns_friendly_message(): void
    {
        Http::fake(['https://hook.test/*' => Http::response(['ok' => true], 200)]);

        $service = new WebhookActionService(new WebhookService);
        $widget = new Widget([
            'slug' => 'w-test',
            'user_id' => 1,
            'settings' => ['webhook_url' => 'https://hook.test/in', 'webhook_secret' => 's3cr3t'],
        ]);

        $result = $service->dispatchIfAction($widget, '{"action": "save_lead", "name": "Budi"}');

        $this->assertSame('Data berhasil diproses.', $result);
        Http::assertSent(fn ($request) => $request->url() === 'https://hook.test/in'
            && $request->hasHeader('X-Cekat-Signature'));
    }

    public function test_extract_action_parses_pipe_variant_tool_call(): void
    {
        $service = new WebhookActionService(new WebhookService);

        $txt = "<|tool_call_start|>\n[api_lookup(lookup=\"jadwal_sidang_perkara\", params=(\"nomor\": \"229/Pdt.G/2026/PA.Sal\"))]\n<|tool_call_end|>";
        $data = $service->extractAction($txt);

        $this->assertSame('api_lookup', $data['action'] ?? null);
        $this->assertSame('jadwal_sidang_perkara', $data['lookup'] ?? null);
        $this->assertSame('229/Pdt.G/2026/PA.Sal', $data['params']['nomor'] ?? null);
    }

    public function test_strip_never_leaks_pipe_variant_to_visitor(): void
    {
        $service = new WebhookActionService(new WebhookService);

        $txt = "<|tool_call_start|>[api_lookup(lookup=\"x\", params=(\"a\": \"b\"))]<|tool_call_end|>";
        $cleaned = $service->stripActionJson('Halo! '.$txt.' terima kasih');

        $this->assertStringNotContainsString('api_lookup', $cleaned);
        $this->assertStringNotContainsString('tool_call', $cleaned);
        $this->assertStringContainsString('Halo!', $cleaned);
    }

    public function test_extract_action_parses_bare_tool_call_without_tags(): void
    {
        $service = new WebhookActionService(new WebhookService);

        $data = $service->extractAction('[api_lookup(lookup="jadwal_sidang", params={"tanggal": "20/10/2026"})]');

        $this->assertSame('api_lookup', $data['action'] ?? null);
        $this->assertSame('20/10/2026', $data['params']['tanggal'] ?? null);

        $cleaned = $service->stripActionJson('Halo [api_lookup(lookup="x")] ya');
        $this->assertStringNotContainsString('api_lookup', $cleaned);
    }
    public function test_dispatch_returns_null_without_webhook_url(): void
    {
        $service = new WebhookActionService(new WebhookService);
        $widget = new Widget(['slug' => 'w-test', 'user_id' => 1, 'settings' => []]);

        $this->assertNull($service->dispatchIfAction($widget, '{"action": "save_lead"}'));
    }
}
