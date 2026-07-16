<script>
    function initLearningAssessment() {
        var tableId = '#assessmentTable';
        var $table = $(tableId);

        if (!$table.length || $.fn.DataTable.isDataTable(tableId)) {
            return;
        }

        var assessmentTable = $table.DataTable({
                ordering: true,
                order: [],
                pageLength: 10,
                autoWidth: false,
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
            var query = params.toString();
            history.pushState({}, '', window.location.pathname + (query ? '?' + query : ''));
            _activeTab = null;
            loadLearningTab('assessment');
        }

        function collectAssessmentFilterState() {
            var state = {};
            $('#assessmentFilterBar [data-assessment-filter]').each(function () {
                state[String($(this).data('assessment-filter'))] = String($(this).attr('data-selected-value') || 'all');
            });
            state.batch = String($('input[name="assessmentModalBatch"]:checked').val() || 'all');
            state.grade = String($('input[name="assessmentModalGrade"]:checked').val() || 'all');
            state.score = String($('input[name="assessmentModalScore"]:checked').val() || 'all');
            state.result = String($('input[name="assessmentModalResult"]:checked').val() || 'all');
            state.date = String($('input[name="assessmentModalDate"]:checked').val() || 'all');
            state.availability = String($('input[name="assessmentModalAvailability"]:checked').val() || 'all');
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
                if (state[key] !== 'all') {
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
                var value = String($('input[name="' + name + '"]:checked').val() || 'all');
                return count + (value !== 'all' ? 1 : 0);
            }, 0);

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
            updateAdvancedFilterBadge();
        }

        $('#assessmentModalReset').off('.learningAssessment').on('click.learningAssessment', function () {
            resetAdvancedFilters();
        });

        $('#assessmentMoreFiltersModal input[type="radio"]')
            .off('.learningAssessment')
            .on('change.learningAssessment', updateAdvancedFilterBadge);

        $('#assessmentResetFilter').off('.learningAssessment').on('click.learningAssessment', function () {
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
            var headings = [];
            $table.find('thead th').slice(0, 7).each(function () {
                headings.push($.trim($(this).find('.record-sort-label').text() || $(this).text()));
            });

            var rows = assessmentTable.rows({ search: 'applied', order: 'applied' }).nodes().toArray().map(function (row) {
                return $(row).find('td').slice(0, 7).map(function () {
                    return $.trim($(this).text().replace(/\s+/g, ' '));
                }).get();
            });

            function excelCell(value) {
                return String(value == null ? '' : value)
                    .replace(/\t/g, ' ')
                    .replace(/\r?\n/g, ' ');
            }

            var spreadsheetRows = [headings].concat(rows).map(function (row) {
                return row.map(excelCell).join('\t');
            });
            var blob = new Blob(['\ufeff' + spreadsheetRows.join('\r\n')], {
                type: 'application/vnd.ms-excel;charset=utf-8'
            });
            var url = URL.createObjectURL(blob);
            var link = document.createElement('a');
            link.href = url;
            link.download = 'assessment-list-' + new Date().toISOString().slice(0, 10) + '.xls';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        });

        assessmentTable.on('order.dt', updateAssessmentSortIcons);
        updateAssessmentSortIcons();
    }
</script>
