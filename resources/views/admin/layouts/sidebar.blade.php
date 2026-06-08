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
                @can('view dashboard')
                    <a class="nav-link @yield('dashboard')" href="{{ route('admin.dashboard') }}">
                        <i class="fas fa-th-large"></i>
                        <span class="sidebar-text">Dashboard</span>
                    </a>
                @endcan
                @can('view users')
                    <a class="nav-link @yield(section: 'users')" href="{{ route('admin.users.index') }}">
                        <i class="fas fa-building"></i>
                        <span class="sidebar-text">Users</span>
                    </a>
                @endcan
                @can('view merchants')
                    <a class="nav-link @yield(section: 'merchants')" href="{{ route('admin.merchants.index') }}">
                        <i class="fa-solid fa-store"></i>
                        <span class="sidebar-text">Merchant</span>
                    </a>
                @endcan
                @can('view agents')
                    <a class="nav-link @yield(section: 'agents')" href="{{ route('admin.agents.index') }}">
                        <i class="fa-solid fa-jug-detergent"></i>
                        <span class="sidebar-text">Agent</span>
                    </a>
                @endcan
                @can('view transactions')
                    <a href="{{ route('admin.transactions.index') }}" class="nav-link @yield(section: 'transactions')">
                        <i class="fa-solid fa-receipt"></i>
                        <span>Transactions</span>
                    </a>
                @endcan
                {{-- <a href="{{route('admin.promotions.index')}}" class="nav-link @yield(section: 'promotions')">
                    <i class="fas fa-bullhorn"></i>
                    <span>Rules & Promotion</span>
                </a>

                <a href="" class="nav-link @yield(section: 'billing')">
                    <i class="fas fa-bullhorn"></i>
                    <span>Analytic</span>
                </a> --}}
                {{-- @if (Auth::user()->can('admin.tenant.view'))
                    <a class="nav-link @yield(section: 'tenants')" href="{{ route('admin.tenant.index') }}">
                        <i class="fas fa-building"></i>
                        <span class="sidebar-text">Users</span>
                    </a>
                @endif

                 @if (Auth::user()->can('admin.request-demo.view'))
                    <a href="{{ route('admin.user-requests.index') }}" class="nav-link @yield(section: 'user-request')">
                        <i class="fa-solid fa-person-circle-question"></i>
                        <span>Request Demo</span>
                    </a>
                @endif

                 @if (Auth::user()->can('admin.plan-request.view'))
                    <a href="{{ route('admin.plan-upgrade-request.index') }}" class="nav-link @yield(section: 'plan-request')">
                        <i class="fa-solid fa-person-arrow-up-from-line"></i>
                        <span>Plan Request</span>
                    </a>
                @endif
                @if (Auth::user()->can('admin.plan.view'))
                    <a class="nav-link @yield(section: 'plans')" href="{{ route('admin.plans.index') }}">
                        <i class="fas fa-credit-card"></i>
                        <span class="sidebar-text">Plans</span>
                    </a>
                @endif

                @if (Auth::user()->can('admin.billing.view'))
                    <a href="{{ route('admin.billing.index') }}" class="nav-link @yield(section: 'billing')">
                        <i class="fa-solid fa-receipt"></i>
                        <span>Billing</span>
                    </a>
                @endif --}}

                {{-- @if (Auth::user()->can('admin.custom-pages.view'))
                    <a class="nav-link @yield(section: 'custom-pages')" href="{{ route('admin.custom-pages.index') }}">
                        <i class="fa-solid fa-file"></i>
                        <span class="sidebar-text">Custom Page</span>
                    </a>
                @endif
                @if (Auth::user()->can('admin.blog.view'))
                    <a class="nav-link @yield(section: 'blog')" href="{{ route('admin.blogs.index') }}">
                        <i class="fa-solid fa-blog"></i>
                        <span class="sidebar-text">Blog</span>
                    </a>
                @endif
                @if (Auth::user()->can('admin.contact.view'))
                    <a class="nav-link @yield(section: 'contacts')" href="{{ route('admin.contact.index') }}">
                        <i class="fas fa-envelope"></i>
                        <span class="sidebar-text">Contacts</span>
                    </a>
                @endif
                @if (Auth::user()->can('admin.faq.view'))
                    <a class="nav-link @yield(section: 'faqs')" href="{{ route('admin.faq.index') }}">
                        <i class="fas fa-question-circle"></i>
                        <span class="sidebar-text">FAQs</span>
                    </a>
                @endif
                @if (Auth::user()->can('admin.testimonial.view'))
                    <a class="nav-link @yield(section: 'testimonial')" href="{{ route('admin.testimonial.index') }}">
                        <i class="fa-solid fa-message"></i>
                        <span class="sidebar-text">Testimonial</span>
                    </a>
                @endif
                @if (Auth::user()->can('admin.user.view'))
                    <a class="nav-link @yield(section: 'users')" href="{{ route('admin.users.index') }}">
                        <i class="fas fa-users"></i>
                        <span class="sidebar-text">Admin Users</span>
                    </a>
                @endif
                @if (Auth::user()->can('admin.settings.view'))
                    <a class="nav-link @yield(section: 'settings')" href="{{ route('admin.settings.index') }}">
                        <i class="fas fa-cog"></i>
                        <span class="sidebar-text">System Settings</span>
                    </a>
                @endif --}}
                @can('view admins')
                    <a class="nav-link @yield(section: 'admin-users')" href="{{ route('admin.admins.index') }}">
                        <i class="fas fa-users"></i>
                        <span class="sidebar-text">Admin Users</span>
                    </a>
                @endcan
                @can('view settings')
                    <a class="nav-link @yield(section: 'settings')" href="{{ route('admin.settings.index') }}">
                        <i class="fas fa-cog"></i>
                        <span class="sidebar-text">System Settings</span>
                    </a>
                @endcan
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
