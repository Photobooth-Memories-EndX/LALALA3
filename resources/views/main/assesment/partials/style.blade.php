<link href="{{ asset('css/bootstrap-toggle.min.css') }}" rel="stylesheet">

<style>

    /* ==========================
       PAGE HEADER
    ========================== */

    .owl-page-title {
        font-size: 1.35rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 4px;
    }

    .owl-page-subtitle {
        color: #6b7280;
        font-size: 13px;
    }

    /* ==========================
       STATISTIC CARD
    ========================== */

    .owl-stat-card {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .06);
        transition: all .25s ease;
        height: 100%;
    }

    .owl-stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, .08);
    }

    .owl-stat-card .card-body {
        padding: 1rem 1.25rem;
    }

    .owl-stat-icon {
        width: 50px;
        height: 50px;
        min-width: 50px;
        border-radius: 14px;

        display: flex;
        align-items: center;
        justify-content: center;

        color: #fff;
        font-size: 16px;

        flex-shrink: 0;
    }

    .owl-stat-content {
        margin-left: 14px;
    }

    .owl-stat-label {
        font-size: 12px;
        color: #64748b;
        font-weight: 500;
        line-height: 1.2;
        margin-bottom: 4px;
    }

    .owl-stat-number {
        font-size: 28px;
        font-weight: 700;
        color: #111827;
        line-height: 1;
    }

    /* ==========================
       MAIN CARD
    ========================== */

    .owl-main-card {
        border: 0;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .07);
    }

    .owl-main-card .card-header {
        padding: 1.5rem;
        background: #fff;
    }

    .owl-main-card .card-body {
        padding: 1.25rem 1.5rem;
    }

    /* ==========================
       TABLE
    ========================== */

    .owl-table thead th {
        background: #f8fafc;
        color: #64748b;

        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;

        border-bottom: 1px solid #e5e7eb;

        vertical-align: middle;
        padding: 14px 12px;
    }

    .owl-table tbody td {
        vertical-align: middle;
        font-size: 13px;
        padding: 18px 12px;
    }

    .table-hover tbody tr:hover {
        background: #f8fafc;
    }

    /* ==========================
       BADGE
    ========================== */

    .owl-badge {
        display: inline-block;
        border-radius: 999px;
        padding: 5px 10px;
        font-size: 11px;
        font-weight: 600;
    }

    .owl-badge-blue {
        background: #e0f2fe;
        color: #0369a1;
    }

    .owl-badge-purple {
        background: #ede9fe;
        color: #6d28d9;
    }

    .owl-badge-green {
        background: #dcfce7;
        color: #15803d;
    }

    .owl-badge-orange {
        background: #ffedd5;
        color: #c2410c;
    }

    /* ==========================
       UNIQUE CODE
    ========================== */

    .owl-code {
        background: #f8fafc;
        border: 1px solid #e5e7eb;

        color: #334155;

        border-radius: 8px;

        padding: 4px 8px;
        font-size: 11px;
    }

    /* ==========================
       BUTTON
    ========================== */

    .owl-action-btn {
        border-radius: 10px;
    }

    .btn-copy-assessment {
        border-radius: 8px;
        transition: all .2s ease;
    }

    .btn-copy-assessment:hover {
        background: #206bc4;
        color: #fff;
    }

    .copy-success {
        background: #2fb344 !important;
        color: #fff !important;
    }

    /* ==========================
       DROPDOWN
    ========================== */

    .owl-dropdown-btn {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .owl-dropdown-menu {
        min-width: 220px;
        border-radius: 14px;
        padding: .5rem;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .15);
    }

    .owl-dropdown-menu .dropdown-item {
        display: flex;
        align-items: center;
        gap: 12px;

        padding: 10px 12px;
        border-radius: 10px;

        font-size: 13px;
        font-weight: 500;
    }

    .owl-dropdown-menu .dropdown-item i {
        width: 16px;
        text-align: center;
    }

    .owl-dropdown-menu .dropdown-item:hover {
        background: #f1f5f9;
    }

    .dropdown-delete {
        color: #dc2626 !important;
    }

    .dropdown-delete:hover {
        background: #fef2f2 !important;
    }

    /* ==========================
       TOAST
    ========================== */

    #owl-toast-wrapper {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
    }

    .owl-toast {
        min-width: 260px;

        border: 0;
        border-radius: 12px;

        box-shadow: 0 12px 30px rgba(0, 0, 0, .15);

        margin-bottom: 10px;
    }

    /* ==========================
       PAGINATION
    ========================== */

    .pagination {
        margin-bottom: 0;
    }

    .page-link {
        border-radius: 8px !important;
        margin: 0 2px;
    }

   /* ==========================
         MISC
    ========================== */
    /* Assessment stat card - Tabler dark mode support */
    .owl-stat-card {
        border: 1px solid rgba(98, 105, 118, .16);
        background: var(--tblr-card-bg, #ffffff);
        color: var(--tblr-body-color);
        border-radius: 14px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, .04);
    }

    .owl-stat-card .card-body {
        background: transparent;
    }

    .owl-stat-icon {
        width: 42px;
        height: 42px;
        min-width: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        margin-right: 12px;
    }

    .owl-stat-icon i {
        font-size: 16px;
    }

    .owl-stat-content {
        min-width: 0;
    }

    .owl-stat-label {
        font-size: 12px;
        font-weight: 600;
        color: var(--tblr-muted);
        margin-bottom: 2px;
    }

    .owl-stat-number {
        font-size: 22px;
        font-weight: 700;
        line-height: 1.2;
        color: var(--tblr-body-color);
    }

    /* Jika Tabler dark mode aktif */
    [data-bs-theme="dark"] .owl-stat-card,
    [data-theme="dark"] .owl-stat-card,
    body.theme-dark .owl-stat-card {
        background: var(--tblr-bg-surface, #1f2937);
        border-color: rgba(255, 255, 255, .08);
        box-shadow: none;
    }

    [data-bs-theme="dark"] .owl-stat-label,
    [data-theme="dark"] .owl-stat-label,
    body.theme-dark .owl-stat-label {
        color: rgba(255, 255, 255, .65);
    }

    [data-bs-theme="dark"] .owl-stat-number,
    [data-theme="dark"] .owl-stat-number,
    body.theme-dark .owl-stat-number {
        color: #ffffff;
    }

      [data-theme="dark"] .owl-main-card,
    body.theme-dark .owl-main-card {
        background: #182433 !important;
        border-color: #25384d !important;
        color: #dce6f1 !important;
    }

    [data-theme="dark"] .owl-main-card .card-header,
    [data-theme="dark"] .owl-main-card .card-footer,
    body.theme-dark .owl-main-card .card-header,
    body.theme-dark .owl-main-card .card-footer {
        background: #1f2d3d !important;
        border-color: #25384d !important;
        color: #dce6f1 !important;
    }

    [data-theme="dark"] .owl-main-card .card-body,
    body.theme-dark .owl-main-card .card-body {
        background: #182433 !important;
        border-color: #25384d !important;
    }

    [data-theme="dark"] .owl-table,
    body.theme-dark .owl-table {
        color: #dce6f1 !important;
        background: #182433 !important;
    }

    [data-theme="dark"] .owl-table thead th,
    body.theme-dark .owl-table thead th {
        background: #223247 !important;
        color: #b6c7da !important;
        border-color: #25384d !important;
    }

    [data-theme="dark"] .owl-table tbody tr,
    body.theme-dark .owl-table tbody tr {
        background: #182433 !important;
        border-color: #25384d !important;
    }

    [data-theme="dark"] .owl-table tbody tr:hover,
    body.theme-dark .owl-table tbody tr:hover {
        background: #223247 !important;
    }

    [data-theme="dark"] .owl-table td,
    [data-theme="dark"] .owl-table th,
    body.theme-dark .owl-table td,
    body.theme-dark .owl-table th {
        border-color: #25384d !important;
    }

    [data-theme="dark"] .owl-table a.text-dark,
    body.theme-dark .owl-table a.text-dark {
        color: #ffffff !important;
    }

    [data-theme="dark"] .text-muted,
    body.theme-dark .text-muted {
        color: #8fa3b8 !important;
    }

    [data-theme="dark"] .form-control,
    body.theme-dark .form-control {
        background-color: #111827 !important;
        border-color: #25384d !important;
        color: #ffffff !important;
    }

    [data-theme="dark"] .form-control:focus,
    body.theme-dark .form-control:focus {
        background-color: #111827 !important;
        border-color: #2f80ed !important;
        color: #ffffff !important;
    }

    [data-theme="dark"] .btn-light,
    body.theme-dark .btn-light {
        background: #223247 !important;
        border-color: #33465f !important;
        color: #ffffff !important;
    }

    /* ==========================
       ASSESSMENT STATUS TOGGLE
    ========================== */

    .toggle.btn-success,
    .toggle.btn-success:hover,
    .toggle.btn-success:focus,
    .toggle.btn-success:active,
    .toggle.btn-success.active,
    .toggle-on.btn-success,
    .toggle-on.btn-success:hover,
    .toggle-on.btn-success:focus,
    .toggle-on.btn-success:active {
        background-color: #198754 !important;
        border-color: #198754 !important;
        color: #ffffff !important;
        --tblr-btn-bg: #198754 !important;
        --tblr-btn-color: #ffffff !important;
        --tblr-btn-border-color: #198754 !important;
    }

    .toggle.btn-danger,
    .toggle.btn-danger:hover,
    .toggle.btn-danger:focus,
    .toggle.btn-danger:active,
    .toggle.btn-danger.active,
    .toggle-off.btn-danger,
    .toggle-off.btn-danger:hover,
    .toggle-off.btn-danger:focus,
    .toggle-off.btn-danger:active {
        background-color: #dc3545 !important;
        border-color: #dc3545 !important;
        color: #ffffff !important;
        --tblr-btn-bg: #dc3545 !important;
        --tblr-btn-color: #ffffff !important;
        --tblr-btn-border-color: #dc3545 !important;
    }

    .toggle-handle {
        background-color: #ffffff !important;
        border-color: rgba(0, 0, 0, 0.15) !important;
    }

    /* Dark mode support */
    [data-bs-theme="dark"] .toggle.btn-success,
    [data-theme="dark"] .toggle.btn-success,
    body.dark-mode .toggle.btn-success,
    body.theme-dark .toggle.btn-success,
    [data-bs-theme="dark"] .toggle-on.btn-success,
    [data-theme="dark"] .toggle-on.btn-success,
    body.dark-mode .toggle-on.btn-success,
    body.theme-dark .toggle-on.btn-success {
        background-color: #198754 !important;
        border-color: #198754 !important;
        color: #ffffff !important;
        --tblr-btn-bg: #198754 !important;
        --tblr-btn-color: #ffffff !important;
        --tblr-btn-border-color: #198754 !important;
    }

    [data-bs-theme="dark"] .toggle.btn-danger,
    [data-theme="dark"] .toggle.btn-danger,
    body.dark-mode .toggle.btn-danger,
    body.theme-dark .toggle.btn-danger,
    [data-bs-theme="dark"] .toggle-off.btn-danger,
    [data-theme="dark"] .toggle-off.btn-danger,
    body.dark-mode .toggle-off.btn-danger,
    body.theme-dark .toggle-off.btn-danger {
        background-color: #dc3545 !important;
        border-color: #dc3545 !important;
        color: #ffffff !important;
        --tblr-btn-bg: #dc3545 !important;
        --tblr-btn-color: #ffffff !important;
        --tblr-btn-border-color: #dc3545 !important;
    }

    [data-bs-theme="dark"] .toggle-handle,
    [data-theme="dark"] .toggle-handle,
    body.dark-mode .toggle-handle,
    body.theme-dark .toggle-handle {
        background-color: #f8f9fa !important;
        border-color: rgba(255, 255, 255, 0.2) !important;
    }

</style>
