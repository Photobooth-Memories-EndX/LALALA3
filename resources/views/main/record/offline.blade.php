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

                <!-- Action Buttons (Placeholders for Phase 2E & 2F) -->
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('manage.record.offline.import') }}" class="btn btn-outline-primary rounded-3 px-3 d-inline-flex align-items-center gap-1.5">
                        <i class="ti ti-file-spreadsheet fs-2"></i>
                        <span>Import Excel</span>
                    </a>
                    <button type="button" class="btn btn-primary rounded-3 px-3 d-inline-flex align-items-center gap-1.5"
                            style="background-color: #d97706; border-color: #d97706;"
                            data-bs-toggle="tooltip" data-bs-placement="top" title="Phase 2E: Add Training form will be enabled in upcoming phase">
                        <i class="ti ti-plus fs-2"></i>
                        <span>Add Training</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- 4 KPI SUMMARY CARDS (ASYNC SKELETON SHELL) -->
        <div id="offline-kpi-summary-container">
            @include('main.record.components.offline-kpi-skeleton')
        </div>

        <!-- QUICK FILTER CARD -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-3">
                <div class="text-secondary small fw-semibold mb-2" style="color: #6c757d !important; font-size: 0.85rem;">
                    Quick Filter
                </div>
                <form id="offline-filter-form" onsubmit="return false;">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <!-- Employee Search -->
                        <div class="position-relative flex-grow-1" style="min-width: 180px; max-width: 240px;">
                            <input type="text" id="offline-employee-input" class="form-control rounded-3" placeholder="Search NIP or name..." autocomplete="off">
                        </div>

                        <!-- Course Name Search -->
                        <div class="position-relative flex-grow-1" style="min-width: 200px; max-width: 260px;">
                            <input type="text" id="offline-course-input" class="form-control rounded-3" placeholder="Search course name..." autocomplete="off">
                        </div>

                        <!-- Type Dropdown -->
                        <div style="min-width: 130px; flex-shrink: 0;">
                            <select class="form-select rounded-3" id="offline-type-select">
                                <option value="all">All Types</option>
                                @foreach ($trainingTypes as $type)
                                    <option value="{{ $type }}">{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Main Classification Dropdown -->
                        <div style="min-width: 150px; flex-shrink: 0;">
                            <select class="form-select rounded-3" id="offline-classification-select">
                                <option value="all">All Classifications</option>
                                @foreach ($mainClassifications as $class)
                                    <option value="{{ $class }}">{{ $class }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Status Dropdown -->
                        <div style="min-width: 120px; flex-shrink: 0;">
                            <select class="form-select rounded-3" id="offline-status-select">
                                <option value="all">All Status</option>
                                <option value="finished">Finished</option>
                                <option value="ongoing">Ongoing</option>
                            </select>
                        </div>

                        <!-- Year Dropdown -->
                        <div style="min-width: 110px; flex-shrink: 0;">
                            <select class="form-select rounded-3" id="offline-year-select">
                                <option value="all">All Year</option>
                                @foreach ($years as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Action Buttons: Apply & Reset -->
                        <button type="button" id="btn-apply-offline-filter" class="btn btn-primary btn-icon px-3 flex-shrink-0" 
                                style="background-color: #003b6d; border-color: #003b6d;" title="Apply Filter">
                            <i class="ti ti-search fs-2"></i>
                        </button>

                        <button type="button" id="btn-reset-offline-filter" class="btn btn-outline-secondary btn-icon px-3 flex-shrink-0" title="Reset Filters">
                            <i class="ti ti-refresh fs-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- SERVER-SIDE DATATABLE CARD -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="offline-learning-table" class="table table-vcenter table-striped card-table w-100">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 40px;">NO</th>
                                <th class="text-center">STATUS</th>
                                <th class="text-center">NIP</th>
                                <th>PARTICIPANT</th>
                                <th>COURSE NAME</th>
                                <th class="text-center">TYPE</th>
                                <th>MAIN CLASSIFICATION</th>
                                <th class="text-center">BATCH</th>
                                <th class="text-center">HOURS</th>
                                <th class="text-center" style="min-width: 90px;">PROGRESS</th>
                                <th class="text-center">FINISH DATE</th>
                                <th class="text-center">ATTENDANCE</th>
                                <th class="text-center">SCORE</th>
                                <th class="text-center">CERTIFICATE</th>
                                <th class="text-center" style="width: 70px;">ACTION</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="card-footer d-flex flex-wrap align-items-center justify-content-between p-3 border-top">
                    <div class="d-flex align-items-center gap-2">
                        <select class="form-select form-select-sm w-auto rounded-3" id="offline-table-entries-select">
                            <option value="10">10 Entries</option>
                            <option value="25" selected>25 Entries</option>
                            <option value="50">50 Entries</option>
                            <option value="100">100 Entries</option>
                        </select>
                        <div id="offline-table-info-container"></div>
                    </div>
                    <div id="offline-table-pagination-container"></div>
                </div>
            </div>
        </div>
    </div>
</div>

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

    #offline-learning-table {
        font-size: 13px;
    }

    #offline-learning-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap;
    }

    #offline-learning-table tbody td {
        vertical-align: middle;
        padding-top: 12px;
        padding-bottom: 12px;
    }

    [data-bs-theme="dark"] #offlineTrainingDetailModal .modal-content,
    body.dark-mode #offlineTrainingDetailModal .modal-content,
    body.theme-dark #offlineTrainingDetailModal .modal-content {
        background-color: var(--tblr-bg-surface, #1f2937) !important;
        color: #f8fafc !important;
    }
    [data-bs-theme="dark"] #offlineTrainingDetailModal .bg-light,
    body.dark-mode #offlineTrainingDetailModal .bg-light,
    body.theme-dark #offlineTrainingDetailModal .bg-light {
        background-color: #111827 !important;
    }
    [data-bs-theme="dark"] #offlineTrainingDetailModal .text-dark,
    body.dark-mode #offlineTrainingDetailModal .text-dark,
    body.theme-dark #offlineTrainingDetailModal .text-dark {
        color: #f8fafc !important;
    }
