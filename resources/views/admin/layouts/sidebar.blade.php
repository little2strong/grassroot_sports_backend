<!-- Sidebar Overlay for Mobile -->
<div class="sidebar-overlay"></div>

<!-- Mobile Toggle Button -->
<button class="sidebar-toggle-mobile d-lg-none">
    <i class="fas fa-bars"></i>
</button>

<!-- Sidebar -->
<aside id="sidebar">
    <div class="d-flex flex-column h-100">
        <!-- Logo -->
        <div class="p-3 border-bottom">

            <div class="d-flex align-items-center justify-content-between">
                <a href="{{ route('admin.dashboard') }}" class="logo-container text-decoration-none">
                    <div class="logo-icon">
                        <div class="logo-gradient"></div>
                        <div class="logo-solid mb-1">
                            <span class="logo-letter">L</span>
                        </div>
                    </div>
                    <div class="logo-text-container">
                        <span class="logo-brand mb-1">L</span>

                        <span class="logo-subtitle">C</span>

                    </div>
                </a>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="sidebar-nav flex-grow-1 p-3">
            <div class="nav flex-column">
                {{-- @can('view dashboard') --}}
                    <a class="nav-link @yield('dashboard')" href="{{ route('admin.dashboard') }}">
                        <i class="fas fa-th-large"></i>
                        <span class="sidebar-text">Dashboard</span>
                    </a>
                {{-- @endcan --}}


              

                {{-- @canany(['view clubs', 'view players']) --}}
                    {{-- <div class="sidebar-heading">Management</div> --}}
                {{-- @endcanany --}}

                {{-- @can('view clubs') --}}
                <a class="nav-link @yield('clubs')" href="{{ route('admin.clubs.index') }}">
                    <i class="fas fa-building"></i>
                    <span class="sidebar-text">Clubs</span>
                </a>
                {{-- @endcan --}}

                {{-- @can('view players') --}}
                <a class="nav-link @yield('players')" href="{{ route('admin.players.index') }}">
                    <i class="fas fa-users"></i>
                    <span class="sidebar-text">Players</span>
                </a>
                {{-- @endcan --}}
                {{-- @can('view admins') --}}
                <a class="nav-link @yield(section: 'admin-users')" href="{{ route('admin.admins.index') }}">
                    <i class="fas fa-users"></i>
                    <span class="sidebar-text">Admin Users</span>
                </a>
                {{-- @endcan --}}
                {{-- @can('view settings') --}}
                    <a class="nav-link @yield(section: 'settings')" href="{{ route('admin.settings.index') }}">
                        <i class="fas fa-cog"></i>
                        <span class="sidebar-text">System Settings</span>
                    </a>
                {{-- @endcan --}}
            </div>
        </nav>

        <!-- Logout Button -->
        <div class="p-3 border-top">
            <a href="{{ route('admin.logout') }}" class="logout-btn text-decoration-none w-100 d-block"
                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="fas fa-sign-out-alt me-2"></i>
                <span class="sidebar-text">Logout</span>
            </a>
            <form class="logout" id="logout-form" action="{{ route('admin.logout') }}" method="POST">
                @csrf
            </form>
        </div>
    </div>
</aside>
