@extends('club.layouts.master')

@section('dashboard', 'active')
@section('title', $title ?? 'Dashboard')

@section('content')
<main class="club-page">

    {{-- Welcome banner --}}
    <div class="club-welcome">
        <div class="position-relative" style="z-index:1;">
            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3">
                <div class="min-w-0">
                    <p class="club-welcome-label">Club Dashboard</p>
                    <h2 class="text-truncate">{{ $club->name }}</h2>
                    <p class="club-welcome-meta">
                        @if($club->city || $club->country)
                            <i class="fas fa-map-marker-alt me-1"></i>
                            {{ collect([$club->city, $club->country])->filter()->join(', ') }}
                        @endif
                    </p>
                    @if($club->is_verified)
                        <span class="club-verified-badge"><i class="fas fa-check-circle"></i> Verified club</span>
                    @endif
                </div>
                @if($club->logo_url)
                    <img src="{{ $club->logo_url }}" alt="{{ $club->name }}" class="club-welcome-logo flex-shrink-0">
                @endif
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="club-stat-grid">
        <div class="club-stat-card">
            <div class="club-stat-icon green"><i class="fas fa-layer-group"></i></div>
            <div>
                <p class="club-stat-label">Teams</p>
                <p class="club-stat-value">{{ $stats['teams'] }}</p>
            </div>
        </div>
        <div class="club-stat-card">
            <div class="club-stat-icon blue"><i class="fas fa-user-friends"></i></div>
            <div>
                <p class="club-stat-label">Members</p>
                <p class="club-stat-value">{{ $stats['members'] }}</p>
            </div>
        </div>
        <div class="club-stat-card {{ $stats['fixtures_live'] > 0 ? 'is-alert' : '' }}">
            <div class="club-stat-icon {{ $stats['fixtures_live'] > 0 ? 'red' : 'amber' }}">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div>
                <p class="club-stat-label">Fixtures</p>
                <p class="club-stat-value">{{ $stats['fixtures_total'] }}</p>
                <p class="club-stat-hint">
                    {{ $stats['fixtures_upcoming'] }} upcoming
                    @if($stats['fixtures_live'] > 0)
                        · <span class="text-danger fw-semibold">{{ $stats['fixtures_live'] }} live</span>
                    @endif
                </p>
            </div>
        </div>
        <div class="club-stat-card">
            <div class="club-stat-icon amber"><i class="fas fa-envelope"></i></div>
            <div>
                <p class="club-stat-label">Invitations</p>
                <p class="club-stat-value">{{ $stats['invitations_pending'] }}</p>
                <p class="club-stat-hint">pending</p>
            </div>
        </div>
    </div>

    {{-- Live matches --}}
    @if($liveFixtures->count() > 0)
    <div class="mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
            <h5 class="mb-0 d-flex align-items-center gap-2 fw-bold" style="font-size:1rem;color:var(--club-navy);">
                <span class="live-dot"></span> Live Matches
            </h5>
            <span class="club-badge danger">{{ $liveFixtures->count() }} in progress</span>
        </div>
        <div class="row g-3">
            @foreach($liveFixtures as $fixture)
            <div class="col-sm-6 col-xl-4">
                <div class="club-live-card">
                    <span class="club-badge danger mb-2">{{ strtoupper($fixture->status) }}</span>
                    <p class="fw-semibold mb-2" style="font-size:0.9rem;color:var(--club-navy);">
                        {{ $fixture->home_display_name }}
                        <span class="text-muted fw-normal">vs</span>
                        {{ $fixture->away_display_name }}
                    </p>
                    @if($fixture->home_team_runs !== null)
                    <p class="font-monospace mb-2 small text-muted">
                        {{ $fixture->home_team_runs }}/{{ $fixture->home_team_wickets }}
                        ({{ $fixture->home_team_overs }})
                        —
                        {{ $fixture->away_team_runs }}/{{ $fixture->away_team_wickets }}
                        ({{ $fixture->away_team_overs }})
                    </p>
                    @endif
                    <p class="text-muted mb-0" style="font-size:0.8rem;">
                        {{ $fixture->match_type_label ?? $fixture->match_type }}
                        @if($fixture->venue) · {{ $fixture->venue->name }} @endif
                    </p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="row g-3 g-lg-4">
        {{-- Upcoming fixtures --}}
        <div class="col-lg-8">
            <div class="club-card">
                <div class="club-card-header">
                    <h6><i class="fas fa-calendar-check me-2 text-success"></i>Upcoming Fixtures</h6>
                    <span class="club-badge muted">{{ $upcomingFixtures->count() }} shown</span>
                </div>
                <div class="club-card-body">
                    @if($upcomingFixtures->isEmpty())
                        <div class="club-empty">
                            <i class="fas fa-calendar-plus"></i>
                            <p class="fw-semibold mb-1">No upcoming fixtures</p>
                            <small>Fixture management is coming soon to the club panel.</small>
                        </div>
                    @else
                        {{-- Desktop table --}}
                        <div class="club-fixtures-table-wrap table-responsive">
                            <table class="table club-fixtures-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Match</th>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($upcomingFixtures as $fixture)
                                    <tr>
                                        <td>
                                            <span class="fw-medium">{{ $fixture->home_display_name }}</span>
                                            <span class="text-muted"> vs </span>
                                            <span class="fw-medium">{{ $fixture->away_display_name }}</span>
                                        </td>
                                        <td class="small text-muted">
                                            {{ $fixture->scheduled_date?->format('d M Y') }}
                                            @if($fixture->scheduled_time)
                                                <br>{{ \Carbon\Carbon::parse($fixture->scheduled_time)->format('H:i') }}
                                            @endif
                                        </td>
                                        <td><span class="club-badge muted">{{ $fixture->match_type_label ?? $fixture->match_type }}</span></td>
                                        <td>
                                            <span class="club-badge {{ $fixture->status === 'published' ? 'success' : 'muted' }}">
                                                {{ ucfirst($fixture->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Mobile cards --}}
                        <div class="club-fixture-mobile-list">
                            @foreach($upcomingFixtures as $fixture)
                            <div class="club-fixture-mobile-item">
                                <div class="match-teams">
                                    {{ $fixture->home_display_name }}
                                    <span class="text-muted fw-normal">vs</span>
                                    {{ $fixture->away_display_name }}
                                </div>
                                <div class="match-meta">
                                    <span><i class="far fa-calendar me-1"></i>{{ $fixture->scheduled_date?->format('d M Y') }}</span>
                                    @if($fixture->scheduled_time)
                                        <span><i class="far fa-clock me-1"></i>{{ \Carbon\Carbon::parse($fixture->scheduled_time)->format('H:i') }}</span>
                                    @endif
                                    <span class="club-badge muted">{{ $fixture->match_type_label ?? $fixture->match_type }}</span>
                                    <span class="club-badge {{ $fixture->status === 'published' ? 'success' : 'muted' }}">{{ ucfirst($fixture->status) }}</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar column --}}
        <div class="col-lg-4">
            <div class="club-card mb-3">
                <div class="club-card-header">
                    <h6><i class="fas fa-bolt me-2" style="color:var(--club-gold);"></i>Quick Actions</h6>
                </div>
                <div class="club-card-body padded d-grid gap-2">
                    <a href="{{ route('club.fixtures.create') }}" class="club-action-btn text-decoration-none">
                        <span class="action-icon"><i class="fas fa-plus"></i></span>
                        <span class="flex-grow-1 text-dark">Add Fixture</span>
                        <i class="fas fa-chevron-right text-muted"></i>
                    </a>
                    <a href="{{ route('club.invitations.create') }}" class="club-action-btn text-decoration-none">
                        <span class="action-icon"><i class="fas fa-user-plus"></i></span>
                        <span class="flex-grow-1 text-dark">Invite Player</span>
                        <i class="fas fa-chevron-right text-muted"></i>
                    </a>
                    <a href="{{ route('club.squads.index') }}" class="club-action-btn text-decoration-none">
                        <span class="action-icon"><i class="fas fa-users"></i></span>
                        <span class="flex-grow-1 text-dark">Manage Squads</span>
                        <i class="fas fa-chevron-right text-muted"></i>
                    </a>
                    <a href="{{ route('club.scoring.index') }}" class="club-action-btn text-decoration-none">
                        <span class="action-icon"><i class="fas fa-baseball-ball"></i></span>
                        <span class="flex-grow-1 text-dark">Live Scoring</span>
                        <i class="fas fa-chevron-right text-muted"></i>
                    </a>
                </div>
            </div>

            <div class="club-tip-card">
                <h6><i class="fas fa-lightbulb me-1"></i> Getting started</h6>
                    <p>
                        Use the sidebar to manage your club, squads, fixtures, players, and live scoring.
                    </p>
            </div>
        </div>
    </div>
</main>
@endsection
