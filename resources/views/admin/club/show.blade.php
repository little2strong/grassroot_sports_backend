@extends('admin.layouts.master')
@section('clubs', 'active')
@section('title', $title ?? 'Club Details')

@push('style')
<style>
    /* Custom Styling Overrides for Modern Aesthetic */
    .back-btn {
        color: var(--text-gray);
        font-weight: 500;
        transition: color 0.2s ease, transform 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .back-btn:hover {
        color: var(--primary-blue);
        transform: translateX(-2px);
    }
    
    .stat-card-modern {
        border-radius: 16px !important;
        background: #ffffff;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: 1px solid rgba(0, 0, 0, 0.04) !important;
    }
    .stat-card-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 18px rgba(0, 106, 108, 0.05);
    }
    .icon-shape {
        transition: transform 0.3s ease;
    }
    .stat-card-modern:hover .icon-shape {
        transform: scale(1.08);
    }
    .bg-primary-soft { background-color: rgba(0, 106, 108, 0.1); }
    .text-primary-custom { color: #006a6c; }
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
    .bg-danger-soft { background-color: rgba(220, 53, 69, 0.1); }
    .bg-info-soft { background-color: rgba(13, 202, 240, 0.1); }
    .bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); }
    
    /* Modern Dashboard Card */
    .dashboard-card {
        border-radius: 16px !important;
        border: 1px solid rgba(0, 0, 0, 0.05) !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
    }
    .dashboard-card .card-header {
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        padding: 16px 20px;
        font-weight: 600;
        background-color: #ffffff !important;
    }
    .dashboard-card .card-body {
        padding: 20px;
    }
    
    /* Table Styling */
    .table-modern th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
        color: #8b95a5;
        border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        padding: 12px 16px;
    }
    .table-modern td {
        padding: 12px 16px;
        vertical-align: middle;
        border-bottom: 1px solid rgba(0, 0, 0, 0.04);
    }
    .table-modern tbody tr {
        transition: background-color 0.2s ease;
    }
    .table-modern tbody tr:hover {
        background-color: #f9fbfd;
    }
    
    /* Soft Badges */
    .badge-soft-success { background-color: rgba(25, 135, 84, 0.1); color: #198754; }
    .badge-soft-danger { background-color: rgba(220, 53, 69, 0.1); color: #dc3545; }
    .badge-soft-warning { background-color: rgba(255, 193, 7, 0.1); color: #b58105; }
    .badge-soft-info { background-color: rgba(13, 202, 240, 0.15); color: #0b9cb8; }
    .badge-soft-secondary { background-color: rgba(108, 117, 125, 0.1); color: #6c757d; }
    .badge-soft-primary { background-color: rgba(0, 106, 108, 0.1); color: #006a6c; }
    
    .badge-pill-custom {
        padding: 5px 12px;
        font-size: 11px;
        font-weight: 600;
        border-radius: 30px;
        display: inline-block;
    }
    
    .hover-primary:hover {
        color: var(--primary-blue) !important;
    }
</style>
@endpush

@section('content')
<main class="container-fluid p-3 p-lg-4">
    {{-- Top Navigation Header --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <a href="{{ route('admin.clubs.index') }}" class="back-btn text-decoration-none small">
                <i class="fas fa-arrow-left"></i> Back to clubs
            </a>
            <div class="d-flex align-items-center gap-2 mt-2">
                <h2 class="page-content-title fw-bold text-dark fs-4 mb-0">{{ $club->name }}</h2>
                <span class="badge-pill-custom {{ $club->is_verified ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                    {{ $club->is_verified ? 'Verified' : 'Unverified' }}
                </span>
            </div>
            <p class="text-muted mb-0 small mt-1"><i class="fas fa-link me-1"></i>{{ $club->short_name ?? $club->slug }}</p>
        </div>
        <div class="d-flex gap-2">
            <form method="POST" action="{{ route('admin.clubs.toggle-verified', $club) }}">
                @csrf
                <button type="submit" class="btn btn-sm {{ $club->is_verified ? 'btn-outline-warning' : 'btn-primary-custom' }} py-2 px-3">
                    {{ $club->is_verified ? 'Remove Verification' : 'Mark as Verified' }}
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="background-color: rgba(25, 135, 84, 0.1); color: #198754; border-left: 4px solid #198754 !important;">
            <i class="fas fa-circle-check me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Stats Cards Rows --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="card stat-card-modern p-3">
                <div class="card-body p-0 d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted mb-1 small fw-medium">Squads</p>
                        <h4 class="mb-0 fw-bold text-dark">{{ $stats['teams'] }}</h4>
                    </div>
                    <div class="icon-shape bg-primary-soft text-primary-custom rounded-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                        <i class="fas fa-network-wired"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card stat-card-modern p-3">
                <div class="card-body p-0 d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted mb-1 small fw-medium">Members</p>
                        <h4 class="mb-0 fw-bold text-dark">{{ $stats['members'] }}</h4>
                    </div>
                    <div class="icon-shape bg-info-soft text-info rounded-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card stat-card-modern p-3">
                <div class="card-body p-0 d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted mb-1 small fw-medium">Fixtures</p>
                        <h4 class="mb-0 fw-bold text-dark">{{ $stats['fixtures'] }}</h4>
                    </div>
                    <div class="icon-shape bg-success-soft text-success rounded-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card stat-card-modern p-3">
                <div class="card-body p-0 d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted mb-1 small fw-medium">Live / Upcoming</p>
                        <h4 class="mb-0 fw-bold text-dark">{{ $stats['fixtures_live'] }} / {{ $stats['fixtures_upcoming'] }}</h4>
                    </div>
                    <div class="icon-shape bg-danger-soft text-danger rounded-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                        <i class="fas fa-satellite-dish"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Details Layout --}}
    <div class="row g-4">
        {{-- Profile Panel --}}
        <div class="col-lg-4">
            <div class="card dashboard-card h-100">
                <div class="card-header bg-white"><h6 class="mb-0 fw-semibold text-dark"><i class="fas fa-id-card me-2 text-primary-custom"></i>Club Profile</h6></div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        @if($club->logo)
                            <img src="{{ asset($club->logo) }}" alt="" class="rounded shadow-sm" style="width:96px;height:96px;object-fit:cover;border: 3px solid #f8f9fa;">
                        @else
                            @php
                                $initial = strtoupper(substr($club->name, 0, 1));
                            @endphp
                            <div class="rounded bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center fw-bold shadow-sm" style="width:96px;height:96px;font-size:2.5rem;background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue)); color:#fff !important;">
                                {{ $initial }}
                            </div>
                        @endif
                    </div>
                    <table class="table table-borderless table-sm mb-0 align-middle">
                        <tr style="border-bottom: 1px solid rgba(0,0,0,0.04);"><th class="text-muted fw-medium py-2">Owner</th><td class="text-dark fw-semibold py-2 text-end">{{ $club->owner?->name ?? '—' }}</td></tr>
                        <tr style="border-bottom: 1px solid rgba(0,0,0,0.04);"><th class="text-muted fw-medium py-2">Email</th><td class="text-dark py-2 text-end" style="font-size: 0.85rem;">{{ $club->owner?->email ?? '—' }}</td></tr>
                        <tr style="border-bottom: 1px solid rgba(0,0,0,0.04);"><th class="text-muted fw-medium py-2">Location</th><td class="text-dark py-2 text-end">{{ collect([$club->city, $club->country])->filter()->join(', ') ?: '—' }}</td></tr>
                        <tr style="border-bottom: 1px solid rgba(0,0,0,0.04);"><th class="text-muted fw-medium py-2">Website</th><td class="py-2 text-end">
                            @if($club->website)
                                <a href="{{ $club->website }}" target="_blank" class="text-decoration-none hover-primary">{{ $club->website }}</a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td></tr>
                        <tr style="border-bottom: 1px solid rgba(0,0,0,0.04);"><th class="text-muted fw-medium py-2">Founded</th><td class="text-dark py-2 text-end">{{ $club->founded_year ?: '—' }}</td></tr>
                        <tr style="border-bottom: 1px solid rgba(0,0,0,0.04);"><th class="text-muted fw-medium py-2">Visibility</th><td class="py-2 text-end">
                            <span class="badge-pill-custom {{ $club->is_public ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                                {{ $club->is_public ? 'Public' : 'Private' }}
                            </span>
                        </td></tr>
                        <tr><th class="text-muted fw-medium py-2">Joined</th><td class="text-muted py-2 text-end small"><i class="far fa-clock me-1"></i>{{ $club->created_at->format('d M Y') }}</td></tr>
                    </table>
                    @if($club->description)
                        <hr class="my-3" style="border-color: rgba(0,0,0,0.08);">
                        <p class="small text-muted mb-0" style="line-height: 1.5;">{{ $club->description }}</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Tables Column --}}
        <div class="col-lg-8">
            {{-- Squads List --}}
            <div class="card dashboard-card mb-4">
                <div class="card-header bg-white"><h6 class="mb-0 fw-semibold text-dark"><i class="fas fa-network-wired me-2 text-primary-custom"></i>Squads ({{ $club->teams->count() }})</h6></div>
                <div class="table-responsive">
                    <table class="table table-modern mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Short Code</th>
                                <th class="text-center">Players Count</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($club->teams as $team)
                                <tr>
                                    <td class="fw-bold text-dark">{{ $team->name }}</td>
                                    <td><span class="badge-pill-custom badge-soft-secondary">{{ $team->short_name ?? '—' }}</span></td>
                                    <td class="text-center fw-semibold text-dark">{{ $team->members_count }}</td>
                                    <td class="text-center">
                                        <span class="badge-pill-custom {{ $team->is_active ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                                            {{ $team->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4"><i class="fas fa-network-wired fs-4 text-muted mb-2"></i><br>No squads found for this club.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Members List --}}
            <div class="card dashboard-card mb-4">
                <div class="card-header bg-white"><h6 class="mb-0 fw-semibold text-dark"><i class="fas fa-users me-2 text-primary-custom"></i>Members ({{ $club->members->count() }})</h6></div>
                <div class="table-responsive">
                    <table class="table table-modern mb-0">
                        <thead>
                            <tr>
                                <th>Player</th>
                                <th>Role</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Joined Date</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($club->members as $member)
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $member->user?->name ?? '—' }}</div>
                                        <div class="text-muted" style="font-size:.75rem;">{{ $member->user?->email }}</div>
                                    </td>
                                    <td><span class="badge-pill-custom badge-soft-primary">{{ ucfirst($member->role) }}</span></td>
                                    <td class="text-center">
                                        <span class="badge-pill-custom {{ $member->status === 'active' ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                                            {{ ucfirst($member->status) }}
                                        </span>
                                    </td>
                                    <td class="text-center small text-muted"><i class="far fa-clock me-1"></i>{{ $member->joined_at?->format('d M Y') ?? '—' }}</td>
                                    <td class="text-center">
                                        @if($member->user && $member->user->user_type === 'player')
                                            <a href="{{ route('admin.players.show', $member->user) }}" class="btn btn-xs btn-outline-primary border-0 rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;" title="View Player Profile">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4"><i class="fas fa-users-slash fs-4 text-muted mb-2"></i><br>No members registered in this club.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Recent Fixtures --}}
            <div class="card dashboard-card">
                <div class="card-header bg-white"><h6 class="mb-0 fw-semibold text-dark"><i class="fas fa-calendar-check me-2 text-primary-custom"></i>Recent Fixtures</h6></div>
                <div class="table-responsive">
                    <table class="table table-modern mb-0">
                        <thead>
                            <tr>
                                <th>Match</th>
                                <th>Schedule Date</th>
                                <th>Type</th>
                                <th class="text-center">Status</th>
                                <th>Scorer</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($fixtures as $fixture)
                                <tr>
                                    <td class="fw-bold text-dark small">{{ $fixture->home_display_name }} <span class="text-muted fw-normal">vs</span> {{ $fixture->away_display_name }}</td>
                                    <td class="small text-muted">
                                        <i class="far fa-calendar me-1"></i>{{ $fixture->scheduled_date?->format('d M Y') }}
                                        @if($fixture->scheduled_time) · <i class="far fa-clock me-1"></i>{{ $fixture->scheduled_time?->format('H:i') }} @endif
                                    </td>
                                    <td><span class="badge-pill-custom badge-soft-primary">{{ $fixture->match_type_label }}</span></td>
                                    <td class="text-center">
                                        @php
                                            $fixtureStatus = 'badge-soft-secondary';
                                            if ($fixture->status === 'live') {
                                                $fixtureStatus = 'badge-soft-danger animate-pulse';
                                            } elseif ($fixture->status === 'completed') {
                                                $fixtureStatus = 'badge-soft-success';
                                            }
                                        @endphp
                                        <span class="badge-pill-custom {{ $fixtureStatus }}">
                                            {{ $fixture->status_label }}
                                        </span>
                                    </td>
                                    <td class="small text-muted">{{ $fixture->scorer?->name ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4"><i class="fas fa-calendar-xmark fs-4 text-muted mb-2"></i><br>No fixtures found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
