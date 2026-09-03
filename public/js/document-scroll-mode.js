/**
 * OWL LMS - Document Scroll Mode (Continuous Vertical Reader)
 * Isolated controller for self-paced PDF reading with continuous vertical scrolling.
 */
(function (global) {
    'use strict';
    var window = global;
    var document = global.document || {
        getElementById: function () { return null; },
        querySelector: function () { return null; },
        querySelectorAll: function () { return []; },
        addEventListener: function () {}
    };

    function DocumentScrollMode(config) {
        this.config = Object.assign({
            shellId: 'pdfviewer-shell',
            viewerSelector: '.content-pdfjs-viewer',
            downloadButtonId: 'pdf-download-btn',
            fullscreenButtonId: 'pdf-fs-btn',
            fullscreenIconId: 'pdf-fs-icon',
            readingModeButtonId: 'pdf-reading-mode-btn',
            readingModeIconId: 'pdf-reading-mode-icon',
            readingModeLabelId: 'pdf-reading-mode-label',
            previousButtonId: 'pdf-previous-page',
            nextButtonId: 'pdf-next-page',
            completedPages: 0,
            contentCompleted: false,
            totalPages: 0,
            onPageProgress: null
        }, config || {});

        this.viewer = null;
        this.pdfUrl = '';
        this.rotation = 0;
        this.documentReady = false;
        this.totalPages = Math.max(0, parseInt(this.config.totalPages, 10) || 0);
        this.currentPage = Math.max(1, parseInt(this.config.completedPages, 10) || 1);
        this.maxPagesRead = Math.max(1, parseInt(this.config.completedPages, 10) || 1);
        this.contentCompleted = Boolean(this.config.contentCompleted);
        this.observer = null;
        this.userZoomOverridden = false;
        this.pdfOrientation = null;
        this.scrollDebounceTimer = null;
    }

    DocumentScrollMode.prototype.getShell = function () {
        return document.getElementById(this.config.shellId);
    };

    DocumentScrollMode.prototype.getViewerElement = function () {
        return document.querySelector(this.config.viewerSelector);
    };

    DocumentScrollMode.prototype.getViewerOptions = function (callbacks) {
        var self = this;
        var handlers = callbacks || {};

        return {
            extraPagesToLoad: 2,

            onNewPage: function (page, pageno) {
                var pageElement = page.get(0);
                if (pageElement) {
                    pageElement.classList.add('owl-pdf-scroll-page');
                    if (Number(pageno) === 1) {
                        pageElement.classList.add('owl-pdf-page-active');
                    }
                    if (self.observer) {
                        self.observer.observe(pageElement);
                    }
                }
            },

            onDocumentReady: function () {
                var viewerInstance = this;
                var pageCount = viewerInstance.getPageCount();
                if (pageCount > 0) {
                    self.totalPages = pageCount;
                }

                if (self.contentCompleted && self.totalPages > 0) {
                    self.maxPagesRead = self.totalPages;
                }

                self.initIntersectionObserver();
                self.detectFirstPageOrientation(viewerInstance);
                self.scheduleAutoFit(true);
                self.documentReady = true;

                if (typeof handlers.onDocumentReady === 'function') {
                    handlers.onDocumentReady.call(viewerInstance);
                }

                self.updatePageLabel(self.currentPage, self.totalPages);
                self.updateNavigationButtons(self.currentPage);
            },

            onZoomChange: function (zoom) {
                self.updateZoomLabel(zoom);
                if (typeof handlers.onZoomChange === 'function') {
                    handlers.onZoomChange.call(this, zoom);
                }
            },

            onActivePageChanged: function (page, pageno) {
                var pageNum = parseInt(pageno, 10) || 1;
                var total = this.getPageCount() || self.totalPages;
                self.currentPage = pageNum;
                self.updatePageLabel(pageNum, total);
                self.updateNavigationButtons(pageNum);
                self.markPageRead(pageNum, total);

                if (typeof handlers.onActivePageChanged === 'function') {
                    handlers.onActivePageChanged.call(this, page, pageno);
                }
            }
        };
    };

    DocumentScrollMode.prototype.initIntersectionObserver = function () {
        var self = this;
        var container = this.getViewerElement();
        if (!container || typeof IntersectionObserver === 'undefined') return;

        if (this.observer) {
            this.observer.disconnect();
        }

        var visibleMap = new Map();

        this.observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                var pageNum = parseInt(entry.target.dataset.page, 10);
                if (!pageNum) return;

                if (entry.isIntersecting && entry.intersectionRatio >= 0.25) {
                    visibleMap.set(pageNum, {
                        ratio: entry.intersectionRatio,
                        target: entry.target
                    });
                    self.markPageRead(pageNum, self.totalPages);
                } else if (!entry.isIntersecting || entry.intersectionRatio < 0.1) {
                    visibleMap.delete(pageNum);
                }
            });

            // Determine most visible page for the top label
            if (visibleMap.size > 0) {
                var bestPage = null;
                var bestRatio = -1;
                visibleMap.forEach(function (data, p) {
                    var ratio = typeof data === 'object' && data !== null ? data.ratio : data;
                    if (ratio > bestRatio) {
                        bestRatio = ratio;
                        bestPage = p;
                    }
                });

                // Stability check: if current page is still visible with ratio close to bestRatio,
                // keep current page to avoid flickering between adjacent pages.
                if (self.currentPage && visibleMap.has(self.currentPage)) {
                    var currentItem = visibleMap.get(self.currentPage);
                    var currentRatio = typeof currentItem === 'object' && currentItem !== null ? currentItem.ratio : currentItem;
                    if (bestRatio - currentRatio < 0.08) {
                        bestPage = self.currentPage;
                    }
                }

                if (bestPage && bestPage !== self.currentPage) {
                    self.currentPage = bestPage;
                    self.updatePageLabel(bestPage, self.totalPages);
                    self.updateNavigationButtons(bestPage);
                    self.highlightActivePage(bestPage);
                }
            }
        }, {
            root: container,
            threshold: [0.1, 0.25, 0.5, 0.75]
        });

        // Observe all existing page elements
        container.querySelectorAll('.pdfpage').forEach(function (el) {
            self.observer.observe(el);
        });
    };

    DocumentScrollMode.prototype.highlightActivePage = function (pageNum) {
        var container = this.getViewerElement();
        if (!container) return;

        container.querySelectorAll('.pdfpage').forEach(function (el) {
            var isCurrent = parseInt(el.dataset.page, 10) === Number(pageNum);
            el.classList.toggle('owl-pdf-page-active', isCurrent);
        });
    };

    DocumentScrollMode.prototype.markPageRead = function (pageNum, totalPages) {
        pageNum = Math.max(1, parseInt(pageNum, 10) || 1);
        totalPages = Math.max(1, parseInt(totalPages || this.totalPages, 10) || 1);

        if (pageNum > this.maxPagesRead) {
            this.maxPagesRead = Math.min(pageNum, totalPages);
        }

        if (typeof this.config.onPageProgress === 'function') {
            this.config.onPageProgress(this.maxPagesRead, totalPages);
        }
    };

    DocumentScrollMode.prototype.attach = function (viewer, pdfUrl) {
        this.viewer = viewer;
        this.pdfUrl = pdfUrl || '';
        this.userZoomOverridden = false;

        var shell = this.getShell();
        if (shell) {
            shell.classList.remove('owl-pdf-single-page');
            shell.classList.add('owl-pdf-scroll-mode');
            shell.dataset.readingMode = 'scroll';

            // In scroll mode, hide Previous/Next buttons and countdown timer
            var navButtons = shell.querySelectorAll(
                '[data-pdf-nav], .pdf-page-navigation-btn, .pdf-page-navigation-previous, .pdf-page-navigation-next, .pdf-tb-reading-timing'
            );
            navButtons.forEach(function (el) {
                el.style.display = 'none';
            });

            // Ensure navigation bar is flex-centered and visible
            var nav = shell.querySelector('.pdf-page-navigation');
            if (nav) {
                nav.classList.add('is-scroll-mode');
                nav.style.display = 'flex';
            }
        }

        this.bindControls();
        this.updateDownloadLink();

        if (this.totalPages > 0) {
            this.updatePageLabel(this.currentPage, this.totalPages);
        }

        return this;
    };

    DocumentScrollMode.prototype.detectFirstPageOrientation = function (viewer) {
        viewer = viewer || this.viewer;
        if (!viewer) return 'portrait';

        if (this.pdfOrientation) {
            return this.pdfOrientation;
        }

        var orientation = 'portrait';

        try {
            if (viewer.pages && viewer.pages[1]) {
                var p1 = viewer.pages[1];
                var w = parseFloat(p1.width) || 0;
                var h = parseFloat(p1.height) || 0;
                if (w > 0 && h > 0) {
                    orientation = w > h ? 'landscape' : 'portrait';
                }
            }
        } catch (e) {
            // Fallback
        }

        this.pdfOrientation = orientation;
        this.applyOrientationState(orientation);
        return orientation;
    };

    DocumentScrollMode.prototype.applyOrientationState = function (orientation) {
        var shell = this.getShell();
        if (!shell) return;

        var isLandscape = orientation === 'landscape';
        shell.dataset.pdfOrientation = orientation;
        shell.classList.toggle('is-landscape', isLandscape);
        shell.classList.toggle('is-portrait', !isLandscape);
        shell.classList.toggle('owl-pdf-is-landscape', isLandscape);
        shell.classList.toggle('owl-pdf-is-portrait', !isLandscape);
    };

    DocumentScrollMode.prototype.autoFit = function (force) {
        if (!this.viewer) return;

        if (!force && this.userZoomOverridden) {
            return;
        }

        if (typeof this.viewer.setZoom === 'function') {
            this.viewer.setZoom('width');
        }
    };

    DocumentScrollMode.prototype.scheduleAutoFit = function (force) {
        var self = this;
        self.autoFit(force);

        [50, 150, 360].forEach(function (delay) {
            setTimeout(function () {
                if (self.viewer && (!self.userZoomOverridden || force)) {
                    self.autoFit(force);
                }
            }, delay);
        });
    };

    DocumentScrollMode.prototype.updateZoomLabel = function (zoom) {
        var shell = this.getShell();
        var label = shell ? shell.querySelector('.zoomval') : null;
        if (label) {
            zoom = parseInt(zoom * 10000, 10) / 100;
            label.textContent = zoom + '%';
        }
    };

    DocumentScrollMode.prototype.updatePageLabel = function (page, totalPages) {
        var pageNum = Math.max(1, parseInt(page, 10) || this.currentPage || 1);
        var total = Math.max(0, parseInt(totalPages || this.totalPages || (this.viewer && typeof this.viewer.getPageCount === 'function' ? this.viewer.getPageCount() : 0), 10) || 0);

        if (total > 0) {
            this.totalPages = total;
        }
        this.currentPage = pageNum;

        var shell = this.getShell();
        if (shell) {
            var labels = shell.querySelectorAll('.pageno, .pdf-tb-pageno');
            labels.forEach(function (label) {
                label.textContent = total > 0 ? (pageNum + ' / ' + total) : (pageNum + ' / --');
            });
        }
    };

    DocumentScrollMode.prototype.updateNavigationButtons = function (page) {
        var shell = this.getShell();
        if (shell) {
            var navElements = shell.querySelectorAll('[data-pdf-nav], .pdf-page-navigation-btn, .pdf-page-navigation-previous, .pdf-page-navigation-next');
            navElements.forEach(function (el) {
                el.style.display = 'none';
            });
        }
    };

    DocumentScrollMode.prototype.scrollToPage = function (pageNum) {
        if (!this.viewer) return;
        var total = this.totalPages || (typeof this.viewer.getPageCount === 'function' ? this.viewer.getPageCount() : 0);
        pageNum = Math.max(1, Math.min(pageNum, total || 1));

        this.currentPage = pageNum;
        if (typeof this.viewer.scrollToPage === 'function') {
            this.viewer.scrollToPage(pageNum);
        }
        this.highlightActivePage(pageNum);
        this.updatePageLabel(pageNum, total);
        this.updateNavigationButtons(pageNum);
        this.markPageRead(pageNum, total);
    };

    DocumentScrollMode.prototype.next = function () {
        var total = this.totalPages || (this.viewer ? this.viewer.getPageCount() : 0);
        if (this.currentPage < total) {
            this.scrollToPage(this.currentPage + 1);
        }
    };

    DocumentScrollMode.prototype.prev = function () {
        if (this.currentPage > 1) {
            this.scrollToPage(this.currentPage - 1);
        }
    };

    DocumentScrollMode.prototype.updateDownloadLink = function () {
        var button = document.getElementById(this.config.downloadButtonId);
        if (button && this.pdfUrl) {
            button.href = this.pdfUrl;
        }
    };

    DocumentScrollMode.prototype.rotate = function (delta) {
        if (!this.viewer || !this.viewer.getPageCount()) return Promise.resolve();

        this.rotation = (this.rotation + delta + 360) % 360;

        if (typeof this.viewer.setRotation === 'function') {
            return Promise.resolve(this.viewer.setRotation(this.rotation));
        } else if (typeof this.viewer.rotate === 'function') {
            return Promise.resolve(this.viewer.rotate(this.rotation));
        }
        return Promise.resolve();
    };

    DocumentScrollMode.prototype.toggleFullscreen = function () {
        var shell = this.getShell();
        if (!shell) return;

        var fullscreenElement = document.fullscreenElement || document.webkitFullscreenElement || null;
        if (!fullscreenElement) {
            var req = shell.requestFullscreen || shell.webkitRequestFullscreen || shell.mozRequestFullScreen;
            if (req) req.call(shell);
        } else {
            var exit = document.exitFullscreen || document.webkitExitFullscreen || document.mozCancelFullScreen;
            if (exit) exit.call(document);
        }
    };

    DocumentScrollMode.prototype.print = function () {
        if (this.pdfUrl) {
            window.open(this.pdfUrl, '_blank', 'noopener');
        }
    };

    DocumentScrollMode.prototype.bindControls = function () {
        var self = this;
        var shell = this.getShell();
        var viewerElement = this.getViewerElement();

        if (!shell || shell.dataset.documentScrollModeBound === 'true') return;
        shell.dataset.documentScrollModeBound = 'true';

        // Override Previous & Next button clicks for jump-scrolling in Scroll Mode
        var prevBtn = shell.querySelector('[data-pdf-nav="previous"]') || document.getElementById(this.config.previousButtonId);
        var nextBtn = shell.querySelector('[data-pdf-nav="next"]') || document.getElementById(this.config.nextButtonId);

        if (prevBtn) {
            prevBtn.onclick = function (e) {
                e.preventDefault();
                self.prev();
                return false;
            };
        }

        if (nextBtn) {
            nextBtn.onclick = function (e) {
                e.preventDefault();
                self.next();
                return false;
            };
        }

        window.addEventListener('resize', function () {
            if (!self.userZoomOverridden) {
                self.autoFit();
            }
        });

        if (viewerElement) {
            viewerElement.addEventListener('wheel', function (event) {
                if (!event.ctrlKey || !self.viewer) return;
                event.preventDefault();
                self.userZoomOverridden = true;
                self.viewer.setZoom(event.deltaY < 0 ? 'in' : 'out');
            }, { passive: false });
        }

        document.addEventListener('keydown', function (event) {
            if (!self.viewer) return;
            var tag = (event.target.tagName || '').toLowerCase();
            if (tag === 'input' || tag === 'textarea' || event.target.isContentEditable) return;

            if (event.key === 'PageDown' || event.key === 'ArrowRight') {
                event.preventDefault();
                self.next();
            } else if (event.key === 'PageUp' || event.key === 'ArrowLeft') {
                event.preventDefault();
                self.prev();
            }
        });
    };

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = DocumentScrollMode;
    }
    if (typeof window !== 'undefined') {
        window.DocumentScrollMode = DocumentScrollMode;
    }
})(typeof window !== 'undefined' ? window : (typeof globalThis !== 'undefined' ? globalThis : this));
