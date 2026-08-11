{{-- User scope table (role 3/4) - Yajra server-side. Loaded via AJAX, so script is inline. --}}
<style>
    .monitoring-link { transition: color .15s ease; cursor: pointer; }
    .monitoring-link:hover { color: var(--tblr-primary) !important; text-decoration: underline !important; }
</style>

<div class="card">
    <div class="card-header">
        <div>
            <h3 class="card-title mb-1">User List</h3>
            <div class="text-muted small">Select a user to view detailed learning monitoring information.</div>
        </div>
        <div class="card-actions">
            <div class="input-icon">
                <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                <input id="mlMonitoringSearch" type="text" class="form-control form-control-sm" placeholder="Cari name / NIP...">
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table id="mlMonitoringUserTable" class="table table-vcenter card-table table-hover" style="width:100%">
            <thead>
                <tr>
                    <th class="w-1">No</th>
                    <th>Nam</th>
                    <th>NIP</th>
                    <th>Division</th>
                    <th>Department</th>
                    <th>Team</th>
                    <th>Average Progress</th>
                    <th class="text-center">Finished</th>
                    <th class="text-center">Assessment</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<script>
    (function () {
        var tableId = '#mlMonitoringUserTable';
        var searchTimer;

        // Re-init aman bila tab dibuka ulang.
        if ($.fn.DataTable.isDataTable(tableId)) {
            $(tableId).DataTable().destroy();
        }

        var table = $(tableId).DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            pageLength: 10,
            lengthMenu: [[10, 25, 50], [10, 25, 50]],
            order: [[1, 'asc']],
            ajax: { url: @json(route('user.learning.monitoring.user.datatable')) },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'name_link', name: 'name'},
                {data: 'id_emp', name: 'id_emp'},
                {data: 'division', name: 'division'},
                {data: 'department', name: 'department'},
                {data: 'team', name: 'team'},
                {data: 'progress_badge', name: 'avg_progress', orderable: false, searchable: false},
                {data: 'finished_content_count', name: 'finished_content_count', orderable: false, searchable: false, className: 'text-center'},
                {data: 'assessment_count', name: 'assessment_count', orderable: false, searchable: false, className: 'text-center'},
                {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center'},
            ]
        });

        $('#mlMonitoringSearch').off('input.ml').on('input.ml', function () {
            clearTimeout(searchTimer);
            var keyword = this.value;
            searchTimer = setTimeout(function () { table.search(keyword).draw(); }, 400);
        });
    })();
</script>
