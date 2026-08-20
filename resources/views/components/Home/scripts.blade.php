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
            // Hide input modal
            $('#modal-assessment-access').modal('hide');

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

            // Populate Modal Data
            $modal.find('#assessmentAccessModalTypeBadge').text(headerLabel);
            $modal.find('#assessmentAccessModalName').text(response.assessment_title || '-');

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
                dateTimeDisplay = fromDateVal + ' – ' + toDateVal;
            } else if (fromDateVal !== '-') {
                dateTimeDisplay = fromDateVal;
            } else if (toDateVal !== '-') {
                dateTimeDisplay = toDateVal;
            }
            $modal.find('#assessmentAccessModalDateTime').text(dateTimeDisplay);

            $modal.find('#assessmentAccessModalPassingGrade').text(response.assessment_passing_grade !== undefined && response.assessment_passing_grade !== '' ? response.assessment_passing_grade : '-');
            $modal.find('#assessmentAccessModalDuration').text((response.assessment_duration || '60') + ' Minutes');
            $modal.find('#assessmentAccessModalAttempt').text(attemptDisplay);

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
                $modal.find('#assessmentAccessModalProgressContainer').show();
                $modal.find('#assessmentAccessNoticeStart').hide();
                $modal.find('#assessmentAccessNoticeContinue').show();
                $modal.find('#assessmentAccessNoticeRemedial').hide();
                $startBtn.attr('style', 'background-color: #0c5b9e !important; border-color: #0c5b9e !important; color: #ffffff !important;')
                         .html('<i class="ti ti-player-play"></i> <span>Continue Assessment</span>');
            } else if (actionType === 'remedial') {
                $modal.find('#assessmentAccessModalTitle').text('REMEDIAL').attr('style', 'color: #f59e0b !important;');
                $modal.find('#assessmentAccessModalIconBox').attr('style', 'width: 40px; height: 40px; background-color: rgba(245, 158, 11, 0.12); color: #f59e0b;');
                $modal.find('#assessmentAccessModalProgressContainer').hide();
                $modal.find('#assessmentAccessNoticeStart').hide();
                $modal.find('#assessmentAccessNoticeContinue').hide();
                $modal.find('#assessmentAccessNoticeRemedial').show();
                $startBtn.attr('style', 'background-color: #f59e0b !important; border-color: #f59e0b !important; color: #ffffff !important;')
                         .html('<i class="ti ti-refresh"></i> <span>Start Remedial</span>');
            } else {
                $modal.find('#assessmentAccessModalTitle').text('Start Assessment').attr('style', 'color: #28be58 !important;');
                $modal.find('#assessmentAccessModalIconBox').attr('style', 'width: 40px; height: 40px; background-color: rgba(40, 190, 88, 0.12); color: #28be58;');
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

            // Open #assessmentAccessModal safely using event-driven transition
            function showAccessModal() {
                if (typeof $.fn.modal === 'function') {
                    $modal.modal('show');
                } else if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    var bsModal = bootstrap.Modal.getOrCreateInstance($modal[0]);
                    bsModal.show();
                } else {
                    $modal.addClass('show').css('display', 'block');
                }
            }

            var $inputModal = $('#modal-assessment-access');
            if ($inputModal.length && ($inputModal.hasClass('show') || $inputModal.is(':visible'))) {
                var opened = false;
                var safeOpen = function () {
                    if (!opened) {
                        opened = true;
                        $inputModal.off('hidden.bs.modal', safeOpen);
                        showAccessModal();
                    }
                };
                $inputModal.one('hidden.bs.modal', safeOpen);
                $inputModal.modal('hide');
                setTimeout(safeOpen, 350);
            } else {
                showAccessModal();
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
