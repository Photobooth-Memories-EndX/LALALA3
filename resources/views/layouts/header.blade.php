@php
    $category = isset($category) && is_iterable($category) ? $category : collect();
    $mylearningongoing = $mylearningongoing ?? 0;
    $darkThemeUrl = request()->fullUrlWithQuery(['theme' => 'dark']);
    $lightThemeUrl = request()->fullUrlWithQuery(['theme' => 'light']);

    $authUser = auth()->user();
    $isAssessmentPage = request()->is('assesment*') || request()->is('assessment*') || request()->routeIs('assesment.*') || request()->routeIs('assessment.*');
@endphp


{{-- <div style="color:red; font-size:12px;">
    email: {{ $authUser->email }} |
    role: {{ $authUser->role }} |
    type: {{ gettype($authUser->role) }} |
    canManageLms: {{ $authUser->canManageLms() ? 'YES' : 'NO' }}
</div> --}}

<aside class="navbar navbar-vertical navbar-expand-lg d-print-none">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <h1 class="navbar-brand navbar-brand-autodark owl-sidebar-brand">
            <a href="{{ url('/') }}">
                <img src="{{ asset('owl.png') }}" width="164" height="97" alt="OWL Learning Hub" class="navbar-brand-image owl-sidebar-logo">
            </a>
        </h1>

        @if ($isAssessmentPage)
            {{-- Assessment Mobile: Notification & Dark Mode Icons on Top Right --}}
            <div class="d-flex align-items-center gap-1 ms-auto d-lg-none owl-assessment-mobile-actions">
                {{-- NOTIFICATION BELL --}}
                <div class="nav-item dropdown me-1">
                    <a href="#"
                       class="nav-link px-2 position-relative text-muted"
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

                {{-- DARK MODE TOGGLE --}}
                <div class="nav-item">
                    <a href="{{ $darkThemeUrl }}"
                       class="nav-link px-2 hide-theme-dark text-muted"
                       title="Enable dark mode">
                        <i class="ti ti-moon fs-2"></i>
                    </a>

                    <a href="{{ $lightThemeUrl }}"
                       class="nav-link px-2 hide-theme-light text-muted"
                       title="Enable light mode">
                        <i class="ti ti-sun fs-2"></i>
                    </a>
                </div>
            </div>
        @endif

        <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav owl-sidebar-nav">
                <li class="nav-item {{ request()->is('/') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ url('/') }}">
                        <span class="nav-link-icon"><i class="ti ti-home"></i></span>
                        <span class="nav-link-title">Home</span>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('user.learning.dashboard') ? 'active' : '' }}">
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

                <li class="nav-item {{ request()->is('trainingplan*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('trainingplan.user.index') }}">
                        <span class="nav-link-icon"><i class="ti ti-clipboard-list"></i></span>
                        <span class="nav-link-title">Training Schedule</span>
                    </a>
                </li>
                {{-- Manage LMS: Admin Training (2) & Super Admin (3) --}}
                @if (auth()->user()->canManageLms())
                    <li class="nav-item {{ (request()->is('manage') || request()->is('manage/*')) && !request()->routeIs('manage.role.*') && !request()->routeIs('manage.learning-hours.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ url('manage') }}">
                            <span class="nav-link-icon"><i class="ti ti-settings"></i></span>
                            <span class="nav-link-title">Manage</span>
                        </a>
                    </li>
                @endif

            </ul>

            {{-- Sidebar Footer: Logout fixed at bottom-left --}}
            <div class="owl-sidebar-footer">
                <form action="{{ route('logoutpage') }}" method="POST" id="sidebar-logout-form" class="m-0 p-0">
                    @csrf
                    <button type="submit" class="nav-link owl-sidebar-logout-btn" data-logout-trigger="true">
                        <span class="nav-link-icon"><i class="ti ti-logout"></i></span>
                        <span class="nav-link-title">Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</aside>

