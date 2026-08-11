<script>
    $(function () {
        const $filterForm = $('#contentFilterForm');
        const defaultYear = String($filterForm.data('default-year'));

        $('#contentFilterMonth, #contentFilterYear').on('change', function () {
            if ($('#contentFilterMonth').val() && !$('#contentFilterYear').val()) {
                $('#contentFilterYear').val(defaultYear);
            }

            $filterForm.trigger('submit');
        });

        $('.content-auto-submit').on('change', function () {
            $filterForm.trigger('submit');
        });
    });
</script>
