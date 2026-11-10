<!-- Modern Detail View Modal (18 Canonical Fields) -->
<div class="modal fade" id="recordDetailModal" tabindex="-1" aria-labelledby="recordDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            <!-- Modal Header -->
            <div class="modal-header border-0 pb-0 pt-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <span class="avatar avatar-sm rounded-2 bg-primary-lt text-primary">
                        <i class="ti ti-file-text fs-3"></i>
                    </span>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0 fs-4" id="recordDetailModalLabel">Record Details</h5>
                        <p class="text-muted small mb-0">Detailed view of participant learning record attributes.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body py-3 px-4 pb-4">
                <!-- Loading State -->
                <div class="py-5 text-center text-muted" id="record-detail-loading" style="display: none;">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 small fw-medium">Loading record details...</div>
                </div>

                <!-- Attributes Container -->
                <div id="record-detail-content">
                    <!-- Top Area: Source Badge & Course Title -->
                    <div class="p-3 rounded-3 mb-3 border bg-white detail-top-card">
                        <div class="mb-1.5 d-flex align-items-center gap-2">
                            <span class="badge px-2.5 py-1 fw-bold text-uppercase" id="detail-modal-source" style="font-size: 0.75rem; letter-spacing: 0.05em; border-radius: 6px; background-color: #dbeafe; color: #1d4ed8;">
                                KP OWL
                            </span>
                            <span class="text-muted small" id="detail-modal-employee-header">-</span>
                        </div>
                        <h4 class="fw-bold text-dark mb-0 fs-3" id="detail-modal-course-title" style="line-height: 1.4;">
                            -
                        </h4>
                    </div>

                    <!-- 1. Participant Profile -->
                    <div class="card border border-light-subtle bg-light p-3 rounded-3 mb-3 shadow-none detail-section-card">
                        <div class="section-heading small fw-bold text-secondary mb-2.5 d-flex align-items-center gap-1.5">
                            <i class="ti ti-user text-primary fs-3"></i>
                            <span>Participant Profile</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-6 col-md-3">
                                <div class="field-label text-muted text-uppercase tracking-wider mb-1" style="font-size: 0.7rem; font-weight: 600;">EMPLOYEE ID</div>
                                <div class="field-value fw-bold text-dark fs-6" id="detail-modal-employee-id">-</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="field-label text-muted text-uppercase tracking-wider mb-1" style="font-size: 0.7rem; font-weight: 600;">EMPLOYEE NAME</div>
                                <div class="field-value fw-bold text-dark text-uppercase fs-6" id="detail-modal-employee-name">-</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="field-label text-muted text-uppercase tracking-wider mb-1" style="font-size: 0.7rem; font-weight: 600;">1. GRADE</div>
                                <div class="field-value fw-bold text-dark fs-6" id="detail-modal-grade">-</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="field-label text-muted text-uppercase tracking-wider mb-1" style="font-size: 0.7rem; font-weight: 600;">2. JOB POSITION</div>
                                <div class="field-value fw-bold text-dark fs-6" id="detail-modal-job-position">-</div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Course & Provider Details -->
                    <div class="card border border-light-subtle bg-light p-3 rounded-3 mb-3 shadow-none detail-section-card">
                        <div class="section-heading small fw-bold text-secondary mb-2.5 d-flex align-items-center gap-1.5">
                            <i class="ti ti-book text-primary fs-3"></i>
                            <span>Course &amp; Provider Details</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="field-label text-muted text-uppercase tracking-wider mb-1" style="font-size: 0.7rem; font-weight: 600;">3. SUB COURSE TITLE</div>
                                <div class="field-value fw-bold text-dark" id="detail-modal-sub-course-title">-</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="field-label text-muted text-uppercase tracking-wider mb-1" style="font-size: 0.7rem; font-weight: 600;">4. LEVEL</div>
                                <div class="field-value fw-bold text-dark" id="detail-modal-level">-</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="field-label text-muted text-uppercase tracking-wider mb-1" style="font-size: 0.7rem; font-weight: 600;">5. BATCH</div>
                                <div class="field-value fw-bold text-dark" id="detail-modal-batch">-</div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="field-label text-muted text-uppercase tracking-wider mb-1" style="font-size: 0.7rem; font-weight: 600;">6. SUB PROVIDER</div>
                                <div class="field-value fw-bold text-dark" id="detail-modal-sub-provider">-</div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="field-label text-muted text-uppercase tracking-wider mb-1" style="font-size: 0.7rem; font-weight: 600;">7. INSTRUCTOR</div>
                                <div class="field-value fw-bold text-dark" id="detail-modal-instructor">-</div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="field-label text-muted text-uppercase tracking-wider mb-1" style="font-size: 0.7rem; font-weight: 600;">18. PERSON IN CHARGE (PIC)</div>
                                <div class="field-value fw-bold text-dark" id="detail-modal-pic">-</div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Attendance & Evaluation -->
                    <div class="card border border-light-subtle bg-light p-3 rounded-3 mb-3 shadow-none detail-section-card">
                        <div class="section-heading small fw-bold text-secondary mb-2.5 d-flex align-items-center gap-1.5">
                            <i class="ti ti-checkup-list text-primary fs-3"></i>
                            <span>Attendance &amp; Evaluation</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="field-label text-muted text-uppercase tracking-wider mb-1" style="font-size: 0.7rem; font-weight: 600;">8. ATTENDANCE REASON</div>
                                <div class="field-value fw-bold text-dark" id="detail-modal-attendance-reason">-</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="field-label text-muted text-uppercase tracking-wider mb-1" style="font-size: 0.7rem; font-weight: 600;">13. L2 STATUS</div>
                                <div id="detail-modal-l2-status-container">
                                    <span class="badge bg-secondary-lt fw-bold px-2 py-0.5" id="detail-modal-l2-status">-</span>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="field-label text-muted text-uppercase tracking-wider mb-1" style="font-size: 0.7rem; font-weight: 600;">14. L3 STATUS</div>
                                <div id="detail-modal-l3-status-container">
                                    <span class="badge bg-secondary-lt fw-bold px-2 py-0.5" id="detail-modal-l3-status">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Service Bond / Ikatan Dinas -->
                    <div class="card border border-light-subtle bg-light p-3 rounded-3 mb-3 shadow-none detail-section-card">
                        <div class="section-heading small fw-bold text-secondary mb-2.5 d-flex align-items-center gap-1.5">
                            <i class="ti ti-certificate-2 text-primary fs-3"></i>
                            <span>Service Bond (Ikatan Dinas)</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-6 col-md-3">
                                <div class="field-label text-muted text-uppercase tracking-wider mb-1" style="font-size: 0.7rem; font-weight: 600;">9. BOND STATUS</div>
                                <div class="field-value fw-bold text-dark" id="detail-modal-bond-status">No</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="field-label text-muted text-uppercase tracking-wider mb-1" style="font-size: 0.7rem; font-weight: 600;">10. BOND DURATION</div>
                                <div class="field-value fw-bold text-dark" id="detail-modal-bond-duration">-</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="field-label text-muted text-uppercase tracking-wider mb-1" style="font-size: 0.7rem; font-weight: 600;">11. BOND END DATE</div>
                                <div class="field-value fw-bold text-dark" id="detail-modal-bond-end-date">-</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="field-label text-muted text-uppercase tracking-wider mb-1" style="font-size: 0.7rem; font-weight: 600;">12. BOND AMOUNT</div>
                                <div class="field-value fw-bold text-dark font-monospace" id="detail-modal-bond-amount">-</div>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Certificate Details -->
                    <div class="card border border-light-subtle bg-light p-3 rounded-3 mb-2 shadow-none detail-section-card">
                        <div class="section-heading small fw-bold text-secondary mb-2.5 d-flex align-items-center gap-1.5">
                            <i class="ti ti-award text-primary fs-3"></i>
                            <span>Certificate Details</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="field-label text-muted text-uppercase tracking-wider mb-1" style="font-size: 0.7rem; font-weight: 600;">15. CERTIFICATE INSTITUTION</div>
                                <div class="field-value fw-bold text-dark" id="detail-modal-cert-institution">-</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="field-label text-muted text-uppercase tracking-wider mb-1" style="font-size: 0.7rem; font-weight: 600;">16. CERTIFICATE ISSUED DATE</div>
                                <div class="field-value fw-bold text-dark" id="detail-modal-cert-issued-date">-</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="field-label text-muted text-uppercase tracking-wider mb-1" style="font-size: 0.7rem; font-weight: 600;">17. CERTIFICATE EXPIRED DATE</div>
                                <div class="field-value fw-bold text-dark" id="detail-modal-cert-expired-date">-</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-3">
                <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
