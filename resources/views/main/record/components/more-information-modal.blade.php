<!-- More Information Modal (Standardized Action -> Details Display) -->
<div class="modal fade" id="moreInformationModal" tabindex="-1" aria-labelledby="moreInformationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            <!-- Modal Header -->
            <div class="modal-header border-0 pb-0 pt-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="modal-title font-semibold text-slate-800 d-flex align-items-center gap-2 mb-0" id="moreInformationModalLabel" style="font-size: 1.05rem; font-weight: 600; color: #1e293b;">
                        <i class="bi bi-info-circle text-blue-600"></i> More Information
                    </h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body py-3 px-4 pb-4">
                <!-- Loading State -->
                <div class="py-4 text-center text-muted" id="more-info-loading" style="display: none;">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 small">Loading record details...</div>
                </div>

                <!-- Attributes Container -->
                <div id="more-info-content">
                    <!-- Top Area: Source/Type Pill & Header Sub-title [Category] Course Title -->
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="mb-1.5 d-flex align-items-center gap-2">
                            <span class="badge px-2.5 py-1 fw-bold text-uppercase modal-more-source-pill" id="modal-badge-source" style="font-size: 0.75rem; letter-spacing: 0.05em; border-radius: 6px; background-color: #fef3c7; color: #92400e;">
                                KP OWL
                            </span>
                        </div>
                        <h4 class="fw-bold text-slate-900 mb-1" id="modal-course-title" style="font-size: 1.125rem; font-weight: 700; line-height: 1.4; color: #0f172a;">
                            -
                        </h4>
                    </div>

                    <!-- SECTION 1: Employee Information -->
                    <div class="border border-slate-200 rounded-2xl p-4 bg-slate-50/50 mb-3 shadow-sm" style="border: 1px solid #e2e8f0; border-radius: 1rem; padding: 1rem; background-color: rgba(248, 250, 252, 0.5); box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);">
                        <div class="section-heading fw-semibold text-slate-700 mb-3 d-flex align-items-center gap-1.5" style="font-size: 0.8125rem; font-weight: 600; color: #334155;">
                            <i class="bi bi-person text-slate-500 me-1.5" style="color: #64748b; font-size: 1rem;"></i> <span>Employee Information</span>
                        </div>
                        <!-- Row 1: Employee ID | Employee Name -->
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <div class="field-label text-uppercase mb-1" style="font-size: 0.6875rem; font-weight: 500; letter-spacing: 0.05em; color: #94a3b8;">EMPLOYEE ID</div>
                                <div class="field-value fw-bold text-slate-800" id="modal-employee-id" style="font-size: 0.8125rem; font-weight: 700; color: #1e293b;">-</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="field-label text-uppercase mb-1" style="font-size: 0.6875rem; font-weight: 500; letter-spacing: 0.05em; color: #94a3b8;">EMPLOYEE NAME</div>
                                <div class="field-value fw-bold text-slate-800 text-uppercase" id="modal-employee-name" style="font-size: 0.8125rem; font-weight: 700; color: #1e293b;">-</div>
                            </div>
                        </div>
                        <!-- Row 2: Grade | Job Position -->
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="field-label text-uppercase mb-1" style="font-size: 0.6875rem; font-weight: 500; letter-spacing: 0.05em; color: #94a3b8;">GRADE</div>
                                <div class="field-value fw-bold text-slate-800" id="modal-grade" style="font-size: 0.8125rem; font-weight: 700; color: #1e293b;">-</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="field-label text-uppercase mb-1" style="font-size: 0.6875rem; font-weight: 500; letter-spacing: 0.05em; color: #94a3b8;">JOB POSITION</div>
                                <div class="field-value fw-bold text-slate-800" id="modal-job-position" style="font-size: 0.8125rem; font-weight: 700; color: #1e293b;">-</div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: Course & Batch Information -->
                    <div class="border border-slate-200 rounded-2xl p-4 bg-slate-50/50 mb-3 shadow-sm" style="border: 1px solid #e2e8f0; border-radius: 1rem; padding: 1rem; background-color: rgba(248, 250, 252, 0.5); box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);">
                        <div class="section-heading fw-semibold text-slate-700 mb-3 d-flex align-items-center gap-1.5" style="font-size: 0.8125rem; font-weight: 600; color: #334155;">
                            <i class="bi bi-book text-slate-500 me-1.5" style="color: #64748b; font-size: 1rem;"></i> <span>Course &amp; Batch Information</span>
                        </div>
                        <!-- Row 1: Sub Course Title | Level -->
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <div class="field-label text-uppercase mb-1" style="font-size: 0.6875rem; font-weight: 500; letter-spacing: 0.05em; color: #94a3b8;">SUB COURSE TITLE</div>
                                <div class="field-value fw-bold text-slate-800" id="modal-sub-course-title" style="font-size: 0.8125rem; font-weight: 700; color: #1e293b;">-</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="field-label text-uppercase mb-1" style="font-size: 0.6875rem; font-weight: 500; letter-spacing: 0.05em; color: #94a3b8;">LEVEL</div>
                                <div class="field-value fw-bold text-slate-800" id="modal-level" style="font-size: 0.8125rem; font-weight: 700; color: #1e293b;">-</div>
                            </div>
                        </div>
                        <!-- Row 2: Batch | Instructor -->
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="field-label text-uppercase mb-1" style="font-size: 0.6875rem; font-weight: 500; letter-spacing: 0.05em; color: #94a3b8;">BATCH</div>
                                <div class="field-value fw-bold text-slate-800" id="modal-batch" style="font-size: 0.8125rem; font-weight: 700; color: #1e293b;">-</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="field-label text-uppercase mb-1" style="font-size: 0.6875rem; font-weight: 500; letter-spacing: 0.05em; color: #94a3b8;">INSTRUCTOR</div>
                                <div class="field-value fw-bold text-slate-800" id="modal-instructor" style="font-size: 0.8125rem; font-weight: 700; color: #1e293b;">-</div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: Location & Provider Information -->
                    <div class="border border-slate-200 rounded-2xl p-4 bg-slate-50/50 mb-3 shadow-sm" style="border: 1px solid #e2e8f0; border-radius: 1rem; padding: 1rem; background-color: rgba(248, 250, 252, 0.5); box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);">
                        <div class="section-heading fw-semibold text-slate-700 mb-3 d-flex align-items-center gap-1.5" style="font-size: 0.8125rem; font-weight: 600; color: #334155;">
                            <i class="bi bi-geo-alt text-slate-500 me-1.5" style="color: #64748b; font-size: 1rem;"></i> <span>Location &amp; Provider Information</span>
                        </div>
                        <!-- Row 1: Sub Provider | Attendance Reason -->
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="field-label text-uppercase mb-1" style="font-size: 0.6875rem; font-weight: 500; letter-spacing: 0.05em; color: #94a3b8;">SUB PROVIDER</div>
                                <div class="field-value fw-bold text-slate-800" id="modal-sub-provider" style="font-size: 0.8125rem; font-weight: 700; color: #1e293b;">-</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="field-label text-uppercase mb-1" style="font-size: 0.6875rem; font-weight: 500; letter-spacing: 0.05em; color: #94a3b8;">ATTENDANCE REASON</div>
                                <div class="field-value fw-bold text-slate-800" id="modal-attendance-reason" style="font-size: 0.8125rem; font-weight: 700; color: #1e293b;">-</div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 4: Bond Details (1 Row with 4 Columns) -->
                    <div class="border border-slate-200 rounded-2xl p-4 bg-slate-50/50 mb-3 shadow-sm" style="border: 1px solid #e2e8f0; border-radius: 1rem; padding: 1rem; background-color: rgba(248, 250, 252, 0.5); box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);">
                        <div class="section-heading fw-semibold text-slate-700 mb-3 d-flex align-items-center gap-1.5" style="font-size: 0.8125rem; font-weight: 600; color: #334155;">
                            <i class="bi bi-journal-text text-slate-500 me-1.5" style="color: #64748b; font-size: 1rem;"></i> <span>Bond Details</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-6 col-md-3">
                                <div class="field-label text-uppercase mb-1" style="font-size: 0.6875rem; font-weight: 500; letter-spacing: 0.05em; color: #94a3b8;">BOND STATUS</div>
                                <div class="field-value fw-bold text-slate-800" id="modal-bond-status" style="font-size: 0.8125rem; font-weight: 700; color: #1e293b;">No</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="field-label text-uppercase mb-1" style="font-size: 0.6875rem; font-weight: 500; letter-spacing: 0.05em; color: #94a3b8;">BOND DURATION</div>
                                <div class="field-value fw-bold text-slate-800" id="modal-bond-duration" style="font-size: 0.8125rem; font-weight: 700; color: #1e293b;">-</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="field-label text-uppercase mb-1" style="font-size: 0.6875rem; font-weight: 500; letter-spacing: 0.05em; color: #94a3b8;">BOND END DATE</div>
                                <div class="field-value fw-bold text-slate-800" id="modal-bond-end-date" style="font-size: 0.8125rem; font-weight: 700; color: #1e293b;">-</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="field-label text-uppercase mb-1" style="font-size: 0.6875rem; font-weight: 500; letter-spacing: 0.05em; color: #94a3b8;">BOND AMOUNT</div>
                                <div class="field-value fw-bold text-slate-800 font-monospace" id="modal-bond-amount" style="font-size: 0.8125rem; font-weight: 700; color: #1e293b;">-</div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 5: Assessment & Score Details -->
                    <div class="border border-slate-200 rounded-2xl p-4 bg-slate-50/50 mb-3 shadow-sm" style="border: 1px solid #e2e8f0; border-radius: 1rem; padding: 1rem; background-color: rgba(248, 250, 252, 0.5); box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);">
                        <div class="section-heading fw-semibold text-slate-700 mb-3 d-flex align-items-center gap-1.5" style="font-size: 0.8125rem; font-weight: 600; color: #334155;">
                            <i class="bi bi-check2-circle text-slate-500 me-1.5" style="color: #64748b; font-size: 1rem;"></i> <span>Assessment &amp; Score Details</span>
                        </div>
                        <!-- Row 1: L2 Status | L3 Status -->
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="field-label text-uppercase mb-1" style="font-size: 0.6875rem; font-weight: 500; letter-spacing: 0.05em; color: #94a3b8;">L2 STATUS</div>
                                <div class="field-value fw-bold text-slate-800" id="modal-l2-status-container">
                                    <span id="modal-l2-status">-</span>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="field-label text-uppercase mb-1" style="font-size: 0.6875rem; font-weight: 500; letter-spacing: 0.05em; color: #94a3b8;">L3 STATUS</div>
                                <div class="field-value fw-bold text-slate-800" id="modal-l3-status-container">
                                    <span id="modal-l3-status">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 6: Certificate Details -->
                    <div class="border border-slate-200 rounded-2xl p-4 bg-slate-50/50 mb-3 shadow-sm" style="border: 1px solid #e2e8f0; border-radius: 1rem; padding: 1rem; background-color: rgba(248, 250, 252, 0.5); box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);">
                        <div class="section-heading fw-semibold text-slate-700 mb-3 d-flex align-items-center gap-1.5" style="font-size: 0.8125rem; font-weight: 600; color: #334155;">
                            <i class="bi bi-award text-slate-500 me-1.5" style="color: #64748b; font-size: 1rem;"></i> <span>Certificate Details</span>
                        </div>
                        <!-- Row 1: Certificate Institution -->
                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <div class="field-label text-uppercase mb-1" style="font-size: 0.6875rem; font-weight: 500; letter-spacing: 0.05em; color: #94a3b8;">CERTIFICATE INSTITUTION</div>
                                <div class="field-value fw-bold text-slate-800" id="modal-cert-institution" style="font-size: 0.8125rem; font-weight: 700; color: #1e293b;">-</div>
                            </div>
                        </div>
                        <!-- Row 2: Certificate Issued Date | Certificate Expired Date -->
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="field-label text-uppercase mb-1" style="font-size: 0.6875rem; font-weight: 500; letter-spacing: 0.05em; color: #94a3b8;">CERTIFICATE ISSUED DATE</div>
                                <div class="field-value fw-bold text-slate-800" id="modal-cert-issued-date" style="font-size: 0.8125rem; font-weight: 700; color: #1e293b;">-</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="field-label text-uppercase mb-1" style="font-size: 0.6875rem; font-weight: 500; letter-spacing: 0.05em; color: #94a3b8;">CERTIFICATE EXPIRED DATE</div>
                                <div class="field-value fw-bold text-slate-800" id="modal-cert-expired-date" style="font-size: 0.8125rem; font-weight: 700; color: #1e293b;">-</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
