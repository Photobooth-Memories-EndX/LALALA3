<div class="modal fade" id="kp-owl-detail-modal" tabindex="-1" aria-labelledby="kpOwlDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            {{-- Modal Header --}}
            <div class="modal-header py-3 px-4 bg-primary text-white">
                <div class="d-flex align-items-center">
                    <span class="avatar avatar-sm rounded-circle bg-white text-primary me-3">
                        <i class="ti ti-file-certificate fs-3"></i>
                    </span>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="kpOwlDetailModalLabel">Learning Record Details</h5>
                        <div class="text-white-50 small" id="detail-modal-subtitle">KP OWL Online Record</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            {{-- Modal Body with 6 Grouped Sections --}}
            <div class="modal-body p-4 bg-light">
                <div class="row g-3">
                    {{-- 1. Employee Information --}}
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header py-2.5 px-3 bg-white border-bottom">
                                <h6 class="card-title mb-0 fw-bold text-dark">
                                    <i class="ti ti-user me-1.5 text-primary"></i>Employee Information
                                </h6>
                            </div>
                            <div class="card-body p-3">
                                <div class="row g-2 small">
                                    <div class="col-sm-6"><span class="text-muted">Employee ID:</span> <span class="fw-bold font-monospace text-dark ms-1" id="detail-emp-id">-</span></div>
                                    <div class="col-sm-6"><span class="text-muted">Employee Name:</span> <span class="fw-semibold text-dark ms-1" id="detail-emp-name">-</span></div>
                                    <div class="col-sm-6"><span class="text-muted">Grade:</span> <span class="badge bg-blue-lt text-blue ms-1" id="detail-emp-grade">-</span></div>
                                    <div class="col-sm-6"><span class="text-muted">Job Position:</span> <span class="fw-medium text-dark ms-1" id="detail-emp-position">-</span></div>
                                    <div class="col-sm-6"><span class="text-muted">Team:</span> <span class="fw-medium text-dark ms-1" id="detail-emp-team">-</span></div>
                                    <div class="col-sm-6"><span class="text-muted">Department:</span> <span class="fw-medium text-dark ms-1" id="detail-emp-department">-</span></div>
                                    <div class="col-sm-12"><span class="text-muted">Company:</span> <span class="fw-medium text-dark ms-1" id="detail-emp-company">-</span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 2. Learning Information --}}
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header py-2.5 px-3 bg-white border-bottom">
                                <h6 class="card-title mb-0 fw-bold text-dark">
                                    <i class="ti ti-book me-1.5 text-primary"></i>Learning Information
                                </h6>
                            </div>
                            <div class="card-body p-3">
                                <div class="row g-2 small">
                                    <div class="col-sm-12"><span class="text-muted">Course Title:</span> <span class="fw-bold text-dark ms-1" id="detail-course-title">-</span></div>
                                    <div class="col-sm-6"><span class="text-muted">Sub Course Title:</span> <span class="text-muted ms-1" id="detail-sub-course-title">-</span></div>
                                    <div class="col-sm-6"><span class="text-muted">Type / Category:</span> <span class="badge bg-azure-lt text-azure ms-1" id="detail-type-cat">-</span></div>
                                    <div class="col-sm-6"><span class="text-muted">Level:</span> <span class="text-muted ms-1" id="detail-level">-</span></div>
                                    <div class="col-sm-6"><span class="text-muted">Batch:</span> <span class="text-muted ms-1" id="detail-batch">-</span></div>
                                    <div class="col-sm-6"><span class="text-muted">Main Classification:</span> <span class="fw-medium text-dark ms-1" id="detail-main-class">-</span></div>
                                    <div class="col-sm-6"><span class="text-muted">Instructor:</span> <span class="fw-medium text-dark ms-1" id="detail-instructor">-</span></div>
                                    <div class="col-sm-6"><span class="text-muted">Provider:</span> <span class="fw-medium text-dark ms-1" id="detail-provider">Internal (KP-OWL)</span></div>
                                    <div class="col-sm-6"><span class="text-muted">Sub Provider:</span> <span class="text-muted ms-1" id="detail-sub-provider">-</span></div>
                                    <div class="col-sm-6"><span class="text-muted">Course Duration:</span> <span class="font-monospace text-secondary ms-1" id="detail-course-duration">-</span></div>
                                    <div class="col-sm-6"><span class="text-muted">Total Recognized Duration:</span> <span class="font-monospace fw-bold text-primary ms-1" id="detail-recognized-duration">-</span></div>
                                    <div class="col-sm-6"><span class="text-muted">Learning Hours:</span> <span class="fw-bold text-dark ms-1" id="detail-learning-hours">-</span></div>
                                    <div class="col-sm-6"><span class="text-muted">Total Engagement Time:</span> <span class="font-monospace text-dark ms-1" id="detail-engagement-time">-</span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 3. Attendance Information --}}
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header py-2.5 px-3 bg-white border-bottom">
                                <h6 class="card-title mb-0 fw-bold text-dark">
                                    <i class="ti ti-calendar-check me-1.5 text-success"></i>Attendance Information
                                </h6>
                            </div>
                            <div class="card-body p-3 small">
                                <div class="mb-2"><span class="text-muted">Attendance Status:</span> <span class="badge bg-success-lt text-success fw-bold ms-1" id="detail-attendance">-</span></div>
                                <div class="mb-2"><span class="text-muted">Attendance Reason:</span> <span class="text-muted ms-1" id="detail-attendance-reason">-</span></div>
                                <div><span class="text-muted">Learning Period:</span> <div class="fw-medium text-dark mt-1" id="detail-period">-</div></div>
                            </div>
                        </div>
                    </div>

                    {{-- 4. Service Bond Information --}}
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header py-2.5 px-3 bg-white border-bottom">
                                <h6 class="card-title mb-0 fw-bold text-dark">
                                    <i class="ti ti-link me-1.5 text-warning"></i>Bond Information
                                </h6>
                            </div>
                            <div class="card-body p-3 small">
                                <div class="mb-2"><span class="text-muted">Bond Status:</span> <span class="badge bg-secondary-lt text-secondary ms-1" id="detail-bond-status">No</span></div>
                                <div class="mb-2"><span class="text-muted">Bond Duration:</span> <span class="text-muted ms-1" id="detail-bond-duration">-</span></div>
                                <div class="mb-2"><span class="text-muted">Bond End Date:</span> <span class="text-muted ms-1" id="detail-bond-end-date">-</span></div>
                                <div><span class="text-muted">Bond Amount (IDR):</span> <span class="text-muted ms-1" id="detail-bond-amount">-</span></div>
                            </div>
                        </div>
                    </div>

                    {{-- 5. Assessment Information --}}
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header py-2.5 px-3 bg-white border-bottom">
                                <h6 class="card-title mb-0 fw-bold text-dark">
                                    <i class="ti ti-clipboard-check me-1.5 text-info"></i>Assessment Information
                                </h6>
                            </div>
                            <div class="card-body p-3 small">
                                <div class="mb-2"><span class="text-muted">Score (L1) - Course:</span> <span class="text-muted ms-1" id="detail-score-l1">-</span></div>
                                <div class="mb-2"><span class="text-muted">Score (L2):</span> <span class="fw-bold text-dark ms-1" id="detail-score-l2">-</span></div>
                                <div class="mb-2"><span class="text-muted">L2 Status:</span> <span class="badge bg-blue-lt text-blue ms-1" id="detail-l2-status">-</span></div>
                                <div class="mb-2"><span class="text-muted">Score (L3):</span> <span class="text-muted ms-1" id="detail-score-l3">-</span></div>
                                <div><span class="text-muted">L3 Status:</span> <span class="text-muted ms-1" id="detail-l3-status">-</span></div>
                            </div>
                        </div>
                    </div>

                    {{-- 6. Certificate Information --}}
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header py-2.5 px-3 bg-white border-bottom">
                                <h6 class="card-title mb-0 fw-bold text-dark">
                                    <i class="ti ti-certificate me-1.5 text-teal"></i>Certificate Information
                                </h6>
                            </div>
                            <div class="card-body p-3 small">
                                <div class="mb-2"><span class="text-muted">Availability:</span> <span class="badge bg-teal-lt text-teal ms-1" id="detail-cert-avail">-</span></div>
                                <div class="mb-2"><span class="text-muted">Institution:</span> <span class="fw-medium text-dark ms-1" id="detail-cert-institution">-</span></div>
                                <div class="mb-2"><span class="text-muted">Issued Date:</span> <span class="fw-medium text-dark ms-1" id="detail-cert-issued">-</span></div>
                                <div><span class="text-muted">Expired Date:</span> <span class="text-muted ms-1" id="detail-cert-expired">-</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="modal-footer py-2.5 px-4 bg-white border-top d-flex justify-content-end">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
