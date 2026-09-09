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

    .filter-label-head {
        color: #1e293b;
    }

    /* Live Search Dropdown Styling */
    #monitoring-search-results {
        position: absolute;
        top: 100%;
        left: 0;
        z-index: 2000;
        border-radius: 8px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
        border: 1px solid #e2e8f0 !important;
        background: #ffffff !important;
        overflow: hidden !important;
    }

    #monitoring-autocomplete-list {
        max-height: 280px !important;
        overflow-y: auto !important;
        background-color: #ffffff !important;
        scrollbar-width: thin;
    }

    #monitoring-search-results .dropdown-item {
        padding: .6rem .85rem;
        transition: background-color .15s ease;
        text-decoration: none;
    }

    #monitoring-search-results .dropdown-item:hover,
    #monitoring-search-results .dropdown-item:focus {
        background-color: rgba(15, 94, 156, .08);
    }

    #monitoring-search-results .dropdown-item:not(:last-child) {
        border-bottom: 1px solid rgba(0, 0, 0, .05);
    }

    /* ==========================
       DARK MODE (Tabler)
    ========================== */
    [data-bs-theme="dark"] .hero-header-card,
    [data-theme="dark"] .hero-header-card,
    body.theme-dark .hero-header-card,
    [data-bs-theme="dark"] .stat-card-item,
    [data-theme="dark"] .stat-card-item,
    body.theme-dark .stat-card-item,
    [data-bs-theme="dark"] .monitoring-filter-card,
    [data-theme="dark"] .monitoring-filter-card,
    body.theme-dark .monitoring-filter-card,
    [data-bs-theme="dark"] .monitoring-table-card,
    [data-theme="dark"] .monitoring-table-card,
    body.theme-dark .monitoring-table-card,
    [data-bs-theme="dark"] .owl-stat-card,
    [data-theme="dark"] .owl-stat-card,
    body.theme-dark .owl-stat-card {
        background-color: #1e293b !important;
        border: 1px solid rgba(255, 255, 255, 0.12) !important;
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .hero-icon-box,
    [data-theme="dark"] .hero-icon-box,
    body.theme-dark .hero-icon-box {
        background-color: rgba(0, 59, 109, 0.35) !important;
        color: #60a5fa !important;
    }

    [data-bs-theme="dark"] .header-title,
    [data-theme="dark"] .header-title,
    body.theme-dark .header-title,
    [data-bs-theme="dark"] .card-title,
    [data-theme="dark"] .card-title,
    body.theme-dark .card-title,
    [data-bs-theme="dark"] .filter-label-head,
    [data-theme="dark"] .filter-label-head,
    body.theme-dark .filter-label-head {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .header-subtitle,
    [data-theme="dark"] .header-subtitle,
    body.theme-dark .header-subtitle {
        color: #94a3b8 !important;
    }

    [data-bs-theme="dark"] #monitoring-search-results,
    [data-theme="dark"] #monitoring-search-results,
    body.theme-dark #monitoring-search-results {
        background-color: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.12) !important;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4) !important;
    }

    [data-bs-theme="dark"] #monitoring-autocomplete-list,
    [data-theme="dark"] #monitoring-autocomplete-list,
    body.theme-dark #monitoring-autocomplete-list {
        background-color: #1e293b !important;
    }

    [data-bs-theme="dark"] #monitoring-search-footer,
    [data-theme="dark"] #monitoring-search-footer,
    body.theme-dark #monitoring-search-footer {
        background-color: #0f172a !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    [data-bs-theme="dark"] #monitoring-search-results .dropdown-item,
    [data-theme="dark"] #monitoring-search-results .dropdown-item,
    body.theme-dark #monitoring-search-results .dropdown-item {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] #monitoring-search-results .dropdown-item .text-dark,
    [data-theme="dark"] #monitoring-search-results .dropdown-item .text-dark,
    body.theme-dark #monitoring-search-results .dropdown-item .text-dark {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] #monitoring-search-results .dropdown-item:not(:last-child),
    [data-theme="dark"] #monitoring-search-results .dropdown-item:not(:last-child),
    body.theme-dark #monitoring-search-results .dropdown-item:not(:last-child) {
        border-bottom-color: rgba(255, 255, 255, 0.08) !important;
    }

    [data-bs-theme="dark"] #monitoring-search-results .dropdown-item:hover,
    [data-bs-theme="dark"] #monitoring-search-results .dropdown-item:focus,
    [data-theme="dark"] #monitoring-search-results .dropdown-item:hover,
    [data-theme="dark"] #monitoring-search-results .dropdown-item:focus,
    body.theme-dark #monitoring-search-results .dropdown-item:hover,
    body.theme-dark #monitoring-search-results .dropdown-item:focus {
        background-color: rgba(106, 174, 214, 0.16) !important;
    }

    [data-bs-theme="dark"] #moreMonitoringFiltersModal .modal-content,
    [data-theme="dark"] #moreMonitoringFiltersModal .modal-content,
    body.theme-dark #moreMonitoringFiltersModal .modal-content {
        background-color: var(--tblr-bg-surface, #1e293b) !important;
        color: #f8fafc !important;
        border: 1px solid rgba(255, 255, 255, 0.12) !important;
    }

    [data-bs-theme="dark"] #moreMonitoringFiltersModal .text-dark,
    [data-theme="dark"] #moreMonitoringFiltersModal .text-dark,
    body.theme-dark #moreMonitoringFiltersModal .text-dark {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] #moreMonitoringFiltersModal .modal-header,
    [data-bs-theme="dark"] #moreMonitoringFiltersModal .modal-footer,
    [data-theme="dark"] #moreMonitoringFiltersModal .modal-header,
    [data-theme="dark"] #moreMonitoringFiltersModal .modal-footer,
    body.theme-dark #moreMonitoringFiltersModal .modal-header,
    body.theme-dark #moreMonitoringFiltersModal .modal-footer {
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    [data-bs-theme="dark"] #moreMonitoringFiltersModal .form-control,
    [data-bs-theme="dark"] #moreMonitoringFiltersModal .form-select,
    [data-bs-theme="dark"] .monitoring-filter-card .form-control,
    [data-bs-theme="dark"] .monitoring-filter-card .form-select,
    [data-theme="dark"] #moreMonitoringFiltersModal .form-control,
    [data-theme="dark"] #moreMonitoringFiltersModal .form-select,
    [data-theme="dark"] .monitoring-filter-card .form-control,
    [data-theme="dark"] .monitoring-filter-card .form-select,
    body.theme-dark #moreMonitoringFiltersModal .form-control,
    body.theme-dark #moreMonitoringFiltersModal .form-select,
    body.theme-dark .monitoring-filter-card .form-control,
    body.theme-dark .monitoring-filter-card .form-select {
        background-color: #0f172a !important;
        border-color: rgba(255, 255, 255, 0.15) !important;
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .monitoring-filter-card .form-select option,
    [data-bs-theme="dark"] #moreMonitoringFiltersModal .form-select option,
    [data-theme="dark"] .monitoring-filter-card .form-select option,
    [data-theme="dark"] #moreMonitoringFiltersModal .form-select option,
    body.theme-dark .monitoring-filter-card .form-select option,
    body.theme-dark #moreMonitoringFiltersModal .form-select option {
        background-color: #0f172a !important;
        color: #f8fafc !important;
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
