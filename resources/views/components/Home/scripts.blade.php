@section('script')

<script src="{{ asset('Tabler/dist/libs/apexcharts/dist/apexcharts.min.js?1684106062') }}" defer></script>

<script>

/* =====================================================
   APEXCHARTS — Profile Sidebar Donut
===================================================== */

document.addEventListener("DOMContentLoaded", function () {

    var chartEl = document.getElementById('chart-demo-pie');
    if (!window.ApexCharts || !chartEl) return;

    new ApexCharts(chartEl, {
        chart: {
            type: "donut",
            fontFamily: 'inherit',
            height: 200,
            sparkline: { enabled: true },
            animations: { enabled: false },
        },
        fill: { opacity: 1 },
        series: [{{ $ongoingCourse }}, {{ $completedCourse }}],
        labels: ["On Going", "Completed"],
        colors: [
            tabler.getColor("primary"),
            tabler.getColor("success")
        ],
        legend: {
            show: true,
            position: 'bottom',
            markers: { width: 8, height: 8, radius: 100 },
            itemMargin: { horizontal: 8, vertical: 4 },
        },
        tooltip: { theme: 'dark', fillSeriesColor: false },
        dataLabels: { enabled: false },
        plotOptions: {
            pie: {
                donut: {
                    size: '72%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total',
                            color: '#64748b',
                            fontSize: '12px',
                            formatter: function (w) {
                                return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                            }
                        }
                    }
                }
            }
        }
    }).render();

});


/* =====================================================
   LATEST GALLERY — Auto-slide Carousel
===================================================== */

document.addEventListener("DOMContentLoaded", function () {

    var galleryCarouselEl = document.getElementById('homeLatestGalleryCarousel');
    if (!galleryCarouselEl || !window.bootstrap || !window.bootstrap.Carousel) return;

    new bootstrap.Carousel(galleryCarouselEl, {
        interval: 3500,
        pause: 'hover',
        wrap: true,
        ride: 'carousel',
    });

});


/* =====================================================
   ASSESSMENT ACCESS MODAL
===================================================== */

$('#btn-assessment-access').on('click', function () {

    $('#unique-code').val('');
    $('#invalid-text-unique-code')
        .html('')
        .removeClass('d-block')
        .addClass('d-none');

    $('#modal-assessment-access').modal('show');

});


function submitAssessmentAccess() {

    var unique_code = $('#unique-code').val();

    $.ajax({
        url: "{{ route('home.user.access.assessment.by.unique.code') }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            unique_code: unique_code,
        },
        success: function (response) {
            var route = "{{ route('assesment.user.view', ':slug') }}"
                .replace(':slug', response.assessment_slug);
            window.location.replace(route);
        },
        error: function (error) {
            var msg = 'An error occurred. Please try again.';
            if (error.responseJSON) {
                if (error.responseJSON.unique_code) {
                    msg = error.responseJSON.unique_code[0];
                } else if (error.responseJSON.error_message) {
                    msg = error.responseJSON.error_message;
                }
            }
            $('#invalid-text-unique-code')
                .html(msg)
                .removeClass('d-none')
                .addClass('d-block');
        }
    });

}

</script>

@endsection
