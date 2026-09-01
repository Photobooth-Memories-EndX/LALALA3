<div class="page">

    {{-- SIDEBAR --}}
    <aside class="navbar navbar-vertical navbar-expand-lg">
        <div class="container-fluid">

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <h1 class="navbar-brand navbar-brand-autodark owl-sidebar-brand">
                <a href="{{ url('/') }}">
                    <img src="{{ asset('owl.png') }}" width="164" height="97" alt="OWL Learning Hub" class="navbar-brand-image owl-sidebar-logo">
                </a>
            </h1>

            <div class="collapse navbar-collapse" id="sidebar-menu">
                <ul class="navbar-nav owl-sidebar-nav">

                    <li class="nav-item {{ request()->is('/') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ url('/') }}">
                            <span class="nav-link-icon"><i class="ti ti-home"></i></span>
                            <span class="nav-link-title">Home</span>
                        </a>
                    </li>

                    <li class="nav-item {{ request()->is('content') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('content.user.index') }}">
                            <span class="nav-link-icon"><i class="ti ti-list-details"></i></span>
                            <span class="nav-link-title">All Content</span>
                        </a>
                    </li>

                    <li class="nav-item {{ request()->routeIs('user.learning.dashboard') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('user.learning.dashboard') }}">
                            <span class="nav-link-icon"><i class="ti ti-book-2"></i></span>
                            <span class="nav-link-title">My Learning</span>

                            @if ($mylearningongoing >= 1)
                                <span class="badge bg-red ms-2 d-none">
                                    {{ $mylearningongoing }}
                                </span>
                            @endif
                        </a>
                    </li>
                    <li class="nav-item dropdown {{ request()->is('content*') ? 'active' : '' }}">
                                <a class="nav-link dropdown-toggle"
                                    href="#"
                                    data-bs-toggle="dropdown"
                                    data-bs-auto-close="outside"
                                    role="button"
                                    aria-expanded="false">
                            <span class="nav-link-icon"><i class="ti ti-category-2"></i></span>
                            <span class="nav-link-title">Category</span>
                        </a>

                        <div class="dropdown-menu">
                            @forelse($category as $cat)
                                <a class="dropdown-item {{ empty($cat->slug) ? 'disabled' : '' }}"
                                   href="{{ $cat->slug ? route('content.user.index', ['classification' => $cat->id]) : '#' }}">
                                    <i class="ti ti-folder me-2"></i>
                                    {{ $cat->classification_name }}
                                </a>
                            @empty
                                <span class="dropdown-item">Tidak ada category</span>
                            @endforelse
                        </div>
                    </li>

                    <li class="nav-item {{ request()->is('playlist*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ url('/playlist') }}">
                            <span class="nav-link-icon"><i class="ti ti-playlist"></i></span>
                            <span class="nav-link-title">Playlist</span>
                        </a>
                    </li>

                    <li class="nav-item {{ request()->is('news*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ url('/news') }}">
                            <span class="nav-link-icon"><i class="ti ti-news"></i></span>
                            <span class="nav-link-title">News</span>
                        </a>
                    </li>

                    <li class="nav-item {{ request()->is('gallery*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ url('/gallery') }}">
                            <span class="nav-link-icon"><i class="ti ti-photo-heart"></i></span>
                            <span class="nav-link-title">Gallery</span>
                        </a>
                    </li>

                    {{-- MANAGE LMS: Admin Training (2) & Super Admin (3) --}}
                    @if (auth()->user()->canManageLms())
                        <li class="nav-item dropdown {{ request()->is('manage*') && !request()->routeIs('manage.role.*') ? 'active' : '' }}">
                                     <a class="nav-link dropdown-toggle"
                                         href="#"
                                         data-bs-toggle="dropdown"
                                         data-bs-auto-close="outside"
                                         role="button"
                                         aria-expanded="false">
                                <span class="nav-link-icon"><i class="ti ti-settings"></i></span>
                                <span class="nav-link-title">Manage</span>
                            </a>

                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="{{ url('manage/learning') }}"><i class="ti ti-school me-2"></i> Learning</a>
                                <a class="dropdown-item" href="{{ url('manage/user') }}"><i class="ti ti-users me-2"></i> User</a>
                                <a class="dropdown-item" href="{{ url('manage/admin') }}"><i class="ti ti-user-shield me-2"></i> Admin</a>
                                <a class="dropdown-item" href="{{ url('manage/pages') }}"><i class="ti ti-file-text me-2"></i> Page</a>
                                <a class="dropdown-item" href="{{ url('manage/news') }}"><i class="ti ti-news me-2"></i> News</a>
                                <a class="dropdown-item" href="{{ url('manage/content') }}"><i class="ti ti-archive me-2"></i> Content</a>
                                <a class="dropdown-item" href="{{ url('manage/classification') }}"><i class="ti ti-category me-2"></i> Category</a>
                                <a class="dropdown-item" href="{{ url('manage/assesment') }}"><i class="ti ti-clipboard-check me-2"></i> Assesment</a>
                                <a class="dropdown-item" href="{{ url('manage/question-bank') }}"><i class="ti ti-help-circle me-2"></i> Question Bank</a>
                                <a class="dropdown-item" href="{{ url('manage/playlist') }}"><i class="ti ti-list-check me-2"></i> Playlist</a>
                                <a class="dropdown-item" href="{{ url('manage/eventphoto') }}"><i class="ti ti-camera me-2"></i> Event Gallery</a>
                                @if (auth()->user()->canAccessManageModule('learning_hours_management'))
                                    <div class="dropdown-divider"></div>
                                    <div class="dropdown-header">Learning Hours</div>
                                    <a class="dropdown-item {{ request()->routeIs('manage.learning-hours.content-user.*') ? 'active' : '' }}" href="{{ route('manage.learning-hours.content-user.index') }}">
                                        <i class="ti ti-file-text me-2"></i> Content User
                                    </a>
                                    <a class="dropdown-item {{ request()->routeIs('manage.learning-hours.playlist-content-user.*') ? 'active' : '' }}" href="{{ route('manage.learning-hours.playlist-content-user.index') }}">
                                        <i class="ti ti-playlist me-2"></i> Playlist Content User
                                    </a>
                                @endif
                            </div>
                        </li>
                    @endif

                    {{-- MANAGE ROLE: hanya Super Admin (3) --}}
                    @if (auth()->user()->canManageRole())
                        <li class="nav-item {{ request()->routeIs('manage.role.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('manage.role.index') }}">
                                <span class="nav-link-icon"><i class="ti ti-shield-lock"></i></span>
                                <span class="nav-link-title">Manage Role</span>
                            </a>
                        </li>
                    @endif

                    {{-- MONITORING: Admin Training (2), Super Admin (3), Subadmin Division (4) --}}
                    @if (auth()->user()->canMonitor())
                        <li class="nav-item {{ request()->routeIs('monitoring.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('monitoring.index') }}">
                                <span class="nav-link-icon"><i class="ti ti-chart-bar"></i></span>
                                <span class="nav-link-title">
                                    {{ auth()->user()->isSubadminDivision() ? 'Monitoring Division' : 'Monitoring' }}
                                </span>
                            </a>
                        </li>
                    @endif

                </ul>
            </div>
        </div>
    </aside>

    {{-- PAGE WRAPPER --}}
    <div class="page-wrapper">

        {{-- TOP NAVBAR --}}
        <header class="navbar navbar-expand-md d-print-none app-topbar">
            <div class="container-xl">
                {{-- DESKTOP (>= 992px) --}}
                <div class="d-none d-lg-flex align-items-center justify-content-end w-100 py-1">
                    {{-- DARK MODE --}}
                    <div class="me-3">
                        <a href="?theme=dark" class="nav-link px-0 hide-theme-dark" title="Enable dark mode">
                            <i class="ti ti-moon fs-2"></i>
                        </a>

                        <a href="?theme=light" class="nav-link px-0 hide-theme-light" title="Enable light mode">
                            <i class="ti ti-sun fs-2"></i>
                        </a>
                    </div>

                    {{-- PROFILE --}}
                    <div class="nav-item dropdown">
                        <a href="#"
                           class="nav-link d-flex lh-1 text-reset p-0"
                           data-bs-toggle="dropdown"
                           data-bs-auto-close="outside"
                           role="button"
                           aria-expanded="false"
                           aria-label="Open user menu">

                            @if (!empty(auth()->user()->avatar))
                                <span class="avatar avatar-sm"
                                      style="background-image: url({{ asset('storage/profile') }}/{{ auth()->user()->avatar }})">
                                </span>
                            @else
                                <span class="avatar avatar-sm avatar-default-icon">
                                    <i class="ti ti-user"></i>
                                </span>
                            @endif

                            <div class="user-header-details ps-2">
                                <div class="user-header-name">
                                    {{ ucwords(strtolower(auth()->user()->name ?? '')) . " (" . auth()->user()->id_emp . ")" }}
                                </div>
                                <div class="user-header-team mt-1 small text-muted">
                                    {{ auth()->user()->team ?? '' }}
                                </div>
                            </div>
                        </a>

                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                            <a href="{{ route('user.profile') }}" class="dropdown-item">
                                <i class="ti ti-user me-2"></i> Profile
                            </a>

                            <form action="{{ route('logoutpage') }}" method="POST" class="m-0 p-0">
                                @csrf
                                <button type="submit" class="dropdown-item" data-logout-trigger="true">
                                    <i class="ti ti-logout me-2"></i> Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- MOBILE (< 992px) USER ROW --}}
                <div class="d-lg-none w-100 py-1">
                    <div class="owl-mobile-user-row d-flex align-items-center justify-content-between py-1 border-bottom mb-2">
                        <a href="{{ route('user.profile') }}" class="d-flex align-items-center text-decoration-none text-reset min-w-0 flex-grow-1 me-2">
                            @if (!empty(auth()->user()->avatar))
                                <span class="avatar owl-mobile-avatar rounded-circle flex-shrink-0"
                                      style="background-image: url({{ asset('storage/profile') }}/{{ auth()->user()->avatar }})">
                                </span>
                            @else
                                <span class="avatar owl-mobile-avatar rounded-circle avatar-default-icon flex-shrink-0">
                                    <i class="ti ti-user"></i>
                                </span>
                            @endif

                            <div class="ps-2 ms-1 min-w-0 flex-grow-1">
                                <div class="fw-bold text-truncate owl-mobile-user-name" style="font-size: 0.9rem; line-height: 1.2;">
                                    {{ ucwords(strtolower(auth()->user()->name ?? '')) }}
                                </div>
                                <div class="text-muted text-truncate owl-mobile-user-team" style="font-size: 0.775rem; line-height: 1.2;">
                                    {{ auth()->user()->team ?? (auth()->user()->division ?? 'Training Team') }}
                                </div>
                            </div>
                        </a>

                        {{-- Dark Mode Toggle --}}
                        <div class="nav-item flex-shrink-0 ms-auto">
                            <a href="?theme=dark" class="nav-link px-2 hide-theme-dark text-muted" title="Enable dark mode">
                                <i class="ti ti-moon fs-2"></i>
                            </a>
                            <a href="?theme=light" class="nav-link px-2 hide-theme-light text-muted" title="Enable light mode">
                                <i class="ti ti-sun fs-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        {{-- SEARCH --}}
        <div class="page-header d-print-none">
            <div class="container-xl">
                <form action="{{ route('search', 'all') }}" method="get" autocomplete="off" novalidate>
                    <div class="input-icon">
                        <span class="input-icon-addon">
                            <i class="ti ti-search"></i>
                        </span>
                        <input type="text" name="s" class="form-control" placeholder="Search..." aria-label="Search in website">
                    </div>
                </form>
            </div>
        </div>

        {{-- CONTENT --}}
        <div class="page-body">
            <div class="container-xl">
                @yield('content')
            </div>
        </div>

    </div>
</div>

@include('partials.logout-modal')
