@section('style')
<link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">
{{-- Learning Record: style tabel client-side (dipisah — clean code). --}}
<link rel="stylesheet" href="{{ asset('css/learning-record.css') }}">
@include('user.learning.partials.assessment-style')
@include('user.learning.partials.certificate-style')

<style>
    :root {
        --learning-ui-radius: 20px;
        --learning-hero-radius: 20px;
        --learning-ui-surface: #ffffff;
        --learning-ui-border: #D9D9D6;
        --learning-ui-text: #53565A;
        --learning-ui-muted: #8A8D8F;
        --learning-ui-shadow: 0 2px 12px rgba(83, 86, 90, .05);
        --learning-ui-shadow-hover: 0 10px 28px rgba(83, 86, 90, .10);
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
        background: #D9D9D6;
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

    .learning-module-hero {
        background: linear-gradient(135deg, #05507D 0%, #6AAED6 100%);
        border-radius: var(--learning-hero-radius) !important;
        color: #fff;
    }

    .learning-hero-stats-panel {
        width: 100%;
        max-width: 360px;
        margin-left: auto;
        padding: .5rem;
        border: 1px solid rgba(255, 255, 255, .18);
        border-radius: 1rem;
        background: rgba(5, 80, 125, .2);
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, .08),
            0 8px 20px rgba(5, 80, 125, .12);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
    }

    .learning-hero-stats-split {
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr);
        gap: .25rem;
        align-items: stretch;
    }

    .learning-hero-stat-stack {
        display: grid;
        grid-template-rows: repeat(3, minmax(0, 1fr));
        gap: .25rem;
    }

    .learning-hero-stats-paired {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .5rem;
        align-items: stretch;
    }

    .learning-hero-stats-panel--paired {
        max-width: 15rem;
    }

    .learning-hero-stats-panel--paired .learning-stat-small {
        min-height: 4.5rem;
        align-items: center;
        padding: .75rem;
        text-align: center;
    }

    .learning-hero-stats-panel--paired .learning-stat-small-label {
        overflow: visible;
        font-size: .6875rem;
        line-height: 1.2;
        text-align: center;
        text-overflow: clip;
        white-space: normal;
    }

    .learning-hero-stats-panel--paired .learning-stat-small-value {
        margin-top: .375rem;
        font-size: 1.375rem;
        font-weight: 700;
        line-height: 1.1;
        text-align: center;
    }

    .learning-stat-card {
        min-width: 0;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, .12);
        color: #fff;
        text-align: left;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, .08),
            0 5px 12px rgba(5, 80, 125, .1);
    }

    .learning-stat-large {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
        padding: .5rem;
        border-radius: 1rem;
    }

    .learning-stat-small {
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: .25rem .5rem;
        border-radius: .75rem;
    }

    .learning-stat-large-label {
        font-size: .6875rem;
        font-weight: 600;
        line-height: 1.2;
        text-transform: uppercase;
        letter-spacing: .035em;
    }

    .learning-stat-large-value {
        margin: .125rem 0;
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1.1;
    }

    .learning-stat-large-description {
        color: rgba(255, 255, 255, .72);
        font-size: .5625rem;
        line-height: 1.25;
    }

    .learning-stat-small-label {
        overflow: hidden;
        color: rgba(255, 255, 255, .8);
        font-size: .5625rem;
        line-height: 1.15;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .learning-stat-small-value {
        overflow: hidden;
        font-size: 1rem;
        font-weight: 600;
        line-height: 1.15;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .learning-stat--blue {
        background: linear-gradient(135deg, rgba(5, 80, 125, .78), rgba(106, 174, 214, .34));
    }

    .learning-stat--dark-blue {
        background: linear-gradient(135deg, rgba(3, 44, 70, .86), rgba(5, 80, 125, .58));
    }

    .learning-stat--green {
        background: linear-gradient(135deg, rgba(22, 163, 74, .68), rgba(74, 222, 128, .24));
    }

    .learning-stat--cyan {
        background: linear-gradient(135deg, rgba(8, 145, 178, .68), rgba(103, 232, 249, .22));
    }

    .learning-stat--orange {
        background: linear-gradient(135deg, rgba(217, 119, 6, .72), rgba(251, 191, 36, .28));
    }

    @media (max-width: 575.98px) {
        /* Keep right-column menus inside the viewport on the two-column row. */
        .learning-filter-dropdown-row .dropdown:nth-child(even) .dropdown-menu.show {
            right: 0 !important;
            left: auto !important;
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

    /*
     * Learning On Going filter layer
     *
     * The generic card rule above intentionally clips regular cards, but a
     * dropdown cannot escape an overflow-clipping ancestor. Keep that behavior
     * for learning cards and open only the filter's ancestor chain.
     */
    #learning-tab-content,
    .learning-section-wrapper,
    .learning-section-wrapper > .learning-section-padding,
    .learning-filter-card,
    .learning-filter-card > .card-body,
    .learning-filter-card form {
        overflow: visible !important;
    }

    /* Match the filter edges to the existing Hero inside .container-xl. */
    .learning-section-wrapper > .learning-section-padding {
        width: 100%;
        max-width: none;
        margin-right: 0;
        margin-left: 0;
        padding-right: 0 !important;
        padding-left: 0 !important;
    }

    .learning-filter-card {
        position: relative;
        border-radius: 22px !important;
    }

    .learning-filter-dropdown-row,
    .learning-filter-dropdown-row .dropdown {
        position: relative;
        overflow: visible;
    }

    /*
     * Override Popper's per-item flip so the four quick filters never become
     * a mixed up/down row. All menus consistently open below their trigger.
     */
    .learning-filter-dropdown-row .dropdown-menu {
        z-index: 1085;
    }

    .learning-filter-dropdown-row .dropdown-menu.show {
        top: 100% !important;
        right: auto !important;
        bottom: auto !important;
        left: 0 !important;
        transform: translateY(.25rem) !important;
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
        background: #05507D;
        border-color: #05507D;
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
        background: #6AAED6;
        border-color: #6AAED6;
        color: #fff;
    }
</style>
@endsection
