@extends('club.layouts.master')

@section('title', $title)

@section('content')
<main class="club-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <p class="text-muted small mb-0">
            {{ $fixture->home_display_name }} vs {{ $fixture->away_display_name }}
        </p>
        <a href="{{ route('club.fixtures.index') }}" class="btn btn-sm btn-light border">
            <i class="fas fa-arrow-left me-1"></i> Back to fixtures
        </a>
    </div>

    <div class="club-card">
        <div class="club-card-header">
            <h6 class="mb-0">Insert Match Score</h6>
            <span class="club-badge {{ $fixture->status === 'published' ? 'success' : 'muted' }}">{{ ucfirst($fixture->status) }}</span>
        </div>
        <div class="club-card-body padded">
            <form action="{{ route('club.fixtures.insert-score.store', $fixture) }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <h6 class="border-bottom pb-2 mb-3">Home: {{ $fixture->home_display_name }}</h6>
                        <div class="mb-3">
                            <label class="form-label">Runs</label>
                            <input type="number" name="home_team_runs" class="form-control" value="{{ old('home_team_runs', $fixture->home_team_runs) }}" min="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Wickets</label>
                            <input type="number" name="home_team_wickets" class="form-control" value="{{ old('home_team_wickets', $fixture->home_team_wickets) }}" min="0" max="10">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Overs</label>
                            <input type="number" step="0.1" name="home_team_overs" class="form-control" value="{{ old('home_team_overs', $fixture->home_team_overs) }}" min="0">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="border-bottom pb-2 mb-3">Away: {{ $fixture->away_display_name }}</h6>
                        <div class="mb-3">
                            <label class="form-label">Runs</label>
                            <input type="number" name="away_team_runs" class="form-control" value="{{ old('away_team_runs', $fixture->away_team_runs) }}" min="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Wickets</label>
                            <input type="number" name="away_team_wickets" class="form-control" value="{{ old('away_team_wickets', $fixture->away_team_wickets) }}" min="0" max="10">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Overs</label>
                            <input type="number" step="0.1" name="away_team_overs" class="form-control" value="{{ old('away_team_overs', $fixture->away_team_overs) }}" min="0">
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Match Status</label>
                            <select name="status" class="form-select">
                                <option value="completed" @selected(old('status', $fixture->status) === 'completed')>Completed</option>
                                <option value="abandoned" @selected(old('status', $fixture->status) === 'abandoned')>Abandoned</option>
                                <option value="live" @selected(old('status', $fixture->status) === 'live')>Live</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Winner</label>
                            <select name="winner_team_id" class="form-select">
                                <option value="">None / External Team</option>
                                @if($fixture->home_team_id)
                                    <option value="{{ $fixture->home_team_id }}" @selected(old('winner_team_id', $fixture->winner_team_id) == $fixture->home_team_id)>{{ $fixture->homeTeam->name }}</option>
                                @endif
                                @if($fixture->away_team_id)
                                    <option value="{{ $fixture->away_team_id }}" @selected(old('winner_team_id', $fixture->winner_team_id) == $fixture->away_team_id)>{{ $fixture->awayTeam->name }}</option>
                                @endif
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Result Type</label>
                            <select name="result_type" class="form-select">
                                <option value="">Custom Description Only</option>
                                <option value="runs" @selected(old('result_type', $fixture->result_type) === 'runs')>Won by runs</option>
                                <option value="wickets" @selected(old('result_type', $fixture->result_type) === 'wickets')>Won by wickets</option>
                                <option value="tie" @selected(old('result_type', $fixture->result_type) === 'tie')>Tie</option>
                                <option value="dl_method" @selected(old('result_type', $fixture->result_type) === 'dl_method')>Won by DLS</option>
                                <option value="draw" @selected(old('result_type', $fixture->result_type) === 'draw')>Draw</option>
                                <option value="no_result" @selected(old('result_type', $fixture->result_type) === 'no_result')>No Result</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Margin (e.g. 10 runs, 5 wickets)</label>
                            <input type="number" name="result_margin" class="form-control" value="{{ old('result_margin', $fixture->result_margin) }}" min="1">
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label">Result Description (Optional / Fallback)</label>
                            <input type="text" name="result_description" class="form-control" value="{{ old('result_description', $fixture->result_description) }}" placeholder="e.g. {{ $fixture->home_display_name }} won by 10 runs">
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-club-primary">
                        <i class="fas fa-save me-1"></i> Save Score
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>
@endsection