[data-bs-theme="dark"] #moreInformationModal .modal-content,
body.dark-mode #moreInformationModal .modal-content,
body.theme-dark #moreInformationModal .modal-content {
    background-color: var(--tblr-bg-surface, #1f2937) !important;
    color: #f8fafc !important;
}
[data-bs-theme="dark"] #moreInformationModal .border-slate-200,
body.dark-mode #moreInformationModal .border-slate-200,
body.theme-dark #moreInformationModal .border-slate-200 {
    border-color: rgba(255, 255, 255, 0.1) !important;
    background-color: rgba(30, 41, 59, 0.5) !important;
}
[data-bs-theme="dark"] #moreInformationModal .text-slate-800,
body.dark-mode #moreInformationModal .text-slate-800,
body.theme-dark #moreInformationModal .text-slate-800 {
    color: #f1f5f9 !important;
}
[data-bs-theme="dark"] #moreInformationModal .text-slate-900,
body.dark-mode #moreInformationModal .text-slate-900,
body.theme-dark #moreInformationModal .text-slate-900 {
    color: #f8fafc !important;
}
[data-bs-theme="dark"] #moreInformationModal .text-slate-700,
body.dark-mode #moreInformationModal .text-slate-700,
body.theme-dark #moreInformationModal .text-slate-700 {
    color: #cbd5e1 !important;
}
[data-bs-theme="dark"] #moreInformationModal .text-slate-500,
[data-bs-theme="dark"] #moreInformationModal .text-slate-400,
body.dark-mode #moreInformationModal .text-slate-400,
body.theme-dark #moreInformationModal .text-slate-400 {
    color: #94a3b8 !important;
}
</style>

