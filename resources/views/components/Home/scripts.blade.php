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

            var $modal = $('#assessmentConfirmationModal');
            if (!$modal.length) {
                var fallbackRoute = response.assessment_url || "{{ route('assesment.user.view', ':slug') }}".replace(':slug', response.assessment_slug);
                window.location.replace(fallbackRoute);
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

            // Complete Reset Modal State for Start Test mode
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

            // Format Attempt display
            var userAttempts = response.assessment_user_attempts !== undefined ? response.assessment_user_attempts : 0;
            var maxAttempts = response.assessment_max_attempts !== undefined ? response.assessment_max_attempts : 1;
            var attemptDisplay = userAttempts + ' / ' + maxAttempts + ' Attempts';

            // Populate Modal Data
            $modal.find('#assessmentModalTypeHeader').text(headerLabel);
            $modal.find('#assessmentModalName').text(response.assessment_title || '-');
            $modal.find('#assessmentModalBatch').text(response.assessment_batch && response.assessment_batch !== '-' ? (String(response.assessment_batch).toLowerCase().indexOf('batch') !== -1 ? response.assessment_batch : 'Batch ' + response.assessment_batch) : '-');
            var fromDateVal = (typeof formatAssessmentDate === 'function') ? formatAssessmentDate(response.assessment_from_date) : (response.assessment_from_date || '-');
            var toDateVal = (typeof formatAssessmentDate === 'function') ? formatAssessmentDate(response.assessment_to_date) : (response.assessment_to_date || '-');
            $modal.find('#assessmentModalFromDate').text(fromDateVal);
            $modal.find('#assessmentModalToDate').text(toDateVal);
            $modal.find('#assessmentModalPassingGrade').text(response.assessment_passing_grade !== undefined && response.assessment_passing_grade !== '' ? response.assessment_passing_grade : '-');
            $modal.find('#assessmentModalAttempt').text(attemptDisplay);

            var targetUrl = response.assessment_url || "{{ route('assesment.user.view', ':slug') }}".replace(':slug', response.assessment_slug);
            $modal.data('assessment-id', response.assessment_id);
            $modal.data('target-url', targetUrl);
            $modal.data('action-type', 'start');

            // Open Shared Assessment Confirmation Modal
            setTimeout(function () {
                if (typeof $.fn.modal === 'function') {
                    $modal.modal('show');
                } else if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    var bsModal = bootstrap.Modal.getOrCreateInstance($modal[0]);
                    bsModal.show();
                } else {
                    $modal.addClass('show').css('display', 'block');
                }
            }, 150);
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
</script>
