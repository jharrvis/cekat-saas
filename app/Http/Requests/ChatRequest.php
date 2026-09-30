<?php

namespace App\Http\Requests;

use App\Services\Chat\SessionIdService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ChatRequest extends FormRequest
{
    /**
     * Public widget endpoint; quota/suspend/domain gates live in ChatOrchestrator.
     *
     * The origin check runs first: browsers always attach Origin (POST) or
     * Referer, so header-less clients (curl, scripts) - the audit's
     * exfiltration path - are rejected before any work happens.
     */
    public function authorize(): bool
    {
        return $this->browserOrigin() !== null;
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => 'Origin header required',
            'error_code' => 'origin_required',
        ], 403));
    }

    public function rules(): array
    {
        return [
            'message' => 'required|string|max:2000',
            'widgetId' => 'nullable|string|max:100',
            // Bounded history: at most 50 items, each capped - a single
            // request can no longer ship an unbounded transcript.
            'history' => 'nullable|array|max:50',
            'history.*.role' => 'required|string|in:user,assistant',
            'history.*.content' => 'required|string|max:10000',
            'sessionId' => 'nullable|string|max:200',
            // Page the visitor chats on + where they came from (old widgets
            // omit both - nullable keeps them compatible).
            'pageUrl' => 'nullable|string|max:500',
            'referrerUrl' => 'nullable|string|max:500',
            // Pre-chat form (Strategy 3): the visitor's details captured
            // before the first message. Sent once with that message.
            'leadForm' => 'nullable|array',
            'leadForm.name' => 'nullable|string|max:120',
            'leadForm.email' => 'nullable|string|email|max:190',
            'leadForm.phone' => 'nullable|string|max:30',
        ];
    }

    /**
     * Validated input with orchestrator defaults applied.
     *
     * Session ids are minted server-side with an HMAC signature; unsigned
     * ids coming from old clients (or a forged id) are replaced, so
     * visitors can never address another visitor's ChatSession row.
     *
     * @return array{message:string,widgetSlug:string,history:array,sessionId:string}
     */
    public function chatInput(): array
    {
        $validated = $this->validated();

        $sessions = app(SessionIdService::class);
        $sessionId = (string) ($validated['sessionId'] ?? '');

        if ($sessionId === '' || ! $sessions->isSigned($sessionId)) {
            $sessionId = $sessions->mint();
        }

        return [
            'message' => $validated['message'],
            'widgetSlug' => $validated['widgetId'] ?? 'default',
            'history' => $validated['history'] ?? [],
            'sessionId' => $sessionId,
            'pageUrl' => $validated['pageUrl'] ?? '',
            'referrerUrl' => $validated['referrerUrl'] ?? '',
            'leadForm' => $validated['leadForm'] ?? null,
        ];
    }

    protected function browserOrigin(): ?string
    {
        $origin = $this->header('Origin') ?: $this->header('Referer');

        return ($origin !== null && $origin !== '') ? $origin : null;
    }
}
