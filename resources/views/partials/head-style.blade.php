@php
    $url_tabler = asset('Tabler/dist/css/tabler.min.css?1684106062');
    $url_demo = asset('Tabler/dist/css/demo.min.css?1684106062');
@endphp

<link rel="stylesheet" href={{ $url_tabler }}>
<link rel="stylesheet" href={{ $url_demo }}>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<style>
    @import url('https://rsms.me/inter/inter.css');

    :root {
        --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
        --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;

        --tblr-primary-rgb: 37, 99, 235;
        --tblr-primary: rgb(var(--tblr-primary-rgb));
        --tblr-secondary: 29, 78, 216;
        --tblr-body-color: #0f172a;
        --tblr-bg-surface: #ffffff;
        --tblr-border-color: #e6e7e9;
        --tblr-muted: #64748b;

        --owl-primary-rgb: var(--tblr-primary-rgb);
        --owl-primary: var(--tblr-primary, rgb(var(--owl-primary-rgb)));
        --owl-secondary: var(--tblr-secondary, #1d4ed8);
        --owl-primary-light: #3b82f6;
        --owl-accent: #f8fbff;
        --owl-surface: var(--tblr-bg-surface, #ffffff);
        --owl-surface-alt: #f8fafc;
        --owl-text: var(--tblr-body-color, #0f172a);
        --owl-text-secondary: #475569;
        --owl-text-tertiary: #94a3b8;
        --owl-border: var(--tblr-border-color, #e6e7e9);
        --owl-border-soft: rgba(226, 232, 240, 0.75);
        --owl-shadow: 0 15px 40px rgba(15, 23, 42, 0.08);
        --owl-shadow-md: 0 12px 30px rgba(15, 23, 42, 0.08);
        --owl-shadow-lg: 0 18px 70px rgba(15, 23, 42, 0.12);
    }

    body[data-bs-theme="dark"], body.dark-mode {
        --owl-body-bg: #020617;
        --owl-body-color: #f8fafc;
        --owl-text: #f8fafc;
        --owl-text-secondary: #cbd5e1;
        --owl-text-tertiary: #94a3b8;
        --owl-surface: #111827;
        --owl-surface-alt: #1f2937;
        --owl-border: #334155;
        --owl-border-soft: rgba(148, 163, 184, 0.24);
        --owl-shadow: 0 15px 40px rgba(15, 23, 42, 0.32);
        --owl-shadow-md: 0 12px 30px rgba(15, 23, 42, 0.24);
        --owl-shadow-lg: 0 18px 70px rgba(15, 23, 42, 0.32);
    }

    body {
        font-feature-settings: "cv03", "cv04", "cv11";
        color: var(--owl-body-color);
        background: var(--owl-body-bg);
        transition: background 0.25s ease, color 0.25s ease;
    }
    /* .navbar-vertical {
        background-color: #ffffff !important;
        border-right: 1px solid #e6e7e9 !important;
    } */

        .navbar-vertical {
        background-color: var(--owl-surface) !important;
        border-right: 1px solid var(--owl-border) !important;
    }

    .navbar-vertical .nav-link,
    .navbar-vertical .nav-link-icon,
    .navbar-vertical .nav-link-title,
    .navbar-vertical .ti {
        /* color: #111827 !important; */
        color: var(--owl-body-color) !important;
    }

    .navbar-vertical .nav-link {
        display: flex;
        align-items: center;
        gap: 10px;
        border-radius: 12px;
        margin-bottom: 6px;
        font-weight: 600;
        transition: all .2s ease;
    }

    .navbar-vertical .nav-link:hover,
    .navbar-vertical .nav-item.active > .nav-link {
        background: #eef3ff !important;
        color: #206bc4 !important;
        /* background: rgba(37, 99, 235, 0.12) !important;
        color: var(--tblr-primary) !important; */
    }

    .navbar-vertical .nav-link:hover .ti,
    .navbar-vertical .nav-link:hover .nav-link-title,
    .navbar-vertical .nav-item.active > .nav-link .ti,
    .navbar-vertical .nav-item.active > .nav-link .nav-link-title {
        /* color: #206bc4 !important; */
        color: var(--tblr-primary) !important;


    }

    .navbar-vertical .badge {
        margin-left: auto;
    }

    .navbar-vertical .dropdown-menu {
        border-radius: 12px;
        /* border: 1px solid #e6e7e9; */
        border: 1px solid var(--owl-border);
        background: var(--owl-surface);
    }

    .navbar-vertical .dropdown-item {
        display: flex;
        align-items: center;
        /* color: #111827 !important; */
        color: var(--owl-body-color) !important;
        font-weight: 500;
    }

    .navbar-vertical .dropdown-item .ti {
        /* color: #111827 !important; */
        color: var(--owl-body-color) !important;
    }

    .avatar-default-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        /* background: #eef3ff;
        color: #206bc4; */
        background: var(--tblr-primary-lt);
        color: var(--tblr-primary);
        font-size: 18px;
        font-weight: 600;
    }

    [data-bs-theme="dark"] .navbar-vertical,
    body[data-bs-theme="dark"] .navbar-vertical {
        background-color: #111827 !important;
        border-right: 1px solid #1f2937 !important;
    }

    [data-bs-theme="dark"] .navbar-vertical .nav-link,
    [data-bs-theme="dark"] .navbar-vertical .nav-link-icon,
    [data-bs-theme="dark"] .navbar-vertical .nav-link-title,
    [data-bs-theme="dark"] .navbar-vertical .ti,
    body[data-bs-theme="dark"] .navbar-vertical .nav-link,
    body[data-bs-theme="dark"] .navbar-vertical .nav-link-icon,
    body[data-bs-theme="dark"] .navbar-vertical .nav-link-title,
    body[data-bs-theme="dark"] .navbar-vertical .ti {
        color: #f9fafb !important;
    }

    [data-bs-theme="dark"] .navbar-vertical .nav-link:hover,
    [data-bs-theme="dark"] .navbar-vertical .nav-item.active > .nav-link,
    body[data-bs-theme="dark"] .navbar-vertical .nav-link:hover,
    body[data-bs-theme="dark"] .navbar-vertical .nav-item.active > .nav-link {
        background: rgba(255,255,255,.10) !important;
    }

    [data-bs-theme="dark"] .navbar-vertical .dropdown-menu,
    body[data-bs-theme="dark"] .navbar-vertical .dropdown-menu {
        background: #1f2937 !important;
        border-color: #374151 !important;
    }

    [data-bs-theme="dark"] .navbar-vertical .dropdown-item,
    [data-bs-theme="dark"] .navbar-vertical .dropdown-item .ti,
    body[data-bs-theme="dark"] .navbar-vertical .dropdown-item,
    body[data-bs-theme="dark"] .navbar-vertical .dropdown-item .ti {
        color: #f9fafb !important;
    }

    .navbar-vertical,
    .navbar-vertical .navbar-collapse,
    .navbar-vertical .navbar-nav {
        overflow: visible;
    }

    .navbar-vertical .dropdown-menu {
        z-index: 1050;
    }

    .app-topbar {
        position: relative;
        z-index: 1030;
        background: var(--tblr-bg-surface, #ffffff);
        border-bottom: 1px solid var(--tblr-border-color, #e6e7e9);
    }

    .app-topbar .ti,
    .app-topbar .nav-link {
        color: var(--tblr-body-color);
    }

    .topbar-search {
        max-width: 520px;
        min-width: 260px;
        margin-left: 1rem;
        margin-right: 1rem;
    }

    .topbar-search .form-control {
        background: var(--tblr-bg-surface);
        color: var(--tblr-body-color);
        border-color: var(--tblr-border-color);
    }

    .topbar-search .form-control::placeholder {
        color: var(--tblr-muted);
    }

    .topbar-search .input-icon-addon {
        color: var(--tblr-muted);
    }

    .badge-notification {
        position: absolute;
        top: 2px;
        right: -6px;
        min-width: 16px;
        height: 16px;
        padding: 2px 5px;
        font-size: 10px;
        line-height: 12px;
        border-radius: 999px;
    }

    .avatar-default-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--tblr-primary-lt);
        color: var(--tblr-primary);
        font-size: 18px;
    }

    @media (max-width: 767.98px) {
        .topbar-search {
            display: none !important;
        }
    }

    /* all content  */
     .content-card {
        height: 100%;
        display: flex;
        flex-direction: column;
        border-radius: 18px;
        overflow: hidden;
    }

    .content-card .card-body {
        flex: 1;
        display: flex;
        flex-direction: column;
        padding: 18px;
    }

    .content-card-thumb {
        height: 170px;
        background-size: cover;
        background-position: center;
        background-color: #f1f5f9;
    }

    .content-card-title {
        min-height: 52px;
        font-size: 16px;
        line-height: 1.4;
    }

    .content-card-meta {
        font-size: 13px;
    }

    .content-card-action {
        margin-top: auto;
    }

    @media (max-width: 768px) {
        .content-card-thumb {
            height: 190px;
        }
    }


    [data-bs-theme="dark"] .card,
    [data-theme="dark"] .card,
    body.theme-dark .card {
        background-color: var(--tblr-bg-surface, #182433) !important;
        color: var(--tblr-body-color, #dce6f1) !important;
        border-color: var(--tblr-border-color, #25384d) !important;
    }

    [data-bs-theme="dark"] .card-header,
    [data-theme="dark"] .card-header,
    body.theme-dark .card-header {
        background-color: var(--tblr-bg-surface-secondary, #1f2d3d) !important;
        color: var(--tblr-body-color, #dce6f1) !important;
        border-color: var(--tblr-border-color, #25384d) !important;
    }

    [data-bs-theme="dark"] .card-body,
    [data-theme="dark"] .card-body,
    body.theme-dark .card-body {
        background-color: var(--tblr-bg-surface, #182433) !important;
        color: var(--tblr-body-color, #dce6f1) !important;
    }

    [data-bs-theme="dark"] .table,
    [data-theme="dark"] .table,
    body.theme-dark .table {
        color: var(--tblr-body-color, #dce6f1) !important;
        background-color: transparent !important;
        border-color: var(--tblr-border-color, #25384d) !important;
    }

    [data-bs-theme="dark"] .table thead th,
    [data-theme="dark"] .table thead th,
    body.theme-dark .table thead th {
        background-color: var(--tblr-bg-surface-secondary, #223247) !important;
        color: var(--tblr-muted, #8fa3b8) !important;
        border-color: var(--tblr-border-color, #25384d) !important;
    }

    [data-bs-theme="dark"] .table tbody tr,
    [data-theme="dark"] .table tbody tr,
    body.theme-dark .table tbody tr {
        background-color: var(--tblr-bg-surface, #182433) !important;
        color: var(--tblr-body-color, #dce6f1) !important;
    }

    [data-bs-theme="dark"] .table tbody td,
    [data-theme="dark"] .table tbody td,
    body.theme-dark .table tbody td {
        background-color: transparent !important;
        color: var(--tblr-body-color, #dce6f1) !important;
        border-color: var(--tblr-border-color, #25384d) !important;
    }

    [data-bs-theme="dark"] .table-hover tbody tr:hover,
    [data-bs-theme="dark"] .table-hover tbody tr:hover td,
    [data-theme="dark"] .table-hover tbody tr:hover,
    [data-theme="dark"] .table-hover tbody tr:hover td,
    body.theme-dark .table-hover tbody tr:hover,
    body.theme-dark .table-hover tbody tr:hover td {
        background-color: var(--tblr-bg-surface-secondary, #223247) !important;
        color: #ffffff !important;
    }

    [data-bs-theme="dark"] .bg-white,
    [data-theme="dark"] .bg-white,
    body.theme-dark .bg-white {
        background-color: var(--tblr-bg-surface, #182433) !important;
    }

    [data-bs-theme="dark"] .bg-light,
    [data-theme="dark"] .bg-light,
    body.theme-dark .bg-light {
        background-color: var(--tblr-bg-surface-secondary, #223247) !important;
    }

    [data-bs-theme="dark"] .text-dark,
    [data-theme="dark"] .text-dark,
    body.theme-dark .text-dark {
        color: var(--tblr-body-color, #dce6f1) !important;
    }

    [data-bs-theme="dark"] .text-muted,
    [data-theme="dark"] .text-muted,
    body.theme-dark .text-muted {
        color: var(--tblr-muted, #8fa3b8) !important;
    }

    [data-bs-theme="dark"] .form-control,
    [data-bs-theme="dark"] .form-select,
    [data-theme="dark"] .form-control,
    [data-theme="dark"] .form-select,
    body.theme-dark .form-control,
    body.theme-dark .form-select {
        background-color: var(--tblr-bg-surface, #111827) !important;
        color: var(--tblr-body-color, #ffffff) !important;
        border-color: var(--tblr-border-color, #25384d) !important;
    }

    [data-bs-theme="dark"] .form-control:focus,
    [data-bs-theme="dark"] .form-select:focus,
    [data-theme="dark"] .form-control:focus,
    [data-theme="dark"] .form-select:focus,
    body.theme-dark .form-control:focus,
    body.theme-dark .form-select:focus {
        background-color: var(--tblr-bg-surface, #111827) !important;
        color: #ffffff !important;
        border-color: var(--tblr-primary, #206bc4) !important;
        box-shadow: 0 0 0 .25rem rgba(32, 107, 196, .25) !important;
    }

    [data-bs-theme="dark"] .btn-light,
    [data-theme="dark"] .btn-light,
    body.theme-dark .btn-light {
        background-color: var(--tblr-bg-surface-secondary, #223247) !important;
        border-color: var(--tblr-border-color, #33465f) !important;
        color: #ffffff !important;
    }

    [data-bs-theme="dark"] .page-title,
    [data-theme="dark"] .page-title,
    body.theme-dark .page-title {
        color: var(--tblr-body-color, #ffffff) !important;
    }

    [data-bs-theme="dark"] .breadcrumb a,
    [data-theme="dark"] .breadcrumb a,
    body.theme-dark .breadcrumb a {
        color: var(--tblr-primary, #4299e1) !important;
    }

    [data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-selection,
    [data-theme="dark"] .select2-container--bootstrap-5 .select2-selection,
    body.theme-dark .select2-container--bootstrap-5 .select2-selection {
        background-color: var(--tblr-bg-surface, #111827) !important;
        color: #ffffff !important;
        border-color: var(--tblr-border-color, #25384d) !important;
    }

    [data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-selection__rendered,
    [data-theme="dark"] .select2-container--bootstrap-5 .select2-selection__rendered,
    body.theme-dark .select2-container--bootstrap-5 .select2-selection__rendered {
        color: #ffffff !important;
    }

    [data-bs-theme="dark"] .select2-dropdown,
    [data-theme="dark"] .select2-dropdown,
    body.theme-dark .select2-dropdown {
        background-color: var(--tblr-bg-surface, #182433) !important;
        border-color: var(--tblr-border-color, #25384d) !important;
        color: #ffffff !important;
    }

    [data-bs-theme="dark"] .select2-results__option,
    [data-theme="dark"] .select2-results__option,
    body.theme-dark .select2-results__option {
        color: #ffffff !important;
    }

    [data-bs-theme="dark"] .select2-results__option--highlighted,
    [data-theme="dark"] .select2-results__option--highlighted,
    body.theme-dark .select2-results__option--highlighted {
        background-color: var(--tblr-primary, #206bc4) !important;
        color: #ffffff !important;
    }

</style>

{{-- Select2 --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
