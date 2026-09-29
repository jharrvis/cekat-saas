<?php

namespace Tests\Feature;

use App\Models\ChatSession;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatHistoryDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithSession(): array
    {
        static $seq = 0;
        $seq++;

        $user = User::create([
            'name' => 'User ' . $seq,
            'email' => 'chatter-' . $seq . '-' . uniqid() . '@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
            'role' => 'user',
        ]);

        $widget = Widget::create([
            'user_id' => $user->id,
            'name' => 'w-' . uniqid(),
            'slug' => 'w-' . uniqid(),
            'status' => 'active',
            'is_active' => true,
        ]);

        $session = ChatSession::create([
            'widget_id' => $widget->id,
            'visitor_uuid' => 'v-' . uniqid(),
        ]);

        return [$user, $session];
    }

    public function test_delete_from_show_page_redirects_to_chat_history(): void
    {
        [$user, $session] = $this->makeUserWithSession();

        $this->actingAs($user)
            ->get(route('chats.show', $session->id))
            ->assertOk();

        $response = $this->actingAs($user)
            ->delete(route('chats.destroy', $session->id));

        $response->assertRedirect(route('chats.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('chat_sessions', ['id' => $session->id]);
    }

    public function test_delete_from_index_redirects_to_chat_history(): void
    {
        [$user, $session] = $this->makeUserWithSession();

        $this->actingAs($user)
            ->get(route('chats.index'))
            ->assertOk();

        $response = $this->actingAs($user)
            ->delete(route('chats.destroy', $session->id));

        $response->assertRedirect(route('chats.index'));
        $this->assertDatabaseMissing('chat_sessions', ['id' => $session->id]);
    }

    public function test_delete_lead_alias_redirects_to_chat_history(): void
    {
        [$user, $session] = $this->makeUserWithSession();

        $response = $this->actingAs($user)
            ->delete(route('leads.destroy', $session->id));

        $response->assertRedirect(route('chats.index'));
        $this->assertDatabaseMissing('chat_sessions', ['id' => $session->id]);
    }
}
