@extends('layouts.sidebar-master')

@section('content')
<div class="page-body manage-learning-hours-page">
    <div class="container-xl">

        <!-- Page Header -->
        <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <div class="text-uppercase text-muted small fw-semibold">Manage &bull; Learning Hours</div>
                <h1 class="page-title mb-1">Content User Learning Hours</h1>
                <div class="text-muted">Calculate, inspect, and synchronize learning hours for employee Content User records.</div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('manage.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>
        </header>

        <!-- Summary KPI Cards -->
        @include('manage.learning-hours.content-user.partials.summary')

        <!-- Filter Component -->
        @include('manage.learning-hours.content-user.partials.filters')

        <!-- DataTables Component -->
        @include('manage.learning-hours.content-user.partials.table')

        <!-- Batch Calculation Modal -->
        @include('manage.learning-hours.content-user.partials.batch-modal')

    </div>
</div>
@endsection

@section('style')
<link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">

<style>
    .manage-learning-hours-page {
        background: #fff;
    }

    [data-bs-theme="dark"] .manage-learning-hours-page,
    body.dark-mode .manage-learning-hours-page,
    body.theme-dark .manage-learning-hours-page {
        background: var(--tblr-body-bg, #111827);
    }

    .manage-learning-hours-page .stat-card {
        border-radius: 18px;
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

    .manage-learning-hours-page .stat-card-remaining {
        background: linear-gradient(180deg, #fff7e6 0%, #ffffff 100%);
    }

    .manage-learning-hours-page .stat-card-failed {
        background: linear-gradient(180deg, #fff0f0 0%, #ffffff 100%);
    }

    [data-bs-theme="dark"] .manage-learning-hours-page .stat-card-total,
    [data-bs-theme="dark"] .manage-learning-hours-page .stat-card-calculated,
    [data-bs-theme="dark"] .manage-learning-hours-page .stat-card-remaining,
    [data-bs-theme="dark"] .manage-learning-hours-page .stat-card-failed,
    body.dark-mode .manage-learning-hours-page .stat-card,
    body.theme-dark .manage-learning-hours-page .stat-card {
        background: var(--tblr-card-bg, #1e293b);
        border: 1px solid rgba(255, 255, 255, .08) !important;
    }

    #content-user-lh-table {
        font-size: 13px;
    }

    #content-user-lh-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap;
    }

    [data-bs-theme="dark"] #content-user-lh-table thead th,
    body.dark-mode #content-user-lh-table thead th,
    body.theme-dark #content-user-lh-table thead th {
        background: #1e293b;
        color: #94a3b8;
    }

    #content-user-lh-table tbody td {
        vertical-align: middle;
        padding-top: 12px;
        padding-bottom: 12px;
    }
</style>
@endsection

@section('script')
    @include('manage.learning-hours.content-user.partials.scripts')
@endsection
