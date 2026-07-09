{{-- Filter user. Data: $search, $division, $department, $team, $divisions, $departments, $teams --}}
<div class="card owl-main-card mb-3">

    <div class="card-header bg-white border-0">
        <div>
            <h3 class="card-title mb-1">Filter User</h3>
            <div class="text-muted small">
                Search users by name, employee ID, division, department, or team.
            </div>
        </div>
    </div>

    <div class="card-body border-top py-3">
        <form method="GET" action="{{ route('manage.role.index') }}">
            <div class="row align-items-end">

                <div class="col-md-3 mb-2">
                    <label class="form-label small text-muted mb-1">Search</label>
                    <input type="text" name="s" value="{{ $search }}"
                           class="form-control form-control-sm"
                           placeholder="Name / email / NIP...">
                </div>

                <div class="col-md-3 mb-2">
                    <label class="form-label small text-muted mb-1">Division</label>
                    <select name="division" class="form-select form-select-sm">
                        <option value="">All Division</option>
                        @foreach ($divisions as $item)
                            <option value="{{ $item }}" {{ $division == $item ? 'selected' : '' }}>{{ $item }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 mb-2">
                    <label class="form-label small text-muted mb-1">Department</label>
                    <select name="department" class="form-select form-select-sm">
                        <option value="">All Department</option>
                        @foreach ($departments as $item)
                            <option value="{{ $item }}" {{ $department == $item ? 'selected' : '' }}>{{ $item }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 mb-2">
                    <label class="form-label small text-muted mb-1">Team</label>
                    <select name="team" class="form-select form-select-sm">
                        <option value="">All Team</option>
                        @foreach ($teams as $item)
                            <option value="{{ $item }}" {{ $team == $item ? 'selected' : '' }}>{{ $item }}</option>
                        @endforeach
                    </select>
                </div>

            </div>

            <div class="row mt-2">
                <div class="col-12 text-right">
                    <a href="{{ route('manage.role.index') }}" class="btn btn-sm btn-light owl-action-btn">
                        <i class="ti ti-rotate mr-1"></i> Reset
                    </a>
                    <button type="submit" class="btn btn-sm btn-primary owl-action-btn">
                        <i class="ti ti-filter mr-1"></i> Terapkan Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

</div>
