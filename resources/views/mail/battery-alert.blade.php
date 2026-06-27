@php
    $urgent = $status->isUrgent();
    $accent = $reason === 'recovery' ? '#198754' : ($urgent ? '#dc3545' : '#fd7e14');
    $heading = match ($reason) {
        'recovery' => 'Battery recovered',
        'reminder' => 'Battery still ' . strtolower($status->label()),
        'test'     => 'Test notification',
        default    => 'Battery ' . strtolower($status->label()),
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;padding:24px;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;background:#fff;border-radius:10px;overflow:hidden;border:1px solid #e5e7eb;">
        <tr>
            <td style="background:{{ $accent }};padding:18px 24px;color:#fff;font-size:18px;font-weight:bold;">
                SALTO Battery Monitor
            </td>
        </tr>
        <tr>
            <td style="padding:24px;">
                <h2 style="margin:0 0 8px;font-size:20px;">{{ $heading }}</h2>
                <p style="margin:0 0 20px;color:#4b5563;">
                    @if ($reason === 'recovery')
                        The following lock has returned to a normal battery level.
                    @elseif ($reason === 'test')
                        This is a test message confirming email delivery is working.
                    @else
                        The following SALTO lock needs attention.
                    @endif
                </p>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                    <tr><td style="padding:8px 0;color:#6b7280;width:140px;">Lock</td><td style="padding:8px 0;font-weight:bold;">{{ $lock->name }}</td></tr>
                    @if ($lock->location)
                        <tr><td style="padding:8px 0;color:#6b7280;">Location</td><td style="padding:8px 0;">{{ $lock->location }}</td></tr>
                    @endif
                    <tr><td style="padding:8px 0;color:#6b7280;">Battery</td><td style="padding:8px 0;font-weight:bold;color:{{ $accent }};">{{ $status->label() }}</td></tr>
                    <tr><td style="padding:8px 0;color:#6b7280;">SALTO ID</td><td style="padding:8px 0;">{{ $lock->saltoId }}</td></tr>
                    @if ($lock->lastSeenAt)
                        <tr><td style="padding:8px 0;color:#6b7280;">Last seen</td><td style="padding:8px 0;">{{ $lock->lastSeenAt->format('Y-m-d H:i') }}</td></tr>
                    @endif
                    <tr><td style="padding:8px 0;color:#6b7280;">Detected</td><td style="padding:8px 0;">{{ now()->format('Y-m-d H:i') }}</td></tr>
                </table>

                @if ($reason !== 'recovery' && $reason !== 'test')
                    <p style="margin:20px 0 0;padding:12px 16px;background:#fff7ed;border-left:4px solid {{ $accent }};color:#7c2d12;">
                        Please replace or service the lock battery as soon as possible.
                    </p>
                @endif
            </td>
        </tr>
        <tr>
            <td style="padding:16px 24px;background:#f9fafb;color:#9ca3af;font-size:12px;">
                Automated message from the SALTO Battery Monitor.
            </td>
        </tr>
    </table>
</body>
</html>
