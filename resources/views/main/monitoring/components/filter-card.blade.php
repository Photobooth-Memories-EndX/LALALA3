{{-- Props: $divisions, $departments, $teams, $roles --}}
<div class="card border border-light-subtle shadow-sm rounded-4 mb-4 monitoring-filter-card">
    <div class="card-body p-3">
        <div class="fw-semibold mb-2 filter-label-head" style="font-size: 0.85rem;">
            Quick Filter
        </div>

        <form id="monitoring-filter-form" action="javascript:void(0);" method="GET" class="m-0 p-0">
            <!-- Hidden inputs for More Filters Modal -->
            <input type="hidden" id="filter-department" name="department" value="">
            <input type="hidden" id="filter-team" name="team" value="">

            <div class="d-flex align-items-center gap-2 flex-nowrap w-100">

                <!-- 1. Entries Select (FAR LEFT) -->
                <div style="width: 110px; flex-shrink: 0;">
                    <select id="filter-entries" class="form-select rounded-3">
                        <option value="10">10 Entries</option>
                        <option value="15" selected>15 Entries</option>
                        <option value="25">25 Entries</option>
                        <option value="50">50 Entries</option>
                        <option value="100">100 Entries</option>
                    </select>
                </div>

                <!-- 2. Division Select -->
                <div style="width: 160px; flex-shrink: 0;">
                    <select id="filter-division" class="form-select rounded-3">
                        <option value="">All Division</option>
                        @foreach ($divisions as $division)
                            <option value="{{ $division }}">{{ $division }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 3. Role Select -->
                <div style="width: 140px; flex-shrink: 0;">
                    <select id="filter-role" class="form-select rounded-3">
                        <option value="">All Role</option>
                        @foreach ($roles as $roleId => $roleName)
                            <option value="{{ $roleId }}">{{ $roleName }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 4. Live Search Input Container with Dropdown Popup -->
                <div class="position-relative flex-grow-1 min-w-0">
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                        <input type="text" id="search_monitoring_user" name="q"
                               class="form-control rounded-3"
                               placeholder="Name / NIP / email..."
                               autocomplete="off">
                    </div>

                    <!-- Live Autocomplete Floating Dropdown -->
                    <div id="monitoring-search-results" class="dropdown-menu shadow-sm w-100 position-absolute mt-1 p-0 overflow-hidden"
                         style="display: none; z-index: 2000; background-color: #ffffff;">
                        <!-- Container 1: Scrollable List Area -->
                        <div id="monitoring-autocomplete-list" class="list-group list-group-flush"
                             style="max-height: 280px; overflow-y: auto; background-color: #ffffff;"></div>

                        <!-- Container 2: Fixed/Sticky Bottom Footer -->
                        <div id="monitoring-search-footer" class="border-top p-2 text-center"
                             style="display: none; position: sticky; bottom: 0; z-index: 2005; background-color: #f8f9fa;"></div>
                    </div>
                </div>

                <!-- 5. Action Buttons (Search, More Filters, Reset) -->
                <div class="d-flex align-items-center gap-1 flex-shrink-0">
                    <!-- Search Submit Button -->
                    <button type="button" id="btn-filter" class="btn btn-primary px-3" style="background-color: #003b6d; border-color: #003b6d;" title="Search">
                        <i class="ti ti-search fs-2"></i>
                    </button>

                    <!-- More Filters Modal Trigger Button -->
                    <button type="button" id="btn-more-filters" class="btn btn-outline-primary px-3 position-relative" style="color: #003b6d; border-color: #003b6d;" data-bs-toggle="modal" data-bs-target="#moreMonitoringFiltersModal" title="More Filters">
                        <i class="ti ti-adjustments-horizontal fs-2"></i>
                        <span id="more-filters-badge" class="position-absolute top-0 start-100 translate-middle p-1 bg-primary border border-light rounded-circle" style="display: none;">
                            <span class="visually-hidden">Active filter</span>
                        </span>
                    </button>

                    <!-- Reset Button -->
                    <button type="button" id="btn-reset" class="btn btn-outline-primary px-3" style="color: #003b6d; border-color: #003b6d;" title="Reset Filters">
                        <i class="ti ti-refresh fs-2"></i>
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>

<!-- More Monitoring Filters Modal -->
<div class="modal fade" id="moreMonitoringFiltersModal" tabindex="-1" aria-labelledby="moreMonitoringFiltersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="moreMonitoringFiltersModalLabel">
                    <i class="ti ti-adjustments-horizontal text-primary"></i> More Monitoring Filters
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <!-- Department Filter -->
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark small" for="modal-filter-department">Department</label>
                    <select id="modal-filter-department" class="form-select rounded-3">
                        <option value="">All Department</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department }}">{{ $department }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Team Filter -->
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark small" for="modal-filter-team">Team</label>
                    <select id="modal-filter-team" class="form-select rounded-3">
                        <option value="">All Team</option>
                        @foreach ($teams as $team)
                            <option value="{{ $team }}">{{ $team }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="modal-footer border-top pt-3">
                <button type="button" id="btn-modal-reset" class="btn btn-outline-secondary px-3">
                    Reset
                </button>
                <button type="button" id="btn-modal-apply" class="btn btn-primary px-4" style="background-color: #003b6d; border-color: #003b6d;" data-bs-dismiss="modal">
                    <i class="ti ti-check me-1"></i> Apply Filter
                </button>
            </div>
        </div>
    </div>
</div>

