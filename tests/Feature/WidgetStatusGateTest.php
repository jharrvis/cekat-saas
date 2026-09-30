<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WidgetStatusGateTest extends TestCase
{
    use RefreshDatabase;

    private function makeWidget(string $status, bool $isActive = true): Widget
    {
        $plan = Plan::create([
            'name' => 'Starter',
            'slug' => 'starter-' . uniqid(),
            'max_messages_per_month' => 100,
            'ai_tier' => 'basic',
        ]);

        $user = User::create([
            'name' => 'Owner',
            'email' => uniqid() . '@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
            'plan_id' => $plan->id,
        ]);

        return Widget::create([
            'user_id' => $user->id,
            'name' => 'Widget',
            'slug' => 'w-status-' . uniqid(),
            'status' => $status,
            'is_active' => $isActive,
        ]);
    }

    public function test_config_endpoint_returns_404_for_inactive_widget(): void
    {
        $widget = $this->makeWidget('inactive');

        $this->getJson("/api/widget/{$widget->slug}/config")
            ->assertNotFound()
            ->assertJsonPath('error_code', 'widget_inactive');
    }

    public function test_config_endpoint_returns_404_for_draft_widget(): void
    {
        $widget = $this->makeWidget('draft');

        $this->getJson("/api/widget/{$widget->slug}/config")
            ->assertNotFound()
            ->assertJsonPath('error_code', 'widget_inactive');
    }

    public function test_config_endpoint_returns_404_when_is_active_is_false(): void
    {
        $widget = $this->makeWidget('active', false);

        $this->getJson("/api/widget/{$widget->slug}/config")
            ->assertNotFound()
            ->assertJsonPath('error_code', 'widget_inactive');
    }

    public function test_config_endpoint_serves_active_widget(): void
    {
        $widget = $this->makeWidget('active');

        $this->getJson("/api/widget/{$widget->slug}/config")
            ->assertOk()
            ->assertJsonPath('widgetId', $widget->slug)
            ->assertJsonPath('model', config('services.openrouter.default_model'));
    }

    public function test_config_endpoint_exposes_widget_model_override(): void
    {
        $widget = $this->makeWidget('active');
        $widget->update(['settings' => ['model' => 'openai/gpt-4o-mini']]);

        $this->getJson("/api/widget/{$widget->slug}/config")
            ->assertOk()
            ->assertJsonPath('model', 'openai/gpt-4o-mini');
    }

    public function test_chat_endpoint_rejects_inactive_widget(): void
    {
        $widget = $this->makeWidget('inactive');

        $response = $this->postJson('/api/chat', [
            'message' => 'halo',
            'widgetId' => $widget->slug,
            'history' => [],
            'sessionId' => 'sess_gate_test',
        ], ['Origin' => 'https://toko.test']);

        $response->assertNotFound()
            ->assertJsonPath('error_code', 'widget_inactive')
            ->assertJsonPath('success', false);
    }

    public function test_chat_endpoint_rejects_draft_widget(): void
    {
        $widget = $this->makeWidget('draft');

        $this->postJson('/api/chat', [
            'message' => 'halo',
            'widgetId' => $widget->slug,
            'history' => [],
            'sessionId' => 'sess_gate_test_2',
        ], ['Origin' => 'https://toko.test'])->assertNotFound()->assertJsonPath('error_code', 'widget_inactive');
    }
}
