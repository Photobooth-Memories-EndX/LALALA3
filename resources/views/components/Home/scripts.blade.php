@section('script')

<script>

/* =====================================================
   HERO BACKGROUND — Local preview and AJAX upload
===================================================== */

$(function () {
    var $hero = $('.compact-home-hero');
    var $form = $('#homeHeroCoverForm');
    var $input = $('#homeHeroCoverInput');
    var $saveButton = $('#homeHeroCoverSave');
    var allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    var allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    var maxSize = 5 * 1024 * 1024;
    var targetSize = 100 * 1024;
    var maxWidth = 1920;
    var qualities = [0.8, 0.75, 0.70, 0.65, 0.60];
    var previewUrl = null;
    var compressedBackground = null;
    var compressionSequence = 0;
    var saveButtonDefaultHtml = $saveButton.html();

    if (!$hero.length || !$form.length) return;

    function setHeroBackground(url) {
        $hero.css('--hero-bg-image', 'url("' + url + '")');
    }

    function clearPreviewUrl() {
        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            previewUrl = null;
        }
    }

    function resetHeroToSavedBackground() {
        setHeroBackground($hero.attr('data-background-url'));
    }

    function supportsWebP() {
        var canvas = document.createElement('canvas');

        return canvas.toDataURL('image/webp').indexOf('data:image/webp') === 0;
    }

    function loadImage(file) {
        return new Promise(function (resolve, reject) {
            var image = new Image();
            var sourceUrl = URL.createObjectURL(file);

            image.onload = function () {
                URL.revokeObjectURL(sourceUrl);
                resolve(image);
            };
            image.onerror = function () {
                URL.revokeObjectURL(sourceUrl);
                reject(new Error('The selected image could not be read.'));
            };
            image.src = sourceUrl;
        });
    }

    function canvasToBlob(canvas, mimeType, quality) {
        return new Promise(function (resolve, reject) {
            canvas.toBlob(function (blob) {
                if (!blob) {
                    reject(new Error('The browser could not compress this image.'));
                    return;
                }

                resolve(blob);
            }, mimeType, quality);
        });
    }

    async function compressImage(file) {
        var image = await loadImage(file);
        var scale = image.naturalWidth > maxWidth
            ? maxWidth / image.naturalWidth
            : 1;
        var width = Math.max(1, Math.round(image.naturalWidth * scale));
        var height = Math.max(1, Math.round(image.naturalHeight * scale));
        var canvas = document.createElement('canvas');
        var context = canvas.getContext('2d');

        if (!context) {
            throw new Error('Canvas compression is not supported by this browser.');
        }

        canvas.width = width;
        canvas.height = height;
        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, width, height);
        context.drawImage(image, 0, 0, width, height);

        var mimeType = supportsWebP() ? 'image/webp' : 'image/jpeg';
        var blob = null;

        for (var index = 0; index < qualities.length; index++) {
            blob = await canvasToBlob(canvas, mimeType, qualities[index]);

            if (blob.size <= targetSize) break;
        }

        return {
            blob: blob,
            fileName: 'hero-background.' + (mimeType === 'image/webp' ? 'webp' : 'jpg')
        };
    }

    $input.on('change', async function () {
        var input = this;
        var file = this.files && this.files[0];
        var extension = file && file.name.indexOf('.') !== -1
            ? file.name.split('.').pop().toLowerCase()
            : '';
        var selectionId = ++compressionSequence;

        if (!file) return;

        clearPreviewUrl();
        compressedBackground = null;
        resetHeroToSavedBackground();

        if (allowedTypes.indexOf(file.type) === -1
            && allowedExtensions.indexOf(extension) === -1) {
            window.alert('Use a JPG, JPEG, PNG, or WEBP image.');
            this.value = '';
            $saveButton.addClass('d-none');
            return;
        }

        if (file.size > maxSize) {
            window.alert('Maximum image size is 5 MB.');
            this.value = '';
            $saveButton.addClass('d-none');
            return;
        }

        $saveButton
            .removeClass('d-none')
            .prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span>Compressing...</span>');

        try {
            var compressed = await compressImage(file);

            if (selectionId !== compressionSequence) return;

            compressedBackground = compressed;
            previewUrl = URL.createObjectURL(compressed.blob);
            setHeroBackground(previewUrl);
            $saveButton
                .prop('disabled', false)
                .html(saveButtonDefaultHtml);
        } catch (error) {
            if (selectionId !== compressionSequence) return;

            input.value = '';
            resetHeroToSavedBackground();
            $saveButton
                .prop('disabled', false)
                .addClass('d-none')
                .html(saveButtonDefaultHtml);
            window.alert(error.message || 'Image compression failed.');
        }
    });

    $form.on('submit', function (event) {
        event.preventDefault();

        if (!compressedBackground) {
            window.alert('Choose a background image first.');
            return;
        }

        var formData = new FormData($form[0]);

        formData.set(
            'hero_background',
            compressedBackground.blob,
            compressedBackground.fileName
        );

        $saveButton
            .prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span>Saving...</span>');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': $form.find('input[name="_token"]').val()
            },
            success: function (response) {
                if (response.background_url) {
                    $hero.attr('data-background-url', response.background_url);
                    setHeroBackground(response.background_url);
                }

                window.alert(response.message || 'Background updated successfully.');
            },
            error: function (xhr) {
                var message = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'Upload failed. HTTP status: ' + xhr.status + '.';

                resetHeroToSavedBackground();
                window.alert(message);
            },
            complete: function () {
                clearPreviewUrl();
                compressedBackground = null;
                $input.val('');
                $saveButton
                    .prop('disabled', false)
                    .addClass('d-none')
                    .html(saveButtonDefaultHtml);
            }
        });
    });
});

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
