{{-- Resigned Employee DataTable Card --}}
<div class="card border border-light-subtle shadow-sm rounded-3 role-table-card">

    <div class="card-header bg-transparent border-0 py-3 px-3 px-md-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div>
                <h3 class="card-title fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                    <span>List Resigned Employees</span>
                    <span class="badge bg-danger-lt text-danger fs-5 px-2 py-1" id="badge-total-records">
                        {{ number_format($totalResigned) }} Resigned
                    </span>
                </h3>
                <div class="text-muted small">
                    Data diambil dinamis dari tabel <code>users</code> saat <code>deleted_at IS NOT NULL</code>.
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <button type="button" id="btn-refresh-table" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 shadow-sm rounded-2" title="Refresh Table Data">
                    <i class="ti ti-refresh fs-3"></i>
                    <span>Reload Data</span>
                </button>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table id="resign-users-table" class="table table-hover table-vcenter owl-table mb-0 w-100">
            <thead>
                <tr>
                    <th class="text-center" style="width: 45px;">No</th>
                    <th style="min-width: 110px;">Employee ID</th>
                    <th style="min-width: 220px;">Employee Name</th>
                    <th style="min-width: 100px;">SAP Code</th>
                    <th style="min-width: 160px;">Company</th>
                    <th style="min-width: 120px;">Grade</th>
                    <th style="min-width: 150px;">Job Position</th>
                    <th style="min-width: 150px;">Team</th>
                    <th style="min-width: 180px;">Department</th>
                    <th style="min-width: 180px;">Division</th>
                    <th style="min-width: 170px;">Org Unit</th>
                    <th style="min-width: 140px;">Resign Date</th>
                    <th class="text-center" style="width: 100px;">Status</th>
                    <th class="text-end" style="width: 80px;">Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

</div>
