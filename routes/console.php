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
