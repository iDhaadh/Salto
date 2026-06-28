@extends('layouts.app')

@section('title', 'Notification Logs')

@section('content')
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h4 fw-bold mb-0">Notification Logs</h1>
        <div class="text-muted small mt-1">Full history of every email and WhatsApp notification sent</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('logs.export.pdf', request()->query()) }}" class="btn btn-sm btn-outline-danger" target="_blank">
            <i class="bi bi-file-earmark-pdf me-1"></i>Export PDF
        </a>
        <a href="{{ route('logs.export.excel', request()->query()) }}" class="btn btn-sm btn-outline-success">
            <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
        </a>
    </div>
</div>

{{-- Stat cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon text-primary"><i class="bi bi-send"></i></div>
                <div>
                    <div class="stat-value text-primary">{{ number_format($totals['total']) }}</div>
                    <div class="stat-label text-muted">Total Sent</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon text-success"><i class="bi bi-check-circle"></i></div>
                <div>
                    <div class="stat-value text-success">{{ number_format($totals['sent']) }}</div>
                    <div class="stat-label text-muted">Delivered</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon text-danger"><i class="bi bi-x-circle"></i></div>
                <div>
                    <div class="stat-value text-danger">{{ number_format($totals['failed']) }}</div>
                    <div class="stat-label text-muted">Failed</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('logs.index') }}" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold mb-1">Room / Lock</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="form-control" placeholder="Room-101...">
                </div>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Channel</label>
                <select name="channel" class="form-select form-select-sm">
                    <option value="">All channels</option>
                    <option value="email" {{ request('channel') === 'email' ? 'selected' : '' }}>Email</option>
                    <option value="whatsapp" {{ request('channel') === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All statuses</option>
                    <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Reason</label>
                <select name="reason" class="form-select form-select-sm">
                    <option value="">All reasons</option>
                    <option value="alert" {{ request('reason') === 'alert' ? 'selected' : '' }}>Alert</option>
                    <option value="reminder" {{ request('reason') === 'reminder' ? 'selected' : '' }}>Reminder</option>
                    <option value="recovery" {{ request('reason') === 'recovery' ? 'selected' : '' }}>Recovery</option>
                    <option value="test" {{ request('reason') === 'test' ? 'selected' : '' }}>Test</option>
                </select>
            </div>
            <div class="col-6 col-md-1">
                <label class="form-label small fw-semibold mb-1">From</label>
                <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-1">
                <label class="form-label small fw-semibold mb-1">To</label>
                <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm">
            </div>
            <div class="col-12 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-fill">
                    <i class="bi bi-funnel"></i>
                </button>
                @if(request()->hasAny(['search','channel','status','reason','from','to']))
                    <a href="{{ route('logs.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-body p-0">
        @if($logs->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-journal-x" style="font-size:2.5rem;opacity:.3"></i>
                <div class="mt-2">No logs found matching your filters.</div>
            </div>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0 small">
                    <thead>
                        <tr>
                            <th class="ps-3">#</th>
                            <th>Date & Time</th>
                            <th>Lock / Room</th>
                            <th>Location</th>
                            <th>Severity</th>
                            <th>Channel</th>
                            <th>Recipient</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th class="pe-3">Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                            @php $lock = $log->alert?->lock; @endphp
                            <tr>
                                <td class="ps-3 text-muted">{{ $log->id }}</td>
                                <td class="text-nowrap">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="fw-semibold">{{ $lock?->name ?? '—' }}</td>
                                <td class="text-muted">{{ $lock?->location ?? '—' }}</td>
                                <td>
                                    @php $sev = $log->alert?->severity ?? '' @endphp
                                    @if($sev === 'flat')
                                        <span class="badge text-bg-danger">Flat/Dead</span>
                                    @elseif($sev === 'low')
                                        <span class="badge text-bg-warning text-dark">Low</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->channel === 'whatsapp')
                                        <span class="badge" style="background:#25d366;color:#fff">
                                            <i class="bi bi-whatsapp me-1"></i>WhatsApp
                                        </span>
                                    @else
                                        <span class="badge text-bg-primary">
                                            <i class="bi bi-envelope me-1"></i>Email
                                        </span>
                                    @endif
                                </td>
                                <td class="text-muted" style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                                    title="{{ $log->recipient }}">{{ $log->recipient }}</td>
                                <td>
                                    @php $reasonColors = ['alert'=>'danger','reminder'=>'warning','recovery'=>'success','test'=>'secondary'] @endphp
                                    <span class="badge text-bg-{{ $reasonColors[$log->reason] ?? 'secondary' }}">
                                        {{ ucfirst($log->reason ?? '—') }}
                                    </span>
                                </td>
                                <td>
                                    @if($log->status === 'sent')
                                        <span class="badge text-bg-success"><i class="bi bi-check me-1"></i>Sent</span>
                                    @else
                                        <span class="badge text-bg-danger"><i class="bi bi-x me-1"></i>Failed</span>
                                    @endif
                                </td>
                                <td class="pe-3 text-danger" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                                    title="{{ $log->error }}">
                                    {{ $log->error ? Str::limit($log->error, 50) : '' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="px-3 py-2 border-top d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ number_format($logs->total()) }} entries
                    </div>
                    {{ $logs->links() }}
                </div>
            @else
                <div class="px-3 py-2 border-top text-muted small">
                    {{ number_format($logs->total()) }} entries
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
