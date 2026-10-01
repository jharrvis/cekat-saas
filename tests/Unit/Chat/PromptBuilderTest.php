<?php

namespace Tests\Unit\Chat;

use App\Models\AiAgent;
use App\Models\KnowledgeBase;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeFaq;
use App\Models\Plan;
use App\Models\User;
use App\Models\Widget;
use App\Services\Chat\PromptBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromptBuilderTest extends TestCase
{
    use RefreshDatabase;

    private PromptBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new PromptBuilder;
    }

    private function makeWidget(): Widget
    {
        $user = User::factory()->create([
            'plan_id' => Plan::create([
                'name' => 'Prompt Plan',
                'slug' => 'prompt-plan-'.uniqid(),
                'price' => 0,
                'billing_period' => 'monthly',
                'max_widgets' => 1,
                'max_messages_per_month' => 100,
                'ai_tier' => 'basic',
                'is_active' => true,
            ])->id,
            'status' => 'active',
        ]);

        return Widget::create([
            'user_id' => $user->id,
            'name' => 'Prompt Widget',
            'slug' => 'prompt-widget-'.uniqid(),
            'settings' => [],
            'is_active' => true,
            'status' => 'active',
        ]);
    }

    public function test_agent_with_knowledge_base_takes_priority(): void
    {
        $widget = $this->makeWidget();

        $kb = KnowledgeBase::create([
            'ai_agent_id' => null,
            'company_name' => 'Floodbar',
            'persona_name' => 'Rina',
        ]);

        $agent = AiAgent::create([
            'user_id' => $widget->user_id,
            'name' => 'Agent Rina',
            'personality' => 'friendly',
            'system_prompt' => 'Kamu adalah Rina dari Floodbar.',
        ]);

        $kb->update(['ai_agent_id' => $agent->id]);
        KnowledgeFaq::create([
            'knowledge_base_id' => $kb->id,
            'question' => 'Berapa lama pengiriman?',
            'answer' => '1-3 hari kerja.',
        ]);
        $widget->update(['ai_agent_id' => $agent->id]);

        $result = $this->builder->buildKnowledgeArray($widget->fresh(['aiAgent.knowledgeBase.faqs']));

        $this->assertSame($kb->id, $result['knowledge_base_id']);
        $this->assertSame('Agent Rina', $result['ai_agent']['name']);
        $this->assertSame('Kamu adalah Rina dari Floodbar.', $result['ai_agent']['system_prompt']);
        $this->assertSame('Floodbar', $result['company']['name']);
        $this->assertSame('Berapa lama pengiriman?', $result['faqs'][0]['question']);
    }

    public function test_widget_falls_back_to_its_own_knowledge_base(): void
    {
        $widget = $this->makeWidget();

        $kb = KnowledgeBase::create([
            'widget_id' => $widget->id,
            'company_name' => 'Toko Aman',
            'persona_name' => 'Dian',
        ]);
        KnowledgeFaq::create([
            'knowledge_base_id' => $kb->id,
            'question' => 'Ada COD?',
            'answer' => 'Ada untuk area tertentu.',
        ]);

        $result = $this->builder->buildKnowledgeArray($widget->fresh(['knowledgeBase.faqs']));

        $this->assertSame($kb->id, $result['knowledge_base_id']);
        $this->assertSame('Toko Aman', $result['company']['name']);
        $this->assertArrayNotHasKey('ai_agent', $result);
    }

    public function test_widget_without_agent_or_knowledge_base_returns_default_structure(): void
    {
        $result = $this->builder->buildKnowledgeArray($this->makeWidget());

        $this->assertNull($result['knowledge_base_id']);
        $this->assertSame('Perusahaan', $result['company']['name']);
        $this->assertSame('AI Assistant', $result['persona']['name']);
        $this->assertSame([], $result['faqs']);
    }

    public function test_default_prompt_contains_company_faq_rules_and_function_calling(): void
    {
        $prompt = $this->builder->buildSystemPrompt([
            'company' => ['name' => 'Floodbar', 'description' => 'Toko flooding parts'],
            'persona' => ['name' => 'Rina', 'tone' => 'santai'],
            'faqs' => [['question' => 'Jam berapa buka?', 'answer' => '09.00-17.00']],
            'custom_instructions' => 'Selalu tawarkan promo bulan ini.',
        ], 'session-1');

        $this->assertStringContainsString('Customer Service untuk Floodbar', $prompt);
        $this->assertStringContainsString('Q: Jam berapa buka?', $prompt);
        $this->assertStringContainsString('Selalu tawarkan promo bulan ini.', $prompt);
        $this->assertStringContainsString('## Aturan Penting', $prompt);
        $this->assertStringContainsString('## INTEGRASI SISTEM (Function Calling)', $prompt);
        $this->assertStringContainsString('"action": "save_lead"', $prompt);
    }

    public function test_lead_prompt_settings_add_lead_instructions(): void
    {
        $prompt = $this->builder->buildSystemPrompt([
            'company' => ['name' => 'Floodbar', 'description' => ''],
            'faqs' => [],
            'settings' => [
                'lead_prompt_enabled' => true,
                'lead_ask_name' => true,
                'lead_ask_email' => true,
            ],
        ], 'session-2');

        $this->assertStringContainsString('## Instruksi Lead Collection', $prompt);
        $this->assertStringContainsString('Nama Lengkap', $prompt);
        $this->assertStringContainsString('Email', $prompt);
        $this->assertStringNotContainsString('Nomor HP', $prompt);
    }

    public function test_agent_system_prompt_is_used_as_base_with_documents(): void
    {
        $kb = KnowledgeBase::create([
            'ai_agent_id' => null,
            'company_name' => 'Floodbar',
        ]);

        KnowledgeDocument::create([
            'knowledge_base_id' => $kb->id,
            'name' => 'harga.pdf',
            'type' => 'pdf',
            'status' => 'completed',
            'chunks' => ['Harga paket A 100 ribu.', 'Harga paket B 200 ribu.', 'Harga paket C 300 ribu.'],
        ]);

        $prompt = $this->builder->buildSystemPrompt([
            'knowledge_base_id' => $kb->id,
            'ai_agent' => ['system_prompt' => 'Kamu adalah CS Floodbar yang ramah.'],
            'faqs' => [['question' => 'Ada garansi?', 'answer' => 'Ada 1 tahun.']],
        ], 'session-3');

        $this->assertStringStartsWith('Kamu adalah CS Floodbar yang ramah.', $prompt);
        $this->assertStringContainsString('Q: Ada garansi?', $prompt);
        $this->assertStringContainsString('## Dokumen & Informasi Tambahan', $prompt);
        $this->assertStringContainsString('Harga paket A 100 ribu.', $prompt);
        $this->assertStringContainsString('Harga paket B 200 ribu.', $prompt);
        $this->assertStringNotContainsString('Harga paket C 300 ribu.', $prompt);
    }
}
