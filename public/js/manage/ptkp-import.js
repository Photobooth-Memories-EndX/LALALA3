/**
 * PTKP Offline Training Import UI Handler
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

    const $uploadCard = $('#ptkp-upload-card');
    const $loadingCard = $('#ptkp-loading-card');
    const $previewCard = $('#ptkp-preview-card');
    const $resultCard = $('#ptkp-result-card');
    const $fileInput = $('#ptkp-file-input');
    const $dropzone = $('#ptkp-dropzone');
    const $selectedFilename = $('#ptkp-selected-filename');
    const $btnValidate = $('#ptkp-btn-validate');
    const $uploadError = $('#ptkp-upload-error');

    $dropzone.on('click', () => $fileInput.trigger('click'));
    $fileInput.on('click', (e) => e.stopPropagation());
    $fileInput.on('change', function () {
        if (this.files && this.files[0]) handleFileSelect(this.files[0]);
    });

    $dropzone.on('dragover dragenter', function (e) {
        e.preventDefault(); e.stopPropagation(); $(this).addClass('dragover');
    });
    $dropzone.on('dragleave dragend drop', function (e) {
        e.preventDefault(); e.stopPropagation(); $(this).removeClass('dragover');
    });
    $dropzone.on('drop', function (e) {
        const dt = e.originalEvent.dataTransfer;
        if (dt && dt.files && dt.files.length) handleFileSelect(dt.files[0]);
    });

    function handleFileSelect(file) {
        $uploadError.hide().text('');
        const ext = file.name.split('.').pop().toLowerCase();
        if (['xlsx', 'xls'].indexOf(ext) === -1) {
            $uploadError.text('Invalid format. Please select an .xlsx or .xls spreadsheet.').show();
            return;
        }
        if (file.size > 20 * 1024 * 1024) {
            $uploadError.text('File size exceeds 20MB limit.').show();
            return;
        }
        selectedFile = file;
        $selectedFilename.html('<strong>' + escapeHtml(file.name) + '</strong> (' + formatBytes(file.size) + ')');
        $btnValidate.prop('disabled', false);
    }

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
                } else {
                    $uploadCard.show();
                    $uploadError.text(res.message || 'Validation failed.').show();
                }
            },
            error: function (xhr) {
                $loadingCard.hide();
                $uploadCard.show();
                $uploadError.text((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to process file.').show();
            }
        });
    });

    function renderPreviewStage(data) {
        $previewCard.show();
        $('#ptkp-preview-filename').text(data.filename || selectedFile.name);
        const stats = data.stats || {};

        $('#ptkp-kpi-total').text(stats.total_rows || 0);
        $('#ptkp-kpi-valid').text(stats.valid || 0);
        $('#ptkp-kpi-warning').text(stats.warning || 0);
        $('#ptkp-kpi-duplicate').text(stats.duplicate || 0);
        $('#ptkp-kpi-invalid').text(stats.invalid || 0);
        $('#ptkp-kpi-invalid-company').text(stats.invalid_company || 0);

        $('#count-tab-all').text(allRows.length);
        $('#count-tab-valid').text(stats.valid || 0);
        $('#count-tab-warning').text(stats.warning || 0);
        $('#count-tab-duplicate').text(stats.duplicate || 0);
        $('#count-tab-invalid').text(stats.invalid || 0);
        $('#count-tab-invalid-company').text(stats.invalid_company || 0);

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
        $('#ptkp-btn-confirm-count').text(importable);
        $('#ptkp-btn-confirm').prop('disabled', !(data.can_confirm && importable > 0));

        applyFilters();
    }

    $('.ptkp-filter-tab').on('click', function () {
        $('.ptkp-filter-tab').removeClass('active');
        $(this).addClass('active');
        currentFilter = $(this).data('filter');
        currentPage = 1;
        applyFilters();
    });

    $('#ptkp-search-empid, #ptkp-search-name, #ptkp-search-course, #ptkp-search-pic').on('input', function () {
        searchQuery.empId = $('#ptkp-search-empid').val().trim().toLowerCase();
        searchQuery.name = $('#ptkp-search-name').val().trim().toLowerCase();
        searchQuery.course = $('#ptkp-search-course').val().trim().toLowerCase();
        searchQuery.pic = $('#ptkp-search-pic').val().trim().toLowerCase();
        currentPage = 1;
        applyFilters();
    });

    $('#ptkp-page-size').on('change', function () {
        pageSize = parseInt($(this).val(), 10) || 25;
        currentPage = 1;
        renderTableRows();
    });

    function applyFilters() {
        filteredRows = allRows.filter(function (row) {
            if (currentFilter !== 'all') {
                if (currentFilter === 'valid' && row.status !== 'VALID') return false;
                if (currentFilter === 'warning' && row.status !== 'WARNING') return false;
                if (currentFilter === 'duplicate' && row.status !== 'DUPLICATE') return false;
                if (currentFilter === 'invalid' && row.status !== 'INVALID') return false;
                if (currentFilter === 'invalid_company' && row.status !== 'INVALID COMPANY') return false;
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
            $tbody.append('<tr><td colspan="9" class="text-center py-4 text-muted">No records match the filter.</td></tr>');
        } else {
            pageItems.forEach(function (row, idx) {
                const globalIndex = startIdx + idx;
                let msgsHtml = '';
                if (row.messages && row.messages.length) {
                    msgsHtml = '<div class="ptkp-row-msg text-muted">' + escapeHtml(row.messages[0]);
                    if (row.messages.length > 1) {
                        msgsHtml += ' <span class="badge bg-light text-secondary border">+' + (row.messages.length - 1) + '</span>';
                    }
                    msgsHtml += '</div>';
                }

                $tbody.append(`
                    <tr class="${getRowClass(row.status)}">
                        <td class="text-center fw-medium text-muted">${row.no || (globalIndex + 1)}</td>
                        <td>${getStatusBadge(row.status)}</td>
                        <td class="fw-semibold font-monospace">${escapeHtml(row.employee_id || '-')}</td>
                        <td>
                            <div class="fw-semibold text-dark">${escapeHtml(row.name || '-')}</div>
                            <div class="text-muted extra-small" style="font-size: 0.72rem;">${escapeHtml(row.data.job_position || '-')}</div>
                        </td>
                        <td><span class="text-muted small">${escapeHtml(row.company || '-')}</span></td>
                        <td>
                            <div class="fw-semibold text-dark">${escapeHtml(row.course_name || '-')}</div>
                            <div class="text-muted extra-small" style="font-size: 0.72rem;">${escapeHtml(row.data.batch || '')} &bull; ${escapeHtml(row.data.start_date || '')}</div>
                        </td>
                        <td><span class="badge bg-light text-secondary border">${escapeHtml(row.type || '-')}</span></td>
                        <td><span class="text-muted small">${escapeHtml(row.pic || '-')}</span></td>
                        <td>
                            ${msgsHtml}
                            <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none btn-inspect-row mt-1" data-row-idx="${globalIndex}">
                                Inspect <i class="ti ti-chevron-right"></i>
                            </button>
                        </td>
                    </tr>
                `);
            });
        }

        $('#ptkp-pagination-info').text(total > 0 ? `Showing ${startIdx + 1} to ${endIdx} of ${total} records (Total file rows: ${allRows.length})` : 'No records');
        renderPaginationControls(totalPages);
    }

    function getStatusBadge(status) {
        switch (status) {
            case 'VALID': return '<span class="badge badge-ptkp-valid px-2 py-1"><i class="ti ti-check me-1"></i>VALID</span>';
            case 'WARNING': return '<span class="badge badge-ptkp-warning px-2 py-1"><i class="ti ti-alert-triangle me-1"></i>WARNING</span>';
            case 'DUPLICATE': return '<span class="badge badge-ptkp-duplicate px-2 py-1"><i class="ti ti-copy me-1"></i>DUPLICATE</span>';
            case 'INVALID': return '<span class="badge badge-ptkp-invalid px-2 py-1"><i class="ti ti-x me-1"></i>INVALID</span>';
            case 'INVALID COMPANY': return '<span class="badge badge-ptkp-invalid-company px-2 py-1"><i class="ti ti-building me-1"></i>INVALID COMPANY</span>';
            default: return `<span class="badge bg-secondary">${escapeHtml(status)}</span>`;
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

        $ul.append(`<li class="page-item ${currentPage === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a></li>`);
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

        $ul.append(`<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${currentPage + 1}">Next</a></li>`);

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
        $('#modal-inspect-status').html(getStatusBadge(row.status));

        let msgsHtml = '';
        if (row.messages && row.messages.length) {
            msgsHtml = '<div class="alert alert-warning border-0 rounded-3 small mb-3 py-2 px-3"><ul class="mb-0 ps-3">';
            row.messages.forEach((m) => msgsHtml += '<li>' + escapeHtml(m) + '</li>');
            msgsHtml += '</ul></div>';
        }
        $('#modal-inspect-messages').html(msgsHtml);

        const d = row.data || {};
        const fields = [
            ['No', row.no], ['Employee ID', row.employee_id],
            ['Employee Name (from users)', d.name], ['Grade (from users)', d.grade],
            ['Job Position (from users)', d.job_position], ['Team (from users)', d.team],
            ['Department (from users)', d.department], ['Company (Excel)', d.company],
            ['Course Title', d.course_name], ['Sub Course Title', d.sub_course_name],
            ['Type', d.type], ['Category', d.category], ['Level', d.level], ['Batch', d.batch],
            ['Main Classification', d.main_classification], ['Sub Classification', d.sub_classification],
            ['Delivery Method', d.delivery_method], ['Sub Delivery Method', d.sub_delivery_method],
            ['Provider', d.provider], ['Sub Provider', d.sub_provider], ['Provider Name', d.provider_name],
            ['Venue', d.venue], ['Instructor', d.instructor],
            ['Course Duration (seconds)', d.duration_seconds], ['Learning Hours', d.learning_hours],
            ['% Progress', d.progress], ['Progress Status', d.progress_status],
            ['Start Date', d.start_date], ['End Date', d.finish_date],
            ['Start Time', d.start_time], ['End Time', d.end_time],
            ['Record Month', d.month], ['Requirement', d.requirement],
            ['Attendance', d.attendance === true ? 'Yes' : (d.attendance === false ? 'No' : '-')],
            ['Attendance Reason', d.attendance_reason], ['Bond Status', d.bond_status],
            ['Bond Duration', d.bond_duration], ['Bond End Date', d.bond_end_date],
            ['Bond Amount', d.bond_amount], ['Score (L1)', d.l1_program],
            ['Score (L2)', d.assessment_score], ['L2 Status', d.l2_verbal],
            ['Score (L3)', d.l3_numerical], ['L3 Status', d.l3_verbal],
            ['Person in Charge (PIC)', d.pic],
            ['Certificate Availability', d.certificate_availability],
            ['Certificate Institution', d.certificate_institution],
            ['Certificate Issued Date', d.certificate_issued_date],
            ['Certificate Expired Date', d.certificate_expired_date],
        ];

        let tbodyHtml = '';
        fields.forEach(([label, val]) => {
            tbodyHtml += `<tr><th>${escapeHtml(label)}</th><td>${escapeHtml(val !== null && val !== undefined ? String(val) : '-')}</td></tr>`;
        });
        $('#modal-inspect-tbody').html(tbodyHtml);

        new bootstrap.Modal(document.getElementById('ptkpInspectionModal')).show();
    });

    $('#ptkp-btn-cancel-preview, #ptkp-btn-footer-cancel').on('click', function () {
        if (!confirm('Cancel this import session?')) return;
        if (importToken) {
            $.post(window.ptkpRoutes.cancel, { token: importToken, _token: $('meta[name="csrf-token"]').attr('content') });
        }
        resetToStage1();
    });

    $('#ptkp-btn-confirm').on('click', function () {
        if (isSubmitting || !importToken) return;
        if (!confirm('Proceed to import the valid records into OWL LMS?')) return;

        isSubmitting = true;
        const $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Importing Records...');

        $.ajax({
            url: window.ptkpRoutes.confirm,
            type: 'POST',
            data: { token: importToken, _token: $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                isSubmitting = false;
                $previewCard.hide();
                renderResultStage(res.result || {});
            },
            error: function (xhr) {
                isSubmitting = false;
                $btn.prop('disabled', false).html('<i class="ti ti-check me-1"></i> Confirm & Import');
                alert('Error: ' + ((xhr.responseJSON && xhr.responseJSON.message) || 'Import failed.'));
            }
        });
    });

    function renderResultStage(res) {
        $resultCard.show();
        $('#res-total-processed').text(res.total_rows_processed || 0);
        $('#res-imported-participants').text(res.imported || 0);
        $('#res-duplicates-skipped').text(res.skipped_duplicates || 0);
        $('#res-invalid-skipped').text(res.skipped_invalid || 0);
        $('#res-warnings-imported').text(res.warnings_imported || 0);
        $('#res-invalid-company-imported').text(res.invalid_company_imported || 0);
        $('#res-trainings-created').text(res.trainings_created || 0);
        $('#res-trainings-reused').text(res.trainings_reused || 0);
    }

    $('#ptkp-btn-import-another').on('click', resetToStage1);

    function resetToStage1() {
        selectedFile = null; importToken = null; allRows = []; filteredRows = []; currentPage = 1; isSubmitting = false;
        $fileInput.val('');
        $selectedFilename.text('No file selected');
        $btnValidate.prop('disabled', true);
        $uploadError.hide().text('');
        $previewCard.hide(); $resultCard.hide(); $loadingCard.hide(); $uploadCard.show();
    }

    function formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024, sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
})(jQuery);
