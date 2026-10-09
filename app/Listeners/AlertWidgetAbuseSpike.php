<?php

namespace App\Listeners;

use App\Events\DomainBlocked;
use App\Events\QuotaExceeded;
use App\Mail\WidgetAbuseAlert;
use App\Models\User;
use App\Models\Widget;
use App\Services\Email\EmailSender;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Emails the widget owner when rejections spike: many domain blocks
 * mean someone is trying to run the widget outside its allowed
 * domains (or the allowlist is misconfigured), and many quota
 * denials mean the exhausted widget is still being hammered. One
 * alert per widget + type per cooldown so an attack never becomes
 * an email flood.
 */
class AlertWidgetAbuseSpike
{
    private const WINDOW_MINUTES = 10;
    private const DOMAIN_THRESHOLD = 20;
    private const QUOTA_THRESHOLD = 50;
    private const COOLDOWN_HOURS = 6;

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(DomainBlocked::class, [self::class, 'onDomainBlocked']);
        $events->listen(QuotaExceeded::class, [self::class, 'onQuotaExceeded']);
    }

    public function onDomainBlocked(DomainBlocked $event): void
    {
        $widget = Widget::where('slug', $event->widgetSlug)->with('user')->first();

        if ($widget?->user) {
            $this->track('domain', $widget, $widget->user, self::DOMAIN_THRESHOLD);
        }
    }

    public function onQuotaExceeded(QuotaExceeded $event): void
    {
        $widget = Widget::where('slug', $event->widgetSlug)->with('user')->first();
        $owner = $widget?->user ?? User::find($event->userId);

        if ($widget && $owner) {
            $this->track('quota', $widget, $owner, self::QUOTA_THRESHOLD);
        }
    }

    private function track(string $type, Widget $widget, User $owner, int $threshold): void
    {
        $key = "abuse:{$type}:{$widget->id}";

        Cache::add($key, 0, now()->addMinutes(self::WINDOW_MINUTES));
        $count = (int) Cache::increment($key);

        if ($count < $threshold) {
            return;
        }

        if (! Cache::add("abuse-alerted:{$type}:{$widget->id}", 1, now()->addHours(self::COOLDOWN_HOURS))) {
            return; // already alerted inside the cooldown
        }

        try {
            EmailSender::send($owner->email, new WidgetAbuseAlert(
                $owner,
                $widget->name,
                $type,
                $count,
                self::WINDOW_MINUTES,
            ), 'abuse_alert', ['user_id' => $owner->id, 'widget_id' => $widget->id]);
        } catch (\Throwable $e) {
            Log::error('Failed to send widget abuse alert', [
                'widget_id' => $widget->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
