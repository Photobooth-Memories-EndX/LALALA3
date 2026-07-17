@php
    $heroPageTitle = $heroPageTitle ?? 'Learning Dashboard';
    $heroPageSubtitle = $heroPageSubtitle ?? 'Continue your learning journey';
@endphp

<div class="home-modern-db-header">
    <div>
        <h1 class="home-modern-db-title">{{ $heroPageTitle }}</h1>
        <p class="home-modern-db-subtitle">{{ $heroPageSubtitle }}</p>
    </div>
    <button type="button"
            class="home-modern-assessment-btn"
            id="btn-assessment-access">
        <i class="ti ti-clipboard-list"></i>
        Assessment Access
    </button>
</div>
