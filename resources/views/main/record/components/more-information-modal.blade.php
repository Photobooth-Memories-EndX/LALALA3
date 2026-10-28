<!-- More Information Modal (Standardized 3-Card Layout) -->
<div class="modal fade" id="moreInformationModal" tabindex="-1" aria-labelledby="moreInformationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            <!-- Modal Header -->
            <div class="modal-header border-0 pb-0 pt-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-text ti ti-file-text text-primary fs-5"></i>
                    <h5 class="modal-title text-base text-lg font-semibold fw-semibold text-slate-800 mb-0 fs-5" id="moreInformationModalLabel" style="font-size: 1.05rem; font-weight: 600; color: #1e293b;">More Information</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body py-3 px-4 pb-4">
                <!-- Loading State -->
                <div class="py-4 text-center text-muted" id="more-info-loading" style="display: none;">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 small">Loading record information...</div>
                </div>

                <!-- Attributes Container -->
                <div id="more-info-content">
                    <!-- Top Area: Source/Type Pill & Large Bold Course Title -->
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="mb-1.5">
                            <span class="badge px-2.5 py-1 fw-bold text-uppercase modal-more-source-pill" id="modal-badge-source" style="font-size: 0.75rem; letter-spacing: 0.05em; border-radius: 6px; background-color: #fef3c7; color: #92400e;">
                                NON KP OWL
                            </span>
                        </div>
                        <h4 class="text-base text-lg font-bold fw-bold text-slate-900 mb-0" id="modal-course-title" style="font-size: 1.125rem; font-weight: 700; line-height: 1.4; color: #0f172a;">
                            -
                        </h4>
                    </div>

                    <!-- CARD 1: Employee Information -->
                    <div class="card border border-light-subtle bg-light p-3 rounded-3 mb-3 shadow-none">
                        <div class="section-heading text-xs text-sm font-semibold fw-semibold text-slate-700 mb-2.5 d-flex align-items-center gap-1.5" style="font-size: 0.8125rem; font-weight: 600; color: #334155;">
                            <i class="bi bi-person ti ti-user text-slate-500 me-1.5" style="color: #64748b; font-size: 1rem;"></i> <span>Employee Information</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-6 col-md-3">
                                <div class="field-label text-[10px] text-xs font-medium fw-medium text-slate-400 text-uppercase tracking-wider mb-1" style="font-size: 0.6875rem; font-weight: 500; letter-spacing: 0.05em; color: #94a3b8;">EMPLOYEE ID</div>
                                <div class="field-value text-xs text-sm font-bold fw-bold text-slate-800" id="modal-employee-id" style="font-size: 0.8125rem; font-weight: 700; color: #1e293b;">-</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="field-label text-[10px] text-xs font-medium fw-medium text-slate-400 text-uppercase tracking-wider mb-1" style="font-size: 0.6875rem; font-weight: 500; letter-spacing: 0.05em; color: #94a3b8;">EMPLOYEE NAME</div>
                                <div class="field-value text-xs text-sm font-bold fw-bold text-slate-800 text-uppercase" id="modal-employee-name" style="font-size: 0.8125rem; font-weight: 700; color: #1e293b;">-</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="field-label text-[10px] text-xs font-medium fw-medium text-slate-400 text-uppercase tracking-wider mb-1" style="font-size: 0.6875rem; font-weight: 500; letter-spacing: 0.05em; color: #94a3b8;">GRADE</div>
                                <div class="field-value text-xs text-sm font-bold fw-bold text-slate-800" id="modal-grade" style="font-size: 0.8125rem; font-weight: 700; color: #1e293b;">-</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="field-label text-[10px] text-xs font-medium fw-medium text-slate-400 text-uppercase tracking-wider mb-1" style="font-size: 0.6875rem; font-weight: 500; letter-spacing: 0.05em; color: #94a3b8;">JOB POSITION</div>
                                <div class="field-value text-xs text-sm font-bold fw-bold text-slate-800" id="modal-job-position" style="font-size: 0.8125rem; font-weight: 700; color: #1e293b;">-</div>
                            </div>
                        </div>
                    </div>

                    <!-- CARD 2: Course & Progress Status (Training Details) -->
                    <div class="card border border-light-subtle bg-light p-3 rounded-3 mb-3 shadow-none">
                        <div class="section-heading text-xs text-sm font-semibold fw-semibold text-slate-700 mb-2.5 d-flex align-items-center gap-1.5" style="font-size: 0.8125rem; font-weight: 600; color: #334155;">
                            <i class="bi bi-info-circle ti ti-info-circle text-slate-500 me-1.5" style="color: #64748b; font-size: 1rem;"></i> <span>Course &amp; Progress Status</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-6 col-md-3">
                                <div class="field-label text-[10px] text-xs font-medium fw-medium text-slate-400 text-uppercase tracking-wider mb-1" style="font-size: 0.6875rem; font-weight: 500; letter-spacing: 0.05em; color: #94a3b8;">INSTRUCTOR</div>
                                <div class="field-value text-xs text-sm font-bold fw-bold text-slate-800" id="modal-instructor" style="font-size: 0.8125rem; font-weight: 700; color: #1e293b;">-</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="field-label text-[10px] text-xs font-medium fw-medium text-slate-400 text-uppercase tracking-wider mb-1" style="font-size: 0.6875rem; font-weight: 500; letter-spacing: 0.05em; color: #94a3b8;">PROGRESS STATUS</div>
                                <div id="modal-progress-status-container">
                                    <span class="badge bg-success-lt fw-bold px-2 py-0.5 text-success text-xs" id="modal-progress-status" style="font-size: 0.75rem;">-</span>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="field-label text-[10px] text-xs font-medium fw-medium text-slate-400 text-uppercase tracking-wider mb-1" style="font-size: 0.6875rem; font-weight: 500; letter-spacing: 0.05em; color: #94a3b8;">L2 STATUS</div>
                                <div id="modal-l2-status-container">
                                    <span class="badge bg-success-lt fw-bold px-2 py-0.5 text-success text-xs" id="modal-l2-status" style="font-size: 0.75rem;">-</span>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="field-label text-[10px] text-xs font-medium fw-medium text-slate-400 text-uppercase tracking-wider mb-1" style="font-size: 0.6875rem; font-weight: 500; letter-spacing: 0.05em; color: #94a3b8;">L3 STATUS</div>
                                <div id="modal-l3-status-container">
                                    <span class="badge bg-success-lt fw-bold px-2 py-0.5 text-success text-xs" id="modal-l3-status" style="font-size: 0.75rem;">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CARD 3: Bond Information -->
                    <div class="card border border-light-subtle bg-light p-3 rounded-3 mb-2 shadow-none">
                        <div class="section-heading text-xs text-sm font-semibold fw-semibold text-slate-700 mb-2.5 d-flex align-items-center gap-1.5" style="font-size: 0.8125rem; font-weight: 600; color: #334155;">
                            <i class="bi bi-journal-text ti ti-file-text text-slate-500 me-1.5" style="color: #64748b; font-size: 1rem;"></i> <span>Bond Information</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-6 col-md-3">
                                <div class="field-label text-[10px] text-xs font-medium fw-medium text-slate-400 text-uppercase tracking-wider mb-1" style="font-size: 0.6875rem; font-weight: 500; letter-spacing: 0.05em; color: #94a3b8;">BOND STATUS</div>
                                <div class="field-value text-xs text-sm font-bold fw-bold text-slate-800" id="modal-bond-status" style="font-size: 0.8125rem; font-weight: 700; color: #1e293b;">No</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="field-label text-[10px] text-xs font-medium fw-medium text-slate-400 text-uppercase tracking-wider mb-1" style="font-size: 0.6875rem; font-weight: 500; letter-spacing: 0.05em; color: #94a3b8;">BOND DURATION</div>
                                <div class="field-value text-xs text-sm font-bold fw-bold text-slate-800" id="modal-bond-duration" style="font-size: 0.8125rem; font-weight: 700; color: #1e293b;">-</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="field-label text-[10px] text-xs font-medium fw-medium text-slate-400 text-uppercase tracking-wider mb-1" style="font-size: 0.6875rem; font-weight: 500; letter-spacing: 0.05em; color: #94a3b8;">BOND END DATE</div>
                                <div class="field-value text-xs text-sm font-bold fw-bold text-slate-800" id="modal-bond-end-date" style="font-size: 0.8125rem; font-weight: 700; color: #1e293b;">-</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="field-label text-[10px] text-xs font-medium fw-medium text-slate-400 text-uppercase tracking-wider mb-1" style="font-size: 0.6875rem; font-weight: 500; letter-spacing: 0.05em; color: #94a3b8;">BOND AMOUNT</div>
                                <div class="field-value text-xs text-sm font-bold fw-bold text-slate-800 font-monospace" id="modal-bond-amount" style="font-size: 0.8125rem; font-weight: 700; color: #1e293b;">-</div>
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
[data-bs-theme="dark"] #moreInformationModal .bg-light,
body.dark-mode #moreInformationModal .bg-light,
body.theme-dark #moreInformationModal .bg-light {
    background-color: #111827 !important;
    border-color: #374151 !important;
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
[data-bs-theme="dark"] #moreInformationModal .text-slate-400,
body.dark-mode #moreInformationModal .text-slate-400,
body.theme-dark #moreInformationModal .text-slate-400 {
    color: #64748b !important;
}
</style>

