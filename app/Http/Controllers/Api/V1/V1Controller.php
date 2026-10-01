<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use App\Support\VisitorGeo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Shared helpers for the public read API (/api/v1): cursor pagination
 * (id-descending, stable for new rows arriving), uniform 404s, and the
 * full session/lead resource shape (contact, AI summary, device, coarse
 * location, ip, page/referrer) - all owned by the key's user.
 */
abstract class V1Controller extends Controller
{
    protected function cursorPaginate(Builder $query, Request $request, callable $map): JsonResponse
    {
        $limit = min(max((int) $request->integer('limit', 50), 1), 100);
        $cursor = $request->integer('cursor', 0);

        if ($cursor > 0) {
            $query->where('id', '<', $cursor);
        }

        $rows = $query->orderByDesc('id')->limit($limit + 1)->get();
        $nextCursor = $rows->count() > $limit ? $rows[$limit - 1]->id : null;
        $rows = $rows->slice(0, $limit)->values();

        return response()->json([
            'data' => $rows->map($map),
            'meta' => [
                'per_page' => $limit,
                'next_cursor' => $nextCursor,
            ],
        ]);
    }

    protected function notFound(string $what = 'Data'): JsonResponse
    {
        return response()->json([
            'error' => 'not_found',
            'error_code' => 'not_found',
            "message" => "{$what} tidak ditemukan atau bukan milik Anda.",
        ], 404);
    }

    protected function invalidParam(string $field, string $hint): JsonResponse
    {
        return response()->json([
            'error' => 'invalid_param',
            'error_code' => 'invalid_param',
            'message' => "Parameter {$field} tidak valid. {$hint}",
        ], 400);
    }

    /**
     * widget_id query filter: accepts a numeric widget ID or a widget slug
     * (as returned in the `widget.slug` resource field). Returns null when
     * the parameter is absent, an int when resolved, or a 400 JsonResponse
     * when the value matches neither - never silently ignored.
     */
    protected function resolveWidgetId(Request $request): int|JsonResponse|null
    {
        $raw = trim((string) $request->query('widget_id', ''));
        if ($raw === '') {
            return null;
        }

        if (ctype_digit($raw)) {
            return (int) $raw;
        }

        $widget = $request->user()->widgets()->where('slug', $raw)->first();
        if (! $widget) {
            return $this->invalidParam('widget_id', 'Harus ID numerik atau slug widget yang valid.');
        }

        return (int) $widget->id;
    }

    /**
     * Common date-range filters (created_at) shared by leads/sessions.
     */
    protected function applyDateFilters(Builder $query, Request $request): void
    {
        if ($since = $request->query('since')) {
            $query->where('created_at', '>=', $since);
        }
        if ($until = $request->query('until')) {
            $query->where('created_at', '<=', $until);
        }
    }

    protected function sessionResource(ChatSession $session): array
    {
        return [
            'id' => $session->id,
            'name' => $session->visitor_name,
            'email' => $session->visitor_email,
            'phone' => $session->visitor_phone,
            'is_lead' => (bool) $session->is_lead,
            'is_converted' => (bool) $session->is_converted,
            'status' => $session->status,
            'summary' => $session->summary,
            'summary_generated_at' => $session->summary_generated_at?->toIso8601String(),
            'source_url' => $session->source_url,
            'referer_url' => $session->referer_url,
            'ip_address' => $session->ip_address,
            'device' => [
                'type' => $session->device_type,
                'label' => VisitorGeo::describeAgent($session->user_agent),
                'user_agent' => $session->user_agent,
            ],
            'location' => $session->location_data,
            'widget' => [
                'id' => $session->widget_id,
                'slug' => $session->widget?->slug,
                'name' => $session->widget?->display_name ?: $session->widget?->name,
            ],
            'started_at' => $session->started_at?->toIso8601String(),
            'ended_at' => $session->ended_at?->toIso8601String(),
            'created_at' => $session->created_at?->toIso8601String(),
            'updated_at' => $session->updated_at?->toIso8601String(),
        ];
    }
}
