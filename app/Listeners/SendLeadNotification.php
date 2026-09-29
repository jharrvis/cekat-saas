<?php

namespace App\Listeners;

use App\Events\LeadCaptured;
use App\Mail\NewLead;
use App\Models\ChatSession;
use App\Models\Widget;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Persists a captured lead onto its chat session (so it shows up in
 * Lead Collection) and emails the widget owner for follow-up.
 * Logs never contain lead PII.
 */
class SendLeadNotification
{
    public function handle(LeadCaptured $e): void
    {
        $widget = Widget::where('slug', $e->widgetSlug)->first();

        if (! $widget) {
            return;
        }

        $session = null;

        if ($e->sessionId) {
            // Orchestrator mints the id into chat_sessions.visitor_uuid
            // (chat_sessions.session_id is a nullable legacy column).
            $session = ChatSession::where('widget_id', $widget->id)
                ->where('visitor_uuid', $e->sessionId)
                ->first();
        }

        $alreadyLead = (bool) $session?->is_lead;

        if ($session && ! $alreadyLead) {
            $session->is_lead = true;

            if ($e->lead['name'] ?? null) {
                $session->visitor_name = $e->lead['name'];
            }
            if ($e->lead['email'] ?? null) {
                $session->visitor_email = $e->lead['email'];
            }
            if ($e->lead['phone'] ?? null) {
                $session->visitor_phone = $e->lead['phone'];
            }

            $session->save();
        }

        // One notification per session; Lead Collection is a paid feature.
        if ($alreadyLead) {
            return;
        }

        $owner = $widget->user;

        if (! $owner || ! $owner->email || ! $owner->canUseLeads()) {
            return;
        }

        try {
            Mail::to($owner->email)->send(new NewLead($owner, $widget, $session, $e->lead));
        } catch (\Throwable $ex) {
            Log::error('Failed to send lead notification', [
                'widget' => $e->widgetSlug,
                'user_id' => $owner->id,
                'error' => $ex->getMessage(),
            ]);
        }
    }
}
