/**
 * ContentFileHandler
 * Centralized frontend upload and validation handler for Content Files and Thumbnails.
 */
class ContentFileHandler {
    constructor(config = {}) {
        this.formId = config.formId || 'contentCreateForm';
        this.typeSelectId = config.typeSelectId || 'type';
        this.documentDropzoneSelector = config.documentDropzoneSelector || 'div#document-dropzone';
        this.thumbnailDropzoneSelector = config.thumbnailDropzoneSelector || 'div#thumbnail-dropzone';

        this.uploadFileUrl = config.uploadFileUrl || '';
        this.deleteFileUrl = config.deleteFileUrl || '';
        this.uploadThumbUrl = config.uploadThumbUrl || '';
        this.deleteThumbUrl = config.deleteThumbUrl || '';
        this.csrfToken = config.csrfToken || '';
        this.mode = config.mode || 'create';

        this.docDropzone = null;
        this.thumbDropzone = null;

        this.uploadedDocumentMap = {};
        this.uploadedThumbnailMap = {};

        // Validation Rules
        this.rules = {
            Document: {
                extensions: ['pdf'],
                maxSizeMB: 10,
                invalidTypeError: 'Only PDF files are allowed.',
                invalidSizeError: 'Maximum file size is 10 MB.'
            },
            'SOP/Regulation': {
                extensions: ['pdf'],
                maxSizeMB: 10,
                invalidTypeError: 'Only PDF files are allowed.',
                invalidSizeError: 'Maximum file size is 10 MB.'
            },
            Video: {
                extensions: ['mp4', 'avi'],
                maxSizeMB: 300,
                invalidTypeError: 'Only MP4 and AVI files are allowed.',
                invalidSizeError: 'Maximum file size is 300 MB.'
            },
            Thumbnail: {
                extensions: ['jpg', 'jpeg', 'png', 'webp'],
                maxSizeMB: 1,
                invalidTypeError: 'Only JPG, PNG, or WEBP images are allowed.',
                invalidSizeError: 'Maximum thumbnail size is 1 MB.'
            },
            Default: {
                extensions: ['pdf', 'mp4', 'avi'],
                maxSizeMB: 300,
                invalidTypeError: 'Only PDF, MP4, and AVI files are allowed.',
                invalidSizeError: 'Maximum file size is 300 MB.'
            }
        };
    }

    /**
     * Initialize Dropzones and bind events.
     */
    initialize() {
        if (typeof Dropzone === 'undefined') {
            console.error('Dropzone library is required for ContentFileHandler.');
            return;
        }

        Dropzone.autoDiscover = false;

        const self = this;
        const formElement = document.getElementById(this.formId) || document.querySelector('form');

        // 1. Initialize Document Dropzone
        const docElement = document.querySelector(this.documentDropzoneSelector);
        if (docElement) {
            if (docElement.dropzone) {
                docElement.dropzone.destroy();
            }

            this.docDropzone = new Dropzone(this.documentDropzoneSelector, {
                url: this.uploadFileUrl,
                autoProcessQueue: true,
                uploadMultiple: true,
                addRemoveLinks: true,
                acceptedFiles: self.getAcceptedFilesForType(),
                parallelUploads: 2,
                headers: { 'X-CSRF-TOKEN': this.csrfToken },
                maxFiles: 1,
                maxFilesize: 500,
                accept: function (file, done) {
                    const validation = self.validateContentFile(file);
                    if (!validation.valid) {
                        file.rejected = true;
                        done(validation.message);
                    } else {
                        file.rejected = false;
                        done();
                    }
                },
                init: function () {
                    this.on("error", function (file, message) {
                        if (typeof message === 'string' && message.length > 0) {
                            self.showError(message);
                        }
                        self.clearSelection(this, file);
                    });
                },
                successmultiple: function (data, response) {
                    if (response && response.name) {
                        $.each(response['name'], function (key, val) {
                            $(formElement).append('<input type="hidden" name="documents[]" value="' + val + '" required>');
                            self.uploadedDocumentMap[data[key].name] = val;
                        });
                    }
                },
                removedfile: function (file) {
                    if (file.previewElement) {
                        file.previewElement.remove();
                    }
                    let name = typeof file.file_name !== 'undefined' ? file.file_name : self.uploadedDocumentMap[file.name];
                    if (name && self.deleteFileUrl) {
                        $.ajax({
                            url: self.deleteFileUrl,
                            data: { name: name },
                            success: function (data) { console.log('Deleted document file:', data); }
                        });
                        $(formElement).find('input[name="documents[]"][value="' + name + '"]').remove();
                    }
                }
            });
        }

        // 2. Initialize Thumbnail Dropzone
        const thumbElement = document.querySelector(this.thumbnailDropzoneSelector);
        if (thumbElement) {
            if (thumbElement.dropzone) {
                thumbElement.dropzone.destroy();
            }

            this.thumbDropzone = new Dropzone(this.thumbnailDropzoneSelector, {
                url: this.uploadThumbUrl,
                autoProcessQueue: true,
                uploadMultiple: true,
                addRemoveLinks: true,
                acceptedFiles: ".jpg,.jpeg,.png,.webp",
                parallelUploads: 2,
                headers: { 'X-CSRF-TOKEN': this.csrfToken },
                maxFiles: 1,
                maxFilesize: 50,
                accept: function (file, done) {
                    const validation = self.validateThumbnail(file);
                    if (!validation.valid) {
                        file.rejected = true;
                        done(validation.message);
                    } else {
                        file.rejected = false;
                        done();
                    }
                },
                init: function () {
                    this.on("error", function (file, message) {
                        if (typeof message === 'string' && message.length > 0) {
                            self.showError(message);
                        }
                        self.clearSelection(this, file);
                    });
                },
                successmultiple: function (data, response) {
                    if (response && response.name) {
                        $.each(response['name'], function (key, val) {
                            $(formElement).append('<input type="hidden" name="thumbnail[]" value="' + val + '" required>');
                            self.uploadedThumbnailMap[data[key].name] = val;
                        });
                    }
                },
                removedfile: function (file) {
                    if (file.previewElement) {
                        file.previewElement.remove();
                    }
                    let name = typeof file.file_name !== 'undefined' ? file.file_name : self.uploadedThumbnailMap[file.name];
                    if (name && self.deleteThumbUrl) {
                        $.ajax({
                            url: self.deleteThumbUrl,
                            data: { name: name },
                            success: function (data) { console.log('Deleted thumbnail file:', data); }
                        });
                        $(formElement).find('input[name="thumbnail[]"][value="' + name + '"]').remove();
                    }
                }
            });
        }

        this.bindEvents();
        this.updateAcceptAttribute();
    }

