<?php

namespace Tests\Feature;

use App\Models\ChatSession;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRecentConversationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_recent_conversation_row_links_to_chat_detail(): void
    {
        $user = User::create([
            'name' => 'Dash Owner',
            'email' => 'dash-owner-' . uniqid() . '@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
            'role' => 'user',
        ]);

        $widget = Widget::create([
            'user_id' => $user->id,
            'name' => 'w-dash',
            'slug' => 'w-dash-' . uniqid(),
            'status' => 'active',
            'is_active' => true,
        ]);

        $session = ChatSession::create([
            'widget_id' => $widget->id,
            'visitor_uuid' => 'sess-dash-' . uniqid(),
            'visitor_name' => 'Rina',
            'started_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Percakapan Terbaru')
            ->assertSee(route('chats.show', $session->id), false);
    }
}
