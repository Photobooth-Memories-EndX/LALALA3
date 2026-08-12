<div class="modal fade"
     id="assessmentConfirmationModal"
     tabindex="-1"
     role="dialog"
     aria-labelledby="assessmentConfirmationModalTitle"
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div class="modal-title-wrapper">
                    <div class="modal-title-icon" id="assessmentModalHeaderIcon">
                        <i class="ti ti-player-play" id="assessmentModalHeaderIconInner"></i>
                    </div>
                    <div>
                        <h4 class="modal-title fw-bold mb-0" id="assessmentConfirmationModalTitle">Start Assessment</h4>
                        <div class="text-muted small" id="assessmentModalSubtitle">Confirmation</div>
                    </div>
                </div>
                <button type="button"
                        class="btn-close modal-close-btn border-0 bg-transparent text-muted ms-auto"
                        data-dismiss="modal"
                        data-bs-dismiss="modal"
                        aria-label="Close">
                    <i class="ti ti-x fs-3"></i>
                </button>
            </div>

            <div class="modal-body">
                <div class="assessment-detail-card mb-3">
                    <div class="assessment-detail-section mb-3">
                        <div class="assessment-detail-label">Assessment</div>
                        <div class="assessment-detail-title" id="assessmentModalName">-</div>
                    </div>
                    
                    <div class="assessment-detail-section">
                        <div class="assessment-detail-label">Batch</div>
                        <div class="assessment-detail-batch-value" id="assessmentModalBatch">-</div>
                    </div>
                </div>

                <div class="assessment-detail-card mb-3">
                    <div class="row g-3">
                        <div class="col-12 col-sm-7">
                            <div class="assessment-detail-label">Date Time</div>
                            <div class="assessment-detail-meta-text mb-1">
                                <span class="text-muted">From:</span> <strong id="assessmentModalFromDate">-</strong>
                            </div>
                            <div class="assessment-detail-meta-text">
                                <span class="text-muted">To:</span> <strong id="assessmentModalToDate">-</strong>
                            </div>
                        </div>
                        <div class="col-12 col-sm-5">
                            <div class="assessment-detail-label">Passing Grade</div>
                            <div class="assessment-detail-meta-value fw-bold text-primary" id="assessmentModalPassingGrade">-</div>
                        </div>
                        <div class="col-12 mt-2 pt-2 border-top">
                            <div class="assessment-detail-label">Attempt</div>
                            <div class="assessment-detail-meta-value fw-semibold" id="assessmentModalAttempt">-</div>
                        </div>
                    </div>
                </div>

                <p class="assessment-confirm-question" id="assessmentModalQuestion">
                    Apakah Anda yakin ingin memulai assessment ini?
                </p>
            </div>

            <div class="modal-footer border-0">
                <button type="button"
                        class="btn btn-outline-secondary"
                        data-dismiss="modal"
                        data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="button"
                        class="btn btn-primary btn-assessment-start-confirm"
                        id="btnConfirmAssessmentAction">
                    <i class="ti ti-player-play me-1"></i>
                    <span>Start Test</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    (function ($) {
        'use strict';

        console.log('[Assessment Popup] Script loaded and initialized');

        // Single unified delegated event handler with event namespacing
        $(document)
            .off('click.assessmentConfirmation', '.btn-start-test, .btn-remedial, .btn-assessment-start, .btn-assessment-remedial')
            .on('click.assessmentConfirmation', '.btn-start-test, .btn-remedial, .btn-assessment-start, .btn-assessment-remedial', function (e) {
                console.log('[Assessment Popup] Click event detected on action button');
                e.preventDefault();
                var $btn = $(this);

                // 1. Primary Identifier: assessment_id
                var assessmentId = $btn.data('assessment-id') || $btn.attr('data-assessment-id');
                console.log('[Assessment Popup] Resolved assessment_id:', assessmentId);

                // Safeguard: Check if assessmentId is missing or empty
                if (!assessmentId) {
                    console.error('[Assessment Popup] Cancelled: assessment_id is missing or invalid.');
                    return;
                }

                // 2. Existing URL action target
                var targetUrl = $btn.attr('href') || $btn.data('action-url');
                console.log('[Assessment Popup] Resolved targetUrl:', targetUrl);

                // Safeguard: Check if targetUrl is missing or invalid
                if (!targetUrl || targetUrl === '#' || targetUrl === 'javascript:void(0);') {
                    console.error('[Assessment Popup] Cancelled: Target URL is missing or invalid.');
                    return;
                }

                // 3. Resolve matching row using assessment_id
                var $row = $('tr[data-assessment-id="' + assessmentId + '"], .assessment-row[data-assessment-id="' + assessmentId + '"]');
                if (!$row.length) {
                    $row = $btn.closest('tr, .assessment-row');
                }

                // 4. Resolve Title & Batch based on assessmentId
                var title = $btn.data('assessment-title') 
                    || $row.attr('data-assessment-title') 
                    || $row.data('assessment-title')
                    || $.trim($row.find('.assessment-title, .assessment-name').first().text()) 
                    || '-';

                var batchRaw = $btn.data('assessment-batch') 
                    || $row.attr('data-assessment-batch') 
                    || $row.data('assessment-batch');

                var batchDisplay = '-';
                if (batchRaw !== undefined && batchRaw !== null && batchRaw !== '' && batchRaw !== '-') {
                    var batchStr = String(batchRaw).trim();
                    batchDisplay = batchStr.toLowerCase().indexOf('batch') !== -1 ? batchStr : 'Batch ' + batchStr;
                }

                // 5. Resolve Date Time, Passing Grade, and Attempt Information
                var fromDate = $btn.data('assessment-from-date') || $row.attr('data-assessment-from-date') || '-';
                var toDate = $btn.data('assessment-to-date') || $row.attr('data-assessment-to-date') || '-';
                var passingGrade = $btn.data('assessment-passing-grade') || $row.attr('data-assessment-passing-grade') || $row.attr('data-assessment-grade') || '-';

                var userAttempts = $btn.data('assessment-user-attempts');
                if (userAttempts === undefined || userAttempts === null || userAttempts === '') {
                    userAttempts = $row.attr('data-assessment-user-attempts') || 0;
                }

                var maxAttempts = $btn.data('assessment-max-attempts');
                if (maxAttempts === undefined || maxAttempts === null || maxAttempts === '') {
                    maxAttempts = $row.attr('data-assessment-max-attempts') || 1;
                }

                var attemptDisplay = (userAttempts !== undefined && userAttempts !== null && userAttempts !== '')
                    ? (userAttempts + ' / ' + maxAttempts + ' Attempts')
                    : '-';

                // 6. Action Type (start vs remedial)
                var actionType = $btn.data('assessment-action-type') 
                    || ($btn.hasClass('btn-remedial') || $btn.hasClass('btn-assessment-remedial') ? 'remedial' : 'start');
                console.log('[Assessment Popup] Resolved actionType:', actionType);

                var $modal = $('#assessmentConfirmationModal');
                if (!$modal.length) {
                    console.error('[Assessment Popup] Error: #assessmentConfirmationModal not found in DOM.');
                    return;
                }

                // 7. Complete Reset Modal State
                $modal.removeClass('is-remedial-modal');
                $modal.find('#assessmentModalName').text('-');
                $modal.find('#assessmentModalBatch').text('-');
                $modal.find('#assessmentModalFromDate').text('-');
                $modal.find('#assessmentModalToDate').text('-');
                $modal.find('#assessmentModalPassingGrade').text('-');
                $modal.find('#assessmentModalAttempt').text('-');
                $modal.find('#assessmentConfirmationModalTitle').text('Start Assessment');
                $modal.find('#assessmentModalSubtitle').text('Confirmation');
                $modal.find('#assessmentModalQuestion').text('Apakah Anda yakin ingin memulai assessment ini?');
                $modal.find('#assessmentModalHeaderIconInner').attr('class', 'ti ti-player-play');
                $modal.find('#btnConfirmAssessmentAction')
                    .removeClass('btn-warning text-white btn-assessment-remedial-confirm')
                    .addClass('btn-primary btn-assessment-start-confirm')
                    .html('<i class="ti ti-player-play me-1"></i> <span>Start Test</span>');
                $modal.removeData('target-url').removeData('assessment-id').removeData('action-type');

                // 8. Apply Action-Type & Display Data
                if (actionType === 'remedial') {
                    $modal.addClass('is-remedial-modal');
                    $modal.find('#assessmentModalHeaderIconInner').attr('class', 'ti ti-refresh');
                    $modal.find('#assessmentConfirmationModalTitle').text('Assessment Remedial');
                    $modal.find('#assessmentModalSubtitle').text('Remedial Confirmation');
                    $modal.find('#assessmentModalQuestion').text('Apakah Anda yakin ingin mengerjakan remedial assessment ini?');
                    
                    $modal.find('#btnConfirmAssessmentAction')
                        .removeClass('btn-primary btn-assessment-start-confirm')
                        .addClass('btn-warning text-white btn-assessment-remedial-confirm')
                        .html('<i class="ti ti-refresh me-1"></i> <span>Remedial</span>');
                } else {
                    $modal.removeClass('is-remedial-modal');
                    $modal.find('#assessmentModalHeaderIconInner').attr('class', 'ti ti-player-play');
                    $modal.find('#assessmentConfirmationModalTitle').text('Start Assessment');
                    $modal.find('#assessmentModalSubtitle').text('Confirmation');
                    $modal.find('#assessmentModalQuestion').text('Apakah Anda yakin ingin memulai assessment ini?');
                    
                    $modal.find('#btnConfirmAssessmentAction')
                        .removeClass('btn-warning text-white btn-assessment-remedial-confirm')
                        .addClass('btn-primary btn-assessment-start-confirm')
                        .html('<i class="ti ti-player-play me-1"></i> <span>Start Test</span>');
                }

                $modal.find('#assessmentModalName').text(title);
                $modal.find('#assessmentModalBatch').text(batchDisplay);
                $modal.find('#assessmentModalFromDate').text(fromDate && fromDate !== '' ? fromDate : '-');
                $modal.find('#assessmentModalToDate').text(toDate && toDate !== '' ? toDate : '-');
                $modal.find('#assessmentModalPassingGrade').text(passingGrade && passingGrade !== '' ? passingGrade : '-');
                $modal.find('#assessmentModalAttempt').text(attemptDisplay);
                
                $modal.data('assessment-id', assessmentId);
                $modal.data('target-url', targetUrl);
                $modal.data('action-type', actionType);

                console.log('[Assessment Popup] Opening modal for assessment_id:', assessmentId);

                // 9. Show Modal
                if (typeof $.fn.modal === 'function') {
                    $modal.modal('show');
                } else if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    var bsModal = bootstrap.Modal.getOrCreateInstance($modal[0]);
                    bsModal.show();
                } else {
                    $modal.addClass('show').css('display', 'block');
                }
            });

        // Delegated confirmation submission handler
        $(document)
            .off('click.assessmentConfirmSubmit', '#btnConfirmAssessmentAction')
            .on('click.assessmentConfirmSubmit', '#btnConfirmAssessmentAction', function (e) {
                e.preventDefault();
                var $modal = $('#assessmentConfirmationModal');
                var targetUrl = $modal.data('target-url');
                console.log('[Assessment Popup] Confirm clicked, navigating to:', targetUrl);
                if (targetUrl && targetUrl !== '#' && targetUrl !== 'javascript:void(0);') {
                    window.location.href = targetUrl;
                } else {
                    console.error('[Assessment Popup] Action submission cancelled: Target URL is missing or invalid.');
                }
            });

        // Hover & Tap Popover untuk Nama Assessment
        function initAssessmentNamePopovers() {
            $('[data-assessment-id].assessment-name, [data-assessment-id].assessment-title-link, tr[data-assessment-id] .assessment-title').each(function () {
                var $el = $(this);
                if ($el.data('bs.popover') || $el.data('popover')) {
                    return;
                }

                var assessmentId = $el.data('assessment-id') || $el.closest('[data-assessment-id]').data('assessment-id');
                var $row = $('tr[data-assessment-id="' + assessmentId + '"]');
                if (!$row.length) {
                    $row = $el.closest('tr, .assessment-row');
                }

                var title = $el.data('assessment-title') || $row.attr('data-assessment-title') || $.trim($el.text()) || '-';
                var batchRaw = $el.data('assessment-batch') || $row.attr('data-assessment-batch');
                var batchDisplay = '-';
                if (batchRaw !== undefined && batchRaw !== null && batchRaw !== '' && batchRaw !== '-') {
                    var batchStr = String(batchRaw).trim();
                    batchDisplay = batchStr.toLowerCase().indexOf('batch') !== -1 ? batchStr : 'Batch ' + batchStr;
                }

                var popoverHtml = '<div class="assessment-info-popover-card">' +
                    '<div class="assessment-info-popover-header">Assessment Info</div>' +
                    '<div class="assessment-info-popover-title">' + $('<div>').text(title).html() + '</div>' +
                    '<div class="assessment-info-popover-meta">' +
                    '<i class="ti ti-layers-intersect"></i> ' + $('<div>').text(batchDisplay).html() +
                    '</div>' +
                    '</div>';

                if (typeof $.fn.popover === 'function') {
                    $el.popover({
                        trigger: 'hover focus',
                        placement: 'top',
                        html: true,
                        sanitize: false,
                        content: popoverHtml,
                        template: '<div class="popover assessment-info-popover" role="tooltip"><div class="popover-arrow"></div><div class="popover-body"></div></div>',
                        container: 'body',
                        boundary: 'viewport'
                    });
                }
            });
        }

        $(document).ready(function () {
            initAssessmentNamePopovers();
        });

        $(document).on('draw.dt', function () {
            initAssessmentNamePopovers();
        });
    })(jQuery);
</script>
