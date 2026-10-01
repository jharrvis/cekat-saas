<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Widget;
use Illuminate\Http\Request;

/**
 * GET /api/v1/widgets
 */
class WidgetController extends V1Controller
{
    public function index(Request $request)
    {
        $widgets = Widget::query()
            ->whereIn('user_id', [$request->user()->id])
            ->orderBy('id')
            ->get()
            ->map(fn (Widget $widget) => [
                'id' => $widget->id,
                'slug' => $widget->slug,
                'name' => $widget->display_name ?: $widget->name,
                'status' => $widget->status,
                'is_active' => (bool) $widget->is_active,
                'created_at' => $widget->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'data' => $widgets,
            'meta' => ['count' => $widgets->count()],
        ]);
    }
}
