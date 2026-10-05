<script>
    document.addEventListener('DOMContentLoaded', function () {
        const $addForm = $('#offline-manual-add-form');
        const $addAlert = $('#add-training-alert');
        const $btnSubmitAdd = $('#btn-submit-add-training');

        let addParticipants = [];
        let currentActiveParticipantIndex = -1;
        let empSearchTimer = null;

        // --- Helper: Format Month from Date ---
        function deriveMonthString(dateStr) {
            if (!dateStr) return '';
            const parts = dateStr.split('-');
            if (parts.length < 3) return '';
            const d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
            if (isNaN(d.getTime())) return '';
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            return `${months[d.getMonth()]} ${d.getFullYear()}`;
        }

        // --- Helper: Parse duration string to hours ---
        function parseDurationToHours(durStr) {
            if (!durStr) return null;
            const parts = durStr.trim().split(':');
            if (parts.length >= 2) {
                const h = parseFloat(parts[0]) || 0;
                const m = parseFloat(parts[1]) || 0;
                const s = parseFloat(parts[2]) || 0;
                return (h + (m / 60) + (s / 3600)).toFixed(1);
            }
            return null;
        }

        // --- Helper: Escape HTML ---
        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            return String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // --- Select2 Initialization for Master Option Dropdowns ---
        if ($.fn.select2) {
            $('.select2-master-option').each(function () {
                const placeholder = $(this).data('placeholder') || 'Select option';
                $(this).select2({
                    theme: 'bootstrap-5',
                    placeholder: placeholder,
                    allowClear: true,
                    width: 'resolve'
                });
            });
        }

        // --- Helper: Get Current KPI Year from Start Date or fallback ---
        function getCurrentKpiYear() {
            const startVal = $('#add-start-date').val();
            if (startVal && startVal.length >= 4) {
                const y = parseInt(startVal.substring(0, 4), 10);
                if (!isNaN(y) && y >= 1970 && y <= 2100) {
                    return y;
                }
            }
            return {{ $currentYear ?? date('Y') }};
        }

        // --- Helper: Update Dynamic Yearly Corporate KPI Labels & Options ---
        function updateKpiYearDisplay() {
            const kpiYear = getCurrentKpiYear();
            const shortYear = "'" + String(kpiYear).slice(-2);
            $('#label-counted-corporate-kpi').text(`Counted as ${shortYear} Corporate KPI`);
            $('#label-sub-corporate-kpi').text(`Sub ${shortYear} Corporate KPI`);

            // Update button data-year attribute
            $('.btn-add-option-modal[data-type="sub_corporate_kpi"]').data('year', kpiYear);

            // Fetch and refresh sub_corporate_kpi datalist for this year
            $.ajax({
                url: "{{ route('manage.record.offline.options.index') }}",
                type: 'GET',
                data: { type: 'sub_corporate_kpi', year: kpiYear },
                success: function (res) {
                    const results = (res && res.results) ? res.results : [];
                    const $dl = $('#sub-corporate-kpi-datalist');
                    $dl.empty();
                    results.forEach(function (item) {
                        $dl.append(`<option value="${escapeHtml(item.value)}"></option>`);
                    });
                }
            });
        }

        // --- Schedule & Duration Watchers ---
        $('#add-start-date').on('change', function () {
            const startVal = $(this).val();
            const monthText = deriveMonthString(startVal);
            $('#add-month').val(monthText);

            const $finishDate = $('#add-finish-date');
            if (startVal && (!$finishDate.val() || $finishDate.val() < startVal)) {
                $finishDate.val(startVal);
            }
            updateKpiYearDisplay();
            updateSection4Review();
        });

        $('#add-finish-date').on('change', function () {
            updateSection4Review();
        });

        $('#add-duration').on('change', function () {
            const val = $(this).val();
            const $learningHours = $('#add-learning-hours');
            if (!$learningHours.val() || $learningHours.val() === '0') {
                const suggested = parseDurationToHours(val);
                if (suggested) {
                    $learningHours.val(suggested);
                }
            }
            updateSection4Review();
        });

        // Trigger review update on any Section 1 input change
        $('#section-1-training-info input, #section-1-training-info select').on('input change', function () {
            updateSection4Review();
        });

        // --- Section 2: Participant Search & Autocomplete ---
        const $empSearchInput = $('#participant-search-input');
        const $empSearchResults = $('#participant-search-results');

        $empSearchInput.on('input', function () {
            clearTimeout(empSearchTimer);
            const query = $(this).val().trim();
            if (query.length < 2) {
                $empSearchResults.hide().empty();
                return;
            }

            empSearchTimer = setTimeout(function () {
                $.ajax({
                    url: "{{ route('manage.record.offline.search-employees') }}",
                    type: 'GET',
                    data: { q: query },
                    success: function (res) {
                        const list = (res && res.results) ? res.results : [];
                        if (list.length === 0) {
                            $empSearchResults.html('<div class="p-2 text-muted small text-center">No employee found.</div>').show();
                            return;
                        }

                        let html = '';
                        list.forEach(function (emp) {
                            const empJson = escapeHtml(JSON.stringify(emp));
                            html += `
                                <a href="#" class="dropdown-item py-2 border-bottom autocomplete-emp-item" data-employee='${empJson}'>
                                    <div class="fw-semibold text-dark">${escapeHtml(emp.name)}</div>
                                    <div class="text-muted extra-small d-flex align-items-center gap-2">
                                        <span class="badge bg-light text-secondary border">${escapeHtml(emp.employee_id || emp.id_emp || '-')}</span>
                                        <span>${escapeHtml(emp.job_position || emp.department || 'Employee')}</span>
                                        <span>&bull; ${escapeHtml(emp.company || 'PT Krakatau Posco')}</span>
                                    </div>
                                </a>
                            `;
                        });
                        $empSearchResults.html(html).show();
                    },
                    error: function () {
                        $empSearchResults.html('<div class="p-2 text-danger small text-center">Search error.</div>').show();
                    }
                });
            }, 300);
        });

        $('#btn-search-participant').on('click', function () {
            $empSearchInput.trigger('input');
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('#participant-search-input, #participant-search-results').length) {
                $empSearchResults.hide();
            }
        });

        // Selecting from search dropdown
        $(document).on('click', '.autocomplete-emp-item', function (e) {
            e.preventDefault();
            const empData = $(this).data('employee');
            if (!empData) return;

            addParticipantToList({
                user_id: empData.user_id || empData.id || null,
                employee_id: empData.employee_id || empData.id_emp || '',
                name: empData.name || '',
                grade: empData.grade || '',
                job_position: empData.job_position || '',
                team: empData.team || '',
                department: empData.department || '',
                company: empData.company || 'PT Krakatau Posco',
            });

            $empSearchInput.val('');
            $empSearchResults.hide().empty();
        });

        // --- Section 2: Manual Participant Entry ---
        $('#btn-toggle-manual-participant').on('click', function () {
            $('#manual-participant-card').slideToggle(200);
        });

        $('#btn-cancel-manual-participant').on('click', function () {
            $('#manual-participant-card').slideUp(200);
        });

        $('#btn-add-manual-participant').on('click', function () {
            const empId = $('#manual-emp-id').val().trim();
            const empName = $('#manual-emp-name').val().trim();

            if (!empId || !empName) {
                alert('Employee ID / NIP and Name are required for manual participant entry.');
                return;
            }

            addParticipantToList({
                user_id: null,
                employee_id: empId,
                name: empName,
                grade: $('#manual-emp-grade').val().trim(),
                job_position: $('#manual-emp-position').val().trim(),
                team: $('#manual-emp-team').val().trim(),
                department: $('#manual-emp-dept').val().trim(),
                company: $('#manual-emp-company').val().trim() || 'PT Krakatau Posco',
            });

            // Reset manual form fields
            $('#manual-emp-id').val('');
            $('#manual-emp-name').val('');
            $('#manual-emp-grade').val('');
            $('#manual-emp-position').val('');
            $('#manual-emp-team').val('');
            $('#manual-emp-dept').val('');
            $('#manual-participant-card').slideUp(200);
        });

        // Generic function to add participant with duplicate check
        function addParticipantToList(empObj) {
            const normEmpId = (empObj.employee_id || '').trim().toLowerCase();
            const normUserId = empObj.user_id ? parseInt(empObj.user_id, 10) : null;

            // Check if already in list
            const isDuplicate = addParticipants.some(function (p) {
                if (normUserId && p.user_id && parseInt(p.user_id, 10) === normUserId) return true;
                if (normEmpId && (p.employee_id || '').trim().toLowerCase() === normEmpId) return true;
                return false;
            });

            if (isDuplicate) {
                alert(`Employee [${empObj.name} (${empObj.employee_id})] is already enrolled in this training.`);
                return;
            }

            const defaultLearningHours = parseFloat($('#add-learning-hours').val()) || null;

            // Push participant with default learning results
            addParticipants.push({
                user_id: normUserId,
                employee_id: empObj.employee_id || '',
                name: empObj.name || '',
                grade: empObj.grade || '',
                job_position: empObj.job_position || '',
                team: empObj.team || '',
                department: empObj.department || '',
                company: empObj.company || 'PT Krakatau Posco',
                learning_hours: defaultLearningHours,
                progress: 100,
                assessment_score: '',
                certificate: 'Yes',
                attendance: 1,
                attendance_reason: '',
                bond_status: 'No',
                bond_end_date: '',
                bond_amount: '',
                l1_program: '',
                l2_numerical: '',
                l2_verbal: '',
                l3_numerical: '',
                l3_verbal: '',
                pic: ''
            });

            currentActiveParticipantIndex = addParticipants.length - 1;
            renderParticipantsTable();
            populateActiveParticipantSelect();
            loadActiveParticipantForm(currentActiveParticipantIndex);
            updateSection4Review();
        }

        // Render Section 2 Participants Table
        function renderParticipantsTable() {
            const count = addParticipants.length;
            $('#badge-participants-count').text(`${count} Enrolled`);

            if (count === 0) {
                $('#selected-participants-tbody').html(`
                    <tr id="empty-participants-row">
                        <td colspan="9" class="text-center text-muted py-4 small">
                            <i class="ti ti-users fs-2 d-block mb-1 text-secondary"></i>
                            No participants added yet. Search employee or click Input Manually.
                        </td>
                    </tr>
                `);
                $('#no-participants-alert').show();
                $('#active-participant-form-container').hide();
                return;
            }

            $('#no-participants-alert').hide();
            $('#active-participant-form-container').show();

            let rows = '';
            addParticipants.forEach(function (p, index) {
                const isActive = (index === currentActiveParticipantIndex);
                const activeClass = isActive ? 'table-warning fw-medium' : '';
                rows += `
                    <tr class="${activeClass} participant-table-row" data-index="${index}" style="cursor: pointer;">
                        <td class="text-center text-muted">${index + 1}</td>
                        <td><span class="badge bg-light text-secondary border font-monospace">${escapeHtml(p.employee_id || '-')}</span></td>
                        <td>
                            <div class="fw-semibold text-dark">${escapeHtml(p.name)}</div>
                            ${p.user_id ? '<span class="badge bg-green-lt extra-small py-0">LMS User</span>' : ''}
                        </td>
                        <td>${escapeHtml(p.grade || '-')}</td>
                        <td>${escapeHtml(p.job_position || '-')}</td>
                        <td>${escapeHtml(p.team || '-')}</td>
                        <td>${escapeHtml(p.department || '-')}</td>
                        <td>${escapeHtml(p.company || '-')}</td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-ghost-danger p-1 btn-remove-participant" data-index="${index}" title="Remove participant">
                                <i class="ti ti-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });

            $('#selected-participants-tbody').html(rows);
        }

        // Click row to switch active participant
        $(document).on('click', '.participant-table-row', function (e) {
            if ($(e.target).closest('.btn-remove-participant').length) return;
            const idx = parseInt($(this).data('index'), 10);
            if (!isNaN(idx) && idx !== currentActiveParticipantIndex) {
                saveCurrentActiveParticipantForm();
                currentActiveParticipantIndex = idx;
                populateActiveParticipantSelect();
                loadActiveParticipantForm(currentActiveParticipantIndex);
                renderParticipantsTable();
            }
        });

        // Remove participant
        $(document).on('click', '.btn-remove-participant', function (e) {
            e.stopPropagation();
            const removeIdx = parseInt($(this).data('index'), 10);
            if (isNaN(removeIdx) || removeIdx < 0 || removeIdx >= addParticipants.length) return;

            addParticipants.splice(removeIdx, 1);

            if (addParticipants.length === 0) {
                currentActiveParticipantIndex = -1;
            } else if (currentActiveParticipantIndex >= addParticipants.length) {
                currentActiveParticipantIndex = addParticipants.length - 1;
            }

            renderParticipantsTable();
            populateActiveParticipantSelect();
            if (currentActiveParticipantIndex >= 0) {
                loadActiveParticipantForm(currentActiveParticipantIndex);
            }
            updateSection4Review();
        });

        // --- Section 3: Learning Results Management ---
        function populateActiveParticipantSelect() {
            const $select = $('#select-active-participant');
            $select.empty();

            if (addParticipants.length === 0) {
                $select.html('<option value="">No participants enrolled</option>');
                $('#participant-indicator-text').text('0 of 0');
                $('#btn-prev-participant').prop('disabled', true);
                $('#btn-next-participant').prop('disabled', true);
                return;
            }

            addParticipants.forEach(function (p, idx) {
                $select.append(`<option value="${idx}">${idx + 1}. ${p.name} (${p.employee_id})</option>`);
            });

            $select.val(currentActiveParticipantIndex);
            $('#participant-indicator-text').text(`${currentActiveParticipantIndex + 1} of ${addParticipants.length}`);
            $('#btn-prev-participant').prop('disabled', currentActiveParticipantIndex <= 0);
            $('#btn-next-participant').prop('disabled', currentActiveParticipantIndex >= addParticipants.length - 1);
        }

        $('#select-active-participant').on('change', function () {
            saveCurrentActiveParticipantForm();
            const newIdx = parseInt($(this).val(), 10);
            if (!isNaN(newIdx) && newIdx >= 0 && newIdx < addParticipants.length) {
                currentActiveParticipantIndex = newIdx;
                populateActiveParticipantSelect();
                loadActiveParticipantForm(currentActiveParticipantIndex);
                renderParticipantsTable();
            }
        });

        $('#btn-prev-participant').on('click', function () {
            if (currentActiveParticipantIndex > 0) {
                saveCurrentActiveParticipantForm();
                currentActiveParticipantIndex--;
                populateActiveParticipantSelect();
                loadActiveParticipantForm(currentActiveParticipantIndex);
                renderParticipantsTable();
            }
        });

        $('#btn-next-participant').on('click', function () {
            if (currentActiveParticipantIndex < addParticipants.length - 1) {
                saveCurrentActiveParticipantForm();
                currentActiveParticipantIndex++;
                populateActiveParticipantSelect();
                loadActiveParticipantForm(currentActiveParticipantIndex);
                renderParticipantsTable();
            }
        });

        // Load active participant into Section 3 form
        function loadActiveParticipantForm(idx) {
            if (idx < 0 || idx >= addParticipants.length) return;
            const p = addParticipants[idx];

            $('#res-progress').val(p.progress !== undefined ? p.progress : 100);
            updateStatusBadgeFromProgress(p.progress !== undefined ? p.progress : 100);

            $('#res-assessment-score').val(p.assessment_score || '');
            $('#res-certificate').val(p.certificate || 'Yes');

            if (p.attendance == 1 || p.attendance === true || p.attendance === '1') {
                $('#res-attendance-yes').prop('checked', true);
            } else {
                $('#res-attendance-no').prop('checked', true);
            }
            $('#res-attendance-reason').val(p.attendance_reason || '');

            const isBonded = (p.bond_status === 'Yes');
            $('#res-bond-status').val(p.bond_status || 'No');
            $('#res-bond-end-date').val(p.bond_end_date || '').prop('disabled', !isBonded);
            $('#res-bond-amount').val(p.bond_amount || '').prop('disabled', !isBonded);

            $('#res-l1-program').val(p.l1_program || '');
            $('#res-l2-numerical').val(p.l2_numerical || '');
            $('#res-l2-verbal').val(p.l2_verbal || '');
            $('#res-l3-numerical').val(p.l3_numerical || '');
            $('#res-l3-verbal').val(p.l3_verbal || '');
            $('#res-pic').val(p.pic || '');

            $('#save-result-status-msg').text(`Configuring results for: ${p.name}`);
        }

        // Save current Section 3 inputs back into active participant object
        function saveCurrentActiveParticipantForm() {
            if (currentActiveParticipantIndex < 0 || currentActiveParticipantIndex >= addParticipants.length) return;
            const p = addParticipants[currentActiveParticipantIndex];

            const rawProg = parseFloat($('#res-progress').val());
            p.progress = isNaN(rawProg) ? 100 : Math.min(100, Math.max(0, rawProg));
            p.assessment_score = $('#res-assessment-score').val() !== '' ? parseFloat($('#res-assessment-score').val()) : null;
            p.certificate = $('#res-certificate').val();
            p.attendance = $('#res-attendance-yes').is(':checked') ? 1 : 0;
            p.attendance_reason = $('#res-attendance-reason').val().trim();
            p.bond_status = $('#res-bond-status').val();
            p.bond_end_date = p.bond_status === 'Yes' ? $('#res-bond-end-date').val() : null;
            p.bond_amount = p.bond_status === 'Yes' && $('#res-bond-amount').val() !== '' ? parseFloat($('#res-bond-amount').val()) : null;
            p.l1_program = $('#res-l1-program').val().trim();
            p.l2_numerical = $('#res-l2-numerical').val() !== '' ? parseFloat($('#res-l2-numerical').val()) : null;
            p.l2_verbal = $('#res-l2-verbal').val().trim();
            p.l3_numerical = $('#res-l3-numerical').val() !== '' ? parseFloat($('#res-l3-numerical').val()) : null;
            p.l3_verbal = $('#res-l3-verbal').val().trim();
            p.pic = $('#res-pic').val().trim();
        }

        // Live status badge updater
        function updateStatusBadgeFromProgress(val) {
            const $badge = $('#res-status-badge');
            const num = parseFloat(val);
            if (isNaN(num) || num >= 100) {
                $badge.attr('class', 'badge bg-success-lt fw-bold').text('FINISHED');
            } else if (num > 0) {
                $badge.attr('class', 'badge bg-warning-lt fw-bold').text('ONGOING');
            } else {
                $badge.attr('class', 'badge bg-secondary-lt fw-bold').text('NOT STARTED');
            }
        }

        $('#res-progress').on('input', function () {
            updateStatusBadgeFromProgress($(this).val());
            saveCurrentActiveParticipantForm();
            updateSection4Review();
        });

        $('#res-bond-status').on('change', function () {
            const isBonded = ($(this).val() === 'Yes');
            $('#res-bond-end-date').prop('disabled', !isBonded);
            $('#res-bond-amount').prop('disabled', !isBonded);
            if (!isBonded) {
                $('#res-bond-end-date').val('');
                $('#res-bond-amount').val('');
            }
            saveCurrentActiveParticipantForm();
            updateSection4Review();
        });

        $('input[name="res_attendance"]').on('change', function () {
            saveCurrentActiveParticipantForm();
            updateSection4Review();
        });

        $('#section-3-results input, #section-3-results select').on('input change', function () {
            saveCurrentActiveParticipantForm();
            updateSection4Review();
        });

        $('#btn-save-participant-result').on('click', function () {
            saveCurrentActiveParticipantForm();
            updateSection4Review();
            $('#save-result-status-msg').html('<span class="text-success fw-bold"><i class="ti ti-check"></i> Result saved for this participant!</span>');
            setTimeout(function () {
                if (currentActiveParticipantIndex >= 0 && addParticipants[currentActiveParticipantIndex]) {
                    $('#save-result-status-msg').text(`Configuring results for: ${addParticipants[currentActiveParticipantIndex].name}`);
                }
            }, 2000);
        });

        // Helper: Format Date for Review Display (e.g., "10 Aug 2026")
        function formatReviewDate(dateStr) {
            if (!dateStr || dateStr === '-') return '-';
            const parts = String(dateStr).trim().split('-');
            if (parts.length === 3) {
                const year = parseInt(parts[0], 10);
                const month = parseInt(parts[1], 10) - 1;
                const day = parseInt(parts[2], 10);
                const d = new Date(year, month, day);
                if (!isNaN(d.getTime())) {
                    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    const dayPadded = String(d.getDate()).padStart(2, '0');
                    return `${dayPadded} ${months[d.getMonth()]} ${d.getFullYear()}`;
                }
            }
            return String(dateStr);
        }

        // --- Section 4: Dynamic Review Update — Full Canonical 44-Column Record Preview ---
        function updateSection4Review() {
            const total = addParticipants.length;
            $('#rev-part-count').text(total);

            // Correct singular / plural labels
            const countBadgeText = total === 1 ? '1 Participant Record' : `${total} Participant Records`;
            $('#rev-part-count-badge').html(`<span id="rev-part-count">${total}</span> ${total === 1 ? 'Participant Record' : 'Participant Records'}`);

            const footerSummaryText = total === 1 ? '1 participant record ready to save' : `${total} participant records ready to save`;
            $('#rev-footer-summary').text(footerSummaryText);

            if (total === 0) {
                $('#rev-empty-state').show();
                $('#rev-table-container').hide();
                $('#offline-review-tbody').empty();
                return;
            }

            $('#rev-empty-state').hide();
            $('#rev-table-container').show();

            // Extract training-level fields once (repeated across all participant rows)
            const courseName = $('#add-course-name').val().trim() || '-';
            const subCourseName = $('#add-sub-course-name').val().trim() || '-';
            const type = $('#add-type').val() || '-';
            const category = $('#add-category').val().trim() || '-';
            const level = $('#add-level').val() || '-';
            const batch = $('#add-batch').val().trim() || '-';
            const mainClass = $('#add-main-classification').val() || '-';
            const subClass = $('#add-sub-classification').val().trim() || '-';
            const isKpi = $('input[name="counted_corporate_kpi"]:checked').val() === '1';
            const subKpi = $('#add-sub-corporate-kpi').val().trim() || '-';
            const delivery = $('#add-delivery-method').val() || '-';
            const subDelivery = $('#add-sub-delivery-method').val().trim() || '-';
            const provider = $('#add-provider').val() || '-';
            const subProvider = $('#add-sub-provider').val() || '-';
            const duration = $('#add-duration').val().trim() || '-';
            const defaultHours = parseFloat($('#add-learning-hours').val()) || 0;
            const startDate = $('#add-start-date').val() || '-';
            const finishDate = $('#add-finish-date').val() || (startDate !== '-' ? startDate : '-');
            const month = $('#add-month').val() || deriveMonthString($('#add-start-date').val()) || '-';
            const providerName = $('#add-provider-name').val().trim() || '-';
            const venue = $('#add-venue').val().trim() || '-';
            const instructor = $('#add-instructor').val().trim() || '-';

            let rowsHtml = '';
            addParticipants.forEach(function (p, idx) {
                const rowNo = idx + 1;

                // Participant hours (or fallback to training learning hours)
                const pHoursNum = (p.learning_hours !== null && p.learning_hours !== '' && !isNaN(p.learning_hours))
                    ? parseFloat(p.learning_hours)
                    : defaultHours;
                const hoursFormatted = pHoursNum > 0 ? (pHoursNum.toFixed(1) + 'h') : '-';

                // Progress with bar and badge
                const rawProg = parseFloat(p.progress !== undefined ? p.progress : 100);
                const prog = isNaN(rawProg) ? 100 : Math.min(100, Math.max(0, rawProg));
                const progColor = prog >= 100 ? 'bg-success' : (prog > 0 ? 'bg-warning' : 'bg-secondary');
                const progressHtml = `<div class="d-flex align-items-center gap-1.5" style="min-width: 80px;">
                    <div class="progress progress-xs flex-grow-1" style="width: 44px; height: 6px;">
                        <div class="progress-bar ${progColor}" style="width: ${prog}%"></div>
                    </div>
                    <span class="small fw-semibold text-muted">${Math.round(prog)}%</span>
                </div>`;

                // Attendance
                const isPresent = (p.attendance == 1 || p.attendance === true || p.attendance === '1');
                const attendanceHtml = isPresent
                    ? '<span class="badge bg-success-lt text-success px-2 py-0.5">Present</span>'
                    : '<span class="badge bg-danger-lt text-danger px-2 py-0.5">Absent</span>';

                // Certificate
                const certHtml = p.certificate === 'Yes'
                    ? '<span class="badge bg-blue-lt text-primary px-2 py-0.5"><i class="ti ti-file-certificate me-1"></i>Yes</span>'
                    : '<span class="badge bg-light text-muted border px-2 py-0.5">No</span>';

                // Counted KPI
                const kpiHtml = isKpi
                    ? '<span class="badge bg-blue-lt text-primary px-2 py-0.5">Yes</span>'
                    : '<span class="badge bg-light text-muted border px-2 py-0.5">No</span>';

                // Type badge
                const typeHtml = type !== '-'
                    ? `<span class="badge bg-light text-secondary border px-2 py-0.5">${escapeHtml(type)}</span>`
                    : '<span class="text-muted">-</span>';

                // Assessment Score
                const scoreHtml = (p.assessment_score !== null && p.assessment_score !== '' && !isNaN(p.assessment_score))
                    ? `<span class="fw-semibold">${parseFloat(p.assessment_score).toFixed(1)}</span>`
                    : '<span class="text-muted">-</span>';

                // Bond
                const isBonded = (p.bond_status === 'Yes');
                const bondStatusHtml = isBonded
                    ? '<span class="badge bg-warning-lt text-warning fw-bold px-2 py-0.5">Yes</span>'
                    : '<span class="badge bg-light text-muted border px-2 py-0.5">No</span>';
                const bondEndDateStr = (isBonded && p.bond_end_date) ? formatReviewDate(p.bond_end_date) : '-';
                const bondAmountStr = (isBonded && p.bond_amount !== null && p.bond_amount !== '' && !isNaN(p.bond_amount))
                    ? `<span class="font-monospace fw-semibold">Rp ${Number(p.bond_amount).toLocaleString('id-ID')}</span>`
                    : '<span class="text-muted">-</span>';

                // Kirkpatrick Evaluation
                const l1Html = p.l1_program ? escapeHtml(p.l1_program) : '<span class="text-muted">-</span>';
                const l2NumHtml = (p.l2_numerical !== null && p.l2_numerical !== '' && !isNaN(p.l2_numerical))
                    ? `<span class="fw-semibold">${parseFloat(p.l2_numerical).toFixed(1)}</span>`
                    : '<span class="text-muted">-</span>';
                const l2VerbHtml = p.l2_verbal ? escapeHtml(p.l2_verbal) : '<span class="text-muted">-</span>';
                const l3NumHtml = (p.l3_numerical !== null && p.l3_numerical !== '' && !isNaN(p.l3_numerical))
                    ? `<span class="fw-semibold">${parseFloat(p.l3_numerical).toFixed(1)}</span>`
                    : '<span class="text-muted">-</span>';
                const l3VerbHtml = p.l3_verbal ? escapeHtml(p.l3_verbal) : '<span class="text-muted">-</span>';
                const picHtml = p.pic ? escapeHtml(p.pic) : '<span class="text-muted">-</span>';

                rowsHtml += `
                    <tr>
                        <td class="text-center text-muted sticky-col sticky-col-1">${rowNo}</td>
                        <td class="text-center font-monospace sticky-col sticky-col-2">
                            <span class="badge bg-light text-secondary border font-monospace">${escapeHtml(p.employee_id || '-')}</span>
                        </td>
                        <td class="sticky-col sticky-col-3 text-start">
                            <div class="fw-semibold text-dark text-nowrap">${escapeHtml(p.name || '-')}</div>
                            ${p.user_id ? '<span class="badge bg-green-lt extra-small py-0">LMS</span>' : ''}
                        </td>
                        <td class="text-center">${escapeHtml(p.grade || '-')}</td>
                        <td class="text-start">${escapeHtml(p.job_position || '-')}</td>
                        <td class="text-start">${escapeHtml(p.team || '-')}</td>
                        <td class="text-start">${escapeHtml(p.department || '-')}</td>
                        <td class="text-start">${escapeHtml(p.company || '-')}</td>
                        <td class="text-start fw-semibold text-dark">${escapeHtml(courseName)}</td>
                        <td class="text-start">${escapeHtml(subCourseName)}</td>
                        <td class="text-center">${typeHtml}</td>
                        <td class="text-start">${escapeHtml(category)}</td>
                        <td class="text-center">${escapeHtml(level)}</td>
                        <td class="text-center">${escapeHtml(batch)}</td>
                        <td class="text-start">${escapeHtml(mainClass)}</td>
                        <td class="text-start">${escapeHtml(subClass)}</td>
                        <td class="text-center">${kpiHtml}</td>
                        <td class="text-start">${escapeHtml(subKpi)}</td>
                        <td class="text-center">${escapeHtml(delivery)}</td>
                        <td class="text-start">${escapeHtml(subDelivery)}</td>
                        <td class="text-center">${escapeHtml(provider)}</td>
                        <td class="text-start">${escapeHtml(subProvider)}</td>
                        <td class="text-center font-monospace">${escapeHtml(duration)}</td>
                        <td class="text-center">${hoursFormatted !== '-' ? '<span class="fw-semibold">' + hoursFormatted + '</span>' : '<span class="text-muted">-</span>'}</td>
                        <td class="text-center">${progressHtml}</td>
                        <td class="text-center">${formatReviewDate(startDate)}</td>
                        <td class="text-center">${formatReviewDate(finishDate)}</td>
                        <td class="text-center">${escapeHtml(month)}</td>
                        <td class="text-start">${escapeHtml(providerName)}</td>
                        <td class="text-start">${escapeHtml(venue)}</td>
                        <td class="text-start">${escapeHtml(instructor)}</td>
                        <td class="text-center">${scoreHtml}</td>
                        <td class="text-center">${certHtml}</td>
                        <td class="text-center">${attendanceHtml}</td>
                        <td class="text-start">${escapeHtml(p.attendance_reason || '-')}</td>
                        <td class="text-center">${bondStatusHtml}</td>
                        <td class="text-center">${bondEndDateStr}</td>
                        <td class="text-end">${bondAmountStr}</td>
                        <td class="text-center">${l1Html}</td>
                        <td class="text-center">${l2NumHtml}</td>
                        <td class="text-start">${l2VerbHtml}</td>
                        <td class="text-center">${l3NumHtml}</td>
                        <td class="text-start">${l3VerbHtml}</td>
                        <td class="text-start">${picHtml}</td>
                    </tr>
                `;
            });

            $('#offline-review-tbody').html(rowsHtml);
        }

        // Smooth scroll to form section and focus element
        function scrollToSection(sectionId, focusSelector) {
            const target = $(sectionId);
            if (target.length) {
                $('html, body').animate({
                    scrollTop: target.offset().top - 90
                }, 250, function () {
                    if (focusSelector) {
                        $(focusSelector).focus();
                    }
                });
            }
        }

        $('#btn-review-edit-1').on('click', function () { scrollToSection('#section-1-training-info', '#add-course-name'); });
        $('#btn-review-edit-2, #btn-review-empty-edit-part').on('click', function () { scrollToSection('#section-2-participants', '#participant-search-input'); });
        $('#btn-review-edit-3').on('click', function () { scrollToSection('#section-3-results', '#res-progress'); });

        // --- SUBMIT TRAINING FORM ---
        $btnSubmitAdd.on('click', function () {
            // First save currently edited participant
            saveCurrentActiveParticipantForm();

            $addAlert.hide().empty();

            // Client Validation: Course Name
            const courseName = $('#add-course-name').val().trim();
            if (!courseName) {
                $addAlert.text('Course Name is required in Section 1.').show();
                scrollToSection('#section-1-training-info', 1);
                $('#add-course-name').focus();
                return;
            }

            // Client Validation: Dates
            const startDate = $('#add-start-date').val();
            const finishDate = $('#add-finish-date').val();
            if (startDate && finishDate && finishDate < startDate) {
                $addAlert.text(`Finish Date (${finishDate}) cannot be earlier than Start Date (${startDate}).`).show();
                scrollToSection('#section-1-training-info', 1);
                return;
            }

            // Client Validation: Participants
            if (addParticipants.length === 0) {
                $addAlert.text('At least one participant is required in Section 2.').show();
                scrollToSection('#section-2-participants', 2);
                return;
            }

            // Prepare Payload
            const payload = {
                course_name: courseName,
                sub_course_name: $('#add-sub-course-name').val().trim(),
                type: $('#add-type').val(),
                category: $('#add-category').val().trim(),
                level: $('#add-level').val(),
                batch: $('#add-batch').val().trim(),
                main_classification: $('#add-main-classification').val(),
                sub_classification: $('#add-sub-classification').val().trim(),
                counted_corporate_kpi: $('input[name="counted_corporate_kpi"]:checked').val(),
                sub_corporate_kpi: $('#add-sub-corporate-kpi').val().trim(),
                corporate_kpi_year: getCurrentKpiYear(),
                delivery_method: $('#add-delivery-method').val(),
                sub_delivery_method: $('#add-sub-delivery-method').val().trim(),
                provider: $('#add-provider').val(),
                sub_provider: $('#add-sub-provider').val(),
                provider_name: $('#add-provider-name').val().trim(),
                duration: $('#add-duration').val().trim(),
                learning_hours: $('#add-learning-hours').val(),
                start_date: startDate || null,
                finish_date: finishDate || null,
                venue: $('#add-venue').val().trim(),
                instructor: $('#add-instructor').val().trim(),
                participants: addParticipants,
                _token: $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}'
            };

            // Loading state
            $btnSubmitAdd.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Saving...');

            $.ajax({
                url: "{{ route('manage.record.offline.store') }}",
                type: 'POST',
                data: JSON.stringify(payload),
                contentType: 'application/json',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                success: function (res) {
                    $btnSubmitAdd.prop('disabled', false).html('<i class="ti ti-check fs-2"></i><span>Save Training</span>');

                    if (res && res.success) {
                        // Redirect back to Non KP OWL landing with success message
                        sessionStorage.setItem('offline_flash_success', res.message || 'Non KP OWL training has been created successfully.');
                        window.location.href = "{{ route('manage.record.offline') }}?saved=1";
                    } else {
                        $addAlert.text(res.message || 'Failed to save training.').show();
                    }
                },
                error: function (xhr) {
                    $btnSubmitAdd.prop('disabled', false).html('<i class="ti ti-check fs-2"></i><span>Save Training</span>');

                    let errMsg = 'Failed to save training.';
                    if (xhr.responseJSON) {
                        if (xhr.responseJSON.errors) {
                            const errs = Object.values(xhr.responseJSON.errors).flat();
                            errMsg = errs.join(' ');
                        } else if (xhr.responseJSON.message) {
                            errMsg = xhr.responseJSON.message;
                        }
                    }
                    $addAlert.text(errMsg).show();
                    $('html, body').animate({ scrollTop: $addAlert.offset().top - 100 }, 200);
                }
            });
        });

        // --- MASTER OPTION [+] MODAL LOGIC ---
        $(document).on('click', '.btn-add-option-modal', function (e) {
            e.preventDefault();
            const type = $(this).data('type');
            const title = $(this).data('title') || type;
            const targetSelect = $(this).data('target');
            const targetDatalist = $(this).data('datalist');

            $('#modal-option-type').val(type);
            $('#modal-option-target-select').val(targetSelect || '');
            $('#modal-option-target-datalist').val(targetDatalist || '');
            $('#modal-option-value').val('');
            $('#modal-option-alert').hide().empty();
            $('#modal-option-info').hide().empty();
            $('#modal-add-option-title').text(`Add New ${title}`);
            $('#modal-option-value-label').text(`${title} Value`);

            if (type === 'sub_corporate_kpi') {
                const kpiYear = getCurrentKpiYear();
                $('#modal-option-year').val(kpiYear);
                $('#modal-option-year-display').val(kpiYear);
                $('#modal-option-year-container').show();
            } else {
                $('#modal-option-year').val('');
                $('#modal-option-year-container').hide();
            }

            const modalEl = document.getElementById('modal-add-master-option');
            if (modalEl) {
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
                setTimeout(function () {
                    $('#modal-option-value').focus();
                }, 350);
            }
        });

        function submitMasterOption() {
            const type = $('#modal-option-type').val();
            const value = $('#modal-option-value').val().trim();
            const year = $('#modal-option-year').val();
            const targetSelect = $('#modal-option-target-select').val();
            const targetDatalist = $('#modal-option-target-datalist').val();

            $('#modal-option-alert').hide().empty();
            $('#modal-option-info').hide().empty();

            if (!value) {
                $('#modal-option-alert').text('Option value is required.').show();
                $('#modal-option-value').focus();
                return;
            }

            $('#btn-save-master-option').prop('disabled', true);
            $('#spinner-save-option').show();
            $('#icon-save-option').hide();

            $.ajax({
                url: "{{ route('manage.record.offline.options.store') }}",
                type: 'POST',
                data: JSON.stringify({
                    option_type: type,
                    value: value,
                    year: year ? parseInt(year, 10) : null
                }),
                contentType: 'application/json',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                success: function (res) {
                    $('#btn-save-master-option').prop('disabled', false);
                    $('#spinner-save-option').hide();
                    $('#icon-save-option').show();

                    const optVal = (res.data && res.data.value) ? res.data.value : value;

                    // If target is a <select>
                    if (targetSelect && $(targetSelect).is('select')) {
                        const $sel = $(targetSelect);
                        let optExists = false;
                        $sel.find('option').each(function () {
                            if ($(this).val().toLowerCase() === optVal.toLowerCase()) {
                                optExists = true;
                                $(this).val(optVal).text(optVal);
                            }
                        });
                        if (!optExists) {
                            $sel.append(new Option(optVal, optVal, true, true));
                        } else {
                            $sel.val(optVal);
                        }
                        $sel.trigger('change');
                    } else if (targetSelect && $(targetSelect).is('input')) {
                        // If target is an <input>
                        const $inp = $(targetSelect);
                        $inp.val(optVal).trigger('input').trigger('change');
                    }

                    // If target has an associated <datalist>
                    if (targetDatalist && $(targetDatalist).length) {
                        const $dl = $(targetDatalist);
                        let existsInDl = false;
                        $dl.find('option').each(function () {
                            if ($(this).val().toLowerCase() === optVal.toLowerCase()) {
                                existsInDl = true;
                            }
                        });
                        if (!existsInDl) {
                            $dl.append(`<option value="${escapeHtml(optVal)}"></option>`);
                        }
                    }

                    // Hide modal
                    const modalEl = document.getElementById('modal-add-master-option');
                    if (modalEl) {
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) {
                            modal.hide();
                        }
                    }

                    updateSection4Review();
                },
                error: function (xhr) {
                    $('#btn-save-master-option').prop('disabled', false);
                    $('#spinner-save-option').hide();
                    $('#icon-save-option').show();

                    let errMsg = 'Failed to create option.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errMsg = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        const errs = Object.values(xhr.responseJSON.errors).flat();
                        errMsg = errs.join(' ');
                    }
                    $('#modal-option-alert').text(errMsg).show();
                }
            });
        }

        $('#btn-save-master-option').on('click', submitMasterOption);
        $('#modal-option-value').on('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                submitMasterOption();
            }
        });
    });
</script>