<!-- Standard Delete Record Modal Triggered by Action Dropdown -->
<div class="modal fade" id="deleteRecordModal" tabindex="-1" aria-labelledby="deleteRecordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-body text-center py-4">
                <div class="avatar avatar-lg rounded-circle bg-danger-lt mx-auto mb-3 text-danger">
                    <i class="bi bi-trash fs-1"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2" id="deleteRecordModalLabel">Delete Record?</h5>
                <p class="text-muted small mb-3">Are you sure you want to delete this training record? This action cannot be undone.</p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger px-3 btn-confirm-delete-action">Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    // Helper to safely set text content by element ID
    function setModalText(id, val) {
        var el = document.getElementById(id);
        if (el) {
            el.textContent = (val !== null && val !== undefined && val !== '') ? val : '-';
        }
    }

    // Helper for rendering status pills (Green for Passed/Good, Yellow for Ongoing, Gray for Null)
    function renderStatusPill(pillSelector, containerSelector, val) {
        var cleanId = pillSelector.replace('#', '');
        var container = document.querySelector(containerSelector);
        var target = document.querySelector(pillSelector);

        if (!val || val === '-' || val === 'null' || val === 'undefined') {
            var html = '<span class="text-muted fw-bold" style="font-size: 0.8125rem;" id="' + cleanId + '">-</span>';
            if (container) {
                container.innerHTML = html;
            } else if (target) {
                target.outerHTML = html;
            }
            return;
        }

        var str = String(val).trim();
        str = str.replace(/\s*\(\d+(\.\d+)?%\)/i, '');
        var lower = str.toLowerCase();

        var badgeClass = 'bg-secondary-lt text-secondary';
        if (lower === 'not started' || lower === 'not_started' || lower === 'null') {
            badgeClass = 'bg-secondary-lt text-secondary';
        } else if (lower === 'passed' || lower === 'finished' || lower === 'completed' || lower === 'pass' || lower.includes('good') || lower === 'excellent') {
            badgeClass = 'bg-success-lt text-success';
        } else if (lower === 'ongoing' || lower.includes('progress')) {
            badgeClass = 'bg-warning-lt text-warning';
        } else if (lower === 'fail' || lower === 'failed' || lower.includes('poor')) {
            badgeClass = 'bg-danger-lt text-danger';
        }

        var pillHtml = '<span class="badge ' + badgeClass + ' fw-bold px-2 py-0.5" style="font-size: 0.75rem;" id="' + cleanId + '">' + str + '</span>';
        if (container) {
            container.innerHTML = pillHtml;
        } else if (target) {
            target.outerHTML = pillHtml;
        }
    }

    // Global modal populator function (vanilla JS, independent of jQuery timing)
    window.populateMoreInformationModal = function(response) {
        if (!response) return;
        var raw = response.data || response;
        // Normalize nested employee, learning, and progress structures (e.g. from RecordOnlineController::detail)
        var emp = raw.employee || {};
        var lrn = raw.learning || {};
        var prog = raw.progress || {};

        var d = {
            source: raw.source || raw.source_type || (raw.source_badge ? (typeof raw.source_badge === 'string' ? raw.source_badge.replace(/<[^>]*>?/gm, '').trim() : 'KP OWL') : 'KP OWL'),
            source_type: raw.source_type || raw.source || 'KP OWL',
            category: raw.category || raw.category_source || lrn.enrollment || lrn.category || '',
            course_title: raw.course_title || raw.title || raw.course_name || raw.learning_title || lrn.title || '-',
            sub_course_title: raw.sub_course_title || raw.sub_course_name || raw.sub_course || lrn.sub_title || '-',
            employee_id: raw.employee_id || raw.id_emp || raw.nip || emp.id_emp || emp.employee_id || emp.nip || '-',
            employee_name: raw.employee_name || raw.user_name || raw.participant_name || raw.name || emp.name || emp.employee_name || '-',
            grade: raw.grade || raw.jobgrade || raw.user_jobgrade || emp.grade || emp.jobgrade || '-',
            job_position: raw.job_position || raw.jobposition || raw.user_jobposition || raw.position || emp.job_position || emp.jobposition || '-',
            level: raw.level || lrn.level || '-',
            batch: raw.batch || lrn.batch || '-',
            instructor: raw.instructor || raw.author || raw.content_author || lrn.author || lrn.instructor || '-',
            sub_provider: raw.sub_provider || raw.provider_name || raw.provider || lrn.provider || '-',
            attendance_reason: raw.attendance_reason || '-',
            bond_status: raw.bond_status ?? '-',
            bond_duration: raw.bond_duration ?? '-',
            bond_end_date: raw.bond_end_date ?? '-',
            bond_amount: raw.bond_amount ?? raw.bond_amount_idr ?? '-',
            l2_status: raw.l2_status || raw.score_l2_status || '-',
            l3_status: raw.l3_status || raw.score_l3_status || '-',
            certificate_institution: raw.certificate_institution || raw.institution || '-',
            certificate_issued_date: raw.certificate_issued_date || raw.issued_date || '-',
            certificate_expired_date: raw.certificate_expired_date || raw.expired_date || '-',
        };

        // Set #modal-badge-source text & color
        var sourceText = d.source || (d.source_type || 'KP OWL');
        var isOffline = (sourceText.toUpperCase().includes('NON') || sourceText.toLowerCase().includes('offline'));
        var badge = document.getElementById('modal-badge-source');
        if (badge) {
            badge.textContent = sourceText;
            if (isOffline) {
                badge.style.backgroundColor = '#fef3c7';
                badge.style.color = '#92400e';
            } else {
                badge.style.backgroundColor = '#e0f2fe';
                badge.style.color = '#0369a1';
            }
        }

        // Header Sub-title: Display [Category] Course Title
        var rawCategory = d.category || d.category_source || '';
        var cleanCategory = (rawCategory && rawCategory !== '-' && rawCategory !== 'null' && rawCategory !== 'undefined') ? String(rawCategory).trim() : '';
        var courseTitle = d.course_title || d.title || d.learning_title || '-';
        var headerSubTitle = cleanCategory ? ('[' + cleanCategory + '] ' + courseTitle) : courseTitle;
        setModalText('modal-course-title', headerSubTitle);

        // Section 1 - Employee Information
        setModalText('modal-employee-id', d.employee_id || d.id_emp || '-');
        var empName = d.employee_name || d.user_name || d.participant_name || '-';
        setModalText('modal-employee-name', empName !== '-' ? String(empName).toUpperCase() : '-');
        setModalText('modal-grade', d.grade || d.jobgrade || d.user_jobgrade || '-');
        setModalText('modal-job-position', d.job_position || d.jobposition || d.user_jobposition || '-');

        // Section 2 - Course & Batch Information
        setModalText('modal-sub-course-title', d.sub_course_title || d.sub_course_name || '-');
        setModalText('modal-level', d.level || '-');
        setModalText('modal-batch', d.batch || '-');
        setModalText('modal-instructor', d.instructor || d.content_author || d.author || '-');

        // Section 3 - Location & Provider Information
        setModalText('modal-sub-provider', d.sub_provider || '-');
        setModalText('modal-attendance-reason', d.attendance_reason || '-');

        // Section 4 - Bond Details (1 Row with 4 Columns)
        var rawBond = String(d.bond_status ?? '').toLowerCase().trim();
        var hasBond = ['yes', '1', 'true', 'ya'].includes(rawBond);
        var isOnlineOrKpOwl = (d.source === 'KP OWL' || d.source === 'online' || d.record_source === 'online' || (d.source_type && String(d.source_type).includes('KP OWL')));
        if (hasBond) {
            setModalText('modal-bond-status', 'Yes');
        } else if (isOnlineOrKpOwl || rawBond === '-' || rawBond === '' || rawBond === 'null' || rawBond === 'undefined') {
            setModalText('modal-bond-status', '-');
        } else {
            setModalText('modal-bond-status', 'No');
        }

        if (hasBond) {
            var bondDur = d.bond_duration;
            if (bondDur && bondDur !== '-' && String(bondDur).trim() !== '' && !isNaN(bondDur) && parseInt(bondDur) > 0) {
                setModalText('modal-bond-duration', parseInt(bondDur) + ' Months');
            } else {
                setModalText('modal-bond-duration', d.bond_duration || '-');
            }
            setModalText('modal-bond-end-date', d.bond_end_date && d.bond_end_date !== '-' ? d.bond_end_date : '-');
            var bondAmt = d.bond_amount || d.bond_amount_idr || '-';
            if (bondAmt !== '-' && !isNaN(bondAmt) && parseFloat(bondAmt) > 0) {
                bondAmt = 'Rp ' + Math.round(parseFloat(bondAmt)).toLocaleString('id-ID');
            }
            setModalText('modal-bond-amount', bondAmt);
        } else {
            setModalText('modal-bond-duration', '-');
            setModalText('modal-bond-end-date', '-');
            setModalText('modal-bond-amount', '-');
        }

        // Section 5 - Assessment & Score Details
        renderStatusPill('#modal-l2-status', '#modal-l2-status-container', d.l2_status || d.score_l2_status);
        renderStatusPill('#modal-l3-status', '#modal-l3-status-container', d.l3_status || d.score_l3_status);

        // Section 6 - Certificate Details
        setModalText('modal-cert-institution', d.certificate_institution || d.institution || '-');
        setModalText('modal-cert-issued-date', d.certificate_issued_date || d.issued_date || '-');
        setModalText('modal-cert-expired-date', d.certificate_expired_date || d.expired_date || '-');
    };

    // Initialize jQuery event listeners once jQuery is loaded
    function initMoreInfoModalListeners() {
        if (typeof jQuery === 'undefined' && typeof $ === 'undefined') {
            setTimeout(initMoreInfoModalListeners, 60);
            return;
        }
        var $ = window.jQuery || window.$;

        var deleteRecordId = null;
        var deleteRecordSource = 'offline';
        var deleteRecordType = 'content';

        $(document).off('click.deleteRecordModalTrigger').on('click.deleteRecordModalTrigger', '[data-bs-target="#deleteRecordModal"]', function(e) {
            deleteRecordId = $(this).data('record-id') || $(this).data('id');
            deleteRecordSource = ($(this).data('record-source') || $(this).data('source') || 'offline').toString().toLowerCase();
            deleteRecordType = ($(this).data('source-type') || $(this).data('type') || 'content').toString().toLowerCase();
        });

        $(document).off('click.confirmDeleteRecordAction').on('click.confirmDeleteRecordAction', '.btn-confirm-delete-action', function(e) {
            e.preventDefault();
            if (!deleteRecordId) return;

            var $btn = $(this);
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span> Deleting...');

            var url = (deleteRecordSource === 'offline')
                ? '/manage/record/offline/' + deleteRecordId
                : '/manage/record/online/' + (deleteRecordType === 'playlist' ? 'playlist' : 'content') + '/' + deleteRecordId;

            $.ajax({
                url: url,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(res) {
                    $('#deleteRecordModal').modal('hide');
                    $btn.prop('disabled', false).text('Delete');
                    deleteRecordId = null;
                    if (window.LaravelDataTables) {
                        $.each(window.LaravelDataTables, function(k, dt) { dt.ajax.reload(null, false); });
                    } else if ($.fn.DataTable) {
                        $('.dataTable').each(function() {
                            if ($.fn.DataTable.isDataTable(this)) {
                                $(this).DataTable().ajax.reload(null, false);
                            }
                        });
                    }
                },
                error: function(xhr) {
                    $btn.prop('disabled', false).text('Delete');
                    var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error deleting record.';
                    alert(msg);
                }
            });
        });

        // Global click handler for .btn-more-info / More Information
        $(document).off('click.moreInfoModal').on('click.moreInfoModal', '[data-bs-target="#moreInformationModal"], .btn-more-info, .btn-more-information', function(e) {
            var $el = $(this);
            var recordId = $el.data('id') || $el.data('record-id') || $el.attr('data-id') || $el.attr('data-record-id');
            var source = ($el.data('source') || $el.data('record-source') || $el.attr('data-source') || $el.attr('data-record-source') || 'online').toString().toLowerCase();
            var type = ($el.data('source-type') || $el.data('type') || $el.attr('data-source-type') || $el.attr('data-type') || '').toString().toLowerCase();

            if (!recordId) return;

            // Avoid duplicate parallel requests if already fetching
            if ($el.data('more-info-fetching')) return;
            $el.data('more-info-fetching', true);
            setTimeout(function() { $el.removeData('more-info-fetching'); }, 800);

            var modalEl = document.getElementById('moreInformationModal');
            if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                var modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
                modalInstance.show();
            }

            $('#more-info-loading').show();
            $('#more-info-content').hide();

            // Fetch the record detail via $.ajax() from /manage/record/detail/{id}
            $.ajax({
                url: '/manage/record/detail/' + encodeURIComponent(recordId) + '?source=' + encodeURIComponent(source) + (type ? '&type=' + encodeURIComponent(type) : ''),
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    $('#more-info-loading').hide();
                    $('#more-info-content').show();
                    window.populateMoreInformationModal(response);
                },
                error: function() {
                    // Fallback attempt to /manage/record/{id}/more-info
                    $.ajax({
                        url: '/manage/record/' + encodeURIComponent(recordId) + '/more-info?source=' + encodeURIComponent(source) + (type ? '&type=' + encodeURIComponent(type) : ''),
                        type: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            $('#more-info-loading').hide();
                            $('#more-info-content').show();
                            window.populateMoreInformationModal(response);
                        },
                        error: function() {
                            // Fallback to user learning record detail
                            $.ajax({
                                url: '/my-learning/learning-record/detail/' + encodeURIComponent(source) + '/' + encodeURIComponent(recordId),
                                type: 'GET',
                                dataType: 'json',
                                success: function(userRes) {
                                    $('#more-info-loading').hide();
                                    $('#more-info-content').show();
                                    window.populateMoreInformationModal(userRes);
                                },
                                error: function() {
                                    $('#more-info-loading').hide();
                                    $('#more-info-content').show();
                                    window.populateMoreInformationModal({
                                        source: (source.includes('non') || source === 'offline') ? 'NON KP OWL' : 'KP OWL',
                                        category: (source.includes('non') || source === 'offline') ? 'Non KP OWL' : 'KP OWL',
                                        course_title: '-',
                                        employee_id: '-',
                                        employee_name: '-',
                                        grade: '-',
                                        job_position: '-',
                                        sub_course_title: '-',
                                        level: '-',
                                        batch: '-',
                                        instructor: '-',
                                        sub_provider: '-',
                                        attendance_reason: '-',
                                        bond_status: '-',
                                        bond_duration: '-',
                                        bond_end_date: '-',
                                        bond_amount: '-',
                                        l2_status: '-',
                                        l3_status: '-',
                                        certificate_institution: '-',
                                        certificate_issued_date: '-',
                                        certificate_expired_date: '-'
                                    });
                                }
                            });
                        }
                    });
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMoreInfoModalListeners);
    } else {
        initMoreInfoModalListeners();
    }
})();
</script>
