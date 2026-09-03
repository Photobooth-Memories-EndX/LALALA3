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

        --tblr-primary-rgb: 5, 80, 125;
        --tblr-primary: rgb(var(--tblr-primary-rgb));
        --tblr-primary-lt: rgba(var(--tblr-primary-rgb), .12);
        --tblr-secondary-rgb: 106, 174, 214;
        --tblr-secondary: #6AAED6;
        --tblr-secondary-lt: rgba(var(--tblr-secondary-rgb), .18);
        --tblr-danger-rgb: 239, 51, 64;
        --tblr-danger: #EF3340;
        --tblr-danger-lt: rgba(var(--tblr-danger-rgb), .12);
        --tblr-warning-rgb: 255, 184, 28;
        --tblr-warning: #FFB81C;
        --tblr-warning-lt: rgba(var(--tblr-warning-rgb), .14);
        --tblr-success-rgb: 106, 174, 214;
        --tblr-success: #6AAED6;
        --tblr-success-lt: rgba(var(--tblr-success-rgb), .16);
        --tblr-body-color: #53565A;
        --tblr-bg-surface: #ffffff;
        --tblr-border-color: #D9D9D6;
        --tblr-muted: #8A8D8F;

        --owl-primary-rgb: var(--tblr-primary-rgb);
        --owl-primary: var(--tblr-primary, rgb(var(--owl-primary-rgb)));
        --owl-secondary: var(--tblr-secondary, #6AAED6);
        --owl-primary-light: #6AAED6;
        --owl-accent: #D9D9D6;
        --owl-surface: var(--tblr-bg-surface, #ffffff);
        --owl-surface-alt: #D9D9D6;
        --owl-text: var(--tblr-body-color, #53565A);
        --owl-text-secondary: #53565A;
        --owl-text-tertiary: #8A8D8F;
        --owl-border: var(--tblr-border-color, #D9D9D6);
        --owl-border-soft: rgba(217, 217, 214, 0.75);
        --owl-shadow: 0 15px 40px rgba(83, 86, 90, 0.08);
        --owl-shadow-md: 0 12px 30px rgba(83, 86, 90, 0.08);
        --owl-shadow-lg: 0 18px 70px rgba(83, 86, 90, 0.12);
    }

    body[data-bs-theme="dark"], body.dark-mode {
        --owl-body-bg: var(--tblr-body-bg);
        --owl-body-color: #ffffff;
        --owl-text: #ffffff;
        --owl-text-secondary: #D9D9D6;
        --owl-text-tertiary: #BBBCBC;
        --owl-surface: #53565A;
        --owl-surface-alt: #8A8D8F;
        --owl-border: #8A8D8F;
        --owl-border-soft: rgba(138, 141, 143, 0.24);
        --owl-shadow: 0 15px 40px rgba(83, 86, 90, 0.32);
        --owl-shadow-md: 0 12px 30px rgba(83, 86, 90, 0.24);
        --owl-shadow-lg: 0 18px 70px rgba(83, 86, 90, 0.32);
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

    .navbar-vertical .owl-sidebar-brand,
    .navbar-vertical .owl-sidebar-brand > a {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .navbar-vertical .owl-sidebar-brand {
        flex: 1 1 auto;
        min-width: 0;
        padding: 2px 12px;
    }

    .navbar-vertical .owl-sidebar-logo {
        display: block;
        width: 164px;
        height: auto;
        max-width: 100%;
        object-fit: contain;
    }

    @media (min-width: 992px) {
        .navbar-vertical .owl-sidebar-brand {
            flex: none;
            width: 100%;
            min-height: 104px;
            padding: 3px 16px;
        }

        .navbar-vertical .owl-sidebar-brand > a {
            width: 100%;
        }

        .navbar-vertical .owl-sidebar-nav {
            padding-top: 4px;
        }
    }

    @media (max-width: 991.98px) {
        .navbar-vertical .owl-sidebar-logo {
            width: 132px;
        }
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
        background: rgba(var(--tblr-primary-rgb), .12) !important;
        color: var(--tblr-primary) !important;
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

    /* =====================================================
       DESKTOP SIDEBAR FIXED FOOTER LOGOUT & SCROLLABLE MENU (>= 992px)
       ===================================================== */
    @media (min-width: 992px) {
        .navbar-vertical .container-fluid {
            display: flex !important;
            flex-direction: column !important;
            height: 100vh !important;
            max-height: 100vh !important;
            padding-bottom: 0 !important;
            overflow: hidden !important;
        }

        .navbar-vertical #sidebar-menu {
            flex: 1 1 auto !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
            overflow: hidden !important;
            min-height: 0 !important;
        }

        .navbar-vertical .owl-sidebar-nav {
            flex: 1 1 auto !important;
            overflow-y: auto !important;
            padding-top: 4px;
            padding-bottom: 12px;
        }

        .navbar-vertical .owl-sidebar-footer {
            flex: 0 0 auto !important;
            margin-top: auto !important;
            padding-top: 10px !important;
            padding-bottom: 16px !important;
            border-top: 1px solid var(--owl-border, #e6e7e9) !important;
        }
    }

    @media (max-width: 991.98px) {
        .navbar-vertical .owl-sidebar-footer {
            margin-top: 12px !important;
            padding-top: 10px !important;
            padding-bottom: 8px !important;
            border-top: 1px solid var(--owl-border, #e6e7e9) !important;
        }
    }

    .navbar-vertical .owl-sidebar-logout-btn {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 0.5rem 0.75rem;
        border-radius: 12px;
        background: transparent;
        border: 0;
        cursor: pointer;
        font-weight: 600;
        text-align: left;
        color: var(--owl-body-color) !important;
        transition: all .2s ease;
    }

    .navbar-vertical .owl-sidebar-logout-btn .ti,
    .navbar-vertical .owl-sidebar-logout-btn .nav-link-title {
        color: var(--owl-body-color) !important;
        transition: color .2s ease;
    }

    .navbar-vertical .owl-sidebar-logout-btn:hover {
        background: rgba(var(--tblr-danger-rgb, 239, 51, 64), .12) !important;
    }

    .navbar-vertical .owl-sidebar-logout-btn:hover .ti,
    .navbar-vertical .owl-sidebar-logout-btn:hover .nav-link-title {
        color: var(--tblr-danger, #EF3340) !important;
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
    body[data-bs-theme="dark"] .navbar-vertical,
    body.dark-mode .navbar-vertical {
        background-color: var(--tblr-body-bg) !important;
        border-color: var(--tblr-border-color) !important;
    }

    [data-bs-theme="dark"] .navbar-vertical .nav-link,
    [data-bs-theme="dark"] .navbar-vertical .nav-link-icon,
    [data-bs-theme="dark"] .navbar-vertical .nav-link-title,
    [data-bs-theme="dark"] .navbar-vertical .ti,
    body[data-bs-theme="dark"] .navbar-vertical .nav-link,
    body[data-bs-theme="dark"] .navbar-vertical .nav-link-icon,
    body[data-bs-theme="dark"] .navbar-vertical .nav-link-title,
    body[data-bs-theme="dark"] .navbar-vertical .ti {
        color: #ffffff !important;
    }

    [data-bs-theme="dark"] .navbar-vertical .nav-link:hover,
    [data-bs-theme="dark"] .navbar-vertical .nav-item.active > .nav-link,
    body[data-bs-theme="dark"] .navbar-vertical .nav-link:hover,
    body[data-bs-theme="dark"] .navbar-vertical .nav-item.active > .nav-link {
        background: rgba(255,255,255,.10) !important;
    }

    [data-bs-theme="dark"] .navbar-vertical .dropdown-menu,
    body[data-bs-theme="dark"] .navbar-vertical .dropdown-menu {
        background: #53565A !important;
        border-color: #8A8D8F !important;
    }

    [data-bs-theme="dark"] .navbar-vertical .dropdown-item,
    [data-bs-theme="dark"] .navbar-vertical .dropdown-item .ti,
    body[data-bs-theme="dark"] .navbar-vertical .dropdown-item,
    body[data-bs-theme="dark"] .navbar-vertical .dropdown-item .ti {
        color: #ffffff !important;
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

    .user-header-details {
        display: flex;
        flex-direction: column;
        justify-content: center;
        max-width: 140px;
        line-height: 1.2;
    }

    .user-header-details .user-header-name {
        font-size: 0.825rem;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: var(--tblr-body-color);
    }

    .user-header-details .user-header-team {
        font-size: 7pt;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    @media (min-width: 576px) {
        .user-header-details {
            max-width: 220px;
        }
        .user-header-details .user-header-name {
            font-size: 0.875rem;
        }
    }

    @media (min-width: 992px) {
        .user-header-details {
            max-width: 320px;
        }
    }

    /* Mobile Header 3-Row Layout Custom Styles */
    .owl-mobile-user-row {
        border-color: var(--tblr-border-color, #e6e7e9) !important;
        padding-top: 4px !important;
        padding-bottom: 6px !important;
    }

    .owl-mobile-avatar {
        width: 40px !important;
        height: 40px !important;
        min-width: 40px !important;
        font-size: 19px !important;
    }

    .owl-mobile-user-name {
        color: var(--tblr-body-color, #1e293b);
    }

    .owl-mobile-user-team {
        color: var(--tblr-muted, #64748b);
    }

    [data-bs-theme="dark"] .owl-mobile-user-row,
    body.dark-mode .owl-mobile-user-row {
        border-color: rgba(255, 255, 255, 0.12) !important;
    }

    [data-bs-theme="dark"] .owl-mobile-user-name,
    body.dark-mode .owl-mobile-user-name {
        color: #ffffff !important;
    }

    [data-bs-theme="dark"] .owl-mobile-user-team,
    body.dark-mode .owl-mobile-user-team {
        color: #94a3b8 !important;
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
        background-color: #D9D9D6;
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
        border-color: var(--tblr-primary, #05507D) !important;
        box-shadow: 0 0 0 .25rem rgba(5, 80, 125, .25) !important;
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
        color: var(--tblr-primary, #05507D) !important;
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
        background-color: var(--tblr-primary, #05507D) !important;
        color: #ffffff !important;
    }

    @media (max-width: 576px) {
        /* Mengunci lebar modal agar memiliki jarak aman dari pinggir layar */
        .modal-dialog {
            margin: 12px auto !important;
            max-width: calc(100% - 24px) !important;
        }
        /* Penyesuaian ukuran font dan padding modal agar pas di mobile */
        .modal-content {
            padding: 0.5rem !important;
        }
        .modal-body {
            padding: 0.75rem 1rem !important;
        }
        .modal-title {
            font-size: 1.05rem !important;
        }
        .assessment-title-wrapper h5 {
            font-size: 1rem !important;
        }
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
    #header-autocomplete-list,
    #mobile-header-autocomplete-list,
    #gallery-autocomplete-list,
    #news-autocomplete-list,
    #content-autocomplete-list,
    #playlist-autocomplete-list,
    #ongoing-autocomplete-list,
    #assessment-autocomplete-list,
    #certificate-autocomplete-list,
    #search-autocomplete-list,
    #mobile-search-autocomplete-list {
        max-height: 280px !important;
        overflow-y: auto !important;
        background-color: #ffffff !important;
        background: #ffffff !important;
        scrollbar-width: thin;
    }

    /* Autocomplete Items */
    .dropdown-menu .dropdown-item,
    .autocomplete-list-group .dropdown-item {
        background-color: #ffffff !important;
        color: #333333 !important;
    }

    .dropdown-menu .dropdown-item:hover,
    .dropdown-menu .dropdown-item:focus,
    .autocomplete-list-group .dropdown-item:hover,
    .autocomplete-list-group .dropdown-item:focus {
        background-color: #f8f9fa !important;
    }

    /* Manage Module Autocomplete Styling */
    .manage-search-container {
        position: relative !important;
    }

    .dropdown-menu[id*="-search-results"],
    [id^="manage-"][id$="-search-results"] {
        background-color: #ffffff !important;
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 8px !important;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12) !important;
        overflow: hidden !important;
    }

    .list-group[id*="-autocomplete-list"],
    [id^="manage-"][id$="-autocomplete-list"] {
        max-height: 280px !important;
        overflow-y: auto !important;
        background-color: #ffffff !important;
        scrollbar-width: thin;
    }

    .list-group[id*="-autocomplete-list"] .dropdown-item,
    [id^="manage-"][id$="-autocomplete-list"] .dropdown-item {
        background-color: #ffffff !important;
        color: #333333 !important;
    }

    .list-group[id*="-autocomplete-list"] .dropdown-item:hover,
    [id^="manage-"][id$="-autocomplete-list"] .dropdown-item:hover {
        background-color: #f8f9fa !important;
    }

    div[id*="-search-footer"],
    [id^="manage-"][id$="-search-footer"] {
        position: sticky !important;
        bottom: 0 !important;
        background-color: #f8f9fa !important;
        border-top: 1px solid #e2e8f0 !important;
        z-index: 2005 !important;
    }
</style>

{{-- Select2 --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
