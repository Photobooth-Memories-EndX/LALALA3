/**
 * Non PTKP Offline Training Import Controller
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
    const $importingCard = $('#non-ptkp-importing-card');
    const $resultCard = $('#non-ptkp-result-card');

    const $fileInput = $('#non-ptkp-file-input');
    const $dropzone = $('#non-ptkp-dropzone');
    const $selectedFilename = $('#non-ptkp-selected-filename');
    const $btnValidate = $('#non-ptkp-btn-validate');
    const $uploadError = $('#non-ptkp-upload-error');
    const $btnConfirm = $('#non-ptkp-btn-confirm-import');

    // Drag and drop handlers
    ['dragover', 'dragenter'].forEach(ev => $dropzone.on(ev, function (e) { e.preventDefault(); e.stopPropagation(); $(this).addClass('dragover'); }));
    ['dragleave', 'dragend', 'drop'].forEach(ev => $dropzone.on(ev, function (e) { e.preventDefault(); e.stopPropagation(); $(this).removeClass('dragover'); }));
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
        $selectedFilename.text(`${file.name} (${(file.size / 1024).toFixed(1)} KB)`);
        $btnValidate.prop('disabled', false);
    }

    function resetFileSelection() {
        $fileInput.val('');
        $selectedFilename.text('No file selected');
        $btnValidate.prop('disabled', true);
    }

    function setStep(stepNumber) {
        $('.non-ptkp-step-item').removeClass('active completed');
        for (let i = 1; i <= 4; i++) {
            const $item = $(`#step-ind-${i}`);
            if (i < stepNumber) {
                $item.addClass('completed');
            } else if (i === stepNumber) {
                $item.addClass('active');
            }
        }
    }

    // Validate & Preview Action
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
        $resultCard.hide();
        $loadingCard.show();
        setStep(2);

        $.ajax({
            url: config.previewUrl,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (res) {
                $loadingCard.hide();
                if (res.success && res.data) {
                    renderPreview(res.data);
                } else {
                    $uploadError.text(res.message || 'Validation failed.').show();
                    $uploadCard.show();
                    setStep(1);
                }
            },
            error: function (xhr) {
                $loadingCard.hide();
                $uploadCard.show();
                setStep(1);
                let errMsg = 'Failed to parse Excel file.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errMsg = xhr.responseJSON.message;
                }
                $uploadError.text(errMsg).show();
            }
        });
    });

    function renderPreview(data) {
        currentToken = data.token;
        allRowsData = data.preview_rows || [];
        const stats = data.stats || {};

        $('#non-ptkp-preview-filename').text(data.filename || 'Spreadsheet');
        const kpiMap = {
            total: stats.total_rows,
            valid: stats.valid,
            warning: stats.warning,
            duplicate: stats.duplicate,
            invalid: stats.invalid,
            invalid_company: stats.invalid_company
        };
        Object.keys(kpiMap).forEach(k => {
            $(`#non-ptkp-kpi-${k}`).text(Number(kpiMap[k] || 0).toLocaleString());
            $(`#count-${k}`).text(Number(kpiMap[k] || 0).toLocaleString());
        });

        const newTrainings = stats.new_trainings || 0;
        const reusedTrainings = stats.reused_trainings || 0;
        $('#non-ptkp-kpi-events').html(`<span title="New: ${newTrainings} | Reused: ${reusedTrainings}">${newTrainings + reusedTrainings}</span>`);

        const headerCheck = data.header_check || {};
        if (headerCheck && typeof headerCheck.can_proceed !== 'undefined') {
            $('#non-ptkp-header-check-card').show();
            const formatName = data.format === '2_ROW_GROUPED' ? '2-Row Grouped Headers' : 'Single-Row Canonical Headers';
            $('#non-ptkp-header-check-subtitle').text(`${formatName} • 48 Canonical Columns`);
            $('#non-ptkp-badge-header-valid').toggle(!!headerCheck.can_proceed);
            $('#non-ptkp-badge-header-invalid').toggle(!headerCheck.can_proceed);
        } else {
            $('#non-ptkp-header-check-card').hide();
        }

        if (data.unknown_headers && data.unknown_headers.length > 0) {
            $('#non-ptkp-unknown-headers-list').text(data.unknown_headers.join(', '));
            $('#non-ptkp-unknown-headers-alert').show();
        } else {
            $('#non-ptkp-unknown-headers-alert').hide();
        }

        // Importability check & "No New Records to Import" condition
        const importableCount = Number(data.importable_count || (Number(stats.valid || 0) + Number(stats.warning || 0) + Number(stats.invalid_company || 0)));
        const duplicateCount = Number(stats.duplicate || 0);
        const totalRows = Number(stats.total_rows || allRowsData.length);
        $('#non-ptkp-import-btn-count').text(importableCount.toLocaleString());

        if (totalRows > 0 && importableCount === 0 && duplicateCount > 0) {
            $('#non-ptkp-no-new-records-alert').show();
            $('#non-ptkp-no-new-records-desc').text(
                'All records already exist in the database. No new records will be imported.'
            );
            $btnConfirm.prop('disabled', true).addClass('disabled');
            $('#non-ptkp-summary-action-text').text('No new records eligible for import.');
        } else if (importableCount <= 0) {
            $('#non-ptkp-no-new-records-alert').hide();
            $btnConfirm.prop('disabled', true).addClass('disabled');
            $('#non-ptkp-summary-action-text').text('No valid records to import.');
        } else {
            $('#non-ptkp-no-new-records-alert').hide();
            $btnConfirm.prop('disabled', false).removeClass('disabled');
            $('#non-ptkp-summary-action-text').text(`${importableCount} records ready to import.`);
        }

        currentFilter = 'all';
        searchQuery = '';
        currentPage = 1;
        $('#non-ptkp-search-input').val('');
        $('.filter-tab-pill').removeClass('active');
        $('[data-filter="all"]').addClass('active');

        setStep(3);
        renderTable();
        $previewCard.show();
    }

    // Filter and search
    $('#non-ptkp-filter-tabs').on('click', '.filter-tab-pill', function () {
        $('.filter-tab-pill').removeClass('active');
        $(this).addClass('active');
        currentFilter = $(this).data('filter');
        currentPage = 1;
        renderTable();
    });

    $('#non-ptkp-search-input').on('input', function () {
        searchQuery = $(this).val().toLowerCase().trim();
        currentPage = 1;
        renderTable();
    });

    $('#non-ptkp-page-size').on('change', function () {
        pageSize = parseInt($(this).val(), 10) || 25;
        currentPage = 1;
        renderTable();
    });

    function getFilteredRows() {
        return allRowsData.filter(function (row) {
            const statusMatch = (currentFilter === 'all') || (row.status === currentFilter);
            if (!statusMatch) return false;

            if (!searchQuery) return true;

            const d = row.data || {};
            const empId = (d.employee_id || '').toLowerCase();
            const name = (d.name || '').toLowerCase();
            const course = (d.course_name || '').toLowerCase();
            const pic = (d.pic || '').toLowerCase();
            const batch = (d.batch || '').toLowerCase();

            return empId.includes(searchQuery) ||
                name.includes(searchQuery) ||
                course.includes(searchQuery) ||
                pic.includes(searchQuery) ||
                batch.includes(searchQuery);
        });
    }

    function renderTable() {
        const $tbody = $('#non-ptkp-preview-tbody');
        $tbody.empty();

        const filtered = getFilteredRows();
        const totalFiltered = filtered.length;
        const totalPages = Math.ceil(totalFiltered / pageSize) || 1;

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const startIndex = (currentPage - 1) * pageSize;
        const endIndex = Math.min(startIndex + pageSize, totalFiltered);
        const pageRows = filtered.slice(startIndex, endIndex);

        if (pageRows.length === 0) {
            $tbody.append('<tr><td colspan="10" class="text-center py-4 text-muted">No records match the current filter or search criteria.</td></tr>');
            $('#non-ptkp-pagination-info').text('Showing 0 of 0 entries');
            renderPagination(0, 1);
            return;
        }

        pageRows.forEach(function (row, idx) {
            const actualIndex = startIndex + idx;
            const d = row.data || {};
            const badgeMap = {
                valid: { cls: 'badge-ptkp-valid', lbl: 'VALID', icon: 'ti-check' },
                warning: { cls: 'badge-ptkp-warning', lbl: 'WARNING', icon: 'ti-alert-triangle' },
                duplicate: { cls: 'badge-ptkp-duplicate', lbl: 'DUPLICATE', icon: 'ti-copy' },
                invalid: { cls: 'badge-ptkp-invalid', lbl: 'INVALID', icon: 'ti-x' },
                invalid_company: { cls: 'badge-ptkp-invalid-company', lbl: 'INVALID COMPANY', icon: 'ti-building' }
            };
            const b = badgeMap[row.status] || { cls: 'badge-ptkp-valid', lbl: 'VALID', icon: 'ti-check' };
            const userMatchBadge = row.user_matched
                ? '<span class="badge bg-success-lt ms-1" style="font-size: 0.65rem;" title="Matched with LMS user"><i class="ti ti-check"></i> Matched</span>'
                : '<span class="badge bg-secondary-lt ms-1" style="font-size: 0.65rem;" title="Participant snapshot preserved (user_id = NULL)"><i class="ti ti-user-x"></i> Not in LMS</span>';

            let msgsHtml = '';
            if (row.messages && row.messages.length > 0) {
                msgsHtml = '<div class="ptkp-row-msg ' + (row.status === 'invalid' ? 'text-danger' : 'text-muted') + '">' + escapeHtml(row.messages[0]);
                if (row.messages.length > 1) {
                    msgsHtml += ' <span class="badge bg-light text-secondary border">+' + (row.messages.length - 1) + '</span>';
                }
                msgsHtml += '</div>';
            } else {
                msgsHtml = '<span class="text-success small"><i class="ti ti-check me-1"></i>Ready to import</span>';
            }

            $tbody.append(`
                <tr>
                    <td class="text-center text-muted fw-semibold">${row.excel_row}</td>
                    <td><span class="badge ${b.cls} px-2 py-1"><i class="ti ${b.icon} me-1"></i>${b.lbl}</span></td>
                    <td class="fw-semibold font-monospace">${escapeHtml(d.employee_id || '-')}</td>
                    <td>
                        <div class="fw-bold text-dark">${escapeHtml(d.name || '-')}${userMatchBadge}</div>
                        <div class="text-muted extra-small" style="font-size: 0.72rem;">${escapeHtml(d.job_position || d.department || '-')}</div>
                    </td>
                    <td><span class="text-muted small">${escapeHtml(d.company || '-')}</span></td>
                    <td>
                        <div class="fw-semibold text-dark">${escapeHtml(d.course_name || '-')}</div>
                        <div class="text-muted extra-small" style="font-size: 0.72rem;">${escapeHtml(d.batch || '')}${d.start_date ? ' &bull; ' + escapeHtml(d.start_date) : ''}</div>
                    </td>
                    <td><span class="badge bg-light text-secondary border">${escapeHtml(d.type || '-')}</span></td>
                    <td><span class="text-muted small">${escapeHtml(d.pic || '-')}</span></td>
                    <td>${msgsHtml}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-ghost-primary rounded-circle btn-view-non-ptkp-row" data-index="${actualIndex}" title="Inspect Details">
                            <i class="ti ti-eye"></i>
                        </button>
                    </td>
                </tr>
            `);
        });

        $('#non-ptkp-pagination-info').text(
            `Showing ${startIndex + 1} to ${endIndex} of ${totalFiltered} entries` +
            (totalFiltered !== allRowsData.length ? ` (filtered from ${allRowsData.length} total)` : '')
        );

        renderPagination(totalPages, currentPage);
    }

    function renderPagination(totalPages, activePage) {
        const $nav = $('#non-ptkp-pagination-nav').empty();
        if (totalPages <= 1) return;

        $nav.append(`<li class="page-item ${activePage === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${activePage - 1}"><i class="ti ti-chevron-left"></i></a></li>`);
        for (let p = 1; p <= totalPages; p++) {
            if (p === 1 || p === totalPages || (p >= activePage - 1 && p <= activePage + 1)) {
                $nav.append(`<li class="page-item ${p === activePage ? 'active' : ''}"><a class="page-link" href="#" data-page="${p}">${p}</a></li>`);
            } else if (p === activePage - 2 || p === activePage + 2) {
                $nav.append('<li class="page-item disabled"><span class="page-link">&hellip;</span></li>');
            }
        }
        $nav.append(`<li class="page-item ${activePage === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${activePage + 1}"><i class="ti ti-chevron-right"></i></a></li>`);
    }

    $('#non-ptkp-pagination-nav').on('click', '.page-link', function (e) {
        e.preventDefault();
        const p = parseInt($(this).data('page'), 10);
        if (p && p !== currentPage) {
            currentPage = p;
            renderTable();
        }
    });

    // Inspect Row Details in Modal
    $(document).on('click', '.btn-view-non-ptkp-row', function () {
        const idx = $(this).data('index');
        const row = getFilteredRows()[idx];
        if (!row) return;

        const d = row.data || {};
        $('#insp-row-num').text(row.excel_row);

        // Messages banner
        const $msgContainer = $('#insp-messages-container');
        $msgContainer.empty();
        if (row.messages && row.messages.length > 0) {
            const alertClass = row.status === 'invalid' ? 'alert-danger' : (row.status === 'warning' ? 'alert-warning' : 'alert-info');
            let alertHtml = `<div class="alert ${alertClass} border-0 rounded-3 p-2 small mb-0"><ul class="mb-0 ps-3">`;
            row.messages.forEach(function (m) { alertHtml += `<li>${escapeHtml(m)}</li>`; });
            alertHtml += '</ul></div>';
            $msgContainer.html(alertHtml);
        }

        // Set text fields in modal
        const fieldMap = {
            'insp-p-nip': d.employee_id, 'insp-p-name': d.name, 'insp-p-grade': d.grade,
            'insp-p-position': d.job_position, 'insp-p-team': d.team, 'insp-p-dept': d.department,
            'insp-p-company': d.company, 'insp-t-course': d.course_name, 'insp-t-subcourse': d.sub_course_name,
            'insp-t-batch': d.batch, 'insp-t-type': d.type, 'insp-t-category': d.category,
            'insp-t-level': d.level, 'insp-t-venue': d.venue, 'insp-t-instructor': d.instructor,
            'insp-t-start-date': d.start_date, 'insp-t-end-date': d.finish_date || d.end_date,
            'insp-t-start-time': d.start_time, 'insp-t-end-time': d.end_time,
            'insp-t-hours': d.learning_hours || '0',
            'insp-t-duration': d.duration_seconds ? `${d.duration_seconds} s` : '-',
            'insp-t-main-class': d.main_classification, 'insp-t-sub-class': d.sub_classification,
            'insp-t-delivery': d.delivery_method, 'insp-t-provider': d.provider ? (d.sub_provider ? `${d.provider} (${d.sub_provider})` : d.provider) : (d.provider_name || '-'),
            'insp-t-sub-provider': d.sub_provider || '-', 'insp-t-provider-name': d.provider_name || '-',
            'insp-o-progress': (d.progress !== null && typeof d.progress !== 'undefined') ? `${d.progress}%` : '-',
            'insp-o-progress-status': d.progress_status,
            'insp-o-attendance': d.attendance ? 'Present' : (d.attendance === false ? 'Absent' : '-'),
            'insp-o-attendance-reason': d.attendance_reason, 'insp-o-bond-status': d.bond_status || 'No',
            'insp-o-bond-duration': d.bond_duration ? `${d.bond_duration} months` : '-',
            'insp-o-bond-end-date': d.bond_end_date,
            'insp-o-bond-amount': d.bond_amount ? `IDR ${Number(d.bond_amount).toLocaleString()}` : '-',
            'insp-e-l1': d.l1_program,
            'insp-e-l2': (d.l2_numerical !== null ? d.l2_numerical : '-') + (d.l2_verbal ? ` (${d.l2_verbal})` : ''),
            'insp-e-l3': (d.l3_numerical !== null ? d.l3_numerical : '-') + (d.l3_verbal ? ` (${d.l3_verbal})` : ''),
            'insp-e-pic': d.pic, 'insp-e-cert': d.certificate || d.certificate_availability || '-',
            'insp-e-cert-avail': d.certificate_availability || '-',
            'insp-e-cert-inst': d.certificate_institution || '-',
            'insp-e-cert-issued': d.certificate_issued_date || '-',
            'insp-e-cert-expired': d.certificate_expired_date || '-',
            'insp-e-engagement': d.total_engagement_time
        };
        Object.keys(fieldMap).forEach(k => $(`#${k}`).text(fieldMap[k] || '-'));

        $('#insp-p-user-match').html(row.user_matched
            ? '<span class="badge bg-success-lt">Matched in LMS</span>'
            : '<span class="badge bg-secondary-lt">Not in LMS (Snapshot Preserved)</span>'
        );

        $('#nonPtkpRowDetailModal').modal('show');
    });

    // Cancel Import Handling
    function handleCancel() {
        if (!currentToken) {
            resetToUpload();
            return;
        }

        $.ajax({
            url: config.cancelUrl,
            type: 'POST',
            data: {
                token: currentToken,
                _token: config.csrfToken
            },
            complete: function () {
                resetToUpload();
            }
        });
    }

    $('#non-ptkp-btn-reupload, #non-ptkp-btn-cancel-bottom').on('click', handleCancel);

    // Confirm & Execute Import
    $btnConfirm.on('click', function () {
        if (!currentToken || importInProgress) return;

        if (!confirm('Are you sure you want to import valid records into the database? Existing duplicates will be safely skipped.')) {
            return;
        }

        importInProgress = true;
        $btnConfirm.prop('disabled', true).addClass('disabled');
        $previewCard.hide();
        $importingCard.show();
        setStep(4);

        $.ajax({
            url: config.confirmUrl,
            type: 'POST',
            data: {
                token: currentToken,
                _token: config.csrfToken
            },
            success: function (res) {
                $importingCard.hide();
                importInProgress = false;
                if (res.success && res.result) {
                    renderResult(res.result);
                } else {
                    alert(res.message || 'Import execution failed.');
                    $previewCard.show();
                    setStep(3);
                    $btnConfirm.prop('disabled', false).removeClass('disabled');
                }
            },
            error: function (xhr) {
                $importingCard.hide();
                importInProgress = false;
                $previewCard.show();
                setStep(3);
                $btnConfirm.prop('disabled', false).removeClass('disabled');
                alert((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to execute import.');
            }
        });
    });

    function renderResult(result) {
        const resMap = {
            total: result.rows_processed, imported: result.imported, duplicates: result.skipped_duplicates,
            invalid: result.invalid, created: result.trainings_created, reused: result.trainings_reused
        };
        Object.keys(resMap).forEach(k => $(`#res-non-ptkp-${k}`).text(Number(resMap[k] || 0).toLocaleString()));

        currentToken = null;
        setStep(4);
        $resultCard.show();
    }

    function resetToUpload() {
        currentToken = null;
        allRowsData = [];
        resetFileSelection();
        $previewCard.hide();
        $loadingCard.hide();
        $importingCard.hide();
        $resultCard.hide();
        $uploadCard.show();
        setStep(1);
    }

    $('#non-ptkp-btn-import-another').on('click', resetToUpload);

    function escapeHtml(text) {
        if (!text) return '';
        return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
});
