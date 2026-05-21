<div class="page">

    {{-- SIDEBAR --}}
    <aside class="navbar navbar-vertical navbar-expand-lg">
        <div class="container-fluid">

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu">
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

                    <li class="nav-item {{ request()->is('content') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('content.user.index') }}">
                            <span class="nav-link-icon"><i class="ti ti-list-details"></i></span>
                            <span class="nav-link-title">All Content</span>
                        </a>
                    </li>

                    <li class="nav-item dropdown {{ request()->is('learning*') ? 'active' : '' }}">
                        <a class="nav-link dropdown-toggle" href="#sidebar-learning" data-bs-toggle="dropdown">
                            <span class="nav-link-icon"><i class="ti ti-book-2"></i></span>
                            <span class="nav-link-title">My Learning</span>

                            @if ($mylearningongoing >= 1)
                                <span class="badge bg-red ms-2 d-none">
                                    {{ $mylearningongoing }}
                                </span>
                            @endif
                        </a>

                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="{{ url('learning/on-going') }}">
                                <i class="ti ti-player-play me-2"></i> Learning On Going
                            </a>
                            <a class="dropdown-item" href="{{ url('learning/record') }}">
                                <i class="ti ti-history me-2"></i> Learning Record
                            </a>
                            <a class="dropdown-item" href="{{ url('learning/assesment') }}">
                                <i class="ti ti-checkup-list me-2"></i> My Assessment
                            </a>
                        </div>
                    </li>
                    <li class="nav-item dropdown {{ request()->is('content/category*') ? 'active' : '' }}">
                        <a class="nav-link dropdown-toggle" href="#sidebar-category" data-bs-toggle="dropdown">
                            <span class="nav-link-icon"><i class="ti ti-category-2"></i></span>
                            <span class="nav-link-title">Category</span>
                        </a>

                        <div class="dropdown-menu">
                            @forelse($category as $cat)
                                <a class="dropdown-item {{ empty($cat->slug) ? 'disabled' : '' }}"
                                   href="{{ $cat->slug ? route('content.user.category', $cat->slug) : '#' }}">
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

                    {{-- ADMIN & SUPER ADMIN --}}
                    @if (in_array(auth()->user()->role, ['2', '3']))
                        <li class="nav-item dropdown {{ request()->is('manage*') ? 'active' : '' }}">
                            <a class="nav-link dropdown-toggle" href="#sidebar-manage" data-bs-toggle="dropdown">
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
                                <a class="dropdown-item" href="{{ url('manage/trainingplan') }}"><i class="ti ti-list-check me-2"></i> Playlist</a>
                                <a class="dropdown-item" href="{{ url('manage/eventphoto') }}"><i class="ti ti-camera me-2"></i> Event Gallery</a>
                            </div>
                        </li>
                    @endif

                </ul>
            </div>
        </div>
    </aside>

    {{-- PAGE WRAPPER --}}
    <div class="page-wrapper">

        {{-- TOP NAVBAR --}}
        <header class="navbar navbar-expand-md d-print-none">
            <div class="container-xl">
                <div class="navbar-nav flex-row ms-auto">

                    {{-- DARK MODE --}}
                    <div class="d-none d-md-flex me-3">
                        <a href="?theme=dark" class="nav-link px-0 hide-theme-dark" title="Enable dark mode">
                            <i class="ti ti-moon"></i>
                        </a>

                        <a href="?theme=light" class="nav-link px-0 hide-theme-light" title="Enable light mode">
                            <i class="ti ti-sun"></i>
                        </a>
                    </div>

                    {{-- PROFILE --}}
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open user menu">

                            @if (!empty(auth()->user()->avatar))
                                <span class="avatar avatar-sm"
                                      style="background-image: url({{ asset('storage/profile') }}/{{ auth()->user()->avatar }})">
                                </span>
                            @else
                                <span class="avatar avatar-sm avatar-default-icon">
                                    <i class="ti ti-user"></i>
                                </span>
                            @endif

                            <div class="ps-2">
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
                                <i class="ti ti-user me-2"></i> Profile
                            </a>

                            <form action="{{ route('logoutpage') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <i class="ti ti-logout me-2"></i> Logout
                                </button>
                            </form>
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