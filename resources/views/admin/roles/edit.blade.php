@extends('admin.layouts.master')

@section('admin-users', 'active')
@section('title') Admin| role edit @endsection

@push('style')
@endpush

@section('content')
    <main class="container-fluid p-3 p-lg-4">
        <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-center">
            <div class="mb-0">
                <h2 class="page-content-title fw-medium fs-5">Admin Role</h2>
                <p class="page-subtitle">Manage Roles</p>
            </div>
            <div class="d-flex gap-2">
                {{-- @can('role.manage') --}}
                <a href="{{ route('admin.roles.index') }}" class="btn btn-primary">
                    <i class="fa-solid fa-angle-left"></i> Back</a>
                {{-- @endcan --}}
            </div>
        </div>

        <!-- Main Card -->
        <div class="main-card mb-4">
            <div
                class="card-header d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                <h5 class="card-title mb-0">Role Update</h5>
            </div>
            <div class="p-3">
                <form method="POST" action="{{ route('admin.roles.update', $role->id) }}">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">Role_name</label>
                        <input value="{{ $role->name }}" type="text" class="form-control" name="name"
                            placeholder="Role name" required>

                        @if ($errors->has('name'))
                            <span class="text-danger text-left">{{ $errors->first('name') }}</span>
                        @endif
                    </div>

                    <div class="mb-3">
                        <div class="row">
                            <div class="col-3">
                                <div class="custom-control custom-checkbox">
                                    <input
                                        {{ App\Models\Admin::roleHasPermission($role, $permissions) ? 'checked' : '' }}
                                        class="custom-control-input" type="checkbox" id="permission_all" value="1">
                                    <label for="permission_all" class="custom-control-label">Roles.all</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        @php $i=1; @endphp
                        @foreach ($permission_groups as $group)
                            @php
                                $permissionss = App\Models\Admin::getpermissionsByGroupName($group->name);
                                $j = 1;
                            @endphp
                            <div class="row">
                                <div class="col-3">
                                    <div class="custom-control custom-checkbox">
                                        <input
                                            {{ App\Models\Admin::roleHasPermission($role, $permissions) ? 'checked' : '' }}
                                            class="custom-control-input" type="checkbox" id="{{ $i }}management"
                                            onclick="CheckPermissionByGroup('role-{{ $i }}-management-checkbox',this)"
                                            value="2">
                                        <label for="{{ $i }}management"
                                            class="custom-control-label text-capitalize">{{ $group->name }}</label>
                                    </div>
                                </div>
                                <div class="col-9 role-{{ $i }}-management-checkbox">
                                    @foreach ($permissionss as $permission)
                                        <div class="custom-control custom-checkbox">
                                            <input
                                                onclick="checksinglepermission('role-{{ $i }}-management-checkbox','{{ $i }}management',{{ count($permissionss) }})"
                                                {{ $role->hasPermissionTo($permission->name) ? 'checked' : '' }}
                                                name="permissions[]" class="custom-control-input" type="checkbox"
                                                id="permission_checkbox_{{ $permission->id }}"
                                                value="{{ $permission->name }}">
                                            <label for="permission_checkbox_{{ $permission->id }}"
                                                class="custom-control-label">{{ __($permission->name) }}</label>
                                        </div>
                                        @php $j++; @endphp
                                    @endforeach
                                </div>
                            </div>
                            <hr>
                            @php $i++; @endphp
                        @endforeach

                    </div>


                    <button type="submit" class="btn btn-primary-custom">Update</button>

                </form>
            </div>
        </div>
    </main>


@endsection

@push('script')
    <script>
        $('#permission_all').click(function() {
            if ($(this).is(':checked')) {
                // check all the checkbox
                $('input[type=checkbox]').prop('checked', true);
            } else {
                // uncheck all the checkbox
                $('input[type=checkbox]').prop('checked', false);
            }
        });

        // check permission by group
        function CheckPermissionByGroup(classname, checkthis) {
            const groupIdName = $("#" + checkthis.id);
            const classCheckBox = $('.' + classname + ' input');
            if (groupIdName.is(':checked')) {
                // check all the checkbox
                classCheckBox.prop('checked', true);
            } else {
                // uncheck all the checkbox
                classCheckBox.prop('checked', false);
            }
        }
    </script>
@endpush
