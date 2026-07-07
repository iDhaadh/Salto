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

Schedule::command('salto:check')
    ->cron("*/{$pollMinutes} * * * *")
    ->withoutOverlapping()
    ->runInBackground();

// Scan the SALTO audit trail for new alarm events (intrusion, tamper, forced
// entry, duress, door-left-open, hardware failure). Runs every minute so
// alarms are delivered promptly — it only reads new rows past the watermark.
Schedule::command('salto:alarms')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
