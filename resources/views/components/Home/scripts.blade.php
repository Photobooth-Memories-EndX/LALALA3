@include('components.shared.hero-upload-script')

<script>
/* =====================================================
   LATEST GALLERY — Auto-slide Carousel
===================================================== */

document.addEventListener("DOMContentLoaded", function () {
    var galleryCarouselEl = document.getElementById('homeLatestGalleryCarousel');
    if (!galleryCarouselEl || !window.bootstrap || !window.bootstrap.Carousel) return;

    new bootstrap.Carousel(galleryCarouselEl, {
        interval: 3500,
        pause: 'hover',
        wrap: true,
        ride: 'carousel',
    });
});

/* =====================================================
   LATEST NEWS — Auto-slide Carousel
===================================================== */

document.addEventListener("DOMContentLoaded", function () {
    var newsCarouselEl = document.getElementById('homeLatestNewsCarousel');
    if (!newsCarouselEl || !window.bootstrap || !window.bootstrap.Carousel) return;

    new bootstrap.Carousel(newsCarouselEl, {
        interval: 4500,
        pause: 'hover',
        wrap: true,
        ride: 'carousel',
    });
});

/* =====================================================
   SLIDE CAROUSEL — Top Video + Training Modules tabs
   Responsive sliding window (4 desktop / 2 tablet / 1 mobile)
===================================================== */

document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll('.home-modern-slide-carousel').forEach(function (carousel) {
        var track = carousel.querySelector('.home-modern-slide-track');
        var prevBtn = carousel.querySelector('.home-modern-slide-nav-prev');
        var nextBtn = carousel.querySelector('.home-modern-slide-nav-next');
        if (!track || !prevBtn || !nextBtn) return;

        var items = track.children.length;
        var index = 0;
        var autoplayMs = parseInt(carousel.dataset.autoplay, 10) || 4000;
        var timer = null;

        function getVisibleCount() {
            var w = window.innerWidth;
            if (w <= 767.98) return 1;
            if (w <= 991.98) return 2;
            return 4;
        }

        function getMaxIndex(visible) {
            return Math.max(0, items - visible);
        }

        function getStepWidth() {
            var firstItem = track.children[0];
            return firstItem ? firstItem.getBoundingClientRect().width : 0;
        }

        function update() {
            var visible = getVisibleCount();
            var maxIndex = getMaxIndex(visible);
            var stepWidth = getStepWidth();

            if (index > maxIndex) index = maxIndex;

            track.style.transform = 'translate3d(-' + (index * stepWidth) + 'px, 0, 0)';

            var hideNav = items <= visible;
            prevBtn.classList.toggle('d-none', hideNav);
            nextBtn.classList.toggle('d-none', hideNav);
        }

        function step(dir) {
            var visible = getVisibleCount();
            var maxIndex = getMaxIndex(visible);
            if (maxIndex <= 0) return;
            index = (index + dir + maxIndex + 1) % (maxIndex + 1);
            update();
        }

        function startAutoplay() {
            stopAutoplay();
            timer = setInterval(function () { step(1); }, autoplayMs);
        }

        function stopAutoplay() {
            if (timer) clearInterval(timer);
            timer = null;
        }

        prevBtn.addEventListener('click', function () { step(-1); startAutoplay(); });
        nextBtn.addEventListener('click', function () { step(1); startAutoplay(); });
        carousel.addEventListener('mouseenter', stopAutoplay);
        carousel.addEventListener('mouseleave', startAutoplay);
        window.addEventListener('resize', update);

        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (tab) {
            tab.addEventListener('shown.bs.tab', update);
        });

        update();
        startAutoplay();
    });
});

/* =====================================================
   ASSESSMENT ACCESS MODAL
===================================================== */

$('#btn-assessment-access').on('click', function () {
    $('#unique-code').val('');
    $('#invalid-text-unique-code')
        .html('')
        .removeClass('d-block')
        .addClass('d-none');

    $('#modal-assessment-access').modal('show');
});

