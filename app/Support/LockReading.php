<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Immutable normalized view of one lock as read from SALTO.
 */
final class LockReading
{
    public function __construct(
        public readonly string $saltoId,
        public readonly string $name,
        public readonly ?string $location,
        public readonly BatteryStatus $battery,
        public readonly ?CarbonImmutable $lastSeenAt,
    ) {
    }

    public function withBattery(BatteryStatus $battery): self
    {
        return new self($this->saltoId, $this->name, $this->location, $battery, $this->lastSeenAt);
    }
}
