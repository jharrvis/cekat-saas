<?php

namespace App\Services\Chat;

use App\Models\ChatSession;
use App\Models\Widget;
use App\Support\HttpClientIp;
use Illuminate\Support\Str;

/**
 * Mints and validates tamper-proof widget session ids.
 *
 * Session ids are persisted as chat_sessions.visitor_uuid and are fully
 * client-supplied. Unsigned, guessable ids (legacy "sess_" + random) let
 * anyone continue or pollute another visitor's conversation, so every id
 * issued now carries an HMAC signature over the application key, and an
 * existing session is only continued when the client fingerprint
 * (IP + user agent) still matches the one recorded at creation.
 */
class SessionIdService
{
    public function mint(): string
    {
        $id = 'sess_'.Str::random(24);

        return $id.'.'.$this->signature($id);
    }

    public function isSigned(string $sessionId): bool
    {
        $dot = strrpos($sessionId, '.');
        if ($dot === false) {
            return false;
        }

        return hash_equals(
            $this->signature(substr($sessionId, 0, $dot)),
            substr($sessionId, $dot + 1),
        );
    }

    /**
     * Returns the session id to use for this turn: unchanged when the
     * stored session still belongs to this client, otherwise a fresh
     * signed id (old conversation is not continued).
     */
    public function bindFingerprint(Widget $widget, string $sessionId): string
    {
        $session = ChatSession::query()
            ->where('widget_id', $widget->id)
            ->where('visitor_uuid', $sessionId)
            ->first();

        if (! $session) {
            return $sessionId;
        }

        if ($session->ip_address === HttpClientIp::get()
            && $session->user_agent === request()->userAgent()) {
            return $sessionId;
        }

        return $this->mint();
    }

    protected function signature(string $payload): string
    {
        return hash_hmac('sha256', $payload, (string) config('app.key'));
    }
}
