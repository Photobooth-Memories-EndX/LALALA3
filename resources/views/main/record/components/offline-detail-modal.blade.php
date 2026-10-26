<!-- Offline Training Detail Modal (7 Sections matching HRDC Excel schema) -->
<div class="modal fade" id="offlineTrainingDetailModal" tabindex="-1" aria-labelledby="offlineTrainingDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-2">
                    <span class="avatar rounded-3" style="background-color: #fef3c7; color: #d97706; width: 42px; height: 42px;">
                        <i class="ti ti-certificate fs-2"></i>
                    </span>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="offlineTrainingDetailModalLabel">Training Record Detail</h5>
                        <div class="text-muted extra-small" style="font-size: 0.75rem;">Non KP OWL (Offline / External Training)</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body py-3" id="offlineTrainingDetailModalBody">
                <!-- Loading State -->
                <div class="py-5 text-center text-muted" id="offline-detail-loading">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 small">Loading training details...</div>
                </div>

                <!-- Content Container (populated dynamically via JS) -->
                <div id="offline-detail-content" style="display: none;">
                    <!-- Badge Header -->
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-3 border-bottom">
                        <div>
                            <h4 class="fw-bold mb-1 text-dark" id="modal-detail-course-name">-</h4>
                            <div class="text-muted small" id="modal-detail-sub-course-name"></div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
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

                        <!-- Section 2: Classification & KPI -->
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
                                        <span class="fw-semibold text-dark" id="modal-detail-learning-hours">-</span>
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
                                        <span class="text-muted d-block">Bond Duration:</span>
                                        <span class="fw-semibold text-dark" id="modal-detail-bond-duration">-</span>
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
