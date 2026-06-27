<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\Lock;
use App\Models\NotificationLog;
use Illuminate\Database\Seeder;

/**
 * Optional sample data so you can preview the dashboard without a live SALTO
 * connection. Run with:  php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $samples = [
            ['Front Entrance', 'Lobby / Ground Floor', 'flat'],
            ['Server Room', 'IT / 2nd Floor', 'low'],
            ['Room 101', 'Guest Wing A', 'normal'],
            ['Room 102', 'Guest Wing A', 'normal'],
            ['Pool Gate', 'Recreation', 'low'],
            ['Staff Door', 'Back of House', 'unknown'],
        ];

        foreach ($samples as $i => [$name, $location, $status]) {
            $lock = Lock::updateOrCreate(
                ['salto_id' => 'DEMO-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT)],
                [
                    'name' => $name,
                    'location' => $location,
                    'battery_status' => $status,
                    'battery_changed_at' => now()->subHours($i),
                    'last_seen_at' => now()->subMinutes(15 * $i),
                    'synced_at' => now(),
                ],
            );

            if (in_array($status, ['low', 'flat'], true)) {
                $alert = Alert::updateOrCreate(
                    ['lock_id' => $lock->id, 'status' => 'open'],
                    [
                        'severity' => $status,
                        'opened_at' => now()->subHours($i + 1),
                        'last_notified_at' => now()->subHours($i),
                        'notify_count' => 1,
                    ],
                );

                NotificationLog::firstOrCreate(
                    ['alert_id' => $alert->id, 'channel' => 'email', 'recipient' => 'ops@example.com'],
                    ['status' => 'sent', 'reason' => 'alert', 'sent_at' => now()->subHours($i)],
                );
            }
        }
    }
}
