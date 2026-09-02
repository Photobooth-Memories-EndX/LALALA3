@extends('layouts.sidebar-master')

@section('content')
<div class="page-body manage-learning-hours-page">
    <div class="container-xl">

        <!-- Page Header -->
        <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <div class="text-uppercase text-muted small fw-bold tracking-wider mb-1">Manage &bull; Learning Hours</div>
                <h1 class="page-title mb-1 fw-bold">Playlist Content User Learning Hours</h1>
                <div class="text-muted small">Compare Master Learning Hours with Actual Watched Duration for all Playlist Content records.</div>
            </div>

            <div class="d-flex align-items-center flex-wrap gap-2 flex-shrink-0">
                <a href="{{ route('manage.learning-hours.content-user.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-layout-list me-1"></i> Direct Content View
                </a>
                <a href="{{ route('manage.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>
        </header>

        <!-- Summary KPI Cards -->
        @include('manage.learning-hours.playlist-content-user.partials.summary')

        <!-- Filter Component -->
        @include('manage.learning-hours.playlist-content-user.partials.filters')

        <!-- DataTables Component -->
        @include('manage.learning-hours.playlist-content-user.partials.table')

    </div>
</div>
@endsection

@section('style')
<link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">

<style>
    /* Page & layout containment to prevent body overflow */
    .manage-learning-hours-page {
        min-width: 0;
        max-width: 100%;
        overflow-x: hidden;
    }

    .manage-learning-hours-page .container-xl {
        min-width: 0;
        max-width: 100%;
    }

    .manage-learning-hours-page .card {
        min-width: 0;
        max-width: 100%;
    }

    /* Summary cards: equal height and modern card styling */
    .manage-learning-hours-page .stat-card {
        border-radius: 12px;
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .manage-learning-hours-page .stat-card:hover {
        transform: translateY(-2px);
    }

    .manage-learning-hours-page .stat-card-total {
        background: linear-gradient(180deg, #eef4ff 0%, #ffffff 100%);
    }

    .manage-learning-hours-page .stat-card-calculated {
        background: linear-gradient(180deg, #edfdf2 0%, #ffffff 100%);
    }

    .manage-learning-hours-page .stat-card-actual {
        background: linear-gradient(180deg, #e8faf0 0%, #ffffff 100%);
    }

    .manage-learning-hours-page .stat-card-fallback {
        background: linear-gradient(180deg, #eaf5ff 0%, #ffffff 100%);
    }

    [data-bs-theme="dark"] .manage-learning-hours-page .stat-card-total,
    [data-bs-theme="dark"] .manage-learning-hours-page .stat-card-calculated,
    [data-bs-theme="dark"] .manage-learning-hours-page .stat-card-actual,
    [data-bs-theme="dark"] .manage-learning-hours-page .stat-card-fallback,
    body.dark-mode .manage-learning-hours-page .stat-card,
    body.theme-dark .manage-learning-hours-page .stat-card {
        background: var(--tblr-card-bg, #1e293b);
        border: 1px solid rgba(255, 255, 255, .08) !important;
    }

    /* DataTable specific adjustments */
    .manage-learning-hours-page .table-responsive {
        min-width: 0;
        max-width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    #playlist-content-user-lh-table {
        font-size: 13px;
        margin-bottom: 0 !important;
    }

    #playlist-content-user-lh-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 11.5px;
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap;
        vertical-align: middle;
        padding-top: 10px;
        padding-bottom: 10px;
    }

    [data-bs-theme="dark"] #playlist-content-user-lh-table thead th,
    body.dark-mode #playlist-content-user-lh-table thead th,
    body.theme-dark #playlist-content-user-lh-table thead th {
        background: #1e293b;
        color: #94a3b8;
    }

    #playlist-content-user-lh-table tbody td {
        vertical-align: middle;
        padding-top: 10px;
        padding-bottom: 10px;
    }

    .cursor-help {
        cursor: help;
    }

    /* =========================================================
       Information & Reference Guide Theme Styling
       ========================================================= */
    .manage-learning-hours-page .info-guide-card {
        background: #ffffff;
        border: 1px solid #e2e8f0 !important;
    }

    .manage-learning-hours-page .info-guide-title {
        color: #0f172a !important;
        letter-spacing: -0.01em;
    }

    .manage-learning-hours-page .info-guide-badge {
        background-color: #f0f9ff !important;
        color: #0369a1 !important;
        border-color: #bae6fd !important;
    }

    .manage-learning-hours-page .info-guide-item {
        background-color: #f8fafc !important;
        border: 1px solid #e2e8f0 !important;
        color: #334155 !important;
        transition: background-color .15s ease, border-color .15s ease;
    }

    .manage-learning-hours-page .info-guide-item-title {
        color: #0f172a !important;
        font-weight: 600;
        font-size: 13px;
    }

    .manage-learning-hours-page .info-guide-item-desc {
        color: #475569 !important;
        font-size: 12.5px;
        line-height: 1.45;
        display: block;
    }

    .manage-learning-hours-page .info-guide-item-desc strong {
        color: #0f172a !important;
        font-weight: 700;
    }

    /* Dark Mode: Information & Reference Guide */
    [data-bs-theme="dark"] .manage-learning-hours-page .info-guide-card,
    body.dark-mode .manage-learning-hours-page .info-guide-card,
    body.theme-dark .manage-learning-hours-page .info-guide-card {
        background: var(--tblr-card-bg, #1e293b) !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
    }

    [data-bs-theme="dark"] .manage-learning-hours-page .info-guide-title,
    body.dark-mode .manage-learning-hours-page .info-guide-title,
    body.theme-dark .manage-learning-hours-page .info-guide-title {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .manage-learning-hours-page .info-guide-badge,
    body.dark-mode .manage-learning-hours-page .info-guide-badge,
    body.theme-dark .manage-learning-hours-page .info-guide-badge {
        background-color: rgba(32, 107, 196, 0.15) !important;
        color: #70a1ff !important;
        border-color: rgba(112, 161, 255, 0.3) !important;
    }

    [data-bs-theme="dark"] .manage-learning-hours-page .info-guide-item,
    body.dark-mode .manage-learning-hours-page .info-guide-item,
    body.theme-dark .manage-learning-hours-page .info-guide-item {
        background-color: rgba(30, 41, 59, 0.6) !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        color: #cbd5e1 !important;
    }

    [data-bs-theme="dark"] .manage-learning-hours-page .info-guide-item-title,
    body.dark-mode .manage-learning-hours-page .info-guide-item-title,
    body.theme-dark .manage-learning-hours-page .info-guide-item-title {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .manage-learning-hours-page .info-guide-item-desc,
    body.dark-mode .manage-learning-hours-page .info-guide-item-desc,
    body.theme-dark .manage-learning-hours-page .info-guide-item-desc {
        color: #94a3b8 !important;
    }

    [data-bs-theme="dark"] .manage-learning-hours-page .info-guide-item-desc strong,
    body.dark-mode .manage-learning-hours-page .info-guide-item-desc strong,
    body.theme-dark .manage-learning-hours-page .info-guide-item-desc strong {
        color: #ffffff !important;
    }

    /* General Form & Card Label Contrast Enhancements */
    .manage-learning-hours-page .form-label {
        color: #334155;
        font-weight: 600;
    }

    [data-bs-theme="dark"] .manage-learning-hours-page .form-label,
    body.dark-mode .manage-learning-hours-page .form-label,
    body.theme-dark .manage-learning-hours-page .form-label {
        color: #94a3b8;
    }

    .manage-learning-hours-page .stat-card .tracking-wider {
        color: #475569 !important;
    }

    [data-bs-theme="dark"] .manage-learning-hours-page .stat-card .tracking-wider,
    body.dark-mode .manage-learning-hours-page .stat-card .tracking-wider,
    body.theme-dark .manage-learning-hours-page .stat-card .tracking-wider {
        color: #94a3b8 !important;
    }

    .bg-light-lt {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #334155;
    }

    [data-bs-theme="dark"] .bg-light-lt,
    body.dark-mode .bg-light-lt,
    body.theme-dark .bg-light-lt {
        background-color: rgba(30, 41, 59, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.08);
        color: #cbd5e1;
    }
</style>
@endsection

@section('script')
    @include('manage.learning-hours.playlist-content-user.partials.scripts')
@endsection
