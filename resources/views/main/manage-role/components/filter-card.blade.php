{{-- Quick Filter Card. Data: $search, $division, $department, $team, $orgUnit, $entries, $divisions, $departments, $teams, $orgUnits --}}
<div class="card border border-light-subtle shadow-sm rounded-4 mb-4 role-filter-card">
    <div class="card-body p-3">
        <div class="fw-semibold mb-2 filter-label-head" style="font-size: 0.85rem;">
            Quick Filter
        </div>

        <form id="role-filter-form" action="{{ route('manage.role.index') }}" method="GET" class="m-0 p-0">
            <!-- Hidden inputs for More Filters Modal -->
            <input type="hidden" name="department" value="{{ request('department') }}">
            <input type="hidden" name="org_unit" value="{{ request('org_unit') }}">

            <div class="d-flex align-items-center gap-2 flex-nowrap w-100">

                <!-- 1. Entries Select (FAR LEFT) -->
                <div style="width: 110px; flex-shrink: 0;">
                    <select class="form-select rounded-3" name="entries" onchange="this.form.submit()">
                        @foreach ([10, 15, 25, 50, 100] as $opt)
                            <option value="{{ $opt }}" {{ (int)request('entries', 15) === $opt ? 'selected' : '' }}>{{ $opt }} Entries</option>
                        @endforeach
                    </select>
                </div>

                <!-- 2. Division Select -->
                <div style="width: 160px; flex-shrink: 0;">
                    <select class="form-select rounded-3" name="division" onchange="this.form.submit()">
                        <option value="">All Division</option>
                        @foreach ($divisions as $item)
                            <option value="{{ $item }}" {{ request('division') == $item ? 'selected' : '' }}>{{ $item }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 3. Team Select -->
                <div style="width: 140px; flex-shrink: 0;">
                    <select class="form-select rounded-3" name="team" onchange="this.form.submit()">
                        <option value="">All Team</option>
                        @foreach ($teams as $item)
                            <option value="{{ $item }}" {{ request('team') == $item ? 'selected' : '' }}>{{ $item }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 4. Search Input Box -->
                <div class="position-relative flex-grow-1 min-w-0">
                    <input type="text" name="q" value="{{ request('q') ?? ($search ?? '') }}"
                           class="form-control rounded-3"
                           placeholder="Name, email, NIP..."
                           autocomplete="off">
                </div>

                <!-- 5. Action Buttons (Search, More Filters, Reset) -->
                <div class="d-flex align-items-center gap-1 flex-shrink-0">
                    <!-- Search Submit Button -->
                    <button type="submit" class="btn btn-primary px-3" style="background-color: #003b6d; border-color: #003b6d;" title="Search">
                        <i class="ti ti-search fs-2"></i>
                    </button>

                    <!-- More Filters Modal Trigger Button -->
                    <button type="button" class="btn btn-outline-primary px-3 position-relative" style="color: #003b6d; border-color: #003b6d;" data-bs-toggle="modal" data-bs-target="#moreRoleFiltersModal" title="More Filters">
                        <i class="ti ti-adjustments-horizontal fs-2"></i>
                        @if(request('department') || request('org_unit'))
                            <span class="position-absolute top-0 start-100 translate-middle p-1 bg-primary border border-light rounded-circle">
                                <span class="visually-hidden">Active filter</span>
                            </span>
                        @endif
                    </button>

                    <!-- Reset Button -->
                    <a href="{{ route('manage.role.index') }}" class="btn btn-outline-primary px-3" style="color: #003b6d; border-color: #003b6d;" title="Reset Filters">
                        <i class="ti ti-refresh fs-2"></i>
                    </a>
                </div>

            </div>
        </form>
    </div>
</div>

<!-- More Role Filters Modal -->
<div class="modal fade" id="moreRoleFiltersModal" tabindex="-1" aria-labelledby="moreRoleFiltersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="moreRoleFiltersModalLabel">
                    <i class="ti ti-adjustments-horizontal text-primary"></i> More Role Filters
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('manage.role.index') }}" method="GET">
                <div class="modal-body py-3">
                    <!-- Preserved parameters -->
                    <input type="hidden" name="q" value="{{ request('q') ?? ($search ?? '') }}">
                    <input type="hidden" name="entries" value="{{ request('entries', 15) }}">
                    <input type="hidden" name="division" value="{{ request('division') }}">
                    <input type="hidden" name="team" value="{{ request('team') }}">

                    <!-- Department Filter -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small">Department</label>
                        <select name="department" class="form-select rounded-3">
                            <option value="">All Department</option>
                            @foreach ($departments as $item)
                                <option value="{{ $item }}" {{ request('department') == $item ? 'selected' : '' }}>{{ $item }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Organization Unit Filter -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small">Organization Unit</label>
                        <select name="org_unit" class="form-select rounded-3">
                            <option value="">All Organization Unit</option>
                            @foreach ($orgUnits as $item)
                                <option value="{{ $item }}" {{ request('org_unit') == $item ? 'selected' : '' }}>{{ $item }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-footer border-top pt-3">
                    <a href="{{ route('manage.role.index', array_filter(['q' => request('q') ?? ($search ?? ''), 'entries' => request('entries', 15), 'division' => request('division'), 'team' => request('team')])) }}" class="btn btn-outline-secondary px-3">
                        Reset
                    </a>
                    <button type="submit" class="btn btn-primary px-4" style="background-color: #003b6d; border-color: #003b6d;" data-bs-dismiss="modal">
                        <i class="ti ti-check me-1"></i> Apply Filter
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

