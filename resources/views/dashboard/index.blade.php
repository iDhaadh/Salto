@extends('layouts.app')

@section('title', 'Locks')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Lock battery status</h1>
    <div class="d-flex align-items-center gap-3">
        @if (session('status'))
            <span class="text-success small"><i class="bi bi-check-circle me-1"></i>{{ session('status') }}</span>
        @endif
        <span class="text-muted small">
            Last sync:
            {{ $lastSync ? \Illuminate\Support\Carbon::parse($lastSync)->format('d/m/Y H:i') : 'never' }}
        </span>
        <form method="POST" action="{{ route('dashboard.sync') }}">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-clockwise me-1"></i> Sync now
            </button>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    @php
        $cards = [
            ['Total locks', $counts['total'], 'secondary', null],
            ['Flat / Dead', $counts['flat'], 'danger', 'flat'],
            ['Low', $counts['low'], 'warning', 'low'],
            ['Unknown', $counts['unknown'], 'secondary', 'unknown'],
        ];
    @endphp
    @foreach ($cards as [$label, $value, $color, $key])
        <div class="col-6 col-md-3">
            <a href="{{ $key ? route('dashboard', ['battery' => $key]) : route('dashboard') }}" class="text-decoration-none">
                <div class="card stat-card border-start border-4 border-{{ $color }}">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase">{{ $label }}</div>
                        <div class="display-6 text-{{ $color === 'secondary' ? 'dark' : $color }}">{{ $value }}</div>
                    </div>
                </div>
            </a>
        </div>
    @endforeach
</div>

<div class="card">
    <div class="card-body">
        @if ($filter)
            <p class="mb-3">
                Filtered by <span class="badge text-bg-secondary text-uppercase">{{ $filter }}</span>
                <a href="{{ route('dashboard') }}" class="ms-2 small">clear</a>
            </p>
        @endif

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Lock</th>
                        <th>Location</th>
                        <th>Battery</th>
                        <th>Last seen</th>
                        <th>SALTO ID</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($locks as $lock)
                        <tr>
                            <td class="fw-semibold">{{ $lock->name }}</td>
                            <td>{{ $lock->location ?? '—' }}</td>
                            <td>
                                <span class="badge text-bg-{{ $lock->status()->color() }}">{{ $lock->status()->label() }}</span>
                            </td>
                            <td>{{ $lock->last_seen_at ? $lock->last_seen_at->format('d/m/Y H:i') : '—' }}</td>
                            <td class="text-muted small">{{ $lock->salto_id }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                No locks yet. Configure the SALTO connection and run
                                <code>php artisan salto:check</code>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $locks->links() }}</div>
@endsection
