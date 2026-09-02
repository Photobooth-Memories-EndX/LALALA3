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

    function updateAssessmentTypeConfiguration() {
        const typeEl = document.getElementById('assessment_type') || document.querySelector('select[name="type"]');
        if (!typeEl) return;

        const type = typeEl.value;

        const contentWrapper  = $('#content_id_wrapper');
        const tpWrapper       = $('#trainingplan_id_wrapper');
        const contentSelect   = $('#content_id');
        const tpSelect        = $('#trainingplan_id');

        const timeLimitSecWrapper = $('#time_limit_section_wrapper');
        const remedialSecWrapper  = $('#remedial_section_wrapper');
        const remedialInput       = $('#total-remedials');
        const remedialHint        = $('#total-remedials-hint');
        const sectionTitle        = $('#period-timer-section-title');
        const sectionSubtitle     = $('#period-timer-section-subtitle');

        if (type === 'content') {
            contentWrapper.removeClass('d-none');
            tpWrapper.addClass('d-none');
            if (contentSelect.length) contentSelect.attr('required', 'required');
            if (tpSelect.length) {
                tpSelect.removeAttr('required');
                tpSelect.val('').change();
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
            contentWrapper.addClass('d-none');
            tpWrapper.removeClass('d-none');
            if (contentSelect.length) {
                contentSelect.removeAttr('required');
                contentSelect.val('').change();
            }
            if (tpSelect.length) tpSelect.attr('required', 'required');

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
            contentWrapper.addClass('d-none');
            tpWrapper.addClass('d-none');
            if (contentSelect.length) {
                contentSelect.removeAttr('required');
                contentSelect.val('').change();
            }
            if (tpSelect.length) {
                tpSelect.removeAttr('required');
                tpSelect.val('').change();
            }

            timeLimitSecWrapper.addClass('d-none');
            remedialSecWrapper.addClass('d-none');
            if (remedialInput.length) {
                remedialInput.removeAttr('required').prop('disabled', true).val(0);
            }
            if (remedialHint.length) {
                remedialHint.text('Global Assessment allows 1 attempt per batch.');
            }
            if (sectionTitle.length) sectionTitle.text('Access Period');
            if (sectionSubtitle.length) sectionSubtitle.text('Schedule window configuration.');
        } else {
            contentWrapper.addClass('d-none');
            tpWrapper.addClass('d-none');
            if (contentSelect.length) contentSelect.removeAttr('required');
            if (tpSelect.length) tpSelect.removeAttr('required');
            timeLimitSecWrapper.removeClass('d-none');
            remedialSecWrapper.removeClass('d-none');
            if (remedialInput.length) remedialInput.prop('disabled', false);
        }
    }

    function setRequired(that) {
        updateAssessmentTypeConfiguration();
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
        updateAssessmentTypeConfiguration();
        toggleAccessPeriod(document.getElementById('has_access_period'));
    });
</script>
