<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Admin\AiUsageStats;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AiUsageStatsTest extends TestCase
{
    use RefreshDatabase;

    private function seedUsage(): void
    {
        $owner = User::factory()->create();
        $widget = Widget::create([
            'user_id' => $owner->id,
            'name' => 'Widget Usage',
            'slug' => 'w-usage-'.uniqid(),
            'status' => 'active',
            'is_active' => true,
        ]);
        $session = ChatSession::create([
            'widget_id' => $widget->id,
            'visitor_uuid' => 'sess-usage-'.uniqid(),
            'started_at' => now(),
        ]);

        $add = function (string $role, ?string $model, int $tokens, $when) use ($session) {
            // created_at is not fillable on ChatMessage - stamp it via
            // the query builder so period windows see the intended age.
            $row = ChatMessage::create([
                'session_id' => $session->id,
                'role' => $role,
                'content' => 'x',
                'model_used' => $model,
                'tokens_used' => $tokens,
            ]);
            ChatMessage::where('id', $row->id)->update(['created_at' => $when, 'updated_at' => $when]);
        };

        $add('assistant', 'model-a', 100, now());
        $add('assistant', 'model-a', 100, now());
        $add('assistant', 'model-a', 100, now()->subDays(2));
        $add('assistant', 'model-b', 50, now());
        $add('user', null, 0, now());
        $add('assistant', 'model-a', 999, now()->subDays(40)); // outside 30d, inside 90d
    }

    public function test_usage_is_aggregated_per_model_for_the_period(): void
    {
        $this->seedUsage();

        $component = Livewire::test(AiUsageStats::class);

        $rows = collect($component->get('rows'))->keyBy('model');
        $this->assertSame(3, $rows['model-a']['messages']);
        $this->assertSame(300, $rows['model-a']['tokens']);
        $this->assertSame(1, $rows['model-b']['messages']);
        $this->assertSame(4, $component->get('totalMessages'));
        $this->assertSame(350, $component->get('totalTokens'));

        $component->call('setDays', 90);
        $rows = collect($component->get('rows'))->keyBy('model');
        $this->assertSame(4, $rows['model-a']['messages']);
        $this->assertSame(1299, $rows['model-a']['tokens']);
    }

    public function test_empty_period_has_no_rows(): void
    {
        $component = Livewire::test(AiUsageStats::class);

        $this->assertSame([], $component->get('rows'));
        $this->assertSame(0, $component->get('totalMessages'));
    }
}
