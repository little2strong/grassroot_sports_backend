@extends('admin.layouts.master')
@section('players', 'active')
@section('title', $title ?? 'Manage Players')

@section('content')
<main class="container-fluid p-3 p-lg-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="page-content-title fw-medium fs-5 mb-1">Player Management</h2>
            <p class="text-muted mb-0 small">View registered players and their club memberships.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card"><div class="card-body py-3">
                <p class="text-muted small mb-1">Total Players</p>
                <h4 class="mb-0 fw-bold">{{ $summary['total'] }}</h4>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card"><div class="card-body py-3">
                <p class="text-muted small mb-1">Active</p>
                <h4 class="mb-0 fw-bold text-success">{{ $summary['active'] }}</h4>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card"><div class="card-body py-3">
                <p class="text-muted small mb-1">Onboarded</p>
                <h4 class="mb-0 fw-bold">{{ $summary['onboarded'] }}</h4>
            </div></div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Name, email, phone...">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Status</label>
                    <select name="is_active" class="form-select">
                        <option value="">All</option>
                        <option value="1" @selected(request('is_active') === '1')>Active</option>
                        <option value="0" @selected(request('is_active') === '0')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Role</label>
                    <select name="primary_role" class="form-select">
                        <option value="">All</option>
                        @foreach(['batsman' => 'Batsman', 'bowler' => 'Bowler', 'all_rounder' => 'All Rounder', 'wicket_keeper' => 'Wicket Keeper'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('primary_role') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Onboarded</label>
                    <select name="is_onboarded" class="form-select">
                        <option value="">All</option>
                        <option value="1" @selected(request('is_onboarded') === '1')>Yes</option>
                        <option value="0" @selected(request('is_onboarded') === '0')>No</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                    <a href="{{ route('admin.players.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Player List</h5>
            <span class="text-muted small">{{ $players->total() }} players</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Player</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Clubs</th>
                        <th class="text-center">Matches</th>
                        <th class="text-center">Onboarded</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Joined</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($players as $player)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($player->image)
                                        <img src="{{ asset($player->image) }}" alt="" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;">
                                    @else
                                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold" style="width:40px;height:40px;">
                                            {{ strtoupper(substr($player->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <div class="fw-semibold">
                                            <a href="{{ route('admin.players.show', $player) }}" class="text-decoration-none text-dark">{{ $player->name }}</a>
                                        </div>
                                        <div class="text-muted small">{{ $player->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="small">{{ $player->phone ?? '—' }}</td>
                            <td>
                                @if($player->playerProfile)
                                    <span class="badge bg-light text-dark">{{ $player->playerProfile->role_label }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($player->clubs_count > 0)
                                    <a href="{{ route('admin.players.show', $player) }}" class="small text-decoration-none">
                                        {{ $player->clubs->pluck('name')->take(2)->join(', ') }}
                                    </a>
                                    @if($player->clubs_count > 2)
                                        <span class="text-muted small">+{{ $player->clubs_count - 2 }} more</span>
                                    @endif
                                @else
                                    <span class="text-muted">No club</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $player->playerProfile?->total_matches ?? 0 }}</td>
                            <td class="text-center">
                                <span class="badge {{ $player->is_onboarded ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $player->is_onboarded ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $player->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $player->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-center small text-muted">{{ $player->created_at->format('d M Y') }}</td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('admin.players.show', $player) }}" class="btn btn-sm btn-outline-primary" title="View player">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.players.toggle-active', $player) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $player->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}" title="Toggle active status">
                                            <i class="fas {{ $player->is_active ? 'fa-user-slash' : 'fa-user-check' }}"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">No players found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($players->hasPages())
            <div class="card-footer bg-white">{{ $players->links() }}</div>
        @endif
    </div>
</main>
@endsection