<header class="navbar navbar-expand-md d-print-none app-topbar">
    <div class="container-fluid">

        {{-- ========================================================
             DESKTOP HEADER LAYOUT (>= 992px)
             ======================================================== --}}
        <div class="d-none d-lg-flex align-items-center w-100 py-1">

            {{-- SEARCH DESKTOP --}}
            <div class="topbar-search flex-grow-1 me-3">
                <form action="/search"
                      method="get"
                      autocomplete="off"
                      novalidate
                      class="w-100">

                    <div class="position-relative w-100" style="max-width: 450px;">
                        <input type="text"
                               id="header-search-input"
                               name="q"
                               class="form-control rounded-pill px-4"
                               placeholder="Search learning, content, news..."
                               autocomplete="off"
                               aria-label="Search in website">

                        <!-- DROPDOWN HASIL AUTOCOMPLETE -->
                        <div id="header-search-results" class="dropdown-menu shadow-sm w-100 position-absolute mt-1 p-0 overflow-hidden" style="display: none; z-index: 2000; background-color: #ffffff;">
                            <!-- 1. Scrollable List Container (Only items scroll here) -->
                            <div id="header-autocomplete-list" class="list-group list-group-flush" style="max-height: 280px; overflow-y: auto; background-color: #ffffff;"></div>
                            
                            <!-- 2. Sticky/Fixed Footer Container (Always visible at bottom) -->
                            <div id="header-search-footer" class="border-top bg-light p-2 text-center" style="display: none; position: sticky; bottom: 0; z-index: 2005;"></div>
                        </div>
                    </div>
                </form>
            </div>

            {{-- RIGHT MENU DESKTOP --}}
            <div class="navbar-nav flex-row align-items-center ms-auto">

                {{-- DARK MODE --}}
                <div class="nav-item me-3">
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

                {{-- NOTIFICATION BELL DESKTOP --}}
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

                {{-- PROFILE DESKTOP --}}
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
                            <i class="ti ti-user me-2"></i>
                            Profile
                        </a>

                        <form action="{{ route('logoutpage') }}" method="POST" class="m-0 p-0">
                            @csrf
                            <button type="submit" class="dropdown-item" data-logout-trigger="true">
                                <i class="ti ti-logout me-2"></i>
                                Logout
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>

        {{-- ========================================================
             MOBILE HEADER LAYOUT (< 992px)
             Row 1: Hamburger + Logo (in <aside>)
             Row 2: User Profile Strip (Avatar + Username + Team)
             Row 3: Search Bar (left) + Notification Bell (right) [Non-Assessment only]
             ======================================================== --}}
        <div class="d-lg-none w-100 py-1">

            @if ($isAssessmentPage)
                {{-- ========================================================
                     ASSESSMENT MOBILE HEADER LAYOUT (< 992px)
                     Row 2: User Profile Strip (Avatar + Username + Team)
                     ======================================================== --}}
                <div class="owl-mobile-user-row d-flex align-items-center justify-content-between py-1">
                    <a href="{{ route('user.profile') }}" class="d-flex align-items-center text-decoration-none text-reset min-w-0 flex-grow-1">
                        @if (!empty(auth()->user()->avatar))
                            <span class="avatar owl-mobile-avatar rounded-circle flex-shrink-0"
                                  style="background-image: url('{{ asset('storage/profile/' . auth()->user()->avatar) }}')">
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
                </div>
            @else
                {{-- ========================================================
                     DEFAULT NON-ASSESSMENT MOBILE HEADER LAYOUT (< 992px)
                     Row 2: User Profile Strip + Dark Mode
                     Row 3: Search Bar (left) + Notification Bell (right)
                     ======================================================== --}}
                <div class="owl-mobile-user-row d-flex align-items-center justify-content-between py-1 border-bottom mb-2">
                    <a href="{{ route('user.profile') }}" class="d-flex align-items-center text-decoration-none text-reset min-w-0 flex-grow-1 me-2">
                        @if (!empty(auth()->user()->avatar))
                            <span class="avatar owl-mobile-avatar rounded-circle flex-shrink-0"
                                  style="background-image: url('{{ asset('storage/profile/' . auth()->user()->avatar) }}')">
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
                        <a href="{{ $darkThemeUrl }}"
                           class="nav-link px-2 hide-theme-dark text-muted"
                           title="Enable dark mode">
                            <i class="ti ti-moon fs-2"></i>
                        </a>

                        <a href="{{ $lightThemeUrl }}"
                           class="nav-link px-2 hide-theme-light text-muted"
                           title="Enable light mode">
                            <i class="ti ti-sun fs-2"></i>
                        </a>
                    </div>
                </div>

                {{-- ROW 3: SEARCH BAR (LEFT) + NOTIFICATION BELL (RIGHT) --}}
                <div class="owl-mobile-action-row d-flex align-items-center gap-2 pb-1">
                    {{-- Search Bar (left, flexible) --}}
                    <form action="/search"
                          method="get"
                          autocomplete="off"
                          novalidate
                          class="flex-grow-1 min-w-0">

                        <div class="position-relative w-100">
                            <input type="text"
                                   id="mobile-header-search-input"
                                   name="q"
                                   class="form-control rounded-pill px-4"
                                   placeholder="Search learning, content, news..."
                                   autocomplete="off"
                                   aria-label="Search in website">

                            <!-- DROPDOWN HASIL AUTOCOMPLETE -->
                            <div id="mobile-header-search-results" class="dropdown-menu shadow-sm w-100 position-absolute mt-1 p-0 overflow-hidden" style="display: none; z-index: 2000; background-color: #ffffff;">
                                <!-- 1. Scrollable List Container (Only items scroll here) -->
                                <div id="mobile-header-autocomplete-list" class="list-group list-group-flush" style="max-height: 280px; overflow-y: auto; background-color: #ffffff;"></div>
                                
                                <!-- 2. Sticky/Fixed Footer Container (Always visible at bottom) -->
                                <div id="mobile-header-search-footer" class="border-top bg-light p-2 text-center" style="display: none; position: sticky; bottom: 0; z-index: 2005;"></div>
                            </div>
                        </div>
                    </form>

                    {{-- Notification Bell (right, fixed) --}}
                    <div class="nav-item dropdown flex-shrink-0">
                        <a href="#"
                           class="nav-link px-2 position-relative"
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
                </div>
            @endif

        </div>

    </div>
