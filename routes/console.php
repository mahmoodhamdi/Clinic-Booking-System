<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Hourly: send 24h reminders for confirmed appointments. The command itself
// is idempotent via reminder_sent_at, so cron drift / double-runs are safe.
// Production deploys must run `php artisan schedule:work` (or schedule:run
// from a system cron) — see DEPLOY.md.
Schedule::command('appointments:send-reminders --hours=24')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer()
    ->name('appointments-reminder-24h');
