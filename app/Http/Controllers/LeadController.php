<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class LeadController extends Controller
{
    /**
     * Display list of leads (chat sessions with customer info)
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $widgetIds = $user->widgets()->pluck('id');

        $query = ChatSession::whereIn('widget_id', $widgetIds)
            ->whereNotNull('visitor_name')
            ->with('widget');

        // Filter by widget
        if ($request->widget) {
            $query->where('widget_id', $request->widget);
        }

        // Search
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('visitor_name', 'like', '%' . $request->search . '%')
                    ->orWhere('visitor_email', 'like', '%' . $request->search . '%');
            });
        }

        $leads = $query->orderBy('created_at', 'desc')->paginate(20);

        // Stats
        $totalSessions = ChatSession::whereIn('widget_id', $widgetIds)->count();
        $totalLeads = ChatSession::whereIn('widget_id', $widgetIds)->whereNotNull('visitor_name')->count();

        $stats = [
            'total' => $totalLeads,
            'this_month' => ChatSession::whereIn('widget_id', $widgetIds)
                ->whereNotNull('visitor_name')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'this_week' => ChatSession::whereIn('widget_id', $widgetIds)
                ->whereNotNull('visitor_name')
                ->where('created_at', '>=', now()->startOfWeek())
                ->count(),
            'conversion_rate' => $totalSessions > 0 ? ($totalLeads / $totalSessions) * 100 : 0,
        ];

        $widgets = $user->widgets;

        return view('user.leads.index', compact('leads', 'stats', 'widgets'));
    }

    /**
     * Export leads to CSV
     */
    public function export(Request $request)
    {
        $user = auth()->user();
        $widgetIds = $user->widgets()->pluck('id');

        $leads = ChatSession::whereIn('widget_id', $widgetIds)
            ->whereNotNull('visitor_name')
            ->with('widget')
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'leads-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($leads) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, ['Name', 'Email', 'Phone', 'Widget', 'Date', 'Session ID']);

            foreach ($leads as $lead) {
                fputcsv($file, [
                    $lead->visitor_name ?? '',
                    $lead->visitor_email ?? '',
                    $lead->visitor_phone ?? '',
                    $lead->widget->display_name ?? 'Unknown',
                    $lead->created_at->format('Y-m-d H:i:s'),
                    $lead->session_id ?? $lead->id,
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
