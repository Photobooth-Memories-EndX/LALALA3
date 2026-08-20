<script>
    function initLearningAssessment() {
        var tableId = '#assessmentTable';
        var $table = $(tableId);

        if (!$table.length || $.fn.DataTable.isDataTable(tableId)) {
            return;
        }

        var assessmentFilterParams = new URLSearchParams(window.location.search);

        function filterValue(name) {
            return String(assessmentFilterParams.get('assessment_filter_' + name) || 'all');
        }

        function rowAttribute(row, name) {
            return String(row.getAttribute('data-assessment-' + name) || '');
        }

        function assessmentRowMatches(row) {
            var exactFilters = ['type', 'status', 'year', 'month', 'batch', 'grade', 'availability'];

            for (var index = 0; index < exactFilters.length; index++) {
                var filterName = exactFilters[index];
                var selectedValue = filterValue(filterName);

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
            ? [[2, 'desc']]
            : (initialDateFilter === 'oldest' ? [[2, 'asc']] : []);

        var assessmentTable = $table.DataTable({
                ordering: true,
                order: initialOrder,
                pageLength: 10,
                autoWidth: false,
                search: {
                    search: $.trim($('#assessmentSearch').val())
                },
                dom: 't<"assessment-datatable-footer"ip>',
                language: {
                    emptyTable: 'No assessments available.',
                    zeroRecords: 'No assessments match the selected filters.'
                },
                columnDefs: [
                    {
                        targets: [0, 1, 2, 3, 4, 5],
                        orderable: true,
                        orderSequence: ['asc', 'desc', '']
                    },
                    {
                        targets: 6,
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
            assessmentTable.search($.trim($('#assessmentSearch').val()));

            var dateFilter = filterValue('date');
            if (dateFilter === 'newest') {
                assessmentTable.order([2, 'desc']);
            } else if (dateFilter === 'oldest') {
                assessmentTable.order([2, 'asc']);
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
            Array.from(params.keys()).forEach(function (key) {
                if (key.indexOf('assessment_filter_') === 0 || key === 'assessment_search') {
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
            var search = $.trim($('#assessmentSearch').val());
            reloadAssessment(buildAssessmentParams(state, search));
        }

        $('#assessmentSearch').off('.learningAssessment').on('input.learningAssessment', function () {
            var value = $.trim(this.value);
            clearTimeout(window.learningAssessmentSearchTimer);
            window.learningAssessmentSearchTimer = setTimeout(function () {
                var params = new URLSearchParams(window.location.search);
                params.delete('assessment_search');
                if (value) params.set('assessment_search', value);
                reloadAssessment(params);
            }, 450);
        });

        $('.assessment-quick-item').off('.learningAssessment').on('click.learningAssessment', function (event) {
            event.preventDefault();
            var $item = $(this);
            setDropdownValue($item.closest('[data-assessment-filter]'), String($item.data('value')), $.trim($item.text()));
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

        $('#assessmentModalReset, #assessmentResetFilter').off('.learningAssessment').on('click.learningAssessment', function () {
            clearTimeout(window.learningAssessmentSearchTimer);
            $('#assessmentSearch').val('');
            $('#assessmentFilterBar [data-assessment-filter]').each(function () {
                setDropdownValue($(this), 'all', 'All');
            });
            resetAdvancedFilters();

            var params = new URLSearchParams(window.location.search);
            Array.from(params.keys()).forEach(function (key) {
                if (key.indexOf('assessment_filter_') === 0 || key === 'assessment_search') {
                    params.delete(key);
                }
            });
            reloadAssessment(params);
        });

        $('#assessmentDownloadExcel').off('.learningAssessment').on('click.learningAssessment', function () {
            var query = window.location.search || '';
            window.location.href = @json(route('user.learning.assessment.export')) + query;
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

        assessmentTable.on('preDraw.dt', function () {
            $('.popover.assessment-history-popover').remove();
        });

        assessmentTable.on('draw.dt order.dt', function () {
            updateAssessmentSortIcons();
            initAssessmentScorePopovers();
        });

        updateAssessmentSortIcons();
        initAssessmentScorePopovers();
    }
</script>
