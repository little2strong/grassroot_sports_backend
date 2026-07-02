@extends('admin.layouts.master')
@section('clubs', 'active')
@section('title', $title ?? 'Manage Clubs')

@section('content')
<main class="container-fluid p-3 p-lg-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="page-content-title fw-medium fs-5 mb-1">Club Management</h2>
            <p class="text-muted mb-0 small">View and manage all registered cricket clubs.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card"><div class="card-body py-3">
                <p class="text-muted small mb-1">Total Clubs</p>
                <h4 class="mb-0 fw-bold">{{ $summary['total'] }}</h4>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card"><div class="card-body py-3">
                <p class="text-muted small mb-1">Verified</p>
                <h4 class="mb-0 fw-bold text-success">{{ $summary['verified'] }}</h4>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card"><div class="card-body py-3">
                <p class="text-muted small mb-1">Public Profiles</p>
                <h4 class="mb-0 fw-bold">{{ $summary['public'] }}</h4>
            </div></div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Club, city, owner email...">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Verified</label>
                    <select name="is_verified" class="form-select">
                        <option value="">All</option>
                        <option value="1" @selected(request('is_verified') === '1')>Verified</option>
                        <option value="0" @selected(request('is_verified') === '0')>Unverified</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Visibility</label>
                    <select name="is_public" class="form-select">
                        <option value="">All</option>
                        <option value="1" @selected(request('is_public') === '1')>Public</option>
                        <option value="0" @selected(request('is_public') === '0')>Private</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Country</label>
                    <select name="country" class="form-select">
                        <option value="">All</option>
                        @foreach($countries as $country)
                            <option value="{{ $country }}" @selected(request('country') === $country)>{{ $country }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                    <a href="{{ route('admin.clubs.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Club List</h5>
            <span class="text-muted small">{{ $clubs->total() }} clubs</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Club</th>
                        <th>Owner</th>
                        <th>Location</th>
                        <th class="text-center">Teams</th>
                        <th class="text-center">Members</th>
                        <th class="text-center">Fixtures</th>
                        <th class="text-center">Verified</th>
                        <th class="text-center">Joined</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clubs as $club)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($club->logo)
                                        <img src="{{ asset($club->logo) }}" alt="" class="rounded" style="width:40px;height:40px;object-fit:cover;">
                                    @else
                                        <div class="rounded bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold" style="width:40px;height:40px;">
                                            {{ strtoupper(substr($club->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <div class="fw-semibold">
                                            <a href="{{ route('admin.clubs.show', $club) }}" class="text-decoration-none text-dark">{{ $club->name }}</a>
                                        </div>
                                        @if($club->short_name)
                                            <div class="text-muted small">{{ $club->short_name }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($club->owner)
                                    <div class="small fw-medium">{{ $club->owner->name }}</div>
                                    <div class="text-muted" style="font-size:.75rem;">{{ $club->owner->email }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($club->city || $club->country)
                                    {{ collect([$club->city, $club->country])->filter()->join(', ') }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $club->teams_count }}</td>
                            <td class="text-center">{{ $club->members_count }}</td>
                            <td class="text-center">{{ $club->fixtures_count }}</td>
                            <td class="text-center">
                                <span class="badge {{ $club->is_verified ? 'bg-success' : 'bg-light text-dark' }}">
                                    {{ $club->is_verified ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td class="text-center small text-muted">{{ $club->created_at->format('d M Y') }}</td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('admin.clubs.show', $club) }}" class="btn btn-sm btn-outline-primary" title="View club">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.clubs.toggle-verified', $club) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $club->is_verified ? 'btn-outline-warning' : 'btn-outline-success' }}" title="Toggle verification">
                                            <i class="fas {{ $club->is_verified ? 'fa-times-circle' : 'fa-check-circle' }}"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">No clubs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($clubs->hasPages())
            <div class="card-footer bg-white">{{ $clubs->links() }}</div>
        @endif
    </div>
</main>
@endsection
