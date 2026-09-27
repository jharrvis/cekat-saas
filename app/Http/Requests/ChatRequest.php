<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ChatRequest extends FormRequest
{
    /**
     * Public widget endpoint; quota/suspend/domain gates live in ChatOrchestrator.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => 'required|string|max:2000',
            'widgetId' => 'nullable|string',
            'history' => 'nullable|array',
            'sessionId' => 'nullable|string',
        ];
    }

    /**
     * Validated input with orchestrator defaults applied.
     *
     * @return array{message:string,widgetSlug:string,history:array,sessionId:string}
     */
    public function chatInput(): array
    {
        $validated = $this->validated();

        return [
            'message' => $validated['message'],
            'widgetSlug' => $validated['widgetId'] ?? 'default',
            'history' => $validated['history'] ?? [],
            'sessionId' => $validated['sessionId'] ?? 'sess_'.Str::random(16),
        ];
    }
}
