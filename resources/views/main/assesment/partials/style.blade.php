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

</style>