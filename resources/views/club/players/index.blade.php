@extends('club.layouts.master')

@section('title', $title)

@section('content')
<main class="club-page">
    <div class="club-card mb-3">
        <div class="club-card-body padded">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-sm-4">
                    <label class="form-label small text-muted mb-1">Member status</label>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Active only</option>
                        @foreach(['active', 'pending', 'inactive'] as $status)
                            <option value="{{ $status }}" @selected($statusFilter === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="club-card">
        <div class="club-card-header">
            <h6 class="mb-0"><i class="fas fa-user-friends me-2 text-success"></i>Club Players</h6>
            <span class="club-badge muted">{{ $members->total() }} members</span>
        </div>
        <div class="club-card-body">
            @if($members->isEmpty())
                <div class="club-empty">
                    <i class="fas fa-user-plus"></i>
                    <p class="fw-semibold mb-1">No players yet</p>
                    <small>Invite players from the <a href="{{ route('club.invitations.create') }}">Invitations</a> page.</small>
                </div>
            @else
                <div class="table-responsive d-none d-md-block">
                    <table class="table club-fixtures-table mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($members as $member)
                            @php $user = $member->user; @endphp
                            <tr>
                                <td class="fw-medium">{{ $user?->full_name ?? '—' }}</td>
                                <td class="small text-muted">{{ $user?->email ?? '—' }}</td>
                                <td><span class="club-badge muted">{{ ucfirst($member->role) }}</span></td>
                                <td>
                                    <span class="club-badge {{ $member->status === 'active' ? 'success' : 'muted' }}">
                                        {{ ucfirst($member->status) }}
                                    </span>
                                </td>
                                <td class="small text-muted">{{ $member->joined_at?->format('d M Y') ?? '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="club-fixture-mobile-list d-md-none">
                    @foreach($members as $member)
                    @php $user = $member->user; @endphp
                    <div class="club-fixture-mobile-item">
                        <div class="match-teams">{{ $user?->full_name ?? 'Unknown' }}</div>
                        <div class="match-meta">
                            <span>{{ $user?->email }}</span>
                            <span class="club-badge muted">{{ ucfirst($member->role) }}</span>
                            <span class="club-badge {{ $member->status === 'active' ? 'success' : 'muted' }}">{{ ucfirst($member->status) }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
                @if($members->hasPages())
                    <div class="p-3 border-top">{{ $members->links() }}</div>
                @endif
            @endif
        </div>
    </div>
</main>
@endsection
