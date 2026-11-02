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
            dom: "t<'d-flex align-items-center justify-content-between p-3'ip>",
            pageLength: 15,
            order: [[0, 'asc']],
            language: {
                emptyTable: "No certificate settings found",
                zeroRecords: "No matching certificate settings found",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)"
            },
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
                {data: 'institution', name: 'institution'},
                {data: 'issued_date', name: 'issued_date'},
                {data: 'expired_date', name: 'expired_date'},
                {data: 'assessment', name: 'assessment', orderable: false},
                {data: 'minimum_progress', name: 'minimum_progress'},
                {data: 'minimum_score', name: 'minimum_score'},
                {data: 'issuer', name: 'issuer_name'},
                {data: 'status', name: 'is_active'},
                {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end'}
            ]
        });

        // Entries select
        $('#certificate-entries').on('change', function () {
            const len = parseInt($(this).val()) || 15;
            table.page.len(len).draw();
        });

        // Quick filter dropdowns (type, status)
        $('#certificate-type, #certificate-status').on('change', function () {
            table.ajax.reload();
        });

        // Search box input with debounce
        $('#certificate-search').on('keyup', function (e) {
            if (e.key === 'Enter') {
                clearTimeout(searchTimer);
                table.search(this.value).draw();
            } else {
                clearTimeout(searchTimer);
                const keyword = this.value;
                searchTimer = setTimeout(function () {
                    table.search(keyword).draw();
                }, 400);
            }
        });

        // Search submit button
        $('#btn-certificate-search').on('click', function () {
            table.search($('#certificate-search').val()).draw();
        });

        // Apply More Filters modal
        $('#btn-apply-certificate-filters').on('click', function () {
            table.ajax.reload();
            const modalEl = document.getElementById('moreCertificateFiltersModal');
            if (modalEl) {
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                }
                $(modalEl).modal('hide');
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open').css('overflow', '');
            }
        });

        // Reset More Filters modal inputs
        $('#btn-reset-modal-filters').on('click', function () {
            $('#certificate-issuer').val('all');
            $('#certificate-assessment-required').val('all');
            $('#certificate-minimum-progress').val('all');
            $('#certificate-minimum-score').val('all');
            table.ajax.reload();
        });

        // Global Reset button
        $('#certificate-filter-reset').on('click', function () {
            $('#certificate-entries').val(15);
            $('#certificate-type').val('all');
            $('#certificate-status').val('all');
            $('#certificate-issuer').val('all');
            $('#certificate-assessment-required').val('all');
            $('#certificate-minimum-progress').val('all');
            $('#certificate-minimum-score').val('all');
            $('#certificate-search').val('');
            table.search('').page.len(15).ajax.reload();
        });

        // Reset Add Modal when opened
        $('#addCertificateSettingModal').on('show.bs.modal', function () {
            const form = $(this).find('form');
            if (!form.data('reloaded-from-error')) {
                form.find('.certificate-institution-input').val('PT KRAKATAU POSCO');
                form.find('.certificate-issued-date').val('');
                form.find('.certificate-expired-date').val('');
            }
        });

        // Edit Certificate Modal trigger
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
                .data('selected-offline', setting.offline_training_id || '')
                .data('selected-assessment', setting.assessment_id || '');
            form.find('.certificate-assessment-select').data('selected', setting.assessment_id || '');
            form.find('.certificate-assessment-required').prop('checked', setting.is_assessment_required);
            form.find('[name="minimum_progress"]').val(setting.minimum_progress);
            form.find('[name="minimum_score"]').val(setting.minimum_score);
            form.find('[name="issuer_name"]').val(setting.issuer_name);
            form.find('.certificate-institution-input').val(setting.institution || 'PT KRAKATAU POSCO');
            form.find('.certificate-issued-date').val(setting.issued_date || '');
            form.find('.certificate-expired-date').val(setting.expired_date || '');
            form.find('.certificate-default-template').prop('checked', setting.use_default_template);
            form.find('[name="is_active"]').val(setting.is_active ? '1' : '0');
            form.trigger('certificate:reload');

            // Show/hide current custom template info
            const hasCustomBg = !setting.use_default_template && setting.custom_background;
            form.find('.certificate-current-template-info').toggle(!!hasCustomBg);
            if (hasCustomBg) {
                const previewCustomUrl = @json(route('certificate.setting.preview.custom-template', ['certificateSetting' => '__CERT_ID__'])).replace('__CERT_ID__', setting.id);
                form.find('.certificate-preview-custom-link').attr('href', previewCustomUrl);
            }

            modal.modal('show');
        });
    });
</script>
@endonce
