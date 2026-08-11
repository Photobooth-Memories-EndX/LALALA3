@php
    $heroPageTitle = $heroPageTitle ?? 'OWL Learning Hub';
    $heroPageSubtitle = $heroPageSubtitle ?? 'Open Mind, Wide Growth, Lead Impact';

    $user = auth()->user();
    $userName = $user?->name ? ucwords(strtolower($user->name)) : 'Learner';
    $userDivision = $user?->division ?? $user?->team ?? 'Training Team';
    $userAvatar = !empty($user?->avatar) ? asset('storage/profile/' . $user->avatar) : null;
    $mylearningongoing = $mylearningongoing ?? 0;
@endphp

{{-- Mobile Header (< 768px) --}}
<div class="home-modern-mobile-header d-md-none mb-3">
    {{-- KIRI: User Info (Avatar Icon Kecil + Nama & Team) --}}
    <div class="user-info-mobile min-w-0 flex-grow-1">
        @if ($userAvatar)
            <span class="avatar avatar-sm rounded-circle flex-shrink-0" style="background-image: url('{{ $userAvatar }}')"></span>
        @else
            <span class="avatar avatar-sm rounded-circle bg-primary-subtle text-primary fw-bold flex-shrink-0">
                <i class="ti ti-user"></i>
            </span>
        @endif
        <div class="min-w-0 flex-grow-1">
            <h3 class="home-modern-mobile-user-name mb-0 text-truncate" style="font-size: 0.95rem; font-weight: 700; line-height: 1.2;">
                {{ $userName }}
            </h3>
            <div class="home-modern-mobile-user-division text-muted text-truncate" style="font-size: 0.8rem; font-weight: 500;">
                {{ $userDivision }}
            </div>
        </div>
    </div>

    {{-- KANAN: HANYA Ikon Lonceng Notifikasi --}}
    <div class="dropdown flex-shrink-0 ms-auto">
        <a href="#"
           class="nav-link px-0 position-relative home-modern-mobile-bell-link"
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
            <div class="dropdown-header">Notifications</div>
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

{{-- Desktop Header (>= 768px) --}}
<div class="home-modern-db-header d-none d-md-flex">
    <div>
        <h1 class="fw-bold home-modern-db-title home-header-title text-dark dark:text-white mb-1">{{ $heroPageTitle }}</h1>
        <p class="fw-medium home-modern-db-subtitle home-header-subtitle text-dark dark:text-white-50">{{ $heroPageSubtitle }}</p>
    </div>
    <button type="button"
            class="home-modern-assessment-btn bg-[#ff5700] hover:bg-[#e04d00] text-white font-semibold rounded-lg shadow-md shadow-orange-500/40"
            id="btn-assessment-access">
        <i class="ti ti-clipboard-list"></i>
        Assessment Access
    </button>
</div>
