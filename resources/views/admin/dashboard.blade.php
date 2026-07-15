@extends('admin.layouts.master')

@push('style')
<style>
    /* Custom Styling Overrides for Modern Aesthetic */
    .dashboard-banner {
        position: relative;
        background: linear-gradient(135deg, var(--dark-blue) 0%, var(--primary-blue) 100%);
        border-radius: 16px !important;
        box-shadow: 0 10px 30px -10px rgba(0, 106, 108, 0.3);
    }
    .banner-circle-1 {
        position: absolute;
        top: -50px;
        right: -50px;
        width: 180px;
        height: 180px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
        filter: blur(10px);
    }
    .banner-circle-2 {
        position: absolute;
        bottom: -30px;
        right: 100px;
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.04);
        filter: blur(15px);
    }
    
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
    
    /* Live Pulsing Elements */
    .live-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        animation: pulse-dot 1.5s infinite;
        display: inline-block;
        box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
    }
    @keyframes pulse-dot {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(220, 53, 69, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
    }
    .animate-pulse {
        animation: text-pulse 2s infinite ease-in-out;
    }
    @keyframes text-pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    
    /* Live Match Grid */
    .scoreboard-card {
        border-radius: 16px !important;
        border: none !important;
        background: linear-gradient(145deg, #0d1b2a, #1b263b);
        color: #ffffff;
        box-shadow: 0 8px 24px rgba(13, 27, 42, 0.15);
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .scoreboard-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 28px rgba(13, 27, 42, 0.25);
    }
    .scoreboard-header {
        background-color: rgba(255, 255, 255, 0.05);
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        padding: 10px 16px;
    }
    
    /* Growth Chart Legend and Styling */
    .chart-container-modern {
        height: 250px;
        position: relative;
    }
    
    /* Modern list group and tables */
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
    
    .list-group-flush .list-group-item {
        border-color: rgba(0, 0, 0, 0.04);
        padding: 14px 20px;
        transition: background-color 0.2s ease;
    }
    .list-group-flush .list-group-item:hover {
        background-color: #f8f9fa;
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
    
    /* Avatars with Gradient Initials */
    .avatar-initials {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        color: #ffffff;
        font-weight: 600;
        font-size: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue));
        box-shadow: 0 3px 6px rgba(0, 106, 108, 0.15);
    }
    
    /* Soft Badges */
    .bg-primary-soft { background-color: rgba(0, 106, 108, 0.1); }
    .text-primary-custom { color: #006a6c; }
    .bg-info-soft { background-color: rgba(13, 202, 240, 0.1); }
    .bg-danger-soft { background-color: rgba(220, 53, 69, 0.1); }
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
    .bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); }
    .text-warning { color: #ffc107 !important; }
    .text-info { color: #0dcaf0 !important; }
    .text-success { color: #198754 !important; }
    .text-danger { color: #dc3545 !important; }
    
    .badge-soft-primary { background-color: rgba(0, 106, 108, 0.1); color: #006a6c; }
    .badge-soft-success { background-color: rgba(25, 135, 84, 0.1); color: #198754; }
    .badge-soft-danger { background-color: rgba(220, 53, 69, 0.1); color: #dc3545; }
    .badge-soft-warning { background-color: rgba(255, 193, 7, 0.1); color: #b58105; }
    .badge-soft-info { background-color: rgba(13, 202, 240, 0.15); color: #0b9cb8; }
    .badge-soft-secondary { background-color: rgba(108, 117, 125, 0.1); color: #6c757d; }
    
    .badge-pill-custom {
        padding: 5px 10px;
        font-size: 11px;
        font-weight: 600;
        border-radius: 30px;
        display: inline-block;
    }
    
    .hover-primary:hover {
        color: var(--primary-blue) !important;
    }
    
    /* Scrollbar for dashboard elements */
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 2px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
</style>
@endpush

@section('dashboard', 'active')
@section('title', $title ?? 'Dashboard')

@section('content')
<main class="container-fluid px-3 px-lg-4 py-3">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert" style="background-color: rgba(25, 135, 84, 0.1); color: #198754; border-left: 4px solid #198754 !important;">
            <i class="fas fa-circle-check me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Welcome Banner --}}
    <div class="dashboard-banner mb-4 p-4 text-white position-relative overflow-hidden">
        <div class="position-relative" style="z-index: 2;">
            <p class="text-white-50 mb-1 small text-uppercase fw-bold" style="letter-spacing: 1px;">Overview</p>
            <h2 class="fw-bold mb-1">Grassroot Sports Dashboard</h2>
            <p class="text-white-50 mb-0 small">
                <i class="far fa-calendar-alt me-1"></i> {{ now()->format('l, d M Y') }} · Welcome back, administrator!
            </p>
        </div>
        <div class="banner-circle-1"></div>
        <div class="banner-circle-2"></div>
    </div>

    @php
        $clubPercent = $stats['clubs'] > 0 ? round(($stats['clubs_verified'] / $stats['clubs']) * 100) : 0;
        $playerPercent = $stats['players'] > 0 ? round(($stats['players_active'] / $stats['players']) * 100) : 0;
    @endphp

    {{-- Stats Row --}}
    <div class="row g-3 g-lg-4 mb-4">
        <div class="col-6 col-xl-3">
            <div class="card stat-card-modern h-100 p-3 position-relative overflow-hidden">
                <div class="card-body p-0 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-medium">Total Clubs</p>
                            <h3 class="fw-bold text-dark mb-0">{{ $stats['clubs'] }}</h3>
                        </div>
                        <div class="icon-shape bg-primary-soft text-primary-custom rounded-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fas fa-shield-halved fs-5"></i>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-1 small text-muted">
                            <span>{{ $stats['clubs_verified'] }} verified</span>
                            <span class="fw-semibold">{{ $clubPercent }}%</span>
                        </div>
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar" role="progressbar" style="width: {{ $clubPercent }}%; background-color: var(--primary-blue);" aria-valuenow="{{ $clubPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
                <a href="{{ route('admin.clubs.index') }}" class="stretched-link"></a>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="card stat-card-modern h-100 p-3 position-relative overflow-hidden">
                <div class="card-body p-0 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-medium">Total Players</p>
                            <h3 class="fw-bold text-dark mb-0">{{ $stats['players'] }}</h3>
                        </div>
                        <div class="icon-shape bg-info-soft text-info rounded-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fas fa-users fs-5"></i>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-1 small text-muted">
                            <span>{{ $stats['players_active'] }} active</span>
                            <span class="fw-semibold">{{ $playerPercent }}%</span>
                        </div>
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar bg-info" role="progressbar" style="width: {{ $playerPercent }}%" aria-valuenow="{{ $playerPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
                <a href="{{ route('admin.players.index') }}" class="stretched-link"></a>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="card stat-card-modern h-100 p-3 position-relative overflow-hidden {{ $stats['fixtures_live'] > 0 ? 'border-start border-danger border-4' : '' }}">
                <div class="card-body p-0 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-medium">Total Fixtures</p>
                            <h3 class="fw-bold text-dark mb-0">{{ $stats['fixtures_total'] }}</h3>
                        </div>
                        <div class="icon-shape bg-danger-soft text-danger rounded-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fas fa-calendar-alt fs-5"></i>
                        </div>
                    </div>
                    <div>
                        <p class="text-muted mb-0 small text-truncate">
                            {{ $stats['fixtures_upcoming'] }} upcoming ·
                            <span class="{{ $stats['fixtures_live'] > 0 ? 'text-danger fw-semibold animate-pulse' : '' }}">
                                {{ $stats['fixtures_live'] }} live
                            </span> ·
                            {{ $stats['fixtures_today'] }} today
                        </p>
                    </div>
                </div>
                <span class="stretched-link"></span>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="card stat-card-modern h-100 p-3 position-relative overflow-hidden">
                <div class="card-body p-0 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-medium">Completed Matches</p>
                            <h3 class="fw-bold text-dark mb-0">{{ $stats['fixtures_completed'] }}</h3>
                        </div>
                        <div class="icon-shape bg-success-soft text-success rounded-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fas fa-circle-check fs-5"></i>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex align-items-center justify-content-between small text-muted">
                            <span>Pending invitations</span>
                            <span class="badge-pill-custom badge-soft-warning">{{ $stats['invitations_pending'] }}</span>
                        </div>
                    </div>
                </div>
                <a href="{{ route('admin.clubs.index') }}" class="stretched-link"></a>
            </div>
        </div>
    </div>

    {{-- Live Matches Block --}}
    @if($liveFixtures->count() > 0)
    <div class="mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="mb-0 d-flex align-items-center gap-2 fw-bold text-dark fs-5">
                <span class="live-dot bg-danger"></span> Live Matches
            </h5>
            <span class="badge-pill-custom badge-soft-danger animate-pulse">{{ $liveFixtures->count() }} matches live</span>
        </div>
        <div class="row g-3">
            @foreach($liveFixtures as $fixture)
            <div class="col-md-6 col-xl-4">
                <div class="card scoreboard-card">
                    <div class="scoreboard-header d-flex justify-content-between align-items-center">
                        <span class="badge-pill-custom badge-soft-danger animate-pulse px-2 py-1"><i class="fas fa-circle me-1 small"></i>{{ strtoupper($fixture->status) }}</span>
                        <span class="small text-white-50 text-truncate max-w-150"><i class="fas fa-building me-1"></i>{{ $fixture->club?->name }}</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="min-w-0 flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold text-white text-truncate">{{ $fixture->home_display_name }}</span>
                                    <span class="font-monospace text-warning fw-bold fs-6">
                                        @if($fixture->home_team_runs !== null)
                                            {{ $fixture->home_team_runs }}/{{ $fixture->home_team_wickets }}
                                            <span class="text-white-50" style="font-size: 11px;">({{ $fixture->home_team_overs }})</span>
                                        @else
                                            —
                                        @endif
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-white text-truncate">{{ $fixture->away_display_name }}</span>
                                    <span class="font-monospace text-warning fw-bold fs-6">
                                        @if($fixture->away_team_runs !== null)
                                            {{ $fixture->away_team_runs }}/{{ $fixture->away_team_wickets }}
                                            <span class="text-white-50" style="font-size: 11px;">({{ $fixture->away_team_overs }})</span>
                                        @else
                                            —
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                        <hr class="my-2" style="border-color: rgba(255,255,255,0.1)">
                        <div class="d-flex justify-content-between align-items-center text-white-50" style="font-size: 11px;">
                            <span><i class="fas fa-trophy me-1 text-warning"></i>{{ $fixture->match_type_label }}</span>
                            @if($fixture->venue)
                                <span class="text-truncate max-w-150"><i class="fas fa-map-marker-alt me-1"></i>{{ $fixture->venue->name }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Chart & Today's Matches Row --}}
    <div class="row g-3 g-lg-4 mb-4">
        <div class="col-lg-8">
            <div class="card dashboard-card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold text-dark"><i class="fas fa-chart-line me-2 text-primary-custom"></i>Growth — Last 30 Days</h6>
                    <div class="d-flex gap-3 small">
                        <span><span class="d-inline-block rounded-circle me-1" style="width:10px;height:10px;background:#0d6efd;"></span> Players</span>
                        <span><span class="d-inline-block rounded-circle me-1" style="width:10px;height:10px;background:#198754;"></span> Clubs</span>
                        <span><span class="d-inline-block rounded-circle me-1" style="width:10px;height:10px;background:#6f42c1;"></span> Fixtures</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container-modern">
                        <canvas id="growthChart" style="height: 250px;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card dashboard-card h-100">
                <div class="card-header bg-white"><h6 class="mb-0 fw-semibold text-dark"><i class="fas fa-calendar-day me-2 text-primary-custom"></i>Today's Matches</h6></div>
                <div class="card-body p-3 custom-scrollbar" style="max-height: 290px; overflow-y: auto;">
                    @forelse($todayFixtures as $fixture)
                        <div class="p-3 mb-2 rounded-3 border-0 shadow-sm" style="background-color: #f8f9fa; border-left: 3px solid var(--primary-blue) !important;">
                            <div class="d-flex justify-content-between align-items-center gap-2">
                                <div class="min-w-0">
                                    <p class="mb-1 fw-bold text-dark text-truncate" style="font-size: 0.85rem;">
                                        {{ $fixture->home_display_name }} <span class="text-muted fw-normal">vs</span> {{ $fixture->away_display_name }}
                                    </p>
                                    <p class="mb-0 text-muted" style="font-size:.75rem;">
                                        <i class="far fa-clock me-1"></i>{{ $fixture->scheduled_time?->format('H:i') ?? 'TBC' }} · <span class="badge bg-light text-dark">{{ strtoupper($fixture->match_type) }}</span>
                                    </p>
                                </div>
                                @php
                                    $statusClass = 'badge-soft-secondary';
                                    if ($fixture->status === 'live') {
                                        $statusClass = 'badge-soft-danger animate-pulse';
                                    } elseif ($fixture->status === 'completed') {
                                        $statusClass = 'badge-soft-success';
                                    } elseif ($fixture->status === 'published') {
                                        $statusClass = 'badge-soft-primary';
                                    }
                                @endphp
                                <span class="badge-pill-custom {{ $statusClass }}">
                                    {{ ucfirst($fixture->status) }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-xmark text-muted mb-2 fs-3"></i>
                            <p class="text-muted mb-0 small">No matches scheduled for today.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Upcoming/Recent/Pending Lists Row --}}
    <div class="row g-3 g-lg-4 mb-4">
        <div class="col-md-4">
            <div class="card dashboard-card h-100">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold text-dark"><i class="fas fa-calendar-days text-primary-custom me-2"></i>Upcoming Fixtures</h6>
                </div>
                <div class="list-group list-group-flush custom-scrollbar" style="max-height: 350px; overflow-y: auto;">
                    @forelse($upcomingFixtures as $fixture)
                        <div class="list-group-item border-0 border-bottom">
                            <p class="mb-1 fw-bold text-dark small">{{ $fixture->home_display_name }} <span class="text-muted fw-normal">vs</span> {{ $fixture->away_display_name }}</p>
                            <p class="mb-0 text-muted" style="font-size:.75rem;">
                                <i class="far fa-calendar me-1"></i>{{ $fixture->scheduled_date->format('d M Y') }}
                                @if($fixture->scheduled_time) · <i class="far fa-clock me-1"></i>{{ $fixture->scheduled_time->format('H:i') }} @endif
                                · <span class="text-primary-custom fw-semibold">{{ $fixture->club?->name }}</span>
                            </p>
                        </div>
                    @empty
                        <div class="list-group-item text-muted text-center py-5 small">No upcoming fixtures.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card dashboard-card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold text-dark"><i class="fas fa-shield-halved text-primary-custom me-2"></i>Recent Clubs</h6>
                    <a href="{{ route('admin.clubs.index') }}" class="btn btn-xs btn-link text-decoration-none p-0 small fw-semibold">View all</a>
                </div>
                <div class="list-group list-group-flush custom-scrollbar" style="max-height: 350px; overflow-y: auto;">
                    @forelse($recentClubs as $club)
                        <div class="list-group-item border-0 border-bottom">
                            <p class="mb-1 fw-bold text-dark small">{{ $club->name }}</p>
                            <div class="d-flex justify-content-between align-items-center" style="font-size:.75rem;">
                                <span class="text-muted"><i class="far fa-user me-1"></i>{{ $club->owner?->name ?? 'No owner' }} · {{ $club->created_at->diffForHumans() }}</span>
                                <a href="{{ route('admin.clubs.show', $club) }}" class="fw-semibold text-primary-custom text-decoration-none">Details <i class="fas fa-chevron-right ms-1" style="font-size: 8px;"></i></a>
                            </div>
                        </div>
                    @empty
                        <div class="list-group-item text-muted text-center py-5 small">No clubs registered.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card dashboard-card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold text-dark"><i class="fas fa-paper-plane text-primary-custom me-2"></i>Pending Invites</h6>
                    <span class="badge-pill-custom badge-soft-warning">{{ $stats['invitations_pending'] }}</span>
                </div>
                <div class="list-group list-group-flush custom-scrollbar" style="max-height: 350px; overflow-y: auto;">
                    @forelse($pendingInvitations as $inv)
                        <div class="list-group-item border-0 border-bottom">
                            <p class="mb-1 fw-bold text-dark small text-truncate">{{ $inv->invited_email }}</p>
                            <p class="mb-0 text-muted" style="font-size:.75rem;">
                                <i class="fas fa-network-wired me-1"></i>{{ $inv->club?->name }}{{ $inv->team ? ' → '.$inv->team->name : '' }}
                                · <i class="far fa-clock me-1"></i>{{ $inv->created_at->diffForHumans() }}
                            </p>
                        </div>
                    @empty
                        <div class="list-group-item text-muted text-center py-5 small">No pending invitations.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Players Table & Activity Log Row --}}
    <div class="row g-3 g-lg-4 mb-4">
        <div class="col-lg-8">
            <div class="card dashboard-card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold text-dark"><i class="fas fa-users text-primary-custom me-2"></i>Recent Players</h6>
                    <a href="{{ route('admin.players.index') }}" class="btn btn-xs btn-link text-decoration-none p-0 small fw-semibold">View all players</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-modern mb-0">
                        <thead>
                            <tr>
                                <th>Player</th>
                                <th>Role</th>
                                <th>Clubs</th>
                                <th>Joined</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentPlayers as $player)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            @php
                                                $names = explode(' ', $player->name);
                                                $initials = strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));
                                            @endphp
                                            <div class="avatar-initials">{{ $initials }}</div>
                                            <div>
                                                <a href="{{ route('admin.players.show', $player) }}" class="text-decoration-none text-dark fw-bold small hover-primary">
                                                    {{ $player->name }}
                                                </a>
                                                <div class="text-muted" style="font-size:.75rem;">{{ $player->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge-pill-custom badge-soft-primary">{{ $player->playerProfile?->role_label ?? '—' }}</span></td>
                                    <td>
                                        <span class="badge-pill-custom badge-soft-info"><i class="fas fa-shield me-1"></i>{{ $player->clubs->count() }}</span>
                                    </td>
                                    <td class="text-muted small"><i class="far fa-clock me-1"></i>{{ $player->created_at->diffForHumans() }}</td>
                                    <td>
                                        <span class="badge-pill-custom {{ $player->is_active ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                                            {{ $player->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-5"><i class="fas fa-users-slash fs-3 mb-2"></i><br>No players registered yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card dashboard-card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold text-dark"><i class="fas fa-history me-2 text-primary-custom"></i>Recent Activity Log</h6>
                </div>
                <div class="card-body p-3 custom-scrollbar" style="max-height: 400px; overflow-y: auto;">
                    @forelse($recentActivity as $activity)
                        <div class="d-flex align-items-start gap-2 mb-3">
                            <div class="avatar-initials bg-light-primary text-primary-custom rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 11px;">
                                @php
                                    $userInitials = '';
                                    if ($activity->user) {
                                        $names = explode(' ', $activity->user->name);
                                        $userInitials = strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));
                                    } else {
                                        $userInitials = 'SYS';
                                    }
                                @endphp
                                {{ $userInitials }}
                            </div>
                            <div class="min-w-0">
                                <p class="mb-0 small text-dark"><span class="fw-semibold">{{ $activity->user?->name ?? 'System' }}</span> {{ $activity->description }}</p>
                                <span class="text-muted" style="font-size: 10px;">{{ $activity->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted text-center py-5 mb-0 small"><i class="fas fa-clock-rotate-left fs-3 text-muted mb-2"></i><br>No recent activity logged.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

</main>
@endsection

@push('script')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('growthChart').getContext('2d');
        const chartData = @json($dates->values());
        
        const labels = chartData.map(d => d.label);
        const playersData = chartData.map(d => d.players);
        const clubsData = chartData.map(d => d.clubs);
        const fixturesData = chartData.map(d => d.fixtures);
        
        // Define gradients
        const playersGradient = ctx.createLinearGradient(0, 0, 0, 250);
        playersGradient.addColorStop(0, 'rgba(13, 110, 253, 0.25)');
        playersGradient.addColorStop(1, 'rgba(13, 110, 253, 0)');
        
        const clubsGradient = ctx.createLinearGradient(0, 0, 0, 250);
        clubsGradient.addColorStop(0, 'rgba(25, 135, 84, 0.25)');
        clubsGradient.addColorStop(1, 'rgba(25, 135, 84, 0)');
        
        const fixturesGradient = ctx.createLinearGradient(0, 0, 0, 250);
        fixturesGradient.addColorStop(0, 'rgba(111, 66, 193, 0.25)');
        fixturesGradient.addColorStop(1, 'rgba(111, 66, 193, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Players',
                        data: playersData,
                        borderColor: '#0d6efd',
                        backgroundColor: playersGradient,
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2,
                        pointRadius: 1,
                        pointHoverRadius: 4
                    },
                    {
                        label: 'Clubs',
                        data: clubsData,
                        borderColor: '#198754',
                        backgroundColor: clubsGradient,
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2,
                        pointRadius: 1,
                        pointHoverRadius: 4
                    },
                    {
                        label: 'Fixtures',
                        data: fixturesData,
                        borderColor: '#6f42c1',
                        backgroundColor: fixturesGradient,
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2,
                        pointRadius: 1,
                        pointHoverRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        padding: 10,
                        cornerRadius: 8,
                        backgroundColor: '#0a2a43'
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#8b95a5',
                            font: {
                                size: 10
                            }
                        }
                    },
                    y: {
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#8b95a5',
                            font: {
                                size: 10
                            },
                            stepSize: 1
                        }
                    }
                }
            }
        });
    });
</script>
@if($liveFixtures->count() > 0)
<script>setTimeout(() => location.reload(), 60000);</script>
@endif
@endpush
