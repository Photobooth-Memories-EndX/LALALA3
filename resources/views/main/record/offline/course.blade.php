@extends('layouts.sidebar-master')

@section('content')
<div class="page-body manage-record-page">
    <div class="container-xl">
        <!-- BACK BUTTON -->
        <div class="mb-3">
            <a href="{{ $backUrl }}" class="btn-back d-inline-flex align-items-center gap-1 text-decoration-none fw-medium">
                <i class="ti ti-arrow-left"></i>
                <span>{{ $backTitle }}</span>
            </a>
        </div>

        <!-- COURSE HEADER & METADATA CARD -->
        <div class="card border-0 shadow-sm p-3 p-md-4 mb-4 rounded-3 bg-white record-section-header">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
                <div class="d-flex align-items-start gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 record-section-icon"
                         style="width: 56px; height: 56px; background-color: #fef3c7; color: #d97706;">
                        <i class="ti ti-book fs-1"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <h2 class="h3 fw-bold mb-0 text-dark" id="course-name">{{ $training->course_name ?: '-' }}</h2>
                            @if (!empty($training->type))
                                <span class="badge bg-light text-secondary border px-2 py-1">{{ $training->type }}</span>
                            @endif
                            @if (!empty($training->main_classification))
                                <span class="badge bg-primary-lt fw-bold px-2 py-1">{{ $training->main_classification }}</span>
                            @endif
                            @if (!empty($training->batch))
                                <span class="badge bg-secondary-lt fw-semibold px-2 py-1">Batch: {{ $training->batch }}</span>
                            @endif
                        </div>
                        @if (!empty($training->sub_course_name))
                            <div class="text-muted fw-medium fs-4 mb-2" id="sub-course-name">{{ $training->sub_course_name }}</div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- TRAINING METADATA GRID -->
            <div class="p-3 rounded-3 bg-light border border-light-subtle">
                <div class="row g-3 small">
                    <!-- Column 1: Classification & Categorization -->
                    <div class="col-sm-6 col-md-3">
                        <div class="text-muted text-uppercase fw-semibold extra-small mb-1" style="font-size: 0.72rem;">Classification</div>
                        <div class="text-dark fw-medium">{{ $training->main_classification ?: '-' }}</div>
                        @if (!empty($training->sub_classification))
                            <div class="text-muted extra-small">Sub: {{ $training->sub_classification }}</div>
                        @endif
                        <div class="mt-2 text-muted text-uppercase fw-semibold extra-small mb-1" style="font-size: 0.72rem;">Category / Level</div>
                        <div class="text-dark fw-medium">{{ $training->category ?: '-' }} @if(!empty($training->level))<span class="text-muted">({{ $training->level }})</span>@endif</div>
                    </div>

                    <!-- Column 2: Delivery & Provider -->
                    <div class="col-sm-6 col-md-3">
                        <div class="text-muted text-uppercase fw-semibold extra-small mb-1" style="font-size: 0.72rem;">Delivery Method</div>
                        <div class="text-dark fw-medium">{{ $training->delivery_method ?: '-' }}</div>
                        @if (!empty($training->sub_delivery_method))
                            <div class="text-muted extra-small">Sub: {{ $training->sub_delivery_method }}</div>
                        @endif
                        <div class="mt-2 text-muted text-uppercase fw-semibold extra-small mb-1" style="font-size: 0.72rem;">Provider</div>
                        <div class="text-dark fw-medium">{{ $training->provider_name ?: ($training->provider ?: '-') }}</div>
                        @if (!empty($training->sub_provider))
                            <div class="text-muted extra-small">Sub: {{ $training->sub_provider }}</div>
                        @endif
                    </div>

                    <!-- Column 3: Schedule, Hours & Venue -->
                    <div class="col-sm-6 col-md-3">
                        <div class="text-muted text-uppercase fw-semibold extra-small mb-1" style="font-size: 0.72rem;">Dates & Duration</div>
                        <div class="text-dark fw-medium">
                            {{ $training->start_date ? \Carbon\Carbon::parse($training->start_date)->format('d M Y') : '-' }}
                            @if ($training->finish_date && $training->finish_date != $training->start_date)
                                — {{ \Carbon\Carbon::parse($training->finish_date)->format('d M Y') }}
                            @endif
                        </div>
                        <div class="text-muted extra-small">
                            Hours: {{ $training->learning_hours ? number_format($training->learning_hours, 2) . 'h' : '-' }}
                            @if (!empty($training->duration_seconds))
                                • {{ sprintf('%02d:%02d:%02d', floor($training->duration_seconds/3600), floor(($training->duration_seconds%3600)/60), $training->duration_seconds%60) }}
                            @endif
                        </div>
                        <div class="mt-2 text-muted text-uppercase fw-semibold extra-small mb-1" style="font-size: 0.72rem;">Venue & Instructor</div>
                        <div class="text-dark fw-medium">{{ $training->venue ?: ($training->location ?: '-') }}</div>
                        @if (!empty($training->instructor))
                            <div class="text-muted extra-small">Instructor: {{ $training->instructor }}</div>
                        @endif
                    </div>

                    <!-- Column 4: Corporate KPI -->
                    <div class="col-sm-6 col-md-3">
                        <div class="text-muted text-uppercase fw-semibold extra-small mb-1" style="font-size: 0.72rem;">Corporate KPI</div>
                        <div>
                            @if ($training->counted_corporate_kpi)
                                <span class="badge bg-success-lt text-success fw-bold px-2 py-1">Counted</span>
                            @else
                                <span class="badge bg-secondary-lt fw-normal px-2 py-1">Not Counted</span>
                            @endif
                        </div>
                        @if (!empty($training->corporate_kpi_year))
                            <div class="text-muted extra-small mt-1">Year: {{ $training->corporate_kpi_year }}</div>
                        @endif
                        @if (!empty($training->sub_corporate_kpi))
                            <div class="text-muted extra-small">Sub: {{ $training->sub_corporate_kpi }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- COURSE SUMMARY CARDS -->
        <div class="row g-3 mb-4">
            <!-- Total Participants -->
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm p-3 rounded-3 bg-white stat-kpi-card h-100">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                             style="width: 44px; height: 44px; background-color: #0f4c81; color: #ffffff;">
                            <i class="ti ti-users fs-2"></i>
                        </div>
                        <div>
                            <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Total Participants</div>
                            <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;" id="kpi-total-participants">{{ number_format($summary['total_participants']) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Finished -->
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm p-3 rounded-3 bg-white stat-kpi-card h-100">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                             style="width: 44px; height: 44px; background-color: #16a34a; color: #ffffff;">
                            <i class="ti ti-circle-check fs-2"></i>
                        </div>
                        <div>
                            <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Finished</div>
                            <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;" id="kpi-finished">{{ number_format($summary['finished']) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ongoing -->
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm p-3 rounded-3 bg-white stat-kpi-card h-100">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                             style="width: 44px; height: 44px; background-color: #f59e0b; color: #ffffff;">
                            <i class="ti ti-clock-hour-4 fs-2"></i>
                        </div>
                        <div>
                            <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Ongoing</div>
                            <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;" id="kpi-ongoing">{{ number_format($summary['ongoing']) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Average Progress -->
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm p-3 rounded-3 bg-white stat-kpi-card h-100">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                             style="width: 44px; height: 44px; background-color: #0284c7; color: #ffffff;">
                            <i class="ti ti-chart-pie fs-2"></i>
                        </div>
                        <div>
                            <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Avg. Progress</div>
                            <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;" id="kpi-avg-progress">{{ number_format($summary['avg_progress'], 1) }}%</div>
                        </div>
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
                <form id="course-filter-form" onsubmit="return false;">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <!-- Search Participant / NIP -->
                        <div class="position-relative flex-grow-1" style="min-width: 240px; max-width: 360px;">
                            <input type="text" id="course-participant-input" class="form-control rounded-3" 
                                   placeholder="Search participant name or NIP..." autocomplete="off">
                        </div>

                        <!-- Status Dropdown -->
                        <div style="min-width: 140px; flex-shrink: 0;">
                            <select class="form-select rounded-3" id="course-status-select">
                                <option value="all">All Status</option>
                                <option value="finished">Finished</option>
                                <option value="ongoing">Ongoing</option>
                                <option value="not_started">Not Started</option>
                            </select>
                        </div>

                        <!-- Filter Button -->
                        <button type="button" id="btn-apply-course-filter" class="btn btn-primary btn-icon px-3 flex-shrink-0" 
                                style="background-color: #003b6d; border-color: #003b6d;" title="Apply Filter">
                            <i class="ti ti-search fs-2"></i>
                        </button>

                        <!-- Reset Button -->
                        <button type="button" id="btn-reset-course-filter" class="btn btn-outline-secondary btn-icon px-3 flex-shrink-0" 
                                title="Reset Filter">
                            <i class="ti ti-refresh fs-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- PARTICIPANT TABLE CARD -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <div>
                    <h3 class="card-title fw-bold mb-0 text-dark">Participants</h3>
                    <div class="text-muted small">Participants enrolled specifically in this training session.</div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive offline-table-responsive">
                    <table id="course-participants-table" class="table table-vcenter table-striped card-table">
                        <thead>
                            <tr>
                                <th class="text-center" style="min-width: 45px; width: 45px;">NO</th>
                                <th class="text-center" style="min-width: 105px;">STATUS</th>
                                <th class="text-center" style="min-width: 90px;">NIP</th>
                                <th style="min-width: 200px;">PARTICIPANT</th>
                                <th class="text-center" style="min-width: 75px;">GRADE</th>
                                <th style="min-width: 160px;">JOB POSITION</th>
                                <th style="min-width: 140px;">TEAM</th>
                                <th style="min-width: 160px;">DEPARTMENT</th>
                                <th style="min-width: 160px;">COMPANY</th>
                                <th class="text-center" style="min-width: 120px;">PROGRESS</th>
                                <th class="text-center" style="min-width: 115px;">LEARNING HOURS</th>
                                <th class="text-center" style="min-width: 105px;">ATTENDANCE</th>
                                <th class="text-center" style="min-width: 95px;">SCORE</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="card-footer d-flex flex-wrap align-items-center justify-content-between p-3 border-top">
                    <div class="d-flex align-items-center gap-2 mb-2 mb-md-0">
                        <span class="text-muted small">Show</span>
                        <select id="course-table-entries-select" class="form-select form-select-sm w-auto rounded-2">
                            <option value="10">10</option>
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span class="text-muted small">entries</span>
                    </div>
                    <div id="course-table-info-container" class="mb-2 mb-md-0"></div>
                    <div id="course-table-pagination-container"></div>
                </div>
            </div>
        </div>
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

    [data-bs-theme="dark"] .record-section-header .bg-light,
    body.dark-mode .record-section-header .bg-light,
    body.theme-dark .record-section-header .bg-light {
        background-color: #111827 !important;
        border-color: #374151 !important;
    }

    .stat-kpi-card {
        border: 1px solid #e2e8f0 !important;
        border-radius: 12px;
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

    .offline-table-responsive {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
        width: 100%;
        max-width: 100%;
        border-radius: 0;
    }

    .offline-table-responsive::-webkit-scrollbar {
        height: 8px;
    }
    .offline-table-responsive::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }
    .offline-table-responsive::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    .offline-table-responsive::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    #course-participants-table {
        font-size: 13px;
        width: max-content !important;
        min-width: 100% !important;
        margin-bottom: 0;
    }

    #course-participants-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap !important;
        vertical-align: middle;
        padding: 11px 14px;
        border-bottom: 1px solid #e2e8f0;
    }

    #course-participants-table tbody td {
        vertical-align: middle;
        padding: 10px 14px;
        white-space: nowrap !important;
    }

    [data-bs-theme="dark"] #course-participants-table thead th,
    body.dark-mode #course-participants-table thead th,
    body.theme-dark #course-participants-table thead th {
        background: #1e293b !important;
        color: #94a3b8 !important;
        border-bottom-color: #334155 !important;
    }

    [data-bs-theme="dark"] #course-participants-table tbody td,
    body.dark-mode #course-participants-table tbody td,
    body.theme-dark #course-participants-table tbody td {
        border-bottom-color: #334155 !important;
        color: #e2e8f0 !important;
    }

    .btn-icon {
        width: 30px;
        height: 30px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
    }

    .hover-underline:hover,
    .participant-link:hover {
        text-decoration: underline !important;
    }
