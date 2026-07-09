@extends('club.layouts.master')

@section('title', $title)

@php
    function sideName($fixture, $isClubBatting) {
        return $isClubBatting ? ($fixture->clubPlaysHome() ? $fixture->home_display_name : $fixture->away_display_name)
                              : ($fixture->clubPlaysHome() ? $fixture->away_display_name : $fixture->home_display_name);
    }
@endphp

@section('content')
<main class="club-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="min-w-0">
            <p class="text-muted small mb-1">Scorecard</p>
            <h5 class="mb-0 text-truncate">
                {{ $fixture->home_display_name }} vs {{ $fixture->away_display_name }}
            </h5>
            <p class="text-muted small mb-0">
                {{ $fixture->scheduled_date?->format('d M Y') }}
                @if($fixture->venue) · {{ $fixture->venue->name }} @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('club.scoring.matches') }}" class="btn btn-sm btn-light border">
                <i class="fas fa-arrow-left me-1"></i> Back to matches
            </a>
            @if($fixture->is_public && $fixture->public_share_slug)
                <a href="{{ $fixture->public_url }}" target="_blank" class="btn btn-sm btn-club-primary">
                    <i class="fas fa-external-link-alt me-1"></i> Public page
                </a>
            @endif
        </div>
    </div>

    <div class="club-card mb-3">
        <div class="club-card-body padded">
            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <div class="d-flex flex-wrap gap-2">
                    <span class="club-badge {{ in_array($fixture->status, ['live','paused']) ? 'danger' : ($fixture->status === 'completed' ? 'success' : 'muted') }}">{{ strtoupper($fixture->status) }}</span>
                    @if($fixture->result_text)
                        <span class="club-badge muted">{{ $fixture->result_text }}</span>
                    @endif
                    @if($fixture->toss_winner_side)
                        <span class="club-badge muted">Toss: {{ ucfirst($fixture->toss_winner_side) }} chose {{ $fixture->toss_decision }}</span>
                    @endif
                </div>
                <div class="small text-muted">
                    Overs: {{ $fixture->overs_per_innings }} · {{ strtoupper($fixture->ball_type) }}
                </div>
            </div>
        </div>
    </div>

    @if(!$match)
        <div class="club-card">
            <div class="club-card-body">
                <div class="club-empty">
                    <i class="fas fa-hourglass-start"></i>
                    <p class="fw-semibold mb-1">Match not started</p>
                    <small>Scorecard will appear once scoring begins.</small>
                </div>
            </div>
        </div>
    @else
        @foreach($innings as $inn)
            <div class="club-card mb-3">
                <div class="club-card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-flag me-2 text-success"></i>
                        {{ sideName($fixture, (bool) $inn->batting_is_club) }}
                        <span class="text-muted fw-normal">·</span>
                        {{ $inn->score_display }}
                    </h6>
                    <span class="club-badge muted">RR {{ number_format($inn->run_rate, 2) }}</span>
                </div>

                <div class="club-card-body">
                    <div class="table-responsive">
                        <table class="table club-fixtures-table mb-0">
                            <thead>
                                <tr>
                                    <th>Batter</th>
                                    <th>Dismissal</th>
                                    <th class="text-end">R</th>
                                    <th class="text-end">B</th>
                                    <th class="text-end">4s</th>
                                    <th class="text-end">6s</th>
                                    <th class="text-end">SR</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($inn->battingScores as $bat)
                                    <tr>
                                        <td class="fw-medium">{{ $bat->player?->name ?? $bat->external_player_name ?? 'Player' }}</td>
                                        <td class="small text-muted">{{ $bat->dismissal_text }}</td>
                                        <td class="text-end fw-medium">{{ $bat->runs }}</td>
                                        <td class="text-end small text-muted">{{ $bat->balls_faced }}</td>
                                        <td class="text-end small text-muted">{{ $bat->fours }}</td>
                                        <td class="text-end small text-muted">{{ $bat->sixes }}</td>
                                        <td class="text-end small text-muted">{{ $bat->strike_rate ?? '—' }}</td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td class="small text-muted" colspan="2">Extras</td>
                                    <td class="text-end fw-medium">{{ $inn->extras_total }}</td>
                                    <td colspan="4" class="small text-muted">
                                        {{ $inn->extras_display }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="small text-muted" colspan="2">Total</td>
                                    <td class="text-end fw-medium">{{ $inn->runs }}</td>
                                    <td colspan="4" class="small text-muted">
                                        {{ $inn->wickets }} wkts · {{ $inn->overs }} ov
                                        @if($inn->target) · Target {{ $inn->target }} @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 table-responsive">
                        <table class="table club-fixtures-table mb-0">
                            <thead>
                                <tr>
                                    <th>Bowler</th>
                                    <th class="text-end">O</th>
                                    <th class="text-end">M</th>
                                    <th class="text-end">R</th>
                                    <th class="text-end">W</th>
                                    <th class="text-end">Wd</th>
                                    <th class="text-end">Nb</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($inn->bowlingFigures as $bowl)
                                    <tr>
                                        <td class="fw-medium">{{ $bowl->bowler?->name ?? $bowl->external_player_name ?? 'Bowler' }}</td>
                                        <td class="text-end small text-muted">{{ $bowl->overs }}</td>
                                        <td class="text-end small text-muted">{{ $bowl->maidens }}</td>
                                        <td class="text-end fw-medium">{{ $bowl->runs_conceded }}</td>
                                        <td class="text-end fw-medium">{{ $bowl->wickets }}</td>
                                        <td class="text-end small text-muted">{{ $bowl->wides_bowled }}</td>
                                        <td class="text-end small text-muted">{{ $bowl->no_balls_bowled }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($inn->wickets->isNotEmpty())
                        <div class="mt-3">
                            <div class="small text-muted mb-2 fw-semibold">Fall of wickets</div>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($inn->wickets as $w)
                                    <span class="club-badge muted">
                                        {{ $w->runs_at_dismissal }} — {{ $w->dismissedBatter?->name ?? $w->external_dismissed_batter_name ?? 'Batter' }}
                                        @if($w->ballEvent)
                                            ({{ ($w->ballEvent->over_number + 1) . '.' . ($w->ballEvent->ball_number + 1) }})
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    @endif
</main>
@endsection

