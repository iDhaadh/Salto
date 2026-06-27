<?php

namespace App\Console\Commands;

use App\Services\AlertNotifier;
use App\Support\BatteryStatus;
use App\Support\LockSnapshot;
use Illuminate\Console\Command;

/**
 * Sends a test notification through the configured channels so you can verify
 * email + WhatsApp delivery without waiting for a real low battery.
 */
class TestNotify extends Command
{
    protected $signature = 'salto:test-notify';

    protected $description = 'Send a test battery alert to the configured recipients';

    public function handle(AlertNotifier $notifier): int
    {
        $lock = LockSnapshot::make(
            saltoId: 'TEST-LOCK',
            name: 'Test Lock (Front Entrance)',
            location: 'Demo / Reception',
            lastSeenAt: now()->toImmutable(),
        );

        $jobs = $notifier->notify($lock, BatteryStatus::Low, 'test');

        if ($jobs === 0) {
            $this->warn('No notifications dispatched — check that channels are enabled and recipients are set in Settings.');

            return self::SUCCESS;
        }

        $this->info("Dispatched {$jobs} test notification job(s). Ensure the queue worker is running to deliver them.");

        return self::SUCCESS;
    }
}