</style>
@endsection

@section('script')
<script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>

<script>
    $(function () {
        const summaryUrl = @json(route('manage.record.offline.ajax.summary'));
        const datatableUrl = @json(route('manage.record.offline.datatable'));
        const defaultEntries = 25;

        let kpiXhr = null;
        let offlineTable = null;

        // 1. Asynchronous Offline KPI Summary Loader
        window.loadOfflineKpiSummary = function () {
            if (kpiXhr) {
                kpiXhr.abort();
                kpiXhr = null;
            }

            const skeletonHtml = @json(view('main.record.components.offline-kpi-skeleton')->render());
            $('#offline-kpi-summary-container').html(skeletonHtml);

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
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadOfflineKpiSummary()">
                                    <i class="ti ti-refresh me-1"></i> Retry
                                </button>
                            </div>
                        </div>
                    `);
                }
            });
        };

        // 2. Initialize Offline DataTable
        if ($.fn.DataTable.isDataTable('#offline-learning-table')) {
            $('#offline-learning-table').DataTable().destroy();
        }

        offlineTable = $('#offline-learning-table').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            pageLength: defaultEntries,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [[10, 'desc']],
            dom: '<"table-responsive"t>',
            ajax: {
                url: datatableUrl,
                type: 'GET',
                data: function (d) {
                    d.employee_id = $('#offline-employee-input').val().trim();
                    d.course_name = $('#offline-course-input').val().trim();
                    d.type = $('#offline-type-select').val();
                    d.main_classification = $('#offline-classification-select').val();
                    d.status = $('#offline-status-select').val();
                    d.year = $('#offline-year-select').val();
                    d.entries = $('#offline-table-entries-select').val() || defaultEntries;
                    d.length = parseInt($('#offline-table-entries-select').val(), 10) || defaultEntries;
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                { data: 'status_badge', name: 'otu.progress', className: 'text-center' },
                { data: 'employee_id', name: 'otu.employee_id', className: 'text-center' },
                { data: 'employee', name: 'otu.name' },
                { data: 'course_name', name: 'ot.course_name' },
                { data: 'type_badge', name: 'ot.type', className: 'text-center' },
                { data: 'main_classification', name: 'ot.main_classification' },
                { data: 'batch', name: 'ot.batch', className: 'text-center' },
                { data: 'learning_hours', name: 'otu.learning_hours', className: 'text-center' },
                { data: 'progress_badge', name: 'otu.progress', className: 'text-center' },
                { data: 'finish_date', name: 'ot.finish_date', className: 'text-center' },
                { data: 'attendance_badge', name: 'otu.attendance', className: 'text-center' },
                { data: 'assessment_score', name: 'otu.assessment_score', className: 'text-center' },
                { data: 'certificate_badge', name: 'otu.certificate', className: 'text-center' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
            ],
            language: {
                processing: '<div class="py-4 text-center"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 text-muted">Loading offline training data...</div></div>',
                emptyTable: '<div class="py-5 text-center text-muted"><i class="ti ti-inbox fs-1 d-block mb-2 text-secondary"></i>No offline training records found. Once migration and imports are run, records will appear here.</div>'
            },
            drawCallback: function (settings) {
                const api = this.api();
                const pageInfo = api.page.info();
                const formatNum = (num) => (num || 0).toLocaleString();

                if (pageInfo.recordsTotal > 0 && pageInfo.recordsDisplay > 0) {
                    const start = pageInfo.start + 1;
                    const end = pageInfo.end;
                    const total = pageInfo.recordsDisplay;
                    $('#offline-table-info-container').html(
                        `<span class="text-muted small">Showing ${formatNum(start)} to ${formatNum(end)} of ${formatNum(total)} entries</span>`
                    );
                } else {
                    $('#offline-table-info-container').html('<span class="text-muted small">Showing 0 to 0 of 0 entries</span>');
                }

                // Render pagination
                const $paginationContainer = $('#offline-table-pagination-container');
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

        // Pagination Click Handler
        $(document).on('click', '#offline-table-pagination-container .page-link', function (e) {
            e.preventDefault();
            const page = $(this).data('page');
            if (page !== undefined && page !== null && page >= 0) {
                offlineTable.page(page).draw('page');
            }
        });

        // Filter Handlers
        $('#btn-apply-offline-filter').on('click', function () {
            offlineTable.ajax.reload(null, true);
        });

        $('#offline-employee-input, #offline-course-input').on('keypress', function (e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#btn-apply-offline-filter').trigger('click');
            }
        });

        $('#offline-type-select, #offline-classification-select, #offline-status-select, #offline-year-select').on('change', function () {
            $('#btn-apply-offline-filter').trigger('click');
        });

        $('#offline-table-entries-select').on('change', function () {
            const nextLength = parseInt($(this).val(), 10) || defaultEntries;
            offlineTable.page.len(nextLength).page(0).draw(false);
        });

        $('#btn-reset-offline-filter').on('click', function () {
            $('#offline-employee-input').val('');
            $('#offline-course-input').val('');
            $('#offline-type-select').val('all');
            $('#offline-classification-select').val('all');
            $('#offline-status-select').val('all');
            $('#offline-year-select').val('all');
            $('#offline-table-entries-select').val(defaultEntries);

            offlineTable.page.len(defaultEntries).page(0).draw(false);
        });

        // 3. Detail Modal Handler (7 Sections)
        let detailModalEl = document.getElementById('offlineTrainingDetailModal');
        let detailModal = null;
        if (detailModalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            detailModal = new bootstrap.Modal(detailModalEl);
        }

        $(document).on('click', '.btn-view-offline-detail', function () {
            const id = $(this).data('id');
            if (!id) return;

            $('#offline-detail-loading').show();
            $('#offline-detail-content').hide();

            if (detailModal) {
                detailModal.show();
            } else {
                $('#offlineTrainingDetailModal').modal('show');
            }

            $.ajax({
                url: `/manage/record/offline/${id}/detail`,
                type: 'GET',
                dataType: 'json',
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

                        $('#modal-detail-certificate').text(d.certificate || '-');
                        $('#modal-detail-l1-program').text(d.l1_program || '-');
                        $('#modal-detail-l2-num').text(d.l2_numerical !== null && d.l2_numerical !== undefined ? parseFloat(d.l2_numerical).toFixed(2) : '-');
                        $('#modal-detail-l2-verbal').text(d.l2_verbal || '-');
                        $('#modal-detail-l3-eval').text(`${d.l3_numerical ?? '-'} / ${d.l3_verbal ?? '-'}`);

                        // Section 7: Service Bond & Administration
                        $('#modal-detail-bond-status').text(d.bond_status || '-');
                        $('#modal-detail-bond-end-date').text(d.bond_end_date || '-');
                        $('#modal-detail-bond-amount').text(d.bond_amount ? `Rp ${parseFloat(d.bond_amount).toLocaleString()}` : '-');
                        $('#modal-detail-pic').text(d.pic || '-');

                        $('#offline-detail-content').show();
                    }
                },
                error: function () {
                    $('#offline-detail-loading').html('<div class="text-danger small py-3">Failed to load training details.</div>');
                }
            });
        });

        // Initialize tooltips
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }

        // Parallel load summary on DOM ready
        window.loadOfflineKpiSummary();
    });
</script>
@endsection
