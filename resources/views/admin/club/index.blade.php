@extends('admin.layouts.master')
@section('clubs', 'active')
@section('title'){{ $title ?? '' }} @endsection

@push('style')
    <style>
        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 40px;
            /* space for eye icon */
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            font-size: 16px;
        }

        .toggle-password:hover {
            color: #0d6efd;
        }
    </style>
@endpush

@section('content')
    <!-- Main Content Area -->
    <main class="container-fluid p-3 p-lg-4">
        <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-center">
            <div class="mb-0">
                <h2 class="page-content-title fw-medium fs-5">Club Management</h2>
                <p class="page-subtitle">Manage Clubs</p>
            </div>
           
            {{-- <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-plus me-2"></i>Add Admin User
        </button> --}}
        </div>

        
        

        <!-- Main Card -->
        <div class="main-card mb-4">
            <div
                class="card-header d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                <h5 class="card-title mb-0">Club List</h5>
            </div>
            <div class="p-3">
                <table id="adminUsersTable" class="table data-table table-hover admin-user-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Club</th>
                            <th>Location</th>
                            <th class="text-center">Teams</th>
                            <th class="text-center">Members</th>
                            <th class="text-center">Verified</th>
                            <th class="text-center">Public</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($clubs as $club)
                            <tr>
                                {{-- Club Name + Logo --}}
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($club->logo)
                                            <img src="{{ asset('storage/' . $club->logo) }}" alt="" class="rounded"
                                                style="width:36px;height:36px;object-fit:cover;">
                                        @else
                                            <div class="rounded bg-primary bg-opacity-10 d-flex align-items-center justify-content-center text-primary"
                                                style="width:36px;height:36px;font-size:.75rem;font-weight:600;">
                                                {{ strtoupper(substr($club->name, 0, 1)) }}
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="mb-0 fw-semibold text-truncate" style="font-size:.875rem;">
                                                {{ $club->name }}</p>
                                            @if ($club->website)
                                                <p class="mb-0 text-truncate text-muted" style="font-size:.75rem;">
                                                    {{ $club->website }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Location --}}
                                <td>
                                    @if ($club->city)
                                        {{ $club->city }}{{ $club->country ? ', ' . $club->country : '' }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                {{-- Teams Count --}}
                                <td class="text-center">{{ $club->teams_count }}</td>

                                {{-- Members Count --}}
                                <td class="text-center">{{ $club->members_count }}</td>

                                {{-- Verified Badge --}}
                                <td class="text-center">
                                    @if ($club->is_verified)
                                        <span class="badge bg-success">Verified</span>
                                    @else
                                        <span class="badge bg-light text-dark">Unverified</span>
                                    @endif
                                </td>

                                {{-- Public Badge --}}
                                <td class="text-center">
                                    @if ($club->is_public)
                                        <span class="bg-success bg-opacity-10 text-success">Public</span>
                                    @else
                                        <span class="bg-secondary bg-opacity-10 text-secondary">Private</span>
                                    @endif
                                </td>

                                {{-- Status Badge --}}
                                <td class="text-center">
                                    <span class="badge bg-primary bg-opacity-10 text-primary">Active</span>
                                </td>

                                {{-- Actions --}}
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="{{ route('admin.clubs.edit', $club) }}"
                                            class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-warning" title="Toggle Status"
                                            onclick="openStatusModal({{ $club->id }}, '{{ addslashes($club->name) }}')"
                                            data-bs-toggle="modal" data-bs-target="#statusModal">
                                            <i class="fas fa-toggle-on"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" title="Delete"
                                            onclick="openDeleteModal({{ $club->id }}, '{{ addslashes($club->name) }}')"
                                            data-bs-toggle="modal" data-bs-target="#deleteModal">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </main>



@endsection

@push('script')
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#adminUsersTable').DataTable({
                responsive: true,
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    paginate: {
                        previous: "<i class='fas fa-chevron-left'></i>",
                        next: "<i class='fas fa-chevron-right'></i>"
                    }
                },
                columnDefs: [{
                    orderable: false,
                    targets: 5
                }],
                pageLength: 10,
                order: [
                    [0, 'asc']
                ]
            });
        });
    </script>
@endpush