[data-bs-theme="dark"] #recordDetailModal .modal-content,
body.dark-mode #recordDetailModal .modal-content,
body.theme-dark #recordDetailModal .modal-content {
    background-color: var(--tblr-bg-surface, #1f2937) !important;
    color: #f8fafc !important;
}
[data-bs-theme="dark"] #recordDetailModal .detail-top-card,
body.dark-mode #recordDetailModal .detail-top-card,
body.theme-dark #recordDetailModal .detail-top-card {
    background-color: #111827 !important;
    border-color: #374151 !important;
}
[data-bs-theme="dark"] #recordDetailModal .detail-section-card,
body.dark-mode #recordDetailModal .detail-section-card,
body.theme-dark #recordDetailModal .detail-section-card {
    background-color: #111827 !important;
    border-color: #374151 !important;
}
[data-bs-theme="dark"] #recordDetailModal .text-dark,
body.dark-mode #recordDetailModal .text-dark,
body.theme-dark #recordDetailModal .text-dark {
    color: #f8fafc !important;
}
</style>

<script>
window.populateRecordDetailModal = function(data) {
    if (!data) return;
    const d = data.data || data;

    const sourceText = d.source || 'KP OWL';
    const isOffline = (sourceText.toUpperCase().includes('NON') || sourceText.toLowerCase().includes('offline'));
    const $badge = $('#detail-modal-source');
    $badge.text(sourceText);
    if (isOffline) {
        $badge.css({'background-color': '#fef3c7', 'color': '#92400e'});
    } else {
        $badge.css({'background-color': '#dbeafe', 'color': '#1d4ed8'});
    }

    const empId = d.employee_id || '-';
    const empName = d.employee_name || '-';
    $('#detail-modal-employee-header').text(empId !== '-' ? (empId + ' - ' + empName) : empName);

    $('#detail-modal-course-title').text(d.course_title || '-');
    $('#detail-modal-employee-id').text(empId);
    $('#detail-modal-employee-name').text(empName);

    // 18 canonical detail fields
    $('#detail-modal-grade').text(d.grade || '-');
    $('#detail-modal-job-position').text(d.job_position || '-');
    $('#detail-modal-sub-course-title').text(d.sub_course_title || '-');
    $('#detail-modal-level').text(d.level || '-');
    $('#detail-modal-batch').text(d.batch || '-');
    $('#detail-modal-sub-provider').text(d.sub_provider || '-');
    $('#detail-modal-instructor').text(d.instructor || '-');
    $('#detail-modal-attendance-reason').text(d.attendance_reason || '-');

    const bondStatus = (d.bond_status === 'Yes' || d.bond_status === '1' || d.bond_status === true) ? 'Yes' : 'No';
    $('#detail-modal-bond-status').text(bondStatus);
    $('#detail-modal-bond-duration').text(d.bond_duration || '-');
    $('#detail-modal-bond-end-date').text(d.bond_end_date || '-');
    $('#detail-modal-bond-amount').text(d.bond_amount || d.bond_amount_idr || '-');

    // L2 Status Pill
    renderDetailPill('#detail-modal-l2-status', '#detail-modal-l2-status-container', d.l2_status);
    // L3 Status Pill
    renderDetailPill('#detail-modal-l3-status', '#detail-modal-l3-status-container', d.l3_status);

    $('#detail-modal-cert-institution').text(d.certificate_institution || '-');
    $('#detail-modal-cert-issued-date').text(d.certificate_issued_date || '-');
    $('#detail-modal-cert-expired-date').text(d.certificate_expired_date || '-');
    $('#detail-modal-pic').text(d.pic || '-');
};

