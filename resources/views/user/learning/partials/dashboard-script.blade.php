@section('script')
<script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>

<script>
    $(function () {
        const currentUrl = new URL(window.location.href);

        if (currentUrl.searchParams.has('ongoing_page') ||
            currentUrl.searchParams.has('ongoing_search') ||
            currentUrl.searchParams.has('ongoing_status') ||
            currentUrl.searchParams.has('ongoing_year')) {

            $('.learning-tab-btn').removeClass('btn-primary').addClass('btn-white');
            $('.learning-tab-btn[data-target="#tab-ongoing"]').removeClass('btn-white').addClass('btn-primary');

            $('.learning-tab-content').addClass('d-none');
            $('#tab-ongoing').removeClass('d-none');
        }

        $('.learning-tab-btn').on('click', function () {
            let target = $(this).data('target');

            $('.learning-tab-btn').removeClass('btn-primary').addClass('btn-white');
            $(this).removeClass('btn-white').addClass('btn-primary');

            $('.learning-tab-content').addClass('d-none');
            $(target).removeClass('d-none');
        });

        if ($('#recordTable').length) {
            $('#recordTable').DataTable({
                ordering: false,
                pageLength: 10,
                autoWidth: false,
            });
        }

        if ($('#assessmentTable').length) {
            $('#assessmentTable').DataTable({
                ordering: false,
                pageLength: 10,
                autoWidth: false,
            });
        }

        if ($('#assessmentResultTable').length) {
            $('#assessmentResultTable').DataTable({
                ordering: false,
                pageLength: 10,
                autoWidth: false,
            });
        }
    });


</script>
@endsection