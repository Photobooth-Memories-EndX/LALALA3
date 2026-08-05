(function (window, document) {
    'use strict';

    function OwlDocumentDurationForm(options) {
        this.options = options || {};
        this.form = document.getElementById(this.options.formId);
        this.typeInput = document.getElementById(this.options.typeInputId || 'type');
        this.row = document.getElementById('document-page-duration-row');
        this.pageDurationInput = document.getElementById('page_duration');
        this.totalPagesInput = document.getElementById('document_total_pages');
        this.durationInput = document.getElementById('duration');
        this.durationLabel = document.getElementById('document-calculated-duration');
        this.statusLabel = document.getElementById('document-page-count-status');
        this.existingPdfUrl = this.options.existingPdfUrl || null;
        this.expectsPdf = Boolean(this.existingPdfUrl);
        this._countRequest = null;

        if (!this.form || !this.typeInput || !this.row || !this.pageDurationInput) {
            return;
        }

        this.bind();
        this.syncType();

        if (this.isDocument() && this.existingPdfUrl) {
            this.loadExistingPdf();
        } else {
            this.recalculate();
        }
    }

    OwlDocumentDurationForm.prototype.isDocument = function () {
        return this.typeInput && this.typeInput.value === 'Document';
    };

    OwlDocumentDurationForm.prototype.bind = function () {
        var self = this;

        this.typeInput.addEventListener('change', function () {
            self.syncType();

            if (self.isDocument() && self.existingPdfUrl && !self.getTotalPages()) {
                self.loadExistingPdf();
            }
        });

        this.pageDurationInput.addEventListener('input', function () {
            self.recalculate();
        });

        this.form.addEventListener('submit', function (event) {
            if (!self.isDocument()) return;

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

    OwlDocumentDurationForm.prototype.syncType = function () {
        var documentSelected = this.isDocument();

        this.row.style.display = documentSelected ? '' : 'none';
        this.pageDurationInput.required = documentSelected;

        if (this.durationInput) {
            this.durationInput.readOnly = documentSelected;

            if (documentSelected) {
                this.durationInput.setAttribute('aria-describedby', 'document-page-count-status');
            } else {
                this.durationInput.removeAttribute('aria-describedby');
            }
        }

        if (documentSelected) {
            this.recalculate();
        }
    };

    OwlDocumentDurationForm.prototype.getTotalPages = function () {
        return Math.max(0, parseInt(this.totalPagesInput && this.totalPagesInput.value, 10) || 0);
    };

    OwlDocumentDurationForm.prototype.recalculate = function () {
        var pages = this.getTotalPages();
        var secondsPerPage = Math.max(0, parseInt(this.pageDurationInput.value, 10) || 0);

        if (!pages || !secondsPerPage) {
            if (this.durationLabel) this.durationLabel.textContent = '—';
            return;
        }

        var totalSeconds = pages * secondsPerPage;
        var hours = Math.floor(totalSeconds / 3600);
        var minutes = Math.floor((totalSeconds % 3600) / 60);
        var seconds = totalSeconds % 60;
        var value = [hours, minutes, seconds]
            .map(function (part) { return String(part).padStart(2, '0'); })
            .join(':');

        if (this.durationInput) this.durationInput.value = value;
        if (this.durationLabel) this.durationLabel.textContent = value + ' (' + pages + ' pages)';
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
