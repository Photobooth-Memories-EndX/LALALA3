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

        placeholder = placeholder || '— Select Learning Content (Optional / None) —';

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

        $contentSelect.trigger('change.select2');
    }

    function initPlaylistSelect2() {
        if (!$.fn.select2) return;
        const $tpSelect = $('#trainingplan_id');
        if (!$tpSelect.length) return;

        if (!$tpSelect.hasClass('select2-hidden-accessible')) {
            $tpSelect.select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: '— Select Playlist (Optional / None) —',
                allowClear: true
            });
        }
    }

    function initAssessmentSelect2() {
        if (!$.fn.select2) return;

        initPlaylistSelect2();

        if (!$('#content_id').hasClass('select2-hidden-accessible')) {
            const placeholder = $('#content_id').attr('data-placeholder') || '— Select Learning Content (Optional / None) —';
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
        const contentLabel      = $('#content_id_label');
        const contentSelect     = $('#content_id');
        const tpSelect          = $('#trainingplan_id');
        const hintEl            = $('#assasment_content_for_hint');

        const timeLimitSecWrapper = $('#time_limit_section_wrapper');
        const remedialSecWrapper  = $('#remedial_section_wrapper');
        const remedialInput       = $('#total-remedials');
        const remedialHint        = $('#total-remedials-hint');
        const sectionTitle        = $('#period-timer-section-title');
        const sectionSubtitle     = $('#period-timer-section-subtitle');

        if (type === 'content') {
            contentForWrapper.removeClass('d-none');

            const contentForEl = document.getElementById('assasment_content_for') || document.querySelector('select[name="assasment_content_for"]');
            const contentFor = contentForEl ? contentForEl.value : '';

            // Update helper text (PERUBAHAN 7)
            if (hintEl.length) {
                if (contentFor === 'content') {
                    hintEl.text('Assessment akan digunakan sebagai Direct Content dan tidak harus terhubung ke Playlist.').removeClass('d-none');
                } else if (contentFor === 'playlist') {
                    hintEl.text('Assessment akan digunakan pada materi di dalam Playlist. Playlist maupun Learning Content dapat dipilih atau dikosongkan.').removeClass('d-none');
                } else {
                    hintEl.text('').addClass('d-none');
                }
            }

            if (contentFor === 'content') {
                // Sembunyikan: Playlist
                tpWrapper.addClass('d-none');
                if (!isInitialLoad) {
                    tpSelect.val('').trigger('change.select2');
                }

                // Tampilkan: Learning Content (Optional)
                contentWrapper.removeClass('d-none');
                if (contentLabel.length) {
                    contentLabel.text('Learning Content (Optional)').removeClass('required');
                }

                if (!isInitialLoad) {
                    const currentVal = contentSelect.val();
                    contentSelect.empty();
                    contentSelect.append('<option value="">— Select Learning Content (Optional / None) —</option>');
                    if (window.__globalDirectContents && window.__globalDirectContents.length > 0) {
                        window.__globalDirectContents.forEach(function(c) {
                            const isSelected = (currentVal == c.id) ? 'selected' : '';
                            contentSelect.append('<option value="' + c.id + '" ' + isSelected + '>' + $('<div>').text(c.title).html() + '</option>');
                        });
                    }
                    reinitContentSelect2('— Select Learning Content (Optional / None) —', false);
                }
            } else if (contentFor === 'playlist') {
                // Tampilkan: Playlist (Optional)
                tpWrapper.removeClass('d-none');
                if (tpLabel.length) {
                    tpLabel.text('Playlist (Optional)').removeClass('required');
                }
                initPlaylistSelect2();

                // Tampilkan: Learning Content (Optional)
                contentWrapper.removeClass('d-none');
                if (contentLabel.length) {
                    contentLabel.text('Learning Content (Optional)').removeClass('required');
                }

                const selectedPlaylistId = tpSelect.val();
                if (!selectedPlaylistId) {
                    if (!isInitialLoad) {
                        const currentVal = contentSelect.val();
                        contentSelect.empty();
                        contentSelect.append('<option value="">— Select Learning Content (Optional / None) —</option>');
                        if (window.__globalDirectContents && window.__globalDirectContents.length > 0) {
                            window.__globalDirectContents.forEach(function(c) {
                                const isSelected = (currentVal == c.id) ? 'selected' : '';
                                contentSelect.append('<option value="' + c.id + '" ' + isSelected + '>' + $('<div>').text(c.title).html() + '</option>');
                            });
                        }
                        reinitContentSelect2('— Select Learning Content (Optional / None) —', false);
                    }
                } else {
                    if (!isInitialLoad && contentSelect.children('option').length <= 1) {
                        tpSelect.trigger('change');
                    }
                }
            } else {
                // Belum memilih apa pun: Sembunyikan kedua field
                tpWrapper.addClass('d-none');
                contentWrapper.addClass('d-none');
                if (!isInitialLoad) {
                    tpSelect.val('').trigger('change.select2');
                    contentSelect.val('').trigger('change.select2');
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
            if (tpLabel.length) tpLabel.text('Training Plan').addClass('required');
            initPlaylistSelect2();
            contentSelect.val('').trigger('change.select2');

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
            contentSelect.val('').trigger('change.select2');
            tpSelect.val('').trigger('change.select2');

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

        $(document).on('change', '#assasment_content_for, select[name="assasment_content_for"]', function() {
            updateAssessmentTypeConfiguration(false);
        });

        // Dependent Dropdown: Playlist change event
        $(document).on('change', '#trainingplan_id', function(e, opts) {
            if (opts && opts.silent) return;

            const type = $('#assessment_type').val();
            const contentForEl = document.getElementById('assasment_content_for') || document.querySelector('select[name="assasment_content_for"]');
            const contentFor = contentForEl ? contentForEl.value : '';

            if (type !== 'content' || contentFor !== 'playlist') {
                return;
            }

            const playlistId = $(this).val();
            const $contentSelect = $('#content_id');

            if (!playlistId) {
                if (playlistChangeXhr) {
                    playlistChangeXhr.abort();
                }
                $('#content_id_loading').addClass('d-none');

                const currentVal = $contentSelect.val();
                $contentSelect.empty();
                $contentSelect.append('<option value="">— Select Learning Content (Optional / None) —</option>');
                if (window.__globalDirectContents && window.__globalDirectContents.length > 0) {
                    window.__globalDirectContents.forEach(function(c) {
                        const isSelected = (currentVal == c.id) ? 'selected' : '';
                        $contentSelect.append('<option value="' + c.id + '" ' + isSelected + '>' + $('<div>').text(c.title).html() + '</option>');
                    });
                }
                reinitContentSelect2('— Select Learning Content (Optional / None) —', false);
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
                    $contentSelect.append('<option value="">— Select Learning Content (Optional / None) —</option>');
                    if (contents.length > 0) {
                        contents.forEach(function(c) {
                            const title = c.title || c.text || ('Content ' + c.id);
                            $contentSelect.append('<option value="' + c.id + '">' + $('<div>').text(title).html() + '</option>');
                        });
                        reinitContentSelect2('— Select Learning Content (Optional / None) —', false);
                    } else {
                        reinitContentSelect2('— Select Learning Content (Optional / None) —', false);
                    }
                },
                error: function(xhr) {
                    if (xhr.statusText === 'abort') return;
                    $('#content_id_loading').addClass('d-none');
                    $contentSelect.empty()
                        .append('<option value="">— Select Learning Content (Optional / None) —</option>');
                    reinitContentSelect2('— Select Learning Content (Optional / None) —', false);
                }
            });
        });

        updateAssessmentTypeConfiguration(true);
        toggleAccessPeriod(document.getElementById('has_access_period'));
    });
</script>
