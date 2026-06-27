<?php

namespace App\Console\Commands;

use App\Services\BatteryMonitor;
use Illuminate\Console\Command;

class CheckBatteries extends Command
{
    protected $signature = 'salto:check';

    protected $description = 'Read SALTO lock battery status and dispatch low/flat alerts';

    public function handle(BatteryMonitor $monitor): int
    {
        $this->info('Checking SALTO lock batteries...');

        try {
            $stats = $monitor->run();
        } catch (\Throwable $e) {
            $this->error('Monitor failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Scanned', 'Low', 'Flat', 'Opened', 'Reminded', 'Resolved', 'Jobs'],
            [[
                $stats['scanned'], $stats['low'], $stats['flat'],
                $stats['opened'], $stats['reminded'], $stats['resolved'], $stats['jobs'],
            ]],
        );

        return self::SUCCESS;
    }
}
