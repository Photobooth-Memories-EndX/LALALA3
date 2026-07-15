<script>
    function initLearningAssessment() {
        var tableId = '#assessmentTable';

        if ($(tableId).length && !$.fn.DataTable.isDataTable(tableId)) {
            var assessmentTable = $(tableId).DataTable({
                ordering: true,
                order: [],
                pageLength: 10,
                autoWidth: false,
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
                var $headers = $(tableId).find('th.assessment-sort-heading');
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

            assessmentTable.on('order.dt', updateAssessmentSortIcons);
            updateAssessmentSortIcons();
        }
    }
</script>
