@once
<script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>
<script>
    $(function () {
        let searchTimer;
        const table = $('#certificate-settings-table').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            pageLength: 15,
            order: [[0, 'asc']],
            ajax: {
                url: @json(route('certificate.setting.datatable')),
                data: function (data) {
                    data.type = $('#certificate-type').val();
                    data.status = $('#certificate-status').val();
                    data.assessment_required = $('#certificate-assessment-required').val();
                    data.issuer = $('#certificate-issuer').val();
                    data.minimum_progress = $('#certificate-minimum-progress').val();
                    data.minimum_score = $('#certificate-minimum-score').val();
                }
            },
            columns: [
                {data: 'type', name: 'certificate_type'},
                {data: 'name', name: 'name', orderable: false},
                {data: 'assessment', name: 'assessment', orderable: false},
                {data: 'minimum_progress', name: 'minimum_progress'},
                {data: 'minimum_score', name: 'minimum_score'},
                {data: 'issuer', name: 'issuer_name'},
                {data: 'status', name: 'is_active'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });

        $('.certificate-filter').on('change', function () {
            table.ajax.reload();
        });

        $('#certificate-search').on('input', function () {
            clearTimeout(searchTimer);
            const keyword = this.value;
            searchTimer = setTimeout(function () {
                table.search(keyword).draw();
            }, 400);
        });

        $('#certificate-filter-reset').on('click', function () {
            $('.certificate-filter').val('all');
            $('#certificate-search').val('');
            table.search('').ajax.reload();
        });

        $(document).on('click', '.certificate-edit', function () {
            const setting = $(this).data('setting');
            const modal = $('#editCertificateSettingModal');
            const form = modal.find('form');
            const url = form.data('update-url').replace('__ID__', setting.id);

            form.attr('action', url);
            form.find('.certificate-type-select').val(setting.certificate_type);
            form.find('.certificate-item-select')
                .data('selected-content', setting.content_id || '')
                .data('selected-trainingplan', setting.trainingplan_id || '')
                .data('selected-assessment', setting.assessment_id || '');
            form.find('.certificate-assessment-select').data('selected', setting.assessment_id || '');
            form.find('.certificate-assessment-required').prop('checked', setting.is_assessment_required);
            form.find('[name="minimum_progress"]').val(setting.minimum_progress);
            form.find('[name="minimum_score"]').val(setting.minimum_score);
            form.find('[name="issuer_name"]').val(setting.issuer_name);
            form.find('.certificate-default-template').prop('checked', setting.use_default_template);
            form.find('[name="is_active"]').val(setting.is_active ? '1' : '0');
            form.trigger('certificate:reload');
            modal.modal('show');
        });
    });
</script>
@endonce
