@extends('club.layouts.master')

@section('title', $title)

@section('content')
<main class="club-page">

    @if($liveFixtures->isNotEmpty())
    <div class="mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="mb-0 fw-bold d-flex align-items-center gap-2" style="font-size:1rem;color:var(--club-navy);">
                <span class="live-dot"></span> Live Now
            </h5>
            <span class="club-badge danger">{{ $liveFixtures->count() }} matches</span>
        </div>
        <div class="row g-3">
            @foreach($liveFixtures as $fixture)
            <div class="col-md-6 col-xl-4">
                <div class="club-live-card">
                    <span class="club-badge danger mb-2">{{ strtoupper($fixture->status) }}</span>
                    <p class="fw-semibold mb-2" style="font-size:0.9rem;">
                        {{ $fixture->home_display_name }}
                        <span class="text-muted fw-normal">vs</span>
                        {{ $fixture->away_display_name }}
                    </p>
                    <p class="font-monospace small mb-2">
                        @if($fixture->isLive() && $fixture->match && $fixture->match->firstInnings && $fixture->match->firstInnings->batting_is_club === $fixture->clubPlaysHome())
                            {{ $fixture->match->firstInnings->runs }}/{{ $fixture->match->firstInnings->wickets }} ({{ $fixture->match->firstInnings->overs }})
                        @elseif($fixture->isLive() && $fixture->match && $fixture->match->secondInnings && $fixture->match->secondInnings->batting_is_club === $fixture->clubPlaysHome())
                            {{ $fixture->match->secondInnings->runs }}/{{ $fixture->match->secondInnings->wickets }} ({{ $fixture->match->secondInnings->overs }})
                        @else
                            {{ $fixture->home_team_runs }}/{{ $fixture->home_team_wickets }} ({{ $fixture->home_team_overs }})
                        @endif
                        —
                        @if($fixture->isLive() && $fixture->match && $fixture->match->firstInnings && $fixture->match->firstInnings->batting_is_club !== $fixture->clubPlaysHome())
                            {{ $fixture->match->firstInnings->runs }}/{{ $fixture->match->firstInnings->wickets }} ({{ $fixture->match->firstInnings->overs }})
                        @elseif($fixture->isLive() && $fixture->match && $fixture->match->secondInnings && $fixture->match->secondInnings->batting_is_club !== $fixture->clubPlaysHome())
                            {{ $fixture->match->secondInnings->runs }}/{{ $fixture->match->secondInnings->wickets }} ({{ $fixture->match->secondInnings->overs }})
                        @else
                            {{ $fixture->away_team_runs }}/{{ $fixture->away_team_wickets }} ({{ $fixture->away_team_overs }})
                        @endif
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('club.scoring.show', $fixture) }}" class="btn btn-sm btn-light border">
                            <i class="fas fa-chart-bar me-1"></i> Scorecard
                        </a>
                        <a href="{{ route('club.scoring.live', $fixture) }}" class="btn btn-sm btn-club-primary border">
                            <i class="fas fa-play me-1"></i> Live Score
                        </a>
                    @if($fixture->is_public && $fixture->public_share_slug)
                        <a href="{{ $fixture->public_url }}" target="_blank" class="btn btn-sm btn-club-primary">
                            <i class="fas fa-external-link-alt me-1"></i> Public score page
                        </a>
                    @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="club-card">
        <div class="club-card-header">
            <h6 class="mb-0"><i class="fas fa-baseball-ball me-2 text-success"></i>Ready to Score</h6>
            <a href="{{ route('club.scoring.matches') }}" class="btn btn-sm btn-light border">
                <i class="fas fa-chart-bar me-1"></i> All matches
            </a>
        </div>
        <div class="club-card-body">
            @if($readyFixtures->isEmpty() && $liveFixtures->isEmpty())
                <div class="club-empty">
                    <i class="fas fa-baseball-ball"></i>
                    <p class="fw-semibold mb-1">No matches to score</p>
                    <small>Publish a fixture, assign a scorer, and complete pre-match setup via the scorer API.</small>
                </div>
            @elseif($readyFixtures->isEmpty())
                <p class="text-muted small p-3 mb-0">No additional fixtures ready to start. Live matches are shown above.</p>
            @else
                <div class="club-fixture-mobile-list d-md-none">
                    @foreach($readyFixtures as $fixture)
                    <div class="club-fixture-mobile-item w-100">
                        <div class="match-teams">
                            {{ $fixture->home_display_name }}
                            <span class="text-muted fw-normal">vs</span>
                            {{ $fixture->away_display_name }}
                        </div>
                        <div class="match-meta mb-2">
                            <span><i class="far fa-calendar me-1"></i>{{ $fixture->scheduled_date?->format('d M Y') }}</span>
                            <span class="club-badge success">Scorer assigned</span>
                        </div>
                        <div class="d-flex gap-2 mt-2 w-100">
                            <a href="{{ route('club.scoring.show', $fixture) }}" class="btn btn-sm btn-light border flex-grow-1">
                                <i class="fas fa-chart-bar me-1"></i> Scorecard
                            </a>
                            <a href="{{ route('club.scoring.live', $fixture) }}" class="btn btn-sm btn-club-primary flex-grow-1">
                                <i class="fas fa-play me-1"></i> Live Score
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</main>
@endsection
