@extends('club.layouts.master')

@section('title', $title)

@section('content')
<main class="club-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="min-w-0">
            <p class="text-muted small mb-1">Availability for fixture</p>
            <h5 class="mb-0 text-truncate">
                {{ $fixture->home_display_name }} vs {{ $fixture->away_display_name }}
            </h5>
            <p class="text-muted small mb-0">
                {{ $fixture->scheduled_date?->format('d M Y') }}
                @if($fixture->scheduled_time) · {{ \Carbon\Carbon::parse($fixture->scheduled_time)->format('H:i') }} @endif
            </p>
        </div>
        <a href="{{ route('club.fixtures.index') }}" class="btn btn-sm btn-light border">
            <i class="fas fa-arrow-left me-1"></i> Back to fixtures
        </a>
    </div>

    <div class="club-card mb-3">
        <div class="club-card-body padded">
            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <div class="d-flex flex-wrap gap-2">
                    <span class="club-badge muted">Total: {{ $summary['total'] }}</span>
                    <span class="club-badge success">Available: {{ $summary['available'] }}</span>
                    <span class="club-badge muted">Maybe: {{ $summary['maybe'] }}</span>
                    <span class="club-badge danger">Unavailable: {{ $summary['unavailable'] }}</span>
                    <span class="club-badge muted">Pending: {{ $summary['pending'] }}</span>
                </div>
                <form method="GET" class="d-flex gap-2 align-items-center">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All</option>
                        @foreach(['available','maybe','unavailable','pending'] as $s)
                            <option value="{{ $s }}" @selected($statusFilter === $s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            @if(!$team)
                <div class="alert alert-light border mt-3 mb-0">
                    <i class="fas fa-info-circle me-1 text-muted"></i>
                    This fixture has no club squad selected yet.
                </div>
            @else
                <small class="text-muted d-block mt-2">Squad: <strong>{{ $team->name }}</strong></small>
            @endif
        </div>
    </div>

    <div class="club-card">
        <div class="club-card-header">
            <h6 class="mb-0"><i class="fas fa-user-check me-2 text-success"></i>Players</h6>
            <span class="club-badge muted">{{ $rows->count() }} shown</span>
        </div>
        <div class="club-card-body">
            @if($rows->isEmpty())
                <div class="club-empty">
                    <i class="fas fa-users"></i>
                    <p class="fw-semibold mb-1">No players found</p>
                    <small>Try removing the filter or add players to the squad.</small>
                </div>
            @else
                <div class="table-responsive d-none d-md-block">
                    <table class="table club-fixtures-table mb-0">
                        <thead>
                            <tr>
                                <th>Player</th>
                                <th>Status</th>
                                <th>Reason</th>
                                <th class="text-end">Responded</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                                @php
                                    $badge = match($row['status']) {
                                        'available' => 'success',
                                        'unavailable' => 'danger',
                                        default => 'muted',
                                    };
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-medium">{{ $row['member']->user?->full_name ?: $row['member']->user?->email }}</div>
                                        <div class="small text-muted">{{ $row['member']->user?->email }}</div>
                                    </td>
                                    <td><span class="club-badge {{ $badge }}">{{ $row['status_label'] }}</span></td>
                                    <td class="small text-muted">{{ $row['reason'] ?: '—' }}</td>
                                    <td class="small text-muted text-end">{{ $row['responded_at']?->format('d M Y H:i') ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="club-fixture-mobile-list d-md-none">
                    @foreach($rows as $row)
                        <div class="club-fixture-mobile-item">
                            <div class="match-teams mb-1">{{ $row['member']->user?->full_name ?: $row['member']->user?->email }}</div>
                            <div class="match-meta mb-2">
                                <span class="club-badge {{ $row['status'] === 'available' ? 'success' : ($row['status'] === 'unavailable' ? 'danger' : 'muted') }}">{{ $row['status_label'] }}</span>
                                @if($row['responded_at'])
                                    <span>{{ $row['responded_at']->format('d M Y') }}</span>
                                @endif
                            </div>
                            @if($row['reason'])
                                <div class="small text-muted">{{ $row['reason'] }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</main>
@endsection

