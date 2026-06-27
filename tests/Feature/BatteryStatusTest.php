<?php

namespace Tests\Feature;

use App\Support\BatteryStatus;
use Tests\TestCase;

class BatteryStatusTest extends TestCase
{
    public function test_maps_raw_values_to_normalized_states(): void
    {
        config(['salto.battery_map' => [
            'normal' => ['normal', 'ok', '0'],
            'low' => ['low', 'warning', '2'],
            'flat' => ['flat', 'dead', '3'],
        ]]);

        $this->assertSame(BatteryStatus::Normal, BatteryStatus::fromRaw('OK'));
        $this->assertSame(BatteryStatus::Low, BatteryStatus::fromRaw('Warning'));
        $this->assertSame(BatteryStatus::Low, BatteryStatus::fromRaw(2));
        $this->assertSame(BatteryStatus::Flat, BatteryStatus::fromRaw('dead'));
        $this->assertSame(BatteryStatus::Unknown, BatteryStatus::fromRaw(null));
        $this->assertSame(BatteryStatus::Unknown, BatteryStatus::fromRaw('something-else'));
    }

    public function test_alertable_and_urgency_helpers(): void
    {
        $this->assertTrue(BatteryStatus::Low->isAlertable());
        $this->assertTrue(BatteryStatus::Flat->isAlertable());
        $this->assertFalse(BatteryStatus::Normal->isAlertable());
        $this->assertFalse(BatteryStatus::Unknown->isAlertable());

        $this->assertTrue(BatteryStatus::Flat->isUrgent());
        $this->assertFalse(BatteryStatus::Low->isUrgent());
    }
}
