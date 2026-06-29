<?php

namespace App\Services;

use App\Jobs\SendBatteryAlert;
use App\Models\Alert;
use App\Support\BatteryStatus;
use App\Support\LockSnapshot;
use App\Support\Settings;

/**
 * Decides who to notify on which channels and dispatches the queued jobs.
 */
class AlertNotifier
{
    /**
     * @return int Number of notification jobs dispatched.
     */
    public function notify(LockSnapshot $lock, BatteryStatus $status, string $reason, ?Alert $alert = null): int
    {
        $count = 0;

        if (Settings::emailEnabled()) {
            foreach (Settings::emailRecipients() as $email) {
                SendBatteryAlert::dispatch($lock, $status, 'email', $email, $reason, $alert?->id);
                $count++;
            }
        }

        if (Settings::whatsappEnabled()) {
            foreach (Settings::whatsappRecipients() as $number) {
                SendBatteryAlert::dispatch($lock, $status, 'whatsapp', $number, $reason, $alert?->id);
                $count++;
            }
        }

        if (Settings::smsEnabled()) {
            foreach (Settings::smsRecipients() as $number) {
                SendBatteryAlert::dispatch($lock, $status, 'sms', $number, $reason, $alert?->id);
                $count++;
            }
        }

        return $count;
    }
}
