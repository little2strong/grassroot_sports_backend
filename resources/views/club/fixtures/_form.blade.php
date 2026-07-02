@php
    $clubTeamId = old('club_team_id', $fixture->clubTeamId());
    $opponentName = old('opponent_name', $fixture->exists ? $fixture->opponentName() : '');
    $scheduledTime = old('scheduled_time', $fixture->scheduled_time
        ? \Carbon\Carbon::parse($fixture->scheduled_time)->format('H:i')
        : '');
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label for="club_team_id" class="form-label">Your squad <span class="text-danger">*</span></label>
        <select class="form-select @error('club_team_id') is-invalid @enderror" id="club_team_id" name="club_team_id" required>
            <option value="">Select squad</option>
            @foreach($teams as $team)
                <option value="{{ $team->id }}" @selected((string) $clubTeamId === (string) $team->id)>{{ $team->name }}</option>
            @endforeach
        </select>
        @error('club_team_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="opponent_name" class="form-label">Opponent <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('opponent_name') is-invalid @enderror"
            id="opponent_name" name="opponent_name" value="{{ $opponentName }}"
            placeholder="External team name" required>
        @error('opponent_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="club_plays_home" name="club_plays_home" value="1"
                @checked(old('club_plays_home', $fixture->club_plays_home ?? true))>
            <label class="form-check-label" for="club_plays_home">Club plays at home</label>
        </div>
        <small class="text-muted">If unchecked, your squad plays as the away team.</small>
    </div>

    <div class="col-md-4">
        <label for="scheduled_date" class="form-label">Date <span class="text-danger">*</span></label>
        <input type="date" class="form-control @error('scheduled_date') is-invalid @enderror"
            id="scheduled_date" name="scheduled_date"
            value="{{ old('scheduled_date', $fixture->scheduled_date?->format('Y-m-d')) }}" required>
        @error('scheduled_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="scheduled_time" class="form-label">Time</label>
        <input type="time" class="form-control @error('scheduled_time') is-invalid @enderror"
            id="scheduled_time" name="scheduled_time" value="{{ $scheduledTime }}">
        @error('scheduled_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="venue_id" class="form-label">Venue</label>
        <select class="form-select @error('venue_id') is-invalid @enderror" id="venue_id" name="venue_id">
            <option value="">No venue</option>
            @foreach($venues as $venue)
                <option value="{{ $venue->id }}" @selected((string) old('venue_id', $fixture->venue_id) === (string) $venue->id)>
                    {{ $venue->name }}@if($venue->city) — {{ $venue->city }}@endif
                </option>
            @endforeach
        </select>
        @error('venue_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="match_type" class="form-label">Match type <span class="text-danger">*</span></label>
        <select class="form-select @error('match_type') is-invalid @enderror" id="match_type" name="match_type" required>
            @foreach(['t10' => 'T10', 't20' => 'T20', 'odi_40' => 'ODI (40)', 'odi_50' => 'ODI (50)', 'test' => 'Test', 'custom' => 'Custom'] as $value => $label)
                <option value="{{ $value }}" @selected(old('match_type', $fixture->match_type) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('match_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="overs_per_innings" class="form-label">Overs per innings</label>
        <input type="number" class="form-control @error('overs_per_innings') is-invalid @enderror"
            id="overs_per_innings" name="overs_per_innings" min="1" max="500"
            value="{{ old('overs_per_innings', $fixture->overs_per_innings) }}">
        @error('overs_per_innings')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="ball_type" class="form-label">Ball type <span class="text-danger">*</span></label>
        <select class="form-select @error('ball_type') is-invalid @enderror" id="ball_type" name="ball_type" required>
            @foreach(['leather' => 'Leather', 'tennis' => 'Tennis', 'tape' => 'Tape'] as $value => $label)
                <option value="{{ $value }}" @selected(old('ball_type', $fixture->ball_type) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('ball_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
            @foreach(['draft' => 'Draft', 'published' => 'Published', 'postponed' => 'Postponed', 'cancelled' => 'Cancelled'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $fixture->status ?? 'draft') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 d-flex align-items-end">
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" id="is_public" name="is_public" value="1"
                @checked(old('is_public', $fixture->is_public ?? true))>
            <label class="form-check-label" for="is_public">Public fixture (visible on live score page)</label>
        </div>
    </div>
</div>

@push('script')
<script>
    const oversDefaults = { t10: 10, t20: 20, odi_40: 40, odi_50: 50, test: 90, custom: 20 };
    const matchType = document.getElementById('match_type');
    const oversInput = document.getElementById('overs_per_innings');

    matchType?.addEventListener('change', function () {
        if (!oversInput.value || oversInput.dataset.auto === '1') {
            oversInput.value = oversDefaults[this.value] || 20;
            oversInput.dataset.auto = '1';
        }
    });

    oversInput?.addEventListener('input', function () {
        this.dataset.auto = '0';
    });
</script>
@endpush
