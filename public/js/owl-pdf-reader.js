(function (window, document) {
    'use strict';

    function OwlPdfReader(config) {
        this.config = Object.assign({
            shellId: 'pdfviewer-shell',
            viewerSelector: '.owl-pdf-viewer',
            downloadButtonId: 'pdf-download-btn',
            fullscreenButtonId: 'pdf-fs-btn',
            fullscreenIconId: 'pdf-fs-icon',
            readingModeButtonId: 'pdf-reading-mode-btn',
            readingModeIconId: 'pdf-reading-mode-icon',
            readingModeLabelId: 'pdf-reading-mode-label'
        }, config || {});

        this.viewer = null;
        this.pdfUrl = '';
        this.rotation = 0;
        this.restoringView = false;
        this.changingLayout = false;
        this.documentReady = false;
        this.modeTransitionTimer = null;
        this.pageTimingEnabled = Boolean(this.config.pageTimingEnabled);
        this.pageDuration = Math.max(1, parseInt(this.config.pageDuration, 10) || 15);
        this.completedPages = Math.max(0, parseInt(this.config.completedPages, 10) || 0);
        this.contentCompleted = Boolean(this.config.contentCompleted);
        this.totalPages = 0;
        this.timedPage = 0;
        this.pageElapsed = 0;
        this.pageTimer = null;
        this.pageTimerLastTick = 0;
        this.pageCompletionPending = false;
        this.pageCompletionRetryAt = 0;
    }

    OwlPdfReader.prototype.getShell = function () {
        return document.getElementById(this.config.shellId);
    };

    OwlPdfReader.prototype.getViewerElement = function () {
        return document.querySelector(this.config.viewerSelector);
    };

    OwlPdfReader.prototype.getViewerOptions = function (callbacks) {
        var reader = this;
        var handlers = callbacks || {};

        return {
            extraPagesToLoad: 0,

            onNewPage: function (page, pageno) {
                var pageElement = page.get(0);
                var isActive = Number(pageno) === 1;

                pageElement.classList.toggle('owl-pdf-page-active', isActive);
                pageElement.setAttribute('aria-hidden', isActive ? 'false' : 'true');
            },

            onDocumentReady: function () {
                try {
                    if (typeof handlers.onDocumentReady === 'function') {
                        handlers.onDocumentReady.call(this);
                    }
                } finally {
                    reader.documentReady = true;
                    reader.initializePageTiming(this.getPageCount());
                }
            },

            onZoomChange: function (zoom) {
                reader.updateZoomLabel(zoom);

                if (typeof handlers.onZoomChange === 'function') {
                    handlers.onZoomChange.call(this, zoom);
                }
            },

            onActivePageChanged: function (page, pageno) {
                if (!pageno) return;

                if (!reader.canActivatePage(pageno)) {
                    reader.restoreAllowedPage();
                    return;
                }

                reader.syncSinglePage(pageno);
                reader.updatePageLabel(pageno, this.getPageCount());
                reader.activateTimedPage(pageno, this.getPageCount());

                if (
                    reader.documentReady
                    && !reader.restoringView
                    && !reader.changingLayout
                    && typeof handlers.onActivePageChanged === 'function'
                ) {
                    handlers.onActivePageChanged.call(this, page, pageno);
                }
            }
        };
    };

    OwlPdfReader.prototype.attach = function (viewer, pdfUrl) {
        this.viewer = viewer;
        this.pdfUrl = pdfUrl || '';

        this.patchVisibleAreaMeasurement();
        this.bindControls();
        this.updateDownloadLink();

        return this;
    };

    OwlPdfReader.prototype.updateDownloadLink = function () {
        var button = document.getElementById(this.config.downloadButtonId);

        if (button && this.pdfUrl) {
            button.href = this.pdfUrl;
        }
    };

    OwlPdfReader.prototype.updateZoomLabel = function (zoom) {
        var shell = this.getShell();
        var label = shell ? shell.querySelector('.zoomval') : null;

        if (label) {
            zoom = parseInt(zoom * 10000, 10) / 100;
            label.textContent = zoom + '%';
        }
    };

    OwlPdfReader.prototype.updatePageLabel = function (page, totalPages) {
        var shell = this.getShell();
        var label = shell ? shell.querySelector('.pageno') : null;

        if (label) {
            label.textContent = page + ' / ' + totalPages;
        }
    };

    OwlPdfReader.prototype.initializePageTiming = function (totalPages) {
        this.totalPages = Math.max(0, parseInt(totalPages, 10) || 0);

        if (this.contentCompleted && this.totalPages > 0) {
            this.completedPages = this.totalPages;
        } else if (this.totalPages > 0) {
            this.completedPages = Math.min(this.completedPages, this.totalPages);
        }

        var state = this.getViewState();
        var activePage = Math.max(1, parseInt(state.page, 10) || 1);

        this.updatePageLabel(activePage, this.totalPages);
        this.activateTimedPage(activePage, this.totalPages);
    };

    OwlPdfReader.prototype.canActivatePage = function (page) {
        if (!this.pageTimingEnabled || !this.documentReady) return true;

        return Number(page) <= Math.min(this.totalPages, this.completedPages + 1);
    };

    OwlPdfReader.prototype.restoreAllowedPage = function () {
        var reader = this;
        var allowedPage = Math.max(1, Math.min(this.totalPages, this.completedPages + 1));

        setTimeout(function () {
            if (reader.viewer && typeof reader.viewer.scrollToPage === 'function') {
                reader.viewer.scrollToPage(allowedPage);
            }
        }, 0);
    };

    OwlPdfReader.prototype.activateTimedPage = function (page, totalPages) {
        page = Math.max(1, parseInt(page, 10) || 1);
        this.totalPages = Math.max(0, parseInt(totalPages, 10) || this.totalPages);
        this.updateNavigationButtons(page);

        if (!this.pageTimingEnabled || !this.documentReady) return;

        if (page <= this.completedPages || (this.totalPages > 0 && page > this.totalPages)) {
            this.stopPageTimer();
            this.timedPage = page;
            this.pageElapsed = this.pageDuration;
            this.updatePageTimingLabels(0, true);
            return;
        }

        if (page !== this.completedPages + 1) {
            this.restoreAllowedPage();
            return;
        }

        if (this.timedPage === page && this.pageTimer) {
            return;
        }

        this.stopPageTimer();
        this.timedPage = page;
        this.pageElapsed = 0;
        this.pageCompletionPending = false;
        this.pageCompletionRetryAt = 0;
        this.updatePageTimingLabels(this.pageDuration, false);

        if (typeof this.config.onPageStarted === 'function') {
            this.config.onPageStarted(page, this.totalPages);
        }

        this.pageTimerLastTick = Date.now();
        this.pageTimer = setInterval(this.tickPageTimer.bind(this), 250);
    };

    OwlPdfReader.prototype.tickPageTimer = function () {
        var now = Date.now();
        var delta = Math.max(0, (now - this.pageTimerLastTick) / 1000);
        this.pageTimerLastTick = now;

        if (document.visibilityState !== 'visible') {
            return;
        }

        this.pageElapsed = Math.min(this.pageDuration, this.pageElapsed + delta);
        var remaining = this.pageElapsed >= this.pageDuration && now < this.pageCompletionRetryAt
            ? Math.max(1, Math.ceil((this.pageCompletionRetryAt - now) / 1000))
            : Math.max(0, Math.ceil(this.pageDuration - this.pageElapsed));
        this.updatePageTimingLabels(remaining, false);

        if (this.pageElapsed >= this.pageDuration
            && !this.pageCompletionPending
            && now >= this.pageCompletionRetryAt) {
            this.completeTimedPage();
        }
    };

    OwlPdfReader.prototype.completeTimedPage = function () {
        var reader = this;
        var page = this.timedPage;
        var completion = typeof this.config.onPageCompleted === 'function'
            ? this.config.onPageCompleted(page, this.totalPages, this.pageElapsed)
            : true;

        this.pageCompletionPending = true;

        Promise.resolve(completion).then(function (result) {
            var completed = result === true || Boolean(result && result.page_completed);

            if (completed) {
                reader.completedPages = Math.max(
                    reader.completedPages,
                    parseInt(result && result.last_page, 10) || page
                );
                reader.pageCompletionPending = false;
                reader.stopPageTimer();
                reader.updatePageTimingLabels(0, true);
                reader.updateNavigationButtons(page);
                return;
            }

            var retrySeconds = Math.max(1, parseInt(result && result.remaining_seconds, 10) || 1);
            reader.pageCompletionPending = false;
            reader.pageCompletionRetryAt = Date.now() + (retrySeconds * 1000);
            reader.updatePageTimingLabels(retrySeconds, false);
        }).catch(function () {
            reader.pageCompletionPending = false;
            reader.pageCompletionRetryAt = Date.now() + 1000;
            reader.updatePageTimingLabels(1, false);
        });
    };

    OwlPdfReader.prototype.stopPageTimer = function () {
        if (this.pageTimer) {
            clearInterval(this.pageTimer);
            this.pageTimer = null;
        }
    };

    OwlPdfReader.prototype.updatePageTimingLabels = function (remaining, complete) {
        var shell = this.getShell();
        if (!shell) return;

        shell.querySelectorAll('.pdf-tb-minimum-time-value').forEach(function (label) {
            label.textContent = this.pageDuration;
        }, this);
        shell.querySelectorAll('.pdf-tb-remaining-time-value').forEach(function (label) {
            label.textContent = Math.max(0, parseInt(remaining, 10) || 0);
        });
        shell.querySelectorAll('.pdf-tb-reading-timing').forEach(function (group) {
            group.classList.toggle('is-complete', Boolean(complete));
        });
    };

    OwlPdfReader.prototype.updateNavigationButtons = function (page) {
        var shell = this.getShell();
        if (!shell) return;

        page = Math.max(1, parseInt(page, 10) || 1);
        var nextLocked = this.pageTimingEnabled && page > this.completedPages;

        shell.querySelectorAll('[data-pdf-nav="previous"]').forEach(function (button) {
            button.disabled = page <= 1;
        });
        shell.querySelectorAll('[data-pdf-nav="next"]').forEach(function (button) {
            button.disabled = !this.totalPages || page >= this.totalPages || nextLocked;
        }, this);
    };

    OwlPdfReader.prototype.syncSinglePage = function (pageno) {
        var reader = this;
        var viewerElement = this.getViewerElement();

        if (!viewerElement || !pageno) return;

        viewerElement.querySelectorAll('.pdfpage').forEach(function (pageElement) {
            var isActive = Number(pageElement.dataset.page) === Number(pageno);

            pageElement.classList.toggle('owl-pdf-page-active', isActive);
            pageElement.setAttribute('aria-hidden', isActive ? 'false' : 'true');
        });

        viewerElement.scrollTop = 0;
        viewerElement.scrollLeft = 0;

        var refreshPage = function () {
            if (!reader.viewer) return;

            var activePage = reader.viewer.getActivePage();
            var activePageNumber = activePage && typeof activePage.data === 'function'
                ? activePage.data('page')
                : null;

            if (
                Number(activePageNumber) === Number(pageno)
                && typeof reader.viewer.refreshAll === 'function'
            ) {
                reader.viewer.refreshAll();
            }
        };

        if (typeof window.requestAnimationFrame === 'function') {
            window.requestAnimationFrame(refreshPage);
        } else {
            setTimeout(refreshPage, 0);
        }
    };

    OwlPdfReader.prototype.patchVisibleAreaMeasurement = function () {
        var reader = this;

        if (!this.viewer || typeof this.viewer._areaOfPageVisible !== 'function') {
            return;
        }

        var measureVisibleArea = this.viewer._areaOfPageVisible;

        this.viewer._areaOfPageVisible = function (page) {
            if (
                page
                && typeof page.hasClass === 'function'
                && !page.hasClass('owl-pdf-page-active')
            ) {
                return 0;
            }

            return measureVisibleArea.call(reader.viewer, page);
        };
    };

    OwlPdfReader.prototype.getViewState = function () {
        var activePage = this.viewer ? this.viewer.getActivePage() : null;
        var pageNumber = activePage && typeof activePage.data === 'function'
            ? activePage.data('page')
            : 1;

        return {
            page: pageNumber || 1,
            zoom: this.viewer && typeof this.viewer.getZoom === 'function'
                ? this.viewer.getZoom()
                : 1
        };
    };

    OwlPdfReader.prototype.rotate = function (delta) {
        var reader = this;

        if (!this.viewer || !this.viewer.getPageCount()) {
            return Promise.resolve();
        }

        var viewState = this.getViewState();
        var rotationResult;

        this.rotation = (this.rotation + delta + 360) % 360;
        this.restoringView = true;

        if (typeof this.viewer.setRotation === 'function') {
            rotationResult = this.viewer.setRotation(this.rotation);
        } else if (typeof this.viewer.rotate === 'function') {
            rotationResult = this.viewer.rotate(this.rotation);
        } else {
            var viewerElement = this.getViewerElement();
            if (viewerElement) {
                viewerElement.style.transform = 'rotate(' + this.rotation + 'deg)';
            }
        }

        return Promise.resolve(rotationResult).then(function () {
            if (typeof reader.viewer.setZoom === 'function') {
                reader.viewer.setZoom(viewState.zoom);
            }

            var currentState = reader.getViewState();
            if (currentState.page !== viewState.page) {
                reader.viewer.scrollToPage(
                    Math.min(viewState.page, reader.viewer.getPageCount())
                );
            }

            reader.restoringView = false;
        }, function () {
            reader.restoringView = false;
        });
    };

    OwlPdfReader.prototype.setReadingMode = function (mode) {
        var reader = this;
        var shell = this.getShell();

        if (!shell || !this.viewer) return;

        var mobileQuery = typeof window.matchMedia === 'function'
            ? window.matchMedia('(max-width: 575.98px)')
            : null;

        if (mobileQuery && mobileQuery.matches) {
            mode = 'portrait';
        }

        var isLandscape = mode === 'landscape';
        var viewState = this.getViewState();
        var button = document.getElementById(this.config.readingModeButtonId);
        var icon = document.getElementById(this.config.readingModeIconId);
        var label = document.getElementById(this.config.readingModeLabelId);

        this.changingLayout = true;
        shell.classList.toggle('owl-pdf-mode-portrait', !isLandscape);
        shell.classList.toggle('owl-pdf-mode-landscape', isLandscape);
        shell.dataset.pdfReadingMode = isLandscape ? 'landscape' : 'portrait';

        if (button) {
            button.setAttribute('aria-pressed', isLandscape ? 'true' : 'false');
            button.title = isLandscape
                ? 'Switch to Portrait layout'
                : 'Switch to Landscape layout';
        }
        if (icon) {
            icon.className = isLandscape
                ? 'ti ti-rectangle'
                : 'ti ti-rectangle-vertical';
        }
        if (label) {
            label.textContent = isLandscape ? 'Landscape' : 'Portrait';
        }

        if (this.modeTransitionTimer) {
            clearTimeout(this.modeTransitionTimer);
        }

        this.modeTransitionTimer = setTimeout(function () {
            if (typeof reader.viewer.refreshAll === 'function') {
                reader.viewer.refreshAll();
            }

            if (
                typeof reader.viewer.getZoom === 'function'
                && Math.abs(reader.viewer.getZoom() - viewState.zoom) > 0.0001
            ) {
                reader.viewer.setZoom(viewState.zoom);
            }

            var currentState = reader.getViewState();
            if (currentState.page !== viewState.page) {
                reader.viewer.scrollToPage(viewState.page);
            }

            reader.changingLayout = false;
            reader.modeTransitionTimer = null;
        }, 340);
    };

    OwlPdfReader.prototype.toggleReadingMode = function () {
        var shell = this.getShell();
        if (!shell) return;

        this.setReadingMode(
            shell.classList.contains('owl-pdf-mode-landscape')
                ? 'portrait'
                : 'landscape'
        );
    };

    OwlPdfReader.prototype.print = function () {
        var reader = this;
        var frame = document.createElement('iframe');
        var handled = false;

        frame.style.cssText = 'position:fixed;left:-9999px;top:-9999px;width:0;height:0;border:none;';

        function removePrintFrame() {
            if (frame.parentNode) {
                frame.parentNode.removeChild(frame);
            }
        }

        function openPrintFallback() {
            if (handled) return;
            handled = true;
            removePrintFrame();
            window.open(reader.pdfUrl, '_blank', 'noopener');
        }

        frame.onload = function () {
            try {
                if (!frame.contentWindow || typeof frame.contentWindow.print !== 'function') {
                    throw new Error('Browser print is unavailable.');
                }

                frame.contentWindow.focus();
                frame.contentWindow.print();
                handled = true;
                setTimeout(removePrintFrame, 3000);
            } catch (error) {
                openPrintFallback();
            }
        };
        frame.onerror = openPrintFallback;
        frame.src = this.pdfUrl;
        document.body.appendChild(frame);
        setTimeout(openPrintFallback, 5000);
    };

    OwlPdfReader.prototype.syncFullscreenButton = function () {
        var shell = this.getShell();
        var icon = document.getElementById(this.config.fullscreenIconId);
        var button = document.getElementById(this.config.fullscreenButtonId);
        var fullscreenElement = document.fullscreenElement
            || document.webkitFullscreenElement
            || null;
        var isFullscreen = fullscreenElement === shell;

        if (icon) {
            icon.className = isFullscreen ? 'ti ti-minimize' : 'ti ti-maximize';
        }
        if (button) {
            button.title = isFullscreen ? 'Exit Fullscreen' : 'Fullscreen';
            button.setAttribute('aria-pressed', isFullscreen ? 'true' : 'false');
        }
    };

    OwlPdfReader.prototype.toggleFullscreen = function () {
        var shell = this.getShell();
        if (!shell) return;

        var fullscreenElement = document.fullscreenElement
            || document.webkitFullscreenElement
            || null;
        var fullscreenAction;
        var actionResult;

        if (!fullscreenElement) {
            fullscreenAction = shell.requestFullscreen
                || shell.webkitRequestFullscreen
                || shell.mozRequestFullScreen;

            if (fullscreenAction) {
                actionResult = fullscreenAction.call(shell);
            }
        } else {
            fullscreenAction = document.exitFullscreen
                || document.webkitExitFullscreen
                || document.mozCancelFullScreen;

            if (fullscreenAction) {
                actionResult = fullscreenAction.call(document);
            }
        }

        if (actionResult && typeof actionResult.catch === 'function') {
            actionResult.catch(function () {});
        }
    };

    OwlPdfReader.prototype.bindControls = function () {
        var reader = this;
        var shell = this.getShell();
        var viewerElement = this.getViewerElement();

        if (!shell || shell.dataset.owlPdfReaderBound === 'true') return;

        shell.dataset.owlPdfReaderBound = 'true';

        document.addEventListener('fullscreenchange', function () {
            reader.syncFullscreenButton();
        });
        document.addEventListener('webkitfullscreenchange', function () {
            reader.syncFullscreenButton();
        });

        if (typeof window.matchMedia === 'function') {
            var mobileQuery = window.matchMedia('(max-width: 575.98px)');
            var enforceMobileMode = function (event) {
                if (
                    event.matches
                    && shell.classList.contains('owl-pdf-mode-landscape')
                ) {
                    reader.setReadingMode('portrait');
                }
            };

            if (typeof mobileQuery.addEventListener === 'function') {
                mobileQuery.addEventListener('change', enforceMobileMode);
            } else if (typeof mobileQuery.addListener === 'function') {
                mobileQuery.addListener(enforceMobileMode);
            }
        }

        if (viewerElement) {
            viewerElement.addEventListener('wheel', function (event) {
                if (!event.ctrlKey || !reader.viewer) return;

                event.preventDefault();
                reader.viewer.setZoom(event.deltaY < 0 ? 'in' : 'out');
            }, { passive: false });

            viewerElement.addEventListener('dblclick', function () {
                if (reader.viewer) {
                    reader.viewer.setZoom('width');
                }
            });
        }

        document.addEventListener('keydown', function (event) {
            if (!reader.viewer) return;

            var tag = (event.target.tagName || '').toLowerCase();
            if (tag === 'input' || tag === 'textarea' || event.target.isContentEditable) {
                return;
            }

            if (event.ctrlKey) {
                switch (event.key) {
                    case '+':
                    case '=':
                        event.preventDefault();
                        reader.viewer.setZoom('in');
                        break;
                    case '-':
                        event.preventDefault();
                        reader.viewer.setZoom('out');
                        break;
                    case '0':
                        event.preventDefault();
                        reader.viewer.setZoom('fit');
                        break;
                }
                return;
            }

            switch (event.key) {
                case 'PageDown':
                case 'ArrowRight':
                    event.preventDefault();
                    reader.viewer.next();
                    break;
                case 'PageUp':
                case 'ArrowLeft':
                    event.preventDefault();
                    reader.viewer.prev();
                    break;
                case 'Home':
                    event.preventDefault();
                    reader.viewer.first();
                    break;
                case 'End':
                    event.preventDefault();
                    reader.viewer.last();
                    break;
                case ' ':
                    event.preventDefault();
                    if (event.shiftKey) {
                        reader.viewer.prev();
                    } else {
                        reader.viewer.next();
                    }
                    break;
                case 'f':
                case 'F':
                    event.preventDefault();
                    reader.viewer.setZoom('width');
                    break;
            }
        });
    };

    window.OwlPdfReader = OwlPdfReader;
}(window, document));
