@extends('club.layouts.master')

@section('title', $title)

@section('content')
<main class="club-page">
    <div class="club-card mb-3">
        <div class="club-card-body padded">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-sm-4">
                    <label class="form-label small text-muted mb-1">Filter by status</label>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All statuses</option>
                        @foreach(['draft', 'published', 'live', 'paused', 'completed', 'cancelled', 'postponed'] as $status)
                            <option value="{{ $status }}" @selected($statusFilter === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="club-card">
        <div class="club-card-header">
            <h6 class="mb-0"><i class="fas fa-calendar-alt me-2 text-success"></i>Fixtures</h6>
            <div class="d-flex align-items-center gap-2">
                <span class="club-badge muted">{{ $fixtures->total() }} total</span>
                <a href="{{ route('club.fixtures.create') }}" class="btn btn-sm btn-club-primary">
                    <i class="fas fa-plus me-1"></i> Add Fixture
                </a>
            </div>
        </div>
        <div class="club-card-body">
            @if($fixtures->isEmpty())
                <div class="club-empty">
                    <i class="fas fa-calendar-plus"></i>
                    <p class="fw-semibold mb-1">No fixtures found</p>
                    <small class="d-block mb-3">Schedule your first match to get started.</small>
                    <a href="{{ route('club.fixtures.create') }}" class="btn btn-club-primary btn-sm">
                        <i class="fas fa-plus me-1"></i> Add Fixture
                    </a>
                </div>
            @else
                <div class="table-responsive d-none d-md-block">
                    <table class="table club-fixtures-table mb-0">
                        <thead>
                            <tr>
                                <th>Match</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Public</th>
                                <th>Fees</th>
                                <th>Scorer</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fixtures as $fixture)
                            @php $locked = in_array($fixture->status, ['live', 'paused', 'completed']); @endphp
                            <tr>
                                <td>
                                    <span class="fw-medium">{{ $fixture->home_display_name }}</span>
                                    <span class="text-muted"> vs </span>
                                    <span class="fw-medium">{{ $fixture->away_display_name }}</span>
                                </td>
                                <td class="small text-muted">
                                    {{ $fixture->scheduled_date?->format('d M Y') }}
                                    @if($fixture->scheduled_time)
                                        <br>{{ \Carbon\Carbon::parse($fixture->scheduled_time)->format('H:i') }}
                                    @endif
                                </td>
                                <td><span class="club-badge muted">{{ $fixture->match_type_label ?? $fixture->match_type }}</span></td>
                                <td>
                                    <span class="club-badge {{ in_array($fixture->status, ['live','paused']) ? 'danger' : ($fixture->status === 'published' ? 'success' : 'muted') }}">
                                        {{ ucfirst($fixture->status) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="club-badge {{ $fixture->is_public ? 'success' : 'muted' }}">
                                        {{ $fixture->is_public ? 'Yes' : 'No' }}
                                    </span>
                                </td>
                                <td class="small">
                                    @php
                                        $totalFees = $fixture->matchFees->sum('amount');
                                        $unpaidCount = $fixture->matchFees->whereNotIn('status', ['verified', 'waived'])->count();
                                        $totalPlayers = $fixture->matchFees->count();
                                    @endphp
                                    @if($totalFees > 0 || $unpaidCount > 0)
                                        <div class="fw-medium">{{ $totalFees > 0 ? '$' . number_format($totalFees, 2) : '$0.00' }}</div>
                                        @if($unpaidCount > 0)
                                            <span class="text-warning small">{{ $unpaidCount }} unpaid</span>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="small">
                                    @if($fixture->scorer)
                                        <div class="fw-medium">{{ $fixture->scorer->full_name ?: $fixture->scorer->email }}</div>
                                        <div class="text-muted small">{{ $fixture->scorer->email }}</div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="club-table-actions">
                                        <a href="{{ route('club.fixtures.availability', $fixture) }}" class="btn btn-sm btn-light border" title="Availability">
                                            <i class="fas fa-user-check"></i>
                                        </a>
                                        <a href="{{ route('club.fixtures.collect-fee', $fixture) }}" class="btn btn-sm btn-light border" title="Collect Fee">
                                            <i class="fas fa-money-bill"></i>
                                        </a>
                                        <a href="{{ route('club.fixtures.bulk-collect-fee', $fixture) }}" class="btn btn-sm btn-light border" title="Bulk Collect Fee">
                                            <i class="fas fa-users"></i>
                                        </a>
                                        @if(!$locked)
                                            <button type="button"
                                                class="btn btn-sm btn-light border assign-scorer-btn"
                                                data-fixture-id="{{ $fixture->id }}"
                                                data-current-scorer="{{ $fixture->scorer_user_id ?? '' }}"
                                                title="Assign scorer">
                                                <i class="fas fa-user-pen"></i>
                                            </button>
                                            <a href="{{ route('club.fixtures.edit', $fixture) }}" class="btn btn-sm btn-light border" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('club.fixtures.destroy', $fixture) }}" method="POST" class="d-inline fixture-delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-light border text-danger" title="Delete">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted small">Locked</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="club-fixture-mobile-list d-md-none">
                    @foreach($fixtures as $fixture)
                    @php $locked = in_array($fixture->status, ['live', 'paused', 'completed']); @endphp
                    <div class="club-fixture-mobile-item">
                        <div class="match-teams">
                            {{ $fixture->home_display_name }}
                            <span class="text-muted fw-normal">vs</span>
                            {{ $fixture->away_display_name }}
                        </div>
                        <div class="match-meta mb-2">
                            <span>{{ $fixture->scheduled_date?->format('d M Y') }}</span>
                            <span class="club-badge muted">{{ $fixture->match_type_label ?? $fixture->match_type }}</span>
                            <span class="club-badge {{ in_array($fixture->status, ['live','paused']) ? 'danger' : 'muted' }}">{{ ucfirst($fixture->status) }}</span>
                        </div>
                        @php
                            $totalFees = $fixture->matchFees->sum('amount');
                            $unpaidCount = $fixture->matchFees->whereNotIn('status', ['verified', 'waived'])->count();
                        @endphp
                        @if($totalFees > 0 || $unpaidCount > 0)
                        <div class="small text-muted mb-2">
                            <strong>Fees:</strong> ${{ $totalFees > 0 ? number_format($totalFees, 2) : '0.00' }}
                            @if($unpaidCount > 0)
                                <span class="text-warning">, {{ $unpaidCount }} unpaid</span>
                            @endif
                        </div>
                        @endif
                        <div class="small text-muted mb-2">
                            <strong>Scorer:</strong>
                            {{ $fixture->scorer?->full_name ?: $fixture->scorer?->email ?: '—' }}
                        </div>
                        @if(!$locked)
                        <div class="club-table-actions">
                            <a href="{{ route('club.fixtures.availability', $fixture) }}" class="btn btn-sm btn-light border flex-grow-1">
                                <i class="fas fa-user-check me-1"></i> Availability
                            </a>
                            <a href="{{ route('club.fixtures.collect-fee', $fixture) }}" class="btn btn-sm btn-light border flex-grow-1">
                                <i class="fas fa-money-bill me-1"></i> Collect Fee
                            </a>
                            <a href="{{ route('club.fixtures.bulk-collect-fee', $fixture) }}" class="btn btn-sm btn-light border flex-grow-1">
                                <i class="fas fa-users me-1"></i> Bulk Collect
                            </a>
                            <button type="button"
                                class="btn btn-sm btn-light border flex-grow-1 assign-scorer-btn"
                                data-fixture-id="{{ $fixture->id }}"
                                data-current-scorer="{{ $fixture->scorer_user_id ?? '' }}">
                                <i class="fas fa-user-pen me-1"></i> Assign Scorer
                            </button>
                            <a href="{{ route('club.fixtures.edit', $fixture) }}" class="btn btn-sm btn-light border flex-grow-1">
                                <i class="fas fa-edit me-1"></i> Edit
                            </a>
                            <form action="{{ route('club.fixtures.destroy', $fixture) }}" method="POST" class="flex-grow-1 fixture-delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light border text-danger w-100">
                                    <i class="fas fa-trash-alt me-1"></i> Delete
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @if($fixtures->hasPages())
                    <div class="p-3 border-top">{{ $fixtures->links() }}</div>
                @endif
            @endif
        </div>
    </div>
