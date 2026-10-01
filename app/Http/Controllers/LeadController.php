<?php

namespace App\Http\Controllers;

use App\Services\Api\LeadQueryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class LeadController extends Controller
{
    /**
     * Display list of leads (chat sessions with customer info)
     */
    public function index(Request $request, LeadQueryService $leads)
    {
        $user = auth()->user();

        // A lead counts as soon as ANY contact detail was captured (name
        // alone is optional — email/phone-only leads still must show up).
        $query = $leads->leadsFor($user)->with('widget');

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

        $leadsPage = $query->orderBy('created_at', 'desc')->paginate(20);

        $stats = $leads->statsFor($user);

        $widgets = $user->widgets;

        return view('user.leads.index', compact('leadsPage', 'stats', 'widgets'))
            ->with('leads', $leadsPage);
    }

    /**
     * Export leads to CSV
     */
    public function export(Request $request, LeadQueryService $leads)
    {
        $user = auth()->user();

        $rows = $leads->leadsFor($user)
            ->with('widget')
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'leads-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($rows) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, ['Name', 'Email', 'Phone', 'Widget', 'Date', 'Session ID']);

            foreach ($rows as $lead) {
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
