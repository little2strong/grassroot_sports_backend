@php
    if (!function_exists('sideName')) {
        function sideName($fixture, $isClubBatting) {
            return $isClubBatting ? ($fixture->clubPlaysHome() ? $fixture->home_display_name : $fixture->away_display_name)
                                  : ($fixture->clubPlaysHome() ? $fixture->away_display_name : $fixture->home_display_name);
        }
    }
    if (!function_exists('realWickets')) {
        function realWickets($innings) {
            return $innings->relationLoaded('wickets') ? $innings->getRelation('wickets')->count() : $innings->wickets;
        }
    }
    if (!function_exists('playerLink')) {
        function playerLink($player, $externalName, $fallback = 'Player') {
            if ($player) {
                return '<a href="' . route('public.player.show', $player->id) . '" style="color: inherit; text-decoration: none;" onmouseover="this.style.textDecoration=\'underline\'" onmouseout="this.style.textDecoration=\'none\'" target="_blank">' . e($player->name) . '</a>';
            }
            return e($externalName ?? $fallback);
        }
    }
    
    $currentInnings = clone $innings;
    $currentInnings = collect($currentInnings)->last();
@endphp

<!-- MATCH HEADER -->
<div class="match-header mb-3 rounded shadow-sm bg-white p-3 border">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h5 class="mb-1 text-dark fw-bold">
                {{ $fixture->home_display_name }} vs {{ $fixture->away_display_name }}
            </h5>
            <div class="text-muted small mb-2">
                {{ $fixture->scheduled_date?->format('d M Y') }} 
                @if($fixture->venue) • {{ $fixture->venue->name }} @endif
                • {{ strtoupper($fixture->match_type ?? '') }}
            </div>
        </div>
        <div class="text-end">
            <span class="badge bg-{{ in_array($fixture->status, ['live','paused']) ? 'danger' : ($fixture->status === 'completed' ? 'success' : 'secondary') }}">
                {{ strtoupper($fixture->status) }}
            </span>
        </div>
    </div>
    
    <div class="d-flex justify-content-between align-items-center mt-3">
        @if($currentInnings)
            <div>
                <h2 class="score-large mb-0">
                    {{ sideName($fixture, (bool) $currentInnings->batting_is_club) }} 
                    {{ $currentInnings->runs }}/{{ realWickets($currentInnings) }} 
                    <span class="fs-5 text-muted fw-normal">({{ $currentInnings->overs }})</span>
                </h2>
                <div class="text-muted small fw-medium mt-1">
                    CRR: {{ number_format($currentInnings->run_rate, 2) }}
                    @if($currentInnings->target)
                        • REQ: {{ number_format($currentInnings->required_run_rate ?? 0, 2) }}
                    @endif
                </div>
            </div>
            @if($fixture->result_text)
                <div class="text-end text-primary fw-bold">
                    {{ $fixture->result_text }}
                </div>
            @elseif($currentInnings->target)
                <div class="text-end text-primary fw-bold">
                    Target: {{ $currentInnings->target }}
                </div>
            @endif
        @else
            <div>
                <h2 class="score-large mb-0 text-muted">Match not started</h2>
                @if($fixture->toss_winner_side)
                    <div class="text-muted mt-1 fw-medium">
                        Toss: {{ ucfirst($fixture->toss_winner_side) }} chose {{ $fixture->toss_decision }}
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>

<!-- NAV TABS -->
<ul class="nav nav-tabs cric-nav-tabs mb-4" id="scorecardTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ in_array($fixture->status, ['live', 'paused']) ? 'active' : '' }}" id="live-tab" data-bs-toggle="tab" data-bs-target="#live" type="button" role="tab" aria-controls="live" aria-selected="true">Live</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ !in_array($fixture->status, ['live', 'paused']) ? 'active' : '' }}" id="scorecard-tab" data-bs-toggle="tab" data-bs-target="#scorecard" type="button" role="tab" aria-controls="scorecard" aria-selected="false">Scorecard</button>
    </li>
