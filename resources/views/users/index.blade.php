@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-0">User Management</h1>
        <div class="text-muted small mt-1">Manage who can access SALTO Battery Monitor</div>
    </div>
    <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-person-plus me-1"></i>Add user
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-3">Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td class="ps-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                                     style="width:34px;height:34px;font-size:.8rem;flex-shrink:0;
                                            background:{{ $user->isAdmin() ? '#6366f1' : '#6b7280' }}">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $user->name }}</div>
                                    @if($user->id === auth()->id())
                                        <div class="text-muted" style="font-size:.72rem">You</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="text-muted">{{ $user->email }}</td>
                        <td>
                            @if($user->isAdmin())
                                <span class="badge" style="background:#ede9fe;color:#6d28d9">
                                    <i class="bi bi-shield-check me-1"></i>Admin
                                </span>
                            @else
                                <span class="badge text-bg-secondary">
                                    <i class="bi bi-eye me-1"></i>Viewer
                                </span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ $user->created_at->format('d/m/Y') }}</td>
                        <td class="text-end pe-3 text-nowrap">
                            <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @if($user->id !== auth()->id())
                                <form method="POST" action="{{ route('users.destroy', $user) }}" class="d-inline ms-1">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Delete {{ $user->name }}? This cannot be undone.')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3 p-3 bg-light rounded border small text-muted">
    <i class="bi bi-info-circle me-1"></i>
    <strong>Admin</strong> — full access including Settings and User Management.
    <strong class="ms-2">Viewer</strong> — read-only access to Locks and Alerts only.
</div>
@endsection
