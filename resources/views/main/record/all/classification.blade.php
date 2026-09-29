@extends('layouts.sidebar-master')

@section('content')
<div class="page-body manage-record-page">
    <div class="container-xl">
        <!-- TOMBOL BACK RATA KIRI ATAS -->
        <div class="mb-3">
            <a href="{{ route('manage.record.all') }}" class="btn-back d-inline-flex align-items-center gap-1 text-decoration-none fw-medium">
                <i class="ti ti-arrow-left"></i>
                <span>Back to All KP OWL</span>
            </a>
        </div>

        <!-- SECTION HEADER: Classification Detail -->
        <div class="card border-0 shadow-sm p-3 p-md-4 mb-4 rounded-3 bg-white record-section-header">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 record-section-icon"
                         style="width: 54px; height: 54px; background-color: #ede9fe; color: #7c3aed; border-radius: 12px;">
                        <i class="ti ti-category fs-1"></i>
                    </div>
                    <div>
                        <h2 class="h3 fw-bold mb-1 text-dark">Classification: {{ $classificationName }}</h2>
                        <p class="text-muted small mb-0">Combined Online &amp; Offline Learning Records for this classification.</p>
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
                <form id="all-filter-form" onsubmit="return false;">
                    <div class="row g-2 align-items-center">
                        <!-- 1. Employee Search -->
                        <div class="col-12 col-sm-6 col-md-3">
                            <input type="text"
                                   id="all-filter-employee"
                                   class="form-control form-control-sm"
                                   placeholder="Search Employee / NIP..."
                                   autocomplete="off">
                        </div>

                        <!-- 2. Learning Title Search -->
                        <div class="col-12 col-sm-6 col-md-3">
                            <input type="text"
                                   id="all-filter-title"
                                   class="form-control form-control-sm"
                                   placeholder="Search Learning Title..."
                                   autocomplete="off">
                        </div>

                        <!-- 3. Source Filter -->
                        <div class="col-12 col-sm-6 col-md-2">
                            <select id="all-filter-source" class="form-select form-select-sm">
                                <option value="all">All Sources</option>
                                <option value="online">KP OWL (Online)</option>
                                <option value="offline">Non KP OWL (Offline)</option>
                            </select>
                        </div>

                        <!-- 4. Status Filter -->
                        <div class="col-12 col-sm-6 col-md-2">
                            <select id="all-filter-status" class="form-select form-select-sm">
                                <option value="all">All Status</option>
                                <option value="finished">Finished</option>
                                <option value="ongoing">Ongoing</option>
                                <option value="not_started">Not Started</option>
                            </select>
                        </div>

                        <!-- 5. Year Filter -->
                        <div class="col-12 col-sm-6 col-md-1">
                            <select id="all-filter-year" class="form-select form-select-sm">
                                <option value="all">All Years</option>
                                @foreach ($years as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Action Buttons -->
                        <div class="col-12 col-md-1 text-end d-flex gap-1 justify-content-end">
                            <button type="submit" id="btn-all-filter" class="btn btn-sm btn-primary px-2.5" title="Apply Filter">
                                <i class="ti ti-search"></i>
                            </button>
                            <button type="button" id="btn-all-reset" class="btn btn-sm btn-outline-secondary px-2.5" title="Reset Filters">
                                <i class="ti ti-rotate-clockwise"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- COMBINED LEARNING RECORDS TABLE CARD -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h4 class="card-title fw-bold text-dark mb-0">Combined Learning Records</h4>
                    <p class="text-muted small mb-0">Participant records for classification <strong>{{ $classificationName }}</strong>.</p>
                </div>

                <!-- Length Menu / Entries Selector -->
                <div class="d-flex align-items-center gap-2">
                    <label for="all-table-entries-select" class="text-muted small mb-0">Show</label>
                    <select id="all-table-entries-select" class="form-select form-select-sm" style="width: 80px;">
                        <option value="10">10</option>
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span class="text-muted small">entries</span>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="all-classification-learning-table" style="width: 100%;">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th style="width: 45px;">No</th>
                                <th style="width: 95px;">Source</th>
                                <th style="width: 95px;">Status</th>
                                <th style="width: 85px;">ID</th>
                                <th style="min-width: 170px;">Employee</th>
                                <th style="min-width: 80px;">Grade</th>
                                <th style="min-width: 150px;">Job Position</th>
                                <th style="min-width: 130px;">Team</th>
                                <th style="min-width: 150px;">Department</th>
                                <th style="min-width: 150px;">Company</th>
                                <th style="min-width: 220px;">Learning Title</th>
                                <th style="min-width: 150px;">Sub Title</th>
                                <th style="min-width: 110px;">Type</th>
                                <th style="min-width: 120px;">Category</th>
                                <th style="min-width: 90px;">Level</th>
                                <th style="min-width: 90px;">Batch</th>
                                <th style="min-width: 150px;">Main Classification</th>
                                <th style="min-width: 150px;">Sub Classification</th>
                                <th style="min-width: 110px;">Corporate KPI</th>
                                <th style="min-width: 150px;">Sub Corporate KPI</th>
                                <th style="min-width: 130px;">Delivery Method</th>
                                <th style="min-width: 140px;">Sub Delivery Method</th>
                                <th style="min-width: 110px;">Provider</th>
                                <th style="min-width: 120px;">Sub Provider</th>
                                <th style="min-width: 160px;">Provider Name</th>
                                <th style="min-width: 130px;">Venue</th>
                                <th style="min-width: 140px;">Instructor</th>
                                <th style="min-width: 100px;">Duration</th>
                                <th class="text-center" style="min-width: 110px;">Training Hours</th>
                                <th style="min-width: 110px;">Start Date</th>
                                <th style="min-width: 110px;">Finish Date</th>
                                <th class="text-center" style="min-width: 120px;">Participant Hours</th>
                                <th style="min-width: 130px;">Progress</th>
                                <th class="text-center" style="min-width: 120px;">Assessment Score</th>
                                <th style="min-width: 110px;">Certificate</th>
                                <th style="min-width: 110px;">Attendance</th>
                                <th style="min-width: 150px;">Attendance Reason</th>
                                <th style="min-width: 110px;">Bond Status</th>
                                <th style="min-width: 115px;">Bond End Date</th>
                                <th style="min-width: 130px;">Bond Amount</th>
                                <th style="min-width: 110px;">L1 Program</th>
                                <th class="text-center" style="min-width: 110px;">L2 Numerical</th>
                                <th style="min-width: 110px;">L2 Verbal</th>
                                <th class="text-center" style="min-width: 110px;">L3 Numerical</th>
                                <th style="min-width: 110px;">L3 Verbal</th>
                                <th style="min-width: 110px;">PIC</th>
                                <th class="text-end" style="width: 90px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Server-side DataTable rows injected dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Custom Pagination Footer Container -->
            <div class="card-footer bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2" id="all-table-footer">
                <div class="text-muted small" id="all-table-info">
                    Showing 0 to 0 of 0 entries
                </div>
                <div id="all-table-pagination"></div>
            </div>
        </div>
    </div>
</div>

<!-- INCLUDE OFFLINE DETAIL MODAL -->
@include('main.record.components.offline-detail-modal')

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

    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    #all-classification-learning-table {
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
    }

    #all-classification-learning-table thead th {
        font-size: 0.72rem;
        letter-spacing: 0.03em;
        font-weight: 700;
        color: #64748b;
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 10px 12px;
        white-space: nowrap;
        text-transform: uppercase;
    }

    #all-classification-learning-table tbody td {
        padding: 9px 12px;
        font-size: 0.825rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        white-space: nowrap;
        background-color: #ffffff;
    }

    #all-classification-learning-table tbody tr:hover td {
        background-color: #f8fafc;
    }

    /* Sticky Frozen Columns - Left (Cols 1-5) */
    #all-classification-learning-table th:nth-child(1),
    #all-classification-learning-table td:nth-child(1) {
        position: sticky;
        left: 0;
        z-index: 2;
        min-width: 45px;
        max-width: 45px;
        width: 45px;
    }

    #all-classification-learning-table th:nth-child(2),
    #all-classification-learning-table td:nth-child(2) {
        position: sticky;
        left: 45px;
        z-index: 2;
        min-width: 95px;
        max-width: 95px;
        width: 95px;
    }

    #all-classification-learning-table th:nth-child(3),
    #all-classification-learning-table td:nth-child(3) {
        position: sticky;
        left: 140px;
        z-index: 2;
        min-width: 95px;
        max-width: 95px;
        width: 95px;
    }

    #all-classification-learning-table th:nth-child(4),
    #all-classification-learning-table td:nth-child(4) {
        position: sticky;
        left: 235px;
        z-index: 2;
        min-width: 85px;
        max-width: 85px;
        width: 85px;
    }

    #all-classification-learning-table th:nth-child(5),
    #all-classification-learning-table td:nth-child(5) {
        position: sticky;
        left: 320px;
        z-index: 2;
        min-width: 170px;
        max-width: 170px;
        width: 170px;
        box-shadow: 3px 0 5px -1px rgba(0, 0, 0, 0.08);
    }

    /* Sticky Frozen Column - Right (Col 47: Action) */
    #all-classification-learning-table th:nth-child(47),
    #all-classification-learning-table td:nth-child(47) {
        position: sticky;
        right: 0;
        z-index: 2;
        min-width: 90px;
        max-width: 90px;
        width: 90px;
        text-align: right;
        box-shadow: -3px 0 5px -1px rgba(0, 0, 0, 0.08);
    }

    /* Header z-index higher than body td */
    #all-classification-learning-table thead th:nth-child(1),
    #all-classification-learning-table thead th:nth-child(2),
    #all-classification-learning-table thead th:nth-child(3),
    #all-classification-learning-table thead th:nth-child(4),
    #all-classification-learning-table thead th:nth-child(5),
    #all-classification-learning-table thead th:nth-child(47) {
        z-index: 4;
        background-color: #f8fafc;
    }

    /* Dark Mode Overrides */
    [data-bs-theme="dark"] #all-classification-learning-table thead th,
    body.dark-mode #all-classification-learning-table thead th,
    body.theme-dark #all-classification-learning-table thead th {
        background-color: #1e293b !important;
        color: #94a3b8 !important;
        border-color: rgba(255, 255, 255, 0.08) !important;
    }

    [data-bs-theme="dark"] #all-classification-learning-table tbody td,
    body.dark-mode #all-classification-learning-table tbody td,
    body.theme-dark #all-classification-learning-table tbody td {
        background-color: #0f172a !important;
        border-color: rgba(255, 255, 255, 0.05) !important;
        color: #cbd5e1 !important;
    }

    [data-bs-theme="dark"] #all-classification-learning-table tbody tr:hover td,
    body.dark-mode #all-classification-learning-table tbody tr:hover td,
    body.theme-dark #all-classification-learning-table tbody tr:hover td {
        background-color: #1e293b !important;
    }

    [data-bs-theme="dark"] #all-classification-learning-table th:nth-child(5),
    [data-bs-theme="dark"] #all-classification-learning-table td:nth-child(5) {
        box-shadow: 3px 0 5px -1px rgba(0, 0, 0, 0.35);
    }

    [data-bs-theme="dark"] #all-classification-learning-table th:nth-child(47),
    [data-bs-theme="dark"] #all-classification-learning-table td:nth-child(47) {
        box-shadow: -3px 0 5px -1px rgba(0, 0, 0, 0.35);
    }

    .extra-small {
        font-size: 0.75rem;
    }
