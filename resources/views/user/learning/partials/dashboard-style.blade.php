@section('style')
<link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">
{{-- Learning Record: style tabel client-side (dipisah — clean code). --}}
<link rel="stylesheet" href="{{ asset('css/learning-record.css') }}">
@include('user.learning.partials.assessment-style')

<style>
    :root {
        --learning-ui-radius: 20px;
        --learning-hero-radius: 24px;
        --learning-ui-surface: #ffffff;
        --learning-ui-border: #e2e8f0;
        --learning-ui-text: #0f172a;
        --learning-ui-muted: #64748b;
        --learning-ui-shadow: 0 2px 12px rgba(15, 23, 42, .05);
        --learning-ui-shadow-hover: 0 10px 28px rgba(15, 23, 42, .10);
    }

    .learning-section-padding {
        padding: 1.25rem;
    }

    #learning-tab-content .learning-section-padding {
        padding-top: .75rem !important;
    }

    .learning-dashboard-container {
        max-width: 1500px;
        margin: auto;
    }

    .page-body {
        background: #f1f5f9;
        min-height: 100vh;
    }

    .learning-dashboard-container .card,
    #learning-tab-content > .card,
    .learning-filter-card {
        border-radius: var(--learning-ui-radius) !important;
        border: 1px solid var(--learning-ui-border) !important;
        box-shadow: var(--learning-ui-shadow) !important;
        overflow: hidden;
    }

    .learning-dashboard-container .card-body,
    #learning-tab-content > .card > .card-body {
        color: var(--learning-ui-text);
    }

    .learning-hero {
        background: linear-gradient(135deg, #0041b9 0%, #1248a7 45%, #2a72ca 100%);
        border-radius: var(--learning-hero-radius);
        color: #fff;
    }

    .learning-mini-stat {
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 1rem;
        padding: 1rem 1.1rem;
        color: #fff;
        backdrop-filter: blur(10px);
    }

    .learning-mini-stat-label {
        font-size: .75rem;
        letter-spacing: .04em;
        text-transform: uppercase;
        opacity: .8;
        margin-bottom: .35rem;
    }

    .learning-mini-stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1;
    }

    /* 4 card statistik hero: grid 2x2, glass effect, tinggi seragam, tanpa overflow */
    .learning-hero-stats {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
        align-items: stretch;
    }

    .learning-hero-stats .learning-mini-stat {
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: .5rem;
        text-align: center;
        padding: 1.25rem 1rem;
    }

    .learning-hero-stats .learning-mini-stat-label {
        font-size: .75rem;
        opacity: .7;
    }

    .learning-hero-stats .learning-mini-stat-value {
        font-size: 2rem;
        font-weight: 700;
    }

    @media (max-width: 575.98px) {
        .learning-hero-cta {
            width: 100%;
        }
    }

    @media (max-width: 419.98px) {
        .learning-hero-stats {
            grid-template-columns: minmax(0, 1fr);
        }
    }

    .learning-filter-card .form-control,
    .learning-filter-card .form-select {
        min-height: 42px;
        border-radius: 12px;
    }

    .learning-filter-card .form-label {
        font-weight: 600;
    }

    /* Tombol Filter/Reset sejajar & sama tinggi dengan input di baris filter */
    .learning-filter-actions .btn {
        min-height: 42px;
        border-radius: 12px;
        white-space: nowrap;
    }

    .learning-modern-card {
        border-radius: var(--learning-ui-radius) !important;
        border: 1px solid var(--learning-ui-border) !important;
        box-shadow: var(--learning-ui-shadow) !important;
        overflow: hidden;
        height: 100%;
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    }

    .learning-modern-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--learning-ui-shadow-hover) !important;
    }

    .learning-thumb {
        height: 180px;
        object-fit: cover;
        background: linear-gradient(135deg, #dbeafe, #eff6ff);
    }

    .learning-placeholder {
        height: 180px;
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .learning-progress-bar {
        height: .7rem;
        border-radius: 999px;
        background: #e2e8f0;
    }

    .learning-progress-bar .progress-bar {
        border-radius: 999px;
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .learning-card-title {
        min-height: calc(1.4em * 2);
        color: var(--learning-ui-text);
    }

    .rounded-4 {
        border-radius: var(--learning-ui-radius) !important;
    }

    .learning-tab-btn {
        white-space: nowrap;
        border-radius: 12px;
        min-height: 40px;
    }

    .text-white-75 {
        color: rgba(255, 255, 255, .75) !important;
    }

    /* Style tabel Learning Record dipindah ke css/learning-record.css */

    .pagination {
        gap: .4rem;
    }

    .page-item .page-link {
        border-radius: 12px !important;
        border: 1px solid #dbe4f0;
        color: #1e40af;
        min-width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
    }

    .page-item.active .page-link {
        background: #2563eb;
        border-color: #2563eb;
        color: #fff;
        box-shadow: 0 8px 18px rgba(37, 99, 235, .25);
    }

    .page-item .page-link:hover {
        background: #eff6ff;
    }

    [data-bs-theme="dark"] .page-body,
    body.dark-mode .page-body {
        background: #0b1629;
    }

    [data-bs-theme="dark"] .learning-dashboard-container .card,
    [data-bs-theme="dark"] #learning-tab-content > .card,
    [data-bs-theme="dark"] .learning-filter-card,
    body.dark-mode .learning-dashboard-container .card,
    body.dark-mode #learning-tab-content > .card,
    body.dark-mode .learning-filter-card {
        background: #152235 !important;
        border-color: rgba(255, 255, 255, .07) !important;
        color: #f1f5f9 !important;
        box-shadow: none !important;
    }

    [data-bs-theme="dark"] .learning-filter-card .form-control,
    [data-bs-theme="dark"] .learning-filter-card .form-select,
    body.dark-mode .learning-filter-card .form-control,
    body.dark-mode .learning-filter-card .form-select {
        background: #192d45;
        border-color: rgba(255, 255, 255, .08);
        color: #f1f5f9;
    }

    [data-bs-theme="dark"] .learning-filter-card .form-control::placeholder,
    body.dark-mode .learning-filter-card .form-control::placeholder {
        color: #64748b;
    }

    [data-bs-theme="dark"] .learning-dashboard-container .card-body,
    [data-bs-theme="dark"] #learning-tab-content > .card > .card-body,
    body.dark-mode .learning-dashboard-container .card-body,
    body.dark-mode #learning-tab-content > .card > .card-body {
        background: #152235 !important;
        color: #f1f5f9 !important;
    }

    [data-bs-theme="dark"] .learning-modern-card,
    body.dark-mode .learning-modern-card {
        background: #152235 !important;
        border-color: rgba(255, 255, 255, .07) !important;
    }

    [data-bs-theme="dark"] .learning-card-title,
    [data-bs-theme="dark"] .learning-modern-card .card-title,
    body.dark-mode .learning-card-title,
    body.dark-mode .learning-modern-card .card-title {
        color: #f1f5f9 !important;
    }

    [data-bs-theme="dark"] .learning-modern-card .text-muted,
    [data-bs-theme="dark"] .learning-filter-card .text-muted,
    body.dark-mode .learning-modern-card .text-muted,
    body.dark-mode .learning-filter-card .text-muted {
        color: #94a3b8 !important;
    }

    [data-bs-theme="dark"] .learning-progress-bar,
    body.dark-mode .learning-progress-bar {
        background: #1a3356;
    }

    [data-bs-theme="dark"] .learning-placeholder,
    body.dark-mode .learning-placeholder {
        background: linear-gradient(135deg, #10233d, #132e5a);
    }

    [data-bs-theme="dark"] .learning-tab-btn.btn-white,
    body.dark-mode .learning-tab-btn.btn-white {
        background: #192d45;
        border-color: rgba(255, 255, 255, .07);
        color: #e2e8f0;
    }

    [data-bs-theme="dark"] .learning-tab-btn.btn-white:hover,
    body.dark-mode .learning-tab-btn.btn-white:hover {
        border-color: rgba(96, 165, 250, .28);
        color: #93c5fd;
    }

    [data-bs-theme="dark"] .page-item .page-link,
    body.dark-mode .page-item .page-link {
        background: #152235;
        border-color: rgba(255, 255, 255, .08);
        color: #93c5fd;
    }

    [data-bs-theme="dark"] .page-item.active .page-link,
    body.dark-mode .page-item.active .page-link {
        background: #3b82f6;
        border-color: #3b82f6;
        color: #fff;
    }
</style>
@endsection
