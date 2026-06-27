<?php

/*
|--------------------------------------------------------------------------
| Battery monitor defaults
|--------------------------------------------------------------------------
| These are fallback defaults. Values stored in the `settings` table (edited
| from the dashboard Settings page) take precedence at runtime — see
| App\Support\Settings.
*/

return [
    // How often the scheduler runs salto:check (minutes).
    'poll_minutes' => (int) env('POLL_MINUTES', 15),

    // Re-send a reminder for a still-open alert every N hours.
    'reminder_hours' => (int) env('REMINDER_HOURS', 24),

    // Notify when a lock recovers to normal.
    'notify_on_recovery' => (bool) env('NOTIFY_ON_RECOVERY', true),

    // Channels enabled by default.
    'email_enabled' => (bool) env('ALERT_EMAIL_ENABLED', true),
    'whatsapp_enabled' => (bool) env('ALERT_WHATSAPP_ENABLED', true),

    // Comma-separated default recipient lists.
    'emails' => env('ALERT_EMAILS', ''),
    'whatsapp' => env('ALERT_WHATSAPP', ''),
];
