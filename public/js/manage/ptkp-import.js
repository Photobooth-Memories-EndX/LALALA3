/**
 * PTKP Offline Training Import UI Handler
 * Redesigned 3-Step Process for OWL LMS
 */
(function ($) {
    'use strict';

    let selectedFile = null;
    let importToken = null;
    let allRows = [];
    let filteredRows = [];
    let currentFilter = 'all';
    let searchQuery = { empId: '', name: '', course: '', pic: '' };
    let currentPage = 1;
    let pageSize = 25;
    let isSubmitting = false;
    let isFiltering = false;

    // DOM Elements
    const $uploadCard = $('#ptkp-upload-card');
    const $loadingCard = $('#ptkp-loading-card');
    const $previewCard = $('#ptkp-preview-card');
    const $confirmCard = $('#ptkp-confirm-card');
    const $resultCard = $('#ptkp-result-card');

    const $fileInput = $('#ptkp-file-input');
    const $dropzone = $('#ptkp-dropzone');
    const $dropzoneInitial = $('#ptkp-dropzone-initial');
    const $fileInfoCard = $('#ptkp-file-info-card');
    const $cardFilename = $('#ptkp-card-filename');
    const $cardFilesize = $('#ptkp-card-filesize');
    const $selectedFilename = $('#ptkp-selected-filename');
    const $btnValidate = $('#ptkp-btn-validate');
    const $uploadError = $('#ptkp-upload-error');
    const $tableLoading = $('#ptkp-table-loading');

    // Header Action Buttons
    const $btnDownloadTemplate = $('#btn-download-ptkp-template');
    const $btnImportHistory = $('#btn-ptkp-history');

    // =========================================================================
    // CONDITIONAL HEADER BUTTON VISIBILITY
    // =========================================================================
    function updateHeaderActions(currentStep) {
        const downloadTemplate = document.getElementById('btn-download-ptkp-template') || document.getElementById('downloadTemplate');
        const importHistory = document.getElementById('btn-ptkp-history') || document.getElementById('importHistory');

        if (downloadTemplate) {
            downloadTemplate.classList.toggle('d-none', currentStep !== 1);
        }

        if (importHistory) {
            // Task 1: Do not display Import History button on Step 3 or upon successful import
            importHistory.classList.add('d-none');
        }
    }

    // =========================================================================
    // 3-STEP PROCESS STEPPER & HEADER ACTION PLACEMENT
    // =========================================================================
    function setStep(stepNumber) {
        // Step 1: Upload File
        // Step 2: Validation & Review
        // Step 3: Import Records
        // Step 4: Import Summary (Result)
        $('.ptkp-step-item').removeClass('active completed');
        $('.ptkp-step-line').removeClass('completed');

        if (stepNumber === 1) {
            $('#ptkp-step-ind-1').addClass('active');
        } else if (stepNumber === 2) {
            $('#ptkp-step-ind-1').addClass('completed');
            $('#ptkp-step-line-1').addClass('completed');
            $('#ptkp-step-ind-2').addClass('active');
        } else if (stepNumber === 3) {
            $('#ptkp-step-ind-1').addClass('completed');
            $('#ptkp-step-line-1').addClass('completed');
            $('#ptkp-step-ind-2').addClass('completed');
            $('#ptkp-step-line-2').addClass('completed');
            $('#ptkp-step-ind-3').addClass('active');
        } else if (stepNumber >= 4) {
            $('#ptkp-step-ind-1, #ptkp-step-ind-2, #ptkp-step-ind-3').addClass('completed');
            $('#ptkp-step-line-1, #ptkp-step-line-2').addClass('completed');
        }

        updateHeaderActions(stepNumber);
    }

    // Initialize Stepper at Step 1
    setStep(1);

    // =========================================================================
    // FILE SELECTION, DRAG-AND-DROP & UI FIX
    // =========================================================================
    $dropzone.on('click', function (e) {
        // Only trigger click if target is not a button inside the card
        if ($(e.target).closest('button, a').length === 0) {
            $fileInput.trigger('click');
        }
    });

    $fileInput.on('click', function (e) {
        e.stopPropagation();
    });

    $fileInput.on('change', function () {
        if (this.files && this.files[0]) {
            handleFileSelect(this.files[0]);
        }
    });

    $dropzone.on('dragover dragenter', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('dragover');
    });

    $dropzone.on('dragleave dragend drop', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');
    });

    $dropzone.on('drop', function (e) {
        const dt = e.originalEvent.dataTransfer;
        if (dt && dt.files && dt.files.length) {
            $fileInput[0].files = dt.files;
            handleFileSelect(dt.files[0]);
        }
    });

    function handleFileSelect(file) {
        $uploadError.hide().text('');
        const ext = file.name.split('.').pop().toLowerCase();
        if (['xlsx', 'xls'].indexOf(ext) === -1) {
            $uploadError.text('Invalid format. Please select an .xlsx or .xls spreadsheet.').show();
            resetFileSelection();
            return;
        }
        if (file.size > 20 * 1024 * 1024) {
            $uploadError.text('File size exceeds maximum allowed limit of 20MB.').show();
            resetFileSelection();
            return;
        }

        selectedFile = file;

        // Visual fix: Hide initial state, show file info card
        $dropzoneInitial.hide();
        $fileInfoCard.show();
        $cardFilename.text(file.name);
        $cardFilesize.text(formatBytes(file.size));
        $selectedFilename.html('<strong>' + escapeHtml(file.name) + '</strong> (' + formatBytes(file.size) + ')');

        $btnValidate.prop('disabled', false);
    }

    function resetFileSelection() {
        selectedFile = null;
        $fileInput.val('');
        $dropzoneInitial.show();
        $fileInfoCard.hide();
        $selectedFilename.html('<i class="ti ti-paperclip me-1"></i>No file selected');
        $btnValidate.prop('disabled', true);
        $uploadError.hide().text('');
    }

    // Remove / Change File buttons
    $('#ptkp-btn-remove-file').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        resetFileSelection();
    });

    $('#ptkp-btn-change-file').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $fileInput.trigger('click');
    });

    // =========================================================================
    // STEP 1: VALIDATE ACTION (LOADING STATE, NOT A STEPPER STAGE)
    // =========================================================================
    $btnValidate.on('click', function () {
        if (!selectedFile) return;

        const formData = new FormData();
        formData.append('file', selectedFile);
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

        $uploadCard.hide();
        $loadingCard.show();
        $('#ptkp-loading-title').text('Validating Spreadsheet...');
        $('#ptkp-loading-subtitle').text('Reading PTKP columns, verifying Employee IDs, and checking duplicates...');

        $.ajax({
            url: window.ptkpRoutes.preview,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                $loadingCard.hide();
                if (res.success) {
                    importToken = res.token;
                    allRows = res.rows || [];
                    renderPreviewStage(res);
                    setStep(2);
                    updateHeaderActions(2);
                } else {
                    $uploadCard.show();
                    setStep(1);
                    updateHeaderActions(1);
                    $uploadError.text(res.message || 'Validation failed.').show();
                }
            },
            error: function (xhr) {
                $loadingCard.hide();
                $uploadCard.show();
                setStep(1);
                updateHeaderActions(1);
                $uploadError.text((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to process spreadsheet file.').show();
            }
        });
    });

    // =========================================================================
    // STEP 2: RENDER PREVIEW & REVIEW DATA
    // =========================================================================
    function renderPreviewStage(data) {
        $previewCard.show();
        $('#ptkp-preview-filename').text(data.filename || (selectedFile && selectedFile.name) || 'Spreadsheet');
        const stats = data.stats || {};

        const totalRowsCount = (stats.total_rows !== undefined && stats.total_rows !== null) ? stats.total_rows : allRows.length;
        $('#ptkp-kpi-total').text(totalRowsCount);
        $('#ptkp-kpi-valid').text(stats.valid || 0);
        $('#ptkp-kpi-warning').text(stats.warning || 0);
        $('#ptkp-kpi-duplicate').text(stats.duplicate || 0);
        $('#ptkp-kpi-invalid').text(stats.invalid || 0);
        const kpiInvalidCompany = (stats.invalid_company !== undefined ? stats.invalid_company : (stats.invalid_company_count || 0));
        $('#ptkp-kpi-invalid-company').text(kpiInvalidCompany);

        // Sub-breakdown for Invalid ID vs Invalid Course Title (without double counting the total)
        let countInvalidId = 0;
        let countInvalidCourse = 0;
        allRows.forEach(function (r) {
            if (r.status === 'INVALID') {
                if (isRowInvalidId(r)) countInvalidId++;
                if (isRowInvalidCourse(r)) countInvalidCourse++;
            }
        });
        const finalIdCount = (stats.invalid_id_count !== undefined) ? stats.invalid_id_count : countInvalidId;
        const finalCourseCount = (stats.invalid_course_count !== undefined) ? stats.invalid_course_count : countInvalidCourse;
        $('#ptkp-kpi-invalid-id-count').text(finalIdCount);
        $('#ptkp-kpi-invalid-course-count').text(finalCourseCount);
        if ((stats.invalid || 0) > 0) {
            $('#ptkp-kpi-invalid-subinfo').show();
        } else {
            $('#ptkp-kpi-invalid-subinfo').hide();
        }

        // Pre-populate Step 3 values
        const validCount = stats.valid || 0;
        const warningCount = stats.warning || 0;
        const invalidCompanyCount = (stats.invalid_company !== undefined ? stats.invalid_company : (stats.invalid_company_count || 0));
        const invalidCount = stats.invalid || 0;
        const duplicateCount = stats.duplicate || 0;

        // Group A: Records to Be Imported
        const totalToImport = data.importable_count !== undefined 
            ? data.importable_count 
            : (validCount + warningCount + invalidCompanyCount);
        $('#ptkp-step3-valid-count').text(validCount.toLocaleString());
        $('#ptkp-step3-warning-count').text(warningCount.toLocaleString());
        if (invalidCompanyCount > 0) {
            $('#ptkp-step3-invalid-company-count').text(invalidCompanyCount.toLocaleString());
            $('#ptkp-step3-invalid-company-eligible-row').show();
        } else {
            $('#ptkp-step3-invalid-company-eligible-row').hide();
        }
        $('#ptkp-step3-total-to-import').text(totalToImport.toLocaleString() + ' Records');

        // Group B: Records Not to Be Imported (Skipped)
        const invalidCompanySkipped = 0; // In OWL, invalid company is a non-blocking warning and is imported
        const totalNotImported = invalidCount + duplicateCount + invalidCompanySkipped;
        $('#ptkp-step3-invalid-count').text(invalidCount.toLocaleString());
        $('#ptkp-step3-invalid-company-skipped').text(invalidCompanySkipped.toLocaleString());
        $('#ptkp-step3-duplicate-count').text(duplicateCount.toLocaleString());
        $('#ptkp-step3-total-not-imported').text(totalNotImported.toLocaleString() + ' Records');

        // Reset filter state to Total Rows (all)
        currentFilter = 'all';
        $('.ptkp-summary-filter-card').removeClass('active');
        $('.ptkp-summary-filter-card[data-filter="all"]').addClass('active');

        // Reset search queries
        $('#ptkp-search-empid, #ptkp-search-name, #ptkp-search-course, #ptkp-search-pic').val('');
        searchQuery = { empId: '', name: '', course: '', pic: '' };
        currentPage = 1;

        // Header diagnostic check
        const headerCheck = data.header_check || {};
        if (headerCheck.issues && headerCheck.issues.length > 0) {
            let html = '<ul class="mb-0 ps-3">';
            headerCheck.issues.forEach((iss) => html += '<li>' + escapeHtml(iss.message) + '</li>');
            $('#ptkp-header-alert-body').html(html + '</ul>');
            $('#ptkp-header-alert').show();
        } else {
            $('#ptkp-header-alert').hide();
        }

        const importable = data.importable_count || 0;
        const total = (data.stats && data.stats.total_rows) || allRows.length;
        const duplicates = (data.stats && data.stats.duplicate) || 0;
        $('#ptkp-btn-confirm-count').text(importable);

        if (total > 0 && importable === 0 && duplicates > 0) {
            $('#ptkp-no-new-records-alert').show();
            $('#ptkp-no-new-records-desc').text('All records already exist in the database. No new records will be imported.');
            $('#ptkp-btn-next-to-step3').prop('disabled', true);
            $('#ptkp-btn-confirm').prop('disabled', true);
        } else if (importable <= 0) {
            $('#ptkp-no-new-records-alert').hide();
            $('#ptkp-btn-next-to-step3').prop('disabled', true);
            $('#ptkp-btn-confirm').prop('disabled', true);
        } else {
            $('#ptkp-no-new-records-alert').hide();
            $('#ptkp-btn-next-to-step3').prop('disabled', false);
            $('#ptkp-btn-confirm').prop('disabled', false);
        }

        applyFilters();
        initTooltips();
    }

    let tooltipHideTimer = null;

    // Initialize tooltips on page elements using rich HTML definitions
    function initTooltips() {
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('.ptkp-info-icon[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                const existing = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
                if (existing) {
                    existing.dispose();
                }
                const targetId = tooltipTriggerEl.getAttribute('data-tooltip-id');
                const contentEl = targetId ? document.getElementById(targetId) : null;
                const contentHtml = contentEl ? contentEl.innerHTML : (tooltipTriggerEl.getAttribute('title') || '');

                let customClass = tooltipTriggerEl.getAttribute('data-bs-custom-class') || 'ptkp-status-tooltip';
                const validationIds = [
                    'ptkp-tt-content-total',
                    'ptkp-tt-content-valid',
                    'ptkp-tt-content-warning',
                    'ptkp-tt-content-duplicate',
                    'ptkp-tt-content-invalid',
                    'ptkp-tt-content-invalid-company'
                ];
                if (validationIds.includes(targetId) && !customClass.includes('ptkp-validation-tooltip')) {
                    customClass += ' ptkp-validation-tooltip';
                }

                new bootstrap.Tooltip(tooltipTriggerEl, {
                    container: 'body',
                    boundary: 'window',
                    html: true,
                    title: contentHtml,
                    customClass: customClass,
                    trigger: 'manual',
                    fallbackPlacements: ['bottom', 'right', 'left']
                });
            });
        }
    }

    $(document).ready(function () {
        initTooltips();
    });

    // Helper to dismiss all active status tooltips
    function hideAllStatusTooltips() {
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            $('.ptkp-info-icon[data-bs-toggle="tooltip"]').each(function () {
                const instance = bootstrap.Tooltip.getInstance(this);
                if (instance) {
                    instance.hide();
                }
            });
        }
    }

    // Summary Card Click Filter Handler
    $(document).on('click', '.ptkp-summary-filter-card', function (e) {
        // Prevent filter toggle if user clicked on tooltip/info icon
        if ($(e.target).closest('.ptkp-info-icon, .tooltip').length > 0) {
            return;
        }

        // Hide all status tooltips when card filter is executed
        hideAllStatusTooltips();

        if (isFiltering) return;
        const newFilter = $(this).data('filter');
        if (newFilter === currentFilter) return;

        isFiltering = true;
        $('.ptkp-summary-filter-card').removeClass('active');
        $(this).addClass('active');
        currentFilter = newFilter;
        currentPage = 1;

        $tableLoading.stop(true, true).fadeIn(100);

        setTimeout(function () {
            try {
                applyFilters();
            } catch (err) {
                console.error('Filter execution failed:', err);
            } finally {
                $tableLoading.stop(true, true).fadeOut(120);
                isFiltering = false;
            }
        }, 120);
    });

    // Sub-pill filter click handler (Inside Invalid ID & Course Title card)
    $(document).on('click', '.ptkp-sub-pill', function (e) {
        e.preventDefault();
        e.stopPropagation();
        hideAllStatusTooltips();
        const subfilter = $(this).data('subfilter');
        $('.ptkp-summary-filter-card').removeClass('active');
        $('.ptkp-kpi-invalid').addClass('active');
        currentFilter = subfilter;
        currentPage = 1;
        $tableLoading.stop(true, true).fadeIn(100);
        setTimeout(function () {
            applyFilters();
            $tableLoading.stop(true, true).fadeOut(120);
        }, 120);
    });

    // Keyboard support for Summary Cards (Enter / Space)
    $(document).on('keydown', '.ptkp-summary-filter-card', function (e) {
        if ($(e.target).closest('.ptkp-info-icon').length > 0) {
            return;
        }
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            $(this).trigger('click');
        }
    });

    // Desktop hover: Hovering summary card shows detailed status explanation
    $(document).on('mouseenter', '.ptkp-summary-filter-card', function () {
        if (tooltipHideTimer) {
            clearTimeout(tooltipHideTimer);
            tooltipHideTimer = null;
        }
        const iconEl = $(this).find('.ptkp-info-icon')[0];
        if (iconEl && typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            // Dismiss other cards' tooltips
            $('.ptkp-info-icon').each(function () {
                if (this !== iconEl) {
                    const other = bootstrap.Tooltip.getInstance(this);
                    if (other) other.hide();
                }
            });
            const instance = bootstrap.Tooltip.getInstance(iconEl);
            if (instance) {
                instance.show();
            }
        }
    });

    $(document).on('mouseleave', '.ptkp-summary-filter-card', function () {
        const iconEl = $(this).find('.ptkp-info-icon')[0];
        if (iconEl && typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            tooltipHideTimer = setTimeout(function () {
                if (!$('.tooltip:hover').length && !$(iconEl).closest('.ptkp-summary-filter-card').is(':hover')) {
                    const instance = bootstrap.Tooltip.getInstance(iconEl);
                    if (instance) {
                        instance.hide();
                    }
                }
            }, 120);
        }
    });

    // Keep tooltip visible when cursor moves inside the tooltip popup to read or select text
    $(document).on('mouseenter', '.tooltip', function () {
        if (tooltipHideTimer) {
            clearTimeout(tooltipHideTimer);
            tooltipHideTimer = null;
        }
    });

    $(document).on('mouseleave', '.tooltip', function () {
        tooltipHideTimer = setTimeout(function () {
            if (!$('.ptkp-summary-filter-card:hover').length) {
                hideAllStatusTooltips();
            }
        }, 120);
    });

    // Keyboard focus accessibility for info icon
    $(document).on('focus', '.ptkp-info-icon', function () {
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            const instance = bootstrap.Tooltip.getInstance(this);
            if (instance) instance.show();
        }
    });

    $(document).on('blur', '.ptkp-info-icon', function () {
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            const instance = bootstrap.Tooltip.getInstance(this);
            if (instance) instance.hide();
        }
    });

    // Independent interaction for info icon (stops propagation so card filter does not trigger)
    $(document).on('click', '.ptkp-info-icon', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            const instance = bootstrap.Tooltip.getInstance(this);
            if (instance) {
                const tip = instance.getTipElement ? instance.getTipElement() : null;
                const isShown = tip && tip.classList.contains('show');
                // Hide other tooltips
                $('.ptkp-info-icon').each(function () {
                    if (this !== e.currentTarget) {
                        const other = bootstrap.Tooltip.getInstance(this);
                        if (other) other.hide();
                    }
                });
                if (isShown) {
                    instance.hide();
                } else {
                    instance.show();
                }
            }
        }
    });

    // Dismiss active tooltips when clicking anywhere outside
    $(document).on('click', function (e) {
        if ($(e.target).closest('.ptkp-info-icon, .tooltip, .ptkp-summary-filter-card').length === 0) {
            hideAllStatusTooltips();
        }
    });

    $(document).on('keydown', '.ptkp-info-icon', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            e.stopPropagation();
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                const tooltipInstance = bootstrap.Tooltip.getInstance(this);
                if (tooltipInstance) {
                    const tip = tooltipInstance.getTipElement ? tooltipInstance.getTipElement() : null;
                    const isShown = tip && tip.classList.contains('show');
                    if (isShown) {
                        tooltipInstance.hide();
                    } else {
                        tooltipInstance.show();
                    }
                }
            }
        }
    });

    // Reset Filters Button Handler
    $(document).on('click', '#ptkp-btn-reset-filters', function () {
        $('#ptkp-search-empid, #ptkp-search-name, #ptkp-search-course, #ptkp-search-pic').val('');
        searchQuery = { empId: '', name: '', course: '', pic: '' };
        currentPage = 1;

        if (currentFilter !== 'all') {
            $('.ptkp-summary-filter-card[data-filter="all"]').trigger('click');
        } else {
            $tableLoading.stop(true, true).fadeIn(80);
            setTimeout(function () {
                applyFilters();
                $tableLoading.stop(true, true).fadeOut(100);
            }, 80);
        }
    });

    // Debounced search field inputs
    let searchDebounceTimer = null;
    $('#ptkp-search-empid, #ptkp-search-name, #ptkp-search-course, #ptkp-search-pic').on('input', function () {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(function () {
            searchQuery.empId = $('#ptkp-search-empid').val().trim().toLowerCase();
            searchQuery.name = $('#ptkp-search-name').val().trim().toLowerCase();
            searchQuery.course = $('#ptkp-search-course').val().trim().toLowerCase();
            searchQuery.pic = $('#ptkp-search-pic').val().trim().toLowerCase();
            currentPage = 1;
            applyFilters();
        }, 150);
    });

    $('#ptkp-page-size').on('change', function () {
        pageSize = parseInt($(this).val(), 10) || 25;
        currentPage = 1;
        renderTableRows();
    });

    function isRowInvalidId(row) {
        if (!row) return false;
        if (typeof row.is_invalid_id === 'boolean') {
            return row.is_invalid_id;
        }
        if (row.invalid_type && row.invalid_type.indexOf('INVALID_ID') !== -1) {
            return true;
        }
        if (row.status !== 'INVALID') return false;
        const empId = row.employee_id !== undefined && row.employee_id !== null ? String(row.employee_id).trim() : '';
        if (empId === '') return true;
        if (row.messages && Array.isArray(row.messages)) {
            for (let i = 0; i < row.messages.length; i++) {
                const m = String(row.messages[i]).toLowerCase();
                if (m.indexOf('employee id is empty') !== -1 ||
                    m.indexOf('employee id is required') !== -1 ||
                    m.indexOf('was not found in lms') !== -1 ||
                    m.indexOf('not found or ambiguous in lms') !== -1 ||
                    m.indexOf('matches multiple users') !== -1 ||
                    m.indexOf('ambiguous identifier') !== -1) {
                    return true;
                }
            }
        }
        return false;
    }

    function isRowInvalidCourse(row) {
        if (!row) return false;
        if (typeof row.is_invalid_course === 'boolean') {
            return row.is_invalid_course;
        }
        if (row.invalid_type && row.invalid_type.indexOf('INVALID_COURSE') !== -1) {
            return true;
        }
        if (row.status !== 'INVALID') return false;
        const course = row.course_name !== undefined && row.course_name !== null ? String(row.course_name).trim() : '';
        if (course === '' || course === '-') return true;
        if (row.messages && Array.isArray(row.messages)) {
            for (let i = 0; i < row.messages.length; i++) {
                const m = String(row.messages[i]).toLowerCase();
                if (m.indexOf('course title is required') !== -1 ||
                    m.indexOf('course title cannot be empty') !== -1 ||
                    m.indexOf('course title not found') !== -1 ||
                    m.indexOf('ambiguous in offline trainings') !== -1) {
                    return true;
                }
            }
        }
        return false;
    }

    function isRowInvalidCompany(row) {
        if (!row) return false;
        if (typeof row.is_invalid_company === 'boolean') {
            return row.is_invalid_company;
        }
        if (row.status === 'INVALID COMPANY') return true;
        if (row.company_mismatch === true) return true;
        if (row.invalid_type && row.invalid_type.indexOf('COMPANY') !== -1) {
            return true;
        }
        const comp = row.company !== undefined && row.company !== null ? String(row.company).trim() : '';
        if (comp === '' || comp === '-' || comp === '—' || comp === '–') return true;
        const clean = comp.toLowerCase().replace(/[.,]/g, '').replace(/\s+/g, ' ');
        if (clean !== 'pt krakatau posco') return true;

        if (row.messages && Array.isArray(row.messages)) {
            for (let i = 0; i < row.messages.length; i++) {
                const m = String(row.messages[i]).toLowerCase();
                if (m.indexOf('company') !== -1 && (m.indexOf('invalid') !== -1 || m.indexOf('mismatch') !== -1 || m.indexOf('krakatau posco') !== -1)) {
                    return true;
                }
            }
        }
        return false;
    }

    function applyFilters() {
        filteredRows = allRows.filter(function (row) {
            if (currentFilter !== 'all') {
                if (currentFilter === 'valid' && row.status !== 'VALID') return false;
                if (currentFilter === 'warning' && row.status !== 'WARNING') return false;
                if (currentFilter === 'duplicate' && row.status !== 'DUPLICATE') return false;
                if (currentFilter === 'invalid' && row.status !== 'INVALID') return false;
                if (currentFilter === 'invalid_id') {
                    if (row.status !== 'INVALID' || !isRowInvalidId(row)) return false;
                }
                if (currentFilter === 'invalid_course') {
                    if (row.status !== 'INVALID' || !isRowInvalidCourse(row)) return false;
                }
                if (currentFilter === 'invalid_company') {
                    if (row.status !== 'INVALID COMPANY' && !isRowInvalidCompany(row)) return false;
                }
            }
            if (searchQuery.empId && (row.employee_id || '').toLowerCase().indexOf(searchQuery.empId) === -1) return false;
            if (searchQuery.name && (row.name || '').toLowerCase().indexOf(searchQuery.name) === -1) return false;
            if (searchQuery.course && (row.course_name || '').toLowerCase().indexOf(searchQuery.course) === -1) return false;
            if (searchQuery.pic && (row.pic || '').toLowerCase().indexOf(searchQuery.pic) === -1) return false;
            return true;
        });
        renderTableRows();
    }

    function renderTableRows() {
        const total = filteredRows.length;
        const totalPages = Math.ceil(total / pageSize) || 1;
        if (currentPage > totalPages) currentPage = totalPages;

        const startIdx = (currentPage - 1) * pageSize;
        const endIdx = Math.min(startIdx + pageSize, total);
        const pageItems = filteredRows.slice(startIdx, endIdx);

        const $tbody = $('#ptkp-preview-tbody');
        $tbody.empty();

        if (pageItems.length === 0) {
            $tbody.append('<tr><td colspan="10" class="text-center py-4 text-muted"><i class="ti ti-inbox fs-2 d-block mb-1 text-secondary"></i>No records match the active filter criteria.</td></tr>');
        } else {
            pageItems.forEach(function (row, idx) {
                const globalIndex = startIdx + idx;
                let msgsHtml = '';

                // Explicitly format Validation Details strictly according to row.status
                if (row.status === 'VALID') {
                    msgsHtml = '<div class="ptkp-row-msg text-muted" style="font-size: 10.5px;"><i class="ti ti-check text-success me-1"></i>Row is valid and ready for import.</div>';
                } else if (row.status === 'WARNING') {
                    const warningReasons = (row.warning_reasons && row.warning_reasons.length > 0)
                        ? row.warning_reasons
                        : (row.empty_warning_fields && row.empty_warning_fields.length > 0)
                            ? row.empty_warning_fields.map(function (f) { return f + ' is empty'; })
                            : (row.warnings && row.warnings.length > 0 ? row.warnings : []);

                    if (warningReasons.length > 0) {
                        const rawText = warningReasons.join('; ');
                        const warningText = rawText.indexOf('WARNING:') === 0 ? rawText : ('WARNING: ' + rawText);
                        msgsHtml = '<div class="ptkp-row-msg text-warning fw-semibold" title="' + escapeHtml(warningText) + '">' +
                            '<i class="ti ti-alert-triangle me-1"></i>' + escapeHtml(warningText) +
                            '</div>';
                    } else if (row.messages && row.messages.length) {
                        msgsHtml = '<div class="ptkp-row-msg text-warning fw-semibold"><i class="ti ti-alert-triangle me-1"></i>' + escapeHtml(row.messages[0]) + '</div>';
                    } else {
                        msgsHtml = '<div class="ptkp-row-msg text-warning fw-semibold"><i class="ti ti-alert-triangle me-1"></i>Non-blocking warning detected.</div>';
                    }

                    if (row.company_mismatch) {
                        const companyMismatchMsg = (row.messages || []).find(function (m) {
                            return m.toLowerCase().indexOf('company mismatch') !== -1;
                        }) || 'Company mismatch detected.';
                        msgsHtml += '<div class="ptkp-row-msg text-muted mt-0.5" style="font-size: 10px;" title="' + escapeHtml(companyMismatchMsg) + '"><i class="ti ti-building me-1"></i>' + escapeHtml(companyMismatchMsg) + '</div>';
                    }
                } else if (row.status === 'INVALID') {
                    // Mandatory blocking errors (Strictly and exclusively Employee ID and Course Title)
                    let blockingErrors = [];
                    if (row.id_errors && Array.isArray(row.id_errors) && row.id_errors.length > 0) {
                        blockingErrors = blockingErrors.concat(row.id_errors);
                    }
                    if (row.course_errors && Array.isArray(row.course_errors) && row.course_errors.length > 0) {
                        blockingErrors = blockingErrors.concat(row.course_errors);
                    }
                    if (blockingErrors.length === 0 && row.blocking_errors && Array.isArray(row.blocking_errors) && row.blocking_errors.length > 0) {
                        blockingErrors = row.blocking_errors.filter(function (m) {
                            const lower = String(m).toLowerCase();
                            return lower.indexOf('warning:') === -1 && lower.indexOf('company mismatch') === -1;
                        });
                    }
                    if (blockingErrors.length === 0 && row.errors && Array.isArray(row.errors) && row.errors.length > 0) {
                        blockingErrors = row.errors.filter(function (m) {
                            const lower = String(m).toLowerCase();
                            return lower.indexOf('warning:') === -1 && lower.indexOf('company mismatch') === -1;
                        });
                    }
                    if (blockingErrors.length === 0 && row.messages && Array.isArray(row.messages)) {
                        blockingErrors = row.messages.filter(function (m) {
                            const lower = String(m).toLowerCase();
                            return (lower.indexOf('employee id') !== -1 || lower.indexOf('course title') !== -1 || lower.indexOf('course name') !== -1)
                                && lower.indexOf('warning:') === -1 && lower.indexOf('company mismatch') === -1 && lower.indexOf('ready for import') === -1;
                        });
                    }
                    if (blockingErrors.length === 0) {
                        blockingErrors = ['Validation failed for Employee ID or Course Title.'];
                    }

                    const primaryError = blockingErrors[0];
                    msgsHtml = '<div class="ptkp-row-msg text-danger fw-semibold" title="' + escapeHtml(primaryError) + '"><i class="ti ti-alert-circle me-1"></i>' + escapeHtml(primaryError);
                    if (blockingErrors.length > 1) {
                        msgsHtml += ' <span class="badge bg-light text-secondary border px-1" style="font-size: 9.5px;">+' + (blockingErrors.length - 1) + '</span>';
                        msgsHtml += '<div class="text-muted mt-0.5 fw-normal" style="font-size: 10px;">' + escapeHtml(blockingErrors.slice(1).join('; ')) + '</div>';
                    }
                    msgsHtml += '</div>';

                    if (row.company_mismatch || isRowInvalidCompany(row)) {
                        const compMismatch = (row.messages || []).find(function (m) {
                            return m.toLowerCase().indexOf('company mismatch') !== -1 || m.toLowerCase().indexOf('non ptkp') !== -1;
                        }) || 'Company mismatch detected.';
                        msgsHtml += '<div class="ptkp-row-msg text-muted mt-0.5" style="font-size: 10px;" title="' + escapeHtml(compMismatch) + '"><i class="ti ti-building me-1"></i>' + escapeHtml(compMismatch) + '</div>';
                    }
                } else if (row.status === 'DUPLICATE') {
                    const dupMsg = (row.messages || []).find(function (m) {
                        return m.toLowerCase().indexOf('duplicate') !== -1;
                    }) || (row.duplicate_reason || 'Duplicate record detected and will be skipped.');
                    msgsHtml = '<div class="ptkp-row-msg text-purple fw-semibold" style="color: #6b21a8;" title="' + escapeHtml(dupMsg) + '">' +
                        '<i class="ti ti-copy me-1"></i>' + escapeHtml(dupMsg) +
                        '</div>';
                } else if (row.status === 'INVALID COMPANY') {
                    const companyMsg = (row.messages || []).find(function (m) {
                        return m.toLowerCase().indexOf('company mismatch') !== -1 || m.toLowerCase().indexOf('non ptkp') !== -1;
                    }) || 'Company mismatch detected.';
                    msgsHtml = '<div class="ptkp-row-msg text-warning fw-semibold" style="color: #b45309;" title="' + escapeHtml(companyMsg) + '">' +
                        '<i class="ti ti-building me-1"></i>' + escapeHtml(companyMsg) +
                        '</div>';
                    if (row.warning_reasons && row.warning_reasons.length > 0) {
                        msgsHtml += '<div class="text-muted mt-0.5 fw-normal" style="font-size: 10px;" title="' + escapeHtml(row.warning_reasons.join('; ')) + '">' + escapeHtml(row.warning_reasons.join('; ')) + '</div>';
                    }
                } else {
                    msgsHtml = '<div class="ptkp-row-msg text-muted">' + escapeHtml(row.status || '-') + '</div>';
                }

                const rowData = row.data || {};
                $tbody.append(`
                    <tr class="${getRowClass(row.status)}">
                        <td class="text-center fw-medium text-muted">${row.no || (globalIndex + 1)}</td>
                        <td>${getStatusBadge(row.status, row)}</td>
                        <td class="ptkp-cell-code">${escapeHtml(row.employee_id || '-')}</td>
                        <td>
                            <div class="ptkp-cell-title">${escapeHtml(row.name || '-')}</div>
                            <div class="ptkp-cell-subtext">${escapeHtml(rowData.job_position || '-')}</div>
                        </td>
                        <td><span class="ptkp-cell-subtext" style="color: inherit;">${escapeHtml(row.company || '-')}</span></td>
                        <td>
                            <div class="ptkp-cell-title">${escapeHtml(row.course_name || '-')}</div>
                            <div class="ptkp-cell-subtext">${[escapeHtml(rowData.batch || ''), escapeHtml(formatDateDisplay(rowData.start_date_display || rowData.start_date))].filter(Boolean).join(' &bull; ')}</div>
                        </td>
                        <td><span class="ptkp-badge-type">${escapeHtml(row.type || '-')}</span></td>
                        <td><span class="ptkp-cell-subtext">${escapeHtml(row.pic || '-')}</span></td>
                        <td>${msgsHtml || '-'}</td>
                        <td class="text-center">
                            <button type="button" class="ptkp-btn-inspect btn-inspect-row" data-row-idx="${globalIndex}" title="Inspect Row">
                                <i class="ti ti-eye"></i>
                            </button>
                        </td>
                    </tr>
                `);
            });
        }

        const filterLabel = currentFilter !== 'all' ? ` [Filter: ${currentFilter.toUpperCase().replace('_', ' ')}]` : '';
        $('#ptkp-pagination-info').text(total > 0 ? `Showing ${startIdx + 1} to ${endIdx} of ${total} records${filterLabel} (Total file rows: ${allRows.length})` : `No records found (Total file rows: ${allRows.length})`);
        renderPaginationControls(totalPages);
    }

    function getStatusBadge(status, row) {
        if (typeof status === 'object' && status !== null) {
            row = status;
            status = row.status;
        }
        switch (status) {
            case 'VALID':
                return '<span class="badge badge-ptkp-valid"><i class="ti ti-check me-1"></i>VALID</span>';
            case 'WARNING':
                return '<span class="badge badge-ptkp-warning"><i class="ti ti-alert-triangle me-1"></i>WARNING</span>';
            case 'DUPLICATE': {
                let badge = '<span class="badge badge-ptkp-duplicate"><i class="ti ti-copy me-1"></i>DUPLICATE</span>';
                if (row && row.duplicate_source) {
                    if (row.duplicate_source === 'database' && row.matched_record_id) {
                        badge += `<div class="mt-0.5" style="font-size: 9.5px;"><span class="badge bg-light text-secondary border px-1" style="font-size: 9.5px;">Database (ID ${escapeHtml(String(row.matched_record_id))}) &bull; 40 cols</span></div>`;
                    } else if (row.duplicate_source === 'file' && row.original_excel_row) {
                        badge += `<div class="mt-0.5" style="font-size: 9.5px;"><span class="badge bg-light text-secondary border px-1" style="font-size: 9.5px;">Excel (Row ${escapeHtml(String(row.original_excel_row))}) &bull; 42 cols</span></div>`;
                    }
                }
                return badge;
            }
            case 'INVALID': {
                const hasEmpErr = isRowInvalidId(row);
                const hasCourseErr = isRowInvalidCourse(row);
                const hasCompanyErr = isRowInvalidCompany(row);

                const badges = [];
                if (hasEmpErr) {
                    badges.push('<span class="badge badge-ptkp-invalid-id"><i class="ti ti-user-x me-1"></i>INVALID ID</span>');
                }
                if (hasCourseErr) {
                    badges.push('<span class="badge badge-ptkp-invalid-course"><i class="ti ti-book-off me-1"></i>INVALID COURSE TITLE</span>');
                }
                if (hasCompanyErr) {
                    badges.push('<span class="badge badge-ptkp-invalid-company"><i class="ti ti-building me-1"></i>INVALID COMPANY</span>');
                }

                if (badges.length > 1) {
                    return '<div class="d-flex flex-column gap-1 align-items-start">' + badges.join('') + '</div>';
                } else if (badges.length === 1) {
                    return badges[0];
                } else {
                    return '<span class="badge badge-ptkp-invalid"><i class="ti ti-x me-1"></i>INVALID</span>';
                }
            }
            case 'INVALID COMPANY':
                return '<span class="badge badge-ptkp-invalid-company"><i class="ti ti-building me-1"></i>INVALID COMPANY</span>';
            default:
                return `<span class="badge bg-secondary">${escapeHtml(status || '')}</span>`;
        }
    }

    function getRowClass(status) {
        switch (status) {
            case 'VALID': return 'row-valid';
            case 'WARNING': return 'row-warning';
            case 'DUPLICATE': return 'row-duplicate';
            case 'INVALID': return 'row-invalid';
            case 'INVALID COMPANY': return 'row-invalid-company';
            default: return '';
        }
    }

    function renderPaginationControls(totalPages) {
        const $ul = $('#ptkp-pagination-nav');
        $ul.empty();
        if (totalPages <= 1) return;

        $ul.append(`<li class="page-item ${currentPage === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">&laquo;</a></li>`);
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, currentPage + 2);

        if (startPage > 1) {
            $ul.append(`<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`);
            if (startPage > 2) $ul.append(`<li class="page-item disabled"><span class="page-link">&hellip;</span></li>`);
        }

        for (let p = startPage; p <= endPage; p++) {
            $ul.append(`<li class="page-item ${p === currentPage ? 'active' : ''}"><a class="page-link" href="#" data-page="${p}">${p}</a></li>`);
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) $ul.append(`<li class="page-item disabled"><span class="page-link">&hellip;</span></li>`);
            $ul.append(`<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`);
        }

        $ul.append(`<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">&raquo;</a></li>`);

        $ul.find('a.page-link').on('click', function (e) {
            e.preventDefault();
            const p = parseInt($(this).data('page'), 10);
            if (p >= 1 && p <= totalPages && p !== currentPage) {
                currentPage = p;
                renderTableRows();
            }
        });
    }

    // Row Inspection
    $(document).on('click', '.btn-inspect-row', function () {
        const idx = $(this).data('row-idx');
        const row = filteredRows[idx];
        if (!row) return;

        $('#modal-inspect-row-num').text(row.excel_row);
        $('#modal-inspect-status').html(getStatusBadge(row.status, row));

        let msgsHtml = '';

        if (row.status === 'VALID') {
            msgsHtml = `
                <div class="alert alert-success border-0 rounded-3 small mb-3 py-2 px-3" style="background-color: #f0fdf4; border: 1px solid #bbf7d0 !important; color: #166534;">
                    <div class="d-flex align-items-center gap-2 fw-semibold">
                        <i class="ti ti-check fs-4 text-success"></i>
                        <span>Row is valid and ready for import. All mandatory requirements and template checks passed.</span>
                    </div>
                </div>
            `;
        } else if (row.status === 'DUPLICATE') {
            const isDb = row.duplicate_source === 'database';
            const dupSourceLabel = isDb
                ? `Existing Database (Participant ID: ${escapeHtml(String(row.matched_record_id || '-'))})`
                : `Uploaded Excel (Original Row: ${escapeHtml(String(row.original_excel_row || '-'))})`;
            const dupMatchBadge = isDb
                ? '<span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-0">DATABASE_40_COLUMN_MATCH</span>'
                : '<span class="badge bg-purple-subtle text-purple border border-purple-subtle px-2 py-0">FULL_42_COLUMN_MATCH</span>';
            const dupExplanation = isDb
                ? 'Matches an existing database record across all 40 physically stored columns. (Note: <code>progress_status</code> and <code>month</code> are unverified as they are not stored in the database).'
                : `Matches an identical record in the uploaded file (Row ${escapeHtml(String(row.original_excel_row || '-'))}) across all 42 canonical columns.`;

            msgsHtml += `
                <div class="alert alert-secondary border-0 rounded-3 small mb-3 py-2 px-3" style="background-color: #faf5ff; border: 1px solid #e9d5ff !important; color: #6b21a8;">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div class="d-flex align-items-center gap-2 fw-bold">
                            <i class="ti ti-copy fs-2"></i>
                            <span>Duplicate Detection Alert</span>
                        </div>
                        <div>${dupMatchBadge}</div>
                    </div>
                    <div class="mb-2">${dupExplanation}</div>
                    <div class="pt-2 border-top border-purple-subtle extra-small" style="font-size: 0.8rem;">
                        <div><strong>Verification Level:</strong> ${isDb ? 'Partial Database Verification (40 of 42 columns)' : 'Full Row Match (42 of 42 columns)'}</div>
                        <div><strong>Duplicate Source:</strong> ${dupSourceLabel}</div>
                        ${row.matched_record_id ? `<div><strong>Matched Record ID:</strong> ${escapeHtml(String(row.matched_record_id))}</div>` : ''}
                        ${row.original_excel_row ? `<div><strong>Original Excel Row:</strong> Row ${escapeHtml(String(row.original_excel_row))}</div>` : ''}
                    </div>
                </div>
            `;
        } else if (row.status === 'INVALID') {
            // Blocking validation errors (Strictly and exclusively Employee ID and Course Title - NEVER format or warning strings!)
            let blockingErrors = [];
            if (row.id_errors && Array.isArray(row.id_errors) && row.id_errors.length > 0) {
                blockingErrors = blockingErrors.concat(row.id_errors);
            }
            if (row.course_errors && Array.isArray(row.course_errors) && row.course_errors.length > 0) {
                blockingErrors = blockingErrors.concat(row.course_errors);
            }
            if (blockingErrors.length === 0 && row.blocking_errors && Array.isArray(row.blocking_errors) && row.blocking_errors.length > 0) {
                blockingErrors = row.blocking_errors.filter(function (m) {
                    const lower = String(m).toLowerCase();
                    return lower.indexOf('warning:') === -1 && lower.indexOf('company mismatch') === -1;
                });
            }
            if (blockingErrors.length === 0 && row.errors && Array.isArray(row.errors) && row.errors.length > 0) {
                blockingErrors = row.errors.filter(function (m) {
                    const lower = String(m).toLowerCase();
                    return lower.indexOf('warning:') === -1 && lower.indexOf('company mismatch') === -1;
                });
            }
            if (blockingErrors.length === 0 && row.messages && Array.isArray(row.messages)) {
                blockingErrors = row.messages.filter(function (m) {
                    const lower = String(m).toLowerCase();
                    return (lower.indexOf('employee id') !== -1 || lower.indexOf('course title') !== -1 || lower.indexOf('course name') !== -1)
                        && lower.indexOf('warning:') === -1 && lower.indexOf('company mismatch') === -1 && lower.indexOf('ready for import') === -1;
                });
            }
            if (blockingErrors.length === 0) {
                blockingErrors = ['Validation failed for Employee ID or Course Title.'];
            }

            msgsHtml += `
                <div class="alert alert-danger border-0 rounded-3 small mb-3 py-2 px-3">
                    <div class="d-flex align-items-center gap-2 fw-bold mb-1">
                        <i class="ti ti-alert-circle fs-3 text-danger"></i>
                        <span>Validation Errors (Blocking)</span>
                    </div>
                    <ul class="mb-0 ps-3">
                        ${blockingErrors.map(m => '<li>' + escapeHtml(m) + '</li>').join('')}
                    </ul>
                </div>
            `;

            // If this invalid row ALSO has non-blocking empty warning fields, show warning details below blocking errors
            if ((row.empty_warning_fields && row.empty_warning_fields.length > 0) || (row.empty_warning_details && row.empty_warning_details.length > 0)) {
                let warningRowsHtml = '';
                const details = (row.empty_warning_details && row.empty_warning_details.length > 0)
                    ? row.empty_warning_details
                    : (row.empty_warning_fields || []).map(function (f) {
                        return { field: f, display_value: 'Empty' };
                    });

                details.forEach(function (item) {
                    const displayVal = item.display_value || 'Empty';
                    warningRowsHtml += `
                        <tr>
                            <td class="fw-semibold text-dark">${escapeHtml(item.field)}</td>
                            <td><span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace">${escapeHtml(displayVal)}</span></td>
                        </tr>
                    `;
                });

                msgsHtml += `
                    <div class="card border border-warning-subtle rounded-3 mb-3 shadow-none" style="background-color: #fffbeb;">
                        <div class="card-header bg-transparent border-warning-subtle py-2 px-3 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2 fw-bold text-warning-emphasis">
                                <i class="ti ti-alert-triangle fs-3 text-warning"></i>
                                <span>WARNING DETAILS (Non-blocking)</span>
                            </div>
                            <span class="badge bg-warning text-dark fw-bold px-2 py-1">WARNING</span>
                        </div>
                        <div class="card-body p-3">
                            <div class="table-responsive rounded-2 border border-warning-subtle mb-2 bg-white">
                                <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.82rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 50%;">Field</th>
                                            <th style="width: 50%;">Validation Result</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${warningRowsHtml}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
            }
        } else if (row.status === 'WARNING') {
            if ((row.empty_warning_fields && row.empty_warning_fields.length > 0) || (row.empty_warning_details && row.empty_warning_details.length > 0)) {
                let warningRowsHtml = '';
                const details = (row.empty_warning_details && row.empty_warning_details.length > 0)
                    ? row.empty_warning_details
                    : (row.empty_warning_fields || []).map(function (f) {
                        return { field: f, display_value: 'Empty' };
                    });

                details.forEach(function (item) {
                    const displayVal = item.display_value || 'Empty';
                    warningRowsHtml += `
                        <tr>
                            <td class="fw-semibold text-dark">${escapeHtml(item.field)}</td>
                            <td><span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace">${escapeHtml(displayVal)}</span></td>
                        </tr>
                    `;
                });

                const rawWarnMsg = row.warning_message || (row.warning_reasons && row.warning_reasons.length ? ('WARNING: ' + row.warning_reasons.join('; ')) : 'WARNING: Attention required for designated fields.');
                const formattedWarnMsg = rawWarnMsg.indexOf('WARNING:') === 0 ? rawWarnMsg : ('WARNING: ' + rawWarnMsg);

                msgsHtml += `
                    <div class="card border border-warning-subtle rounded-3 mb-3 shadow-none" style="background-color: #fffbeb;">
                        <div class="card-header bg-transparent border-warning-subtle py-2 px-3 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2 fw-bold text-warning-emphasis">
                                <i class="ti ti-alert-triangle fs-3 text-warning"></i>
                                <span>WARNING DETAILS</span>
                            </div>
                            <span class="badge bg-warning text-dark fw-bold px-2 py-1">WARNING</span>
                        </div>
                        <div class="card-body p-3">
                            <div class="alert alert-warning border-0 rounded-2 py-2 px-3 mb-3 d-flex align-items-center gap-2" style="background-color: #fef3c7; color: #92400e; font-size: 0.85rem;">
                                <i class="ti ti-alert-triangle fs-3 flex-shrink-0"></i>
                                <span class="fw-semibold">${escapeHtml(formattedWarnMsg)}</span>
                            </div>

                            <div class="row g-2 mb-3 pb-2 border-bottom border-warning-subtle" style="font-size: 0.82rem;">
                                <div class="col-6 col-sm-3">
                                    <span class="text-muted d-block extra-small">Employee ID:</span>
                                    <span class="font-monospace fw-bold text-dark">${escapeHtml(row.employee_id || '')}</span>
                                </div>
                                <div class="col-6 col-sm-3">
                                    <span class="text-muted d-block extra-small">Employee Name:</span>
                                    <span class="fw-semibold text-dark">${escapeHtml(row.name || (row.data && row.data.name) || '')}</span>
                                </div>
                                <div class="col-6 col-sm-3">
                                    <span class="text-muted d-block extra-small">Excel Row Number:</span>
                                    <span class="fw-semibold text-dark">Row ${escapeHtml(String(row.excel_row || ''))}</span>
                                </div>
                                <div class="col-6 col-sm-3">
                                    <span class="text-muted d-block extra-small">Warning Status:</span>
                                    <span class="badge bg-warning text-dark fw-semibold">WARNING</span>
                                </div>
                            </div>

                            <div class="table-responsive rounded-2 border border-warning-subtle mb-3 bg-white">
                                <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.82rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 50%;">Field</th>
                                            <th style="width: 50%;">Validation Result</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${warningRowsHtml}
                                    </tbody>
                                </table>
                            </div>

                            ${row.format_warnings && row.format_warnings.length > 0 ? `
                                <div class="alert alert-warning border-0 rounded-2 py-2 px-3 mb-3 small" style="background-color: #fef3c7; color: #92400e;">
                                    <div class="fw-semibold mb-1"><i class="ti ti-clock me-1"></i>Format Notices:</div>
                                    <ul class="mb-0 ps-3">
                                        ${row.format_warnings.map(fw => '<li>' + escapeHtml(fw) + '</li>').join('')}
                                    </ul>
                                </div>
                            ` : ''}

                            <div class="d-flex align-items-start gap-2 text-muted" style="font-size: 0.8rem; line-height: 1.4;">
                                <i class="ti ti-info-circle text-warning fs-3 flex-shrink-0 mt-0.5"></i>
                                <div>
                                    <em>These fields have designated warning validation in the PTKP Excel template and require attention. This warning is non-blocking, and the record can still be imported.</em>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else if (row.messages && row.messages.length) {
                msgsHtml += '<div class="alert alert-warning border-0 rounded-3 small mb-3 py-2 px-3"><ul class="mb-0 ps-3">';
                row.messages.forEach(function (m) { msgsHtml += '<li>' + escapeHtml(m) + '</li>'; });
                msgsHtml += '</ul></div>';
            }
        } else if (row.status === 'INVALID COMPANY') {
            const companyMismatchMsg = (row.messages || []).find(function (m) {
                return m.toLowerCase().indexOf('company') !== -1 && (m.toLowerCase().indexOf('invalid') !== -1 || m.toLowerCase().indexOf('mismatch') !== -1 || m.toLowerCase().indexOf('strictly requires') !== -1);
            }) || 'Invalid Company: Import PTKP strictly requires \'PT Krakatau Posco\'.';

            msgsHtml += `
                <div class="alert alert-warning border-0 rounded-3 small mb-3 py-2 px-3" style="background-color: #fff7ed; border: 1px solid #fed7aa !important; color: #9a3412;">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div class="d-flex align-items-center gap-2 fw-bold">
                            <i class="ti ti-building fs-3 text-warning"></i>
                            <span>Invalid Company Notice (Strict Whitelist)</span>
                        </div>
                        <span class="badge bg-warning text-dark fw-bold px-2 py-1">INVALID COMPANY</span>
                    </div>
                    <div style="font-size: 0.85rem;" class="fw-semibold">${escapeHtml(companyMismatchMsg)}</div>
                    <div class="extra-small text-muted mt-1" style="font-size: 0.75rem;">Import PTKP strictly permits only 'PT Krakatau Posco'. Any other company name or empty value is categorized as INVALID COMPANY. In OWL LMS Import PTKP, this record is non-blocking and remains eligible for import with a review tag.</div>
                </div>
            `;

            if ((row.empty_warning_fields && row.empty_warning_fields.length > 0) || (row.empty_warning_details && row.empty_warning_details.length > 0)) {
                let warningRowsHtml = '';
                const details = (row.empty_warning_details && row.empty_warning_details.length > 0)
                    ? row.empty_warning_details
                    : (row.empty_warning_fields || []).map(function (f) {
                        return { field: f, display_value: 'Empty' };
                    });

                details.forEach(function (item) {
                    const displayVal = item.display_value || 'Empty';
                    warningRowsHtml += `
                        <tr>
                            <td class="fw-semibold text-dark">${escapeHtml(item.field)}</td>
                            <td><span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace">${escapeHtml(displayVal)}</span></td>
                        </tr>
                    `;
                });

                msgsHtml += `
                    <div class="card border border-warning-subtle rounded-3 mb-3 shadow-none" style="background-color: #fffbeb;">
                        <div class="card-header bg-transparent border-warning-subtle py-2 px-3 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2 fw-bold text-warning-emphasis">
                                <i class="ti ti-alert-triangle fs-3 text-warning"></i>
                                <span>WARNING DETAILS (Non-blocking)</span>
                            </div>
                            <span class="badge bg-warning text-dark fw-bold px-2 py-1">WARNING</span>
                        </div>
                        <div class="card-body p-3">
                            <div class="table-responsive rounded-2 border border-warning-subtle mb-2 bg-white">
                                <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.82rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 50%;">Field</th>
                                            <th style="width: 50%;">Validation Result</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${warningRowsHtml}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
            }
        }

        if (row.status !== 'INVALID COMPANY' && (row.company_mismatch || isRowInvalidCompany(row))) {
            const companyMismatchMsg = (row.messages || []).find(function (m) {
                return m.toLowerCase().indexOf('company mismatch') !== -1 || m.toLowerCase().indexOf('non ptkp') !== -1;
            }) || 'Company mismatch: Excel company differs from user record company.';
            msgsHtml += `
                <div class="alert alert-info border-0 rounded-3 small mb-3 py-2 px-3" style="background-color: #eff6ff; border: 1px solid #bfdbfe !important; color: #1e40af;">
                    <div class="d-flex align-items-center gap-2 fw-bold mb-1">
                        <i class="ti ti-building fs-3 text-primary"></i>
                        <span>Company Discrepancy Notice</span>
                    </div>
                    <div style="font-size: 0.82rem;">${escapeHtml(companyMismatchMsg)}</div>
                    <div class="extra-small text-muted mt-1" style="font-size: 0.75rem;">This company discrepancy has been recorded in the classification metadata.</div>
                </div>
            `;
        }

        $('#modal-inspect-messages').html(msgsHtml);

        const d = row.data || {};
        const fields = [];
        if (row.status === 'DUPLICATE') {
            fields.push(
                ['Duplicate Source', row.duplicate_source === 'database' ? 'Existing Database' : (row.duplicate_source === 'file' ? 'Uploaded Excel' : (row.duplicate_source || ''))],
                ['Duplicate Reference', row.duplicate_source === 'database' ? `Participant Record ID: ${row.matched_record_id || ''}` : `Uploaded Excel Row: ${row.original_excel_row || ''}`]
            );
        }
        fields.push(
            ['1. Employee ID', row.employee_id || d.employee_id],
            ['2. Employee Name (from users)', d.name],
            ['3. Grade (from users)', d.grade],
            ['4. Job Position (from users)', d.job_position],
            ['5. Team (from users)', d.team],
            ['6. Department (from users)', d.department],
            ['7. Company', d.company],
            ['8. Course Title', d.course_name],
            ['9. Sub Course Title', d.sub_course_name],
            ['10. Type', d.type],
            ['11. Category', d.category],
            ['12. Level', d.level],
            ['13. Batch', d.batch],
            ['14. Main Classification', d.main_classification],
            ['15. Sub Classification', d.sub_classification],
            ['16. Delivery Method', d.delivery_method],
            ['17. Sub Delivery Method', d.sub_delivery_method],
            ['18. Provider', d.provider],
            ['19. Sub Provider', d.sub_provider],
            ['20. Provider Name', d.provider_name],
            ['21. Venue', d.venue],
            ['22. Instructor', d.instructor],
            ['23. Learning Hours', d.learning_hours],
            ['24. % Progress', d.progress],
            ['25. Start Date', formatDateDisplay(d.start_date_display || d.start_date)],
            ['26. End Date', formatDateDisplay(d.end_date_display || d.finish_date || d.end_date)],
            ['27. Start Time', d.start_time],
            ['28. End Time', d.end_time],
            ['29. Record Month', d.month],
            ['30. Requirement', d.requirement],
            ['31. Attendance', d.attendance === true ? 'Yes' : (d.attendance === false ? 'No' : (d.attendance !== null && d.attendance !== undefined ? d.attendance : ''))],
            ['32. Attendance Reason', d.attendance_reason],
            ['33. Bond Status', d.bond_status],
            ['34. Bond Duration', d.bond_duration],
            ['35. Bond End Date', formatDateDisplay(d.bond_end_date_display || d.bond_end_date)],
            ['36. Bond Amount', d.bond_amount],
            ['37. Score (L1) - Course', d.l1_program],
            ['38. Score (L2)', d.assessment_score],
            ['39. L2 Status', d.l2_verbal],
            ['40. Score (L3)', d.l3_numerical],
            ['41. L3 Status', d.l3_verbal],
            ['42. Certificate Availability', d.certificate_availability],
            ['43. Certificate Institution', d.certificate_institution],
            ['44. Certificate Issued Date', formatDateDisplay(d.certificate_issued_date_display || d.certificate_issued_date)],
            ['45. Certificate Expired Date', formatDateDisplay(d.certificate_expired_date_display || d.certificate_expired_date)],
            ['46. Person in Charge (PIC)', d.pic]
        );

        let tbodyHtml = '';
        fields.forEach(([label, val]) => {
            tbodyHtml += `<tr><th>${escapeHtml(label)}</th><td>${formatInspectionValue(val)}</td></tr>`;
        });
        $('#modal-inspect-tbody').html(tbodyHtml);

        new bootstrap.Modal(document.getElementById('ptkpInspectionModal')).show();
    });

    // =========================================================================
    // STEP 2 NAVIGATION: Cancel, Back, Next
    // =========================================================================
    $('#ptkp-btn-cancel-preview').on('click', function () {
        if (!confirm('Are you sure you want to cancel this import session? Temporary files will be removed.')) return;
        if (importToken) {
            $.post(window.ptkpRoutes.cancel, { token: importToken, _token: $('meta[name="csrf-token"]').attr('content') }, function () {
                window.location.href = window.ptkpRoutes.offline;
            }).fail(function () {
                window.location.href = window.ptkpRoutes.offline;
            });
        } else {
            window.location.href = window.ptkpRoutes.offline;
        }
    });

    // Back from Step 2 to Step 1 without losing file/validation data
    $('#ptkp-btn-back-to-step1').on('click', function () {
        $previewCard.hide();
        $uploadCard.show();
        setStep(1);
        updateHeaderActions(1);
    });

    // Next from Step 2 to Step 3
    $('#ptkp-btn-next-to-step3').on('click', function () {
        $previewCard.hide();
        $confirmCard.show();
        setStep(3);
        updateHeaderActions(3);
    });

    // =========================================================================
    // STEP 3: BACK & CONFIRM IMPORT
    // =========================================================================
    $('#ptkp-btn-back-to-step2').on('click', function () {
        $confirmCard.hide();
        $previewCard.show();
        setStep(2);
        updateHeaderActions(2);
    });

    $('#ptkp-btn-confirm').on('click', function () {
        if (isSubmitting || !importToken) return;

        isSubmitting = true;
        const $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Importing Records...');

        $.ajax({
            url: window.ptkpRoutes.confirm,
            type: 'POST',
            data: { token: importToken, _token: $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                isSubmitting = false;
                $confirmCard.hide();
                renderResultStage(res.result || {});
                setStep(4);
            },
            error: function (xhr) {
                isSubmitting = false;
                const importable = $('#ptkp-btn-confirm-count').text();
                $btn.prop('disabled', false).html('<i class="ti ti-check me-1"></i> Confirm & Import (' + importable + ' Records)');
                alert('Error: ' + ((xhr.responseJSON && xhr.responseJSON.message) || 'Import failed. Please try again.'));
            }
        });
    });

    // =========================================================================
    // FINAL RESULT: IMPORT SUMMARY
    // =========================================================================
    function renderResultStage(res) {
        $resultCard.show();
        const total = res.total_rows_processed ?? res.rows_processed ?? 0;
        const imported = res.imported ?? 0;
        const warnings = res.warnings_imported ?? 0;
        const duplicates = res.skipped_duplicates ?? 0;
        const invalid = res.skipped_invalid ?? res.invalid ?? 0;
        const invalidCompany = res.invalid_company_imported ?? 0;
        const created = res.trainings_created ?? 0;
        const reused = res.trainings_reused ?? 0;
        const totalSkipped = duplicates + invalid;

        // 1. Success Banner Notification
        if (totalSkipped === 0 && imported > 0) {
            $('#res-header-title').text('Import Completed');
            $('#res-header-desc').text('Your training records have been processed successfully. All eligible records have been imported.');
        } else if (imported > 0 && totalSkipped > 0) {
            $('#res-header-title').text('Import Completed');
            $('#res-header-desc').text('Your training records have been processed with partial results. ' + imported.toLocaleString() + ' imported, ' + totalSkipped.toLocaleString() + ' skipped.');
        } else if (total === 0) {
            $('#res-header-title').text('Import Completed');
            $('#res-header-desc').text('No training records were processed.');
        } else {
            $('#res-header-title').text('Import Completed');
            $('#res-header-desc').text('Your training records have been processed. No records were imported as all records were skipped.');
        }

        // 2. Success Rate Calculation (Safe handling of 0 records)
        let rateNum = 0;
        if (total > 0) {
            rateNum = (imported / total) * 100;
        }
        const rateFormatted = (rateNum % 1 === 0 ? rateNum.toFixed(0) : rateNum.toFixed(1)) + '%';
        $('#res-rate-percentage').text(rateFormatted);
        $('#res-rate-progress-bar').css('width', rateNum + '%').attr('aria-valuenow', rateNum.toFixed(1));
        $('#res-rate-imported-count').text(imported.toLocaleString());
        $('#res-rate-skipped-count').text(totalSkipped.toLocaleString());

        // 3. 6 Summary Cards (Dynamic actual results)
        $('#res-total-processed').text(total.toLocaleString());
        $('#res-imported-participants').text(imported.toLocaleString());
        $('#res-imported-count').text(imported.toLocaleString());
        $('#res-warnings-imported').text(warnings.toLocaleString());
        $('#res-duplicates-skipped').text(duplicates.toLocaleString());
        $('#res-invalid-skipped').text(invalid.toLocaleString());
        $('#res-invalid-company-imported').text(invalidCompany.toLocaleString());
        $('#res-trainings-created').text(created.toLocaleString());
        $('#res-trainings-reused').text(reused.toLocaleString());

        // 4. Update Navigation Buttons
        const batchUuid = res.batch_uuid || null;
        if (batchUuid && window.ptkpRoutes && window.ptkpRoutes.historyDetail) {
            $('#ptkp-btn-view-log').attr('href', window.ptkpRoutes.historyDetail + '/' + encodeURIComponent(batchUuid) + '/detail');
        } else if (window.ptkpRoutes && window.ptkpRoutes.history) {
            $('#ptkp-btn-view-log').attr('href', window.ptkpRoutes.history);
        }
    }

    $('#ptkp-btn-import-another').on('click', resetToStage1);

    function resetToStage1() {
        selectedFile = null;
        importToken = null;
        allRows = [];
        filteredRows = [];
        currentPage = 1;
        isSubmitting = false;
        isFiltering = false;

        $fileInput.val('');
        $dropzoneInitial.show();
        $fileInfoCard.hide();
        $selectedFilename.html('<i class="ti ti-paperclip me-1"></i>No file selected');
        $btnValidate.prop('disabled', true);
        $uploadError.hide().text('');

        $previewCard.hide();
        $confirmCard.hide();
        $resultCard.hide();
        $loadingCard.hide();
        $uploadCard.show();
        $tableLoading.hide();

        currentFilter = 'all';
        $('.ptkp-summary-filter-card').removeClass('active');
        $('.ptkp-summary-filter-card[data-filter="all"]').addClass('active');
        $('#ptkp-search-empid, #ptkp-search-name, #ptkp-search-course, #ptkp-search-pic').val('');
        searchQuery = { empId: '', name: '', course: '', pic: '' };

        setStep(1);
        updateHeaderActions(1);
    }

    function formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024, sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function formatInspectionValue(value) {
        if (value === null || value === undefined) {
            return '';
        }

        if (typeof value === 'string' && value.trim() === '') {
            return '';
        }

        return escapeHtml(String(value));
    }

    function formatDateDisplay(val) {
        if (val === null || val === undefined) return '';
        const s = String(val).trim();
        if (s === '-' || s === '—' || s === '–') return '-';
        if (s === '' || s === 'null' || s === 'undefined') return '';
        const isoMatch = s.match(/^(\d{4})[-/.](\d{1,2})[-/.](\d{1,2})/);
        if (isoMatch) {
            return `${String(isoMatch[3]).padStart(2, '0')}/${String(isoMatch[2]).padStart(2, '0')}/${isoMatch[1]}`;
        }
        const dmyMatch = s.match(/^(\d{1,2})[-/.](\d{1,2})[-/.](\d{4})$/);
        if (dmyMatch) {
            return `${String(dmyMatch[1]).padStart(2, '0')}/${String(dmyMatch[2]).padStart(2, '0')}/${dmyMatch[3]}`;
        }
        return s;
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
})(jQuery);
