<?php

namespace App\Http\Controllers;

use App\Models\NotificationLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LogController extends Controller
{
    private function buildQuery(Request $request)
    {
        $q = NotificationLog::with(['alert.lock'])
            ->orderBy('created_at', 'desc');

        if ($search = trim((string) $request->input('search'))) {
            $q->whereHas('alert.lock', fn ($l) => $l->where('name', 'like', "%{$search}%")
                ->orWhere('location', 'like', "%{$search}%"));
        }

        if ($channel = $request->input('channel')) {
            $q->where('channel', $channel);
        }

        if ($status = $request->input('status')) {
            $q->where('status', $status);
        }

        if ($reason = $request->input('reason')) {
            $q->where('reason', $reason);
        }

        if ($from = $request->input('from')) {
            $q->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $q->whereDate('created_at', '<=', $to);
        }

        return $q;
    }

    public function index(Request $request): View
    {
        $logs = $this->buildQuery($request)->paginate(50)->withQueryString();

        $totals = [
            'total'  => NotificationLog::count(),
            'sent'   => NotificationLog::where('status', 'sent')->count(),
            'failed' => NotificationLog::where('status', 'failed')->count(),
        ];

        return view('logs.index', compact('logs', 'totals'));
    }

    public function exportPdf(Request $request): Response
    {
        $logs = $this->buildQuery($request)->limit(2000)->get();
        $filters = $this->activeFilters($request);

        $pdf = Pdf::loadView('logs.pdf', compact('logs', 'filters'))
            ->setPaper('a4', 'landscape');

        $filename = 'notification-logs-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    public function exportExcel(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $logs = $this->buildQuery($request)->limit(5000)->get();
        $filters = $this->activeFilters($request);
        $filename = 'notification-logs-' . now()->format('Y-m-d') . '.xlsx';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Notification Logs');

        // Title row
        $sheet->mergeCells('A1:J1');
        $sheet->setCellValue('A1', 'SALTO Battery Monitor — Notification Logs');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Filter info row
        $sheet->mergeCells('A2:J2');
        $sheet->setCellValue('A2', 'Exported: ' . now()->format('d/m/Y H:i') . ($filters ? '   Filters: ' . $filters : ''));
        $sheet->getStyle('A2')->getFont()->setSize(9)->setItalic(true);
        $sheet->getStyle('A2')->getFont()->getColor()->setRGB('666666');

        // Header row
        $headers = ['#', 'Date & Time', 'Lock / Room', 'Location', 'Severity', 'Channel', 'Recipient', 'Reason', 'Status', 'Error'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '3', $header);
            $col++;
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e293b']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '334155']]],
        ];
        $sheet->getStyle('A3:J3')->applyFromArray($headerStyle);

        // Data rows
        $row = 4;
        foreach ($logs as $log) {
            $lock     = $log->alert?->lock;
            $isEven   = ($row % 2 === 0);
            $isFailed = $log->status === 'failed';

            $sheet->setCellValue('A' . $row, $log->id);
            $sheet->setCellValue('B' . $row, $log->created_at?->format('d/m/Y H:i:s'));
            $sheet->setCellValue('C' . $row, $lock?->name ?? '—');
            $sheet->setCellValue('D' . $row, $lock?->location ?? '—');
            $sheet->setCellValue('E' . $row, ucfirst($log->alert?->severity ?? '—'));
            $sheet->setCellValue('F' . $row, ucfirst($log->channel));
            $sheet->setCellValue('G' . $row, $log->recipient);
            $sheet->setCellValue('H' . $row, ucfirst($log->reason ?? '—'));
            $sheet->setCellValue('I' . $row, ucfirst($log->status));
            $sheet->setCellValue('J' . $row, $log->error ?? '');

            if ($isFailed) {
                $sheet->getStyle('I' . $row)->getFont()->getColor()->setRGB('dc2626');
            }
            if ($isEven) {
                $sheet->getStyle('A' . $row . ':J' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('f8fafc');
            }

            $row++;
        }

        // Column widths
        foreach (['A' => 8, 'B' => 18, 'C' => 20, 'D' => 20, 'E' => 12, 'F' => 12, 'G' => 28, 'H' => 12, 'I' => 10, 'J' => 35] as $c => $w) {
            $sheet->getColumnDimension($c)->setWidth($w);
        }

        $sheet->getStyle('A4:J' . max($row - 1, 4))->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_HAIR)->getColor()->setRGB('e2e8f0');

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function activeFilters(Request $request): string
    {
        $parts = [];
        if ($v = $request->input('search'))  $parts[] = "Room: {$v}";
        if ($v = $request->input('channel')) $parts[] = "Channel: {$v}";
        if ($v = $request->input('status'))  $parts[] = "Status: {$v}";
        if ($v = $request->input('reason'))  $parts[] = "Reason: {$v}";
        if ($v = $request->input('from'))    $parts[] = "From: {$v}";
        if ($v = $request->input('to'))      $parts[] = "To: {$v}";
        return implode(', ', $parts);
    }
}
