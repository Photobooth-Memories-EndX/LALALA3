@once
<style>
    /* ==========================
       PAGE HEADER
    ========================== */
    .owl-page-title {
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--tblr-body-color, #1f2937);
        margin-bottom: 4px;
    }

    .owl-page-subtitle {
        color: var(--tblr-muted, #6b7280);
        font-size: 13px;
    }

    /* ==========================
       STATISTIC CARD
    ========================== */
    .owl-stat-card {
        border: 1px solid rgba(98, 105, 118, .16);
        background: var(--tblr-card-bg, #ffffff);
        color: var(--tblr-body-color);
        border-radius: 14px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, .04);
        transition: all .25s ease;
        height: 100%;
    }

    .owl-stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, .08);
    }

    .owl-stat-card .card-body {
        background: transparent;
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
        font-size: 18px;
        margin-right: 14px;
        flex-shrink: 0;
    }

    .owl-stat-content {
        min-width: 0;
    }

    .owl-stat-label {
        font-size: 12px;
        color: var(--tblr-muted, #64748b);
        font-weight: 600;
        line-height: 1.2;
        margin-bottom: 4px;
    }

    .owl-stat-number {
        font-size: 26px;
        font-weight: 700;
        color: var(--tblr-body-color, #111827);
        line-height: 1.1;
    }

    /* ==========================
       MAIN CARD + TABLE
    ========================== */
    .owl-main-card {
        border: 0;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .07);
    }

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
        padding: 16px 12px;
    }

    .table-hover tbody tr:hover {
        background: #f8fafc;
    }

    /* ==========================
       CLICKABLE LINK (name + group)
    ========================== */
    .monitoring-link {
        transition: color .15s ease;
        cursor: pointer;
    }

    .monitoring-link:hover {
        color: var(--tblr-primary) !important;
        text-decoration: underline !important;
    }

    .owl-action-btn { border-radius: 10px; }

    /* ==========================
       DARK MODE (Tabler)
    ========================== */
    [data-bs-theme="dark"] .owl-stat-card,
    [data-theme="dark"] .owl-stat-card,
    body.theme-dark .owl-stat-card {
        background: var(--tblr-bg-surface, #1f2937);
        border-color: rgba(255, 255, 255, .08);
        box-shadow: none;
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

    [data-theme="dark"] .btn-light,
    body.theme-dark .btn-light {
        background: #223247 !important;
        border-color: #33465f !important;
        color: #ffffff !important;
    }
</style>
@endonce
