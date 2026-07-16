@extends('club.layouts.master')

@section('title', $title)

@section('content')
<main class="club-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="min-w-0">
            <p class="text-muted small mb-1">Player details</p>
            <h5 class="mb-0 text-truncate">{{ $player->full_name ?: $player->email }}</h5>
            <p class="text-muted small mb-0">{{ $player->email }}</p>
        </div>
        <a href="{{ route('club.players.index') }}" class="btn btn-sm btn-light border">
            <i class="fas fa-arrow-left me-1"></i> Back to players
        </a>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="club-card h-100">
                <div class="club-card-header">
                    <h6 class="mb-0"><i class="fas fa-id-card me-2 text-success"></i>Profile</h6>
                </div>
                <div class="club-card-body padded">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center fw-bold"
                             style="width:56px;height:56px;">
                            {{ strtoupper(substr($player->full_name ?: $player->email, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <div class="fw-semibold text-truncate">{{ $player->full_name ?: '—' }}</div>
                            <div class="small text-muted text-truncate">{{ $player->phone ?: 'No phone' }}</div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="club-badge muted">Club role: {{ ucfirst($membership->role) }}</span>
                        <span class="club-badge {{ $membership->status === 'active' ? 'success' : 'muted' }}">{{ ucfirst($membership->status) }}</span>
                    </div>

                    @if($playerClubs && $playerClubs->count() > 1)
                        <div class="mb-3">
                            <h6 class="small text-muted mb-2">Other Clubs</h6>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($playerClubs as $playerClub)
                                    @if($playerClub['club'] && $playerClub['club']->id !== $club->id)
                                        <span class="club-badge muted">{{ $playerClub['club']->name }}</span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <table class="table table-sm mb-0">
                        <tr>
                            <td class="text-muted small">Primary role</td>
                            <td class="text-end fw-medium">{{ $player->playerProfile?->role_label ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Batting</td>
                            <td class="text-end fw-medium">{{ $player->playerProfile?->batting_style_label ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Bowling</td>
                            <td class="text-end fw-medium">{{ $player->playerProfile?->bowling_style_label ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Joined club</td>
                            <td class="text-end fw-medium">{{ $membership->joined_at?->format('d M Y') ?? '—' }}</td>
                        </tr>
                    </table>

                    @if($player->playerProfile?->bio)
                        <hr class="my-3">
                        <div class="small text-muted">{{ $player->playerProfile->bio }}</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="club-card mb-3">
                <div class="club-card-header">
                    <h6 class="mb-0"><i class="fas fa-chart-line me-2 text-success"></i>Stats</h6>
                </div>
                <div class="club-card-body padded">
                    <div class="row g-3 text-center">
                        <div class="col-6 col-md-3">
                            <div class="fw-bold">{{ $stats['total_matches'] }}</div>
                            <div class="small text-muted">Matches</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="fw-bold">{{ $stats['total_runs'] }}</div>
                            <div class="small text-muted">Runs</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="fw-bold">{{ $stats['total_wickets'] }}</div>
                            <div class="small text-muted">Wickets</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="fw-bold">{{ $stats['highest_score'] }}</div>
                            <div class="small text-muted">Highest</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="fw-bold">{{ $stats['total_fifties'] }}</div>
                            <div class="small text-muted">50s</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="fw-bold">{{ $stats['total_hundreds'] }}</div>
                            <div class="small text-muted">100s</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="fw-bold">{{ $stats['total_five_wickets'] }}</div>
                            <div class="small text-muted">5W</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="fw-bold">{{ $player->playerProfile?->average ?? '—' }}</div>
                            <div class="small text-muted">Avg</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="club-card">
                <div class="club-card-header">
                    <h6 class="mb-0"><i class="fas fa-calendar-check me-2 text-success"></i>Recent Availability</h6>
                    <span class="club-badge muted">{{ $recentAvailability->count() }} shown</span>
                </div>
                <div class="club-card-body">
                    @if($recentAvailability->isEmpty())
                        <div class="club-empty">
                            <i class="fas fa-user-clock"></i>
                            <p class="fw-semibold mb-1">No availability yet</p>
                            <small>This player hasn’t responded for any fixtures.</small>
                        </div>
                    @else
                        <div class="table-responsive d-none d-md-block">
                            <table class="table club-fixtures-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Fixture</th>
                                        <th>Status</th>
                                        <th class="text-end">Responded</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentAvailability as $availability)
                                        @php
                                            $badge = match($availability->status) {
                                                'available' => 'success',
                                                'unavailable' => 'danger',
                                                default => 'muted',
                                            };
                                        @endphp
                                        <tr>
                                            <td class="small">
                                                @if($availability->fixture)
                                                    {{ $availability->fixture->home_display_name }} vs {{ $availability->fixture->away_display_name }}
                                                    <span class="text-muted">({{ $availability->fixture->scheduled_date?->format('d M Y') }})</span>
                                                @else
                                                    Fixture #{{ $availability->fixture_id }}
                                                @endif
                                            </td>
                                            <td><span class="club-badge {{ $badge }}">{{ $availability->status_label }}</span></td>
                                            <td class="small text-muted text-end">{{ $availability->responded_at?->format('d M Y H:i') ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="club-fixture-mobile-list d-md-none">
                            @foreach($recentAvailability as $availability)
                                <div class="club-fixture-mobile-item">
                                    <div class="match-teams mb-1">
                                        {{ $availability->fixture?->home_display_name ?? 'Fixture' }}
                                        <span class="text-muted fw-normal">vs</span>
                                        {{ $availability->fixture?->away_display_name ?? '#' . $availability->fixture_id }}
                                    </div>
                                    <div class="match-meta">
                                        <span class="club-badge {{ $availability->status === 'available' ? 'success' : ($availability->status === 'unavailable' ? 'danger' : 'muted') }}">{{ $availability->status_label }}</span>
                                        @if($availability->responded_at)
                                            <span>{{ $availability->responded_at->format('d M Y') }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</main>
@endsection

