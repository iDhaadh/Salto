<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Phase 0 helper. Inspects the SALTO database (read-only) to locate the
 * table/column that hold battery status, so config/salto.php can be set
 * correctly. Run this once after DB credentials are configured.
 */
class DiscoverSaltoSchema extends Command
{
    protected $signature = 'salto:discover {--sample= : Print TOP 10 rows from this table}';

    protected $description = 'Find battery-related tables/columns in the SALTO database (read-only)';

    public function handle(): int
    {
        $connection = config('salto.connection', 'salto');

        try {
            DB::connection($connection)->getPdo();
        } catch (\Throwable $e) {
            $this->error('Cannot connect to SALTO ['.$connection.']: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($table = $this->option('sample')) {
            return $this->sample($connection, $table);
        }

        $this->info('Searching for battery/power-related columns...');

        $columns = DB::connection($connection)->select(<<<'SQL'
            SELECT t.name AS table_name, c.name AS column_name, ty.name AS data_type
            FROM sys.columns c
            JOIN sys.tables t ON c.object_id = t.object_id
            JOIN sys.types ty ON c.system_type_id = ty.system_type_id
            WHERE c.name LIKE '%batt%' OR c.name LIKE '%bater%'
               OR c.name LIKE '%power%' OR c.name LIKE '%energy%'
            ORDER BY t.name, c.name
        SQL);

        if (empty($columns)) {
            $this->warn('No obvious battery columns found. List candidate door/lock tables instead:');
            $tables = DB::connection($connection)->select(<<<'SQL'
                SELECT name FROM sys.tables
                WHERE name LIKE '%door%' OR name LIKE '%lock%' OR name LIKE '%peripher%'
                ORDER BY name
            SQL);
            foreach ($tables as $t) {
                $this->line('  • '.$t->name);
            }
            $this->newLine();
            $this->line('Then inspect one with:  php artisan salto:discover --sample=<TABLE>');

            return self::SUCCESS;
        }

        $this->table(
            ['Table', 'Column', 'Type'],
            collect($columns)->map(fn ($c) => [$c->table_name, $c->column_name, $c->data_type])->all(),
        );
        $this->newLine();
        $this->line('Inspect sample data with:  php artisan salto:discover --sample=<TABLE>');
        $this->line('Then set SALTO_LOCK_TABLE and SALTO_COL_* in .env accordingly.');

        return self::SUCCESS;
    }

    private function sample(string $connection, string $table): int
    {
        $safe = '['.str_replace([']', '['], '', $table).']';

        try {
            $rows = DB::connection($connection)->select("SELECT TOP 10 * FROM {$safe}");
        } catch (\Throwable $e) {
            $this->error('Query failed: '.$e->getMessage());

            return self::FAILURE;
        }

        if (empty($rows)) {
            $this->warn('No rows returned.');

            return self::SUCCESS;
        }

        $headers = array_keys((array) $rows[0]);
        $data = collect($rows)->map(fn ($r) => array_map(
            fn ($v) => is_null($v) ? 'NULL' : \Illuminate\Support\Str::limit((string) $v, 24),
            (array) $r,
        ))->all();

        $this->table($headers, $data);

        return self::SUCCESS;
    }
}