function showAssessmentStatusModal(status, response) {
    var $modal = $('#assessmentAccessModal');
    if (!$modal.length) return false;

    var typeToMap = response.assessment_type_label || response.assessment_type || 'content';
    var headerLabel = 'ASSESSMENT CONTENT';
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

    $modal.find('#assessmentAccessModalTypeBadge').text(headerLabel);
    $modal.find('#assessmentAccessModalName').text(response.assessment_title || '-');

    // Hide active details and notice
    $modal.find('#assessmentAccessModalDetailsGrid').hide();
    $modal.find('#assessmentAccessModalNoticeSection').hide();

    // Show status box & status footer
    $modal.find('#assessmentAccessModalStatusBox').show();
    $modal.find('#assessmentAccessModalActiveFooter').attr('style', 'display: none !important;');
    $modal.find('#assessmentAccessModalStatusFooter').show().attr('style', '');

    if (status === 'inactive') {
        $modal.find('#assessmentAccessModalTitle').text('Assessment Unavailable').attr('style', 'color: #dc3545 !important;');
        $modal.find('#assessmentAccessModalSubtitle').text('Inactive');
        $modal.find('#assessmentAccessModalIconBox').attr('style', 'width: 40px; height: 40px; background-color: rgba(220, 53, 69, 0.12); color: #dc3545;');
        $modal.find('#assessmentAccessModalIconInner').attr('class', 'ti ti-circle-x fs-2');

        $modal.find('#assessmentAccessModalStatusAlert').attr('class', 'alert alert-danger border-0 rounded-3 p-3 mb-0 d-flex align-items-start gap-2');
        $modal.find('#assessmentAccessModalStatusAlertIcon').attr('class', 'ti ti-alert-triangle fs-2 flex-shrink-0 mt-1');
        $modal.find('#assessmentAccessModalStatusAlertTitle').text('Assessment Inactive');
        $modal.find('#assessmentAccessModalStatusText').text(response.status_message || 'This assessment is currently inactive and cannot be started.');
    } else if (status === 'expired') {
        $modal.find('#assessmentAccessModalTitle').text('Assessment Expired').attr('style', 'color: #fd7e14 !important;');
        $modal.find('#assessmentAccessModalSubtitle').text('Expired');
        $modal.find('#assessmentAccessModalIconBox').attr('style', 'width: 40px; height: 40px; background-color: rgba(253, 126, 20, 0.12); color: #fd7e14;');
        $modal.find('#assessmentAccessModalIconInner').attr('class', 'ti ti-calendar-x fs-2');

        $modal.find('#assessmentAccessModalStatusAlert').attr('class', 'alert alert-warning border-0 rounded-3 p-3 mb-0 d-flex align-items-start gap-2');
        $modal.find('#assessmentAccessModalStatusAlertIcon').attr('class', 'ti ti-clock-off fs-2 flex-shrink-0 mt-1');
        $modal.find('#assessmentAccessModalStatusAlertTitle').text('Assessment Expired');
        $modal.find('#assessmentAccessModalStatusText').text(response.status_message || 'This assessment has expired and can no longer be started.');
    }

    if (typeof $.fn.modal === 'function') {
        $modal.modal('show');
    } else if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        var bsModal = bootstrap.Modal.getOrCreateInstance($modal[0]);
        bsModal.show();
    } else {
        $modal.addClass('show').css('display', 'block');
    }

    return true;
}

