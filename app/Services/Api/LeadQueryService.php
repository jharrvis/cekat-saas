<?php

namespace App\Services\Api;

use App\Models\ChatSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared query scopes between the dashboard Lead pages and the public
 * read API (/api/v1). A "lead" is any chat session with at least one
 * captured contact detail (name/email/phone).
 */
class LeadQueryService
{
    /**
     * Chat sessions that carry at least one contact detail.
     * Wrapped in where(closure) by callers so it groups with other filters.
     */
    public function hasContact(): \Closure
    {
        return function ($q) {
            $q->whereNotNull('visitor_name')
                ->orWhereNotNull('visitor_email')
                ->orWhereNotNull('visitor_phone');
        };
    }

    /**
     * Leads (sessions with contact data) owned by the user.
     */
    public function leadsFor(User $user): Builder
    {
        return ChatSession::query()
            ->whereIn('widget_id', $user->widgets()->pluck('id'))
            ->where($this->hasContact());
    }

    /**
     * All chat sessions owned by the user.
     */
    public function sessionsFor(User $user): Builder
    {
        return ChatSession::query()
            ->whereIn('widget_id', $user->widgets()->pluck('id'));
    }

    /**
     * Dashboard lead stats (totals + conversion).
     *
     * @return array<string, float|int>
     */
    public function statsFor(User $user): array
    {
        $widgetIds = $user->widgets()->pluck('id');
        $hasContact = $this->hasContact();

        $totalSessions = ChatSession::whereIn('widget_id', $widgetIds)->count();
        $totalLeads = ChatSession::whereIn('widget_id', $widgetIds)->where($hasContact)->count();

        return [
            'total' => $totalLeads,
            'this_month' => ChatSession::whereIn('widget_id', $widgetIds)
                ->where($hasContact)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'this_week' => ChatSession::whereIn('widget_id', $widgetIds)
                ->where($hasContact)
                ->where('created_at', '>=', now()->startOfWeek())
                ->count(),
            'conversion_rate' => $totalSessions > 0 ? round(($totalLeads / $totalSessions) * 100, 1) : 0,
            'total_sessions' => $totalSessions,
        ];
    }
}
