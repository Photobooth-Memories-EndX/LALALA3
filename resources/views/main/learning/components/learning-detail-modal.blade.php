<!-- View Learning Record Detail Modal -->
<div class="modal modal-blur fade" id="learningDetailModal" tabindex="-1" aria-labelledby="learningDetailModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 820px;">
        <div class="modal-content rounded-3 border-0 shadow">
            <!-- Modal Header -->
            <div class="modal-header px-4 py-3 border-bottom">
                <div class="d-flex align-items-center gap-2 flex-grow-1 min-w-0">
                    <div class="avatar avatar-sm bg-primary-lt text-primary rounded-2 flex-shrink-0">
                        <i class="ti ti-eye fs-3"></i>
                    </div>
                    <div class="min-w-0 flex-grow-1">
                        <h5 class="modal-title fw-bold text-dark mb-0 text-truncate" id="learningDetailModalTitle">Learning Record Details</h5>
                        <small class="text-muted d-block lh-1" id="learningDetailModalSubtitle">Comprehensive employee progress and tracking information</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Loading State -->
            <div class="modal-body py-5 text-center" id="learningDetailLoading">
                <div class="spinner-border text-primary mb-3" style="width: 2.5rem; height: 2.5rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h5 class="fw-bold mb-1">Loading Learning Details...</h5>
                <p class="text-muted small mb-0">Please wait while we retrieve the record data.</p>
            </div>

            <!-- Error State -->
            <div class="modal-body py-4" id="learningDetailError" style="display: none;">
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-0 py-3 px-3">
                    <i class="ti ti-alert-circle fs-2 flex-shrink-0"></i>
                    <div class="flex-grow-1 min-w-0">
                        <strong class="d-block small">Failed to Load Details</strong>
                        <span class="small" id="learningDetailErrorMessage">Unable to load learning record details. Please try again.</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger px-3 ms-auto" data-bs-dismiss="modal">
                        Close
                    </button>
                </div>
            </div>

            <!-- Data Body -->
            <div class="modal-body px-4 py-3" id="learningDetailContent" style="display: none;">
                <!-- 1. Learning Information Section -->
                <div class="border rounded-3 p-3 mb-3 bg-light owl-modal-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase text-muted fw-bold small" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                            <i class="ti ti-book me-1 text-primary"></i> Learning Information
                        </span>
                        <div class="d-flex align-items-center gap-1">
                            <span class="badge bg-blue-lt" id="ld-content-type">-</span>
                            <span class="badge bg-azure-lt" id="ld-content-source">-</span>
                            <span class="badge bg-green-lt" id="ld-content-delivery">Online</span>
                        </div>
                    </div>
                    <h4 class="fw-bold text-dark mb-1 lh-sm" id="ld-content-title">-</h4>
                    <div class="text-muted small mb-3" id="ld-content-sub-title-wrapper">
                        Sub Course: <span class="fw-semibold text-secondary" id="ld-content-sub-title">-</span>
                    </div>

                    <div class="row g-2 small">
                        <div class="col-sm-6">
                            <div class="p-2 rounded-2 bg-white border d-flex justify-content-between">
                                <span class="text-muted">Main Classification</span>
                                <strong class="text-dark" id="ld-content-main-class">-</strong>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-2 rounded-2 bg-white border d-flex justify-content-between">
                                <span class="text-muted">Sub Classification</span>
                                <strong class="text-dark" id="ld-content-sub-class">-</strong>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-2 rounded-2 bg-white border d-flex justify-content-between">
                                <span class="text-muted">Category</span>
                                <strong class="text-dark" id="ld-content-category">-</strong>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-2 rounded-2 bg-white border d-flex justify-content-between">
                                <span class="text-muted">Sub Delivery Method</span>
                                <strong class="text-dark" id="ld-content-sub-delivery">-</strong>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-2 rounded-2 bg-white border d-flex justify-content-between">
                                <span class="text-muted">Level</span>
                                <strong class="text-dark" id="ld-content-level">-</strong>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-2 rounded-2 bg-white border d-flex justify-content-between">
                                <span class="text-muted">Batch</span>
                                <strong class="text-dark" id="ld-content-batch">-</strong>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-2 rounded-2 bg-white border d-flex justify-content-between">
                                <span class="text-muted">Provider Name</span>
                                <strong class="text-dark" id="ld-content-provider">-</strong>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-2 rounded-2 bg-white border d-flex justify-content-between">
                                <span class="text-muted">Venue</span>
                                <strong class="text-dark" id="ld-content-venue">-</strong>
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div class="p-2 rounded-2 bg-white border d-flex justify-content-between">
                                <span class="text-muted">Instructor</span>
                                <strong class="text-dark" id="ld-content-instructor">-</strong>
                            </div>
                        </div>
                        <div class="col-12" id="ld-playlist-container" style="display: none;">
                            <div class="p-2 rounded-2 bg-white border d-flex justify-content-between">
                                <span class="text-muted"><i class="ti ti-list-details me-1"></i>Playlist</span>
                                <strong class="text-dark" id="ld-playlist-name">-</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Employee Information Section -->
                <div class="border rounded-3 p-3 mb-3 bg-light owl-modal-card">
                    <span class="text-uppercase text-muted fw-bold small d-block mb-2" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                        <i class="ti ti-user me-1 text-primary"></i> Employee Information
                    </span>
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <div class="p-2 rounded-2 bg-white border owl-modal-subcard d-flex align-items-center justify-content-between">
                                <span class="small text-muted">Employee ID / NIP</span>
                                <strong class="small text-dark font-monospace" id="ld-emp-id">-</strong>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-2 rounded-2 bg-white border owl-modal-subcard d-flex align-items-center justify-content-between">
                                <span class="small text-muted">Employee Name</span>
                                <strong class="small text-dark text-truncate ms-2" id="ld-emp-name">-</strong>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-2 rounded-2 bg-white border owl-modal-subcard d-flex align-items-center justify-content-between">
                                <span class="small text-muted">Grade</span>
                                <strong class="small text-dark text-truncate ms-2" id="ld-emp-grade">-</strong>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-2 rounded-2 bg-white border owl-modal-subcard d-flex align-items-center justify-content-between">
                                <span class="small text-muted">Job Position</span>
                                <strong class="small text-dark text-truncate ms-2" id="ld-emp-job-position">-</strong>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-2 rounded-2 bg-white border owl-modal-subcard d-flex align-items-center justify-content-between">
                                <span class="small text-muted">Team</span>
                                <strong class="small text-dark text-truncate ms-2" id="ld-emp-team">-</strong>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-2 rounded-2 bg-white border owl-modal-subcard d-flex align-items-center justify-content-between">
                                <span class="small text-muted">Department</span>
                                <strong class="small text-dark text-truncate ms-2" id="ld-emp-dept">-</strong>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-2 rounded-2 bg-white border owl-modal-subcard d-flex align-items-center justify-content-between">
                                <span class="small text-muted">Company</span>
                                <strong class="small text-dark text-truncate ms-2" id="ld-emp-company">-</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Progress & Duration Tracking Section -->
                <div class="border rounded-3 p-3 mb-3 bg-light owl-modal-card">
                    <span class="text-uppercase text-muted fw-bold small d-block mb-2" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                        <i class="ti ti-chart-bar me-1 text-primary"></i> Duration & Progress Tracking
                    </span>

                    <!-- Progress Bar & Finish Badge -->
                    <div class="p-2 rounded-2 bg-white border owl-modal-subcard mb-2">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="small text-muted fw-semibold">% Progress</span>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge" id="ld-finish-badge">-</span>
                                <strong class="small text-dark" id="ld-progress-text">0%</strong>
                            </div>
                        </div>
                        <div class="progress progress-sm">
                            <div class="progress-bar bg-primary" id="ld-progress-bar" style="width: 0%"></div>
                        </div>
                    </div>

                    <!-- Tracking Metrics Grid -->
                    <div class="row g-2">
                        <div class="col-sm-4">
                            <div class="p-2 rounded-2 bg-white border owl-modal-subcard text-center">
                                <div class="text-muted small lh-1 mb-1">Progress Status</div>
                                <div class="fw-semibold text-dark small" id="ld-progress-status">-</div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-2 rounded-2 bg-white border owl-modal-subcard text-center">
                                <div class="text-muted small lh-1 mb-1">Learning Status</div>
                                <div class="fw-semibold text-dark small" id="ld-learning-status">-</div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-2 rounded-2 bg-white border owl-modal-subcard text-center">
                                <div class="text-muted small lh-1 mb-1">Requirement</div>
                                <div class="fw-semibold text-dark small" id="ld-status">-</div>
                            </div>
                        </div>

                        <div class="col-sm-3">
                            <div class="p-2 rounded-2 bg-white border owl-modal-subcard text-center">
                                <div class="text-muted small lh-1 mb-1">Course Duration</div>
                                <div class="fw-bold text-dark font-monospace small" id="ld-duration">00:00:00</div>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="p-2 rounded-2 bg-white border owl-modal-subcard text-center">
                                <div class="text-muted small lh-1 mb-1">Recognized Duration</div>
                                <div class="fw-bold text-dark font-monospace small" id="ld-total-recognized-duration">-</div>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="p-2 rounded-2 bg-white border owl-modal-subcard text-center">
                                <div class="text-muted small lh-1 mb-1">Learning Hours</div>
                                <div class="h5 fw-bold text-primary mb-0 font-monospace" id="ld-learning-hours">0.0000 h</div>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="p-2 rounded-2 bg-white border owl-modal-subcard text-center">
                                <div class="text-muted small lh-1 mb-1">Total Engagement Time</div>
                                <div class="h5 fw-bold text-dark mb-0 font-monospace" id="ld-total-engagement-time">0.00 h</div>
                            </div>
                        </div>

                        <div class="col-sm-4">
                            <div class="p-2 rounded-2 bg-white border owl-modal-subcard text-center">
                                <div class="text-muted small lh-1 mb-1">Current Time</div>
                                <div class="fw-bold text-dark font-monospace small" id="ld-current-time">-</div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-2 rounded-2 bg-white border owl-modal-subcard text-center">
                                <div class="text-muted small lh-1 mb-1">Actual Watch</div>
                                <div class="fw-bold text-dark font-monospace small" id="ld-actual-watch">-</div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-2 rounded-2 bg-white border owl-modal-subcard text-center">
                                <div class="text-muted small lh-1 mb-1">AFK Duration</div>
                                <div class="fw-bold text-dark font-monospace small" id="ld-afk-duration">-</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Record Information & Obligations Section -->
                <div class="border rounded-3 p-3 mb-3 bg-light owl-modal-card">
                    <span class="text-uppercase text-muted fw-bold small d-block mb-2" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                        <i class="ti ti-calendar-event me-1 text-primary"></i> Record Information & Obligations
                    </span>
                    <div class="row g-2 small">
                        <div class="col-sm-6">
                            <div class="p-2 rounded-2 bg-white border d-flex justify-content-between">
                                <span class="text-muted">Start Date & Time</span>
                                <strong class="text-dark font-monospace" id="ld-record-start-date">-</strong>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-2 rounded-2 bg-white border d-flex justify-content-between">
                                <span class="text-muted">End Date & Time</span>
                                <strong class="text-dark font-monospace" id="ld-record-end-date">-</strong>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-2 rounded-2 bg-white border d-flex justify-content-between">
                                <span class="text-muted">Record Month</span>
                                <strong class="text-dark" id="ld-record-month">-</strong>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-2 rounded-2 bg-white border d-flex justify-content-between">
                                <span class="text-muted">Attendance</span>
                                <strong class="text-dark" id="ld-record-attendance">-</strong>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-2 rounded-2 bg-white border d-flex justify-content-between">
                                <span class="text-muted">Attendance Reason</span>
                                <strong class="text-dark" id="ld-record-attendance-reason">-</strong>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="p-2 rounded-2 bg-white border text-center">
                                <div class="text-muted extra-small mb-1">Bond Status</div>
                                <strong class="text-dark" id="ld-obligation-bond-status">-</strong>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="p-2 rounded-2 bg-white border text-center">
                                <div class="text-muted extra-small mb-1">Bond Duration</div>
                                <strong class="text-dark" id="ld-obligation-bond-duration">-</strong>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="p-2 rounded-2 bg-white border text-center">
                                <div class="text-muted extra-small mb-1">Bond End Date</div>
                                <strong class="text-dark" id="ld-obligation-bond-end-date">-</strong>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="p-2 rounded-2 bg-white border text-center">
                                <div class="text-muted extra-small mb-1">Bond Amount</div>
                                <strong class="text-dark" id="ld-obligation-bond-amount">-</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. Assessment & Certification Section -->
                <div class="border rounded-3 p-3 mb-3 bg-light owl-modal-card">
                    <span class="text-uppercase text-muted fw-bold small d-block mb-2" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                        <i class="ti ti-award me-1 text-primary"></i> Assessment & Certification
                    </span>
                    <div class="row g-2 small">
                        <div class="col-sm-4">
                            <div class="p-2 rounded-2 bg-white border text-center">
                                <div class="text-muted extra-small mb-1">Score (L1) - Course</div>
                                <strong class="text-dark font-monospace" id="ld-score-l1">-</strong>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-2 rounded-2 bg-white border text-center">
                                <div class="text-muted extra-small mb-1">Score (L2)</div>
                                <strong class="text-dark font-monospace" id="ld-score-l2">-</strong>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-2 rounded-2 bg-white border text-center">
                                <div class="text-muted extra-small mb-1">L2 Status</div>
                                <strong class="text-dark" id="ld-l2-status">-</strong>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-2 rounded-2 bg-white border text-center">
                                <div class="text-muted extra-small mb-1">Score (L3)</div>
                                <strong class="text-dark font-monospace" id="ld-score-l3">-</strong>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-2 rounded-2 bg-white border text-center">
                                <div class="text-muted extra-small mb-1">L3 Status</div>
                                <strong class="text-dark" id="ld-l3-status">-</strong>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-2 rounded-2 bg-white border text-center">
                                <div class="text-muted extra-small mb-1">Person in Charge (PIC)</div>
                                <strong class="text-dark" id="ld-pic">-</strong>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-2 rounded-2 bg-white border d-flex justify-content-between align-items-center">
                                <span class="text-muted"><i class="ti ti-certificate me-1 text-warning"></i>Certificate</span>
                                <strong class="text-dark" id="ld-certificate">-</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 6. Timestamp Information Section -->
                <div class="border rounded-3 p-3 bg-light owl-modal-card">
                    <span class="text-uppercase text-muted fw-bold small d-block mb-2" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                        <i class="ti ti-clock me-1 text-primary"></i> Timestamps & History
                    </span>
                    <div class="row g-2">
                        <div class="col-sm-4">
                            <div class="p-2 rounded-2 bg-white border owl-modal-subcard">
                                <span class="small text-muted d-block lh-1 mb-1">Enrolled / Started</span>
                                <strong class="small text-dark font-monospace" id="ld-created-at">-</strong>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-2 rounded-2 bg-white border owl-modal-subcard">
                                <span class="small text-muted d-block lh-1 mb-1">Last Activity / Updated</span>
                                <strong class="small text-dark font-monospace" id="ld-updated-at">-</strong>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-2 rounded-2 bg-white border owl-modal-subcard">
                                <span class="small text-muted d-block lh-1 mb-1">Completed At</span>
                                <strong class="small text-success font-monospace" id="ld-completed-at">-</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Modal Footer -->
            <div class="modal-footer px-4 py-2 bg-light border-top d-flex justify-content-end align-items-center">
                <button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Learning Record Modal (Functional) -->
