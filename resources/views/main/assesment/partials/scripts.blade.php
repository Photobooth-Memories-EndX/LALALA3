<script src="{{ asset('js/bootstrap-toggle.min.js') }}"></script>

<script>
    $(document).ready(function () {

        $('[data-toggle="tooltip"]').tooltip();

        $(document).on('click', '.btn-copy-assessment', function () {
            let button = $(this);
            let text = button.data('copy-text');

            copyAssessmentLink(text, button);
        });

        $(document).on('change', '.toggle-class', function () {
            let checkbox = $(this);
            let status = checkbox.prop('checked') ? 'OK' : 'NOT';
            let id = checkbox.data('id');

            $.ajax({
                type: "POST",
                dataType: "json",
                url: "{{ route('assesment.changestatus') }}",
                data: {
                    "_token": "{{ csrf_token() }}",
                    "status": status,
                    "id": id
                },
                success: function () {
                    showToast('Status assessment berhasil diperbarui.', 'success');
                },
                error: function () {
                    if (typeof checkbox.bootstrapToggle === 'function') {
                        checkbox.bootstrapToggle(status === 'OK' ? 'off' : 'on', true);
                    } else {
                        checkbox.prop('checked', status !== 'OK');
                    }
                    showToast('Gagal memperbarui status assessment.', 'danger');
                }
            });
        });

        $('#assessment-search').on('keyup', function () {
            if ($.fn.DataTable && $.fn.DataTable.isDataTable('#MyTable')) {
                $('#MyTable').DataTable().search(this.value).draw();
            }
        });

        function updateAdvancedFilterBadge() {
            let questionUsed = $('#assessmentFilterQuestionUsed').val();
            let questionBank = $('#assessmentFilterQuestionBank').val();
            let periodFrom = $('#assessmentFilterPeriodFrom').val();
            let periodTo = $('#assessmentFilterPeriodTo').val();
            let duration = $('#assessmentFilterDuration').val();
            let sort = $('#assessmentFilterSort').val();

            let count = 0;
            if (questionUsed && questionUsed !== '') count++;
            if (questionBank && questionBank !== '') count++;
            if (periodFrom) count++;
            if (periodTo) count++;
            if (duration && duration !== '' && duration !== 'all') count++;
            if (sort && !['default', 'newest', ''].includes(sort)) count++;

            let $badge = $('#assessmentMoreFiltersCount');
            if (count > 0) {
                $badge.text(count).removeClass('d-none');
            } else {
                $badge.text('').addClass('d-none');
            }
        }

        $('#assessmentFilterQuestionUsed, #assessmentFilterQuestionBank, #assessmentFilterPeriodFrom, #assessmentFilterPeriodTo, #assessmentFilterDuration, #assessmentFilterSort').on('change input', function () {
            updateAdvancedFilterBadge();
        });

        $('#assessmentResetMoreFilters').on('click', function (e) {
            e.preventDefault();
            $('#assessmentFilterQuestionUsed').val('');
            $('#assessmentFilterQuestionBank').val('');
            $('#assessmentFilterPeriodFrom').val('');
            $('#assessmentFilterPeriodTo').val('');
            $('#assessmentFilterDuration').val('');
            $('#assessmentFilterSort').val('newest');
            updateAdvancedFilterBadge();
            $('#assessmentFilterForm').submit();
        });

        $('#assessmentQuickYear, #assessmentQuickMonth, #assessmentQuickType, #assessmentQuickStatus').on('change', function () {
            $('#assessmentFilterForm').submit();
        });

        $('#assessmentSearchInput').on('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                $('#assessmentFilterForm').submit();
            }
        });

        function positionAssessmentDropdown($dropdown) {
            let $menu = $dropdown.find('.dropdown-menu');
            let $button = $dropdown.find('.owl-dropdown-btn, [data-toggle="dropdown"], [data-bs-toggle="dropdown"]');

            if (!$button.length || !$menu.length) return;

            $menu.css({
                'position': 'fixed',
                'top': '0',
                'left': '0',
                'right': 'auto',
                'bottom': 'auto',
                'margin': '0',
                'z-index': '1065',
                'visibility': 'hidden',
                'display': 'block'
            });

            let btnRect = $button[0].getBoundingClientRect();
            let menuWidth = $menu.outerWidth();
            let menuHeight = $menu.outerHeight();

            let windowWidth = $(window).width();
            let windowHeight = $(window).height();

            let leftPos = btnRect.right - menuWidth;

            if (leftPos < 10) {
                leftPos = Math.max(10, btnRect.left);
            }
            if (leftPos + menuWidth > windowWidth - 10) {
                leftPos = windowWidth - menuWidth - 10;
            }

            let topPos = btnRect.bottom + 4;

            if (topPos + menuHeight > windowHeight - 10) {
                if (btnRect.top - menuHeight - 4 > 10) {
                    topPos = btnRect.top - menuHeight - 4;
                } else {
                    topPos = Math.max(10, windowHeight - menuHeight - 10);
                }
            }

            $menu.css({
                'top': topPos + 'px',
                'left': leftPos + 'px',
                'visibility': 'visible'
            });
        }

        function resetAssessmentDropdown($dropdown) {
            let $menu = $dropdown.find('.dropdown-menu');
            $menu.css({
                'position': '',
                'top': '',
                'left': '',
                'right': '',
                'bottom': '',
                'margin': '',
                'z-index': '',
                'visibility': '',
                'display': ''
            });
        }

        $(document).on('show.bs.dropdown shown.bs.dropdown', '.table-responsive .dropdown', function () {
            positionAssessmentDropdown($(this));
        });

        $(document).on('hidden.bs.dropdown', '.table-responsive .dropdown', function () {
            resetAssessmentDropdown($(this));
        });

        $(window).add('.table-responsive').on('scroll resize', function () {
            $('.table-responsive .dropdown.show, .table-responsive .dropdown .show').each(function () {
                let $dropdown = $(this).hasClass('dropdown') ? $(this) : $(this).closest('.dropdown');
                let $btn = $dropdown.find('[data-toggle="dropdown"], [data-bs-toggle="dropdown"]');
                if ($btn.length && typeof $btn.dropdown === 'function') {
                    $btn.dropdown('hide');
                } else {
                    $dropdown.removeClass('show');
                    $dropdown.find('.dropdown-menu').removeClass('show');
                    resetAssessmentDropdown($dropdown);
                }
            });
        });

        // Setup duplicate assessment modal data
        $(document).on('click', '.btn-duplicate-assessment', function () {
            let url = $(this).data('url');
            let title = $(this).data('title');

            $('#formDuplicateAssessment').attr('action', url);
            $('#duplicateAssessmentTitle').text(title || '-');

            let $submitBtn = $('#btnSubmitDuplicate');
            let $cancelBtn = $('#btnCancelDuplicate');

            $submitBtn.data('submitting', false).prop('disabled', false).removeClass('disabled');
            $cancelBtn.prop('disabled', false).removeClass('disabled');
            $submitBtn.find('.btn-text').text('Duplicate Assessment');
            $submitBtn.find('i').attr('class', 'fas fa-copy mr-1 me-1');
        });

        $('#modalDuplicateAssessment').on('show.bs.modal', function (event) {
            let button = $(event.relatedTarget);
            let type = '';
            let contentFor = 'content';
            let trainingplanId = '';

            if (button && button.length && button.data('url')) {
                let url = button.data('url');
                let title = button.data('title');
                type = button.data('type') || '';
                contentFor = button.data('content-for') || 'content';
                trainingplanId = button.data('trainingplan-id') || '';

                $('#formDuplicateAssessment').attr('action', url);
                $('#duplicateAssessmentTitle').text(title || '-');
            }

            // Only show context selection for type === 'content'
            if (type === 'content') {
                $('#duplicateContextSection').removeClass('d-none');
                $('#duplicate_target_content').prop('disabled', false);
                $('#duplicate_target_playlist').prop('disabled', false);

                // If source assessment was already for playlist, pre-select playlist target
                if (contentFor === 'playlist' && trainingplanId) {
                    $('#duplicate_target_playlist').prop('checked', true);
                    $('#duplicatePlaylistSelectWrapper').removeClass('d-none');
                    $('#duplicate_trainingplan_id').val(trainingplanId);
                } else {
                    $('#duplicate_target_content').prop('checked', true);
                    $('#duplicatePlaylistSelectWrapper').addClass('d-none');
                    $('#duplicate_trainingplan_id').val('');
                }
            } else {
                $('#duplicateContextSection').addClass('d-none');
                $('#duplicatePlaylistSelectWrapper').addClass('d-none');
                $('#duplicate_trainingplan_id').val('');
                // Disable context inputs so they are not submitted for global/trainingplan
                $('#duplicate_target_content').prop('disabled', true);
                $('#duplicate_target_playlist').prop('disabled', true);
            }

            $('#duplicate_trainingplan_id').removeClass('is-invalid');

            let $submitBtn = $('#btnSubmitDuplicate');
            let $cancelBtn = $('#btnCancelDuplicate');
            $submitBtn.data('submitting', false).prop('disabled', false).removeClass('disabled');
            $cancelBtn.prop('disabled', false).removeClass('disabled');
            $submitBtn.find('.btn-text').text('Duplicate Assessment');
            $submitBtn.find('i').attr('class', 'fas fa-copy mr-1 me-1');
        });

        // Toggle playlist select on target context radio change
        $(document).on('change', 'input[name="target_context"]', function () {
            let val = $(this).val();
            if (val === 'playlist') {
                $('#duplicatePlaylistSelectWrapper').removeClass('d-none');
            } else {
                $('#duplicatePlaylistSelectWrapper').addClass('d-none');
                $('#duplicate_trainingplan_id').val('').removeClass('is-invalid'); // clear stale value
            }
        });

        $('#duplicate_trainingplan_id').on('change', function () {
            if ($(this).val()) {
                $(this).removeClass('is-invalid');
            }
        });

        // Prevent double submission & show loading indicator with client-side validation
        $('#formDuplicateAssessment').on('submit', function (e) {
            let $submitBtn = $('#btnSubmitDuplicate');
            let $cancelBtn = $('#btnCancelDuplicate');

            // Validate playlist selection if playlist context is chosen
            if ($('#duplicateContextSection').is(':visible')) {
                let targetContext = $('input[name="target_context"]:checked').val();
                if (targetContext === 'playlist') {
                    let plVal = $('#duplicate_trainingplan_id').val();
                    if (!plVal) {
                        e.preventDefault();
                        $('#duplicate_trainingplan_id').addClass('is-invalid').focus();
                        return false;
                    }
                }
            }

            if ($submitBtn.data('submitting') === true) {
                e.preventDefault();
                return false;
            }

            $submitBtn.data('submitting', true);
            $submitBtn.find('i').attr('class', 'fas fa-spinner fa-spin mr-1 me-1');
            $submitBtn.find('.btn-text').text('Duplicating...');
            $cancelBtn.prop('disabled', true).addClass('disabled');

            setTimeout(function () {
                $submitBtn.prop('disabled', true).addClass('disabled');
            }, 10);

            return true;
        });

        $('#modalDuplicateAssessment').on('hidden.bs.modal', function () {
            $('#duplicateContextSection').addClass('d-none');
            $('#duplicatePlaylistSelectWrapper').addClass('d-none');
            $('#duplicate_trainingplan_id').val('').removeClass('is-invalid');
            $('#duplicate_target_content').prop('checked', true).prop('disabled', false);
            $('#duplicate_target_playlist').prop('disabled', false);

            let $submitBtn = $('#btnSubmitDuplicate');
            let $cancelBtn = $('#btnCancelDuplicate');
            $submitBtn.data('submitting', false).prop('disabled', false).removeClass('disabled');
            $cancelBtn.prop('disabled', false).removeClass('disabled');
            $submitBtn.find('.btn-text').text('Duplicate Assessment');
            $submitBtn.find('i').attr('class', 'fas fa-copy mr-1 me-1');
        });

    });

    function copyAssessmentLink(text, button) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(function () {
                showCopySuccess(button);
            }).catch(function () {
                fallbackCopyText(text, button);
            });
        } else {
            fallbackCopyText(text, button);
        }
    }

    function fallbackCopyText(text, button) {
        let tempInput = $('<input>');

        $('body').append(tempInput);
        tempInput.val(text).select();

        try {
            document.execCommand('copy');
            showCopySuccess(button);
        } catch (err) {
            showToast('Copy link gagal. Silakan copy manual.', 'danger');
        }

        tempInput.remove();
    }

    function showCopySuccess(button) {
        let originalHtml = button.html();

        button
            .addClass('copy-success')
            .html('<i class="fas fa-check"></i>');

        showToast('Link assessment berhasil disalin.', 'success');

        setTimeout(function () {
            button
                .removeClass('copy-success')
                .html(originalHtml);
        }, 1200);
    }

    function showToast(message, type) {
        let bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
        let icon = type === 'success' ? 'fa-check-circle' : 'fa-times-circle';

        let toast = `
            <div class="toast owl-toast ${bgClass} text-white" role="alert" data-delay="2200">
                <div class="toast-body d-flex align-items-center">
                    <i class="fas ${icon} mr-2"></i>
                    <span>${message}</span>
                </div>
            </div>
        `;

        if ($('#owl-toast-wrapper').length === 0) {
            $('body').append('<div id="owl-toast-wrapper"></div>');
        }

        $('#owl-toast-wrapper').append(toast);

        let currentToast = $('#owl-toast-wrapper .toast').last();
        currentToast.toast('show');

        currentToast.on('hidden.bs.toast', function () {
            $(this).remove();
        });
    }
</script>