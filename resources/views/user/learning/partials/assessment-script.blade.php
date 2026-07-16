<script>
    function initLearningAssessment() {
        var tableId = '#assessmentTable';
        var $table = $(tableId);

        if (!$table.length || $.fn.DataTable.isDataTable(tableId)) {
            return;
        }

        if (!window._assessmentDataTableFilterRegistered) {
            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                if (!settings.nTable || settings.nTable.id !== 'assessmentTable') {
                    return true;
                }

                var $row = $(settings.aoData[dataIndex].nTr);
                var state = window._assessmentFilterState || {};

                if (!$row.length) {
                    return true;
                }

                var type = String(state.type || 'all');
                var status = String(state.status || 'all');
                var year = String(state.year || 'all');
                var month = String(state.month || 'all');
                var batch = String(state.batch || 'all');
                var grade = String(state.grade || 'all');
                var scoreFilter = String(state.score || 'all');
                var result = String(state.result || 'all');
                var availability = String(state.availability || 'all');
                var dateFilter = String(state.date || 'all');
                var rowStatus = String($row.attr('data-assessment-status') || 'not_started');
                var rawScore = String($row.attr('data-assessment-score') || '');
                var score = rawScore === '' ? null : parseFloat(rawScore);

                if (type !== 'all' && String($row.attr('data-assessment-type')) !== type) return false;
                if (year !== 'all' && String($row.attr('data-assessment-year')) !== year) return false;
                if (month !== 'all' && String($row.attr('data-assessment-month')) !== month) return false;
                if (status !== 'all' && rowStatus !== status) return false;
                if (batch !== 'all' && String($row.attr('data-assessment-batch')) !== batch) return false;
                if (grade !== 'all' && String($row.attr('data-assessment-grade')) !== grade) return false;
                if (result !== 'all' && rowStatus !== result) return false;
                if (availability !== 'all' && String($row.attr('data-assessment-availability')) !== availability) return false;

                if (scoreFilter === 'none' && score !== null) return false;
                if (scoreFilter === '0_59' && !(score !== null && score >= 0 && score < 60)) return false;
                if (scoreFilter === '60_79' && !(score !== null && score >= 60 && score < 80)) return false;
                if (scoreFilter === '80_100' && !(score !== null && score >= 80 && score <= 100)) return false;

                if (dateFilter === 'this_month' || dateFilter === 'last_month' || dateFilter === 'this_year') {
                    var timestamp = parseInt($row.attr('data-assessment-date'), 10);
                    if (!timestamp) return false;

                    var rowDate = new Date(timestamp * 1000);
                    var now = new Date();
                    if (dateFilter === 'this_month' && (rowDate.getFullYear() !== now.getFullYear() || rowDate.getMonth() !== now.getMonth())) return false;
                    if (dateFilter === 'this_year' && rowDate.getFullYear() !== now.getFullYear()) return false;
                    if (dateFilter === 'last_month') {
                        var lastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                        if (rowDate.getFullYear() !== lastMonth.getFullYear() || rowDate.getMonth() !== lastMonth.getMonth()) return false;
                    }
                }

                return true;
            });
            window._assessmentDataTableFilterRegistered = true;
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

        function applyQuickFilters() {
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
            window._assessmentFilterState = state;

            var advancedCount = ['batch', 'grade', 'score', 'result', 'date', 'availability'].filter(function (key) {
                return state[key] !== 'all';
            }).length;
            $('#assessmentMoreFiltersCount').text(advancedCount).toggleClass('d-none', advancedCount === 0);

            if (state.date === 'newest' || state.date === 'oldest') {
                assessmentTable.order([2, state.date === 'newest' ? 'desc' : 'asc']);
            }
            assessmentTable.page('first').draw();
            $('#assessmentMoreFiltersModal').modal('hide');
        }

        var searchTimer = null;
        $('#assessmentSearch').off('.learningAssessment').on('input.learningAssessment', function () {
            var value = this.value;
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                assessmentTable.search(value).page('first').draw();
            }, 300);
        });

        $('.assessment-quick-item').off('.learningAssessment').on('click.learningAssessment', function (event) {
            event.preventDefault();
            var $item = $(this);
            setDropdownValue($item.closest('[data-assessment-filter]'), String($item.data('value')), $.trim($item.text()));
        });

        $('#assessmentApplyFilter, .assessment-apply-filter').off('.learningAssessment').on('click.learningAssessment', applyQuickFilters);

        function resetAdvancedFilters() {
            $('input[name="assessmentModalBatch"][value="all"], input[name="assessmentModalGrade"][value="all"], input[name="assessmentModalScore"][value="all"], input[name="assessmentModalResult"][value="all"], input[name="assessmentModalAvailability"][value="all"]').prop('checked', true);
            $('input[name="assessmentModalDate"]').prop('checked', false);
        }

        $('#assessmentModalReset').off('.learningAssessment').on('click.learningAssessment', function () {
            resetAdvancedFilters();
        });

        $('#assessmentResetFilter').off('.learningAssessment').on('click.learningAssessment', function () {
            $('#assessmentSearch').val('');
            assessmentTable.search('');
            $('#assessmentFilterBar [data-assessment-filter]').each(function () {
                setDropdownValue($(this), 'all', 'All');
            });
            resetAdvancedFilters();
            window._assessmentFilterState = {};
            $('#assessmentMoreFiltersCount').text('').addClass('d-none');
            assessmentTable.order([]).page('first').draw();
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

        $('#assessmentFilterBar [data-assessment-filter]').attr({
            'data-selected-value': 'all'
        });
        window._assessmentFilterState = {};

        assessmentTable.on('order.dt', updateAssessmentSortIcons);
        updateAssessmentSortIcons();
    }
</script>
