<?php

namespace App\Services;

use App\Jobs\SendAlarmNotification;
use App\Support\LockSnapshot;
use App\Support\Settings;

/**
 * Dispatches per-alarm notifications across every enabled channel, mirroring
 * AlarmNotifier's battery counterpart. Each alarm code carries its own WhatsApp
 * template so recipients get a message tailored to the exact fault condition.
 */
class AlarmNotifier
{
    /**
     * @param  array{0:string,1:string,2:string,3:string}  $alarm  [key, label, template, severity]
     * @return int  number of notification jobs dispatched
     */
    public function notify(LockSnapshot $lock, array $alarm, string $when): int
    {
        $count = 0;

        if (Settings::whatsappEnabled()) {
            foreach (Settings::whatsappRecipients() as $number) {
                SendAlarmNotification::dispatch($lock, $alarm, $when, 'whatsapp', $number);
                $count++;
            }
        }

        if (Settings::emailEnabled()) {
            foreach (Settings::emailRecipients() as $email) {
                SendAlarmNotification::dispatch($lock, $alarm, $when, 'email', $email);
                $count++;
            }
        }

        if (Settings::smsEnabled()) {
            foreach (Settings::smsRecipients() as $number) {
                SendAlarmNotification::dispatch($lock, $alarm, $when, 'sms', $number);
                $count++;
            }
        }

        return $count;
    }
}
