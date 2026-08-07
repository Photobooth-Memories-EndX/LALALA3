(function ($) {
    'use strict';

    function initCertificatePreviewModal() {
        var modalId = 'certificatePreviewModal';
        var $triggers = $('.certificate-preview-trigger[data-certificate-preview-url]');

        var canHover = window.matchMedia
            && window.matchMedia('(min-width: 768px) and (hover: hover)').matches;

        if (!canHover || !$triggers.length) {
            $('#' + modalId).remove();
            return;
        }

        var $modal = $('#' + modalId);
        if (!$modal.length) {
            $modal = $(
                '<div class="certificate-preview-modal" id="' + modalId + '" role="dialog" aria-hidden="true" aria-label="Certificate Preview">' +
                    '<div class="certificate-preview-modal-header">' +
                        '<h3 class="certificate-preview-modal-title">Certificate Preview</h3>' +
                        '<button type="button" class="certificate-preview-modal-close" aria-label="Close certificate preview">' +
                            '<i class="ti ti-x" aria-hidden="true"></i>' +
                        '</button>' +
                    '</div>' +
                    '<div class="certificate-preview-modal-body">' +
                        '<iframe class="certificate-preview-native-frame" title="Certificate PDF Document" src="about:blank"></iframe>' +
                        '<div class="certificate-preview-modal-state" aria-live="polite">' +
                            '<span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>' +
                            '<span>Loading certificate preview...</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="certificate-preview-modal-footer">' +
                        '<a class="btn btn-primary certificate-preview-modal-action certificate-preview-full-link" href="#" target="_blank" rel="noopener">' +
                            '<span>View Full Certificate</span>' +
                            '<i class="ti ti-arrow-right" aria-hidden="true"></i>' +
                        '</a>' +
                        '<a class="btn btn-outline-secondary certificate-preview-modal-action certificate-preview-download-link" href="#" download>' +
                            '<i class="ti ti-download" aria-hidden="true"></i>' +
                            '<span>Download PDF</span>' +
                        '</a>' +
                    '</div>' +
                '</div>'
            ).appendTo(document.body);
        }

        var $title = $modal.find('.certificate-preview-modal-title');
        var $body = $modal.find('.certificate-preview-modal-body');
        var $frame = $modal.find('.certificate-preview-native-frame');
        var $state = $modal.find('.certificate-preview-modal-state');
        var $fullLink = $modal.find('.certificate-preview-full-link');
        var $downloadLink = $modal.find('.certificate-preview-download-link');
        var $closeBtn = $modal.find('.certificate-preview-modal-close');

        var openTimer = null;
        var closeTimer = null;
        var activeTrigger = null;
        var activeUrl = '';
        var isLocked = false;

        function showState(message, isError, viewerUrl) {
            $frame.hide();
            var errorContent = '<i class="ti ti-alert-circle text-danger fs-2" aria-hidden="true"></i>' +
                '<span class="fw-semibold mt-1">Unable to preview certificate.</span>' +
                '<span class="text-secondary small">' + message + '</span>' +
                (viewerUrl ? '<a href="' + viewerUrl + '" class="btn btn-sm btn-primary mt-2"><i class="ti ti-external-link me-1"></i> Open Full Viewer</a>' : '');

            var loadingContent = '<span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>' +
                '<span>' + message + '</span>';

            $state
                .removeClass('d-none')
                .toggleClass('text-danger', Boolean(isError))
                .html(isError ? errorContent : loadingContent);
        }

        function positionModal($trigger) {
            var rect = $trigger[0].getBoundingClientRect();
            var gap = 10;
            var edge = 12;
            var modalWidth = $modal.outerWidth() || 480;
            var modalHeight = $modal.outerHeight() || 640;
            var left = rect.right + gap;
            var top = rect.top;

            if (left + modalWidth > window.innerWidth - edge) {
                left = rect.left - modalWidth - gap;
            }

            left = Math.max(edge, Math.min(left, window.innerWidth - modalWidth - edge));

            if (top + modalHeight > window.innerHeight - edge) {
                top = window.innerHeight - modalHeight - edge;
            }
            top = Math.max(edge, top);

            $modal.css({
                left: Math.round(left) + 'px',
                top: Math.round(top) + 'px'
            });
        }

        function loadNativeDocument(url, viewerUrl) {
            if (activeUrl === url && $frame.attr('src') && $frame.attr('src') !== 'about:blank') {
                return;
            }

            activeUrl = url;
            showState('Loading certificate preview...', false);

            var documentNativeUrl = url + '#toolbar=0&navpanes=0&view=FitH';

            $frame.off('load.nativePreview error.nativePreview')
                .one('load.nativePreview', function () {
                    if (activeUrl !== url) return;
                    $state.addClass('d-none');
                    $frame.show();
                })
                .one('error.nativePreview', function () {
                    if (activeUrl !== url) return;
                    showState('Failed to load PDF document.', true, viewerUrl);
                });

            $frame.attr('src', documentNativeUrl);
        }

        function openModal(trigger, lock) {
            var $trigger = $(trigger);
            var url = String($trigger.data('certificate-preview-url') || '');
            var viewerUrl = String($trigger.data('certificate-viewer-url') || $trigger.attr('href') || '#');
            var downloadUrl = String($trigger.data('certificate-download-url') || viewerUrl);
            var title = String($trigger.data('certificate-preview-title') || $trigger.attr('title') || 'Certificate Preview');

            if (!url) return;

            clearTimeout(closeTimer);

            if (activeTrigger && activeTrigger !== trigger) {
                $(activeTrigger).attr('aria-expanded', 'false');
            }

            activeTrigger = trigger;
            if (lock) isLocked = true;

            $trigger.attr({ 'aria-expanded': 'true', 'aria-controls': modalId });
            $title.text(title);
            $fullLink.attr('href', viewerUrl);
            $downloadLink.attr('href', downloadUrl);

            $modal.addClass('is-visible').attr('aria-hidden', 'false');
            positionModal($trigger);
            loadNativeDocument(url, viewerUrl);
        }

        function closeModal(immediate) {
            clearTimeout(openTimer);
            clearTimeout(closeTimer);

            var close = function () {
                if (activeTrigger) {
                    $(activeTrigger).attr('aria-expanded', 'false');
                }
                activeTrigger = null;
                activeUrl = '';
                isLocked = false;

                $modal.removeClass('is-visible').attr('aria-hidden', 'true');

                // Memory cleanup: Clear native iframe src when closed
                setTimeout(function () {
                    if (!$modal.hasClass('is-visible')) {
                        $frame.attr('src', 'about:blank');
                    }
                }, 200);
            };

            if (immediate) {
                close();
            } else {
                closeTimer = setTimeout(close, 180);
            }
        }

        $triggers.off('.certPreview')
            .on('mouseenter.certPreview', function () {
                var trigger = this;
                clearTimeout(openTimer);
                clearTimeout(closeTimer);
                openTimer = setTimeout(function () { openModal(trigger, false); }, 150);
            })
            .on('mouseleave.certPreview', function () {
                if (!isLocked) {
                    closeModal(false);
                }
            })
            .on('click.certPreview', function (e) {
                if (!canHover) {
                    // Mobile: redirect directly to full PDF.js viewer
                    window.location.href = $(this).data('certificate-viewer-url') || $(this).attr('href');
                    return;
                }
                e.preventDefault();
                openModal(this, true);
            });

        $modal.off('.certPreview')
            .on('mouseenter.certPreview', function () {
                clearTimeout(closeTimer);
            })
            .on('mouseleave.certPreview', function () {
                if (!isLocked) {
                    closeModal(false);
                }
            });

        $closeBtn.off('.certPreview').on('click.certPreview', function () {
            closeModal(true);
        });

        $(document).off('keydown.certPreview').on('keydown.certPreview', function (e) {
            if (e.key === 'Escape') closeModal(true);
        });

        $(window).off('resize.certPreview scroll.certPreview').on('resize.certPreview scroll.certPreview', function () {
            if ($modal.hasClass('is-visible') && activeTrigger) {
                positionModal($(activeTrigger));
            }
        });
    }

    $(function () {
        initCertificatePreviewModal();
    });

    window.initCertificatePreviewModal = initCertificatePreviewModal;

})(jQuery);
