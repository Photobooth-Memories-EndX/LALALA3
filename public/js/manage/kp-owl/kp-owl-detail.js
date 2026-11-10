/**
 * KP OWL Detail Modal Controller
 */
$(document).ready(function () {
    $(document).on('click', '.btn-kp-owl-detail', function (e) {
        e.preventDefault();

        const sourceType = $(this).data('source-type');
        const id = $(this).data('id');

        if (!sourceType || !id) {
            return;
        }

        const modal = new bootstrap.Modal(document.getElementById('kp-owl-detail-modal'));
        resetModalFields();
        modal.show();

        const detailUrl = window.KP_OWL_ROUTES.detailBase + '/' + encodeURIComponent(sourceType) + '/' + encodeURIComponent(id) + '/detail';

        $.ajax({
            url: detailUrl,
            type: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    populateModalFields(res);
                } else {
                    alert(res.message || 'Error loading learning record details.');
                }
            },
            error: function (xhr) {
                console.error('Error fetching record detail:', xhr);
                alert('Could not fetch details. Please try again.');
            }
        });
    });

    function resetModalFields() {
        $('#detail-modal-subtitle').text('Loading details...');
        $('#detail-emp-id').text('...');
        $('#detail-emp-name').text('...');
        $('#detail-emp-grade').text('-');
        $('#detail-emp-position').text('...');
        $('#detail-emp-team').text('...');
        $('#detail-emp-department').text('...');
        $('#detail-emp-company').text('...');

        $('#detail-course-title').text('...');
        $('#detail-sub-course-title').text('-');
        $('#detail-type-cat').text('...');
        $('#detail-level').text('-');
        $('#detail-batch').text('-');
        $('#detail-main-class').text('...');
        $('#detail-instructor').text('...');
        $('#detail-provider').text('Internal (KP-OWL)');
        $('#detail-sub-provider').text('-');
        $('#detail-course-duration').text('...');
        $('#detail-recognized-duration').text('...');
        $('#detail-learning-hours').text('...');
        $('#detail-engagement-time').text('...');

        $('#detail-attendance').text('...');
        $('#detail-attendance-reason').text('-');
        $('#detail-period').text('...');

        $('#detail-bond-status').text('No');
        $('#detail-bond-duration').text('-');
        $('#detail-bond-end-date').text('-');
        $('#detail-bond-amount').text('-');

        $('#detail-score-l1').text('-');
        $('#detail-score-l2').text('...');
        $('#detail-l2-status').text('...');
        $('#detail-score-l3').text('-');
        $('#detail-l3-status').text('-');

        $('#detail-cert-avail').text('...');
        $('#detail-cert-institution').text('...');
        $('#detail-cert-issued').text('...');
        $('#detail-cert-expired').text('-');
    }

    function populateModalFields(data) {
        const emp = data.employee || {};
        const lrn = data.learning || {};
        const att = data.attendance || {};
        const bnd = data.bond || {};
        const asm = data.assessment || {};
        const crt = data.certificate || {};

        $('#detail-modal-subtitle').text('KP OWL • ' + (data.source_type === 'playlist' ? 'Playlist Record' : 'Standalone Content Record'));

        // Employee
        $('#detail-emp-id').text(emp.id_emp || '-');
        $('#detail-emp-name').text(emp.name || '-');
        $('#detail-emp-grade').text(data.grade || emp.grade || '-');
        $('#detail-emp-position').text(data.job_position || emp.job_position || '-');
        $('#detail-emp-team').text(emp.team || '-');
        $('#detail-emp-department').text(emp.department || '-');
        $('#detail-emp-company').text(emp.company || 'PT Krakatau Posco');

        // Learning
        $('#detail-course-title').text(lrn.title || '-');
        $('#detail-sub-course-title').text(data.sub_course_title || '-');
        $('#detail-type-cat').text((lrn.type || '-') + ' / ' + (lrn.category || '-'));
        $('#detail-level').text(data.level || '-');
        $('#detail-batch').text(data.batch || '-');
        $('#detail-main-class').text(lrn.main_classification || '-');
        $('#detail-instructor').text(data.instructor || lrn.instructor || '-');
        $('#detail-sub-provider').text(data.sub_provider || '-');
        $('#detail-course-duration').text(lrn.course_duration || '-');
        $('#detail-recognized-duration').text(lrn.total_recognized_duration || '-');
        $('#detail-learning-hours').text(lrn.learning_hours || '-');
        $('#detail-engagement-time').text(lrn.total_engagement_time || '-');

        // Attendance
        $('#detail-attendance').text(att.attendance || '-');
        $('#detail-attendance-reason').text(data.attendance_reason || '-');
        $('#detail-period').text(att.period || '-');

        // Bond
        $('#detail-bond-status').text(data.bond_status || 'No');
        $('#detail-bond-duration').text(data.bond_duration || '-');
        $('#detail-bond-end-date').text(data.bond_end_date || '-');
        $('#detail-bond-amount').text(data.bond_amount || '-');

        // Assessment
        $('#detail-score-l1').text(asm.score_l1 || '-');
        $('#detail-score-l2').text(asm.score_l2 || '-');
        $('#detail-l2-status').text(data.l2_status || asm.l2_status || '-');
        $('#detail-score-l3').text(asm.score_l3 || '-');
        $('#detail-l3-status').text(data.l3_status || asm.l3_status || '-');

        // Certificate
        $('#detail-cert-avail').text(crt.availability || '-');
        $('#detail-cert-institution').text(data.certificate_institution || crt.institution || '-');
        $('#detail-cert-issued').text(data.certificate_issued_date || crt.issued_date || '-');
        $('#detail-cert-expired').text(data.certificate_expired_date || '-');
    }
});
