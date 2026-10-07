<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Remediation T-10 / finding F-11: the landing footer linked everything to
 * "#" (dead links), the legal pages did not exist, and the sales CTA
 * pointed at a missing #kontak anchor.
 */
class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_legal_pages_are_public_and_indonesian(): void
    {
        $this->get('/kebijakan-privasi')
            ->assertOk()
            ->assertSee('Kebijakan Privasi')
            ->assertSee('Data yang kami kumpulkan');

        $this->get('/syarat-ketentuan')
            ->assertOk()
            ->assertSee('Syarat & Ketentuan')
            ->assertSee('Paket, kuota, dan pembayaran');
    }

    public function test_landing_has_no_dead_hash_links_and_footer_points_to_real_pages(): void
    {
        // Pricing cards render from the plans table; the sales CTA sits on
        // the last paid card.
        foreach ([['Starter', 'starter', 0, 1], ['Pro', 'pro', 99000, 2], ['Business', 'business', 199000, 3]] as [$name, $slug, $price, $sort]) {
            \App\Models\Plan::create(['name' => $name, 'slug' => $slug, 'price' => $price, 'is_active' => true, 'sort_order' => $sort]);
        }

        $response = $this->get('/');
        $response->assertOk();
        $response->assertDontSee('href="#"', false);
        $response->assertSee('/kebijakan-privasi');
        $response->assertSee('/syarat-ketentuan');
        $response->assertSee('/docs/api');
        // The sales CTA anchor target exists and carries a contact address.
        $response->assertSee('id="kontak"', false);
        $response->assertSee('support@cekat.ai');
        $response->assertSee('Hubungi Penjualan');
    }
}
