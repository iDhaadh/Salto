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

// NB: withoutOverlapping() is given an explicit expiry (minutes) so a run that
// is interrupted (e.g. the container is killed mid-run) cannot leave a stale
// lock that blocks the task forever. runInBackground() is deliberately NOT used:
// it defers lock release to a separate schedule:finish call which can be lost if
// the process is killed, re-introducing the stuck-lock problem. These tasks are
// lightweight (seconds), so running them inline is simpler and reliable.
Schedule::command('salto:check')
    ->cron("*/{$pollMinutes} * * * *")
    ->withoutOverlapping(10);

// Scan the SALTO audit trail for new alarm events (intrusion, tamper, forced
// entry, duress, door-left-open, hardware failure). Runs every minute so
// alarms are delivered promptly — it only reads new rows past the watermark.
Schedule::command('salto:alarms')
    ->everyMinute()
    ->withoutOverlapping(5);
