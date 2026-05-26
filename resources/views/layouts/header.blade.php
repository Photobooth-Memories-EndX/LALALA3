@php
    $category = isset($category) && is_iterable($category) ? $category : collect();
    $mylearningongoing = $mylearningongoing ?? 0;
    $darkThemeUrl = request()->fullUrlWithQuery(['theme' => 'dark']);
    $lightThemeUrl = request()->fullUrlWithQuery(['theme' => 'light']);
@endphp

<aside class="navbar navbar-vertical navbar-expand-lg d-print-none">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <h1 class="navbar-brand navbar-brand-autodark">
            <a href="{{ url('/') }}">
                <img src="{{ asset('owl.png') }}" width="110" height="32" alt="OWL LMS" class="navbar-brand-image">
            </a>
        </h1>

        <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">
                <li class="nav-item {{ request()->is('/') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ url('/') }}">
                        <span class="nav-link-icon"><i class="ti ti-home"></i></span>
                        <span class="nav-link-title">Home</span>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('user.learning.dashboard') || request()->is('learning*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('user.learning.dashboard') }}">
                        <span class="nav-link-icon"><i class="ti ti-book-2"></i></span>
                        <span class="nav-link-title">My Learning</span>

                        @if ($mylearningongoing >= 1)
                            <span class="badge bg-red ms-auto">{{ $mylearningongoing }}</span>
                        @endif
                    </a>
                </li>

                <li class="nav-item {{ request()->is('content*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('content.user.index') }}">
                        <span class="nav-link-icon">
                            <i class="ti ti-category-2"></i>
                        </span>
                        <span class="nav-link-title">
                            All Content
                        </span>
                    </a>
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

                @if (in_array(auth()->user()->role, ['2', '3']))
                    <li class="nav-item dropdown {{ request()->is('manage*') ? 'active' : '' }}">
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
                            <a class="dropdown-item" href="{{ url('manage/pages') }}"><i class="ti ti-file-text me-2"></i> Pages</a>
                            <a class="dropdown-item" href="{{ url('manage/news') }}"><i class="ti ti-news me-2"></i> News</a>
                            <a class="dropdown-item" href="{{ url('manage/content') }}"><i class="ti ti-archive me-2"></i> Content</a>
                            <a class="dropdown-item" href="{{ url('manage/classification') }}"><i class="ti ti-category me-2"></i> Category</a>
                            <a class="dropdown-item" href="{{ url('manage/assesment') }}"><i class="ti ti-clipboard-check me-2"></i> Assessment</a>
                            <a class="dropdown-item" href="{{ url('manage/question-bank') }}"><i class="ti ti-help-circle me-2"></i> Question Bank</a>
                            <a class="dropdown-item" href="{{ url('manage/trainingplan') }}"><i class="ti ti-list-check me-2"></i> Playlist</a>
                            <a class="dropdown-item" href="{{ url('manage/eventphoto') }}"><i class="ti ti-camera me-2"></i> Event Gallery</a>
                        </div>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</aside>

<header class="navbar navbar-expand-md d-print-none app-topbar">
    <div class="container-fluid">

        {{-- MOBILE SIDEBAR TOGGLE --}}
        <button class="navbar-toggler me-2"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#sidebar-menu"
                aria-controls="sidebar-menu"
                aria-expanded="false"
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        {{-- SEARCH DESKTOP ONLY --}}
        <div class="topbar-search d-none d-md-flex flex-grow-1">
            <form action="{{ route('search', ['searchtype' => 'all']) }}"
                  method="get"
                  autocomplete="off"
                  novalidate
                  class="w-100">

                <div class="input-icon w-100">
                    <span class="input-icon-addon">
                        <i class="ti ti-search"></i>
                    </span>

                    <input type="text"
                           name="s"
                           class="form-control"
                           placeholder="Search learning, content, news..."
                           aria-label="Search in website">
                </div>
            </form>
        </div>

        {{-- RIGHT MENU --}}
        <div class="navbar-nav flex-row align-items-center ms-auto">

            {{-- DARK MODE --}}
            <div class="nav-item d-none d-md-flex me-3">
                <a href="{{ $darkThemeUrl }}"
                   class="nav-link px-0 hide-theme-dark"
                   title="Enable dark mode"
                   data-bs-toggle="tooltip"
                   data-bs-placement="bottom">
                    <i class="ti ti-moon fs-2"></i>
                </a>

                <a href="{{ $lightThemeUrl }}"
                   class="nav-link px-0 hide-theme-light"
                   title="Enable light mode"
                   data-bs-toggle="tooltip"
                   data-bs-placement="bottom">
                    <i class="ti ti-sun fs-2"></i>
                </a>
            </div>

            {{-- NOTIFICATION BELL --}}
            <div class="nav-item dropdown me-3">
                <a href="#"
                   class="nav-link px-0 position-relative"
                   data-bs-toggle="dropdown"
                   role="button"
                   aria-expanded="false"
                   aria-label="Open notifications">

                    <i class="ti ti-bell fs-2"></i>

                    @if (($mylearningongoing ?? 0) >= 1)
                        <span class="badge bg-red badge-notification">
                            {{ $mylearningongoing }}
                        </span>
                    @endif
                </a>

                <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <div class="dropdown-header">
                        Notifications
                    </div>

                    @if (($mylearningongoing ?? 0) >= 1)
                        <a href="{{ route('user.learning.dashboard') }}" class="dropdown-item">
                            <i class="ti ti-book-2 me-2"></i>
                            You have {{ $mylearningongoing }} ongoing learning
                        </a>
                    @else
                        <span class="dropdown-item text-muted">
                            <i class="ti ti-bell-off me-2"></i>
                            No notifications
                        </span>
                    @endif
                </div>
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
                            style="background-image: url('{{ asset('storage/profile/' . auth()->user()->avatar) }}')">
                        </span>
                    @else
                        <span class="avatar avatar-sm avatar-default-icon">
                            <i class="ti ti-user"></i>
                        </span>
                    @endif

                    <div class="d-none d-xl-block ps-2">
                        <div>
                            {{ ucwords(strtolower(auth()->user()->name ?? '')) . " (" . auth()->user()->id_emp . ")" }}
                        </div>
                        <div class="mt-1 small text-muted" style="font-size: 7pt">
                            {{ auth()->user()->team ?? '' }}
                        </div>
                    </div>
                </a>

                <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <a href="{{ route('user.profile') }}" class="dropdown-item">
                        <i class="ti ti-user me-2"></i>
                        Profile
                    </a>

                    <form action="{{ route('logoutpage') }}" method="POST">
                        @csrf
                        <button type="submit" class="dropdown-item">
                            <i class="ti ti-logout me-2"></i>
                            Logout
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</header>
