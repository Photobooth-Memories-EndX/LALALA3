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
    }

    body {
        font-feature-settings: "cv03", "cv04", "cv11";
    }
    .navbar-vertical {
        background-color: #ffffff !important;
        border-right: 1px solid #e6e7e9 !important;
    }

    .navbar-vertical .nav-link,
    .navbar-vertical .nav-link-icon,
    .navbar-vertical .nav-link-title,
    .navbar-vertical .ti {
        color: #111827 !important;
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
    }

    .navbar-vertical .nav-link:hover .ti,
    .navbar-vertical .nav-link:hover .nav-link-title,
    .navbar-vertical .nav-item.active > .nav-link .ti,
    .navbar-vertical .nav-item.active > .nav-link .nav-link-title {
        color: #206bc4 !important;
    }

    .navbar-vertical .badge {
        margin-left: auto;
    }

    .navbar-vertical .dropdown-menu {
        border-radius: 12px;
        border: 1px solid #e6e7e9;
    }

    .navbar-vertical .dropdown-item {
        display: flex;
        align-items: center;
        color: #111827 !important;
        font-weight: 500;
    }

    .navbar-vertical .dropdown-item .ti {
        color: #111827 !important;
    }

    .avatar-default-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #eef3ff;
        color: #206bc4;
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
</style>

{{-- Select2 --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />