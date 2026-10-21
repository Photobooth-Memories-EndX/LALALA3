{{-- Shared Training Record Detail Modal (Used by KP OWL Online, Non KP OWL Offline, and All Record) --}}
@props(['record' => []])
@php
    $record = $record ?? [];
    $authUser = auth()->user();
    $authEmployeeId = $authUser?->employee_id ?? $authUser?->nip ?? $authUser?->id_emp ?? '-';
    $authName = $authUser?->name ?? '-';
    $authGrade = $authUser?->grade ?? '-';
    $authJobPosition = $authUser?->job_position ?? $authUser?->position ?? '-';
    $authTeam = $authUser?->team ?? '-';
    $authDepartment = $authUser?->department ?? '-';
    $authCompany = $authUser?->company ?? $authUser?->company_name ?? 'PT Krakatau Posco';
@endphp
<div class="modal fade" id="recordDetailModal" tabindex="-1" aria-labelledby="recordDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3 px-4 bg-light">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-file-text text-primary fs-2"></i>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="recordDetailModalLabel">Training Record Detail</h5>
                        <div class="text-muted" style="font-size: 0.75rem;" id="modal-detail-source-badge">Learning Record Detail</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body py-3 px-4" id="recordDetailModalBody">
                <!-- Loading State (for async fetches) -->
                <div class="py-5 text-center text-muted" id="record-detail-loading" style="display: none;">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 small">Loading training details...</div>
                </div>

                <!-- Content Container -->
                <div id="record-detail-content">
                    <!-- Title & Status Header -->
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-3 border-bottom">
                        <div>
                            <h4 class="fw-bold mb-1 text-dark" style="font-size: 1.15rem;" id="modal-detail-course-name">-</h4>
                            <div class="text-muted" style="font-size: 0.85rem;" id="modal-detail-sub-course-name"></div>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge bg-light text-secondary border px-2.5 py-1.5" style="font-size: 0.8rem;" id="modal-detail-type">-</span>
                            <span class="badge bg-success-lt px-2.5 py-1.5 fw-bold" style="font-size: 0.8rem;" id="modal-detail-status">-</span>
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- Section 1: Course Information -->
                        <div class="col-md-6">
                            <div class="card border-0 bg-light p-3 rounded-3 h-100 shadow-none">
                                <div class="fw-bold text-dark mb-2 text-uppercase d-flex align-items-center gap-1" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    <i class="ti ti-info-circle text-primary"></i> 1. Course Information
                                </div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <span class="text-muted d-block text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Batch</span>
                                        <span class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-batch">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Category</span>
                                        <span class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-category">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Level</span>
                                        <span class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-level">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Type</span>
                                        <span class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-sec-type">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 2: Classification & Corporate KPI -->
                        <div class="col-md-6">
                            <div class="card border-0 bg-light p-3 rounded-3 h-100 shadow-none">
                                <div class="fw-bold text-dark mb-2 text-uppercase d-flex align-items-center gap-1" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    <i class="ti ti-chart-dots text-primary"></i> 2. Classification & Corporate KPI
                                </div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <span class="text-muted d-block text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Main Classification</span>
                                        <span class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-main-class">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Sub Classification</span>
                                        <span class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-sub-class">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Counted Corporate KPI</span>
                                        <span class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-corporate-kpi">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Sub Corporate KPI</span>
                                        <span class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-sub-corporate-kpi">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Delivery Method & Provider -->
                        <div class="col-md-6">
                            <div class="card border-0 bg-light p-3 rounded-3 h-100 shadow-none">
                                <div class="fw-bold text-dark mb-2 text-uppercase d-flex align-items-center gap-1" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    <i class="ti ti-building text-primary"></i> 3. Delivery Method & Provider
                                </div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <span class="text-muted d-block text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Delivery Method</span>
                                        <span class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-delivery-method">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Sub Delivery Method</span>
                                        <span class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-sub-delivery-method">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Provider</span>
                                        <span class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-provider">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Sub Provider</span>
                                        <span class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-sub-provider">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Provider Name</span>
                                        <span class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-provider-name">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Venue / Location</span>
                                        <span class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-venue">-</span>
                                    </div>
                                    <div class="col-12">
                                        <span class="text-muted d-block text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Instructor</span>
                                        <span class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-instructor">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 4: Schedule Duration & Program Evaluation -->
                        <div class="col-md-6">
                            <div class="card border-0 bg-light p-3 rounded-3 h-100 shadow-none">
                                <div class="fw-bold text-dark mb-2 text-uppercase d-flex align-items-center gap-1" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    <i class="ti ti-calendar-time text-primary"></i> 4. Schedule Duration & Program Evaluation
                                </div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <span class="text-muted d-block text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Start Date</span>
                                        <span class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-start-date">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Finish Date</span>
                                        <span class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-finish-date">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Duration (Seconds)</span>
                                        <span class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-duration-seconds">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Program Learning Hours</span>
                                        <span class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-course-hours">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 5: Participant & Job Information -->
                        <div class="col-12">
                            <div class="card border-0 bg-light p-3 rounded-3 shadow-none">
                                <div class="fw-bold text-dark mb-2 text-uppercase d-flex align-items-center gap-1" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    <i class="ti ti-user text-primary"></i> 5. Participant & Job Information
                                </div>
                                <div class="row g-2">
                                    <div class="col-sm-4 col-md-3">
                                        <div class="text-xs text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Employee ID (NIP)</div>
                                        <div class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-employee-id">{{ $record['employee_id'] ?? $authEmployeeId }}</div>
                                    </div>
                                    <div class="col-sm-4 col-md-3">
                                        <div class="text-xs text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Participant Name</div>
                                        <div class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-participant-name">{{ $record['participant_name'] ?? $record['name'] ?? $authName }}</div>
                                    </div>
                                    <div class="col-sm-4 col-md-2">
                                        <div class="text-xs text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Grade</div>
                                        <div class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-grade">{{ $record['grade'] ?? $authGrade }}</div>
                                    </div>
                                    <div class="col-sm-6 col-md-4">
                                        <div class="text-xs text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Job Position</div>
                                        <div class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-job-position">{{ $record['job_position'] ?? $authJobPosition }}</div>
                                    </div>
                                    <div class="col-sm-4 col-md-4">
                                        <div class="text-xs text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Team / Unit</div>
                                        <div class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-team">{{ $record['team'] ?? $authTeam }}</div>
                                    </div>
                                    <div class="col-sm-4 col-md-4">
                                        <div class="text-xs text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Department</div>
                                        <div class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-department">{{ $record['department'] ?? $authDepartment }}</div>
                                    </div>
                                    <div class="col-sm-4 col-md-4">
                                        <div class="text-xs text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Company</div>
                                        <div class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-company">{{ $record['company'] ?? $authCompany }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 6: Learning Progress & Evaluation -->
                        <div class="col-12">
                            <div class="card border-0 bg-light p-3 rounded-3 shadow-none">
                                <div class="fw-bold text-dark mb-2 text-uppercase d-flex align-items-center gap-1" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    <i class="ti ti-chart-bar text-primary"></i> 6. Learning Progress & Evaluation
                                </div>
                                <!-- Top Row: Progress | Learning Hours | Assessment Score | Attendance | Attendance Reason (if present) | Certificate -->
                                <div class="row g-2 align-items-start mb-2 pb-2 border-bottom">
                                    <div class="col-sm-4 col-md-2">
                                        <div class="text-xs text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Progress</div>
                                        <div class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-progress">{{ !empty($record['progress']) ? $record['progress'] . '%' : '-' }}</div>
                                    </div>
                                    <div class="col-sm-4 col-md-2">
                                        <div class="text-xs text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Learning Hours</div>
                                        <div class="fw-semibold text-dark text-primary" style="font-size: 0.875rem;" id="modal-detail-learning-hours">{{ $record['learning_hours'] ?? '-' }}</div>
                                    </div>
                                    <div class="col-sm-4 col-md-2">
                                        <div class="text-xs text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Assessment Score</div>
                                        <div class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-assessment-score">{{ $record['assessment_score'] ?? '-' }}</div>
                                    </div>
                                    <div class="col-sm-6 col-md-3" id="modal-detail-attendance-col">
                                        <div class="text-xs text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Attendance</div>
                                        <div class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-attendance">{{ $record['attendance'] ?? '-' }}</div>
                                    </div>
                                    <div class="col-sm-6 col-md-2" id="modal-detail-reason-container" style="display: none;">
                                        <div class="text-xs text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Attendance Reason</div>
                                        <div class="fw-semibold text-dark text-truncate d-block" style="font-size: 0.875rem;" id="modal-detail-attendance-reason" title="{{ $record['attendance_reason'] ?? '-' }}">{{ $record['attendance_reason'] ?? '-' }}</div>
                                    </div>
                                    <div class="col-sm-6 col-md-3" id="modal-detail-certificate-col">
                                        <div class="text-xs text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Certificate</div>
                                        <div class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-certificate">{{ $record['certificate'] ?? '-' }}</div>
                                    </div>
                                </div>
                                <!-- Bottom Row: L1 Program | L2 Numerical | L2 Verbal | L3 Numerical / Verbal -->
                                <div class="row g-2">
                                    <div class="col-sm-6 col-md-3">
                                        <div class="text-xs text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 500;">L1 Program</div>
                                        <div class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-l1-program">{{ $record['l1_program'] ?? '-' }}</div>
                                    </div>
                                    <div class="col-sm-6 col-md-3">
                                        <div class="text-xs text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 500;">L2 Numerical</div>
                                        <div class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-l2-num">{{ $record['l2_numerical'] ?? '-' }}</div>
                                    </div>
                                    <div class="col-sm-6 col-md-3">
                                        <div class="text-xs text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 500;">L2 Verbal</div>
                                        <div class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-l2-verbal">{{ $record['l2_verbal'] ?? '-' }}</div>
                                    </div>
                                    <div class="col-sm-6 col-md-3">
                                        <div class="text-xs text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 500;">L3 Numerical / Verbal</div>
                                        <div class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-l3-eval">{{ $record['l3_eval'] ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 7: Service Bond & Administration -->
                        <div class="col-12">
                            <div class="card border-0 bg-light p-3 rounded-3 shadow-none">
                                <div class="fw-bold text-dark mb-2 text-uppercase d-flex align-items-center gap-1" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    <i class="ti ti-file-certificate text-primary"></i> 7. Service Bond & Administration
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-2">
                                        <div class="text-xs text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Bond Status</div>
                                        <div class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-bond-status">{{ $record['bond_status'] ?? '-' }}</div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="text-xs text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Bond Duration</div>
                                        <div class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-bond-duration">{{ !empty($record['bond_duration']) ? $record['bond_duration'] . ' Months' : '-' }}</div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="text-xs text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Bond End Date</div>
                                        <div class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-bond-end-date">{{ $record['bond_end_date'] ?? '-' }}</div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="text-xs text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Bond Amount (IDR)</div>
                                        <div class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-bond-amount-idr">{{ $record['bond_amount_idr_formatted'] ?? $record['bond_amount_idr'] ?? $record['bond_amount'] ?? '-' }}</div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="text-xs text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 500;">Bond Amount (USD)</div>
                                        <div class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-bond-amount-usd">{{ $record['bond_amount_usd_formatted'] ?? $record['bond_amount_usd'] ?? '-' }}</div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="text-xs text-muted text-uppercase" style="font-size: 0.75rem; font-weight: 500;">PIC</div>
                                        <div class="fw-semibold text-dark" style="font-size: 0.875rem;" id="modal-detail-pic">{{ $record['pic'] ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top py-2 px-4 bg-light">
                <button type="button" class="btn btn-outline-secondary px-3 rounded-3" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    function fallback(val, def) {
        if (val === undefined || val === null || val === '') return (def !== undefined ? def : '-');
        return val;
    }

    function populateRecordDetailModal(d, source) {
        if (!d) return;

        var isOnline = (source === 'online' || (d.source && d.source === 'online') || (!d.offline_training_id && !d.batch && (d.category === 'All Content' || d.category === 'Content' || d.category === 'Playlist')));

        var authData = {
            employee_id: @json($authEmployeeId),
            name: @json($authName),
            grade: @json($authGrade),
            job_position: @json($authJobPosition),
            team: @json($authTeam),
            department: @json($authDepartment),
            company: @json($authCompany)
        };

        // Header
        var title = fallback(d.title, fallback(d.course_name, '-'));
        $('#modal-detail-course-name').text(title);
        $('#modal-detail-sub-course-name').text(fallback(d.subtitle, fallback(d.sub_course_name, '')));
        $('#modal-detail-source-badge').text(isOnline ? 'KP OWL (Online Learning)' : 'Non KP OWL (Offline / External Training)');

        var typeLabel = fallback(d.type, isOnline ? 'Content' : '-');
        $('#modal-detail-type').text(typeLabel);
        $('#modal-detail-sec-type').text(typeLabel);

        var progVal = parseInt(d.progress !== undefined ? d.progress : 0, 10);
        var isFinished = progVal >= 100 || String(d.finish) === '1' || String(d.status).toLowerCase() === 'finished' || String(d.completion_status).toLowerCase() === 'complete';
        $('#modal-detail-status').text(isFinished ? 'Finished' : (progVal > 0 ? 'Ongoing (' + progVal + '%)' : 'Not Started'))
            .removeClass('bg-success-lt bg-warning-lt bg-secondary-lt text-success text-warning text-secondary')
            .addClass(isFinished ? 'bg-success-lt text-success' : (progVal > 0 ? 'bg-warning-lt text-warning' : 'bg-secondary-lt text-secondary'));

        // Section 1: Course Information
        $('#modal-detail-batch').text(fallback(d.batch));
        $('#modal-detail-category').text(fallback(d.category, isOnline ? 'All Content' : 'Non KP-OWL'));
        $('#modal-detail-level').text(fallback(d.level));

        // Section 2: Classification & Corporate KPI
        $('#modal-detail-main-class').text(fallback(d.classification, fallback(d.main_classification)));
        $('#modal-detail-sub-class').text(fallback(d.sub_classification));
        $('#modal-detail-corporate-kpi').text(fallback(d.counted_corporate_kpi, fallback(d.corporate_kpi)));
        $('#modal-detail-sub-corporate-kpi').text(fallback(d.sub_corporate_kpi));

        // Section 3: Delivery Method & Provider
        $('#modal-detail-delivery-method').text(fallback(d.delivery_method, isOnline ? 'Online' : '-'));
        $('#modal-detail-sub-delivery-method').text(fallback(d.sub_delivery_method));
        $('#modal-detail-provider').text(fallback(d.provider, isOnline ? 'Internal' : '-'));
        $('#modal-detail-sub-provider').text(fallback(d.sub_provider));
        $('#modal-detail-provider-name').text(fallback(d.provider_name, isOnline ? 'KP-OWL' : '-'));
        $('#modal-detail-venue').text(fallback(d.venue, isOnline ? 'KP-OWL' : '-'));
        $('#modal-detail-instructor').text(fallback(d.instructor, fallback(d.author)));

        // Section 4: Schedule Duration & Program Evaluation
        $('#modal-detail-start-date').text(fallback(d.start_date, fallback(d.start_datetime)));
        $('#modal-detail-finish-date').text(fallback(d.finish_date, fallback(d.end_date, fallback(d.end_datetime))));
        var durSec = fallback(d.duration_seconds, fallback(d.duration_sort));
        $('#modal-detail-duration-seconds').text(durSec !== '-' ? durSec + ' s' : fallback(d.duration));
        var courseHours = fallback(d.course_learning_hours, fallback(d.learning_hours, fallback(d.learning_hour_value)));
        $('#modal-detail-course-hours').text(courseHours !== '-' ? (typeof courseHours === 'number' ? courseHours.toFixed(2) + ' h' : courseHours) : '-');

        // Section 5: Participant & Job Information
        var empId = fallback(d.employee_id, fallback(d.nip, fallback(d.user_id_emp, (isOnline ? authData.employee_id : '-'))));
        if ((empId === '-' || !empId) && isOnline) empId = authData.employee_id;
        $('#modal-detail-employee-id').text(empId);

        var partName = fallback(d.participant_name, fallback(d.name, fallback(d.user_name, (isOnline ? authData.name : '-'))));
        if ((partName === '-' || !partName) && isOnline) partName = authData.name;
        $('#modal-detail-participant-name').text(partName);

        var grade = fallback(d.grade, (isOnline ? authData.grade : '-'));
        if ((grade === '-' || !grade) && isOnline) grade = authData.grade;
        $('#modal-detail-grade').text(grade);

        var jobPos = fallback(d.job_position, fallback(d.position, (isOnline ? authData.job_position : '-')));
        if ((jobPos === '-' || !jobPos) && isOnline) jobPos = authData.job_position;
        $('#modal-detail-job-position').text(jobPos);

        var team = fallback(d.team, fallback(d.user_team, (isOnline ? authData.team : '-')));
        if ((team === '-' || !team) && isOnline) team = authData.team;
        $('#modal-detail-team').text(team);

        var dept = fallback(d.department, fallback(d.user_department, (isOnline ? authData.department : '-')));
        if ((dept === '-' || !dept) && isOnline) dept = authData.department;
        $('#modal-detail-department').text(dept);

        var comp = fallback(d.company, fallback(d.company_name, fallback(d.user_company, (isOnline ? authData.company : 'PT Krakatau Posco'))));
        if ((comp === '-' || !comp) && isOnline) comp = authData.company;
        if (comp === '-' || !comp) comp = 'PT Krakatau Posco';
        $('#modal-detail-company').text(comp);

        // Section 6: Learning Progress & Evaluation
        $('#modal-detail-progress').text(progVal + '%');
        var lhDisplay = fallback(d.learning_hour, (d.learning_hours !== undefined ? parseFloat(d.learning_hours).toFixed(2) + ' h' : '-'));
        $('#modal-detail-learning-hours').text(lhDisplay);

        var scoreVal = fallback(d.assessment_score, fallback(d.score));
        if (scoreVal !== '-' && !isNaN(scoreVal) && scoreVal !== '' && scoreVal !== null) {
            var sNum = parseFloat(scoreVal);
            $('#modal-detail-assessment-score').text((sNum % 1 === 0) ? sNum.toString() : sNum.toString());
        } else {
            $('#modal-detail-assessment-score').text('-');
        }

        var rawAtt = fallback(d.attendance_status, fallback(d.attendance));
        var isPresent = (String(rawAtt).toLowerCase() === 'yes' || String(rawAtt) === '1' || String(rawAtt).toLowerCase() === 'present' || progVal >= 100);
        $('#modal-detail-attendance').text(isPresent ? 'Yes' : 'No');

        var certVal = (d.has_certificate || d.cert_state === 'ready' || String(d.certificate).toLowerCase() === 'available' || String(d.certificate).toLowerCase() === 'yes' || String(d.certificate) === '1') ? 'Available' : 'Not Available';
        $('#modal-detail-certificate').text(certVal);

        if (d.attendance_reason && d.attendance_reason !== '-' && String(d.attendance_reason).trim() !== '') {
            $('#modal-detail-attendance-reason').text(d.attendance_reason).attr('title', d.attendance_reason);
            $('#modal-detail-reason-container').show();
            $('#modal-detail-attendance-col').removeClass('col-md-3').addClass('col-md-2');
            $('#modal-detail-certificate-col').removeClass('col-md-3').addClass('col-md-2');
        } else {
            $('#modal-detail-reason-container').hide();
            $('#modal-detail-attendance-col').removeClass('col-md-2').addClass('col-md-3');
            $('#modal-detail-certificate-col').removeClass('col-md-2').addClass('col-md-3');
        }

        $('#modal-detail-l1-program').text(fallback(d.l1_program));
        $('#modal-detail-l2-num').text(fallback(d.l2_numerical, scoreVal));
        $('#modal-detail-l2-verbal').text(fallback(d.l2_verbal, (scoreVal !== '-' && !isNaN(scoreVal) ? (parseFloat(scoreVal) >= 70 ? 'Passed' : 'Failed') : '-')));
        var l3Num = fallback(d.l3_numerical, '');
        var l3Verb = fallback(d.l3_verbal, '');
        var l3Display = (l3Num && l3Verb) ? (l3Num + ' / ' + l3Verb) : (l3Num || l3Verb || '-');
        $('#modal-detail-l3-eval').text(l3Display);

        // Section 7: Service Bond & Administration
        $('#modal-detail-bond-status').text(fallback(d.bond_status));
        var bondDur = fallback(d.bond_duration);
        if (bondDur !== '-' && bondDur !== null && bondDur !== undefined && String(bondDur).trim() !== '') {
            $('#modal-detail-bond-duration').text(bondDur + ' Months');
        } else {
            $('#modal-detail-bond-duration').text('-');
        }
        $('#modal-detail-bond-end-date').text(fallback(d.bond_end_date, fallback(d.bond_end_date_display)));
        var idrVal = d.bond_amount_idr !== undefined ? d.bond_amount_idr : d.bond_amount;
        if (idrVal !== '-' && idrVal !== null && idrVal !== undefined && idrVal !== '' && !isNaN(idrVal) && parseFloat(idrVal) > 0) {
            $('#modal-detail-bond-amount-idr').text('Rp ' + parseFloat(idrVal).toLocaleString('id-ID'));
        } else if (d.bond_amount_idr_formatted && d.bond_amount_idr_formatted !== '-') {
            $('#modal-detail-bond-amount-idr').text(d.bond_amount_idr_formatted);
        } else {
            $('#modal-detail-bond-amount-idr').text('-');
        }

        var usdVal = d.bond_amount_usd;
        if (usdVal !== '-' && usdVal !== null && usdVal !== undefined && usdVal !== '' && !isNaN(usdVal) && parseFloat(usdVal) > 0) {
            $('#modal-detail-bond-amount-usd').text('$ ' + parseFloat(usdVal).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        } else if (d.bond_amount_usd_formatted && d.bond_amount_usd_formatted !== '-') {
            $('#modal-detail-bond-amount-usd').text(d.bond_amount_usd_formatted);
        } else {
            $('#modal-detail-bond-amount-usd').text('-');
        }

        $('#modal-detail-bond-amount').text(fallback(d.bond_amount_formatted, fallback(d.bond_amount)));
        $('#modal-detail-pic').text(fallback(d.pic));
    }

    $(document).on('click', '[data-bs-target="#recordDetailModal"], .btn-record-detail', function(e) {
        var $el = $(this);
        var recordId = $el.data('record-id');
        var source = ($el.data('record-source') || 'offline').toString().toLowerCase();

        // 1. Try finding local JSON element
        var dataEl = document.getElementById('record-data-' + recordId)
                  || document.getElementById('record-offline-data-' + recordId)
                  || document.getElementById('record-online-data-' + recordId);

        if (dataEl) {
            try {
                var d = JSON.parse(dataEl.textContent);
                populateRecordDetailModal(d, source);
                return;
            } catch (err) {
                console.warn('Failed parsing local record JSON:', err);
            }
        }

        // 2. Fallback to AJAX endpoint
        if (recordId) {
            $('#record-detail-loading').show();
            $('#record-detail-content').hide();

            var url = '/my-learning/learning-record/detail/' + encodeURIComponent(source) + '/' + encodeURIComponent(recordId);
            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    $('#record-detail-loading').hide();
                    $('#record-detail-content').show();
                    if (res && res.success && res.data) {
                        populateRecordDetailModal(res.data, source);
                    }
                },
                error: function() {
                    $('#record-detail-loading').hide();
                    $('#record-detail-content').show();
                }
            });
        }
    });
})();
</script>
