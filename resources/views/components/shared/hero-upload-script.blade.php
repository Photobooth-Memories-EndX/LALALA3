@once
<script>
$(function () {
    var $form = $('.js-hero-background-form').first();

    if (!$form.length) return;

    var $hero = $form.closest('[data-hero-background]');
    var $input = $form.find('.js-hero-background-input');
    var $trigger = $form.find('.js-hero-background-trigger');
    var $saveButton = $form.find('.js-hero-background-save');
    var $removeButton = $form.find('.js-hero-background-remove');
    var $modal = $('#heroBackgroundCropModal');
    var $cropImage = $('#heroCropImage');
    var $zoom = $('#heroCropZoom');
    var $applyButton = $('#heroCropApply');
    var $cropPreview = $('#heroCropPreview');
    var $cropDimensions = $('#heroCropDimensions');
    var $resetButton = $('#heroCropReset');
    var outputWidth = 1600;
    var outputHeight = 320;
    var allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    var allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    var maximumUploadSize = 5 * 1024 * 1024;
    var maximumOutputSize = 200 * 1024;
    var compressionQualities = [0.82, 0.78, 0.74, 0.70];
    var cropper = null;
    var activeFileReader = null;
    var previewUrl = null;
    var compressedBackground = null;
    var cropWasApplied = false;
    var applyButtonDefaultHtml = $applyButton.html();

    if (!$hero.length || !$input.length || !$modal.length || !$cropImage.length
        || !Number.isFinite(outputWidth) || outputWidth <= 0
        || !Number.isFinite(outputHeight) || outputHeight <= 0) {
        console.error('[Hero Crop] Required frontend elements are missing.');
        return;
    }

    $cropPreview.css('--hero-crop-aspect-ratio', outputWidth + ' / ' + outputHeight);
    $cropDimensions.text(outputWidth + ' × ' + outputHeight + ' Hero');

    function setHeroBackground(url) {
        $hero.css('--hero-bg-image', 'url("' + url + '")');
    }

    function clearPreviewUrl() {
        if (!previewUrl) return;

        URL.revokeObjectURL(previewUrl);
        previewUrl = null;
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
            if (BootstrapModal && typeof BootstrapModal.getOrCreateInstance === 'function') {
                BootstrapModal.getOrCreateInstance($modal[0])[action]();
                return true;
            }

            if ($.fn && typeof $.fn.modal === 'function') {
                $modal.modal(action);
                return true;
            }

            if (typeof BootstrapModal === 'function') {
                var instance = typeof BootstrapModal.getInstance === 'function'
                    ? BootstrapModal.getInstance($modal[0])
                    : null;

                (instance || new BootstrapModal($modal[0]))[action]();
                return true;
            }
        } catch (error) {
            console.error('[Hero Crop] Modal action failed.', error);
        }

        return false;
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
        var detailCanvas = sourceCanvas;
        var workingCanvas = sourceCanvas;

        if (!supportsWebP()) {
            throw new Error('This browser cannot create WebP images. Please use a current browser.');
        }

        for (var resizeAttempt = 0; resizeAttempt < 8; resizeAttempt++) {
            for (var index = 0; index < compressionQualities.length; index++) {
                var blob = await canvasToBlob(
                    workingCanvas,
                    'image/webp',
                    compressionQualities[index]
                );

                if (blob.size <= maximumOutputSize) {
                    return {
                        blob: blob,
                        fileName: 'hero-background.webp'
                    };
                }
            }

            detailCanvas = resizeCanvas(
                detailCanvas,
                Math.max(640, Math.round(detailCanvas.width * 0.85)),
                Math.max(240, Math.round(detailCanvas.height * 0.85))
            );
            workingCanvas = resizeCanvas(detailCanvas, outputWidth, outputHeight);
        }

        throw new Error('The image could not be compressed below 200 KB.');
    }

    function initializeCropper() {
        if (!$cropImage.attr('src') || cropper || typeof window.Cropper !== 'function'
            || !$cropImage[0].complete || !$cropImage[0].naturalWidth) {
            return;
        }

        try {
            cropper = new window.Cropper($cropImage[0], {
                aspectRatio: 1600 / 320,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 1,
                cropBoxMovable: false,
                cropBoxResizable: false,
                toggleDragModeOnDblclick: false,
                scalable: false,
                rotatable: false,
                responsive: true,
                restore: false,
                background: false,
                preview: '#heroCropPreview',
                ready: function () {
                    var ratio = cropper.getImageData().ratio || 1;
                    $zoom.val(Math.min(3, Math.max(0.1, ratio)));
                },
                zoom: function (event) {
                    $zoom.val(Math.min(3, Math.max(0.1, event.detail.ratio)));
                }
            });
        } catch (error) {
            cropper = null;
            console.error('[Hero Crop] Cropper initialization failed.', error);
            window.alert('The image cropper could not be initialized.');
        }
    }

    $input.on('change', function () {
        var file = this.files && this.files[0];
        var extension = file && file.name.includes('.')
            ? file.name.split('.').pop().toLowerCase()
            : '';

        if (!file) return;

        if (allowedTypes.indexOf(file.type) === -1
            && allowedExtensions.indexOf(extension) === -1) {
            window.alert('Use a JPG, JPEG, PNG, or WEBP image.');
            this.value = '';
            return;
        }

        if (file.size > maximumUploadSize) {
            window.alert('Maximum image size is 5 MB.');
            this.value = '';
            return;
        }

        if (typeof window.Cropper !== 'function' || typeof window.FileReader !== 'function') {
            window.alert('The image cropper could not be loaded. Please try again.');
            this.value = '';
            return;
        }

        destroyCropper();
        clearPreviewUrl();
        compressedBackground = null;
        cropWasApplied = false;
        activeFileReader = new FileReader();

        activeFileReader.onerror = function () {
            $input.val('');
            window.alert('The selected image could not be read.');
        };

        activeFileReader.onload = function (event) {
            var imageSource = event.target && event.target.result;

            if (typeof imageSource !== 'string' || !imageSource.startsWith('data:image/')) {
                $input.val('');
                window.alert('The selected image preview is invalid.');
                return;
            }

            $cropImage
                .off('.heroCropSource')
                .one('load.heroCropSource', function () {
                    $cropImage.off('error.heroCropSource');
                    if (!modalAction('show')) {
                        $input.val('');
                        window.alert('The crop modal could not be opened.');
                    }
                })
                .one('error.heroCropSource', function () {
                    $cropImage.off('load.heroCropSource');
                    $input.val('');
                    window.alert('The selected image preview could not be loaded.');
                })
                .attr('src', imageSource);
        };

        activeFileReader.readAsDataURL(file);
    });

    $cropImage.on('load', function () {
        if ($modal.hasClass('show')) initializeCropper();
    });

    $modal.on('shown.bs.modal', initializeCropper);

    $zoom.on('input', function () {
        if (cropper) cropper.zoomTo(Number.parseFloat(this.value));
    });

    $resetButton.on('click', function () {
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
                width: 1600,
                height: 320,
                fillColor: '#ffffff',
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high'
            });

            compressedBackground = await compressCanvas(croppedCanvas);
            clearPreviewUrl();
            previewUrl = URL.createObjectURL(compressedBackground.blob);
            setHeroBackground(previewUrl);
            cropWasApplied = true;
            modalAction('hide');
            $form.trigger('submit');
        } catch (error) {
            window.alert(error.message || 'Image compression failed.');
        } finally {
            $applyButton.prop('disabled', false).html(applyButtonDefaultHtml);
        }
    });

    $modal.on('hidden.bs.modal', function () {
        destroyCropper();

        if (!cropWasApplied) $input.val('');
        cropWasApplied = false;
    });

    $form.on('submit', function (event) {
        event.preventDefault();

        if (!compressedBackground) return;

        var formData = new FormData($form[0]);
        formData.set(
            $input.attr('name'),
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
                var backgroundUrl = response && (response.image_url || response.background_url);
                var backgroundPath = response && (response.image_path || response.background_path);

                if (!backgroundUrl && backgroundPath) {
                    backgroundUrl = '{{ rtrim(asset('storage'), '/') }}/' + backgroundPath;
                }

                if (backgroundUrl) {
                    $hero.attr('data-background-url', backgroundUrl);
                    $hero.attr('data-has-custom', 'true');
                    setHeroBackground(backgroundUrl);
                    $removeButton.removeClass('d-none');
                } else if (previewUrl) {
                    $hero.attr('data-background-url', previewUrl);
                    $hero.attr('data-has-custom', 'true');
                    previewUrl = null;
                    $removeButton.removeClass('d-none');
                }

                window.alert(response && response.message
                    ? response.message
                    : 'Background updated successfully.');
            },
            error: function (xhr) {
                var response = xhr.responseJSON || {};
                var message = response.message;

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

    $removeButton.on('click', function () {
        if (!window.confirm('Are you sure you want to reset your hero background to the default template?')) {
            return;
        }

        var removeUrl = $form.data('remove-url');
        if (!removeUrl) return;

        $removeButton.prop('disabled', true);

        $.ajax({
            url: removeUrl,
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': $form.find('input[name="_token"]').val(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function (response) {
                var defaultUrl = (response && response.image_url) || $hero.attr('data-default-background-url');
                if (defaultUrl) {
                    $hero.attr('data-background-url', defaultUrl);
                    $hero.attr('data-has-custom', 'false');
                    setHeroBackground(defaultUrl);
                }
                $removeButton.addClass('d-none');
                window.alert(response && response.message
                    ? response.message
                    : 'Hero background reset to default template.');
            },
            error: function (xhr) {
                var response = xhr.responseJSON || {};
                window.alert(response.message || 'Failed to remove custom background.');
            },
            complete: function () {
                $removeButton.prop('disabled', false);
            }
        });
    });

    if (window.bootstrap && window.bootstrap.Tooltip) {
        document.querySelectorAll('[data-hero-tooltip], .shared-hero-donut-hit[data-bs-toggle="tooltip"]')
            .forEach(function (element) {
                new window.bootstrap.Tooltip(element);
            });
    } else if ($.fn.tooltip) {
        $('[data-hero-tooltip], .shared-hero-donut-hit[data-bs-toggle="tooltip"]').tooltip();
    }
});
</script>
@endonce
