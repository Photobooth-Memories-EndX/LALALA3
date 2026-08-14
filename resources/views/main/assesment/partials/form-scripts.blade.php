<script src="{{ asset('js/flatpickr.js') }}"></script>
<script>
    $(".input-date").flatpickr({
        enableTime: true,
        dateFormat: "Y-m-d H:i",
    });

    function updatePageTitle(val) {
        const titleEl = document.getElementById('dynamic_page_title');
        if (titleEl) {
            titleEl.textContent = val && val.trim() !== '' ? val : 'New Assessment';
        }
    }

    function setRequired(that) {
        if (!that) return;
        if (that.value == "content") {
            var elContent = document.getElementById("content_id");
            var elTp = document.getElementById("trainingplan_id");
            if (elContent) elContent.setAttribute('required', 'required');
            if (elTp) elTp.removeAttribute('required');
            $("#trainingplan_id_wrapper").addClass('d-none');
            $("#content_id_wrapper").removeClass('d-none');
            $("#trainingplan_id").val('').change();
        } else if (that.value == "trainingplan") {
            var elContent = document.getElementById("content_id");
            var elTp = document.getElementById("trainingplan_id");
            if (elTp) elTp.setAttribute('required', 'required');
            if (elContent) elContent.removeAttribute('required');
            $("#content_id_wrapper").addClass('d-none');
            $("#trainingplan_id_wrapper").removeClass('d-none');
            $("#content_id").val('').change();
        } else {
            var elContent = document.getElementById("content_id");
            var elTp = document.getElementById("trainingplan_id");
            if (elContent) elContent.removeAttribute('required');
            if (elTp) elTp.removeAttribute('required');
            $("#content_id_wrapper").addClass('d-none');
            $("#trainingplan_id_wrapper").addClass('d-none');
            $("#content_id").val('').change();
            $("#trainingplan_id").val('').change();
        }
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
        toggleAccessPeriod(document.getElementById('has_access_period'));
        toggleTimeLimit(document.getElementById('timer'));
    });
</script>