</main>
@endsection

@push('script')
<script>
    const scorerFormTemplate = (fixtureId, currentScorerId) => {
        const action = `{{ url('/club/fixtures') }}/${fixtureId}/scorer`;
        const optionsHtml = [
            `<option value=\"\">No scorer</option>`,
            @foreach($scorers as $s)
                `<option value=\"{{ $s->id }}\">{{ addslashes($s->full_name ?: $s->email) }}</option>`,
            @endforeach
        ].join('');

        return `
            <form method="POST" action="${action}">
                @csrf
                <div class="mb-3 text-start">
                    <label class="form-label">Select scorer</label>
                    <select name="scorer_user_id" class="form-select" id="scorer_select">
                        ${optionsHtml}
                    </select>
                    <div class="form-text">Only active club members (scorer/manager/captain/admin/owner) are shown.</div>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light border" onclick="Swal.close()">Cancel</button>
                    <button type="submit" class="btn btn-club-primary">Save</button>
                </div>
            </form>
        `;
    };

    document.querySelectorAll('.assign-scorer-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const fixtureId = this.dataset.fixtureId;
            const current = this.dataset.currentScorer || '';

            Swal.fire({
                title: 'Assign scorer',
                html: scorerFormTemplate(fixtureId, current),
                showConfirmButton: false,
                didOpen: () => {
                    const select = document.getElementById('scorer_select');
                    if (select) select.value = current;
                }
            });
        });
    });

    document.querySelectorAll('.fixture-delete-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            Swal.fire({
                title: 'Delete fixture?',
                text: 'This match schedule will be removed.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete',
                cancelButtonText: 'Cancel'
            }).then(function (result) {
                if (result.isConfirmed) form.submit();
            });
        });
    });
</script>
@endpush
