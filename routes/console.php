<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Poll SALTO for battery status. Interval comes from config/monitor.php
// (POLL_MINUTES). Clamped to 1..59 for a valid cron expression; a server
// reboot or `php artisan schedule:run` picks up changes.
$pollMinutes = max(1, min(59, (int) config('monitor.poll_minutes', 15)));

// NB: withoutOverlapping() is deliberately NOT used. It stores a lock in the
// cache that must be released after each run; a run interrupted while
// runInBackground() was previously in use left a stale lock that wedged the
// scheduler permanently (filtersPass() kept returning false). These tasks are
// lightweight (a few seconds) and run serially once per minute via
// schedule:work, so overlap is a non-issue — and without a mutex there is no
// lock that can get stuck. Both commands are also safe to run concurrently:
// salto:alarms advances a watermark, salto:check upserts idempotently.
Schedule::command('salto:check')
    ->cron("*/{$pollMinutes} * * * *");

// Scan the SALTO audit trail for new alarm events (intrusion, tamper, forced
// entry, duress, door-left-open, hardware failure). Runs every minute so
// alarms are delivered promptly — it only reads new rows past the watermark.
Schedule::command('salto:alarms')
    ->everyMinute();
