@extends('admin.layouts.master')

@section('admin-users', 'active')
@section('title') Admin| roles @endsection

@push('style')
    <style>
        .table-bordered td,
        .table-bordered th {
            border: 1px solid #dee2e6 !important;
        }
    </style>
@endpush

@section('content')
    <main class="container-fluid p-3 p-lg-4">
        <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-center">
            <div class="mb-0">
                <h2 class="page-content-title fw-medium fs-5">Admin roles list</h2>
                <p class="page-subtitle">Manage Roles</p>
            </div>
            <div class="d-flex gap-2">
                @can('create role')
                    <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">+Create Role</a>
                @endcan
            </div>
        </div>

        <!-- Main Card -->
        <div class="main-card mb-4">
            <div
                class="card-header d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                <h5 class="card-title mb-0">Role List</h5>
            </div>
            <div class="p-3">
                <table class="data-table table table-hover">
                    <tr>
                        <th width="1%">No</th>
                        <th>Name</th>
                        <th>Permission</th>
                        <th width="10%" colspan="3" class="text-center">
                            Action</th>
                    </tr>
                    @foreach ($roles as $key => $role)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td>{{ $role->name }}</td>
                            <td>
                                <div>
                                    @foreach ($role->permissions as $item)
                                        <span class="badge badge-primary permission">{{ __($item->name) }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="">
                                <div class="action-buttons">
                                    {{-- <a class="btn btn-info btn-xs"
                                                        href="{{ route('admin.roles.show', $role->id) }}">Show</a> --}}
                                    @can('edit role')
                                        <a class="action-btn view" href="{{ route('admin.roles.edit', $role->id) }}"
                                            title="Edit">
                                            <i class="fas fa-pencil"></i></a>
                                    @endcan

                                    {{-- <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST"
                                                        class="d-inline">
                                                        @method('DELETE')
                                                        @csrf
                                                        <button
                                                            onclick="return confirm('Are you sure you want to delete this item?');"
                                                            class="btn btn-danger btn-xs">Delete</button>
                                                    </form> --}}
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </table>

            </div>
        </div>
    </main>

@endsection
@push('script')
@endpush
