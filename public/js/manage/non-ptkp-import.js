/**
 * Non-PTKP Offline Training Import UI Handler
 * Redesigned 3-Step Process for OWL LMS
 * Unified with PTKP Import Design Standard
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

    // DOM Elements - State Panels
    const $uploadCard = $('#panel-state-upload');
    const $loadingCard = $('#panel-state-loading');
    const $previewCard = $('#panel-state-preview');
    const $confirmCard = $('#panel-state-confirm');
    const $resultCard = $('#panel-state-result');

    // DOM Elements - Upload
    const $fileInput = $('#excel-file-input');
    const $dropzone = $('#dropzone-container');
    const $dropzoneInitial = $('#dropzone-initial');
    const $fileInfoCard = $('#selected-file-card');
    const $cardFilename = $('#selected-filename-display');
    const $cardFilesize = $('#selected-filesize-display');
    const $selectedFilename = $('#selected-filename-text');
    const $btnValidate = $('#btn-validate-upload');
    const $uploadError = $('#upload-error-box');
    const $uploadErrorMsg = $('#upload-error-message');
    const $tableLoading = $('#ptkp-table-loading');

    // Header Action Buttons
    const $btnDownloadTemplate = $('#btn-download-template');
    const $btnImportHistory = $('#btn-import-history');

    // =========================================================================
    // CONDITIONAL HEADER BUTTON VISIBILITY
    // =========================================================================
    function updateHeaderActions(currentStep) {
        const downloadTemplate = document.getElementById('btn-download-template') || document.getElementById('btn-download-non-ptkp-template');
        const importHistory = document.getElementById('btn-import-history') || document.getElementById('btn-non-ptkp-history');

        if (downloadTemplate) {
            downloadTemplate.classList.toggle('d-none', currentStep !== 1);
        }

        if (importHistory) {
            importHistory.classList.add('d-none');
        }
    }

    // =========================================================================
    // 3-STEP PROCESS STEPPER
    // =========================================================================
    function setStep(stepNumber) {
        $('.ptkp-step-item, .stepper-item-node').removeClass('active completed');
        $('.ptkp-step-line, .stepper-connector').removeClass('completed');

        if (stepNumber === 1) {
            $('#stepper-step-1').addClass('active');
        } else if (stepNumber === 2) {
            $('#stepper-step-1').addClass('completed');
            $('#stepper-line-1').addClass('completed');
            $('#stepper-step-2').addClass('active');
        } else if (stepNumber === 3) {
            $('#stepper-step-1').addClass('completed');
            $('#stepper-line-1').addClass('completed');
            $('#stepper-step-2').addClass('completed');
            $('#stepper-line-2').addClass('completed');
            $('#stepper-step-3').addClass('active');
        } else if (stepNumber >= 4) {
            $('#stepper-step-1, #stepper-step-2, #stepper-step-3').addClass('completed');
            $('#stepper-line-1, #stepper-line-2').addClass('completed');
        }

        updateHeaderActions(stepNumber);
    }

    // Initialize Stepper at Step 1
    setStep(1);

    // =========================================================================
    // FILE SELECTION, DRAG-AND-DROP & UI FIX
    // =========================================================================
    $dropzone.on('click', function (e) {
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
        $uploadError.hide();
        $uploadErrorMsg.text('');
        const ext = file.name.split('.').pop().toLowerCase();
        if (['xlsx', 'xls'].indexOf(ext) === -1) {
            $uploadErrorMsg.text('Invalid format. Please select an .xlsx or .xls spreadsheet.');
            $uploadError.show();
            resetFileSelection();
            return;
        }
        if (file.size > 20 * 1024 * 1024) {
            $uploadErrorMsg.text('File size exceeds maximum allowed limit of 20MB.');
            $uploadError.show();
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
        $selectedFilename.html('No file selected');
        $btnValidate.prop('disabled', true);
        $uploadError.hide();
        $uploadErrorMsg.text('');
    }

    // Remove / Change File buttons
    $('#btn-clear-file').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        resetFileSelection();
    });

    $('#btn-change-file').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $fileInput.trigger('click');
    });

    // =========================================================================
    // STEP 1: VALIDATE ACTION (LOADING STATE)
    // =========================================================================
    $btnValidate.on('click', function () {
        if (!selectedFile) return;

        const formData = new FormData();
        formData.append('file', selectedFile);
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

        $uploadCard.hide();
        $loadingCard.show();
        $('#loading-state-title').text('Validating Spreadsheet...');
        $('#loading-state-subtitle').text('Reading columns, resolving header groups, and checking existing records...');

        $.ajax({
            url: window.nonPtkpRoutes.preview,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                $loadingCard.hide();
                if (res.success) {
                    importToken = res.token || (res.data && res.data.token);
                    const payload = res.data || res;
                    allRows = payload.preview_rows || res.rows || [];
                    renderPreviewStage(payload);
                    setStep(2);
                    updateHeaderActions(2);
                } else {
                    $uploadCard.show();
                    setStep(1);
                    updateHeaderActions(1);
                    $uploadErrorMsg.text(res.message || 'Validation failed.');
                    $uploadError.show();
                }
            },
            error: function (xhr) {
                $loadingCard.hide();
                $uploadCard.show();
                setStep(1);
                updateHeaderActions(1);
                $uploadErrorMsg.text((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to process spreadsheet file.');
                $uploadError.show();
            }
        });
    });

    // =========================================================================
    // STEP 2: RENDER PREVIEW & REVIEW DATA
    // =========================================================================
    function renderPreviewStage(data) {
        $previewCard.show();
        $('#preview-filename-badge').text(data.filename || (selectedFile && selectedFile.name) || 'Spreadsheet');
        const stats = data.stats || {};

        const totalRowsCount = (stats.total_rows !== undefined && stats.total_rows !== null) ? stats.total_rows : allRows.length;
        $('#stat-total-rows').text(totalRowsCount);
        $('#stat-valid-rows').text(stats.valid || 0);
        $('#stat-warning-rows').text(stats.warning || 0);
        $('#stat-duplicate-rows').text(stats.duplicate || 0);
        $('#stat-invalid-rows').text(stats.invalid || 0);
        $('#stat-invalid-company-rows').text(stats.invalid_company || 0);
        $('#stat-training-events').text(stats.training_events || 0);

        // Sub-breakdown for Invalid ID vs Invalid Course Title (without double counting the total)
        let countInvalidId = 0;
        let countInvalidCourse = 0;
        allRows.forEach(function (r) {
            const st = normalizeStatus(r.status);
            if (st === 'INVALID') {
                if (isRowInvalidId(r)) countInvalidId++;
                if (isRowInvalidCourse(r)) countInvalidCourse++;
            }
        });
        $('#ptkp-kpi-invalid-id-count').text(countInvalidId);
        $('#ptkp-kpi-invalid-course-count').text(countInvalidCourse);
        if ((stats.invalid || 0) > 0) {
            $('#ptkp-kpi-invalid-subinfo').show();
        } else {
            $('#ptkp-kpi-invalid-subinfo').hide();
        }

        // Pre-populate Step 3 values
        const validCount = stats.valid || 0;
        const warningCount = stats.warning || 0;
        const invalidCompanyCount = stats.invalid_company || 0;
        const invalidCount = stats.invalid || 0;
        const duplicateCount = stats.duplicate || 0;

        // Group A: Records to Be Imported
        const totalToImport = data.importable_count !== undefined 
            ? data.importable_count 
            : (validCount + warningCount + invalidCompanyCount);
        $('#res-step3-valid-count').text(validCount.toLocaleString());
        $('#res-step3-warning-count').text(warningCount.toLocaleString());
        if (invalidCompanyCount > 0) {
            $('#res-step3-invalid-company-count').text(invalidCompanyCount.toLocaleString());
            $('#res-step3-invalid-company-eligible-row').show();
        } else {
            $('#res-step3-invalid-company-eligible-row').hide();
        }
        $('#res-step3-total-to-import').text(totalToImport.toLocaleString() + ' Records');

        // Group B: Records Not to Be Imported (Skipped)
        const invalidCompanySkipped = 0;
        const totalNotImported = invalidCount + duplicateCount + invalidCompanySkipped;
        $('#res-step3-invalid-count').text(invalidCount.toLocaleString());
        $('#res-step3-invalid-company-skipped').text(invalidCompanySkipped.toLocaleString());
        $('#res-step3-duplicate-count').text(duplicateCount.toLocaleString());
        $('#res-step3-total-not-imported').text(totalNotImported.toLocaleString() + ' Records');

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
            headerCheck.issues.forEach((iss) => html += '<li>' + escapeHtml(iss.message || iss) + '</li>');
            $('#header-check-issues-container').html(html + '</ul>');
            $('#preview-header-check-card').show();
        } else {
            $('#preview-header-check-card').hide();
        }

        const importable = data.importable_count || 0;
        const total = (data.stats && data.stats.total_rows) || allRows.length;
        const duplicates = (data.stats && data.stats.duplicate) || 0;
        $('#btn-confirm-import-count').text(importable);

        if (total > 0 && importable === 0 && duplicates > 0) {
            $('#ptkp-no-new-records-alert').show();
            $('#ptkp-no-new-records-desc').text('All records already exist in the database. No new records will be imported.');
            $('#btn-next-to-step3').prop('disabled', true);
            $('#btn-action-confirm-import').prop('disabled', true);
        } else if (importable <= 0) {
            $('#ptkp-no-new-records-alert').hide();
            $('#btn-next-to-step3').prop('disabled', true);
            $('#btn-action-confirm-import').prop('disabled', true);
        } else {
            $('#ptkp-no-new-records-alert').hide();
            $('#btn-next-to-step3').prop('disabled', false);
            $('#btn-action-confirm-import').prop('disabled', false);
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
        if ($(e.target).closest('.ptkp-info-icon, .tooltip').length > 0) {
            return;
        }

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

    $(document).on('click', '.ptkp-info-icon', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            const instance = bootstrap.Tooltip.getInstance(this);
            if (instance) {
                const tip = instance.getTipElement ? instance.getTipElement() : null;
                const isShown = tip && tip.classList.contains('show');
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

    function normalizeStatus(st) {
        if (!st) return '';
        const s = String(st).trim().toUpperCase();
        if (s === 'INVALID_COMPANY') return 'INVALID COMPANY';
        return s;
    }

    function isRowInvalidId(row) {
        if (!row) return false;
        const empId = row.employee_id !== undefined && row.employee_id !== null ? String(row.employee_id).trim() : '';
        if (empId === '') return true;
        if (row.messages && Array.isArray(row.messages)) {
            for (let i = 0; i < row.messages.length; i++) {
                const m = String(row.messages[i]).toLowerCase();
                if (m.indexOf('employee id') !== -1 || m.indexOf('user accounts') !== -1 || m.indexOf('not found in lms') !== -1 || m.indexOf('matches multiple users') !== -1 || m.indexOf('ambiguous identifier') !== -1) {
                    return true;
                }
            }
        }
        return false;
    }

    function isRowInvalidCourse(row) {
        if (!row) return false;
        const course = row.course_name !== undefined && row.course_name !== null ? String(row.course_name).trim() : '';
        if (course === '') return true;
        if (row.messages && Array.isArray(row.messages)) {
            for (let i = 0; i < row.messages.length; i++) {
                const m = String(row.messages[i]).toLowerCase();
                if (m.indexOf('course title') !== -1 || m.indexOf('course name') !== -1) {
                    return true;
                }
            }
        }
        return false;
    }

    function applyFilters() {
        const qEmpId = (searchQuery.empId || '').trim().toLowerCase();
        let exactEmpIdExists = false;
        if (qEmpId !== '') {
            for (let i = 0; i < allRows.length; i++) {
                const e = String(allRows[i].employee_id !== undefined && allRows[i].employee_id !== null ? allRows[i].employee_id : '').trim().toLowerCase();
                if (e === qEmpId) {
                    exactEmpIdExists = true;
                    break;
                }
            }
        }

        filteredRows = allRows.filter(function (row) {
            const st = normalizeStatus(row.status);
            if (currentFilter !== 'all') {
                if (currentFilter === 'valid' && st !== 'VALID') return false;
                if (currentFilter === 'warning' && st !== 'WARNING') return false;
                if (currentFilter === 'duplicate' && st !== 'DUPLICATE') return false;
                if (currentFilter === 'invalid' && st !== 'INVALID') return false;
                if (currentFilter === 'invalid_id') {
                    if (st !== 'INVALID' || !isRowInvalidId(row)) return false;
                }
                if (currentFilter === 'invalid_course') {
                    if (st !== 'INVALID' || !isRowInvalidCourse(row)) return false;
                }
                if (currentFilter === 'invalid_company' && st !== 'INVALID COMPANY') return false;
            }
            if (qEmpId !== '') {
                const rowEmpId = String(row.employee_id !== undefined && row.employee_id !== null ? row.employee_id : '').trim().toLowerCase();
                if (exactEmpIdExists) {
                    if (rowEmpId !== qEmpId) return false;
                } else {
                    if (rowEmpId.indexOf(qEmpId) === -1) return false;
                }
            }
            if (searchQuery.name) {
                const rName = String(row.name || row.employee_name || (row.data && row.data.name) || '').trim().toLowerCase();
                if (rName.indexOf(searchQuery.name) === -1) return false;
            }
            if (searchQuery.course) {
                const rCourse = String(row.course_name || (row.data && row.data.course_name) || '').trim().toLowerCase();
                if (rCourse.indexOf(searchQuery.course) === -1) return false;
            }
            if (searchQuery.pic) {
                const rPic = String(row.pic || (row.data && row.data.pic) || '').trim().toLowerCase();
                if (rPic.indexOf(searchQuery.pic) === -1) return false;
            }
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

        const $tbody = $('#preview-main-tbody');
        $tbody.empty();

        if (pageItems.length === 0) {
            $tbody.append('<tr><td colspan="10" class="text-center py-4 text-muted"><i class="ti ti-inbox fs-2 d-block mb-1 text-secondary"></i>No records match the active filter criteria.</td></tr>');
        } else {
            pageItems.forEach(function (row, idx) {
                const globalIndex = startIdx + idx;
                const st = normalizeStatus(row.status);
                let msgsHtml = '';
                const warningReasons = (row.warning_reasons && row.warning_reasons.length > 0)
                    ? row.warning_reasons
                    : (row.empty_warning_fields && row.empty_warning_fields.length > 0)
                        ? row.empty_warning_fields.map(function (f) { return f + ' is empty'; })
                        : [];

                if (warningReasons.length > 0) {
                    const warningText = 'WARNING: ' + warningReasons.join('; ');
                    msgsHtml = '<div class="ptkp-row-msg text-warning fw-semibold" title="' + escapeHtml(warningText) + '">' +
                        '<i class="ti ti-alert-triangle me-1"></i><span class="fw-bold">WARNING:</span> ' + escapeHtml(warningReasons.join('; ')) +
                        '</div>';

                    if (row.company_mismatch) {
                        const companyMismatchMsg = (row.messages || []).find(function (m) {
                            return m.toLowerCase().indexOf('company mismatch') !== -1;
                        }) || 'Company mismatch detected.';
                        msgsHtml += '<div class="ptkp-row-msg text-muted mt-0.5" style="font-size: 10px;" title="' + escapeHtml(companyMismatchMsg) + '"><i class="ti ti-building me-1"></i>' + escapeHtml(companyMismatchMsg) + '</div>';
                    }
                } else if (st === 'WARNING' || st === 'INVALID COMPANY') {
                    if (row.messages && row.messages.length) {
                        msgsHtml = '<div class="ptkp-row-msg text-warning fw-semibold"><i class="ti ti-alert-triangle me-1"></i>' + escapeHtml(row.messages[0]) + '</div>';
                        if (row.messages.length > 1) {
                            msgsHtml += '<div class="text-muted mt-0.5" style="font-size: 10px;">' + escapeHtml(row.messages.slice(1).join('; ')) + '</div>';
                        }
                    }
                } else if (st === 'VALID') {
                    msgsHtml = '<div class="ptkp-row-msg text-muted" style="font-size: 10.5px;"><i class="ti ti-check text-success me-1"></i>Row is valid and ready for import.</div>';
                } else if (row.messages && row.messages.length) {
                    msgsHtml = '<div class="ptkp-row-msg text-muted">' + escapeHtml(row.messages[0]);
                    if (row.messages.length > 1) {
                        msgsHtml += ' <span class="badge bg-light text-secondary border px-1" style="font-size: 9.5px;">+' + (row.messages.length - 1) + '</span>';
                    }
                    msgsHtml += '</div>';
                }

                const rowData = row.data || {};
                $tbody.append(`
                    <tr class="${getRowClass(st)}">
                        <td class="text-center fw-medium text-muted">${row.no || (globalIndex + 1)}</td>
                        <td>${getStatusBadge(st, row)}</td>
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
        status = normalizeStatus(status);
        switch (status) {
            case 'VALID':
                return '<span class="badge badge-ptkp-valid"><i class="ti ti-check me-1"></i>VALID</span>';
            case 'WARNING':
                return '<span class="badge badge-ptkp-warning"><i class="ti ti-alert-triangle me-1"></i>WARNING</span>';
            case 'DUPLICATE': {
                let badge = '<span class="badge badge-ptkp-duplicate"><i class="ti ti-copy me-1"></i>DUPLICATE</span>';
                if (row && row.duplicate_source) {
                    if (row.duplicate_source === 'database' && row.matched_record_id) {
                        badge += `<div class="mt-0.5" style="font-size: 9.5px;"><span class="badge bg-light text-secondary border px-1" style="font-size: 9.5px;">Database (ID ${escapeHtml(String(row.matched_record_id))})</span></div>`;
                    } else if (row.duplicate_source === 'file' && row.original_excel_row) {
                        badge += `<div class="mt-0.5" style="font-size: 9.5px;"><span class="badge bg-light text-secondary border px-1" style="font-size: 9.5px;">Excel (Row ${escapeHtml(String(row.original_excel_row))})</span></div>`;
                    }
                }
                return badge;
            }
            case 'INVALID': {
                const hasEmpErr = isRowInvalidId(row);
                const hasCourseErr = isRowInvalidCourse(row);

                if (hasEmpErr && hasCourseErr) {
                    return '<div class="d-flex flex-column gap-1 align-items-start">' +
                        '<span class="badge badge-ptkp-invalid-id"><i class="ti ti-user-x me-1"></i>INVALID ID</span>' +
                        '<span class="badge badge-ptkp-invalid-course"><i class="ti ti-book-off me-1"></i>INVALID COURSE TITLE</span>' +
                        '</div>';
                } else if (hasEmpErr) {
                    return '<span class="badge badge-ptkp-invalid-id"><i class="ti ti-user-x me-1"></i>INVALID ID</span>';
                } else if (hasCourseErr) {
                    return '<span class="badge badge-ptkp-invalid-course"><i class="ti ti-book-off me-1"></i>INVALID COURSE TITLE</span>';
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
        status = normalizeStatus(status);
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

    // Row Inspection Modal Handler
    $(document).on('click', '.btn-inspect-row', function () {
        const idx = $(this).data('row-idx');
        const row = filteredRows[idx];
        if (!row) return;

        $('#modal-detail-row-num').text(row.excel_row || row.no || (idx + 1));
        $('#modal-detail-status').html(getStatusBadge(row.status, row));

        let msgsHtml = '';
        if (row.messages && row.messages.length) {
            const st = normalizeStatus(row.status);
            const alertClass = st === 'INVALID' ? 'alert-danger' : (st === 'WARNING' || st === 'INVALID COMPANY' ? 'alert-warning' : 'alert-secondary');
            msgsHtml += `
                <div class="alert ${alertClass} border-0 rounded-3 small mb-3 py-2 px-3">
                    <div class="d-flex align-items-center gap-2 fw-bold mb-1">
                        <i class="ti ti-info-circle fs-3"></i>
                        <span>Validation Details &amp; Diagnostics</span>
                    </div>
                    <ul class="mb-0 ps-3">
                        ${row.messages.map(m => '<li>' + escapeHtml(m) + '</li>').join('')}
                    </ul>
                </div>
            `;
        }
        $('#modal-detail-messages').html(msgsHtml);

        const d = row.data || {};
        const fields = [
            ['1. Employee ID', row.employee_id || d.employee_id],
            ['2. Participant Name', d.name || row.name],
            ['3. Grade', d.grade],
            ['4. Job Position', d.job_position],
            ['5. Team', d.team],
            ['6. Department', d.department],
            ['7. Company', d.company || row.company],
            ['8. Course Title', d.course_name || row.course_name],
            ['9. Sub Course Title', d.sub_course_name],
            ['10. Type', d.type || row.type],
            ['11. Category', d.category],
            ['12. Level', d.level],
            ['13. Batch', d.batch],
            ['14. Main Classification', d.main_classification || d.main_class],
            ['15. Sub Classification', d.sub_classification || d.sub_class],
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
            ['26. Finish Date', formatDateDisplay(d.finish_date_display || d.finish_date || d.end_date)],
            ['27. Start Time', d.start_time],
            ['28. End Time', d.end_time],
            ['29. Record Month', d.month],
            ['30. Requirement', d.requirement],
            ['31. Attendance', d.attendance === true ? 'Yes' : (d.attendance === false ? 'No' : (d.attendance !== null && d.attendance !== undefined ? d.attendance : ''))],
            ['32. Attendance Reason', d.attendance_reason],
            ['33. Service Bond', d.service_bond || d.bond_status],
            ['34. Bond Duration', d.service_bond_duration || d.bond_duration],
            ['35. Bond End Date', formatDateDisplay(d.service_bond_end_date || d.bond_end_date)],
            ['36. Bond Amount', d.service_bond_amount || d.bond_amount],
            ['37. Kirkpatrick L1', d.kirkpatrick_l1 || d.l1_program],
            ['38. Kirkpatrick L2', d.kirkpatrick_l2 || d.assessment_score],
            ['39. Kirkpatrick L2 Status', d.kirkpatrick_l2_status || d.l2_verbal],
            ['40. Kirkpatrick L3', d.kirkpatrick_l3 || d.l3_numerical],
            ['41. Kirkpatrick L3 Status', d.kirkpatrick_l3_status || d.l3_verbal],
            ['42. Certificate Availability', d.certificate_availability],
            ['43. Certificate Institution', d.certificate_institution],
            ['44. Certificate Issued Date', formatDateDisplay(d.certificate_issued_date)],
            ['45. Certificate Expired Date', formatDateDisplay(d.certificate_expired_date)],
            ['46. Person in Charge (PIC)', d.pic || row.pic]
        ];

        let tbodyHtml = '';
        fields.forEach(([label, val]) => {
            tbodyHtml += `<tr><th>${escapeHtml(label)}</th><td>${formatInspectionValue(val)}</td></tr>`;
        });
        $('#modal-detail-tbody').html(tbodyHtml);

        new bootstrap.Modal(document.getElementById('previewRowModal')).show();
    });

    // =========================================================================
    // STEP 2 NAVIGATION: Cancel, Back, Next
    // =========================================================================
    $('#btn-action-cancel').on('click', function () {
        if (!confirm('Are you sure you want to cancel this import session? Temporary files will be removed.')) return;
        if (importToken) {
            $.post(window.nonPtkpRoutes.cancel, { token: importToken, _token: $('meta[name="csrf-token"]').attr('content') }, function () {
                window.location.href = window.nonPtkpRoutes.offline;
            }).fail(function () {
                window.location.href = window.nonPtkpRoutes.offline;
            });
        } else {
            window.location.href = window.nonPtkpRoutes.offline;
        }
    });

    // Back from Step 2 to Step 1 without losing file/validation data
    $('#btn-back-to-step1').on('click', function () {
        $previewCard.hide();
        $uploadCard.show();
        setStep(1);
        updateHeaderActions(1);
    });

    // Next from Step 2 to Step 3
    $('#btn-next-to-step3').on('click', function () {
        $previewCard.hide();
        $confirmCard.show();
        setStep(3);
        updateHeaderActions(3);
    });

    // =========================================================================
    // STEP 3: BACK & CONFIRM IMPORT
    // =========================================================================
    $('#btn-back-to-step2').on('click', function () {
        $confirmCard.hide();
        $previewCard.show();
        setStep(2);
        updateHeaderActions(2);
    });

    $('#btn-action-confirm-import').on('click', function () {
        if (isSubmitting || !importToken) return;

        isSubmitting = true;
        const $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Importing Records...');

        $.ajax({
            url: window.nonPtkpRoutes.confirm,
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
                const importable = $('#btn-confirm-import-count').text();
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

        // 2. Success Rate Calculation
        let rateNum = 0;
        if (total > 0) {
            rateNum = (imported / total) * 100;
        }
        const rateFormatted = (rateNum % 1 === 0 ? rateNum.toFixed(0) : rateNum.toFixed(1)) + '%';
        $('#res-rate-percentage').text(rateFormatted);
        $('#res-rate-progress-bar').css('width', rateNum + '%').attr('aria-valuenow', rateNum.toFixed(1));
        $('#res-rate-imported-count').text(imported.toLocaleString());
        $('#res-rate-skipped-count').text(totalSkipped.toLocaleString());

        // 3. 6 Summary Cards
        $('#res-total-processed').text(total.toLocaleString());
        $('#res-imported-count').text(imported.toLocaleString());
        $('#res-warnings-imported').text(warnings.toLocaleString());
        $('#res-duplicates-skipped').text(duplicates.toLocaleString());
        $('#res-invalid-skipped').text(invalid.toLocaleString());
        $('#res-invalid-company-imported').text(invalidCompany.toLocaleString());
        $('#res-trainings-created').text(created.toLocaleString());
        $('#res-trainings-reused').text(reused.toLocaleString());

        // 4. Update Navigation Buttons
        const batchUuid = res.batch_uuid || null;
        if (batchUuid && window.nonPtkpRoutes && window.nonPtkpRoutes.historyDetail) {
            $('#btn-view-import-log').attr('href', window.nonPtkpRoutes.historyDetail + '/' + encodeURIComponent(batchUuid) + '/detail').show();
        } else if (window.nonPtkpRoutes && window.nonPtkpRoutes.history) {
            $('#btn-view-import-log').attr('href', window.nonPtkpRoutes.history).show();
        }
    }

    $('#btn-import-another').on('click', resetToStage1);

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
        $selectedFilename.html('No file selected');
        $btnValidate.prop('disabled', true);
        $uploadError.hide();
        $uploadErrorMsg.text('');

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
        if (value === null || value === undefined) return '';
        if (typeof value === 'string' && value.trim() === '') return '';
        return escapeHtml(String(value));
    }

    function formatDateDisplay(val) {
        if (val === null || val === undefined) return '';
        const s = String(val).trim();
        if (s === '' || s === '-' || s === 'null' || s === 'undefined') return '';
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
