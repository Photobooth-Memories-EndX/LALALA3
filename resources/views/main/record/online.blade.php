@extends('layouts.sidebar-master')

@section('content')
<div class="page-body manage-record-page">
    <div class="container-xl">
        <!-- TOMBOL BACK RATA KIRI ATAS -->
        <div class="mb-3">
            <a href="{{ route('manage.index') }}" class="btn-back d-inline-flex align-items-center gap-1 text-decoration-none fw-medium">
                <i class="ti ti-arrow-left"></i>
                <span>Back</span>
            </a>
        </div>

        @include('main.record.components.record-hero')

        <!-- 4 KPI SUMMARY CARDS (ASYNC SKELETON SHELL) -->
        <div id="kpi-summary-container">
            @include('main.record.components.online-kpi-skeleton')
        </div>

        @include('main.record.components.record-navigation')

        <!-- Action Button: Download Dropdown -->
        @can('learning_management')
        <div class="d-flex justify-content-end mb-4">
            <div class="dropdown">
                <button type="button" class="btn btn-outline-secondary dropdown-toggle rounded-3 px-3 d-inline-flex align-items-center gap-1.5"
                        data-bs-toggle="dropdown" data-toggle="dropdown" aria-expanded="false" id="btn-download-dropdown">
                    <i class="ti ti-download fs-2"></i>
                    <span>Download</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border rounded-3 mt-1 py-1" aria-labelledby="btn-download-dropdown" style="min-width: 250px;">
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('manage.record.online.export', request()->query()) }}" id="btn-export-online-xlsx">
                            <i class="ti ti-file-spreadsheet text-success fs-3"></i>
                            <span>Download Online Record (.xlsx)</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('manage.record.online.export.pdf', request()->query()) }}" id="btn-export-online-pdf">
                            <i class="ti ti-file-type-pdf text-danger fs-3"></i>
                            <span>Download PDF (.pdf)</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('manage.record.online.export.csv', request()->query()) }}" id="btn-export-online-csv">
                            <i class="ti ti-file-type-csv text-primary fs-3"></i>
                            <span>Download CSV (.csv)</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        @endcan

        <!-- CLASSIFICATION SUMMARY (ASYNC SKELETON SHELL) -->
        <div class="card border-0 shadow-sm mb-4 classification-summary-card">
            <div class="card-header border-0 pb-0">
                <div>
                    <h3 class="card-title mb-1">Classification Summary</h3>
                    <div class="text-muted small">Aggregate overview per classification category in KP OWL.</div>
                </div>
            </div>

            <div class="card-body" id="classification-summary-container">
                @include('main.record.components.online-classification-skeleton')
            </div>
        </div>
    </div>
</div>

@include('main.record.components.online-detail-modal')
@include('main.record.components.more-information-modal')
@endsection

