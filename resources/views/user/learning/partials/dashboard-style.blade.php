@section('style')
<link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">
{{-- Learning Record: style tabel client-side (dipisah — clean code). --}}
<link rel="stylesheet" href="{{ asset('css/learning-record.css') }}">
<link rel="stylesheet" href="{{ asset('css/learning-assessment.css') }}?v={{ filemtime(public_path('css/learning-assessment.css')) }}">
<link rel="stylesheet" href="{{ asset('css/learning-certificate.css') }}">
<link rel="stylesheet" href="{{ asset('css/learning-ongoing.css') }}">
<link rel="stylesheet" href="{{ asset('css/mooc.css') }}">
@include('user.learning.partials.hero-style')

<style>
    :root {
        --learning-ui-radius: 20px;
        --learning-ui-surface: #ffffff;
        --learning-ui-border: rgba(0, 0, 0, .08);
        --learning-ui-text: #53565A;
        --learning-ui-muted: #8A8D8F;
        --learning-ui-shadow: 0 2px 12px rgba(83, 86, 90, .05);
        --learning-ui-shadow-hover: 0 10px 28px rgba(83, 86, 90, .10);
        --learning-btn-primary: #0F5E9C;
        --learning-btn-primary-hover: #0B4F84;
        --learning-btn-success-start: #22C55E;
        --learning-btn-success-end: #16A34A;
        --learning-btn-radius: 10px;
        --learning-btn-height-sm: 34px;
        --learning-btn-height-md: 42px;
        --learning-btn-height-lg: 46px;
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
        background: #fff;
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

    /* Shared My Learning button design system. */
    .btn-learning-primary,
    .btn-learning-success,
    .btn-learning-secondary,
    .btn-learning-outline,
    .btn-filter-chip,
    .btn-action,
    .btn-learning-tab {
        display: inline-flex;
        min-height: var(--learning-btn-height-md);
        align-items: center;
        justify-content: center;
        gap: .45rem;
        padding: .55rem 1rem;
        border-radius: var(--learning-btn-radius) !important;
        font-weight: 600;
        line-height: 1.2;
        white-space: nowrap;
        transition:
            transform .18s ease,
            box-shadow .18s ease,
            color .18s ease,
            background-color .18s ease,
            border-color .18s ease;
    }

    .btn-learning-sm {
        min-height: var(--learning-btn-height-sm);
        padding: .4rem .75rem;
    }

    .btn-learning-lg {
        min-height: var(--learning-btn-height-lg);
        padding: .7rem 1.25rem;
    }

    .btn-learning-primary {
        border-color: var(--learning-btn-primary) !important;
        background: var(--learning-btn-primary) !important;
        color: #fff !important;
        box-shadow: 0 6px 14px rgba(15, 94, 156, .22);
    }

    .btn-learning-primary:hover,
    .btn-learning-primary:focus {
        border-color: var(--learning-btn-primary-hover) !important;
        background: var(--learning-btn-primary-hover) !important;
        color: #fff !important;
        box-shadow: 0 9px 20px rgba(15, 94, 156, .28);
        transform: translateY(-1px);
    }

    .btn-learning-success {
        border-color: var(--learning-btn-success-end) !important;
        background: linear-gradient(135deg, var(--learning-btn-success-start), var(--learning-btn-success-end)) !important;
        color: #fff !important;
        box-shadow: 0 6px 14px rgba(22, 163, 74, .22);
    }

    .btn-learning-success:hover,
    .btn-learning-success:focus {
        border-color: #15803D !important;
        background: linear-gradient(135deg, #20B957, #15803D) !important;
        color: #fff !important;
        box-shadow: 0 9px 20px rgba(22, 163, 74, .28);
        transform: translateY(-1px);
    }

    .btn-learning-certificate {
        display: inline-flex;
        width: 40px;
        min-width: 40px;
        height: 40px;
        min-height: 40px;
        align-items: center;
        justify-content: center;
        padding: 0;
        border: 1px solid #16A34A !important;
        border-radius: var(--learning-btn-radius) !important;
        background: #16A34A !important;
        color: #fff !important;
        box-shadow: 0 2px 6px rgba(22, 163, 74, .18);
        cursor: pointer;
        line-height: 1;
        transition:
            transform 200ms ease,
            box-shadow 200ms ease,
            background-color 200ms ease,
            border-color 200ms ease;
    }

    .btn-learning-certificate > i {
        margin: 0 !important;
        font-size: 1.25rem;
        line-height: 1;
    }

    .btn-learning-certificate:hover,
    .btn-learning-certificate:focus-visible {
        border-color: #15803D !important;
        background: #15803D !important;
        color: #fff !important;
        box-shadow: 0 4px 10px rgba(22, 163, 74, .22);
        transform: translateY(-1px);
    }

    .btn-learning-certificate:focus-visible {
        outline: 0;
        box-shadow:
            0 4px 10px rgba(22, 163, 74, .22),
            0 0 0 3px rgba(34, 197, 94, .18);
    }

    .btn-learning-certificate:active,
    .btn-learning-certificate.active {
        border-color: #166534 !important;
        background: #166534 !important;
        box-shadow: 0 1px 3px rgba(22, 101, 52, .2);
        transform: scale(.96);
    }

    .btn-learning-certificate:disabled,
    .btn-learning-certificate.disabled {
        border-color: #CBD5E1 !important;
        background: #E2E8F0 !important;
        color: #94A3B8 !important;
        box-shadow: none !important;
        cursor: not-allowed;
        opacity: 1;
        transform: none !important;
        pointer-events: none;
    }

    .btn-learning-secondary {
        border-color: rgba(15, 94, 156, .48) !important;
        background: #fff !important;
        color: var(--learning-btn-primary) !important;
    }

    .btn-learning-secondary:hover,
    .btn-learning-secondary:focus {
        border-color: var(--learning-btn-primary) !important;
        background: rgba(15, 94, 156, .08) !important;
        color: var(--learning-btn-primary-hover) !important;
        box-shadow: 0 6px 14px rgba(15, 94, 156, .12);
        transform: translateY(-1px);
    }

    .btn-learning-outline {
        border-color: #CBD5E1 !important;
        background: #fff !important;
        color: #64748B !important;
    }

    .btn-learning-outline:hover,
    .btn-learning-outline:focus {
        border-color: #94A3B8 !important;
        background: #F1F5F9 !important;
        color: #334155 !important;
        transform: translateY(-1px);
    }

    .btn-learning-primary:disabled,
    .btn-learning-success:disabled,
    .btn-learning-secondary:disabled,
    .btn-learning-outline:disabled,
    .btn-filter-chip:disabled,
    .btn-action:disabled,
    .btn-learning-tab:disabled,
    .btn-learning-primary.disabled,
    .btn-learning-success.disabled,
    .btn-learning-secondary.disabled,
    .btn-learning-outline.disabled {
        box-shadow: none !important;
        opacity: .58;
        transform: none !important;
    }

    .btn-learning-primary.learning-expired-btn:disabled {
        cursor: not-allowed;
        opacity: 1;
    }

    .learning-fast-filter-label {
        color: #64748B !important;
        font-weight: 600;
    }

    .learning-fast-filter {
        width: 100%;
        align-items: center;
        gap: 12px !important;
        padding: 0;
        border: 0;
        border-radius: 0;
        background: transparent;
        box-shadow: none;
    }

    .learning-fast-filter .learning-fast-filter-control {
        min-height: 40px;
        padding: 0 12px;
        border-color: transparent !important;
        background: transparent !important;
        color: #475569 !important;
        box-shadow: none;
        font-size: 13px;
        font-weight: 500;
        transform: none;
    }

    .learning-fast-filter .learning-fast-filter-control:hover {
        border-color: transparent !important;
        background: #F4F7FA !important;
        color: #334155 !important;
        box-shadow: none;
        transform: none;
    }

    .learning-fast-filter .learning-fast-filter-control:focus,
    .learning-fast-filter .learning-fast-filter-control:focus-visible {
        border-color: transparent !important;
        background: #fff !important;
        color: var(--learning-btn-primary) !important;
        box-shadow: 0 0 0 3px rgba(15, 94, 156, .14) !important;
        outline: 0;
        transform: none;
    }

    .learning-fast-filter.learning-quick-dropdowns .learning-fast-filter-control.active {
        border-color: transparent !important;
        background: rgba(15, 94, 156, .08) !important;
        color: var(--learning-btn-primary) !important;
        box-shadow: none;
        transform: none;
    }

    .learning-quick-dropdowns .dropdown-menu {
        padding: .4rem;
        border: 1px solid #E2E8F0;
        border-radius: var(--learning-btn-radius);
        box-shadow: 0 12px 28px rgba(15, 23, 42, .14);
    }

    .learning-quick-dropdowns .dropdown-item {
        border-radius: 7px;
        padding: .55rem .7rem;
        font-weight: 500;
        transition: color .15s ease, background-color .15s ease;
    }

    .learning-quick-dropdowns .dropdown-item:hover,
    .learning-quick-dropdowns .dropdown-item:focus {
        background: rgba(15, 94, 156, .08);
        color: var(--learning-btn-primary);
    }

    .learning-quick-dropdowns .dropdown-item.active {
        background: var(--learning-btn-primary) !important;
        color: #fff !important;
    }

    .btn-learning-tab {
        border: 1px solid #D6DEE8;
        background: #fff;
        color: #334155;
    }

    .btn-learning-tab.btn-primary {
        border-color: var(--learning-btn-primary) !important;
        background: var(--learning-btn-primary) !important;
        color: #fff !important;
        box-shadow: 0 6px 14px rgba(15, 94, 156, .22);
    }

    .btn-learning-tab.btn-white:hover,
    .btn-learning-tab.btn-white:focus {
        border-color: rgba(15, 94, 156, .48) !important;
        background: rgba(15, 94, 156, .08) !important;
        color: var(--learning-btn-primary) !important;
        transform: translateY(-1px);
    }

    .btn-action {
        border-color: rgba(15, 94, 156, .48) !important;
        background: #fff !important;
        color: var(--learning-btn-primary) !important;
    }

    .btn-action:hover,
    .btn-action:focus,
    .btn-action[aria-expanded="true"] {
        border-color: var(--learning-btn-primary) !important;
        background: rgba(15, 94, 156, .08) !important;
        color: var(--learning-btn-primary-hover) !important;
        box-shadow: 0 5px 12px rgba(15, 94, 156, .12);
    }

    .certificate-action-menu .dropdown-item.learning-action-success {
        color: #15803D;
    }

    .certificate-action-menu .dropdown-item.learning-action-success i {
        color: #16A34A;
    }

    .certificate-action-menu .dropdown-item.learning-action-success:hover,
    .certificate-action-menu .dropdown-item.learning-action-success:focus {
        background: rgba(34, 197, 94, .10);
        color: #166534;
    }

    .btn-learning-primary > i,
    .btn-learning-success > i,
    .btn-learning-secondary > i,
    .btn-learning-outline > i,
    .btn-filter-chip > i,
    .btn-action > i,
    .btn-learning-tab > i {
        margin: 0 !important;
        flex: 0 0 auto;
        font-size: 1.05rem;
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
        background: var(--tblr-body-bg, #111827);
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

    [data-bs-theme="dark"] .btn-learning-secondary,
    [data-bs-theme="dark"] .btn-learning-outline,
    [data-bs-theme="dark"] .btn-action,
    [data-bs-theme="dark"] .btn-learning-tab.btn-white,
    body.dark-mode .btn-learning-secondary,
    body.dark-mode .btn-learning-outline,
    body.dark-mode .btn-action,
    body.dark-mode .btn-learning-tab.btn-white {
        background: #192D45 !important;
        border-color: rgba(148, 163, 184, .28) !important;
        color: #E2E8F0 !important;
    }

    [data-bs-theme="dark"] .learning-fast-filter,
    body.dark-mode .learning-fast-filter {
        border-color: transparent;
        background: transparent;
        box-shadow: none;
    }

    [data-bs-theme="dark"] .learning-fast-filter .learning-fast-filter-control,
    body.dark-mode .learning-fast-filter .learning-fast-filter-control {
        border-color: transparent !important;
        background: transparent !important;
        color: #CBD5E1 !important;
    }

    [data-bs-theme="dark"] .learning-fast-filter .learning-fast-filter-control:hover,
    body.dark-mode .learning-fast-filter .learning-fast-filter-control:hover {
        background: rgba(148, 163, 184, .10) !important;
        color: #F1F5F9 !important;
    }

    [data-bs-theme="dark"] .learning-fast-filter .learning-fast-filter-control:focus,
    [data-bs-theme="dark"] .learning-fast-filter .learning-fast-filter-control:focus-visible,
    [data-bs-theme="dark"] .learning-fast-filter .learning-fast-filter-control.active,
    body.dark-mode .learning-fast-filter .learning-fast-filter-control:focus,
    body.dark-mode .learning-fast-filter .learning-fast-filter-control:focus-visible,
    body.dark-mode .learning-fast-filter .learning-fast-filter-control.active {
        background: rgba(106, 174, 214, .14) !important;
        color: #93C5FD !important;
    }

    [data-bs-theme="dark"] .learning-quick-dropdowns .dropdown-menu,
    body.dark-mode .learning-quick-dropdowns .dropdown-menu {
        background: #152235;
        border-color: rgba(148, 163, 184, .18);
    }

    [data-bs-theme="dark"] .learning-quick-dropdowns .dropdown-item,
    body.dark-mode .learning-quick-dropdowns .dropdown-item {
        color: #E2E8F0;
    }

    [data-bs-theme="dark"] .learning-quick-dropdowns .dropdown-item:hover,
    [data-bs-theme="dark"] .learning-quick-dropdowns .dropdown-item:focus,
    body.dark-mode .learning-quick-dropdowns .dropdown-item:hover,
    body.dark-mode .learning-quick-dropdowns .dropdown-item:focus {
        background: rgba(106, 174, 214, .14);
        color: #93C5FD;
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
