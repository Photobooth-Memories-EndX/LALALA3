<div class="card border-0 shadow-sm">
    <div class="card-header border-0 pb-0 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h3 class="card-title mb-1">Playlist Content User Learning Hours</h3>
            <div class="text-muted small">Server-side DataTables with pure numeric sorting and Master vs Actual comparison for 244K+ records.</div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <button type="button" id="btn-refresh-table" class="btn btn-outline-secondary btn-sm">
                <i class="ti ti-refresh me-1"></i> Refresh
            </button>
        </div>
    </div>

    <div class="card-body p-0 mt-3">
        <div class="table-responsive">
            <table id="playlist-content-user-lh-table" class="table table-vcenter table-striped card-table w-100">
                <thead>
                    <tr>
                        <th style="width: 45px;">No</th>
                        <th>User</th>
                        <th>Playlist</th>
                        <th>Content Title</th>
                        <th>Type</th>
                        <th style="min-width: 110px;">Progress</th>
                        <th>Current Time</th>
                        <th>Duration</th>
                        <th class="text-nowrap">Actual Learning Hour</th>
                        <th class="text-nowrap">Master Learning Hour</th>
                        <th>Calculation Source</th>
                        <th>Status</th>
                        <th>Completed At</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
