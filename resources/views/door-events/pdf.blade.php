<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8.5px; color: #1e293b; }
        .header { background: #1e293b; color: #fff; padding: 10px 14px; margin-bottom: 10px; }
        .header h1 { font-size: 13px; font-weight: bold; }
        .header p { font-size: 7.5px; opacity: .7; margin-top: 3px; }
        .filters { background: #f1f5f9; border: 1px solid #e2e8f0; padding: 5px 8px; border-radius: 3px; margin-bottom: 8px; font-size: 7.5px; color: #64748b; }
        table { width: 100%; border-collapse: collapse; }
        thead th { background: #334155; color: #fff; padding: 4px 5px; text-align: left; font-size: 7.5px; font-weight: bold; text-transform: uppercase; }
        tbody tr:nth-child(even) td { background: #f8fafc; }
        tbody td { padding: 3.5px 5px; border-bottom: 1px solid #f1f5f9; }
        .badge { display: inline-block; padding: 1.5px 4px; border-radius: 2px; font-size: 7px; font-weight: bold; }
        .access  { background: #dcfce7; color: #15803d; }
        .denied  { background: #fee2e2; color: #dc2626; }
        .battery { background: #fef3c7; color: #d97706; }
        .door    { background: #dbeafe; color: #1d4ed8; }
        .system  { background: #f1f5f9; color: #475569; }
        .mono { font-family: monospace; }
        .footer { margin-top: 8px; text-align: center; font-size: 7px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SALTO Battery Monitor — Door Events Log</h1>
        <p>Exported: {{ now()->format('d/m/Y H:i') }}{{ $filters ? '   |   Filters: ' . $filters : '' }}   |   Records: {{ count($rows) }}</p>
    </div>

    @if($filters)
        <div class="filters"><strong>Filters:</strong> {{ $filters }}</div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Date & Time</th>
                <th>Room / Lock</th>
                <th>Location</th>
                <th>Event</th>
                <th>Category</th>
                <th>User / Cardholder</th>
                <th>Card Code</th>
                <th>Code</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td style="white-space:nowrap">{{ $row['datetime'] }}</td>
                    <td><strong>{{ $row['lock_name'] }}</strong></td>
                    <td>{{ $row['lock_location'] }}</td>
                    <td>
                        <span class="badge {{ $row['category'] }}">{{ $row['event_label'] }}</span>
                    </td>
                    <td>{{ ucfirst($row['category']) }}</td>
                    <td>{{ $row['user_display'] }}</td>
                    <td class="mono">{{ $row['cardcode'] ?? '—' }}</td>
                    <td class="mono">{{ $row['event_code'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        SALTO Battery Monitor &mdash; Generated {{ now()->format('d/m/Y H:i:s') }} &mdash; &copy; {{ date('Y') }} Dhaadh
    </div>
</body>
</html>