@section('style')
<style>
    .manage-record-page {
        background: #fff;
    }

    [data-bs-theme="dark"] .manage-record-page,
    body.dark-mode .manage-record-page,
    body.theme-dark .manage-record-page {
        background: var(--tblr-body-bg, #111827);
    }

    .btn-back {
        color: #0c5b9e !important;
        font-weight: 500;
        transition: transform 0.2s ease, opacity 0.2s ease;
    }
    .btn-back:hover {
        opacity: 0.85;
        transform: translateX(-3px);
    }
    [data-bs-theme="dark"] .btn-back,
    body.dark-mode .btn-back,
    body.theme-dark .btn-back {
        color: #60a5fa !important;
    }

    [data-bs-theme="dark"] .stat-kpi-card,
    body.dark-mode .stat-kpi-card,
    body.theme-dark .stat-kpi-card {
        background-color: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    [data-bs-theme="dark"] .stat-kpi-card .text-dark,
    body.dark-mode .stat-kpi-card .text-dark,
    body.theme-dark .stat-kpi-card .text-dark {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .classification-summary-card,
    body.dark-mode .classification-summary-card,
    body.theme-dark .classification-summary-card {
        background-color: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    [data-bs-theme="dark"] .classification-summary-card .table,
    body.dark-mode .classification-summary-card .table,
    body.theme-dark .classification-summary-card .table {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .kpi-skeleton-row .stat-kpi-card,
    body.dark-mode .kpi-skeleton-row .stat-kpi-card,
    body.theme-dark .kpi-skeleton-row .stat-kpi-card {
        background-color: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    /* Download button & dropdown dark mode support */
    [data-bs-theme="dark"] #btn-download-dropdown,
    body.dark-mode #btn-download-dropdown,
    body.theme-dark #btn-download-dropdown {
        color: #cbd5e1 !important;
        border-color: rgba(255, 255, 255, 0.2) !important;
        background-color: transparent !important;
    }

    [data-bs-theme="dark"] #btn-download-dropdown:hover,
    body.dark-mode #btn-download-dropdown:hover,
    body.theme-dark #btn-download-dropdown:hover {
        color: #ffffff !important;
        background-color: rgba(255, 255, 255, 0.08) !important;
        border-color: rgba(255, 255, 255, 0.3) !important;
    }

    [data-bs-theme="dark"] .manage-record-page .dropdown-menu,
    body.dark-mode .manage-record-page .dropdown-menu,
    body.theme-dark .manage-record-page .dropdown-menu {
        background-color: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    [data-bs-theme="dark"] .manage-record-page .dropdown-item,
    body.dark-mode .manage-record-page .dropdown-item,
    body.theme-dark .manage-record-page .dropdown-item {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .manage-record-page .dropdown-item:hover,
    body.dark-mode .manage-record-page .dropdown-item:hover,
    body.theme-dark .manage-record-page .dropdown-item:hover {
        background-color: rgba(255, 255, 255, 0.08) !important;
        color: #38bdf8 !important;
    }
</style>
@endsection

@section('script')
<script>
    $(function () {
        const summaryUrl = @json(route('manage.record.online.ajax.summary'));
        const classificationUrl = @json(route('manage.record.online.ajax.classification'));
        const kpiSkeletonHtml = @json(view('main.record.components.online-kpi-skeleton')->render());
        const classSkeletonHtml = @json(view('main.record.components.online-classification-skeleton')->render());

        let kpiXhr = null;
        let classificationXhr = null;

        // 1. Asynchronous KPI Summary Loader
        window.loadKpiSummary = function () {
            if (kpiXhr) {
                kpiXhr.abort();
                kpiXhr = null;
            }

            $('#kpi-summary-container').html(kpiSkeletonHtml);

            kpiXhr = $.ajax({
                url: summaryUrl,
                type: 'GET',
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function (response) {
                    kpiXhr = null;
                    if (response && response.html) {
                        $('#kpi-summary-container').html(response.html);
                    }
                },
                error: function (xhr, status) {
                    if (status === 'abort') {
                        return;
                    }
                    kpiXhr = null;
                    $('#kpi-summary-container').html(`
                        <div class="card border-0 shadow-sm p-3 mb-4 rounded-3 bg-white stat-kpi-card">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2 text-danger small">
                                    <i class="ti ti-alert-circle fs-3"></i>
                                    <span>Unable to load summary.</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-retry-kpi-summary d-inline-flex align-items-center gap-1">
                                    <i class="ti ti-refresh"></i>
                                    <span>Retry</span>
                                </button>
                            </div>
                        </div>
                    `);
                }
            });
        };

        // 2. Asynchronous Classification Summary Loader
        window.loadClassificationSummary = function () {
            if (classificationXhr) {
                classificationXhr.abort();
                classificationXhr = null;
            }

            $('#classification-summary-container').html(classSkeletonHtml);

            classificationXhr = $.ajax({
                url: classificationUrl,
                type: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function (response) {
                    classificationXhr = null;
                    $('#classification-summary-container').html(response);
                },
                error: function (xhr, status) {
                    if (status === 'abort') {
                        return;
                    }
                    classificationXhr = null;
                    $('#classification-summary-container').html(`
                        <div class="p-4 text-center">
                            <div class="text-danger small mb-2 d-flex align-items-center justify-content-center gap-1">
                                <i class="ti ti-alert-circle fs-3"></i>
                                <span>Unable to load classification summary.</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary btn-retry-classification-summary d-inline-flex align-items-center gap-1">
                                <i class="ti ti-refresh"></i>
                                <span>Retry</span>
                            </button>
                        </div>
                    `);
                }
            });
        };

        // Event delegation for retry buttons
        $(document).on('click', '.btn-retry-kpi-summary', function (e) {
            e.preventDefault();
            window.loadKpiSummary();
        });

        $(document).on('click', '.btn-retry-classification-summary', function (e) {
            e.preventDefault();
            window.loadClassificationSummary();
        });

        // Trigger parallel execution on DOM ready
        window.loadKpiSummary();
        window.loadClassificationSummary();
    });
</script>
@endsection
