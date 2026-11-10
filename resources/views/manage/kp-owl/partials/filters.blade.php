<div class="card border-0 shadow-sm rounded-4 mb-4 kp-owl-filter-card">
    <div class="card-body p-3">
        <!-- Label Header Quick Filter -->
        <div class="text-secondary small fw-semibold mb-2" style="color: #6c757d !important; font-size: 0.85rem;">
            Quick Filter
        </div>

        <form id="kp-owl-filter-form" onsubmit="return false;" class="w-100 m-0">
            <!-- Hidden inputs from More Filters Modal to preserve in quick filter submit/export -->
            <input type="hidden" id="filter-department" name="department" value="all">
            <input type="hidden" id="filter-team" name="team" value="all">
            <input type="hidden" id="filter-company" name="company" value="all">
            <input type="hidden" id="filter-month" name="record_month" value="all">
            <input type="hidden" id="filter-from-date" name="from_date" value="">
            <input type="hidden" id="filter-to-date" name="to_date" value="">
            <input type="hidden" id="filter-progress-range" name="progress_range" value="all">

            <div class="d-flex align-items-center gap-2 flex-wrap flex-xl-nowrap quick-filter-toolbar w-100">
                <!-- 1. Search Box (Employee ID, Name, Title) -->
                <div class="position-relative flex-grow-1 quick-filter-search">
                    <input type="text"
                           id="filter-search"
                           name="search"
                           class="form-control rounded-3"
                           placeholder="Search ID, name, course title..."
                           autocomplete="off">
                </div>

                <!-- 2. Main Classification Dropdown -->
                <div class="quick-filter-classification">
                    <select id="filter-classification" name="main_classification" class="form-select rounded-3 quick-filter-select">
                        <option value="all">All Classification</option>
                        @foreach($classifications as $cl)
                            <option value="{{ $cl->id }}">{{ $cl->classification_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 3. Category Dropdown -->
                <div class="quick-filter-category">
                    <select id="filter-category" name="category" class="form-select rounded-3 quick-filter-select">
                        <option value="all">All Category</option>
                        <option value="All Content">All Content</option>
                        <option value="Playlist">Playlist</option>
                    </select>
                </div>

                <!-- 4. Progress Status Dropdown -->
                <div class="quick-filter-status">
                    <select id="filter-status" name="progress_status" class="form-select rounded-3 quick-filter-select">
                        <option value="all">All Status</option>
                        <option value="finished">Finished</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="not_started">Not Started</option>
                    </select>
                </div>

                <!-- 5. Type Dropdown -->
                <div class="quick-filter-type">
                    <select id="filter-type" name="type" class="form-select rounded-3 quick-filter-select">
                        <option value="all">Type: All</option>
                        <option value="Video">Video</option>
                        <option value="Document">Document</option>
                        <option value="Playlist">Playlist</option>
                    </select>
                </div>

                <!-- 6. Year Dropdown -->
                <div class="quick-filter-year">
                    <select id="filter-year" name="year" class="form-select rounded-3 quick-filter-select">
                        <option value="all">All Year</option>
                        @foreach($years as $yr)
                            <option value="{{ $yr }}" {{ (string) $yr === (string) date('Y') ? 'selected' : '' }}>{{ $yr }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 7. Icon Button: Apply Filter -->
                <button type="button" id="btn-apply-filter" class="btn btn-primary btn-icon rounded-3 quick-filter-action-btn flex-shrink-0" style="background-color: #003b6d; border-color: #003b6d;" data-bs-toggle="tooltip" data-bs-title="Apply Filter" title="Apply Filter">
                    <i class="ti ti-search fs-2"></i>
                </button>

                <!-- 8. Icon Button: More Filters -->
                <button type="button" id="btn-more-filters" class="btn btn-outline-primary btn-icon rounded-3 position-relative btn-more-filters quick-filter-action-btn flex-shrink-0" data-bs-toggle="modal" data-bs-target="#kp-owl-more-filters-modal" title="More Filters">
                    <i class="ti ti-adjustments-horizontal fs-2"></i>
                    <span id="activeFilterBadge" class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle d-none">0</span>
                </button>

                <!-- 9. Icon Button: Reset -->
                <button type="button" id="btn-reset-filter" class="btn btn-outline-secondary btn-icon rounded-3 btn-quick-reset quick-filter-action-btn flex-shrink-0" data-bs-toggle="tooltip" data-bs-title="Reset Filters" title="Reset Filters">
                    <i class="ti ti-refresh fs-2"></i>
                </button>

                <!-- 10. Button: Download Dropdown -->
                <div class="dropdown flex-shrink-0 quick-filter-download">
                    <button type="button" class="btn btn-success dropdown-toggle rounded-3 px-3 btn-download"
                            data-bs-toggle="dropdown" aria-expanded="false" id="btn-download-dropdown" title="Download Records">
                        <i class="ti ti-download me-1"></i> Download
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border rounded-3 mt-1 py-1" aria-labelledby="btn-download-dropdown" style="min-width: 220px;">
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:void(0);" id="btn-export-excel">
                                <i class="ti ti-file-spreadsheet text-success fs-3"></i>
                                <span>Download Excel (52 Cols)</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:void(0);" id="btn-export-pdf">
                                <i class="ti ti-file-type-pdf text-danger fs-3"></i>
                                <span>Download PDF (.pdf)</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:void(0);" id="btn-export-csv">
                                <i class="ti ti-file-type-csv text-primary fs-3"></i>
                                <span>Download CSV (.csv)</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- More Filters Modal (Standardized with Master Reference) -->
<div class="modal fade" id="kp-owl-more-filters-modal" tabindex="-1" aria-labelledby="kpOwlMoreFiltersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <form id="kp-owl-more-filters-modal-form" onsubmit="return false;">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="kpOwlMoreFiltersModalLabel">
                        <i class="ti ti-adjustments-horizontal text-primary"></i> More Filters
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <div class="row g-3">
                        <!-- Department -->
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-dark">Department</label>
                            <select id="modal-filter-department" class="form-select rounded-3">
                                <option value="all">All Departments</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept }}">{{ $dept }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Team -->
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-dark">Team</label>
                            <select id="modal-filter-team" class="form-select rounded-3">
                                <option value="all">All Teams</option>
                                @foreach($teams as $t)
                                    <option value="{{ $t }}">{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Company -->
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-dark">Company</label>
                            <select id="modal-filter-company" class="form-select rounded-3">
                                <option value="all">All Companies</option>
                                @foreach($companies as $cmp)
                                    <option value="{{ $cmp }}">{{ $cmp }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Record Month -->
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-dark">Record Month</label>
                            <select id="modal-filter-month" class="form-select rounded-3">
                                <option value="all">All Months</option>
                                @foreach($months as $num => $mName)
                                    <option value="{{ $num }}">{{ $mName }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Date Range -->
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold text-dark"><i class="ti ti-calendar me-1"></i> Date Range (Learning Date)</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="date" id="modal-filter-from-date" class="form-control rounded-3" placeholder="From Date">
                                </div>
                                <div class="col-6">
                                    <input type="date" id="modal-filter-to-date" class="form-control rounded-3" placeholder="To Date">
                                </div>
                            </div>
                        </div>

                        <!-- Progress Range -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-dark d-block">Progress Range</label>
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_kp_progress_range" id="kpPrAll" value="all" checked>
                                    <label class="form-check-label text-dark" for="kpPrAll">All</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_kp_progress_range" id="kpPr0" value="0">
                                    <label class="form-check-label text-dark" for="kpPr0">0%</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_kp_progress_range" id="kpPr25" value="1-25">
                                    <label class="form-check-label text-dark" for="kpPr25">1 - 25%</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_kp_progress_range" id="kpPr50" value="26-50">
                                    <label class="form-check-label text-dark" for="kpPr50">26 - 50%</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_kp_progress_range" id="kpPr75" value="51-75">
                                    <label class="form-check-label text-dark" for="kpPr75">51 - 75%</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_kp_progress_range" id="kpPr99" value="76-99">
                                    <label class="form-check-label text-dark" for="kpPr99">76 - 99%</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_kp_progress_range" id="kpPr100" value="100">
                                    <label class="form-check-label text-dark" for="kpPr100">100%</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light d-flex justify-content-end align-items-center gap-2">
                    <!-- Reset Button -->
                    <button type="button" id="btn-modal-reset" class="btn btn-outline-secondary px-3 d-inline-flex align-items-center gap-1" data-bs-dismiss="modal">
                        <i class="ti ti-rotate me-1"></i>
                        <span>Reset</span>
                    </button>

                    <!-- Apply Filter Button -->
                    <button type="button" id="btn-modal-apply" class="btn btn-primary px-3 d-inline-flex align-items-center gap-1" style="background-color: #0c5b9e; border-color: #0c5b9e;" data-bs-dismiss="modal">
                        <i class="ti ti-check me-1"></i>
                        <span>Apply Filter</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
