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

    public function test_dispatch_returns_null_without_webhook_url(): void
    {
        $service = new WebhookActionService(new WebhookService);
        $widget = new Widget(['slug' => 'w-test', 'user_id' => 1, 'settings' => []]);

        $this->assertNull($service->dispatchIfAction($widget, '{"action": "save_lead"}'));
    }
}
