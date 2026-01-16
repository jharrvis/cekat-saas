<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use App\Models\ChatMessage;
use App\Models\LlmModel;
use Illuminate\Support\Facades\DB;

class BillingMonitoring extends Component
{
    public $openRouterCredits = 0;
    public $openRouterUsage = 0;
    public $internalCost = 0;
    public $usageByModel = [];
    public $topUsers = [];
    public $isLoading = true;

    public function mount()
    {
        $this->fetchData();
    }

    public function fetchData()
    {
        $this->isLoading = true;

        // 1. Fetch OpenRouter Credits
        $this->fetchOpenRouterCredits();

        // 2. Calculate Internal Usage Stats
        $this->calculateInternalUsage();

        $this->isLoading = false;
    }

    public function fetchOpenRouterCredits()
    {
        try {
            $apiKey = config('services.openrouter.api_key');
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->get('https://openrouter.ai/api/v1/credits');

            if ($response->successful()) {
                $data = $response->json()['data'] ?? [];
                $this->openRouterCredits = $data['total_credits'] ?? 0;
                $this->openRouterUsage = $data['total_usage'] ?? 0;
            }
        } catch (\Exception $e) {
            // Log error or just set to 0
            \Log::error('OpenRouter Credits Fetch Error: ' . $e->getMessage());
        }
    }

    public function calculateInternalUsage()
    {
        // Get all assistant messages with token usage
        $usageStats = ChatMessage::where('role', 'assistant')
            ->whereNotNull('tokens_used')
            ->where('created_at', '>=', now()->startOfMonth())
            ->select('model_used', DB::raw('SUM(tokens_used) as total_tokens'), DB::raw('COUNT(*) as request_count'))
            ->groupBy('model_used')
            ->get();

        $this->usageByModel = [];
        $totalEstimatedCost = 0;

        foreach ($usageStats as $stat) {
            $model = LlmModel::where('model_id', $stat->model_used)->first();

            // Estimate cost: (tokens / 1M) * average price
            // Using average of input/output price for estimation since we store total tokens
            $avgPrice = $model ? ($model->input_price + $model->output_price) / 2 : 0.5; // Default fallback price
            $cost = ($stat->total_tokens / 1000000) * $avgPrice;

            $this->usageByModel[] = [
                'model' => $stat->model_used,
                'name' => $model->name ?? $stat->model_used,
                'tokens' => $stat->total_tokens,
                'requests' => $stat->request_count,
                'cost' => $cost,
            ];

            $totalEstimatedCost += $cost;
        }

        $this->internalCost = $totalEstimatedCost;

        // Top Users usage
        // Note: This requires joining chat_messages -> chat_sessions -> widgets -> users
        // For now, let's group by widget as a proxy for user/chatbot
        $this->topUsers = DB::table('chat_messages')
            ->join('chat_sessions', 'chat_messages.session_id', '=', 'chat_sessions.id')
            ->join('widgets', 'chat_sessions.widget_id', '=', 'widgets.id')
            ->leftJoin('users', 'widgets.user_id', '=', 'users.id')
            ->where('chat_messages.role', 'assistant')
            ->where('chat_messages.created_at', '>=', now()->startOfMonth())
            ->select(
                'widgets.name as widget_name',
                'users.name as user_name',
                'users.email',
                DB::raw('SUM(chat_messages.tokens_used) as total_tokens'),
                DB::raw('COUNT(chat_messages.id) as total_messages')
            )
            ->groupBy('widgets.id', 'widgets.name', 'users.name', 'users.email')
            ->orderByDesc('total_tokens')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                // Crude cost estimation
                $item->estimated_cost = ($item->total_tokens / 1000000) * 1.0; // Assume avg $1/1M tokens
                return $item;
            });
    }

    public function render()
    {
        return view('livewire.admin.billing-monitoring')
            ->extends('layouts.dashboard')
            ->section('content');
    }
}
