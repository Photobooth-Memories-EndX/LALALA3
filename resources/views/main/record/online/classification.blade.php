@extends('layouts.sidebar-master')

@section('content')
<div class="page-body manage-record-page">
    <div class="container-xl">
        <!-- TOMBOL BACK RATA KIRI ATAS -->
        <div class="mb-3">
            <a href="{{ route('manage.record.online') }}" class="btn-back d-inline-flex align-items-center gap-1 text-decoration-none fw-medium">
                <i class="ti ti-arrow-left"></i>
                <span>Back to KP OWL</span>
            </a>
        </div>

        <!-- SECTION HEADER: Classification Detail -->
        <div class="card border-0 shadow-sm p-3 p-md-4 mb-4 rounded-3 bg-white record-section-header">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 record-section-icon"
                         style="width: 54px; height: 54px; background-color: #e0f2fe; color: #0284c7; border-radius: 12px;">
                        <i class="ti ti-school fs-1"></i>
                    </div>
                    <div>
                        <h2 class="h3 fw-bold mb-1 text-dark">Classification: {{ $classificationName }}</h2>
                        <p class="text-muted small mb-0">Monitor online learning records and participant progress for this classification.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- QUICK FILTER CARD -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-3">
                <div class="text-secondary small fw-semibold mb-2" style="color: #6c757d !important; font-size: 0.85rem;">
                    Quick Filter
                </div>
                <form id="online-filter-form" class="w-100" onsubmit="return false;">
                    <div class="d-flex align-items-center gap-2 flex-wrap w-100">
                        <!-- Employee Search -->
                        <div class="position-relative flex-grow-1" style="min-width: 180px;">
                            <input type="text" id="online-employee-input" class="form-control rounded-3 w-100"
                                   placeholder="Search employee or NIP..." autocomplete="off"
                                   value="{{ request('employee_id') ?? request('employee') }}">
                        </div>

                        <!-- Learning Title Search -->
                        <div class="position-relative flex-grow-1" style="min-width: 200px;">
                            <input type="text" id="online-title-input" class="form-control rounded-3 w-100"
                                   placeholder="Search learning title..." autocomplete="off"
                                   value="{{ request('title') ?? (request('learning_title') ?? request('content_title')) }}">
                        </div>

                        <!-- Category / Source Dropdown -->
                        <div class="flex-shrink-0" style="min-width: 130px;">
                            <select class="form-select rounded-3" id="online-category-select">
                                <option value="all" {{ request('category', 'all') === 'all' ? 'selected' : '' }}>Category: All</option>
                                <option value="all_content" {{ request('category') === 'all_content' ? 'selected' : '' }}>All Content</option>
                                <option value="playlist" {{ request('category') === 'playlist' ? 'selected' : '' }}>Playlist</option>
                            </select>
                        </div>

                        <!-- Status Dropdown -->
                        <div class="flex-shrink-0" style="min-width: 120px;">
                            <select class="form-select rounded-3" id="online-status-select">
                                <option value="all" {{ request('status', 'all') === 'all' ? 'selected' : '' }}>All Status</option>
                                <option value="finished" {{ in_array(request('status'), ['finished', 'finish', '1']) ? 'selected' : '' }}>Finished</option>
                                <option value="ongoing" {{ in_array(request('status'), ['ongoing', '0']) ? 'selected' : '' }}>Ongoing</option>
                                <option value="not_started" {{ in_array(request('status'), ['not_started', 'not-started']) ? 'selected' : '' }}>Not Started</option>
                            </select>
                        </div>

                        <!-- Year Dropdown -->
                        <div class="flex-shrink-0" style="min-width: 105px;">
                            <select class="form-select rounded-3" id="online-year-select">
                                <option value="all" {{ request('year', 'all') === 'all' ? 'selected' : '' }}>All Year</option>
                                @foreach ($years as $year)
                                    <option value="{{ $year }}" {{ (string) request('year') === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Apply Filter -->
                        <button type="button" id="btn-apply-online-filter" class="btn btn-primary btn-icon px-3 flex-shrink-0"
                                style="background-color: #003b6d; border-color: #003b6d;" data-bs-toggle="tooltip" data-bs-title="Apply Filter" title="Apply Filter">
                            <i class="ti ti-search fs-2"></i>
                        </button>

                        <!-- Reset Filter -->
                        <button type="button" id="btn-reset-online-filter" class="btn btn-outline-secondary btn-icon px-3 btn-quick-reset flex-shrink-0"
                                data-bs-toggle="tooltip" data-bs-title="Reset Filters" title="Reset Filters">
                            <i class="ti ti-refresh fs-2"></i>
                        </button>

                        <!-- Download Dropdown (Excel, CSV, PDF) -->
                        @can('learning_management')
                        <div class="dropdown flex-shrink-0">
                            <button class="btn btn-success dropdown-toggle rounded-3 px-3 btn-download d-inline-flex align-items-center gap-1"
                                    type="button" id="btn-download-online-export" data-bs-toggle="dropdown" aria-expanded="false" title="Export Records">
                                <i class="ti ti-download me-1"></i>
                                <span class="btn-download-text">Download</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 py-2" aria-labelledby="btn-download-online-export" style="min-width: 220px; z-index: 1060;">
                                <li>
                                    <h6 class="dropdown-header text-uppercase text-muted fw-bold small mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                        Export Options
                                    </h6>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2 export-format-btn" href="javascript:void(0);" data-format="xlsx" id="btn-export-online-excel">
                                        <i class="ti ti-file-spreadsheet text-success fs-2"></i>
                                        <div>
                                            <div class="fw-semibold">Excel (.xlsx)</div>
                                            <div class="small text-muted" style="font-size: 0.72rem;">Full dataset spreadsheet</div>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2 export-format-btn" href="javascript:void(0);" data-format="csv" id="btn-export-online-csv">
                                        <i class="ti ti-file-text text-primary fs-2"></i>
                                        <div>
                                            <div class="fw-semibold">CSV (.csv)</div>
                                            <div class="small text-muted" style="font-size: 0.72rem;">Standard UTF-8 tabular data</div>
                                        </div>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider my-1"></li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2 export-format-btn" href="javascript:void(0);" data-format="pdf" id="btn-export-online-pdf">
                                        <i class="ti ti-file-type-pdf text-danger fs-2"></i>
                                        <div>
                                            <div class="fw-semibold">PDF Landscape (.pdf)</div>
                                            <div class="small text-muted" style="font-size: 0.72rem;">Printable landscape report</div>
                                        </div>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        @endcan
                    </div>
                </form>
            </div>
        </div>

        <!-- SERVER-SIDE DATATABLE CARD -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
                <h4 class="card-title fw-bold text-dark mb-0">Learning Records</h4>
                <p class="text-muted small mb-0">Participant records for this classification.</p>
            </div>
            <div class="card-body p-0">
                <!-- Top Synchronized Scrollbar -->
                <div class="table-scroll-top-wrapper" id="online-scroll-top-wrapper">
                    <div class="table-scroll-top-inner" id="online-scroll-top-inner"></div>
                </div>

                <div class="table-scroll-container table-responsive" id="online-table-container">
                    <table id="online-classification-table" class="table table-vcenter table-hover align-middle mb-0 card-table">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="text-center" style="min-width: 45px; width: 45px;">NO</th>
                                <th class="text-center" style="min-width: 110px;">EMPLOYEE ID</th>
                                <th style="min-width: 160px;">EMPLOYEE NAME</th>
                                <th style="min-width: 130px;">TEAM</th>
                                <th style="min-width: 140px;">DEPARTMENT</th>
                                <th style="min-width: 130px;">COMPANY</th>
                                <th style="min-width: 220px;">COURSE TITLE</th>
                                <th class="text-center" style="min-width: 100px;">TYPE</th>
                                <th style="min-width: 110px;">CATEGORY</th>
                                <th style="min-width: 140px;">MAIN CLASSIFICATION</th>
                                <th class="text-center" style="min-width: 140px;">SUB CLASSIFICATION</th>
                                <th class="text-center" style="min-width: 120px;">DELIVERY METHOD</th>
                                <th style="min-width: 130px;">SUB DELIVERY METHOD</th>
                                <th class="text-center" style="min-width: 110px;">PROVIDER</th>
                                <th style="min-width: 130px;">PROVIDER NAME</th>
                                <th style="min-width: 120px;">VENUE</th>
                                <th class="text-center" style="min-width: 110px;">COURSE DURATION</th>
                                <th class="text-center" style="min-width: 130px;">TOTAL RECOGNIZED DURATION</th>
                                <th class="text-center" style="min-width: 110px;">LEARNING HOURS</th>
                                <th class="text-center" style="min-width: 110px;">% PROGRESS</th>
                                <th class="text-center" style="min-width: 110px;">PROGRESS STATUS</th>
                                <th style="min-width: 140px;">DATE (START - END)</th>
                                <th style="min-width: 120px;">TIME (START - END)</th>
                                <th class="text-center" style="min-width: 110px;">RECORD MONTH</th>
                                <th class="text-center" style="min-width: 110px;">REQUIREMENT</th>
                                <th class="text-center" style="min-width: 100px;">ATTENDANCE</th>
                                <th class="text-center" style="min-width: 120px;">SCORE (L1) - COURSE</th>
                                <th class="text-center" style="min-width: 90px;">SCORE (L2)</th>
                                <th class="text-center" style="min-width: 90px;">SCORE (L3)</th>
                                <th class="text-center" style="min-width: 130px;">CERTIFICATE AVAILABILITY</th>
                                <th style="min-width: 130px;">PERSON IN CHARGE (PIC)</th>
                                <th class="text-center" style="min-width: 130px;">TOTAL ENGAGEMENT TIME</th>
                                <th class="text-end" style="min-width: 80px; width: 80px;">ACTION</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="card-footer d-flex flex-wrap align-items-center justify-content-between p-3 border-top">
                    <div class="d-flex align-items-center gap-2">
                        <select class="form-select form-select-sm w-auto rounded-3" id="online-table-entries-select">
                            <option value="10" {{ $entries == 10 ? 'selected' : '' }}>10 Entries</option>
                            <option value="25" {{ $entries == 25 || empty($entries) ? 'selected' : '' }}>25 Entries</option>
                            <option value="50" {{ $entries == 50 ? 'selected' : '' }}>50 Entries</option>
                            <option value="100" {{ $entries == 100 ? 'selected' : '' }}>100 Entries</option>
                        </select>
                        <div id="online-table-info-container"></div>
                    </div>
                    <div id="online-table-pagination-container"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('main.record.components.online-detail-modal')
@include('main.record.components.more-information-modal')
@endsection

@section('style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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

    .table-scroll-top-wrapper {
        overflow-x: auto;
        overflow-y: hidden;
        height: 12px;
        width: 100%;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f8fafc;
        border-bottom: 1px solid #f1f5f9;
    }
    .table-scroll-top-wrapper::-webkit-scrollbar {
        height: 8px;
    }
    .table-scroll-top-wrapper::-webkit-scrollbar-track {
        background: #f8fafc;
    }
    .table-scroll-top-wrapper::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    .table-responsive,
    .table-scroll-container {
        max-height: 70vh;
        overflow-y: auto;
        position: relative;
    }
    .table-scroll-container {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        overflow-y: auto;
        max-height: 70vh;
        position: relative;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        scrollbar-color: #94a3b8 #f1f5f9;
    }
    .table-scroll-container::-webkit-scrollbar {
        height: 10px;
    }
    .table-scroll-container::-webkit-scrollbar-track {
        background: #f1f5f9;
    }
    .table-scroll-container::-webkit-scrollbar-thumb {
        background: #94a3b8;
        border-radius: 5px;
    }

    #online-classification-table {
        border-collapse: separate;
        border-spacing: 0;
        width: max-content !important;
        min-width: 100%;
        margin-bottom: 0;
    }
    #online-classification-table thead th {
        position: sticky !important;
        top: 0 !important;
        z-index: 10 !important;
        background-color: #f8fafc !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        font-size: 0.72rem;
        letter-spacing: 0.03em;
        font-weight: 700;
        color: #64748b;
        background-color: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
        padding: 10px 12px;
        white-space: nowrap;
        text-transform: uppercase;
    }
    #online-classification-table tbody td {
        padding: 10px 12px;
        font-size: 0.825rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        white-space: nowrap;
    }

    [data-bs-theme="dark"] #online-classification-table thead th,
    body.dark-mode #online-classification-table thead th,
    body.theme-dark #online-classification-table thead th {
        background-color: #1e293b !important;
        color: #94a3b8 !important;
        border-bottom-color: rgba(255, 255, 255, 0.1) !important;
    }
    [data-bs-theme="dark"] #online-classification-table tbody td,
    body.dark-mode #online-classification-table tbody td,
    body.theme-dark #online-classification-table tbody td {
        color: #f8fafc !important;
        border-bottom-color: rgba(255, 255, 255, 0.05) !important;
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
    .btn-quick-reset {
        transition: all 0.2s ease;
    }
    .btn-quick-reset:hover {
        background-color: #0c5b9e !important;
        color: #ffffff !important;
    }
