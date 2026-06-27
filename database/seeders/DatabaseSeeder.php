<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin login for the dashboard. Override via ADMIN_EMAIL / ADMIN_PASSWORD.
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@example.com')],
            [
                'name'     => 'Administrator',
                'username' => env('ADMIN_USERNAME', 'admin'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'role'     => 'admin',
            ],
        );

        // Seed default settings from config so the Settings page is pre-filled.
        $defaults = [
            'poll_minutes' => (string) config('monitor.poll_minutes'),
            'reminder_hours' => (string) config('monitor.reminder_hours'),
            'notify_on_recovery' => config('monitor.notify_on_recovery') ? '1' : '0',
            'email_enabled' => config('monitor.email_enabled') ? '1' : '0',
            'whatsapp_enabled' => config('monitor.whatsapp_enabled') ? '1' : '0',
            'emails' => (string) config('monitor.emails'),
            'whatsapp' => (string) config('monitor.whatsapp'),
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
