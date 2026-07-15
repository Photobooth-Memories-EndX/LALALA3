<script>
    function initLearningAssessment() {
        var tableId = '#assessmentTable';

        if ($(tableId).length && !$.fn.DataTable.isDataTable(tableId)) {
            $(tableId).DataTable({
                ordering: false,
                pageLength: 10,
                autoWidth: false
            });
        }
    }
</script>
