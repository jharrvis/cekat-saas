<?php

namespace Tests\Feature;

use App\Livewire\KnowledgeBaseEditor;
use App\Models\KnowledgeBase;
use App\Models\Plan;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Remediation T-15 / findings F-09, F-16, F-17 (badge claim lives in T-21):
 * the landing must not promise CSV uploads the product cannot read, the KB
 * editor must show the user's REAL plan upload limit, a new account's
 * business profile must not be prefilled with the owner's name, and Quick
 * Start step 1 must not be pre-checked by the auto-created widget.
 */
class LandingHonestyTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_no_longer_claims_csv_support(): void
    {
        $response = $this->get('/');
        $response->assertOk();
        $response->assertDontSee('CSV');
        $response->assertSee('Mendukung PDF, DOCX, TXT');
    }

    public function test_registration_default_knowledge_base_has_empty_company_name(): void
    {
        Plan::create(['name' => 'Starter', 'slug' => 'starter', 'price' => 0, 'is_active' => true, 'sort_order' => 1]);

        $this->post('/register', [
            'name' => 'Nama Pemilik',
            'email' => 'baru-' . uniqid() . '@test.id',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect();

        $kb = KnowledgeBase::first();
        $this->assertNotNull($kb);
        $this->assertSame('', $kb->company_name);
    }

    public function test_kb_editor_shows_the_plans_real_upload_limit(): void
    {
        $plan = Plan::create([
            'name' => 'Starter', 'slug' => 'starter', 'price' => 0,
            'max_file_size_mb' => 5, 'is_active' => true,
        ]);
        $user = User::create([
            'name' => 'Pemilik', 'email' => 'lim-' . uniqid() . '@test.id',
            'password' => 'secret123', 'email_verified_at' => now(), 'plan_id' => $plan->id,
        ]);
        $widget = $user->widgets()->create([
            'name' => 'W', 'slug' => 'w-' . uniqid(), 'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(KnowledgeBaseEditor::class, ['widgetId' => $widget->id])
            ->assertSet('maxFileSizeMb', 5)
            ->assertSee('maks 5MB sesuai paket Anda');
    }

    public function test_quick_start_step_one_requires_a_linked_widget(): void
    {
        $plan = Plan::create(['name' => 'Starter', 'slug' => 'starter', 'price' => 0, 'is_active' => true]);
        $user = User::create([
            'name' => 'Baru', 'email' => 'qs-' . uniqid() . '@test.id',
            'password' => 'secret123', 'email_verified_at' => now(), 'plan_id' => $plan->id,
        ]);
        $widget = $user->widgets()->create([
            'name' => 'W', 'slug' => 'w-qs-' . uniqid(), 'is_active' => true, 'ai_agent_id' => null,
        ]);

        $html = $this->actingAs($user)->get(route('dashboard'))->getContent();
        $stepOne = $this->segmentBefore($html, 'Buat Channel');
        $this->assertStringNotContainsString('fa-check', $stepOne);
        $this->assertStringContainsString('Hubungkan', $this->segmentAfter($html, 'Buat Channel', 300));

        // After linking the widget to an agent, step 1 completes.
        $agent = $user->aiAgents()->create(['name' => 'Agen', 'slug' => 'agen-' . uniqid()]);
        $widget->update(['ai_agent_id' => $agent->id]);

        $html = $this->actingAs($user)->get(route('dashboard'))->getContent();
        $this->assertStringContainsString('fa-check', $this->segmentBefore($html, 'Buat Channel'));
    }

    private function segmentBefore(string $html, string $needle, int $length = 900): string
    {
        $pos = strpos($html, $needle);
        $this->assertNotFalse($pos, "Needle {$needle} not found in page");

        return substr($html, max(0, $pos - $length), $length);
    }

    private function segmentAfter(string $html, string $needle, int $length = 300): string
    {
        $pos = strpos($html, $needle);
        $this->assertNotFalse($pos, "Needle {$needle} not found in page");

        return substr($html, $pos, $length);
    }
}