</style>
@endsection

@section('script')
<script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>

<script>
$(function () {
    const datatableUrl = @json(route('manage.record.all.classification.datatable', $slug));
    const defaultEntries = 25;

    let allTable = null;

    // 1. Initialize Combined Classification DataTable
    if ($.fn.DataTable.isDataTable('#all-classification-learning-table')) {
        $('#all-classification-learning-table').DataTable().destroy();
    }

    const renderSafeText = function (data) {
        if (data === null || data === undefined || data === '' || data === '-') {
            return '<span class="text-muted">-</span>';
        }
        return $('<div>').text(data).html();
    };

    allTable = $('#all-classification-learning-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: false,
        autoWidth: false,
        pageLength: defaultEntries,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [[30, 'desc']], // Finish date column (Index 30)
        dom: '<"table-responsive"t>',
        ajax: {
            url: datatableUrl,
            type: 'GET',
            data: function (d) {
                d.employee_id = $('#all-filter-employee').val().trim();
                d.learning_title = $('#all-filter-title').val().trim();
                d.source = $('#all-filter-source').val();
                d.status = $('#all-filter-status').val();
                d.year = $('#all-filter-year').val();
                d.entries = $('#all-table-entries-select').val() || defaultEntries;
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-muted fw-semibold text-center' },
            { data: 'source_badge', name: 'source', orderable: true },
            { data: 'status_badge', name: 'status', orderable: true },
            { data: 'employee_id', name: 'employee_id', orderable: true, defaultContent: '-', className: 'text-muted fw-semibold' },
            { data: 'employee_name', name: 'employee_name', orderable: true, defaultContent: '-', className: 'fw-semibold text-dark' },
            { data: 'grade', name: 'grade', orderable: true, defaultContent: '-', render: renderSafeText },
            { data: 'job_position', name: 'job_position', orderable: true, defaultContent: '-', render: renderSafeText },
            { data: 'team', name: 'team', orderable: true, defaultContent: '-', render: renderSafeText },
            { data: 'department', name: 'department', orderable: true, defaultContent: '-', render: renderSafeText },
            { data: 'company', name: 'company', orderable: true, defaultContent: '-', render: renderSafeText },
            {
                data: 'learning_title',
                name: 'learning_title',
                orderable: true,
                defaultContent: '-',
                render: function (data, type) {
                    if (type !== 'display') {
                        return data;
                    }
                    const text = data ? $('<div>').text(data).html() : '<span class="text-muted">-</span>';
                    return '<div class="fw-semibold text-dark text-truncate" style="max-width: 280px;" title="' + (data ? $('<div>').text(data).html() : '') + '">' + text + '</div>';
                }
            },
            { data: 'sub_course_name', name: 'sub_course_name', orderable: true, defaultContent: '-', render: renderSafeText },
            { data: 'type', name: 'type', orderable: true, defaultContent: '-', render: renderSafeText },
            { data: 'category', name: 'category', orderable: true, defaultContent: '-', render: renderSafeText },
            { data: 'level', name: 'level', orderable: true, defaultContent: '-', render: renderSafeText },
            { data: 'batch', name: 'batch', orderable: true, defaultContent: '-', render: renderSafeText },
            { data: 'classification', name: 'classification', orderable: true, defaultContent: '-', render: renderSafeText },
            { data: 'sub_classification', name: 'sub_classification', orderable: true, defaultContent: '-', render: renderSafeText },
            { data: 'kpi_badge', name: 'counted_corporate_kpi', orderable: true, defaultContent: '-' },
            { data: 'sub_corporate_kpi', name: 'sub_corporate_kpi', orderable: true, defaultContent: '-', render: renderSafeText },
            { data: 'delivery_method', name: 'delivery_method', orderable: true, defaultContent: '-', render: renderSafeText },
            { data: 'sub_delivery_method', name: 'sub_delivery_method', orderable: true, defaultContent: '-', render: renderSafeText },
            { data: 'provider', name: 'provider', orderable: true, defaultContent: '-', render: renderSafeText },
            { data: 'sub_provider', name: 'sub_provider', orderable: true, defaultContent: '-', render: renderSafeText },
            { data: 'provider_name', name: 'provider_name', orderable: true, defaultContent: '-', render: renderSafeText },
            { data: 'venue', name: 'venue', orderable: true, defaultContent: '-', render: renderSafeText },
            { data: 'instructor', name: 'instructor', orderable: true, defaultContent: '-', render: renderSafeText },
            { data: 'duration_formatted', name: 'duration_seconds', orderable: true, defaultContent: '-' },
            { data: 'training_hours_formatted', name: 'training_hours', orderable: true, defaultContent: '-', className: 'text-center' },
            { data: 'start_date_formatted', name: 'start_date', orderable: true, defaultContent: '-', className: 'text-nowrap' },
            { data: 'finish_date_formatted', name: 'finish_date', orderable: true, defaultContent: '-', className: 'text-nowrap' },
            { data: 'learning_hours_formatted', name: 'learning_hours', orderable: true, defaultContent: '-', className: 'text-center fw-bold text-dark' },
            { data: 'progress_bar', name: 'progress', orderable: true },
            { data: 'assessment_score_formatted', name: 'assessment_score', orderable: true, defaultContent: '-', className: 'text-center' },
            { data: 'certificate_badge', name: 'certificate', orderable: true, defaultContent: '-' },
            { data: 'attendance_badge', name: 'attendance', orderable: true, defaultContent: '-' },
            { data: 'attendance_reason', name: 'attendance_reason', orderable: true, defaultContent: '-', render: renderSafeText },
            { data: 'bond_status', name: 'bond_status', orderable: true, defaultContent: '-', render: renderSafeText },
            { data: 'bond_end_date_formatted', name: 'bond_end_date', orderable: true, defaultContent: '-', className: 'text-nowrap' },
            { data: 'bond_amount_formatted', name: 'bond_amount', orderable: true, defaultContent: '-' },
            { data: 'l1_program', name: 'l1_program', orderable: true, defaultContent: '-', render: renderSafeText },
            { data: 'l2_numerical_formatted', name: 'l2_numerical', orderable: true, defaultContent: '-', className: 'text-center' },
            { data: 'l2_verbal', name: 'l2_verbal', orderable: true, defaultContent: '-', render: renderSafeText },
            { data: 'l3_numerical_formatted', name: 'l3_numerical', orderable: true, defaultContent: '-', className: 'text-center' },
            { data: 'l3_verbal', name: 'l3_verbal', orderable: true, defaultContent: '-', render: renderSafeText },
            { data: 'pic', name: 'pic', orderable: true, defaultContent: '-', render: renderSafeText },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end text-nowrap' },
        ],
        language: {
            processing: '<div class="py-3 text-center"><div class="spinner-border spinner-border-sm text-primary" role="status"></div><span class="ms-2 small text-muted">Loading learning records...</span></div>',
            emptyTable: 'No learning records found matching the criteria.',
            zeroRecords: 'No matching learning records found.'
        },
        drawCallback: function (settings) {
            const api = this.api();
            const pageInfo = api.page.info();

            // Sync custom pagination controls
            $('#all-table-info').text(
                pageInfo.recordsDisplay > 0
                    ? `Showing ${pageInfo.start + 1} to ${pageInfo.end} of ${pageInfo.recordsDisplay.toLocaleString()} entries`
                    : 'Showing 0 to 0 of 0 entries'
            );

            renderCustomPagination(pageInfo);
        }
    });

    // Custom pagination renderer
    function renderCustomPagination(info) {
        const totalPages = info.pages;
        const currentPage = info.page;
        const $container = $('#all-table-pagination');

        if (totalPages <= 1) {
            $container.empty();
            return;
        }

        let html = '<ul class="pagination pagination-sm mb-0">';
        html += `<li class="page-item ${currentPage === 0 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">&laquo;</a>
                 </li>`;

        const startPage = Math.max(0, currentPage - 2);
        const endPage = Math.min(totalPages - 1, currentPage + 2);

        if (startPage > 0) {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="0">1</a></li>`;
            if (startPage > 1) {
                html += `<li class="page-item disabled"><span class="page-link">&hellip;</span></li>`;
            }
        }

        for (let p = startPage; p <= endPage; p++) {
            html += `<li class="page-item ${p === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${p}">${p + 1}</a>
                     </li>`;
        }

        if (endPage < totalPages - 1) {
            if (endPage < totalPages - 2) {
                html += `<li class="page-item disabled"><span class="page-link">&hellip;</span></li>`;
            }
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${totalPages - 1}">${totalPages}</a></li>`;
        }

        html += `<li class="page-item ${currentPage >= totalPages - 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">&raquo;</a>
                 </li>`;
        html += '</ul>';

        $container.html(html);
    }

    // Pagination link click
    $(document).on('click', '#all-table-pagination .page-link', function (e) {
        e.preventDefault();
        const targetPage = parseInt($(this).data('page'), 10);
        if (!isNaN(targetPage) && allTable) {
            allTable.page(targetPage).draw('page');
        }
    });

    // Entries count select change
    $('#all-table-entries-select').on('change', function () {
        const entries = parseInt($(this).val(), 10) || defaultEntries;
        if (allTable) {
            allTable.page.len(entries).draw();
        }
    });

    // Filter submit
    $('#all-filter-form').on('submit', function (e) {
        e.preventDefault();
        if (allTable) {
            allTable.page(0).ajax.reload();
        }
    });

    // Filter reset
    $('#btn-all-reset').on('click', function () {
        $('#all-filter-employee').val('');
        $('#all-filter-title').val('');
        $('#all-filter-source').val('all');
        $('#all-filter-status').val('all');
        $('#all-filter-year').val('all');
        if (allTable) {
            allTable.page(0).ajax.reload();
        }
    });

    // Filter changes instant reload
    $('#all-filter-source, #all-filter-status, #all-filter-year').on('change', function () {
        if (allTable) {
            allTable.page(0).ajax.reload();
        }
    });

    // 2. Detail Modal for Offline records
    $(document).on('click', '.btn-view-offline-detail', function () {
        const participantId = $(this).data('id');
        if (!participantId) return;

        const modalEl = document.getElementById('offlineTrainingDetailModal');
        if (!modalEl) return;

        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        $('#offline-detail-loading').show();
        $('#offline-detail-content').hide();
        $('#offline-detail-error').hide();

        $.ajax({
            url: `/manage/record/offline/${participantId}/detail`,
            type: 'GET',
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function (res) {
                $('#offline-detail-loading').hide();
                if (res && res.success && res.data) {
                    const d = res.data;

                    // Header
                    $('#modal-detail-course-name').text(d.course_name || '-');
                    $('#modal-detail-sub-course-name').text(d.sub_course_name || '');
                    $('#modal-detail-type').text(d.type || 'In-House');

                    const progress = parseFloat(d.progress) || 0;
                    const statusBadge = $('#modal-detail-status');
                    if (progress >= 100) {
                        statusBadge.removeClass('bg-warning-lt bg-secondary-lt').addClass('bg-success-lt').text('FINISHED');
                    } else if (progress > 0) {
                        statusBadge.removeClass('bg-success-lt bg-secondary-lt').addClass('bg-warning-lt').text(`ONGOING (${Math.round(progress)}%)`);
                    } else {
                        statusBadge.removeClass('bg-success-lt bg-warning-lt').addClass('bg-secondary-lt').text('NOT STARTED');
                    }

                    // Section 1: Course Info
                    $('#modal-detail-batch').text(d.batch || '-');
                    $('#modal-detail-category').text(d.category || '-');
                    $('#modal-detail-level').text(d.level || '-');
                    $('#modal-detail-sec-type').text(d.type || '-');

                    // Section 2: Classification & KPI
                    $('#modal-detail-main-class').text(d.main_classification || '-');
                    $('#modal-detail-sub-class').text(d.sub_classification || '-');
                    $('#modal-detail-corporate-kpi').text(d.counted_corporate_kpi ? 'Yes' : (d.counted_corporate_kpi === false ? 'No' : '-'));
                    $('#modal-detail-sub-corporate-kpi').text(d.sub_corporate_kpi || '-');

                    // Section 3: Delivery & Provider
                    $('#modal-detail-delivery-method').text(d.delivery_method || '-');
                    $('#modal-detail-sub-delivery-method').text(d.sub_delivery_method || '-');
                    $('#modal-detail-provider').text(d.provider || '-');
                    $('#modal-detail-sub-provider').text(d.sub_provider || '-');
                    $('#modal-detail-provider-name').text(d.provider_name || '-');
                    $('#modal-detail-venue').text(d.venue || '-');
                    $('#modal-detail-instructor').text(d.instructor || '-');

                    // Section 4: Schedule & Duration
                    $('#modal-detail-start-date').text(d.start_date || '-');
                    $('#modal-detail-finish-date').text(d.finish_date || '-');
                    $('#modal-detail-duration-seconds').text(d.duration_seconds ? `${d.duration_seconds.toLocaleString()}s` : '-');
                    $('#modal-detail-course-hours').text(d.course_learning_hours ? `${parseFloat(d.course_learning_hours).toFixed(1)} Hours` : '-');

                    // Section 5: Participant & Job Info
                    $('#modal-detail-employee-id').text(d.employee_id || '-');
                    $('#modal-detail-participant-name').text(d.participant_name || d.user_name || '-');
                    $('#modal-detail-grade').text(d.grade || '-');
                    $('#modal-detail-job-position').text(d.job_position || '-');
                    $('#modal-detail-team').text(d.team || '-');
                    $('#modal-detail-department').text(d.department || '-');
                    $('#modal-detail-company').text(d.company || '-');

                    // Section 6: Progress & Evaluation
                    $('#modal-detail-progress').text(`${progress.toFixed(1)}%`);
                    $('#modal-detail-learning-hours').text(d.participant_learning_hours ? `${parseFloat(d.participant_learning_hours).toFixed(1)} Hours` : '-');
                    $('#modal-detail-assessment-score').text(d.assessment_score !== null && d.assessment_score !== undefined ? parseFloat(d.assessment_score).toFixed(1) : '-');

                    if (d.attendance === null || d.attendance === undefined) {
                        $('#modal-detail-attendance').text('-');
                    } else {
                        $('#modal-detail-attendance').html(d.attendance
                            ? '<span class="badge bg-success-lt text-success">Present</span>'
                            : '<span class="badge bg-danger-lt text-danger">Absent</span>'
                        );
                    }

                    if (d.attendance_reason) {
                        $('#modal-detail-attendance-reason').text(d.attendance_reason);
                        $('#modal-detail-reason-container').show();
                    } else {
                        $('#modal-detail-reason-container').hide();
                    }

                    // Section 7: Bond & Kirkpatrick L1-L3
                    $('#modal-detail-bond-status').text(d.bond_status || '-');
                    $('#modal-detail-bond-end-date').text(d.bond_end_date || '-');
                    $('#modal-detail-bond-amount').text(d.bond_amount ? `IDR ${parseFloat(d.bond_amount).toLocaleString('id-ID')}` : '-');
                    $('#modal-detail-l1-program').text(d.l1_program || '-');
                    $('#modal-detail-l2-numerical').text(d.l2_numerical !== null && d.l2_numerical !== undefined ? parseFloat(d.l2_numerical).toFixed(1) : '-');
                    $('#modal-detail-l2-verbal').text(d.l2_verbal || '-');
                    $('#modal-detail-l3-numerical').text(d.l3_numerical !== null && d.l3_numerical !== undefined ? parseFloat(d.l3_numerical).toFixed(1) : '-');
                    $('#modal-detail-l3-verbal').text(d.l3_verbal || '-');
                    $('#modal-detail-pic').text(d.pic || '-');

                    $('#offline-detail-content').show();
                } else {
                    $('#offline-detail-error').text(res.message || 'Failed to load details.').show();
                }
            },
            error: function () {
                $('#offline-detail-loading').hide();
                $('#offline-detail-error').text('An error occurred while loading record details.').show();
            }
        });
    });
});
</script>
@endsection
