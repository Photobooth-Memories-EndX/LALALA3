<script>
$(function () {
    // ── View Learning Record Detail Modal Handler ─────────────
    let isFetchingRecordDetail = false;

    $(document).on('click', '.btn-view-learning-detail', function (e) {
        e.preventDefault();

        if (isFetchingRecordDetail) {
            return;
        }

        const source = $(this).data('source') || ($(this).data('source-type') === 'offline' ? 'offline' : 'online');
        const sourceType = $(this).data('source-type') || source;
        const sourceId = $(this).data('source-id') || $(this).data('id');

        if (!sourceId) {
            return;
        }

        const modalEl = document.getElementById('learningDetailModal');
        if (!modalEl) {
            return;
        }

        const modalInstance = (typeof bootstrap !== 'undefined' && bootstrap.Modal)
            ? (bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl))
            : null;

        // Show loading state, hide error and content
        $('#learningDetailLoading').show();
        $('#learningDetailError').hide();
        $('#learningDetailContent').hide();

        if (modalInstance) {
            modalInstance.show();
        } else if ($.fn.modal) {
            $('#learningDetailModal').modal('show');
        }

        isFetchingRecordDetail = true;

        // Dedicated profile record-detail endpoint
        const detailUrl = "{{ url('manage/user/profile') }}/{{ $profile->ssohash }}/record-detail/" + encodeURIComponent(source) + "/" + encodeURIComponent(sourceId);

        $.ajax({
            url: detailUrl,
            type: 'GET',
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}'
            },
            success: function (res) {
                if (res && (res.success || res.status === 'success' || res.data)) {
                    const d = res.data || res;
                    const emp = d.employee || {};
                    const lr = d.learning || {};
                    const pr = d.progress || {};
                    const rec = d.record_info || {};
                    const ob = d.obligation || {};
                    const as = d.assessment || {};
                    const ts = d.timestamps || {};

                    // 1. Employee Information (including Grade, Job Position)
                    $('#ld-emp-id').text(d.employee_id || emp.id_emp || '-');
                    $('#ld-emp-name').text(d.employee_name || emp.name || '-');
                    $('#ld-emp-dept').text(d.department || emp.department || '-');
                    $('#ld-emp-team').text(d.team || emp.team || '-');
                    $('#ld-emp-company').text(d.company || emp.company || '-');
                    $('#ld-emp-grade').text(d.grade || emp.grade || '-');
                    $('#ld-emp-job-position').text(d.job_position || emp.job_position || '-');

                    // 2. Learning Information (including Sub Course Title, Level, Batch, Sub Provider, Instructor)
                    $('#ld-content-title').text(d.course_title || lr.title || '-');
                    $('#ld-content-sub-title').text(d.sub_course_title || lr.sub_course_title || '-');
                    $('#ld-content-type').text(d.type || lr.type || '-');
                    $('#ld-content-classification').text(d.main_classification || d.classification || lr.classification || '-');
                    $('#ld-content-main-class').text(d.main_classification || d.classification || lr.main_classification || '-');
                    $('#ld-content-sub-class').text(d.sub_classification || lr.sub_classification || '-');
                    $('#ld-content-category').text(d.category || lr.category || '-');
                    $('#ld-content-source').text(d.source || lr.source || '-');
                    $('#ld-content-level').text(d.level || lr.level || '-');
                    $('#ld-content-batch').text(d.batch || lr.batch || '-');
                    $('#ld-content-delivery').text(d.delivery_method || lr.delivery_method || 'Online');
                    $('#ld-content-sub-delivery').text(d.sub_delivery_method || lr.sub_delivery_method || '-');
                    $('#ld-content-provider').text(d.provider_name || lr.provider_name || '-');
                    $('#ld-content-sub-provider').text(d.sub_provider || lr.sub_provider || '-');
                    $('#ld-content-venue').text(d.venue || lr.venue || '-');
                    $('#ld-content-instructor').text(d.instructor || lr.instructor || '-');

                    if ((d.source_type === 'playlist' || source === 'playlist') && lr.playlist_name && lr.playlist_name !== '-') {
                        $('#ld-playlist-name').text(lr.playlist_name);
                        $('#ld-playlist-container').show();
                    } else {
                        $('#ld-playlist-container').hide();
                    }

                    // 3. Progress & Duration Information
                    const rawProgress = (d.progress !== undefined && d.progress !== null) ? d.progress : pr.progress;
                    const pct = Math.max(0, Math.min(100, parseInt(rawProgress, 10) || 0));
                    $('#ld-progress-text').text(pct + '%');
                    $('#ld-progress-bar').css('width', pct + '%');

                    const isFinished = d.finish === 'Finished' || d.finish === '1' || pr.finish === 'Finished' || pr.is_finished === true || pct >= 100;
                    $('#ld-finish-badge')
                        .text(isFinished ? 'Finished' : (pct > 0 ? 'Ongoing' : 'Not Started'))
                        .removeClass('bg-success-lt text-success bg-warning-lt text-warning')
                        .addClass(isFinished ? 'bg-success-lt text-success' : 'bg-warning-lt text-warning');

                    $('#ld-progress-status').text(d.progress_status || pr.progress_status || (isFinished ? 'Finished' : (pct > 0 ? 'Ongoing' : 'Not Started')));
                    $('#ld-learning-status').text(d.learning_status || pr.learning_status || (isFinished ? 'Finished' : (pct > 0 ? 'Ongoing' : 'Not Started')));
                    $('#ld-status').text(d.requirement || pr.status || '-');
                    $('#ld-learning-hours').text(d.learning_hours ? (d.learning_hours + ' h') : (pr.learning_hours || '-'));
                    $('#ld-total-engagement-time').text(d.total_engagement_time || pr.total_engagement_time || '-');
                    $('#ld-duration').text(d.course_duration || pr.duration || '-');
                    $('#ld-course-duration').text(d.course_duration || pr.course_duration || pr.duration || '-');
                    $('#ld-total-recognized-duration').text(d.total_recognized_duration || pr.total_recognized_duration || '-');
                    $('#ld-current-time').text(d.current_time || pr.current_time || '-');
                    $('#ld-actual-watch').text(d.actual_watch_seconds || pr.actual_watch_seconds || '-');
                    $('#ld-afk-duration').text(d.afk_duration || pr.afk_duration || '-');

                    // 4. Record Information & Obligations (including Attendance Reason, Bond Status, Bond Duration, Bond End Date, Bond Amount)
                    $('#ld-record-month').text(d.record_month || rec.record_month || '-');
                    $('#ld-record-requirement').text(d.requirement || rec.requirement || '-');
                    $('#ld-record-attendance').text(d.attendance || rec.attendance || '-');
                    $('#ld-record-attendance-reason').text(d.attendance_reason || rec.attendance_reason || '-');
                    $('#ld-record-start-date').text((d.start_date || rec.start_date || '-') + ((d.start_time || rec.start_time) && (d.start_time || rec.start_time) !== '-' ? ', ' + (d.start_time || rec.start_time) : ''));
                    $('#ld-record-end-date').text((d.end_date || rec.end_date || '-') + ((d.end_time || rec.end_time) && (d.end_time || rec.end_time) !== '-' ? ', ' + (d.end_time || rec.end_time) : ''));
                    $('#ld-obligation-bond-status').text(d.bond_status || ob.bond_status || 'No');
                    $('#ld-obligation-bond-duration').text(d.bond_duration || ob.bond_duration || '-');
                    $('#ld-obligation-bond-end-date').text(d.bond_end_date || ob.bond_end_date || '-');
                    $('#ld-obligation-bond-amount').text(d.bond_amount || ob.bond_amount || '-');

                    // 5. Assessment & Certification (including L2 Status, L3 Status, Certificate Institution, Certificate Issued Date, Certificate Expired Date)
                    $('#ld-score-l1').text(d.score_l1 || as.score_l1 || '-');
                    $('#ld-score-l2').text(d.score_l2 || as.score_l2 || '-');
                    $('#ld-l2-status').text(d.l2_status || as.l2_status || '-');
                    $('#ld-score-l3').text(d.score_l3 || as.score_l3 || '-');
                    $('#ld-l3-status').text(d.l3_status || as.l3_status || '-');
                    $('#ld-pic').text(d.pic || as.pic || '-');
                    $('#ld-certificate').text(d.certificate_availability || as.certificate || '-');
                    $('#ld-certificate-institution').text(d.certificate_institution || '-');
                    $('#ld-certificate-issued-date').text(d.certificate_issued_date || '-');
                    $('#ld-certificate-expired-date').text(d.certificate_expired_date || '-');

                    // 6. Timestamps
                    $('#ld-created-at').text(d.created_at || ts.created_at || '-');
                    $('#ld-updated-at').text(d.updated_at || ts.updated_at || '-');
                    $('#ld-completed-at').text(d.completed_at || ts.completed_at || '-');

                    // Transition to content view
                    $('#learningDetailLoading').hide();
                    $('#learningDetailContent').fadeIn(150);
                } else {
                    $('#learningDetailLoading').hide();
                    $('#learningDetailErrorMessage').text((res && res.message) ? res.message : 'Unable to load learning record details.');
                    $('#learningDetailError').show();
                }
            },
            error: function (xhr) {
                $('#learningDetailLoading').hide();
                let errMsg = 'Unable to load learning record details.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errMsg = xhr.responseJSON.message;
                }
                $('#learningDetailErrorMessage').text(errMsg);
                $('#learningDetailError').show();
            },
            complete: function () {
                isFetchingRecordDetail = false;
            }
        });
    });
});
</script>
