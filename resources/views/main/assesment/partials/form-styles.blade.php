<link href="{{ asset('Tabler/dist/css/tabler-vendors.min.css?1684106062') }}" rel="stylesheet" />
<link rel="stylesheet" href="{{ asset('css/flatpickr.min.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('css/dark.css') }}">

<style>
    /* Scoped Assessment Header Styling */
    .assessment-page-header {
        margin-bottom: 1.25rem !important;
        padding-top: 0.25rem;
    }

    .assessment-back-link,
    .assessment-page-header .assessment-back a {
        display: inline-flex;
        align-items: center;
        color: var(--tblr-primary, #206bc4) !important;
        font-size: 0.9rem;
        font-weight: 600;
        text-decoration: none !important;
        background: transparent !important;
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        padding: 0 !important;
        transition: color 0.15s ease-in-out;
        cursor: pointer;
    }

    .assessment-back-link:hover,
    .assessment-page-header .assessment-back a:hover {
        color: var(--tblr-primary-dark, #185294) !important;
        text-decoration: underline !important;
        background: transparent !important;
        box-shadow: none !important;
        border: none !important;
    }

    .assessment-back-link i,
    .assessment-page-header .assessment-back a i {
        font-size: 1rem;
        transition: transform 0.15s ease-in-out;
    }

    .assessment-back-link:hover i,
    .assessment-page-header .assessment-back a:hover i {
        transform: translateX(-2px);
    }

    .assessment-page-header .page-title {
        font-size: 1.35rem;
        font-weight: 700;
        line-height: 1.3;
        color: #1f2937;
    }

    .assessment-page-header .breadcrumb {
        font-size: 0.85rem;
        margin-bottom: 0;
    }

    [data-bs-theme="dark"] .assessment-page-header .page-title,
    [data-theme="dark"] .assessment-page-header .page-title,
    body.dark-mode .assessment-page-header .page-title,
    body.theme-dark .assessment-page-header .page-title {
        color: #f8fafc !important;
    }

    .owl-section-number {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        background: rgba(32, 107, 196, 0.12);
        color: #206bc4;
        font-weight: 700;
        font-size: 13px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .owl-section-title {
        font-size: 1rem;
        color: #1f2937;
    }

    /* Sub-card Section Container */
    .owl-sub-card {
        border-radius: 12px;
        background-color: #f8fafc;
        border-color: #e2e8f0;
        color: #1e293b !important;
    }

    .owl-sub-card .card-header {
        border-bottom: 1px solid rgba(98, 105, 118, .1);
        background-color: transparent;
    }

    .owl-sub-card .card-title,
    .owl-sub-card .form-label,
    .owl-sub-card label {
        color: #1e293b !important;
        font-weight: 600;
    }

    .owl-sub-card .input-group-text {
        color: #475569 !important;
        background-color: #f1f5f9 !important;
        border-color: #d8dee9 !important;
    }

    .owl-sub-card .form-control {
        color: #1e293b !important;
        background-color: #ffffff !important;
        border-color: #d8dee9 !important;
    }

    /* Dark Mode Tabler Support */
    body.theme-dark .owl-section-number,
    body[data-theme="dark"] .owl-section-number,
    [data-bs-theme="dark"] .owl-section-number {
        background: rgba(66, 153, 225, 0.2) !important;
        color: #60a5fa !important;
    }

    body.theme-dark .owl-section-title,
    body[data-theme="dark"] .owl-section-title,
    [data-bs-theme="dark"] .owl-section-title {
        color: #f3f4f6 !important;
    }

    body.theme-dark .owl-sub-card,
    body[data-theme="dark"] .owl-sub-card,
    [data-bs-theme="dark"] .owl-sub-card {
        background-color: rgba(17, 24, 39, 0.6) !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: #f8fafc !important;
    }

    body.theme-dark .owl-sub-card .card-header,
    body[data-theme="dark"] .owl-sub-card .card-header,
    [data-bs-theme="dark"] .owl-sub-card .card-header {
        border-bottom-color: rgba(255, 255, 255, 0.08) !important;
    }

    body.theme-dark .owl-sub-card .card-title,
    body.theme-dark .owl-sub-card .form-label,
    body.theme-dark .owl-sub-card label,
    body[data-theme="dark"] .owl-sub-card .card-title,
    body[data-theme="dark"] .owl-sub-card .form-label,
    body[data-theme="dark"] .owl-sub-card label,
    [data-bs-theme="dark"] .owl-sub-card .card-title,
    [data-bs-theme="dark"] .owl-sub-card .form-label,
    [data-bs-theme="dark"] .owl-sub-card label {
        color: #f8fafc !important;
    }

    body.theme-dark .owl-sub-card .input-group-text,
    body[data-theme="dark"] .owl-sub-card .input-group-text,
    [data-bs-theme="dark"] .owl-sub-card .input-group-text {
        color: #cbd5e1 !important;
        background-color: #1f2937 !important;
        border-color: #374151 !important;
    }

    body.theme-dark .owl-sub-card .form-control,
    body[data-theme="dark"] .owl-sub-card .form-control,
    [data-bs-theme="dark"] .owl-sub-card .form-control {
        color: #ffffff !important;
        background-color: #111827 !important;
        border-color: #374151 !important;
    }
</style>
