<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Reset monthly message quota on the 1st of each month at midnight
Schedule::command('quota:reset')->monthlyOn(1, '00:00');

// Check plan expiry daily at 8 AM (send reminders + auto-downgrade)
Schedule::command('plans:check-expiry')->dailyAt('08:00');

// Enforce per-plan chat retention (plans.chat_history_days 7/30/90)
Schedule::command('chat:purge')->dailyAt('03:30');

// Email Center: drain campaigns marked "sending" in small chunks
// (survives closed browsers; cron runs schedule:run every minute)
Schedule::command('campaigns:send')->everyMinute()->withoutOverlapping();

// Email Center: outbound email logs keep PII for 90 days only
Schedule::command('email:prune')->dailyAt('03:45');