<div class="modal modal-blur fade" id="learningEditModal" tabindex="-1" aria-labelledby="learningEditModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 780px;">
        <div class="modal-content rounded-3 border-0 shadow">
            <form id="learningEditForm" autocomplete="off">
                <input type="hidden" id="edit-source-type" value="">
                <input type="hidden" id="edit-source-id" value="">
                <input type="hidden" id="edit-original-finish" value="0">
                <input type="hidden" id="edit-duration-seconds" value="0">

                <!-- Modal Header -->
                <div class="modal-header px-4 py-3 border-bottom">
                    <div class="d-flex align-items-center gap-2 flex-grow-1 min-w-0">
                        <div class="avatar avatar-sm bg-warning-lt text-warning rounded-2 flex-shrink-0">
                            <i class="ti ti-pencil fs-3"></i>
                        </div>
                        <div class="min-w-0 flex-grow-1">
                            <h5 class="modal-title fw-bold text-dark mb-0 text-truncate" id="learningEditModalTitle">Edit Learning Record</h5>
                            <small class="text-muted d-block lh-1">Correct employee tracking metrics and progress</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Loading State -->
                <div class="modal-body py-5 text-center" id="learningEditLoading">
                    <div class="spinner-border text-warning mb-3" style="width: 2.5rem; height: 2.5rem;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5 class="fw-bold mb-1">Loading Learning Record...</h5>
                    <p class="text-muted small mb-0">Please wait while we retrieve the record data.</p>
                </div>

                <!-- Error State (when loading fails) -->
                <div class="modal-body py-4" id="learningEditFetchError" style="display: none;">
                    <div class="alert alert-danger d-flex align-items-center gap-2 mb-0 py-3 px-3">
                        <i class="ti ti-alert-circle fs-2 flex-shrink-0"></i>
                        <div class="flex-grow-1 min-w-0">
                            <strong class="d-block small">Failed to Load Learning Record</strong>
                            <span class="small" id="learningEditFetchErrorMessage">Unable to load learning record data. Please try again.</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger px-3 ms-auto" data-bs-dismiss="modal">
                            Close
                        </button>
                    </div>
                </div>

                <!-- Form Content -->
                <div class="modal-body px-4 py-3" id="learningEditContent" style="display: none;">
                    <!-- Error Alert for form submit validation -->
                    <div class="alert alert-danger mb-3 py-2 px-3 small" id="learningEditSubmitError" style="display: none;">
                        <div class="d-flex align-items-start gap-2">
                            <i class="ti ti-alert-circle fs-3 flex-shrink-0 mt-1"></i>
                            <div class="flex-grow-1" id="learningEditSubmitErrorMessage"></div>
                        </div>
                    </div>

                    <!-- 1. READ ONLY CONTEXT: Learning & Employee Info -->
                    <div class="border rounded-3 p-3 mb-3 bg-light owl-modal-card">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-uppercase text-muted fw-bold small" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                                <i class="ti ti-info-circle me-1 text-primary"></i> Learning Information (Read-Only)
                            </span>
                            <div class="d-flex align-items-center gap-1">
                                <span class="badge bg-blue-lt" id="le-content-type">-</span>
                                <span class="badge bg-azure-lt" id="le-content-source">-</span>
                            </div>
                        </div>

                        <h4 class="fw-bold text-dark mb-2 lh-sm" id="le-content-title">-</h4>

                        <div class="row g-2 small mb-2">
                            <div class="col-sm-4">
                                <div class="text-muted">Classification: <strong class="text-dark" id="le-content-classification">-</strong></div>
                            </div>
                            <div class="col-sm-4">
                                <div class="text-muted">Source: <strong class="text-dark" id="le-content-category">-</strong></div>
                            </div>
                            <div class="col-sm-4">
                                <div class="text-muted">Duration: <strong class="text-dark font-monospace" id="le-content-duration">-</strong></div>
                            </div>
                            <div class="col-12" id="le-playlist-container" style="display: none;">
                                <div class="text-muted"><i class="ti ti-list-details me-1"></i>Playlist Name: <strong class="text-dark" id="le-playlist-name">-</strong></div>
                            </div>
                        </div>

                        <div class="row g-2 pt-2 border-top">
                            <div class="col-sm-6">
                                <div class="text-muted small">Employee: <strong class="text-dark" id="le-emp-name">-</strong> (<span class="font-monospace" id="le-emp-id">-</span>)</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted small">Department: <strong class="text-dark" id="le-emp-dept">-</strong></div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. EDITABLE TRACKING FIELDS -->
                    <div class="border rounded-3 p-3 mb-3 bg-white owl-modal-card">
                        <span class="text-uppercase text-muted fw-bold small d-block mb-3" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                            <i class="ti ti-adjustments me-1 text-warning"></i> Editable Tracking Metrics
                        </span>

                        <div class="row g-3">
                            <!-- Progress (%) -->
                            <div class="col-sm-6">
                                <label for="edit-progress" class="form-label fw-bold small mb-1">
                                    Progress (%) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control font-monospace" id="edit-progress" name="progress" min="0" max="100" step="1" required placeholder="0 - 100">
                                    <span class="input-group-text">%</span>
                                </div>
                                <div class="form-text extra-small text-muted" style="font-size: 0.76rem;">
                                    Integer (0 - 100). Setting to 100 marks finished; &lt;100 reopens record.
                                </div>
                                <div class="invalid-feedback d-block small" id="err-edit-progress" style="display: none;"></div>
                            </div>

                            <!-- Current Time (seconds) -->
                            <div class="col-sm-6">
                                <label for="edit-current-time" class="form-label fw-bold small mb-1">
                                    Current Time (seconds) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control font-monospace" id="edit-current-time" name="current_time" min="0" step="1" required placeholder="0">
                                    <span class="input-group-text">sec</span>
                                </div>
                                <div class="form-text extra-small text-muted" style="font-size: 0.76rem;" id="help-edit-current-time">
                                    Current position in seconds (cannot exceed duration).
                                </div>
                                <div class="invalid-feedback d-block small" id="err-edit-current-time" style="display: none;"></div>
                            </div>

                            <!-- Actual Watch (seconds) -->
                            <div class="col-sm-6">
                                <label for="edit-actual-watch" class="form-label fw-bold small mb-1">
                                    Actual Watch (seconds) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control font-monospace" id="edit-actual-watch" name="actual_watch_seconds" min="0" step="1" required placeholder="0">
                                    <span class="input-group-text">sec</span>
                                </div>
                                <div class="form-text extra-small text-muted" style="font-size: 0.76rem;">
                                    Accumulated active watch time in seconds. Recalculates Learning Hours.
                                </div>
                                <div class="invalid-feedback d-block small" id="err-edit-actual-watch" style="display: none;"></div>
                            </div>

                            <!-- AFK Duration (seconds) -->
                            <div class="col-sm-6">
                                <label for="edit-afk-duration" class="form-label fw-bold small mb-1">
                                    AFK Duration (seconds) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control font-monospace" id="edit-afk-duration" name="afk_duration_seconds" min="0" step="1" required placeholder="0">
                                    <span class="input-group-text">sec</span>
                                </div>
                                <div class="form-text extra-small text-muted" style="font-size: 0.76rem;">
                                    Accumulated AFK / idle time in seconds.
                                </div>
                                <div class="invalid-feedback d-block small" id="err-edit-afk-duration" style="display: none;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. NOTICE OF AUTOMATIC DERIVED FIELDS -->
                    <div class="alert alert-info py-2 px-3 small mb-0">
                        <div class="d-flex align-items-start gap-2">
                            <i class="ti ti-info-circle fs-3 flex-shrink-0 mt-1"></i>
                            <span>
                                <strong>System Managed:</strong>
                                Learning Hours, Finish flag, Completed Timestamp, and Status will be automatically recalculated by the backend based on canonical OWL tracking rules.
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer px-4 py-2 bg-light border-top d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 d-flex align-items-center gap-2" id="btn-save-learning-edit">
                        <span class="spinner-border spinner-border-sm" id="edit-save-spinner" style="display: none;" role="status" aria-hidden="true"></span>
                        <span id="edit-save-text">Save Changes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Learning Progress Modal -->
