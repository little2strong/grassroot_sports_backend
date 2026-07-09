@extends('club.layouts.master')

@section('title', $title)

@section('content')
<main class="club-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="min-w-0">
            <p class="text-muted small mb-1">Manage players in</p>
            <h5 class="mb-0 text-truncate">{{ $team->name }}</h5>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('club.squads.index') }}" class="btn btn-sm btn-light border">
                <i class="fas fa-arrow-left me-1"></i> Back to squads
            </a>
        </div>
    </div>

    <div class="club-card mb-3">
        <div class="club-card-header">
            <h6 class="mb-0"><i class="fas fa-user-plus me-2 text-success"></i>Add Player</h6>
        </div>
        <div class="club-card-body padded">
            <form method="POST" action="{{ route('club.squads.players.add', $team) }}" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label class="form-label">Player <span class="text-danger">*</span></label>
                    <select name="player_id" class="form-select @error('player_id') is-invalid @enderror" required>
                        <option value="">Select player</option>
                        @foreach($availablePlayers as $player)
                            <option value="{{ $player->id }}" @selected((string) old('player_id') === (string) $player->id)>
                                {{ $player->full_name ?: $player->email }} — {{ $player->email }}
                            </option>
                        @endforeach
                    </select>
                    @error('player_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @if($availablePlayers->isEmpty())
                        <small class="text-muted">No available club members to add. Invite players first, or remove from this squad.</small>
                    @endif
                </div>
                <div class="col-md-3">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                        @foreach(['player' => 'Player', 'captain' => 'Captain', 'manager' => 'Manager', 'scorer' => 'Scorer'] as $v => $label)
                            <option value="{{ $v }}" @selected(old('role', 'player') === $v)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Jersey #</label>
                    <input type="number" name="jersey_number" class="form-control @error('jersey_number') is-invalid @enderror"
                        value="{{ old('jersey_number') }}" min="0" max="999" placeholder="Optional">
                    @error('jersey_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="move_from_other_teams" name="move_from_other_teams" value="1"
                            @checked(old('move_from_other_teams', true))>
                        <label class="form-check-label" for="move_from_other_teams">Move from other squads (one player can be active in one squad)</label>
                    </div>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-club-primary" @disabled($availablePlayers->isEmpty())>
                        <i class="fas fa-plus me-1"></i> Add Player
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="club-card">
        <div class="club-card-header">
            <h6 class="mb-0"><i class="fas fa-users me-2 text-success"></i>Squad Players</h6>
            <span class="club-badge muted">{{ $members->count() }} players</span>
        </div>
        <div class="club-card-body">
            @if($members->isEmpty())
                <div class="club-empty">
                    <i class="fas fa-user-friends"></i>
                    <p class="fw-semibold mb-1">No players in this squad</p>
                    <small>Add players using the form above.</small>
                </div>
            @else
                <div class="table-responsive d-none d-md-block">
                    <table class="table club-fixtures-table mb-0">
                        <thead>
                            <tr>
                                <th>Player</th>
                                <th>Role</th>
                                <th>Jersey</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($members as $member)
                                <tr>
                                    <td>
                                        <div class="fw-medium">{{ $member->user?->full_name ?: $member->user?->email }}</div>
                                        <div class="small text-muted">{{ $member->user?->email }}</div>
                                    </td>
                                    <td><span class="club-badge muted">{{ ucfirst($member->role) }}</span></td>
                                    <td class="small text-muted">{{ $member->jersey_number ?? '—' }}</td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2 flex-wrap">
                                            @if($otherTeams->isNotEmpty())
                                                <form method="POST" action="{{ route('club.squads.players.move', [$team, $member->user_id]) }}" class="d-flex gap-2">
                                                    @csrf
                                                    <select name="to_team_id" class="form-select form-select-sm" required>
                                                        <option value="">Move to...</option>
                                                        @foreach($otherTeams as $t)
                                                            <option value="{{ $t->id }}">{{ $t->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="btn btn-sm btn-light border">
                                                        <i class="fas fa-arrow-right"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            <form method="POST" action="{{ route('club.squads.players.remove', [$team, $member->user_id]) }}" class="squad-player-remove-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-light border text-danger" title="Remove">
                                                    <i class="fas fa-user-minus"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="club-fixture-mobile-list d-md-none">
                    @foreach($members as $member)
                        <div class="club-fixture-mobile-item">
                            <div class="match-teams mb-1">{{ $member->user?->full_name ?: $member->user?->email }}</div>
                            <div class="match-meta mb-2">
                                <span class="club-badge muted">{{ ucfirst($member->role) }}</span>
                                <span>Jersey: {{ $member->jersey_number ?? '—' }}</span>
                            </div>
                            <div class="club-table-actions">
                                @if($otherTeams->isNotEmpty())
                                    <form method="POST" action="{{ route('club.squads.players.move', [$team, $member->user_id]) }}" class="flex-grow-1 d-flex gap-2">
                                        @csrf
                                        <select name="to_team_id" class="form-select form-select-sm" required>
                                            <option value="">Move to...</option>
                                            @foreach($otherTeams as $t)
                                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-light border">
                                            <i class="fas fa-arrow-right"></i>
                                        </button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('club.squads.players.remove', [$team, $member->user_id]) }}" class="flex-grow-1 squad-player-remove-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light border text-danger w-100">
                                        <i class="fas fa-user-minus me-1"></i> Remove
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</main>
@endsection

@push('script')
<script>
    document.querySelectorAll('.squad-player-remove-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            Swal.fire({
                title: 'Remove player?',
                text: 'Player will be removed from this squad.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, remove',
                cancelButtonText: 'Cancel'
            }).then(function (result) {
                if (result.isConfirmed) form.submit();
            });
        });
    });
</script>
@endpush

