@section('style')
<link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">
{{-- Learning Record: style tabel client-side (dipisah — clean code). --}}
<link rel="stylesheet" href="{{ asset('css/learning-record.css') }}?v={{ filemtime(public_path('css/learning-record.css')) }}">
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
    .learning-filter-card form,
    .my-task-filter-card,
    .my-task-filter-card > .card-body,
    .my-task-filter-card form,
    .my-task-toolbar {
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

    /*
     * Live Autocomplete Dropdown Search for Ongoing, Assessment & Certificate Learning
     */
    .search-ongoing-container,
    .assessment-search-container,
    .certificate-search-container {
        position: relative;
        width: 100%;
    }

    #ongoing-search-results,
    #assessment-search-results,
    #certificate-search-results {
        position: absolute;
        top: 100%;
        left: 0;
        z-index: 1085;
        max-height: 300px;
        border-radius: 8px !important;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12) !important;
        border: 1px solid #e2e8f0 !important;
        background-color: #ffffff !important;
        background: #ffffff !important;
        overflow: hidden !important;
    }

    .autocomplete-list-group,
    #ongoing-autocomplete-list,
    #assessment-autocomplete-list,
    #certificate-autocomplete-list {
        background-color: #ffffff !important;
        background: #ffffff !important;
    }

    #ongoing-search-results .dropdown-item,
    #assessment-search-results .dropdown-item,
    #certificate-search-results .dropdown-item {
        background-color: #ffffff !important;
        color: #333333 !important;
        padding: .6rem .85rem;
        transition: background-color .15s ease;
        text-decoration: none;
    }

    #ongoing-search-results .dropdown-item:hover,
    #ongoing-search-results .dropdown-item:focus,
    #assessment-search-results .dropdown-item:hover,
    #assessment-search-results .dropdown-item:focus,
    #certificate-search-results .dropdown-item:hover,
    #certificate-search-results .dropdown-item:focus {
        background-color: #f8f9fa !important;
    }

    #ongoing-search-results .dropdown-item:not(:last-child),
    #assessment-search-results .dropdown-item:not(:last-child),
    #certificate-search-results .dropdown-item:not(:last-child) {
        border-bottom: 1px solid rgba(0, 0, 0, .05);
    }

    .search-footer,
    #ongoing-search-footer,
    #assessment-search-footer,
    #certificate-search-footer {
        background-color: #f8f9fa !important;
        border-top: 1px solid #e2e8f0 !important;
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
        cursor: pointer;
        user-select: none;
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    }

    .learning-modern-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--learning-ui-shadow-hover) !important;
    }

    .learning-modern-card * {
        cursor: inherit;
    }

    .learning-modern-card .btn {
        cursor: pointer !important;
    }

    .learning-modern-card .btn:disabled,
    .learning-modern-card .btn.disabled {
        cursor: not-allowed !important;
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

    [data-bs-theme="dark"] #ongoing-search-results,
    [data-bs-theme="dark"] #assessment-search-results,
    [data-bs-theme="dark"] #certificate-search-results,
    body.dark-mode #ongoing-search-results,
    body.dark-mode #assessment-search-results,
    body.dark-mode #certificate-search-results {
        background: #152235;
        border-color: rgba(255, 255, 255, 0.08);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
    }

    [data-bs-theme="dark"] #ongoing-search-results .dropdown-item,
    [data-bs-theme="dark"] #assessment-search-results .dropdown-item,
    [data-bs-theme="dark"] #certificate-search-results .dropdown-item,
    body.dark-mode #ongoing-search-results .dropdown-item,
    body.dark-mode #assessment-search-results .dropdown-item,
    body.dark-mode #certificate-search-results .dropdown-item {
        color: #f1f5f9;
    }

    [data-bs-theme="dark"] #ongoing-search-results .dropdown-item:not(:last-child),
    [data-bs-theme="dark"] #assessment-search-results .dropdown-item:not(:last-child),
    [data-bs-theme="dark"] #certificate-search-results .dropdown-item:not(:last-child),
    body.dark-mode #ongoing-search-results .dropdown-item:not(:last-child),
    body.dark-mode #assessment-search-results .dropdown-item:not(:last-child),
    body.dark-mode #certificate-search-results .dropdown-item:not(:last-child) {
        border-bottom-color: rgba(255, 255, 255, 0.06);
    }

    [data-bs-theme="dark"] #ongoing-search-results .dropdown-item:hover,
    [data-bs-theme="dark"] #ongoing-search-results .dropdown-item:focus,
    [data-bs-theme="dark"] #assessment-search-results .dropdown-item:hover,
    [data-bs-theme="dark"] #assessment-search-results .dropdown-item:focus,
    [data-bs-theme="dark"] #certificate-search-results .dropdown-item:hover,
    [data-bs-theme="dark"] #certificate-search-results .dropdown-item:focus,
    body.dark-mode #ongoing-search-results .dropdown-item:hover,
    body.dark-mode #ongoing-search-results .dropdown-item:focus,
    body.dark-mode #assessment-search-results .dropdown-item:hover,
    body.dark-mode #assessment-search-results .dropdown-item:focus,
    body.dark-mode #certificate-search-results .dropdown-item:hover,
    body.dark-mode #certificate-search-results .dropdown-item:focus {
        background: rgba(106, 174, 214, 0.14);
    }

    [data-bs-theme="dark"] #ongoing-search-results .dropdown-item .text-dark,
    [data-bs-theme="dark"] #assessment-search-results .dropdown-item .text-dark,
    [data-bs-theme="dark"] #certificate-search-results .dropdown-item .text-dark,
    body.dark-mode #ongoing-search-results .dropdown-item .text-dark,
    body.dark-mode #assessment-search-results .dropdown-item .text-dark,
    body.dark-mode #certificate-search-results .dropdown-item .text-dark {
        color: #f1f5f9 !important;
    }

    button.attendance-detail-trigger {
        border: 0;
        cursor: pointer;
        font-family: inherit;
        text-decoration: none;
        user-select: none;
        transition: transform 0.15s ease, box-shadow 0.15s ease, filter 0.15s ease;
    }
    button.attendance-detail-trigger:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.12);
        filter: brightness(0.95);
    }
    button.attendance-detail-trigger:active {
        transform: translateY(0);
        box-shadow: none;
        filter: brightness(0.9);
    }
    button.attendance-detail-trigger:focus-visible {
        outline: 2px solid var(--tblr-primary, #206bc4);
        outline-offset: 2px;
        border-radius: 4px;
    }

    /* ── Unified Quick Filter UI (Standardized Across All Learning Tabs) ─────── */
    .my-task-filter-card,
    .learning-quick-filter-card {
        background: #ffffff;
        border: 1px solid #f1f5f9;
        overflow: visible !important;
        position: relative !important;
        z-index: 40 !important;
    }

    .my-task-filter-card .card-body,
    .learning-quick-filter-card .card-body {
        overflow: visible !important;
        position: relative;
    }

    #learning-tab-content > .my-task-filter-card,
    #learning-tab-content > .learning-quick-filter-card {
        overflow: visible !important;
    }

    .my-task-filter-title,
    .learning-filter-title {
        color: #0f172a;
        font-size: 1.25rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .my-task-filter-subtitle,
    .learning-filter-subtitle {
        color: #64748b;
        font-size: 0.875rem;
    }

    /* Category Filter Pills */
    .my-task-pills-row,
    .learning-pills-row {
        margin-top: 0.75rem;
    }

    .my-task-category-pill,
    .learning-category-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        padding: 0.45rem 1.25rem;
        border-radius: 50rem;
        font-size: 0.8125rem;
        font-weight: 500;
        line-height: 1.2;
        text-decoration: none;
        white-space: nowrap;
        background-color: #f1f5f9;
        color: #334155;
        border: none;
        outline: none;
        cursor: pointer;
        transition: all 0.15s ease-in-out;
    }

    .my-task-category-pill:hover,
    .learning-category-pill:hover {
        background-color: #e2e8f0;
        color: #0f172a;
        text-decoration: none;
    }

    /* Active State: Soft sky blue with bright blue text (Task Reference Style) */
    .my-task-category-pill.active,
    .learning-category-pill.active {
        background-color: #e0f2fe !important;
        color: #0284c7 !important;
        font-weight: 600;
    }

    /* Divider */
    .my-task-divider,
    .learning-divider {
        border-top: 1px solid #e2e8f0;
        opacity: 0.7;
        margin: 1rem 0;
    }

    /* Toolbar & Dropdowns */
    .my-task-toolbar,
    .learning-toolbar {
        gap: 0.5rem;
        overflow: visible !important;
        position: relative;
    }

    .my-task-toolbar .dropdown,
    .learning-toolbar .dropdown {
        position: relative;
    }

    .my-task-toolbar .dropdown-menu,
    .learning-toolbar .dropdown-menu {
        z-index: 1060 !important;
        min-width: 175px;
        border-radius: 10px !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.15), 0 8px 10px -6px rgba(15, 23, 42, 0.08) !important;
        padding: 0.35rem 0;
    }

    .my-task-toolbar .dropdown-item,
    .learning-toolbar .dropdown-item {
        font-size: 0.8125rem;
        padding: 0.45rem 1rem;
        color: #334155;
        transition: all 0.15s ease-in-out;
    }

    .my-task-toolbar .dropdown-item:hover,
    .learning-toolbar .dropdown-item:hover {
        background-color: #f1f5f9;
        color: #0f172a;
    }

    .my-task-toolbar .dropdown-item.active,
    .my-task-toolbar .dropdown-item:active,
    .learning-toolbar .dropdown-item.active,
    .learning-toolbar .dropdown-item:active {
        background-color: #e0f2fe !important;
        color: #0284c7 !important;
        font-weight: 600;
    }

    .my-task-dropdown-btn,
    .learning-dropdown-btn {
        height: 38px;
        padding: 0.45rem 0.85rem;
        font-size: 0.8125rem;
        font-weight: 400;
        color: #334155;
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px !important;
        display: inline-flex;
        align-items: center;
        justify-content: space-between;
        transition: all 0.15s ease-in-out;
        box-shadow: none !important;
        white-space: nowrap;
    }

    .my-task-dropdown-btn:hover,
    .learning-dropdown-btn:hover {
        background-color: #f8fafc;
        border-color: #cbd5e1;
        color: #0f172a;
    }

    .my-task-dropdown-btn:focus,
    .my-task-dropdown-btn.show,
    .learning-dropdown-btn:focus,
    .learning-dropdown-btn.show {
        border-color: #0284c7;
        box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.15) !important;
    }

    .my-task-dropdown-btn.active,
    .learning-dropdown-btn.active {
        border-color: #0284c7;
        color: #0284c7;
        background-color: #f0f9ff;
    }

    .my-task-dropdown-btn::after,
    .learning-dropdown-btn::after {
        margin-left: 0.55rem;
        vertical-align: 0.15em;
    }

    /* Search input */
    .my-task-search-wrapper,
    .learning-search-wrapper {
        position: relative;
        flex-grow: 1;
        min-width: 200px;
    }

    .my-task-search-input,
    .learning-search-input {
        height: 38px;
        border-radius: 8px !important;
        padding-left: 2.25rem;
        padding-right: 2rem;
        font-size: 0.8125rem;
        border: 1px solid #e2e8f0;
        background-color: #ffffff;
        color: #334155;
        transition: all 0.15s ease-in-out;
    }

    .my-task-search-input:focus,
    .learning-search-input:focus {
        background-color: #ffffff;
        border-color: #0284c7;
        box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.15);
    }

    .my-task-search-input::placeholder,
    .learning-search-input::placeholder {
        color: #94a3b8;
    }

    /* Square Action Buttons (38px x 38px, rounded 8px) */
    .my-task-btn-square,
    .learning-btn-square {
        width: 38px !important;
        height: 38px !important;
        min-width: 38px !important;
        min-height: 38px !important;
        max-width: 38px !important;
        max-height: 38px !important;
        padding: 0 !important;
        border-radius: 8px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        flex-shrink: 0 !important;
        box-shadow: none !important;
        transition: all 0.15s ease-in-out;
        line-height: 1 !important;
        text-decoration: none !important;
    }

    .my-task-btn-square i,
    .learning-btn-square i {
        font-size: 1.15rem !important;
        line-height: 1 !important;
    }

    .my-task-btn-search,
    .learning-btn-search {
        background-color: #0054a6 !important;
        border-color: #0054a6 !important;
        color: #ffffff !important;
    }

    .my-task-btn-search:hover,
    .learning-btn-search:hover {
        background-color: #004085 !important;
        border-color: #004085 !important;
        color: #ffffff !important;
    }

    .my-task-btn-filter,
    .my-task-btn-reset,
    .my-task-btn-export,
    .learning-btn-filter,
    .learning-btn-reset,
    .learning-btn-export {
        background-color: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        color: #475569 !important;
    }

    .my-task-btn-filter:hover,
    .my-task-btn-reset:hover,
    .my-task-btn-export:hover,
    .learning-btn-filter:hover,
    .learning-btn-reset:hover,
    .learning-btn-export:hover {
        background-color: #f8fafc !important;
        border-color: #cbd5e1 !important;
        color: #0f172a !important;
    }

    /* Badge counter on square filter button */
    .my-task-btn-square .badge,
    .learning-btn-square .badge {
        position: absolute;
        top: -5px;
        right: -5px;
        font-size: 0.65rem;
        padding: 0.2rem 0.35rem;
        border-radius: 50rem;
    }

    /* Dark mode adjustments */
    [data-bs-theme="dark"] .my-task-filter-card,
    body.dark-mode .my-task-filter-card,
    [data-bs-theme="dark"] .learning-quick-filter-card,
    body.dark-mode .learning-quick-filter-card {
        background: #1e293b;
        border-color: #334155;
    }

    [data-bs-theme="dark"] .my-task-filter-title,
    body.dark-mode .my-task-filter-title,
    [data-bs-theme="dark"] .learning-filter-title,
    body.dark-mode .learning-filter-title {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .my-task-filter-subtitle,
    body.dark-mode .my-task-filter-subtitle,
    [data-bs-theme="dark"] .learning-filter-subtitle,
    body.dark-mode .learning-filter-subtitle {
        color: #94a3b8 !important;
    }

    [data-bs-theme="dark"] .my-task-category-pill,
    body.dark-mode .my-task-category-pill,
    [data-bs-theme="dark"] .learning-category-pill,
    body.dark-mode .learning-category-pill {
        background-color: #334155;
        color: #cbd5e1;
    }

    [data-bs-theme="dark"] .my-task-category-pill:hover,
    body.dark-mode .my-task-category-pill:hover,
    [data-bs-theme="dark"] .learning-category-pill:hover,
    body.dark-mode .learning-category-pill:hover {
        background-color: #475569;
        color: #ffffff;
    }

    [data-bs-theme="dark"] .my-task-category-pill.active,
    body.dark-mode .my-task-category-pill.active,
    [data-bs-theme="dark"] .learning-category-pill.active,
    body.dark-mode .learning-category-pill.active {
        background-color: rgba(2, 132, 199, 0.25) !important;
        color: #38bdf8 !important;
    }

    [data-bs-theme="dark"] .my-task-divider,
    body.dark-mode .my-task-divider,
    [data-bs-theme="dark"] .learning-divider,
    body.dark-mode .learning-divider {
        border-color: #334155;
    }

    [data-bs-theme="dark"] .my-task-dropdown-btn,
    body.dark-mode .my-task-dropdown-btn,
    [data-bs-theme="dark"] .learning-dropdown-btn,
    body.dark-mode .learning-dropdown-btn {
        background-color: #1e293b;
        border-color: #334155;
        color: #cbd5e1;
    }

    [data-bs-theme="dark"] .my-task-search-input,
    body.dark-mode .my-task-search-input,
    [data-bs-theme="dark"] .learning-search-input,
    body.dark-mode .learning-search-input {
        background-color: #1e293b;
        border-color: #334155;
        color: #f1f5f9;
    }

    [data-bs-theme="dark"] .my-task-btn-filter,
    [data-bs-theme="dark"] .my-task-btn-reset,
    [data-bs-theme="dark"] .my-task-btn-export,
    body.dark-mode .my-task-btn-filter,
    body.dark-mode .my-task-btn-reset,
    body.dark-mode .my-task-btn-export,
    [data-bs-theme="dark"] .learning-btn-filter,
    [data-bs-theme="dark"] .learning-btn-reset,
    [data-bs-theme="dark"] .learning-btn-export,
    body.dark-mode .learning-btn-filter,
    body.dark-mode .learning-btn-reset,
    body.dark-mode .learning-btn-export {
        background-color: #1e293b !important;
        border-color: #334155 !important;
        color: #cbd5e1 !important;
    }
</style>
@endsection
