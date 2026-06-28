@extends('layouts.app')

@section('title', 'Remote Door Open')

@section('content')
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h4 fw-bold mb-0"><i class="bi bi-door-open me-2 text-primary"></i>Remote Door Open</h1>
        <div class="text-muted small mt-1">Open any online SALTO door remotely via ProAccess Space API</div>
    </div>
    <button class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
        <i class="bi bi-arrow-clockwise me-1"></i>Refresh
    </button>
</div>

@if(count($doors) === 0 && !session('error'))
    <div class="alert alert-warning d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-triangle-fill"></i>
        No online doors found. Make sure the SALTO ProAccess Space service is reachable and the API credentials are configured.
    </div>
@endif

@php
    $total  = count($doors);
    $search = request('q', '');
    $filtered = $search
        ? array_filter($doors, fn($d) => stripos($d['Name'] ?? '', $search) !== false
                                      || stripos($d['Description'] ?? '', $search) !== false)
        : $doors;
@endphp

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon text-primary"><i class="bi bi-door-open"></i></div>
                <div>
                    <div class="stat-value text-primary">{{ $total }}</div>
                    <div class="stat-label text-muted">Online Doors</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Search --}}
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('doors.index') }}" class="row g-2 align-items-end">
            <div class="col-sm-5 col-md-4">
                <label class="form-label small text-muted mb-1">Search</label>
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Room name or number…" value="{{ $search }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Search</button>
                @if($search)
                    <a href="{{ route('doors.index') }}" class="btn btn-sm btn-outline-secondary ms-1">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Confirmation modal --}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fs-6 fw-bold" id="confirmModalLabel">
                    <i class="bi bi-door-open text-primary me-2"></i>Open Door?
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2 pb-1">
                <p class="mb-0 small">Send open command to <strong id="confirmDoorName"></strong>?</p>
            </div>
            <div class="modal-footer border-0 pt-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="openForm" method="POST" class="d-inline">
                    @csrf
                    <input type="hidden" name="name" id="confirmDoorHidden">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-unlock me-1"></i>Open
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Room / Door</th>
                        <th>Description</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($filtered as $door)
                        <tr>
                            <td class="text-muted" style="width:60px">{{ $door['Id'] }}</td>
                            <td class="fw-semibold">{{ $door['Name'] ?? '—' }}</td>
                            <td class="text-muted">{{ $door['Description'] ?? '—' }}</td>
                            <td class="text-end" style="width:110px">
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary open-btn"
                                        data-id="{{ $door['AttachedAccessPointId'] }}"
                                        data-name="{{ $door['Description'] ?? $door['Name'] }}"
                                        data-url="{{ route('doors.open', $door['AttachedAccessPointId']) }}"
                                        data-bs-toggle="modal"
                                        data-bs-target="#confirmModal">
                                    <i class="bi bi-unlock me-1"></i>Open
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No doors match your search.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($search && count($filtered) !== $total)
        <div class="card-footer text-muted small">
            Showing {{ count($filtered) }} of {{ $total }} doors
        </div>
    @endif
</div>

@push('scripts')
<script>
document.querySelectorAll('.open-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('confirmDoorName').textContent   = btn.dataset.name;
        document.getElementById('confirmDoorHidden').value       = btn.dataset.name;
        document.getElementById('openForm').action               = btn.dataset.url;
    });
});
</script>
@endpush
@endsection
