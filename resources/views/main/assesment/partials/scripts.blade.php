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

        // =========================================================================
        // Bulk Duplicate Assessments Handlers (with Assessment ↔ Content Mapping)
        // =========================================================================
        let previewXhr = null;
        let playlistContentsXhr = null;
        let activeSourcesXhr = null;
        let cachedActiveSources = null;
        let currentPlaylistContents = [];

        function escapeHtml(text) {
            if (!text) return '';
            return $('<div>').text(text).html();
        }

        function updateBulkSelectionState() {
            let checkedBoxes = $('.assessment-select-checkbox:checked');
            let count = checkedBoxes.length;
            let totalEligible = $('.assessment-select-checkbox').length;

            $('#bulkSelectedCountBadge').text(count);

            let $checkAll = $('#checkAllAssessments');
            if (totalEligible > 0 && count === totalEligible) {
                $checkAll.prop('checked', true).prop('indeterminate', false);
            } else if (count > 0 && count < totalEligible) {
                $checkAll.prop('checked', false).prop('indeterminate', true);
            } else {
                $checkAll.prop('checked', false).prop('indeterminate', false);
            }

            let $bulkBtn = $('#btnOpenBulkDuplicate');
            if (count > 0) {
                $bulkBtn.prop('disabled', false).removeClass('disabled');
                $('#bulkDuplicateBtnText').text('Bulk Duplicate (' + count + ')');
            } else {
                $bulkBtn.prop('disabled', true).addClass('disabled');
                $('#bulkDuplicateBtnText').text('Bulk Duplicate');
            }
        }

        $(document).on('change', '#checkAllAssessments', function () {
            let isChecked = $(this).is(':checked');
            $('.assessment-select-checkbox').prop('checked', isChecked);
            updateBulkSelectionState();
        });

        $(document).on('change', '.assessment-select-checkbox', function () {
            updateBulkSelectionState();
        });

        $(document).on('click', '.btn-open-bulk-all', function () {
            $('#bulk_mode_all').prop('checked', true);
        });

        function getCurrentSelectedSources() {
            let sources = [];
            $('.assessment-select-checkbox:checked').each(function () {
                let $cb = $(this);
                sources.push({
                    id: parseInt($cb.val()),
                    title: $cb.data('title') || ('Assessment ' + $cb.val()),
                    content_id: parseInt($cb.data('content-id')) || null,
                    content_title: $cb.data('content-title') || ('Content ' + $cb.data('content-id'))
                });
            });
            return sources;
        }

        function getCurrentContentMapping() {
            let mapping = {};
            $('.bulk-content-select').each(function () {
                let aId = $(this).data('assessment-id');
                let cId = $(this).val();
                if (aId && cId) {
                    mapping[aId] = parseInt(cId);
                }
            });
            return mapping;
        }

        function validateMapping() {
            let counts = {};
            let hasDuplicates = false;
            let totalSelects = 0;

            $('.bulk-content-select').each(function () {
                totalSelects++;
                let cId = $(this).val();
                if (cId) {
                    counts[cId] = (counts[cId] || 0) + 1;
                    if (counts[cId] > 1) {
                        hasDuplicates = true;
                    }
                }
            });

            $('.bulk-content-select').each(function () {
                let cId = $(this).val();
                if (cId && counts[cId] > 1) {
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            if (hasDuplicates) {
                $('#bulkMappingErrorAlert').removeClass('d-none');
                $('#bulkMappingErrorMessage').text('Each Content can only be assigned to one Assessment.');
                $('#btnSubmitBulkDuplicate').prop('disabled', true);
                return false;
            } else {
                $('#bulkMappingErrorAlert').addClass('d-none');
                return true;
            }
        }

        function renderMappingTable(sources, availableContents, preservedMapping) {
            let $tbody = $('#bulkMappingTbody');
            $tbody.empty();

            if (!sources || sources.length === 0) {
                $tbody.append('<tr><td colspan="3" class="text-center text-muted py-3">No assessments selected.</td></tr>');
                return;
            }

            sources.forEach(function (source, idx) {
                let selectedContentId = (preservedMapping && preservedMapping[source.id])
                    ? preservedMapping[source.id]
                    : null;

                if (!selectedContentId && source.content_id) {
                    let match = availableContents.find(function (c) { return c.id == source.content_id; });
                    if (match) {
                        selectedContentId = match.id;
                    }
                }

                if (!selectedContentId && availableContents[idx]) {
                    selectedContentId = availableContents[idx].id;
                }

                let optionsHtml = '';
                availableContents.forEach(function (c) {
                    let isSelected = (selectedContentId == c.id) ? 'selected' : '';
                    optionsHtml += '<option value="' + c.id + '" ' + isSelected + '>' + escapeHtml(c.title) + '</option>';
                });

                let rowHtml = `
                    <tr data-assessment-id="${source.id}">
                        <td class="text-center text-muted fw-bold">${idx + 1}</td>
                        <td>
                            <div class="fw-semibold text-dark text-truncate" style="max-width: 220px;" title="${escapeHtml(source.title)}">
                                ${escapeHtml(source.title)}
                            </div>
                        </td>
                        <td>
                            <select class="form-select form-select-sm bulk-content-select" data-assessment-id="${source.id}">
                                ${optionsHtml}
                            </select>
                        </td>
                    </tr>
                `;
                $tbody.append(rowHtml);
            });

            validateMapping();
        }

        function refreshMappingAndPreview() {
            let mode = $('input[name="bulk_selection_mode"]:checked').val();
            let targetContext = $('input[name="bulk_target_context"]:checked').val();
            let trainingplanId = $('#bulk_playlist_id').val();
            let preservedMapping = getCurrentContentMapping();

            if (targetContext === 'playlist') {
                if (!trainingplanId) {
                    $('#bulkMappingPlaylistNotice').removeClass('d-none');
                    $('#bulkMappingTableWrapper').addClass('d-none');
                    $('#bulkMappingTbody').empty();
                    $('#previewTotal').text('-');
                    $('#previewWillCreate').text('-');
                    $('#previewAlreadyExists').text('-');
                    $('#bulkPreviewNotice').text('Please select a target playlist to calculate preview.');
                    $('#btnSubmitBulkDuplicate').prop('disabled', true);
                    return;
                }

                $('#bulkMappingPlaylistNotice').addClass('d-none');
                $('#bulkMappingTableWrapper').removeClass('d-none');
                $('#bulkMappingLoading').removeClass('d-none');

                if (playlistContentsXhr) playlistContentsXhr.abort();

                playlistContentsXhr = $.ajax({
                    type: 'GET',
                    url: '/manage/assesment/playlist/' + trainingplanId + '/contents',
                    dataType: 'json',
                    success: function (res) {
                        $('#bulkMappingLoading').addClass('d-none');
                        currentPlaylistContents = res.contents || [];

                        resolveSources(mode, function (sources) {
                            renderMappingTable(sources, currentPlaylistContents, preservedMapping);
                            if (validateMapping()) {
                                fetchBulkPreview(sources, getCurrentContentMapping());
                            }
                        });
                    },
                    error: function () {
                        $('#bulkMappingLoading').addClass('d-none');
                        $('#bulkMappingTbody').html('<tr><td colspan="3" class="text-center text-danger py-3">Failed to load playlist contents.</td></tr>');
                    }
                });

                return;
            }

            $('#bulkMappingPlaylistNotice').addClass('d-none');
            $('#bulkMappingTableWrapper').removeClass('d-none');

            resolveSources(mode, function (sources) {
                let contentMap = {};
                sources.forEach(function (s) {
                    if (s.content_id && !contentMap[s.content_id]) {
                        contentMap[s.content_id] = {
                            id: s.content_id,
                            title: s.content_title || ('Content ' + s.content_id)
                        };
                    }
                });
                let directContents = Object.values(contentMap);

                renderMappingTable(sources, directContents, preservedMapping);
                if (validateMapping()) {
                    fetchBulkPreview(sources, getCurrentContentMapping());
                }
            });
        }

        function resolveSources(mode, callback) {
            if (mode === 'selected') {
                callback(getCurrentSelectedSources());
            } else {
                if (cachedActiveSources) {
                    callback(cachedActiveSources);
                } else {
                    $('#bulkMappingLoading').removeClass('d-none');
                    if (activeSourcesXhr) activeSourcesXhr.abort();

                    activeSourcesXhr = $.ajax({
                        type: 'GET',
                        url: "{{ route('assesment.bulk-duplicate.sources') }}",
                        dataType: 'json',
                        success: function (res) {
                            $('#bulkMappingLoading').addClass('d-none');
                            cachedActiveSources = res.sources || [];
                            callback(cachedActiveSources);
                        },
                        error: function () {
                            $('#bulkMappingLoading').addClass('d-none');
                            callback([]);
                        }
                    });
                }
            }
        }

        function fetchBulkPreview(sources, mapping) {
            let mode = $('input[name="bulk_selection_mode"]:checked').val();
            let targetContext = $('input[name="bulk_target_context"]:checked').val();
            let trainingplanId = $('#bulk_playlist_id').val();

            if (targetContext === 'playlist' && !trainingplanId) {
                $('#previewTotal').text('-');
                $('#previewWillCreate').text('-');
                $('#previewAlreadyExists').text('-');
                $('#bulkPreviewNotice').text('Please select a target playlist to calculate preview.');
                $('#btnSubmitBulkDuplicate').prop('disabled', true);
                return;
            }

            if (!sources) {
                sources = (mode === 'selected') ? getCurrentSelectedSources() : (cachedActiveSources || []);
            }
            if (!mapping) {
                mapping = getCurrentContentMapping();
            }

            if (mode === 'selected' && sources.length === 0) {
                $('#previewTotal').text('0');
                $('#previewWillCreate').text('0');
                $('#previewAlreadyExists').text('0');
                $('#bulkPreviewNotice').text('No active assessments are currently selected. Please select at least one assessment or choose "All Active Content Assessments".');
                $('#btnSubmitBulkDuplicate').prop('disabled', true);
                return;
            }

            let sourceIds = sources.map(function (s) { return s.id; });

            let payload = {
                _token: "{{ csrf_token() }}",
                target_context: targetContext,
                trainingplan_id: targetContext === 'playlist' ? trainingplanId : null,
                select_all: (mode === 'all_active') ? 1 : 0,
                source_ids: sourceIds,
                content_mapping: mapping
            };

            if (previewXhr) {
                previewXhr.abort();
            }

            $('#bulkPreviewLoading').removeClass('d-none');
            $('#bulkPlaylistError').addClass('d-none');

            previewXhr = $.ajax({
                type: 'POST',
                url: "{{ route('assesment.bulk-duplicate.preview') }}",
                data: payload,
                dataType: 'json',
                success: function (res) {
                    $('#bulkPreviewLoading').addClass('d-none');
                    if (res.success && res.data) {
                        let data = res.data;
                        $('#previewTotal').text(data.total);
                        $('#previewWillCreate').text(data.will_be_created);
                        $('#previewAlreadyExists').text(data.already_exists);

                        if (targetContext === 'playlist') {
                            if (data.will_be_created > 0 && data.already_exists > 0) {
                                $('#bulkPreviewNotice').html(
                                    '<strong>' + data.will_be_created + '</strong> new assessment(s) will be created. ' +
                                    '<strong>' + data.already_exists + '</strong> existing assessment(s) in this playlist will be skipped.'
                                );
                            } else if (data.will_be_created > 0) {
                                $('#bulkPreviewNotice').text('All ' + data.will_be_created + ' assessment(s) will be created in the target playlist.');
                            } else {
                                $('#bulkPreviewNotice').text('All selected content already has active assessments in this playlist. No new records will be created.');
                            }
                        } else {
                            $('#bulkPreviewNotice').text('Existing assessments will remain unchanged. ' + data.will_be_created + ' new assessment copy(ies) will be created.');
                        }

                        if (data.will_be_created > 0 && validateMapping()) {
                            $('#btnSubmitBulkDuplicate').prop('disabled', false);
                        } else {
                            $('#btnSubmitBulkDuplicate').prop('disabled', true);
                        }
                    }
                },
                error: function (xhr) {
                    $('#bulkPreviewLoading').addClass('d-none');
                    if (xhr.statusText === 'abort') return;

                    let msg = 'Failed to load preview.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    $('#bulkPreviewNotice').text(msg);
                    $('#btnSubmitBulkDuplicate').prop('disabled', true);
                }
            });
        }

        $(document).on('change', 'input[name="bulk_selection_mode"]', function () {
            refreshMappingAndPreview();
        });

        $(document).on('change', 'input[name="bulk_target_context"]', function () {
            let val = $(this).val();
            if (val === 'playlist') {
                $('#bulkPlaylistSelectWrapper').removeClass('d-none');
            } else {
                $('#bulkPlaylistSelectWrapper').addClass('d-none');
                $('#bulk_playlist_id').val('');
            }
            refreshMappingAndPreview();
        });

        $(document).on('change', '#bulk_playlist_id', function () {
            if ($(this).val()) {
                $('#bulkPlaylistError').addClass('d-none');
            }
            refreshMappingAndPreview();
        });

        $(document).on('change', '.bulk-content-select', function () {
            if (validateMapping()) {
                fetchBulkPreview(null, getCurrentContentMapping());
            }
        });

        $('#modalBulkDuplicateAssessment').on('show.bs.modal', function () {
            updateBulkSelectionState();
            refreshMappingAndPreview();
        });

        $('#btnSubmitBulkDuplicate').on('click', function (e) {
            e.preventDefault();
            let $btn = $(this);
            if ($btn.data('submitting') === true) return;

            if (!validateMapping()) {
                return;
            }

            let mode = $('input[name="bulk_selection_mode"]:checked').val();
            let targetContext = $('input[name="bulk_target_context"]:checked').val();
            let trainingplanId = $('#bulk_playlist_id').val();

            if (targetContext === 'playlist' && !trainingplanId) {
                $('#bulkPlaylistError').removeClass('d-none');
                $('#bulk_playlist_id').focus();
                return;
            }

            let sources = (mode === 'selected') ? getCurrentSelectedSources() : (cachedActiveSources || []);
            let selectedIds = sources.map(function (s) { return s.id; });

            if (mode === 'selected' && selectedIds.length === 0) {
                showToast('Please select at least one active Content Assessment.', 'danger');
                return;
            }

            let mapping = getCurrentContentMapping();

            let payload = {
                _token: "{{ csrf_token() }}",
                target_context: targetContext,
                trainingplan_id: targetContext === 'playlist' ? trainingplanId : null,
                select_all: (mode === 'all_active') ? 1 : 0,
                source_ids: selectedIds,
                content_mapping: mapping
            };

            $btn.data('submitting', true).prop('disabled', true);
            $btn.find('i').attr('class', 'fas fa-spinner fa-spin mr-1 me-1');
            $btn.find('.btn-text').text('Duplicating...');
            $('#btnCancelBulkDuplicate').prop('disabled', true);

            $.ajax({
                type: 'POST',
                url: "{{ route('assesment.bulk-duplicate') }}",
                data: payload,
                dataType: 'json',
                success: function (res) {
                    $btn.data('submitting', false);
                    $btn.find('i').attr('class', 'fas fa-check mr-1 me-1');
                    $btn.find('.btn-text').text('Completed');
                    $('#btnCancelBulkDuplicate').prop('disabled', false).text('Close');

                    if (res.data) {
                        let data = res.data;
                        let detailsHtml = 'Created: <strong>' + data.success + '</strong> | Skipped: <strong>' + data.skipped + '</strong> | Failed: <strong>' + data.failed + '</strong>';

                        if (data.created_items && data.created_items.length > 0) {
                            detailsHtml += '<ul class="mt-2 mb-0 ps-3 small">';
                            data.created_items.slice(0, 5).forEach(function (item) {
                                detailsHtml += '<li>' + (item.title || ('ID ' + item.new_assessment_id)) + '</li>';
                            });
                            if (data.created_items.length > 5) {
                                detailsHtml += '<li>...and ' + (data.created_items.length - 5) + ' more</li>';
                            }
                            detailsHtml += '</ul>';
                        }

                        $('#bulkResultMessage').text(res.message || 'Duplication completed.');
                        $('#bulkResultDetails').html(detailsHtml);
                        $('#bulkExecutionResults').removeClass('d-none');
                        $('#bulkPreviewSection').addClass('d-none');
                        $('#bulkMappingSection').addClass('d-none');
                    }

                    showToast(res.message || 'Duplication completed.', 'success');

                    setTimeout(function () {
                        window.location.reload();
                    }, 1800);
                },
                error: function (xhr) {
                    $btn.data('submitting', false).prop('disabled', false);
                    $btn.find('i').attr('class', 'fas fa-copy mr-1 me-1');
                    $btn.find('.btn-text').text('Duplicate Assessments');
                    $('#btnCancelBulkDuplicate').prop('disabled', false);

                    let errMsg = 'An unexpected error occurred during bulk duplication.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errMsg = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        let errs = Object.values(xhr.responseJSON.errors).flat();
                        errMsg = errs.join(' ');
                    }

                    showToast(errMsg, 'danger');
                }
            });
        });

        $('#modalBulkDuplicateAssessment').on('hidden.bs.modal', function () {
            $('#bulkExecutionResults').addClass('d-none');
            $('#bulkPreviewSection').removeClass('d-none');
            $('#bulkMappingSection').removeClass('d-none');
            $('#bulkMappingErrorAlert').addClass('d-none');
            $('#bulkPlaylistError').addClass('d-none');
            $('#bulkPlaylistSelectWrapper').addClass('d-none');
            $('#bulk_playlist_id').val('');
            $('#bulk_target_content').prop('checked', true);
            $('#bulk_mode_selected').prop('checked', true);
            currentPlaylistContents = [];

            let $btn = $('#btnSubmitBulkDuplicate');
            $btn.data('submitting', false).prop('disabled', false);
            $btn.find('i').attr('class', 'fas fa-copy mr-1 me-1');
            $btn.find('.btn-text').text('Duplicate Assessments');
            $('#btnCancelBulkDuplicate').prop('disabled', false).text('Cancel');
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