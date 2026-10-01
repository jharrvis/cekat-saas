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
 * Lead Collection) and emails the widget owner for follow-up - or the
 * channel's dedicated notification address when configured in the
 * Lead tab. Logs never contain lead PII.
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

        if ($session) {
            $session->is_lead = true;

            // Merge on EVERY capture - not only the first one. A visitor
            // typically reveals data in pieces (phone first, email later);
            // the first capture must not lock out the rest, or the lead
            // row would keep a partial name/email forever.
            foreach (['name' => 'visitor_name', 'email' => 'visitor_email', 'phone' => 'visitor_phone'] as $key => $column) {
                $value = $e->lead[$key] ?? null;

                if ($value !== null && trim((string) $value) !== '' && $session->{$column} !== $value) {
                    $session->{$column} = $value;
                }
            }

            $session->save();
        }

        // One notification per session; Lead Collection is a paid feature.
        // The email always carries whatever the session row holds at this
        // moment; completing the data later only updates Lead Collection.
        if ($alreadyLead) {
            return;
        }

        $owner = $widget->user;

        if (! $owner || ! $owner->email || ! $owner->canUseLeads()) {
            return;
        }

        // Per-channel email notification (Lead tab). Opt-out checkbox is
        // only stored when the toggle is on; off = legacy owner email.
        $settings = (array) $widget->settings;

        if (! ($settings['lead_email_new_lead'] ?? true)) {
            return;
        }

        $recipient = $owner->email;

        if (! empty($settings['lead_email_notif_enabled'])) {
            $custom = trim((string) ($settings['lead_email_notif'] ?? ''));

            if ($custom !== '' && filter_var($custom, FILTER_VALIDATE_EMAIL)) {
                $recipient = $custom;
            }
        }

        $send = function () use ($recipient, $owner, $widget, $session, $e) {
            Mail::to($recipient)->send(new NewLead($owner, $widget, $session, $e->lead));
        };

        // The owner expects the conversation summary inside the email.
        // Generate it first, but only after the chat response is out -
        // the visitor must never wait for the summary LLM call. Falls
        // back to the excerpt built into the view when generation fails.
        if ($session && ! $session->summary && $session->messages()->exists()) {
            app()->terminating(function () use ($session, $send, $e, $owner) {
                // The geo lookup registers its own terminating callback before
                // this one (ChatOrchestrator::persistConversation), so a refresh
                // here picks up location_data resolved moments earlier.
                try {
                    $session->refresh();
                } catch (\Throwable $ex) {
                    // Row vanished mid-flight; render with the instance we hold.
                }

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
