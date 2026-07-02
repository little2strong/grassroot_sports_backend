@extends('admin.layouts.master')

@push('style')
<style>
    .stat-card { transition: transform .2s ease, box-shadow .2s ease; border: 1px solid rgba(0,0,0,.08); }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.1) !important; }
    .live-dot { width: 8px; height: 8px; border-radius: 50%; animation: pulse-dot 1.5s infinite; display: inline-block; }
    @keyframes pulse-dot { 0%,100%{opacity:1} 50%{opacity:.4} }
    .fixture-card { border-left: 4px solid transparent; }
    .fixture-card.is-live { border-left-color: #dc3545; }
    .chart-container { height: 220px; display: flex; align-items: flex-end; gap: 3px; padding-bottom: 24px; }
    .chart-col { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; gap: 2px; min-width: 0; position: relative; }
    .chart-bar { width: 100%; border-radius: 3px 3px 0 0; min-height: 2px; }
    .chart-label { font-size: 9px; color: #6c757d; position: absolute; bottom: -20px; white-space: nowrap; }
</style>
@endpush

@section('dashboard', 'active')
@section('title', $title ?? 'Dashboard')

@section('content')
<main class="container-fluid px-3 px-lg-4">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3 g-lg-4 mt-1 mb-4">
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Total Clubs</p>
                    <h3 class="fw-bold mb-1">{{ $stats['clubs'] }}</h3>
                    <p class="text-muted mb-3 small">{{ $stats['clubs_verified'] }} verified</p>
                    <a href="{{ route('admin.clubs.index') }}" class="btn btn-sm btn-outline-primary w-100">View clubs</a>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Players</p>
                    <h3 class="fw-bold mb-1">{{ $stats['players'] }}</h3>
                    <p class="text-muted mb-3 small">{{ $stats['players_active'] }} active</p>
                    <a href="{{ route('admin.players.index') }}" class="btn btn-sm btn-outline-primary w-100">View players</a>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100 {{ $stats['fixtures_live'] > 0 ? 'border-danger border-opacity-50' : '' }}">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Fixtures</p>
                    <h3 class="fw-bold mb-1">{{ $stats['fixtures_total'] }}</h3>
                    <p class="text-muted mb-3 small">
                        {{ $stats['fixtures_upcoming'] }} upcoming ·
                        <span class="{{ $stats['fixtures_live'] > 0 ? 'text-danger fw-semibold' : '' }}">{{ $stats['fixtures_live'] }} live</span> ·
                        {{ $stats['fixtures_today'] }} today
                    </p>
                    <span class="btn btn-sm btn-outline-secondary w-100 disabled">Match module</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Completed Matches</p>
                    <h3 class="fw-bold mb-1">{{ $stats['fixtures_completed'] }}</h3>
                    <p class="text-muted mb-3 small">{{ $stats['invitations_pending'] }} pending invites</p>
                    <a href="{{ route('admin.clubs.index') }}" class="btn btn-sm btn-outline-primary w-100">Manage clubs</a>
                </div>
            </div>
        </div>
    </div>

    @if($liveFixtures->count() > 0)
    <div class="mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="mb-0 d-flex align-items-center gap-2">
                <span class="live-dot bg-danger"></span> Live Matches
            </h5>
            <span class="badge bg-danger">{{ $liveFixtures->count() }} in progress</span>
        </div>
        <div class="row g-3">
            @foreach($liveFixtures as $fixture)
            <div class="col-md-6 col-xl-4">
                <div class="card fixture-card is-live">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-danger">{{ strtoupper($fixture->status) }}</span>
                            <small class="text-muted">{{ $fixture->club?->name }}</small>
                        </div>
                        <p class="fw-semibold mb-2">{{ $fixture->home_display_name }} <span class="text-muted">vs</span> {{ $fixture->away_display_name }}</p>
                        <p class="font-monospace mb-2">
                            {{ $fixture->home_team_runs }}/{{ $fixture->home_team_wickets }}
                            ({{ $fixture->home_team_overs }})
                            —
                            {{ $fixture->away_team_runs }}/{{ $fixture->away_team_wickets }}
                            ({{ $fixture->away_team_overs }})
                        </p>
                        <p class="text-muted small mb-0">
                            {{ $fixture->match_type_label }} · {{ $fixture->scheduled_date?->format('d M Y') }}
                            @if($fixture->venue) · {{ $fixture->venue->name }} @endif
                        </p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="row g-3 g-lg-4 mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Growth — Last 30 Days</h6>
                    <div class="d-flex gap-3 small">
                        <span><span class="d-inline-block rounded-circle me-1" style="width:10px;height:10px;background:#0d6efd;"></span> Players</span>
                        <span><span class="d-inline-block rounded-circle me-1" style="width:10px;height:10px;background:#198754;"></span> Clubs</span>
                        <span><span class="d-inline-block rounded-circle me-1" style="width:10px;height:10px;background:#6f42c1;"></span> Fixtures</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        @foreach($dates as $day)
                        <div class="chart-col">
                            @if($day['fixtures'] > 0)
                                <div class="chart-bar" style="height:{{ max(4, ($day['fixtures'] / $chartMax) * 180) }}px;background:#6f42c1;" title="Fixtures: {{ $day['fixtures'] }}"></div>
                            @endif
                            @if($day['clubs'] > 0)
                                <div class="chart-bar" style="height:{{ max(4, ($day['clubs'] / $chartMax) * 180) }}px;background:#198754;" title="Clubs: {{ $day['clubs'] }}"></div>
                            @endif
                            @if($day['players'] > 0)
                                <div class="chart-bar" style="height:{{ max(4, ($day['players'] / $chartMax) * 180) }}px;background:#0d6efd;" title="Players: {{ $day['players'] }}"></div>
                            @endif
                            @if($loop->index % 5 === 0)
                                <span class="chart-label">{{ $day['label'] }}</span>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-white"><h6 class="mb-0">Today's Matches</h6></div>
                <div class="card-body p-2">
                    @forelse($todayFixtures as $fixture)
                        <div class="p-2 mb-2 rounded bg-light">
                            <div class="d-flex justify-content-between gap-2">
                                <div class="min-w-0">
                                    <p class="mb-0 fw-semibold small text-truncate">
                                        {{ $fixture->home_display_name }} vs {{ $fixture->away_display_name }}
                                    </p>
                                    <p class="mb-0 text-muted" style="font-size:.75rem;">
                                        {{ $fixture->scheduled_time?->format('H:i') ?? 'TBC' }} · {{ strtoupper($fixture->match_type) }}
                                    </p>
                                </div>
                                <span class="badge {{ $fixture->status === 'live' ? 'bg-danger' : ($fixture->status === 'completed' ? 'bg-success' : 'bg-primary') }}">
                                    {{ ucfirst($fixture->status) }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted text-center py-4 mb-0 small">No matches scheduled for today.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 g-lg-4 mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-white d-flex justify-content-between">
                    <h6 class="mb-0">Upcoming Fixtures</h6>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($upcomingFixtures as $fixture)
                        <div class="list-group-item">
                            <p class="mb-0 fw-semibold small">{{ $fixture->home_display_name }} vs {{ $fixture->away_display_name }}</p>
                            <p class="mb-0 text-muted" style="font-size:.75rem;">
                                {{ $fixture->scheduled_date->format('d M Y') }}
                                @if($fixture->scheduled_time) · {{ $fixture->scheduled_time->format('H:i') }} @endif
                                · {{ $fixture->club?->name }}
                            </p>
                        </div>
                    @empty
                        <div class="list-group-item text-muted small">No upcoming fixtures.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-white d-flex justify-content-between">
                    <h6 class="mb-0">Recent Clubs</h6>
                    <a href="{{ route('admin.clubs.index') }}" class="small">View all</a>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($recentClubs as $club)
                        <div class="list-group-item">
                            <p class="mb-0 fw-semibold small">{{ $club->name }}</p>
                            <p class="mb-0 text-muted" style="font-size:.75rem;">
                                <a href="{{ route('admin.clubs.show', $club) }}" class="text-decoration-none">View club</a>
                                · {{ $club->owner?->name ?? 'No owner' }} · {{ $club->created_at->diffForHumans() }}
                            </p>
                        </div>
                    @empty
                        <div class="list-group-item text-muted small">No clubs yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-white d-flex justify-content-between">
                    <h6 class="mb-0">Pending Invitations</h6>
                    <span class="badge bg-info">{{ $stats['invitations_pending'] }}</span>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($pendingInvitations as $inv)
                        <div class="list-group-item">
                            <p class="mb-0 fw-semibold small">{{ $inv->invited_email }}</p>
                            <p class="mb-0 text-muted" style="font-size:.75rem;">
                                {{ $inv->club?->name }}{{ $inv->team ? ' → '.$inv->team->name : '' }}
                                · {{ $inv->created_at->diffForHumans() }}
                            </p>
                        </div>
                    @empty
                        <div class="list-group-item text-muted small">No pending invitations.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-white"><h6 class="mb-0">Recent Players</h6></div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
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
                                <a href="{{ route('admin.players.show', $player) }}" class="text-decoration-none text-dark">
                                    <div class="fw-semibold small">{{ $player->name }}</div>
                                </a>
                                <div class="text-muted" style="font-size:.75rem;">{{ $player->email }}</div>
                            </td>
                            <td>{{ $player->playerProfile?->role_label ?? '—' }}</td>
                            <td>{{ $player->clubs->count() }}</td>
                            <td class="text-muted small">{{ $player->created_at->diffForHumans() }}</td>
                            <td>
                                <span class="badge {{ $player->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $player->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No players registered yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</main>
@endsection

@push('script')
@if($liveFixtures->count() > 0)
<script>setTimeout(() => location.reload(), 60000);</script>
@endif
@endpush
