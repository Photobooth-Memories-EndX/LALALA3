@extends('layouts.sidebar-master')

@section('content')
<div class="page-body manage-record-page">
    <div class="container-xl">
        <!-- BACK BUTTON -->
        <div class="mb-3">
            <a href="{{ $backUrl ?? route('manage.record.online') }}" class="btn-back d-inline-flex align-items-center gap-1 text-decoration-none fw-medium">
                <i class="ti ti-arrow-left"></i>
                <span>{{ $backTitle ?? 'Back to KP OWL' }}</span>
            </a>
        </div>

        <!-- COURSE HEADER CARD -->
        <div class="card border-0 shadow-sm p-3 p-md-4 mb-4 rounded-3 bg-white record-section-header">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-start gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 record-section-icon"
                         style="width: 56px; height: 56px; background-color: #e0f2fe; color: #0284c7;">
                        <i class="ti ti-device-laptop fs-1"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <h2 class="h3 fw-bold mb-0 text-dark">{{ $title ?? 'KP OWL Course Record' }}</h2>
                            <span class="badge bg-primary-lt fw-bold px-2 py-1">Online Learning</span>
                        </div>
                        <div class="text-muted fw-medium fs-4 mb-0">{{ $subtitle ?? 'Enrolled participants and progress tracking.' }}</div>
                    </div>
                </div>

                <!-- Action Download -->
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="{{ route('manage.record.online.export') }}" class="btn btn-success rounded-3 px-3 d-inline-flex align-items-center gap-1.5" id="btn-export-online-course">
                        <i class="ti ti-download fs-2"></i>
                        <span>Download Excel</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- QUICK FILTER CARD -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-3">
                <div class="text-secondary small fw-semibold mb-2" style="color: #6c757d !important; font-size: 0.85rem;">
                    Quick Filter
                </div>
                <form id="online-course-filter-form" class="w-100" onsubmit="return false;">
                    <div class="d-flex align-items-center gap-2 flex-wrap w-100">
                        <!-- Search Participant / NIP -->
                        <div class="position-relative flex-grow-1" style="min-width: 200px;">
                            <input type="text" id="online-participant-input" class="form-control rounded-3 w-100"
                                   placeholder="Search participant or ID..." autocomplete="off">
                        </div>

                        <!-- Status Dropdown -->
                        <div class="flex-shrink-0" style="min-width: 130px;">
                            <select class="form-select rounded-3" id="online-status-select">
                                <option value="all">All Status</option>
                                <option value="finished">Finished</option>
                                <option value="ongoing">Ongoing</option>
                                <option value="not_started">Not Started</option>
                            </select>
                        </div>

                        <!-- Search Button -->
                        <button type="button" id="btn-apply-online-filter" class="btn btn-primary btn-icon px-3 flex-shrink-0"
                                style="background-color: #003b6d; border-color: #003b6d;" data-bs-toggle="tooltip" data-bs-title="Apply Filter" title="Apply Filter">
                            <i class="ti ti-search fs-2"></i>
                        </button>

                        <!-- Reset Button -->
                        <button type="button" id="btn-reset-online-filter" class="btn btn-outline-secondary btn-icon px-3 flex-shrink-0"
                                data-bs-toggle="tooltip" data-bs-title="Reset Filters" title="Reset Filters">
                            <i class="ti ti-refresh fs-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- KP OWL 31-COLUMN TABLE CARD -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h3 class="card-title fw-bold mb-0 text-dark">KP OWL Online Records</h3>
                    <div class="text-muted small">Standardized 29-column learning record view for KP OWL.</div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive online-table-responsive">
                    <table id="online-course-table" class="table table-vcenter table-striped card-table">
                        <thead>
                            <tr>
                                <th class="text-center" style="min-width: 45px; width: 45px;">NO</th>
                                <th class="text-center" style="min-width: 90px;">EMPLOYEE ID</th>
                                <th style="min-width: 180px;">EMPLOYEE NAME</th>
                                <th style="min-width: 140px;">TEAM</th>
                                <th style="min-width: 160px;">DEPARTMENT</th>
                                <th style="min-width: 160px;">COMPANY</th>
                                <th style="min-width: 220px;">COURSE TITLE</th>
                                <th class="text-center" style="min-width: 100px;">TYPE</th>
                                <th style="min-width: 150px;">CATEGORY</th>
                                <th style="min-width: 160px;">MAIN CLASSIFICATION</th>
                                <th style="min-width: 150px;">SUB CLASSIFICATION</th>
                                <th class="text-center" style="min-width: 120px;">DELIVERY METHOD</th>
                                <th style="min-width: 140px;">SUB DELIVERY METHOD</th>
                                <th style="min-width: 150px;">PROVIDER NAME</th>
                                <th style="min-width: 140px;">VENUE</th>
                                <th class="text-center" style="min-width: 110px;">COURSE DURATION</th>
                                <th class="text-center" style="min-width: 110px;">LEARNING HOURS</th>
                                <th class="text-center" style="min-width: 120px;">% PROGRESS</th>
                                <th class="text-center" style="min-width: 110px;">PROGRESS STATUS</th>
                                <th style="min-width: 170px;">DATE TIME</th>
                                <th class="text-center" style="min-width: 110px;">RECORD MONTH</th>
                                <th class="text-center" style="min-width: 110px;">REQUIREMENT</th>
                                <th class="text-center" style="min-width: 95px;">ATTENDANCE</th>
                                <th class="text-center" style="min-width: 130px;">SCORE (L1) - COURSE</th>
                                <th class="text-center" style="min-width: 105px;">SCORE (L2)</th>
                                <th class="text-center" style="min-width: 105px;">SCORE (L3)</th>
                                <th style="min-width: 140px;">PERSON IN CHARGE (PIC)</th>
                                <th class="text-center" style="min-width: 140px;">TOTAL ENGAGEMENT TIME</th>
                                <th class="text-center" style="min-width: 80px; width: 80px;">ACTION</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="card-footer d-flex flex-wrap align-items-center justify-content-between p-3 border-top">
                    <div class="d-flex align-items-center gap-2 mb-2 mb-md-0">
                        <select class="form-select form-select-sm w-auto rounded-3" id="online-table-entries-select">
                            <option value="10">10 Entries</option>
                            <option value="25" selected>25 Entries</option>
                            <option value="50">50 Entries</option>
                            <option value="100">100 Entries</option>
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

    .record-section-header {
        border-radius: 14px !important;
        border: 1px solid #e2e8f0 !important;
    }

    .online-table-responsive {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
        width: 100%;
        max-width: 100%;
    }

    #online-course-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap !important;
        vertical-align: middle;
        padding: 11px 12px;
        border-bottom: 1px solid #e2e8f0;
    }

    #online-course-table tbody td {
        vertical-align: middle;
        padding: 9px 12px;
        white-space: nowrap !important;
    }
