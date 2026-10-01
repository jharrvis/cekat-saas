<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Plan;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Enforces the per-plan retention promise (plans.chat_history_days
 * 7/30/90) that billing advertises: `chat:purge` deletes sessions older
 * than the tenant's window (fallback 7 days) and their messages.
 */
class ChatRetentionTest extends TestCase
{
    use RefreshDatabase;

    private function makeStack(int $days): array
    {
        $plan = Plan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'max_messages_per_month' => 100,
            'ai_tier' => 'basic',
            'chat_history_days' => $days,
        ]);

        $user = User::create([
            'name' => 'Owner',
            'email' => 'owner-ret@'.uniqid().'.test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
            'plan_id' => $plan->id,
        ]);

        $widget = Widget::create([
            'user_id' => $user->id,
            'name' => 'Widget Ret',
            'slug' => 'w-ret-'.uniqid(),
            'status' => 'active',
        ]);

        return compact('plan', 'user', 'widget');
    }

    private function makeSessionAt(Widget $widget, string $uuid, int $daysAgo): ChatSession
    {
        $session = ChatSession::create([
            'widget_id' => $widget->id,
            'visitor_uuid' => $uuid,
            'started_at' => now()->subDays($daysAgo),
        ]);

        DB::table('chat_sessions')->where('id', $session->id)->update([
            'created_at' => now()->subDays($daysAgo),
        ]);

        return $session->fresh();
    }

    public function test_purge_deletes_sessions_older_than_plan_retention(): void
    {
        ['widget' => $widget] = $this->makeStack(30);

        $expired = $this->makeSessionAt($widget, 'sess_ret_old', 40);
        ChatMessage::create(['session_id' => $expired->id, 'role' => 'user', 'content' => 'pesan kadaluarsa']);

        $fresh = $this->makeSessionAt($widget, 'sess_ret_new', 5);
        ChatMessage::create(['session_id' => $fresh->id, 'role' => 'user', 'content' => 'pesan aktif']);

        $this->artisan('chat:purge')->assertExitCode(0);

        $this->assertDatabaseMissing('chat_sessions', ['id' => $expired->id]);
        $this->assertDatabaseMissing('chat_messages', ['session_id' => $expired->id]);
        $this->assertDatabaseHas('chat_sessions', ['id' => $fresh->id]);
        $this->assertDatabaseHas('chat_messages', ['session_id' => $fresh->id]);
    }

    public function test_dry_run_reports_without_deleting(): void
    {
        ['widget' => $widget] = $this->makeStack(30);

        $expired = $this->makeSessionAt($widget, 'sess_ret_dry', 40);

        $this->artisan('chat:purge', ['--dry-run' => true])->assertExitCode(0);

        $this->assertDatabaseHas('chat_sessions', ['id' => $expired->id]);
    }

    public function test_plan_without_retention_window_falls_back_to_seven_days(): void
    {
        $plan = Plan::create([
            'name' => 'Tanpa Retensi',
            'slug' => 'tanpa-retensi',
            'max_messages_per_month' => 100,
            // chat_history_days intentionally NULL
        ]);

        $user = User::create([
            'name' => 'Owner',
            'email' => 'owner-noret@'.uniqid().'.test.id',
            'password' => 'secret123',
            'email_verified_at' => now(),
            'plan_id' => $plan->id,
        ]);

        $widget = Widget::create([
            'user_id' => $user->id,
            'name' => 'Widget NoRet',
            'slug' => 'w-noret-'.uniqid(),
            'status' => 'active',
        ]);

        $expired = $this->makeSessionAt($widget, 'sess_noret_old', 8);
        $fresh = $this->makeSessionAt($widget, 'sess_noret_new', 2);

        $this->artisan('chat:purge')->assertExitCode(0);

        $this->assertDatabaseMissing('chat_sessions', ['id' => $expired->id]);
        $this->assertDatabaseHas('chat_sessions', ['id' => $fresh->id]);
    }
}