<!-- Standard Delete Record Modal Triggered by Action Dropdown -->
<div class="modal fade" id="deleteRecordModal" tabindex="-1" aria-labelledby="deleteRecordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-body text-center py-4">
                <div class="avatar avatar-lg rounded-circle bg-danger-lt mx-auto mb-3 text-danger">
                    <i class="ti ti-trash fs-1"></i>
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
let deleteRecordId = null;
let deleteRecordSource = 'offline';

$(document).on('click', '[data-bs-target="#deleteRecordModal"]', function(e) {
    deleteRecordId = $(this).data('record-id') || $(this).data('id');
    deleteRecordSource = ($(this).data('record-source') || $(this).data('source') || 'offline').toString().toLowerCase();
});

$(document).on('click', '.btn-confirm-delete-action', function(e) {
    e.preventDefault();
    if (!deleteRecordId) return;

    const $btn = $(this);
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span> Deleting...');

    const url = (deleteRecordSource === 'offline')
        ? '/manage/record/offline/' + deleteRecordId
        : '/manage/record/online/content/' + deleteRecordId;

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
            const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error deleting record.';
            alert(msg);
        }
    });
});

// Helper for rendering status pills (Green for Passed/Good, Yellow for Ongoing, Gray for Null)
function renderStatusPill(pillSelector, containerSelector, val, type) {
    const $container = $(containerSelector);
    if (!val || val === '-' || val === 'null' || val === 'undefined') {
        const html = '<span class="text-muted fw-bold text-xs" style="font-size: 0.8125rem;" id="' + pillSelector.replace('#', '') + '">-</span>';
        if ($container.length) {
            $container.html(html);
        } else {
            $(pillSelector).replaceWith(html);
        }
        return;
    }

    let str = String(val).trim();
    // Strip percentage labels e.g. "Ongoing (75%)" -> "Ongoing"
    str = str.replace(/\s*\(\d+(\.\d+)?%\)/i, '');
    const lower = str.toLowerCase();

    let badgeClass = 'bg-secondary-lt text-secondary';
    if (lower === 'not started' || lower === 'not_started' || lower === 'null') {
        badgeClass = 'bg-secondary-lt text-secondary';
    } else if (lower === 'passed' || lower === 'finished' || lower === 'completed' || lower === 'pass' || lower.includes('good') || lower === 'excellent') {
        badgeClass = 'bg-success-lt text-success';
    } else if (lower === 'ongoing' || lower.includes('progress')) {
        badgeClass = 'bg-warning-lt text-warning';
    } else if (lower === 'fail' || lower === 'failed' || lower.includes('poor')) {
        badgeClass = 'bg-danger-lt text-danger';
    }

    const pillHtml = '<span class="badge ' + badgeClass + ' fw-bold px-2 py-0.5 text-xs" style="font-size: 0.75rem;" id="' + pillSelector.replace('#', '') + '">' + str + '</span>';
    if ($container.length) {
        $container.html(pillHtml);
    } else {
        $(pillSelector).replaceWith(pillHtml);
    }
}

