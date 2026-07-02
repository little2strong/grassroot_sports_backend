@extends('club.layouts.master')

@section('title', $title)

@section('content')
<main class="club-page">
    <div class="club-card mb-3">
        <div class="club-card-body padded">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-sm-4">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All</option>
                        @foreach(['pending', 'accepted', 'rejected', 'cancelled', 'expired'] as $status)
                            <option value="{{ $status }}" @selected($statusFilter === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="club-card">
        <div class="club-card-header">
            <h6 class="mb-0"><i class="fas fa-envelope me-2 text-success"></i>Player Invitations</h6>
            <div class="d-flex align-items-center gap-2">
                <span class="club-badge muted">{{ $invitations->total() }} total</span>
                <a href="{{ route('club.invitations.create') }}" class="btn btn-sm btn-club-primary">
                    <i class="fas fa-user-plus me-1"></i> Invite Player
                </a>
            </div>
        </div>
        <div class="club-card-body">
            @if($invitations->isEmpty())
                <div class="club-empty">
                    <i class="fas fa-envelope-open"></i>
                    <p class="fw-semibold mb-1">No invitations</p>
                    <small class="d-block mb-3">Select a player from the system to send an invite.</small>
                    <a href="{{ route('club.invitations.create') }}" class="btn btn-club-primary btn-sm">
                        <i class="fas fa-user-plus me-1"></i> Invite Player
                    </a>
                </div>
            @else
                <div class="table-responsive d-none d-md-block">
                    <table class="table club-fixtures-table mb-0">
                        <thead>
                            <tr>
                                <th>Invitee</th>
                                <th>Squad</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Sent</th>
                                <th>Expires</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invitations as $invitation)
                            @php
                                $isPending = $invitation->isPending();
                                $statusClass = match (true) {
                                    $invitation->status === 'accepted' => 'success',
                                    $invitation->status === 'rejected' || $invitation->status === 'cancelled' => 'muted',
                                    $isPending => 'success',
                                    default => 'muted',
                                };
                            @endphp
                            <tr>
                                <td>
                                    <span class="fw-medium d-block">{{ $invitation->invited_email }}</span>
                                    @if($invitation->invitedUser)
                                        <small class="text-muted">{{ $invitation->invitedUser->full_name }}</small>
                                    @elseif($invitation->invited_phone)
                                        <small class="text-muted">{{ $invitation->invited_phone }}</small>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $invitation->team?->name ?? '—' }}</td>
                                <td><span class="club-badge muted">{{ $invitation->role_label }}</span></td>
                                <td>
                                    <span class="club-badge {{ $statusClass }}">
                                        {{ $invitation->status_label }}
                                    </span>
                                </td>
                                <td class="small text-muted">
                                    {{ $invitation->created_at?->format('d M Y') }}
                                    <br>{{ $invitation->invitedBy?->full_name ?? '—' }}
                                </td>
                                <td class="small text-muted">{{ $invitation->expires_at?->format('d M Y') ?? '—' }}</td>
                                <td class="text-end">
                                    @if($isPending)
                                        <form action="{{ route('club.invitations.destroy', $invitation) }}" method="POST" class="d-inline invitation-cancel-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light border text-danger" title="Cancel invitation">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="club-fixture-mobile-list d-md-none">
                    @foreach($invitations as $invitation)
                    @php $isPending = $invitation->isPending(); @endphp
                    <div class="club-fixture-mobile-item">
                        <div class="match-teams">{{ $invitation->invited_email }}</div>
                        <div class="match-meta mb-2">
                            @if($invitation->team)
                                <span>{{ $invitation->team->name }}</span>
                            @endif
                            <span class="club-badge muted">{{ $invitation->role_label }}</span>
                            <span class="club-badge {{ $isPending ? 'success' : 'muted' }}">{{ $invitation->status_label }}</span>
                            <span>{{ $invitation->created_at?->format('d M Y') }}</span>
                        </div>
                        @if($isPending)
                        <form action="{{ route('club.invitations.destroy', $invitation) }}" method="POST" class="invitation-cancel-form">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-light border text-danger w-100">
                                <i class="fas fa-times me-1"></i> Cancel Invitation
                            </button>
                        </form>
                        @endif
                    </div>
                    @endforeach
                </div>
                @if($invitations->hasPages())
                    <div class="p-3 border-top">{{ $invitations->links() }}</div>
                @endif
            @endif
        </div>
    </div>
</main>
@endsection

@push('script')
<script>
    document.querySelectorAll('.invitation-cancel-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            Swal.fire({
                title: 'Cancel invitation?',
                text: 'The player will no longer be able to accept this invite.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, cancel',
                cancelButtonText: 'Keep'
            }).then(function (result) {
                if (result.isConfirmed) form.submit();
            });
        });
    });
</script>
@endpush
