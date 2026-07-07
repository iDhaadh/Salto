<?php

namespace App\Http\Controllers;

use App\Models\DoorOpen;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DoorEventController extends Controller
{
    // SALTO EventCode → [label, category]. Sourced from SALTO Space's own
    // operation catalog (GetEventStreamOperationIdAndNameAndIsExitList), not
    // guessed — labels match exactly what SALTO Space displays.
    // category: access | denied | alarm | battery | privacy | door | comm | system
    private const EVENT_CODES = [
        // ── Access granted (door opened) ──────────────────────────────
        8   => ['New Renovation Code (Online)',      'system'],
        16  => ['Door Opened (Inside Handle)',       'access'],
        17  => ['Door Opened (Key)',                 'access'],
        18  => ['Door Opened (Key + Keypad)',        'access'],
        19  => ['Door Opened (Multiple Guest Key)',  'access'],
        20  => ['Door Opened (Unique Opening)',      'access'],
        21  => ['Door Opened (Switch)',              'access'],
        22  => ['Door Opened (Metal Key)',           'access'],
        23  => ['First Double-Key Read',             'access'],
        24  => ['Door Opened (Second Double Key)',   'access'],
        25  => ['Door Opened (PPD)',                 'access'],
        26  => ['Door Opened (Keypad)',              'access'],
        27  => ['Door Opened (Spare Key)',           'access'],
        28  => ['Door Opened (Online)',              'access'],
        29  => ['Door Opened (Key + PIN)',           'access'],
        43  => ['Door Opened (Fingerprint + Key)',   'access'],
        45  => ['Door Opened (PIN + Fingerprint)',   'access'],
        2000 => ['Guest New Key',                    'access'],
        2001 => ['Guest Copy Key',                   'access'],

        // ── Door / lock state ─────────────────────────────────────────
        33  => ['Door Closed (Key)',                 'door'],
        34  => ['Door Closed (Key + Keypad)',        'door'],
        35  => ['Door Closed (Keypad)',              'door'],
        36  => ['Door Closed (Switch)',              'door'],
        37  => ['Key Inserted (Energy Saver)',       'door'],
        38  => ['Key Removed (Energy Saver)',        'door'],
        39  => ['Room Prepared (Energy Saver)',      'door'],
        44  => ['Door Closed (Fingerprint + Key)',   'door'],
        46  => ['Door Closed (PIN + Fingerprint)',   'door'],
        52  => ['Locked',                            'door'],
        53  => ['Unlocked',                          'door'],
        121 => ['Bolt Thrown (Out)',                 'door'],
        122 => ['Bolt Retracted (Inside)',           'door'],
        123 => ['Locker Taken',                      'door'],
        125 => ['Locker Released',                   'door'],

        // ── Privacy ───────────────────────────────────────────────────
        40  => ['Privacy Started',                   'privacy'],
        41  => ['Privacy Ended',                     'privacy'],

        // ── Alarms / fault conditions ─────────────────────────────────
        3   => ['Short-Circuit in Input',            'alarm'],
        4   => ['Open-Circuit in Input',             'alarm'],
        42  => ['DURESS Alarm',                      'alarm'],
        56  => ['Forced Opening Started',            'alarm'],
        57  => ['Forced Opening Ended',              'alarm'],
        58  => ['Forced Closing Started',            'alarm'],
        59  => ['Forced Closing Ended',              'alarm'],
        60  => ['Intrusion Alarm',                   'alarm'],
        61  => ['Tamper Alarm',                      'alarm'],
        62  => ['Door Left Open',                    'alarm'],
        63  => ['Door Left Open — Cleared',          'alarm'],
        64  => ['Intrusion Alarm — Cleared',         'alarm'],
        67  => ['Tamper Alarm — Cleared',            'alarm'],
        119 => ['Hardware Failure (Open/Close)',     'alarm'],

        // ── Battery ───────────────────────────────────────────────────
        100 => ['Denied — Battery Flat',             'battery'],
        115 => ['Low Battery Level',                 'battery'],

        // ── Access denied ─────────────────────────────────────────────
        81  => ['Denied — Key Not Activated',        'denied'],
        82  => ['Denied — Key Expired',              'denied'],
        83  => ['Denied — Key Out of Date',          'denied'],
        84  => ['Denied — Invalid Key',              'denied'],
        85  => ['Denied — Out of Time Schedule',     'denied'],
        87  => ['Denied — Privacy Not Overridden',   'denied'],
        88  => ['Denied — Old Guest Key',            'denied'],
        89  => ['Denied — Guest Key Cancelled',      'denied'],
        90  => ['Denied — Antipassback',             'denied'],
        91  => ['Denied — Second Double Key Missing','denied'],
        92  => ['Denied — No Authorization',         'denied'],
        93  => ['Denied — Invalid PIN',              'denied'],
        94  => ['Denied — Not Enough Points',        'denied'],
        95  => ['Denied — Door in Emergency State',  'denied'],
        96  => ['Denied — Key Cancelled',            'denied'],
        97  => ['Denied — Unique Key Already Used',  'denied'],
        98  => ['Denied — Incompatible Renovation',  'denied'],
        101 => ['Denied — Cannot Audit Opening',     'denied'],
        102 => ['Denied — Locker Occupancy Timeout', 'denied'],
        103 => ['Denied — Refused by Host',          'denied'],
        107 => ['Denied — Key Data Manipulated',     'denied'],
        108 => ['Denied — Invalid Fingerprint',      'denied'],

        // ── Communication ─────────────────────────────────────────────
        47   => ['Reader Communication Lost',        'comm'],
        48   => ['Reader Communication Restored',    'comm'],
        79   => ['Server Communication Lost',        'comm'],
        80   => ['Server Communication Restored',    'comm'],
        1000 => ['Device Communication Restored',    'comm'],
        1001 => ['Device Communication Lost',        'comm'],

        // ── System / maintenance ──────────────────────────────────────
        31  => ['Office Mode ON (Keypad)',           'system'],
        32  => ['Office Mode OFF (Keypad)',          'system'],
        49  => ['Office Mode ON',                    'system'],
        50  => ['Office Mode OFF',                   'system'],
        51  => ['Guest Cancelled',                   'system'],
        54  => ['Door Programmed (Spare Key)',       'system'],
        55  => ['New Hotel Guest Key',               'system'],
        65  => ['Office Mode ON (Online)',           'system'],
        66  => ['Office Mode OFF (Online)',          'system'],
        69  => ['Key Updated (Out of Site)',         'system'],
        70  => ['Key Expiry Extended (Offline)',     'system'],
        72  => ['Online Peripheral Updated',         'system'],
        76  => ['Key Updated (Online)',              'system'],
        78  => ['Key Cancelled (Online)',            'system'],
        99  => ['Warning — Key Not Fully Updated',   'system'],
        104 => ['Key Deleted',                       'system'],
        112 => ['New Renovation Code (Door)',        'system'],
        113 => ['PPD Connection',                    'system'],
        114 => ['Daylight Saving Time Change',       'system'],
        116 => ['Incorrect Clock Value',             'system'],
        117 => ['RF Lock Date/Time Updated',         'system'],
        118 => ['RF Lock Updated',                   'system'],
        120 => ['Lock Restarted',                    'system'],
    ];

    private const BASE_SELECT = "
        SELECT {top}
            a.InsertionCounter, a.EventDateTime, a.EventCode,
            a.id_object AS lock_id, a.id_subject AS user_id, a.Cardcode,
            l.name AS lock_name, l.Description AS lock_location,
            u.name AS user_name, u.FirstName AS first_name, u.LastName AS last_name,
            op.log_username AS operator_username
        FROM [tb_LockAuditTrail] a
        LEFT JOIN [tb_Locks] l ON a.id_object  = l.id_lock
        LEFT JOIN [tb_Users] u ON a.id_subject = u.id_user
        LEFT JOIN [vi_Operators_log_Username] op ON a.id_subject = op.id_operator
        WHERE 1=1
    ";

    /** Returns [whereClause, bindings] — no SELECT, no ORDER BY. */
    private function buildWhere(Request $request): array
    {
        $where = '';
        $bindings = [];

        if ($search = trim((string) $request->input('search'))) {
            $where .= " AND (l.name LIKE ? OR l.Description LIKE ?)";
            $bindings[] = "%{$search}%";
            $bindings[] = "%{$search}%";
        }

        if ($user = trim((string) $request->input('user'))) {
            $where .= " AND (u.name LIKE ? OR u.FirstName LIKE ? OR u.LastName LIKE ?)";
            $bindings[] = "%{$user}%";
            $bindings[] = "%{$user}%";
            $bindings[] = "%{$user}%";
        }

        if ($eventCode = $request->input('event_code')) {
            $where .= " AND a.EventCode = ?";
            $bindings[] = (int) $eventCode;
        }

        if ($category = $request->input('category')) {
            $codes = array_keys(array_filter(self::EVENT_CODES, fn ($e) => $e[1] === $category));
            if ($codes) {
                $placeholders = implode(',', array_fill(0, count($codes), '?'));
                $where .= " AND a.EventCode IN ({$placeholders})";
                $bindings = array_merge($bindings, $codes);
            }
        }

        if ($from = $request->input('from')) {
            $where .= " AND CAST(a.EventDateTime AS DATE) >= ?";
            $bindings[] = $from;
        }

        if ($to = $request->input('to')) {
            $where .= " AND CAST(a.EventDateTime AS DATE) <= ?";
            $bindings[] = $to;
        }

        return [$where, $bindings];
    }

    private function makeSelect(string $where, string $top = ''): string
    {
        return str_replace('{top}', $top, self::BASE_SELECT) . $where;
    }

    public function index(Request $request)
    {
        [$where, $bindings] = $this->buildWhere($request);

        $perPage = 50;
        $page    = (int) $request->input('page', 1);
        $offset  = ($page - 1) * $perPage;

        $countSql = "SELECT COUNT(*) AS total FROM [tb_LockAuditTrail] a LEFT JOIN [tb_Locks] l ON a.id_object = l.id_lock LEFT JOIN [tb_Users] u ON a.id_subject = u.id_user WHERE 1=1 {$where}";
        $total = DB::connection('salto')->selectOne($countSql, $bindings)->total ?? 0;

        $rows = DB::connection('salto')->select(
            $this->makeSelect($where) . " ORDER BY a.InsertionCounter DESC OFFSET ? ROWS FETCH NEXT ? ROWS ONLY",
            array_merge($bindings, [$offset, $perPage])
        );

        $rows = collect($rows)->map(fn ($r) => $this->decorate($r));

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $rows, $total, $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $eventCodes = self::EVENT_CODES;
        $categories = ['access', 'denied', 'alarm', 'battery', 'privacy', 'door', 'comm', 'system'];

        $baseCount = "SELECT COUNT(*) AS c FROM [tb_LockAuditTrail] a LEFT JOIN [tb_Locks] l ON a.id_object = l.id_lock LEFT JOIN [tb_Users] u ON a.id_subject = u.id_user WHERE 1=1";
        $totalCount  = DB::connection('salto')->selectOne("{$baseCount}")->c ?? 0;
        $todayCount  = DB::connection('salto')->selectOne("{$baseCount} AND CAST(a.EventDateTime AS DATE) = CAST(GETDATE() AS DATE)")->c ?? 0;

        // Alarms & fault conditions (intrusion, tamper, forced, duress, low battery, etc.)
        // in the last 24h — the operationally important events.
        $alarmCodes = array_keys(array_filter(self::EVENT_CODES, fn ($e) => in_array($e[1], ['alarm', 'battery'])));
        $alarmPlaceholders = implode(',', $alarmCodes);
        $alarmCount = DB::connection('salto')->selectOne(
            "{$baseCount} AND a.EventCode IN ({$alarmPlaceholders}) AND a.EventDateTime > DATEADD(day,-1,GETDATE())"
        )->c ?? 0;

        // App-initiated remote opens (our own audit log).
        $appOpens = DoorOpen::with('user')->latest('opened_at')->limit(100)->get();

        return view('door-events.index', compact('paginator', 'eventCodes', 'categories', 'totalCount', 'todayCount', 'alarmCount', 'appOpens'));
    }

    public function exportPdf(Request $request)
    {
        ini_set('memory_limit', '512M');

        [$where, $bindings] = $this->buildWhere($request);
        $rows = DB::connection('salto')->select(
            $this->makeSelect($where, 'TOP 500') . " ORDER BY a.InsertionCounter DESC",
            $bindings
        );
        $rows = collect($rows)->map(fn ($r) => $this->decorate($r));
        $filters = $this->activeFilters($request);
        $eventCodes = self::EVENT_CODES;

        $pdf = Pdf::loadView('door-events.pdf', compact('rows', 'filters', 'eventCodes'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('door-events-' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        [$where, $bindings] = $this->buildWhere($request);
        $rows = DB::connection('salto')->select(
            $this->makeSelect($where, 'TOP 5000') . " ORDER BY a.InsertionCounter DESC",
            $bindings
        );
        $rows = collect($rows)->map(fn ($r) => $this->decorate($r));
        $filters = $this->activeFilters($request);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Door Events');

        $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A1', 'SALTO Battery Monitor — Door Events Log');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:I2');
        $sheet->setCellValue('A2', 'Exported: ' . now()->format('d/m/Y H:i') . ($filters ? '   Filters: ' . $filters : ''));
        $sheet->getStyle('A2')->getFont()->setSize(9)->setItalic(true);
        $sheet->getStyle('A2')->getFont()->getColor()->setRGB('666666');

        $headers = ['Date & Time', 'Room / Lock', 'Location', 'Event', 'Category', 'User / Cardholder', 'Card Code', 'Lock ID', 'Event Code'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '3', $h);
            $col++;
        }
        $sheet->getStyle('A3:I3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e293b']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);

        $row = 4;
        foreach ($rows as $r) {
            $isEven = ($row % 2 === 0);
            $sheet->setCellValue('A' . $row, $r['datetime']);
            $sheet->setCellValue('B' . $row, $r['lock_name']);
            $sheet->setCellValue('C' . $row, $r['lock_location']);
            $sheet->setCellValue('D' . $row, $r['event_label']);
            $sheet->setCellValue('E' . $row, ucfirst($r['category']));
            $sheet->setCellValue('F' . $row, $r['user_display']);
            $sheet->setCellValue('G' . $row, $r['cardcode'] ?: '');
            $sheet->setCellValue('H' . $row, $r['lock_id']);
            $sheet->setCellValue('I' . $row, $r['event_code']);
            if ($isEven) {
                $sheet->getStyle('A'.$row.':I'.$row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('f8fafc');
            }
            $row++;
        }

        foreach (['A'=>18,'B'=>18,'C'=>20,'D'=>26,'E'=>12,'F'=>24,'G'=>12,'H'=>10,'I'=>12] as $c=>$w) {
            $sheet->getColumnDimension($c)->setWidth($w);
        }

        $sheet->getStyle('A4:I'.max($row-1,4))->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_HAIR)->getColor()->setRGB('e2e8f0');

        $writer = new Xlsx($spreadsheet);
        $filename = 'door-events-' . now()->format('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function decorate(object $r): array
    {
        $code = (int) $r->EventCode;
        [$label, $category] = self::EVENT_CODES[$code] ?? ["Event {$code}", 'system'];

        $operatorUsername = $r->operator_username ?? null;

        if ($operatorUsername) {
            // Action was initiated by a SALTO operator (e.g. online open via API/app).
            $appUser = $this->getOperatorMap()[$operatorUsername] ?? null;
            $name    = $appUser ?? $operatorUsername;
            $label   = 'Door Opened (Online)';
            $category = 'access';
        } else {
            $name = trim($r->user_name ?? '');
            if (! $name) {
                $fn = trim($r->first_name ?? '');
                $ln = trim($r->last_name ?? '');
                $name = trim("{$fn} {$ln}");
            }
            // SALTO hotel mode stores room-mapped cardholders as "@<room>" — make readable.
            if ($name && str_starts_with($name, '@')) {
                $name = 'Rm-' . ltrim($name, '@');
            }
        }

        return [
            'counter'       => $r->InsertionCounter,
            'datetime'      => $r->EventDateTime ? date('d/m/Y H:i:s', strtotime($r->EventDateTime)) : '—',
            'event_code'    => $code,
            'event_label'   => $label,
            'category'      => $category,
            'lock_id'       => $r->lock_id,
            'lock_name'     => $r->lock_name ?? '—',
            'lock_location' => $r->lock_location ?? '—',
            'user_id'       => $r->user_id,
            'user_display'  => $name ?: '—',
            'cardcode'      => ($r->Cardcode && $r->Cardcode != 0) ? $r->Cardcode : null,
            'is_online'     => (bool) $operatorUsername,
        ];
    }

    private ?array $operatorMapCache = null;

    private function getOperatorMap(): array
    {
        if ($this->operatorMapCache === null) {
            $this->operatorMapCache = \App\Models\User::whereNotNull('username')
                ->pluck('name', 'username')
                ->toArray();
        }
        return $this->operatorMapCache;
    }

    private function activeFilters(Request $request): string
    {
        $parts = [];
        if ($v = $request->input('search'))     $parts[] = "Room: {$v}";
        if ($v = $request->input('user'))       $parts[] = "User: {$v}";
        if ($v = $request->input('category'))   $parts[] = "Category: {$v}";
        if ($v = $request->input('event_code')) $parts[] = "Event code: {$v}";
        if ($v = $request->input('from'))       $parts[] = "From: {$v}";
        if ($v = $request->input('to'))         $parts[] = "To: {$v}";
        return implode(', ', $parts);
    }
}