// Global modal populator function
window.populateMoreInformationModal = function(response) {
    if (!response) return;
    const d = response.data || response;

    // Set #modal-badge-source text to response.source
    const sourceText = d.source || (d.source_type || 'NON KP OWL');
    const isOffline = (sourceText.toUpperCase().includes('NON') || sourceText.toLowerCase().includes('offline'));
    const $badge = $('#modal-badge-source');
    $badge.text(sourceText);
    if (isOffline) {
        $badge.css({'background-color': '#fef3c7', 'color': '#92400e'});
    } else {
        $badge.css({'background-color': '#e0f2fe', 'color': '#0369a1'});
    }

    // Set #modal-course-title text to response.course_title
    $('#modal-course-title').text(d.course_title || '-');

    // Set #modal-employee-id text to response.employee_id || '-'
    $('#modal-employee-id').text(d.employee_id || '-');

    // Set #modal-employee-name text to response.employee_name || '-'
    const empName = d.employee_name || '-';
    $('#modal-employee-name').text(empName !== '-' ? empName.toUpperCase() : '-');

    // Set #modal-grade text to response.grade || '-'
    $('#modal-grade').text(d.grade || '-');

    // Set #modal-job-position text to response.job_position || '-'
    $('#modal-job-position').text(d.job_position || '-');

    // Set #modal-instructor text to response.instructor || '-'
    $('#modal-instructor').text(d.instructor || '-');

    // Render status pills for progress_status, l2_status, and l3_status (Green for Passed/Good, Yellow for Ongoing, Gray for Null)
    renderStatusPill('#modal-progress-status', '#modal-progress-status-container', d.progress_status, 'progress');
    renderStatusPill('#modal-l2-status', '#modal-l2-status-container', d.l2_status, 'eval');
    renderStatusPill('#modal-l3-status', '#modal-l3-status-container', d.l3_status, 'eval');

    // Set #modal-bond-status text to response.bond_status || 'No'
    const bondStatus = (d.bond_status === 'Yes' || d.bond_status === '1' || d.bond_status === true) ? 'Yes' : 'No';
    $('#modal-bond-status').text(bondStatus);

    // Set #modal-bond-duration, #modal-bond-end-date, and #modal-bond-amount with fallback '-'
    $('#modal-bond-duration').text(d.bond_duration || '-');
    $('#modal-bond-end-date').text(d.bond_end_date || '-');
    $('#modal-bond-amount').text(d.bond_amount || d.bond_amount_idr || '-');

    if ($('#modal-attendance').length || $('#modal-detail-attendance').length) {
        const rawAtt = String(d.attendance ?? '').toLowerCase().trim();
        const isAttYes = ['1', 'yes', 'true', 'present', 'hadir', 'completed', 'finished'].includes(rawAtt);
        $('#modal-attendance, #modal-detail-attendance').html(isAttYes
            ? '<span class="badge bg-success-lt text-success fw-bold px-2 py-1">Yes</span>'
            : '<span class="badge bg-danger-lt text-danger fw-bold px-2 py-1">No</span>');
    }
};

