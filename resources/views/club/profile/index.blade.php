@extends('club.layouts.master')

@section('title', $title)

@section('content')
<main class="club-page">
    <div class="club-welcome mb-4">
        <div class="position-relative" style="z-index: 1;">
            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3">
                <div class="min-w-0">
                    <p class="club-welcome-label">My Club</p>
                    <h2 class="text-truncate">{{ $club->name }}</h2>
                    <p class="club-welcome-meta">
                        @if($club->city || $club->country)
                            <i class="fas fa-map-marker-alt me-1"></i>
                            {{ collect([$club->city, $club->country])->filter()->join(', ') }}
                        @endif
                    </p>
                    @if($club->is_verified)
                        <span class="club-verified-badge"><i class="fas fa-check-circle"></i> Verified club</span>
                    @endif
                </div>
                @if($club->logo_url)
                    <img src="{{ $club->logo_url }}" alt="{{ $club->name }}" class="club-welcome-logo flex-shrink-0">
                @else
                    <div class="club-avatar-fallback mx-auto mb-3" style="width:96px;height:96px;font-size:3rem;">{{ strtoupper(substr($club->name, 0, 1)) }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-4 g-lg-5">
        <div class="col-lg-8">
            <div class="club-card">
                <div class="club-card-header">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2 text-primary"></i>Club Details</h6>
                    <a href="{{ route('club.profile.edit') }}" class="btn btn-sm btn-club-primary">
                        <i class="fas fa-edit me-1"></i> Edit Club
                    </a>
                </div>
                <div class="club-card-body padded">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="text-muted small d-block">Location</label>
                            <span class="fw-medium">{{ collect([$club->city, $club->country])->filter()->join(', ') ?: '—' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small d-block">Founded</label>
                            <span class="fw-medium">{{ $club->founded_year ?? '—' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small d-block">Website</label>
                            @if($club->website)
                                <a href="{{ $club->website }}" target="_blank" rel="noopener" class="fw-medium text-decoration-none">{{ $club->website }}</a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small d-block">Visibility</label>
                            <span class="club-badge {{ $club->is_public ? 'success' : 'muted' }}">{{ $club->is_public ? 'Public' : 'Private' }}</span>
                        </div>
                        <div class="col-12">
                            <label class="text-muted small d-block">Description</label>
                            <p class="mb-0">{{ $club->description ?: 'No description added yet.' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 g-md-4 mt-4">
                <div class="col-sm-6">
                    <div class="club-stat-card h-100">
                        <div class="club-stat-icon green"><i class="fas fa-layer-group"></i></div>
                        <div>
                            <p class="club-stat-label">Squad</p>
                            <p class="club-stat-value">{{ $club->teams_count }}</p>
                            <p class="club-stat-hint">Active squads</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="club-stat-card h-100">
                        <div class="club-stat-icon blue"><i class="fas fa-user-friends"></i></div>
                        <div>
                            <p class="club-stat-label">Players</p>
                            <p class="club-stat-value">{{ $club->members_count }}</p>
                            <p class="club-stat-hint">Club members</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="club-stat-card h-100">
                        <div class="club-stat-icon amber"><i class="fas fa-calendar-alt"></i></div>
                        <div>
                            <p class="club-stat-label">Fixtures</p>
                            <p class="club-stat-value">{{ $club->fixtures_count }}</p>
                            <p class="club-stat-hint">Total matches</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="club-card mb-4">
                <div class="club-card-header">
                    <h6 class="mb-0"><i class="fas fa-image me-2 text-success"></i>Club Branding</h6>
                </div>
                <div class="club-card-body padded text-center">
                    @if($club->cover_url)
                        <img src="{{ $club->cover_url }}" alt="Cover" class="rounded mb-3 w-100" style="max-height:120px;object-fit:cover;">
                    @endif
                    @if($club->logo_url)
                        <img src="{{ $club->logo_url }}" alt="{{ $club->name }}" class="rounded mb-3" width="100" height="100" style="object-fit:cover; border: 3px solid #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    @else
                        <div class="club-avatar-fallback mx-auto mb-3" style="width:100px;height:100px;font-size:2.5rem;">{{ strtoupper(substr($club->name, 0, 1)) }}</div>
                    @endif
                    <p class="fw-medium mb-1">{{ $club->name }}</p>
                    @if($club->short_name)
                        <p class="text-muted small mb-2">{{ $club->short_name }}</p>
                    @endif
                    @if($club->is_verified)
                        <span class="club-verified-badge"><i class="fas fa-check-circle"></i> Verified</span>
                    @endif
                </div>
            </div>

            <div class="club-card">
                <div class="club-card-header">
                    <h6 class="mb-0"><i class="fas fa-bolt me-2" style="color: var(--club-gold);"></i>Quick Stats</h6>
                </div>
                <div class="club-card-body padded d-grid gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="club-stat-icon green" style="width: 36px; height: 36px; font-size: 14px;"><i class="fas fa-layer-group"></i></span>
                        <span class="flex-grow-1">
                            <small class="text-muted"><a href="{{ route('club.squads.index') }}">Squad</a></small>
                            <div class="fw-semibold">{{ $club->teams_count }}</div>
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="club-stat-icon blue" style="width: 36px; height: 36px; font-size: 14px;"><i class="fas fa-user-friends"></i></span>
                        <span class="flex-grow-1">
                            <small class="text-muted"> <a href="{{ route('club.players.index') }}">Players</a>  </small>
                            <div class="fw-semibold">{{ $club->members_count }}</div>
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="club-stat-icon amber" style="width: 36px; height: 36px; font-size: 14px;"><i class="fas fa-calendar-alt"></i></span>
                        <span class="flex-grow-1">
                            <small class="text-muted"> <a href="{{ route('club.fixtures.index') }}">Fixtures</a>  </small>
                            <div class="fw-semibold">{{ $club->fixtures_count }}</div>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
