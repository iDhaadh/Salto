@extends('layouts.app')

@section('title', 'Door Events')

@section('content')
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h4 fw-bold mb-0">Door Events</h1>
        <div class="text-muted small mt-1">Live access log from SALTO database — all door openings, denials and system events</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('door-events.export.pdf', request()->query()) }}" class="btn btn-sm btn-outline-danger" target="_blank">
            <i class="bi bi-file-earmark-pdf me-1"></i>Export PDF
        </a>
        <a href="{{ route('door-events.export.excel', request()->query()) }}" class="btn btn-sm btn-outline-success">
            <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
        </a>
    </div>
</div>

{{-- Stat cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon text-primary"><i class="bi bi-journal-text"></i></div>
                <div>
                    <div class="stat-value text-primary">{{ number_format($totalCount) }}</div>
                    <div class="stat-label text-muted">Total Events</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon text-success"><i class="bi bi-door-open"></i></div>
                <div>
                    <div class="stat-value text-success">{{ number_format($todayCount) }}</div>
                    <div class="stat-label text-muted">Today</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon text-warning"><i class="bi bi-credit-card-2-front"></i></div>
                <div>
                    <div class="stat-value text-warning">{{ number_format($accessCount) }}</div>
                    <div class="stat-label text-muted">Card Access</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('door-events.index') }}" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold mb-1">Room / Lock</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-door-closed"></i></span>
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="form-control" placeholder="Room-101...">
                </div>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small fw-semibold mb-1">User / Cardholder</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" name="user" value="{{ request('user') }}"
                           class="form-control" placeholder="Name...">
                </div>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Category</label>
                <select name="category" class="form-select form-select-sm">
                    <option value="">All categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>
                            {{ ucfirst($cat) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Event Type</label>
                <select name="event_code" class="form-select form-select-sm">
                    <option value="">All events</option>
                    @foreach($eventCodes as $code => [$label, $cat])
                        <option value="{{ $code }}" {{ request('event_code') == $code ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
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
                @if(request()->hasAny(['search','user','category','event_code','from','to']))
                    <a href="{{ route('door-events.index') }}" class="btn btn-outline-secondary btn-sm">
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
        @if($paginator->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-door-closed" style="font-size:2.5rem;opacity:.3"></i>
                <div class="mt-2">No events found matching your filters.</div>
            </div>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0 small">
                    <thead>
                        <tr>
                            <th class="ps-3">Date & Time</th>
                            <th>Room / Lock</th>
                            <th>Location</th>
                            <th>Event</th>
                            <th>User / Cardholder</th>
                            <th class="pe-3">Card Code</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paginator as $row)
                            <tr>
                                <td class="ps-3 text-nowrap text-muted">{{ $row['datetime'] }}</td>
                                <td class="fw-semibold">{{ $row['lock_name'] }}</td>
                                <td class="text-muted">{{ $row['lock_location'] }}</td>
                                <td>
                                    @php
                                        $badgeClass = match($row['category']) {
                                            'access'  => 'bg-success',
                                            'denied'  => 'bg-danger',
                                            'battery' => 'bg-warning text-dark',
                                            'door'    => 'bg-primary',
                                            default   => 'bg-secondary',
                                        };
                                        $icon = match($row['category']) {
                                            'access'  => 'bi-check-circle',
                                            'denied'  => 'bi-x-circle',
                                            'battery' => 'bi-battery-half',
                                            'door'    => 'bi-door-open',
                                            default   => 'bi-gear',
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">
                                        <i class="bi {{ $icon }} me-1"></i>{{ $row['event_label'] }}
                                    </span>
                                </td>
                                <td>
                                    @if($row['user_display'] !== '—')
                                        <i class="bi bi-person-circle me-1 text-muted"></i>{{ $row['user_display'] }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="pe-3 text-muted font-monospace">{{ $row['cardcode'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-3 py-2 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted small">
                    Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ number_format($paginator->total()) }} events
                </div>
                {{ $paginator->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
