<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Remediation T-13 / finding F-13: every member page must carry its own
 * title in the "{Page} - Cekat.biz.id" pattern. Audit result: only the
 * four WhatsApp views lacked @section('title') and silently fell back to
 * "Dashboard" — including the page QA flagged.
 */
class PageTitlesTest extends TestCase
{
    use RefreshDatabase;

    public function test_whatsapp_index_title_is_not_dashboard(): void
    {
        $plan = Plan::create([
            'name' => 'Bisnis', 'slug' => 'business', 'price' => 199000,
            'can_use_whatsapp' => true, 'is_active' => true,
        ]);
        $user = User::create([
            'name' => 'Pemilik', 'email' => 'wa-' . uniqid() . '@test.id',
            'password' => 'secret123', 'email_verified_at' => now(), 'plan_id' => $plan->id,
        ]);

        $response = $this->actingAs($user)->get(route('whatsapp.index'));
        $response->assertOk();
        // In the test environment the WhatsApp module is unavailable, so the
        // controller serves the "disabled" view — the point of T-13 is that
        // even that view now carries its own title instead of "Dashboard".
        $response->assertSee('<title>WhatsApp Tidak Tersedia - Cekat.biz.id</title>', false);
        $response->assertDontSee('<title>Dashboard - Cekat.biz.id</title>', false);
    }

    public function test_key_member_pages_have_their_own_titles(): void
    {
        $plan = Plan::create(['name' => 'Starter', 'slug' => 'starter', 'price' => 0, 'is_active' => true]);
        $user = User::create([
            'name' => 'Pemilik', 'email' => 't-' . uniqid() . '@test.id',
            'password' => 'secret123', 'email_verified_at' => now(), 'plan_id' => $plan->id,
        ]);

        $expectations = [
            [route('dashboard'), 'Dashboard - Cekat.biz.id'],
            [route('agents.index'), 'AI Agents - Cekat.biz.id'],
            [route('channels.index'), 'Channels - Cekat.biz.id'],
            [route('billing'), 'Billing &amp; Subscription - Cekat.biz.id'],
            [route('settings'), 'Pengaturan Akun - Cekat.biz.id'],
        ];

        foreach ($expectations as [$url, $title]) {
            $this->actingAs($user)->get($url)
                ->assertOk()
                ->assertSee('<title>' . $title . '</title>', false);
        }
    }
}
