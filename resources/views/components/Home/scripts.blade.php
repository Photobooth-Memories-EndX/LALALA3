@section('script')

<script>

/* =====================================================
   ASSESSMENT ACCESS MODAL
===================================================== */

$('#btn-assessment-access').on('click', function () {

    $('#unique-code').val('');

    $('#invalid-text-unique-code').html('');

    $('#invalid-text-unique-code')
        .removeClass('d-block')
        .addClass('d-none');

    $('#modal-assessment-access').modal('show');

});


function submitAssessmentAccess() {

    let unique_code = $('#unique-code').val();

    $.ajax({

        url: "{{ route('home.user.access.assessment.by.unique.code') }}",

        type: "POST",

        data: {
            _token: "{{ csrf_token() }}",
            unique_code: unique_code,
        },

        success: function (response) {

            let routeAssessment =
                "{{ route('assesment.user.view', ':slug') }}";

            let routeAssessmentFixed =
                routeAssessment.replace(':slug', response.assessment_slug);

            window.location.replace(routeAssessmentFixed);

        },

        error: function (error) {

            if (error.responseJSON.unique_code) {
                $('#invalid-text-unique-code')
                    .html(error.responseJSON.unique_code[0])
                    .removeClass('d-none')
                    .addClass('d-block');
            }

            if (error.responseJSON.error_message) {
                $('#invalid-text-unique-code')
                    .html(error.responseJSON.error_message)
                    .removeClass('d-none')
                    .addClass('d-block');
            }

        }

    });

}

</script>

@endsection