</ul>

<!-- TAB CONTENT -->
<div class="tab-content" id="scorecardTabsContent">

    <!-- LIVE TAB -->
    <div class="tab-pane fade {{ in_array($fixture->status, ['live', 'paused']) ? 'show active' : '' }}" id="live" role="tabpanel" aria-labelledby="live-tab">
        @if(!$match || !$currentInnings)
            <div class="text-center py-5 text-muted bg-white rounded shadow-sm border">
                <i class="fas fa-hourglass-start fs-2 mb-2"></i>
                <p class="mb-0">Match will begin soon.</p>
            </div>
        @else
            <div class="card shadow-sm border mb-3">
                <div class="card-body">
                    @php
                        $battingTeamName = sideName($fixture, (bool) $currentInnings->batting_is_club);
                        $bowlingTeamName = sideName($fixture, !(bool) $currentInnings->batting_is_club);
                    @endphp
                    <h6 class="section-title mb-3">{{ $battingTeamName }} Batters</h6>
                    <div class="table-responsive">
                        <table class="table table-borderless mb-0 cric-table">
                            <thead>
                                <tr>
                                    <th>Batter</th>
                                    <th class="text-end">R</th>
                                    <th class="text-end">B</th>
                                    <th class="text-end">4s</th>
                                    <th class="text-end">6s</th>
                                    <th class="text-end">SR</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($currentInnings->battingScores->where('is_out', false)->where('dismissal_type', 'not_out') as $bat)
                                    @if($bat->is_on_strike || $bat->balls_faced > 0 || $bat->runs > 0)
                                        <tr>
                                            <td class="fw-bold text-primary">
                                                {!! playerLink($bat->player, $bat->external_player_name, 'Player') !!}
                                                @if($bat->is_on_strike) <span class="text-danger">*</span> @endif
                                            </td>
                                            <td class="text-end fw-bold">{{ $bat->runs }}</td>
                                            <td class="text-end">{{ $bat->balls_faced }}</td>
                                            <td class="text-end">{{ $bat->fours }}</td>
                                            <td class="text-end">{{ $bat->sixes }}</td>
                                            <td class="text-end">{{ $bat->strike_rate ?? '—' }}</td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    @php
                        $wicketsRelation = $currentInnings->relationLoaded('wickets') ? $currentInnings->getRelation('wickets') : collect();
                        $lastWicket = $wicketsRelation->last();
                    @endphp
                    @if($lastWicket)
                        <div class="mt-3 pt-2 border-top">
                            <span class="fw-bold text-muted" style="font-size: 0.85rem;">Last Wicket: </span>
                            <span class="fw-medium text-dark ms-1">
                                {!! playerLink($lastWicket->dismissedBatter, $lastWicket->external_dismissed_batter_name, 'Batter') !!} 
                                {{ $lastWicket->runs_at_dismissal }}-{{ $wicketsRelation->count() }}
                                @if($lastWicket->ballEvent)
                                    ({{ $lastWicket->ballEvent->over_number . '.' . ($lastWicket->ballEvent->ball_number + 1) }} ov)
                                @endif
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm border mb-3">
                <div class="card-body">
                    <h6 class="section-title mb-3">{{ $bowlingTeamName ?? 'Bowlers' }} Bowlers</h6>
                    <div class="table-responsive">
                        <table class="table table-borderless mb-0 cric-table">
                            <thead>
                                <tr>
                                    <th>Bowler</th>
                                    <th class="text-end">O</th>
                                    <th class="text-end">M</th>
                                    <th class="text-end">R</th>
                                    <th class="text-end">W</th>
                                    <th class="text-end">ECO</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $currentBowler = $currentInnings->bowlingFigures->first(function ($score) use ($currentInnings) {
                                        return $currentInnings->current_bowler_id 
                                            ? $score->user_id === $currentInnings->current_bowler_id 
                                            : ($currentInnings->external_bowler_index !== null && $score->external_player_index === $currentInnings->external_bowler_index);
                                    });
                                    
                                    $previousBowler = $currentInnings->bowlingFigures
                                        ->where('id', '!=', $currentBowler?->id)
                                        ->sortByDesc('updated_at')
                                        ->first();
                                        
                                    $activeBowlers = collect([$currentBowler, $previousBowler])->filter();
                                @endphp
                                @if($activeBowlers->isNotEmpty())
                                    @foreach($activeBowlers as $bowl)
                                        <tr>
                                            <td class="fw-bold text-primary">
                                                {!! playerLink($bowl->bowler, $bowl->external_player_name, 'Bowler') !!}
                                                @if($currentBowler && $bowl->id === $currentBowler->id) <span class="text-danger">*</span> @endif
                                            </td>
                                            <td class="text-end">{{ $bowl->overs }}</td>
                                            <td class="text-end">{{ $bowl->maidens }}</td>
                                            <td class="text-end">{{ $bowl->runs_conceded }}</td>
                                            <td class="text-end fw-bold">{{ $bowl->wickets }}</td>
                                            <td class="text-end">{{ $bowl->economy_rate ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr><td colspan="6" class="text-muted text-center py-2">Waiting for bowler</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- SCORECARD TAB -->
    <div class="tab-pane fade {{ !in_array($fixture->status, ['live', 'paused']) ? 'show active' : '' }}" id="scorecard" role="tabpanel" aria-labelledby="scorecard-tab">
        @if(!$match)
            <div class="text-center py-5 text-muted bg-white rounded shadow-sm border">
                <i class="fas fa-clipboard-list fs-2 mb-2"></i>
                <p class="mb-0">Scorecard will be generated when the match starts.</p>
            </div>
        @else
            <div class="accordion" id="inningsAccordion">
                @foreach($innings as $index => $inn)
                    <div class="accordion-item border mb-3 shadow-sm rounded overflow-hidden">
                        <h2 class="accordion-header" id="heading{{ $inn->id }}">
                            <button class="accordion-button {{ $loop->last ? '' : 'collapsed' }} fw-bold bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $inn->id }}" aria-expanded="{{ $loop->last ? 'true' : 'false' }}" aria-controls="collapse{{ $inn->id }}">
                                <div class="d-flex justify-content-between w-100 pe-3">
                                    <span>{{ sideName($fixture, (bool) $inn->batting_is_club) }} Innings</span>
                                    <span>{{ $inn->runs }}-{{ realWickets($inn) }} ({{ $inn->overs }})</span>
                                </div>
                            </button>
                        </h2>
                        <div id="collapse{{ $inn->id }}" class="accordion-collapse collapse {{ $loop->last ? 'show' : '' }}" aria-labelledby="heading{{ $inn->id }}" data-bs-parent="#inningsAccordion">
                            <div class="accordion-body p-0 bg-white">
                                
                                <!-- BATTING -->
                                <div class="table-responsive">
                                    <table class="table mb-0 cric-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 25%">Batter</th>
                                                <th style="width: 35%"></th>
                                                <th class="text-end">R</th>
                                                <th class="text-end">B</th>
                                                <th class="text-end">4s</th>
                                                <th class="text-end">6s</th>
                                                <th class="text-end">SR</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($inn->battingScores->where('has_batted', true) as $bat)
                                                <tr>
                                                    <td>
                                                        <div class="fw-bold text-primary">
                                                            {!! playerLink($bat->player, $bat->external_player_name, 'Player') !!}
                                                            @if($bat->is_on_strike) <span class="text-danger">*</span> @endif
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="dismissal-text">{{ $bat->dismissal_text }}</div>
                                                    </td>
                                                    <td class="text-end fw-bold">{{ $bat->runs }}</td>
                                                    <td class="text-end text-muted">{{ $bat->balls_faced }}</td>
                                                    <td class="text-end text-muted">{{ $bat->fours }}</td>
                                                    <td class="text-end text-muted">{{ $bat->sixes }}</td>
                                                    <td class="text-end text-muted">{{ $bat->strike_rate ?? '—' }}</td>
                                                </tr>
                                            @endforeach
                                            <tr class="bg-light">
                                                <td class="fw-bold">Extras</td>
                                                <td class="dismissal-text">{{ $inn->extras_display }}</td>
                                                <td class="text-end fw-bold">{{ $inn->extras_total }}</td>
                                                <td colspan="4"></td>
                                            </tr>
                                            <tr class="bg-light border-bottom-0">
                                                <td class="fw-bold fs-6">Total</td>
                                                <td class="dismissal-text">{{ realWickets($inn) }} wkts, {{ $inn->overs }} ov</td>
                                                <td class="text-end fw-bold fs-6">{{ $inn->runs }}</td>
                                                <td colspan="4"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- DID NOT BAT -->
                                @php
                                    $didNotBat = $inn->battingScores->where('has_batted', false);
                                @endphp
                                @if($didNotBat->isNotEmpty())
                                    <div class="px-3 py-2 border-bottom bg-light">
                                        <span class="section-title" style="margin-right: 8px;">Did not bat: </span>
                                        <span class="fow-text">
                                            @foreach($didNotBat as $dnb)
                                                <span class="text-primary fw-bold">{!! playerLink($dnb->player, $dnb->external_player_name, 'Player') !!}</span>{{ !$loop->last ? ', ' : '' }}
                                            @endforeach
                                        </span>
                                    </div>
                                @endif

                                <!-- FOW -->
                                @php
                                    $wicketsRelation = $inn->relationLoaded('wickets') ? $inn->getRelation('wickets')->sortBy('runs_at_dismissal')->values() : collect();
                                @endphp
                                @if($wicketsRelation->isNotEmpty())
                                    <div class="px-3 py-3 border-bottom">
                                        <div class="section-title mb-2">Fall of Wickets</div>
                                        <div class="fow-text">
                                            @foreach($wicketsRelation as $w)
                                                {{ $w->runs_at_dismissal }}-{{ $loop->iteration }} ({!! playerLink($w->dismissedBatter, $w->external_dismissed_batter_name, 'Batter') !!}, {{ $w->ballEvent ? ($w->ballEvent->over_number . '.' . ($w->ballEvent->ball_number + 1)) : '' }}){{ !$loop->last ? ', ' : '' }}
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <!-- BOWLING -->
                                <div class="table-responsive mt-3">
                                    <table class="table mb-0 cric-table">
                                        <thead>
                                            <tr>
                                                <th>Bowler</th>
                                                <th class="text-end">O</th>
                                                <th class="text-end">M</th>
                                                <th class="text-end">R</th>
                                                <th class="text-end">W</th>
                                                <th class="text-end">NB</th>
                                                <th class="text-end">WD</th>
                                                <th class="text-end">ECO</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($inn->bowlingFigures as $bowl)
                                                <tr>
                                                    <td class="fw-bold text-primary">{!! playerLink($bowl->bowler, $bowl->external_player_name, 'Bowler') !!}</td>
                                                    <td class="text-end">{{ $bowl->overs }}</td>
                                                    <td class="text-end">{{ $bowl->maidens }}</td>
                                                    <td class="text-end">{{ $bowl->runs_conceded }}</td>
                                                    <td class="text-end fw-bold">{{ $bowl->wickets }}</td>
                                                    <td class="text-end text-muted">{{ $bowl->no_balls_bowled }}</td>
                                                    <td class="text-end text-muted">{{ $bowl->wides_bowled }}</td>
                                                    <td class="text-end text-muted">{{ $bowl->economy_rate ?? '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
