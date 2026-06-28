<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; background: #fff; }
        .header { background: #1e293b; color: #fff; padding: 12px 16px; margin-bottom: 12px; }
        .header h1 { font-size: 14px; font-weight: bold; }
        .header p { font-size: 8px; opacity: .7; margin-top: 3px; }
        .filters { background: #f1f5f9; border: 1px solid #e2e8f0; padding: 6px 10px; border-radius: 4px; margin-bottom: 10px; font-size: 8px; color: #64748b; }
        table { width: 100%; border-collapse: collapse; }
        thead th { background: #334155; color: #fff; padding: 5px 6px; text-align: left; font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: .04em; }
        tbody tr:nth-child(even) td { background: #f8fafc; }
        tbody td { padding: 4px 6px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        .badge { display: inline-block; padding: 2px 5px; border-radius: 3px; font-size: 7.5px; font-weight: bold; }
        .badge-danger { background: #fee2e2; color: #dc2626; }
        .badge-warning { background: #fef3c7; color: #d97706; }
        .badge-success { background: #dcfce7; color: #16a34a; }
        .badge-primary { background: #dbeafe; color: #2563eb; }
        .badge-secondary { background: #f1f5f9; color: #64748b; }
        .badge-whatsapp { background: #dcfce7; color: #15803d; }
        .text-muted { color: #94a3b8; }
        .text-danger { color: #dc2626; }
        .footer { margin-top: 10px; text-align: center; font-size: 7.5px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SALTO Battery Monitor — Notification Logs</h1>
        <p>Exported: {{ now()->format('d/m/Y H:i') }}{{ $filters ? '   |   Filters: ' . $filters : '' }}   |   Total records: {{ count($logs) }}</p>
    </div>

    @if($filters)
        <div class="filters"><strong>Active filters:</strong> {{ $filters }}</div>
    @endif

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Date & Time</th>
                <th>Lock / Room</th>
                <th>Location</th>
                <th>Severity</th>
                <th>Channel</th>
                <th>Recipient</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Error</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
                @php $lock = $log->alert?->lock; @endphp
                <tr>
                    <td class="text-muted">{{ $log->id }}</td>
                    <td style="white-space:nowrap">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                    <td><strong>{{ $lock?->name ?? '—' }}</strong></td>
                    <td class="text-muted">{{ $lock?->location ?? '—' }}</td>
                    <td>
                        @if($log->alert?->severity === 'flat')
                            <span class="badge badge-danger">Flat/Dead</span>
                        @elseif($log->alert?->severity === 'low')
                            <span class="badge badge-warning">Low</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($log->channel === 'whatsapp')
                            <span class="badge badge-whatsapp">WhatsApp</span>
                        @else
                            <span class="badge badge-primary">Email</span>
                        @endif
                    </td>
                    <td>{{ $log->recipient }}</td>
                    <td>
                        @php $rc = ['alert'=>'danger','reminder'=>'warning','recovery'=>'success','test'=>'secondary'] @endphp
                        <span class="badge badge-{{ $rc[$log->reason] ?? 'secondary' }}">{{ ucfirst($log->reason ?? '—') }}</span>
                    </td>
                    <td>
                        @if($log->status === 'sent')
                            <span class="badge badge-success">Sent</span>
                        @else
                            <span class="badge badge-danger">Failed</span>
                        @endif
                    </td>
                    <td class="text-danger" style="max-width:120px;word-break:break-all;font-size:7.5px">{{ Str::limit($log->error ?? '', 60) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        SALTO Battery Monitor &mdash; Generated on {{ now()->format('d/m/Y H:i:s') }} &mdash; &copy; {{ date('Y') }} Dhaadh
    </div>
</body>
</html>
