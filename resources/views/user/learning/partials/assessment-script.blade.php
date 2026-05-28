@section('script')
<script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>

<script>
    $(function () {
        if ($.fn.DataTable.isDataTable('#table-list-assessment')) {
            $('#table-list-assessment').DataTable().destroy();
        }

        if ($('#table-list-assessment').length) {
            const assessmentTable = $('#table-list-assessment').DataTable({
                ordering: false,
                pageLength: 10,
                lengthMenu: [
                    [10, 25, 50, -1],
                    [10, 25, 50, 'All']
                ],
                autoWidth: false,
                scrollX: true,
                dom: 'rt<"d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3"lip>',
                language: {
                    search: "",
                    searchPlaceholder: "Cari assessment..."
                }
            });

            $('#assessmentSearch').on('keyup', function () {
                assessmentTable.search(this.value).draw();
            });
        }

        if ($.fn.DataTable.isDataTable('#MyTable')) {
            $('#MyTable').DataTable().destroy();
        }

        if ($('#MyTable').length) {
            const resultTable = $('#MyTable').DataTable({
                ordering: false,
                pageLength: 10,
                lengthMenu: [
                    [10, 25, 50, -1],
                    [10, 25, 50, 'All']
                ],
                autoWidth: false,
                scrollX: true,
                dom: 'rt<"d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3"lip>',
                language: {
                    search: "",
                    searchPlaceholder: "Cari hasil assessment..."
                }
            });

            $('#resultSearch').on('keyup', function () {
                resultTable.search(this.value).draw();
            });
        }
    });
</script>
@endsection