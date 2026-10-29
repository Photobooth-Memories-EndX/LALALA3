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
        <div id="offline-kpi-summary-container">
            @include('main.record.components.offline-kpi-skeleton')
        </div>

        @if(request()->get('saved'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="ti ti-circle-check fs-2 text-success"></i>
                <div>
                    <strong>Success!</strong> Non KP OWL training has been created successfully.
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <!-- SECTION HEADER: Non KP OWL -->
        <div class="card border-0 shadow-sm p-3 p-md-4 mb-4 rounded-3 bg-white record-section-header">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 record-section-icon"
                         style="width: 54px; height: 54px; background-color: #fef3c7; color: #d97706; border-radius: 12px;">
                        <i class="ti ti-certificate fs-1"></i>
                    </div>
                    <div>
                        <h2 class="h3 fw-bold mb-1 text-dark">Non KP OWL</h2>
                        <p class="text-muted small mb-0">Offline Training Record — Manage offline training programs, classroom sessions, external certifications, and vendor learning records.</p>
                    </div>
                </div>

                <!-- Action Buttons: Add Training Dropdown (Manual & Import) | Download Dropdown -->
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="dropdown">
                        <button type="button" class="btn btn-primary dropdown-toggle rounded-3 px-3 d-inline-flex align-items-center gap-1.5"
                                style="background-color: #d97706; border-color: #d97706;"
                                data-bs-toggle="dropdown" data-toggle="dropdown" aria-expanded="false" id="btn-add-training-dropdown">
                            <i class="ti ti-plus fs-2"></i>
                            <span>Add Training</span>
                        </button>
                        <ul class="dropdown-menu shadow-sm border rounded-3 mt-1 py-1" aria-labelledby="btn-add-training-dropdown" style="min-width: 180px;">
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('manage.record.offline.create') }}" id="btn-open-offline-add" title="Manual Training Record">
                                    <i class="ti ti-pencil text-secondary fs-3"></i>
                                    <span>Manual</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 py-2"
                                   href="{{ route('manage.record.offline.import-non-ptkp') }}"
                                   id="btn-open-offline-import" title="Import Data Non PTKP">
                                    <i class="ti ti-file-spreadsheet text-success fs-3"></i>
                                    <span>Import Data Non PTKP</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="dropdown">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle rounded-3 px-3 d-inline-flex align-items-center gap-1.5"
                                data-bs-toggle="dropdown" data-toggle="dropdown" aria-expanded="false" id="btn-download-dropdown">
                            <i class="ti ti-download fs-2"></i>
                            <span>Download</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border rounded-3 mt-1 py-1" aria-labelledby="btn-download-dropdown" style="min-width: 250px;">
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('manage.record.offline.import.template') }}" id="btn-download-offline-template">
                                    <i class="ti ti-file-description text-secondary fs-3"></i>
                                    <span>Download Template</span>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('manage.record.offline.export') }}" id="btn-export-offline-xlsx">
                                    <i class="ti ti-file-spreadsheet text-success fs-3"></i>
                                    <span>Download Offline Record (.xlsx)</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('manage.record.offline.export.pdf') }}" id="btn-export-offline-pdf">
                                    <i class="ti ti-file-type-pdf text-danger fs-3"></i>
                                    <span>Download PDF (.pdf)</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('manage.record.offline.export.csv') }}" id="btn-export-offline-csv">
                                    <i class="ti ti-file-type-csv text-primary fs-3"></i>
                                    <span>Download CSV (.csv)</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        @include('main.record.components.record-navigation')

        <!-- CLASSIFICATION SUMMARY (ASYNC SKELETON SHELL) -->
        <div class="card border-0 shadow-sm mb-4 classification-summary-card">
            <div class="card-header border-0 pb-0">
                <div>
                    <h3 class="card-title mb-1">Classification Summary</h3>
                    <div class="text-muted small">Aggregate overview per classification category in Non KP OWL.</div>
                </div>
            </div>

            <div class="card-body" id="offline-classification-summary-container">
                @include('main.record.components.offline-classification-skeleton')
            </div>
        </div>
    </div>
