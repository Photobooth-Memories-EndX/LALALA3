{{-- Spreadsheet Rows Audit Log Card --}}
<div class="card audit-table-card border-0 mb-4 shadow-sm">
    <div class="card-header border-bottom py-3 px-4 bg-transparent d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <i class="ti ti-list-details text-primary fs-2"></i>
                <h4 class="card-title fw-bold text-dark mb-0">Spreadsheet Rows Audit Log</h4>
            </div>
            <div class="card-subtitle text-muted mt-1 small" id="audit-rows-counter">
                Showing audit log rows and validation outcomes
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-outline-success px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2 btn-trigger-export" title="Download Excel with active filters">
                <i class="ti ti-file-spreadsheet fs-2"></i><span>Download Excel</span>
            </button>
        </div>
    </div>

    {{-- Filter Panel --}}
    <div class="audit-filter-panel">
        <form id="audit-rows-filter-form" onsubmit="return false;">
            <div class="row g-2 align-items-center">
                <!-- Filter: Action -->
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1" for="row-filter-action">Action Filter</label>
                    <select class="form-select form-select-sm rounded-3" id="row-filter-action" aria-label="Action Filter">
                        <option value="">All Actions</option>
                        <option value="IMPORTED">Action: IMPORTED</option>
                        <option value="SKIPPED">Action: SKIPPED</option>
                        <option value="FAILED">Action: FAILED</option>
                    </select>
                </div>

                <!-- Filter: Validation Status -->
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1" for="row-filter-status">Validation Status</label>
                    <select class="form-select form-select-sm rounded-3" id="row-filter-status" aria-label="Validation Status">
                        <option value="">All Validation Statuses</option>
                        <option value="VALID">Status: VALID</option>
                        <option value="WARNING">Status: WARNING</option>
                        <option value="DUPLICATE">Status: DUPLICATE</option>
                        <option value="INVALID">Status: INVALID</option>
                        <option value="INVALID COMPANY">Status: INVALID COMPANY</option>
                    </select>
                </div>

                <!-- Search Input: Employee ID, Name, Course -->
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold text-muted mb-1" for="row-filter-search">Search Records</label>
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                        <input type="text" class="form-control form-control-sm rounded-3" id="row-filter-search" placeholder="Search Employee ID, Name, or Course..." autocomplete="off">
                    </div>
                </div>

                <!-- Action Buttons: Apply & Reset -->
                <div class="col-12 col-md-2 d-flex align-items-end gap-1 mt-auto">
                    <button type="button" class="btn btn-sm btn-primary flex-fill rounded-3 py-1.5" id="btn-row-filter-apply" title="Apply Filters">
                        <i class="ti ti-filter me-1"></i>Filter
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-3 px-2.5 py-1.5" id="btn-row-filter-reset" title="Reset Filters">
                        <i class="ti ti-refresh"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Table Container --}}
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover audit-log-table mb-0" id="detail-rows-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 55px;">Row</th>
                        <th>Sheet</th>
                        <th>Employee ID</th>
                        <th>Employee Name</th>
                        <th>Course Name</th>
                        <th class="text-center" style="width: 110px;">Validation</th>
                        <th class="text-center" style="width: 100px;">Action</th>
                        <th>Notes</th>
                        <th class="text-center" style="width: 90px;">Refs</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
