@extends('layouts.sidebar-master')

@section('content')
@include('main.monitoring.components.style')

<div class="page-body">
    <div class="container-xl">

        @include('main.certificate.components.header')
        @include('main.certificate.components.alert')
        @include('main.certificate.components.summary-cards')
        @include('main.certificate.components.filters')
        @include('main.certificate.components.table')
        @include('main.certificate.components.add-modal')
        @include('main.certificate.components.edit-modal')

    </div>
</div>
@endsection

@section('style')
<link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">
<style>
    /* Thin Subtle Card Border Definition */
    .admin-card-bordered,
    .hero-header-card,
    .summary-metric-card,
    .quick-filter-box,
    .certificate-table-card {
        border: 1px solid #e2e8f0 !important;
        border-radius: 0.75rem !important;
    }

    .table-modern thead th {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
        padding: 12px 14px;
    }

    .table-modern tbody td {
        vertical-align: middle;
        padding: 12px 14px;
        border-color: #f1f5f9;
        font-size: 13px;
    }

    .table-modern tbody tr:hover {
        background-color: #f8fafc;
    }

    .certificate-info-alert {
        display: none;
    }

    #certificate-settings-table td:last-child { 
        white-space: nowrap; 
    }

    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_length { 
        display: none; 
    }

    /* Dark Mode Support */
    [data-bs-theme="dark"],
    [data-theme="dark"],
    body.theme-dark {
        --bg-card-dark: #1e293b;
        --bg-input-dark: #0f172a;
        --border-dark: #334155;
        --text-main-dark: #f8fafc;
        --text-muted-dark: #94a3b8;
    }

    [data-bs-theme="dark"] .hero-header-card,
    [data-theme="dark"] .hero-header-card,
    body.theme-dark .hero-header-card,
    [data-bs-theme="dark"] .summary-metric-card,
    [data-theme="dark"] .summary-metric-card,
    body.theme-dark .summary-metric-card,
    [data-bs-theme="dark"] .certificate-table-card,
    [data-theme="dark"] .certificate-table-card,
    body.theme-dark .certificate-table-card,
    [data-bs-theme="dark"] .card,
    [data-theme="dark"] .card,
    body.theme-dark .card {
        background-color: #1e293b !important;
        border: 1px solid rgba(255, 255, 255, 0.12) !important;
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .quick-filter-box,
    [data-theme="dark"] .quick-filter-box,
    body.theme-dark .quick-filter-box {
        background-color: #0f172a !important;
        border: 1px solid rgba(255, 255, 255, 0.12) !important;
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
    [data-bs-theme="dark"] .stat-value,
    [data-theme="dark"] .stat-value,
    body.theme-dark .stat-value {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .header-subtitle,
    [data-theme="dark"] .header-subtitle,
    body.theme-dark .header-subtitle,
    [data-bs-theme="dark"] .stat-label,
    [data-theme="dark"] .stat-label,
    body.theme-dark .stat-label {
        color: #94a3b8 !important;
    }

    [data-bs-theme="dark"] .form-control,
    [data-theme="dark"] .form-control,
    body.theme-dark .form-control,
    [data-bs-theme="dark"] .form-select,
    [data-theme="dark"] .form-select,
    body.theme-dark .form-select {
        background-color: #0f172a !important;
        border-color: #334155 !important;
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .form-control::placeholder,
    [data-theme="dark"] .form-control::placeholder,
    body.theme-dark .form-control::placeholder {
        color: #64748b !important;
    }

    [data-bs-theme="dark"] .table-modern thead th,
    [data-theme="dark"] .table-modern thead th,
    body.theme-dark .table-modern thead th {
        background: #0f172a !important;
        color: #94a3b8 !important;
        border-color: #334155 !important;
    }

    [data-bs-theme="dark"] .table-modern tbody td,
    [data-theme="dark"] .table-modern tbody td,
    body.theme-dark .table-modern tbody td {
        border-color: #334155 !important;
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .table-modern tbody tr:hover,
    [data-theme="dark"] .table-modern tbody tr:hover,
    body.theme-dark .table-modern tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.03) !important;
    }

    [data-bs-theme="dark"] .modal-content,
    [data-theme="dark"] .modal-content,
    body.theme-dark .modal-content {
        background-color: #1e293b !important;
        color: #f8fafc !important;
        border-color: #334155 !important;
    }

    [data-bs-theme="dark"] .modal-header,
    [data-theme="dark"] .modal-header,
    body.theme-dark .modal-header,
    [data-bs-theme="dark"] .modal-footer,
    [data-theme="dark"] .modal-footer,
    body.theme-dark .modal-footer {
        border-color: #334155 !important;
    }
</style>
@endsection

@section('script')
    @include('main.certificate.scripts.datatable')
    @include('main.certificate.scripts.dynamic-dropdown')
@endsection
