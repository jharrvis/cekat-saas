<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\Api\LeadQueryService;
use Illuminate\Http\Request;

/**
 * GET /api/v1/stats
 */
class StatsController extends V1Controller
{
    public function __construct(private LeadQueryService $leads)
    {
    }

    public function index(Request $request)
    {
        return response()->json([
            'data' => $this->leads->statsFor($request->user()),
        ]);
    }
}
