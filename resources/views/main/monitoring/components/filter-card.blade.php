{{-- Props: $divisions, $departments, $teams, $roles --}}
<div class="card owl-main-card mb-3">

    <div class="card-header bg-white border-0">
        <div>
            <h3 class="card-title mb-1">Filter Monitoring</h3>
            <div class="text-muted small">Filter users by division, department, team, or role.</div>
        </div>
    </div>

    <div class="card-body border-top py-3">
        <div class="row align-items-end">

            <div class="col-md-4 col-lg mb-2">
                <label class="form-label small text-muted mb-1">Search User</label>
                <input id="filter-search" type="text" class="form-control form-control-sm"
                       placeholder="Name / NIP / email...">
            </div>

            <div class="col-md-4 col-lg mb-2">
                <label class="form-label small text-muted mb-1">Division</label>
                <select id="filter-division" class="form-control form-control-sm">
                    <option value="">All Division</option>
                    @foreach ($divisions as $division)
                        <option value="{{ $division }}">{{ $division }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4 col-lg mb-2">
                <label class="form-label small text-muted mb-1">Department</label>
                <select id="filter-department" class="form-control form-control-sm">
                    <option value="">All Department</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department }}">{{ $department }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4 col-lg mb-2">
                <label class="form-label small text-muted mb-1">Team</label>
                <select id="filter-team" class="form-control form-control-sm">
                    <option value="">All Team</option>
                    @foreach ($teams as $team)
                        <option value="{{ $team }}">{{ $team }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4 col-lg mb-2">
                <label class="form-label small text-muted mb-1">Role</label>
                <select id="filter-role" class="form-control form-control-sm">
                    <option value="">All Role</option>
                    @foreach ($roles as $roleId => $roleName)
                        <option value="{{ $roleId }}">{{ $roleName }}</option>
                    @endforeach
                </select>
            </div>

        </div>

        <div class="row mt-2">
            <div class="col-12 text-right">
                <button id="btn-filter" type="button" class="btn btn-sm btn-primary owl-action-btn">
                    <i class="fas fa-filter mr-1"></i> Filter
                </button>
                <button id="btn-reset" type="button" class="btn btn-sm btn-light owl-action-btn">
                    <i class="fas fa-undo mr-1"></i> Reset
                </button>
            </div>
        </div>
    </div>

</div>
