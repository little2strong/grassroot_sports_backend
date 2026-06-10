@extends('admin.layouts.master')
@section('players', 'active')
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
                <h2 class="page-content-title fw-medium fs-5">Players Management</h2>
                <p class="page-subtitle">Manage Players</p>
            </div>

            {{-- <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-plus me-2"></i>Add Admin User
        </button> --}}
        </div>




        <!-- Main Card -->
        <div class="main-card mb-4">
            <div
                class="card-header d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                <h5 class="card-title mb-0">Player List</h5>
            </div>
            <div class="p-3">
                <table id="adminUsersTable" class="table data-table table-hover admin-user-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Player</th>
                            <th>Role</th>
                            <th>Batting</th>
                            <th>Bowling</th>
                            <th>Matches</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($players as $player)
                            <tr>
                                {{-- Player Name + Avatar --}}
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($player->avatar)
                                            <img src="{{ asset('storage/' . $player->avatar) }}" alt=""
                                                class="rounded-circle" style="width:36px;height:36px;object-fit:cover;">
                                        @else
                                            <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center text-primary fw-bold"
                                                style="width:36px;height:36px;font-size:.75rem;font-weight:600;">
                                                {{ strtoupper(substr($player->name, 0, 1)) }}
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="mb-0 fw-semibold text-truncate" style="font-size:.875rem;">
                                                {{ $player->name }}</p>
                                            <p class="mb-0 text-truncate text-muted" style="font-size:.75rem;">
                                                {{ $player->email }}</p>
                                        </div>
                                    </div>
                                </td>

                                {{-- Role Badge --}}
                                <td>
                                    @if ($player->playerProfile)
                                        @php
                                            $roleColors = [
                                                'batsman' => 'bg-info text-white',
                                                'bowler' => 'bg-warning text-dark',
                                                'all_rounder' => 'bg-success text-white',
                                                'wicket_keeper' => 'bg-purple text-white',
                                            ];
                                        @endphp
                                        <span
                                            class="badge {{ $roleColors[$player->playerProfile->primary_role] ?? 'bg-secondary text-white' }}">
                                            {{ $player->playerProfile->role_label }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary text-white">Player</span>
                                    @endif
                                </td>

                                {{-- Batting Style --}}
                                <td>
                                    @if ($player->playerProfile?->batting_style)
                                        <span class="badge bg-light text-dark">
                                            {{ $player->playerProfile->batting_style === 'right_hand' ? 'RHB' : 'LHB' }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>


                                <td>
                                    @if ($player->playerProfile?->bowling_style)
                                        @php
                                            $short = str_replace(
                                                'right_arm_',
                                                'RA ',
                                                str_replace('left_arm_', 'LA ', $player->playerProfile->bowling_style),
                                            );
                                        @endphp
                                        <span class="text-truncate d-inline-block" style="font-size:.8rem;max-width:120px;"
                                            title="{{ $player->playerProfile->bowling_style }}">
                                            {{ $short }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                {{-- Matches --}}
                                <td class="text-center">
                                    {{ $player->playerProfile?->total_matches ?? 0 }}
                                </td>

                                {{-- Status Badge --}}
                                <td class="text-center">
                                    @if ($player->is_active)
                                        <span class="bg-success bg-opacity-10 text-success">Active</span>
                                    @else
                                        <span class="bg-secondary bg-opacity-10 text-secondary">Inactive</span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="{{ route('admin.players.edit', $player) }}"
                                            class="btn btn-sm btn-outline-primary btn-sm" title="Edit Player">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-warning btn-sm"
                                            title="Toggle Active/Inactive"
                                            onclick="toggleActive({{ $player->id }}, '{{ addslashes($player->name) }}')"
                                            data-bs-toggle="modal" data-bs-target="#activeModal">
                                            <i class="fas fa-toggle-on"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-sm"
                                            title="Delete Player"
                                            onclick="openDeleteModal({{ $player->id }}, '{{ addslashes($player->name) }}')"
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
