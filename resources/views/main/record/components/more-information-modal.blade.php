<!-- More Information Modal (14 Non-Yellow Highlighted Fields) -->
<div class="modal fade" id="moreInformationModal" tabindex="-1" aria-labelledby="moreInformationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-2">
                    <span class="avatar rounded-3" style="background-color: #e0f2fe; color: #0284c7; width: 42px; height: 42px;">
                        <i class="ti ti-info-circle fs-2"></i>
                    </span>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="moreInformationModalLabel">More Information</h5>
                        <div class="text-muted extra-small" style="font-size: 0.75rem;">Complete Non-Table Details &amp; Administration Attributes</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body py-3">
                <!-- Loading State -->
                <div class="py-4 text-center text-muted" id="more-info-loading" style="display: none;">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 small">Loading record information...</div>
                </div>

                <!-- Attributes Grid (14 Non-Yellow Fields) -->
                <div id="more-info-content">
                    <div class="row g-3">
                        <!-- 1. Employee Context -->
                        <div class="col-12 col-md-6">
                            <div class="card border-0 bg-light p-3 rounded-3 h-100">
                                <div class="fw-bold small text-dark mb-2 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    <i class="ti ti-user me-1 text-primary"></i> Participant Attributes
                                </div>
                                <div class="row g-2 small">
                                    <div class="col-6">
                                        <span class="text-muted d-block">Grade:</span>
                                        <span class="fw-semibold text-dark" id="modal-more-grade">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Job Position:</span>
                                        <span class="fw-semibold text-dark" id="modal-more-job-position">-</span>
                                    </div>
                                    <div class="col-12">
                                        <span class="text-muted d-block">Attendance Reason:</span>
                                        <span class="fw-semibold text-dark" id="modal-more-attendance-reason">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Course Meta -->
                        <div class="col-12 col-md-6">
                            <div class="card border-0 bg-light p-3 rounded-3 h-100">
                                <div class="fw-bold small text-dark mb-2 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    <i class="ti ti-book me-1 text-primary"></i> Course Details
                                </div>
                                <div class="row g-2 small">
                                    <div class="col-12">
                                        <span class="text-muted d-block">Sub Course Title:</span>
                                        <span class="fw-semibold text-dark" id="modal-more-sub-course-title">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Level:</span>
                                        <span class="fw-semibold text-dark" id="modal-more-level">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Batch:</span>
                                        <span class="fw-semibold text-dark" id="modal-more-batch">-</span>
                                    </div>
                                    <div class="col-12">
                                        <span class="text-muted d-block">Instructor:</span>
                                        <span class="fw-semibold text-dark" id="modal-more-instructor">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Evaluations & Certification -->
                        <div class="col-12 col-md-6">
                            <div class="card border-0 bg-light p-3 rounded-3 h-100">
                                <div class="fw-bold small text-dark mb-2 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    <i class="ti ti-award me-1 text-primary"></i> Evaluations &amp; Certificate
                                </div>
                                <div class="row g-2 small">
                                    <div class="col-6">
                                        <span class="text-muted d-block">L2 Status:</span>
                                        <span class="fw-semibold text-dark" id="modal-more-l2-status">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">L3 Status:</span>
                                        <span class="fw-semibold text-dark" id="modal-more-l3-status">-</span>
                                    </div>
                                    <div class="col-12">
                                        <span class="text-muted d-block">Certificate Status:</span>
                                        <span class="badge bg-success-lt fw-bold px-2 py-1" id="modal-more-certificate-status">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Obligation & Bond -->
                        <div class="col-12 col-md-6">
                            <div class="card border-0 bg-light p-3 rounded-3 h-100">
                                <div class="fw-bold small text-dark mb-2 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    <i class="ti ti-shield-lock me-1 text-primary"></i> Service Bond Information
                                </div>
                                <div class="row g-2 small">
                                    <div class="col-6">
                                        <span class="text-muted d-block">Bond Status:</span>
                                        <span class="fw-semibold text-dark" id="modal-more-bond-status">No</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Bond Duration:</span>
                                        <span class="fw-semibold text-dark" id="modal-more-bond-duration">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Bond End Date:</span>
                                        <span class="fw-semibold text-dark" id="modal-more-bond-end-date">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Bond Amount (IDR):</span>
                                        <span class="fw-semibold text-dark font-monospace" id="modal-more-bond-amount">-</span>
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

$(document).on('click', '[data-bs-target="#moreInformationModal"], .btn-more-information', function(e) {
    var recordId = $(this).data('record-id') || $(this).data('id');
    var source = ($(this).data('record-source') || $(this).data('source') || 'offline').toString().toLowerCase();

    if (!recordId) return;

    $('#more-info-loading').show();
    $('#more-info-content').hide();

    $.ajax({
        url: '/manage/record/' + recordId + '/more-info?source=' + source,
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            $('#more-info-loading').hide();
            $('#more-info-content').show();
            if (res && res.data) {
                var d = res.data;
                $('#modal-more-grade').text(d.grade || '-');
                $('#modal-more-job-position').text(d.job_position || '-');
                $('#modal-more-sub-course-title').text(d.sub_course_title || '-');
                $('#modal-more-level').text(d.level || '-');
                $('#modal-more-batch').text(d.batch || '-');
                $('#modal-more-instructor').text(d.instructor || '-');
                $('#modal-more-attendance-reason').text(d.attendance_reason || '-');
                $('#modal-more-bond-status').text(d.bond_status || 'No');
                $('#modal-more-bond-duration').text(d.bond_duration || '-');
                $('#modal-more-bond-end-date').text(d.bond_end_date || '-');
                $('#modal-more-bond-amount').text(d.bond_amount_idr || '-');
                $('#modal-more-l2-status').text(d.l2_status || '-');
                $('#modal-more-l3-status').text(d.l3_status || '-');
                $('#modal-more-certificate-status').text(d.certificate_status || '-');
            }
        },
        error: function() {
            $('#more-info-loading').hide();
            $('#more-info-content').show();
        }
    });
});
</script>
