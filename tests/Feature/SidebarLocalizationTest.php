<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Follow-up to T-12 (owner report, Oct 2026): the dashboard sidebar was
 * still hardcoded English. Sidebar labels and the matching page titles
 * now resolve through lang/{locale}/nav.php.
 */
class SidebarLocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.locale' => 'id', 'app.fallback_locale' => 'id']);
        app()->setLocale('id');
    }

    private function makeUser(string $role): User
    {
        return User::create([
            'name' => 'Nav ' . $role, 'email' => 'nav-' . $role . '-' . uniqid() . '@test.id',
            'password' => 'secret123', 'email_verified_at' => now(), 'role' => $role,
        ]);
    }

    public function test_member_sidebar_renders_indonesian_labels(): void
    {
        $html = $this->actingAs($this->makeUser('user'))->get(route('dashboard'))->getContent();

        // Note: the WhatsApp item is feature-gated off in the test env.
        foreach (['Dasbor', 'Agen AI', 'Saluran', 'Kotak Masuk', 'Pengaturan', 'Penagihan', 'Integrasi', 'Kunci API', 'BARU'] as $label) {
            $this->assertStringContainsString($label, $html, "Sidebar label hilang: {$label}");
        }
        foreach (['>Channels<', '>Billing<', '>Settings<', '>API Keys<', '>NEW<'] as $english) {
            $this->assertStringNotContainsString($english, $html, "Label Inggris masih tampil: {$english}");
        }
    }

    public function test_admin_sidebar_renders_indonesian_labels(): void
    {
        $html = $this->actingAs($this->makeUser('admin'))->get(route('admin.dashboard'))->getContent();

        foreach (['Dasbor', 'Transaksi', 'Pengguna', 'Paket', 'Pusat Email'] as $label) {
            $this->assertStringContainsString($label, $html, "Sidebar admin label hilang: {$label}");
        }
        $this->assertStringNotContainsString('>Transactions<', $html);
        $this->assertStringNotContainsString('>Users<', $html);
        $this->assertStringNotContainsString('>Email Center<', $html);
    }

    public function test_member_page_titles_are_indonesian(): void
    {
        $user = $this->makeUser('user');

        $channels = $this->actingAs($user)->get(route('channels.index'))->getContent();
        $this->assertStringContainsString('<title>Saluran', $channels);

        $billing = $this->actingAs($user)->get(route('billing'))->getContent();
        $this->assertStringContainsString('<title>Penagihan &amp; Langganan', $billing);
    }
}
