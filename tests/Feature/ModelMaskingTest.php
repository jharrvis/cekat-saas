<?php

namespace Tests\Feature;

use App\Models\AiAgent;
use App\Models\KnowledgeBase;
use App\Models\Plan;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Remediation T-21 (owner policy): Cekat never names an AI model or
 * provider to users — not on the landing, not in the dashboard, not in
 * the public chat API response. Model selection is an admin-only concern.
 */
class ModelMaskingTest extends TestCase
{
    use RefreshDatabase;

    private const FORBIDDEN = '/gpt|chatgpt|openai|openrouter|nemotron|llama|claude|gemini|mistral|qwen|deepseek/i';

    public function test_chat_api_response_contains_no_model_identity(): void
    {
        $plan = Plan::create(['name' => 'Starter', 'slug' => 'starter', 'max_messages_per_month' => 100, 'ai_tier' => 'basic']);
        $user = User::create([
            'name' => 'Owner', 'email' => 'mm-' . uniqid() . '@test.id', 'password' => 'secret123',
            'email_verified_at' => now(), 'plan_id' => $plan->id,
        ]);
        $agent = AiAgent::create(['user_id' => $user->id, 'name' => 'CS Agent', 'slug' => 'cs-agent']);
        KnowledgeBase::create(['ai_agent_id' => $agent->id, 'company_name' => 'Toko Uji']);
        Widget::create([
            'user_id' => $user->id, 'ai_agent_id' => $agent->id,
            'name' => 'Widget Uji', 'slug' => 'w-uji', 'settings' => [],
        ]);

        Http::fake([
            'openrouter.ai/*' => Http::response([
                'model' => 'nvidia/nemotron-nano-9b-v2:free',
                'choices' => [['message' => ['content' => 'Halo kak!']]],
                'usage' => ['total_tokens' => 42],
            ], 200),
        ]);

        $response = $this->postJson('/api/chat', [
            'message' => 'Halo',
            'widgetId' => 'w-uji',
        ], ['Origin' => 'https://toko.test']);

        $response->assertOk();
        $response->assertJsonMissingPath('meta');
        $response->assertJsonMissingPath('usage');
        $this->assertDoesNotMatchRegularExpression(self::FORBIDDEN, $response->getContent());
        $this->assertStringNotContainsString('nemotron', $response->getContent());
    }

    public function test_widget_config_endpoint_exposes_no_model(): void
    {
        $plan = Plan::create(['name' => 'Starter', 'slug' => 'starter', 'max_messages_per_month' => 100, 'ai_tier' => 'basic']);
        $user = User::create([
            'name' => 'Owner', 'email' => 'cfg-' . uniqid() . '@test.id', 'password' => 'secret123',
            'email_verified_at' => now(), 'plan_id' => $plan->id,
        ]);
        Widget::create([
            'user_id' => $user->id, 'name' => 'Widget Cfg', 'slug' => 'w-cfg',
            'is_active' => true, 'status' => 'active',
            'settings' => ['model' => 'nvidia/nemotron-nano-9b-v2:free'],
        ]);

        $response = $this->getJson('/api/widget/w-cfg/config', ['Origin' => 'https://toko.test']);

        $response->assertOk();
        $response->assertJsonMissingPath('model');
        $this->assertDoesNotMatchRegularExpression(self::FORBIDDEN, $response->getContent());
    }

    public function test_landing_has_no_model_claims(): void
    {
        $response = $this->get('/');
        $response->assertOk();
        $this->assertDoesNotMatchRegularExpression(self::FORBIDDEN, $response->getContent());
        $response->assertSee('Didukung Teknologi RAG & AI Generatif', false);
    }

    public function test_agent_pages_show_no_tier_card(): void
    {
        $plan = Plan::create(['name' => 'Pro', 'slug' => 'pro', 'price' => 99000, 'ai_tier' => 'advanced']);
        $user = User::create([
            'name' => 'Owner', 'email' => 'tier-' . uniqid() . '@test.id', 'password' => 'secret123',
            'email_verified_at' => now(), 'plan_id' => $plan->id,
        ]);
        $agent = AiAgent::create(['user_id' => $user->id, 'name' => 'Agen', 'slug' => 'agen-mm']);

        foreach ([route('agents.edit', $agent), route('agents.create')] as $url) {
            $html = $this->actingAs($user)->get($url)->getContent();
            $this->assertStringNotContainsString('AI Quality', $html);
            $this->assertStringNotContainsString('Model AI terbaik', $html);
            $this->assertDoesNotMatchRegularExpression(self::FORBIDDEN, $html);
        }
    }

    public function test_user_facing_surfaces_are_clean_of_forbidden_terms(): void
    {
        $violations = [];
        $scan = function (string $dir) use (&$violations) {
            if (! is_dir($dir)) {
                return;
            }
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
            foreach ($it as $file) {
                if ($file->isDir()) {
                    continue;
                }
                $path = $file->getPathname();
                // Admin surfaces are explicitly exempt (owner policy).
                if (str_contains($path, '/admin/') || str_contains($path, 'admin.')) {
                    continue;
                }
                if (preg_match(self::FORBIDDEN, (string) file_get_contents($path))) {
                    $violations[] = $path;
                }
            }
        };

        $scan(resource_path('views'));
        $scan(public_path('widget'));
        $scan(base_path('lang'));

        $this->assertSame([], $violations, 'Forbidden model/provider terms found in user-facing files: ' . implode(', ', $violations));
    }
}
