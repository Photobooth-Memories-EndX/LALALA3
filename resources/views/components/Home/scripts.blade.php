@section('script')

<script>

/* =====================================================
   HERO DONUT — Bootstrap tooltips on hover
===================================================== */

document.addEventListener("DOMContentLoaded", function () {

    if (!window.bootstrap || !window.bootstrap.Tooltip) return;

    document.querySelectorAll('.home-modern-donut-hit[data-bs-toggle="tooltip"]')
        .forEach(function (el) { new bootstrap.Tooltip(el); });

});


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

        function update() {
            var visible = getVisibleCount();
            var maxIndex = getMaxIndex(visible);

            if (index > maxIndex) index = maxIndex;

            track.style.transform = 'translateX(-' + (index * (100 / visible)) + '%)';

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

    $.ajax({
        url: "{{ route('home.user.access.assessment.by.unique.code') }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            unique_code: unique_code,
        },
        success: function (response) {
            var route = "{{ route('assesment.user.view', ':slug') }}"
                .replace(':slug', response.assessment_slug);
            window.location.replace(route);
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

@endsection