function renderDetailPill(selector, container, val) {
    if (!val || val === '-' || val === 'null' || val === 'undefined') {
        $(container).html('<span class="badge bg-secondary-lt fw-bold px-2 py-0.5">-</span>');
        return;
    }
    const lower = String(val).toLowerCase().trim();
    let cls = 'bg-secondary-lt text-secondary';
    if (lower === 'pass' || lower === 'passed' || lower === 'good' || lower === 'excellent') {
        cls = 'bg-success-lt text-success';
    } else if (lower === 'fail' || lower === 'failed' || lower === 'poor') {
        cls = 'bg-danger-lt text-danger';
    } else if (lower === 'ongoing') {
        cls = 'bg-warning-lt text-warning';
    }
    $(container).html('<span class="badge ' + cls + ' fw-bold px-2 py-0.5">' + val + '</span>');
}

// Global click listener for Details button across all pages
$(document).on('click', '.btn-view-detail, .btn-more-info', function(e) {
    e.preventDefault();
    const $btn = $(this);
    const id = $btn.data('id') || $btn.data('record-id') || $btn.attr('data-id');
    const source = ($btn.data('source') || $btn.data('record-source') || $btn.attr('data-source') || 'online').toString().toLowerCase();

    if (!id) return;

    const modalEl = document.getElementById('recordDetailModal');
    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    $('#record-detail-loading').show();
    $('#record-detail-content').hide();

    // Use current page or classification context if available
    let endpoint = '/manage/record/all/' + encodeURIComponent(source) + '/' + encodeURIComponent(id) + '/details';
    if (window.CLASSIFICATION_DETAIL_ENDPOINT_TEMPLATE) {
        endpoint = window.CLASSIFICATION_DETAIL_ENDPOINT_TEMPLATE.replace('__SOURCE__', encodeURIComponent(source)).replace('__ID__', encodeURIComponent(id));
    }

    $.ajax({
        url: endpoint,
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            $('#record-detail-loading').hide();
            $('#record-detail-content').show();
            window.populateRecordDetailModal(res);
        },
        error: function() {
            // Fallback to legacy detail endpoint
            $.ajax({
                url: '/manage/record/detail/' + encodeURIComponent(id) + '?source=' + encodeURIComponent(source),
                type: 'GET',
                dataType: 'json',
                success: function(res) {
                    $('#record-detail-loading').hide();
                    $('#record-detail-content').show();
                    window.populateRecordDetailModal(res);
                },
                error: function() {
                    $('#record-detail-loading').hide();
                    $('#record-detail-content').show();
                    window.populateRecordDetailModal({
                        source: (source.includes('non') || source === 'offline') ? 'Non KP OWL' : 'KP OWL',
                        course_title: '-',
                        employee_id: '-',
                        employee_name: '-',
                        grade: '-',
                        job_position: '-',
                        sub_course_title: '-',
                        level: '-',
                        batch: '-',
                        sub_provider: '-',
                        instructor: '-',
                        attendance_reason: '-',
                        bond_status: 'No',
                        bond_duration: '-',
                        bond_end_date: '-',
                        bond_amount: '-',
                        l2_status: '-',
                        l3_status: '-',
                        certificate_institution: '-',
                        certificate_issued_date: '-',
                        certificate_expired_date: '-',
                        pic: '-'
                    });
                }
            });
        }
    });
});
</script>