</header>

@include('partials.logout-modal')

<style>
.bg-purple-subtle {
    background-color: rgba(174, 62, 201, 0.12) !important;
}
.text-purple {
    color: #ae3ec9 !important;
}
[data-bs-theme="dark"] .bg-purple-subtle {
    background-color: rgba(174, 62, 201, 0.25) !important;
}
[data-bs-theme="dark"] .text-purple {
    color: #d670ec !important;
}
.sticky-bottom,
#search-more-results-footer,
#mobile-search-more-results-footer,
.search-footer {
    position: sticky !important;
    bottom: 0 !important;
    z-index: 2 !important;
}
[data-bs-theme="dark"] #search-more-results-footer,
[data-bs-theme="dark"] #mobile-search-more-results-footer,
[data-bs-theme="dark"] .search-footer {
    background-color: var(--tblr-bg-surface-secondary, #1e293b) !important;
    border-color: var(--tblr-border-color, rgba(255, 255, 255, 0.1)) !important;
}
#header-search-results::-webkit-scrollbar,
#mobile-header-search-results::-webkit-scrollbar,
.module-search-dropdown::-webkit-scrollbar {
    width: 6px;
}
#header-search-results::-webkit-scrollbar-thumb,
#mobile-header-search-results::-webkit-scrollbar-thumb,
.module-search-dropdown::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.18);
    border-radius: 4px;
}
[data-bs-theme="dark"] #header-search-results::-webkit-scrollbar-thumb,
[data-bs-theme="dark"] #mobile-header-search-results::-webkit-scrollbar-thumb,
[data-bs-theme="dark"] .module-search-dropdown::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
}

