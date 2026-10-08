<?php

namespace App\Listeners;

use App\Events\LeadCaptured;
use App\Models\ChatSession;
use App\Models\Setting;
use App\Models\User;
use App\Models\Widget;
use App\Models\WhatsAppDevice;
use App\Services\WhatsApp\FonnteService;
use App\Services\WhatsApp\PhoneNumber;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp counterpart of the lead email notification: when a visitor
 * becomes a lead, ping the WhatsApp number configured for the channel
 * (Lead tab) so the owner can follow up immediately.
 *
 * The message is sent from the platform notification device (admin
 * setting `whatsapp_lead_notif_device_token`) or, when that is empty,
 * from the owner's own connected device. One ping per chat session:
 * the dedupe claim is taken atomically when the send is registered and
 * released again when the send fails, so a later capture can retry.
 * Logs never contain lead PII (same rule as SendLeadNotification).
 */
class SendLeadWhatsAppNotification
{
    public function handle(LeadCaptured $e): void
    {
        if (! FonnteService::isEnabled()) {
            return;
        }

        $widget = Widget::where('slug', $e->widgetSlug)->first();

        if (! $widget) {
            return;
        }

        $owner = $widget->user;

        if (! $owner || ! $owner->canUseLeads()) {
            return;
        }

        $settings = (array) $widget->settings;

        if (! ($settings['lead_wa_notif_enabled'] ?? false)) {
            return;
        }

        if (! ($settings['lead_wa_new_lead'] ?? true)) {
            return;
        }

        $target = PhoneNumber::normalizeId((string) ($settings['lead_wa_notif'] ?? ''));

        if ($target === null) {
            return;
        }

        $session = null;

        if ($e->sessionId) {
            $session = ChatSession::where('widget_id', $widget->id)
                ->where('visitor_uuid', $e->sessionId)
                ->first();
        }

        // Independent of the email listener's is_lead flag: listener
        // execution order must not decide whether the ping goes out.
        // The flag is claimed atomically here (not in the deferred send)
        // so two captures inside one request lifecycle cannot both
        // register a send; a failed send releases the claim again.
        $dedupeKey = 'lead-wa-notified:' . ($session?->id ?? 'w' . $widget->id . '-' . md5($target));

        if (! Cache::add($dedupeKey, true, now()->addDays(30))) {
            return;
        }

        $deviceToken = $this->resolveDeviceToken($owner);

        if ($deviceToken === null) {
            Cache::forget($dedupeKey);

            return;
        }

        // The current event's values win over the stored session row:
        // this listener may run before SendLeadNotification persists.
        $lead = [
            'name' => $e->lead['name'] ?? $session?->visitor_name,
            'email' => $e->lead['email'] ?? $session?->visitor_email,
            'phone' => $e->lead['phone'] ?? $session?->visitor_phone,
        ];

        $message = $this->buildMessage($owner, $widget, $session, $lead);

        $send = function () use ($deviceToken, $target, $message, $dedupeKey, $e, $owner) {
            try {
                app(FonnteService::class)->sendMessage($deviceToken, $target, $message);
            } catch (\Throwable $ex) {
                // Release the claim so the session's next capture retries.
                Cache::forget($dedupeKey);

                Log::error('Failed to send lead WhatsApp notification', [
                    'widget' => $e->widgetSlug,
                    'user_id' => $owner->id,
                    'error' => $ex->getMessage(),
                ]);
            }
        };

        // Never let a Fonnte round-trip slow the visitor's chat response.
        app()->terminating($send);
    }

    /**
     * Platform notification device first (one consistent sender for all
     * tenants); the owner's own connected device is the fallback.
     */
    private function resolveDeviceToken(User $owner): ?string
    {
        $platform = trim((string) Setting::get('whatsapp_lead_notif_device_token', ''));

        if ($platform !== '') {
            return $platform;
        }

        $device = WhatsAppDevice::where('user_id', $owner->id)
            ->where('status', 'connected')
            ->latest('id')
            ->first();

        $token = trim((string) ($device?->fonnte_device_token ?? ''));

        return $token !== '' ? $token : null;
    }

    private function buildMessage(User $owner, Widget $widget, ?ChatSession $session, array $lead): string
    {
        $locale = $owner->locale ?: config('app.locale');

        $lines = [
            __('whatsapp.lead_notif_title', ['channel' => $widget->name], $locale),
        ];

        foreach (['name', 'email', 'phone'] as $field) {
            $value = trim((string) ($lead[$field] ?? ''));

            if ($value !== '') {
                $lines[] = __('whatsapp.lead_notif_' . $field, ['value' => $value], $locale);
            }
        }

        if ($session) {
            $lines[] = __('whatsapp.lead_notif_view', ['url' => route('chats.show', $session->id)], $locale);
        }

        return implode("\n", $lines);
    }
}