function populateAndShowActiveAccessModal(response) {
    var $modal = $('#assessmentAccessModal');
    if (!$modal.length) {
        var fallbackRoute = response.assessment_url || "{{ route('assesment.user.view', ':slug') }}".replace(':slug', response.assessment_slug);
        window.location.href = fallbackRoute;
        return;
    }

    // Determine Header Label (trainingplan -> ASSESSMENT PLAYLIST)
    var typeToMap = response.assessment_type_label || response.assessment_type || 'content';
    var headerLabel = 'ASSESSMENT CONTENT';
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

    // Format Attempt display
    var userAttempts = response.assessment_user_attempts !== undefined ? response.assessment_user_attempts : 0;
    var maxAttempts = response.assessment_max_attempts !== undefined ? response.assessment_max_attempts : 1;
    var attemptDisplay = userAttempts + ' / ' + maxAttempts + ' Attempts';

    // Reset status box & status footer, show details grid & active footer
    $modal.find('#assessmentAccessModalStatusBox').hide();
    $modal.find('#assessmentAccessModalStatusFooter').attr('style', 'display: none !important;');
    $modal.find('#assessmentAccessModalActiveFooter').show().attr('style', '');
    $modal.find('#assessmentAccessModalDetailsGrid').show();
    $modal.find('#assessmentAccessModalNoticeSection').show();

    // Populate Modal Data
    $modal.find('#assessmentAccessModalTypeBadge').text(headerLabel);
    $modal.find('#assessmentAccessModalName').text(response.assessment_title || '-');
    $modal.find('#assessmentAccessModalSubtitle').text('Confirmation');

    var batchDisplay = response.assessment_batch && response.assessment_batch !== '-'
        ? (String(response.assessment_batch).toLowerCase().indexOf('batch') !== -1 ? response.assessment_batch : 'Batch ' + response.assessment_batch)
        : '-';
    $modal.find('#assessmentAccessModalSubText').text(batchDisplay);
    $modal.find('#assessmentAccessModalBatch').text(batchDisplay !== '-' ? batchDisplay : 'Batch 1');

    var fromDateVal = (typeof formatAssessmentDate === 'function') ? formatAssessmentDate(response.assessment_from_date) : (response.assessment_from_date || '-');
    var toDateVal = (typeof formatAssessmentDate === 'function') ? formatAssessmentDate(response.assessment_to_date) : (response.assessment_to_date || '-');
    $modal.find('#assessmentAccessModalFromDate').text(fromDateVal);
    $modal.find('#assessmentAccessModalToDate').text(toDateVal);

    var dateTimeDisplay = '-';
    if (fromDateVal !== '-' && toDateVal !== '-') {
        dateTimeDisplay = '<div>' + fromDateVal + '</div><div>' + toDateVal + '</div>';
    } else if (fromDateVal !== '-') {
        dateTimeDisplay = '<div>' + fromDateVal + '</div>';
    } else if (toDateVal !== '-') {
        dateTimeDisplay = '<div>' + toDateVal + '</div>';
    }
    $modal.find('#assessmentAccessModalDateTime').html(dateTimeDisplay);

    $modal.find('#assessmentAccessModalPassingGrade').text(response.assessment_passing_grade !== undefined && response.assessment_passing_grade !== '' ? response.assessment_passing_grade : '-');
    $modal.find('#assessmentAccessModalDuration').text((response.assessment_duration || '60') + ' Minutes');
    $modal.find('#assessmentAccessModalAttempt').text(attemptDisplay);

    // Toggle Batch vs Attempt Container
    var isGlobal = String(typeToMap).trim().toLowerCase().indexOf('global') !== -1 || String(response.assessment_type).trim().toLowerCase() === 'global';
    if (isGlobal) {
        $modal.find('#assessmentAccessModalBatchContainer').show();
        $modal.find('#assessmentAccessModalAttemptContainer').hide();
    } else {
        $modal.find('#assessmentAccessModalBatchContainer').hide();
        $modal.find('#assessmentAccessModalAttemptContainer').show();
    }

    var answeredCount = response.answered_count || 0;
    var totalCount = response.total_count || 0;
    $modal.find('#assessmentAccessModalProgress').text(answeredCount + ' / ' + totalCount + ' answered');

    var targetUrl = response.assessment_url;
    if (response.assessment_slug) {
        targetUrl = "{{ url('/assesment') }}/" + response.assessment_slug;
    } else if (!targetUrl) {
        targetUrl = "{{ route('assesment.user.view', ':slug') }}".replace(':slug', response.assessment_slug);
    }

    var actionType = response.action_type || 'start';
    var $startBtn = $('#btnStartAssessmentAccess');
    if (actionType === 'continue') {
        $modal.find('#assessmentAccessModalTitle').text('Continue Assessment').attr('style', 'color: #0c5b9e !important;');
        $modal.find('#assessmentAccessModalIconBox').attr('style', 'width: 40px; height: 40px; background-color: rgba(12, 91, 158, 0.12); color: #0c5b9e;');
        $modal.find('#assessmentAccessModalIconInner').attr('class', 'ti ti-player-play fs-2');
        $modal.find('#assessmentAccessModalProgressContainer').show();
        $modal.find('#assessmentAccessNoticeStart').hide();
        $modal.find('#assessmentAccessNoticeContinue').show();
        $modal.find('#assessmentAccessNoticeRemedial').hide();
        $startBtn.attr('style', 'background-color: #0c5b9e !important; border-color: #0c5b9e !important; color: #ffffff !important;')
                 .html('<i class="ti ti-player-play"></i> <span>Continue Assessment</span>');
    } else if (actionType === 'remedial') {
        $modal.find('#assessmentAccessModalTitle').text('REMEDIAL').attr('style', 'color: #f59e0b !important;');
        $modal.find('#assessmentAccessModalIconBox').attr('style', 'width: 40px; height: 40px; background-color: rgba(245, 158, 11, 0.12); color: #f59e0b;');
        $modal.find('#assessmentAccessModalIconInner').attr('class', 'ti ti-refresh fs-2');
        $modal.find('#assessmentAccessModalProgressContainer').hide();
        $modal.find('#assessmentAccessNoticeStart').hide();
        $modal.find('#assessmentAccessNoticeContinue').hide();
        $modal.find('#assessmentAccessNoticeRemedial').show();
        $startBtn.attr('style', 'background-color: #f59e0b !important; border-color: #f59e0b !important; color: #ffffff !important;')
                 .html('<i class="ti ti-refresh"></i> <span>Start Remedial</span>');
    } else {
        $modal.find('#assessmentAccessModalTitle').text('Start Assessment').attr('style', 'color: #28be58 !important;');
        $modal.find('#assessmentAccessModalIconBox').attr('style', 'width: 40px; height: 40px; background-color: rgba(40, 190, 88, 0.12); color: #28be58;');
        $modal.find('#assessmentAccessModalIconInner').attr('class', 'ti ti-player-play fs-2');
        $modal.find('#assessmentAccessModalProgressContainer').hide();
        $modal.find('#assessmentAccessNoticeStart').show();
        $modal.find('#assessmentAccessNoticeContinue').hide();
        $modal.find('#assessmentAccessNoticeRemedial').hide();
        $startBtn.attr('style', 'background-color: #28be58 !important; border-color: #28be58 !important; color: #ffffff !important;')
                 .html('<i class="ti ti-player-play"></i> <span>Start Test</span>');
    }

    $('#btnStartAssessmentAccess')
        .data('target-url', targetUrl)
        .attr('data-target-url', targetUrl);

    $modal
        .data('target-url', targetUrl)
        .attr('data-target-url', targetUrl);

    if (typeof $.fn.modal === 'function') {
        $modal.modal('show');
    } else if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        var bsModal = bootstrap.Modal.getOrCreateInstance($modal[0]);
        bsModal.show();
    } else {
        $modal.addClass('show').css('display', 'block');
    }
}

