<?php

namespace Tests\Feature;

use App\Livewire\Admin\LandingChatbotManager;
use App\Models\AiAgent;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regression: the i18n sweep once mangled `$widget->id` inside the
 * knowledge-base @livewire call in the landing chatbot manager view
 * into a translation expression. The directive stopped compiling and
 * the tab printed the raw @livewire(...) text (breaking the page's
 * tab structure, including the AI Model tab). The page must render
 * the nested editors, never raw directives.
 */
class LandingChatbotManagerRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_renders_nested_editors_without_raw_directives(): void
    {
        $owner = User::create([
            'name' => 'Admin',
            'email' => 'landing-admin@test.id',
            'password' => 'secret123',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // The page (and the KB editor inside it) assumes an
        // authenticated admin, as its route enforces.
        $this->actingAs($owner);

        $agent = AiAgent::create([
            'user_id' => $owner->id,
            'name' => 'Landing Agent',
            'slug' => 'landing-agent',
        ]);

        $widget = Widget::create([
            'user_id' => $owner->id,
            'ai_agent_id' => $agent->id,
            'name' => 'Cekat AI Support',
            'slug' => 'landing-page-default',
        ]);

        Livewire::test(LandingChatbotManager::class, ['widget' => $widget])
            ->assertDontSee('@livewire(', false)
            ->assertSee('activeSubTab', false);
    }
}
