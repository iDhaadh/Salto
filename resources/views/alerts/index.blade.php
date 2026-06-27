@extends('layouts.app')

@section('title', 'Alerts')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Alerts</h1>
    <div class="btn-group">
        <a href="{{ route('alerts.index', ['status' => 'open']) }}"
           class="btn btn-sm {{ $status === 'open' ? 'btn-primary' : 'btn-outline-primary' }}">
            Open <span class="badge text-bg-light">{{ $openCount }}</span>
        </a>
        <a href="{{ route('alerts.index', ['status' => 'resolved']) }}"
           class="btn btn-sm {{ $status === 'resolved' ? 'btn-primary' : 'btn-outline-primary' }}">Resolved</a>
        <a href="{{ route('alerts.index', ['status' => 'all']) }}"
           class="btn btn-sm {{ $status === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">All</a>
    </div>
</div>

@if (session('status'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('status') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Lock</th>
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
                            $sev = \App\Support\BatteryStatus::tryFrom($alert->severity) ?? \App\Support\BatteryStatus::Low;
                            $sent = $alert->notifications->where('status', 'sent')->count();
                            $failed = $alert->notifications->where('status', 'failed')->count();
                            $isOpen = $alert->status === 'open';
                        @endphp
                        <tr>
                            <td class="fw-semibold">
                                {{ $alert->lock?->name ?? '—' }}
                                <div class="text-muted small">{{ $alert->lock?->location }}</div>
                            </td>
                            <td><span class="badge text-bg-{{ $sev->color() }}">{{ $sev->label() }}</span></td>
                            <td>
                                <span class="badge {{ $isOpen ? 'text-bg-danger' : 'text-bg-success' }}">
                                    {{ ucfirst($alert->status) }}
                                </span>
                            </td>
                            <td>{{ $alert->opened_at?->format('Y-m-d H:i') }}</td>
                            <td>{{ $alert->last_notified_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td>
                                <span class="text-success">{{ $sent }} sent</span>
                                @if ($failed)<span class="text-danger ms-1">/ {{ $failed }} failed</span>@endif
                            </td>
                            <td>{{ $alert->resolved_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="text-end text-nowrap">
                                @if ($isOpen)
                                    <form method="POST" action="{{ route('alerts.resend', $alert) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-primary"
                                                title="Resend notification now">
                                            <i class="bi bi-send"></i> Resend
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('alerts.resolve', $alert) }}" class="d-inline ms-1">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success"
                                                onclick="return confirm('Mark this alert as resolved?')"
                                                title="Mark as resolved">
                                            <i class="bi bi-check-lg"></i> Resolve
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No alerts to show.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $alerts->links() }}</div>
@endsection
