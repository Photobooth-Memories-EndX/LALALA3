<!-- Online Learning Record Detail Modal (4 Sections: Employee, Content, Tracking, Completion) -->
<div class="modal fade" id="onlineTrainingDetailModal" tabindex="-1" aria-labelledby="onlineTrainingDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-2">
                    <span class="avatar rounded-3" style="background-color: #e0f2fe; color: #0284c7; width: 42px; height: 42px;">
                        <i class="ti ti-device-laptop fs-2"></i>
                    </span>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="onlineTrainingDetailModalLabel">Online Learning Record Detail</h5>
                        <div class="text-muted extra-small" style="font-size: 0.75rem;">KP OWL (Online Learning &amp; LMS Tracking)</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body py-3" id="onlineTrainingDetailModalBody">
                <!-- Loading State -->
                <div class="py-5 text-center text-muted" id="online-detail-loading">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 small">Loading online learning details...</div>
                </div>

                <!-- Error State -->
                <div class="py-5 text-center text-danger" id="online-detail-error" style="display: none;">
                    <div class="avatar avatar-lg rounded-circle bg-danger-lt mx-auto mb-2 text-danger">
                        <i class="ti ti-alert-circle fs-1"></i>
                    </div>
                    <div class="fw-bold mb-1" id="online-detail-error-title">Unable to Load Record</div>
                    <div class="small text-muted" id="online-detail-error-message">Record not found or access denied.</div>
                </div>

                <!-- Content Container (populated dynamically via JS) -->
                <div id="online-detail-content" style="display: none;">
                    <!-- Badge Header -->
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-3 border-bottom">
                        <div>
                            <h4 class="fw-bold mb-1 text-dark" id="online-modal-title">-</h4>
                            <div class="text-muted small" id="online-modal-playlist-sub" style="display: none;"></div>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge" id="online-modal-source-badge" style="background-color: #e0f2fe; color: #0284c7; font-weight: 600; font-size: 0.75rem; border-radius: 6px; padding: 4px 10px;">KP OWL</span>
                            <span class="badge bg-light text-secondary border px-2.5 py-1.5" id="online-modal-type-badge">-</span>
                            <span class="badge bg-success-lt px-2.5 py-1.5 fw-bold" id="online-modal-status-badge">-</span>
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- Section 1: Employee Information -->
                        <div class="col-12 col-md-6">
                            <div class="card border-0 bg-light p-3 rounded-3 h-100">
                                <div class="fw-bold small text-dark mb-2 text-uppercase d-flex align-items-center justify-content-between" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    <span><i class="ti ti-user me-1 text-primary"></i> 1. Employee Information</span>
                                    <a href="#" id="online-modal-profile-link" target="_blank" class="text-primary text-decoration-none extra-small" style="font-size: 0.725rem; display: none;">
                                        View Profile <i class="ti ti-external-link"></i>
                                    </a>
                                </div>
                                <div class="row g-2 small">
                                    <div class="col-6">
                                        <span class="text-muted d-block">Employee ID:</span>
                                        <span class="fw-semibold text-dark" id="online-modal-employee-id">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Name:</span>
                                        <span class="fw-semibold text-dark" id="online-modal-employee-name">-</span>
                                    </div>
                                    <div class="col-12">
                                        <span class="text-muted d-block">Email:</span>
                                        <span class="fw-semibold text-dark text-break" id="online-modal-employee-email">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Department:</span>
                                        <span class="fw-semibold text-dark" id="online-modal-employee-department">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Division:</span>
                                        <span class="fw-semibold text-dark" id="online-modal-employee-division">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Team / Unit:</span>
                                        <span class="fw-semibold text-dark" id="online-modal-employee-team">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Company:</span>
                                        <span class="fw-semibold text-dark" id="online-modal-employee-company">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Grade:</span>
                                        <span class="fw-semibold text-dark" id="online-modal-employee-grade">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Job Position:</span>
                                        <span class="fw-semibold text-dark" id="online-modal-employee-job-position">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 2: Content & Curriculum Information -->
                        <div class="col-12 col-md-6">
                            <div class="card border-0 bg-light p-3 rounded-3 h-100">
                                <div class="fw-bold small text-dark mb-2 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    <i class="ti ti-book me-1 text-primary"></i> 2. Content &amp; Curriculum
                                </div>
                                <div class="row g-2 small">
                                    <div class="col-12">
                                        <span class="text-muted d-block">Course Title:</span>
                                        <span class="fw-semibold text-dark" id="online-modal-content-title">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Content Type:</span>
                                        <span class="fw-semibold text-dark" id="online-modal-content-type">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Main Classification:</span>
                                        <span class="fw-semibold text-dark" id="online-modal-classification">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Level:</span>
                                        <span class="fw-semibold text-dark" id="online-modal-level">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Batch:</span>
                                        <span class="fw-semibold text-dark" id="online-modal-batch">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Enrollment Type:</span>
                                        <span class="fw-semibold text-dark" id="online-modal-enrollment-type">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Course Duration:</span>
                                        <span class="fw-semibold text-dark" id="online-modal-catalog-duration">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Learning Hours:</span>
                                        <span class="fw-semibold text-dark" id="online-modal-catalog-hours">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Author / Publisher:</span>
                                        <span class="fw-semibold text-dark" id="online-modal-author">-</span>
                                    </div>
                                    <!-- Playlist specific info container -->
                                    <div class="col-12 pt-1 border-top mt-1" id="online-modal-playlist-container" style="display: none;">
                                        <div class="row g-2">
                                            <div class="col-8">
                                                <span class="text-muted d-block">Playlist:</span>
                                                <span class="fw-semibold text-purple" id="online-modal-playlist-title">-</span>
                                            </div>
                                            <div class="col-4">
                                                <span class="text-muted d-block">Playlist Duration:</span>
                                                <span class="fw-semibold text-dark" id="online-modal-playlist-duration">-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Learning Activity & Tracking -->
                        <div class="col-12 col-md-6">
                            <div class="card border-0 bg-light p-3 rounded-3 h-100">
                                <div class="fw-bold small text-dark mb-2 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    <i class="ti ti-chart-bar me-1 text-primary"></i> 3. Learning Activity &amp; Tracking
                                </div>
                                <div class="row g-2 small">
                                    <div class="col-12 mb-1">
                                        <span class="text-muted d-block mb-1">Progress Completion:</span>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-fill" style="height: 8px;">
                                                <div class="progress-bar bg-primary" id="online-modal-progress-bar" style="width: 0%;"></div>
                                            </div>
                                            <span class="fw-bold text-dark" id="online-modal-progress-text">0%</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Finish Status:</span>
                                        <span class="fw-semibold text-dark" id="online-modal-finish-status">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Current Cursor Position:</span>
                                        <span class="fw-semibold text-dark" id="online-modal-current-time">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Tracked Duration:</span>
                                        <span class="fw-semibold text-dark" id="online-modal-tracked-duration">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Actual Watch Time:</span>
                                        <span class="fw-semibold text-dark" id="online-modal-actual-watch">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">AFK / Idle Time:</span>
                                        <span class="fw-semibold text-dark" id="online-modal-afk-duration">-</span>
                                    </div>
                                    <div class="col-6" id="online-modal-pages-container">
                                        <span class="text-muted d-block">Pages Read:</span>
                                        <span class="fw-semibold text-dark" id="online-modal-pages">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 4: Learning Hours & Completion Audit -->
                        <div class="col-12 col-md-6">
                            <div class="card border-0 bg-light p-3 rounded-3 h-100">
                                <div class="fw-bold small text-dark mb-2 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    <i class="ti ti-clock-check me-1 text-primary"></i> 4. Learning Hours &amp; Completion
                                </div>
                                <div class="row g-2 small">
                                    <div class="col-6">
                                        <span class="text-muted d-block">Credited Learning Hours:</span>
                                        <span class="fw-bold text-success fs-4 d-block mt-0.5" id="online-modal-credited-hours">0.00 Hours</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Learning Audit Status:</span>
                                        <span class="badge bg-blue-lt mt-1 px-2.5 py-1 fw-bold" id="online-modal-learning-status">Valid</span>
                                    </div>
                                    <div class="col-12 pt-2 border-top">
                                        <div class="row g-2">
                                            <div class="col-4">
                                                <span class="text-muted d-block">Enrolled At:</span>
                                                <span class="fw-semibold text-dark" id="online-modal-enrolled-at">-</span>
                                            </div>
                                            <div class="col-4">
                                                <span class="text-muted d-block">Completed At:</span>
                                                <span class="fw-semibold text-dark" id="online-modal-completed-at">-</span>
                                            </div>
                                            <div class="col-4">
                                                <span class="text-muted d-block">Last Activity:</span>
                                                <span class="fw-semibold text-dark" id="online-modal-last-activity">-</span>
                                            </div>
                                            <div class="col-6 pt-2">
                                                <span class="text-muted d-block">Attendance Reason:</span>
                                                <span class="fw-semibold text-dark" id="online-modal-attendance-reason">-</span>
                                            </div>
                                            <div class="col-6 pt-2">
                                                <span class="text-muted d-block">Certificate Status:</span>
                                                <span class="fw-semibold text-dark" id="online-modal-certificate-status">-</span>
                                            </div>
                                            <div class="col-6 pt-1">
                                                <span class="text-muted d-block">L2 Status:</span>
                                                <span class="fw-semibold text-dark" id="online-modal-l2-status">-</span>
                                            </div>
                                            <div class="col-6 pt-1">
                                                <span class="text-muted d-block">L3 Status:</span>
                                                <span class="fw-semibold text-dark" id="online-modal-l3-status">-</span>
                                            </div>
                                            <div class="col-3 pt-1">
                                                <span class="text-muted d-block">Bond Status:</span>
                                                <span class="fw-semibold text-dark" id="online-modal-bond-status">No</span>
                                            </div>
                                            <div class="col-3 pt-1">
                                                <span class="text-muted d-block">Bond Duration:</span>
                                                <span class="fw-semibold text-dark" id="online-modal-bond-duration">-</span>
                                            </div>
                                            <div class="col-3 pt-1">
                                                <span class="text-muted d-block">Bond End Date:</span>
                                                <span class="fw-semibold text-dark" id="online-modal-bond-end-date">-</span>
                                            </div>
                                            <div class="col-3 pt-1">
                                                <span class="text-muted d-block">Bond Amount (IDR):</span>
                                                <span class="fw-semibold text-dark font-monospace" id="online-modal-bond-amount">-</span>
                                            </div>
                                        </div>
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
