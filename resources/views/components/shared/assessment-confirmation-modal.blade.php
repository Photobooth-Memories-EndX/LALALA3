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
                <div class="assessment-detail-card">
                    <!-- Assessment Header Label & Title -->
                    <div class="assessment-detail-section">
                        <div class="assessment-detail-label" id="assessmentModalTypeHeader">ASSESSMENT CONTENT</div>
                        <div class="assessment-detail-title" id="assessmentModalName">-</div>
                    </div>

                    <div class="assessment-detail-divider"></div>

                    <!-- 2x2 Grid: Date Time (Col 1, Row 1), Passing Grade (Col 2, Row 1), Attempt (Col 1, Row 2), Batch (Col 2, Row 2) -->
                    <div class="row g-3">
                        <!-- Row 1, Col 1: Date Time -->
                        <div class="col-12 col-sm-6">
                            <div class="assessment-detail-label">
                                <i class="ti ti-calendar-event me-1"></i>Date Time
                            </div>
                            <div class="assessment-datetime-refined">
                                <div class="assessment-datetime-row">
                                    <span class="assessment-datetime-val" id="assessmentModalFromDate">-</span>
                                </div>
                                {{-- <div class="assessment-datetime-arrow text-muted">
                                    <i class="ti ti-arrow-down"></i>
                                </div> --}}
                                <div class="assessment-datetime-row">
                                    <span class="assessment-datetime-val" id="assessmentModalToDate">-</span>
                                </div>
                            </div>
                        </div>

                        <!-- Row 1, Col 2: Passing Grade -->
                        <div class="col-12 col-sm-6">
                            <div class="assessment-detail-label">Passing Grade</div>
                            <div class="assessment-detail-meta-value fw-bold text-primary" id="assessmentModalPassingGrade">-</div>
                        </div>

                        <!-- Row 2, Col 1: Attempt -->
                        <div class="col-12 col-sm-6">
                            <div class="assessment-detail-label">Attempt</div>
                            <div class="assessment-detail-meta-value fw-semibold" id="assessmentModalAttempt">-</div>
                        </div>

                        <!-- Row 2, Col 2: Batch -->
                        <div class="col-12 col-sm-6">
                            <div class="assessment-detail-label">Batch</div>
                            <div class="assessment-detail-batch-value" id="assessmentModalBatch">-</div>
                        </div>
                    </div>
                </div>

                <p class="assessment-confirm-question mt-3" id="assessmentModalQuestion">
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
                        class="btn btn-success text-white btn-assessment-start-confirm"
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

        // Date formatter helper (converts raw/slash dates to e.g. "29 Sep 2025, 12:00")
        function formatAssessmentDate(dateStr) {
            if (!dateStr || dateStr === '-' || dateStr === 'null' || dateStr === 'undefined') {
                return '-';
            }
            var str = String(dateStr).trim();
            // If already in "29 Sep 2025, 12:00" format, return as is
            if (/^[0-9]{1,2}\s+[A-Za-z]{3}\s+[0-9]{4},\s+[0-9]{2}:[0-9]{2}$/.test(str)) {
                return str;
            }
            try {
                var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                var slashParts = str.match(/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})\s+(\d{1,2}):(\d{2})/);
                if (slashParts) {
                    var day = parseInt(slashParts[1], 10);
                    var monthIdx = parseInt(slashParts[2], 10) - 1;
                    var year = parseInt(slashParts[3], 10);
                    if (year < 100) year += 2000;
                    var hours = slashParts[4].padStart(2, '0');
                    var mins = slashParts[5].padStart(2, '0');
                    var monthName = months[monthIdx] || '';
                    return (day < 10 ? '0' + day : day) + ' ' + monthName + ' ' + year + ', ' + hours + ':' + mins;
                }
                var parsed = new Date(str);
                if (!isNaN(parsed.getTime())) {
                    var d = parsed.getDate();
                    var m = months[parsed.getMonth()];
                    var y = parsed.getFullYear();
                    var h = String(parsed.getHours()).padStart(2, '0');
                    var min = String(parsed.getMinutes()).padStart(2, '0');
                    return (d < 10 ? '0' + d : d) + ' ' + m + ' ' + y + ', ' + h + ':' + min;
                }
            } catch (e) {}
            return str;
        }

        // Single unified delegated event handler with event namespacing for Start Test and Remedial
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

                // 5. Resolve Type to build Header Label (trainingplan -> ASSESSMENT PLAYLIST)
                var rawTypeLabel = $btn.data('assessment-type-label') || $row.attr('data-assessment-type-label');
                var rawType = $btn.data('assessment-type') || $row.attr('data-assessment-type');
                
                var headerLabel = 'ASSESSMENT CONTENT';
                var typeToMap = rawTypeLabel || rawType;
                if (typeToMap && typeToMap !== '-') {
                    var lowerType = String(typeToMap).trim().toLowerCase();
                    if (lowerType === 'trainingplan' || lowerType === 'playlist') {
                        headerLabel = 'ASSESSMENT PLAYLIST';
                    } else if (lowerType === 'global') {
                        headerLabel = 'ASSESSMENT GLOBAL';
                    } else if (lowerType === 'content') {
                        headerLabel = 'ASSESSMENT CONTENT';
                    } else if (lowerType === 'youtube') {
                        headerLabel = 'ASSESSMENT MOOC';
                    } else {
                        headerLabel = 'ASSESSMENT ' + String(typeToMap).toUpperCase();
                    }
                }

                // 6. Resolve Date Time, Passing Grade, and Attempt Information
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

                // 7. Action Type (start vs remedial)
                var actionType = $btn.data('assessment-action-type') 
                    || ($btn.hasClass('btn-remedial') || $btn.hasClass('btn-assessment-remedial') ? 'remedial' : 'start');
                console.log('[Assessment Popup] Resolved actionType:', actionType);

                var $modal = $('#assessmentConfirmationModal');
                if (!$modal.length) {
                    console.error('[Assessment Popup] Error: #assessmentConfirmationModal not found in DOM.');
                    return;
                }

                // 8. Complete Reset Modal State
                $modal.removeClass('is-remedial-modal');
                $modal.find('#assessmentModalTypeHeader').text('ASSESSMENT CONTENT');
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
                    .addClass('btn-success text-white btn-assessment-start-confirm')
                    .html('<i class="ti ti-player-play me-1"></i> <span>Start Test</span>');
                $modal.removeData('target-url').removeData('assessment-id').removeData('action-type');

                // 9. Apply Action-Type Specific Configuration & Display Data
                if (actionType === 'remedial') {
                    $modal.addClass('is-remedial-modal');
                    $modal.find('#assessmentModalHeaderIconInner').attr('class', 'ti ti-refresh');
                    $modal.find('#assessmentConfirmationModalTitle').text('Assessment Remedial');
                    $modal.find('#assessmentModalSubtitle').text('Remedial Confirmation');
                    $modal.find('#assessmentModalQuestion').text('Apakah Anda yakin ingin mengerjakan remedial assessment ini?');
                    
                    $modal.find('#btnConfirmAssessmentAction')
                        .removeClass('btn-success btn-assessment-start-confirm')
                        .addClass('btn-warning text-white btn-assessment-remedial-confirm')
                        .html('<i class="ti ti-refresh me-1"></i> <span>Remedial</span>');
                } else {
                    $modal.removeClass('is-remedial-modal');
                    $modal.find('#assessmentModalHeaderIconInner').attr('class', 'ti ti-player-play');
                    $modal.find('#assessmentConfirmationModalTitle').text('Start Assessment');
                    $modal.find('#assessmentModalSubtitle').text('Confirmation');
                    $modal.find('#assessmentModalQuestion').text('Apakah Anda yakin ingin memulai assessment ini?');
                    
                    $modal.find('#btnConfirmAssessmentAction')
                        .removeClass('btn-warning btn-assessment-remedial-confirm')
                        .addClass('btn-success text-white btn-assessment-start-confirm')
                        .html('<i class="ti ti-player-play me-1"></i> <span>Start Test</span>');
                }

                $modal.find('#assessmentModalTypeHeader').text(headerLabel);
                $modal.find('#assessmentModalName').text(title);
                $modal.find('#assessmentModalBatch').text(batchDisplay);
                $modal.find('#assessmentModalFromDate').text(formatAssessmentDate(fromDate));
                $modal.find('#assessmentModalToDate').text(formatAssessmentDate(toDate));
                $modal.find('#assessmentModalPassingGrade').text(passingGrade && passingGrade !== '' ? passingGrade : '-');
                $modal.find('#assessmentModalAttempt').text(attemptDisplay);
                
                $modal.data('assessment-id', assessmentId);
                $modal.data('target-url', targetUrl);
                $modal.data('action-type', actionType);

                console.log('[Assessment Popup] Opening modal for assessment_id:', assessmentId, 'action:', actionType);

                // 10. Show Modal
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

        // Prevent click navigation on assessment name elements
        $(document)
            .off('click.assessmentName', '.assessment-name, .assessment-title-link')
            .on('click.assessmentName', '.assessment-name, .assessment-title-link', function (e) {
                e.preventDefault();
                e.stopPropagation();
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
