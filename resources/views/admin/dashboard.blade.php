@extends('admin.layouts.master')

@push('style')
<style>
    .chart-col { position: relative; }
    .chart-bar {
        transition: height 0.3s ease;
        border-radius: 3px 3px 0 0;
        cursor: pointer;
    }
    .chart-bar:hover { opacity: 0.8; }
    .stat-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border: 1px solid rgba(0,0,0,.125);
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15) !important;
    }
    .live-dot {
        width: 8px; height: 8px; border-radius: 50%;
        animation: pulse-dot 1.5s infinite;
        display: inline-block;
    }
    @keyframes pulse-dot {
        0%, 100% { opacity: 1; }
        50% { opacity: .4; }
    }
    .fixture-card {
        transition: background-color 0.15s ease;
        border-left: 4px solid transparent;
    }
    .fixture-card:hover {
        background-color: #f8f9fa;
    }
    .fixture-card.is-live { border-left-color: #dc3545; }
    .activity-row {
        animation: fadeSlideIn 0.3s ease forwards;
        opacity: 0;
    }
    @keyframes fadeSlideIn {
        from { opacity: 0; transform: translateX(-10px); }
        to { opacity: 1; transform: translateX(0); }
    }
    .chart-container { height: 200px; display: flex; align-items: flex-end; gap: 2px; }
    .chart-col { display: flex; flex-direction: column; flex: 1; align-items: center; justify-content: flex-end; gap: 1px; position: relative; }
    .chart-line { position: absolute; left: 0; right: 0; height: 1px; background: #e9ecef; }
    .chart-label { font-size: 10px; color: #6c757d; white-space: nowrap; position: absolute; bottom: -18px; left: 50%; transform: translateX(-50%) rotate(-45deg); transform-origin: top left; }
</style>
@endpush

@section('dashboard', 'active')
@section('title', $data['title'] ?? 'Dashboard')

@section('content')
<main class="container-fluid px-3 px-lg-4">

    <!-- ──────────────────────────────────────────────────────────────────────
         ROW 1: STAT CARDS (4 columns)
    ────────────────────────────────────────────────────────────────────── -->
    <div class="row g-3 g-lg-4 mt-1 mb-4">

        {{-- Clubs --}}
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1" style="font-size:.8rem;">Total Clubs</p>
                            <h4 class="mb-0 fw-bold mb-1" style="font-size:1.75rem;">{{ $stats['clubs'] }}</h4>
                            <p class="text-muted mb-0" style="font-size:.75rem;">{{ $stats['clubs_verified'] }} verified</p>
                        </div>
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;background:#d1e7dd;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="#198754" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                    </div>
                    <a href="" class="btn btn-sm btn-outline-primary btn-sm mt-auto w-100">View all →</a>
                </div>
            </div>
        </div>

        {{-- Players --}}
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1" style="font-size:.8rem;">Players</p>
                            <h4 class="mb-0 fw-bold mb-1" style="font-size:1.75rem;">{{ $stats['players'] }}</h4>
                            <p class="text-muted mb-0" style="font-size:.75rem;">{{ $stats['players_active'] }} active</p>
                        </div>
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;background:#cfe2ff;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="#0d6efd" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                    </div>
                    <a href="" class="btn btn-sm btn-outline-primary btn-sm mt-auto w-100">View all →</a>
                </div>
            </div>
        </div>

        {{-- Fixtures --}}
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100 border-danger border-opacity-25">
                @if($stats['fixtures_live'] > 0)
                <span class="position-absolute top-0 end-0 badge bg-danger rounded-0 rounded-bottom-start rounded-end-0" style="font-size:.7rem;border-radius:0 .375rem 0 0 !important;padding:.25rem .6rem;">
                    {{ $stats['fixtures_live'] }} LIVE
                </span>
                @endif
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1" style="font-size:.8rem;">Fixtures</p>
                            <h4 class="mb-0 fw-bold mb-1" style="font-size:1.75rem;">{{ $stats['fixtures_upcoming'] }}</h4>
                            <p class="text-muted mb-0" style="font-size:.75rem;">{{ $stats['fixtures_today'] }} today · {{ $stats['fixtures_live'] }} live</p>
                        </div>
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;background:#e5dbff;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="#6f42c1" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V7m8 4v10a2 2 0 01-2 2H6a2 2 0 01-2-2V7m16 0a2 2 0 002 2v10a2 2 0 002-2V9a2 2 0 00-2-2h-2m-6 0a2 2 0 00-2-2v-2a2 2 0 012-2h2m0 0a2 2 0 00-2-2v-2"/></svg>
                        </div>
                    </div>
                    <a href="#" class="btn btn-sm btn-outline-primary btn-sm mt-auto w-100">Manage →</a>
                </div>
            </div>
        </div>

        {{-- Admins --}}
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1" style="font-size:.8rem;">Admin Users</p>
                            <h4 class="mb-0 fw-bold mb-1" style="font-size:1.75rem;">{{ $stats['admins'] }}</h4>
                            <p class="text-muted mb-0" style="font-size:.75rem;">{{ $stats['admins_active'] }} active</p>
                        </div>
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;background:#fff3cd;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="#f59e0b" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                    </div>
                    <a href="" class="btn btn-sm btn-outline-primary btn-sm mt-auto w-100">Manage →</a>
                </div>
            </div>
        </div>
    </div>

    <!-- ──────────────────────────────────────────────────────────────────────
         ROW 2: LIVE MATCHES (conditional)
    ────────────────────────────────────────────────────────────────────── -->
    @if($liveFixtures->count() > 0)
    <div class="mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="mb-0 d-flex align-items-center gap-2">
                <span class="live-dot bg-danger"></span>
                <span>Live Matches</span>
            </h5>
            <span class="badge bg-danger">{{ $liveFixtures->count() }} in progress</span>
        </div>

        <div class="row g-3">
            @foreach($liveFixtures as $fixture)
            <div class="col-md-6">
                <div class="card fixture-card is-live border-danger border-opacity-50">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-danger">{{ $fixture->scheduled_time?->format('H:i') }}</span>
                        </div>

                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-semibold">{{ $fixture->homeTeam->short_name ?? $fixture->homeTeam->name }}</span>
                            <span class="text-muted mx-2">vs</span>
                            <span class="fw-semibold">{{ $fixture->awayTeam->short_name ?? $fixture->awayTeam->name }}</span>
                        </div>

                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-semibold font-monospace">{{ $fixture->home_team_runs ?? 0 }}/{{ $fixture->home_team_wickets ?? 0 }}</span>
                            <span class="text-muted mx-2">Ov {{ $fixture->home_team_overs ?? '0.0' }}</span>
                            <span class="fw-semibold font-monospace">{{ $fixture->away_team_runs ?? 0 }}/{{ $fixture->away_team_wickets ?? 0 }}</span>
                        </div>

                        @if($fixture->venue)
                            <p class="text-muted mb-0" style="font-size:.8rem;">📍 {{ $fixture->venue->name }}</p>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif

    <!-- ──────────────────────────────────────────────────────────────────────
         ROW 3: CHART + TODAY'S MATCHES + UPCOMING FIXTURES
    ────────────────────────────────────────────────────────────── -->
    <div class="row g-3 g-lg-4 mb-4">

        {{-- Chart (2/3 width) --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Last 30 Days</h6>
                    <div class="d-flex align-items-center gap-3" style="font-size:.8rem;">
                        <span class="d-flex align-items-center gap-1"><span class="rounded-circle d-inline-block" style="width:10px;height:10px;background:#0d6efd;"></span> Players</span>
                        <span class="d-flex align-items-center gap-1"><span class="rounded-circle d-inline-block" style="width:10px;height:10px;background:#198754;"></span> Clubs</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        @php $maxVal = max($stats['players'], $stats['clubs'], 1); @endphp
                        @foreach($dates as $data)
                        <div class="chart-col">
                            {{-- @php
                                // $playerHeight = $data['players'] > 0 ? max(($data['players'] / $maxVal) * 180) : 0;
                                $playerHeight = 0;
                                $clubHeight = $data['clubs'] > 0 ? max(($data['clubs'] / $maxVal) * 180) : 0;
                            @endphp --}}
                            @if($data['clubs'] > 0)
                                {{-- <div class="w-100 rounded-top chart-bar" style="height:{{ $clubHeight }}px;background:#198754;"></div> --}}
                            @else
                                <div class="w-100" style="height:2px;background:#e9ecef;"></div>
                            @endif
                            @if($data['players'] > 0)
                                {{-- <div class="w-100 rounded-top chart-bar" style="height:{{ $playerHeight }}px;background:#0d6efd;"></div> --}}
                            @else
                                <div class="w-100" style="height:2px;background:#e9ecef;"></div>
                            @endif

                            @if($loop->index % 5 === 0)
                                <div class="chart-label">{{ $data['label'] }}</div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Today's Matches (1/3 width) --}}
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h6 class="mb-0">Today's Matches</h6>
                </div>
                <div class="card-body p-3">
                    @if($todayFixtures->count() > 0)
                        <div class="d-flex flex-column gap-2">
                            @foreach($todayFixtures as $fixture)
                                <div class="d-flex align-items-center gap-2 p-2 rounded-2 bg-light">
                                    <div class="rounded-pill flex-shrink-0" style="width:4px;height:38px;
                                        background:{{ $fixture->status === 'live' ? '#dc3545' : ($fixture->status === 'completed' ? '#198754' : '#6c757d') }}">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="mb-0 fw-semibold text-truncate" style="font-size:.875rem;">
                                            {{ $fixture->homeTeam->short_name ?? $fixture->homeTeam->name }}
                                            <span class="text-muted"> vs </span>
                                            {{ $fixture->awayTeam->short_name ?? $fixture->awayTeam->name }}
                                        </p>
                                        <p class="mb-0 text-muted" style="font-size:.75rem;">
                                            {{ $fixture->scheduled_time?->format('H:i') }}
                                            @if($fixture->venue) · 📍 {{ $fixture->venue->name }} @endif
                                            · {{ strtoupper($fixture->match_type) }}
                                        </p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        @if($fixture->status === 'live')
                                            <span class="badge bg-danger">LIVE</span>
                                        @elseif($fixture->status === 'completed')
                                            <span class="bg-secondary text-white">DONE</span>
                                        @else
                                            <span class="bg-primary text-white">UPCOMING</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <span style="font-size:2rem;">📅</span>
                            <p class="text-muted mb-0" style="font-size:.875rem;">No matches today</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- ──────────────────────────────────────────────────────────────────────
         ROW 4: 3 ACTION CARDS
    ────────────────────────────────────────────────────────────────────── -->
    <div class="row g-3 g-lg-4 mb-4">

        {{-- Pending Fees --}}
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0">Fees Pending Verification</h6>
                    <span class="badge bg-warning text-dark">{{ $stats['fees_pending'] }}</span>
                </div>
                <div class="card-body p-0">
                    @if($pendingFees->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($pendingFees as $fee)
                                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-3 py-2">
                                    <div class="me-2">
                                        <p class="mb-0 fw-semibold text-truncate" style="font-size:.875rem;">{{ $fee->player->name }}</p>
                                        <p class="mb-0 text-truncate" style="font-size:.75rem;">
                                            {{ $fee->fixture->homeTeam->short_name ?? $fee->fixture->homeTeam->name }}
                                            vs
                                            {{ $fee->fixture->awayTeam->short_name ?? $fee->fixture->awayTeam->name }}
                                        </p>
                                    </div>
                                    <span class="fw-bold" style="font-size:.875rem;">£{{ number_format($fee->amount, 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <span style="font-size:1.5rem;">💰</span>
                            <p class="text-muted mb-0" style="font-size:.875rem;">All fees verified 🎉</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Pending Invitations --}}
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0">Pending Invitations</h6>
                    <span class="badge bg-info">{{ $stats['invitations_pending'] }}</span>
                </div>
                <div class="card-body p-0">
                    @if($pendingInvitations->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($pendingInvitations as $inv)
                                <div class="list-group-item list-group-item-action px-3 py-2">
                                    <div class="me-auto">
                                        <p class="mb-0 fw-semibold text-truncate" style="font-size:.875rem;">{{ $inv->invited_email }}</p>
                                        <p class="mb-0 text-truncate" style="font-size:.75rem;">
                                            {{ $inv->club->name }}{{ $inv->team ? ' → ' . $inv->team->name : '' }}
                                            · {{ $inv->role }}
                                        </p>
                                    </div>
                                    <span class="text-muted" style="font-size:.75rem;">{{ $inv->created_at->diffForHumans() }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <span style="font-size:1.5rem;">📧</span>
                            <p class="text-muted mb-0" style="font-size:.875rem;">No pending invites</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Upcoming Fixtures --}}
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0">Upcoming Fixtures</h6>
                    <a href="#" class="text-primary" style="font-size:.875rem;">View all →</a>
                </div>
                <div class="card-body p-0">
                    @if($upcomingFixtures->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($upcomingFixtures as $fixture)
                                <div class="list-group-item list-group-item-action px-3 py-2">
                                    <div class="me-2">
                                        <p class="mb-0 fw-semibold text-truncate" style="font-size:.875rem;">
                                            {{ $fixture->homeTeam->short_name ?? $fixture->homeTeam->name }}
                                            <span class="text-muted"> vs </span>
                                            {{ $fixture->awayTeam->short_name ?? $fixture->awayTeam->name }}
                                        </p>
                                        <p class="mb-0 text-muted" style="font-size:.75rem;">
                                            {{ $fixture->scheduled_date->format('d M Y') }}
                                            @if($fixture->scheduled_time) · {{ $fixture->scheduled_time->format('H:i') }} @endif
                                            @if($fixture->venue) · 📍 {{ $fixture->venue->name }} @endif
                                            · {{ strtoupper($fixture->match_type) }}
                                        </p>
                                    </div>
                                    <span class="badge
                                        @if($fixture->status === 'published') bg-success
                                        @elseif($fixture->status === 'live') bg-danger
                                        @else bg-secondary text-white
                                        @endif
                                    ">{{ ucfirst($fixture->status) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <span style="font-size:1.5rem;">📋</span>
                            <p class="text-muted mb-0" style="font-size:.875rem;">No upcoming fixtures</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- ──────────────────────────────────────────────────────────────────────
         ROW 5: RECENT ACTIVITY
    ────────────────────────────────────────────────────────────── -->
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Recent Activity</h6>
            <span class="text-muted" style="font-size:.8rem;">{{ $recentActivity->count() }} latest</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px"></th>
                        <th>Activity</th>
                        <th style="width:120px;">Type</th>
                        <th style="width:100px;">Time</th>
                    </tr>
                </thead>
                <tbody>
                    @if($recentActivity->count() > 0)
                        @foreach($recentActivity as $index => $log)
                        <tr class="activity-row" style="animation-delay:{{ $index * 50 }}ms">
                            <td>
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0"
                                    style="width:32px;height:32px;background:{{ $log->user ? '#6366f1' : '#6b7280' }}; font-size:.75rem;">
                                    {{ $log->user ? strtoupper(substr($log->user->name, 0, 1)) : '⚙' }}
                                </div>
                            </td>
                            <td class="text-truncate" style="max-width:400px;">{{ $log->description }}</td>
                            <td>
                                <span class="badge bg-light text-dark" style="font-size:.7rem;">
                                    {{ str_replace('_', ' ', $log->log_type) }}
                                </span>
                            </td>
                            <td>
                                <span class="text-muted" style="font-size:.8rem;">{{ $log->created_at->diffForHumans() }}</span>
                            </td>
                        </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <span style="font-size:2.5rem;">📋</span>
                                <p class="text-muted mb-0">No activity yet. Actions will appear here as you use the system.</p>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

</main>
@endsection

@push('script')
<script>
    // Auto-refresh live matches every 30 seconds
    @if($liveFixtures->count() > 0)
    setTimeout(function() {
        location.reload();
    }, 30000);
    @endif
</script>
@endpush
