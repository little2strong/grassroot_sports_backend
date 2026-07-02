@extends('club.layouts.master')

@section('title', $title)

@section('content')
<main class="club-page">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="club-card">
                <div class="club-card-body padded text-center">
                    @if($club->logo_url)
                        <img src="{{ $club->logo_url }}" alt="{{ $club->name }}" class="rounded mb-3" width="100" height="100" style="object-fit:cover;">
                    @else
                        <div class="club-avatar-fallback mx-auto mb-3" style="width:100px;height:100px;font-size:2.5rem;">{{ strtoupper(substr($club->name, 0, 1)) }}</div>
                    @endif
                    <h4 class="fw-bold mb-1">{{ $club->name }}</h4>
                    @if($club->short_name)
                        <p class="text-muted small mb-2">{{ $club->short_name }}</p>
                    @endif
                    @if($club->is_verified)
                        <span class="club-badge success"><i class="fas fa-check-circle me-1"></i>Verified</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="club-card">
                <div class="club-card-header">
                    <h6 class="mb-0">Club Details</h6>
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
                                <a href="{{ $club->website }}" target="_blank" rel="noopener">{{ $club->website }}</a>
                            @else
                                <span>—</span>
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

            <div class="club-stat-grid mt-4">
                <div class="club-stat-card">
                    <div class="club-stat-icon green"><i class="fas fa-layer-group"></i></div>
                    <div>
                        <p class="club-stat-label">Teams</p>
                        <p class="club-stat-value">{{ $club->teams_count }}</p>
                    </div>
                </div>
                <div class="club-stat-card">
                    <div class="club-stat-icon blue"><i class="fas fa-user-friends"></i></div>
                    <div>
                        <p class="club-stat-label">Members</p>
                        <p class="club-stat-value">{{ $club->members_count }}</p>
                    </div>
                </div>
                <div class="club-stat-card">
                    <div class="club-stat-icon amber"><i class="fas fa-calendar-alt"></i></div>
                    <div>
                        <p class="club-stat-label">Fixtures</p>
                        <p class="club-stat-value">{{ $club->fixtures_count }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
