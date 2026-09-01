<div class="card border-0 shadow-sm">
    <div class="card-header border-0 pb-0 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h3 class="card-title mb-1">Content User Learning Hours Records</h3>
            <div class="text-muted small">Server-side processing with bulk calculation capabilities for 160K+ records.</div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" id="btn-calculate-selected" class="btn btn-outline-primary btn-sm" disabled>
                <i class="ti ti-calculator me-1"></i> Calculate Selected (<span id="selected-count">0</span>)
            </button>
            <button type="button" id="btn-recalculate-selected" class="btn btn-outline-warning btn-sm" disabled>
                <i class="ti ti-refresh me-1"></i> Recalculate Selected
            </button>
            <button type="button" id="btn-calculate-all-remaining" class="btn btn-teal btn-sm text-white">
                <i class="ti ti-player-play me-1"></i> Calculate All Remaining
            </button>
            <button type="button" id="btn-recalculate-all" class="btn btn-outline-danger btn-sm">
                <i class="ti ti-reload me-1"></i> Recalculate All
            </button>
        </div>
    </div>

    <div class="card-body p-0 mt-3">
        <div class="table-responsive">
            <table id="content-user-lh-table" class="table table-vcenter table-striped card-table w-100">
                <thead>
                    <tr>
                        <th style="width: 40px;" class="text-center">
                            <input type="checkbox" class="form-check-input" id="check-all">
                        </th>
                        <th style="width: 50px;">No</th>
                        <th>User</th>
                        <th>Content Title</th>
                        <th>Type</th>
                        <th>Duration</th>
                        <th style="min-width: 120px;">Progress</th>
                        <th>User Learning Hours</th>
                        <th>Completed At</th>
                        <th>Year</th>
                        <th>Status</th>
                        <th style="width: 100px;" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
