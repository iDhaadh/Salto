<?php

namespace Tests\Feature;

use App\Jobs\SendBatteryAlert;
use App\Models\Alert;
use App\Models\Lock;
use App\Repositories\SaltoLockRepository;
use App\Services\BatteryMonitor;
use App\Support\BatteryStatus;
use App\Support\LockReading;
use App\Support\Settings;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/** In-memory stand-in for the SALTO database. */
class FakeSaltoRepository extends SaltoLockRepository
{
    public static Collection $readings;

    public function all(): Collection
    {
        return static::$readings ?? collect();
    }
}

class BatteryMonitorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Settings::put('email_enabled', 1);
        Settings::put('whatsapp_enabled', 0);
        Settings::put('emails', 'ops@example.com');
        Settings::put('reminder_hours', 24);
        Settings::put('notify_on_recovery', 1);

        $this->app->instance(SaltoLockRepository::class, new FakeSaltoRepository());
    }

    private function setReadings(LockReading ...$readings): void
    {
        FakeSaltoRepository::$readings = collect($readings);
    }

    private function reading(string $id, BatteryStatus $battery): LockReading
    {
        return new LockReading($id, "Lock {$id}", "Location {$id}", $battery, CarbonImmutable::now());
    }

    private function monitor(): BatteryMonitor
    {
        return app(BatteryMonitor::class);
    }

    public function test_opens_alert_and_dispatches_notification_on_flat_battery(): void
    {
        Queue::fake();
        $this->setReadings($this->reading('A', BatteryStatus::Flat));

        $stats = $this->monitor()->run();

        $this->assertSame(1, $stats['opened']);
        $this->assertSame(1, Lock::count());
        $this->assertSame(1, Alert::where('status', 'open')->count());
        Queue::assertPushed(SendBatteryAlert::class, 1);
    }

    public function test_does_not_reopen_or_renotify_within_reminder_window(): void
    {
        Queue::fake();
        $this->setReadings($this->reading('A', BatteryStatus::Flat));

        $this->monitor()->run();
        $this->monitor()->run(); // second poll, still flat, within reminder window

        $this->assertSame(1, Alert::count(), 'alert should not be duplicated');
        Queue::assertPushed(SendBatteryAlert::class, 1, 'should not re-notify within window');
    }

    public function test_sends_reminder_after_reminder_window_elapses(): void
    {
        Queue::fake();
        $this->setReadings($this->reading('A', BatteryStatus::Low));

        $this->monitor()->run();

        // Pretend the last notification was sent 25h ago.
        Alert::query()->update(['last_notified_at' => now()->subHours(25)]);

        $stats = $this->monitor()->run();

        $this->assertSame(1, $stats['reminded']);
        Queue::assertPushed(SendBatteryAlert::class, 2); // initial + reminder
    }

    public function test_resolves_alert_when_battery_returns_to_normal(): void
    {
        Queue::fake();
        $this->setReadings($this->reading('A', BatteryStatus::Low));
        $this->monitor()->run();

        $this->setReadings($this->reading('A', BatteryStatus::Normal));
        $stats = $this->monitor()->run();

        $this->assertSame(1, $stats['resolved']);
        $this->assertSame(0, Alert::where('status', 'open')->count());
        $this->assertSame(1, Alert::where('status', 'resolved')->count());
    }

    public function test_unknown_battery_does_not_open_an_alert(): void
    {
        Queue::fake();
        $this->setReadings($this->reading('A', BatteryStatus::Unknown));

        $stats = $this->monitor()->run();

        $this->assertSame(0, $stats['opened']);
        $this->assertSame(0, Alert::count());
        Queue::assertNothingPushed();
    }
}
