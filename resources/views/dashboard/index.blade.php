@extends('layouts.app')

@section('title', 'Locks')

@section('content')
{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-0">Lock Battery Status</h1>
        <div class="text-muted small mt-1">
            <i class="bi bi-clock me-1"></i>Last sync:
            {{ $lastSync ? \Illuminate\Support\Carbon::parse($lastSync)->format('d/m/Y H:i') : 'never' }}
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        @if (session('status'))
            <span class="text-success small"><i class="bi bi-check-circle me-1"></i>{{ session('status') }}</span>
        @endif
        <form method="POST" action="{{ route('dashboard.sync') }}">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-clockwise me-1"></i>Sync now
            </button>
        </form>
    </div>
</div>

{{-- Stat Cards --}}
<div class="row g-3 mb-4">
    @php
        $statCards = [
            ['label' => 'Total Locks',  'value' => $counts['total'],   'color' => '6366f1', 'text' => 'primary',  'icon' => 'bi-lock-fill',               'filter' => null],
            ['label' => 'Flat / Dead',  'value' => $counts['flat'],    'color' => 'ef4444', 'text' => 'danger',   'icon' => 'bi-battery',                 'filter' => 'flat'],
            ['label' => 'Low Battery',  'value' => $counts['low'],     'color' => 'f59e0b', 'text' => 'warning',  'icon' => 'bi-battery-half',            'filter' => 'low'],
            ['label' => 'Unknown',      'value' => $counts['unknown'], 'color' => '9ca3af', 'text' => 'secondary','icon' => 'bi-question-circle-fill',    'filter' => 'unknown'],
        ];
    @endphp
    @foreach ($statCards as $card)
        <div class="col-6 col-md-3">
            <a href="{{ $card['filter'] ? route('dashboard', ['battery' => $card['filter']]) : route('dashboard') }}"
               class="text-decoration-none">
                <div class="card stat-card h-100 {{ ($filter === $card['filter'] || ($card['filter'] === null && !$filter)) ? 'border border-2' : '' }}"
                     style="{{ ($filter === $card['filter'] || ($card['filter'] === null && !$filter)) ? 'border-color:#'.$card['color'].'!important' : '' }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-label text-muted mb-2">{{ $card['label'] }}</div>
                                <div class="stat-value" style="color: #{{ $card['color'] }}">{{ $card['value'] }}</div>
                            </div>
                            <i class="bi {{ $card['icon'] }} stat-icon" style="color: #{{ $card['color'] }}; font-size: 2.4rem; opacity: .12;"></i>
                        </div>
                        @if ($card['filter'])
                            <div class="mt-2">
                                <div class="battery-bar">
                                    <div class="battery-bar-fill"
                                         style="width: {{ $counts['total'] > 0 ? round($card['value'] / $counts['total'] * 100) : 0 }}%;
                                                background: #{{ $card['color'] }};">
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </a>
        </div>
    @endforeach
</div>

{{-- Lock Table --}}
<div class="card">
    <div class="card-body p-0">
        {{-- Table toolbar --}}
        <div class="d-flex align-items-center gap-3 px-3 pt-3 pb-2 border-bottom">
            <div class="input-group input-group-sm" style="max-width: 280px;">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text" id="lockSearch" class="form-control border-start-0 ps-0"
                       placeholder="Search locks…" autocomplete="off">
            </div>
            @if ($filter)
                <span class="text-muted small">
                    Filtered: <span class="badge text-bg-secondary text-uppercase">{{ $filter }}</span>
                    <a href="{{ route('dashboard') }}" class="ms-1 small">clear</a>
                </span>
            @endif
            <span class="text-muted small ms-auto" id="lockCount">{{ $locks->total() }} locks</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="lockTable">
                <thead>
                    <tr>
                        <th class="ps-3">Lock</th>
                        <th>Location</th>
                        <th>Battery</th>
                        <th>Last seen</th>
                        <th class="text-muted">SALTO ID</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($locks as $lock)
                        @php $s = $lock->status(); @endphp
                        <tr class="{{ $s->value === 'flat' ? 'row-flat' : ($s->value === 'low' ? 'row-low' : '') }}">
                            <td class="ps-3 fw-semibold">{{ $lock->name }}</td>
                            <td class="text-muted">{{ $lock->location ?? '—' }}</td>
                            <td>
                                <span class="badge text-bg-{{ $s->color() }} d-inline-flex align-items-center gap-1">
                                    @if($s->value === 'flat')
                                        <i class="bi bi-battery"></i>
                                    @elseif($s->value === 'low')
                                        <i class="bi bi-battery-half"></i>
                                    @elseif($s->value === 'normal')
                                        <i class="bi bi-battery-full"></i>
                                    @else
                                        <i class="bi bi-question-circle"></i>
                                    @endif
                                    {{ $s->label() }}
                                </span>
                            </td>
                            <td class="text-muted small">
                                {{ $lock->last_seen_at ? $lock->last_seen_at->format('d/m/Y H:i') : '—' }}
                            </td>
                            <td class="text-muted small">{{ $lock->salto_id }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="bi bi-database-x d-block mb-2" style="font-size:2rem;opacity:.3"></i>
                                No locks synced yet. Click <strong>Sync now</strong> above.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3 d-flex justify-content-end">{{ $locks->links() }}</div>
@endsection

@push('scripts')
<script>
    const searchInput = document.getElementById('lockSearch');
    const rows = document.querySelectorAll('#lockTable tbody tr');
    const lockCount = document.getElementById('lockCount');

    searchInput.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        let visible = 0;
        rows.forEach(row => {
            const match = !q || row.textContent.toLowerCase().includes(q);
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        lockCount.textContent = visible + ' lock' + (visible !== 1 ? 's' : '');
    });
</script>
@endpush
