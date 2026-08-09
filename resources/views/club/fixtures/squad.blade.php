@extends('club.layouts.master')

@section('title', $title)

@section('content')
<main class="club-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="min-w-0">
            <p class="text-muted small mb-1">Squad Assignment</p>
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

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="club-card">
        <div class="club-card-header border-bottom-0 pb-0">
            <ul class="nav nav-tabs" id="squadTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="club-squad-tab" data-bs-toggle="tab" data-bs-target="#club-squad" type="button" role="tab">Club Squad</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="opponent-squad-tab" data-bs-toggle="tab" data-bs-target="#opponent-squad" type="button" role="tab">Opponent Squad</button>
                </li>
            </ul>
        </div>
        <div class="club-card-body p-0">
            <div class="tab-content" id="squadTabsContent">
                <!-- Club Squad Tab -->
                <div class="tab-pane fade show active p-3" id="club-squad" role="tabpanel">
                    @if(!$team)
                        <div class="alert alert-light border mb-0">
                            <i class="fas fa-info-circle me-1 text-muted"></i>
                            This fixture has no club team assigned.
                        </div>
                    @else
                        <form action="{{ route('club.fixtures.squad.club.store', $fixture) }}" method="POST">
                            @csrf
                            <div class="table-responsive mb-3">
                                <table class="table club-fixtures-table mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 40px;">Select</th>
                                            <th>Player</th>
                                            <th>Role</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($members as $member)
                                            @php
                                                $squadMember = $clubSquad->firstWhere('user_id', $member->user_id);
                                                $isSelected = $squadMember !== null;
                                                $currentRole = $squadMember ? $squadMember->role : 'batsman';
                                                
                                                // Handle the case where currentRole is 'wicket_keeper' but options expect 'wicketkeeper'
                                                if ($currentRole === 'wicket_keeper') $currentRole = 'wicketkeeper';
                                            @endphp
                                            <tr>
                                                <td>
                                                    <input class="form-check-input player-checkbox" type="checkbox" name="players[{{ $loop->index }}][player_id]" value="{{ $member->user_id }}" @checked($isSelected) id="player_{{ $member->user_id }}">
                                                </td>
                                                <td>
                                                    <label class="form-check-label d-block cursor-pointer" for="player_{{ $member->user_id }}">
                                                        <div class="fw-medium">{{ $member->user?->full_name ?: $member->user?->email }}</div>
                                                    </label>
                                                </td>
                                                <td>
                                                    <select name="players[{{ $loop->index }}][role]" class="form-select form-select-sm player-role">
                                                        @foreach($roles as $val => $label)
                                                            <option value="{{ $val }}" @selected($currentRole === $val)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-club-primary">Save Club Squad</button>
                            </div>
                        </form>
                    @endif
                </div>

                <!-- Opponent Squad Tab -->
                <div class="tab-pane fade p-3" id="opponent-squad" role="tabpanel">
                    <form action="{{ route('club.fixtures.squad.opponent.store', $fixture) }}" method="POST">
                        @csrf
                        <div class="table-responsive mb-3">
                            <table class="table club-fixtures-table mb-0" id="opponent-table">
                                <thead>
                                    <tr>
                                        <th>Player Name</th>
                                        <th>Role</th>
                                        <th style="width: 80px;" class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="opponent-tbody">
                                    @forelse($opponentSquad as $index => $player)
                                        @php
                                            $playerRole = $player['role'] ?? 'batsman';
                                            if ($playerRole === 'wicket_keeper') $playerRole = 'wicketkeeper';
                                        @endphp
                                        <tr class="opponent-row">
                                            <td>
                                                <input type="text" name="players[{{ $index }}][name]" class="form-control form-control-sm" value="{{ $player['name'] }}" required placeholder="Player Name">
                                            </td>
                                            <td>
                                                <select name="players[{{ $index }}][role]" class="form-select form-select-sm">
                                                    @foreach($roles as $val => $label)
                                                        <option value="{{ $val }}" @selected($playerRole === $val)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-light border text-danger remove-opponent-btn"><i class="fas fa-trash"></i></button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="opponent-row">
                                            <td>
                                                <input type="text" name="players[0][name]" class="form-control form-control-sm" required placeholder="Player Name">
                                            </td>
                                            <td>
                                                <select name="players[0][role]" class="form-select form-select-sm">
                                                    @foreach($roles as $val => $label)
                                                        <option value="{{ $val }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-light border text-danger remove-opponent-btn" disabled><i class="fas fa-trash"></i></button>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-sm btn-light border" id="add-opponent-btn">
                                <i class="fas fa-plus me-1"></i> Add Player
                            </button>
                            <button type="submit" class="btn btn-club-primary">Save Opponent Squad</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tbody = document.getElementById('opponent-tbody');
    const addBtn = document.getElementById('add-opponent-btn');
    let rowCount = document.querySelectorAll('.opponent-row').length;

    // Roles for cloning
    const rolesHtml = `@foreach($roles as $val => $label)<option value="{{ $val }}">{{ $label }}</option>@endforeach`;

    if (addBtn) {
        addBtn.addEventListener('click', function () {
            const tr = document.createElement('tr');
            tr.className = 'opponent-row';
            tr.innerHTML = `
                <td>
                    <input type="text" name="players[${rowCount}][name]" class="form-control form-control-sm" required placeholder="Player Name">
                </td>
                <td>
                    <select name="players[${rowCount}][role]" class="form-select form-select-sm">
                        ${rolesHtml}
                    </select>
                </td>
                <td class="text-end">
                    <button type="button" class="btn btn-sm btn-light border text-danger remove-opponent-btn"><i class="fas fa-trash"></i></button>
                </td>
            `;
            tbody.appendChild(tr);
            rowCount++;
            updateRemoveButtons();
        });
    }

    if (tbody) {
        tbody.addEventListener('click', function (e) {
            if (e.target.closest('.remove-opponent-btn')) {
                const row = e.target.closest('tr');
                if (document.querySelectorAll('.opponent-row').length > 1) {
                    row.remove();
                    updateRemoveButtons();
                }
            }
        });
    }

    function updateRemoveButtons() {
        const rows = document.querySelectorAll('.opponent-row');
        const btns = document.querySelectorAll('.remove-opponent-btn');
        if (rows.length <= 1) {
            btns.forEach(btn => btn.disabled = true);
        } else {
            btns.forEach(btn => btn.disabled = false);
        }
    }
    
    updateRemoveButtons();

    // Disable role select if checkbox is unchecked (Club Squad)
    const clubForm = document.querySelector('#club-squad form');
    if (clubForm) {
        clubForm.addEventListener('submit', function (e) {
            // Remove unselected players so they aren't submitted
            const checkboxes = clubForm.querySelectorAll('.player-checkbox');
            checkboxes.forEach(cb => {
                if (!cb.checked) {
                    const row = cb.closest('tr');
                    const select = row.querySelector('.player-role');
                    if (select) {
                        select.disabled = true; // Prevent submission
                    }
                }
            });
        });
    }
});
</script>
@endsection
