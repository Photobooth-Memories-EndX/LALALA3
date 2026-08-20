<div class="modal fade"
     id="assessmentConfirmationModal"
     tabindex="-1"
     role="dialog"
     aria-labelledby="assessmentConfirmationModalTitle"
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 480px !important; width: 100%;">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="modal-title-icon d-inline-flex align-items-center justify-content-center rounded-circle p-2" 
                         id="assessmentModalHeaderIconBox" 
                         style="width: 40px; height: 40px; background-color: rgba(40, 190, 88, 0.12); color: #28be58;">
                        <i class="ti ti-player-play fs-2" id="assessmentModalHeaderIconInner"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="assessmentConfirmationModalTitle" style="color: #28be58 !important;">Start Assessment</h5>
                        <div class="text-muted small" id="assessmentModalSubtitle">Confirmation</div>
                    </div>
                </div>
                <button type="button"
                        class="btn-close close modal-close-btn border-0 bg-transparent text-muted ms-auto"
                        data-dismiss="modal"
                        data-bs-dismiss="modal"
                        aria-label="Close">
                    <i class="ti ti-x fs-3"></i>
                </button>
            </div>

            <div class="modal-body px-4 py-3">
                <!-- INFORMATION SECTION -->
                <h6 class="fw-bold text-dark text-uppercase mb-2" style="font-size: 0.85rem; letter-spacing: 0.5px; color: #000000 !important;">
                    INFORMATION
                </h6>
                <div class="bg-light rounded-3 p-3 mb-3">
                    <!-- ASSESSMENT TITLE / CONTENT HEADER -->
                    <div class="assessment-title-wrapper mb-3 pb-2 border-bottom">
                        <div class="text-dark small fw-bold text-uppercase mb-1" 
                             id="assessmentModalTypeHeader" 
                             style="font-size: 0.75rem; color: #212529 !important; letter-spacing: 0.5px;">
                            ASSESSMENT CONTENT
                        </div>
                        <h5 class="fw-bold text-dark mb-0 fs-5" 
                            id="assessmentModalName" 
                            style="font-size: 1.1rem !important; line-height: 1.4; color: #1a1d20 !important;">
                            -
                        </h5>
                    </div>

                    <!-- DETAILS GRID -->
                    <div class="row g-3">
                        <!-- Date & Time (Stacked) -->
                        <div class="col-6">
                            <strong class="text-dark d-block mb-1" style="font-size: 0.78rem; color: #212529 !important;">DATE & TIME</strong>
                            <div class="text-dark fw-normal" id="assessmentModalDateTime" style="color: #212529 !important; font-size: 0.82rem; line-height: 1.35;">
                                -
                            </div>
                        </div>

                        <!-- Passing Grade -->
                        <div class="col-6">
                            <strong class="text-dark d-block mb-1" style="font-size: 0.78rem; color: #212529 !important;">PASSING GRADE</strong>
                            <div class="text-dark fw-bold" id="assessmentModalPassingGrade" style="color: #212529 !important; font-size: 0.85rem;">
                                -
                            </div>
                        </div>

                        <!-- Duration -->
                        <div class="col-6">
                            <strong class="text-dark d-block mb-1" style="font-size: 0.78rem; color: #212529 !important;">DURATION</strong>
                            <div class="text-dark" id="assessmentModalDuration" style="color: #212529 !important; font-size: 0.82rem;">
                                -
                            </div>
                        </div>

                        <!-- Batch (Global) atau Attempt (Content) -->
                        <div class="col-6" id="assessmentModalBatchContainer" style="display: none;">
                            <strong class="text-dark d-block mb-1" style="font-size: 0.78rem; color: #212529 !important;">BATCH</strong>
                            <div class="text-dark" id="assessmentModalBatch" style="color: #212529 !important; font-size: 0.82rem;">
                                -
                            </div>
                        </div>
                        <div class="col-6" id="assessmentModalAttemptContainer">
                            <strong class="text-dark d-block mb-1" style="font-size: 0.78rem; color: #212529 !important;">ATTEMPT</strong>
                            <div class="text-dark" id="assessmentModalAttempt" style="color: #212529 !important; font-size: 0.82rem;">
                                -
                            </div>
                        </div>
                    </div>
                </div>

                <!-- REQUIREMENTS / NOTICE SECTION -->
                <h6 class="fw-bold text-dark text-uppercase mt-3 mb-2" id="assessmentModalNoticeHeader" style="font-size: 0.85rem; letter-spacing: 0.5px; color: #000000 !important;">
                    REQUIREMENTS
                </h6>
                <div id="assessmentModalNoticeContent">
                    <!-- Global Start Notice (English) -->
                    <ol class="small text-dark ps-3 mb-0" id="assessmentNoticeGlobalStart" style="color: #212529 !important; display: none;">
                        <li>Make sure you are in a safe and conducive environment, and that your device is ready to use.</li>
                        <li><em>Once started, the assessment cannot be exited until it is completed or the time expires.</em></li>
                        <li>The assessment will take place according to the stated date & time.</li>
                        <li>If an error occurs on your device or the connection is interrupted, your answers will be saved. Re-enter the assessment to continue as long as time is still available.</li>
                    </ol>
                    <!-- Start Requirements (English) -->
                    <ol class="small text-dark ps-3 mb-0" id="assessmentNoticeStart" style="color: #212529 !important;">
                        <li>Ensure yourself, your device, and your environment are in a safe and conducive condition.</li>
                        <li>The assessment will take place according to the stated duration.</li>
                        <li>Once started, the assessment cannot be exited until it is completed or time expires.</li>
                        <li>If an error or technical issue occurs, your answers are saved. Re-enter immediately to continue as long as time remains.</li>
                        <li>Perform your best by utilizing the available opportunity.</li>
                    </ol>
                    <!-- Remedial Requirements (English) -->
                    <ol class="small text-dark ps-3 mb-0" id="assessmentNoticeRemedial" style="color: #212529 !important; display: none;">
                        <li>Ensure yourself, your device, and your environment are in a safe and conducive condition.</li>
                        <li>Remedial will take place according to the stated duration.</li>
                        <li>Once started, remedial cannot be exited until it is completed or time expires.</li>
                        <li>If an error or technical issue occurs, your answers are saved. Re-enter immediately to continue as long as time remains.</li>
                        <li>Perform your best by utilizing the available remedial opportunity.</li>
                    </ol>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                <button type="button"
                        class="btn btn-light rounded-pill px-4 text-secondary"
                        data-dismiss="modal"
                        data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="button"
                        class="btn rounded-pill px-4 fw-semibold text-white shadow-sm d-inline-flex align-items-center gap-1"
                        id="btnConfirmAssessmentAction"
                        style="background-color: #28be58 !important; border-color: #28be58 !important;">
                    <i class="ti ti-player-play fs-6"></i>
                    <span>Start Test</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    (function ($) {
        'use strict';

        function openModalDirect(modalId) {
            var modalEl = document.getElementById(modalId);
            if (!modalEl) return;
            if (typeof $ !== 'undefined' && $.fn && typeof $.fn.modal === 'function') {
                $(modalEl).modal('show');
            } else if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                var instance = window.bootstrap.Modal.getInstance ? window.bootstrap.Modal.getInstance(modalEl) : null;
                if (!instance) instance = new window.bootstrap.Modal(modalEl);
                instance.show();
            } else {
                modalEl.style.display = 'block';
                modalEl.classList.add('show');
                document.body.classList.add('modal-open');
            }
        }

        function formatAssessmentDate(dateStr) {
            if (!dateStr || dateStr === '-' || dateStr === 'null' || dateStr === 'undefined') {
                return '-';
            }
            var str = String(dateStr).trim();
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

        // Unified click handler for Start, Remedial, and Continue buttons
        $(document)
            .off('click.assessmentConfirmation', '.btn-start-test, .btn-remedial, .btn-continue-test, .btn-assessment-start, .btn-assessment-remedial, .btn-assessment-continue, [data-assessment-trigger="true"]')
            .on('click.assessmentConfirmation', '.btn-start-test, .btn-remedial, .btn-continue-test, .btn-assessment-start, .btn-assessment-remedial, .btn-assessment-continue, [data-assessment-trigger="true"]', function (e) {
                e.preventDefault();
                var $btn = $(this);

                var assessmentId = $btn.data('assessment-id') || $btn.attr('data-assessment-id');
                if (!assessmentId) return;

                var targetUrl = $btn.attr('href') || $btn.data('action-url') || $btn.data('target-url');
                if (!targetUrl || targetUrl === '#' || targetUrl === 'javascript:void(0);') return;

                var $row = $('tr[data-assessment-id="' + assessmentId + '"], .assessment-row[data-assessment-id="' + assessmentId + '"]');
                if (!$row.length) {
                    $row = $btn.closest('tr, .assessment-row');
                }

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

                var rawType = $btn.data('assessment-type') || $row.attr('data-assessment-type') || 'content';
                var rawTypeLabel = $btn.data('assessment-type-label') || $row.attr('data-assessment-type-label');
                var isGlobal = String(rawType).toLowerCase() === 'global';

                var fromDate = $btn.data('assessment-from-date') || $row.attr('data-assessment-from-date') || '-';
                var toDate = $btn.data('assessment-to-date') || $row.attr('data-assessment-to-date') || '-';
                var dateTimeDisplay = '-';
                if (fromDate !== '-' && toDate !== '-') {
                    dateTimeDisplay = '<div>' + formatAssessmentDate(fromDate) + '</div><div>' + formatAssessmentDate(toDate) + '</div>';
                } else if (fromDate !== '-') {
                    dateTimeDisplay = '<div>' + formatAssessmentDate(fromDate) + '</div>';
                } else if (toDate !== '-') {
                    dateTimeDisplay = '<div>' + formatAssessmentDate(toDate) + '</div>';
                }

                var passingGrade = $btn.data('assessment-passing-grade') || $row.attr('data-assessment-passing-grade') || $row.attr('data-assessment-grade') || '-';
                var duration = $btn.data('assessment-duration') || $row.attr('data-assessment-duration') || '60';

                var userAttempts = $btn.data('assessment-user-attempts');
                if (userAttempts === undefined || userAttempts === null || userAttempts === '') {
                    userAttempts = $row.attr('data-assessment-user-attempts') || 0;
                }
                var maxAttempts = $btn.data('assessment-max-attempts');
                if (maxAttempts === undefined || maxAttempts === null || maxAttempts === '') {
                    maxAttempts = $row.attr('data-assessment-max-attempts') || 1;
                }
                var attemptDisplay = (userAttempts !== undefined && userAttempts !== null && userAttempts !== '')
                    ? (userAttempts + ' / ' + maxAttempts)
                    : '-';

                var answeredCount = $btn.data('assessment-answered-count') || $row.attr('data-assessment-answered-count') || 0;
                var totalCount = $btn.data('assessment-total-count') || $row.attr('data-assessment-total-count') || 0;

                var actionType = $btn.data('assessment-action-type');
                if (!actionType) {
                    if ($btn.hasClass('btn-continue-test') || $btn.hasClass('btn-assessment-continue')) {
                        actionType = 'continue';
                    } else if ($btn.hasClass('btn-remedial') || $btn.hasClass('btn-assessment-remedial')) {
                        actionType = 'remedial';
                    } else {
                        actionType = 'start';
                    }
                }

                // 1. CONTINUE MODAL
                if (actionType === 'continue') {
                    var $continueModal = $('#continueAssessmentModal');
                    if ($continueModal.length) {
                        $continueModal.find('#continueModalTypeHeader').text(isGlobal ? 'ASSESSMENT GLOBAL' : 'ASSESSMENT CONTENT');
                        $continueModal.find('#continueModalName').text(title);
                        $continueModal.find('#continueModalDateTime').html(dateTimeDisplay);
                        $continueModal.find('#continueModalPassingGrade').text(passingGrade);
                        $continueModal.find('#continueModalDuration').text(duration + ' Minutes');
                        
                        if (isGlobal) {
                            $continueModal.find('#continueModalBatchContainer').show();
                            $continueModal.find('#continueModalBatch').text(batchDisplay !== '-' ? batchDisplay : 'Batch 1');
                            $continueModal.find('#continueModalAttemptContainer').hide();
                            $continueModal.find('#continueModalProgress').text(answeredCount + ' / ' + totalCount + ' answered');
                            $continueModal.find('#continueModalNoticeTitle').text('NOTICE');
                            $continueModal.find('#continueNoticeGlobal').show();
                            $continueModal.find('#continueNoticeContent').hide();
                        } else {
                            $continueModal.find('#continueModalBatchContainer').hide();
                            $continueModal.find('#continueModalAttemptContainer').show();
                            $continueModal.find('#continueModalAttempt').text(attemptDisplay);
                            $continueModal.find('#continueModalProgress').text(answeredCount + ' / ' + totalCount + ' answered');
                            $continueModal.find('#continueModalNoticeTitle').text('REQUIREMENTS');
                            $continueModal.find('#continueNoticeGlobal').hide();
                            $continueModal.find('#continueNoticeContent').show();
                        }

                        $continueModal.find('#btnConfirmContinueAssessment').attr('href', targetUrl);
                        $continueModal.data('target-url', targetUrl);

                        openModalDirect('continueAssessmentModal');
                        return;
                    }
                }

                // 2. START / REMEDIAL MODAL
                var $confirmModal = $('#assessmentConfirmationModal');
                if ($confirmModal.length) {
                    $confirmModal.find('#assessmentModalTypeHeader').text(isGlobal ? 'ASSESSMENT GLOBAL' : 'ASSESSMENT CONTENT');
                    $confirmModal.find('#assessmentModalName').text(title);
                    $confirmModal.find('#assessmentModalDateTime').html(dateTimeDisplay);
                    $confirmModal.find('#assessmentModalPassingGrade').text(passingGrade);
                    $confirmModal.find('#assessmentModalDuration').text(duration + ' Minutes');

                    if (isGlobal) {
                        $confirmModal.find('#assessmentModalBatchContainer').show();
                        $confirmModal.find('#assessmentModalBatch').text(batchDisplay !== '-' ? batchDisplay : 'Batch 1');
                        $confirmModal.find('#assessmentModalAttemptContainer').hide();
                    } else {
                        $confirmModal.find('#assessmentModalBatchContainer').hide();
                        $confirmModal.find('#assessmentModalAttemptContainer').show();
                        $confirmModal.find('#assessmentModalAttempt').text(attemptDisplay);
                    }

                    if (actionType === 'remedial') {
                        $confirmModal.find('#assessmentModalHeaderIconBox')
                            .attr('style', 'width: 40px; height: 40px; background-color: rgba(245, 158, 11, 0.12); color: #f59e0b;');
                        $confirmModal.find('#assessmentModalHeaderIconInner').attr('class', 'ti ti-refresh fs-2');
                        $confirmModal.find('#assessmentConfirmationModalTitle').text('REMEDIAL').attr('style', 'color: #f59e0b !important;');
                        $confirmModal.find('#assessmentModalSubtitle').text('Confirmation');
                        $confirmModal.find('#assessmentModalNoticeHeader').text('REQUIREMENTS');
                        $confirmModal.find('#assessmentNoticeStart').hide();
                        $confirmModal.find('#assessmentNoticeGlobalStart').hide();
                        $confirmModal.find('#assessmentNoticeRemedial').show();

                        $confirmModal.find('#btnConfirmAssessmentAction')
                            .attr('style', 'background-color: #f59e0b !important; border-color: #f59e0b !important; color: #ffffff !important;')
                            .html('<i class="ti ti-refresh fs-6"></i> <span>Start Remedial</span>');
                    } else {
                        // Start Modal
                        $confirmModal.find('#assessmentModalHeaderIconBox')
                            .attr('style', 'width: 40px; height: 40px; background-color: rgba(40, 190, 88, 0.12); color: #28be58;');
                        $confirmModal.find('#assessmentModalHeaderIconInner').attr('class', 'ti ti-player-play fs-2');
                        $confirmModal.find('#assessmentConfirmationModalTitle').text('Start Assessment').attr('style', 'color: #28be58 !important;');
                        $confirmModal.find('#assessmentModalSubtitle').text('Confirmation');

                        if (isGlobal) {
                            $confirmModal.find('#assessmentModalNoticeHeader').text('NOTICE');
                            $confirmModal.find('#assessmentNoticeGlobalStart').show();
                            $confirmModal.find('#assessmentNoticeStart').hide();
                            $confirmModal.find('#assessmentNoticeRemedial').hide();
                        } else {
                            $confirmModal.find('#assessmentModalNoticeHeader').text('REQUIREMENTS');
                            $confirmModal.find('#assessmentNoticeStart').show();
                            $confirmModal.find('#assessmentNoticeGlobalStart').hide();
                            $confirmModal.find('#assessmentNoticeRemedial').hide();
                        }

                        $confirmModal.find('#btnConfirmAssessmentAction')
                            .attr('style', 'background-color: #28be58 !important; border-color: #28be58 !important; color: #ffffff !important;')
                            .html('<i class="ti ti-player-play fs-6"></i> <span>Start Test</span>');
                    }

                    $confirmModal.data('target-url', targetUrl);
                    $confirmModal.data('assessment-id', assessmentId);

                    openModalDirect('assessmentConfirmationModal');
                }
            });

        $(document)
            .off('click.assessmentConfirmSubmit', '#btnConfirmAssessmentAction')
            .on('click.assessmentConfirmSubmit', '#btnConfirmAssessmentAction', function (e) {
                e.preventDefault();
                var $modal = $('#assessmentConfirmationModal');
                var targetUrl = $modal.data('target-url');
                if (targetUrl && targetUrl !== '#' && targetUrl !== 'javascript:void(0);') {
                    window.location.href = targetUrl;
                }
            });

        $(document)
            .off('click.continueConfirmSubmit', '#btnConfirmContinueAssessment')
            .on('click.continueConfirmSubmit', '#btnConfirmContinueAssessment', function (e) {
                var $modal = $('#continueAssessmentModal');
                var targetUrl = $modal.data('target-url');
                if (targetUrl && targetUrl !== '#' && targetUrl !== 'javascript:void(0);') {
                    $(this).attr('href', targetUrl);
                }
            });

        // Hover Popover
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
