<?php

namespace Tests\Feature;

use App\Models\ChatSession;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatHistoryShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_page_renders_visitor_meta_block(): void
    {
        $user = User::create([
            'name' => 'Meta Owner',
            'email' => 'meta-owner-' . uniqid() . '@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
            'role' => 'user',
        ]);

        $widget = Widget::create([
            'user_id' => $user->id,
            'name' => 'w-meta',
            'slug' => 'w-meta-' . uniqid(),
            'status' => 'active',
            'is_active' => true,
        ]);

        $session = ChatSession::create([
            'widget_id' => $widget->id,
            'visitor_uuid' => 'sess_MetaUuidExample1234567890',
            'ip_address' => '203.0.113.42',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36',
            'device_type' => 'desktop',
            'location_data' => [
                'country_code' => 'ID',
                'country' => 'Indonesia',
                'region' => 'Java',
                'city' => 'Semarang',
                'isp' => 'PT. Telekomunikasi Selular',
            ],
            'is_lead' => true,
            'visitor_name' => 'Rina',
        ]);

        $this->actingAs($user)
            ->get(route('chats.show', $session->id))
            ->assertOk()
            ->assertSee('Session ID')
            ->assertSee('sess_MetaUuidExa')
            ->assertSee('IP Address')
            ->assertSee('203.0.113.42')
            ->assertSee('Browser')
            ->assertSee('Chrome')
            ->assertSee('Device')
            ->assertSee('Desktop')
            ->assertSee('Location')
            ->assertSee('Semarang, Java, Indonesia');
    }
}
