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
                        <option value="">All</option>
                        @foreach(['published','live','paused','completed','cancelled','postponed','draft'] as $status)
                            <option value="{{ $status }}" @selected($statusFilter === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="club-card">
        <div class="club-card-header">
            <h6 class="mb-0"><i class="fas fa-trophy me-2 text-success"></i>Matches</h6>
            <span class="club-badge muted">{{ $fixtures->total() }} total</span>
        </div>
        <div class="club-card-body">
            @if($fixtures->isEmpty())
                <div class="club-empty">
                    <i class="fas fa-trophy"></i>
                    <p class="fw-semibold mb-1">No matches found</p>
                    <small>Schedule fixtures to start scoring.</small>
                </div>
            @else
                <div class="table-responsive d-none d-md-block">
                    <table class="table club-fixtures-table mb-0">
                        <thead>
                            <tr>
                                <th>Match</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th class="text-end">Scorecard</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fixtures as $fixture)
                                <tr>
                                    <td>
                                        <div class="fw-medium">{{ $fixture->home_display_name }} <span class="text-muted fw-normal">vs</span> {{ $fixture->away_display_name }}</div>
                                        <div class="small text-muted">
                                            {{ $fixture->match_type_label ?? $fixture->match_type }}
                                            @if($fixture->venue) · {{ $fixture->venue->name }} @endif
                                        </div>
                                    </td>
                                    <td class="small text-muted">
                                        {{ $fixture->scheduled_date?->format('d M Y') }}
                                        @if($fixture->scheduled_time)
                                            <br>{{ \Carbon\Carbon::parse($fixture->scheduled_time)->format('H:i') }}
                                        @endif
                                    </td>
                                    <td>
                                        <span class="club-badge {{ in_array($fixture->status, ['live','paused']) ? 'danger' : ($fixture->status === 'completed' ? 'success' : 'muted') }}">
                                            {{ ucfirst($fixture->status) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('club.scoring.show', $fixture) }}" class="btn btn-sm btn-light border">
                                            <i class="fas fa-chart-bar me-1"></i> View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="club-fixture-mobile-list d-md-none">
                    @foreach($fixtures as $fixture)
                        <div class="club-fixture-mobile-item">
                            <div class="match-teams">
                                {{ $fixture->home_display_name }}
                                <span class="text-muted fw-normal">vs</span>
                                {{ $fixture->away_display_name }}
                            </div>
                            <div class="match-meta mb-2">
                                <span>{{ $fixture->scheduled_date?->format('d M Y') }}</span>
                                <span class="club-badge {{ in_array($fixture->status, ['live','paused']) ? 'danger' : 'muted' }}">{{ ucfirst($fixture->status) }}</span>
                            </div>
                            <a href="{{ route('club.scoring.show', $fixture) }}" class="btn btn-sm btn-light border w-100">
                                <i class="fas fa-chart-bar me-1"></i> View Scorecard
                            </a>
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

