@extends('admin.layouts.master')
@section('players', 'active')
@section('title', $title ?? 'Player Details')

@section('content')
<main class="container-fluid p-3 p-lg-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <a href="{{ route('admin.players.index') }}" class="text-decoration-none small">&larr; Back to players</a>
            <h2 class="page-content-title fw-medium fs-5 mb-1 mt-2">{{ $player->name }}</h2>
            <p class="text-muted mb-0 small">{{ $player->email }}</p>
        </div>
        <div>
            <form method="POST" action="{{ route('admin.players.toggle-active', $player) }}">
                @csrf
                <button type="submit" class="btn btn-sm {{ $player->is_active ? 'btn-warning' : 'btn-success' }}">
                    {{ $player->is_active ? 'Deactivate player' : 'Activate player' }}
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body py-3"><p class="text-muted small mb-1">Clubs</p><h4 class="mb-0">{{ $stats['clubs'] }}</h4></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body py-3"><p class="text-muted small mb-1">Squads</p><h4 class="mb-0">{{ $stats['teams'] }}</h4></div></div></div>
        <div class="col-md-2"><div class="card"><div class="card-body py-3"><p class="text-muted small mb-1">Matches</p><h4 class="mb-0">{{ $stats['total_matches'] }}</h4></div></div></div>
        <div class="col-md-2"><div class="card"><div class="card-body py-3"><p class="text-muted small mb-1">Runs</p><h4 class="mb-0">{{ $stats['total_runs'] }}</h4></div></div></div>
        <div class="col-md-2"><div class="card"><div class="card-body py-3"><p class="text-muted small mb-1">Wickets</p><h4 class="mb-0">{{ $stats['total_wickets'] }}</h4></div></div></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-white"><h6 class="mb-0">Player Profile</h6></div>
                <div class="card-body text-center">
                    @if($player->image)
                        <img src="{{ asset($player->image) }}" alt="" class="rounded-circle mb-3" style="width:96px;height:96px;object-fit:cover;">
                    @else
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center fw-bold mb-3" style="width:96px;height:96px;font-size:2rem;">
                            {{ strtoupper(substr($player->name, 0, 1)) }}
                        </div>
                    @endif
                    <table class="table table-sm text-start mb-0">
                        <tr><th class="text-muted">Phone</th><td>{{ $player->phone ?? '—' }}</td></tr>
                        <tr><th class="text-muted">Role</th><td>{{ $player->playerProfile?->role_label ?? '—' }}</td></tr>
                        <tr><th class="text-muted">Batting</th><td>{{ $player->playerProfile?->batting_style_label ?? '—' }}</td></tr>
                        <tr><th class="text-muted">Bowling</th><td>{{ $player->playerProfile?->bowling_style_label ?? '—' }}</td></tr>
                        <tr><th class="text-muted">Onboarded</th><td><span class="badge {{ $player->is_onboarded ? 'bg-success' : 'bg-secondary' }}">{{ $player->is_onboarded ? 'Yes' : 'No' }}</span></td></tr>
                        <tr><th class="text-muted">Status</th><td><span class="badge {{ $player->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $player->is_active ? 'Active' : 'Inactive' }}</span></td></tr>
                        <tr><th class="text-muted">Joined</th><td>{{ $player->created_at->format('d M Y') }}</td></tr>
                    </table>
                    @if($player->playerProfile?->bio)
                        <hr>
                        <p class="small text-muted text-start mb-0">{{ $player->playerProfile->bio }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-white"><h6 class="mb-0">Club Memberships</h6></div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>Club</th><th>Role</th><th>Status</th><th>Joined</th><th></th></tr>
                        </thead>
                        <tbody>
                            @forelse($player->clubMemberships as $membership)
                                <tr>
                                    <td class="fw-semibold">{{ $membership->club?->name ?? '—' }}</td>
                                    <td>{{ ucfirst($membership->role) }}</td>
                                    <td><span class="badge {{ $membership->status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($membership->status) }}</span></td>
                                    <td class="small text-muted">{{ $membership->joined_at?->format('d M Y') ?? '—' }}</td>
                                    <td>
                                        @if($membership->club)
                                            <a href="{{ route('admin.clubs.show', $membership->club) }}" class="btn btn-sm btn-outline-primary">View club</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">Not a member of any club.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-white"><h6 class="mb-0">Squads</h6></div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>Squad</th><th>Club</th><th>Role</th><th>Jersey</th></tr>
                        </thead>
                        <tbody>
                            @forelse($player->teams as $team)
                                <tr>
                                    <td class="fw-semibold">{{ $team->name }}</td>
                                    <td>{{ $team->club?->name ?? '—' }}</td>
                                    <td>{{ ucfirst($team->pivot->role ?? 'player') }}</td>
                                    <td>{{ $team->pivot->jersey_number ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">Not assigned to any squad.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($player->playerProfile)
            <div class="card mb-4">
                <div class="card-header bg-white"><h6 class="mb-0">Career Stats</h6></div>
                <div class="card-body">
                    <div class="row g-3 text-center">
                        <div class="col-4 col-md-2"><div class="fw-bold">{{ $player->playerProfile->total_matches }}</div><div class="small text-muted">Matches</div></div>
                        <div class="col-4 col-md-2"><div class="fw-bold">{{ $player->playerProfile->total_runs }}</div><div class="small text-muted">Runs</div></div>
                        <div class="col-4 col-md-2"><div class="fw-bold">{{ $player->playerProfile->total_wickets }}</div><div class="small text-muted">Wickets</div></div>
                        <div class="col-4 col-md-2"><div class="fw-bold">{{ $player->playerProfile->highest_score }}</div><div class="small text-muted">Highest</div></div>
                        <div class="col-4 col-md-2"><div class="fw-bold">{{ $player->playerProfile->total_fifties }}</div><div class="small text-muted">50s</div></div>
                        <div class="col-4 col-md-2"><div class="fw-bold">{{ $player->playerProfile->average ?? '—' }}</div><div class="small text-muted">Average</div></div>
                    </div>
                </div>
            </div>
            @endif

            <div class="card">
                <div class="card-header bg-white"><h6 class="mb-0">Recent Availability</h6></div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>Fixture</th><th>Status</th><th>Responded</th></tr>
                        </thead>
                        <tbody>
                            @forelse($player->availabilityResponses as $availability)
                                <tr>
                                    <td class="small">
                                        @if($availability->fixture)
                                            {{ $availability->fixture->home_display_name }} vs {{ $availability->fixture->away_display_name }}
                                            <span class="text-muted">({{ $availability->fixture->scheduled_date?->format('d M Y') }})</span>
                                        @else
                                            Fixture #{{ $availability->fixture_id }}
                                        @endif
                                    </td>
                                    <td><span class="badge bg-light text-dark">{{ $availability->status_label }}</span></td>
                                    <td class="small text-muted">{{ $availability->responded_at?->format('d M Y H:i') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-3">No availability responses yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
