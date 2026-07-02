@extends('club.layouts.master')

@section('title', $title)

@section('content')
<main class="club-page">
    <div class="club-card">
        <div class="club-card-header">
            <h6 class="mb-0"><i class="fas fa-users me-2 text-success"></i>Squads / Teams</h6>
            <div class="d-flex align-items-center gap-2">
                <span class="club-badge muted">{{ $teams->total() }} teams</span>
                <a href="{{ route('club.squads.create') }}" class="btn btn-sm btn-club-primary">
                    <i class="fas fa-plus me-1"></i> Add Squad
                </a>
            </div>
        </div>
        <div class="club-card-body">
            @if($teams->isEmpty())
                <div class="club-empty">
                    <i class="fas fa-users"></i>
                    <p class="fw-semibold mb-1">No squads yet</p>
                    <small class="d-block mb-3">Create your first squad to organize players.</small>
                    <a href="{{ route('club.squads.create') }}" class="btn btn-club-primary btn-sm">
                        <i class="fas fa-plus me-1"></i> Add Squad
                    </a>
                </div>
            @else
                <div class="table-responsive d-none d-md-block">
                    <table class="table club-fixtures-table mb-0">
                        <thead>
                            <tr>
                                <th>Team</th>
                                <th>Short Name</th>
                                <th>Players</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($teams as $team)
                            <tr>
                                <td>
                                    <span class="fw-medium">{{ $team->name }}</span>
                                    @if($team->primary_color)
                                        <span class="d-inline-block rounded-circle ms-1 align-middle"
                                            style="width:10px;height:10px;background:{{ $team->primary_color }};"></span>
                                    @endif
                                </td>
                                <td>{{ $team->short_name ?? '—' }}</td>
                                <td>{{ $team->players_count }}</td>
                                <td>
                                    <span class="club-badge {{ $team->is_active ? 'success' : 'muted' }}">
                                        {{ $team->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="club-table-actions">
                                        <a href="{{ route('club.squads.edit', $team) }}" class="btn btn-sm btn-light border" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('club.squads.destroy', $team) }}" method="POST" class="d-inline squad-delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light border text-danger" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="club-fixture-mobile-list d-md-none">
                    @foreach($teams as $team)
                    <div class="club-fixture-mobile-item">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div class="match-teams mb-0">{{ $team->name }}</div>
                            <span class="club-badge {{ $team->is_active ? 'success' : 'muted' }} flex-shrink-0">
                                {{ $team->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <div class="match-meta mb-2">
                            <span>{{ $team->short_name ?: 'No short name' }}</span>
                            <span>{{ $team->players_count }} players</span>
                        </div>
                        <div class="club-table-actions">
                            <a href="{{ route('club.squads.edit', $team) }}" class="btn btn-sm btn-light border flex-grow-1">
                                <i class="fas fa-edit me-1"></i> Edit
                            </a>
                            <form action="{{ route('club.squads.destroy', $team) }}" method="POST" class="flex-grow-1 squad-delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light border text-danger w-100">
                                    <i class="fas fa-trash-alt me-1"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
                @if($teams->hasPages())
                    <div class="p-3 border-top">{{ $teams->links() }}</div>
                @endif
            @endif
        </div>
    </div>
</main>
@endsection

@push('script')
<script>
    document.querySelectorAll('.squad-delete-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            Swal.fire({
                title: 'Delete squad?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete',
                cancelButtonText: 'Cancel'
            }).then(function (result) {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
@endpush
