<!-- DYNAMIC OPTION MODAL FOR TRAINING SCHEDULE (CLASSIFICATION & MASTER OPTIONS) -->
<div class="modal fade" id="modal-schedule-add-option" tabindex="-1" aria-labelledby="modal-schedule-option-title" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header py-2 px-3 bg-light">
                <h5 class="modal-title fw-bold fs-6 text-dark" id="modal-schedule-option-title">Add Option</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <form id="form-schedule-add-option" onsubmit="return false;">
                    <input type="hidden" id="modal-schedule-option-type" value="">
                    <input type="hidden" id="modal-schedule-option-target" value="">

                    <div id="modal-schedule-option-alert" class="alert alert-danger py-1 px-2 small mb-2" style="display:none;"></div>
                    <div id="modal-schedule-option-info" class="alert alert-info py-1 px-2 small mb-2" style="display:none;"></div>

                    <!-- Contextual Parent (e.g. Main Classification when adding Sub Classification) -->
                    <div id="modal-schedule-option-parent-container" class="mb-2" style="display:none;">
                        <label class="form-label small fw-semibold mb-1 text-muted">Main Classification</label>
                        <input type="text" class="form-control form-control-sm rounded-2 bg-light text-muted" id="modal-schedule-option-parent-display" readonly>
                    </div>

                    <div class="mb-2">
                        <label for="modal-schedule-option-value" class="form-label small fw-semibold mb-1" id="modal-schedule-option-label">Option Name / Value</label>
                        <input type="text" class="form-control form-control-sm rounded-2" id="modal-schedule-option-value" placeholder="Enter name or value..." autocomplete="off" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer py-2 px-3 bg-light">
                <button type="button" class="btn btn-sm btn-link text-secondary text-decoration-none" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" id="btn-save-schedule-option">
                    <span class="spinner-border spinner-border-sm me-1" id="spinner-save-schedule-option" style="display:none;"></span>
                    <i class="ti ti-plus me-1" id="icon-save-schedule-option"></i> Save Option
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('modal-schedule-add-option');
    if (!modalEl) return;

    // Open Modal function
    window.openScheduleAddOptionModal = function (type, title, targetSelect) {
        title = title || type;
        $('#modal-schedule-option-type').val(type);
        $('#modal-schedule-option-target').val(targetSelect || '');
        $('#modal-schedule-option-value').val('');
        $('#modal-schedule-option-alert').hide().empty();
        $('#modal-schedule-option-info').hide().empty();

        $('#modal-schedule-option-title').text(`Add New ${title}`);
        $('#modal-schedule-option-label').text(`${title} Name`);

        // Context parent for Sub Classification
        if (type === 'sub_classification') {
            const currentMain = $('#select-main-classification').val();
            if (currentMain && currentMain !== '__add_new__') {
                $('#modal-schedule-option-parent-display').val(currentMain);
                $('#modal-schedule-option-parent-container').show();
            } else {
                $('#modal-schedule-option-parent-display').val('All / General');
                $('#modal-schedule-option-parent-container').show();
            }
        } else {
            $('#modal-schedule-option-parent-container').hide();
        }

        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
        setTimeout(function () {
            $('#modal-schedule-option-value').focus();
        }, 350);
    };

    // Open Modal from [+] button (backward compatibility)
    $(document).on('click', '.btn-add-option-modal', function (e) {
        e.preventDefault();
        const type = $(this).data('type');
        const title = $(this).data('title') || type;
        const targetSelect = $(this).data('target');
        window.openScheduleAddOptionModal(type, title, targetSelect);
    });

    // Track previous value on focus / mousedown to reset if __add_new__ is selected
    $(document).on('focus mousedown', 'select', function () {
        const val = $(this).val();
        if (val !== '__add_new__') {
            $(this).data('prev-val', val);
        }
    });

    // Detect when '+ Add New...' option is selected inside any dropdown
    $(document).on('change', 'select', function () {
        const selectedVal = $(this).val();
        if (selectedVal === '__add_new__') {
            const $select = $(this);
            const prevVal = $select.data('prev-val') || '';
            const type = $select.data('option-type') || $select.attr('data-type');
            let title = $select.data('option-title') || $select.attr('data-title');
            if (!title) {
                const $label = $select.closest('div').find('label').first();
                title = $label.length ? $label.text().replace('*', '').trim() : 'Option';
            }
            const targetId = '#' + $select.attr('id');

            // Reset select back to its previous selection or placeholder immediately
            $select.val(prevVal);
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.trigger('change.select2');
            }

            if (type && typeof window.openScheduleAddOptionModal === 'function') {
                window.openScheduleAddOptionModal(type, title, targetId);
            }
        } else {
            $(this).data('prev-val', selectedVal);
        }
    });

    // Save Option via AJAX
    function submitScheduleOption() {
        const type = $('#modal-schedule-option-type').val();
        const value = $('#modal-schedule-option-value').val().trim();
        const targetSelect = $('#modal-schedule-option-target').val();
        const currentMain = $('#select-main-classification').val();

        $('#modal-schedule-option-alert').hide().empty();
        $('#modal-schedule-option-info').hide().empty();

        if (!value) {
            $('#modal-schedule-option-alert').text('Option value is required.').show();
            $('#modal-schedule-option-value').focus();
            return;
        }

        $('#btn-save-schedule-option').prop('disabled', true);
        $('#spinner-save-schedule-option').show();
        $('#icon-save-schedule-option').hide();

        $.ajax({
            url: "{{ route('manage.training-schedule.options.store') }}",
            type: 'POST',
            data: JSON.stringify({
                option_type: type,
                value: value,
                main_classification: (type === 'sub_classification') ? currentMain : null
            }),
            contentType: 'application/json',
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            success: function (res) {
                $('#btn-save-schedule-option').prop('disabled', false);
                $('#spinner-save-schedule-option').hide();
                $('#icon-save-schedule-option').show();

                const optVal = (res.data && res.data.value) ? res.data.value : value;

                // Update target dropdown
                if (targetSelect && $(targetSelect).length) {
                    const $sel = $(targetSelect);
                    let optExists = false;
                    $sel.find('option').each(function () {
                        if ($(this).val().toLowerCase() === optVal.toLowerCase()) {
                            optExists = true;
                            $(this).val(optVal).text(optVal);
                        }
                    });

                    if (!optExists) {
                        const $addNewOpt = $sel.find('option[value="__add_new__"]');
                        if ($addNewOpt.length) {
                            $('<option></option>').val(optVal).text(optVal).insertBefore($addNewOpt);
                        } else {
                            $sel.append(new Option(optVal, optVal, true, true));
                        }
                        $sel.val(optVal);
                    } else {
                        $sel.val(optVal);
                    }

                    $sel.data('prev-val', optVal);
                    if ($sel.hasClass('select2-hidden-accessible')) {
                        $sel.trigger('change.select2');
                    }

                    // If it was Sub Classification, also register into JS memory cache and mapping
                    if (type === 'sub_classification') {
                        if (typeof allSubOptions !== 'undefined') {
                            const inAll = allSubOptions.some(item => item.value.toLowerCase() === optVal.toLowerCase());
                            if (!inAll) {
                                allSubOptions.push({ value: optVal, text: optVal });
                            }
                        }
                        if (currentMain && typeof subClassificationMap !== 'undefined') {
                            if (!subClassificationMap[currentMain]) {
                                subClassificationMap[currentMain] = [];
                            }
                            if (!subClassificationMap[currentMain].includes(optVal)) {
                                subClassificationMap[currentMain].push(optVal);
                            }
                        }
                    }

                    $sel.trigger('change');
                }

                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) {
                    modal.hide();
                }
            },
            error: function (xhr) {
                $('#btn-save-schedule-option').prop('disabled', false);
                $('#spinner-save-schedule-option').hide();
                $('#icon-save-schedule-option').show();

                let err = 'An error occurred while saving the option.';
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) {
                        err = xhr.responseJSON.message;
                    } else if (xhr.responseJSON.errors) {
                        const firstKey = Object.keys(xhr.responseJSON.errors)[0];
                        err = xhr.responseJSON.errors[firstKey][0];
                    }
                }
                $('#modal-schedule-option-alert').text(err).show();
            }
        });
    }

    $('#btn-save-schedule-option').on('click', submitScheduleOption);

    $('#modal-schedule-option-value').on('keypress', function (e) {
        if (e.which === 13) {
            e.preventDefault();
            submitScheduleOption();
        }
    });
});
</script>
