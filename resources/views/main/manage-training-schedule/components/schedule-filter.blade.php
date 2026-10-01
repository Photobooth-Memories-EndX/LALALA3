<div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
    <div class="card-body p-3">
        <!-- Label Header Quick Filter -->
        <div class="text-secondary small fw-semibold mb-2" style="color: #6c757d !important; font-size: 0.85rem;">
            Quick Filter
        </div>

        <form id="scheduleFilterForm" onsubmit="return false;">
            <div class="d-flex align-items-center gap-2 flex-wrap flex-lg-nowrap">
                <!-- 1. Category Select (Width: ~130px) -->
                <div style="min-width: 130px; flex-shrink: 0;">
                    <select id="scheduleCategorySelect" class="form-select rounded-3">
                        <option value="">All Category</option>
                        <option value="Soft Skill">Soft Skill</option>
                        <option value="Technical Skill">Technical Skill</option>
                        <option value="Compliance & Safety">Compliance & Safety</option>
                        <option value="Leadership">Leadership</option>
                    </select>
                </div>

                <!-- 2. Status Select (Width: ~120px) -->
                <div style="min-width: 120px; flex-shrink: 0;">
                    <select id="scheduleStatusSelect" name="status" class="form-select rounded-3">
                        <option value="">All Status</option>
                        <option value="open">Open Registration</option>
                        <option value="running">Running</option>
                        <option value="closed">Closed Registration</option>
                    </select>
                </div>

                <!-- 3. Month Select (Width: ~110px) -->
                <div style="min-width: 110px; flex-shrink: 0;">
                    <select id="scheduleMonthSelect" name="month" class="form-select rounded-3">
                        <option value="" selected>All Month</option>
                        <option value="1">January</option>
                        <option value="2">February</option>
                        <option value="3">March</option>
                        <option value="4">April</option>
                        <option value="5">May</option>
                        <option value="6">June</option>
                        <option value="7">July</option>
                        <option value="8">August</option>
                        <option value="9">September</option>
                        <option value="10">October</option>
                        <option value="11">November</option>
                        <option value="12">December</option>
                    </select>
                </div>

                <!-- 4. Year Select (Width: ~100px) -->
                <div style="min-width: 100px; flex-shrink: 0;">
                    <select id="scheduleYearSelect" name="year" class="form-select rounded-3">
                        <option value="" selected>All Year</option>
                        <option value="2026">2026</option>
                        <option value="2025">2025</option>
                        <option value="2027">2027</option>
                    </select>
                </div>

                <!-- 5. Search Input (Flex grow 1 with Search Icon on left) -->
                <div class="position-relative flex-grow-1" style="min-width: 200px;">
                    <div class="input-icon">
                        <span class="input-icon-addon">
                            <i class="ti ti-search text-muted fs-2"></i>
                        </span>
                        <input type="text" id="scheduleSearchInput" name="search" class="form-control rounded-3" 
                               placeholder="Search training title, instructor, or room..." autocomplete="off">
                    </div>
                </div>

                <!-- 6. Action Buttons Group (Right side) -->
                <div class="d-flex align-items-center gap-1 flex-shrink-0">
                    <!-- Search Submit Button -->
                    <button type="submit" id="btnSubmitScheduleFilter" class="btn btn-primary px-3 rounded-3" 
                            style="background-color: #003b6d; border-color: #003b6d;" title="Apply Filter">
                        <i class="ti ti-search fs-2"></i>
                    </button>

                    <!-- Export Download Button -->
                    <a href="{{ route('manage.training.export') }}" id="btnExportSchedule" class="btn btn-outline-success px-3 rounded-3 d-inline-flex align-items-center justify-content-center" 
                       title="Export Schedule Data (CSV / Excel)">
                        <i class="ti ti-download fs-2"></i>
                    </a>

                    <!-- More Filters Modal Trigger Button -->
                    <button type="button" class="btn btn-outline-primary px-3 rounded-3" 
                            style="color: #003b6d; border-color: #003b6d;" 
                            data-bs-toggle="modal" data-bs-target="#moreScheduleFiltersModal" title="More Filters">
                        <i class="ti ti-adjustments-horizontal fs-2"></i>
                    </button>

                    <!-- Reset Link Button -->
                    <a href="{{ url()->current() }}" id="btnResetScheduleFilter" class="btn btn-outline-primary px-3 rounded-3 d-inline-flex align-items-center justify-content-center" 
                       style="color: #003b6d; border-color: #003b6d;" title="Reset Filters">
                        <i class="ti ti-refresh fs-2"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
