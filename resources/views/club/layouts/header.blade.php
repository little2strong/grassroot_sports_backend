@php
    $club = auth()->user()?->ownedClub;
    $user = auth()->user();
    $displayName = $user->first_name ?: 'Club Owner';
    $initial = strtoupper(substr($user->first_name ?? $user->email, 0, 1));
@endphp

<header class="club-topbar">
    <div class="d-flex align-items-center justify-content-between gap-2 gap-md-3">
        <div class="min-w-0">
            <h1 class="club-topbar-title text-truncate">@yield('title', 'Dashboard')</h1>
            <p class="club-topbar-sub d-none d-sm-block">
                Welcome back, {{ $displayName }}
            </p>
        </div>

        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            @if($club)
                <div class="club-name-pill d-none d-md-inline-flex">
                    <i class="fas fa-shield-alt"></i>
                    <span>{{ $club->name }}</span>
                </div>
            @endif

            <div class="dropdown">
                <button class="club-user-btn dropdown-toggle border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="club-user-avatar">{{ $initial }}</div>
                    <span class="d-none d-lg-inline text-truncate" style="max-width:120px;font-size:0.85rem;font-weight:600;color:var(--club-navy);">
                        {{ $displayName }}
                    </span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" style="border-radius:12px;min-width:200px;">
                    <li class="px-3 py-2">
                        <div class="fw-semibold small">{{ $displayName }}</div>
                        <div class="text-muted" style="font-size:0.78rem;">{{ $user->email }}</div>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <form action="{{ route('club.logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger rounded-2 mx-1" style="width:calc(100% - 8px);">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>
