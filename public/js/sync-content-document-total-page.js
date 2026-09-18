(function (window, document, $) {
    'use strict';

    var DEFAULT_TOTAL_PAGE = 35;

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function SyncContentDocumentTotalPage(options) {
        this.options = Object.assign({
            buttonId: 'btnSyncPdfTotalPage',
            modalId: 'modalTotalPageSynchronization',
            executeBtnId: 'btnExecuteSyncTotalPage',
            cancelBtnId: 'btnCancelSyncTotalPage',
            closeBtnId: 'btnCloseSyncTotalPage',
            headerCloseBtnId: 'syncTotalPageCloseHeaderBtn',
            fetchListUrl: '',
            syncUrl: '',
            csrfToken: '',
            workerSrc: ''
        }, options || {});

        this.button = document.getElementById(this.options.buttonId);
        this.modalEl = document.getElementById(this.options.modalId);
        this.executeBtn = document.getElementById(this.options.executeBtnId);
        this.cancelBtn = document.getElementById(this.options.cancelBtnId);
        this.closeBtn = document.getElementById(this.options.closeBtnId);
        this.headerCloseBtn = document.getElementById(this.options.headerCloseBtnId);

        this.promptBody = document.getElementById('syncTotalPagePromptBody');
        this.loadingBody = document.getElementById('syncTotalPageLoadingBody');
        this.resultBody = document.getElementById('syncTotalPageResultBody');
        this.errorContainer = document.getElementById('syncTotalPageErrorContainer');
        this.errorText = document.getElementById('syncTotalPageErrorText');

        this.progressBar = document.getElementById('syncTotalPageProgressBar');
        this.progressCounter = document.getElementById('syncTotalPageProgressCounter');
        this.loadingTitle = document.getElementById('syncTotalPageLoadingTitle');
        this.loadingText = document.getElementById('syncTotalPageLoadingText');

        this.resTotalDocuments = document.getElementById('resTotalDocuments');
        this.resUpdatedPdf = document.getElementById('resUpdatedPdf');
        this.resAlreadySynced = document.getElementById('resAlreadySynced');
        this.resMissingDefaulted = document.getElementById('resMissingDefaulted');

        this.missingContainer = document.getElementById('syncTotalPageMissingContainer');
        this.missingHeader = document.getElementById('syncTotalPageMissingHeader');
        this.missingListEl = document.getElementById('syncTotalPageMissingList');
        this.missingMoreNote = document.getElementById('syncTotalPageMissingMoreNote');
        this.btnToggleMissing = document.getElementById('btnToggleMissingDetails');
        this.toggleMissingText = document.getElementById('toggleMissingDetailsText');
        this.toggleMissingIcon = document.getElementById('toggleMissingDetailsIcon');
        this.collapseMissing = document.getElementById('collapseMissingDetails');

        this.isRunning = false;
        this.hasCompletedSync = false;
        this.hasChanges = false;

        if (!this.options.fetchListUrl || !this.options.syncUrl) {
            return;
        }

        this.initWorker();
        this.bind();
    }

    SyncContentDocumentTotalPage.prototype.initWorker = function () {
        var pdfApi = window.pdfjsLib || window['pdfjs-dist/build/pdf'];
        if (pdfApi && this.options.workerSrc && !pdfApi.GlobalWorkerOptions.workerSrc) {
            pdfApi.GlobalWorkerOptions.workerSrc = this.options.workerSrc;
        }
    };

    SyncContentDocumentTotalPage.prototype.bind = function () {
        var self = this;

        // Trigger button clicks
        if (this.button) {
            this.button.addEventListener('click', function (e) {
                // If button doesn't have bootstrap modal data attributes, show via JS API
                if (!self.button.getAttribute('data-bs-target') && self.modalEl && window.bootstrap && window.bootstrap.Modal) {
                    var modal = window.bootstrap.Modal.getOrCreateInstance(self.modalEl);
                    modal.show();
                }
            });
        }

        // Execute button inside modal
        if (this.executeBtn) {
            this.executeBtn.addEventListener('click', function (e) {
                e.preventDefault();
                if (self.isRunning) return;
                self.startSync();
            });
        }

        // Modal Lifecycle events
        if (this.modalEl) {
            this.modalEl.addEventListener('show.bs.modal', function () {
                if (!self.isRunning) {
                    self.resetModalState();
                }
            });

            this.modalEl.addEventListener('hidden.bs.modal', function () {
                if (self.hasCompletedSync && self.hasChanges) {
                    window.location.reload();
                }
            });
        }

        // Close button inside modal footer
        if (this.closeBtn) {
            this.closeBtn.addEventListener('click', function () {
                if (self.hasCompletedSync && self.hasChanges) {
                    window.location.reload();
                }
            });
        }

        // Missing Items Collapse Toggle text & icon update
        if (this.collapseMissing) {
            this.collapseMissing.addEventListener('show.bs.collapse', function () {
                if (self.toggleMissingText) self.toggleMissingText.textContent = 'Hide Details';
                if (self.toggleMissingIcon) self.toggleMissingIcon.className = 'ti ti-chevron-up ms-1';
            });
            this.collapseMissing.addEventListener('hide.bs.collapse', function () {
                if (self.toggleMissingText) self.toggleMissingText.textContent = 'View Details';
                if (self.toggleMissingIcon) self.toggleMissingIcon.className = 'ti ti-chevron-down ms-1';
            });
        }
    };

    SyncContentDocumentTotalPage.prototype.resetModalState = function () {
        this.isRunning = false;
        this.hasCompletedSync = false;
        this.hasChanges = false;

        if (this.promptBody) this.promptBody.style.display = 'block';
        if (this.loadingBody) this.loadingBody.style.display = 'none';
        if (this.resultBody) this.resultBody.style.display = 'none';
        if (this.errorContainer) this.errorContainer.style.display = 'none';
        if (this.missingContainer) this.missingContainer.style.display = 'none';

        if (this.executeBtn) {
            this.executeBtn.style.display = 'inline-flex';
            this.executeBtn.disabled = false;
            this.executeBtn.innerHTML = '<i class="ti ti-refresh me-1"></i> <span>Synchronize Total Pages</span>';
        }
        if (this.cancelBtn) {
            this.cancelBtn.style.display = 'inline-block';
            this.cancelBtn.disabled = false;
        }
        if (this.closeBtn) {
            this.closeBtn.style.display = 'none';
        }
        if (this.headerCloseBtn) {
            this.headerCloseBtn.style.display = 'block';
        }

        if (this.progressBar) {
            this.progressBar.style.width = '0%';
            this.progressBar.setAttribute('aria-valuenow', '0');
        }
        if (this.progressCounter) {
            this.progressCounter.textContent = 'Preparing...';
        }
        if (this.missingListEl) {
            this.missingListEl.innerHTML = '';
        }
        if (this.missingMoreNote) {
            this.missingMoreNote.style.display = 'none';
        }
        if (this.toggleMissingText) {
            this.toggleMissingText.textContent = 'View Details';
        }
        if (this.toggleMissingIcon) {
            this.toggleMissingIcon.className = 'ti ti-chevron-down ms-1';
        }
        if (this.collapseMissing && window.bootstrap && window.bootstrap.Collapse) {
            var bsCollapse = window.bootstrap.Collapse.getInstance(this.collapseMissing);
            if (bsCollapse) {
                bsCollapse.hide();
            }
        }
    };

    SyncContentDocumentTotalPage.prototype.startSync = function () {
        var self = this;
        var pdfApi = window.pdfjsLib || window['pdfjs-dist/build/pdf'];

        if (!pdfApi) {
            this.showError('PDF.js library is not available. Please refresh the page and try again.');
            return;
        }

        this.isRunning = true;
        this.hasCompletedSync = false;
        this.hasChanges = false;

        // Set Loading State in Modal
        if (this.promptBody) this.promptBody.style.display = 'none';
        if (this.resultBody) this.resultBody.style.display = 'none';
        if (this.errorContainer) this.errorContainer.style.display = 'none';
        if (this.loadingBody) this.loadingBody.style.display = 'block';

        if (this.executeBtn) {
            this.executeBtn.disabled = true;
            this.executeBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Synchronizing...';
        }
        if (this.cancelBtn) this.cancelBtn.style.display = 'none';
        if (this.headerCloseBtn) this.headerCloseBtn.style.display = 'none';

        if (this.progressBar) {
            this.progressBar.style.width = '0%';
            this.progressBar.setAttribute('aria-valuenow', '0');
        }
        if (this.progressCounter) {
            this.progressCounter.textContent = 'Loading Document PDF list...';
        }

        $.ajax({
            url: this.options.fetchListUrl,
            type: 'GET',
            dataType: 'json',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.options.csrfToken
            }
        }).done(function (res) {
            if (!res || !res.success || !Array.isArray(res.data)) {
                self.showError('Unable to load document PDF list.');
                return;
            }

            var items = res.data;
            if (items.length === 0) {
                self.showEmptyState();
                return;
            }

            self.processQueue(items);
        }).fail(function (xhr) {
            var msg = self.getErrorMessage(xhr, 'Failed to connect to server.');
            self.showError('Error loading PDF list: ' + msg);
        });
    };

    SyncContentDocumentTotalPage.prototype.processQueue = function (items) {
        var self = this;
        var pdfApi = window.pdfjsLib || window['pdfjs-dist/build/pdf'];
        var total = items.length;
        var updatedFromPdf = 0;
        var alreadySynced = 0;
        var missingDefaulted = 0;
        var missingList = [];
        var index = 0;

        function processNext() {
            if (index >= total) {
                self.finishSync({
                    total: total,
                    updatedFromPdf: updatedFromPdf,
                    alreadySynced: alreadySynced,
                    missingDefaulted: missingDefaulted,
                    missingList: missingList
                });
                return;
            }

            var item = items[index];
            index++;

            var pct = Math.round(((index - 1) / total) * 100);
            if (self.progressBar) {
                self.progressBar.style.width = pct + '%';
                self.progressBar.setAttribute('aria-valuenow', pct);
            }
            if (self.progressCounter) {
                self.progressCounter.textContent = 'Processing ' + index + ' of ' + total + ' (' + pct + '%)';
            }

            function applyFallback(reason) {
                missingDefaulted++;
                missingList.push({ id: item.id, title: item.title, reason: reason });

                if (parseInt(item.total_page, 10) === DEFAULT_TOTAL_PAGE) {
                    processNext();
                    return;
                }

                $.ajax({
                    url: self.options.syncUrl,
                    type: 'POST',
                    dataType: 'json',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': self.options.csrfToken
                    },
                    data: {
                        _token: self.options.csrfToken,
                        id: item.id,
                        total_page: DEFAULT_TOTAL_PAGE
                    }
                }).done(function (syncRes) {
                    if (syncRes && syncRes.success && syncRes.updated) {
                        updatedFromPdf++;
                    }
                    processNext();
                }).fail(function () {
                    processNext();
                });
            }

            if (!item.file_url || !item.file_exists) {
                applyFallback('PDF file missing or invalid URL → total_page set to ' + DEFAULT_TOTAL_PAGE);
                return;
            }

            var loadingTask = pdfApi.getDocument({ url: item.file_url, withCredentials: false });
            loadingTask.promise.then(function (pdf) {
                var numPages = parseInt(pdf.numPages, 10) || 0;

                if (numPages <= 0) {
                    applyFallback('Unreadable page count (0) → total_page set to ' + DEFAULT_TOTAL_PAGE);
                    return;
                }

                if (numPages === parseInt(item.total_page, 10)) {
                    alreadySynced++;
                    processNext();
                    return;
                }

                // Total page differs, update backend with actual numPages
                $.ajax({
                    url: self.options.syncUrl,
                    type: 'POST',
                    dataType: 'json',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': self.options.csrfToken
                    },
                    data: {
                        _token: self.options.csrfToken,
                        id: item.id,
                        total_page: numPages
                    }
                }).done(function (syncRes) {
                    if (syncRes && syncRes.success && syncRes.updated) {
                        updatedFromPdf++;
                    } else if (syncRes && syncRes.success && !syncRes.updated) {
                        alreadySynced++;
                    }
                    processNext();
                }).fail(function () {
                    applyFallback('Server update error → total_page set to ' + DEFAULT_TOTAL_PAGE);
                });
            }).catch(function (error) {
                var errorMsg = error && error.message ? error.message : 'Unable to read PDF file';
                applyFallback(errorMsg + ' → total_page set to ' + DEFAULT_TOTAL_PAGE);
            });
        }

        processNext();
    };

    SyncContentDocumentTotalPage.prototype.finishSync = function (stats) {
        var self = this;
        this.isRunning = false;
        this.hasCompletedSync = true;
        this.hasChanges = (stats.updatedFromPdf > 0 || stats.missingDefaulted > 0);

        if (this.progressBar) {
            this.progressBar.style.width = '100%';
            this.progressBar.setAttribute('aria-valuenow', '100');
        }
        if (this.progressCounter) {
            this.progressCounter.textContent = 'Complete (100%)';
        }

        if (this.loadingBody) this.loadingBody.style.display = 'none';
        if (this.promptBody) this.promptBody.style.display = 'none';
        if (this.errorContainer) this.errorContainer.style.display = 'none';
        if (this.resultBody) this.resultBody.style.display = 'block';

        // Summary cards
        if (this.resTotalDocuments) this.resTotalDocuments.textContent = Number(stats.total).toLocaleString();
        if (this.resUpdatedPdf) this.resUpdatedPdf.textContent = Number(stats.updatedFromPdf).toLocaleString();
        if (this.resAlreadySynced) this.resAlreadySynced.textContent = Number(stats.alreadySynced).toLocaleString();
        if (this.resMissingDefaulted) this.resMissingDefaulted.textContent = Number(stats.missingDefaulted).toLocaleString();

        // Missing / Defaulted details
        if (stats.missingList && stats.missingList.length > 0) {
            if (this.missingContainer) this.missingContainer.style.display = 'block';
            if (this.missingHeader) {
                this.missingHeader.textContent = 'Missing / Defaulted Items (' + stats.missingList.length + ')';
            }
            if (this.missingListEl) {
                this.missingListEl.innerHTML = '';
                var maxDisplay = 20;
                var displayList = stats.missingList.slice(0, maxDisplay);

                displayList.forEach(function (m, idx) {
                    var itemDiv = document.createElement('div');
                    itemDiv.className = 'list-group-item px-2 py-1 border-0 bg-transparent small d-flex justify-content-between align-items-center gap-2';

                    var titleText = m.title || ('Content #' + m.id);
                    var reasonText = m.reason ? m.reason.split('→')[0].trim() : 'Defaulted';

                    itemDiv.innerHTML = '<div class="text-truncate flex-grow-1" style="max-width: 70%;">' +
                        '<span class="text-secondary me-1">' + (idx + 1) + '.</span>' +
                        '<strong class="text-dark">' + escapeHtml(titleText) + '</strong>' +
                        '</div>' +
                        '<span class="badge bg-warning-lt text-warning text-nowrap">' + escapeHtml(reasonText) + '</span>';

                    self.missingListEl.appendChild(itemDiv);
                });

                if (stats.missingList.length > maxDisplay) {
                    if (this.missingMoreNote) {
                        this.missingMoreNote.style.display = 'block';
                        this.missingMoreNote.textContent = '+ ' + (stats.missingList.length - maxDisplay) + ' more items defaulted to ' + DEFAULT_TOTAL_PAGE + '.';
                    }
                } else {
                    if (this.missingMoreNote) this.missingMoreNote.style.display = 'none';
                }
            }
        } else {
            if (this.missingContainer) this.missingContainer.style.display = 'none';
        }

        // Buttons
        if (this.executeBtn) this.executeBtn.style.display = 'none';
        if (this.cancelBtn) this.cancelBtn.style.display = 'none';
        if (this.closeBtn) this.closeBtn.style.display = 'inline-block';
        if (this.headerCloseBtn) this.headerCloseBtn.style.display = 'block';
    };

    SyncContentDocumentTotalPage.prototype.showEmptyState = function () {
        this.isRunning = false;
        this.hasCompletedSync = false;
        this.hasChanges = false;

        if (this.loadingBody) this.loadingBody.style.display = 'none';
        if (this.promptBody) this.promptBody.style.display = 'none';
        if (this.errorContainer) this.errorContainer.style.display = 'none';
        if (this.resultBody) this.resultBody.style.display = 'block';

        if (this.resTotalDocuments) this.resTotalDocuments.textContent = '0';
        if (this.resUpdatedPdf) this.resUpdatedPdf.textContent = '0';
        if (this.resAlreadySynced) this.resAlreadySynced.textContent = '0';
        if (this.resMissingDefaulted) this.resMissingDefaulted.textContent = '0';
        if (this.missingContainer) this.missingContainer.style.display = 'none';

        var msgEl = document.getElementById('syncTotalPageResultMessage');
        if (msgEl) msgEl.textContent = 'No Document PDF content found to synchronize.';

        if (this.executeBtn) this.executeBtn.style.display = 'none';
        if (this.cancelBtn) this.cancelBtn.style.display = 'none';
        if (this.closeBtn) this.closeBtn.style.display = 'inline-block';
        if (this.headerCloseBtn) this.headerCloseBtn.style.display = 'block';
    };

    SyncContentDocumentTotalPage.prototype.showError = function (message) {
        this.isRunning = false;

        if (this.loadingBody) this.loadingBody.style.display = 'none';
        if (this.promptBody) this.promptBody.style.display = 'block';
        if (this.resultBody) this.resultBody.style.display = 'none';
        if (this.errorContainer) {
            this.errorContainer.style.display = 'block';
            if (this.errorText) this.errorText.textContent = message;
        }

        if (this.executeBtn) {
            this.executeBtn.disabled = false;
            this.executeBtn.style.display = 'inline-flex';
            this.executeBtn.innerHTML = '<i class="ti ti-refresh me-1"></i> <span>Synchronize Total Pages</span>';
        }
        if (this.cancelBtn) {
            this.cancelBtn.style.display = 'inline-block';
            this.cancelBtn.disabled = false;
        }
        if (this.closeBtn) this.closeBtn.style.display = 'none';
        if (this.headerCloseBtn) this.headerCloseBtn.style.display = 'block';
    };

    SyncContentDocumentTotalPage.prototype.getErrorMessage = function (xhr, fallbackMsg) {
        if (!xhr) return fallbackMsg;
        if (xhr.status === 403) return 'You do not have permission to synchronize total pages.';
        if (xhr.status === 419) return 'Session expired. Please refresh the page and try again.';
        if (xhr.status === 429) return 'Too many requests. Please wait a moment and try again.';
        if (xhr.status === 500) return 'Server error occurred. Please try again later.';
        if (xhr.status === 0) return 'Network error. Please check your connection.';
        if (xhr.responseJSON && xhr.responseJSON.message) return xhr.responseJSON.message;
        return fallbackMsg;
    };

    window.SyncContentDocumentTotalPage = SyncContentDocumentTotalPage;
})(window, document, jQuery);
