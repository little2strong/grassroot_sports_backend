@extends('admin.layouts.master')
@section('clubs', 'active')
@section('title', $title ?? 'Club Details')

@section('content')
<main class="container-fluid p-3 p-lg-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <a href="{{ route('admin.clubs.index') }}" class="text-decoration-none small">&larr; Back to clubs</a>
            <h2 class="page-content-title fw-medium fs-5 mb-1 mt-2">{{ $club->name }}</h2>
            <p class="text-muted mb-0 small">{{ $club->short_name ?? $club->slug }}</p>
        </div>
        <div class="d-flex gap-2">
            <form method="POST" action="{{ route('admin.clubs.toggle-verified', $club) }}">
                @csrf
                <button type="submit" class="btn btn-sm {{ $club->is_verified ? 'btn-warning' : 'btn-success' }}">
                    {{ $club->is_verified ? 'Remove verification' : 'Mark as verified' }}
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body py-3"><p class="text-muted small mb-1">Squads</p><h4 class="mb-0">{{ $stats['teams'] }}</h4></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body py-3"><p class="text-muted small mb-1">Members</p><h4 class="mb-0">{{ $stats['members'] }}</h4></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body py-3"><p class="text-muted small mb-1">Fixtures</p><h4 class="mb-0">{{ $stats['fixtures'] }}</h4></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body py-3"><p class="text-muted small mb-1">Live / Upcoming</p><h4 class="mb-0">{{ $stats['fixtures_live'] }} / {{ $stats['fixtures_upcoming'] }}</h4></div></div></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-white"><h6 class="mb-0">Club Profile</h6></div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        @if($club->logo)
                            <img src="{{ asset($club->logo) }}" alt="" class="rounded" style="width:80px;height:80px;object-fit:cover;">
                        @else
                            <div class="rounded bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center fw-bold" style="width:80px;height:80px;font-size:1.5rem;">
                                {{ strtoupper(substr($club->name, 0, 1)) }}
                            </div>
                        @endif
                    </div>
                    <table class="table table-sm mb-0">
                        <tr><th class="text-muted">Owner</th><td>{{ $club->owner?->name ?? '—' }}</td></tr>
                        <tr><th class="text-muted">Email</th><td>{{ $club->owner?->email ?? '—' }}</td></tr>
                        <tr><th class="text-muted">Location</th><td>{{ collect([$club->city, $club->country])->filter()->join(', ') ?: '—' }}</td></tr>
                        <tr><th class="text-muted">Website</th><td>{{ $club->website ?: '—' }}</td></tr>
                        <tr><th class="text-muted">Founded</th><td>{{ $club->founded_year ?: '—' }}</td></tr>
                        <tr><th class="text-muted">Verified</th><td><span class="badge {{ $club->is_verified ? 'bg-success' : 'bg-secondary' }}">{{ $club->is_verified ? 'Yes' : 'No' }}</span></td></tr>
                        <tr><th class="text-muted">Public</th><td><span class="badge {{ $club->is_public ? 'bg-success' : 'bg-secondary' }}">{{ $club->is_public ? 'Yes' : 'No' }}</span></td></tr>
                        <tr><th class="text-muted">Joined</th><td>{{ $club->created_at->format('d M Y') }}</td></tr>
                    </table>
                    @if($club->description)
                        <hr>
                        <p class="small text-muted mb-0">{{ $club->description }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-white"><h6 class="mb-0">Squads ({{ $club->teams->count() }})</h6></div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>Name</th><th>Short</th><th class="text-center">Players</th><th class="text-center">Status</th></tr>
                        </thead>
                        <tbody>
                            @forelse($club->teams as $team)
                                <tr>
                                    <td class="fw-semibold">{{ $team->name }}</td>
                                    <td>{{ $team->short_name ?? '—' }}</td>
                                    <td class="text-center">{{ $team->members_count }}</td>
                                    <td class="text-center"><span class="badge {{ $team->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $team->is_active ? 'Active' : 'Inactive' }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">No squads yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-white"><h6 class="mb-0">Members ({{ $club->members->count() }})</h6></div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>Player</th><th>Role</th><th>Status</th><th>Joined</th><th></th></tr>
                        </thead>
                        <tbody>
                            @forelse($club->members as $member)
                                <tr>
                                    <td>
                                        <div class="fw-semibold small">{{ $member->user?->name ?? '—' }}</div>
                                        <div class="text-muted" style="font-size:.75rem;">{{ $member->user?->email }}</div>
                                    </td>
                                    <td>{{ ucfirst($member->role) }}</td>
                                    <td><span class="badge {{ $member->status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($member->status) }}</span></td>
                                    <td class="small text-muted">{{ $member->joined_at?->format('d M Y') ?? '—' }}</td>
                                    <td>
                                        @if($member->user && $member->user->user_type === 'player')
                                            <a href="{{ route('admin.players.show', $member->user) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">No members yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-white"><h6 class="mb-0">Recent Fixtures</h6></div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>Match</th><th>Date</th><th>Type</th><th>Status</th><th>Scorer</th></tr>
                        </thead>
                        <tbody>
                            @forelse($fixtures as $fixture)
                                <tr>
                                    <td class="small fw-semibold">{{ $fixture->home_display_name }} vs {{ $fixture->away_display_name }}</td>
                                    <td class="small">{{ $fixture->scheduled_date?->format('d M Y') }} {{ $fixture->scheduled_time?->format('H:i') }}</td>
                                    <td>{{ $fixture->match_type_label }}</td>
                                    <td><span class="badge bg-{{ $fixture->status === 'live' ? 'danger' : ($fixture->status === 'completed' ? 'success' : 'secondary') }}">{{ $fixture->status_label }}</span></td>
                                    <td class="small">{{ $fixture->scorer?->name ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">No fixtures yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