</style>
@endsection

@section('script')
<script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>

<script>
    $(function () {
        const datatableUrl = @json(route('manage.record.online.classification.datatable', $slug));
        const downloadBaseUrl = @json($downloadUrl);
        const classificationSlug = @json($slug);

        let onlineTable = null;

        // Initialize DataTable
        onlineTable = $('#online-classification-table').DataTable({
            processing: true,
            serverSide: true,
            autoWidth: false,
            responsive: false,
            order: [[21, 'desc']], // Order by date desc
            pageLength: 25,
            dom: '<"top">rt<"bottom"><"clear">',
            language: {
                emptyTable: `<div class="p-4 text-center text-muted">
                    <i class="ti ti-school-off fs-1 text-secondary mb-2 d-block"></i>
                    <span class="fw-semibold">No online learning records found for this classification.</span>
                </div>`,
                zeroRecords: `<div class="p-4 text-center text-muted">
                    <i class="ti ti-search-off fs-1 text-secondary mb-2 d-block"></i>
                    <span class="fw-semibold">No matching records found.</span>
                </div>`,
                processing: `<div class="d-flex align-items-center justify-content-center p-3 text-primary">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    <span>Loading classification data...</span>
                </div>`
            },
            ajax: {
                url: datatableUrl,
                type: 'GET',
                data: function (d) {
                    d.classification = classificationSlug;
                    d.classification_slug = classificationSlug;
                    d.slug = classificationSlug;
                    d.employee_id = $('#online-employee-input').val();
                    d.learning_title = $('#online-title-input').val();
                    d.category = $('#online-category-select').val();
                    d.status = $('#online-status-select').val();
                    d.year = $('#online-year-select').val();
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                { data: 'employee_id', name: 'u.id_emp', className: 'text-center' },
                { data: 'employee_name', name: 'u.name' },
                { data: 'team', name: 'u.team' },
                { data: 'department', name: 'u.department' },
                { data: 'company', name: 'u.company_name' },
                { data: 'course_title', name: 'c.title' },
                { data: 'type', name: 'c.type', className: 'text-center' },
                { data: 'category', name: 'unified.category_source' },
                { data: 'main_classification', name: 'cl.classification_name' },
                { data: 'sub_classification', name: 'sub_classification', orderable: false, className: 'text-center' },
                { data: 'delivery_method', name: 'delivery_method', orderable: false, className: 'text-center' },
                { data: 'sub_delivery_method', name: 'sub_delivery_method', orderable: false },
                { data: 'provider', name: 'provider', orderable: false, className: 'text-center' },
                { data: 'provider_name', name: 'provider_name', orderable: false },
                { data: 'venue', name: 'venue', orderable: false },
                { data: 'course_duration', name: 'c.duration', className: 'text-center' },
                { data: 'total_recognized_duration', name: 'total_recognized_duration', orderable: false, className: 'text-center' },
                { data: 'learning_hours', name: 'unified.learning_hours', className: 'text-center' },
                {
                    data: 'progress_bar',
                    name: 'unified.progress',
                    className: 'text-center',
                    render: function (data, type, row) {
                        if (type === 'sort' || type === 'type') {
                            return row.progress !== undefined ? row.progress : 0;
                        }
                        let rawVal = row.progress !== undefined ? row.progress : data;
                        if (typeof rawVal === 'string') {
                            const match = rawVal.match(/(\d+(?:\.\d+)?)\s*%/);
                            if (match) {
                                return Math.round(parseFloat(match[1])) + '%';
                            }
                            rawVal = rawVal.replace(/<[^>]*>?/gm, '').trim();
                        }
                        if (rawVal !== '' && !isNaN(rawVal)) {
                            const num = parseFloat(rawVal);
                            const pct = (num <= 1 && num > 0) ? Math.round(num * 100) : Math.round(num);
                            return pct + '%';
                        }
                        return '0%';
                    }
                },
                { data: 'status_badge', name: 'unified.finish', className: 'text-center' },
                { data: 'date_merged', name: 'unified.created_at' },
                { data: 'time_merged', name: 'unified.created_at' },
                { data: 'record_month', name: 'record_month', orderable: false, className: 'text-center' },
                { data: 'requirement', name: 'requirement', orderable: false, className: 'text-center' },
                { data: 'attendance', name: 'attendance', orderable: false, className: 'text-center' },
                { data: 'score_l1', name: 'score_l1', orderable: false, className: 'text-center' },
                { data: 'score_l2', name: 'score_l2', orderable: false, className: 'text-center' },
                { data: 'score_l3', name: 'score_l3', orderable: false, className: 'text-center' },
                { data: 'certificate', name: 'certificate', orderable: false, className: 'text-center' },
                { data: 'pic', name: 'pic', orderable: false },
                { data: 'total_engagement_time', name: 'total_engagement_time', orderable: false, className: 'text-center' },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    className: 'text-end',
                    render: function (data, type, row) {
                        if (data && typeof data === 'string' && data.indexOf('dropdown-menu') !== -1) {
                            return data;
                        }
                        const recordId = row.source_id || row.id || '';
                        const editUrl = '/manage/record/online/' + encodeURIComponent(recordId) + '/edit';
                        return `<div class="dropdown">
                            <button class="btn btn-sm btn-icon btn-ghost-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ti ti-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 btn-view-learning-detail btn-more-info" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#moreInformationModal" data-source-type="${row.source_type || 'content'}" data-source-id="${recordId}" data-id="${recordId}" data-record-id="${recordId}" data-source="online" data-record-source="online">
                                        <i class="bi bi-eye text-primary me-2"></i> More Information
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2" href="${editUrl}">
                                        <i class="bi bi-pencil-square text-warning me-2"></i> Edit Record
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="#" data-bs-toggle="modal" data-bs-target="#deleteRecordModal" data-record-id="${recordId}" data-record-source="online">
                                        <i class="bi bi-trash text-danger me-2"></i> Delete Record
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 disabled text-muted" href="#" onclick="return false;" style="cursor: not-allowed;">
                                        <i class="bi bi-award text-info me-2"></i> Generate Certificate
                                    </a>
                                </li>
                            </ul>
                        </div>`;
                    }
                }
            ],
            drawCallback: function (settings) {
                const api = this.api();
                const info = api.page.info();

                $('#online-table-info-container').html(
                    `<span class="small text-muted">Showing ${info.start + 1} to ${info.end} of ${info.recordsDisplay} entries</span>`
                );

                renderCustomPagination(api, '#online-table-pagination-container');
                syncScrollbars();
            }
        });

                // Explicit click handler for More Information modal in Online Classification
        $(document).on('click', '.btn-more-info', function (e) {
            e.preventDefault();
            const recordId = $(this).data('id') || $(this).data('record-id') || $(this).attr('data-id') || $(this).attr('data-record-id');
            const source = ($(this).data('source') || $(this).data('record-source') || $(this).attr('data-source') || $(this).attr('data-record-source') || 'online').toString().toLowerCase();
            if (!recordId) return;

            const modalEl = document.getElementById('moreInformationModal');
            if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }

            $('#more-info-loading').show();
            $('#more-info-content').hide();

            $.ajax({
                url: '/manage/record/detail/' + encodeURIComponent(recordId) + (source ? '?source=' + encodeURIComponent(source) : ''),
                type: 'GET',
                dataType: 'json',
                success: function (response) {
                    $('#more-info-loading').hide();
                    $('#more-info-content').show();
                    if (window.populateMoreInformationModal) {
                        window.populateMoreInformationModal(response);
                    }
                },
                error: function () {
                    $('#more-info-loading').hide();
                    $('#more-info-content').show();
                }
            });
        });

        // Entries dropdown change
        $('#online-table-entries-select').on('change', function () {
            onlineTable.page.len($(this).val()).draw();
        });

        // Apply filters
        $('#btn-apply-online-filter').on('click', function () {
            onlineTable.draw();
        });

        // Enter key in inputs
        $('#online-employee-input, #online-title-input').on('keypress', function (e) {
            if (e.which === 13) {
                onlineTable.draw();
            }
        });

        // Dropdown filter changes
        $('#online-category-select, #online-status-select, #online-year-select').on('change', function () {
            onlineTable.draw();
        });

        // Reset filter
        $('#btn-reset-online-filter').on('click', function () {
            $('#online-filter-form')[0].reset();
            onlineTable.draw();
        });

        // Export links handler
        $('.export-format-btn').on('click', function (e) {
            e.preventDefault();
            const format = $(this).data('format');
            let exportUrl = '';
            if (format === 'xlsx') {
                exportUrl = @json(route('manage.record.online.classification.export.excel', $slug));
            } else if (format === 'csv') {
                exportUrl = @json(route('manage.record.online.classification.export.csv', $slug));
            } else if (format === 'pdf') {
                exportUrl = @json(route('manage.record.online.classification.export.pdf', $slug));
            }

            const params = new URLSearchParams();
            if ($('#online-employee-input').val()) params.set('employee_id', $('#online-employee-input').val());
            if ($('#online-title-input').val()) params.set('learning_title', $('#online-title-input').val());
            if ($('#online-category-select').val() && $('#online-category-select').val() !== 'all') params.set('category', $('#online-category-select').val());
            if ($('#online-status-select').val() && $('#online-status-select').val() !== 'all') params.set('status', $('#online-status-select').val());
            if ($('#online-year-select').val() && $('#online-year-select').val() !== 'all') params.set('year', $('#online-year-select').val());

            const finalUrl = exportUrl + (params.toString() ? '?' + params.toString() : '');
            window.location.href = finalUrl;
        });

        // Custom pagination renderer
        function renderCustomPagination(api, containerSelector) {
            const info = api.page.info();
            const totalPages = info.pages;
            const currentPage = info.page;
            const $container = $(containerSelector);

            if (totalPages <= 1) {
                $container.empty();
                return;
            }

            let html = '<ul class="pagination pagination-sm m-0">';
            html += `<li class="page-item ${currentPage === 0 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage - 1}"><i class="ti ti-chevron-left"></i></a>
            </li>`;

            let startPage = Math.max(0, currentPage - 2);
            let endPage = Math.min(totalPages - 1, currentPage + 2);

            if (startPage > 0) {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="0">1</a></li>`;
                if (startPage > 1) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i + 1}</a>
                </li>`;
            }

            if (endPage < totalPages - 1) {
                if (endPage < totalPages - 2) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${totalPages - 1}">${totalPages}</a></li>`;
            }

            html += `<li class="page-item ${currentPage === totalPages - 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage + 1}"><i class="ti ti-chevron-right"></i></a>
            </li>`;
            html += '</ul>';

            $container.html(html);

            $container.find('.page-link').on('click', function (e) {
                e.preventDefault();
                const page = $(this).data('page');
                if (page !== undefined && page >= 0 && page < totalPages && page !== currentPage) {
                    api.page(page).draw('page');
                }
            });
        }

        // Dual synchronized horizontal scrollbars
        function syncScrollbars() {
            const tableWidth = $('#online-classification-table').outerWidth();
            $('#online-scroll-top-inner').width(tableWidth);

            let isSyncingTop = false;
            let isSyncingBottom = false;

            $('#online-scroll-top-wrapper').off('scroll').on('scroll', function () {
                if (!isSyncingTop) {
                    isSyncingBottom = true;
                    $('#online-table-container').scrollLeft($(this).scrollLeft());
                }
                isSyncingTop = false;
            });

            $('#online-table-container').off('scroll').on('scroll', function () {
                if (!isSyncingBottom) {
                    isSyncingTop = true;
                    $('#online-scroll-top-wrapper').scrollLeft($(this).scrollLeft());
                }
                isSyncingBottom = false;
            });
        }
    });
</script>
@endsection
