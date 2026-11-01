<div class="card border-0 shadow-sm rounded-4 mb-4">

    <!-- Card Header with Title and Subtitle (No Dropdown at Top) -->
    <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
        <h4 class="card-title fw-bold text-dark mb-0">Learning History</h4>
        <p class="text-muted small mb-0">Daftar learning yang pernah diikuti oleh user ini.</p>
    </div>

    <!-- Quick Filter Toolbar Section -->
    <div class="px-3 pt-2 pb-3 border-bottom">
        <div class="text-secondary small fw-semibold mb-2" style="color: #6c757d !important; font-size: 0.85rem;">
            Quick Filter
        </div>

        <form id="profile-filter-form" onsubmit="return false;" class="w-100 m-0">
            <div class="d-flex align-items-center gap-2 flex-wrap flex-xl-nowrap quick-filter-toolbar w-100">
                <!-- 1. Search Learning Title -->
                <div class="position-relative flex-grow-1 quick-filter-title">
                    <input type="text" id="profile-title-input" name="learning_title"
                           class="form-control rounded-3" placeholder="Search learning title..." autocomplete="off">
                </div>

                <!-- 2. Classification Dropdown -->
                <div class="quick-filter-classification">
                    <select class="form-select rounded-3 quick-filter-select" id="profile-classification-select" name="classification">
                        <option value="all">All Classification</option>
                        @foreach ($classifications as $c)
                            <option value="{{ $c->id }}">{{ $c->classification_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 3. Status Dropdown -->
                <div class="quick-filter-status">
                    <select class="form-select rounded-3 quick-filter-select" id="profile-status-select" name="status">
                        <option value="all">All Status</option>
                        <option value="finished">Finished</option>
                        <option value="ongoing">Ongoing</option>
                    </select>
                </div>

                <!-- 4. Type Dropdown -->
                <div class="quick-filter-type">
                    <select class="form-select rounded-3 quick-filter-select" id="profile-type-select" name="type">
                        <option value="all">Type: All</option>
                        @foreach ($trainingTypes as $t)
                            <option value="{{ $t }}">{{ $t }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 5. Year Dropdown -->
                <div class="quick-filter-year">
                    <select class="form-select rounded-3 quick-filter-select" id="profile-year-select" name="year">
                        <option value="all">All Year</option>
                        @foreach ($years as $yr)
                            <option value="{{ $yr }}">{{ $yr }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 6. Search Action Button -->
                <button type="button" id="btn-profile-apply-filter" class="btn btn-primary btn-icon rounded-3 quick-filter-action-btn flex-shrink-0" style="background-color: #003b6d; border-color: #003b6d;" data-bs-toggle="tooltip" data-bs-title="Apply Filter" title="Apply Filter">
                    <i class="ti ti-search fs-2"></i>
                </button>

                <!-- 7. More Filters Button with Counter Badge -->
                <button type="button" id="btn-profile-more-filters" class="btn btn-outline-primary btn-icon rounded-3 position-relative btn-more-filters quick-filter-action-btn flex-shrink-0" data-bs-toggle="modal" data-bs-target="#profileMoreFiltersModal" title="More Filters">
                    <i class="ti ti-adjustments-horizontal fs-2"></i>
                    <span id="profileActiveFilterBadge" class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle d-none">0</span>
                </button>

                <!-- 8. Reset Action Button -->
                <button type="button" id="btn-profile-reset-filter" class="btn btn-outline-secondary btn-icon rounded-3 btn-quick-reset quick-filter-action-btn flex-shrink-0" data-bs-toggle="tooltip" data-bs-title="Reset Filters" title="Reset Filters">
                    <i class="ti ti-refresh fs-2"></i>
                </button>

                <!-- 9. Download Dropdown Button -->
                <div class="dropdown flex-shrink-0 quick-filter-download">
                    <button type="button" class="btn btn-success dropdown-toggle rounded-3 px-3 btn-download"
                            data-bs-toggle="dropdown" aria-expanded="false" id="btn-profile-download-dropdown" title="Download Records">
                        <i class="ti ti-download me-1"></i> Download
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border rounded-3 mt-1 py-1" aria-labelledby="btn-profile-download-dropdown" style="min-width: 220px;">
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:void(0);" id="btn-profile-download-excel">
                                <i class="ti ti-file-spreadsheet text-success fs-3"></i>
                                <span>Download Excel (.xlsx)</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:void(0);" id="btn-profile-download-csv">
                                <i class="ti ti-file-type-csv text-primary fs-3"></i>
                                <span>Download CSV (.csv)</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:void(0);" id="btn-profile-download-pdf">
                                <i class="ti ti-file-type-pdf text-danger fs-3"></i>
                                <span>Download PDF (.pdf)</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </form>
    </div>

    <!-- Hidden Inputs for More Filters Preservation -->
    <input type="hidden" id="profile-filter-from-date" value="">
    <input type="hidden" id="profile-filter-to-date" value="">
    <input type="hidden" id="profile-filter-progress-range" value="all">
    <input type="hidden" id="profile-filter-duration" value="all">

    <div class="card-body p-0">
        <!-- Top Synchronized Horizontal Scrollbar -->
        <div class="table-scroll-top-wrapper" id="profile-table-scroll-top-wrapper">
            <div class="table-scroll-top-inner" id="profile-table-scroll-top-inner"></div>
        </div>

        <!-- Primary Table Container with Horizontal Scrollbar -->
        <div class="table-scroll-container" id="profile-table-scroll-container">
            <table class="table table-hover align-middle mb-0" id="MyTable">
                <thead class="bg-light text-muted small text-uppercase">
                    <tr>
                        <th class="text-center" style="min-width: 45px; width: 45px;">NO</th>
                        <th class="text-center" style="min-width: 95px;">SOURCE</th>
                        <th class="text-center" style="min-width: 110px;">EMPLOYEE ID</th>
                        <th style="min-width: 160px;">EMPLOYEE NAME</th>
                        <th style="min-width: 130px;">TEAM</th>
                        <th style="min-width: 140px;">DEPARTMENT</th>
                        <th style="min-width: 130px;">COMPANY</th>
                        <th style="min-width: 220px;">COURSE TITLE</th>
                        <th class="text-center" style="min-width: 100px;">TYPE</th>
                        <th style="min-width: 110px;">CATEGORY</th>
                        <th style="min-width: 140px;">MAIN CLASSIFICATION</th>
                        <th class="text-center" style="min-width: 140px;">SUB CLASSIFICATION</th>
                        <th class="text-center" style="min-width: 120px;">DELIVERY METHOD</th>
                        <th style="min-width: 130px;">SUB DELIVERY METHOD</th>
                        <th class="text-center" style="min-width: 110px;">PROVIDER</th>
                        <th style="min-width: 130px;">PROVIDER NAME</th>
                        <th style="min-width: 120px;">VENUE</th>
                        <th class="text-center" style="min-width: 110px;">COURSE DURATION</th>
                        <th class="text-center" style="min-width: 130px;">TOTAL RECOGNIZED DURATION</th>
                        <th class="text-center" style="min-width: 110px;">LEARNING HOURS</th>
                        <th class="text-center" style="min-width: 110px;">% PROGRESS</th>
                        <th class="text-center" style="min-width: 110px;">PROGRESS STATUS</th>
                        <th style="min-width: 140px;">DATE (START - END)</th>
                        <th style="min-width: 120px;">TIME (START - END)</th>
                        <th class="text-center" style="min-width: 110px;">RECORD MONTH</th>
                        <th class="text-center" style="min-width: 110px;">REQUIREMENT</th>
                        <th class="text-center" style="min-width: 100px;">ATTENDANCE</th>
                        <th class="text-center" style="min-width: 120px;">SCORE (L1) - COURSE</th>
                        <th class="text-center" style="min-width: 90px;">SCORE (L2)</th>
                        <th class="text-center" style="min-width: 90px;">SCORE (L3)</th>
                        <th class="text-center" style="min-width: 130px;">CERTIFICATE AVAILABILITY</th>
                        <th style="min-width: 130px;">PERSON IN CHARGE (PIC)</th>
                        <th class="text-center" style="min-width: 130px;">TOTAL ENGAGEMENT TIME</th>
                        <th class="text-end" style="min-width: 80px; width: 80px;">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Loaded dynamically via DataTables AJAX -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Custom Pagination Footer Container with Bottom-Left Show Entries Dropdown -->
    <div class="card-footer bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-3" id="profile-table-footer">
        <!-- Bottom-Left: Show Entries Dropdown & Info Summary side-by-side -->
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="d-flex align-items-center">
                <select id="profile-table-entries-select" class="form-select form-select-sm" style="width: auto; min-width: 125px;">
                    <option value="10">10 Entries</option>
                    <option value="25" selected>25 Entries</option>
                    <option value="50">50 Entries</option>
                    <option value="100">100 Entries</option>
                </select>
            </div>
            <div class="text-muted small" id="profile-table-info-container">
                Showing 0 to 0 of 0 entries
            </div>
        </div>

        <!-- Bottom-Right: Pagination Controls -->
        <div id="profile-table-pagination-container"></div>
    </div>

</div>
