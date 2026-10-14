<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The integration page's WordPress plugin version badge used to be a
 * hardcoded "v1.0.1" string that drifted from the actual plugin build.
 * It must always reflect the generated update manifest - the same file
 * that tells installed plugins which version is latest.
 */
class IntegrationPluginVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_integration_page_shows_current_plugin_version_from_manifest(): void
    {
        $user = User::factory()->create();
        // The integration page only renders its cards for users that
        // already own at least one widget/channel.
        Widget::create([
            'user_id' => $user->id,
            'name' => 'Widget Integrasi',
            'slug' => 'w-integration-'.uniqid(),
            'status' => 'active',
            'is_active' => true,
        ]);

        $manifest = public_path('downloads/cekat-ai-chatbot-update.json');
        $this->assertFileExists($manifest);
        $version = json_decode((string) file_get_contents($manifest), true)['version'] ?? null;
        $this->assertNotEmpty($version);

        $response = $this->actingAs($user)->get(route('integration'));

        $response->assertOk();
        $response->assertSee('v'.$version);
        $response->assertDontSee('v1.0.1');
    }
}
