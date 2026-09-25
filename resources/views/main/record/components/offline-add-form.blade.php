<!-- OFFLINE ADD TRAINING FORM (PHASE 2F.3.1 DEDICATED PAGE) -->
<form id="offline-manual-add-form" onsubmit="return false;" novalidate>
    
    <!-- ============================================================= -->
    <!-- SECTION 1: TRAINING INFORMATION -->
    <!-- ============================================================= -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 form-section" id="section-1-training-info">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                <span class="badge rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.85rem;">1</span>
                <h4 class="fw-bold mb-0 text-dark">Training Information</h4>
            </div>

            <!-- SUBSECTION: GENERAL INFORMATION -->
            <div class="subsection-header p-2 px-3 rounded-2 mb-3 bg-light-subtle border-start border-primary border-3">
                <span class="fw-bold text-primary small text-uppercase">General Information</span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12 col-md-8">
                    <label for="add-course-name" class="form-label small fw-semibold">Course Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control rounded-2" id="add-course-name" name="course_name" placeholder="e.g. High Voltage Safety &amp; Operation" required>
                </div>
                <div class="col-12 col-md-4">
                    <label for="add-batch" class="form-label small fw-semibold">Batch</label>
                    <input type="text" class="form-control rounded-2" id="add-batch" name="batch" placeholder="e.g. Batch 1">
                </div>
                <div class="col-12 col-md-6">
                    <label for="add-sub-course-name" class="form-label small fw-semibold">Sub Course Name</label>
                    <input type="text" class="form-control rounded-2" id="add-sub-course-name" name="sub_course_name" placeholder="e.g. Switchgear Troubleshooting Module">
                </div>
                <div class="col-12 col-md-6">
                    <label for="add-type" class="form-label small fw-semibold">Type</label>
                    <select class="form-select rounded-2" id="add-type" name="type">
                        <option value="">Select Type</option>
                        <option value="In-House" selected>In-House</option>
                        <option value="Public">Public</option>
                        <option value="Certification">Certification</option>
                        <option value="Workshop">Workshop</option>
                        <option value="Seminar">Seminar</option>
                        <option value="Classroom">Classroom</option>
                        <option value="External">External</option>
                        <option value="Overseas">Overseas</option>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label for="add-category" class="form-label small fw-semibold">Category</label>
                    <input type="text" class="form-control rounded-2" id="add-category" name="category" placeholder="e.g. Technical Competency" list="category-datalist">
                    <datalist id="category-datalist">
                        <option value="Technical Competency">
                        <option value="Leadership Program">
                        <option value="Quality &amp; Safety">
                        <option value="Management Skill">
                        <option value="General Training">
                    </datalist>
                </div>
                <div class="col-12 col-md-6">
                    <label for="add-level" class="form-label small fw-semibold">Level</label>
                    <select class="form-select rounded-2" id="add-level" name="level">
                        <option value="">Select Level</option>
                        <option value="Basic">Basic</option>
                        <option value="Intermediate">Intermediate</option>
                        <option value="Advanced">Advanced</option>
                        <option value="All">All</option>
                    </select>
                </div>
            </div>

            <!-- SUBSECTION: CLASSIFICATION & KPI -->
            <div class="subsection-header p-2 px-3 rounded-2 mb-3 bg-light-subtle border-start border-primary border-3">
                <span class="fw-bold text-primary small text-uppercase">Classification &amp; KPI</span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12 col-md-6">
                    <label for="add-main-classification" class="form-label small fw-semibold">Main Classification</label>
                    <select class="form-select rounded-2" id="add-main-classification" name="main_classification">
                        <option value="">Select Classification</option>
                        <option value="Competency">Competency</option>
                        <option value="Leadership">Leadership</option>
                        <option value="Management">Management</option>
                        <option value="Technical">Technical</option>
                        <option value="Quality & Safety">Quality &amp; Safety</option>
                        <option value="General">General</option>
                        <option value="Basic & Common Duties">Basic &amp; Common Duties</option>
                        <option value="ISO Standard">ISO Standard</option>
                        <option value="Innovation">Innovation</option>
                        <option value="Job Functional">Job Functional</option>
                        <option value="Language">Language</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Other">Other</option>
                        <option value="SHE">SHE</option>
                        <option value="Technology">Technology</option>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label for="add-sub-classification" class="form-label small fw-semibold">Sub Classification</label>
                    <input type="text" class="form-control rounded-2" id="add-sub-classification" name="sub_classification" placeholder="e.g. Plant Maintenance">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold d-block">Counted as '26 Corporate KPI</label>
                    <div class="d-flex align-items-center gap-3 pt-1">
                        <div class="form-check form-check-inline mb-0">
                            <input class="form-check-input" type="radio" name="counted_corporate_kpi" id="add-counted-kpi-yes" value="1" checked>
                            <label class="form-check-label small" for="add-counted-kpi-yes">Yes</label>
                        </div>
                        <div class="form-check form-check-inline mb-0">
                            <input class="form-check-input" type="radio" name="counted_corporate_kpi" id="add-counted-kpi-no" value="0">
                            <label class="form-check-label small" for="add-counted-kpi-no">No</label>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <label for="add-sub-corporate-kpi" class="form-label small fw-semibold">Sub '26 Corporate KPI</label>
                    <input type="text" class="form-control rounded-2" id="add-sub-corporate-kpi" name="sub_corporate_kpi" placeholder="e.g. Safety Incident Reduction">
                </div>
            </div>

            <!-- SUBSECTION: DELIVERY & PROVIDER -->
            <div class="subsection-header p-2 px-3 rounded-2 mb-3 bg-light-subtle border-start border-primary border-3">
                <span class="fw-bold text-primary small text-uppercase">Delivery &amp; Provider</span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12 col-md-6">
                    <label for="add-delivery-method" class="form-label small fw-semibold">Delivery Method</label>
                    <select class="form-select rounded-2" id="add-delivery-method" name="delivery_method">
                        <option value="">Select Delivery Method</option>
                        <option value="Classroom" selected>Classroom</option>
                        <option value="Practical">Practical</option>
                        <option value="Hybrid">Hybrid</option>
                        <option value="Workshop">Workshop</option>
                        <option value="Hands-on">Hands-on</option>
                        <option value="Seminar">Seminar</option>
                        <option value="E-Learning">E-Learning</option>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label for="add-sub-delivery-method" class="form-label small fw-semibold">Sub Delivery Method</label>
                    <input type="text" class="form-control rounded-2" id="add-sub-delivery-method" name="sub_delivery_method" placeholder="e.g. Hands-on Lab">
                </div>
                <div class="col-12 col-md-6">
                    <label for="add-provider" class="form-label small fw-semibold">Provider</label>
                    <select class="form-select rounded-2" id="add-provider" name="provider">
                        <option value="">Select Provider</option>
                        <option value="Internal">Internal</option>
                        <option value="External" selected>External</option>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label for="add-sub-provider" class="form-label small fw-semibold">Sub Provider</label>
                    <select class="form-select rounded-2" id="add-sub-provider" name="sub_provider">
                        <option value="">Select Sub Provider</option>
                        <option value="Vendor" selected>Vendor</option>
                        <option value="University">University</option>
                        <option value="Certified Center">Certified Center</option>
                        <option value="Internal Trainer">Internal Trainer</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="col-12">
                    <label for="add-provider-name" class="form-label small fw-semibold">Provider Name</label>
                    <input type="text" class="form-control rounded-2" id="add-provider-name" name="provider_name" placeholder="e.g. PT Schneider Indonesia">
                </div>
            </div>

            <!-- SUBSECTION: SCHEDULE & DURATION -->
            <div class="subsection-header p-2 px-3 rounded-2 mb-3 bg-light-subtle border-start border-primary border-3">
                <span class="fw-bold text-primary small text-uppercase">Schedule &amp; Duration</span>
            </div>

            <div class="row g-3">
                <div class="col-12 col-sm-6 col-md-3">
                    <label for="add-duration" class="form-label small fw-semibold">Duration</label>
                    <input type="text" class="form-control rounded-2" id="add-duration" name="duration" placeholder="12:00:00" value="08:00:00">
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label for="add-learning-hours" class="form-label small fw-semibold">Learning Hours</label>
                    <input type="number" step="0.1" min="0" class="form-control rounded-2" id="add-learning-hours" name="learning_hours" placeholder="12.00" value="8.0">
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label for="add-start-date" class="form-label small fw-semibold">Start Date</label>
                    <input type="date" class="form-control rounded-2" id="add-start-date" name="start_date">
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label for="add-finish-date" class="form-label small fw-semibold">Finish Date</label>
                    <input type="date" class="form-control rounded-2" id="add-finish-date" name="finish_date">
                </div>
                <div class="col-12 col-sm-6 col-md-4">
                    <label for="add-month" class="form-label small fw-semibold">Month</label>
                    <input type="text" class="form-control rounded-2 bg-light text-muted" id="add-month" readonly placeholder="Auto from Start Date">
                </div>
                <div class="col-12 col-sm-6 col-md-4">
                    <label for="add-venue" class="form-label small fw-semibold">Venue</label>
                    <input type="text" class="form-control rounded-2" id="add-venue" name="venue" placeholder="e.g. Auditorium Hall B">
                </div>
                <div class="col-12 col-md-4">
                    <label for="add-instructor" class="form-label small fw-semibold">Instructor</label>
                    <input type="text" class="form-control rounded-2" id="add-instructor" name="instructor" placeholder="e.g. Ir. Gunawan, M.T.">
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================= -->
    <!-- SECTION 2: PARTICIPANTS -->
    <!-- ============================================================= -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 form-section" id="section-2-participants">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.85rem;">2</span>
                    <h4 class="fw-bold mb-0 text-dark">Participants</h4>
                </div>
                <span class="badge bg-blue-lt fw-semibold px-2 py-1" id="badge-participants-count" style="font-size: 0.85rem;">0 Enrolled</span>
            </div>
            <p class="text-muted small mb-3">Search and add employees to this training event, or input manually for non-LMS participants.</p>

            <!-- SEARCH BAR & MANUAL TOGGLE -->
            <div class="card border p-3 rounded-3 mb-3 bg-light-subtle">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-8 position-relative">
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="ti ti-search text-muted"></i></span>
                            <input type="text" class="form-control" id="participant-search-input" placeholder="Search Employee ID, NIP, or Name..." autocomplete="off">
                            <button type="button" class="btn btn-primary px-3" id="btn-search-participant">Search</button>
                        </div>
                        <!-- Autocomplete Results Dropdown -->
                        <div class="dropdown-menu w-100 shadow p-1 mt-1" id="participant-search-results" style="display: none; max-height: 240px; overflow-y: auto; z-index: 1050;">
                            <!-- Injected dynamically -->
                        </div>
                    </div>
                    <div class="col-12 col-md-4 text-md-end">
                        <button type="button" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1.5" id="btn-toggle-manual-participant">
                            <i class="ti ti-user-plus"></i>
                            <span>Input Manually</span>
                        </button>
                    </div>
                </div>

                <!-- INLINE MANUAL PARTICIPANT FORM (COLLAPSIBLE) -->
                <div id="manual-participant-card" class="mt-3 p-3 bg-white rounded-3 border" style="display: none;">
                    <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
                        <span class="fw-semibold small text-primary">Manual Participant Information</span>
                        <button type="button" class="btn-close btn-sm" id="btn-cancel-manual-participant" aria-label="Close"></button>
                    </div>
                    <div class="row g-2">
                        <div class="col-12 col-sm-6 col-md-3">
                            <label class="form-label extra-small fw-semibold mb-1">Employee ID / NIP <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm rounded-2" id="manual-emp-id" placeholder="e.g. 100234">
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <label class="form-label extra-small fw-semibold mb-1">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm rounded-2" id="manual-emp-name" placeholder="Full Name">
                        </div>
                        <div class="col-12 col-sm-6 col-md-2">
                            <label class="form-label extra-small fw-semibold mb-1">Grade</label>
                            <input type="text" class="form-control form-control-sm rounded-2" id="manual-emp-grade" placeholder="e.g. 4A">
                        </div>
                        <div class="col-12 col-sm-6 col-md-4">
                            <label class="form-label extra-small fw-semibold mb-1">Job Position</label>
                            <input type="text" class="form-control form-control-sm rounded-2" id="manual-emp-position" placeholder="Job Title">
                        </div>
                        <div class="col-12 col-sm-6 col-md-4">
                            <label class="form-label extra-small fw-semibold mb-1">Team</label>
                            <input type="text" class="form-control form-control-sm rounded-2" id="manual-emp-team" placeholder="e.g. Rolling Mill 1">
                        </div>
                        <div class="col-12 col-sm-6 col-md-4">
                            <label class="form-label extra-small fw-semibold mb-1">Department</label>
                            <input type="text" class="form-control form-control-sm rounded-2" id="manual-emp-dept" placeholder="e.g. Maintenance Dept">
                        </div>
                        <div class="col-12 col-sm-6 col-md-4">
                            <label class="form-label extra-small fw-semibold mb-1">Company</label>
                            <input type="text" class="form-control form-control-sm rounded-2" id="manual-emp-company" value="PT Krakatau Posco" placeholder="Company Entity">
                        </div>
                    </div>
                    <div class="text-end mt-2">
                        <button type="button" class="btn btn-sm btn-primary px-3" id="btn-add-manual-participant">
                            <i class="ti ti-check me-1"></i> Add to Training
                        </button>
                    </div>
                </div>
            </div>

            <!-- SELECTED PARTICIPANTS TABLE -->
            <div class="table-responsive border rounded-3">
                <table class="table table-hover align-middle mb-0" id="selected-participants-table">
                    <thead class="bg-light text-muted extra-small text-uppercase">
                        <tr>
                            <th style="width: 40px;" class="text-center">No</th>
                            <th style="width: 120px;">Employee ID</th>
                            <th>Name</th>
                            <th style="width: 80px;">Grade</th>
                            <th>Job Position</th>
                            <th>Team</th>
                            <th>Department</th>
                            <th>Company</th>
                            <th class="text-end" style="width: 60px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="selected-participants-tbody">
                        <tr id="empty-participants-row">
                            <td colspan="9" class="text-center text-muted py-4 small">
                                <i class="ti ti-users fs-2 d-block mb-1 text-secondary"></i>
                                No participants added yet. Search employee or click Input Manually.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ============================================================= -->
    <!-- SECTION 3: LEARNING RESULTS -->
    <!-- ============================================================= -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 form-section" id="section-3-results">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.85rem;">3</span>
                    <h4 class="fw-bold mb-0 text-dark">Learning Results</h4>
                </div>
                <span class="text-muted small">Configure individual results, attendance &amp; scores per participant</span>
            </div>

            <!-- PARTICIPANT SELECTOR SWITCHER -->
            <div class="card border p-2 px-3 rounded-3 mb-3 bg-light-subtle d-flex flex-row align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <label for="select-active-participant" class="small fw-semibold mb-0 text-nowrap">Active Participant:</label>
                    <select class="form-select form-select-sm rounded-2" id="select-active-participant" style="min-width: 240px; max-width: 360px;">
                        <option value="">No participants enrolled</option>
                    </select>
                    <span class="text-muted extra-small" id="participant-indicator-text">0 of 0</span>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-2" id="btn-prev-participant" title="Previous Participant" disabled>
                        <i class="ti ti-chevron-left"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary px-2" id="btn-next-participant" title="Next Participant" disabled>
                        <i class="ti ti-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- EMPTY NOTICE (IF 0 PARTICIPANTS) -->
            <div id="no-participants-alert" class="alert alert-info d-flex align-items-center gap-2 mb-0">
                <i class="ti ti-info-circle fs-3"></i>
                <div class="small">Please enroll at least one participant in Section 2 above to configure learning results.</div>
            </div>

            <!-- ACTIVE PARTICIPANT RESULTS FORM -->
            <div id="active-participant-form-container" style="display: none;">
                <!-- SUBSECTION: LEARNING PROGRESS -->
                <div class="subsection-header p-2 px-3 rounded-2 mb-3 bg-light-subtle border-start border-primary border-3 d-flex align-items-center justify-content-between">
                    <span class="fw-bold text-primary small text-uppercase">Learning Progress</span>
                    <span class="badge bg-success-lt fw-bold" id="res-status-badge">FINISHED</span>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-sm-6 col-md-4">
                        <label for="res-progress" class="form-label small fw-semibold">Progress (%) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" step="1" min="0" max="100" class="form-control rounded-start" id="res-progress" value="100">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4">
                        <label for="res-assessment-score" class="form-label small fw-semibold">Assessment Score</label>
                        <input type="number" step="0.1" min="0" max="100" class="form-control rounded-2" id="res-assessment-score" placeholder="e.g. 85.0">
                    </div>
                    <div class="col-12 col-sm-6 col-md-4">
                        <label for="res-certificate" class="form-label small fw-semibold">Certificate</label>
                        <select class="form-select rounded-2" id="res-certificate">
                            <option value="Yes" selected>Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                </div>

                <!-- SUBSECTION: ATTENDANCE -->
                <div class="subsection-header p-2 px-3 rounded-2 mb-3 bg-light-subtle border-start border-primary border-3">
                    <span class="fw-bold text-primary small text-uppercase">Attendance</span>
                </div>

                <div class="row g-3 mb-4 align-items-center">
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold d-block">Attendance <span class="text-danger">*</span></label>
                        <div class="d-flex align-items-center gap-3 pt-1">
                            <div class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="radio" name="res_attendance" id="res-attendance-yes" value="1" checked>
                                <label class="form-check-label small" for="res-attendance-yes">Yes (Present)</label>
                            </div>
                            <div class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="radio" name="res_attendance" id="res-attendance-no" value="0">
                                <label class="form-check-label small" for="res-attendance-no">No (Absent)</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-8" id="res-reason-container">
                        <label for="res-attendance-reason" class="form-label small fw-semibold">Reason (if absent)</label>
                        <input type="text" class="form-control rounded-2" id="res-attendance-reason" placeholder="Attendance justification...">
                    </div>
                </div>

                <!-- SUBSECTION: OBLIGATION -->
                <div class="subsection-header p-2 px-3 rounded-2 mb-3 bg-light-subtle border-start border-primary border-3">
                    <span class="fw-bold text-primary small text-uppercase">Obligation (Service Bond)</span>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-sm-6 col-md-4">
                        <label for="res-bond-status" class="form-label small fw-semibold">Bond Status</label>
                        <select class="form-select rounded-2" id="res-bond-status">
                            <option value="No" selected>No</option>
                            <option value="Yes">Yes</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4">
                        <label for="res-bond-end-date" class="form-label small fw-semibold">Bond End Date</label>
                        <input type="date" class="form-control rounded-2" id="res-bond-end-date" disabled>
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="res-bond-amount" class="form-label small fw-semibold">Bond Amount (IDR)</label>
                        <input type="number" step="1000" min="0" class="form-control rounded-2" id="res-bond-amount" placeholder="e.g. 5000000" disabled>
                    </div>
                </div>

                <!-- SUBSECTION: EVALUATION (L1, L2, L3) -->
                <div class="subsection-header p-2 px-3 rounded-2 mb-3 bg-light-subtle border-start border-primary border-3">
                    <span class="fw-bold text-primary small text-uppercase">Kirkpatrick Evaluation (L1 - L3)</span>
                </div>

                <div class="row g-3 mb-3">
                    <!-- L1 -->
                    <div class="col-12 col-md-4">
                        <label for="res-l1-program" class="form-label extra-small fw-bold text-muted text-uppercase mb-1">L1 &bull; Program</label>
                        <input type="text" class="form-control form-control-sm rounded-2" id="res-l1-program" placeholder="e.g. 4.8 / Very Good">
                    </div>
                    <!-- L2 Numerical -->
                    <div class="col-12 col-sm-6 col-md-4">
                        <label for="res-l2-numerical" class="form-label extra-small fw-bold text-muted text-uppercase mb-1">L2 (Post Test) &bull; Numerical</label>
                        <input type="number" step="0.1" min="0" max="100" class="form-control form-control-sm rounded-2" id="res-l2-numerical" placeholder="Score (0-100)">
                    </div>
                    <!-- L2 Verbal -->
                    <div class="col-12 col-sm-6 col-md-4">
                        <label for="res-l2-verbal" class="form-label extra-small fw-bold text-muted text-uppercase mb-1">L2 (Post Test) &bull; Verbal</label>
                        <input type="text" class="form-control form-control-sm rounded-2" id="res-l2-verbal" placeholder="e.g. Passed">
                    </div>
                    <!-- L3 Numerical -->
                    <div class="col-12 col-sm-6 col-md-4">
                        <label for="res-l3-numerical" class="form-label extra-small fw-bold text-muted text-uppercase mb-1">L3 (Project) &bull; Numerical</label>
                        <input type="number" step="0.1" min="0" max="100" class="form-control form-control-sm rounded-2" id="res-l3-numerical" placeholder="Score (0-100)">
                    </div>
                    <!-- L3 Verbal -->
                    <div class="col-12 col-sm-6 col-md-4">
                        <label for="res-l3-verbal" class="form-label extra-small fw-bold text-muted text-uppercase mb-1">L3 (Project) &bull; Verbal</label>
                        <input type="text" class="form-control form-control-sm rounded-2" id="res-l3-verbal" placeholder="e.g. Implemented in Plant">
                    </div>
                    <!-- L3 PIC -->
                    <div class="col-12 col-md-4">
                        <label for="res-pic" class="form-label extra-small fw-bold text-muted text-uppercase mb-1">L3 (Project) &bull; PIC</label>
                        <input type="text" class="form-control form-control-sm rounded-2" id="res-pic" placeholder="e.g. HRDC Team">
                    </div>
                </div>

                <!-- SAVE RESULT BUTTON -->
                <div class="d-flex align-items-center justify-content-between p-2 px-3 rounded-2 bg-light border mt-3 flex-wrap gap-2">
                    <span class="text-muted extra-small" id="save-result-status-msg">Changes update live in memory.</span>
                    <button type="button" class="btn btn-sm btn-outline-primary px-3" id="btn-save-participant-result">
                        <i class="ti ti-device-floppy me-1"></i> Save Result for This Participant
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================= -->
    <!-- SECTION 4: REVIEW & SAVE -->
    <!-- ============================================================= -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 form-section" id="section-4-review">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                <span class="badge rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.85rem;">4</span>
                <h4 class="fw-bold mb-0 text-dark">Review &amp; Save</h4>
            </div>
            <p class="text-muted small mb-3">Review all information before saving to the database.</p>

            <div class="row g-3">
                <!-- CARD 1: TRAINING INFORMATION SUMMARY -->
                <div class="col-12 col-lg-6">
                    <div class="card h-100 border shadow-none rounded-3">
                        <div class="card-header bg-light-subtle py-2 px-3 d-flex align-items-center justify-content-between">
                            <span class="fw-bold small text-dark">Training Information</span>
                            <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2" id="btn-review-edit-1">Edit</button>
                        </div>
                        <div class="card-body p-3 extra-small">
                            <table class="table table-sm table-borderless mb-0">
                                <tr><th class="text-muted p-1" style="width: 130px;">Course:</th><td class="fw-semibold text-dark p-1" id="rev-course">-</td></tr>
                                <tr><th class="text-muted p-1">Batch:</th><td class="text-dark p-1" id="rev-batch">-</td></tr>
                                <tr><th class="text-muted p-1">Classification:</th><td class="text-dark p-1" id="rev-classification">-</td></tr>
                                <tr><th class="text-muted p-1">Delivery:</th><td class="text-dark p-1" id="rev-delivery">-</td></tr>
                                <tr><th class="text-muted p-1">Provider:</th><td class="text-dark p-1" id="rev-provider">-</td></tr>
                                <tr><th class="text-muted p-1">Schedule:</th><td class="text-dark p-1" id="rev-schedule">-</td></tr>
                                <tr><th class="text-muted p-1">Learning Hours:</th><td class="text-dark p-1" id="rev-hours">-</td></tr>
                                <tr><th class="text-muted p-1">Venue / Room:</th><td class="text-dark p-1" id="rev-venue">-</td></tr>
                                <tr><th class="text-muted p-1">Instructor:</th><td class="text-dark p-1" id="rev-instructor">-</td></tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- CARD 2: PARTICIPANTS SUMMARY -->
                <div class="col-12 col-lg-6">
                    <div class="card h-100 border shadow-none rounded-3">
                        <div class="card-header bg-light-subtle py-2 px-3 d-flex align-items-center justify-content-between">
                            <span class="fw-bold small text-dark">Participants (<span id="rev-part-count">0</span>)</span>
                            <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2" id="btn-review-edit-2">Edit</button>
                        </div>
                        <div class="card-body p-3 extra-small" style="max-height: 250px; overflow-y: auto;">
                            <ol class="ps-3 mb-0" id="rev-part-list">
                                <li class="text-muted">No participants added</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- CARD 3: RESULT SUMMARY METRICS -->
                <div class="col-12">
                    <div class="card border shadow-none rounded-3">
                        <div class="card-header bg-light-subtle py-2 px-3 d-flex align-items-center justify-content-between">
                            <span class="fw-bold small text-dark">Result Summary</span>
                            <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2" id="btn-review-edit-3">Edit</button>
                        </div>
                        <div class="card-body p-3 extra-small">
                            <div class="row g-2 text-center">
                                <div class="col-6 col-sm-3 col-md-2 p-2 border-end">
                                    <div class="text-muted mb-1">Participants</div>
                                    <div class="fw-bold fs-3 text-dark" id="rev-metric-total">0</div>
                                </div>
                                <div class="col-6 col-sm-3 col-md-2 p-2 border-end">
                                    <div class="text-muted mb-1">Attendance</div>
                                    <div class="fw-bold text-success" id="rev-metric-att-yes">0 Yes</div>
                                    <div class="text-danger extra-small" id="rev-metric-att-no">0 No</div>
                                </div>
                                <div class="col-6 col-sm-3 col-md-2 p-2 border-end">
                                    <div class="text-muted mb-1">Finished</div>
                                    <div class="fw-bold text-success fs-3" id="rev-metric-finished">0</div>
                                </div>
                                <div class="col-6 col-sm-3 col-md-2 p-2 border-end">
                                    <div class="text-muted mb-1">Ongoing</div>
                                    <div class="fw-bold text-warning fs-3" id="rev-metric-ongoing">0</div>
                                </div>
                                <div class="col-6 col-sm-3 col-md-2 p-2 border-end">
                                    <div class="text-muted mb-1">Avg Assessment</div>
                                    <div class="fw-bold text-primary fs-3" id="rev-metric-avg-score">-</div>
                                </div>
                                <div class="col-6 col-sm-3 col-md-2 p-2">
                                    <div class="text-muted mb-1">Bond Obligations</div>
                                    <div class="fw-bold text-dark fs-3" id="rev-metric-bond">0</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BOTTOM ACTION AREA -->
    <div class="card border-0 shadow-sm rounded-3 p-3 mb-4 bg-white sticky-bottom-actions">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="text-danger small fw-semibold" id="add-training-alert" style="display: none;"></div>
            <div class="d-flex align-items-center gap-2 ms-auto">
                <a href="{{ route('manage.record.offline') }}" class="btn btn-outline-secondary px-3" id="btn-cancel-add-training">Cancel</a>
                <button type="button" class="btn btn-primary px-4 d-inline-flex align-items-center gap-1.5" id="btn-submit-add-training" style="background-color: #d97706; border-color: #d97706;">
                    <i class="ti ti-check fs-2"></i>
                    <span>Save Training</span>
                </button>
            </div>
        </div>
    </div>
</form>
