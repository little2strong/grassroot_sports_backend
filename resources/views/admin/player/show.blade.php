@extends('admin.layouts.master')
@section('players', 'active')
@section('title', $title ?? 'Player Details')

@push('style')
<style>
    /* Custom Styling Overrides for Modern Aesthetic */
    .back-btn {
        color: var(--text-gray);
        font-weight: 500;
        transition: color 0.2s ease, transform 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .back-btn:hover {
        color: var(--primary-blue);
        transform: translateX(-2px);
    }
    
    .stat-card-modern {
        border-radius: 16px !important;
        background: #ffffff;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: 1px solid rgba(0, 0, 0, 0.04) !important;
    }
    .stat-card-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 18px rgba(0, 106, 108, 0.05);
    }
    .icon-shape {
        transition: transform 0.3s ease;
    }
    .stat-card-modern:hover .icon-shape {
        transform: scale(1.08);
    }
    .bg-primary-soft { background-color: rgba(0, 106, 108, 0.1); }
    .text-primary-custom { color: #006a6c; }
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
    .bg-danger-soft { background-color: rgba(220, 53, 69, 0.1); }
    .bg-info-soft { background-color: rgba(13, 202, 240, 0.1); }
    .bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); }
    
    /* Career stat item styling */
    .career-stat-card {
        border-radius: 12px;
        background-color: #f8f9fa;
        padding: 15px 10px;
        border: 1px solid rgba(0, 0, 0, 0.02);
        transition: background-color 0.2s ease;
    }
    .career-stat-card:hover {
        background-color: #f1f3f5;
    }
    
    /* Modern Dashboard Card */
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
    .dashboard-card .card-body {
        padding: 20px;
    }
    
    /* Table Styling */
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
        padding: 12px 16px;
        vertical-align: middle;
        border-bottom: 1px solid rgba(0, 0, 0, 0.04);
    }
    .table-modern tbody tr {
        transition: background-color 0.2s ease;
    }
    .table-modern tbody tr:hover {
        background-color: #f9fbfd;
    }
    
    /* Soft Badges */
    .badge-soft-success { background-color: rgba(25, 135, 84, 0.1); color: #198754; }
    .badge-soft-danger { background-color: rgba(220, 53, 69, 0.1); color: #dc3545; }
    .badge-soft-warning { background-color: rgba(255, 193, 7, 0.1); color: #b58105; }
    .badge-soft-info { background-color: rgba(13, 202, 240, 0.15); color: #0b9cb8; }
    .badge-soft-secondary { background-color: rgba(108, 117, 125, 0.1); color: #6c757d; }
    .badge-soft-primary { background-color: rgba(0, 106, 108, 0.1); color: #006a6c; }
    
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
</style>
@endpush

@section('content')
<main class="container-fluid p-3 p-lg-4">
    {{-- Top Navigation Header --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <a href="{{ route('admin.players.index') }}" class="back-btn text-decoration-none small">
                <i class="fas fa-arrow-left"></i> Back to players
            </a>
            <div class="d-flex align-items-center gap-2 mt-2">
                <h2 class="page-content-title fw-bold text-dark fs-4 mb-0">{{ $player->name }}</h2>
                <span class="badge-pill-custom {{ $player->is_active ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                    {{ $player->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
            <p class="text-muted mb-0 small mt-1"><i class="fas fa-envelope me-1"></i>{{ $player->email }}</p>
        </div>
        <div>
            <form method="POST" action="{{ route('admin.players.toggle-active', $player) }}">
                @csrf
                <button type="submit" class="btn btn-sm {{ $player->is_active ? 'btn-outline-warning' : 'btn-primary-custom' }} py-2 px-3">
                    {{ $player->is_active ? 'Deactivate Player' : 'Activate Player' }}
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="background-color: rgba(25, 135, 84, 0.1); color: #198754; border-left: 4px solid #198754 !important;">
            <i class="fas fa-circle-check me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Stats Cards Row --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="card stat-card-modern p-3">
                <div class="card-body p-0 d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted mb-1 small fw-medium">Clubs</p>
                        <h4 class="mb-0 fw-bold text-dark">{{ $stats['clubs'] }}</h4>
                    </div>
                    <div class="icon-shape bg-primary-soft text-primary-custom rounded-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                        <i class="fas fa-shield"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card stat-card-modern p-3">
                <div class="card-body p-0 d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted mb-1 small fw-medium">Squads</p>
                        <h4 class="mb-0 fw-bold text-dark">{{ $stats['teams'] }}</h4>
                    </div>
                    <div class="icon-shape bg-info-soft text-info rounded-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                        <i class="fas fa-network-wired"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card stat-card-modern p-3">
                <div class="card-body p-0 d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted mb-1 small fw-medium">Matches</p>
                        <h4 class="mb-0 fw-bold text-dark">{{ $stats['total_matches'] }}</h4>
                    </div>
                    <div class="icon-shape bg-success-soft text-success rounded-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                        <i class="fas fa-baseball-ball"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card stat-card-modern p-3">
                <div class="card-body p-0 d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted mb-1 small fw-medium">Runs</p>
                        <h4 class="mb-0 fw-bold text-dark">{{ $stats['total_runs'] }}</h4>
                    </div>
                    <div class="icon-shape bg-warning-soft text-warning rounded-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                        <i class="fas fa-running"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card stat-card-modern p-3">
                <div class="card-body p-0 d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted mb-1 small fw-medium">Wickets</p>
                        <h4 class="mb-0 fw-bold text-dark">{{ $stats['total_wickets'] }}</h4>
                    </div>
                    <div class="icon-shape bg-danger-soft text-danger rounded-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                        <i class="fas fa-hand-holding-ball"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Details Layout --}}
    <div class="row g-4">
        {{-- Profile Panel --}}
        <div class="col-lg-4">
            <div class="card dashboard-card h-100">
                <div class="card-header bg-white"><h6 class="mb-0 fw-semibold text-dark"><i class="fas fa-user-circle me-2 text-primary-custom"></i>Player Profile</h6></div>
                <div class="card-body text-center">
                    <div class="text-center mb-4">
                        @if($player->image)
                            <img src="{{ asset($player->image) }}" alt="" class="rounded-circle shadow-sm" style="width:96px;height:96px;object-fit:cover;border: 3px solid #f8f9fa;">
                        @else
                            @php
                                $names = explode(' ', $player->name);
                                $initials = strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));
                            @endphp
                            <div class="rounded-circle text-white d-inline-flex align-items-center justify-content-center fw-bold shadow-sm" style="width:96px;height:96px;font-size:2rem;background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue));">
                                {{ $initials }}
                            </div>
                        @endif
                    </div>
                    <table class="table table-borderless table-sm text-start mb-0 align-middle">
                        <tr style="border-bottom: 1px solid rgba(0,0,0,0.04);"><th class="text-muted fw-medium py-2">Phone</th><td class="text-dark fw-semibold py-2 text-end">{{ $player->phone ?? '—' }}</td></tr>
                        <tr style="border-bottom: 1px solid rgba(0,0,0,0.04);"><th class="text-muted fw-medium py-2">Primary Role</th><td class="py-2 text-end">
                            <span class="badge-pill-custom badge-soft-primary">
                                {{ $player->playerProfile?->role_label ?? '—' }}
                            </span>
                        </td></tr>
                        <tr style="border-bottom: 1px solid rgba(0,0,0,0.04);"><th class="text-muted fw-medium py-2">Batting Style</th><td class="text-dark py-2 text-end">{{ $player->playerProfile?->batting_style_label ?? '—' }}</td></tr>
                        <tr style="border-bottom: 1px solid rgba(0,0,0,0.04);"><th class="text-muted fw-medium py-2">Bowling Style</th><td class="text-dark py-2 text-end">{{ $player->playerProfile?->bowling_style_label ?? '—' }}</td></tr>
                        <tr style="border-bottom: 1px solid rgba(0,0,0,0.04);"><th class="text-muted fw-medium py-2">Onboarded</th><td class="py-2 text-end">
                            <span class="badge-pill-custom {{ $player->is_onboarded ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                                {{ $player->is_onboarded ? 'Yes' : 'No' }}
                            </span>
                        </td></tr>
                        <tr><th class="text-muted fw-medium py-2">Joined</th><td class="text-muted py-2 text-end small"><i class="far fa-clock me-1"></i>{{ $player->created_at->format('d M Y') }}</td></tr>
                    </table>
                    @if($player->playerProfile?->bio)
                        <hr class="my-3" style="border-color: rgba(0,0,0,0.08);">
                        <p class="small text-muted text-start mb-0" style="line-height: 1.5;">{{ $player->playerProfile->bio }}</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Tables Column --}}
        <div class="col-lg-8">
            {{-- Career Stats (only if player profile exists) --}}
            @if($player->playerProfile)
            <div class="card dashboard-card mb-4">
                <div class="card-header bg-white"><h6 class="mb-0 fw-semibold text-dark"><i class="fas fa-trophy me-2 text-primary-custom"></i>Career Statistics</h6></div>
                <div class="card-body py-3">
                    <div class="row g-2 text-center">
                        <div class="col-4 col-md-2">
                            <div class="career-stat-card">
                                <div class="fs-5 fw-bold text-dark">{{ $player->playerProfile->total_matches }}</div>
                                <div class="text-muted" style="font-size: 11px;">Matches</div>
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="career-stat-card">
                                <div class="fs-5 fw-bold text-dark">{{ $player->playerProfile->total_runs }}</div>
                                <div class="text-muted" style="font-size: 11px;">Runs</div>
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="career-stat-card">
                                <div class="fs-5 fw-bold text-dark">{{ $player->playerProfile->total_wickets }}</div>
                                <div class="text-muted" style="font-size: 11px;">Wickets</div>
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="career-stat-card">
                                <div class="fs-5 fw-bold text-dark">{{ $player->playerProfile->highest_score }}</div>
                                <div class="text-muted" style="font-size: 11px;">Highest</div>
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="career-stat-card">
                                <div class="fs-5 fw-bold text-dark">{{ $player->playerProfile->total_fifties }}</div>
                                <div class="text-muted" style="font-size: 11px;">50s / 100s</div>
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="career-stat-card">
                                <div class="fs-5 fw-bold text-dark">{{ $player->playerProfile->average ?? '—' }}</div>
                                <div class="text-muted" style="font-size: 11px;">Average</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Club Memberships --}}
            <div class="card dashboard-card mb-4">
                <div class="card-header bg-white"><h6 class="mb-0 fw-semibold text-dark"><i class="fas fa-shield me-2 text-primary-custom"></i>Club Memberships</h6></div>
                <div class="table-responsive">
                    <table class="table table-modern mb-0">
                        <thead>
                            <tr>
                                <th>Club</th>
                                <th>Role</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Joined Date</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($player->clubMemberships as $membership)
                                <tr>
                                    <td class="fw-bold text-dark">{{ $membership->club?->name ?? '—' }}</td>
                                    <td><span class="badge-pill-custom badge-soft-primary">{{ ucfirst($membership->role) }}</span></td>
                                    <td class="text-center">
                                        <span class="badge-pill-custom {{ $membership->status === 'active' ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                                            {{ ucfirst($membership->status) }}
                                        </span>
                                    </td>
                                    <td class="text-center small text-muted"><i class="far fa-clock me-1"></i>{{ $membership->joined_at?->format('d M Y') ?? '—' }}</td>
                                    <td class="text-center">
                                        @if($membership->club)
                                            <a href="{{ route('admin.clubs.show', $membership->club) }}" class="btn btn-xs btn-outline-primary border-0 rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;" title="View Club details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4"><i class="fas fa-shield-slash fs-4 text-muted mb-2"></i><br>Not a member of any club.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Squads --}}
            <div class="card dashboard-card mb-4">
                <div class="card-header bg-white"><h6 class="mb-0 fw-semibold text-dark"><i class="fas fa-network-wired me-2 text-primary-custom"></i>Squads assigned</h6></div>
                <div class="table-responsive">
                    <table class="table table-modern mb-0">
                        <thead>
                            <tr>
                                <th>Squad</th>
                                <th>Club</th>
                                <th>Squad Role</th>
                                <th class="text-center">Jersey Number</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($player->teams as $team)
                                <tr>
                                    <td class="fw-bold text-dark">{{ $team->name }}</td>
                                    <td class="small text-muted">{{ $team->club?->name ?? '—' }}</td>
                                    <td><span class="badge-pill-custom badge-soft-primary">{{ ucfirst($team->pivot->role ?? 'player') }}</span></td>
                                    <td class="text-center fw-bold text-dark">{{ $team->pivot->jersey_number ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4"><i class="fas fa-network-wired fs-4 text-muted mb-2"></i><br>Not assigned to any squads yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Recent Availability --}}
            <div class="card dashboard-card">
                <div class="card-header bg-white"><h6 class="mb-0 fw-semibold text-dark"><i class="fas fa-calendar-check me-2 text-primary-custom"></i>Recent Availability Responses</h6></div>
                <div class="table-responsive">
                    <table class="table table-modern mb-0">
                        <thead>
                            <tr>
                                <th>Fixture</th>
                                <th class="text-center">Availability Status</th>
                                <th class="text-center">Responded Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($player->availabilityResponses as $availability)
                                <tr>
                                    <td class="small text-dark fw-semibold">
                                        @if($availability->fixture)
                                            {{ $availability->fixture->home_display_name }} <span class="text-muted fw-normal">vs</span> {{ $availability->fixture->away_display_name }}
                                            <div class="text-muted small" style="font-size: 0.72rem;"><i class="far fa-calendar me-1"></i>{{ $availability->fixture->scheduled_date?->format('d M Y') }}</div>
                                        @else
                                            Fixture #{{ $availability->fixture_id }}
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $availStatus = 'badge-soft-secondary';
                                            if ($availability->status_label === 'Available') {
                                                $availStatus = 'badge-soft-success';
                                            } elseif ($availability->status_label === 'Unavailable') {
                                                $availStatus = 'badge-soft-danger';
                                            }
                                        @endphp
                                        <span class="badge-pill-custom {{ $availStatus }}">
                                            {{ $availability->status_label }}
                                        </span>
                                    </td>
                                    <td class="text-center small text-muted"><i class="far fa-clock me-1"></i>{{ $availability->responded_at?->format('d M Y H:i') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-4"><i class="fas fa-calendar-xmark fs-4 text-muted mb-2"></i><br>No availability responses yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
