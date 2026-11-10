/**
 * ContentFileHandler
 * Centralized frontend upload, validation, and metadata extraction handler for Content Files and Thumbnails.
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
                extensions: ['mp4', 'avi', 'mov', 'mkv', 'webm'],
                maxSizeMB: 500,
                invalidTypeError: 'Only video files (MP4, AVI, MOV, MKV, WEBM) are allowed.',
                invalidSizeError: 'Maximum file size is 500 MB.'
            },
            Thumbnail: {
                extensions: ['jpg', 'jpeg', 'png', 'webp'],
                maxSizeMB: 1,
                invalidTypeError: 'Only JPG, PNG, or WEBP images are allowed.',
                invalidSizeError: 'Maximum thumbnail size is 1 MB.'
            },
            Default: {
                extensions: ['pdf', 'mp4', 'avi', 'mov', 'mkv', 'webm'],
                maxSizeMB: 500,
                invalidTypeError: 'Only PDF, MP4, AVI, MOV, MKV, and WEBM files are allowed.',
                invalidSizeError: 'Maximum file size is 500 MB.'
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

        // 1. Initialize Document / Content File Dropzone
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
                    const dz = this;

                    dz.on("maxfilesexceeded", function (file) {
                        const existing = dz.getAcceptedFiles().concat(dz.getUploadingFiles(), dz.getQueuedFiles());
                        existing.forEach(f => {
                            if (f !== file) {
                                dz.removeFile(f);
                            }
                        });
                    });

                    dz.on("uploadprogress", function (file, progress) {
                        if (file && file.previewElement) {
                            let badge = file.previewElement.querySelector('.dz-upload-badge');
                            if (!badge) {
                                badge = document.createElement('span');
                                badge.className = 'badge bg-blue-lt ms-auto dz-upload-badge';
                                file.previewElement.appendChild(badge);
                            }
                            badge.className = 'badge bg-blue-lt ms-auto dz-upload-badge';
                            badge.textContent = Math.round(progress) + '%';
                        }
                        self.updateSubmitButtons(Math.round(progress));
                    });

                    dz.on("error", function (file, message) {
                        if (typeof message === 'object' && message !== null) {
                            message = message.error || message.message || Object.values(message.errors || {})[0]?.[0] || 'Upload error';
                        }
                        if (typeof message === 'string' && message.length > 0) {
                            if (!message.includes('code 0') && !message.includes('responded with 0')) {
                                self.showError(message);
                            }
                        }
                        if (file && file.previewElement) {
                            let badge = file.previewElement.querySelector('.dz-upload-badge');
                            if (!badge) {
                                badge = document.createElement('span');
                                file.previewElement.appendChild(badge);
                            }
                            badge.className = 'badge bg-danger-lt ms-auto dz-upload-badge';
                            badge.innerHTML = '<i class="ti ti-x me-1"></i> Failed';
                        }
                        self.clearSelection(dz, file);
                        self.updateSubmitButtons();
                    });

                    dz.on("addedfile", function (file) {
                        if (file.rejected || file.status === 'error') return;
                        self.handleVideoUpload(file);
                        self.updateSubmitButtons();
                    });

                    const handleDocSuccess = function (data, response) {
                        let res = response;
                        if (typeof res === 'string') {
                            try { res = JSON.parse(res); } catch (e) {}
                        }
                        const fileList = Array.isArray(data) ? data : [data];
                        if (res && res.name) {
                            const names = Array.isArray(res.name) ? res.name : [res.name];
                            names.forEach(function (val, idx) {
                                if (!$(formElement).find(`input[name="documents[]"][value="${val}"]`).length) {
                                    $(formElement).append('<input type="hidden" name="documents[]" value="' + val + '" required>');
                                }
                                const orig = fileList[idx] ? fileList[idx].name : (res.original_name?.[idx] || val);
                                self.uploadedDocumentMap[orig] = val;

                                if (fileList[idx] && fileList[idx].previewElement) {
                                    let badge = fileList[idx].previewElement.querySelector('.dz-upload-badge');
                                    if (!badge) {
                                        badge = document.createElement('span');
                                        fileList[idx].previewElement.appendChild(badge);
                                    }
                                    badge.className = 'badge bg-success-lt ms-auto dz-upload-badge';
                                    badge.innerHTML = '<i class="ti ti-check me-1"></i> Ready';
                                }
                            });
                        }
                        self.updateSubmitButtons();
                    };

                    dz.on("success", function (file, response) {
                        handleDocSuccess(file, response);
                    });

                    dz.on("successmultiple", function (files, response) {
                        handleDocSuccess(files, response);
                    });

                    dz.on("complete", function () {
                        self.updateSubmitButtons();
                    });

                    dz.on("completemultiple", function () {
                        self.updateSubmitButtons();
                    });
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
                    self.updateSubmitButtons();
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
                    const dz = this;

                    dz.on("maxfilesexceeded", function (file) {
                        const existing = dz.getAcceptedFiles().concat(dz.getUploadingFiles(), dz.getQueuedFiles());
                        existing.forEach(f => {
                            if (f !== file) {
                                dz.removeFile(f);
                            }
                        });
                    });

                    dz.on("uploadprogress", function (file, progress) {
                        if (file && file.previewElement) {
                            let badge = file.previewElement.querySelector('.dz-upload-badge');
                            if (!badge) {
                                badge = document.createElement('span');
                                badge.className = 'badge bg-blue-lt ms-auto dz-upload-badge';
                                file.previewElement.appendChild(badge);
                            }
                            badge.className = 'badge bg-blue-lt ms-auto dz-upload-badge';
                            badge.textContent = Math.round(progress) + '%';
                        }
                        self.updateSubmitButtons(Math.round(progress));
                    });

                    dz.on("error", function (file, message) {
                        if (typeof message === 'string' && message.length > 0) {
                            if (!message.includes('code 0') && !message.includes('responded with 0')) {
                                self.showError(message);
                            }
                        }
                        if (file && file.previewElement) {
                            let badge = file.previewElement.querySelector('.dz-upload-badge');
                            if (!badge) {
                                badge = document.createElement('span');
                                file.previewElement.appendChild(badge);
                            }
                            badge.className = 'badge bg-danger-lt ms-auto dz-upload-badge';
                            badge.innerHTML = '<i class="ti ti-x me-1"></i> Failed';
                        }
                        self.clearSelection(dz, file);
                        self.updateSubmitButtons();
                    });

                    dz.on("addedfile", function () {
                        self.updateSubmitButtons();
                    });

                    const handleThumbSuccess = function (data, response) {
                        let res = response;
                        if (typeof res === 'string') {
                            try { res = JSON.parse(res); } catch (e) {}
                        }
                        const fileList = Array.isArray(data) ? data : [data];
                        if (res && res.name) {
                            const names = Array.isArray(res.name) ? res.name : [res.name];
                            names.forEach(function (val, idx) {
                                if (!$(formElement).find(`input[name="thumbnail[]"][value="${val}"]`).length) {
                                    $(formElement).append('<input type="hidden" name="thumbnail[]" value="' + val + '" required>');
                                }
                                const orig = fileList[idx] ? fileList[idx].name : (res.original_name?.[idx] || val);
                                self.uploadedThumbnailMap[orig] = val;

                                if (fileList[idx] && fileList[idx].previewElement) {
                                    let badge = fileList[idx].previewElement.querySelector('.dz-upload-badge');
                                    if (!badge) {
                                        badge = document.createElement('span');
                                        fileList[idx].previewElement.appendChild(badge);
                                    }
                                    badge.className = 'badge bg-success-lt ms-auto dz-upload-badge';
                                    badge.innerHTML = '<i class="ti ti-check me-1"></i> Ready';
                                }
                            });
                            const removeThumbInput = document.getElementById('remove_thumbnail');
                            if (removeThumbInput) {
                                removeThumbInput.value = '0';
                            }
                        }
                        self.updateSubmitButtons();
                    };

                    dz.on("success", function (file, response) {
                        handleThumbSuccess(file, response);
                    });

                    dz.on("successmultiple", function (files, response) {
                        handleThumbSuccess(files, response);
                    });

                    dz.on("complete", function () {
                        self.updateSubmitButtons();
                    });

                    dz.on("completemultiple", function () {
                        self.updateSubmitButtons();
                    });
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
                    self.updateSubmitButtons();
                }
            });
        }

        // Intercept premature form submission while files are uploading
        if (formElement) {
            formElement.addEventListener('submit', function (e) {
                if (self.isUploading()) {
                    e.preventDefault();
                    e.stopPropagation();
                    self.showError('File upload is still in progress. Please wait until upload completes before saving.');
                    return false;
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
            if (this.docDropzone.hiddenFileInput) {
                this.docDropzone.hiddenFileInput.setAttribute('accept', acceptedStr);
            }
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
     * Handle local video upload and automatically extract duration.
     * @param {File} file 
     */
    async handleVideoUpload(file) {
        if (!file) return;

        const type = this.getCurrentType();
        const ext = (file.name || '').split('.').pop().toLowerCase();
        const videoExts = ['mp4', 'avi', 'mov', 'mkv', 'webm'];

        // Only detect duration if Content Type is Video or file is a valid video format
        if (type !== 'Video' && !videoExts.includes(ext)) {
            return;
        }

        // Validate file first — if invalid, do not update duration field
        const validation = this.validateContentFile(file);
        if (!validation.valid || file.rejected) {
            return;
        }

        try {
            const formattedDuration = await this.extractVideoDuration(file);
            if (formattedDuration) {
                this.updateDurationField(formattedDuration);
            }
        } catch (err) {
            console.warn('Could not extract video duration automatically:', err);
        }
    }

    /**
     * Extract video duration in seconds using native HTML5 Video API.
     * @param {File} file 
     * @returns {Promise<string|null>} Formatted HH:MM:SS or null
     */
    extractVideoDuration(file) {
        return new Promise((resolve) => {
            if (!file) {
                resolve(null);
                return;
            }

            const video = document.createElement('video');
            video.preload = 'metadata';

            const objectUrl = URL.createObjectURL(file);
            video.src = objectUrl;

            const cleanup = () => {
                try {
                    URL.revokeObjectURL(objectUrl);
                } catch (e) {}
            };

            video.onloadedmetadata = () => {
                cleanup();
                const duration = video.duration;
                if (isNaN(duration) || duration === Infinity || duration <= 0) {
                    resolve(null);
                } else {
                    resolve(this.formatDuration(duration));
                }
            };

            video.onerror = () => {
                cleanup();
                resolve(null);
            };
        });
    }

    /**
     * Format duration in seconds to HH:MM:SS.
     * @param {number} seconds 
     * @returns {string} HH:MM:SS (padded 2 digits per segment)
     */
    formatDuration(seconds) {
        if (seconds === null || seconds === undefined || isNaN(seconds)) {
            return '';
        }
        const totalSec = Math.round(seconds);
        const hours = Math.floor(totalSec / 3600);
        const minutes = Math.floor((totalSec % 3600) / 60);
        const secs = totalSec % 60;

        const pad = (num) => String(num).padStart(2, '0');
        return `${pad(hours)}:${pad(minutes)}:${pad(secs)}`;
    }

    /**
     * Populate duration string into metadata duration input field.
     * @param {string} durationString 
     */
    updateDurationField(durationString) {
        if (!durationString) return;

        const videoDurationInput = document.getElementById('video_duration') ||
                                    document.getElementById('duration');

        if (videoDurationInput) {
            videoDurationInput.value = durationString;
            // Trigger reactive event listeners
            videoDurationInput.dispatchEvent(new Event('input', { bubbles: true }));
            videoDurationInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    /**
     * Check if any files are currently uploading in document or thumbnail dropzones.
     */
    isUploading() {
        const docUploading = this.docDropzone ? this.docDropzone.getUploadingFiles().length > 0 : false;
        const thumbUploading = this.thumbDropzone ? this.thumbDropzone.getUploadingFiles().length > 0 : false;
        return docUploading || thumbUploading;
    }

    /**
     * Lock or unlock submit buttons to prevent premature submission during file upload.
     */
    updateSubmitButtons(progress) {
        const formElement = document.getElementById(this.formId) || document.querySelector('form');
        const internalButtons = formElement ? Array.from(formElement.querySelectorAll('button[type="submit"], input[type="submit"]')) : [];
        const externalButtons = Array.from(document.querySelectorAll(`button[type="submit"][form="${this.formId}"]`));
        const allButtons = Array.from(new Set(internalButtons.concat(externalButtons)));

        const uploading = this.isUploading();

        allButtons.forEach(btn => {
            if (uploading) {
                if (!btn.dataset.originalHtml) {
                    btn.dataset.originalHtml = btn.innerHTML;
                }
                btn.disabled = true;
                btn.classList.add('disabled');
                const progText = (typeof progress === 'number' && progress > 0 && progress < 100)
                    ? `Uploading (${progress}%)...`
                    : 'Uploading... Please wait';
                btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> ${progText}`;
            } else {
                if (btn.dataset.originalHtml) {
                    btn.innerHTML = btn.dataset.originalHtml;
                    delete btn.dataset.originalHtml;
                }
                btn.disabled = false;
                btn.classList.remove('disabled');
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
