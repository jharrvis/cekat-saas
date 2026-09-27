<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Chat\ChatOrchestrator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function __construct(protected ChatOrchestrator $chats) {}

    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'widgetId' => 'nullable|string',
            'history' => 'nullable|array',
            'sessionId' => 'nullable|string',
        ]);

        $result = $this->chats->handle(
            message: $request->input('message'),
            widgetSlug: $request->input('widgetId', 'default'),
            history: $request->input('history', []),
            sessionId: $request->input('sessionId', 'sess_'.Str::random(16)),
        );

        return response()->json($result['body'], $result['status']);
    }
}
