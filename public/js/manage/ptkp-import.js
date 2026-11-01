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
    let isFiltering = false;

    const $uploadCard = $('#ptkp-upload-card');
    const $loadingCard = $('#ptkp-loading-card');
    const $previewCard = $('#ptkp-preview-card');
    const $resultCard = $('#ptkp-result-card');
    const $fileInput = $('#ptkp-file-input');
    const $dropzone = $('#ptkp-dropzone');
    const $selectedFilename = $('#ptkp-selected-filename');
    const $btnValidate = $('#ptkp-btn-validate');
    const $uploadError = $('#ptkp-upload-error');
    const $tableLoading = $('#ptkp-table-loading');

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

    function setStep(stepNumber) {
        $('.ptkp-step-item').removeClass('active completed');
        for (let i = 1; i <= 4; i++) {
            const $item = $(`#ptkp-step-ind-${i}`);
            if (i < stepNumber) $item.addClass('completed');
            else if (i === stepNumber) $item.addClass('active');
        }
    }

    $btnValidate.on('click', function () {
        if (!selectedFile) return;
        const formData = new FormData();
        formData.append('file', selectedFile);
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

        $uploadCard.hide();
        $loadingCard.show();
        setStep(2);
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
                    setStep(1);
                    $uploadError.text(res.message || 'Validation failed.').show();
                }
            },
            error: function (xhr) {
                $loadingCard.hide();
                $uploadCard.show();
                setStep(1);
                $uploadError.text((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to process file.').show();
            }
        });
    });

    function renderPreviewStage(data) {
        $previewCard.show();
        setStep(3);
        $('#ptkp-preview-filename').text(data.filename || selectedFile.name);
        const stats = data.stats || {};

        const totalRowsCount = (stats.total_rows !== undefined && stats.total_rows !== null) ? stats.total_rows : allRows.length;
        $('#ptkp-kpi-total').text(totalRowsCount);
        $('#ptkp-kpi-valid').text(stats.valid || 0);
        $('#ptkp-kpi-warning').text(stats.warning || 0);
        $('#ptkp-kpi-duplicate').text(stats.duplicate || 0);
        $('#ptkp-kpi-invalid').text(stats.invalid || 0);
        $('#ptkp-kpi-invalid-company').text(stats.invalid_company || 0);

        // Reset filter state to Total Rows (all)
        currentFilter = 'all';
        $('.ptkp-summary-filter-card').removeClass('active');
        $('.ptkp-summary-filter-card[data-filter="all"]').addClass('active');

        // Reset search queries
        $('#ptkp-search-empid, #ptkp-search-name, #ptkp-search-course, #ptkp-search-pic').val('');
        searchQuery = { empId: '', name: '', course: '', pic: '' };
        currentPage = 1;

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
            $('#ptkp-btn-confirm').prop('disabled', true);
        } else if (importable <= 0) {
            $('#ptkp-no-new-records-alert').hide();
            $('#ptkp-btn-confirm').prop('disabled', true);
        } else {
            $('#ptkp-no-new-records-alert').hide();
            $('#ptkp-btn-confirm').prop('disabled', false);
        }

        applyFilters();
    }

    // Summary Card Click Filter Handler
    $(document).on('click', '.ptkp-summary-filter-card', function () {
        if (isFiltering) return;
        const newFilter = $(this).data('filter');
        if (newFilter === currentFilter) return;

        isFiltering = true;
        $('.ptkp-summary-filter-card').removeClass('active');
        $(this).addClass('active');
        currentFilter = newFilter;
        currentPage = 1;

        // Visual loading state on preview table
        $tableLoading.stop(true, true).fadeIn(100);

        setTimeout(function () {
            try {
                applyFilters();
            } catch (err) {
                console.error('Filter execution failed:', err);
                alert('Filter error: ' + err.message);
            } finally {
                $tableLoading.stop(true, true).fadeOut(120);
                isFiltering = false;
            }
        }, 120);
    });

    // Keyboard support for Summary Cards (Enter / Space)
    $(document).on('keydown', '.ptkp-summary-filter-card', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            $(this).trigger('click');
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

    function applyFilters() {
        filteredRows = allRows.filter(function (row) {
            // 1. Status Filter from Active Summary Card
            if (currentFilter !== 'all') {
                if (currentFilter === 'valid' && row.status !== 'VALID') return false;
                if (currentFilter === 'warning' && row.status !== 'WARNING') return false;
                if (currentFilter === 'duplicate' && row.status !== 'DUPLICATE') return false;
                if (currentFilter === 'invalid' && row.status !== 'INVALID') return false;
                if (currentFilter === 'invalid_company' && row.status !== 'INVALID COMPANY') return false;
            }
            // 2. Combined Search Queries
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
                if (row.messages && row.messages.length) {
                    msgsHtml = '<div class="ptkp-row-msg text-muted">' + escapeHtml(row.messages[0]);
                    if (row.messages.length > 1) {
                        msgsHtml += ' <span class="badge bg-light text-secondary border">+' + (row.messages.length - 1) + '</span>';
                    }
                    msgsHtml += '</div>';
                }

                const rowData = row.data || {};
                $tbody.append(`
                    <tr class="${getRowClass(row.status)}">
                        <td class="text-center fw-medium text-muted">${row.no || (globalIndex + 1)}</td>
                        <td>${getStatusBadge(row.status)}</td>
                        <td class="fw-semibold font-monospace">${escapeHtml(row.employee_id || '-')}</td>
                        <td>
                            <div class="fw-semibold text-dark">${escapeHtml(row.name || '-')}</div>
                            <div class="text-muted extra-small" style="font-size: 0.72rem;">${escapeHtml(rowData.job_position || '-')}</div>
                        </td>
                        <td><span class="text-muted small">${escapeHtml(row.company || '-')}</span></td>
                        <td>
                            <div class="fw-semibold text-dark">${escapeHtml(row.course_name || '-')}</div>
                            <div class="text-muted extra-small" style="font-size: 0.72rem;">${escapeHtml(rowData.batch || '')} &bull; ${escapeHtml(rowData.start_date || '')}</div>
                        </td>
                        <td><span class="badge bg-light text-secondary border">${escapeHtml(row.type || '-')}</span></td>
                        <td><span class="text-muted small">${escapeHtml(row.pic || '-')}</span></td>
                        <td>${msgsHtml || '-'}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-ghost-primary rounded-circle btn-inspect-row" data-row-idx="${globalIndex}" title="Inspect Row">
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
            ['25. Start Date', d.start_date],
            ['26. End Date', d.finish_date || d.end_date],
            ['27. Start Time', d.start_time],
            ['28. End Time', d.end_time],
            ['29. Record Month', d.month],
            ['30. Requirement', d.requirement],
            ['31. Attendance', d.attendance === true ? 'Yes' : (d.attendance === false ? 'No' : (d.attendance || '-'))],
            ['32. Attendance Reason', d.attendance_reason],
            ['33. Bond Status', d.bond_status],
            ['34. Bond Duration', d.bond_duration],
            ['35. Bond End Date', d.bond_end_date],
            ['36. Bond Amount', d.bond_amount],
            ['37. Score (L1) - Course', d.l1_program],
            ['38. Score (L2)', d.assessment_score],
            ['39. L2 Status', d.l2_verbal],
            ['40. Score (L3)', d.l3_numerical],
            ['41. L3 Status', d.l3_verbal],
            ['42. Certificate Availability', d.certificate_availability],
            ['43. Certificate Institution', d.certificate_institution],
            ['44. Certificate Issued Date', d.certificate_issued_date],
            ['45. Certificate Expired Date', d.certificate_expired_date],
            ['46. Person in Charge (PIC)', d.pic]
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
        setStep(4);
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
        selectedFile = null; importToken = null; allRows = []; filteredRows = []; currentPage = 1; isSubmitting = false; isFiltering = false;
        $fileInput.val('');
        $selectedFilename.text('No file selected');
        $btnValidate.prop('disabled', true);
        $uploadError.hide().text('');
        $previewCard.hide(); $resultCard.hide(); $loadingCard.hide(); $uploadCard.show();
        $tableLoading.hide();
        currentFilter = 'all';
        $('.ptkp-summary-filter-card').removeClass('active');
        $('.ptkp-summary-filter-card[data-filter="all"]').addClass('active');
        $('#ptkp-search-empid, #ptkp-search-name, #ptkp-search-course, #ptkp-search-pic').val('');
        searchQuery = { empId: '', name: '', course: '', pic: '' };
        setStep(1);
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