/* Global & Module Autocomplete Search Dropdown Containers */
.dropdown-menu.search-results-dropdown,
.search-autocomplete-container,
#header-search-results,
#mobile-header-search-results,
#gallery-search-results,
#news-search-results,
#content-search-results,
#playlist-search-results,
#ongoing-search-results,
#assessment-search-results,
#certificate-search-results {
    background-color: #ffffff !important;
    background: #ffffff !important;
    border-radius: 8px !important;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12) !important;
    border: 1px solid #e2e8f0 !important;
    overflow: hidden !important;
}

/* Scrollable List Container (Where items are rendered) */
.autocomplete-list-group,
#gallery-autocomplete-list,
#news-autocomplete-list,
#content-autocomplete-list,
#playlist-autocomplete-list,
#ongoing-autocomplete-list,
#assessment-autocomplete-list,
#certificate-autocomplete-list,
#header-autocomplete-list,
#mobile-header-autocomplete-list,
#search-autocomplete-list,
#mobile-search-autocomplete-list {
    max-height: 280px !important;
    overflow-y: auto !important;
    background-color: #ffffff !important;
    background: #ffffff !important;
    scrollbar-width: thin;
}

/* Autocomplete Items */
.search-results-dropdown .dropdown-item,
.search-autocomplete-container .dropdown-item,
.autocomplete-list-group .dropdown-item {
    background-color: #ffffff !important;
    color: #333333 !important;
}

.search-results-dropdown .dropdown-item:hover,
.search-results-dropdown .dropdown-item:focus,
.search-autocomplete-container .dropdown-item:hover,
.search-autocomplete-container .dropdown-item:focus,
.autocomplete-list-group .dropdown-item:hover,
.autocomplete-list-group .dropdown-item:focus {
    background-color: #f8f9fa !important;
}

/* Sticky Bottom Footer Area */
.search-footer,
#gallery-search-footer,
#news-search-footer,
#content-search-footer,
#playlist-search-footer,
#ongoing-search-footer,
#assessment-search-footer,
#certificate-search-footer,
#header-search-footer,
#mobile-header-search-footer,
#search-more-results-footer,
#mobile-search-more-results-footer {
    position: sticky !important;
    bottom: 0 !important;
    background-color: #f8f9fa !important;
    border-top: 1px solid #e2e8f0 !important;
    z-index: 2005 !important;
}