</div>

@include('main.record.components.offline-detail-modal')

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

    [data-bs-theme="dark"] .classification-summary-card thead.bg-light,
    body.dark-mode .classification-summary-card thead.bg-light,
    body.theme-dark .classification-summary-card thead.bg-light {
        background-color: #1e293b !important;
        color: #94a3b8 !important;
    }

    [data-bs-theme="dark"] .classification-summary-card .text-dark,
    body.dark-mode .classification-summary-card .text-dark,
    body.theme-dark .classification-summary-card .text-dark {
        color: #f8fafc !important;
    }

    /* Modal dark mode styling */
    [data-bs-theme="dark"] #offlineTrainingDetailModal .modal-content,
    body.dark-mode #offlineTrainingDetailModal .modal-content,
    body.theme-dark #offlineTrainingDetailModal .modal-content,
    [data-bs-theme="dark"] #offlineImportModal .modal-content,
    body.dark-mode #offlineImportModal .modal-content,
    body.theme-dark #offlineImportModal .modal-content {
        background-color: var(--tblr-bg-surface, #1f2937) !important;
        color: #f8fafc !important;
    }
    [data-bs-theme="dark"] #offlineTrainingDetailModal .bg-light,
    body.dark-mode #offlineTrainingDetailModal .bg-light,
    body.theme-dark #offlineTrainingDetailModal .bg-light,
    [data-bs-theme="dark"] #offlineImportModal .bg-light,
    body.dark-mode #offlineImportModal .bg-light,
    body.theme-dark #offlineImportModal .bg-light {
        background-color: #111827 !important;
    }
    [data-bs-theme="dark"] #offlineTrainingDetailModal .text-dark,
    body.dark-mode #offlineTrainingDetailModal .text-dark,
    body.theme-dark #offlineTrainingDetailModal .text-dark,
    [data-bs-theme="dark"] #offlineImportModal .text-dark,
    body.dark-mode #offlineImportModal .text-dark,
    body.theme-dark #offlineImportModal .text-dark {
        color: #f8fafc !important;
    }
    [data-bs-theme="dark"] #offlineImportModal .bg-white,
    body.dark-mode #offlineImportModal .bg-white,
    body.theme-dark #offlineImportModal .bg-white {
        background-color: var(--tblr-bg-surface, #1f2937) !important;
    }
    [data-bs-theme="dark"] #offlineImportModal .table-light,
    body.dark-mode #offlineImportModal .table-light,
    body.theme-dark #offlineImportModal .table-light {
        background-color: #1e293b !important;
        color: #f8fafc !important;
    }
</style>
@endsection

