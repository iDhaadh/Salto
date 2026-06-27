<?php

namespace App\Support;

enum BatteryStatus: string
{
    case Normal = 'normal';
    case Low = 'low';
    case Flat = 'flat';
    case Unknown = 'unknown';

    /**
     * Normalize a raw SALTO battery value using the config/salto.php map.
     */
    public static function fromRaw(mixed $raw): self
    {
        if ($raw === null || $raw === '') {
            return self::Unknown;
        }

        $needle = strtolower(trim((string) $raw));

        foreach (config('salto.battery_map', []) as $state => $values) {
            foreach ($values as $candidate) {
                if ($needle === strtolower((string) $candidate)) {
                    return self::tryFrom($state) ?? self::Unknown;
                }
            }
        }

        return self::Unknown;
    }

    /** Does this state warrant an alert? */
    public function isAlertable(): bool
    {
        return $this === self::Low || $this === self::Flat;
    }

    public function isUrgent(): bool
    {
        return $this === self::Flat;
    }

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::Low => 'Low',
            self::Flat => 'Flat / Dead',
            self::Unknown => 'Unknown',
        };
    }

    /** Bootstrap contextual colour for badges. */
    public function color(): string
    {
        return match ($this) {
            self::Normal => 'success',
            self::Low => 'warning',
            self::Flat => 'danger',
            self::Unknown => 'secondary',
        };
    }
}
