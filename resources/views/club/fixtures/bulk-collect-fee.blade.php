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
            <h6 class="mb-0">Bulk Collect Fee</h6>
        </div>
        <div class="club-card-body padded">
            @php
                $members = $members ?? collect();
                $existingFees = $existingFees ?? collect();
                $existingFeesByPlayer = $existingFeesByPlayer ?? collect();
            @endphp

            @if($existingFees->isNotEmpty())
                <div class="mb-4">
                    <h6 class="mb-3">Already Collected Fees</h6>
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

            <form action="{{ route('club.fixtures.bulk-collect-fee.store', $fixture) }}" method="post" id="bulkFeeForm">
                @csrf
                <input type="hidden" name="collect_all" value="0" id="collectAllInput">
                <input type="hidden" name="all_amount" value="0" id="allAmountInput">
                <div class="mb-3">
                    <label class="form-label">Currency</label>
                    <input type="text" name="currency" value="USD" maxlength="3" class="form-control w-25" required>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="collectAll">
                    <label class="form-check-label" for="collectAll">
                        Collect for all active players (same amount for all)
                    </label>
                </div>

                <div class="table-responsive mb-3">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <th>Player</th>
                                <th>Amount (USD)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($members as $member)
                            @php
                                $playerId = $member->user->id;
                                $playerOldAmount = old("players.$playerId.amount");
                                $hasExistingFee = $existingFeesByPlayer->has($playerId);
                            @endphp
                            <tr>
                                <td>
                                    <input type="checkbox" name="players[{{ $playerId }}][player_id]" value="{{ $playerId }}" class="player-checkbox"
                                        @if($hasExistingFee) checked @disabled(true) @endif>
                                </td>
                                <td class="fw-medium">{{ $member->user->name }}</td>
                                <td>
                                    <input type="number" step="0.01" name="players[{{ $playerId }}][amount]" value="{{ $playerOldAmount ?: '' }}"
                                        class="form-control w-50 all-amount"
                                        @disabled($hasExistingFee)>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="payment_reference" class="form-label">Payment Reference</label>
                        <input type="text" name="payment_reference" id="payment_reference" class="form-control" placeholder="e.g., bank transfer #123">
                    </div>
                    <div class="col-md-6">
                        <label for="notes" class="form-label">Notes</label>
                        <input type="text" name="notes" id="notes" class="form-control" placeholder="Additional details">
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-club-primary">
                        <i class="fas fa-check me-1"></i> Collect Fees
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>
@endsection

@push('script')
<script>
    document.getElementById('selectAll')?.addEventListener('change', function () {
        const checked = this.checked;
        document.querySelectorAll('.player-checkbox').forEach(function (checkbox) {
            if (! checkbox.disabled) {
                checkbox.checked = checked;
            }
        });
    });

    document.getElementById('collectAll')?.addEventListener('change', function () {
        const checked = this.checked;
        const collectAllInput = document.getElementById('collectAllInput');
        const allAmountInput = document.getElementById('allAmountInput');
        const amountInputs = document.querySelectorAll('.all-amount');

        collectAllInput.value = checked ? '1' : '0';

        if (checked) {
            const defaultAmount = '100';
            allAmountInput.value = defaultAmount;
            amountInputs.forEach(function (input) {
                if (! input.disabled) {
                    input.value = defaultAmount;
                }
            });
        }

        document.querySelectorAll('.player-checkbox').forEach(function (checkbox) {
            if (! checkbox.disabled) {
                checkbox.checked = checked;
            }
        });
    });

    document.querySelectorAll('.player-checkbox').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            const collectAll = document.getElementById('collectAll')?.checked;
            if (this.checked && ! collectAll) {
                const row = this.closest('tr');
                const amountInput = row.querySelector('input[name$="[amount]"]');
                if (amountInput && (amountInput.value === '' || amountInput.value === '0')) {
                    amountInput.value = '1';
                }
            }
        });
    });

    document.getElementById('bulkFeeForm')?.addEventListener('submit', function (e) {
        const checkedPlayers = document.querySelectorAll('.player-checkbox:checked');
        const collectAll = document.getElementById('collectAll')?.checked;
        if (checkedPlayers.length === 0 && ! collectAll) {
            e.preventDefault();
            Swal.fire({
                title: 'No players selected',
                text: 'Please select at least one player to collect fees from.',
                icon: 'warning',
            });
        }
    });
</script>
@endpush