<?php

namespace Tests\Feature;

use App\Models\AiAgent;
use App\Models\Plan;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiSmokeTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    private function makeUser(string $role = 'user'): User
    {
        $this->seq++;
        $plan = Plan::create(['name' => 'P'.$this->seq, 'slug' => 'p-'.$this->seq]);

        return User::create([
            'name' => 'U'.$this->seq,
            'email' => "u{$this->seq}@test.id",
            'password' => 'secret123',
            'role' => $role,
            'plan_id' => $plan->id,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/channels')->assertRedirect('/login');
        $this->get('/agents')->assertRedirect('/login');
    }

    public function test_channel_pages_render(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->get('/channels')->assertOk();
        $this->actingAs($user)->get('/channels/create')->assertOk();

        $widget = Widget::create([
            'user_id' => $user->id, 'name' => 'w', 'slug' => 'w-smoke',
        ]);

        foreach (['general', 'knowledge', 'widget', 'lead', 'domains', 'embed', 'webhook', 'analytics'] as $tab) {
            $this->actingAs($user)
                ->get("/channels/{$widget->id}/edit/{$tab}")
                ->assertOk();
        }

        // Unknown tab falls back to general instead of 500.
        $this->actingAs($user)->get("/channels/{$widget->id}/edit/model")->assertOk();
    }

    public function test_legacy_chatbot_urls_redirect(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->get('/chatbots')->assertRedirect('/channels');
        $this->actingAs($user)->get('/chatbots/create')->assertRedirect('/channels/create');
    }

    public function test_agent_editor_renders(): void
    {
        $user = $this->makeUser();
        $agent = AiAgent::create(['user_id' => $user->id, 'name' => 'A', 'slug' => 'a-smoke']);

        $this->actingAs($user)->get("/agents/{$agent->id}/edit")->assertOk();
        $this->actingAs($user)->get('/agents')->assertOk();
    }

    public function test_admin_models_page_requires_admin(): void
    {
        $user = $this->makeUser('user');
        $admin = $this->makeUser('admin');

        $this->actingAs($user)->get('/admin/models')->assertForbidden();
        $this->actingAs($admin)->get('/admin/models')->assertOk();
    }
}