</style>
@endsection

@section('script')
<script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>
<script>
    $(function () {
        const datatableUrl = @json(route('manage.record.offline.course.datatable', $training->id));
        const classificationParam = @json($classificationParam);
        const defaultEntries = 25;
        let courseTable = null;

        courseTable = $('#course-participants-table').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            autoWidth: false,
            pageLength: defaultEntries,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [[3, 'asc']],
            dom: 't',
            ajax: {
                url: datatableUrl,
                type: 'GET',
                data: function (d) {
                    d.participant = ($('#course-participant-input').val() || '').trim();
                    d.status = $('#course-status-select').val();
                    d.classification = classificationParam;
                    d.entries = $('#course-table-entries-select').val() || defaultEntries;
                    d.length = parseInt($('#course-table-entries-select').val(), 10) || defaultEntries;
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                { data: 'status_badge', name: 'otu.progress', className: 'text-center' },
                { data: 'employee_id', name: 'otu.employee_id', className: 'text-center' },
                { data: 'participant_name', name: 'otu.name', className: 'text-start' },
                { data: 'grade', name: 'otu.grade', className: 'text-center' },
                { data: 'job_position', name: 'otu.job_position', className: 'text-start' },
                { data: 'team', name: 'otu.team', className: 'text-start' },
                { data: 'department', name: 'otu.department', className: 'text-start' },
                { data: 'company', name: 'otu.company', className: 'text-start' },
                { data: 'progress_badge', name: 'otu.progress', className: 'text-center' },
                { data: 'learning_hours', name: 'otu.learning_hours', className: 'text-center' },
                { data: 'attendance_badge', name: 'otu.attendance', className: 'text-center' },
                { data: 'assessment_score', name: 'otu.assessment_score', className: 'text-center' }
            ],
            language: {
                processing: '<div class="py-4 text-center"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 text-muted">Loading participant data...</div></div>',
                emptyTable: '<div class="py-5 text-center text-muted"><i class="ti ti-inbox fs-1 d-block mb-2 text-secondary"></i>No participants enrolled in this training session.</div>'
            },
            drawCallback: function (settings) {
                const api = this.api();
                const pageInfo = api.page.info();
                const formatNum = (num) => (num || 0).toLocaleString();

                if (pageInfo.recordsTotal > 0 && pageInfo.recordsDisplay > 0) {
                    const start = pageInfo.start + 1;
                    const end = pageInfo.end;
                    const total = pageInfo.recordsDisplay;
                    $('#course-table-info-container').html(
                        `<span class="text-muted small">Showing ${formatNum(start)} to ${formatNum(end)} of ${formatNum(total)} entries</span>`
                    );
                } else {
                    $('#course-table-info-container').html('<span class="text-muted small">Showing 0 to 0 of 0 entries</span>');
                }

                // Render pagination
                const $paginationContainer = $('#course-table-pagination-container');
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

                    const nextDisabled = pageInfo.page === (pageInfo.pages - 1) ? 'disabled' : '';
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

        // Pagination click handler
        $(document).on('click', '#course-table-pagination-container .page-link', function (e) {
            e.preventDefault();
            const page = $(this).data('page');
            if (page !== undefined && page >= 0) {
                courseTable.page(page).draw('page');
            }
        });

        // Entries select handler
        $('#course-table-entries-select').on('change', function () {
            const length = parseInt($(this).val(), 10) || defaultEntries;
            courseTable.page.len(length).draw();
        });

        // Apply filters
        $('#btn-apply-course-filter').on('click', function () {
            courseTable.ajax.reload();
        });

        $('#course-participant-input').on('keyup', function (e) {
            if (e.key === 'Enter') {
                courseTable.ajax.reload();
            }
        });

        $('#course-status-select').on('change', function () {
            courseTable.ajax.reload();
        });

        // Reset filter
        $('#btn-reset-course-filter').on('click', function () {
            $('#course-participant-input').val('');
            $('#course-status-select').val('all');
            courseTable.ajax.reload();
        });
    });
</script>
@endsection
