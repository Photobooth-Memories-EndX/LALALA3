<script>

/* =====================================================
   HERO BACKGROUND — crop, Canvas compression and AJAX upload
===================================================== */

$(function () {
    var $form = $('.js-hero-background-form').first();

    if (!$form.length) {
        console.warn('[Hero Crop] Upload form was not found.');
        return;
    }

    var $hero = $form.closest('[data-hero-background]');
    var $input = $form.find('.js-hero-background-input');
    var $trigger = $form.find('.js-hero-background-trigger');
    var $saveButton = $form.find('.js-hero-background-save');
    var $modal = $('#heroBackgroundCropModal');
    var $cropImage = $('#heroCropImage');
    var $zoom = $('#heroCropZoom');
    var $applyButton = $('#heroCropApply');
    var allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    var allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    var maxSize = 5 * 1024 * 1024;
    var targetSize = 150 * 1024;
    var maximumSize = 200 * 1024;
    var outputWidth = 1600;
    var outputHeight = 900;
    var compressionQualities = [0.85, 0.80, 0.75, 0.70, 0.65, 0.60, 0.55, 0.50, 0.45, 0.40, 0.35, 0.30, 0.25, 0.20, 0.15, 0.10];
    var previewUrl = null;
    var compressedBackground = null;
    var cropper = null;
    var activeFileReader = null;
    var cropWasApplied = false;
    var applyButtonDefaultHtml = $applyButton.html();

    if (!$hero.length || !$input.length || !$modal.length || !$cropImage.length) {
        console.error('[Hero Crop] Required frontend elements are missing.', {
            hero: $hero.length,
            input: $input.length,
            modal: $modal.length,
            cropImage: $cropImage.length
        });
        return;
    }

    console.debug('[Hero Crop] Frontend handler initialized.');

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

    function setSaving(isSaving) {
        $trigger.toggleClass('d-none', isSaving);
        $saveButton.toggleClass('d-none', !isSaving).prop('disabled', isSaving);
    }

    function modalAction(action) {
        var BootstrapModal = window.bootstrap && window.bootstrap.Modal;

        try {
            // Bootstrap 5.2+ (including recent Tabler bundles).
            if (BootstrapModal
                && typeof BootstrapModal.getOrCreateInstance === 'function') {
                console.debug('[Hero Crop] Bootstrap 5 modal.' + action + '() called.');
                BootstrapModal.getOrCreateInstance($modal[0])[action]();
                return true;
            }

            // Bootstrap 4 registers a jQuery plugin and overwrites
            // window.bootstrap when it is loaded after Tabler/Bootstrap 5.
            if ($.fn && typeof $.fn.modal === 'function') {
                console.debug('[Hero Crop] Bootstrap jQuery modal.' + action + '() called.');
                $modal.modal(action);
                return true;
            }

            // Bootstrap 5.0/5.1 fallback, before getOrCreateInstance existed.
            if (typeof BootstrapModal === 'function') {
                var modalInstance = typeof BootstrapModal.getInstance === 'function'
                    ? BootstrapModal.getInstance($modal[0])
                    : null;

                modalInstance = modalInstance || new BootstrapModal($modal[0]);
                console.debug('[Hero Crop] Bootstrap modal instance.' + action + '() called.');
                modalInstance[action]();
                return true;
            }
        } catch (error) {
            console.error('[Hero Crop] Bootstrap modal ' + action + ' failed.', error);
            return false;
        }

        console.error('[Hero Crop] No compatible Bootstrap Modal API was found.');
        return false;
    }

    function showCropModal() {
        if (!modalAction('show')) {
            window.alert('The crop modal could not be opened. Check the browser console.');
        }
    }

    function hideCropModal() {
        if (modalAction('hide')) {
            return;
        }

        console.error('[Hero Crop] The crop modal could not be closed.');
    }

    function destroyCropper() {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }

        $cropImage.off('.heroCropSource').attr('src', '');

        if (activeFileReader && activeFileReader.readyState === FileReader.LOADING) {
            activeFileReader.abort();
        }

        activeFileReader = null;
    }

    function supportsWebP() {
        var canvas = document.createElement('canvas');

        return canvas.toDataURL('image/webp').indexOf('data:image/webp') === 0;
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

    function resizeCanvas(sourceCanvas, width, height) {
        var canvas = document.createElement('canvas');
        var context = canvas.getContext('2d');

        if (!context) {
            throw new Error('Canvas resize is not supported by this browser.');
        }

        canvas.width = width;
        canvas.height = height;
        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, width, height);
        context.imageSmoothingEnabled = true;
        context.imageSmoothingQuality = 'high';
        context.drawImage(sourceCanvas, 0, 0, width, height);

        return canvas;
    }

    async function compressCanvas(sourceCanvas) {
        var mimeType = supportsWebP() ? 'image/webp' : 'image/jpeg';
        var workingCanvas = sourceCanvas;
        var fallback = null;

        // Try quality first. If a very detailed image is still over 200 KB,
        // reduce the canvas dimensions and repeat until the hard limit is met.
        for (var resizeAttempt = 0; resizeAttempt < 8; resizeAttempt++) {
            for (var index = 0; index < compressionQualities.length; index++) {
                var blob = await canvasToBlob(
                    workingCanvas,
                    mimeType,
                    compressionQualities[index]
                );

                if (blob.size <= targetSize) {
                    return {
                        blob: blob,
                        fileName: 'hero-background.' + (mimeType === 'image/webp' ? 'webp' : 'jpg')
                    };
                }

                if (blob.size <= maximumSize && (!fallback || blob.size < fallback.size)) {
                    fallback = blob;
                }
            }

            if (fallback) {
                return {
                    blob: fallback,
                    fileName: 'hero-background.' + (mimeType === 'image/webp' ? 'webp' : 'jpg')
                };
            }

            workingCanvas = resizeCanvas(
                workingCanvas,
                Math.max(320, Math.round(workingCanvas.width * 0.82)),
                Math.max(120, Math.round(workingCanvas.height * 0.82))
            );
        }

        if (fallback) {
            return {
                blob: fallback,
                fileName: 'hero-background.' + (mimeType === 'image/webp' ? 'webp' : 'jpg')
            };
        }

        throw new Error('The image could not be compressed below 200 KB.');
    }

    $input.on('change', function () {
        var file = this.files && this.files[0];
        var extension = file && file.name.indexOf('.') !== -1
            ? file.name.split('.').pop().toLowerCase()
            : '';

        if (!file) return;

        console.debug('[Hero Crop] File input change event received.', {
            name: file.name,
            type: file.type,
            size: file.size
        });

        if (allowedTypes.indexOf(file.type) === -1
            && allowedExtensions.indexOf(extension) === -1) {
            window.alert('Use a JPG, JPEG, PNG, or WEBP image.');
            this.value = '';
            return;
        }

        if (file.size > maxSize) {
            window.alert('Maximum image size is 5 MB.');
            this.value = '';
            return;
        }

        if (typeof window.Cropper !== 'function') {
            window.alert('The image cropper could not be loaded. Please try again.');
            this.value = '';
            return;
        }

        destroyCropper();
        clearPreviewUrl();
        compressedBackground = null;
        cropWasApplied = false;

        if (typeof window.FileReader !== 'function') {
            console.error('[Hero Crop] FileReader is not supported by this browser.');
            this.value = '';
            window.alert('This browser cannot read the selected image.');
            return;
        }

        activeFileReader = new FileReader();

        activeFileReader.onerror = function (event) {
            console.error('[Hero Crop] FileReader failed.', event);
            $input.val('');
            window.alert('The selected image could not be read.');
        };

        activeFileReader.onload = function (event) {
            var imageSource = event.target && event.target.result;

            console.debug('[Hero Crop] FileReader.onload completed.');

            if (typeof imageSource !== 'string' || !imageSource.startsWith('data:image/')) {
                console.error('[Hero Crop] FileReader returned an invalid image preview.');
                $input.val('');
                window.alert('The selected image preview is invalid.');
                return;
            }

            $cropImage
                .off('.heroCropSource')
                .one('load.heroCropSource', function () {
                    $cropImage.off('error.heroCropSource');
                    console.debug('[Hero Crop] Image preview loaded.', {
                        width: this.naturalWidth,
                        height: this.naturalHeight
                    });
                    showCropModal();
                })
                .one('error.heroCropSource', function (error) {
                    $cropImage.off('load.heroCropSource');
                    console.error('[Hero Crop] Image preview failed to load.', error);
                    $input.val('');
                    window.alert('The selected image preview could not be loaded.');
                })
                .attr('src', imageSource);
        };

        console.debug('[Hero Crop] FileReader.readAsDataURL() called.');
        activeFileReader.readAsDataURL(file);
    });

    function initializeCropper() {
        if (!$cropImage.attr('src')
            || cropper
            || typeof window.Cropper !== 'function'
            || !$cropImage[0].complete
            || !$cropImage[0].naturalWidth) {
            return;
        }

        try {
            console.debug('[Hero Crop] Initializing Cropper.js.');
            cropper = new window.Cropper($cropImage[0], {
                aspectRatio: 16 / 9,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 1,
                responsive: true,
                restore: false,
                background: false,
                preview: '#heroCropPreview',
                ready: function () {
                    var ratio = cropper.getImageData().ratio || 1;
                    $zoom.val(Math.min(3, Math.max(0.1, ratio)));
                    console.debug('[Hero Crop] Cropper.js ready.');
                },
                zoom: function (event) {
                    $zoom.val(Math.min(3, Math.max(0.1, event.detail.ratio)));
                }
            });
        } catch (error) {
            cropper = null;
            console.error('[Hero Crop] Cropper.js initialization failed.', error);
            window.alert('The image cropper could not be initialized. Check the browser console.');
        }
    }

    $cropImage.on('load', function () {
        if ($modal.hasClass('show')) initializeCropper();
    });

    $modal.on('shown.bs.modal', function () {
        console.debug('[Hero Crop] Modal shown event received.');
        // The decoded preview is ready before show() is called. This event
        // initializes Cropper only after Bootstrap has finished its transition.
        initializeCropper();
    });

    $zoom.on('input', function () {
        if (cropper) cropper.zoomTo(parseFloat(this.value));
    });

    $('#heroCropReset').on('click', function () {
        if (!cropper) return;

        cropper.reset();
        var ratio = cropper.getImageData().ratio || 1;
        $zoom.val(Math.min(3, Math.max(0.1, ratio)));
    });

    $applyButton.on('click', async function () {
        if (!cropper) return;

        $applyButton
            .prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Processing...');

        try {
            var croppedCanvas = cropper.getCroppedCanvas({
                fillColor: '#ffffff',
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high'
            });
            var resizedCanvas = resizeCanvas(croppedCanvas, outputWidth, outputHeight);

            compressedBackground = await compressCanvas(resizedCanvas);
            clearPreviewUrl();
            previewUrl = URL.createObjectURL(compressedBackground.blob);
            setHeroBackground(previewUrl);
            cropWasApplied = true;
            hideCropModal();
            $form.trigger('submit');
        } catch (error) {
            window.alert(error.message || 'Image compression failed.');
        } finally {
            $applyButton.prop('disabled', false).html(applyButtonDefaultHtml);
        }
    });

    $modal.on('hidden.bs.modal', function () {
        destroyCropper();

        if (!cropWasApplied) {
            $input.val('');
        }

        cropWasApplied = false;
    });

    $form.on('submit', function (event) {
        event.preventDefault();

        if (!compressedBackground) return;

        var formData = new FormData($form[0]);
        var fieldName = $input.attr('name');

        formData.set(
            fieldName,
            compressedBackground.blob,
            compressedBackground.fileName
        );

        setSaving(true);

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': $form.find('input[name="_token"]').val(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function (response) {
                var backgroundUrl = response && (
                    response.image_url
                    || response.background_url
                    || response.cover_url
                );
                var backgroundPath = response && (
                    response.image_path
                    || response.background_path
                );

                if (backgroundUrl) {
                    $hero.attr('data-background-url', backgroundUrl);
                    setHeroBackground(backgroundUrl);
                } else if (backgroundPath) {
                    var storedUrl = '{{ rtrim(asset('storage'), '/') }}/' + backgroundPath;
                    $hero.attr('data-background-url', storedUrl);
                    setHeroBackground(storedUrl);
                } else if (previewUrl) {
                    $hero.attr('data-background-url', previewUrl);
                    previewUrl = null;
                }

                window.alert(response && response.message
                    ? response.message
                    : 'Background updated successfully.');
            },
            error: function (xhr) {
                var response = xhr.responseJSON || {};
                var message = response.message;

                console.error('Hero background upload failed.', {
                    status: xhr.status,
                    responseJSON: xhr.responseJSON,
                    responseText: xhr.responseText
                });

                if (!message && response.errors) {
                    var fields = Object.keys(response.errors);
                    message = fields.length && response.errors[fields[0]].length
                        ? response.errors[fields[0]][0]
                        : null;
                }

                resetHeroToSavedBackground();
                window.alert(message || 'Upload failed. HTTP status: ' + xhr.status + '.');
            },
            complete: function () {
                clearPreviewUrl();
                compressedBackground = null;
                $input.val('');
                setSaving(false);
            }
        });
    });

    if (window.bootstrap && window.bootstrap.Tooltip) {
        document.querySelectorAll('[data-hero-tooltip]').forEach(function (element) {
            new window.bootstrap.Tooltip(element);
        });
    } else if ($.fn.tooltip) {
        $('[data-hero-tooltip]').tooltip();
    }
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
