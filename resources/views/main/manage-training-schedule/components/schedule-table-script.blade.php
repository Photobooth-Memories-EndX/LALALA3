<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- Elements ---
    const searchInput = document.getElementById('scheduleSearchInput');
    const categorySelect = document.getElementById('scheduleCategorySelect');
    const statusSelect = document.getElementById('scheduleStatusSelect');
    const monthSelect = document.getElementById('scheduleMonthSelect');
    const yearSelect = document.getElementById('scheduleYearSelect');
    const btnReset = document.getElementById('btnResetScheduleFilter');
    const btnExport = document.getElementById('btnExportSchedule');
    const totalBadge = document.getElementById('scheduleTotalBadge');
    const tableBody = document.getElementById('scheduleTableBody');
    const emptyState = document.getElementById('scheduleTableEmpty');
    const addTrainingForm = document.getElementById('addTrainingForm');
    const addTrainingModalEl = document.getElementById('addTrainingModal');

    // --- More Filters Modal Elements ---
    const modalFilterCategory = document.getElementById('modalFilterCategory');
    const modalFilterStatus = document.getElementById('modalFilterStatus');
    const modalFilterInstructor = document.getElementById('modalFilterInstructor');
    const modalFilterLocation = document.getElementById('modalFilterLocation');
    const modalFilterFromDate = document.getElementById('modalFilterFromDate');
    const modalFilterToDate = document.getElementById('modalFilterToDate');
    const btnApplyMoreFilters = document.getElementById('btnApplyMoreScheduleFilters');
    const btnResetMoreFilters = document.getElementById('btnResetMoreScheduleFilters');

    // --- Update Export URLs with active filters (Excel, PDF, JPG) ---
    function updateExportUrls() {
        const params = new URLSearchParams();
        const category = (categorySelect?.value || modalFilterCategory?.value || '').trim();
        const status = (statusSelect?.value || modalFilterStatus?.value || '').trim();
        const month = (monthSelect?.value || '').trim();
        const year = (yearSelect?.value || '').trim();
        const search = (searchInput?.value || '').trim();

        if (category && category !== 'all') params.set('category', category);
        if (status && status !== 'all') params.set('status', status);
        if (month && month !== 'all') params.set('month', month);
        if (year && year !== 'all') params.set('year', year);
        if (search) {
            params.set('search', search);
            params.set('q', search);
        }

        const queryString = params.toString() ? '?' + params.toString() : '';

        const btnExcel = document.getElementById('btnExportExcel');
        const btnPdf = document.getElementById('btnExportPdf');
        const btnJpg = document.getElementById('btnExportJpg');
        const legacyBtn = document.getElementById('btnExportSchedule');

        const excelBase = '{{ route("manage.training.export.excel") }}';
        const pdfBase = '{{ route("manage.training.export.pdf") }}';
        const jpgBase = '{{ route("manage.training.export.jpg") }}';
        const legacyBase = '{{ route("manage.training.export") }}';

        if (btnExcel) btnExcel.href = excelBase + queryString;
        if (btnPdf) btnPdf.href = pdfBase + queryString;
        if (btnJpg) btnJpg.href = jpgBase + queryString;
        if (legacyBtn && legacyBtn.tagName === 'A') legacyBtn.href = legacyBase + queryString;
    }

    // Call initially on page load
    updateExportUrls();

    // --- Filter Logic ---
    function filterScheduleTable() {
        const query = (searchInput?.value || '').toLowerCase().trim();
        const category = (categorySelect?.value || modalFilterCategory?.value || '').trim();
        const status = (statusSelect?.value || modalFilterStatus?.value || '').trim();
        const month = (monthSelect?.value || '').trim();
        const year = (yearSelect?.value || '').trim();
        const instructor = (modalFilterInstructor?.value || '').trim().toLowerCase();
        const location = (modalFilterLocation?.value || '').trim().toLowerCase();
        const fromDate = modalFilterFromDate?.value || '';
        const toDate = modalFilterToDate?.value || '';

        const rows = document.querySelectorAll('#scheduleTableBody tr.schedule-table-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            const rowCat = row.getAttribute('data-category') || '';
            const rowStatus = row.getAttribute('data-status') || '';
            const rowMonth = row.getAttribute('data-month') || '';
            const rowYear = row.getAttribute('data-year') || '';
            const rowInstructor = (row.getAttribute('data-instructor') || '').toLowerCase();
            const rowLocation = (row.getAttribute('data-location') || '').toLowerCase();
            const rowDate = row.getAttribute('data-date') || '';

            const matchesSearch = !query || rowText.includes(query);
            const matchesCategory = !category || rowCat === category;
            const matchesStatus = !status || rowStatus === status || (row.getAttribute('data-status-label') || '').toLowerCase() === status.toLowerCase();
            const matchesMonth = !month || rowMonth === month || parseInt(rowMonth, 10) === parseInt(month, 10);
            const matchesYear = !year || rowYear === year;
            const matchesInstructor = !instructor || rowInstructor.includes(instructor);
            const matchesLocation = !location || rowLocation.includes(location);

            let matchesDate = true;
            if (fromDate && rowDate) {
                matchesDate = matchesDate && (rowDate >= fromDate);
            }
            if (toDate && rowDate) {
                matchesDate = matchesDate && (rowDate <= toDate);
            }

            if (matchesSearch && matchesCategory && matchesStatus && matchesMonth && matchesYear && matchesInstructor && matchesLocation && matchesDate) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Update Export URLs
        updateExportUrls();

        // Update Total Badge in Header Hero Card
        const totalCount = document.getElementById('scheduleTotalCount');
        if (totalCount) {
            totalCount.textContent = `${visibleCount} Training${visibleCount === 1 ? '' : 's'}`;
        } else if (totalBadge) {
            totalBadge.textContent = `Total Schedules: ${visibleCount} Training${visibleCount === 1 ? '' : 's'}`;
        }

        // Toggle Empty State for filter results
        if (emptyState) {
            if (visibleCount === 0 && rows.length > 0) {
                emptyState.classList.remove('d-none');
            } else {
                emptyState.classList.add('d-none');
            }
        }

        // Re-index visible rows
        let index = 1;
        rows.forEach(row => {
            if (row.style.display !== 'none') {
                const indexCell = row.querySelector('.schedule-row-index');
                if (indexCell) indexCell.textContent = index++;
            }
        });
    }

    // Form submit listener: allow GET request submission so active query parameters apply across all paginated pages
    const filterForm = document.getElementById('scheduleFilterForm');
    if (filterForm) {
        // Native GET submission preserves all active parameters in URL
    }

    // Attach quick filter event listeners & sync with modal
    if (searchInput) searchInput.addEventListener('input', filterScheduleTable);
    if (categorySelect) {
        categorySelect.addEventListener('change', function () {
            if (modalFilterCategory) modalFilterCategory.value = categorySelect.value;
            filterScheduleTable();
        });
    }
    if (statusSelect) {
        statusSelect.addEventListener('change', function () {
            if (modalFilterStatus) modalFilterStatus.value = statusSelect.value;
            filterScheduleTable();
        });
    }
    if (monthSelect) monthSelect.addEventListener('change', filterScheduleTable);
    if (yearSelect) yearSelect.addEventListener('change', filterScheduleTable);

    // Apply button inside More Filters modal
    if (btnApplyMoreFilters) {
        btnApplyMoreFilters.addEventListener('click', function () {
            if (modalFilterCategory && categorySelect) {
                categorySelect.value = modalFilterCategory.value;
            }
            if (modalFilterStatus && statusSelect) {
                statusSelect.value = modalFilterStatus.value;
            }
            if (filterForm) {
                filterForm.submit();
            } else {
                filterScheduleTable();
            }
        });
    }

    // Reset button inside More Filters modal
    if (btnResetMoreFilters) {
        btnResetMoreFilters.addEventListener('click', function () {
            if (modalFilterCategory) modalFilterCategory.value = '';
            if (modalFilterStatus) modalFilterStatus.value = '';
            if (modalFilterInstructor) modalFilterInstructor.value = '';
            if (modalFilterLocation) modalFilterLocation.value = '';
            if (modalFilterFromDate) modalFilterFromDate.value = '';
            if (modalFilterToDate) modalFilterToDate.value = '';
            if (categorySelect) categorySelect.value = '';
            if (statusSelect) statusSelect.value = '';
            if (monthSelect) monthSelect.value = '';
            if (yearSelect) yearSelect.value = '';
            filterScheduleTable();
        });
    }

    // Reset quick filter
    if (btnReset) {
        btnReset.addEventListener('click', function (e) {
            e.preventDefault();
            if (window.location.search) {
                window.location.href = "{{ route('manage.training-schedule.index') }}";
                return;
            }
            if (searchInput) searchInput.value = '';
            if (categorySelect) categorySelect.value = '';
            if (statusSelect) statusSelect.value = '';
            if (monthSelect) monthSelect.value = '';
            if (yearSelect) yearSelect.value = '';
            if (modalFilterCategory) modalFilterCategory.value = '';
            if (modalFilterStatus) modalFilterStatus.value = '';
            if (modalFilterInstructor) modalFilterInstructor.value = '';
            if (modalFilterLocation) modalFilterLocation.value = '';
            if (modalFilterFromDate) modalFilterFromDate.value = '';
            if (modalFilterToDate) modalFilterToDate.value = '';
            filterScheduleTable();
            updateExportUrls();
        });
    }

    // --- Modal Form Submit Simulation ---
    if (addTrainingForm) {
        addTrainingForm.addEventListener('submit', function (e) {
            e.preventDefault();

            if (!addTrainingForm.checkValidity()) {
                e.stopPropagation();
                addTrainingForm.classList.add('was-validated');
                return;
            }

            const title = document.getElementById('inputTrainingTitle')?.value || '';
            const category = document.getElementById('inputTrainingCategory')?.value || 'Leadership';
            const instructor = document.getElementById('inputTrainingInstructor')?.value || '';
            const location = document.getElementById('inputTrainingLocation')?.value || '';
            const quota = document.getElementById('inputTrainingQuota')?.value || 30;
            const status = document.getElementById('inputTrainingStatus')?.value || 'open';
            const startDate = document.getElementById('inputTrainingStartDate')?.value || '';
            const startTime = document.getElementById('inputTrainingStartTime')?.value || '08:00';
            const endTime = document.getElementById('inputTrainingEndTime')?.value || '16:00';

            // Format date
            let displayDate = startDate;
            let displayYear = '2026';
            if (startDate) {
                const d = new Date(startDate);
                displayYear = d.getFullYear().toString();
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                displayDate = `${d.getDate()} ${months[d.getMonth()]} ${displayYear}`;
            }

            // Category color
            let catColor = '#003b6d';
            let catBg = '#eef2ff';
            let catIcon = 'ti-school';
            if (category === 'Job Functional' || category === 'Technical Skill') {
                catColor = '#0284c7';
                catBg = '#e0f2fe';
                catIcon = 'ti-file-spreadsheet';
            } else if (category === 'Compliance' || category === 'Compliance & Safety') {
                catColor = '#d97706';
                catBg = '#fef3c7';
                catIcon = 'ti-shield-check';
            } else if (category === 'Leadership') {
                catColor = '#7c3aed';
                catBg = '#f3e8ff';
                catIcon = 'ti-crown';
            } else if (category === 'Innovation') {
                catColor = '#047857';
                catBg = '#ecfdf5';
                catIcon = 'ti-bulb';
            } else if (category === 'Language') {
                catColor = '#b91c1c';
                catBg = '#fef2f2';
                catIcon = 'ti-language';
            } else if (category === 'Basic & Common Duties') {
                catColor = '#475569';
                catBg = '#f1f5f9';
                catIcon = 'ti-clipboard-list';
            }

            // Status Badge
            let statusBadgeHtml = '<span class="badge bg-success-subtle text-success rounded-pill px-3 py-1 fw-semibold">Open Registration</span>';
            if (status === 'closed') {
                statusBadgeHtml = '<span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1 fw-semibold">Closed Registration</span>';
            } else if (status === 'draft') {
                statusBadgeHtml = '<span class="badge bg-warning-subtle text-warning rounded-pill px-3 py-1 fw-semibold">Draft</span>';
            }

            // Generate row HTML
            const newRow = document.createElement('tr');
            newRow.className = 'schedule-table-row text-center align-middle';
            newRow.setAttribute('data-category', category);
            newRow.setAttribute('data-status', status);
            newRow.setAttribute('data-year', displayYear);
            newRow.setAttribute('data-instructor', instructor);
            newRow.setAttribute('data-location', location);
            newRow.setAttribute('data-date', startDate);
            newRow.innerHTML = `
                <td class="text-muted fw-semibold schedule-row-index">1</td>
                <td class="fw-bold text-dark text-start" style="min-width: 200px;">
                    <div class="fs-3 training-title-text">${title}</div>
                    <div class="text-muted extra-small font-monospace">
                        Code: PTKP-NEW &bull; Batch 1 / ${displayYear}
                    </div>
                </td>
                <td>
                    <span class="badge rounded-pill px-3 py-1-5 fw-semibold" style="background-color: ${catBg}; color: ${catColor};">
                        ${category}
                    </span>
                </td>
                <td>
                    <div class="fw-semibold text-dark" style="min-width: 110px;">${displayDate}</div>
                </td>
                <td>
                    <div class="text-muted small" style="min-width: 120px;">${startTime} - ${endTime} WIB</div>
                </td>
                <td class="text-muted small">
                    ${location}
                </td>
                <td class="text-muted small">
                    <div class="fw-semibold text-dark">${instructor}</div>
                    <div class="text-muted extra-small">Training Instructor</div>
                </td>
                <td>
                    <div class="fw-semibold text-dark small">0 / ${quota} Seats</div>
                    <div class="progress progress-xs mt-1 mx-auto rounded-pill" style="height: 4px; max-width: 90px; background-color: #e2e8f0;">
                        <div class="progress-bar rounded-pill" role="progressbar" style="width: 0%; background-color: #003b6d;"></div>
                    </div>
                </td>
                <td>
                    ${statusBadgeHtml}
                </td>
                <td>
                    <div class="d-flex align-items-center justify-content-center gap-1">
                        <button type="button" class="btn btn-sm btn-icon btn-outline-secondary rounded-3 btn-view-syllabus" 
                                title="View Syllabus" data-bs-toggle="modal" data-bs-target="#syllabusModal" 
                                data-id="1" data-title="${title}" data-category="${category}" data-instructor="${instructor}">
                            <i class="ti ti-list-check fs-2"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-icon btn-outline-primary rounded-3 btn-edit-schedule" 
                                title="Edit Schedule" data-title="${title}">
                            <i class="ti ti-edit fs-2"></i>
                        </button>
                        <!-- <button type="button" class="btn btn-sm btn-icon btn-outline-info rounded-3 btn-participants-schedule" 
                                title="View Participants" data-id="1" data-title="${title}">
                            <i class="ti ti-users fs-2"></i>
                        </button> -->
                        <button type="button" class="btn btn-sm btn-icon btn-outline-danger rounded-3 btn-delete-schedule" 
                                title="Delete Schedule" data-title="${title}">
                            <i class="ti ti-trash fs-2"></i>
                        </button>
                    </div>
                </td>
            `;

            // Prepend new row
            if (tableBody) {
                tableBody.prepend(newRow);
            }

            // Close modal & reset form
            const modalInstance = bootstrap.Modal.getInstance(addTrainingModalEl) || new bootstrap.Modal(addTrainingModalEl);
            modalInstance.hide();
            addTrainingForm.reset();
            addTrainingForm.classList.remove('was-validated');

            // Refresh filter & count
            filterScheduleTable();

            // Notification Feedback
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Schedule Created!',
                    text: `Training "${title}" has been successfully added to the schedule.`,
                    timer: 2500,
                    showConfirmButton: false
                });
            } else {
                console.log(`Training "${title}" successfully created!`);
            }
        });
    }

    // --- Action Button Handlers (Participants) ---
    document.addEventListener('click', function (e) {
        // View Participants -> Switch to Inline Participant Tracking Tab
        const partBtn = e.target.closest('.btn-participants-schedule');
        if (partBtn) {
            e.preventDefault();
            const id = partBtn.getAttribute('data-id');
            const title = partBtn.getAttribute('data-title') || 'this training';

            // 1. Switch top pill to Participant Tracking & Approvals
            const trackingTabBtn = document.getElementById('tab-participant-tracking-btn');
            if (trackingTabBtn) {
                const tabTrigger = new bootstrap.Tab(trackingTabBtn);
                tabTrigger.show();
            }

            // 2. Select corresponding training in dropdown & render
            const selectInlineTraining = document.getElementById('selectInlineTraining');
            if (selectInlineTraining) {
                selectInlineTraining.value = id;
                if (typeof window.renderInlineTrackingByTraining === 'function') {
                    window.renderInlineTrackingByTraining(id);
                }
            }

            // 3. Ensure sub-tab is set to 'By Training Title'
            const trainingSubTabBtn = document.getElementById('tab-inline-training-btn');
            if (trainingSubTabBtn) {
                const subTabTrigger = new bootstrap.Tab(trainingSubTabBtn);
                subTabTrigger.show();
            }

            // 4. Smooth scroll to tracking section
            const trackingPane = document.getElementById('tab-participant-tracking-pane');
            if (trackingPane) {
                trackingPane.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            return;
        }
    });

    // --- Delete Confirmation Modal Dynamic Setup ---
    const deleteModal = document.getElementById('deleteScheduleModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (e) {
            const btn = e.relatedTarget;
            if (!btn) return;
            const title = btn.getAttribute('data-training-title') || '-';
            const code = btn.getAttribute('data-training-code') || '-';
            const date = btn.getAttribute('data-training-date') || '-';
            const deleteUrl = btn.getAttribute('data-delete-url') || '';

            const titleEl = document.getElementById('deleteModalTrainingTitle');
            const codeEl = document.getElementById('deleteModalTrainingCode');
            const dateEl = document.getElementById('deleteModalTrainingDate');
            const formEl = document.getElementById('deleteScheduleForm');

            if (titleEl) titleEl.textContent = title;
            if (codeEl) codeEl.textContent = code;
            if (dateEl) dateEl.textContent = date;
            if (formEl) formEl.action = deleteUrl;
        });
    }

    // --- Dynamic Syllabus Builder in Add Training Modal ---
    const btnAddSyllabus = document.getElementById('btnAddSyllabusRow');
    const syllabusContainer = document.getElementById('syllabusRowsContainer');

    function renumberSyllabusPoints() {
        if (!syllabusContainer) return;
        const rows = syllabusContainer.querySelectorAll('.syllabus-input-row');
        rows.forEach((row, idx) => {
            const pointInput = row.querySelector('.syllabus-point-num');
            if (pointInput) {
                pointInput.value = `Point ${idx + 1}`;
            }
            const removeBtn = row.querySelector('.btn-remove-syllabus-row');
            if (removeBtn) {
                removeBtn.disabled = (rows.length === 1 && idx === 0);
            }
        });
    }

    if (btnAddSyllabus && syllabusContainer) {
        btnAddSyllabus.addEventListener('click', function () {
            const currentCount = syllabusContainer.querySelectorAll('.syllabus-input-row').length;
            const newIndex = currentCount + 1;
            const newRow = document.createElement('div');
            newRow.className = 'card border border-light-subtle rounded-3 p-3 bg-white syllabus-input-row shadow-none';
            newRow.innerHTML = `
                <div class="row g-2 align-items-center">
                    <div class="col-md-2 col-3">
                        <label class="form-label extra-small text-muted mb-1 fw-bold">Point #</label>
                        <input type="text" class="form-control form-control-sm rounded-3 text-center fw-bold bg-light syllabus-point-num" value="Point ${newIndex}" readonly>
                    </div>
                    <div class="col-md-4 col-9">
                        <label class="form-label extra-small text-muted mb-1 fw-bold">Syllabus Title / Topic</label>
                        <input type="text" class="form-control form-control-sm rounded-3 syllabus-title-input" placeholder="e.g. Practical Implementation & Capstone">
                    </div>
                    <div class="col-md-5 col-10">
                        <label class="form-label extra-small text-muted mb-1 fw-bold">Short Outline / Highlights</label>
                        <input type="text" class="form-control form-control-sm rounded-3 syllabus-outline-input" placeholder="e.g. Industry simulation, department project...">
                    </div>
                    <div class="col-md-1 col-2 text-end">
                        <label class="form-label extra-small text-muted mb-1 d-none d-md-block">&nbsp;</label>
                        <button type="button" class="btn btn-sm btn-icon btn-outline-danger rounded-3 btn-remove-syllabus-row" title="Remove point">
                            <i class="ti ti-trash fs-2"></i>
                        </button>
                    </div>
                </div>
            `;
            syllabusContainer.appendChild(newRow);
            renumberSyllabusPoints();
        });

        syllabusContainer.addEventListener('click', function (e) {
            const removeBtn = e.target.closest('.btn-remove-syllabus-row');
            if (removeBtn && !removeBtn.disabled) {
                const row = removeBtn.closest('.syllabus-input-row');
                if (row) {
                    row.remove();
                    renumberSyllabusPoints();
                }
            }
        });
    }

    // Run initial filter calculation
    filterScheduleTable();
});
</script>
