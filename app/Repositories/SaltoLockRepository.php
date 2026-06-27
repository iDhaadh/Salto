<?php

namespace App\Repositories;

use App\Support\BatteryStatus;
use App\Support\LockReading;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * READ-ONLY access to the SALTO Space database.
 *
 * This class issues SELECT statements only. It must never write to the
 * SALTO connection. The actual table/column names come from config/salto.php
 * (resolved during the `salto:discover` step).
 */
class SaltoLockRepository
{
    /**
     * @return Collection<int, LockReading>
     */
    public function all(): Collection
    {
        $rows = $this->fetchRows();

        return collect($rows)->map(function ($row): LockReading {
            $row = (array) $row;

            return new LockReading(
                saltoId: (string) ($row['id'] ?? ''),
                name: (string) ($row['name'] ?? 'Unknown lock'),
                location: isset($row['location']) ? (string) $row['location'] : null,
                battery: BatteryStatus::fromRaw($row['battery'] ?? null),
                lastSeenAt: $this->parseDate($row['last_seen'] ?? null),
            );
        })->filter(fn (LockReading $r) => $r->saltoId !== '')->values();
    }

    /**
     * Raw rows aliased to id, name, location, battery, last_seen.
     */
    protected function fetchRows(): array
    {
        $connection = config('salto.connection', 'salto');
        $raw = config('salto.query.raw_sql');

        if (! empty($raw)) {
            return DB::connection($connection)->select($raw);
        }

        $cols = config('salto.query.columns');
        $table = config('salto.query.table');

        $select = [];
        foreach (['id', 'name', 'location', 'battery', 'last_seen'] as $alias) {
            $column = $cols[$alias] ?? null;
            if ($column) {
                $select[] = $this->quote($column).' AS '.$this->quote($alias);
            }
        }

        $sql = 'SELECT '.implode(', ', $select).' FROM '.$this->quote($table);

        return DB::connection($connection)->select($sql);
    }

    /** Quote a SQL Server identifier (handles dotted schema.table). */
    protected function quote(string $identifier): string
    {
        return collect(explode('.', $identifier))
            ->map(fn ($part) => '['.str_replace([']', '['], '', $part).']')
            ->implode('.');
    }

    protected function parseDate(mixed $value): ?CarbonImmutable
    {
        if (empty($value)) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
