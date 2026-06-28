<?php

namespace App\Http\Controllers;

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
    // SALTO EventCode → [label, category]
    // category: access | denied | battery | door | system
    private const EVENT_CODES = [
        17  => ['Access Granted',          'access'],
        25  => ['Access Granted (Emergency)','access'],
        28  => ['Access Granted (Keypad)', 'access'],
        40  => ['Door Closed',             'door'],
        41  => ['Inside Handle Opened',    'door'],
        49  => ['Office Mode ON',          'system'],
        50  => ['Office Mode OFF',         'system'],
        52  => ['Auto-open Mode',          'system'],
        55  => ['Access Denied (Unknown Key)', 'denied'],
        56  => ['Access Denied (Expired)', 'denied'],
        57  => ['Access Denied (Cancelled)','denied'],
        60  => ['Key Programmed',          'system'],
        64  => ['Key Updated',             'system'],
        72  => ['Free Passage ON',         'system'],
        81  => ['Deadbolt Released',       'door'],
        82  => ['Battery Low',             'battery'],
        84  => ['Battery Very Low',        'battery'],
        87  => ['Locked by Keypad',        'door'],
        88  => ['Locked',                  'door'],
        89  => ['Unlocked by Keypad',      'door'],
        96  => ['Emergency Unlock',        'access'],
        99  => ['Timeout',                 'system'],
        100 => ['Privacy Mode ON',         'system'],
        104 => ['Privacy Mode OFF',        'system'],
        113 => ['Auto-open (Inside)',      'door'],
        114 => ['Auto-close',              'door'],
        115 => ['Auto-unlock',             'door'],
        116 => ['Auto-open',               'door'],
        117 => ['Key Update (PPD)',        'system'],
        119 => ['Inhibit Mode ON',         'system'],
        120 => ['Dormant Mode',            'system'],
        1000 => ['Low Battery Alert',      'battery'],
        1001 => ['Flat Battery Alert',     'battery'],
    ];

    private const BASE_SELECT = "
        SELECT {top}
            a.InsertionCounter, a.EventDateTime, a.EventCode,
            a.id_object AS lock_id, a.id_subject AS user_id, a.Cardcode,
            l.name AS lock_name, l.Description AS lock_location,
            u.name AS user_name, u.FirstName AS first_name, u.LastName AS last_name
        FROM [tb_LockAuditTrail] a
        LEFT JOIN [tb_Locks] l ON a.id_object  = l.id_lock
        LEFT JOIN [tb_Users] u ON a.id_subject = u.id_user
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
        $categories = ['access', 'denied', 'door', 'battery', 'system'];

        $baseCount = "SELECT COUNT(*) AS c FROM [tb_LockAuditTrail] a LEFT JOIN [tb_Locks] l ON a.id_object = l.id_lock LEFT JOIN [tb_Users] u ON a.id_subject = u.id_user WHERE 1=1";
        $totalCount  = DB::connection('salto')->selectOne("{$baseCount}")->c ?? 0;
        $todayCount  = DB::connection('salto')->selectOne("{$baseCount} AND CAST(a.EventDateTime AS DATE) = CAST(GETDATE() AS DATE)")->c ?? 0;
        $accessCount = DB::connection('salto')->selectOne("{$baseCount} AND a.EventCode = 17")->c ?? 0;

        return view('door-events.index', compact('paginator', 'eventCodes', 'categories', 'totalCount', 'todayCount', 'accessCount'));
    }

    public function exportPdf(Request $request)
    {
        [$where, $bindings] = $this->buildWhere($request);
        $rows = DB::connection('salto')->select(
            $this->makeSelect($where, 'TOP 1000') . " ORDER BY a.InsertionCounter DESC",
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

        $name = trim($r->user_name ?? '');
        if (! $name) {
            $fn = trim($r->first_name ?? '');
            $ln = trim($r->last_name ?? '');
            $name = trim("{$fn} {$ln}");
        }

        return [
            'counter'      => $r->InsertionCounter,
            'datetime'     => $r->EventDateTime ? date('d/m/Y H:i:s', strtotime($r->EventDateTime)) : '—',
            'event_code'   => $code,
            'event_label'  => $label,
            'category'     => $category,
            'lock_id'      => $r->lock_id,
            'lock_name'    => $r->lock_name ?? '—',
            'lock_location'=> $r->lock_location ?? '—',
            'user_id'      => $r->user_id,
            'user_display' => $name ?: '—',
            'cardcode'     => ($r->Cardcode && $r->Cardcode != 0) ? $r->Cardcode : null,
        ];
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
