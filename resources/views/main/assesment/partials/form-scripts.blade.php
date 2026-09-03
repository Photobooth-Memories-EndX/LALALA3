<script src="{{ asset('js/flatpickr.js') }}"></script>
<script>
    $(".input-date").flatpickr({
        enableTime: true,
        dateFormat: "Y-m-d H:i",
    });

    function updatePageTitle(val) {
        const titleEl = document.getElementById('dynamic_page_title');
        if (titleEl) {
            const fallback = titleEl.getAttribute('data-default-title') || 'Create Assessment';
            titleEl.textContent = val && val.trim() !== '' ? val : fallback;
        }
    }

    let playlistChangeXhr = null;

    function reinitContentSelect2(placeholder, isDisabled) {
        const $contentSelect = $('#content_id');
        if (!$.fn.select2) return;

        if ($contentSelect.hasClass('select2-hidden-accessible')) {
            $contentSelect.select2('destroy');
        }

        $contentSelect.prop('disabled', !!isDisabled);
        $contentSelect.attr('data-placeholder', placeholder);

        $contentSelect.select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: placeholder,
            allowClear: !isDisabled
        });

        $contentSelect.trigger('change');
    }

    function initPlaylistSelect2() {
        if (!$.fn.select2) return;
        const $tpSelect = $('#trainingplan_id');
        if (!$tpSelect.length) return;

        if (!$tpSelect.hasClass('select2-hidden-accessible')) {
            $tpSelect.select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Search playlist...',
                allowClear: true
            });
        }
    }

    function initAssessmentSelect2() {
        if (!$.fn.select2) return;

        initPlaylistSelect2();

        if (!$('#content_id').hasClass('select2-hidden-accessible')) {
            const placeholder = $('#content_id').attr('data-placeholder') || 'Search content...';
            const isDisabled  = $('#content_id').prop('disabled');
            $('#content_id').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: placeholder,
                allowClear: !isDisabled
            });
        }

        // Initialize any other select2 on page (e.g. question bank in edit page)
        $('select.select2').not('#trainingplan_id, #content_id').each(function() {
            if (!$(this).hasClass('select2-hidden-accessible')) {
                $(this).select2({
                    theme: 'bootstrap-5',
                    width: '100%'
                });
            }
        });
    }

    function updateAssessmentTypeConfiguration(isInitialLoad) {
        const typeEl = document.getElementById('assessment_type') || document.querySelector('select[name="type"]');
        if (!typeEl) return;

        const type = typeEl.value;

        const contentForWrapper = $('#content_for_wrapper');
        const contentWrapper    = $('#content_id_wrapper');
        const tpWrapper         = $('#trainingplan_id_wrapper');
        const tpLabel           = $('#trainingplan_id_label');
        const contentSelect     = $('#content_id');
        const tpSelect          = $('#trainingplan_id');

        const timeLimitSecWrapper = $('#time_limit_section_wrapper');
        const remedialSecWrapper  = $('#remedial_section_wrapper');
        const remedialInput       = $('#total-remedials');
        const remedialHint        = $('#total-remedials-hint');
        const sectionTitle        = $('#period-timer-section-title');
        const sectionSubtitle     = $('#period-timer-section-subtitle');

        if (type === 'content') {
            contentForWrapper.removeClass('d-none');
            contentWrapper.removeClass('d-none');

            const contentFor = $('input[name="assasment_content_for"]:checked').val() || 'content';

            if (contentFor === 'playlist') {
                tpWrapper.removeClass('d-none');
                if (tpLabel.length) tpLabel.text('Playlist');
                initPlaylistSelect2();

                const selectedPlaylistId = tpSelect.val();
                if (!selectedPlaylistId) {
                    if (!isInitialLoad) {
                        contentSelect.empty()
                            .append('<option value="">Please select a Playlist first</option>');
                        reinitContentSelect2('Please select a Playlist first', true);
                    }
                } else {
                    if (!isInitialLoad && contentSelect.children('option').length <= 1) {
                        tpSelect.trigger('change');
                    }
                }
            } else {
                // Direct Content
                tpWrapper.addClass('d-none');
                if (!isInitialLoad) {
                    tpSelect.val('').trigger('change');

                    if (window.__globalDirectContents && window.__globalDirectContents.length > 0) {
                        const currentVal = contentSelect.val();
                        contentSelect.empty();
                        contentSelect.append('<option value="">Search content...</option>');
                        window.__globalDirectContents.forEach(function(c) {
                            const isSelected = (currentVal == c.id) ? 'selected' : '';
                            contentSelect.append('<option value="' + c.id + '" ' + isSelected + '>' + $('<div>').text(c.title).html() + '</option>');
                        });
                    }
                    reinitContentSelect2('Search content...', false);
                }
            }

            timeLimitSecWrapper.removeClass('d-none');
            remedialSecWrapper.removeClass('d-none');
            if (remedialInput.length) {
                remedialInput.prop('disabled', false).attr('required', 'required');
                if (remedialInput.val() === '' || remedialInput.val() === null) {
                    remedialInput.val(0);
                }
            }
            if (remedialHint.length) {
                remedialHint.text('Number of additional remedial attempts after the initial assessment.');
            }
            if (sectionTitle.length) sectionTitle.text('Access Period & Time Limit');
            if (sectionSubtitle.length) sectionSubtitle.text('Schedule window, duration limits, and remedial attempt configuration.');

            toggleTimeLimit(document.getElementById('timer'));
        } else if (type === 'trainingplan') {
            contentForWrapper.addClass('d-none');
            contentWrapper.addClass('d-none');
            tpWrapper.removeClass('d-none');
            if (tpLabel.length) tpLabel.text('Training Plan');
            initPlaylistSelect2();
            contentSelect.val('').trigger('change');

            timeLimitSecWrapper.removeClass('d-none');
            remedialSecWrapper.removeClass('d-none');
            if (remedialInput.length) {
                remedialInput.prop('disabled', false).attr('required', 'required');
                if (remedialInput.val() === '' || remedialInput.val() === null) {
                    remedialInput.val(0);
                }
            }
            if (remedialHint.length) {
                remedialHint.text('Number of additional remedial attempts after the initial assessment.');
            }
            if (sectionTitle.length) sectionTitle.text('Access Period & Time Limit');
            if (sectionSubtitle.length) sectionSubtitle.text('Schedule window, duration limits, and remedial attempt configuration.');

            toggleTimeLimit(document.getElementById('timer'));
        } else if (type === 'global') {
            contentForWrapper.addClass('d-none');
            contentWrapper.addClass('d-none');
            tpWrapper.addClass('d-none');
            contentSelect.val('').trigger('change');
            tpSelect.val('').trigger('change');

            timeLimitSecWrapper.addClass('d-none');
            remedialSecWrapper.addClass('d-none');
            if (remedialInput.length) {
                remedialInput.removeAttr('required').prop('disabled', true).val(0);
            }
            if (remedialHint.length) {
                remedialHint.text('Global Assessment allows 1 attempt per batch.');
            }
            if (sectionTitle.length) sectionTitle.text('Access Period');
            if (sectionSubtitle.length) sectionSubtitle.text('Schedule window for Global Assessment.');

            toggleTimeLimit(null);
        } else {
            contentForWrapper.addClass('d-none');
            contentWrapper.addClass('d-none');
            tpWrapper.addClass('d-none');
        }
    }

    function setRequired(that) {
        updateAssessmentTypeConfiguration(false);
    }

    function toggleAccessPeriod(element) {
        const val = element ? element.value : '1';
        if (val === '1' || val === 'y' || val === 'yes' || val === true) {
            $('#section_period').removeClass('d-none');
            $('#datepicker-fromdate').attr('required', 'required');
            $('#datepicker-todate').attr('required', 'required');
        } else {
            $('#section_period').addClass('d-none');
            $('#datepicker-fromdate').removeAttr('required');
            $('#datepicker-todate').removeAttr('required');
        }
    }

    function toggleTimeLimit(element) {
        const val = element ? element.value : 'n';
        if (val === 'y' || val === '1' || val === 'yes' || val === true) {
            $('#time_limit_wrapper').removeClass('d-none');
            $('#time_limit_minutes').attr('required', 'required');
        } else {
            $('#time_limit_wrapper').addClass('d-none');
            $('#time_limit_minutes').removeAttr('required');
        }
    }

    $(document).ready(function() {
        initAssessmentSelect2();

        $(document).on('change', 'input[name="assasment_content_for"]', function() {
            updateAssessmentTypeConfiguration(false);
        });

        // Dependent Dropdown: Playlist change event
        $(document).on('change', '#trainingplan_id', function(e, opts) {
            if (opts && opts.silent) return;

            const type = $('#assessment_type').val();
            const contentFor = $('input[name="assasment_content_for"]:checked').val() || 'content';

            if (type !== 'content' || contentFor !== 'playlist') {
                return;
            }

            const playlistId = $(this).val();
            const $contentSelect = $('#content_id');

            if (!playlistId) {
                $contentSelect.empty()
                    .append('<option value="">Please select a Playlist first</option>');
                reinitContentSelect2('Please select a Playlist first', true);
                return;
            }

            if (playlistChangeXhr) {
                playlistChangeXhr.abort();
            }

            $contentSelect.empty()
                .append('<option value="">Loading content...</option>');
            reinitContentSelect2('Loading content...', true);

            $('#content_id_loading').removeClass('d-none');

            const endpointUrl = "{{ route('assesment.playlist.contents', ':id') }}".replace(':id', playlistId);

            playlistChangeXhr = $.ajax({
                type: 'GET',
                url: endpointUrl,
                dataType: 'json',
                success: function(res) {
                    $('#content_id_loading').addClass('d-none');
                    $contentSelect.empty();

                    const contents = res.contents || res.data || [];
                    if (contents.length > 0) {
                        $contentSelect.append('<option value="">Search content...</option>');
                        contents.forEach(function(c) {
                            const title = c.title || c.text || ('Content ' + c.id);
                            $contentSelect.append('<option value="' + c.id + '">' + $('<div>').text(title).html() + '</option>');
                        });
                        reinitContentSelect2('Search content...', false);
                    } else {
                        $contentSelect.append('<option value="">No content found in this playlist</option>');
                        reinitContentSelect2('No content found in this playlist', true);
                    }
                },
                error: function(xhr) {
                    if (xhr.statusText === 'abort') return;
                    $('#content_id_loading').addClass('d-none');
                    $contentSelect.empty()
                        .append('<option value="">Failed to load content</option>');
                    reinitContentSelect2('Failed to load content', true);
                }
            });
        });

        updateAssessmentTypeConfiguration(true);
        toggleAccessPeriod(document.getElementById('has_access_period'));
    });
</script>
