<div class="row g-3">
    <div class="col-12">
        <label for="player_search" class="form-label">Search player</label>
        <input type="search" class="form-control" id="player_search"
            placeholder="Type name or email to filter..." autocomplete="off">
    </div>

    <div class="col-12">
        <label for="player_id" class="form-label">Select player <span class="text-danger">*</span></label>
        @if($players->isEmpty())
            <div class="alert alert-light border mb-0">
                <i class="fas fa-info-circle me-1 text-muted"></i>
                No players available to invite. All registered players may already be in your club or have a pending invite.
            </div>
        @else
            <select class="form-select @error('player_id') is-invalid @enderror" id="player_id" name="player_id" required size="8">
                <option value="">Choose a player</option>
                @foreach($players as $player)
                    <option value="{{ $player->id }}"
                        data-search="{{ strtolower($player->full_name . ' ' . $player->email . ' ' . ($player->phone ?? '')) }}"
                        @selected((string) old('player_id') === (string) $player->id)>
                        {{ $player->full_name }} — {{ $player->email }}
                        @if($player->playerProfile?->primary_role)
                            ({{ str_replace('_', ' ', $player->playerProfile->primary_role) }})
                        @endif
                    </option>
                @endforeach
            </select>
            @error('player_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            <small class="text-muted">{{ $players->count() }} player(s) available from the system.</small>
        @endif
    </div>

    <div class="col-md-6">
        <label for="team_id" class="form-label">Assign to squad</label>
        <select class="form-select @error('team_id') is-invalid @enderror" id="team_id" name="team_id">
            <option value="">Club only (no squad yet)</option>
            @foreach($teams as $team)
                <option value="{{ $team->id }}" @selected((string) old('team_id') === (string) $team->id)>
                    {{ $team->name }}
                </option>
            @endforeach
        </select>
        @error('team_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
        <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
            @foreach(['player' => 'Player', 'captain' => 'Captain', 'manager' => 'Manager', 'scorer' => 'Scorer', 'admin' => 'Admin'] as $value => $label)
                <option value="{{ $value }}" @selected(old('role', $invitation->role ?? 'player') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="expires_in_days" class="form-label">Expires in</label>
        <select class="form-select @error('expires_in_days') is-invalid @enderror" id="expires_in_days" name="expires_in_days">
            @foreach([3 => '3 days', 7 => '7 days', 14 => '14 days', 30 => '30 days'] as $days => $label)
                <option value="{{ $days }}" @selected((int) old('expires_in_days', 7) === $days)>{{ $label }}</option>
            @endforeach
        </select>
        @error('expires_in_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label for="message" class="form-label">Personal message</label>
        <textarea class="form-control @error('message') is-invalid @enderror"
            id="message" name="message" rows="3"
            placeholder="Optional note included in the invitation email">{{ old('message') }}</textarea>
        @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

@push('script')
<script>
    (function () {
        const search = document.getElementById('player_search');
        const select = document.getElementById('player_id');
        if (!search || !select) return;

        const options = Array.from(select.options).slice(1);

        search.addEventListener('input', function () {
            const term = this.value.trim().toLowerCase();

            options.forEach(function (option) {
                const haystack = option.dataset.search || option.textContent.toLowerCase();
                option.hidden = term !== '' && !haystack.includes(term);
            });

            const visible = options.filter(function (option) { return !option.hidden; });
            if (visible.length === 1) {
                select.value = visible[0].value;
            } else if (!visible.some(function (option) { return option.value === select.value; })) {
                select.value = '';
            }
        });
    })();
</script>
@endpush