<div class="modal modal-blur fade" id="learningResetModal" tabindex="-1" aria-labelledby="learningResetModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 540px;">
        <div class="modal-content rounded-3 border-0 shadow">
            <!-- Modal Header -->
            <div class="modal-header bg-danger-lt border-bottom px-4 py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-sm bg-danger text-white rounded-circle">
                        <i class="ti ti-rotate-clockwise-2 fs-3"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-danger mb-0" id="learningResetModalTitle">Reset Learning Progress</h5>
                        <div class="text-muted extra-small" style="font-size: 0.76rem;">Reset tracking data back to not started</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Loading Spinner -->
            <div id="learningResetLoading" class="py-5 text-center">
                <div class="spinner-border text-danger" role="status">
                    <span class="visually-hidden">Loading record details...</span>
                </div>
                <div class="text-muted small mt-2">Loading learning record details...</div>
            </div>

            <!-- Fetch Error Notice -->
            <div id="learningResetFetchError" class="p-4" style="display: none;">
                <div class="alert alert-danger mb-0 d-flex align-items-start gap-2">
                    <i class="ti ti-alert-triangle fs-3 flex-shrink-0 mt-1"></i>
                    <div>
                        <strong>Failed to load record details:</strong>
                        <div id="learningResetFetchErrorMessage" class="small mt-1">Unable to load learning record data.</div>
                    </div>
                </div>
            </div>

            <!-- Modal Body Content -->
            <div id="learningResetContent" class="modal-body px-4 py-3" style="display: none;">
                <!-- Error Alert Box during reset submit -->
                <div class="alert alert-danger py-2 px-3 small mb-3" id="learningResetSubmitError" style="display: none;">
                    <div class="d-flex align-items-start gap-2">
                        <i class="ti ti-alert-triangle fs-3 flex-shrink-0 mt-1"></i>
                        <div id="learningResetSubmitErrorMessage"></div>
                    </div>
                </div>

                <input type="hidden" id="reset-source-type" value="">
                <input type="hidden" id="reset-source-id" value="">

                <!-- Explanation / Business Context -->
                <p class="text-muted small mb-3">
                    This will reset the learner's progress and tracking data for this learning record back to the initial state. <strong>The enrollment record itself will not be deleted.</strong>
                </p>

                <!-- Context Summary Card -->
                <div class="border rounded-3 p-3 mb-3 bg-white owl-modal-card">
                    <span class="text-uppercase text-muted fw-bold small d-block mb-2" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                        <i class="ti ti-user me-1 text-primary"></i> Target Record
                    </span>
                    <div class="row g-2 small">
                        <div class="col-sm-6">
                            <span class="text-muted d-block extra-small" style="font-size: 0.75rem;">Employee</span>
                            <strong class="text-dark" id="lr-emp-name">-</strong>
                            <span class="text-muted font-monospace d-block extra-small" id="lr-emp-id">-</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted d-block extra-small" style="font-size: 0.75rem;">Source</span>
                            <span class="badge bg-secondary-lt fw-bold" id="lr-content-source">-</span>
                            <div class="text-muted extra-small mt-1" id="lr-playlist-container" style="display: none;">
                                <i class="ti ti-folders me-1"></i><span id="lr-playlist-name">-</span>
                            </div>
                        </div>
                        <div class="col-12 mt-2">
                            <span class="text-muted d-block extra-small" style="font-size: 0.75rem;">Learning Title</span>
                            <strong class="text-dark" id="lr-content-title">-</strong>
                        </div>
                    </div>

                    <div class="row g-2 pt-2 mt-2 border-top small">
                        <div class="col-4">
                            <span class="text-muted d-block extra-small" style="font-size: 0.75rem;">Current Progress</span>
                            <span class="fw-bold font-monospace text-dark" id="lr-current-progress">0%</span>
                        </div>
                        <div class="col-4">
                            <span class="text-muted d-block extra-small" style="font-size: 0.75rem;">Learning Hours</span>
                            <span class="fw-bold font-monospace text-dark" id="lr-learning-hours">0.0000 h</span>
                        </div>
                        <div class="col-4">
                            <span class="text-muted d-block extra-small" style="font-size: 0.75rem;">Status</span>
                            <span class="badge" id="lr-status-badge">-</span>
                        </div>
                    </div>
                </div>

                <!-- Dynamic Warning 1: Completed Record Alert -->
                <div class="alert alert-warning py-2 px-3 small mb-2" id="lr-completed-warning" style="display: none;">
                    <div class="d-flex align-items-start gap-2">
                        <i class="ti ti-alert-triangle fs-3 flex-shrink-0 mt-1"></i>
                        <span>
                            <strong>Completion Warning:</strong> This learning record is currently completed. Resetting it will remove the completion from Learning Record and recalculate the learner's Learning Hours.
                        </span>
                    </div>
                </div>

                <!-- Dynamic Warning 2: Playlist Parent Alert -->
                <div class="alert alert-info py-2 px-3 small mb-0" id="lr-playlist-warning" style="display: none;">
                    <div class="d-flex align-items-start gap-2">
                        <i class="ti ti-info-circle fs-3 flex-shrink-0 mt-1"></i>
                        <span>
                            <strong>Playlist Impact:</strong> Resetting this module may also change the completion status of its parent playlist.
                        </span>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer px-4 py-2 bg-light border-top d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="button" class="btn btn-danger btn-sm px-4 d-flex align-items-center gap-2" id="btn-confirm-learning-reset">
                    <span class="spinner-border spinner-border-sm" id="reset-confirm-spinner" style="display: none;" role="status" aria-hidden="true"></span>
                    <span id="reset-confirm-text">Reset Progress</span>
                </button>
            </div>
        </div>
    </div>
</div>
