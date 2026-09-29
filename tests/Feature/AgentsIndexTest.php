<?php

namespace Tests\Feature;

use App\Models\AiAgent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentsIndexTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::create([
            'name' => 'Agent Owner',
            'email' => 'agent-owner-' . uniqid() . '@test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
            'role' => 'user',
        ]);
    }

    public function test_index_is_compact_and_informative_without_empty_stats(): void
    {
        $user = $this->makeUser();

        $agent = AiAgent::create([
            'user_id' => $user->id,
            'name' => 'CS Bot',
            'description' => 'Menjawab pertanyaan pelanggan.',
            'personality' => 'friendly',
        ]);

        $response = $this->actingAs($user)->get(route('agents.index'));

        $response->assertOk()
            ->assertSee('CS Bot')
            ->assertSee('Knowledge kosong')
            ->assertSee('channel')
            // Empty counters (never incremented) and the model chip are gone.
            ->assertDontSee('Percakapan')
            ->assertDontSee('name="greeting_message"', false);
    }

    public function test_create_form_has_no_greeting_field(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->get(route('agents.create'))
            ->assertOk()
            ->assertSee('Fallback Message (saat error)')
            ->assertDontSee('name="greeting_message"', false)
            ->assertDontSee('Greeting Message');
    }
}
