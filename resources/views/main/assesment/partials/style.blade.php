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
        padding: 12px 10px;
        white-space: nowrap;
    }

    .owl-table tbody td {
        vertical-align: middle;
        font-size: 13px;
        padding: 14px 10px;
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

    /* ==========================
       ASSESSMENT QUICK FILTER & TOOLBAR
    ========================== */

    .assessment-fast-filter-section {
        width: 100%;
    }

    .assessment-fast-filter-label {
        font-size: 12px;
        font-weight: 600;
        color: var(--tblr-muted, #64748b);
        letter-spacing: .02em;
    }

    .assessment-filter-toolbar {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        min-width: 0;
        flex-wrap: nowrap;
    }

    .assessment-filter-field {
        width: max-content;
        min-width: 0;
        flex: 0 1 auto;
    }

    .assessment-filter-field-year {
        min-width: 110px;
    }

    .assessment-filter-field-month {
        min-width: 125px;
    }

    .assessment-filter-field-type {
        min-width: 115px;
    }

    .assessment-filter-field-status {
        min-width: 115px;
    }

    .assessment-filter-control {
        width: 100%;
        min-width: 0;
        height: 40px;
        min-height: 40px;
        padding: 0.375rem 0.75rem;
        border-radius: 8px !important;
        font-size: 13px;
        font-weight: 500;
        background-color: var(--tblr-bg-surface, #ffffff);
        color: var(--tblr-body-color, #1e293b);
        border: 1px solid var(--tblr-border-color, #dbe0e6) !important;
        box-shadow: none !important;
    }

    .assessment-filter-search {
        min-width: 0;
        flex: 1 1 auto;
    }

    .assessment-filter-search .input-icon {
        width: 100%;
        min-width: 0;
        position: relative;
    }

    .assessment-search-control {
        height: 40px;
        min-height: 40px;
        border-radius: 8px !important;
        font-size: 13px;
        border: 1px solid var(--tblr-border-color, #dbe0e6) !important;
        background-color: var(--tblr-bg-surface, #ffffff);
        color: var(--tblr-body-color, #1e293b);
        box-shadow: none !important;
    }

    .assessment-filter-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex: 0 0 auto;
        flex-wrap: nowrap;
    }

    .assessment-filter-action-btn {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        min-width: 40px;
        height: 40px;
        min-height: 40px;
        padding: 0;
        border-radius: 8px !important;
        line-height: 1;
        font-size: 16px;
        transition: all .2s ease;
    }

    .assessment-filter-action-btn > i {
        margin: 0 !important;
        font-size: 18px;
        line-height: 1;
    }

    .assessment-filter-count {
        position: absolute;
        top: -6px;
        right: -6px;
        min-width: 18px;
        height: 18px;
        padding: 0 4px;
        border: 2px solid var(--tblr-bg-surface, #fff);
        border-radius: 999px;
        font-size: 10px;
        line-height: 14px;
        font-weight: 700;
    }

    .assessment-filter-action-btn.is-active,
    .assessment-filter-action-btn.is-active:hover,
    .assessment-filter-action-btn.is-active:focus {
        border-color: #05507d;
        background: #05507d;
        color: #fff;
        box-shadow: 0 4px 12px rgba(5, 80, 125, .22);
    }

    /* Responsive Filter Toolbar */
    @media (max-width: 1199.98px) {
        .assessment-filter-toolbar {
            flex-wrap: wrap;
            gap: 8px;
        }

        .assessment-filter-field-year,
        .assessment-filter-field-month,
        .assessment-filter-field-type,
        .assessment-filter-field-status {
            flex: 1 1 calc(25% - 8px);
            min-width: 110px;
        }

        .assessment-filter-search {
            flex: 1 1 calc(100% - 150px);
            min-width: 180px;
        }

        .assessment-filter-actions {
            flex: 0 0 auto;
        }
    }

    @media (max-width: 767.98px) {
        .assessment-filter-field-year,
        .assessment-filter-field-month,
        .assessment-filter-field-type,
        .assessment-filter-field-status {
            flex: 1 1 calc(50% - 8px);
            min-width: 120px;
        }

        .assessment-filter-search {
            flex: 1 1 100%;
            width: 100%;
        }

        .assessment-filter-actions {
            width: 100%;
            justify-content: flex-end;
        }
    }

    @media (max-width: 480px) {
        .assessment-filter-field-year,
        .assessment-filter-field-month,
        .assessment-filter-field-type,
        .assessment-filter-field-status {
            flex: 1 1 100%;
            min-width: 100%;
        }
    }

    /* ==========================
       MORE FILTERS MODAL
    ========================== */

    .assessment-filter-modal-dialog {
        max-width: 680px;
        width: 100%;
        margin: 1.75rem auto;
    }

    .assessment-filter-modal-dialog .modal-content {
        border-radius: 16px !important;
        border: 0;
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.15);
    }

    .assessment-filter-modal-dialog .modal-header {
        padding: 1.25rem 1.5rem 0.5rem;
    }

    .assessment-filter-modal-dialog .modal-body {
        padding: 0.5rem 1.5rem 1.25rem;
        max-height: calc(100vh - 200px);
        overflow-y: auto;
    }

    .assessment-filter-modal-dialog .modal-footer {
        padding: 1rem 1.5rem 1.25rem;
        border-top: 1px solid var(--tblr-border-color, #e5e7eb) !important;
    }

    .assessment-filter-group label {
        font-size: 12.5px;
        color: var(--tblr-body-color, #334155);
    }

    .assessment-filter-group .form-control,
    .assessment-filter-group .form-select {
        height: 40px;
        min-height: 40px;
        border-radius: 8px;
        font-size: 13px;
        border-color: var(--tblr-border-color, #dbe0e6);
    }

    /* Dark mode for Filter & Modal */
    [data-bs-theme="dark"] .assessment-filter-control,
    [data-bs-theme="dark"] .assessment-search-control,
    [data-bs-theme="dark"] .assessment-filter-group .form-control,
    [data-bs-theme="dark"] .assessment-filter-group .form-select,
    body.dark-mode .assessment-filter-control,
    body.dark-mode .assessment-search-control,
    body.dark-mode .assessment-filter-group .form-control,
    body.dark-mode .assessment-filter-group .form-select {
        background-color: var(--tblr-bg-surface, #111827) !important;
        color: #ffffff !important;
        border-color: #25384d !important;
    }

    [data-bs-theme="dark"] .assessment-filter-modal-dialog .modal-content,
    body.dark-mode .assessment-filter-modal-dialog .modal-content {
        background-color: #182433 !important;
        color: #dce6f1 !important;
        border: 1px solid #25384d !important;
    }

    [data-bs-theme="dark"] .assessment-filter-modal-dialog .modal-header,
    [data-bs-theme="dark"] .assessment-filter-modal-dialog .modal-footer,
    body.dark-mode .assessment-filter-modal-dialog .modal-header,
    body.dark-mode .assessment-filter-modal-dialog .modal-footer {
        background-color: #1f2d3d !important;
        border-color: #25384d !important;
    }

    [data-bs-theme="dark"] .assessment-filter-modal-dialog .btn-close,
    body.dark-mode .assessment-filter-modal-dialog .btn-close {
        filter: invert(1) grayscale(100%) brightness(200%);
    }

    [data-bs-theme="dark"] .assessment-filter-count,
    body.dark-mode .assessment-filter-count {
        border-color: #182433 !important;
    }

</style>
