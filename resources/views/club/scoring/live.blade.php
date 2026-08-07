@extends('club.layouts.master')

@section('title', 'Live Scoring')

@push('style')
<style>
    .scoring-app { font-family: system-ui, -apple-system, sans-serif; }
    .score-banner { background-color: var(--club-navy); color: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem; text-align: center; }
    .score-banner h1 { font-size: 3rem; font-weight: bold; margin: 0; line-height: 1; }
    .score-banner .overs { font-size: 1.25rem; opacity: 0.8; }
    .score-banner .req-run-rate { font-size: 1rem; color: #ffeb3b; margin-top: 0.5rem; }
    
    .player-row { display: flex; justify-content: space-between; padding: 0.75rem; border-bottom: 1px solid #eee; align-items: center; }
    .player-row.striker { background-color: #f8f9fa; font-weight: bold; border-left: 4px solid var(--club-primary); }
    .player-row .stats { font-family: monospace; font-size: 1.1rem; }
    
    .control-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; margin-bottom: 1rem; }
    .btn-score { padding: 1rem; font-size: 1.25rem; font-weight: bold; border-radius: 8px; transition: all 0.2s; }
    .btn-runs { background-color: #f1f3f5; color: #212529; border: 1px solid #dee2e6; }
    .btn-runs:hover { background-color: #e9ecef; }
    .btn-boundary { background-color: #e3f2fd; color: #0d47a1; border: 1px solid #bbdefb; }
    .btn-extra { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
    .btn-wicket { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    
    .action-row { display: flex; gap: 0.5rem; margin-bottom: 1rem; }
    
    .loading-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.8); z-index: 1000; display: flex; justify-content: center; align-items: center; border-radius: 8px; }
    
    .timeline { display: flex; overflow-x: auto; padding: 0.5rem; gap: 0.5rem; background: #f8f9fa; border-radius: 8px; margin-bottom: 1rem; }
    .ball-bubble { min-width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: bold; background: white; border: 1px solid #dee2e6; }
    .ball-bubble.wicket { background: #dc3545; color: white; border-color: #dc3545; }
    .ball-bubble.boundary { background: #0d6efd; color: white; border-color: #0d6efd; }
    .ball-bubble.extra { background: #ffc107; color: black; border-color: #ffc107; }
</style>
@endpush

@section('content')
<main class="club-page scoring-app position-relative">
    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
        <h5 class="mb-0 fw-bold">{{ $fixture->home_display_name }} vs {{ $fixture->away_display_name }}</h5>
        <a href="{{ route('club.scoring.index') }}" class="btn btn-sm btn-light border">Exit Live</a>
    </div>

    <div id="loader" class="loading-overlay">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Match Pre-start State -->
    <div id="pre-match-view" style="display: none;">
        <div class="club-card mb-4">
            <div class="club-card-body text-center py-5">
                <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                <h4 id="pre-match-title">Match Setup</h4>
                <p id="pre-match-msg" class="text-muted">Loading match readiness...</p>
                <div id="pre-match-actions" class="mt-4">
                    <!-- Buttons injected via JS -->
                </div>
            </div>
        </div>
    </div>

    <!-- Live Scoring View -->
    <div id="live-view" style="display: none;">
        
        <!-- Scoreboard -->
        <div class="score-banner shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span id="batting-team-name" class="fw-bold">TEAM A</span>
                <span class="badge bg-danger" id="live-indicator">LIVE</span>
            </div>
            <h1><span id="total-runs">0</span> / <span id="total-wickets">0</span></h1>
            <div class="overs mt-1">Overs: <span id="total-overs" class="fw-bold">0.0</span> <span class="fs-6 opacity-75">/ <span id="max-overs">20</span></span></div>
            <div class="req-run-rate" id="target-info" style="display: none;">
                Target: <span id="target-runs" class="fw-bold"></span> | Need <span id="runs-needed"></span> runs in <span id="balls-remaining"></span> balls
            </div>
        </div>

        <!-- Over Timeline -->
        <div class="timeline shadow-sm" id="over-timeline">
            <!-- Bubbles injected via JS -->
        </div>

        <!-- Players Area -->
        <div class="club-card mb-3">
            <div class="club-card-header bg-light py-2 px-3">
                <h6 class="mb-0 fs-6 text-muted">Batters</h6>
            </div>
            <div class="px-2" id="batters-list">
                <!-- Striker / Non-striker injected via JS -->
            </div>
            <div class="club-card-header bg-light py-2 px-3 mt-2 border-top">
                <h6 class="mb-0 fs-6 text-muted">Bowler</h6>
            </div>
            <div class="px-2 pb-2" id="bowler-info">
                <!-- Bowler injected via JS -->
            </div>
        </div>

        <!-- Control Area -->
        <div class="action-row">
            <button class="btn btn-warning flex-grow-1" onclick="app.showExtrasModal()"><i class="fas fa-plus-circle me-1"></i> Extras</button>
            <button class="btn btn-danger flex-grow-1" onclick="app.showWicketModal()"><i class="fas fa-skull me-1"></i> Wicket</button>
        </div>
        
        <div class="control-grid">
            <button class="btn btn-score btn-runs" onclick="app.recordRuns(0)">0</button>
            <button class="btn btn-score btn-runs" onclick="app.recordRuns(1)">1</button>
            <button class="btn btn-score btn-runs" onclick="app.recordRuns(2)">2</button>
            <button class="btn btn-score btn-runs" onclick="app.recordRuns(3)">3</button>
            <button class="btn btn-score btn-boundary" onclick="app.recordRuns(4)">4</button>
            <button class="btn btn-score btn-boundary" onclick="app.recordRuns(6)">6</button>
        </div>

        <div class="action-row">
            <button class="btn btn-light border flex-grow-1" onclick="app.changeBatter()"><i class="fas fa-exchange-alt me-1"></i> Batter</button>
            <button class="btn btn-light border flex-grow-1" onclick="app.changeBowler()"><i class="fas fa-baseball-ball me-1"></i> Bowler</button>
            <button class="btn btn-light border flex-grow-1" onclick="app.endInnings()"><i class="fas fa-flag-checkered me-1"></i> End Inn.</button>
        </div>
    </div>
</main>

<!-- Toss Modal -->
<div class="modal fade" id="tossModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Toss</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Toss Winner</label>
                    <select id="toss-winner" class="form-select">
                        <option value="club">{{ $fixture->clubPlaysHome() ? $fixture->home_display_name : $fixture->away_display_name }}</option>
                        <option value="opponent">{{ $fixture->clubPlaysHome() ? $fixture->away_display_name : $fixture->home_display_name }}</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Decision</label>
                    <select id="toss-decision" class="form-select">
                        <option value="bat">Batting First</option>
                        <option value="bowl">Bowling First</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-club-primary w-100" onclick="app.submitToss()">Save Toss Result</button>
            </div>
        </div>
    </div>
</div>

<!-- Start Match / Openers Modal -->
<div class="modal fade" id="openersModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="openersModalTitle">Select Openers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Striker</label>
                    <select id="op-striker" class="form-select"></select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Non-Striker</label>
                    <select id="op-non-striker" class="form-select"></select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Opening Bowler</label>
                    <select id="op-bowler" class="form-select"></select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-club-primary w-100" onclick="app.submitOpeners()">Start Match</button>
            </div>
        </div>
    </div>
</div>

<!-- Wicket Modal -->
<div class="modal fade" id="wicketModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Wicket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Dismissal Type</label>
                    <select id="w-type" class="form-select" onchange="app.onDismissalTypeChange()">
                        <option value="bowled">Bowled</option>
                        <option value="caught">Caught</option>
                        <option value="lbw">LBW</option>
                        <option value="run_out">Run Out</option>
                        <option value="stumped">Stumped</option>
                        <option value="hit_wicket">Hit Wicket</option>
                        <option value="caught_and_bowled">Caught & Bowled</option>
                        <option value="retired">Retired / Hurt</option>
                    </select>
                </div>
                <div class="mb-3" id="w-fielder-div" style="display: none;">
                    <label class="form-label">Fielder (Optional)</label>
                    <select id="w-fielder" class="form-select">
                        <option value="">-- None --</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Runs scored on this ball (e.g. 1 run before run out)</label>
                    <input type="number" id="w-runs" class="form-control" value="0" min="0">
                </div>
                <div class="mb-3">
                    <label class="form-label">New Batter</label>
                    <select id="w-new-batter" class="form-select"></select>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="w-crossed">
                    <label class="form-check-label" for="w-crossed">Batters Crossed (Non-striker takes strike)</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger w-100" onclick="app.submitWicket()">Record Wicket</button>
            </div>
        </div>
    </div>
</div>

<!-- Extras Modal -->
<div class="modal fade" id="extrasModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Extra</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <button class="btn btn-outline-warning w-100 extras-type-btn" data-type="wide">Wide (WD)</button>
                    </div>
                    <div class="col-6">
                        <button class="btn btn-outline-warning w-100 extras-type-btn" data-type="no_ball">No Ball (NB)</button>
                    </div>
                    <div class="col-6">
                        <button class="btn btn-outline-secondary w-100 extras-type-btn" data-type="bye">Byes (B)</button>
                    </div>
                    <div class="col-6">
                        <button class="btn btn-outline-secondary w-100 extras-type-btn" data-type="leg_bye">Leg Byes (LB)</button>
                    </div>
                </div>
                <input type="hidden" id="ex-type" value="">
                
                <div class="mb-3">
                    <label class="form-label">Additional Runs (excluding penalty for extra)</label>
                    <input type="number" id="ex-runs" class="form-control" value="0" min="0">
                    <small class="text-muted">e.g. For Wide + boundary, enter 4. Total extras will be 5.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning w-100" onclick="app.submitExtra()">Save Extra</button>
            </div>
        </div>
    </div>
</div>

<!-- Change Player Modal -->
<div class="modal fade" id="changePlayerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changePlayerModalTitle">Change Player</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="cp-role" value="">
                <div class="mb-3">
                    <label class="form-label">Select Player</label>
                    <select id="cp-player" class="form-select"></select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-club-primary w-100" onclick="app.submitChangePlayer()">Save</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('script')
<script>
const FIXTURE_ID = {{ $fixture->id }};
let FIXTURE_MATCH_ID = {{ $fixture->match?->id ?? 'null' }};
const CLUB_IS_HOME = {{ $fixture->clubPlaysHome() ? 'true' : 'false' }};
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

const app = {
    state: null,
    matchId: FIXTURE_MATCH_ID,
    
    init() {
        this.fetchState();
        
        // Extras type toggle
        $('.extras-type-btn').click(function() {
            $('.extras-type-btn').removeClass('active');
            $(this).addClass('active');
            $('#ex-type').val($(this).data('type'));
        });
    },
    
    showLoader() { $('#loader').fadeIn(150); },
    hideLoader() { $('#loader').fadeOut(150); },
    
    apiCall(url, data, method = 'POST') {
        this.showLoader();
        return $.ajax({
            url: url,
            type: method,
            data: data ? JSON.stringify(data) : null,
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
        }).always(() => {
            this.hideLoader();
        });
    },
    
    fetchState() {
        if (!this.matchId) {
            // Fetch readiness
            this.apiCall(`/club/scoring/live/${FIXTURE_ID}/readiness`, null, 'GET')
                .done(res => this.handleReadiness(res.data))
                .fail(this.handleError);
        } else {
            // Fetch live state
            this.apiCall(`/club/scoring/live/matches/${this.matchId}/live`, null, 'GET')
                .done(res => this.renderLiveState(res.data))
                .fail(this.handleError);
        }
    },
    
    handleReadiness(data) {
        this.state = { readiness: data };
        $('#live-view').hide();
        $('#pre-match-view').show();
        
        const $actions = $('#pre-match-actions').empty();
        
        if (!data.toss_done) {
            $('#pre-match-title').text('Toss Not Recorded');
            $('#pre-match-msg').text('You must record the toss before starting the match.');
            $actions.append(`<button class="btn btn-club-primary w-100" data-bs-toggle="modal" data-bs-target="#tossModal">Record Toss</button>`);
        } else if (!data.ready_to_start) {
            $('#pre-match-title').text('Match Not Ready');
            let msg = 'Missing setup: ';
            let errors = [];
            if (!data.has_club_squad) errors.push('Club Squad missing');
            if (!data.has_opponent_squad) errors.push('Opponent Squad missing');
            $('#pre-match-msg').text(msg + errors.join(', '));
            $actions.append(`<a href="/club/fixtures/${FIXTURE_ID}/edit" class="btn btn-outline-secondary w-100">Go to Setup</a>`);
        } else {
            $('#pre-match-title').text('Ready to Start');
            $('#pre-match-msg').text('The toss has been recorded and squads are ready.');
            $actions.append(`<button class="btn btn-success w-100" onclick="app.showOpenersModal()">Start Match</button>`);
        }
    },
    
    submitToss() {
        const winner = $('#toss-winner').val();
        const decision = $('#toss-decision').val();
        
        this.apiCall(`/club/scoring/live/${FIXTURE_ID}/toss`, { winner_side: winner, decision: decision })
            .done(res => {
                $('#tossModal').modal('hide');
                iziToast.success({message: 'Toss recorded'});
                this.handleReadiness(res.data.readiness);
            })
            .fail(this.handleError);
    },
    
    showOpenersModal() {
        const data = this.state.readiness;
        const tossSide = data.toss_winner_side;
        const decision = data.toss_decision;
        
        const battingIsClub = (tossSide === 'club' && decision === 'bat') || (tossSide === 'opponent' && decision === 'bowl');
        
        let batSquad = battingIsClub ? data.club_squad : data.opponent_squad;
        let bowlSquad = battingIsClub ? data.opponent_squad : data.club_squad;
        
        this.populateSelect('#op-striker', batSquad);
        this.populateSelect('#op-non-striker', batSquad);
        this.populateSelect('#op-bowler', bowlSquad);
        
        this.state.openersContext = { battingIsClub };
        $('#openersModal').modal('show');
    },
    
    populateSelect(selector, list) {
        const $el = $(selector).empty();
        $el.append(new Option('-- Select --', ''));
        list.forEach((item, index) => {
            const val = item.user_id ? item.user_id : (item.id || index);
            let name = 'Unknown';
            if (item.player) {
                name = (item.player.first_name || '') + ' ' + (item.player.last_name || '');
            } else if (item.name) {
                name = item.name; // External player might just be a string or object with name
            } else if (item.player_name) {
                name = item.player_name;
            } else if (typeof item === 'string') {
                name = item;
            }
            $el.append(new Option(name.trim(), val));
        });
    },
    
    submitOpeners() {
        const s = $('#op-striker').val();
        const ns = $('#op-non-striker').val();
        const b = $('#op-bowler').val();
        
        if (!s || !ns || !b) return iziToast.error({message: 'Select all players'});
        if (s === ns) return iziToast.error({message: 'Striker and non-striker must be different'});
        
        const payload = this.state.openersContext.battingIsClub ? {
            striker_user_id: s,
            non_striker_user_id: ns,
            opening_bowler_player_index: b
        } : {
            striker_player_index: s,
            non_striker_player_index: ns,
            opening_bowler_user_id: b
        };
        
        const url = this.matchId 
            ? `/club/scoring/live/matches/${this.matchId}/start-second-innings`
            : `/club/scoring/live/${FIXTURE_ID}/start`;
            
        this.apiCall(url, payload)
            .done(res => {
                $('#openersModal').modal('hide');
                this.matchId = this.matchId || (res.data.match_id ? res.data.match_id : res.data.match?.id);
                // After starting, fetch state to get full structure
                this.fetchState();
            })
            .fail(this.handleError);
    },
    
    renderLiveState(state) {
        this.state = state;
        
        if (state.match.status === 'completed') {
            $('#live-view, #pre-match-view').hide();
            iziToast.info({message: 'Match is completed. Redirecting...'});
            setTimeout(() => window.location.href = `/club/scoring/matches/${FIXTURE_ID}`, 1500);
            return;
        }
        
        if (state.match.status === 'innings_break') {
            $('#live-view').hide();
            $('#pre-match-view').show();
            $('#pre-match-title').text('Innings Break');
            $('#pre-match-msg').text(state.first_innings.summary || 'First innings completed.');
            
            // Re-fetch readiness to get squads for second innings
            this.apiCall(`/club/scoring/live/${FIXTURE_ID}/readiness`, null, 'GET')
                .done(res => {
                    this.state.readiness = res.data;
                    $('#pre-match-actions').html(`<button class="btn btn-success w-100" onclick="app.showOpenersModal()">Start Second Innings</button>`);
                });
            return;
        }
        
        $('#pre-match-view').hide();
        $('#live-view').show();
        
        const inn = state.current_innings;
        
        $('#batting-team-name').text(inn.batting_is_club ? state.fixture.club_display : state.fixture.opponent_display);
        $('#total-runs').text(inn.runs);
        $('#total-wickets').text(inn.wickets);
        $('#total-overs').text(inn.overs);
        $('#max-overs').text(state.fixture.overs_per_innings);
        
        if (inn.innings_number === 2) {
            $('#target-info').show();
            const target = state.first_innings.runs + 1;
            const remaining = target - inn.runs;
            const ballsBowled = Math.floor(inn.overs) * 6 + Math.round((inn.overs % 1) * 10);
            const maxBalls = state.fixture.overs_per_innings * 6;
            
            $('#target-runs').text(target);
            $('#runs-needed').text(remaining > 0 ? remaining : 0);
            $('#balls-remaining').text(maxBalls - ballsBowled);
        }
        
        // Players
        const striker = state.striker;
        const nonStriker = state.non_striker;
        const bowler = state.bowler;
        
        let battersHtml = '';
        if (striker) battersHtml += `<div class="player-row striker"><div><i class="fas fa-caret-right me-1 text-primary"></i>${striker.name}</div><div class="stats">${striker.runs} <span class="text-muted text-sm">(${striker.balls})</span></div></div>`;
        if (nonStriker) battersHtml += `<div class="player-row"><div>${nonStriker.name}</div><div class="stats">${nonStriker.runs} <span class="text-muted text-sm">(${nonStriker.balls})</span></div></div>`;
        $('#batters-list').html(battersHtml);
        
        if (bowler) {
            $('#bowler-info').html(`<div class="player-row bg-light rounded border"><div>${bowler.name}</div><div class="stats">${bowler.overs}-${bowler.maidens}-${bowler.runs}-${bowler.wickets}</div></div>`);
        }
        
        // Timeline
        const recent = (inn.recent_balls || []).slice(-10);
        let timelineHtml = '';
        recent.forEach(b => {
            let cssClass = '';
            let text = b.runs_scored;
            
            if (b.is_wicket) { cssClass = 'wicket'; text = 'W'; }
            else if (b.extras_type) { cssClass = 'extra'; text = b.total_runs + (b.extras_type === 'wide' ? 'wd' : 'nb'); }
            else if (b.runs_scored >= 4) { cssClass = 'boundary'; }
            
            timelineHtml += `<div class="ball-bubble ${cssClass}">${text}</div>`;
        });
        $('#over-timeline').html(timelineHtml || '<span class="text-muted small">No balls yet</span>');
        
        // Cache players for modals
        this.cacheAvailablePlayers(state);
    },
    
    cacheAvailablePlayers(state) {
        if (!this.state.readiness) {
            this.apiCall(`/club/scoring/live/${FIXTURE_ID}/readiness`, null, 'GET')
                .done(res => this.state.readiness = res.data);
        }
    },
    
    recordRuns(runs) {
        this.apiCall(`/club/scoring/live/matches/${this.matchId}/balls`, {
            event_type: runs === 0 ? 'dot' : 'run',
            runs_scored: runs,
            total_runs: runs
        }).done(res => this.renderLiveState(res.data.live))
          .fail(this.handleError);
    },
    
    showExtrasModal() {
        $('#extrasModal').modal('show');
    },
    
    submitExtra() {
        const type = $('#ex-type').val();
        const runs = parseInt($('#ex-runs').val()) || 0;
        
        if (!type) return iziToast.error({message: 'Select an extra type'});
        
        let total = runs;
        if (type === 'wide' || type === 'no_ball') total += 1; // default penalty
        
        this.apiCall(`/club/scoring/live/matches/${this.matchId}/balls`, {
            event_type: type,
            extras_runs: total,
            runs_scored: runs,
            total_runs: total
        }).done(res => {
            $('#extrasModal').modal('hide');
            this.renderLiveState(res.data.live);
        }).fail(this.handleError);
    },
    
    showWicketModal() {
        if (!this.state.readiness) return iziToast.warning({message: 'Loading squad data... try again.'});
        
        const inn = this.state.current_innings;
        const batSquad = inn.batting_is_club ? this.state.readiness.club_squad : this.state.readiness.opponent_squad;
        const bowlSquad = inn.batting_is_club ? this.state.readiness.opponent_squad : this.state.readiness.club_squad;
        
        this.populateSelect('#w-new-batter', batSquad); // We should technically filter out those who are out or batting
        this.populateSelect('#w-fielder', bowlSquad);
        
        $('#wicketModal').modal('show');
        this.onDismissalTypeChange();
    },
    
    onDismissalTypeChange() {
        const type = $('#w-type').val();
        if (['caught', 'run_out', 'stumped'].includes(type)) {
            $('#w-fielder-div').show();
        } else {
            $('#w-fielder-div').hide();
        }
    },
    
    submitWicket() {
        const type = $('#w-type').val();
        const runs = parseInt($('#w-runs').val()) || 0;
        const fielder = $('#w-fielder').val();
        
        let wicketData = { dismissal_type: type };
        if (['caught', 'run_out', 'stumped'].includes(type) && fielder) {
            const inn = this.state.current_innings;
            if (inn.bowling_is_club) {
                wicketData.fielder_one_user_id = fielder;
            } else {
                wicketData.fielder_one_player_index = fielder;
            }
        }
        
        this.apiCall(`/club/scoring/live/matches/${this.matchId}/balls`, {
            event_type: 'wicket',
            runs_scored: runs,
            total_runs: runs,
            wicket: wicketData
        }).done(res => {
            $('#wicketModal').modal('hide');
            
            // Immediately change batter after a wicket using the selected new batter
            const newBatter = $('#w-new-batter').val();
            const crossed = $('#w-crossed').is(':checked');
            
            // For simplicity, we just send a changeBatter request if new batter is selected
            if (newBatter) {
                const side = crossed ? 'non_striker' : 'striker';
                const payload = this.state.current_innings.batting_is_club 
                    ? { user_id: newBatter, side: side } 
                    : { player_index: newBatter, side: side };
                    
                this.apiCall(`/club/scoring/live/matches/${this.matchId}/change-batter`, payload)
                    .done(res2 => this.renderLiveState(res2.data.live));
            } else {
                this.renderLiveState(res.data.live);
            }
                
        }).fail(this.handleError);
    },
    
    changeBatter() {
        if (!this.state.readiness) return iziToast.warning({message: 'Loading squad data... try again.'});
        
        const inn = this.state.current_innings;
        const batSquad = inn.batting_is_club ? this.state.readiness.club_squad : this.state.readiness.opponent_squad;
        
        this.populateSelect('#cp-player', batSquad);
        $('#cp-role').val('batter');
        $('#changePlayerModalTitle').text('Select New Batter');
        $('#changePlayerModal').modal('show');
    },
    
    changeBowler() {
        if (!this.state.readiness) return iziToast.warning({message: 'Loading squad data... try again.'});
        
        const inn = this.state.current_innings;
        const bowlSquad = inn.batting_is_club ? this.state.readiness.opponent_squad : this.state.readiness.club_squad;
        
        this.populateSelect('#cp-player', bowlSquad);
        $('#cp-role').val('bowler');
        $('#changePlayerModalTitle').text('Select New Bowler');
        $('#changePlayerModal').modal('show');
    },
    
    submitChangePlayer() {
        const role = $('#cp-role').val();
        const player = $('#cp-player').val();
        const inn = this.state.current_innings;
        
        if (!player) return iziToast.error({message: 'Select a player'});
        
        if (role === 'bowler') {
            const payload = inn.bowling_is_club ? { user_id: player } : { player_index: player };
            this.apiCall(`/club/scoring/live/matches/${this.matchId}/change-bowler`, payload)
                .done(res => {
                    $('#changePlayerModal').modal('hide');
                    this.renderLiveState(res.data.live);
                }).fail(this.handleError);
        } else if (role === 'batter') {
            const payload = inn.batting_is_club ? { user_id: player } : { player_index: player };
            // Optional: allow specifying which side to replace, for now default to striker
            this.apiCall(`/club/scoring/live/matches/${this.matchId}/change-batter`, payload)
                .done(res => {
                    $('#changePlayerModal').modal('hide');
                    this.renderLiveState(res.data.live);
                }).fail(this.handleError);
        }
    },
    
    endInnings() {
        if (confirm("Are you sure you want to declare/end this innings?")) {
            this.apiCall(`/club/scoring/live/matches/${this.matchId}/end-innings`, { result: 'innings_declared' })
                .done(res => {
                    iziToast.success({message: 'Innings Ended'});
                    this.fetchState();
                }).fail(this.handleError);
        }
    },
    
    handleError(err) {
        const msg = err.responseJSON?.message || err.responseJSON?.error || 'An error occurred';
        iziToast.error({message: msg});
        app.hideLoader();
    }
};

$(document).ready(() => app.init());
</script>
@endpush
