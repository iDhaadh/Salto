<?php

/*
|--------------------------------------------------------------------------
| SALTO alarm / fault-condition notifications
|--------------------------------------------------------------------------
| Each SALTO audit EventCode below triggers its own notification with its own
| WhatsApp template. Codes come from SALTO Space's operation catalog
| (GetEventStreamOperationIdAndNameAndIsExitList).
|
| Map: EventCode => [key, label, whatsapp_template, severity]
|   severity: critical | warning  (controls email/SMS wording + icon)
|
| The WhatsApp template must be pre-approved in Meta Business Manager and take
| three body parameters in this order:
|   {{1}} lock / room name   {{2}} location   {{3}} date & time
*/

return [

    'enabled' => (bool) env('ALARM_MONITORING_ENABLED', true),

    'codes' => [
        42  => ['duress',           'Duress Alarm',      'salto_alarm_duress',           'critical'],
        56  => ['forced_opening',   'Forced Opening',    'salto_alarm_forced_opening',   'critical'],
        58  => ['forced_closing',   'Forced Closing',    'salto_alarm_forced_closing',   'critical'],
        60  => ['intrusion',        'Intrusion Alarm',   'salto_alarm_intrusion',        'critical'],
        61  => ['tamper',           'Tamper Alarm',      'salto_alarm_tamper',           'critical'],
        62  => ['door_left_open',   'Door Left Open',    'salto_alarm_door_left_open',   'warning'],
        119 => ['hardware_failure', 'Hardware Failure',  'salto_alarm_hardware_failure', 'warning'],
    ],

    // Never notify more than this many events in a single poll (guards against a
    // flood if the watermark falls far behind). Older ones are skipped forward.
    'max_per_run' => (int) env('ALARM_MAX_PER_RUN', 100),
];
