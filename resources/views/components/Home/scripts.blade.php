@section('script')

<script>

new ApexCharts(
document.querySelector("#hero-progress-chart"),
{
    chart:{
        type:'radialBar',
        height:250
    },

    series:[
        {{ $progressPercent }}
    ],

    labels:[
        'Daily Goal'
    ],

    plotOptions:{
        radialBar:{
            hollow:{
                size:'65%'
            },

            dataLabels:{
                value:{
                    fontSize:'34px'
                }
            }
        }
    }
}).render();

/* =======================================================
   MULTI CAROUSEL
======================================================= */

document.addEventListener("DOMContentLoaded", function () {

    let itemsTopVideo = document.querySelectorAll('#carousel-topvideo .carousel-item');
    let itemsVideo = document.querySelectorAll('#carousel-videos .carousel-item');
    let itemsDocument = document.querySelectorAll('#carousel-documents .carousel-item');
    let itemsPlaylist = document.querySelectorAll('#carousel-playlist .carousel-item');
    let itemsGallery = document.querySelectorAll('#carousel-event-photo .carousel-item');

    itemsTopVideo.forEach((el) => {
        const minPerSlide = 4;
        let next = el.nextElementSibling;

        for (let i = 1; i < minPerSlide; i++) {

            if (!next) {
                next = itemsTopVideo[0];
            }

            let cloneChild = next.cloneNode(true);

            el.appendChild(cloneChild.children[0]);

            next = next.nextElementSibling;
        }
    });

    itemsVideo.forEach((el) => {

        const minPerSlide = 4;

        let next = el.nextElementSibling;

        for (let i = 1; i < minPerSlide; i++) {

            if (!next) {
                next = itemsVideo[0];
            }

            let cloneChild = next.cloneNode(true);

            el.appendChild(cloneChild.children[0]);

            next = next.nextElementSibling;
        }

    });

    itemsDocument.forEach((el) => {

        const minPerSlide = 4;

        let next = el.nextElementSibling;

        for (let i = 1; i < minPerSlide; i++) {

            if (!next) {
                next = itemsDocument[0];
            }

            let cloneChild = next.cloneNode(true);

            el.appendChild(cloneChild.children[0]);

            next = next.nextElementSibling;
        }

    });

    itemsPlaylist.forEach((el) => {

        const minPerSlide = 3;

        let next = el.nextElementSibling;

        for (let i = 1; i < minPerSlide; i++) {

            if (!next) {
                next = itemsPlaylist[0];
            }

            let cloneChild = next.cloneNode(true);

            el.appendChild(cloneChild.children[0]);

            next = next.nextElementSibling;
        }

    });

    if(itemsGallery.length){

        itemsGallery.forEach((el) => {

            const minPerSlide = 1;

            let next = el.nextElementSibling;

            for (let i = 1; i < minPerSlide; i++) {

                if (!next) {
                    next = itemsGallery[0];
                }

                let cloneChild = next.cloneNode(true);

                el.appendChild(cloneChild.children[0]);

                next = next.nextElementSibling;
            }

        });

    }

});


/* =======================================================
   ASSESSMENT ACCESS
======================================================= */

$('#btn-assessment-access').on('click', function () {

    $('#unique-code').val('');

    $('#invalid-text-unique-code').html('');

    $('#invalid-text-unique-code')
        .removeClass('d-block')
        .addClass('d-none');

    $('#modal-assessment-access').modal('show');

});


function submitAssessmentAccess(){

    let unique_code = $('#unique-code').val();

    $.ajax({

        url: "{{ route('home.user.access.assessment.by.unique.code') }}",

        type: "POST",

        data: {

            _token: "{{ csrf_token() }}",

            unique_code: unique_code,

        },

        success: function(response){

            let routeAssessment =
                "{{ route('assesment.user.view', ':slug') }}";

            let routeAssessmentFixed =
                routeAssessment.replace(
                    ':slug',
                    response.assessment_slug
                );

            window.location.replace(
                routeAssessmentFixed
            );

        },

        error: function(error){

            if(error.responseJSON.unique_code){

                $('#invalid-text-unique-code')
                    .html(error.responseJSON.unique_code[0])
                    .removeClass('d-none')
                    .addClass('d-block');
            }

            if(error.responseJSON.error_message){

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