function submitAssessmentAccess() {
    var unique_code = $('#unique-code').val();

    $('#invalid-text-unique-code')
        .html('')
        .removeClass('d-block')
        .addClass('d-none');

    $.ajax({
        url: "{{ route('home.user.access.assessment.by.unique.code') }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            unique_code: unique_code,
        },
        success: function (response) {
            var $inputModal = $('#modal-assessment-access');

            function openTargetModal() {
                // PRIORITY 1: INACTIVE MODAL
                if (response.assessment_status === 'inactive' || response.is_inactive || response.denial_reason === 'inactive') {
                    showAssessmentStatusModal('inactive', response);
                    return;
                }

                // PRIORITY 2: EXPIRED MODAL
                if (response.assessment_status === 'expired' || response.is_expired || response.denial_reason === 'expired') {
                    showAssessmentStatusModal('expired', response);
                    return;
                }

                // PRIORITY 3: RESULT MODAL
                if (response.show_result) {
                    if (populateAndShowResultModal(response)) {
                        return;
                    }
                }

                // PRIORITY 4: START / CONTINUE / REMEDIAL MODAL
                if (response.can_start) {
                    populateAndShowActiveAccessModal(response);
                    return;
                }

                // PRIORITY 5: OTHER DENIAL REASON / ERROR
                var msg = response.status_message || 'You cannot access this assessment.';
                if (response.denial_reason === 'assigned') {
                    msg = 'You are not assigned to this assessment.';
                } else if (response.denial_reason === 'before_start') {
                    msg = 'This assessment is not yet available.';
                } else if (response.denial_reason === 'progress') {
                    msg = 'Complete the related content before starting this assessment.';
                }
                $('#invalid-text-unique-code')
                    .html(msg)
                    .removeClass('d-none')
                    .addClass('d-block');
            }

            if ($inputModal.length && ($inputModal.hasClass('show') || $inputModal.is(':visible'))) {
                var opened = false;
                var safeOpen = function () {
                    if (!opened) {
                        opened = true;
                        $inputModal.off('hidden.bs.modal', safeOpen);
                        openTargetModal();
                    }
                };
                $inputModal.one('hidden.bs.modal', safeOpen);
                $inputModal.modal('hide');
                setTimeout(safeOpen, 350);
            } else {
                openTargetModal();
            }
        },
        error: function (error) {
            var msg = 'An error occurred. Please try again.';
            if (error.responseJSON) {
                if (error.responseJSON.unique_code) {
                    msg = error.responseJSON.unique_code[0];
                } else if (error.responseJSON.error_message) {
                    msg = error.responseJSON.error_message;
                }
            }
            $('#invalid-text-unique-code')
                .html(msg)
                .removeClass('d-none')
                .addClass('d-block');
        }
    });
}

