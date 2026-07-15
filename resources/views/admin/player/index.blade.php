@extends('admin.layouts.master')
@section('players', 'active')
@section('title', $title ?? 'Manage Players')

@push('style')
<style>
    /* Custom Styling Overrides for Modern Aesthetic */
    .stat-card-modern {
        border-radius: 16px !important;
        background: #ffffff;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease;
        border: 1px solid rgba(0, 0, 0, 0.04) !important;
    }
    .stat-card-modern:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0, 106, 108, 0.08);
    }
    .icon-shape {
        transition: transform 0.3s ease;
    }
    .stat-card-modern:hover .icon-shape {
        transform: scale(1.1);
    }
    .bg-primary-soft { background-color: rgba(0, 106, 108, 0.1); }
    .text-primary-custom { color: #006a6c; }
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
    .bg-info-soft { background-color: rgba(13, 202, 240, 0.1); }
    .text-info { color: #0dcaf0 !important; }
    .text-success { color: #198754 !important; }
    
    /* Modern filter card */
    .filter-card {
        border-radius: 16px !important;
        border: 1px solid rgba(0, 0, 0, 0.05) !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
    }
    .form-control, .form-select {
        border-radius: 8px;
        padding: 9px 14px;
        border-color: #dee2e6;
        font-size: 0.9rem;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 0.25rem rgba(0, 106, 108, 0.15);
    }
    
    /* Modern table & lists */
    .dashboard-card {
        border-radius: 16px !important;
        border: 1px solid rgba(0, 0, 0, 0.05) !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
    }
    .dashboard-card .card-header {
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        padding: 16px 20px;
        font-weight: 600;
        background-color: #ffffff !important;
    }
    
    .table-modern th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
        color: #8b95a5;
        border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        padding: 12px 16px;
    }
    .table-modern td {
        padding: 14px 16px;
        vertical-align: middle;
        border-bottom: 1px solid rgba(0, 0, 0, 0.04);
    }
    .table-modern tbody tr {
        transition: background-color 0.2s ease;
    }
    .table-modern tbody tr:hover {
        background-color: #f9fbfd;
    }
    
    /* Player Avatar Slot & Initials */
    .player-avatar-slot {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        object-fit: cover;
        box-shadow: 0 3px 6px rgba(0, 0, 0, 0.05);
        border: 2px solid #f8f9fa;
    }
    .avatar-initials {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        color: #ffffff;
        font-weight: 600;
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue));
        box-shadow: 0 3px 6px rgba(0, 106, 108, 0.15);
    }
    
    /* Soft Badges */
    .badge-soft-success { background-color: rgba(25, 135, 84, 0.1); color: #198754; }
    .badge-soft-secondary { background-color: rgba(108, 117, 125, 0.1); color: #6c757d; }
    .badge-soft-primary { background-color: rgba(0, 106, 108, 0.1); color: #006a6c; }
    .badge-soft-info { background-color: rgba(13, 202, 240, 0.15); color: #0b9cb8; }
    
    .badge-pill-custom {
        padding: 5px 12px;
        font-size: 11px;
        font-weight: 600;
        border-radius: 30px;
        display: inline-block;
    }
    
    .hover-primary:hover {
        color: var(--primary-blue) !important;
    }
    
    /* Custom pagination styling to match brand */
    .pagination {
        margin-bottom: 0;
        gap: 5px;
    }
    .page-item .page-link {
        border-radius: 8px !important;
        border: 1px solid #dee2e6;
        color: var(--text-gray);
        font-weight: 500;
        padding: 8px 14px;
        transition: all 0.2s ease;
    }
    .page-item.active .page-link {
        background-color: var(--primary-blue) !important;
        border-color: var(--primary-blue) !important;
        color: #ffffff !important;
    }
    .page-item:not(.active) .page-link:hover {
        background-color: rgba(0, 106, 108, 0.05);
        color: var(--primary-blue);
        border-color: rgba(0, 106, 108, 0.15);
    }
    .page-item.disabled .page-link {
        background-color: #f8f9fa;
        color: #adb5bd;
    }
</style>

@endpush

@section('content')
<main class="container-fluid p-3 p-lg-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="page-content-title fw-bold text-dark fs-4 mb-1">Player Management</h2>
            <p class="text-muted mb-0 small">View registered players, profiles, stats and active club memberships.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="background-color: rgba(25, 135, 84, 0.1); color: #198754; border-left: 4px solid #198754 !important;">
            <i class="fas fa-circle-check me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="background-color: rgba(220, 53, 69, 0.1); color: #dc3545; border-left: 4px solid #dc3545 !important;">
            <i class="fas fa-circle-xmark me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $activePercent = $summary['total'] > 0 ? round(($summary['active'] / $summary['total']) * 100) : 0;
        $onboardPercent = $summary['total'] > 0 ? round(($summary['onboarded'] / $summary['total']) * 100) : 0;
    @endphp

    {{-- Stats Cards Row --}}
    <div class="row g-3 g-lg-4 mb-4">
        <div class="col-md-4">
            <div class="card stat-card-modern p-3">
                <div class="card-body p-0 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-medium">Total Players</p>
                            <h3 class="fw-bold text-dark mb-0">{{ $summary['total'] }}</h3>
                        </div>
                        <div class="icon-shape bg-primary-soft text-primary-custom rounded-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fas fa-users fs-5"></i>
                        </div>
                    </div>
                    <div class="small text-muted">
                        <span class="fw-semibold">{{ $summary['total'] }}</span> registered players
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card stat-card-modern p-3">
                <div class="card-body p-0 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-medium">Active Players</p>
                            <h3 class="fw-bold text-success mb-0">{{ $summary['active'] }}</h3>
                        </div>
                        <div class="icon-shape bg-success-soft text-success rounded-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fas fa-user-check fs-5"></i>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-1 small text-muted">
                            <span>Activity Rate</span>
                            <span class="fw-semibold text-success">{{ $activePercent }}%</span>
                        </div>
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $activePercent }}%" aria-valuenow="{{ $activePercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card stat-card-modern p-3">
                <div class="card-body p-0 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-medium">Onboarded Players</p>
                            <h3 class="fw-bold text-info mb-0">{{ $summary['onboarded'] }}</h3>
                        </div>
                        <div class="icon-shape bg-info-soft text-info rounded-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fas fa-id-card-clip fs-5"></i>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-1 small text-muted">
                            <span>Onboarding Rate</span>
                            <span class="fw-semibold text-info">{{ $onboardPercent }}%</span>
                        </div>
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar bg-info" role="progressbar" style="width: {{ $onboardPercent }}%" aria-valuenow="{{ $onboardPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="card filter-card mb-4">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 g-lg-3 align-items-end">
                <div class="col-md-4 col-lg-4">
                    <label class="form-label small fw-semibold text-muted mb-1">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Name, email, phone...">
                </div>
                <div class="col-sm-6 col-md-2 col-lg-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Active Status</label>
                    <select name="is_active" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="1" @selected(request('is_active') === '1')>Active</option>
                        <option value="0" @selected(request('is_active') === '0')>Inactive</option>
                    </select>
                </div>
                <div class="col-sm-6 col-md-2 col-lg-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Player Role</label>
                    <select name="primary_role" class="form-select">
                        <option value="">All Roles</option>
                        @foreach(['batsman' => 'Batsman', 'bowler' => 'Bowler', 'all_rounder' => 'All Rounder', 'wicket_keeper' => 'Wicket Keeper'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('primary_role') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-2 col-lg-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Onboarded</label>
                    <select name="is_onboarded" class="form-select">
                        <option value="">All Onboarded</option>
                        <option value="1" @selected(request('is_onboarded') === '1')>Yes</option>
                        <option value="0" @selected(request('is_onboarded') === '0')>No</option>
                    </select>
                </div>
                <div class="col-sm-6 col-md-2 col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary-custom w-100 py-2"><i class="fas fa-filter me-1"></i>Filter</button>
                    <a href="{{ route('admin.players.index') }}" class="btn btn-outline-secondary w-50 py-2 d-flex align-items-center justify-content-center"><i class="fas fa-rotate-left"></i></a>
                </div>
            </form>
        </div>
    </div>

    {{-- Player List Table --}}
    <div class="card dashboard-card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-semibold text-dark"><i class="fas fa-list me-2 text-primary-custom"></i>Player List</h5>
            <span class="badge-pill-custom badge-soft-primary">{{ $players->total() }} players total</span>
        </div>
        <div class="table-responsive">
            <table class="table table-modern align-middle mb-0">
                <thead>
                    <tr>
                        <th>Player</th>
                        <th>Clubs</th>
                        <th class="text-center">Matches</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Joined</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($players as $player)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    @if($player->image)
                                        <img src="{{ asset($player->image) }}" alt="" class="player-avatar-slot">
                                    @else
                                        @php
                                            $names = explode(' ', $player->name);
                                            $initials = strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));
                                        @endphp
                                        <div class="avatar-initials">{{ $initials }}</div>
                                    @endif
                                    <div>
                                        <div class="fw-bold">
                                            <a href="{{ route('admin.players.show', $player) }}" class="text-decoration-none text-dark hover-primary">{{ $player->name }}</a>
                                        </div>
                                        <div class="text-muted small d-flex flex-column gap-1" style="font-size: 0.75rem;">
                                            <span><i class="far fa-envelope me-1" style="width: 12px; text-align: center;"></i>{{ $player->email }}</span>
                                            @if($player->phone)
                                                <span><i class="fas fa-phone me-1" style="width: 12px; text-align: center;"></i>{{ $player->phone }}</span>
                                            @endif
                                            @if($player->playerProfile)
                                                <span class="mt-1"><span class="badge-pill-custom badge-soft-primary py-1 px-2" style="font-size: 10px;">{{ $player->playerProfile->role_label }}</span></span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($player->clubs_count > 0)
                                    <div class="small">
                                        <span class="fw-semibold text-dark">{{ $player->clubs->pluck('name')->take(2)->join(', ') }}</span>
                                        @if($player->clubs_count > 2)
                                            <span class="text-muted small"> +{{ $player->clubs_count - 2 }} more</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted small"><i class="fas fa-circle-exclamation me-1 text-warning"></i>No Club Assigned</span>
                                @endif
                            </td>
                            <td class="text-center fw-semibold text-dark">{{ $player->playerProfile?->total_matches ?? 0 }}</td>
                            <td class="text-center">
                                <span class="badge-pill-custom {{ $player->is_active ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                                    {{ $player->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-center small text-muted"><i class="far fa-clock me-1"></i>{{ $player->created_at->format('d M Y') }}</td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('admin.players.show', $player) }}" class="btn btn-sm btn-outline-primary border-0 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="View Profile">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.players.toggle-active', $player) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $player->is_active ? 'btn-outline-warning' : 'btn-outline-success' }} border-0 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="Toggle Status">
                                            <i class="fas {{ $player->is_active ? 'fa-user-slash' : 'fa-user-check' }}"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5"><i class="fas fa-users-slash fs-3 text-muted mb-2"></i><br>No players registered yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($players->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $players->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</main>
@endsection
