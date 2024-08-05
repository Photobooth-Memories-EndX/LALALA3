<!-- Navbar -->
<header class="navbar navbar-expand-md d-print-none">
    <div class="container-xl">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu" aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
            <a href="{{ url('/') }}">
                <img src="{{ asset('owl.png') }}" width="110" height="32" alt="Online Knowledge Learning" class="navbar-brand-image">
                <span class="text-warning">O</span>nline Kno<span class="text-warning">W</span>ledge <span class="text-warning">L</span>earning
            </a>
        </h1>
        <div class="navbar-nav flex-row order-md-last">
            <div class="d-none d-md-flex">
                <a href="?theme=dark" class="nav-link px-0 hide-theme-dark" title="Enable dark mode" data-bs-toggle="tooltip" data-bs-placement="bottom">
                    <!-- Download SVG icon from http://tabler-icons.io/i/moon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z" />
                    </svg>
                </a>
                <a href="?theme=light" class="nav-link px-0 hide-theme-light" title="Enable light mode" data-bs-toggle="tooltip" data-bs-placement="bottom">
                    <!-- Download SVG icon from http://tabler-icons.io/i/sun -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" />
                        <path d="M3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7" />
                    </svg>
                </a>

            </div>
            <div class="nav-item dropdown">
                <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open user menu">
                    <span class="avatar avatar-sm" style="background-image: url({{ asset('storage/profile') }}/{{ auth()->user()->avatar ?? 'default.jpg' }})"></span>
                    <div class="ps-2">
                        <div>{{ ucwords(strtolower(auth()->user()->name ?? '')) . " (" . auth()->user()->id_emp . ")" }}</div>
                        <div class="mt-1 small text-muted" style="font-size: 7pt">{{ auth()->user()->team ?? '' }}</div>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                    <a href="{{ route('user.profile') }}" class="dropdown-item">Profile</a>
                    <form action="{{ route('logoutpage') }}" method="POST">
                        @csrf
                        <input type="submit" class="dropdown-item" value="Logout">
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
<header class="navbar-expand-md">
    <div class="collapse navbar-collapse" id="navbar-menu">
        <div class="navbar">
            <div class="container-xl">
                <ul class="navbar-nav">
                    <li class="nav-item {{ request()->is('/') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ url('/') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><!-- Download SVG icon from http://tabler-icons.io/i/home -->
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M5 12l-2 0l9 -9l9 9l-2 0" />
                                    <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" />
                                    <path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" />
                                </svg>
                            </span>
                            <span class="nav-link-title">
                                Home
                            </span>
                        </a>
                    </li>
                    <li class="nav-item {{ request()->is('content') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('content.user.index') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><!-- Download SVG icon from http://tabler-icons.io/i/home -->
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-list-details" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M13 5h8"></path>
                                    <path d="M13 9h5"></path>
                                    <path d="M13 15h8"></path>
                                    <path d="M13 19h5"></path>
                                    <path d="M3 4m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"></path>
                                    <path d="M3 14m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"></path>
                                </svg>
                            </span>
                            <span class="nav-link-title">
                                All Content
                            </span>
                        </a>
                    </li>

                    <li class="nav-item dropdown {{ request()->is('learning*') ? 'active' : '' }}">
                        <a class="nav-link dropdown-toggle" href="#navbar-help" data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button" aria-expanded="false">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M3 19a9 9 0 0 1 9 0a9 9 0 0 1 9 0"></path>
                                    <path d="M3 6a9 9 0 0 1 9 0a9 9 0 0 1 9 0"></path>
                                    <path d="M3 6l0 13"></path>
                                    <path d="M12 6l0 13"></path>
                                    <path d="M21 6l0 13"></path>
                                </svg>
                            </span>
                            <span class="nav-link-title">
                                My Learning @if ($mylearningongoing >= 1)
                                    <span class="badge bg-red"></span>
                                @endif
                            </span>
                        </a>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="{{ url('learning/on-going') }}" rel="noopener">
                                Learning On Going
                                @if ($mylearningongoing >= 1)
                                    <span class="badge bg-red ms-2">{{ $mylearningongoing }}</span>
                                @endif

                            </a>
                            <a class="dropdown-item" href="{{ url('learning/record') }}" rel="noopener">
                                Learning Record
                            </a>
                            <a class="dropdown-item" href="{{ url('learning/assesment') }}" rel="noopener">
                                Assesment Result
                            </a>
                        </div>
                    </li>

                    <li class="nav-item dropdown {{ request()->is('content/category*') ? 'active' : '' }}">
                        <a class="nav-link dropdown-toggle" href="#navbar-help" data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button" aria-expanded="false">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M4 4h6v6h-6z"></path>
                                    <path d="M14 4h6v6h-6z"></path>
                                    <path d="M4 14h6v6h-6z"></path>
                                    <path d="M17 17m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"></path>
                                </svg>
                            </span>
                            <span class="nav-link-title">
                                Category
                            </span>
                        </a>
                        <div class="dropdown-menu">
                            @foreach ($category as $cat)
                                <a class="dropdown-item" href="{{ route('content.user.category', $cat->slug) }}" rel="noopener">
                                    {{ $cat->classification_name }}
                                </a>
                            @endforeach
                        </div>
                    </li>

                    <li class="nav-item {{ request()->is('playlist*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ url('/playlist') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><!-- Download SVG icon from http://tabler-icons.io/i/article -->
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-subtask" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M6 9l6 0"></path>
                                    <path d="M4 5l4 0"></path>
                                    <path d="M6 5v11a1 1 0 0 0 1 1h5"></path>
                                    <path d="M12 7m0 1a1 1 0 0 1 1 -1h6a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-6a1 1 0 0 1 -1 -1z"></path>
                                    <path d="M12 15m0 1a1 1 0 0 1 1 -1h6a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-6a1 1 0 0 1 -1 -1z"></path>
                                </svg>
                            </span>
                            <span class="nav-link-title">
                                Playlist
                            </span>
                        </a>
                    </li>
                    <li class="nav-item {{ request()->is('news*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ url('/news') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><!-- Download SVG icon from http://tabler-icons.io/i/article -->
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M3 4m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z"></path>
                                    <path d="M7 8h10"></path>
                                    <path d="M7 12h10"></path>
                                    <path d="M7 16h10"></path>
                                </svg>
                            </span>
                            <span class="nav-link-title">
                                News
                            </span>
                        </a>
                    </li>
                    <li class="nav-item {{ request()->is('gallery*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ url('/gallery') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block"><!-- Download SVG icon from http://tabler-icons.io/i/article -->
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-camera-heart" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M10.5 20h-5.5a2 2 0 0 1 -2 -2v-9a2 2 0 0 1 2 -2h1a2 2 0 0 0 2 -2a1 1 0 0 1 1 -1h6a1 1 0 0 1 1 1a2 2 0 0 0 2 2h1a2 2 0 0 1 2 2v2"></path>
                                    <path d="M14.41 11.212a3 3 0 1 0 -4.15 4.231"></path>
                                    <path d="M18 22l3.35 -3.284a2.143 2.143 0 0 0 .005 -3.071a2.242 2.242 0 0 0 -3.129 -.006l-.224 .22l-.223 -.22a2.242 2.242 0 0 0 -3.128 -.006a2.143 2.143 0 0 0 -.006 3.071l3.355 3.296z"></path>
                                </svg>
                            </span>
                            <span class="nav-link-title">
                                Gallery
                            </span>
                        </a>
                    </li>
                    @can('admin')
                        <li class="nav-item dropdown {{ request()->is('manage*') ? 'active' : '' }}">
                            <a class="nav-link dropdown-toggle" href="#navbar-help" data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button" aria-expanded="false">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M12 6m-8 0a8 3 0 1 0 16 0a8 3 0 1 0 -16 0"></path>
                                        <path d="M4 6v6a8 3 0 0 0 16 0v-6"></path>
                                        <path d="M4 12v6a8 3 0 0 0 16 0v-6"></path>
                                    </svg>
                                </span>
                                <span class="nav-link-title">
                                    Manage
                                </span>
                            </a>
                            <div class="dropdown-menu">

                                <a class="dropdown-item" href="{{ url('manage/learning') }}" rel="noopener">
                                    Learning
                                </a>
                                <a class="dropdown-item" href="{{ url('manage/user') }}" rel="noopener">
                                    User
                                </a>
                                <a class="dropdown-item" href="{{ url('manage/admin') }}" rel="noopener">
                                    Admin
                                </a>
                                <a class="dropdown-item" href="{{ url('manage/pages') }}" rel="noopener">
                                    Page
                                </a>
                                <a class="dropdown-item" href="{{ url('manage/news') }}" rel="noopener">
                                    News
                                </a>
                                <a class="dropdown-item" href="{{ url('manage/content') }}" rel="noopener">
                                    Content
                                </a>
                                <a class="dropdown-item" href="{{ url('manage/classification') }}" rel="noopener">
                                    Category
                                </a>
                                <a class="dropdown-item" href="{{ url('manage/assesment') }}" rel="noopener">
                                    Assesment
                                </a>
                                <a class="dropdown-item" href="{{ url('manage/trainingplan') }}" rel="noopener">
                                    Playlist
                                </a>
                                <a class="dropdown-item" href="{{ url('manage/eventphoto') }}" rel="noopener">
                                    Event Photo Gallery
                                </a>
                            </div>
                        </li>

                        {{-- <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#navbar-help" data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button" aria-expanded="false">
                                <span class="nav-link-icon d-md-none d-lg-inline-block"><!-- Download SVG icon from http://tabler-icons.io/i/lifebuoy -->
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" />
                                        <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                                        <path d="M15 15l3.35 3.35" />
                                        <path d="M9 15l-3.35 3.35" />
                                        <path d="M5.65 5.65l3.35 3.35" />
                                        <path d="M18.35 5.65l-3.35 3.35" />
                                    </svg>
                                </span>
                                <span class="nav-link-title">
                                    Help
                                </span>
                            </a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="https://tabler.io/docs" target="_blank" rel="noopener">
                                    Documentation <span class="badge badge-sm bg-green-lt text-uppercase ms-auto">New</span>
                                </a>
                                <a class="dropdown-item" href="https://github.com/tabler/tabler" target="_blank" rel="noopener">
                                    Source code
                                </a>
                                <a class="dropdown-item" href="{{ url('/Tabler/index.html') }}" target="_blank" rel="noopener">
                                    Template
                                </a>
                            </div>
                        </li> --}}
                    @endcan
                </ul>
                <div class="my-2 my-md-0 flex-grow-1 flex-md-grow-0 order-first order-md-last">
                    <form action="{{ route('search', 'all') }}" method="get" autocomplete="off" novalidate>
                        <div class="input-icon">
                            <span class="input-icon-addon">
                                <!-- Download SVG icon from http://tabler-icons.io/i/search -->
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                                    <path d="M21 21l-6 -6" />
                                </svg>
                            </span>
                            <input type="text" name="s" class="form-control" placeholder="Search…" aria-label="Search in website">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</header>
