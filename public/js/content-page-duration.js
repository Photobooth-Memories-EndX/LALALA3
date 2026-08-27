(function (window, document) {
    'use strict';

    function OwlDocumentDurationForm(options) {
        this.options = options || {};
        this.form = document.getElementById(this.options.formId);
        this.typeInput = document.getElementById(this.options.typeInputId || 'type');
        this.row = document.getElementById('document-duration-fields') || document.getElementById('document-page-duration-row') || document.getElementById('metadata-section-document');
        this.requireDurationInput = document.getElementById('require_page_duration');
        this.scrollModeNotice = document.getElementById('document-scroll-mode-notice');
        this.pageDurationHint = document.getElementById('page-duration-hint');
        this.pageDurationInput = document.getElementById('page_duration');
        this.totalPagesInput = document.getElementById('document_total_pages') || document.getElementById('total_page');
        this.durationInput = document.getElementById('doc_duration') || document.getElementById(this.options.durationInputId || 'duration');
        this.durationLabel = document.getElementById('edit-duration-estimate') || document.getElementById('document-calculated-duration');
        this.totalPageEstimate = document.getElementById('edit-total-page-estimate');
        this.pageDurationEstimate = document.getElementById('edit-page-duration-estimate');
        this.synchronizeButton = document.getElementById('edit-duration-synchronize');
        this.synchronizationStatus = document.getElementById('edit-duration-sync-status');
        this.synchronizationStatusIcon = document.getElementById('edit-duration-sync-status-icon');
        this.synchronizationStatusText = document.getElementById('edit-duration-sync-status-text');
        this.statusLabel = document.getElementById('edit-document-page-status') || document.getElementById('document-page-count-status');
        this.existingPdfUrl = this.options.existingPdfUrl || null;
        this.expectsPdf = Boolean(this.existingPdfUrl);
        this._countRequest = null;

        if (!this.form || !this.typeInput || !this.row || !this.pageDurationInput) {
            return;
        }

        this.bind();
        this.syncType();
        this.syncRequireDuration();

        if (this.isDocument() && this.existingPdfUrl) {
            this.loadExistingPdf();
        } else {
            this.recalculate();
        }
    }

    OwlDocumentDurationForm.prototype.isDocument = function () {
        return this.typeInput && this.typeInput.value === 'Document';
    };

    OwlDocumentDurationForm.prototype.isDurationRequired = function () {
        return !this.requireDurationInput || this.requireDurationInput.checked;
    };

    OwlDocumentDurationForm.prototype.bind = function () {
        var self = this;

        this.typeInput.addEventListener('change', function () {
            self.syncType();

            if (self.isDocument() && self.existingPdfUrl && !self.getTotalPages()) {
                self.loadExistingPdf();
            }
        });

        if (this.requireDurationInput) {
            this.requireDurationInput.addEventListener('change', function () {
                self.syncRequireDuration();
                self.recalculate();
            });
        }

        if (this.totalPagesInput) {
            this.totalPagesInput.addEventListener('input', function () {
                self.recalculate();
            });
        }

        if (this.pageDurationInput) {
            this.pageDurationInput.addEventListener('input', function () {
                if (self.isDurationRequired() && parseInt(self.pageDurationInput.value, 10) === 0) {
                    self.setStatus('Page Duration must be at least 1 second when duration is enabled.', true);
                } else {
                    self.setStatus('', false);
                }
                self.recalculate();
            });
        }

        if (this.durationInput) {
            this.durationInput.addEventListener('input', function () {
                self.updateSynchronizationState();
            });
        }

        if (this.synchronizeButton) {
            this.synchronizeButton.addEventListener('click', function () {
                var calculated = self.getCalculatedDuration();
                if (self.durationLabel) {
                    self.durationLabel.textContent = self.isDurationRequired() ? calculated : 'Self-paced';
                }
                if (self.totalPageEstimate) {
                    self.totalPageEstimate.textContent = self.getTotalPages();
                }
                if (self.pageDurationEstimate) {
                    self.pageDurationEstimate.textContent = self.getPageDuration();
                }
                if (self.durationInput) {
                    self.durationInput.value = calculated;
                }
                self.updateSynchronizationState();
                if (self.durationInput) {
                    self.durationInput.focus();
                }
            });
        }

        this.form.addEventListener('submit', function (event) {
            if (!self.isDocument()) return;

            if (self.isDurationRequired() && self.getPageDuration() < 1) {
                event.preventDefault();
                self.setStatus('Page Duration must be at least 1 second when duration is enabled.', true);
                if (self.pageDurationInput) self.pageDurationInput.focus();
                return;
            }

            if (self._countRequest) {
                event.preventDefault();
                self.setStatus('PDF page count is still loading. Please try again in a moment.', true);
                return;
            }

            if (self.expectsPdf && !self.getTotalPages()) {
                event.preventDefault();
                self.setStatus('The PDF page count is required before this document can be saved.', true);
            }
        });
    };

    OwlDocumentDurationForm.prototype.syncRequireDuration = function () {
        if (!this.isDocument()) return;

        var required = this.isDurationRequired();

        if (this.pageDurationInput) {
            if (required) {
                this.pageDurationInput.readOnly = false;
                this.pageDurationInput.min = '1';
                this.pageDurationInput.style.backgroundColor = '';
                this.pageDurationInput.style.cursor = '';
                var val = parseInt(this.pageDurationInput.value, 10);
                if (!val || val < 1) {
                    this.pageDurationInput.value = 30;
                }
            } else {
                this.pageDurationInput.value = 0;
                this.pageDurationInput.readOnly = true;
                this.pageDurationInput.min = '0';
                this.pageDurationInput.style.backgroundColor = 'var(--tblr-gray-200, #e9ecef)';
                this.pageDurationInput.style.cursor = 'not-allowed';
            }
        }

        if (this.scrollModeNotice) {
            this.scrollModeNotice.style.display = required ? 'none' : '';
        }
        if (this.pageDurationHint) {
            this.pageDurationHint.style.display = required ? '' : 'none';
        }
    };

    OwlDocumentDurationForm.prototype.syncType = function () {
        var documentSelected = this.isDocument();

        if (this.row) this.row.style.display = documentSelected ? '' : 'none';
        if (this.synchronizeButton) this.synchronizeButton.hidden = !documentSelected;
        if (this.pageDurationInput) this.pageDurationInput.required = documentSelected;

        if (this.durationInput) {
            this.durationInput.readOnly = documentSelected;

            if (documentSelected) {
                this.durationInput.setAttribute('aria-readonly', 'true');
                this.durationInput.setAttribute('aria-describedby', 'edit-document-page-status');
                this.durationInput.style.backgroundColor = 'var(--tblr-gray-200, #e9ecef)';
                this.durationInput.style.borderColor = 'var(--tblr-border-color, #ced4da)';
                this.durationInput.style.color = 'var(--tblr-body-color, #495057)';
                this.durationInput.style.cursor = 'not-allowed';
                this.durationInput.style.opacity = '1';
            } else {
                this.durationInput.removeAttribute('aria-readonly');
                this.durationInput.removeAttribute('aria-describedby');
                this.durationInput.style.backgroundColor = '';
                this.durationInput.style.borderColor = '';
                this.durationInput.style.color = '';
                this.durationInput.style.cursor = '';
                this.durationInput.style.opacity = '';
            }
        }

        if (documentSelected) {
            this.syncRequireDuration();
            this.recalculate();
        } else if (this.synchronizeButton) {
            this.synchronizeButton.disabled = true;
        }
    };

    OwlDocumentDurationForm.prototype.getTotalPages = function () {
        return Math.max(0, parseInt(this.totalPagesInput && this.totalPagesInput.value, 10) || 0);
    };

    OwlDocumentDurationForm.prototype.getPageDuration = function () {
        if (!this.isDurationRequired()) return 0;
        return Math.max(0, parseInt(this.pageDurationInput && this.pageDurationInput.value, 10) || 0);
    };

    OwlDocumentDurationForm.prototype.formatDuration = function (totalSeconds) {
        var hours = Math.floor(totalSeconds / 3600);
        var minutes = Math.floor((totalSeconds % 3600) / 60);
        var seconds = totalSeconds % 60;

        return [hours, minutes, seconds]
            .map(function (part) { return String(part).padStart(2, '0'); })
            .join(':');
    };

    OwlDocumentDurationForm.prototype.getCalculatedDuration = function () {
        if (!this.isDurationRequired()) {
            return '00:00:00';
        }
        return this.formatDuration(this.getTotalPages() * this.getPageDuration());
    };

    OwlDocumentDurationForm.prototype.normalizeDurationValue = function (value) {
        var parts = String(value || '').trim().split(':');

        if (parts.length !== 2 && parts.length !== 3) return null;
        if (!parts.every(function (part) { return /^\d+$/.test(part); })) return null;

        var hours = parts.length === 3 ? parseInt(parts[0], 10) : 0;
        var minutes = parseInt(parts[parts.length - 2], 10);
        var seconds = parseInt(parts[parts.length - 1], 10);

        if (seconds > 59 || (parts.length === 3 && minutes > 59)) return null;

        return this.formatDuration((hours * 3600) + (minutes * 60) + seconds);
    };

    OwlDocumentDurationForm.prototype.updateSynchronizationState = function () {
        if (!this.isDocument()) return;

        var currentVal = this.durationInput ? this.durationInput.value : '';
        var calculated = this.getCalculatedDuration();
        var synchronized = this.normalizeDurationValue(currentVal) === calculated;

        if (this.synchronizeButton) this.synchronizeButton.disabled = synchronized;
        if (this.synchronizationStatus) {
            this.synchronizationStatus.classList.toggle('text-success', synchronized);
            this.synchronizationStatus.classList.toggle('text-warning', !synchronized);
        }
        if (this.synchronizationStatusIcon) {
            this.synchronizationStatusIcon.className = synchronized
                ? 'ti ti-circle-check me-1 fs-3'
                : 'ti ti-alert-triangle me-1 fs-3';
        }
        if (this.synchronizationStatusText) {
            this.synchronizationStatusText.textContent = synchronized
                ? 'Duration synchronized'
                : 'Duration not synchronized';
        }
    };

    OwlDocumentDurationForm.prototype.recalculate = function () {
        var pages = this.getTotalPages();
        var secondsPerPage = this.getPageDuration();
        var formatted = this.formatDuration(pages * secondsPerPage);

        if (this.totalPageEstimate) this.totalPageEstimate.textContent = pages;
        if (this.pageDurationEstimate) this.pageDurationEstimate.textContent = secondsPerPage;
        if (this.durationLabel) {
            this.durationLabel.textContent = this.isDurationRequired() ? formatted : 'Self-paced';
        }

        this.updateSynchronizationState();
    };

    OwlDocumentDurationForm.prototype.setStatus = function (message, isError) {
        if (!this.statusLabel) return;

        this.statusLabel.textContent = message || '';
        this.statusLabel.classList.toggle('text-danger', Boolean(isError));
        this.statusLabel.classList.toggle('text-muted', !isError);
    };

    OwlDocumentDurationForm.prototype.readPdf = function (source) {
        var self = this;

        var pdfApi = window.pdfjsLib || window['pdfjs-dist/build/pdf'];

        if (!pdfApi || !source) {
            return Promise.resolve(null);
        }

        this.setStatus('Reading PDF page count…', false);
        this._countRequest = pdfApi.getDocument(source).promise
            .then(function (pdf) {
                self.totalPagesInput.value = pdf.numPages;
                self.setStatus(pdf.numPages + ' PDF pages detected.', false);
                self.recalculate();
                return pdf.numPages;
            })
            .catch(function () {
                self.setStatus('Unable to read the PDF page count.', true);
                return null;
            })
            .finally(function () {
                self._countRequest = null;
            });

        return this._countRequest;
    };

    OwlDocumentDurationForm.prototype.readFile = function (file) {
        var self = this;

        if (!file || !/\.pdf$/i.test(file.name || '')) {
            return Promise.resolve(null);
        }

        this.expectsPdf = true;

        return file.arrayBuffer().then(function (buffer) {
            return self.readPdf({ data: buffer });
        });
    };

    OwlDocumentDurationForm.prototype.loadExistingPdf = function () {
        return this.readPdf(this.existingPdfUrl);
    };

    OwlDocumentDurationForm.prototype.clearUploadedPdf = function () {
        if (this.existingPdfUrl) {
            return this.loadExistingPdf();
        }

        this.expectsPdf = false;
        if (this.totalPagesInput) this.totalPagesInput.value = '';
        if (this.durationLabel) this.durationLabel.textContent = '—';
        this.setStatus('', false);
        return Promise.resolve(null);
    };

    window.OwlDocumentDurationForm = OwlDocumentDurationForm;
})(window, document);