$(document).on('click', '#btnStartAssessmentAccess', function (e) {
    e.preventDefault();

    console.log('[ASSESSMENT DEBUG] Start Test clicked');

    var $btn = $(this);
    var $modal = $('#assessmentAccessModal');

    var targetUrl = $btn.data('target-url') ||
                    $btn.attr('data-target-url') ||
                    $modal.data('target-url') ||
                    $modal.attr('data-target-url');

    console.log('[ASSESSMENT DEBUG] targetUrl:', targetUrl);

    if (!targetUrl) {
        console.error('[ASSESSMENT DEBUG] Assessment target URL is missing.');
        return;
    }

    console.log('[ASSESSMENT DEBUG] Before navigation:', window.location.href);
    console.log('[ASSESSMENT DEBUG] Target:', targetUrl);

    window.location.href = targetUrl;
});

function populateAndShowResultModal(response) {
    var $resModal = $('#assessmentResultModal');
    if (!$resModal.length) return false;

    var isPassed = !!response.assessment_is_passed;
    var scoreVal = response.assessment_score !== undefined ? response.assessment_score : 0;
    var passingGradeVal = response.assessment_passing_grade !== undefined ? response.assessment_passing_grade : '-';
    var typeStr = response.assessment_type_label || response.assessment_type || 'content';
    var isGlobal = String(typeStr).trim().toLowerCase().indexOf('global') !== -1 || String(response.assessment_type).trim().toLowerCase() === 'global';

    $resModal.find('#assessmentResultModalName').text(response.assessment_title || '-');
    $resModal.find('#assessmentResultModalScore').text(scoreVal);
    $resModal.find('#assessmentResultModalScore').attr('class', isPassed ? 'text-success display-4 fw-extrabold mb-1' : 'text-danger display-4 fw-extrabold mb-1');
    $resModal.find('#assessmentResultModalPassingGrade').text(passingGradeVal);

    // Single Status Badge!
    var badgeHtml = isPassed 
        ? '<span class="badge bg-success-subtle text-success px-3 py-2 fs-6 rounded-pill fw-bold"><i class="ti ti-check me-1"></i>Passed</span>'
        : '<span class="badge bg-danger-subtle text-danger px-3 py-2 fs-6 rounded-pill fw-bold"><i class="ti ti-x me-1"></i>Failed</span>';
    $resModal.find('#assessmentResultModalBadgeContainer').html(badgeHtml);

    // Header Icon Box & Icon
    $resModal.find('#assessmentResultModalIconBox').attr('class', isPassed ? 'p-2 rounded-3 bg-success-subtle text-success d-flex align-items-center justify-content-center' : 'p-2 rounded-3 bg-danger-subtle text-danger d-flex align-items-center justify-content-center');
    $resModal.find('#assessmentResultModalIcon').attr('class', isPassed ? 'ti ti-trophy fs-2' : 'ti ti-alert-circle fs-2');

    // Type Display
    var headerLabel = 'Content Assessment';
    var lowerType = String(typeStr).trim().toLowerCase();
    if (lowerType === 'trainingplan' || lowerType === 'playlist') {
        headerLabel = 'Playlist Assessment';
    } else if (lowerType.indexOf('global') !== -1) {
        headerLabel = 'Global Assessment';
    } else if (lowerType === 'youtube') {
        headerLabel = 'MOOC Assessment';
    } else if (typeStr && typeStr !== '-') {
        headerLabel = typeStr.indexOf('Assessment') !== -1 ? typeStr : typeStr + ' Assessment';
    }
    $resModal.find('#assessmentResultModalType').text(headerLabel);

    // Batch vs Attempt Visibility
    if (isGlobal) {
        $resModal.find('#assessmentResultModalBatchRow').removeClass('d-none');
        $resModal.find('#assessmentResultModalAttemptRow').addClass('d-none');
        var batchDisplay = response.assessment_batch && response.assessment_batch !== '-' 
            ? (String(response.assessment_batch).toLowerCase().indexOf('batch') !== -1 ? response.assessment_batch : 'Batch ' + response.assessment_batch) 
            : '-';
        $resModal.find('#assessmentResultModalBatch').text(batchDisplay);
    } else {
        $resModal.find('#assessmentResultModalBatchRow').addClass('d-none');
        $resModal.find('#assessmentResultModalAttemptRow').removeClass('d-none');
        var currAtt = response.assessment_current_attempt || response.assessment_user_attempts || 1;
        var maxAtt = response.assessment_max_attempts || 1;
        $resModal.find('#assessmentResultModalAttempt').text(currAtt + ' / ' + maxAtt);
    }

    // Assessment Period
    var periodVal = response.assessment_period || '-';
    if (periodVal === '-' && (response.assessment_from_date || response.assessment_to_date)) {
        var f = (response.assessment_from_date && response.assessment_from_date !== '-') ? response.assessment_from_date : '';
        var t = (response.assessment_to_date && response.assessment_to_date !== '-') ? response.assessment_to_date : '';
        periodVal = (f && t) ? f + ' - ' + t : (f ? 'From ' + f : 'Until ' + t);
    }
    $resModal.find('#assessmentResultModalPeriod').text(periodVal);

    // Status Message Row
    $resModal.find('#assessmentResultModalStatusRow').attr('class', isPassed ? 'p-3 rounded-3 bg-success-subtle text-success' : 'p-3 rounded-3 bg-danger-subtle text-danger');
    $resModal.find('#assessmentResultModalStatusMessage').text(isPassed ? 'Congratulations, you have passed this test.' : 'Sorry, you have not passed this test.');

    // Notice
    if (response.is_expired) {
        $resModal.find('#assessmentResultModalExpiredNotice').removeClass('d-none').addClass('d-flex');
    } else {
        $resModal.find('#assessmentResultModalExpiredNotice').addClass('d-none').removeClass('d-flex');
    }

    if (typeof $.fn.modal === 'function') {
        $resModal.modal('show');
    } else if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        var bsModal = bootstrap.Modal.getOrCreateInstance($resModal[0]);
        bsModal.show();
    }
    return true;
}

