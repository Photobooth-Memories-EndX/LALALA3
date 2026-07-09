{{-- Props: $tableId, $ajaxUrl, $withFilters (bool), $title (optional), $subtitle (optional) --}}
@php
    $withFilters = $withFilters ?? false;
@endphp

<div class="card owl-main-card">

    <div class="card-header bg-white border-0">
        <div class="w-100 d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <div>
                <h3 class="card-title mb-1">{{ $title ?? 'List User' }}</h3>
                <div class="text-muted small">{{ $subtitle ?? 'Progress belajar user secara real-time.' }}</div>
            </div>
            @unless ($withFilters)
                <div class="mt-2 mt-md-0">
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="fas fa-search"></i></span>
                        <input id="{{ $tableId }}-search" type="text" class="form-control form-control-sm"
                               placeholder="Cari name / NIP...">
                    </div>
                </div>
            @endunless
        </div>
    </div>

    <div class="table-responsive">
        <table id="{{ $tableId }}" class="table table-hover table-vcenter owl-table mb-0" style="width:100%">
            <thead>
                <tr>
                    <th class="text-center">No</th>
                    <th>Nama</th>
                    <th>NIP</th>
                    <th>Division</th>
                    <th>Department</th>
                    <th>Team</th>
                    <th class="text-center">Content Final</th>
                    <th>Average Progress</th>
                    <th>Assessment Terakhir</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

</div>

@push('scripts')
<script>
    $(function () {
        let searchTimer;

        const table = $('#{{ $tableId }}').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            pageLength: 15,
            lengthMenu: [[10, 15, 25, 50], [10, 15, 25, 50]],
            order: [[1, 'asc']],
            dom: "<'row'<'col-sm-12'tr>><'row align-items-center mt-2'<'col-sm-12 col-md-5 text-muted small'i><'col-sm-12 col-md-7'p>>",
            ajax: {
                url: @json($ajaxUrl),
                @if ($withFilters)
                data: function (d) {
                    d.division   = $('#filter-division').val();
                    d.department = $('#filter-department').val();
                    d.team       = $('#filter-team').val();
                    d.role       = $('#filter-role').val();
                }
                @endif
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center'},
                {data: 'name_link', name: 'name'},
                {data: 'id_emp', name: 'id_emp'},
                {data: 'division', name: 'division'},
                {data: 'department', name: 'department'},
                {data: 'team', name: 'team'},
                {data: 'finished_content_count', name: 'finished_content_count', orderable: false, searchable: false, className: 'text-center'},
                {data: 'progress_badge', name: 'avg_progress', orderable: false, searchable: false},
                {data: 'last_assessment', name: 'last_assessment_at', orderable: false, searchable: false},
                {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-right'},
            ]
        });

        @if ($withFilters)
        $('#btn-filter').on('click', function () {
            table.ajax.reload();
        });

        $('#btn-reset').on('click', function () {
            $('#filter-division, #filter-department, #filter-team, #filter-role, #filter-search').val('');
            table.search('').ajax.reload();
        });

        $('#filter-search').on('input', function () {
            clearTimeout(searchTimer);
            const keyword = this.value;
            searchTimer = setTimeout(() => table.search(keyword).draw(), 400);
        });
        @else
        $('#{{ $tableId }}-search').on('input', function () {
            clearTimeout(searchTimer);
            const keyword = this.value;
            searchTimer = setTimeout(() => table.search(keyword).draw(), 400);
        });
        @endif
    });
</script>
@endpush
