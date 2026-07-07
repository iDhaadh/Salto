<?php

namespace App\Console\Commands;

use App\Services\AlarmMonitor;
use Illuminate\Console\Command;

class CheckAlarms extends Command
{
    protected $signature = 'salto:alarms';

    protected $description = 'Scan the SALTO audit trail for new alarm events and dispatch per-code notifications';

    public function handle(AlarmMonitor $monitor): int
    {
        $this->info('Scanning SALTO audit trail for alarm events...');

        try {
            $stats = $monitor->run();
        } catch (\Throwable $e) {
            $this->error('Alarm monitor failed: '.$e->getMessage());

            return self::FAILURE;
        }

        if (! $stats['enabled']) {
            $this->warn('Alarm monitoring is disabled in settings.');

            return self::SUCCESS;
        }

        if ($stats['initialized']) {
            $this->info("First run — watermark seeded at InsertionCounter {$stats['watermark']}. No historical alarms sent.");

            return self::SUCCESS;
        }

        $this->table(
            ['Alarm events', 'Jobs dispatched', 'Watermark'],
            [[$stats['events'], $stats['jobs'], $stats['watermark']]],
        );

        return self::SUCCESS;
    }
}
