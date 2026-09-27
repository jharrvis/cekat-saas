<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChatRequest;
use App\Services\Chat\ChatOrchestrator;

class ChatController extends Controller
{
    public function __construct(protected ChatOrchestrator $chats) {}

    public function chat(ChatRequest $request)
    {
        $input = $request->chatInput();

        $result = $this->chats->handle(
            message: $input['message'],
            widgetSlug: $input['widgetSlug'],
            history: $input['history'],
            sessionId: $input['sessionId'],
        );

        return response()->json($result['body'], $result['status']);
    }
}
