(function (window, document, $) {
    'use strict';

    var DEFAULT_TOTAL_PAGE = 35;

    function SyncContentDocumentTotalPage(options) {
        this.options = Object.assign({
            buttonId: 'btnSyncPdfTotalPage',
            fetchListUrl: '',
            syncUrl: '',
            csrfToken: '',
            workerSrc: ''
        }, options || {});

        this.button = document.getElementById(this.options.buttonId);
        this.isRunning = false;

        if (!this.button || !this.options.fetchListUrl || !this.options.syncUrl) {
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
        this.button.addEventListener('click', function (e) {
            e.preventDefault();
            if (self.isRunning) return;
            self.startSync();
        });
    };

    SyncContentDocumentTotalPage.prototype.startSync = function () {
        var self = this;
        var pdfApi = window.pdfjsLib || window['pdfjs-dist/build/pdf'];

        if (!pdfApi) {
            alert('PDF.js library is not available.');
            return;
        }

        this.isRunning = true;
        this.setButtonState(true, 'Initializing...');

        $.ajax({
            url: this.options.fetchListUrl,
            type: 'GET',
            dataType: 'json'
        }).done(function (res) {
            if (!res || !res.success || !Array.isArray(res.data)) {
                alert('Unable to load document PDF list.');
                self.resetButtonState();
                return;
            }

            var items = res.data;
            if (items.length === 0) {
                alert('No Document PDF content found to synchronize.');
                self.resetButtonState();
                return;
            }

            self.processQueue(items);
        }).fail(function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message)
                ? xhr.responseJSON.message
                : 'Failed to connect to server.';
            alert('Error loading PDF list: ' + msg);
            self.resetButtonState();
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

            self.setButtonState(true, 'Syncing... (' + index + '/' + total + ')');

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

    SyncContentDocumentTotalPage.prototype.setButtonState = function (disabled, text) {
        this.button.disabled = Boolean(disabled);
        if (disabled) {
            this.button.innerHTML = '<i class="ti ti-loader-2 ti-spin me-1"></i>' + text;
        }
    };

    SyncContentDocumentTotalPage.prototype.resetButtonState = function () {
        this.isRunning = false;
        this.button.disabled = false;
        this.button.innerHTML = '<i class="ti ti-file-description me-1"></i>Sync Total Page PDF';
    };

    SyncContentDocumentTotalPage.prototype.finishSync = function (stats) {
        this.resetButtonState();

        var message = '========================================\n' +
            'TOTAL PAGE SYNCHRONIZATION COMPLETE\n' +
            '========================================\n\n' +
            'Total Documents: ' + stats.total + '\n' +
            'Updated from PDF: ' + stats.updatedFromPdf + '\n' +
            'Already Synchronized: ' + stats.alreadySynced + '\n' +
            'Missing / Defaulted to ' + DEFAULT_TOTAL_PAGE + ': ' + stats.missingDefaulted;

        if (stats.missingList.length > 0) {
            message += '\n\nMissing / Defaulted Items (' + stats.missingList.length + '):\n';
            stats.missingList.slice(0, 5).forEach(function (m) {
                message += '- ' + m.title + ' (' + m.reason + ')\n';
            });
            if (stats.missingList.length > 5) {
                message += '... and ' + (stats.missingList.length - 5) + ' more items defaulted to ' + DEFAULT_TOTAL_PAGE + '.';
            }
        }

        alert(message);

        if (stats.updatedFromPdf > 0 || stats.missingDefaulted > 0) {
            window.location.reload();
        }
    };

    window.SyncContentDocumentTotalPage = SyncContentDocumentTotalPage;
})(window, document, jQuery);