/* Autocomplete Dark Mode Support */
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .dropdown-menu.search-results-dropdown,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .search-autocomplete-container,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #header-search-results,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #mobile-header-search-results,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #gallery-search-results,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #news-search-results,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #content-search-results,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #playlist-search-results,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #ongoing-search-results,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #assessment-search-results,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #certificate-search-results {
    background-color: var(--tblr-bg-surface, #1e293b) !important;
    background: var(--tblr-bg-surface, #1e293b) !important;
    border-color: var(--tblr-border-color, rgba(255, 255, 255, 0.1)) !important;
}

:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .autocomplete-list-group,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #gallery-autocomplete-list,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #news-autocomplete-list,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #content-autocomplete-list,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #playlist-autocomplete-list,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #ongoing-autocomplete-list,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #assessment-autocomplete-list,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #certificate-autocomplete-list,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #header-autocomplete-list,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #mobile-header-autocomplete-list,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #search-autocomplete-list,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #mobile-search-autocomplete-list {
    background-color: var(--tblr-bg-surface, #1e293b) !important;
    background: var(--tblr-bg-surface, #1e293b) !important;
}

:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .search-results-dropdown .dropdown-item,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .search-autocomplete-container .dropdown-item,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .autocomplete-list-group .dropdown-item {
    background-color: transparent !important;
    color: var(--tblr-body-color, #f1f5f9) !important;
}

:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .search-results-dropdown .dropdown-item:hover,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .search-results-dropdown .dropdown-item:focus,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .search-autocomplete-container .dropdown-item:hover,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .search-autocomplete-container .dropdown-item:focus,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .autocomplete-list-group .dropdown-item:hover,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .autocomplete-list-group .dropdown-item:focus {
    background-color: rgba(255, 255, 255, 0.08) !important;
}

:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .search-footer,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #gallery-search-footer,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #news-search-footer,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #content-search-footer,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #playlist-search-footer,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #ongoing-search-footer,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #assessment-search-footer,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #certificate-search-footer,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #header-search-footer,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #mobile-header-search-footer,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #search-more-results-footer,
:is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #mobile-search-more-results-footer {
    background-color: var(--tblr-bg-surface-secondary, #1e293b) !important;
    border-top: 1px solid var(--tblr-border-color, rgba(255, 255, 255, 0.1)) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function handleAutocomplete(inputId, resultsId, listId, footerId, endpoint, searchUrl) {
        const input = document.getElementById(inputId);
        const results = document.getElementById(resultsId);
        const list = document.getElementById(listId);
        const footer = document.getElementById(footerId);

        if (!input || !results || !list || !footer) return;

        let timer;
        input.addEventListener('input', function(e) {
            if (e.target && e.target.id !== inputId) return;
            clearTimeout(timer);
            const query = this.value.trim();

            if (query.length < 1) {
                results.style.display = 'none';
                list.innerHTML = '';
                footer.innerHTML = '';
                footer.style.display = 'none';
                return;
            }

            timer = setTimeout(() => {
                fetch(`${endpoint}?q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        list.innerHTML = '';
                        footer.innerHTML = '';

                        if (data && data.length > 0) {
                            data.forEach(item => {
                                const a = document.createElement('a');
                                a.className = 'dropdown-item py-2 px-3 text-wrap d-flex align-items-center justify-content-between border-bottom-subtle';
                                a.href = item.url || `${searchUrl}?q=${encodeURIComponent(item.title)}`;
                                const badgeClass = item.type === 'Playlist' ? 'bg-purple-subtle text-purple' : 'bg-light text-secondary';
                                a.innerHTML = `
                                    <span class="text-truncate fw-normal">${item.title}</span>
                                    <span class="badge ${badgeClass} text-uppercase ms-2 flex-shrink-0" style="font-size: 10px;">${item.type || 'CONTENT'}</span>
                                `;
                                list.appendChild(a);
                            });

                            footer.innerHTML = `
                                <a href="${searchUrl}?q=${encodeURIComponent(query)}" class="dropdown-item fw-semibold text-primary py-1 d-flex align-items-center justify-content-center gap-2">
                                    <i class="ti ti-search"></i>
                                    <span>See all results for "${query}"</span>
                                </a>
                            `;
                            footer.style.display = 'block';
                            results.style.display = 'block';
                        } else {
                            list.innerHTML = `<div class="dropdown-item text-muted disabled py-2 px-3">No results found</div>`;
                            footer.style.display = 'none';
                            results.style.display = 'block';
                        }
                    })
                    .catch(() => {
                        results.style.display = 'none';
                    });
            }, 250);
        });

        // Close dropdown on click outside
        document.addEventListener('click', function(e) {
            if (!input.contains(e.target) && !results.contains(e.target)) {
                results.style.display = 'none';
            }
        });

        // Enter key submission
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const query = this.value.trim();
                if (query.length > 0) {
                    window.location.href = `${searchUrl}?q=${encodeURIComponent(query)}`;
                }
            }
        });

        // Re-open on focus if query present
        input.addEventListener('focus', function() {
            if (this.value.trim().length >= 1 && list.children.length > 0) {
                results.style.display = 'block';
            }
        });
    }

    handleAutocomplete('header-search-input', 'header-search-results', 'header-autocomplete-list', 'header-search-footer', '/api/search-autocomplete', '/search');
    handleAutocomplete('mobile-header-search-input', 'mobile-header-search-results', 'mobile-header-autocomplete-list', 'mobile-header-search-footer', '/api/search-autocomplete', '/search');
});
</script>
