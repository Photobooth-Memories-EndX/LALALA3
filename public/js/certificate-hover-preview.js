(function ($) {
    'use strict';

    var previewId = 'certificateHoverPreview';
    console.log('[HoverPreview] certificate-hover-preview.js loaded successfully');

    function ensurePreviewDomExists() {
        var $preview = $('#' + previewId);
        if ($preview.length) {
            // If popover is nested inside a container with overflow:hidden (e.g. .table-responsive or #learning-tab-content), move it directly to document.body
            if (!$preview.parent().is('body')) {
                console.log('[HoverPreview] Moving #certificateHoverPreview from nested container to document.body');
                $preview.appendTo(document.body);
            }
        } else {
            console.log('[HoverPreview] Creating global #certificateHoverPreview DOM element and appending to document.body');
            $preview = $(
                '<aside class="certificate-hover-preview" id="' + previewId + '" role="dialog" aria-hidden="true" aria-label="Certificate Hover Preview">' +
                    '<div class="certificate-hover-preview-header">' +
                        '<h3 class="certificate-hover-preview-title">Certificate Preview</h3>' +
                        '<button type="button" class="certificate-hover-preview-close" aria-label="Close certificate preview">' +
                            '<i class="ti ti-x" aria-hidden="true"></i>' +
                        '</button>' +
                    '</div>' +
                    '<div class="certificate-hover-preview-body">' +
                        '<iframe class="certificate-hover-preview-frame" title="Certificate PDF Document" src="about:blank"></iframe>' +
                        '<div class="certificate-hover-preview-state" aria-live="polite">' +
                            '<span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>' +
                            '<span>Loading certificate preview...</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="certificate-hover-preview-footer">' +
                        '<a class="btn btn-primary certificate-hover-preview-action certificate-hover-preview-full-link" href="#" target="_blank" rel="noopener">' +
                            '<span>View Full Certificate</span>' +
                            '<i class="ti ti-arrow-right" aria-hidden="true"></i>' +
                        '</a>' +
                        '<a class="btn btn-outline-secondary certificate-hover-preview-action certificate-hover-preview-download-link" href="#" download>' +
                            '<i class="ti ti-download" aria-hidden="true"></i>' +
                            '<span>Download PDF</span>' +
                        '</a>' +
                    '</div>' +
                '</aside>'
            ).appendTo(document.body);
        }
        return $preview;
    }

    function initCertificateHoverPreview() {
        console.log('[HoverPreview] initCertificateHoverPreview() called');

        var canHover = window.matchMedia
            && window.matchMedia('(min-width: 768px) and (hover: hover)').matches;

        if (!canHover) {
            console.log('[HoverPreview] Screen is mobile / touch device (< 768px or no hover). Disabling hover preview.');
            $('#' + previewId).remove();
            return;
        }

        var $preview = ensurePreviewDomExists();
        var $title = $preview.find('.certificate-hover-preview-title');
        var $frame = $preview.find('.certificate-hover-preview-frame');
        var $state = $preview.find('.certificate-hover-preview-state');
        var $fullLink = $preview.find('.certificate-hover-preview-full-link');
        var $downloadLink = $preview.find('.certificate-hover-preview-download-link');
        var $closeBtn = $preview.find('.certificate-hover-preview-close');

        var openTimer = null;
        var closeTimer = null;
        var activeTrigger = null;
        var activeUrl = '';

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

        function positionPreview($trigger) {
            var rect = $trigger[0].getBoundingClientRect();
            var gap = 10;
            var edge = 12;
            var previewWidth = $preview.outerWidth() || 420;
            var previewHeight = $preview.outerHeight() || 560;

            var left = rect.right + gap;
            var top = rect.top;

            if (left + previewWidth > window.innerWidth - edge) {
                left = rect.left - previewWidth - gap;
            }

            left = Math.max(edge, Math.min(left, window.innerWidth - previewWidth - edge));

            if (top + previewHeight > window.innerHeight - edge) {
                top = window.innerHeight - previewHeight - edge;
            }
            top = Math.max(edge, top);

            $preview.css({
                position: 'fixed',
                left: Math.round(left) + 'px',
                top: Math.round(top) + 'px',
                zIndex: 999999
            });

            console.log('[HoverPreview] Position calculated -> left:', left, 'top:', top);
        }

        function loadNativeDocument(url, viewerUrl) {
            if (activeUrl === url && $frame.attr('src') && $frame.attr('src') !== 'about:blank') {
                return;
            }

            activeUrl = url;
            showState('Loading certificate preview...', false);

            var documentNativeUrl = url + '#toolbar=0&navpanes=0&view=FitH';

            $frame.off('load.certPreview error.certPreview')
                .one('load.certPreview', function () {
                    if (activeUrl !== url) return;
                    console.log('[HoverPreview] Native PDF iframe loaded successfully:', documentNativeUrl);
                    $state.addClass('d-none');
                    $frame.show();
                })
                .one('error.certPreview', function () {
                    if (activeUrl !== url) return;
                    console.warn('[HoverPreview] Native PDF iframe error loading:', documentNativeUrl);
                    showState('Failed to load PDF document.', true, viewerUrl);
                });

            $frame.attr('src', documentNativeUrl);
        }

        function openPreview(trigger) {
            var $trigger = $(trigger);
            var url = String($trigger.data('certificate-preview-url') || '');
            var viewerUrl = String($trigger.data('certificate-viewer-url') || $trigger.attr('href') || '#');
            var downloadUrl = String($trigger.data('certificate-download-url') || viewerUrl);
            var title = String($trigger.data('certificate-preview-title') || $trigger.attr('title') || 'Certificate Preview');

            if (!url) {
                console.warn('[HoverPreview] Trigger missing data-certificate-preview-url:', trigger);
                return;
            }

            console.log('[HoverPreview] Opening preview for:', title, 'URL:', url);

            clearTimeout(closeTimer);

            if (activeTrigger && activeTrigger !== trigger) {
                $(activeTrigger).attr('aria-expanded', 'false');
            }

            activeTrigger = trigger;
            $preview = ensurePreviewDomExists();

            $trigger.attr({ 'aria-expanded': 'true', 'aria-controls': previewId });
            $title.text(title);
            $fullLink.attr('href', viewerUrl);
            $downloadLink.attr('href', downloadUrl);

            $preview.addClass('is-visible').attr('aria-hidden', 'false');
            positionPreview($trigger);
            loadNativeDocument(url, viewerUrl);
        }

        function closePreview(immediate) {
            clearTimeout(openTimer);
            clearTimeout(closeTimer);

            var close = function () {
                if (activeTrigger) {
                    $(activeTrigger).attr('aria-expanded', 'false');
                }
                activeTrigger = null;
                activeUrl = '';

                console.log('[HoverPreview] Preview closed');
                $preview.removeClass('is-visible').attr('aria-hidden', 'true');

                setTimeout(function () {
                    if (!$preview.hasClass('is-visible')) {
                        $frame.attr('src', 'about:blank');
                    }
                }, 200);
            };

            if (immediate) {
                close();
            } else {
                closeTimer = setTimeout(close, 200);
            }
        }

        // EVENT DELEGATION ON DOCUMENT
        $(document)
            .off('.certHoverPreview', '.certificate-preview-trigger')
            .on('mouseenter.certHoverPreview', '.certificate-preview-trigger', function () {
                var trigger = this;
                console.log('[HoverPreview] mouseenter on .certificate-preview-trigger');
                clearTimeout(openTimer);
                clearTimeout(closeTimer);
                openTimer = setTimeout(function () { openPreview(trigger); }, 150);
            })
            .on('mouseleave.certHoverPreview', '.certificate-preview-trigger', function () {
                console.log('[HoverPreview] mouseleave on .certificate-preview-trigger');
                closePreview(false);
            })
            .on('click.certHoverPreview', '.certificate-preview-trigger', function () {
                console.log('[HoverPreview] click on .certificate-preview-trigger -> closing popup & navigating to full viewer');
                closePreview(true);
            });

        // POPUP MOUSE EVENTS
        $(document)
            .off('.certHoverPreviewPopup', '#' + previewId)
            .on('mouseenter.certHoverPreviewPopup', '#' + previewId, function () {
                console.log('[HoverPreview] mouseenter inside popup -> keeping open');
                clearTimeout(closeTimer);
            })
            .on('mouseleave.certHoverPreviewPopup', '#' + previewId, function () {
                console.log('[HoverPreview] mouseleave from popup');
                closePreview(false);
            });

        $(document)
            .off('.certHoverPreviewClose', '#' + previewId + ' .certificate-hover-preview-close')
            .on('click.certHoverPreviewClose', '#' + previewId + ' .certificate-hover-preview-close', function () {
                closePreview(true);
            });

        $(document).off('keydown.certHoverPreview').on('keydown.certHoverPreview', function (e) {
            if (e.key === 'Escape') closePreview(true);
        });

        $(window).off('resize.certHoverPreview scroll.certHoverPreview').on('resize.certHoverPreview scroll.certHoverPreview', function () {
            if ($preview.hasClass('is-visible') && activeTrigger) {
                positionPreview($(activeTrigger));
            }
        });
    }

    $(function () {
        initCertificateHoverPreview();
    });

    window.initCertificateHoverPreview = initCertificateHoverPreview;
    window.initCertificatePreviewModal = initCertificateHoverPreview;
    window.CertificateHoverPreview = { init: initCertificateHoverPreview };

})(jQuery);