// Global click handler for .btn-more-info / More Information
$(document).off('click.moreInfo').on('click.moreInfo', '[data-bs-target="#moreInformationModal"], .btn-more-info, .btn-more-information', function(e) {
    e.preventDefault();
    const $el = $(this);
    const recordId = $el.data('id') || $el.data('record-id') || $el.attr('data-id') || $el.attr('data-record-id');
    const source = ($el.data('source') || $el.data('record-source') || $el.attr('data-source') || $el.attr('data-record-source') || 'offline').toString().toLowerCase();

    if (!recordId) return;

    const modalEl = document.getElementById('moreInformationModal');
    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
        modalInstance.show();
    }

    $('#more-info-loading').show();
    $('#more-info-content').hide();

    // Fetch the record detail via $.ajax() from /manage/record/detail/{id}
    $.ajax({
        url: '/manage/record/detail/' + encodeURIComponent(recordId) + (source ? '?source=' + encodeURIComponent(source) : ''),
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
                url: '/manage/record/' + encodeURIComponent(recordId) + '/more-info?source=' + encodeURIComponent(source),
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    $('#more-info-loading').hide();
                    $('#more-info-content').show();
                    window.populateMoreInformationModal(response);
                },
                error: function() {
                    $('#more-info-loading').hide();
                    $('#more-info-content').show();
                    window.populateMoreInformationModal({
                        source: (source.includes('non') || source === 'offline') ? 'NON KP OWL' : 'KP OWL',
                        course_title: '-',
                        employee_id: '-',
                        employee_name: '-',
                        grade: '-',
                        job_position: '-',
                        instructor: '-',
                        progress_status: '-',
                        l2_status: '-',
                        l3_status: '-',
                        bond_status: 'No',
                        bond_duration: '-',
                        bond_end_date: '-',
                        bond_amount: '-'
                    });
                }
            });
        }
    });
});
</script>
