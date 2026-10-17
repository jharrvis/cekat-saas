<?php

namespace App\Livewire\Admin;

use App\Models\ChatMessage;
use Livewire\Component;

/**
 * AI usage per model (Admin > AI Models & Tiers): assistant replies and
 * tokens grouped by the model that actually served them, from
 * chat_messages.model_used / tokens_used. Admin-only surface, so model
 * names are shown as-is (masking applies to visitors, not admins).
 */
class AiUsageStats extends Component
{
    public int $days = 30;

    /** @var array<int, array{model: string, messages: int, tokens: int, share: float}> */
    public array $rows = [];

    public int $totalMessages = 0;

    public int $totalTokens = 0;

    public function mount(): void
    {
        $this->loadStats();
    }

    public function setDays(int $days): void
    {
        $this->days = in_array($days, [7, 30, 90], true) ? $days : 30;
        $this->loadStats();

        $this->dispatch('ai-usage-updated',
            labels: array_column($this->rows, 'model'),
            values: array_column($this->rows, 'messages'),
        );
    }

    public function loadStats(): void
    {
        $data = ChatMessage::query()
            ->where('role', 'assistant')
            ->whereNotNull('model_used')
            ->where('model_used', '!=', '')
            ->where('created_at', '>=', now()->subDays($this->days))
            ->selectRaw('model_used, COUNT(*) as messages_count, COALESCE(SUM(tokens_used), 0) as tokens_total')
            ->groupBy('model_used')
            ->orderByDesc('messages_count')
            ->limit(12)
            ->get();

        $this->totalMessages = (int) $data->sum('messages_count');
        $this->totalTokens = (int) $data->sum('tokens_total');
        $this->rows = $data->map(fn ($r) => [
            'model' => $r->model_used,
            'messages' => (int) $r->messages_count,
            'tokens' => (int) $r->tokens_total,
            'share' => $this->totalMessages > 0 ? round($r->messages_count / $this->totalMessages * 100, 1) : 0.0,
        ])->all();
    }

    public function render()
    {
        return view('livewire.admin.ai-usage-stats');
    }
}
