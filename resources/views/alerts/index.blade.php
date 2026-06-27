@extends('layouts.app')

@section('title', 'Alerts')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-0">Alerts</h1>
        <div class="text-muted small mt-1">Battery alerts and notification history</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('alerts.index', ['status' => 'open']) }}"
           class="btn btn-sm {{ $status === 'open' ? 'btn-danger' : 'btn-outline-secondary' }}">
            <i class="bi bi-exclamation-circle me-1"></i>Open
            <span class="badge {{ $status === 'open' ? 'bg-white text-danger' : 'text-bg-danger' }} ms-1">{{ $openCount }}</span>
        </a>
        <a href="{{ route('alerts.index', ['status' => 'resolved']) }}"
           class="btn btn-sm {{ $status === 'resolved' ? 'btn-success' : 'btn-outline-secondary' }}">
            <i class="bi bi-check-circle me-1"></i>Resolved
        </a>
        <a href="{{ route('alerts.index', ['status' => 'all']) }}"
           class="btn btn-sm {{ $status === 'all' ? 'btn-dark' : 'btn-outline-secondary' }}">All</a>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Lock</th>
                        <th>Severity</th>
                        <th>Status</th>
                        <th>Opened</th>
                        <th>Last notified</th>
                        <th>Notifications</th>
                        <th>Resolved</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($alerts as $alert)
                        @php
                            $sev    = \App\Support\BatteryStatus::tryFrom($alert->severity) ?? \App\Support\BatteryStatus::Low;
                            $sent   = $alert->notifications->where('status', 'sent')->count();
                            $failed = $alert->notifications->where('status', 'failed')->count();
                            $isOpen = $alert->status === 'open';
                        @endphp
                        <tr>
                            <td class="ps-3">
                                <div class="fw-semibold">{{ $alert->lock?->name ?? '—' }}</div>
                                <div class="text-muted small">{{ $alert->lock?->location }}</div>
                            </td>
                            <td>
                                <span class="badge text-bg-{{ $sev->color() }} d-inline-flex align-items-center gap-1">
                                    @if($sev->value === 'flat')<i class="bi bi-battery"></i>
                                    @elseif($sev->value === 'low')<i class="bi bi-battery-half"></i>
                                    @endif
                                    {{ $sev->label() }}
                                </span>
                            </td>
                            <td>
                                @if($isOpen)
                                    <span class="badge text-bg-danger d-inline-flex align-items-center gap-1">
                                        <span class="rounded-circle bg-white d-inline-block" style="width:6px;height:6px;opacity:.8"></span>
                                        Open
                                    </span>
                                @else
                                    <span class="badge text-bg-success"><i class="bi bi-check2 me-1"></i>Resolved</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $alert->opened_at?->format('d/m/Y H:i') }}</td>
                            <td class="small text-muted">{{ $alert->last_notified_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="small">
                                @if($sent > 0)
                                    <span class="text-success"><i class="bi bi-check2 me-1"></i>{{ $sent }} sent</span>
                                @else
                                    <span class="text-muted">0 sent</span>
                                @endif
                                @if($failed)
                                    <span class="text-danger ms-2"><i class="bi bi-x me-1"></i>{{ $failed }} failed</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $alert->resolved_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="text-end pe-3 text-nowrap">
                                @if ($isOpen)
                                    <form method="POST" action="{{ route('alerts.resend', $alert) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-primary" title="Resend notification">
                                            <i class="bi bi-send"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('alerts.resolve', $alert) }}" class="d-inline ms-1">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Resolve (checks SALTO first)">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('alerts.resolve', $alert) }}" class="d-inline ms-1">
                                        @csrf
                                        <input type="hidden" name="force" value="1">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                onclick="return confirm('Force resolve even if SALTO still reports a bad battery?')"
                                                title="Force resolve">
                                            <i class="bi bi-check2-all"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-check-circle d-block mb-2" style="font-size:2rem;opacity:.3"></i>
                                No alerts to show.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3 d-flex justify-content-end">{{ $alerts->links() }}</div>
@endsection
