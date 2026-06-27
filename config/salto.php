<?php

/*
|--------------------------------------------------------------------------
| SALTO Space integration configuration
|--------------------------------------------------------------------------
|
| The exact table/column names that hold battery status differ between
| SALTO Space versions. Rather than hard-coding them, the SaltoLockRepository
| builds its read query from this config. After you run `php artisan
| salto:discover` against the live database, set the matching values here
| (or in .env) so the monitor reads the right columns.
|
| If your schema can't be expressed as a single table SELECT, set
| SALTO_RAW_SQL to a full read-only query that returns the aliased columns
| id, name, location, battery, last_seen.
|
*/

return [

    // Laravel DB connection name (defined in config/database.php).
    'connection' => env('SALTO_CONNECTION', 'salto'),

    'query' => [
        // Table or view holding one row per lock/door.
        'table' => env('SALTO_LOCK_TABLE', 'tb_DOOR'),

        // Map of our normalized field => actual SALTO column name.
        'columns' => [
            'id' => env('SALTO_COL_ID', 'ID'),
            'name' => env('SALTO_COL_NAME', 'NAME'),
            'location' => env('SALTO_COL_LOCATION', 'NAME'),
            'battery' => env('SALTO_COL_BATTERY', 'BATTERY_STATUS'),
            'last_seen' => env('SALTO_COL_LASTSEEN', 'LAST_UPDATE'),
        ],

        // Optional full SELECT override. When set, columns/table above are
        // ignored. Must alias output columns to: id, name, location,
        // battery, last_seen. Example:
        //   SELECT d.ID AS id, d.NAME AS name, z.NAME AS location,
        //          d.BATTERY_STATUS AS battery, d.LAST_UPDATE AS last_seen
        //   FROM tb_DOOR d LEFT JOIN tb_ZONE z ON z.ID = d.ZONE_ID
        'raw_sql' => env('SALTO_RAW_SQL'),
    ],

    /*
    | Map raw SALTO battery values to our normalized states. Values are
    | compared case-insensitively as strings; numeric codes are also matched.
    | Anything not listed (or NULL) becomes "unknown". Adjust to match what
    | salto:discover shows in the real data.
    */
    // Values returned by the CASE statement in SALTO_RAW_SQL or the raw
    // column value. Compared case-insensitively. Anything not listed → unknown.
    'battery_map' => [
        'normal' => ['normal', 'ok', 'good', 'high'],
        'low'    => ['low', 'warning', 'warn'],
        'flat'   => ['flat', 'dead', 'empty', 'critical', 'replace'],
    ],

];