function submitAssessmentAccessWithCode(code) {
    if ($('#unique-code').length) {
        $('#unique-code').val(code);
        submitAssessmentAccess();
    } else {
        $.ajax({
            url: "{{ route('home.user.access.assessment.by.unique.code') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                unique_code: code,
            },
            success: function (response) {
                // PRIORITY 1: INACTIVE MODAL
                if (response.assessment_status === 'inactive' || response.is_inactive || response.denial_reason === 'inactive') {
                    showAssessmentStatusModal('inactive', response);
                    return;
                }

                // PRIORITY 2: EXPIRED MODAL
                if (response.assessment_status === 'expired' || response.is_expired || response.denial_reason === 'expired') {
                    showAssessmentStatusModal('expired', response);
                    return;
                }

                // PRIORITY 3: RESULT MODAL
                if (response.show_result) {
                    if (populateAndShowResultModal(response)) {
                        return;
                    }
                }

                // PRIORITY 4: START / CONTINUE / REMEDIAL MODAL
                if (response.can_start) {
                    populateAndShowActiveAccessModal(response);
                    return;
                }

                var targetUrl = response.assessment_url || ("{{ url('/assesment') }}/" + response.assessment_slug);
                window.location.href = targetUrl;
            }
        });
    }
}

@if(session()->has('open_assessment_code'))
document.addEventListener("DOMContentLoaded", function () {
    setTimeout(function () {
        submitAssessmentAccessWithCode("{{ session('open_assessment_code') }}");
    }, 250);
});
@endif
</script>
