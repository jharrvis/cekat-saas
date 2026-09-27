<?php

namespace Tests\Feature;

use App\Models\AiAgent;
use App\Models\ChatSession;
use App\Models\KnowledgeBase;
use App\Models\Plan;
use App\Models\User;
use App\Models\WhatsAppDevice;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    private function makeUser(string $role = 'user'): User
    {
        $this->seq++;

        $plan = Plan::create(['name' => 'P'.$this->seq, 'slug' => 'p-'.$this->seq]);

        return User::create([
            'name' => 'User '.$this->seq,
            'email' => "user{$this->seq}@test.id",
            'password' => 'secret123',
            'role' => $role,
            'plan_id' => $plan->id,
        ]);
    }

    private function makeStack(User $user, string $slug): array
    {
        $agent = AiAgent::create(['user_id' => $user->id, 'name' => 'A', 'slug' => 'a-'.$slug]);
        $kb = KnowledgeBase::create(['ai_agent_id' => $agent->id, 'company_name' => 'C']);
        $widget = Widget::create([
            'user_id' => $user->id, 'ai_agent_id' => $agent->id,
            'name' => $slug, 'slug' => $slug,
        ]);
        $session = ChatSession::create(['widget_id' => $widget->id, 'visitor_uuid' => 'v-'.$slug]);
        $device = WhatsAppDevice::create([
            'user_id' => $user->id, 'phone_number' => '62812000000', 'device_name' => 'D',
        ]);

        return compact('agent', 'kb', 'widget', 'session', 'device');
    }

    public function test_user_cannot_view_or_update_another_users_agent(): void
    {
        $owner = $this->makeUser();
        $intruder = $this->makeUser();
        ['agent' => $agent] = $this->makeStack($owner, 'w-pol-1');

        $this->actingAs($intruder)->get("/agents/{$agent->id}/edit")->assertForbidden();
        $this->actingAs($intruder)->put("/agents/{$agent->id}", [
            'name' => 'Hijacked',
            'personality' => 'friendly',
            'ai_temperature' => 0.7,
        ])->assertForbidden();

        $this->assertSame('A', $agent->fresh()->name);
    }

    public function test_user_cannot_manage_another_users_channel(): void
    {
        $owner = $this->makeUser();
        $intruder = $this->makeUser();
        ['widget' => $widget] = $this->makeStack($owner, 'w-pol-2');

        // Scoped lookup yields 404 (no existence leak), policy denies even if resolved.
        $this->actingAs($intruder)->get("/channels/{$widget->id}/edit")->assertNotFound();
        // Legacy URL redirects to the channels index instead of leaking.
        $this->actingAs($intruder)->get("/chatbots/{$widget->id}/edit")->assertRedirect('/channels');
        $this->assertFalse(Gate::forUser($intruder)->allows('update', $widget));
        $this->assertFalse(Gate::forUser($intruder)->allows('delete', $widget));
        $this->assertTrue(Gate::forUser($owner)->allows('update', $widget));
    }

    public function test_user_cannot_view_another_users_session_or_knowledge(): void
    {
        $owner = $this->makeUser();
        $intruder = $this->makeUser();
        ['session' => $session, 'kb' => $kb] = $this->makeStack($owner, 'w-pol-3');

        $this->actingAs($intruder)->get("/chats/{$session->id}")->assertNotFound();
        $this->assertFalse(Gate::forUser($intruder)->allows('view', $session));
        $this->assertFalse(Gate::forUser($intruder)->allows('view', $kb));
        $this->assertFalse(Gate::forUser($intruder)->allows('update', $kb));
        $this->assertTrue(Gate::forUser($owner)->allows('view', $kb));
    }

    public function test_user_cannot_access_another_users_whatsapp_device(): void
    {
        $owner = $this->makeUser();
        $intruder = $this->makeUser();
        ['device' => $device] = $this->makeStack($owner, 'w-pol-4');

        $this->actingAs($intruder)->get("/whatsapp/{$device->id}/messages")->assertForbidden();
        $this->assertFalse(Gate::forUser($intruder)->allows('delete', $device));
        $this->assertTrue(Gate::forUser($owner)->allows('delete', $device));
    }

    public function test_admin_routes_require_admin_role(): void
    {
        $user = $this->makeUser('user');

        $this->actingAs($user)->get('/admin/users')->assertForbidden();
        $this->actingAs($user)->get('/admin/settings')->assertForbidden();
        $this->actingAs($user)->get('/admin/whatsapp')->assertForbidden();
    }

    public function test_suspended_user_cannot_use_protected_workflows(): void
    {
        $user = $this->makeUser();
        $user->update(['status' => 'suspended']);

        $this->actingAs($user)->get('/dashboard')->assertRedirect('/account/suspended?type=suspended');
        $this->actingAs($user)->get('/agents')->assertRedirect('/account/suspended?type=suspended');
    }
}
