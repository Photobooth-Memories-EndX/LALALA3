<script>
    $(document).ready(function () {

        function toggleAssessmentInfo(form) {
            let checkbox = form.find('.certificate-assessment-required');
            let alertBox = form.find('.certificate-info-alert');
            let scoreInput = form.find('.certificate-minimum-score');

            if (checkbox.is(':checked')) {
                alertBox.show();
                scoreInput.attr('required', true);
            } else {
                alertBox.hide();
                scoreInput.removeAttr('required');
            }
        }

        function toggleTemplateFields(form) {
            form.find('.certificate-custom-template-fields')
                .toggle(!form.find('.certificate-default-template').is(':checked'));
        }

        function getSelectedItemId(form, type) {
            let itemSelect = form.find('.certificate-item-select');

            if (type === 'content') {
                return itemSelect.data('selected-content');
            }

            if (type === 'playlist') {
                return itemSelect.data('selected-trainingplan');
            }

            if (type === 'global') {
                return itemSelect.data('selected-assessment');
            }

            return '';
        }

        function loadItems(form, callback = null) {
            let type = form.find('.certificate-type-select').val();
            let itemSelect = form.find('.certificate-item-select');
            let label = form.find('.certificate-item-label');

            let selectedItemId = getSelectedItemId(form, type);

            if (type === 'content') {
                label.text('Nama Content');
            } else if (type === 'playlist') {
                label.text('Nama Playlist');
            } else {
                label.text('Nama Global Assessment');
            }

            itemSelect.html('<option value="">Loading...</option>');

            $.ajax({
                url: "{{ route('certificate.setting.ajax.items') }}",
                type: "GET",
                data: {
                    type: type
                },
                success: function (items) {
                    itemSelect.empty();
                    itemSelect.append('<option value="">Pilih data</option>');

                    $.each(items, function (index, item) {
                        let selected = String(item.id) === String(selectedItemId) ? 'selected' : '';

                        itemSelect.append(
                            '<option value="' + item.id + '" ' + selected + '>' +
                                item.title +
                            '</option>'
                        );
                    });

                    if (callback) {
                        callback();
                    }
                },
                error: function () {
                    itemSelect.html('<option value="">Gagal memuat data</option>');
                }
            });
        }

        function loadAssessments(form) {
            let type = form.find('.certificate-type-select').val();
            let itemId = form.find('.certificate-item-select').val();
            let assessmentSelect = form.find('.certificate-assessment-select');
            let selectedAssessmentId = assessmentSelect.data('selected');

            assessmentSelect.html('<option value="">Loading...</option>');

            $.ajax({
                url: "{{ route('certificate.setting.ajax.assessments') }}",
                type: "GET",
                data: {
                    type: type,
                    item_id: itemId
                },
                success: function (items) {
                    assessmentSelect.empty();
                    assessmentSelect.append('<option value="">Assessment Optional</option>');

                    $.each(items, function (index, item) {
                        let selected = String(item.id) === String(selectedAssessmentId) ? 'selected' : '';

                        assessmentSelect.append(
                            '<option value="' + item.id + '" ' + selected + '>' +
                                item.title +
                            '</option>'
                        );
                    });
                },
                error: function () {
                    assessmentSelect.html('<option value="">Gagal memuat assessment</option>');
                }
            });
        }

        $('.modal form').each(function () {
            let form = $(this);

            toggleAssessmentInfo(form);
            toggleTemplateFields(form);

            loadItems(form, function () {
                loadAssessments(form);
            });
        });

        $(document).on('certificate:reload', '.modal form', function () {
            let form = $(this);
            toggleAssessmentInfo(form);
            toggleTemplateFields(form);
            loadItems(form, function () {
                loadAssessments(form);
            });
        });

        $(document).on('change', '.certificate-type-select', function () {
            let form = $(this).closest('form');

            form.find('.certificate-item-select')
                .data('selected-content', '')
                .data('selected-trainingplan', '')
                .data('selected-assessment', '');

            form.find('.certificate-assessment-select')
                .data('selected', '');

            loadItems(form, function () {
                loadAssessments(form);
            });
        });

        $(document).on('change', '.certificate-item-select', function () {
            let form = $(this).closest('form');

            form.find('.certificate-assessment-select')
                .data('selected', '');

            loadAssessments(form);
        });

        $(document).on('change', '.certificate-assessment-required', function () {
            let form = $(this).closest('form');

            toggleAssessmentInfo(form);
        });

        $(document).on('change', '.certificate-default-template', function () {
            toggleTemplateFields($(this).closest('form'));
        });

    });
</script>
