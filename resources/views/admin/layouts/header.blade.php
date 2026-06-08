<header class="top-bar">
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <!-- Collapse Button -->
                <button class="sidebar-toggle-top d-none d-lg-block me-3">
                    <i class="fas fa-chevron-left"></i>
                </button>

                <div class="admin-header-text">
                    <h1 class="page-title fs-5 mb-0">@yield('title')</h1>
                    <p class="page-subtitle mb-0">Admin <span>Dashboard</span></p>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <!-- Search Box -->
                {{-- <div class="search-box d-none d-md-block">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" placeholder="Search..." style="width: 250px;">
                </div> --}}

                <!-- Notifications -->
                {{-- <button class="btn btn-light position-relative">
                    <i class="fas fa-bell"></i>
                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                        <span class="visually-hidden">New alerts</span>
                    </span>
                </button> --}}

                <!-- User Profile -->
                <div class="dropdown">
                    <button class="btn btn-light py-0 border-0 bg-transparent d-flex align-items-center gap-2 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        {{-- @php
                            $split = splitSiteName(auth()->guard('admin')->user()->name);
                            $twoLetter = $split['two_letter'];
                        @endphp --}}
                        <div class="user-avatar">LC</div>
                        <div class="d-none d-lg-block">
                            <p class="text-start mb-0 fw-medium">{{ auth()->guard('admin')->user()->name }}</p>
                            <p class="mb-0 text-muted small">{{ auth()->guard('admin')->user()->email }}</p>
                        </div>
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                        <li>
                            <a class="dropdown-item pe-none" href="#">
                                <span class="fw-semibold">{{ auth()->guard('admin')->user()->email }} </span>
                                <br>
                                <small>{{ auth()->guard('admin')->user()->name }}</small>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="">
                                <i class="fa-solid fa-user me-1"></i> Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="">
                                <i class="fa-solid fa-gear me-1"></i> Settings
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="{{ route('admin.logout') }}"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Logout
                            </a>
                            <form class="logout" id="logout-form" action="{{ route('admin.logout') }}" method="POST">
                                @csrf
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</header>
