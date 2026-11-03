/**
 * Non PTKP Offline Training Import Controller
 * Redesigned 3-Step Process for OWL LMS
 */
$(document).ready(function () {
    const config = window.NON_PTKP_CONFIG || {};
    let currentToken = null;
    let allRowsData = [];
    let currentFilter = 'all';
    let searchQuery = '';
    let currentPage = 1;
    let pageSize = 25;
    let importInProgress = false;

    // DOM Elements
    const $uploadCard = $('#non-ptkp-upload-card');
    const $loadingCard = $('#non-ptkp-loading-card');
    const $previewCard = $('#non-ptkp-preview-card');
    const $confirmCard = $('#non-ptkp-confirm-card');
    const $importingCard = $('#non-ptkp-importing-card');
    const $resultCard = $('#non-ptkp-result-card');

    const $fileInput = $('#non-ptkp-file-input');
    const $dropzone = $('#non-ptkp-dropzone');
    const $dropzoneInitial = $('#non-ptkp-dropzone-initial');
    const $fileInfoCard = $('#non-ptkp-file-info-card');
    const $cardFilename = $('#non-ptkp-card-filename');
    const $cardFilesize = $('#non-ptkp-card-filesize');
    const $selectedFilename = $('#non-ptkp-selected-filename');
    const $btnValidate = $('#non-ptkp-btn-validate');
    const $uploadError = $('#non-ptkp-upload-error');
    const $btnConfirm = $('#non-ptkp-btn-confirm-import');

    // Header buttons
    const $btnDownloadTemplate = $('#btn-download-non-ptkp-template');
    const $btnImportHistory = $('#btn-non-ptkp-history');

    // =========================================================================
    // CONDITIONAL HEADER BUTTON VISIBILITY
    // =========================================================================
    function updateHeaderActions(currentStep) {
        const downloadTemplate = document.getElementById('btn-download-non-ptkp-template') || document.getElementById('downloadTemplate');
        const importHistory = document.getElementById('btn-non-ptkp-history') || document.getElementById('importHistory');

        if (downloadTemplate) {
            downloadTemplate.classList.toggle('d-none', currentStep !== 1);
        }

        if (importHistory) {
            importHistory.classList.toggle('d-none', currentStep < 3);
        }
    }

    // =========================================================================
    // 3-STEP PROCESS STEPPER & HEADER ACTION PLACEMENT
    // =========================================================================
    function setStep(stepNumber) {
        $('.non-ptkp-step-item').removeClass('active completed');
        $('.non-ptkp-step-line').removeClass('completed');

        if (stepNumber === 1) {
            $('#step-ind-1').addClass('active');
        } else if (stepNumber === 2) {
            $('#step-ind-1').addClass('completed');
            $('#step-line-1').addClass('completed');
            $('#step-ind-2').addClass('active');
        } else if (stepNumber === 3) {
            $('#step-ind-1, #step-ind-2').addClass('completed');
            $('#step-line-1, #step-line-2').addClass('completed');
            $('#step-ind-3').addClass('active');
        } else if (stepNumber >= 4) {
            $('#step-ind-1, #step-ind-2, #step-ind-3').addClass('completed');
            $('#step-line-1, #step-line-2').addClass('completed');
        }

        updateHeaderActions(stepNumber);
    }

    setStep(1);
    updateHeaderActions(1);

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

    ['dragover', 'dragenter'].forEach(ev => $dropzone.on(ev, function (e) {
        e.preventDefault(); e.stopPropagation(); $(this).addClass('dragover');
    }));
    ['dragleave', 'dragend', 'drop'].forEach(ev => $dropzone.on(ev, function (e) {
        e.preventDefault(); e.stopPropagation(); $(this).removeClass('dragover');
    }));

    $dropzone.on('drop', function (e) {
        const files = e.originalEvent.dataTransfer.files;
        if (files && files.length > 0) {
            $fileInput[0].files = files;
            handleFileSelection(files[0]);
        }
    });

    $fileInput.on('change', function () {
        if (this.files && this.files.length > 0) handleFileSelection(this.files[0]);
        else resetFileSelection();
    });

    function handleFileSelection(file) {
        $uploadError.hide().text('');
        const ext = file.name.split('.').pop().toLowerCase();
        if (!['xlsx', 'xls'].includes(ext)) {
            $uploadError.text('Invalid file format. Please upload an Excel spreadsheet (.xlsx or .xls).').show();
            resetFileSelection();
            return;
        }
        if (file.size > 20 * 1024 * 1024) {
            $uploadError.text('File size exceeds maximum allowed limit of 20MB.').show();
            resetFileSelection();
            return;
        }

        const sizeKb = (file.size / 1024).toFixed(1);
        $cardFilename.text(file.name);
        $cardFilesize.text(`${sizeKb} KB`);
        $selectedFilename.html('<strong>' + escapeHtml(file.name) + '</strong> (' + sizeKb + ' KB)');

        // Visual fix: Hide initial state, show file info card
        $dropzoneInitial.hide();
        $fileInfoCard.show();

        $btnValidate.prop('disabled', false);
    }

    function resetFileSelection() {
        $fileInput.val('');
        $dropzoneInitial.show();
        $fileInfoCard.hide();
        $selectedFilename.html('<i class="ti ti-paperclip me-1"></i>No file selected');
        $btnValidate.prop('disabled', true);
        $uploadError.hide().text('');
    }

    $('#non-ptkp-btn-remove-file').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        resetFileSelection();
    });

    $('#non-ptkp-btn-change-file').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $fileInput.trigger('click');
    });

    // =========================================================================
    // STEP 1: VALIDATE ACTION (LOADING STATE)
    // =========================================================================
    $btnValidate.on('click', function () {
        const file = $fileInput[0].files[0];
        if (!file) {
            $uploadError.text('Please select an Excel file first.').show();
            return;
        }

        const formData = new FormData();
        formData.append('file', file);
        formData.append('_token', config.csrfToken);

        $uploadCard.hide();
        $previewCard.hide();
        $confirmCard.hide();
        $resultCard.hide();
        $loadingCard.show();

        $.ajax({
            url: config.previewUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                $loadingCard.hide();
                if (res.success) {
                    currentToken = res.token || (res.data && res.data.token);
                    const payload = res.data || res;
                    allRowsData = payload.preview_rows || res.rows || [];
                    renderPreview(payload);
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
                $uploadError.text((xhr.responseJSON && xhr.responseJSON.message) || 'Validation failed.').show();
            }
        });
    });

    // =========================================================================
    // STEP 2: RENDER PREVIEW STATE
    // =========================================================================
    function renderPreview(data) {
        $previewCard.show();
        const stats = data.stats || {};
        $('#stat-non-ptkp-total').text(stats.total_rows || allRowsData.length);
        $('#stat-non-ptkp-valid').text(stats.valid || 0);
        $('#stat-non-ptkp-warning').text(stats.warning || 0);
        $('#stat-non-ptkp-duplicate').text(stats.duplicate || 0);
        $('#stat-non-ptkp-invalid').text(stats.invalid || 0);
        $('#stat-non-ptkp-events').text(stats.events || stats.unique_events || 0);

        const validCount = Number(stats.valid || 0);
        const warningCount = Number(stats.warning || 0);
        const duplicateCount = Number(stats.duplicate || 0);
        const invalidCount = Number(stats.invalid || 0);
        const invalidCompanyCount = Number(stats.invalid_company || 0);

        // Pre-populate Step 3 Pre-Import Summary & Safeguards
        const totalToImport = validCount + warningCount + invalidCompanyCount;
        $('#non-ptkp-step3-valid-count').text(validCount.toLocaleString());
        $('#non-ptkp-step3-warning-count').text(warningCount.toLocaleString());
        if (invalidCompanyCount > 0) {
            $('#non-ptkp-step3-invalid-company-count').text(invalidCompanyCount.toLocaleString());
            $('#non-ptkp-step3-invalid-company-eligible-row').show();
        } else {
            $('#non-ptkp-step3-invalid-company-eligible-row').hide();
        }
        $('#non-ptkp-step3-total-to-import').text(totalToImport.toLocaleString() + ' Records');

        const invalidCompanySkipped = 0;
        const totalNotImported = invalidCount + duplicateCount + invalidCompanySkipped;
        $('#non-ptkp-step3-invalid-count').text(invalidCount.toLocaleString());
        $('#non-ptkp-step3-invalid-company-skipped').text(invalidCompanySkipped.toLocaleString());
        $('#non-ptkp-step3-duplicate-count').text(duplicateCount.toLocaleString());
        $('#non-ptkp-step3-total-not-imported').text(totalNotImported.toLocaleString() + ' Records');
        $('#non-ptkp-btn-confirm-import-count').text(totalToImport.toLocaleString());

        if (totalToImport > 0) {
            $('#non-ptkp-btn-next-to-step3').prop('disabled', false);
            $btnConfirm.prop('disabled', false);
        } else {
            $('#non-ptkp-btn-next-to-step3').prop('disabled', true);
            $btnConfirm.prop('disabled', true);
        }

        renderTable();
    }

    function renderTable() {
        const $tbody = $('#non-ptkp-preview-tbody');
        $tbody.empty();

        if (allRowsData.length === 0) {
            $tbody.append('<tr><td colspan="10" class="text-center py-4 text-muted">No data available</td></tr>');
            return;
        }

        allRowsData.forEach((row, idx) => {
            const d = row.data || {};
            let badgeClass = 'bg-success-lt';
            let status = (row.status || 'VALID').toUpperCase();
            if (status === 'WARNING') badgeClass = 'bg-warning-lt';
            else if (status === 'DUPLICATE') badgeClass = 'bg-purple-lt';
            else if (status === 'INVALID') badgeClass = 'bg-danger-lt';

            $tbody.append(`
                <tr>
                    <td class="text-center text-muted">${row.excel_row || (idx + 1)}</td>
                    <td><span class="badge ${badgeClass} px-2 py-1">${escapeHtml(status)}</span></td>
                    <td class="font-monospace">${escapeHtml(d.employee_id || '-')}</td>
                    <td><div class="fw-semibold">${escapeHtml(d.name || '-')}</div></td>
                    <td><span class="small text-muted">${escapeHtml(d.company || '-')}</span></td>
                    <td><div class="fw-medium">${escapeHtml(d.course_name || '-')}</div></td>
                    <td><span class="badge bg-light text-secondary border">${escapeHtml(d.type || '-')}</span></td>
                    <td><span class="small text-muted">${escapeHtml(d.pic || '-')}</span></td>
                    <td>${row.messages && row.messages.length ? escapeHtml(row.messages[0]) : '<span class="text-success small">Ready to import</span>'}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-ghost-primary rounded-circle p-1 btn-inspect-nonptkp" data-idx="${idx}" title="Inspect">
                            <i class="ti ti-eye"></i>
                        </button>
                    </td>
                </tr>
            `);
        });

        $('#non-ptkp-pagination-info').text(`Showing 1 to ${allRowsData.length} of ${allRowsData.length} entries`);
    }

    // Row Inspection
    $(document).on('click', '.btn-inspect-nonptkp', function () {
        const idx = $(this).data('idx');
        const row = allRowsData[idx];
        if (!row) return;

        const d = row.data || {};
        $('#non-ptkp-modal-row-num').text(row.excel_row || (idx + 1));
        $('#insp-p-empid').text(d.employee_id || '-');
        $('#insp-p-name').text(d.name || '-');
        $('#insp-p-grade').text(d.grade || '-');
        $('#insp-p-position').text(d.job_position || '-');
        $('#insp-p-team').text(d.team || '-');
        $('#insp-p-dept').text(d.department || '-');
        $('#insp-p-company').text(d.company || '-');
        $('#insp-p-user-match').text(row.user_matched ? 'Matched' : 'Not Found');
        $('#insp-t-course').text(d.course_name || '-');
        $('#insp-t-batch').text(d.batch || '-');
        $('#insp-t-type').text(d.type || '-');
        $('#insp-t-category').text(d.category || '-');
        $('#insp-t-venue').text(d.venue || '-');

        new bootstrap.Modal(document.getElementById('nonPtkpInspectionModal')).show();
    });

    // =========================================================================
    // STEP 2 NAVIGATION: Cancel, Back, Next
    // =========================================================================
    $('#non-ptkp-btn-cancel-bottom').on('click', function () {
        if (!confirm('Cancel this import session?')) return;
        if (currentToken) {
            $.post(config.cancelUrl, { token: currentToken, _token: config.csrfToken });
        }
        window.location.href = config.offlineUrl;
    });

    $('#non-ptkp-btn-back-to-step1').on('click', function () {
        $previewCard.hide();
        $uploadCard.show();
        setStep(1);
        updateHeaderActions(1);
    });

    $('#non-ptkp-btn-next-to-step3').on('click', function () {
        $previewCard.hide();
        $confirmCard.show();
        setStep(3);
        updateHeaderActions(3);
    });

    // =========================================================================
    // STEP 3 NAVIGATION: Back, Confirm & Import
    // =========================================================================
    $('#non-ptkp-btn-back-to-step2').on('click', function () {
        $confirmCard.hide();
        $previewCard.show();
        setStep(2);
        updateHeaderActions(2);
    });

    $btnConfirm.on('click', function () {
        if (importInProgress || !currentToken) return;

        importInProgress = true;
        $btnConfirm.prop('disabled', true);
        $confirmCard.hide();
        $importingCard.show();

        $.ajax({
            url: config.confirmUrl,
            type: 'POST',
            data: { token: currentToken, _token: config.csrfToken },
            success: function (res) {
                importInProgress = false;
                $importingCard.hide();
                if (res.success) {
                    const r = res.result || {};
                    const total = Number(r.rows_processed ?? r.total_rows_processed ?? 0);
                    const imported = Number(r.imported ?? 0);
                    const warnings = Number(r.warnings_imported ?? 0);
                    const duplicates = Number(r.skipped_duplicates ?? 0);
                    const invalid = Number(r.invalid ?? r.skipped_invalid ?? 0);
                    const invalidCompany = Number(r.invalid_company_imported ?? 0);
                    const created = Number(r.trainings_created ?? 0);
                    const reused = Number(r.trainings_reused ?? 0);
                    const totalSkipped = duplicates + invalid;

                    // 1. Success Banner Text
                    if (totalSkipped > 0) {
                        $('#res-nonptkp-header-title').text('Import Completed');
                        $('#res-nonptkp-header-desc').text('Your training records have been processed. Please review the import results below.');
                    } else {
                        $('#res-nonptkp-header-title').text('Import Completed');
                        $('#res-nonptkp-header-desc').text('Your training records have been processed successfully. All eligible records have been imported.');
                    }

                    // 2. Success Rate Calculation
                    let rate = '0.0';
                    if (total > 0) {
                        rate = ((imported / total) * 100).toFixed(1);
                    }
                    $('#res-nonptkp-rate-percentage').text(rate + '%');
                    $('#res-nonptkp-rate-progress-bar').css('width', rate + '%').attr('aria-valuenow', rate);
                    $('#res-nonptkp-rate-imported-count').text(imported.toLocaleString());
                    $('#res-nonptkp-rate-skipped-count').text(totalSkipped.toLocaleString());

                    // 3. 6 Summary Cards
                    $('#res-nonptkp-total-processed').text(total.toLocaleString());
                    $('#res-nonptkp-imported-count').text(imported.toLocaleString());
                    $('#res-nonptkp-warnings-imported').text(warnings.toLocaleString());
                    $('#res-nonptkp-duplicates-skipped').text(duplicates.toLocaleString());
                    $('#res-nonptkp-invalid-skipped').text(invalid.toLocaleString());
                    $('#res-nonptkp-invalid-company-imported').text(invalidCompany.toLocaleString());
                    $('#res-nonptkp-trainings-created').text(created.toLocaleString());
                    $('#res-nonptkp-trainings-reused').text(reused.toLocaleString());

                    $resultCard.show();
                    setStep(4);
                } else {
                    $confirmCard.show();
                    $btnConfirm.prop('disabled', false);
                    alert(res.message || 'Import failed.');
                }
            },
            error: function (xhr) {
                importInProgress = false;
                $importingCard.hide();
                $confirmCard.show();
                $btnConfirm.prop('disabled', false);
                alert((xhr.responseJSON && xhr.responseJSON.message) || 'Import failed.');
            }
        });
    });

    // Import Another button
    $('#non-ptkp-btn-import-another').on('click', function () {
        currentToken = null;
        allRowsData = [];
        resetFileSelection();
        $resultCard.hide();
        $confirmCard.hide();
        $previewCard.hide();
        $uploadCard.show();
        setStep(1);
        updateHeaderActions(1);
    });

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
});
