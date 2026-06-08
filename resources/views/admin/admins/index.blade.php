@extends('admin.layouts.master')
@section('admin-users', 'active')
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
                <h2 class="page-content-title fw-medium fs-5">Admin User Management</h2>
                <p class="page-subtitle">Manage super admin and support users</p>
            </div>
            <div class="d-flex gap-2">
                @can('view roles')
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-primary">
                        <i class="fas fa-user-shield me-2"></i>Manage Roles
                    </a>
                @endcan


                @can('create admin')
                    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fas fa-plus me-2"></i>Add Admin User
                    </button>
                @endcan
            </div>
            {{-- <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-plus me-2"></i>Add Admin User
        </button> --}}
        </div>

        <!-- Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="stats-card p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <p class="stats-label">Total Admin Users</p>
                            <p class="stats-value mb-0">{{ $admins->count() }}</p>
                        </div>
                        <div class="stats-icon" style="background-color: rgba(26, 115, 232, 0.1);">
                            <i class="fas fa-users text-primary" style="color: var(--primary-blue);"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="stats-card p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <p class="stats-label">Active Users</p>
                            <p class="stats-value mb-0">{{ $admins->where('status', 1)->count() }}</p>
                        </div>
                        <div class="stats-icon" style="background-color: rgba(220, 252, 231, 1);">
                            <i class="fas fa-check-circle text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="stats-card p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <p class="stats-label">Super Admins <br><sup class="text-warning">Full access</sup></p>
                            <p class="stats-value mb-0">
                                {{ $admins->filter(fn($u) => $u->roles->pluck('name')->contains('superadmin'))->count() }}
                            </p>
                        </div>
                        <div class="stats-icon" style="background-color: rgba(232, 182, 0, 0.1);">
                            <i class="fas fa-shield-alt" style="color: var(--accent-yellow);"></i>
                        </div>
                    </div>
                </div>
            </div>
            {{-- <div class="col-md-6 col-lg-4 col-xl-3">
            <div class="stats-card p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <p class="stats-label">Recent Activity</p>
                        <p class="stats-value mb-0">-</p>
                    </div>
                    <div class="stats-icon" style="background-color: rgba(243, 232, 255, 1);">
                        <i class="fas fa-history text-purple"></i>
                    </div>
                </div>
            </div>
        </div> --}}
        </div>

        <!-- Main Card -->
        <div class="main-card mb-4">
            <div
                class="card-header d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                <h5 class="card-title mb-0">Admin Users</h5>
            </div>
            <div class="p-3">
                <table id="adminUsersTable" class="table data-table table-hover admin-user-table" style="width:100%">
                    <thead>
                        <tr>
                            <th class="text-start">Name</th>
                            <th class="text-center">Role</th>
                            <th class="text-center">Status</th>
                            {{-- <th class="text-center">Last Login</th> --}}
                            <th class="text-center">Created</th>
                            <th class="text-center">Permissions</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($admins as $user)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="admin-user-avatar">{{ strtoupper(substr($user->name, 0, 2)) }}</div>
                                        <div class="text-start">
                                            <p class="tenant-name mb-0">{{ $user->name }}</p>
                                            <p class="tenant-email mb-0">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    @php $role = $user->roles->first()?->name ?? '-' @endphp
                                    <span class="role-badge role-super-admin">{{ $role }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="status-badge {{ $user->status ? 'status-active' : 'status-inactive' }}">
                                        {{ $user->status ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                {{-- <td class="text-center">{{ $user->last_login_at?->format('d/m/Y') ?? '-' }}</td> --}}
                                <td class="text-center">{{ $user->created_at?->format('d/m/Y') ?? '-' }}</td>
                                <td class="text-center">
                                    @php
                                        $permissionsCount = $user->getAllPermissions()->count();
                                    @endphp
                                    <span class="permissions-badge">
                                        {{ $permissionsCount ? $permissionsCount . ' permission' . ($permissionsCount > 1 ? 's' : '') : '' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="user-actions-container">
                                        @can('edit admin')
                                            <button class="user-action-btn edit" onclick="openEditModal({{ $user->id }})"
                                                title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        @endcan
                                        @can('edit admin')
                                            <a class="user-action-btn edit" href="javascript:void(0)" data-bs-toggle="modal"
                                                data-bs-target="#changePasswordModal" data-user-id="{{ $user->id }}"
                                                title="Change Password" data-action="logs">
                                                <i class="fa-solid fa-key me-2"></i>
                                            </a>
                                        @endcan
                                        @can('edit admin')
                                            @if ($role != 'superadmin')
                                                <button class="user-action-btn toggle-status"
                                                    onclick="toggleUserStatus({{ $user->id }}, {{ $user->status }})">
                                                    <i
                                                        class="fas {{ $user->status ? 'fa-ban text-warning' : 'fa-check text-success' }}"></i>
                                                </button>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="addUserForm">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addUserModalLabel">Add New Admin User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Name -->
                        <div class="mb-3">
                            <label for="userName" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="userName" name="name"
                                value="{{ old('name') }}" placeholder="Enter full name">
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="userEmail" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="userEmail" name="email"
                                value="{{ old('email') }}" placeholder="Enter email address">
                        </div>

                        <!-- Role -->
                        <div class="mb-3">
                            <label for="userRole" class="form-label">Role</label>
                            <select class="form-control form-select" id="userRole" name="role">
                                @foreach ($roles as $role)
                                    <option value="{{ $role->name }}"
                                        {{ old('role') == $role->name ? 'selected' : '' }}>{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Permissions -->
                        <div class="mb-3">
                            <label class="form-label">Permissions</label>
                            <div id="permissionsList">
                                @foreach ($permissions as $groupName => $groupPermissions)
                                    <div class="border rounded p-3 mb-3">
                                        <div class="fw-bold mb-2 text-capitalize">
                                            Manage '{{ str_replace('-', ' ', $groupName) }}'
                                        </div>
                                        <div class="row g-3">
                                            @foreach ($groupPermissions as $permission)
                                                <div class="col-md-6 col-sm-6">
                                                    <div class="form-check">
                                                        <input class="form-check-input permission-checkbox"
                                                            type="checkbox" name="permissions[]"
                                                            id="permission_{{ str_replace('.', '_', $permission->name) }}"
                                                            value="{{ $permission->name }}" disabled>
                                                        <label class="form-check-label fw-semibold"
                                                            for="permission_{{ str_replace('.', '_', $permission->name) }}">
                                                            {{ permission_action($permission->name) }}
                                                        </label>
                                                    </div>
                                                    <div class="text-muted small ms-4">
                                                        {{ permission_description($permission->name) }}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label for="userPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="userPassword" name="password"
                                placeholder="Enter password">
                            <div class="form-text">Leave blank to send invitation email</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary-custom">Create User</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="editUserForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="editUserId" name="user_id">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editUserModalLabel">Edit Admin User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Name -->
                        <div class="mb-3">
                            <label for="editUserName" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="editUserName" name="name">
                        </div>

                        <!-- Role -->
                        <div class="mb-3">
                            <label for="editUserRole" class="form-label">Role</label>
                            <select class="form-select" id="editUserRole" name="role">
                                @foreach ($roles as $role)
                                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Status -->
                        <div class="mb-3">
                            <label for="editUserStatus" class="form-label">Status</label>
                            <select class="form-select" id="editUserStatus" name="status">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>

                        <!-- Permissions -->
                        <div class="mb-3">
                            <label class="form-label">Permissions</label>
                            <div id="editPermissionsList">
                                @foreach ($permissions as $groupName => $groupPermissions)
                                    <div class="border rounded p-3 mb-3">
                                        <div class="fw-bold mb-2 text-capitalize">
                                            Manage '{{ str_replace('-', ' ', $groupName) }}'
                                        </div>
                                        <div class="row g-3">
                                            @foreach ($groupPermissions as $permission)
                                                <div class="col-md-6 col-sm-6">
                                                    <div class="form-check">
                                                        <input class="form-check-input edit-permission-checkbox"
                                                            type="checkbox" name="permissions[]"
                                                            id="edit_permission_{{ str_replace('.', '_', $permission->name) }}"
                                                            value="{{ $permission->name }}" disabled>
                                                        <label class="form-check-label fw-semibold"
                                                            for="edit_permission_{{ str_replace('.', '_', $permission->name) }}">
                                                            {{ permission_action($permission->name) }}
                                                        </label>
                                                    </div>
                                                    <div class="text-muted small ms-4">
                                                        {{ permission_description($permission->name) }}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary-custom">Save Changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>



    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="changePasswordForm" method="POST"
                    action="{{ route('admin.admins.chnage-password', ':id') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Change Password</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <div class="password-wrapper">
                                <input type="password" class="form-control password-field" name="password" required>
                                <span class="toggle-password">
                                    <i class="fa-solid fa-eye"></i>
                                </span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Confirm Password</label>
                            <div class="password-wrapper">
                                <input type="password" class="form-control password-field" name="password_confirmation"
                                    required>
                                <span class="toggle-password">
                                    <i class="fa-solid fa-eye"></i>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        $(document).on('click', '.toggle-password', function() {
            const input = $(this).siblings('input');
            const icon = $(this).find('i');

            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                input.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
        $(document).ready(function() {
            $('#changePasswordModal').on('show.bs.modal', function(event) {
                const button = $(event.relatedTarget);
                const userId = button.data('user-id');

                const form = $('#changePasswordForm');
                const action = form.attr('action').replace(':id', userId);

                form.attr('action', action);
            });
        });
    </script>
    <script>
        const rolePermissions = @json(
            $roles->mapWithKeys(function ($role) {
                return [$role->name => $role->permissions->pluck('name')];
            }));

        // Make global function
        function updatePermissions(roleName) {
            $('.permission-checkbox').prop('checked', false);

            if (rolePermissions[roleName]) {
                rolePermissions[roleName].forEach(function(permission) {
                    const id = '#permission_' + permission.replace(/\./g, '_');
                    $(id).prop('checked', true);
                });
            }
        }


        $(document).ready(function() {
            // Set permissions when modal opens
            $('#addUserModal').on('shown.bs.modal', function() {
                const initialRole = $('#userRole').val();
                updatePermissions(initialRole);
            });

            // Update permissions on role change
            $('#userRole').on('change', function() {
                const selectedRole = $(this).val();
                updatePermissions(selectedRole);
            });
        });

        function showRolePermissions(roleName) {
            // Uncheck everything first
            $('.edit-permission-checkbox').prop('checked', false);

            if (!rolePermissions[roleName]) return;

            rolePermissions[roleName].forEach(permission => {
                const id = '#edit_permission_' + permission.replace(/\./g, '_');
                $(id).prop('checked', true);
            });
        }
    </script>

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

            // Add User Modal Select2
            $('#addUserModal').on('shown.bs.modal', function() {
                $('.js-role-select').select2({
                    tags: true,
                    placeholder: 'Select or type a role',
                    allowClear: true,
                    width: '100%',
                    dropdownParent: $('#addUserModal')
                });
            });

            // Add User AJAX
            $('#addUserForm').submit(function(e) {
                e.preventDefault();
                let formData = $(this).serialize();
                $.post("{{ route('admin.admins.store') }}", formData, function(res) {
                    iziToast.success({
                        message: res.message,
                        position: 'topRight'
                    });
                    $('#addUserForm')[0].reset();

                    // Reset to first role and update permissions
                    const firstRole = $('#userRole option:first').val();
                    $('#userRole').val(firstRole);
                    updatePermissions(firstRole);

                    $('.js-role-select').val(null).trigger('change');
                    $('#addUserModal').modal('hide');
                    location.reload();
                }).fail(function(xhr) {
                    if (xhr.status === 422) {
                        $.each(xhr.responseJSON.errors, function(k, v) {
                            iziToast.error({
                                message: v[0],
                                position: 'topRight'
                            });
                        });
                    } else {
                        iziToast.error({
                            message: 'Something went wrong',
                            position: 'topRight'
                        });
                    }
                });
            });

            // Edit User AJAX
            $('#editUserForm').submit(function(e) {
                e.preventDefault();
                let userId = $('#editUserId').val();
                let formData = $(this).serialize();
                $.ajax({
                    url: `/admin/admins/${userId}`,
                    method: 'PUT',
                    data: formData,
                    success: function(res) {
                        iziToast.success({
                            message: res.message,
                            position: 'topRight'
                        });
                        $('#editUserModal').modal('hide');
                        location.reload();
                    },
                    error: function(xhr) {
                        iziToast.error({
                            message: xhr.responseJSON?.message ||
                                'Something went wrong',
                            position: 'topRight'
                        });
                    }
                });
            });
        });

        // Open Edit Modal dynamically
        function openEditModal(userId) {
            $.get(`/admin/admins/${userId}/edit`, function(user) {
                $('#editUserId').val(user.id);
                $('#editUserName').val(user.name);
                $('#editUserRole').val(user.role);
                $('#editUserStatus').val(user.status ? 1 : 0);

                showRolePermissions(user.role);

                // $('.edit-permission-checkbox').each(function() {
                //     let p = $(this).val();

                //     if (user.rolePermissions.includes(p)) {
                //         console.log(p);
                //         $(this).prop('checked', true).prop('disabled', true);
                //     } else {
                //         $(this).prop('checked', user.userPermissions.includes(p)).prop('disabled', false);
                //     }
                // });

                $('#editUserModal').modal('show');
            });
        }
        $('#editUserRole').on('change', function() {
            showRolePermissions(this.value);
        });

        // Suspend User
        function toggleUserStatus(userId, currentStatus) {
            let action = currentStatus ? 'suspend' : 'activate';
            let confirmMessage = currentStatus ?
                'Are you sure you want to suspend this user?' :
                'Are you sure you want to activate this user?';

            if (confirm(confirmMessage)) {
                $.post(`/admin/admins/${userId}/suspend`, {
                    _token: "{{ csrf_token() }}"
                }, function(res) {
                    iziToast.success({
                        message: res.message,
                        position: 'topRight'
                    });
                    location.reload();
                }).fail(function() {
                    iziToast.error({
                        message: 'Something went wrong',
                        position: 'topRight'
                    });
                });
            }
        }
    </script>
@endpush
