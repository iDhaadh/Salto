<?php

namespace App\Support;

use App\Models\Lock;
use Carbon\CarbonImmutable;

/**
 * Plain, queue-safe snapshot of a lock for notifications. Unlike an Eloquent
 * model, this serializes by value, so it is safe to pass into queued jobs and
 * Mailables (including for unsaved/test locks) without SerializesModels trying
 * to re-query a row that may not exist.
 */
final class LockSnapshot
{
    public function __construct(
        public readonly string $saltoId,
        public readonly string $name,
        public readonly ?string $location,
        public readonly ?CarbonImmutable $lastSeenAt,
    ) {
    }

    public static function fromLock(Lock $lock): self
    {
        return new self(
            saltoId: (string) $lock->salto_id,
            name: (string) $lock->name,
            location: $lock->location,
            lastSeenAt: $lock->last_seen_at ? CarbonImmutable::parse($lock->last_seen_at) : null,
        );
    }

    public static function make(string $saltoId, string $name, ?string $location = null, ?CarbonImmutable $lastSeenAt = null): self
    {
        return new self($saltoId, $name, $location, $lastSeenAt);
    }
}
