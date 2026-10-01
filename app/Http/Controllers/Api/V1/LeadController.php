<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\Api\LeadQueryService;
use Illuminate\Http\Request;

/**
 * GET /api/v1/leads, GET /api/v1/leads/{id}
 */
class LeadController extends V1Controller
{
    public function __construct(private LeadQueryService $leads)
    {
    }

    public function index(Request $request)
    {
        $query = $this->leads->leadsFor($request->user())->with('widget');

        if ($widget = $request->integer('widget_id')) {
            $query->where('widget_id', $widget);
        }

        if ($search = trim((string) $request->query('search'))) {
            $needle = '%' . $search . '%';
            $query->where(function ($q) use ($needle) {
                $q->where('visitor_name', 'like', $needle)
                    ->orWhere('visitor_email', 'like', $needle);
            });
        }

        $this->applyDateFilters($query, $request);

        return $this->cursorPaginate($query, $request, fn ($session) => $this->sessionResource($session));
    }

    public function show(Request $request, int $id)
    {
        $session = $this->leads->leadsFor($request->user())
            ->with('widget')
            ->whereKey($id)
            ->first();

        if (! $session) {
            return $this->notFound('Lead');
        }

        return response()->json([
            'data' => $this->sessionResource($session, withSummary: true),
        ]);
    }
}
