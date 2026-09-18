<script>
    function initLearningAssessment() {
        var tableId = '#assessmentTable';
        var $table = $(tableId);

        if (!$table.length || $.fn.DataTable.isDataTable(tableId)) {
            return;
        }

        var assessmentFilterParams = new URLSearchParams(window.location.search);

        function resolveAssessmentMonthNumber(val) {
            if (!val || val === 'all') return null;
            var str = String(val).trim().toLowerCase();
            var n = parseInt(str, 10);
            if (!isNaN(n) && n >= 1 && n <= 12) return n;
            var months = {
                january: 1, jan: 1,
                february: 2, feb: 2,
                march: 3, mar: 3,
                april: 4, apr: 4,
                may: 5,
                june: 6, jun: 6,
                july: 7, jul: 7,
                august: 8, aug: 8,
                september: 9, sep: 9, sept: 9,
                october: 10, oct: 10,
                november: 11, nov: 11,
                december: 12, dec: 12
            };
            return months[str] || null;
        }

        function filterValue(name) {
            var val = assessmentFilterParams.get('assessment_filter_' + name);
            if (val === null || val === undefined || val === '') {
                val = assessmentFilterParams.get(name);
            }
            if (val === null || val === undefined || val === '') {
                return 'all';
            }
            var s = String(val).trim();
            if (name === 'status' && s.toLowerCase() === 'pending') {
                return 'not_started';
            }
            if (name === 'month') {
                var m = resolveAssessmentMonthNumber(s);
                return m !== null ? String(m) : s;
            }
            return s;
        }

        function rowAttribute(row, name) {
            return String(row.getAttribute('data-assessment-' + name) || '');
        }

        function assessmentRowMatches(row) {
            var exactFilters = ['type', 'status', 'year', 'month', 'batch', 'grade', 'availability'];

            for (var index = 0; index < exactFilters.length; index++) {
                var filterName = exactFilters[index];
                var selectedValue = filterValue(filterName);

                if (filterName === 'type') {
                    if (selectedValue === 'all' || selectedValue === '') {
                        continue;
                    }
                    var rowType = rowAttribute(row, 'type');
                    if (selectedValue === 'content') {
                        if (rowType !== 'content' && rowType !== 'content_content' && rowType !== 'content_playlist') {
                            return false;
                        }
                        continue;
                    }
                    if (selectedValue === 'playlist' || selectedValue === 'trainingplan') {
                        if (rowType !== 'trainingplan' && rowType !== 'playlist') {
                            return false;
                        }
                        continue;
                    }
                    if (rowType !== selectedValue) {
                        return false;
                    }
                    continue;
                }

                if (selectedValue !== 'all' && rowAttribute(row, filterName) !== selectedValue) {
                    return false;
                }
            }

            var result = filterValue('result');
            if (result !== 'all' && rowAttribute(row, 'status') !== result) {
                return false;
            }

            var scoreFilter = filterValue('score');
            var scoreValue = rowAttribute(row, 'score');
            var numericScore = scoreValue === '' ? null : Number(scoreValue);

            if (scoreFilter === 'none' && numericScore !== null) return false;
            if (scoreFilter === '0_59' && (numericScore === null || numericScore < 0 || numericScore >= 60)) return false;
            if (scoreFilter === '60_79' && (numericScore === null || numericScore < 60 || numericScore >= 80)) return false;
            if (scoreFilter === '80_100' && (numericScore === null || numericScore < 80 || numericScore > 100)) return false;

            var dateFilter = filterValue('date');
            if (['this_month', 'last_month', 'this_year'].indexOf(dateFilter) !== -1) {
                var timestamp = Number(rowAttribute(row, 'date'));
                if (!timestamp) return false;

                var rowDate = new Date(timestamp * 1000);
                var today = new Date();

                if (dateFilter === 'this_month' &&
                    (rowDate.getFullYear() !== today.getFullYear() || rowDate.getMonth() !== today.getMonth())) {
                    return false;
                }

                if (dateFilter === 'last_month') {
                    var lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    if (rowDate.getFullYear() !== lastMonth.getFullYear() || rowDate.getMonth() !== lastMonth.getMonth()) {
                        return false;
                    }
                }

                if (dateFilter === 'this_year' && rowDate.getFullYear() !== today.getFullYear()) {
                    return false;
                }
            }

            return true;
        }

        var dataTableFilters = $.fn.dataTable.ext.search;
        if (window.learningAssessmentDataTableFilter) {
            var existingFilterIndex = dataTableFilters.indexOf(window.learningAssessmentDataTableFilter);
            if (existingFilterIndex !== -1) {
                dataTableFilters.splice(existingFilterIndex, 1);
            }
        }

        window.learningAssessmentDataTableFilter = function (settings, data, dataIndex) {
            if (!settings.nTable || settings.nTable.id !== 'assessmentTable') {
                return true;
            }

            return assessmentRowMatches(settings.aoData[dataIndex].nTr);
        };
        dataTableFilters.push(window.learningAssessmentDataTableFilter);

        var initialDateFilter = filterValue('date');
        var initialOrder = initialDateFilter === 'newest'
            ? [[4, 'desc']]
            : (initialDateFilter === 'oldest' ? [[4, 'asc']] : []);

        var assessmentTable = $table.DataTable({
                ordering: true,
                order: initialOrder,
                pageLength: 10,
                autoWidth: false,
                search: {
                    search: $.trim($('#assessment-search-input, #assessmentSearch').val())
                },
                dom: 't<"assessment-datatable-footer"ip>',
                language: {
                    emptyTable: 'No assessments available.',
                    zeroRecords: 'No assessments match the selected filters.'
                },
                columnDefs: [
                    {
                        targets: [0, 1, 2, 3, 4, 5, 6],
                        orderable: true,
                        orderSequence: ['asc', 'desc', '']
                    },
                    {
                        targets: 7,
                        orderable: false
                    }
                ]
        });

        function updateAssessmentSortIcons() {
            var $headers = $table.find('th.assessment-sort-heading');
            var order = assessmentTable.order();

            $headers
                .removeClass('is-sorted')
                .find('.record-sort-icons span')
                .removeClass('active');

            if (!order.length || (order[0][1] !== 'asc' && order[0][1] !== 'desc')) {
                return;
            }

            var columnIndex = order[0][0];
            var direction = order[0][1];
            var $activeHeader = $headers.filter('[data-assessment-column="' + columnIndex + '"]');

            $activeHeader
                .addClass('is-sorted')
                .find(direction === 'asc' ? '.record-sort-asc' : '.record-sort-desc')
                .addClass('active');
        }

        function setDropdownValue($dropdown, value, label) {
            $dropdown.attr('data-selected-value', value);
            $dropdown.find('.assessment-quick-item').removeClass('active')
                .filter('[data-value="' + value + '"]').addClass('active');
            $dropdown.find('.dropdown-toggle')
                .toggleClass('active', value !== 'all')
                .find('.fw-semibold').text(label);
        }

        function reloadAssessment(params) {
            params.delete('assessment_page');
            params.set('learning_tab', 'assessment');
            var query = params.toString();
            $('#assessmentMoreFiltersModal').modal('hide');
            history.pushState({}, '', window.location.pathname + (query ? '?' + query : ''));

            if (typeof loadLearningTab === 'function') {
                _activeTab = null;
                loadLearningTab('assessment');
                return;
            }

            assessmentFilterParams = new URLSearchParams(window.location.search);
            assessmentTable.search($.trim($('#assessment-search-input, #assessmentSearch').val()));

            var dateFilter = filterValue('date');
            if (dateFilter === 'newest') {
                assessmentTable.order([4, 'desc']);
            } else if (dateFilter === 'oldest') {
                assessmentTable.order([4, 'asc']);
            }

            assessmentTable.page('first').draw(false);
        }

        function getModalFilterValue(name) {
            var $select = $('#assessmentMoreFiltersModal select[name="' + name + '"]');
            if ($select.length && $select.val()) {
                return String($select.val());
            }
            var $radio = $('#assessmentMoreFiltersModal input[name="' + name + '"]:checked');
            if ($radio.length && $radio.val()) {
                return String($radio.val());
            }
            return 'all';
        }

        function collectAssessmentFilterState() {
            var state = {};
            $('#assessmentFilterBar [data-assessment-filter]').each(function () {
                state[String($(this).data('assessment-filter'))] = String($(this).attr('data-selected-value') || 'all');
            });
            state.batch = getModalFilterValue('assessmentModalBatch');
            state.grade = getModalFilterValue('assessmentModalGrade');
            state.score = getModalFilterValue('assessmentModalScore');
            state.result = getModalFilterValue('assessmentModalResult');
            state.date = getModalFilterValue('assessmentModalDate');
            state.availability = getModalFilterValue('assessmentModalAvailability');
            state.from_date = $.trim($('#assessmentFilterFromDate').val() || '');
            state.to_date = $.trim($('#assessmentFilterToDate').val() || '');
            return state;
        }

        function buildAssessmentParams(state, search) {
            var params = new URLSearchParams(window.location.search);
            var genericKeys = ['assessment_search', 'type', 'status', 'year', 'month', 'batch', 'grade', 'score', 'result', 'date', 'availability', 'search', 'q'];
            Array.from(params.keys()).forEach(function (key) {
                if (key.indexOf('assessment_filter_') === 0 || genericKeys.indexOf(key) !== -1) {
                    params.delete(key);
                }
            });

            Object.keys(state).forEach(function (key) {
                if (state[key] !== 'all' && state[key] !== '') {
                    params.set('assessment_filter_' + key, state[key]);
                }
            });

            if (search) params.set('assessment_search', search);
            return params;
        }

        function updateAdvancedFilterBadge() {
            var advancedNames = [
                'assessmentModalBatch',
                'assessmentModalGrade',
                'assessmentModalScore',
                'assessmentModalResult',
                'assessmentModalDate',
                'assessmentModalAvailability'
            ];
            var activeCount = advancedNames.reduce(function (count, name) {
                var value = getModalFilterValue(name);
                return count + (value !== 'all' ? 1 : 0);
            }, 0);

            if ($('#assessmentFilterFromDate').val()) activeCount++;
            if ($('#assessmentFilterToDate').val()) activeCount++;

            $('#assessmentMoreFiltersCount')
                .toggleClass('d-none', activeCount === 0)
                .text(activeCount || '');
        }

        function applyAssessmentFilters() {
            clearTimeout(window.learningAssessmentSearchTimer);
            var state = collectAssessmentFilterState();
            var search = $.trim($('#assessment-search-input, #assessmentSearch').val());
            reloadAssessment(buildAssessmentParams(state, search));
        }

        function escapeAssessmentHtml(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // ── Live Autocomplete Search for Assessment Filter ───────────────────────
        function initAssessmentAutocomplete() {
            var searchInput = document.getElementById('assessment-search-input') || document.getElementById('assessmentSearch');
            var searchResults = document.getElementById('assessment-search-results');
            var debounceTimer = null;

            if (!searchInput || !searchResults) return;

            var listEl = searchResults.querySelector('.autocomplete-list-group') || searchResults;
            var footerEl = searchResults.querySelector('.search-footer');

            $(searchInput).off('.learningAssessmentSearch').on('input.learningAssessmentSearch', function () {
                var value = $.trim(this.value);
                clearTimeout(debounceTimer);

                if (value.length < 1) {
                    searchResults.style.display = 'none';
                    if (listEl) listEl.innerHTML = '';
                    if (footerEl) footerEl.style.display = 'none';
                    clearTimeout(window.learningAssessmentSearchTimer);
                    window.learningAssessmentSearchTimer = setTimeout(function () {
                        applyAssessmentFilters();
                    }, 400);
                    return;
                }

                debounceTimer = setTimeout(function () {
                    fetch('/user/assessment/search-autocomplete?q=' + encodeURIComponent(value), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(function (response) {
                        if (!response.ok) throw new Error('HTTP ' + response.status);
                        return response.json();
                    })
                    .then(function (data) {
                        var html = '';
                        if (data && data.length > 0) {
                            data.forEach(function (item) {
                                var badgeClass = item.type === 'playlist' || item.type === 'trainingplan'
                                    ? 'bg-teal-lt text-teal'
                                    : (item.type === 'global' ? 'bg-indigo-lt text-indigo' : 'bg-primary-lt text-primary');
                                var typeLabel = item.type_label || (item.type ? escapeAssessmentHtml(item.type.toUpperCase()) : 'CONTENT');

                                html += '<a href="#" class="dropdown-item py-2 px-3 text-wrap border-bottom-subtle assessment-search-item" data-title="' + escapeAssessmentHtml(item.title) + '">' +
                                    '<div class="d-flex align-items-center justify-content-between mb-1">' +
                                        '<span class="text-dark small fw-semibold text-truncate me-2">' + escapeAssessmentHtml(item.title) + '</span>' +
                                        '<span class="badge ' + badgeClass + ' small flex-shrink-0">' + escapeAssessmentHtml(typeLabel) + '</span>' +
                                    '</div>' +
                                    (item.subtitle ? '<div class="text-muted text-truncate" style="font-size: 0.75rem;">' + escapeAssessmentHtml(item.subtitle) + '</div>' : '') +
                                '</a>';
                            });
                        }
                        if (listEl) listEl.innerHTML = html;

                        var keyword = $.trim(searchInput.value);
                        if (footerEl) {
                            if (data && data.length > 0) {
                                footerEl.innerHTML = `
                                    <a href="/my-learning?learning_tab=assessment&q=${encodeURIComponent(keyword)}" class="dropdown-item fw-semibold text-primary py-2 d-flex align-items-center justify-content-center gap-2">
                                        <i class="ti ti-search"></i>
                                        <span>See all results for "${keyword}"</span>
                                    </a>
                                `;
                                footerEl.style.display = 'block';
                            } else {
                                footerEl.innerHTML = '<div class="dropdown-item text-muted disabled py-2">No items found</div>';
                                footerEl.style.display = 'block';
                            }
                        }
                        searchResults.style.display = 'block';
                    })
                    .catch(function () {
                        searchResults.style.display = 'none';
                    });
                }, 250);
            });

            $(searchInput).off('focus.learningAssessmentSearch').on('focus.learningAssessmentSearch', function () {
                if ($.trim(this.value).length >= 1 && ((listEl && listEl.children.length > 0) || (footerEl && footerEl.style.display !== 'none'))) {
                    searchResults.style.display = 'block';
                }
            });

            $(searchInput).off('keydown.learningAssessmentSearch').on('keydown.learningAssessmentSearch', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchResults.style.display = 'none';
                    applyAssessmentFilters();
                }
            });
        }

        initAssessmentAutocomplete();

        $(document).off('click.assessmentSearchItem').on('click.assessmentSearchItem', '.assessment-search-item', function (event) {
            event.preventDefault();
            var title = String($(this).data('title') || '');
            $('#assessment-search-input, #assessmentSearch').val(title);
            var searchResults = document.getElementById('assessment-search-results');
            if (searchResults) searchResults.style.display = 'none';
            applyAssessmentFilters();
        });

        document.addEventListener('click', function (e) {
            var searchInput = document.getElementById('assessment-search-input') || document.getElementById('assessmentSearch');
            var searchResults = document.getElementById('assessment-search-results');
            if (searchInput && searchResults && !searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });

        $('.assessment-quick-item').off('.learningAssessment').on('click.learningAssessment', function (event) {
            event.preventDefault();
            var $item = $(this);
            setDropdownValue($item.closest('[data-assessment-filter]'), String($item.data('value')), $.trim($item.text()));
            applyAssessmentFilters();
        });

        $('#assessmentApplyFilter, .assessment-apply-filter').off('.learningAssessment').on('click.learningAssessment', applyAssessmentFilters);

        function resetAdvancedFilters() {
            $('#assessmentMoreFiltersModal input[type="radio"][value="all"]').prop('checked', true);
            $('#assessmentMoreFiltersModal select').val('all');
            $('#assessmentFilterFromDate, #assessmentFilterToDate').val('');
            updateAdvancedFilterBadge();
        }

        $('#assessmentMoreFiltersModal input, #assessmentMoreFiltersModal select')
            .off('.learningAssessment')
            .on('change.learningAssessment input.learningAssessment', updateAdvancedFilterBadge);

        $('#btn-reset-assessment-modal, #assessmentModalReset, .assessment-modal-reset, #assessmentResetFilter').off('.learningAssessment').on('click.learningAssessment', function () {
            clearTimeout(window.learningAssessmentSearchTimer);
            $('#assessment-search-input, #assessmentSearch').val('');
            var searchResults = document.getElementById('assessment-search-results');
            if (searchResults) {
                searchResults.style.display = 'none';
                searchResults.innerHTML = '';
            }
            $('#assessmentFilterBar [data-assessment-filter]').each(function () {
                setDropdownValue($(this), 'all', 'All');
            });
            resetAdvancedFilters();

            var params = new URLSearchParams(window.location.search);
            var genericKeys = ['assessment_search', 'type', 'status', 'year', 'month', 'batch', 'grade', 'score', 'result', 'date', 'availability', 'search', 'q'];
            Array.from(params.keys()).forEach(function (key) {
                if (key.indexOf('assessment_filter_') === 0 || genericKeys.indexOf(key) !== -1) {
                    params.delete(key);
                }
            });
            reloadAssessment(params);
        });

        $('#assessmentDownloadExcel').off('.learningAssessment').on('click.learningAssessment', function () {
            var state = collectAssessmentFilterState();
            var search = $.trim($('#assessment-search-input, #assessmentSearch').val());
            var params = buildAssessmentParams(state, search);
            params.delete('learning_tab');
            params.delete('assessment_page');
            var query = params.toString();
            window.location.href = @json(route('user.learning.assessment.export')) + (query ? '?' + query : '');
        });

        function initAssessmentScorePopovers() {
            $table.find('.assessment-score-trigger').each(function () {
                var $trigger = $(this);
                if ($trigger.data('bs.popover') || $trigger.data('popover')) {
                    return;
                }

                var $template = $trigger.find('.assessment-score-popover-template');
                if (!$template.length) {
                    return;
                }

                var htmlContent = $.trim($template.html());
                if (!htmlContent) {
                    return;
                }

                $trigger.popover({
                    trigger: 'hover',
                    placement: 'top',
                    html: true,
                    sanitize: false,
                    content: htmlContent,
                    template: '<div class="popover assessment-history-popover" role="tooltip"><div class="popover-arrow"></div><div class="popover-body"></div></div>',
                    container: 'body',
                    boundary: 'viewport'
                });
            });
        }

        function initAssessmentNamePopovers() {
            $('[data-assessment-id].assessment-name, [data-assessment-id].assessment-title-link, tr[data-assessment-id] .assessment-title').each(function () {
                var $el = $(this);
                if ($el.data('bs.popover') || $el.data('popover')) {
                    return;
                }

                var assessmentId = $el.data('assessment-id') || $el.closest('[data-assessment-id]').data('assessment-id');
                var $row = $('tr[data-assessment-id="' + assessmentId + '"]');
                if (!$row.length) {
                    $row = $el.closest('tr, .assessment-row');
                }

                var title = $el.data('assessment-title') || $row.attr('data-assessment-title') || $.trim($el.text()) || '-';
                var batchRaw = $el.data('assessment-batch') || $row.attr('data-assessment-batch');
                var batchDisplay = '-';
                if (batchRaw !== undefined && batchRaw !== null && batchRaw !== '' && batchRaw !== '-') {
                    var batchStr = String(batchRaw).trim();
                    batchDisplay = batchStr.toLowerCase().indexOf('batch') !== -1 ? batchStr : 'Batch ' + batchStr;
                }

                var relatedTo = $el.data('assessment-related-to') || $row.attr('data-assessment-related-to') || '';
                var relatedSection = '';
                if (relatedTo && String(relatedTo).trim() !== '' && String(relatedTo).trim() !== '—' && String(relatedTo).trim() !== '-') {
                    relatedSection = '<div class="assessment-info-popover-related">' +
                        '<div class="assessment-info-popover-related-label">Related to</div>' +
                        '<div class="assessment-info-popover-related-value">' + $('<div>').text(relatedTo).html() + '</div>' +
                        '</div>';
                }

                var popoverHtml = '<div class="assessment-info-popover-card">' +
                    '<div class="assessment-info-popover-header">Assessment Info</div>' +
                    '<div class="assessment-info-popover-title">' + $('<div>').text(title).html() + '</div>' +
                    '<div class="assessment-info-popover-meta">' +
                    '<i class="ti ti-layers-intersect"></i> ' + $('<div>').text(batchDisplay).html() +
                    '</div>' +
                    relatedSection +
                    '</div>';

                if (typeof $.fn.popover === 'function') {
                    $el.popover({
                        trigger: 'hover focus',
                        placement: 'top',
                        html: true,
                        sanitize: false,
                        content: popoverHtml,
                        template: '<div class="popover assessment-info-popover" role="tooltip"><div class="popover-arrow"></div><div class="popover-body"></div></div>',
                        container: 'body',
                        boundary: 'viewport'
                    });
                }
            });
        }

        assessmentTable.on('preDraw.dt', function () {
            $('.popover.assessment-history-popover').remove();
            $('.popover.assessment-info-popover').remove();
        });

        assessmentTable.on('draw.dt order.dt', function () {
            updateAssessmentSortIcons();
            initAssessmentScorePopovers();
            initAssessmentNamePopovers();
        });

        updateAssessmentSortIcons();
        initAssessmentScorePopovers();
        initAssessmentNamePopovers();
        initAssessmentActionModals();
    }

    function openAssessmentModalDirect(modalId) {
        var modalEl = document.getElementById(modalId);
        if (!modalEl) return;
        if (typeof $ !== 'undefined' && $.fn && typeof $.fn.modal === 'function') {
            $(modalEl).modal('show');
        } else if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
            var instance = window.bootstrap.Modal.getInstance ? window.bootstrap.Modal.getInstance(modalEl) : null;
            if (!instance) instance = new window.bootstrap.Modal(modalEl);
            instance.show();
        } else {
            modalEl.style.display = 'block';
            modalEl.classList.add('show');
            document.body.classList.add('modal-open');
        }
    }

    function formatAssessmentModalDate(dateStr) {
        if (!dateStr || dateStr === '-' || dateStr === 'null' || dateStr === 'undefined') {
            return '-';
        }
        var str = String(dateStr).trim();
        if (/^[0-9]{1,2}\s+[A-Za-z]{3}\s+[0-9]{4},\s+[0-9]{2}:[0-9]{2}$/.test(str)) {
            return str;
        }
        try {
            var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            var slashParts = str.match(/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})\s+(\d{1,2}):(\d{2})/);
            if (slashParts) {
                var day = parseInt(slashParts[1], 10);
                var monthIdx = parseInt(slashParts[2], 10) - 1;
                var year = parseInt(slashParts[3], 10);
                if (year < 100) year += 2000;
                var hours = slashParts[4].padStart(2, '0');
                var mins = slashParts[5].padStart(2, '0');
                var monthName = months[monthIdx] || '';
                return (day < 10 ? '0' + day : day) + ' ' + monthName + ' ' + year + ', ' + hours + ':' + mins;
            }
            var parsed = new Date(str);
            if (!isNaN(parsed.getTime())) {
                var d = parsed.getDate();
                var m = months[parsed.getMonth()];
                var y = parsed.getFullYear();
                var h = String(parsed.getHours()).padStart(2, '0');
                var min = String(parsed.getMinutes()).padStart(2, '0');
                return (d < 10 ? '0' + d : d) + ' ' + m + ' ' + y + ', ' + h + ':' + min;
            }
        } catch (e) {}
        return str;
    }

    function initAssessmentActionModals() {
        // Handle Title Link Click -> trigger row's action button if available
        $(document)
            .off('click.learningAssessmentTitleTrigger', '.assessment-title-link')
            .on('click.learningAssessmentTitleTrigger', '.assessment-title-link', function (e) {
                e.preventDefault();
                var $link = $(this);
                var $row = $link.closest('tr, .assessment-row');
                var $actionBtn = $row.find('.btn-start-test, .btn-remedial, .btn-continue-test, .btn-assessment-start, .btn-assessment-remedial, .btn-assessment-continue').first();
                if ($actionBtn.length && !$actionBtn.prop('disabled') && !$actionBtn.hasClass('disabled') && $actionBtn.attr('aria-disabled') !== 'true') {
                    $actionBtn.trigger('click');
                    return;
                }
            });

        // Unified click handler for Start, Remedial, and Continue buttons (All Roles, including Super Admin)
        $(document)
            .off('click.learningAssessmentConfirmation', '.btn-start-test, .btn-remedial, .btn-continue-test, .btn-assessment-start, .btn-assessment-remedial, .btn-assessment-continue, [data-assessment-trigger="true"]')
            .on('click.learningAssessmentConfirmation', '.btn-start-test, .btn-remedial, .btn-continue-test, .btn-assessment-start, .btn-assessment-remedial, .btn-assessment-continue, [data-assessment-trigger="true"]', function (e) {
                var $btn = $(this);

                if ($btn.prop('disabled') || $btn.hasClass('disabled') || $btn.attr('aria-disabled') === 'true') {
                    e.preventDefault();
                    return false;
                }

                e.preventDefault();

                var assessmentId = $btn.data('assessment-id') || $btn.attr('data-assessment-id');
                if (!assessmentId) return;

                var targetUrl = $btn.attr('href') || $btn.data('action-url') || $btn.data('target-url');
                if (!targetUrl || targetUrl === '#' || targetUrl === 'javascript:void(0);') return;

                var $row = $('tr[data-assessment-id="' + assessmentId + '"], .assessment-row[data-assessment-id="' + assessmentId + '"]');
                if (!$row.length) {
                    $row = $btn.closest('tr, .assessment-row');
                }

                var title = $btn.data('assessment-title') 
                    || $row.attr('data-assessment-title') 
                    || $row.data('assessment-title')
                    || $.trim($row.find('.assessment-title, .assessment-name').first().text()) 
                    || '-';

                var batchRaw = $btn.data('assessment-batch') 
                    || $row.attr('data-assessment-batch') 
                    || $row.data('assessment-batch');

                var batchDisplay = '-';
                if (batchRaw !== undefined && batchRaw !== null && batchRaw !== '' && batchRaw !== '-') {
                    var batchStr = String(batchRaw).trim();
                    batchDisplay = batchStr.toLowerCase().indexOf('batch') !== -1 ? batchStr : 'Batch ' + batchStr;
                }

                var rawType = $btn.data('assessment-type') || $row.attr('data-assessment-type') || 'content';
                var rawTypeLabel = $btn.data('assessment-type-label') || $row.attr('data-assessment-type-label');
                var isGlobal = String(rawType).toLowerCase() === 'global';
                var isPlaylist = String(rawType).toLowerCase() === 'playlist' || String(rawType).toLowerCase() === 'trainingplan';

                var headerTypeLabel = isGlobal ? 'ASSESSMENT GLOBAL' : (isPlaylist ? 'ASSESSMENT PLAYLIST' : 'ASSESSMENT CONTENT');

                var fromDate = $btn.data('assessment-from-date') || $row.attr('data-assessment-from-date') || '-';
                var toDate = $btn.data('assessment-to-date') || $row.attr('data-assessment-to-date') || '-';
                var dateTimeDisplay = '-';
                if (fromDate !== '-' && toDate !== '-') {
                    dateTimeDisplay = '<div>' + formatAssessmentModalDate(fromDate) + '</div><div>' + formatAssessmentModalDate(toDate) + '</div>';
                } else if (fromDate !== '-') {
                    dateTimeDisplay = '<div>' + formatAssessmentModalDate(fromDate) + '</div>';
                } else if (toDate !== '-') {
                    dateTimeDisplay = '<div>' + formatAssessmentModalDate(toDate) + '</div>';
                }

                var passingGrade = $btn.data('assessment-passing-grade') || $row.attr('data-assessment-passing-grade') || $row.attr('data-assessment-grade') || '-';
                var duration = $btn.data('assessment-duration') || $row.attr('data-assessment-duration') || '60';

                var userAttempts = $btn.data('assessment-user-attempts');
                if (userAttempts === undefined || userAttempts === null || userAttempts === '') {
                    userAttempts = $row.attr('data-assessment-user-attempts') || 0;
                }
                var maxAttempts = $btn.data('assessment-max-attempts');
                if (maxAttempts === undefined || maxAttempts === null || maxAttempts === '') {
                    maxAttempts = $row.attr('data-assessment-max-attempts') || 1;
                }
                var attemptDisplay = (userAttempts !== undefined && userAttempts !== null && userAttempts !== '')
                    ? (userAttempts + ' / ' + maxAttempts)
                    : '-';

                var answeredCount = $btn.data('assessment-answered-count') || $row.attr('data-assessment-answered-count') || 0;
                var totalCount = $btn.data('assessment-total-count') || $row.attr('data-assessment-total-count') || 0;

                var isExpired = $btn.data('assessment-is-expired') === true || String($btn.attr('data-assessment-is-expired')) === 'true' || $row.attr('data-assessment-availability') === 'expired';
                var isInactive = $btn.data('assessment-is-inactive') === true || String($btn.attr('data-assessment-is-inactive')) === 'true';
                var isBeforeStart = $btn.data('assessment-is-before-start') === true || String($btn.attr('data-assessment-is-before-start')) === 'true';

                if (!isExpired && toDate && toDate !== '-') {
                    var parsedToDate = new Date(toDate);
                    if (!isNaN(parsedToDate.getTime()) && new Date().getTime() > parsedToDate.getTime()) {
                        isExpired = true;
                    }
                }

                var actionType = $btn.data('assessment-action-type');
                if (!actionType) {
                    if ($btn.hasClass('btn-continue-test') || $btn.hasClass('btn-assessment-continue')) {
                        actionType = 'continue';
                    } else if ($btn.hasClass('btn-remedial') || $btn.hasClass('btn-assessment-remedial')) {
                        actionType = 'remedial';
                    } else {
                        actionType = 'start';
                    }
                }

                // 1. CONTINUE MODAL
                if (actionType === 'continue') {
                    var $continueModal = $('#continueAssessmentModal');
                    if ($continueModal.length) {
                        $continueModal.find('#continueModalTypeHeader').text(headerTypeLabel);
                        $continueModal.find('#continueModalName').text(title);
                        $continueModal.find('#continueModalDateTime').html(dateTimeDisplay);
                        $continueModal.find('#continueModalPassingGrade').text(passingGrade);
                        $continueModal.find('#continueModalDuration').text(duration + ' Minutes');
                        
                        if (isGlobal) {
                            $continueModal.find('#continueModalBatchContainer').show();
                            $continueModal.find('#continueModalBatch').text(batchDisplay !== '-' ? batchDisplay : 'Batch 1');
                            $continueModal.find('#continueModalAttemptContainer').hide();
                            $continueModal.find('#continueModalProgress').text(answeredCount + ' / ' + totalCount + ' answered');
                            $continueModal.find('#continueModalNoticeTitle').text('NOTICE');
                            $continueModal.find('#continueNoticeGlobal').show();
                            $continueModal.find('#continueNoticeContent').hide();
                        } else {
                            $continueModal.find('#continueModalBatchContainer').hide();
                            $continueModal.find('#continueModalAttemptContainer').show();
                            $continueModal.find('#continueModalAttempt').text(attemptDisplay);
                            $continueModal.find('#continueModalProgress').text(answeredCount + ' / ' + totalCount + ' answered');
                            $continueModal.find('#continueModalNoticeTitle').text('REQUIREMENTS');
                            $continueModal.find('#continueNoticeGlobal').hide();
                            $continueModal.find('#continueNoticeContent').show();
                        }

                        var $continueBtn = $continueModal.find('#btnConfirmContinueAssessment');
                        if (isExpired || isInactive || isBeforeStart) {
                            $continueBtn.addClass('disabled')
                                .prop('disabled', true)
                                .attr('aria-disabled', 'true')
                                .attr('tabindex', '-1')
                                .attr('style', 'background-color: #6c757d !important; border-color: #6c757d !important; color: #ffffff !important; cursor: not-allowed !important; pointer-events: none;');
                        } else {
                            $continueBtn.removeClass('disabled')
                                .prop('disabled', false)
                                .attr('aria-disabled', 'false')
                                .removeAttr('tabindex')
                                .attr('style', 'background-color: #0c5b9e !important; border-color: #0c5b9e !important; color: #ffffff !important; cursor: pointer; pointer-events: auto;');
                        }

                        $continueModal.find('#btnConfirmContinueAssessment').attr('href', targetUrl);
                        $continueModal.data('target-url', targetUrl);

                        openAssessmentModalDirect('continueAssessmentModal');
                        return;
                    }
                }

                // 2. START / REMEDIAL MODAL
                var $confirmModal = $('#assessmentConfirmationModal');
                if ($confirmModal.length) {
                    $confirmModal.find('#assessmentModalTypeHeader').text(headerTypeLabel);
                    $confirmModal.find('#assessmentModalName').text(title);
                    $confirmModal.find('#assessmentModalDateTime').html(dateTimeDisplay);
                    $confirmModal.find('#assessmentModalPassingGrade').text(passingGrade);
                    $confirmModal.find('#assessmentModalDuration').text(duration + ' Minutes');

                    if (isGlobal) {
                        $confirmModal.find('#assessmentModalBatchContainer').show();
                        $confirmModal.find('#assessmentModalBatch').text(batchDisplay !== '-' ? batchDisplay : 'Batch 1');
                        $confirmModal.find('#assessmentModalAttemptContainer').hide();
                    } else {
                        $confirmModal.find('#assessmentModalBatchContainer').hide();
                        $confirmModal.find('#assessmentModalAttemptContainer').show();
                        $confirmModal.find('#assessmentModalAttempt').text(attemptDisplay);
                    }

                    if (actionType === 'remedial') {
                        $confirmModal.find('#assessmentModalHeaderIconBox')
                            .attr('style', 'width: 40px; height: 40px; background-color: rgba(245, 158, 11, 0.12); color: #f59e0b;');
                        $confirmModal.find('#assessmentModalHeaderIconInner').attr('class', 'ti ti-refresh fs-2');
                        $confirmModal.find('#assessmentConfirmationModalTitle').text('REMEDIAL').attr('style', 'color: #f59e0b !important;');
                        $confirmModal.find('#assessmentModalSubtitle').text('Confirmation');
                        $confirmModal.find('#assessmentModalNoticeHeader').text('REQUIREMENTS');
                        $confirmModal.find('#assessmentNoticeStart').hide();
                        $confirmModal.find('#assessmentNoticeGlobalStart').hide();
                        $confirmModal.find('#assessmentNoticeRemedial').show();

                        $confirmModal.find('#btnConfirmAssessmentAction')
                            .attr('style', 'background-color: #f59e0b !important; border-color: #f59e0b !important; color: #ffffff !important;')
                            .html('<i class="ti ti-refresh fs-6"></i> <span>Start Remedial</span>');
                    } else {
                        // Start Modal
                        $confirmModal.find('#assessmentModalHeaderIconBox')
                            .attr('style', 'width: 40px; height: 40px; background-color: rgba(40, 190, 88, 0.12); color: #28be58;');
                        $confirmModal.find('#assessmentModalHeaderIconInner').attr('class', 'ti ti-player-play fs-2');
                        $confirmModal.find('#assessmentConfirmationModalTitle').text('Start Assessment').attr('style', 'color: #28be58 !important;');
                        $confirmModal.find('#assessmentModalSubtitle').text('Confirmation');

                        if (isGlobal) {
                            $confirmModal.find('#assessmentModalNoticeHeader').text('NOTICE');
                            $confirmModal.find('#assessmentNoticeGlobalStart').show();
                            $confirmModal.find('#assessmentNoticeStart').hide();
                            $confirmModal.find('#assessmentNoticeRemedial').hide();
                        } else {
                            $confirmModal.find('#assessmentModalNoticeHeader').text('REQUIREMENTS');
                            $confirmModal.find('#assessmentNoticeStart').show();
                            $confirmModal.find('#assessmentNoticeGlobalStart').hide();
                            $confirmModal.find('#assessmentNoticeRemedial').hide();
                        }

                        $confirmModal.find('#btnConfirmAssessmentAction')
                            .attr('style', 'background-color: #28be58 !important; border-color: #28be58 !important; color: #ffffff !important;')
                            .html('<i class="ti ti-player-play fs-6"></i> <span>Start Test</span>');
                    }

                    var $actionBtn = $confirmModal.find('#btnConfirmAssessmentAction');
                    if (isExpired || isInactive || isBeforeStart) {
                        $actionBtn.addClass('disabled')
                            .prop('disabled', true)
                            .attr('aria-disabled', 'true')
                            .attr('tabindex', '-1')
                            .attr('style', 'background-color: #6c757d !important; border-color: #6c757d !important; color: #ffffff !important; cursor: not-allowed !important; pointer-events: none;');
                    } else {
                        $actionBtn.removeClass('disabled')
                            .prop('disabled', false)
                            .attr('aria-disabled', 'false')
                            .removeAttr('tabindex');
                    }

                    $confirmModal.data('target-url', targetUrl);
                    $confirmModal.data('assessment-id', assessmentId);

                    openAssessmentModalDirect('assessmentConfirmationModal');
                }
            });

        $(document)
            .off('click.assessmentConfirmSubmit', '#btnConfirmAssessmentAction')
            .on('click.assessmentConfirmSubmit', '#btnConfirmAssessmentAction', function (e) {
                var $btn = $(this);
                if ($btn.prop('disabled') || $btn.hasClass('disabled') || $btn.attr('aria-disabled') === 'true') {
                    e.preventDefault();
                    return false;
                }
                e.preventDefault();
                var $modal = $('#assessmentConfirmationModal');
                var targetUrl = $modal.data('target-url');
                if (targetUrl && targetUrl !== '#' && targetUrl !== 'javascript:void(0);') {
                    window.location.href = targetUrl;
                }
            });

        $(document)
            .off('click.continueConfirmSubmit', '#btnConfirmContinueAssessment')
            .on('click.continueConfirmSubmit', '#btnConfirmContinueAssessment', function (e) {
                var $btn = $(this);
                if ($btn.prop('disabled') || $btn.hasClass('disabled') || $btn.attr('aria-disabled') === 'true') {
                    e.preventDefault();
                    return false;
                }
                var $modal = $('#continueAssessmentModal');
                var targetUrl = $modal.data('target-url');
                if (targetUrl && targetUrl !== '#' && targetUrl !== 'javascript:void(0);') {
                    $(this).attr('href', targetUrl);
                }
            });

        $(document)
            .off('click.assessmentModalDismiss', '#assessmentConfirmationModal [data-dismiss="modal"], #assessmentConfirmationModal [data-bs-dismiss="modal"], #continueAssessmentModal [data-dismiss="modal"], #continueAssessmentModal [data-bs-dismiss="modal"]')
            .on('click.assessmentModalDismiss', '#assessmentConfirmationModal [data-dismiss="modal"], #assessmentConfirmationModal [data-bs-dismiss="modal"], #continueAssessmentModal [data-dismiss="modal"], #continueAssessmentModal [data-bs-dismiss="modal"]', function () {
                var $modal = $(this).closest('.modal');
                if (typeof $ !== 'undefined' && $.fn && typeof $.fn.modal === 'function') {
                    $modal.modal('hide');
                } else if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                    var instance = window.bootstrap.Modal.getInstance ? window.bootstrap.Modal.getInstance($modal[0]) : null;
                    if (instance) instance.hide();
                }
                $modal.removeClass('show').hide();
                document.body.classList.remove('modal-open');
                $('.modal-backdrop').remove();
            });
    }

    $(document).ready(function () {
        initAssessmentActionModals();
    });
</script>
