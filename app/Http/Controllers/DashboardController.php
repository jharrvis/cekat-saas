<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Models\Widget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        // Topics are now handled by Livewire component

        // User's widgets for quick access
        $widgets = $user->widgets()->withCount('chatSessions')->get();

        // Usage quota from plan
        $plan = $user->plan;
        $quotaLimit = $plan ? ($plan->max_messages_per_month ?? 100) : 100;
        $usedMessages = $user->monthly_message_used ?? 0;
        $usagePercent = $quotaLimit > 0
            ? round(($usedMessages / $quotaLimit) * 100)
            : 0;
        $quotaRemaining = max(0, $quotaLimit - $usedMessages);

        // Warning levels
        $quotaWarningLevel = 'normal';
        if ($usagePercent >= 100) {
            $quotaWarningLevel = 'exceeded';
        } elseif ($usagePercent >= 90) {
            $quotaWarningLevel = 'critical';
        } elseif ($usagePercent >= 75) {
            $quotaWarningLevel = 'warning';
        }

        // Recent Conversations (5 terbaru)
        $recentConversations = $this->getRecentConversations($widgetIds);

        // Peak Hours (jam tersibuk chat)
        $peakHours = $this->getPeakHours($widgetIds);

        // Hot Sessions (dengan pesan terbanyak)
        $hotSessions = $this->getHotSessions($widgetIds);

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
            'widgets',
            'usagePercent',
            'quotaLimit',
            'usedMessages',
            'quotaRemaining',
            'quotaWarningLevel',
            'recentConversations',
            'peakHours',
            'hotSessions'
        ));
    }

    /**
     * Get recent conversations with preview and summary
     */
    private function getRecentConversations($widgetIds)
    {
        return ChatSession::whereIn('widget_id', $widgetIds)
            ->with([
                'widget:id,name,display_name',
                'messages' => function ($q) {
                    $q->orderBy('created_at', 'asc');
                }
            ])
            ->withCount('messages')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($session) {
                $messages = $session->messages;
                $userMessages = $messages->where('role', 'user');
                $firstMessage = $userMessages->first();
                $lastMessage = $messages->last();

                // Extract key topics from user messages (simple keyword extraction)
                $topics = $this->extractTopicsFromMessages($userMessages);

                // Calculate duration
                $duration = null;
                if ($session->ended_at && $session->started_at) {
                    $diffMinutes = $session->started_at->diffInMinutes($session->ended_at);
                    $duration = $diffMinutes < 60
                        ? $diffMinutes . ' menit'
                        : round($diffMinutes / 60, 1) . ' jam';
                } elseif ($messages->count() > 1) {
                    $diffMinutes = $messages->first()->created_at->diffInMinutes($messages->last()->created_at);
                    $duration = $diffMinutes < 60
                        ? $diffMinutes . ' menit'
                        : round($diffMinutes / 60, 1) . ' jam';
                }

                // Determine status
                $status = 'active';
                if ($session->is_converted || $session->is_lead) {
                    $status = 'converted';
                } elseif ($session->ended_at) {
                    $status = 'ended';
                } elseif ($session->created_at->diffInHours(now()) > 24) {
                    $status = 'inactive';
                }

                return [
                    'id' => $session->id,
                    'widget_name' => $session->widget->display_name ?? $session->widget->name ?? 'Widget',
                    'visitor_name' => $session->visitor_name ?? 'Visitor',
                    'visitor_email' => $session->visitor_email,
                    'first_message' => $firstMessage
                        ? \Illuminate\Support\Str::limit($firstMessage->content, 60)
                        : null,
                    'last_message' => $lastMessage
                        ? \Illuminate\Support\Str::limit($lastMessage->content, 40)
                        : null,
                    'topics' => $topics,
                    'message_count' => $session->messages_count,
                    'is_lead' => $session->is_lead ?? false,
                    'status' => $status,
                    'duration' => $duration,
                    'created_at' => $session->created_at,
                    'time_ago' => $session->created_at->diffForHumans(),
                ];
            });
    }

    /**
     * Extract simple topics/keywords from messages
     */
    private function extractTopicsFromMessages($messages)
    {
        $stopWords = [
            'yang',
            'dan',
            'di',
            'ini',
            'itu',
            'untuk',
            'ada',
            'bisa',
            'saya',
            'anda',
            'mau',
            'ingin',
            'apa',
            'bagaimana',
            'halo',
            'hai',
            'ok',
            'oke',
            'baik',
            'terima',
            'kasih'
        ];

        $wordCounts = [];

        foreach ($messages as $msg) {
            $text = strtolower($msg->content);
            $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
            $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

            foreach ($words as $word) {
                if (strlen($word) >= 4 && !in_array($word, $stopWords)) {
                    $wordCounts[$word] = ($wordCounts[$word] ?? 0) + 1;
                }
            }
        }

        arsort($wordCounts);
        return array_slice(array_keys($wordCounts), 0, 3);
    }

    /**
     * Get peak hours (jam tersibuk chat)
     */
    private function getPeakHours($widgetIds)
    {
        $sessions = ChatSession::whereIn('widget_id', $widgetIds)
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->orderBy('count', 'desc')
            ->limit(24)
            ->get();

        if ($sessions->isEmpty()) {
            return [
                'peak_hour' => null,
                'peak_count' => 0,
                'hours' => [],
            ];
        }

        // Get top 3 busiest hours
        $topHours = $sessions->take(3)->map(function ($item) {
            $hour = (int) $item->hour;
            return [
                'hour' => $hour,
                'formatted' => str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00',
                'count' => $item->count,
            ];
        })->toArray();

        return [
            'peak_hour' => $topHours[0]['hour'] ?? null,
            'peak_formatted' => $topHours[0]['formatted'] ?? '-',
            'peak_count' => $topHours[0]['count'] ?? 0,
            'hours' => $topHours,
        ];
    }

    /**
     * Get hot sessions (most active conversations)
     */
    private function getHotSessions($widgetIds)
    {
        return ChatSession::whereIn('widget_id', $widgetIds)
            ->with('widget:id,name,display_name')
            ->withCount('messages')
            ->orderBy('messages_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'widget_name' => $session->widget->display_name ?? $session->widget->name ?? 'Widget',
                    'visitor_name' => $session->visitor_name ?? 'Visitor',
                    'message_count' => $session->messages_count,
                    'is_lead' => $session->is_lead ?? false,
                    'created_at' => $session->created_at->format('d M'),
                ];
            });
    }
}
