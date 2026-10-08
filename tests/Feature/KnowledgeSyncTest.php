<?php

namespace Tests\Feature;

use App\Models\AiAgent;
use App\Models\ApiKey;
use App\Models\KnowledgeBase;
use App\Models\KnowledgeDocument;
use App\Models\Plan;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Knowledge sync API (/api/v1/knowledge/documents): integrations such as
 * the WordPress/WooCommerce plugin upsert one document per external item
 * into the knowledge base of a channel's agent, API-key scoped to the
 * key owner, plan document limit enforced for new documents only.
 */
class KnowledgeSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private string $secret;
    private Widget $widget;
    private KnowledgeBase $kb;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = $this->makeOwner('sync-owner@test.id', 'pro-sync', null);
        [, $this->secret] = ApiKey::generate($this->owner, 'Sync Key');

        $agent = AiAgent::create([
            'user_id' => $this->owner->id,
            'name' => 'Sync Agent',
            'slug' => 'sync-agent-' . uniqid(),
        ]);

        $this->widget = Widget::create([
            'user_id' => $this->owner->id,
            'name' => 'Sync Widget',
            'slug' => 'w-sync-' . uniqid(),
            'status' => 'active',
            'is_active' => true,
            'ai_agent_id' => $agent->id,
        ]);

        $this->kb = KnowledgeBase::create([
            'ai_agent_id' => $agent->id,
            'company_name' => 'Toko Sync',
        ]);
    }

    private function makeOwner(string $email, string $slug, ?int $maxDocuments): User
    {
        $plan = Plan::create([
            'name' => 'Pro ' . $slug,
            'slug' => $slug,
            'max_messages_per_month' => 1000,
            'max_documents' => $maxDocuments ?? 1000,
            'features' => ['api_access' => true],
        ]);

        return User::create([
            'name' => 'Sync Owner',
            'email' => $email,
            'password' => 'secret123',
            'email_verified_at' => now(),
            'plan_id' => $plan->id,
        ]);
    }

    private function auth(): array
    {
        return ['Authorization' => 'Bearer ' . $this->secret];
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'widget_slug' => $this->widget->slug,
            'source' => 'woocommerce',
            'external_ref' => 'wc-product-42',
            'name' => 'Kopi Arabika 200g',
            'content' => "Produk: Kopi Arabika 200g\nHarga: Rp85.000\nStok: Tersedia (12)\nDeskripsi: Biji kopi arabika sangrai sedang.",
            'url' => 'https://toko.test/produk/kopi-arabika',
        ], $overrides);
    }

    public function test_upsert_creates_a_completed_chunked_document(): void
    {
        $response = $this->postJson('/api/v1/knowledge/documents', $this->payload(), $this->auth());

        $response->assertCreated()
            ->assertJsonPath('data.external_ref', 'wc-product-42')
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('meta.created', true);

        $doc = KnowledgeDocument::where('knowledge_base_id', $this->kb->id)->first();
        $this->assertNotNull($doc);
        $this->assertSame('woocommerce', $doc->source);
        $this->assertNotEmpty($doc->chunks);
        $this->assertStringContainsString('Kopi Arabika', $doc->content);
    }

    public function test_upsert_twice_updates_instead_of_duplicating(): void
    {
        $this->postJson('/api/v1/knowledge/documents', $this->payload(), $this->auth())->assertCreated();

        $response = $this->postJson('/api/v1/knowledge/documents', $this->payload([
            'content' => "Produk: Kopi Arabika 200g\nHarga: Rp79.000\nStok: Habis",
        ]), $this->auth());

        $response->assertOk()->assertJsonPath('meta.created', false);
        $this->assertSame(1, KnowledgeDocument::where('knowledge_base_id', $this->kb->id)->count());
        $this->assertStringContainsString('Rp79.000', KnowledgeDocument::first()->content);
    }

    public function test_remove_deletes_and_is_idempotent(): void
    {
        $this->postJson('/api/v1/knowledge/documents', $this->payload(), $this->auth())->assertCreated();

        $body = [
            'widget_slug' => $this->widget->slug,
            'source' => 'woocommerce',
            'external_ref' => 'wc-product-42',
        ];

        $this->deleteJson('/api/v1/knowledge/documents', $body, $this->auth())
            ->assertOk()
            ->assertJsonPath('data.deleted', true);

        $this->assertSame(0, KnowledgeDocument::count());

        $this->deleteJson('/api/v1/knowledge/documents', $body, $this->auth())
            ->assertOk()
            ->assertJsonPath('data.deleted', false);
    }

    public function test_widget_of_another_user_is_not_found(): void
    {
        $other = $this->makeOwner('sync-other@test.id', 'pro-sync-other', null);
        [, $otherSecret] = ApiKey::generate($other, 'Other Key');

        $this->postJson('/api/v1/knowledge/documents', $this->payload(), ['Authorization' => 'Bearer ' . $otherSecret])
            ->assertNotFound();

        $this->assertSame(0, KnowledgeDocument::count());
    }

    public function test_missing_api_key_is_unauthorized(): void
    {
        $this->postJson('/api/v1/knowledge/documents', $this->payload())->assertUnauthorized();
    }

    public function test_plan_document_limit_blocks_new_documents_but_not_updates(): void
    {
        $limited = $this->makeOwner('sync-limited@test.id', 'pro-sync-limited', 1);
        [, $limitedSecret] = ApiKey::generate($limited, 'Limited Key');
        $headers = ['Authorization' => 'Bearer ' . $limitedSecret];

        $agent = AiAgent::create(['user_id' => $limited->id, 'name' => 'Limited Agent', 'slug' => 'limited-' . uniqid()]);
        $widget = Widget::create([
            'user_id' => $limited->id, 'name' => 'Limited Widget', 'slug' => 'w-limited-' . uniqid(),
            'status' => 'active', 'is_active' => true, 'ai_agent_id' => $agent->id,
        ]);
        KnowledgeBase::create(['ai_agent_id' => $agent->id, 'company_name' => 'Toko Limited']);

        $base = $this->payload(['widget_slug' => $widget->slug]);

        $this->postJson('/api/v1/knowledge/documents', $base, $headers)->assertCreated();

        // Second NEW document exceeds max_documents = 1.
        $this->postJson('/api/v1/knowledge/documents', array_merge($base, [
            'external_ref' => 'wc-product-43', 'name' => 'Teh Melati',
        ]), $headers)->assertForbidden();

        // Updating the existing one still works at the limit.
        $this->postJson('/api/v1/knowledge/documents', array_merge($base, [
            'content' => 'Produk: Kopi Arabika 200g - diperbarui.',
        ]), $headers)->assertOk();
    }

    public function test_widget_without_knowledge_base_returns_422(): void
    {
        $bare = Widget::create([
            'user_id' => $this->owner->id, 'name' => 'Bare Widget', 'slug' => 'w-bare-' . uniqid(),
            'status' => 'active', 'is_active' => true,
        ]);

        $this->postJson('/api/v1/knowledge/documents', $this->payload(['widget_slug' => $bare->slug]), $this->auth())
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'no_knowledge_base');
    }
}