</style>
@endsection

@section('script')
<script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>
<script>
    $(function () {
        const datatableUrl = @json(route('manage.record.online.datatable'));
        const defaultEntries = 25;
        let onlineTable = null;

        onlineTable = $('#online-course-table').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            autoWidth: false,
            scrollX: true,
            pageLength: defaultEntries,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [[19, 'desc']],
            dom: 't',
            ajax: {
                url: datatableUrl,
                type: 'GET',
                data: function (d) {
                    d.search = $('#online-participant-input').val() || '';
                    d.status = $('#online-status-select').val() || 'all';
                    d.entries = $('#online-table-entries-select').val() || defaultEntries;
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                { data: 'employee_id', name: 'u.id_emp', className: 'text-center' },
                { data: 'employee_name', name: 'u.name', className: 'text-start' },
                { data: 'team', name: 'u.team', className: 'text-start' },
                { data: 'department', name: 'u.department', className: 'text-start' },
                { data: 'company', name: 'u.company_name', className: 'text-start' },
                { data: 'course_title', name: 'c.title', className: 'text-start' },
                { data: 'type', name: 'c.type', className: 'text-center' },
                { data: 'category', name: 'unified.category_source', className: 'text-start' },
                { data: 'main_classification', name: 'cl.classification_name', className: 'text-start' },
                { data: 'sub_classification', orderable: false, searchable: false, className: 'text-center' },
                { data: 'delivery_method', orderable: false, searchable: false, className: 'text-center' },
                { data: 'sub_delivery_method', orderable: false, searchable: false, className: 'text-start' },
                { data: 'provider_name', orderable: false, searchable: false, className: 'text-start' },
                { data: 'venue', orderable: false, searchable: false, className: 'text-start' },
                { data: 'course_duration', name: 'c.duration', className: 'text-center' },
                { data: 'learning_hours', name: 'unified.learning_hours', className: 'text-center' },
                { data: 'progress_bar', name: 'unified.progress', className: 'text-center' },
                { data: 'progress_status', name: 'unified.finish', className: 'text-center' },
                { data: 'date_time_merged', name: 'unified.created_at', className: 'text-start' },
                { data: 'record_month', name: 'unified.created_at', className: 'text-center' },
                { data: 'requirement', name: 'unified.status', className: 'text-center' },
                { data: 'attendance', orderable: false, searchable: false, className: 'text-center' },
                { data: 'score_l1', orderable: false, searchable: false, className: 'text-center' },
                { data: 'score_l2', orderable: false, searchable: false, className: 'text-center' },
                { data: 'score_l3', orderable: false, searchable: false, className: 'text-center' },
                { data: 'pic', orderable: false, searchable: false, className: 'text-start' },
                { data: 'total_engagement_time', name: 'unified.learning_hours', className: 'text-center' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
            ],
            language: {
                processing: '<div class="py-4 text-center"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 text-muted">Loading online learning data...</div></div>',
                emptyTable: '<div class="py-5 text-center text-muted"><i class="ti ti-inbox fs-1 d-block mb-2 text-secondary"></i>No online learning records found.</div>'
            },
            drawCallback: function (settings) {
                const api = this.api();
                const pageInfo = api.page.info();
                const formatNum = (num) => (num || 0).toLocaleString();

                if (pageInfo.recordsTotal > 0 && pageInfo.recordsDisplay > 0) {
                    $('#online-table-info-container').html(
                        `<span class="text-muted small">Showing ${formatNum(pageInfo.start + 1)} to ${formatNum(pageInfo.end)} of ${formatNum(pageInfo.recordsDisplay)} entries</span>`
                    );
                } else {
                    $('#online-table-info-container').html('<span class="text-muted small">Showing 0 to 0 of 0 entries</span>');
                }

                const $paginationContainer = $('#online-table-pagination-container');
                $paginationContainer.empty();

                if (pageInfo.pages > 1) {
                    let paginationHtml = '<ul class="pagination pagination-sm mb-0">';
                    const prevDisabled = pageInfo.page === 0 ? 'disabled' : '';
                    paginationHtml += `
                        <li class="page-item ${prevDisabled}">
                            <a class="page-link" href="javascript:void(0);" data-page="${pageInfo.page - 1}">
                                <i class="ti ti-chevron-left"></i>
                            </a>
                        </li>
                    `;

                    for (let p = 0; p < pageInfo.pages; p++) {
                        const active = p === pageInfo.page ? 'active' : '';
                        paginationHtml += `
                            <li class="page-item ${active}">
                                <a class="page-link" href="javascript:void(0);" data-page="${p}">${p + 1}</a>
                            </li>
                        `;
                    }

                    const nextDisabled = pageInfo.page >= pageInfo.pages - 1 ? 'disabled' : '';
                    paginationHtml += `
                        <li class="page-item ${nextDisabled}">
                            <a class="page-link" href="javascript:void(0);" data-page="${pageInfo.page + 1}">
                                <i class="ti ti-chevron-right"></i>
                            </a>
                        </li>
                    `;
                    paginationHtml += '</ul>';
                    $paginationContainer.html(paginationHtml);
                }
            }
        });

        $(document).on('click', '#online-table-pagination-container .page-link', function (e) {
            e.preventDefault();
            const page = $(this).data('page');
            if (page !== undefined && page >= 0) {
                onlineTable.page(page).draw('page');
            }
        });

        $('#online-table-entries-select').on('change', function () {
            onlineTable.page.len(parseInt($(this).val(), 10)).draw();
        });

        $('#btn-apply-online-filter').on('click', function () {
            onlineTable.ajax.reload();
        });

        $('#online-participant-input').on('keyup', function (e) {
            if (e.key === 'Enter') {
                onlineTable.ajax.reload();
            }
        });

        $('#online-status-select').on('change', function () {
            onlineTable.ajax.reload();
        });

        $('#btn-reset-online-filter').on('click', function () {
            $('#online-participant-input').val('');
            $('#online-status-select').val('all');
            onlineTable.ajax.reload();
        });
    });
</script>
@endsection
