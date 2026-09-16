@extends('layouts.sidebar-master')

@section('content')
@include('main.monitoring.components.style')

{{-- Page Header --}}
<div class="page-header d-print-none mb-0 pb-0">
    <div class="container-xl">
        <!-- Back Navigation Link -->
        <a href="{{ route('manage.dashboard') }}" class="btn btn-link p-0 mb-3 text-decoration-none d-inline-flex align-items-center gap-1 fw-semibold text-primary">
            <i class="ti ti-arrow-left fs-2"></i> Back
        </a>

        <!-- Manage Pages Hero Header Card -->
        <div class="card border border-light-subtle shadow-sm p-4 mb-3 rounded-3 bg-white hero-header-card">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 hero-icon-box" 
                         style="width: 72px; height: 72px; background-color: #eef2ff; color: #003b6d;">
                        <i class="ti ti-file-text" style="font-size: 2rem !important;"></i>
                    </div>
                    <div>
                        <h2 class="h3 fw-bold mb-1 text-dark header-title">Manage Pages</h2>
                        <p class="text-muted small mb-0 header-subtitle">Manage static system pages, policies, and terms in OWL LMS.</p>
                    </div>
                </div>
                <div class="flex-shrink-0">
                    <a href="{{ route('pages.create') }}" class="btn btn-primary px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2" 
                       style="background-color: #003b6d; border-color: #003b6d;">
                        <i class="ti ti-plus fs-2"></i><span>Add Page</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Page Body --}}
<div class="page-body mt-0 pt-0">
    <div class="container-xl">

        {{-- Alerts --}}
        <x-alert type="success" />
        <x-alert type="warning" />

        @if($errors->any())
            <div class="alert alert-danger shadow-sm mb-3">
                {!! implode('', $errors->all('<div>:message</div>')) !!}
            </div>
        @endif

        {{-- List Pages Card --}}
        <div class="card border border-light-subtle shadow-sm rounded-3 pages-table-card">
            <div class="card-header bg-transparent border-0 pb-0 pt-3 px-3 px-md-4">
                <div class="d-flex align-items-center justify-content-between">
                    <h3 class="card-title fw-bold text-dark m-0">List Pages</h3>
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 fw-semibold">
                        {{ count($pages) }} {{ Str::plural('Page', count($pages)) }}
                    </span>
                </div>
            </div>

            <div class="card-body p-3 p-md-4">
                <div class="table-responsive">
                    @if(count($pages) > 0)
                        <table class="table table-modern table-hover align-middle mb-0" id="MyTable">
                            <thead>
                                <tr>
                                    <th style="width: 60px;" class="text-center">No</th>
                                    <th>Title</th>
                                    <th>Slug</th>
                                    <th style="width: 180px;">Last Updated</th>
                                    <th style="width: 80px;" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pages as $list)
                                    <tr>
                                        <td class="text-center fw-medium text-muted">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="page-icon-box rounded-2 d-flex align-items-center justify-content-center text-primary flex-shrink-0"
                                                     style="width: 32px; height: 32px; background: #eef2ff;">
                                                    <i class="ti ti-file-text fs-3"></i>
                                                </div>
                                                <span class="fw-semibold text-dark page-item-title">{{ $list->title }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border px-2 py-1 font-monospace">
                                                {{ $list->slug ?? Str::slug($list->title) }}
                                            </span>
                                        </td>
                                        <td class="text-muted small">
                                            {{ $list->updated_at ? $list->updated_at->format('d M Y, H:i') : '-' }}
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('pages.edit', $list->slug) }}" 
                                               class="btn btn-outline-primary btn-icon rounded-3" 
                                               title="Edit Page"
                                               style="color: #003b6d; border-color: #003b6d;">
                                                <i class="ti ti-edit fs-2"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="ti ti-file-off fs-1 d-block mb-2 text-secondary"></i>
                            <p class="mb-0">No pages data found.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>

@endsection

@section('style')
    <style>
        .hero-header-card,
        .pages-table-card {
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

        .btn-icon {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }

        /* Dark Mode Support */
        [data-bs-theme="dark"] .hero-header-card,
        [data-theme="dark"] .hero-header-card,
        body.theme-dark .hero-header-card,
        [data-bs-theme="dark"] .pages-table-card,
        [data-theme="dark"] .pages-table-card,
        body.theme-dark .pages-table-card {
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

        [data-bs-theme="dark"] .page-icon-box,
        [data-theme="dark"] .page-icon-box,
        body.theme-dark .page-icon-box {
            background-color: rgba(0, 59, 109, 0.35) !important;
            color: #60a5fa !important;
        }

        [data-bs-theme="dark"] .header-title,
        [data-theme="dark"] .header-title,
        body.theme-dark .header-title,
        [data-bs-theme="dark"] .card-title,
        [data-theme="dark"] .card-title,
        body.theme-dark .card-title,
        [data-bs-theme="dark"] .page-item-title,
        [data-theme="dark"] .page-item-title,
        body.theme-dark .page-item-title {
            color: #f8fafc !important;
        }

        [data-bs-theme="dark"] .header-subtitle,
        [data-theme="dark"] .header-subtitle,
        body.theme-dark .header-subtitle {
            color: #94a3b8 !important;
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
    </style>
@endsection

