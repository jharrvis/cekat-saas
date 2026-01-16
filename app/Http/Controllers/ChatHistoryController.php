<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ChatHistoryController extends Controller
{
    /**
     * Display list of chat sessions
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $widgetIds = $user->widgets()->pluck('id');

        $query = ChatSession::whereIn('widget_id', $widgetIds)
            ->with(['widget', 'messages'])
            ->withCount('messages');

        // Filter by widget
        if ($request->widget) {
            $query->where('widget_id', $request->widget);
        }

        // Filter by status (has lead or not)
        if ($request->status === 'has_lead') {
            $query->whereNotNull('visitor_name');
        } elseif ($request->status === 'no_lead') {
            $query->whereNull('visitor_name');
        }

        // Search by customer name
        if ($request->search) {
            $query->where('visitor_name', 'like', '%' . $request->search . '%');
        }

        $sessions = $query->orderBy('created_at', 'desc')->paginate(20);

        // Stats
        $stats = [
            'total' => ChatSession::whereIn('widget_id', $widgetIds)->count(),
            'this_month' => ChatSession::whereIn('widget_id', $widgetIds)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'leads' => ChatSession::whereIn('widget_id', $widgetIds)
                ->whereNotNull('visitor_name')
                ->count(),
            'avg_messages' => ChatSession::whereIn('widget_id', $widgetIds)
                ->withCount('messages')
                ->get()
                ->avg('messages_count') ?? 0,
        ];

        $widgets = $user->widgets;

        return view('user.chats.index', compact('sessions', 'stats', 'widgets'));
    }

    /**
     * Display a specific chat session
     */
    public function show($id)
    {
        $user = auth()->user();
        $widgetIds = $user->widgets()->pluck('id');

        $session = ChatSession::whereIn('widget_id', $widgetIds)
            ->with([
                    'widget',
                    'messages' => function ($q) {
                        $q->orderBy('created_at', 'asc');
                    }
                ])
            ->findOrFail($id);

        return view('user.chats.show', compact('session'));
    }

    /**
     * Generate AI summary for a chat session
     */
    public function generateSummary($id)
    {
        $user = auth()->user();
        $widgetIds = $user->widgets()->pluck('id');

        $session = ChatSession::whereIn('widget_id', $widgetIds)
            ->with('messages')
            ->findOrFail($id);

        // Dispatch job to generate summary
        \App\Jobs\GenerateChatSummary::dispatch($session);

        return redirect()->back()->with('success', 'Summary sedang di-generate. Refresh halaman dalam beberapa detik.');
    }

    /**
     * Export chat sessions to CSV
     */
    public function export(Request $request)
    {
        $user = auth()->user();
        $widgetIds = $user->widgets()->pluck('id');

        $sessions = ChatSession::whereIn('widget_id', $widgetIds)
            ->with(['widget', 'messages'])
            ->withCount('messages')
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'chat-history-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($sessions) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, ['Widget', 'Customer Name', 'Email', 'Phone', 'Messages', 'Date', 'Summary']);

            foreach ($sessions as $session) {
                fputcsv($file, [
                    $session->widget->display_name ?? 'Unknown',
                    $session->visitor_name ?? 'Anonymous',
                    $session->visitor_email ?? '',
                    $session->visitor_phone ?? '',
                    $session->messages_count,
                    $session->created_at->format('Y-m-d H:i:s'),
                    $session->summary ?? '',
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
