@extends('admin.layouts.master')
@section('clubs', 'active')
@section('title', $title ?? 'Manage Clubs')

@push('style')
<style>
    /* Custom Styling Overrides for Modern Aesthetic */
    .stat-card-modern {
        border-radius: 16px !important;
        background: #ffffff;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease;
        border: 1px solid rgba(0, 0, 0, 0.04) !important;
    }
    .stat-card-modern:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0, 106, 108, 0.08);
    }
    .icon-shape {
        transition: transform 0.3s ease;
    }
    .stat-card-modern:hover .icon-shape {
        transform: scale(1.1);
    }
    .bg-primary-soft { background-color: rgba(0, 106, 108, 0.1); }
    .text-primary-custom { color: #006a6c; }
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
    .bg-info-soft { background-color: rgba(13, 202, 240, 0.1); }
    .text-info { color: #0dcaf0 !important; }
    .text-success { color: #198754 !important; }
    
    /* Modern filter card */
    .filter-card {
        border-radius: 16px !important;
        border: 1px solid rgba(0, 0, 0, 0.05) !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
    }
    .form-control, .form-select {
        border-radius: 8px;
        padding: 9px 14px;
        border-color: #dee2e6;
        font-size: 0.9rem;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 0.25rem rgba(0, 106, 108, 0.15);
    }
    
    /* Modern table & lists */
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
        padding: 14px 16px;
        vertical-align: middle;
        border-bottom: 1px solid rgba(0, 0, 0, 0.04);
    }
    .table-modern tbody tr {
        transition: background-color 0.2s ease;
    }
    .table-modern tbody tr:hover {
        background-color: #f9fbfd;
    }
    
    /* Club logo & initials */
    .club-logo-slot {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        object-fit: cover;
        box-shadow: 0 3px 6px rgba(0, 0, 0, 0.05);
    }
    .avatar-initials {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        color: #ffffff;
        font-weight: 600;
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue));
        box-shadow: 0 3px 6px rgba(0, 106, 108, 0.15);
    }
    
    /* Soft Badges */
    .badge-soft-success { background-color: rgba(25, 135, 84, 0.1); color: #198754; }
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
    
    /* Custom pagination styling to match brand */
    .pagination {
        margin-bottom: 0;
        gap: 5px;
    }
    .page-item .page-link {
        border-radius: 8px !important;
        border: 1px solid #dee2e6;
        color: var(--text-gray);
        font-weight: 500;
        padding: 8px 14px;
        transition: all 0.2s ease;
    }
    .page-item.active .page-link {
        background-color: var(--primary-blue) !important;
        border-color: var(--primary-blue) !important;
        color: #ffffff !important;
    }
    .page-item:not(.active) .page-link:hover {
        background-color: rgba(0, 106, 108, 0.05);
        color: var(--primary-blue);
        border-color: rgba(0, 106, 108, 0.15);
    }
    .page-item.disabled .page-link {
        background-color: #f8f9fa;
        color: #adb5bd;
    }
</style>

@endpush

@section('content')
<main class="container-fluid p-3 p-lg-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="page-content-title fw-bold text-dark fs-4 mb-1">Club Management</h2>
            <p class="text-muted mb-0 small">View, verify and manage registered cricket clubs.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="background-color: rgba(25, 135, 84, 0.1); color: #198754; border-left: 4px solid #198754 !important;">
            <i class="fas fa-circle-check me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $verifyPercent = $summary['total'] > 0 ? round(($summary['verified'] / $summary['total']) * 100) : 0;
        $publicPercent = $summary['total'] > 0 ? round(($summary['public'] / $summary['total']) * 100) : 0;
    @endphp

    {{-- Stats Cards Row --}}
    <div class="row g-3 g-lg-4 mb-4">
        <div class="col-md-4">
            <div class="card stat-card-modern p-3">
                <div class="card-body p-0 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-medium">Total Clubs</p>
                            <h3 class="fw-bold text-dark mb-0">{{ $summary['total'] }}</h3>
                        </div>
                        <div class="icon-shape bg-primary-soft text-primary-custom rounded-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fas fa-shield-halved fs-5"></i>
                        </div>
                    </div>
                    <div class="small text-muted">
                        <span class="fw-semibold">{{ $summary['total'] }}</span> registered clubs
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card stat-card-modern p-3">
                <div class="card-body p-0 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-medium">Verified Clubs</p>
                            <h3 class="fw-bold text-success mb-0">{{ $summary['verified'] }}</h3>
                        </div>
                        <div class="icon-shape bg-success-soft text-success rounded-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fas fa-circle-check fs-5"></i>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-1 small text-muted">
                            <span>Verification Rate</span>
                            <span class="fw-semibold text-success">{{ $verifyPercent }}%</span>
                        </div>
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $verifyPercent }}%" aria-valuenow="{{ $verifyPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card stat-card-modern p-3">
                <div class="card-body p-0 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-medium">Public Profiles</p>
                            <h3 class="fw-bold text-info mb-0">{{ $summary['public'] }}</h3>
                        </div>
                        <div class="icon-shape bg-info-soft text-info rounded-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fas fa-globe fs-5"></i>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-1 small text-muted">
                            <span>Visibility Rate</span>
                            <span class="fw-semibold text-info">{{ $publicPercent }}%</span>
                        </div>
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar bg-info" role="progressbar" style="width: {{ $publicPercent }}%" aria-valuenow="{{ $publicPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="card filter-card mb-4">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 g-lg-3 align-items-end">
                <div class="col-md-4 col-lg-4">
                    <label class="form-label small fw-semibold text-muted mb-1">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Club name, city, owner email...">
                </div>
                <div class="col-sm-6 col-md-2 col-lg-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Verified Status</label>
                    <select name="is_verified" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="1" @selected(request('is_verified') === '1')>Verified</option>
                        <option value="0" @selected(request('is_verified') === '0')>Unverified</option>
                    </select>
                </div>
                <div class="col-sm-6 col-md-2 col-lg-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Visibility</label>
                    <select name="is_public" class="form-select">
                        <option value="">All Visibility</option>
                        <option value="1" @selected(request('is_public') === '1')>Public</option>
                        <option value="0" @selected(request('is_public') === '0')>Private</option>
                    </select>
                </div>
                <div class="col-sm-6 col-md-2 col-lg-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Country</label>
                    <select name="country" class="form-select">
                        <option value="">All Countries</option>
                        @foreach($countries as $country)
                            <option value="{{ $country }}" @selected(request('country') === $country)>{{ $country }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-2 col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary-custom w-100 py-2"><i class="fas fa-filter me-1"></i>Filter</button>
                    <a href="{{ route('admin.clubs.index') }}" class="btn btn-outline-secondary w-50 py-2 d-flex align-items-center justify-content-center"><i class="fas fa-rotate-left"></i></a>
                </div>
            </form>
        </div>
    </div>

    {{-- Club List Card --}}
    <div class="card dashboard-card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-semibold text-dark"><i class="fas fa-list me-2 text-primary-custom"></i>Club List</h5>
            <span class="badge-pill-custom badge-soft-primary">{{ $clubs->total() }} clubs total</span>
        </div>
        <div class="table-responsive">
            <table class="table table-modern align-middle mb-0">
                <thead>
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
                                <div class="d-flex align-items-center gap-3">
                                    @if($club->logo)
                                        <img src="{{ asset($club->logo) }}" alt="" class="club-logo-slot">
                                    @else
                                        @php
                                            $initial = strtoupper(substr($club->name, 0, 1));
                                        @endphp
                                        <div class="avatar-initials">{{ $initial }}</div>
                                    @endif
                                    <div>
                                        <div class="fw-bold">
                                            <a href="{{ route('admin.clubs.show', $club) }}" class="text-decoration-none text-dark hover-primary">{{ $club->name }}</a>
                                        </div>
                                        @if($club->short_name)
                                            <div class="text-muted small" style="font-size: 0.75rem;">{{ $club->short_name }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($club->owner)
                                    <div class="small fw-semibold text-dark">{{ $club->owner->name }}</div>
                                    <div class="text-muted" style="font-size:.75rem;">{{ $club->owner->email }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($club->city || $club->country)
                                    <span class="small text-dark"><i class="fas fa-location-dot me-1 text-muted"></i>{{ collect([$club->city, $club->country])->filter()->join(', ') }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center fw-semibold text-dark">{{ $club->teams_count }}</td>
                            <td class="text-center fw-semibold text-dark">{{ $club->members_count }}</td>
                            <td class="text-center fw-semibold text-dark">{{ $club->fixtures_count }}</td>
                            <td class="text-center">
                                <span class="badge-pill-custom {{ $club->is_verified ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                                    {{ $club->is_verified ? 'Verified' : 'Unverified' }}
                                </span>
                            </td>
                            <td class="text-center small text-muted"><i class="far fa-clock me-1"></i>{{ $club->created_at->format('d M Y') }}</td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('admin.clubs.show', $club) }}" class="btn btn-sm btn-outline-primary border-0 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="View details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.clubs.toggle-verified', $club) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $club->is_verified ? 'btn-outline-warning' : 'btn-outline-success' }} border-0 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="Toggle verification">
                                            <i class="fas {{ $club->is_verified ? 'fa-times-circle' : 'fa-check-circle' }}"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5"><i class="fas fa-shield-halved fs-3 text-muted mb-2"></i><br>No clubs registered yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($clubs->hasPages())
            <div class="card-footer bg-white border-0 py-3">{{ $clubs->links() }}</div>
        @endif
    </div>
</main>
@endsection
