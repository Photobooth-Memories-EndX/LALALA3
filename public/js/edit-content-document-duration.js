(function (window, document) {
    'use strict';

    function EditContentDocumentDuration(options) {
        this.options = options || {};
        this.typeInput = document.getElementById(this.options.typeInputId || 'type');
        this.fields = document.getElementById('document-duration-fields') || document.getElementById('metadata-section-document');
        this.requireDurationInput = document.getElementById('require_page_duration');
        this.scrollModeNotice = document.getElementById('document-scroll-mode-notice');
        this.pageDurationHint = document.getElementById('page-duration-hint');
        this.totalPageInput = document.getElementById('total_page') || document.getElementById('document_total_pages');
        this.pageDurationInput = document.getElementById('page_duration');
        this.durationInput = document.getElementById('doc_duration') || document.getElementById(this.options.durationInputId || 'duration');
        this.synchronizeButton = document.getElementById('edit-duration-synchronize');
        this.totalPageEstimate = document.getElementById('edit-total-page-estimate');
        this.pageDurationEstimate = document.getElementById('edit-page-duration-estimate');
        this.durationEstimate = document.getElementById('edit-duration-estimate');
        this.synchronizationStatus = document.getElementById('edit-duration-sync-status');
        this.synchronizationStatusIcon = document.getElementById('edit-duration-sync-status-icon');
        this.synchronizationStatusText = document.getElementById('edit-duration-sync-status-text');
        this.status = document.getElementById('edit-document-page-status');
        this.detectedPageActions = document.getElementById('edit-detected-page-actions');
        this.detectedPageCountLabel = document.getElementById('edit-detected-page-count');
        this.useDetectedPageCountButton = document.getElementById('edit-use-detected-page-count');
        this.existingPdfUrl = this.options.existingPdfUrl || null;
        this.pdfRequest = null;
        this.detectedPageCount = null;

        if (!this.typeInput || !this.fields || !this.totalPageInput
            || !this.pageDurationInput || !this.durationInput) {
            return;
        }

        this.bind();
        this.initializeTooltip();
        this.syncType();
        this.syncRequireDuration();

        if (this.isDocument() && this.existingPdfUrl) {
            this.readPdf(this.existingPdfUrl);
        }
    }

    EditContentDocumentDuration.prototype.isDocument = function () {
        return this.typeInput && this.typeInput.value === 'Document';
    };

    EditContentDocumentDuration.prototype.isDurationRequired = function () {
        return !this.requireDurationInput || this.requireDurationInput.checked;
    };

    EditContentDocumentDuration.prototype.bind = function () {
        var self = this;

        this.typeInput.addEventListener('change', function () {
            self.syncType();

            if (self.isDocument() && self.existingPdfUrl && !self.getTotalPage()) {
                self.readPdf(self.existingPdfUrl);
            }
        });

        if (this.requireDurationInput) {
            this.requireDurationInput.addEventListener('change', function () {
                self.syncRequireDuration();
                self.recalculate();
            });
        }

        if (this.totalPageInput) {
            this.totalPageInput.addEventListener('input', function () {
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
                if (self.durationEstimate) {
                    self.durationEstimate.textContent = self.isDurationRequired() ? calculated : 'Self-paced';
                }
                if (self.totalPageEstimate) {
                    self.totalPageEstimate.textContent = self.getTotalPage();
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

        var form = this.pageDurationInput.closest('form');
        if (form) {
            form.addEventListener('submit', function (event) {
                if (!self.isDocument()) return;

                if (self.isDurationRequired() && self.getPageDuration() < 1) {
                    event.preventDefault();
                    self.setStatus('Page Duration must be at least 1 second when duration is enabled.', true);
                    if (self.pageDurationInput) self.pageDurationInput.focus();
                }
            });
        }

        if (this.useDetectedPageCountButton) {
            this.useDetectedPageCountButton.addEventListener('click', function () {
                if (!self.detectedPageCount) return;

                self.totalPageInput.value = self.detectedPageCount;
                self.recalculate();
                self.totalPageInput.focus();
            });
        }
    };

    EditContentDocumentDuration.prototype.syncRequireDuration = function () {
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

    EditContentDocumentDuration.prototype.syncType = function () {
        var active = this.isDocument();

        if (this.fields) this.fields.style.display = active ? '' : 'none';
        if (this.synchronizeButton) this.synchronizeButton.hidden = !active;
        if (this.totalPageInput) this.totalPageInput.required = active;
        if (this.pageDurationInput) this.pageDurationInput.required = active;

        if (this.durationInput) {
            this.durationInput.readOnly = active;

            if (active) {
                this.durationInput.setAttribute('aria-readonly', 'true');
                this.durationInput.style.backgroundColor = 'var(--tblr-gray-200, #e9ecef)';
                this.durationInput.style.borderColor = 'var(--tblr-border-color, #ced4da)';
                this.durationInput.style.color = 'var(--tblr-body-color, #495057)';
                this.durationInput.style.cursor = 'not-allowed';
                this.durationInput.style.opacity = '1';
            } else {
                this.durationInput.removeAttribute('aria-readonly');
                this.durationInput.style.backgroundColor = '';
                this.durationInput.style.borderColor = '';
                this.durationInput.style.color = '';
                this.durationInput.style.cursor = '';
                this.durationInput.style.opacity = '';
            }
        }

        if (active) {
            this.syncRequireDuration();
            this.recalculate();
        } else if (this.synchronizeButton) {
            this.synchronizeButton.disabled = true;
        }
    };

    EditContentDocumentDuration.prototype.getTotalPage = function () {
        return Math.max(0, parseInt(this.totalPageInput ? this.totalPageInput.value : 0, 10) || 0);
    };

    EditContentDocumentDuration.prototype.getPageDuration = function () {
        if (!this.isDurationRequired()) return 0;
        return Math.max(0, parseInt(this.pageDurationInput ? this.pageDurationInput.value : 0, 10) || 0);
    };

    EditContentDocumentDuration.prototype.formatDuration = function (totalSeconds) {
        var hours = Math.floor(totalSeconds / 3600);
        var minutes = Math.floor((totalSeconds % 3600) / 60);
        var seconds = totalSeconds % 60;

        return [hours, minutes, seconds]
            .map(function (part) { return String(part).padStart(2, '0'); })
            .join(':');
    };

    EditContentDocumentDuration.prototype.getCalculatedDuration = function () {
        if (!this.isDurationRequired()) {
            return '00:00:00';
        }
        return this.formatDuration(this.getTotalPage() * this.getPageDuration());
    };

    EditContentDocumentDuration.prototype.normalizeDurationValue = function (value) {
        var parts = String(value || '').trim().split(':');

        if (parts.length !== 2 && parts.length !== 3) return null;
        if (!parts.every(function (part) { return /^\d+$/.test(part); })) return null;

        var hours = parts.length === 3 ? parseInt(parts[0], 10) : 0;
        var minutes = parseInt(parts[parts.length - 2], 10);
        var seconds = parseInt(parts[parts.length - 1], 10);

        if (seconds > 59 || (parts.length === 3 && minutes > 59)) return null;

        return this.formatDuration((hours * 3600) + (minutes * 60) + seconds);
    };

    EditContentDocumentDuration.prototype.initializeTooltip = function () {
        if (!this.synchronizeButton || !window.bootstrap || !window.bootstrap.Tooltip) return;

        if (typeof window.bootstrap.Tooltip.getOrCreateInstance === 'function') {
            window.bootstrap.Tooltip.getOrCreateInstance(this.synchronizeButton);
        } else {
            new window.bootstrap.Tooltip(this.synchronizeButton);
        }
    };

    EditContentDocumentDuration.prototype.updateSynchronizationState = function () {
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

    EditContentDocumentDuration.prototype.recalculate = function () {
        if (!this.isDocument()) return;

        var totalPage = this.getTotalPage();
        var pageDuration = this.getPageDuration();
        var formatted = this.formatDuration(totalPage * pageDuration);

        if (this.totalPageEstimate) this.totalPageEstimate.textContent = totalPage;
        if (this.pageDurationEstimate) this.pageDurationEstimate.textContent = pageDuration;
        if (this.durationEstimate) {
            this.durationEstimate.textContent = this.isDurationRequired() ? formatted : 'Self-paced';
        }

        this.updateSynchronizationState();
    };

    EditContentDocumentDuration.prototype.setStatus = function (message, isError) {
        if (!this.status) return;

        this.status.textContent = message || '';
        this.status.classList.toggle('text-danger', Boolean(isError));
        this.status.classList.toggle('text-secondary', !isError);
    };

    EditContentDocumentDuration.prototype.readPdf = function (source) {
        var self = this;
        var pdfApi = window.pdfjsLib || window['pdfjs-dist/build/pdf'];

        if (!pdfApi || !source) return Promise.resolve(null);

        this.setStatus('Reading PDF page count...', false);
        this.pdfRequest = pdfApi.getDocument(source).promise
            .then(function (pdf) {
                self.detectedPageCount = pdf.numPages;
                if (self.detectedPageCountLabel) {
                    self.detectedPageCountLabel.textContent = pdf.numPages;
                }
                if (self.detectedPageActions) {
                    self.detectedPageActions.classList.remove('d-none');
                    self.detectedPageActions.classList.add('d-flex');
                }
                self.setStatus('PDF detected successfully. The saved Total Page value was not changed.', false);
                return pdf.numPages;
            })
            .catch(function () {
                self.clearDetectedPageCount();
                self.setStatus('Unable to detect the PDF page count. The saved Total Page value was not changed.', true);
                return null;
            })
            .finally(function () {
                self.pdfRequest = null;
            });

        return this.pdfRequest;
    };

    EditContentDocumentDuration.prototype.readFile = function (file) {
        var self = this;

        if (!file || !/\.pdf$/i.test(file.name || '')) return Promise.resolve(null);

        return file.arrayBuffer().then(function (buffer) {
            return self.readPdf({ data: buffer });
        });
    };

    EditContentDocumentDuration.prototype.restoreExistingPdf = function () {
        if (this.existingPdfUrl) return this.readPdf(this.existingPdfUrl);

        this.clearDetectedPageCount();
        this.setStatus('', false);
        return Promise.resolve(null);
    };

    EditContentDocumentDuration.prototype.clearDetectedPageCount = function () {
        this.detectedPageCount = null;
        if (this.detectedPageCountLabel) this.detectedPageCountLabel.textContent = '0';
        if (this.detectedPageActions) {
            this.detectedPageActions.classList.add('d-none');
            this.detectedPageActions.classList.remove('d-flex');
        }
    };

    window.EditContentDocumentDuration = EditContentDocumentDuration;
})(window, document);
