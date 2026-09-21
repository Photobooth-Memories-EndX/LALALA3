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

        @include('main.record.components.record-navigation')

        <!-- SECTION HEADER: KP OWL -->
        <div class="card border-0 shadow-sm p-3 p-md-4 mb-4 rounded-3 bg-white record-section-header">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 record-section-icon"
                     style="width: 54px; height: 54px; background-color: #e0f2fe; color: #0284c7; border-radius: 12px;">
                    <i class="ti ti-device-laptop fs-1"></i>
                </div>
                <div>
                    <h2 class="h3 fw-bold mb-1 text-dark">KP OWL</h2>
                    <p class="text-muted small mb-0">Online Learning Record — Monitor learning progress, status, and employee training completion in OWL LMS.</p>
                </div>
            </div>
        </div>

        <!-- 4 KPI SUMMARY CARDS (ASYNC SKELETON SHELL) -->
        <div id="kpi-summary-container">
            @include('main.record.components.online-kpi-skeleton')
        </div>

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

        <!-- LEARNING RECORD FILTER & TABLE -->
        @include('main.learning.components.learning-filter')
        @include('main.learning.components.learning-table')
    </div>
</div>
@endsection

@section('style')
<link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">

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

    .record-section-header {
        border-radius: 14px !important;
        border: 1px solid #e2e8f0 !important;
    }

    [data-bs-theme="dark"] .record-section-header,
    body.dark-mode .record-section-header,
    body.theme-dark .record-section-header {
        background-color: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    [data-bs-theme="dark"] .record-section-header .text-dark,
    body.dark-mode .record-section-header .text-dark,
    body.theme-dark .record-section-header .text-dark {
        color: #f8fafc !important;
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

    #learning-table {
        font-size: 13px;
    }

    #learning-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap;
    }

    #learning-table tbody td {
        vertical-align: middle;
        padding-top: 14px;
        padding-bottom: 14px;
    }

    .btn-more-filters,
    #btn-more-filters {
        border-color: #0c5b9e !important;
        color: #0c5b9e !important;
        background-color: transparent !important;
        transition: all 0.2s ease;
    }
    .btn-more-filters:hover,
    .btn-more-filters:focus,
    .btn-more-filters:active,
    #btn-more-filters:hover,
    #btn-more-filters:focus,
    #btn-more-filters:active {
        background-color: #0c5b9e !important;
        color: #ffffff !important;
        border-color: #0c5b9e !important;
    }
    .btn-more-filters:hover i,
    .btn-more-filters:focus i,
    .btn-more-filters:active i,
    #btn-more-filters:hover i,
    #btn-more-filters:focus i,
    #btn-more-filters:active i {
        color: #ffffff !important;
    }

    [data-bs-theme="dark"] .btn-more-filters,
    [data-bs-theme="dark"] #btn-more-filters,
    body.dark-mode .btn-more-filters,
    body.dark-mode #btn-more-filters,
    body.theme-dark .btn-more-filters,
    body.theme-dark #btn-more-filters {
        background-color: transparent !important;
        border-color: #3b82f6 !important;
        color: #60a5fa !important;
    }
    [data-bs-theme="dark"] .btn-more-filters:hover,
    [data-bs-theme="dark"] #btn-more-filters:hover,
    body.dark-mode .btn-more-filters:hover,
    body.dark-mode #btn-more-filters:hover,
    body.theme-dark .btn-more-filters:hover,
    body.theme-dark #btn-more-filters:hover {
        background-color: #2563eb !important;
        color: #ffffff !important;
        border-color: #2563eb !important;
    }

    .btn-download {
        background-color: #2fb344 !important;
        border-color: #2fb344 !important;
        color: #ffffff !important;
        transition: all 0.2s ease;
    }
    .btn-download:hover,
    .btn-download:focus,
    .btn-download:active {
        background-color: #269639 !important;
        border-color: #269639 !important;
        color: #ffffff !important;
    }
    .btn-download:hover i,
    .btn-download:focus i,
    .btn-download:active i {
        color: #ffffff !important;
    }

    .btn-quick-reset {
        transition: all 0.2s ease;
    }
    .btn-quick-reset:hover {
        background-color: #0c5b9e !important;
        color: #ffffff !important;
    }
    [data-bs-theme="dark"] .btn-quick-reset,
    body.dark-mode .btn-quick-reset,
    body.theme-dark .btn-quick-reset {
        background-color: transparent !important;
    }
    [data-bs-theme="dark"] .btn-quick-reset:hover,
    body.dark-mode .btn-quick-reset:hover,
    body.theme-dark .btn-quick-reset:hover {
        background-color: #0c5b9e !important;
        color: #ffffff !important;
    }

    [data-bs-theme="dark"] #moreLearningFiltersModal .modal-content,
    body.dark-mode #moreLearningFiltersModal .modal-content,
    body.theme-dark #moreLearningFiltersModal .modal-content {
        background-color: var(--tblr-bg-surface, #1f2937) !important;
        color: #f8fafc !important;
    }
    [data-bs-theme="dark"] #moreLearningFiltersModal .text-dark,
    body.dark-mode #moreLearningFiltersModal .text-dark,
    body.theme-dark #moreLearningFiltersModal .text-dark {
        color: #f8fafc !important;
    }
    [data-bs-theme="dark"] #moreLearningFiltersModal .form-control,
    [data-bs-theme="dark"] #moreLearningFiltersModal .form-select,
    body.dark-mode #moreLearningFiltersModal .form-control,
    body.dark-mode #moreLearningFiltersModal .form-select,
    body.theme-dark #moreLearningFiltersModal .form-control,
    body.theme-dark #moreLearningFiltersModal .form-select {
        background-color: var(--tblr-bg-surface-secondary, #111827) !important;
        border-color: rgba(255, 255, 255, 0.15) !important;
        color: #f8fafc !important;
    }
    [data-bs-theme="dark"] .kpi-skeleton-row .stat-kpi-card,
    body.dark-mode .kpi-skeleton-row .stat-kpi-card,
    body.theme-dark .kpi-skeleton-row .stat-kpi-card {
        background-color: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }
</style>
@endsection

@section('script')
    @include('main.learning.components.learning-table-script', [
        'datatableUrl' => route('manage.record.online.datatable'),
        'employeeSearchUrl' => route('manage.record.online.search-employees'),
        'titleSearchUrl' => route('manage.record.online.search-titles'),
        'downloadUrl' => route('manage.record.online.download'),
    ])

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
