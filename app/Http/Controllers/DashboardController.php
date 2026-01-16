<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Models\Widget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $widgetIds = $user->widgets()->pluck('id');

        // Get date range for last 7 days
        $startDate = Carbon::now()->subDays(6)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        // Total conversations
        $totalConversations = ChatSession::whereIn('widget_id', $widgetIds)->count();

        // Conversations this month
        $conversationsThisMonth = ChatSession::whereIn('widget_id', $widgetIds)
            ->whereMonth('created_at', Carbon::now()->month)
            ->count();

        // Conversations last month (for comparison)
        $conversationsLastMonth = ChatSession::whereIn('widget_id', $widgetIds)
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->count();

        // Calculate percentage change
        $conversationChange = $conversationsLastMonth > 0
            ? round((($conversationsThisMonth - $conversationsLastMonth) / $conversationsLastMonth) * 100)
            : 0;

        // Total messages answered by AI
        $totalAiMessages = ChatMessage::whereHas('session', function ($q) use ($widgetIds) {
            $q->whereIn('widget_id', $widgetIds);
        })->where('role', 'assistant')->count();

        $totalUserMessages = ChatMessage::whereHas('session', function ($q) use ($widgetIds) {
            $q->whereIn('widget_id', $widgetIds);
        })->where('role', 'user')->count();

        // Automation rate
        $automationRate = $totalUserMessages > 0
            ? round(($totalAiMessages / $totalUserMessages) * 100)
            : 0;

        // Sessions needing human intervention (ended without conversion or abandoned)
        $needsHuman = ChatSession::whereIn('widget_id', $widgetIds)
            ->where('is_converted', false)
            ->whereNotNull('ended_at')
            ->count();

        // Lead conversion rate
        $totalLeads = ChatSession::whereIn('widget_id', $widgetIds)
            ->where('is_lead', true)
            ->count();

        $leadConversionRate = $totalConversations > 0
            ? round(($totalLeads / $totalConversations) * 100, 1)
            : 0;

        // Chat activity for last 7 days
        $chatActivity = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = ChatSession::whereIn('widget_id', $widgetIds)
                ->whereDate('created_at', $date)
                ->count();
            $chatActivity[] = [
                'label' => $date->format('D'),
                'count' => $count,
            ];
        }

        // Top topics (from AI messages, simple word frequency)
        $topics = $this->getTopTopics($widgetIds);

        // User's widgets for quick access
        $widgets = $user->widgets()->withCount('chatSessions')->get();

        // Usage quota
        $usagePercent = $user->monthly_message_quota > 0
            ? round(($user->monthly_message_used / $user->monthly_message_quota) * 100)
            : 0;

        return view('user.dashboard', compact(
            'user',
            'totalConversations',
            'conversationChange',
            'totalAiMessages',
            'automationRate',
            'needsHuman',
            'leadConversionRate',
            'totalLeads',
            'chatActivity',
            'topics',
            'widgets',
            'usagePercent'
        ));
    }

    private function getTopTopics($widgetIds)
    {
        // Get recent user messages to analyze topics
        $messages = ChatMessage::whereHas('session', function ($q) use ($widgetIds) {
            $q->whereIn('widget_id', $widgetIds);
        })
            ->where('role', 'user')
            ->latest()
            ->limit(100)
            ->pluck('content');

        // Simple keyword extraction (can be improved with NLP)
        $keywords = [
            'harga' => 0,
            'produk' => 0,
            'pengiriman' => 0,
            'retur' => 0,
            'pembayaran' => 0,
            'promo' => 0,
            'garansi' => 0,
            'stok' => 0,
        ];

        foreach ($messages as $msg) {
            $lowerMsg = strtolower($msg);
            foreach ($keywords as $key => $count) {
                if (str_contains($lowerMsg, $key)) {
                    $keywords[$key]++;
                }
            }
        }

        // Sort by count and get top 4
        arsort($keywords);
        $total = array_sum($keywords) ?: 1;

        $topics = [];
        $i = 0;
        foreach ($keywords as $topic => $count) {
            if ($i >= 4)
                break;
            $topics[] = [
                'name' => ucfirst($topic),
                'count' => $count,
                'percent' => round(($count / $total) * 100),
            ];
            $i++;
        }

        return $topics;
    }
}
