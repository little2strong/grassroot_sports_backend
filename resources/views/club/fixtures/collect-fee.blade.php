@extends('club.layouts.master')

@section('title', $title)

@section('content')
<main class="club-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <p class="text-muted small mb-0">
            {{ $fixture->home_display_name }} vs {{ $fixture->away_display_name }}
        </p>
        <a href="{{ route('club.fixtures.index') }}" class="btn btn-sm btn-light border">
            <i class="fas fa-arrow-left me-1"></i> Back to fixtures
        </a>
    </div>

    <div class="club-card">
        <div class="club-card-header">
            <h6 class="mb-0">Collect Fee</h6>
        </div>
        <div class="club-card-body padded">
            @php
                $members = $members ?? collect();
                $existingFees = $existingFees ?? collect();
            @endphp

            @if($existingFees->isNotEmpty())
                <div class="mb-4">
                    <h6 class="mb-3">Collected Fees</h6>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Player</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Collected At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($existingFees as $fee)
                                <tr>
                                    <td class="fw-medium">{{ $fee->player?->name ?? 'Unknown' }}</td>
                                    <td>{{ $fee->currency }} {{ number_format($fee->amount, 2) }}</td>
                                    <td><span class="club-badge {{ $fee->status === 'verified' ? 'success' : 'muted' }}">{{ ucfirst($fee->status) }}</span></td>
                                    <td class="small text-muted">{{ $fee->paid_by_player_at?->format('d M Y, H:i') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <form action="{{ route('club.fixtures.collect-fee.store', $fixture) }}" method="post">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="player_id" class="form-label">Player <span class="text-danger">*</span></label>
                        <select class="form-select @error('player_id') is-invalid @enderror" id="player_id" name="player_id" required>
                            <option value="">Select player</option>
                            @foreach($members as $member)
                                <option value="{{ $member->user->id }}">{{ $member->user->name }}</option>
                            @endforeach
                        </select>
                        @error('player_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label for="amount" class="form-label">Amount (USD) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control @error('amount') is-invalid @enderror"
                            id="amount" name="amount" value="{{ old('amount') }}" placeholder="0.00" required>
                        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label for="currency" class="form-label">Currency</label>
                        <input type="text" class="form-control @error('currency') is-invalid @enderror"
                            id="currency" name="currency" value="{{ old('currency', 'USD') }}" maxlength="3">
                        @error('currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row g-3 mt-3">
                    <div class="col-md-6">
                        <label for="payment_reference" class="form-label">Payment Reference</label>
                        <input type="text" class="form-control @error('payment_reference') is-invalid @enderror"
                            id="payment_reference" name="payment_reference" value="{{ old('payment_reference') }}" placeholder="e.g., bank transfer #123">
                        @error('payment_reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="notes" class="form-label">Notes</label>
                        <input type="text" class="form-control @error('notes') is-invalid @enderror"
                            id="notes" name="notes" value="{{ old('notes') }}" placeholder="Additional details">
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-club-primary">
                        <i class="fas fa-check me-1"></i> Collect Fee
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>
@endsection