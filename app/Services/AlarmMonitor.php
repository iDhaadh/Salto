<?php

namespace App\Services;

use App\Support\LockSnapshot;
use App\Support\Settings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Scans the SALTO audit trail for new alarm / fault events (intrusion, tamper,
 * forced entry, duress, door-left-open, hardware failure) and fires a separate
 * notification per event via AlarmNotifier.
 *
 * De-duplication uses tb_LockAuditTrail.InsertionCounter as a monotonic
 * watermark stored in settings (alarm_last_counter). On first run the watermark
 * is seeded to the current max so historical events are never blasted out.
 */
class AlarmMonitor
{
    public function __construct(
        private readonly AlarmNotifier $notifier,
    ) {
    }

    /**
     * @return array{enabled:bool,events:int,jobs:int,watermark:int,initialized:bool}
     */
    public function run(): array
    {
        $result = ['enabled' => true, 'events' => 0, 'jobs' => 0, 'watermark' => 0, 'initialized' => false];

        if (! Settings::alarmMonitoringEnabled()) {
            $result['enabled'] = false;
            return $result;
        }

        $map   = config('alarms.codes', []);
        $codes = array_keys($map);
        if (empty($codes)) {
            return $result;
        }

        $conn = DB::connection('salto');

        // Snapshot the high-water mark BEFORE reading, so events inserted during
        // processing are picked up on the next run rather than skipped.
        $maxCounter = (int) ($conn->selectOne('SELECT MAX(InsertionCounter) AS m FROM [tb_LockAuditTrail]')->m ?? 0);
        $result['watermark'] = $maxCounter;

        $last = Settings::get('alarm_last_counter');
        if ($last === null) {
            // First ever run — seed the watermark, don't notify on history.
            Settings::put('alarm_last_counter', $maxCounter);
            $result['initialized'] = true;
            return $result;
        }
        $last = (int) $last;

        if ($maxCounter <= $last) {
            return $result; // nothing new
        }

        $placeholders = implode(',', array_fill(0, count($codes), '?'));
        $limit        = (int) config('alarms.max_per_run', 100);

        $rows = $conn->select(
            "SELECT TOP {$limit}
                a.InsertionCounter, a.EventCode, a.EventDateTime,
                a.id_object AS lock_id, l.name AS lock_name, l.Description AS lock_location
             FROM [tb_LockAuditTrail] a
             LEFT JOIN [tb_Locks] l ON a.id_object = l.id_lock
             WHERE a.EventCode IN ({$placeholders})
               AND a.InsertionCounter > ? AND a.InsertionCounter <= ?
             ORDER BY a.InsertionCounter ASC",
            array_merge($codes, [$last, $maxCounter])
        );

        foreach ($rows as $r) {
            $alarm = $map[(int) $r->EventCode] ?? null;
            if (! $alarm) {
                continue;
            }

            // Allow the WhatsApp template name to be overridden from Settings
            // (edited on the WhatsApp tab) — fall back to the config default.
            $alarm[2] = (string) Settings::get("wa_alarm_tpl_{$alarm[0]}", $alarm[2]);

            $lock = LockSnapshot::make(
                saltoId: (string) $r->lock_id,
                name: $r->lock_name ?: ('Lock '.$r->lock_id),
                location: $r->lock_location ?: null,
                lastSeenAt: $r->EventDateTime ? CarbonImmutable::parse($r->EventDateTime) : null,
            );

            $when = $r->EventDateTime ? date('d/m/Y H:i', strtotime($r->EventDateTime)) : now()->format('d/m/Y H:i');

            $result['events']++;
            $result['jobs'] += $this->notifier->notify($lock, $alarm, $when);
        }

        // Advance the watermark to the snapshot max so every event (alarm or not)
        // up to this point is considered processed — keeps the scan window bounded.
        Settings::put('alarm_last_counter', $maxCounter);

        return $result;
    }
}
