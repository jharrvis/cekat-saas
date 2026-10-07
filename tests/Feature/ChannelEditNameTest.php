<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Remediation T-06 / finding F-05: the channel edit form showed an empty
 * name for widgets whose display_name is NULL — most notably the default
 * widget auto-created at registration, which only ever set `name`.
 * The index list masked this by falling back to `name`; the edit form did not.
 */
class ChannelEditNameTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithLegacyWidget(): array
    {
        $plan = Plan::create(['name' => 'Starter', 'slug' => 'starter-' . uniqid(), 'price' => 0]);
        $user = User::create([
            'name' => 'Pemilik',
            'email' => 'pemilik-' . uniqid() . '@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
            'plan_id' => $plan->id,
        ]);

        // Legacy shape: name set, display_name NULL (pre-fix default widget).
        $widget = $user->widgets()->create([
            'name' => 'Widget Bawaan',
            'display_name' => null,
            'slug' => 'widget-' . uniqid(),
            'status' => 'active',
            'is_active' => true,
        ]);

        return [$user, $widget];
    }

    public function test_edit_form_shows_name_when_display_name_is_null(): void
    {
        [$user, $widget] = $this->makeUserWithLegacyWidget();

        $response = $this->actingAs($user)->get(route('channels.edit', $widget->id));

        $response->assertOk();
        $response->assertSee('value="Widget Bawaan"', false);
    }

    public function test_registration_default_widget_has_display_name(): void
    {
        Plan::create(['name' => 'Starter', 'slug' => 'starter', 'price' => 0, 'is_active' => true, 'sort_order' => 1]);

        $this->post('/register', [
            'name' => 'Pengguna Baru',
            'email' => 'baru-' . uniqid() . '@test.id',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect();

        $widget = Widget::where('name', "Pengguna Baru's Widget")->first();
        $this->assertNotNull($widget);
        $this->assertSame("Pengguna Baru's Widget", $widget->display_name);
    }

    public function test_update_rejects_empty_channel_name_on_general_tab(): void
    {
        [$user, $widget] = $this->makeUserWithLegacyWidget();

        $this->actingAs($user)
            ->put(route('channels.update', $widget->id), ['display_name' => ''])
            ->assertSessionHasErrors('display_name');

        $this->assertNull($widget->fresh()->display_name);
    }

    public function test_update_from_other_tabs_does_not_require_name(): void
    {
        [$user, $widget] = $this->makeUserWithLegacyWidget();

        $this->actingAs($user)
            ->put(route('channels.update', $widget->id), ['tab' => 'lead'])
            ->assertSessionDoesntHaveErrors('display_name');
    }
}
