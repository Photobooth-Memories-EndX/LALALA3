{{-- Shared Training Record Detail Modal (Used by KP OWL Online, Non KP OWL Offline, and All Record) --}}
<div class="modal fade" id="recordDetailModal" tabindex="-1" aria-labelledby="recordDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-2">
                    <span class="avatar rounded-3" style="background-color: #fef3c7; color: #d97706; width: 42px; height: 42px;">
                        <i class="ti ti-certificate fs-2"></i>
                    </span>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="recordDetailModalLabel">Training Record Detail</h5>
                        <div class="text-muted extra-small" style="font-size: 0.75rem;" id="modal-detail-source-badge">Learning Record Detail</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body py-3" id="recordDetailModalBody">
                <!-- Loading State (for async fetches) -->
                <div class="py-5 text-center text-muted" id="record-detail-loading" style="display: none;">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 small">Loading training details...</div>
                </div>

                <!-- Content Container -->
                <div id="record-detail-content">
                    <!-- Badge Header -->
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-3 border-bottom">
                        <div>
                            <h4 class="fw-bold mb-1 text-dark" id="modal-detail-course-name">-</h4>
                            <div class="text-muted small" id="modal-detail-sub-course-name"></div>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge bg-light text-secondary border px-2.5 py-1.5" id="modal-detail-type">-</span>
                            <span class="badge bg-success-lt px-2.5 py-1.5 fw-bold" id="modal-detail-status">-</span>
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- Section 1: Course Information -->
                        <div class="col-md-6">
                            <div class="card border-0 bg-light p-3 rounded-3 h-100">
                                <div class="fw-bold small text-dark mb-2 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    <i class="ti ti-info-circle me-1 text-primary"></i> 1. Course Information
                                </div>
                                <div class="row g-2 small">
                                    <div class="col-6">
                                        <span class="text-muted d-block">Batch:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-batch">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Category:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-category">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Level:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-level">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Type:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-sec-type">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 2: Classification & Corporate KPI -->
                        <div class="col-md-6">
                            <div class="card border-0 bg-light p-3 rounded-3 h-100">
                                <div class="fw-bold small text-dark mb-2 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    <i class="ti ti-chart-dots me-1 text-primary"></i> 2. Classification & Corporate KPI
                                </div>
                                <div class="row g-2 small">
                                    <div class="col-6">
                                        <span class="text-muted d-block">Main Classification:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-main-class">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Sub Classification:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-sub-class">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Counted Corporate KPI:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-corporate-kpi">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Sub Corporate KPI:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-sub-corporate-kpi">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Delivery Method & Provider -->
                        <div class="col-md-6">
                            <div class="card border-0 bg-light p-3 rounded-3 h-100">
                                <div class="fw-bold small text-dark mb-2 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    <i class="ti ti-building me-1 text-primary"></i> 3. Delivery Method & Provider
                                </div>
                                <div class="row g-2 small">
                                    <div class="col-6">
                                        <span class="text-muted d-block">Delivery Method:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-delivery-method">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Sub Delivery Method:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-sub-delivery-method">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Provider:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-provider">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Sub Provider:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-sub-provider">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Provider Name:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-provider-name">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Venue / Location:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-venue">-</span>
                                    </div>
                                    <div class="col-12">
                                        <span class="text-muted d-block">Instructor:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-instructor">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 4: Schedule Duration & Program Evaluation -->
                        <div class="col-md-6">
                            <div class="card border-0 bg-light p-3 rounded-3 h-100">
                                <div class="fw-bold small text-dark mb-2 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    <i class="ti ti-calendar-time me-1 text-primary"></i> 4. Schedule Duration & Program Evaluation
                                </div>
                                <div class="row g-2 small">
                                    <div class="col-6">
                                        <span class="text-muted d-block">Start Date:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-start-date">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Finish Date:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-finish-date">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Duration (Seconds):</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-duration-seconds">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Program Learning Hours:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-course-hours">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 5: Participant & Job Information -->
                        <div class="col-12">
                            <div class="card border-0 bg-light p-3 rounded-3">
                                <div class="fw-bold small text-dark mb-2 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    <i class="ti ti-user me-1 text-primary"></i> 5. Participant & Job Information
                                </div>
                                <div class="row g-2 small">
                                    <div class="col-sm-4 col-md-3">
                                        <span class="text-muted d-block">Employee ID (NIP):</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-employee-id">-</span>
                                    </div>
                                    <div class="col-sm-4 col-md-3">
                                        <span class="text-muted d-block">Participant Name:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-participant-name">-</span>
                                    </div>
                                    <div class="col-sm-4 col-md-2">
                                        <span class="text-muted d-block">Grade:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-grade">-</span>
                                    </div>
                                    <div class="col-sm-6 col-md-4">
                                        <span class="text-muted d-block">Job Position:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-job-position">-</span>
                                    </div>
                                    <div class="col-sm-4 col-md-4">
                                        <span class="text-muted d-block">Team / Unit:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-team">-</span>
                                    </div>
                                    <div class="col-sm-4 col-md-4">
                                        <span class="text-muted d-block">Department:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-department">-</span>
                                    </div>
                                    <div class="col-sm-4 col-md-4">
                                        <span class="text-muted d-block">Company:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-company">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 6: Learning Progress & Evaluation -->
                        <div class="col-12">
                            <div class="card border-0 bg-light p-3 rounded-3">
                                <div class="fw-bold small text-dark mb-2 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    <i class="ti ti-chart-bar me-1 text-primary"></i> 6. Learning Progress & Evaluation
                                </div>
                                <div class="row g-2 small">
                                    <div class="col-sm-4 col-md-2">
                                        <span class="text-muted d-block">Progress:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-progress">-</span>
                                    </div>
                                    <div class="col-sm-4 col-md-2">
                                        <span class="text-muted d-block">Learning Hours:</span>
                                        <span class="fw-semibold text-dark text-primary" id="modal-detail-learning-hours">-</span>
                                    </div>
                                    <div class="col-sm-4 col-md-2">
                                        <span class="text-muted d-block">Assessment Score:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-assessment-score">-</span>
                                    </div>
                                    <div class="col-sm-6 col-md-3">
                                        <span class="text-muted d-block">Attendance:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-attendance">-</span>
                                    </div>
                                    <div class="col-sm-6 col-md-3">
                                        <span class="text-muted d-block">Certificate:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-certificate">-</span>
                                    </div>
                                    <div class="col-12" id="modal-detail-reason-container" style="display: none;">
                                        <span class="text-muted d-block">Attendance Reason:</span>
                                        <span class="text-dark" id="modal-detail-attendance-reason">-</span>
                                    </div>
                                    <!-- L1 to L3 Evaluation Metrics -->
                                    <div class="col-sm-6 col-md-3 mt-2">
                                        <span class="text-muted d-block">L1 Program:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-l1-program">-</span>
                                    </div>
                                    <div class="col-sm-6 col-md-3 mt-2">
                                        <span class="text-muted d-block">L2 Numerical:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-l2-num">-</span>
                                    </div>
                                    <div class="col-sm-6 col-md-3 mt-2">
                                        <span class="text-muted d-block">L2 Verbal:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-l2-verbal">-</span>
                                    </div>
                                    <div class="col-sm-6 col-md-3 mt-2">
                                        <span class="text-muted d-block">L3 Numerical / Verbal:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-l3-eval">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 7: Service Bond & Administration -->
                        <div class="col-12">
                            <div class="card border-0 bg-light p-3 rounded-3">
                                <div class="fw-bold small text-dark mb-2 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    <i class="ti ti-file-certificate me-1 text-primary"></i> 7. Service Bond & Administration
                                </div>
                                <div class="row g-2 small">
                                    <div class="col-sm-4 col-md-3">
                                        <span class="text-muted d-block">Bond Status:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-bond-status">-</span>
                                    </div>
                                    <div class="col-sm-4 col-md-3">
                                        <span class="text-muted d-block">Bond End Date:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-bond-end-date">-</span>
                                    </div>
                                    <div class="col-sm-4 col-md-3">
                                        <span class="text-muted d-block">Bond Amount:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-bond-amount">-</span>
                                    </div>
                                    <div class="col-sm-6 col-md-3">
                                        <span class="text-muted d-block">PIC:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-pic">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">Close</button>
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

        var isOnline = (source === 'online' || (d.source && d.source === 'online') || (!d.offline_training_id && !d.batch && d.category === 'All Content'));

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
            .removeClass('bg-success-lt bg-warning-lt bg-secondary-lt')
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
        $('#modal-detail-employee-id').text(fallback(d.employee_id, fallback(d.user_id_emp)));
        $('#modal-detail-participant-name').text(fallback(d.participant_name, fallback(d.user_name, fallback(d.name))));
        $('#modal-detail-grade').text(fallback(d.grade));
        $('#modal-detail-job-position').text(fallback(d.job_position));
        $('#modal-detail-team').text(fallback(d.team, fallback(d.user_team)));
        $('#modal-detail-department').text(fallback(d.department, fallback(d.user_department)));
        $('#modal-detail-company').text(fallback(d.company, fallback(d.user_company)));

        // Section 6: Learning Progress & Evaluation
        $('#modal-detail-progress').text(progVal + '%');
        var lhDisplay = fallback(d.learning_hour, (d.learning_hours !== undefined ? parseFloat(d.learning_hours).toFixed(2) + ' h' : '-'));
        $('#modal-detail-learning-hours').text(lhDisplay);

        var scoreVal = fallback(d.assessment_score, fallback(d.score));
        $('#modal-detail-assessment-score').text(scoreVal !== '-' && !isNaN(scoreVal) ? parseFloat(scoreVal).toFixed(2) : scoreVal);

        var rawAtt = fallback(d.attendance_status, fallback(d.attendance));
        var isPresent = (String(rawAtt).toLowerCase() === 'yes' || String(rawAtt) === '1' || String(rawAtt).toLowerCase() === 'present' || progVal >= 100);
        $('#modal-detail-attendance').text(isPresent ? 'Yes' : 'No');

        var certVal = (d.has_certificate || d.cert_state === 'ready' || String(d.certificate).toLowerCase() === 'available') ? 'Available' : 'Not Available';
        $('#modal-detail-certificate').text(certVal);

        if (d.attendance_reason && d.attendance_reason !== '-') {
            $('#modal-detail-attendance-reason').text(d.attendance_reason);
            $('#modal-detail-reason-container').show();
        } else {
            $('#modal-detail-reason-container').hide();
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
        $('#modal-detail-bond-end-date').text(fallback(d.bond_end_date, fallback(d.bond_end_date_display)));
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
