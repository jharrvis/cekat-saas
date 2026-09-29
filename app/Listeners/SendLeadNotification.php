<?php

namespace App\Listeners;

use App\Events\LeadCaptured;
use App\Jobs\GenerateChatSummary;
use App\Mail\NewLead;
use App\Models\ChatSession;
use App\Models\User;
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

        $send = function () use ($owner, $widget, $session, $e) {
            Mail::to($owner->email)->send(new NewLead($owner, $widget, $session, $e->lead));
        };

        // The owner expects the conversation summary inside the email.
        // Generate it first, but only after the chat response is out -
        // the visitor must never wait for the summary LLM call. Falls
        // back to the excerpt built into the view when generation fails.
        if ($session && ! $session->summary && $session->messages()->exists()) {
            app()->terminating(function () use ($session, $send, $e, $owner) {
                try {
                    GenerateChatSummary::dispatchSync($session);
                    $session->refresh();
                } catch (\Throwable $ex) {
                    Log::warning('Lead notification summary skipped', [
                        'widget' => $e->widgetSlug,
                        'user_id' => $owner->id,
                        'error' => $ex->getMessage(),
                    ]);
                }

                try {
                    $send();
                } catch (\Throwable $ex) {
                    $this->logSendFailure($e, $owner, $ex);
                }
            });

            return;
        }

        try {
            $send();
        } catch (\Throwable $ex) {
            $this->logSendFailure($e, $owner, $ex);
        }
    }

    private function logSendFailure(LeadCaptured $e, User $owner, \Throwable $ex): void
    {
        Log::error('Failed to send lead notification', [
            'widget' => $e->widgetSlug,
            'user_id' => $owner->id,
            'error' => $ex->getMessage(),
        ]);
    }
}
