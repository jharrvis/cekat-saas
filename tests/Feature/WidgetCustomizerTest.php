<?php

namespace Tests\Feature;

use App\Livewire\WidgetCustomizer;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Widget Customizer avatar + settings-save regressions:
 * - saving must MERGE settings (a full replace silently wiped allowed_domains,
 *   subtitle, placeholder, lead_* and webhook_* every time)
 * - icon choice must survive a save; upload must switch type and not re-apply
 *   on later saves (avatarUpload was never reset)
 */
class WidgetCustomizerTest extends TestCase
{
    use RefreshDatabase;

    private function makeWidget(): array
    {
        $user = User::create([
            'name' => 'Cust Owner',
            'email' => 'cust-' . uniqid() . '@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
            'role' => 'user',
        ]);

        $widget = Widget::create([
            'user_id' => $user->id,
            'name' => 'Widget Cust',
            'slug' => 'w-cust-' . uniqid(),
            'status' => 'active',
            'is_active' => true,
            'settings' => [
                'allowed_domains' => 'example.com, www.example.com',
                'subtitle' => 'Online • Reply cepat',
                'placeholder' => 'Tulis pesan...',
                'webhook_url' => 'https://hooks.test/lead',
                'model' => 'openrouter/free',
            ],
        ]);

        return [$user, $widget];
    }

    public function test_save_merges_settings_instead_of_replacing_them(): void
    {
        [$user, $widget] = $this->makeWidget();

        Livewire::actingAs($user)->test(WidgetCustomizer::class, ['widgetId' => $widget->id])
            ->set('name', 'Widget Cust Renamed')
            ->call('saveSettings')
            ->assertHasNoErrors();

        $settings = $widget->fresh()->settings;

        $this->assertSame('Widget Cust Renamed', $widget->fresh()->name);
        $this->assertSame('example.com, www.example.com', $settings['allowed_domains']);
        $this->assertSame('Online • Reply cepat', $settings['subtitle']);
        $this->assertSame('Tulis pesan...', $settings['placeholder']);
        $this->assertSame('https://hooks.test/lead', $settings['webhook_url']);
        $this->assertSame('openrouter/free', $settings['model']);
        $this->assertSame('#0f172a', $settings['color']);
    }

    public function test_icon_choice_is_persisted(): void
    {
        [$user, $widget] = $this->makeWidget();

        Livewire::actingAs($user)->test(WidgetCustomizer::class, ['widgetId' => $widget->id])
            ->set('avatarType', 'icon')
            ->set('avatarIcon', 'headphones')
            ->call('saveSettings')
            ->assertHasNoErrors();

        $settings = $widget->fresh()->settings;

        $this->assertSame('icon', $settings['avatar_type']);
        $this->assertSame('headphones', $settings['avatar_icon']);
        $this->assertSame('', $settings['avatar_url']);
    }

    public function test_upload_switches_to_image_then_icon_choice_survives(): void
    {
        Storage::fake('public');
        [$user, $widget] = $this->makeWidget();

        $component = Livewire::actingAs($user)->test(WidgetCustomizer::class, ['widgetId' => $widget->id])
            ->set('avatarUpload', UploadedFile::fake()->image('avatar.png', 100, 100))
            ->call('saveSettings')
            ->assertHasNoErrors();

        $settings = $widget->fresh()->settings;
        $this->assertSame('image', $settings['avatar_type']);
        $this->assertStringStartsWith('/storage/avatars/', $settings['avatar_url']);
        $this->assertNull($component->get('avatarUpload'), 'uploaded file must be reset after save');

        // Switch back to an icon - the next save must not re-store a file or
        // force avatar_type back to image.
        $component
            ->set('avatarType', 'icon')
            ->set('avatarIcon', 'sparkles')
            ->call('saveSettings')
            ->assertHasNoErrors();

        $settings = $widget->fresh()->settings;
        $this->assertSame('icon', $settings['avatar_type']);
        $this->assertSame('sparkles', $settings['avatar_icon']);
    }

    public function test_preview_renders_selected_icon_and_upload_preview(): void
    {
        [$user, $widget] = $this->makeWidget();

        Livewire::actingAs($user)->test(WidgetCustomizer::class, ['widgetId' => $widget->id])
            ->set('avatarIcon', 'life-buoy')
            ->assertSee('life-buoy')
            ->assertSee('Bantuan')
            ->assertSee('Bot AI');
    }
}
