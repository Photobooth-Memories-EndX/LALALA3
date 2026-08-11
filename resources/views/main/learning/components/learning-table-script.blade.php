@once
<script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>

<script>
    $(function () {
        const initialSearch = @json($search ?? '');
        const defaultEntries = @json((int) ($entries ?? 25));

        console.log('datatable init');

        if ($.fn.DataTable.isDataTable('#learning-table')) {
            $('#learning-table').DataTable().destroy();
        }

        let learningTable = $('#learning-table').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            pageLength: defaultEntries,
            lengthMenu: [
                [10, 25, 50, 100],
                [10, 25, 50, 100]
            ],
            order: [[6, 'desc']],
            search: {
                search: initialSearch
            },
            ajax: {
                url: "{{ route('learning.manage.datatable') }}",
                data: function (d) {
                    d.status = $('#learn-status').val();
                    d.year = $('#learn-year').val();
                    d.classification_slug = $('#classification-slug').val();
                    d.content_slug = $('#content-slug').val();
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'status_badge', name: 'status' },
                { data: 'employee', name: 'user.name' },
                { data: 'learning_title', name: 'content.title' },
                { data: 'classification_name', name: 'content.classification.classification_name' },
                { data: 'duration', name: 'content.duration' },
                { data: 'learning_date', name: 'created_at' },
                { data: 'progress_bar', name: 'progress', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            language: {
                processing: '<div class="py-4 text-center"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 text-muted">Loading learning data...</div></div>'
            }
        });

        if (initialSearch) {
            learningTable.search(initialSearch).draw();
        }

        $('#btn-apply-filter').on('click', function () {
            learningTable.ajax.reload();
        });

        $('#btn-reset-filter').on('click', function () {
            $('#learning-filter-form')[0].reset();
            $('#learn-status').val('all');
            $('#learn-year').val('all');
            $('#learn-search').val('');
            $('#learn-entries').val(defaultEntries);
            learningTable.search('').page.len(defaultEntries).draw();
        });

        $('#learn-entries').on('change', function () {
            const nextLength = parseInt($(this).val(), 10) || defaultEntries;
            learningTable.page.len(nextLength).draw();
        });
    });
</script>
@endonce