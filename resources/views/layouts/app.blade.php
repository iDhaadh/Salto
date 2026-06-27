<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SALTO Battery Monitor')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --salto-bg: #f0f2f5;
            --salto-card-shadow: 0 1px 4px rgba(0,0,0,.08), 0 0 1px rgba(0,0,0,.04);
            --salto-card-shadow-hover: 0 4px 16px rgba(0,0,0,.12);
        }
        body { background: var(--salto-bg); font-size: .9375rem; }
        .navbar { box-shadow: 0 2px 8px rgba(0,0,0,.25); }
        .navbar-brand { font-weight: 700; letter-spacing: -.3px; }
        .navbar-brand i { color: #4ade80; }
        .card { border: none; box-shadow: var(--salto-card-shadow); border-radius: .6rem; }
        .card:hover { box-shadow: var(--salto-card-shadow-hover); transition: box-shadow .2s; }
        .stat-card .card-body { padding: 1.1rem 1.25rem; }
        .stat-card .stat-icon { font-size: 2rem; opacity: .15; }
        .stat-card .stat-value { font-size: 2.2rem; font-weight: 800; line-height: 1; }
        .stat-card .stat-label { font-size: .72rem; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; }
        .table > :not(caption) > * > * { padding: .65rem .85rem; }
        .table thead th { font-size: .78rem; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: #6b7280; border-bottom: 2px solid #e5e7eb; }
        .badge { font-weight: 600; letter-spacing: .02em; }
        .btn-sm { font-size: .8rem; }
        .pagination svg { width: 14px; height: 14px; }
        tr.row-flat td { background: #fff5f5 !important; }
        tr.row-low td { background: #fffbeb !important; }
        .battery-bar { height: 5px; border-radius: 3px; background: #e5e7eb; overflow: hidden; min-width: 60px; }
        .battery-bar-fill { height: 100%; border-radius: 3px; }
        .nav-link { font-size: .9rem; font-weight: 500; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-1">
    <div class="container">
        <a class="navbar-brand" href="{{ route('dashboard') }}">
            <i class="bi bi-battery-half me-1"></i>SALTO Battery Monitor
        </a>
        @auth
            <div class="navbar-nav me-auto ms-3">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <i class="bi bi-lock me-1 opacity-75"></i>Locks
                </a>
                <a class="nav-link {{ request()->routeIs('alerts.*') ? 'active' : '' }}" href="{{ route('alerts.index') }}">
                    <i class="bi bi-bell me-1 opacity-75"></i>Alerts
                    @if(($navOpenAlertCount ?? 0) > 0)
                        <span class="badge bg-danger ms-1" style="font-size:.68rem">{{ $navOpenAlertCount }}</span>
                    @endif
                </a>
                <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.edit') }}">
                    <i class="bi bi-gear me-1 opacity-75"></i>Settings
                </a>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-outline-light btn-sm" type="submit">
                    <i class="bi bi-box-arrow-right me-1"></i>Sign out
                </button>
            </form>
        @endauth
    </div>
</nav>

<main class="container py-4">
    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-check-circle-fill text-success"></i>
            {{ session('status') }}
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill text-danger"></i>
            {{ session('error') }}
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @yield('content')
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