@section('script')
<script>
    $(function () {
        const summaryUrl = @json(route('manage.record.offline.ajax.summary'));
        const classificationUrl = @json(route('manage.record.offline.ajax.classification'));
        const kpiSkeletonHtml = @json(view('main.record.components.offline-kpi-skeleton')->render());
        const classSkeletonHtml = @json(view('main.record.components.offline-classification-skeleton')->render());

        let kpiXhr = null;
        let classificationXhr = null;

        // 1. Asynchronous Offline KPI Summary Loader
        window.loadOfflineKpiSummary = function () {
            if (kpiXhr) {
                kpiXhr.abort();
                kpiXhr = null;
            }

            $('#offline-kpi-summary-container').html(kpiSkeletonHtml);

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
                        $('#offline-kpi-summary-container').html(response.html);
                    }
                },
                error: function (xhr, status) {
                    if (status === 'abort') return;
                    kpiXhr = null;
                    $('#offline-kpi-summary-container').html(`
                        <div class="card border-0 shadow-sm p-3 mb-4 rounded-3 bg-white stat-kpi-card">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2 text-danger small">
                                    <i class="ti ti-alert-circle fs-3"></i>
                                    <span>Unable to load offline training summary.</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-retry-offline-kpi">
                                    <i class="ti ti-refresh me-1"></i> Retry
                                </button>
                            </div>
                        </div>
                    `);
                }
            });
        };

        // 2. Asynchronous Offline Classification Summary Loader
        window.loadOfflineClassificationSummary = function () {
            if (classificationXhr) {
                classificationXhr.abort();
                classificationXhr = null;
            }

            $('#offline-classification-summary-container').html(classSkeletonHtml);

            classificationXhr = $.ajax({
                url: classificationUrl,
                type: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function (response) {
                    classificationXhr = null;
                    const html = typeof response === 'object' && response.html ? response.html : response;
                    $('#offline-classification-summary-container').html(html);
                },
                error: function (xhr, status) {
                    if (status === 'abort') return;
                    classificationXhr = null;
                    $('#offline-classification-summary-container').html(`
                        <div class="p-4 text-center">
                            <div class="text-danger small mb-2 d-flex align-items-center justify-content-center gap-1">
                                <i class="ti ti-alert-circle fs-3"></i>
                                <span>Unable to load classification summary.</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary btn-retry-offline-classification d-inline-flex align-items-center gap-1">
                                <i class="ti ti-refresh"></i>
                                <span>Retry</span>
                            </button>
                        </div>
                    `);
                }
            });
        };

        $(document).on('click', '.btn-retry-offline-kpi', function (e) {
            e.preventDefault();
            window.loadOfflineKpiSummary();
        });

        $(document).on('click', '.btn-retry-offline-classification', function (e) {
            e.preventDefault();
            window.loadOfflineClassificationSummary();
        });

        // Trigger parallel async loading on DOM ready
        window.loadOfflineKpiSummary();
        window.loadOfflineClassificationSummary();

        // Auto-redirect if URL has legacy ?open_import=1
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('open_import') === '1') {
            window.location.href = "{{ route('manage.record.offline.import-non-ptkp') }}";
        }

        // Initialize tooltips
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }

        // Click event handler for .btn-more-info / More Information
        $(document).on('click', '.btn-more-info', function (e) {
            e.preventDefault();
            const recordId = $(this).data('id') || $(this).data('record-id') || $(this).attr('data-id') || $(this).attr('data-record-id');
            const source = ($(this).data('source') || $(this).data('record-source') || $(this).attr('data-source') || $(this).attr('data-record-source') || 'offline').toString().toLowerCase();
            if (!recordId) return;

            const modalEl = document.getElementById('moreInformationModal');
            if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }

            $('#more-info-loading').show();
            $('#more-info-content').hide();

            // Fetch the record detail via $.ajax() from /manage/record/detail/{id}
            $.ajax({
                url: '/manage/record/detail/' + encodeURIComponent(recordId) + (source ? '?source=' + encodeURIComponent(source) : ''),
                type: 'GET',
                dataType: 'json',
                success: function (response) {
                    $('#more-info-loading').hide();
                    $('#more-info-content').show();
                    if (window.populateMoreInformationModal) {
                        window.populateMoreInformationModal(response);
                    } else {
                        const d = response.data || response;
                        $('#modal-badge-source').text(d.source || 'NON KP OWL');
                        $('#modal-course-title').text(d.course_title || '-');
                        $('#modal-employee-id').text(d.employee_id || '-');
                        $('#modal-employee-name').text(d.employee_name || '-');
                        $('#modal-grade').text(d.grade || '-');
                        $('#modal-job-position').text(d.job_position || '-');
                        $('#modal-instructor').text(d.instructor || '-');
                        $('#modal-bond-status').text(d.bond_status || 'No');
                        $('#modal-bond-duration').text(d.bond_duration || '-');
                        $('#modal-bond-end-date').text(d.bond_end_date || '-');
                        $('#modal-bond-amount').text(d.bond_amount || '-');
                    }
                },
                error: function () {
                    $('#more-info-loading').hide();
                    $('#more-info-content').show();
                }
            });
        });
    });
</script>
@endsection
