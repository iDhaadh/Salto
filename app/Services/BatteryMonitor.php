<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Lock;
use App\Repositories\SaltoLockRepository;
use App\Support\BatteryStatus;
use App\Support\LockReading;
use App\Support\LockSnapshot;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;

/**
 * Core monitoring engine: pulls battery status from SALTO, updates the local
 * cache, and manages the alert lifecycle (open / remind / resolve) with
 * de-duplication so we don't spam on every poll.
 */
class BatteryMonitor
{
    public function __construct(
        private readonly SaltoLockRepository $repository,
        private readonly AlertNotifier $notifier,
        private readonly SaltoApiService $api,
    ) {
    }

    /**
     * Run one monitoring cycle.
     *
     * @return array{scanned:int,low:int,flat:int,opened:int,reminded:int,resolved:int,jobs:int}
     */
    public function run(): array
    {
        $readings = $this->repository->all();

        // Merge real-time API battery status for online RF3 locks.
        // Falls back to DB value if API is unavailable or lock is offline.
        // If the API has no reading (BatteryStatus=0) and the DB data is older
        // than 30 days, treat the battery as Unknown to avoid re-triggering
        // alerts from stale readings the lock hasn't refreshed in months.
        $apiBattery = $this->api->getOnlineBatteryStatuses();
        $readings = $readings->map(function ($reading) use ($apiBattery) {
            if (isset($apiBattery[$reading->saltoId])) {
                return $reading->withBattery($apiBattery[$reading->saltoId]);
            }
            if ($reading->battery->isAlertable()
                && $reading->lastSeenAt
                && $reading->lastSeenAt->isBefore(now()->subDays(30))) {
                return $reading->withBattery(BatteryStatus::Unknown);
            }
            return $reading;
        });

        $stats = ['scanned' => 0, 'low' => 0, 'flat' => 0, 'opened' => 0, 'reminded' => 0, 'resolved' => 0, 'jobs' => 0];
        $reminderThreshold = now()->subHours(Settings::reminderHours());

        foreach ($readings as $reading) {
            /** @var LockReading $reading */
            $stats['scanned']++;
            $lock = $this->syncLock($reading);
            $status = $reading->battery;

            if ($status === BatteryStatus::Low) {
                $stats['low']++;
            } elseif ($status === BatteryStatus::Flat) {
                $stats['flat']++;
            }

            $openAlert = $lock->openAlert();

            if ($status->isAlertable()) {
                if (! $openAlert) {
                    // No open alert (new problem or was manually resolved but battery still bad).
                    // Either way: open a fresh alert and notify immediately.
                    $alert = $this->openAlert($lock, $status);
                    $stats['opened']++;
                    $stats['jobs'] += $this->dispatchAndStamp($lock, $status, 'alert', $alert);
                } else {
                    // Existing problem. Escalate severity if it worsened.
                    if ($status->isUrgent() && $openAlert->severity !== BatteryStatus::Flat->value) {
                        $openAlert->update(['severity' => BatteryStatus::Flat->value]);
                        $stats['jobs'] += $this->dispatchAndStamp($lock, $status, 'alert', $openAlert);
                    } elseif ($openAlert->last_notified_at && $openAlert->last_notified_at->lessThan($reminderThreshold)) {
                        // Reminder cadence elapsed.
                        $stats['reminded']++;
                        $stats['jobs'] += $this->dispatchAndStamp($lock, $status, 'reminder', $openAlert);
                    }
                }
            } elseif ($openAlert && ($status === BatteryStatus::Normal || $status === BatteryStatus::Unknown)) {
                // Normal = confirmed recovered. Unknown = stale data (>30 days), can't verify still bad.
                // Either way: resolve. Only send a recovery notification when we can confirm Normal.
                $openAlert->update(['status' => 'resolved', 'resolved_at' => now()]);
                $stats['resolved']++;
                if ($status === BatteryStatus::Normal && Settings::notifyOnRecovery()) {
                    $stats['jobs'] += $this->notifier->notify(LockSnapshot::fromLock($lock), $status, 'recovery', $openAlert);
                }
            }
        }

        return $stats;
    }

    private function syncLock(LockReading $reading): Lock
    {
        $lock = Lock::firstOrNew(['salto_id' => $reading->saltoId]);

        $batteryChanged = $lock->battery_status !== $reading->battery->value;

        $lock->fill([
            'name' => $reading->name,
            'location' => $reading->location,
            'battery_status' => $reading->battery->value,
            'last_seen_at' => $reading->lastSeenAt,
            'synced_at' => now(),
        ]);

        if ($batteryChanged || ! $lock->exists) {
            $lock->battery_changed_at = now();
        }

        $lock->save();

        return $lock;
    }

    private function openAlert(Lock $lock, BatteryStatus $status): Alert
    {
        return $lock->alerts()->create([
            'severity' => $status->value,
            'status' => 'open',
            'opened_at' => now(),
            'notify_count' => 0,
        ]);
    }

    private function dispatchAndStamp(Lock $lock, BatteryStatus $status, string $reason, Alert $alert): int
    {
        $jobs = $this->notifier->notify(LockSnapshot::fromLock($lock), $status, $reason, $alert);

        $alert->update([
            'last_notified_at' => now(),
            'notify_count' => DB::raw('notify_count + 1'),
        ]);

        return $jobs;
    }
}