    /**
     * Bind DOM and Dropzone events.
     */
    bindEvents() {
        const typeSelect = document.getElementById(this.typeSelectId);
        if (typeSelect) {
            typeSelect.addEventListener('change', () => {
                this.updateAcceptAttribute();
                this.revalidateActiveContentFile();
            });
        }
    }

    /**
     * Get current selected content type.
     */
    getCurrentType() {
        const typeSelect = document.getElementById(this.typeSelectId);
        return typeSelect ? typeSelect.value : '';
    }

    /**
     * Get accepted files string for current type.
     */
    getAcceptedFilesForType() {
        const type = this.getCurrentType();
        const rule = this.rules[type] || this.rules.Default;
        return rule.extensions.map(ext => '.' + ext).join(',');
    }

    /**
     * Update accept attribute and UI hints based on content type.
     */
    updateAcceptAttribute() {
        const type = this.getCurrentType();
        const rule = this.rules[type] || this.rules.Default;
        const acceptedStr = rule.extensions.map(ext => '.' + ext).join(',');

        if (this.docDropzone) {
            this.docDropzone.options.acceptedFiles = acceptedStr;
        }

        // Update help text UI if element exists
        const fileHelp = document.getElementById('content-file-help-text');
        if (fileHelp) {
            const extsFormatted = rule.extensions.map(e => e.toUpperCase()).join(', ');
            fileHelp.textContent = `Format: ${extsFormatted} — maks. 1 file, ${rule.maxSizeMB} MB`;
        }

        const thumbHelp = document.getElementById('thumbnail-help-text');
        if (thumbHelp) {
            thumbHelp.textContent = 'Format: JPG, JPEG, PNG, WEBP — maks. 1 file, 1 MB';
        }
    }

    /**
     * Validate content file against current Content Type rules.
     */
    validateContentFile(file) {
        const type = this.getCurrentType();
        const rule = this.rules[type] || this.rules.Default;

        if (!this.validateFileType(file, rule.extensions)) {
            return { valid: false, message: rule.invalidTypeError };
        }

        if (!this.validateFileSize(file, rule.maxSizeMB)) {
            return { valid: false, message: rule.invalidSizeError };
        }

        return { valid: true };
    }

    /**
     * Validate thumbnail file.
     */
    validateThumbnail(file) {
        const rule = this.rules.Thumbnail;

        if (!this.validateFileType(file, rule.extensions)) {
            return { valid: false, message: rule.invalidTypeError };
        }

        if (!this.validateFileSize(file, rule.maxSizeMB)) {
            return { valid: false, message: rule.invalidSizeError };
        }

        return { valid: true };
    }

    /**
     * Check if file extension is allowed.
     */
    validateFileType(file, allowedExtensions) {
        if (!file || !file.name) return false;
        const ext = file.name.split('.').pop().toLowerCase();
        return allowedExtensions.includes(ext);
    }

    /**
     * Check if file size is within maximum limit in MB.
     */
    validateFileSize(file, maxSizeMB) {
        if (!file || !file.size) return false;
        const maxSizeBytes = maxSizeMB * 1024 * 1024;
        return file.size <= maxSizeBytes;
    }

    /**
     * Re-validate active files in document dropzone if Content Type changes.
     */
    revalidateActiveContentFile() {
        if (!this.docDropzone) return;
        const files = this.docDropzone.getQueuedFiles()
            .concat(this.docDropzone.getUploadingFiles(), this.docDropzone.getAcceptedFiles());

        files.forEach(file => {
            const validation = this.validateContentFile(file);
            if (!validation.valid) {
                file.rejected = true;
                this.showError(validation.message);
                this.clearSelection(this.docDropzone, file);
            }
        });
    }

    /**
     * Show validation error message.
     */
    showError(message) {
        if (typeof window.Swal !== 'undefined') {
            window.Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: message,
                confirmButtonColor: '#206bc4'
            });
        } else {
            alert(message);
        }
    }

    /**
     * Clear invalid file selection from Dropzone.
     */
    clearSelection(dropzoneInstance, file) {
        if (dropzoneInstance && file) {
            dropzoneInstance.removeFile(file);
        }
    }

    /**
     * Trigger live preview update if needed.
     */
    refreshPreview() {
        if (window.OwlPreview) {
            if (this.thumbDropzone) window.OwlPreview.initThumb(this.thumbDropzone);
            if (this.docDropzone) window.OwlPreview.initFile(this.docDropzone);
        }
    }

    /**
     * Getter for Document Dropzone instance.
     */
    getDocDropzone() {
        return this.docDropzone;
    }

    /**
     * Getter for Thumbnail Dropzone instance.
     */
    getThumbDropzone() {
        return this.thumbDropzone;
    }
}

// Global export
window.ContentFileHandler = ContentFileHandler;
