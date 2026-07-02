@php
    $club = $club ?? auth()->user()?->ownedClub;
    $menu = config('club_panel.menu', []);
@endphp

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<button class="sidebar-toggle-mobile" type="button" aria-label="Toggle menu">
    <i class="fas fa-bars"></i>
</button>

<aside id="sidebar" class="club-sidebar">
    <div class="sidebar-inner">
        <div class="sidebar-brand">
            <div class="d-flex align-items-center justify-content-between gap-2">
                <a href="{{ route('club.dashboard') }}" class="logo-container text-decoration-none">
                    <div class="logo-icon-club"><i class="fas fa-trophy"></i></div>
                    <div class="logo-text-container">
                        <span class="club-brand-text sidebar-text">Cricket OS</span>
                        <span class="club-panel-badge sidebar-text">Club Panel</span>
                    </div>
                </a>
                <button class="btn btn-sm btn-light border collapse-btn d-none d-lg-inline-flex" id="sidebarCollapse" type="button" aria-label="Collapse sidebar">
                    <i class="fas fa-chevron-left"></i>
                </button>
            </div>
        </div>

        @if($club)
            <div class="club-profile-card sidebar-text">
                <div class="d-flex align-items-center gap-3">
                    @if($club->logo_url)
                        <img src="{{ $club->logo_url }}" alt="{{ $club->name }}" class="club-avatar">
                    @else
                        <div class="club-avatar-fallback">{{ strtoupper(substr($club->name, 0, 1)) }}</div>
                    @endif
                    <div class="overflow-hidden min-w-0">
                        <div class="fw-semibold text-truncate" style="font-size:0.9rem;color:var(--club-navy);">{{ $club->name }}</div>
                        @if($club->is_verified)
                            <span class="club-badge success mt-1"><i class="fas fa-check-circle me-1"></i>Verified</span>
                        @else
                            <span class="text-muted" style="font-size:0.75rem;">Your club</span>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <nav class="club-sidebar-nav">
            @foreach($menu as $section)
                <div class="nav-section-label sidebar-text">{{ $section['section'] }}</div>
                @foreach($section['items'] as $item)
                    @php
                        $activePattern = $item['active'] ?? $item['route'];
                        $isActive = request()->routeIs($activePattern);
                    @endphp
                    <a href="{{ route($item['route']) }}"
                       class="nav-link {{ $isActive ? 'active' : '' }}">
                        <span class="nav-icon"><i class="{{ $item['icon'] }}"></i></span>
                        <span class="sidebar-text">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            @endforeach
        </nav>

        <div class="club-sidebar-footer">
            <form action="{{ route('club.logout') }}" method="POST">
                @csrf
                <button type="submit" class="club-logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="sidebar-text">Logout</span>
                </button>
            </form>
        </div>
    </div>
</aside>
