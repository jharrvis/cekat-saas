<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\Api\LeadQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/v1/sessions, GET /api/v1/sessions/{id}/messages
 */
class SessionController extends V1Controller
{
    public function __construct(private LeadQueryService $sessions)
    {
    }

    public function index(Request $request)
    {
        $query = $this->sessions->sessionsFor($request->user())->with('widget');

        if (($widgetId = $this->resolveWidgetId($request)) instanceof JsonResponse) {
            return $widgetId;
        }
        if ($widgetId !== null) {
            $query->where('widget_id', $widgetId);
        }

        if ($request->has('is_lead')) {
            $query->where('is_lead', $request->boolean('is_lead'));
        }

        $this->applyDateFilters($query, $request);

        return $this->cursorPaginate($query, $request, fn ($session) => $this->sessionResource($session));
    }

    public function messages(Request $request, int $id)
    {
        $session = $this->sessions->sessionsFor($request->user())->whereKey($id)->first();

        if (! $session) {
            return $this->notFound('Session');
        }

        $limit = min(max((int) $request->integer('limit', 100), 1), 200);
        $messages = $session->messages()->orderBy('id')->limit($limit)->get();

        return response()->json([
            'data' => $messages->map(fn ($message) => [
                'id' => $message->id,
                'role' => $message->role,
                'content' => $message->content,
                'created_at' => $message->created_at?->toIso8601String(),
            ]),
            'meta' => [
                'session_id' => $session->id,
                'per_page' => $limit,
            ],
        ]);
    }
}
