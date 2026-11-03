@extends('layouts.sidebar-master')

@section('content')
    <div class="page-body manage-record-page">
        <div class="container-xl">
            <!-- BACK BUTTON -->
            <div class="mb-3">
                <a href="{{ $backUrl }}"
                    class="btn-back d-inline-flex align-items-center gap-1 text-decoration-none fw-medium">
                    <i class="ti ti-arrow-left"></i>
                    <span>{{ $backTitle }}</span>
                </a>
            </div>

            <!-- PARTICIPANT HEADER CARD -->
            <div class="card border-0 shadow-sm p-3 p-md-4 mb-4 rounded-3 bg-white record-section-header">
                <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 record-section-icon"
                            style="width: 56px; height: 56px; background-color: #eff6ff; color: #1d4ed8; font-size: 24px; font-weight: 700;">
                            <i class="ti ti-user fs-1"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                <h2 class="h3 fw-bold mb-0 text-dark" id="participant-name">{{ $participant['name'] }}</h2>
                                @if (!empty($participant['employee_id']))
                                    <span class="badge bg-primary-lt fw-bold px-2 py-1" id="participant-nip">NIP:
                                        {{ $participant['employee_id'] }}</span>
                                @endif
                                @if (!empty($participant['grade']))
                                    <span class="badge bg-light text-secondary border px-2 py-1">Grade:
                                        {{ $participant['grade'] }}</span>
                                @endif
                            </div>
                            <div class="text-muted small d-flex align-items-center flex-wrap gap-2 pt-1">
                                @if (!empty($participant['job_position']))
                                    <span class="d-inline-flex align-items-center gap-1">
                                        <i class="ti ti-briefcase text-secondary"></i>
                                        <span>{{ $participant['job_position'] }}</span>
                                    </span>
                                @endif
                                @if (!empty($participant['job_position']) && (!empty($participant['department']) || !empty($participant['team'])))
                                    <span class="text-muted">•</span>
                                @endif
                                @if (!empty($participant['department']) || !empty($participant['team']))
                                    <span class="d-inline-flex align-items-center gap-1">
                                        <i class="ti ti-building text-secondary"></i>
                                        <span>{{ $participant['team'] ? $participant['team'] . ' — ' : '' }}{{ $participant['department'] ?: '-' }}</span>
                                    </span>
                                @endif
                                @if (!empty($participant['company']))
                                    <span class="text-muted">•</span>
                                    <span class="d-inline-flex align-items-center gap-1">
                                        <i class="ti ti-building-community text-secondary"></i>
                                        <span>{{ $participant['company'] }}</span>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PARTICIPANT KPI SUMMARY CARDS -->
            <div class="row g-3 mb-4">
                <!-- Total Training -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm p-3 rounded-3 bg-white stat-kpi-card h-100">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3"
                                style="width: 44px; height: 44px; background-color: #0f4c81; color: #ffffff;">
                                <i class="ti ti-certificate fs-2"></i>
                            </div>
                            <div>
                                <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Total
                                    Training</div>
                                <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;" id="kpi-total-training">
                                    {{ number_format($summary['total_training']) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Finished -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm p-3 rounded-3 bg-white stat-kpi-card h-100">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3"
                                style="width: 44px; height: 44px; background-color: #16a34a; color: #ffffff;">
                                <i class="ti ti-circle-check fs-2"></i>
                            </div>
                            <div>
                                <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Finished
                                </div>
                                <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;" id="kpi-finished">
                                    {{ number_format($summary['finished']) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ongoing -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm p-3 rounded-3 bg-white stat-kpi-card h-100">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3"
                                style="width: 44px; height: 44px; background-color: #f59e0b; color: #ffffff;">
                                <i class="ti ti-clock-hour-4 fs-2"></i>
                            </div>
                            <div>
                                <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Ongoing
                                </div>
                                <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;" id="kpi-ongoing">
                                    {{ number_format($summary['ongoing']) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Learning Hours -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm p-3 rounded-3 bg-white stat-kpi-card h-100">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3"
                                style="width: 44px; height: 44px; background-color: #0284c7; color: #ffffff;">
                                <i class="ti ti-hourglass-empty fs-2"></i>
                            </div>
                            <div>
                                <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Learning
                                    Hours</div>
                                <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;" id="kpi-hours">
                                    {{ number_format($summary['learning_hours'], 1) }}h</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- QUICK FILTER CARD -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-3">
                    <div class="text-secondary small fw-semibold mb-2"
                        style="color: #6c757d !important; font-size: 0.85rem;">
                        Quick Filter
                    </div>
                    <form id="participant-filter-form" class="w-100" onsubmit="return false;">
                        <div class="d-flex align-items-center gap-2 flex-wrap w-100">
                            <!-- Course Name Search -->
                            <div class="position-relative flex-grow-1" style="min-width: 200px;">
                                <input type="text" id="participant-course-input" class="form-control rounded-3 w-100"
                                    placeholder="Search course title..." autocomplete="off">
                            </div>

                            <!-- Type Dropdown -->
                            <div class="flex-shrink-0" style="min-width: 125px;">
                                <select class="form-select rounded-3" id="participant-type-select">
                                    <option value="all">Type: All</option>
                                    @foreach ($trainingTypes as $type)
                                        <option value="{{ $type }}">{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Status Dropdown -->
                            <div class="flex-shrink-0" style="min-width: 120px;">
                                <select class="form-select rounded-3" id="participant-status-select">
                                    <option value="all">All Status</option>
                                    <option value="finished">Finished</option>
                                    <option value="ongoing">Ongoing</option>
                                    <option value="not_started">Not Started</option>
                                </select>
                            </div>

                            <!-- Year Dropdown -->
                            <div class="flex-shrink-0" style="min-width: 105px;">
                                <select class="form-select rounded-3" id="participant-year-select">
                                    <option value="all">All Year</option>
                                    @foreach ($years as $year)
                                        <option value="{{ $year }}">{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- 1. Apply Filter Button -->
                            <button type="button" id="btn-apply-participant-filter"
                                class="btn btn-primary btn-icon px-3 flex-shrink-0"
                                style="background-color: #003b6d; border-color: #003b6d;" data-bs-toggle="tooltip"
                                data-bs-title="Apply Filter" title="Apply Filter">
                                <i class="ti ti-search fs-2"></i>
                            </button>

                            <!-- 2. More Filters Button with Counter Badge -->
                            <button type="button" id="btn-more-participant-filters"
                                class="btn btn-outline-primary btn-icon px-3 position-relative btn-more-filters flex-shrink-0"
                                data-bs-toggle="modal" data-bs-target="#moreParticipantFiltersModal"
                                title="More Filters">
                                <i class="ti ti-adjustments-horizontal fs-2"></i>
                                <span id="activeParticipantFilterBadge"
                                    class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle d-none">0</span>
                            </button>

                            <!-- 3. Reset Button -->
                            <button type="button" id="btn-reset-participant-filter"
                                class="btn btn-outline-secondary btn-icon px-3 btn-quick-reset flex-shrink-0"
                                data-bs-toggle="tooltip" data-bs-title="Reset Filters" title="Reset Filters">
                                <i class="ti ti-refresh fs-2"></i>
                            </button>

                            <!-- 4. Export Dropdown (Excel, CSV, PDF Landscape) -->
                            @can('learning_management')
                            <div class="dropdown flex-shrink-0">
                                <button class="btn btn-success dropdown-toggle rounded-3 px-3 btn-download" type="button"
                                    id="participantExportDropdown" data-bs-toggle="dropdown" aria-expanded="false"
                                    title="Export Records">
                                    <i class="ti ti-download me-1"></i> Download
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3"
                                    aria-labelledby="participantExportDropdown">
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center gap-2 py-2"
                                            href="javascript:void(0);" id="btn-export-participant-excel">
                                            <i class="ti ti-file-spreadsheet text-success fs-2"></i>
                                            <div>
                                                <div class="fw-semibold">Excel (.xlsx)</div>
                                                <div class="small text-muted" style="font-size: 0.72rem;">Canonical
                                                    44-column spreadsheet</div>
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center gap-2 py-2"
                                            href="javascript:void(0);" id="btn-export-participant-csv">
                                            <i class="ti ti-file-text text-primary fs-2"></i>
                                            <div>
                                                <div class="fw-semibold">CSV (.csv)</div>
                                                <div class="small text-muted" style="font-size: 0.72rem;">Standard
                                                    comma-separated format</div>
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider my-1">
                                    </li>
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center gap-2 py-2"
                                            href="javascript:void(0);" id="btn-export-participant-pdf">
                                            <i class="ti ti-file-type-pdf text-danger fs-2"></i>
                                            <div>
                                                <div class="fw-semibold">PDF Landscape (.pdf)</div>
                                                <div class="small text-muted" style="font-size: 0.72rem;">Structured A4
                                                    landscape report</div>
                                            </div>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            @endcan
                        </div>
                    </form>
                </div>
            </div>

            <!-- FULL RAW CANONICAL RECORD TABLE CARD -->
            <div class="card border-0 shadow-sm">
                <div
                    class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h3 class="card-title fw-bold mb-0 text-dark">Training Records</h3>
                        <div class="text-muted small">Canonical Non KP OWL record view for this employee (44 HRDC canonical
                            fields).</div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive offline-table-responsive">
                        <table id="participant-training-table" class="table table-vcenter table-striped card-table">
                            <thead>
                                <tr>
                                    <th class="text-center" style="min-width: 45px; width: 45px;">NO</th>
                                    <th class="text-center" style="min-width: 90px;">EMPLOYEE ID</th>
                                    <th style="min-width: 180px;">EMPLOYEE NAME</th>
                                    <th style="min-width: 140px;">TEAM</th>
                                    <th style="min-width: 160px;">DEPARTMENT</th>
                                    <th style="min-width: 160px;">COMPANY</th>
                                    <th class="text-center" style="min-width: 220px; text-align: center;">COURSE TITLE</th>
                                    <th class="text-center" style="min-width: 100px;">TYPE</th>
                                    <th style="min-width: 130px;">CATEGORY</th>
                                    <th style="min-width: 150px;">MAIN CLASSIFICATION</th>
                                    <th style="min-width: 150px;">SUB CLASSIFICATION</th>
                                    <th class="text-center" style="min-width: 120px;">DELIVERY METHOD</th>
                                    <th style="min-width: 140px;">SUB DELIVERY METHOD</th>
                                    <th class="text-center" style="min-width: 110px;">PROVIDER</th>
                                    <th style="min-width: 160px;">PROVIDER NAME</th>
                                    <th style="min-width: 140px;">VENUE</th>
                                    <th class="text-center" style="min-width: 100px;">COURSE DURATION</th>
                                    <th class="text-center" style="min-width: 130px;">TOTAL RECOGNIZED DURATION</th>
                                    <th class="text-center" style="min-width: 110px;">LEARNING HOURS</th>
                                    <th class="text-center" style="min-width: 110px;">% PROGRESS</th>
                                    <th class="text-center" style="min-width: 110px;">PROGRESS STATUS</th>
                                    <th class="text-center" style="min-width: 140px;">DATE (START - END)</th>
                                    <th class="text-center" style="min-width: 120px;">TIME (START - END)</th>
                                    <th class="text-center" style="min-width: 100px;">RECORD MONTH</th>
                                    <th class="text-center" style="min-width: 110px;">REQUIREMENT</th>
                                    <th class="text-center" style="min-width: 105px;">ATTENDANCE</th>
                                    <th class="text-center" style="min-width: 130px;">BOND AMOUNT</th>
                                    <th class="text-center" style="min-width: 120px;">SCORE (L1) - COURSE</th>
                                    <th class="text-center" style="min-width: 105px;">SCORE (L2)</th>
                                    <th class="text-center" style="min-width: 105px;">SCORE (L3)</th>
                                    <th style="min-width: 130px;">PERSON IN CHARGE (PIC)</th>
                                    <th class="text-center" style="min-width: 120px;">TOTAL ENGAGEMENT TIME (LH)</th>
                                    <th class="text-center" style="min-width: 60px; width: 60px;">ACTION</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="card-footer d-flex flex-wrap align-items-center justify-content-between p-3 border-top">
                        <div class="d-flex align-items-center gap-2 mb-2 mb-md-0">
                            <select class="form-select form-select-sm w-auto rounded-3"
                                id="participant-table-entries-select">
                                <option value="10">10 Entries</option>
                                <option value="25" selected>25 Entries</option>
                                <option value="50">50 Entries</option>
                                <option value="100">100 Entries</option>
                            </select>
                            <div id="participant-table-info-container"></div>
                        </div>
                        <div id="participant-table-pagination-container"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MORE FILTERS MODAL -->
    <div class="modal fade" id="moreParticipantFiltersModal" tabindex="-1"
        aria-labelledby="moreParticipantFiltersModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content rounded-4 border-0 shadow">
                <form id="more-participant-filters-modal-form" onsubmit="return false;">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2"
                            id="moreParticipantFiltersModalLabel">
                            <i class="ti ti-adjustments-horizontal text-primary"></i> More Filters
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body py-3">
                        <!-- SECTION A: TRAINING INFORMATION -->
                        <div class="text-uppercase fw-bold text-muted small mb-2 border-bottom pb-1"
                            style="font-size: 0.75rem; letter-spacing: 0.5px;">
                            A. Training Information
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark small" for="modal-classification">
                                    <i class="ti ti-folder me-1"></i> Main Classification
                                </label>
                                <select class="form-select rounded-3" id="modal-classification"
                                    name="filter_classification">
                                    <option value="all">All Classifications</option>
                                    @foreach ($classifications as $c)
                                        <option value="{{ $c }}">{{ $c }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark small" for="modal-sub-classification">
                                    <i class="ti ti-folders me-1"></i> Sub Classification
                                </label>
                                <input type="text" class="form-control rounded-3" id="modal-sub-classification"
                                    name="sub_classification" placeholder="Search sub classification...">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark small" for="modal-category">
                                    <i class="ti ti-category me-1"></i> Category
                                </label>
                                <select class="form-select rounded-3" id="modal-category" name="category">
                                    <option value="all">All Categories</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat }}">{{ $cat }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark small" for="modal-level">
                                    <i class="ti ti-stairs me-1"></i> Level
                                </label>
                                <select class="form-select rounded-3" id="modal-level" name="level">
                                    <option value="all">All Levels</option>
                                    @foreach ($levels as $lvl)
                                        <option value="{{ $lvl }}">{{ $lvl }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark small" for="modal-batch">
                                    <i class="ti ti-stack-2 me-1"></i> Batch
                                </label>
                                <input type="text" class="form-control rounded-3" id="modal-batch" name="batch"
                                    placeholder="e.g. 1">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark small" for="modal-delivery-method">
                                    <i class="ti ti-presentation me-1"></i> Delivery Method
                                </label>
                                <select class="form-select rounded-3" id="modal-delivery-method" name="delivery_method">
                                    <option value="all">All Delivery Methods</option>
                                    @foreach ($deliveryMethods as $deliv)
                                        <option value="{{ $deliv }}">{{ $deliv }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark small" for="modal-sub-delivery-method">
                                    <i class="ti ti-presentation-analytics me-1"></i> Sub Delivery Method
                                </label>
                                <input type="text" class="form-control rounded-3" id="modal-sub-delivery-method"
                                    name="sub_delivery_method" placeholder="Search sub delivery method...">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark small" for="modal-provider">
                                    <i class="ti ti-building me-1"></i> Provider
                                </label>
                                <select class="form-select rounded-3" id="modal-provider" name="provider">
                                    <option value="all">All Providers</option>
                                    @foreach ($providers as $prov)
                                        <option value="{{ $prov }}">{{ $prov }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark small" for="modal-sub-provider">
                                    <i class="ti ti-building-arch me-1"></i> Sub Provider
                                </label>
                                <input type="text" class="form-control rounded-3" id="modal-sub-provider"
                                    name="sub_provider" placeholder="Search sub provider...">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark small" for="modal-provider-name">
                                    <i class="ti ti-id-badge-2 me-1"></i> Provider Name
                                </label>
                                <input type="text" class="form-control rounded-3" id="modal-provider-name"
                                    name="provider_name" placeholder="Search provider name...">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark small" for="modal-corporate-kpi">
                                    <i class="ti ti-target me-1"></i> Counted Corporate KPI
                                </label>
                                <select class="form-select rounded-3" id="modal-corporate-kpi" name="corporate_kpi">
                                    <option value="all">All</option>
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark small" for="modal-sub-corporate-kpi">
                                    <i class="ti ti-target-arrow me-1"></i> Sub Corporate KPI
                                </label>
                                <input type="text" class="form-control rounded-3" id="modal-sub-corporate-kpi"
                                    name="sub_corporate_kpi" placeholder="Search sub corporate KPI...">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark small" for="modal-venue">
                                    <i class="ti ti-map-pin me-1"></i> Venue
                                </label>
                                <input type="text" class="form-control rounded-3" id="modal-venue" name="venue"
                                    placeholder="Search venue...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark small" for="modal-instructor">
                                    <i class="ti ti-user-star me-1"></i> Instructor
                                </label>
                                <input type="text" class="form-control rounded-3" id="modal-instructor"
                                    name="instructor" placeholder="Search instructor...">
                            </div>
                        </div>

                        <!-- SECTION B: DATE & DURATION -->
                        <div class="text-uppercase fw-bold text-muted small mb-2 border-bottom pb-1 pt-2"
                            style="font-size: 0.75rem; letter-spacing: 0.5px;">
                            B. Date & Duration
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark small"><i class="ti ti-calendar me-1"></i>
                                    Training Date Range</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="text" id="modal-from-date" name="from_date"
                                            onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'"
                                            class="form-control rounded-3" placeholder="From Date">
                                    </div>
                                    <div class="col-6">
                                        <input type="text" id="modal-to-date" name="to_date"
                                            onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'"
                                            class="form-control rounded-3" placeholder="To Date">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark small"><i
                                        class="ti ti-hourglass-empty me-1"></i> Learning Hours Range</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="number" step="0.1" id="modal-hours-min" name="hours_min"
                                            class="form-control rounded-3" placeholder="Min Hours">
                                    </div>
                                    <div class="col-6">
                                        <input type="number" step="0.1" id="modal-hours-max" name="hours_max"
                                            class="form-control rounded-3" placeholder="Max Hours">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark small d-block"><i class="ti ti-clock me-1"></i>
                                Duration</label>
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_duration" id="durAll"
                                        value="all" checked>
                                    <label class="form-check-label text-dark" for="durAll">All</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_duration" id="dur10"
                                        value="lt10">
                                    <label class="form-check-label text-dark" for="dur10">&lt; 10 Minutes</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_duration" id="dur30"
                                        value="10-30">
                                    <label class="form-check-label text-dark" for="dur30">10 - 30 Minutes</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_duration" id="dur60"
                                        value="30-60">
                                    <label class="form-check-label text-dark" for="dur60">30 - 60 Minutes</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_duration" id="durAbove60"
                                        value="gt60">
                                    <label class="form-check-label text-dark" for="durAbove60">&gt; 1 Hour</label>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION C: TRAINING RESULT -->
                        <div class="text-uppercase fw-bold text-muted small mb-2 border-bottom pb-1 pt-2"
                            style="font-size: 0.75rem; letter-spacing: 0.5px;">
                            C. Training Result
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark small d-block"><i class="ti ti-chart-bar me-1"></i>
                                Progress Range</label>
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_progress_range"
                                        id="prAll" value="all" checked>
                                    <label class="form-check-label text-dark" for="prAll">All</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_progress_range"
                                        id="pr0" value="0">
                                    <label class="form-check-label text-dark" for="pr0">0%</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_progress_range"
                                        id="pr25" value="1-25">
                                    <label class="form-check-label text-dark" for="pr25">1 - 25%</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_progress_range"
                                        id="pr50" value="26-50">
                                    <label class="form-check-label text-dark" for="pr50">26 - 50%</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_progress_range"
                                        id="pr75" value="51-75">
                                    <label class="form-check-label text-dark" for="pr75">51 - 75%</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_progress_range"
                                        id="pr99" value="76-99">
                                    <label class="form-check-label text-dark" for="pr99">76 - 99%</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_progress_range"
                                        id="pr100" value="100">
                                    <label class="form-check-label text-dark" for="pr100">100%</label>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark small"><i class="ti ti-award me-1"></i>
                                    Assessment Score Range</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="number" step="0.1" id="modal-score-min" name="score_min"
                                            class="form-control rounded-3" placeholder="Min Score">
                                    </div>
                                    <div class="col-6">
                                        <input type="number" step="0.1" id="modal-score-max" name="score_max"
                                            class="form-control rounded-3" placeholder="Max Score">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark small" for="modal-certificate">
                                    <i class="ti ti-file-certificate me-1"></i> Certificate
                                </label>
                                <select class="form-select rounded-3" id="modal-certificate" name="certificate">
                                    <option value="all">All</option>
                                    <option value="yes">Available / Yes</option>
                                    <option value="no">None / No</option>
                                </select>
                            </div>
                        </div>

                        <!-- SECTION D: ATTENDANCE -->
                        <div class="text-uppercase fw-bold text-muted small mb-2 border-bottom pb-1 pt-2"
                            style="font-size: 0.75rem; letter-spacing: 0.5px;">
                            D. Attendance
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark small d-block"><i
                                        class="ti ti-user-check me-1"></i> Attendance</label>
                                <div class="d-flex flex-wrap gap-3 align-items-center mt-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="modal_attendance"
                                            id="attAll" value="all" checked>
                                        <label class="form-check-label text-dark" for="attAll">All</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="modal_attendance"
                                            id="attPresent" value="present">
                                        <label class="form-check-label text-dark" for="attPresent">Present</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="modal_attendance"
                                            id="attAbsent" value="absent">
                                        <label class="form-check-label text-dark" for="attAbsent">Absent</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark small" for="modal-attendance-reason">
                                    <i class="ti ti-notes me-1"></i> Attendance Reason
                                </label>
                                <input type="text" class="form-control rounded-3" id="modal-attendance-reason"
                                    name="attendance_reason" placeholder="Search attendance reason...">
                            </div>
                        </div>

                        <!-- SECTION E: OBLIGATION / SERVICE BOND -->
                        <div class="text-uppercase fw-bold text-muted small mb-2 border-bottom pb-1 pt-2"
                            style="font-size: 0.75rem; letter-spacing: 0.5px;">
                            E. Obligation / Service Bond
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark small" for="modal-bond-status">
                                    <i class="ti ti-shield me-1"></i> Bond Status
                                </label>
                                <select class="form-select rounded-3" id="modal-bond-status" name="bond_status">
                                    <option value="all">All</option>
                                    <option value="yes">Active / Yes</option>
                                    <option value="no">No</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark small"><i
                                        class="ti ti-calendar-event me-1"></i> Bond End Date</label>
                                <div class="row g-1">
                                    <div class="col-6">
                                        <input type="text" id="modal-bond-end-from" name="bond_end_from"
                                            onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'"
                                            class="form-control rounded-3" placeholder="From">
                                    </div>
                                    <div class="col-6">
                                        <input type="text" id="modal-bond-end-to" name="bond_end_to"
                                            onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'"
                                            class="form-control rounded-3" placeholder="To">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark small"><i class="ti ti-cash me-1"></i> Bond
                                    Amount (IDR)</label>
                                <div class="row g-1">
                                    <div class="col-6">
                                        <input type="number" id="modal-bond-amount-min" name="bond_amount_min"
                                            class="form-control rounded-3" placeholder="Min Rp">
                                    </div>
                                    <div class="col-6">
                                        <input type="number" id="modal-bond-amount-max" name="bond_amount_max"
                                            class="form-control rounded-3" placeholder="Max Rp">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION F: EVALUATION & GOVERNANCE -->
                        <div class="text-uppercase fw-bold text-muted small mb-2 border-bottom pb-1 pt-2"
                            style="font-size: 0.75rem; letter-spacing: 0.5px;">
                            F. Evaluation & Governance
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark small" for="modal-l1-program">
                                    <i class="ti ti-stars me-1"></i> L1 Program (Reaction)
                                </label>
                                <input type="text" class="form-control rounded-3" id="modal-l1-program"
                                    name="l1_program" placeholder="e.g. Excellent / Satisfactory">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark small" for="modal-pic">
                                    <i class="ti ti-user-check me-1"></i> PIC (Person in Charge)
                                </label>
                                <input type="text" class="form-control rounded-3" id="modal-pic" name="pic"
                                    placeholder="Search PIC...">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark small"><i class="ti ti-math me-1"></i> L2 Post
                                    Test (Numerical)</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="number" step="0.1" min="0" max="100"
                                            id="modal-l2-num-min" name="l2_numerical_min" class="form-control rounded-3"
                                            placeholder="Min">
                                    </div>
                                    <div class="col-6">
                                        <input type="number" step="0.1" min="0" max="100"
                                            id="modal-l2-num-max" name="l2_numerical_max" class="form-control rounded-3"
                                            placeholder="Max">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark small" for="modal-l2-verbal">
                                    <i class="ti ti-file-description me-1"></i> L2 Verbal
                                </label>
                                <input type="text" class="form-control rounded-3" id="modal-l2-verbal"
                                    name="l2_verbal" placeholder="e.g. Passed">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark small"><i class="ti ti-math me-1"></i> L3
                                    Project (Numerical)</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="number" step="0.1" min="0" max="100"
                                            id="modal-l3-num-min" name="l3_numerical_min" class="form-control rounded-3"
                                            placeholder="Min">
                                    </div>
                                    <div class="col-6">
                                        <input type="number" step="0.1" min="0" max="100"
                                            id="modal-l3-num-max" name="l3_numerical_max" class="form-control rounded-3"
                                            placeholder="Max">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark small" for="modal-l3-verbal">
                                    <i class="ti ti-file-description me-1"></i> L3 Verbal
                                </label>
                                <input type="text" class="form-control rounded-3" id="modal-l3-verbal"
                                    name="l3_verbal" placeholder="e.g. Implemented successfully">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-secondary rounded-3 px-3"
                            id="btn-reset-modal-participant-filters">
                            <i class="ti ti-refresh me-1"></i> Reset
                        </button>
                        <button type="button" class="btn btn-primary rounded-3 px-4"
                            id="btn-apply-modal-participant-filters"
                            style="background-color: #003b6d; border-color: #003b6d;">
                            <i class="ti ti-check me-1"></i> Apply Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- HIDDEN FILTER INPUTS (Sync between modal and table state) -->
    <input type="hidden" id="filter-classification" value="all">
    <input type="hidden" id="filter-sub-classification" value="">
    <input type="hidden" id="filter-category" value="all">
    <input type="hidden" id="filter-level" value="all">
    <input type="hidden" id="filter-batch" value="">
    <input type="hidden" id="filter-delivery-method" value="all">
    <input type="hidden" id="filter-sub-delivery-method" value="">
    <input type="hidden" id="filter-provider" value="all">
    <input type="hidden" id="filter-sub-provider" value="">
    <input type="hidden" id="filter-provider-name" value="">
    <input type="hidden" id="filter-corporate-kpi" value="all">
    <input type="hidden" id="filter-sub-corporate-kpi" value="">
    <input type="hidden" id="filter-venue" value="">
    <input type="hidden" id="filter-instructor" value="">
    <input type="hidden" id="filter-from-date" value="">
    <input type="hidden" id="filter-to-date" value="">
    <input type="hidden" id="filter-hours-min" value="">
    <input type="hidden" id="filter-hours-max" value="">
    <input type="hidden" id="filter-duration" value="all">
    <input type="hidden" id="filter-progress-range" value="all">
    <input type="hidden" id="filter-score-min" value="">
    <input type="hidden" id="filter-score-max" value="">
    <input type="hidden" id="filter-certificate" value="all">
    <input type="hidden" id="filter-attendance" value="all">
    <input type="hidden" id="filter-attendance-reason" value="">
    <input type="hidden" id="filter-bond-status" value="all">
    <input type="hidden" id="filter-bond-end-from" value="">
    <input type="hidden" id="filter-bond-end-to" value="">
    <input type="hidden" id="filter-bond-amount-min" value="">
    <input type="hidden" id="filter-bond-amount-max" value="">
    <input type="hidden" id="filter-l1-program" value="">
    <input type="hidden" id="filter-l2-numerical-min" value="">
    <input type="hidden" id="filter-l2-numerical-max" value="">
    <input type="hidden" id="filter-l2-verbal" value="">
    <input type="hidden" id="filter-l3-numerical-min" value="">
    <input type="hidden" id="filter-l3-numerical-max" value="">
    <input type="hidden" id="filter-l3-verbal" value="">
    <input type="hidden" id="filter-pic" value="">

    <!-- DETAIL MODAL COMPONENT -->
    @include('main.record.components.offline-detail-modal')

    <!-- DELETE CONFIRMATION MODAL -->
    @include('main.record.components.offline-delete-modal')
    @include('main.record.components.more-information-modal')
@endsection

@section('style')
    <link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">
    <style>
        .manage-record-page {
            background: #fff;
        }

        [data-bs-theme="dark"] .manage-record-page,
        body.dark-mode .manage-record-page,
        body.theme-dark .manage-record-page {
            background: var(--tblr-body-bg, #111827);
        }

        .btn-back {
            color: #0c5b9e !important;
            font-weight: 500;
            transition: transform 0.2s ease, opacity 0.2s ease;
        }

        .btn-back:hover {
            opacity: 0.85;
            transform: translateX(-3px);
        }

        [data-bs-theme="dark"] .btn-back,
        body.dark-mode .btn-back,
        body.theme-dark .btn-back {
            color: #60a5fa !important;
        }

        .record-section-header {
            border-radius: 14px !important;
            border: 1px solid #e2e8f0 !important;
        }

        [data-bs-theme="dark"] .record-section-header,
        body.dark-mode .record-section-header,
        body.theme-dark .record-section-header {
            background-color: #1e293b !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }

        [data-bs-theme="dark"] .record-section-header .text-dark,
        body.dark-mode .record-section-header .text-dark,
        body.theme-dark .record-section-header .text-dark {
            color: #f8fafc !important;
        }

        [data-bs-theme="dark"] .record-section-header .bg-light,
        body.dark-mode .record-section-header .bg-light,
        body.theme-dark .record-section-header .bg-light {
            background-color: #111827 !important;
            border-color: #374151 !important;
        }

        .stat-kpi-card {
            border: 1px solid #e2e8f0 !important;
            border-radius: 12px;
        }

        [data-bs-theme="dark"] .stat-kpi-card,
        body.dark-mode .stat-kpi-card,
        body.theme-dark .stat-kpi-card {
            background-color: #1e293b !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }

        [data-bs-theme="dark"] .stat-kpi-card .text-dark,
        body.dark-mode .stat-kpi-card .text-dark,
        body.theme-dark .stat-kpi-card .text-dark {
            color: #f8fafc !important;
        }

        /* Table Responsive Horizontal Scroll */
        .offline-table-responsive {
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch;
            width: 100%;
            max-width: 100%;
            border-radius: 0;
        }

        .offline-table-responsive::-webkit-scrollbar {
            height: 8px;
        }

        .offline-table-responsive::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        .offline-table-responsive::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .offline-table-responsive::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        [data-bs-theme="dark"] .offline-table-responsive::-webkit-scrollbar-track,
        body.dark-mode .offline-table-responsive::-webkit-scrollbar-track,
        body.theme-dark .offline-table-responsive::-webkit-scrollbar-track {
            background: #1e293b;
        }

        [data-bs-theme="dark"] .offline-table-responsive::-webkit-scrollbar-thumb,
        body.dark-mode .offline-table-responsive::-webkit-scrollbar-thumb,
        body.theme-dark .offline-table-responsive::-webkit-scrollbar-thumb {
            background: #475569;
        }

        #participant-training-table {
            font-size: 13px;
            width: max-content !important;
            min-width: 100% !important;
            margin-bottom: 0;
        }

        #participant-training-table thead th {
            background: #f8fafc;
            color: #475569;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .04em;
            white-space: nowrap !important;
            vertical-align: middle;
            padding: 11px 14px;
            border-bottom: 1px solid #e2e8f0;
        }

        #participant-training-table tbody td {
            vertical-align: middle;
            padding: 10px 14px;
            white-space: nowrap !important;
        }

        [data-bs-theme="dark"] #participant-training-table thead th,
        body.dark-mode #participant-training-table thead th,
        body.theme-dark #participant-training-table thead th {
            background: #1e293b !important;
            color: #94a3b8 !important;
            border-bottom-color: #334155 !important;
        }

        [data-bs-theme="dark"] #participant-training-table tbody td,
        body.dark-mode #participant-training-table tbody td,
        body.theme-dark #participant-training-table tbody td {
            border-bottom-color: #334155 !important;
            color: #e2e8f0 !important;
        }

        #moreParticipantFiltersModal {
            z-index: 1060 !important;
        }

        [data-bs-theme="dark"] #offlineTrainingDetailModal .modal-content,
        [data-bs-theme="dark"] #moreParticipantFiltersModal .modal-content,
        body.dark-mode #offlineTrainingDetailModal .modal-content,
        body.dark-mode #moreParticipantFiltersModal .modal-content,
        body.theme-dark #offlineTrainingDetailModal .modal-content,
        body.theme-dark #moreParticipantFiltersModal .modal-content {
            background-color: var(--tblr-bg-surface, #1f2937) !important;
            color: #f8fafc !important;
        }

        [data-bs-theme="dark"] #offlineTrainingDetailModal .bg-light,
        [data-bs-theme="dark"] #moreParticipantFiltersModal .bg-light,
        body.dark-mode #offlineTrainingDetailModal .bg-light,
        body.dark-mode #moreParticipantFiltersModal .bg-light,
        body.theme-dark #offlineTrainingDetailModal .bg-light,
        body.theme-dark #moreParticipantFiltersModal .bg-light {
            background-color: #111827 !important;
        }

        [data-bs-theme="dark"] #offlineTrainingDetailModal .text-dark,
        [data-bs-theme="dark"] #moreParticipantFiltersModal .text-dark,
        body.dark-mode #offlineTrainingDetailModal .text-dark,
        body.dark-mode #moreParticipantFiltersModal .text-dark,
        body.theme-dark #offlineTrainingDetailModal .text-dark,
        body.theme-dark #moreParticipantFiltersModal .text-dark {
            color: #f8fafc !important;
        }

        /* Standardized Quick Filter Toolbar Controls */
        /* Action buttons in table */
        .table .btn-view-offline-detail,
        .table .btn-icon {
            width: 30px;
            height: 30px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
        }

        /* Quick Filter Toolbar Buttons Styling (Matching Manage Learning) */
        .btn-more-filters,
        #btn-more-filters,
        #btn-more-participant-filters {
            border-color: #0c5b9e !important;
            color: #0c5b9e !important;
            background-color: transparent !important;
            transition: all 0.2s ease;
        }

        .btn-more-filters:hover,
        .btn-more-filters:focus,
        .btn-more-filters:active,
        #btn-more-filters:hover,
        #btn-more-filters:focus,
        #btn-more-filters:active,
        #btn-more-participant-filters:hover,
        #btn-more-participant-filters:focus,
        #btn-more-participant-filters:active {
            background-color: #0c5b9e !important;
            color: #ffffff !important;
            border-color: #0c5b9e !important;
        }

        .btn-more-filters:hover i,
        .btn-more-filters:focus i,
        .btn-more-filters:active i,
        #btn-more-filters:hover i,
        #btn-more-filters:focus i,
        #btn-more-filters:active i,
        #btn-more-participant-filters:hover i,
        #btn-more-participant-filters:focus i,
        #btn-more-participant-filters:active i {
            color: #ffffff !important;
        }

        [data-bs-theme="dark"] .btn-more-filters,
        [data-bs-theme="dark"] #btn-more-filters,
        [data-bs-theme="dark"] #btn-more-participant-filters,
        body.dark-mode .btn-more-filters,
        body.dark-mode #btn-more-filters,
        body.dark-mode #btn-more-participant-filters,
        body.theme-dark .btn-more-filters,
        body.theme-dark #btn-more-filters,
        body.theme-dark #btn-more-participant-filters {
            background-color: transparent !important;
            border-color: #3b82f6 !important;
            color: #60a5fa !important;
        }

        [data-bs-theme="dark"] .btn-more-filters:hover,
        [data-bs-theme="dark"] #btn-more-filters:hover,
        [data-bs-theme="dark"] #btn-more-participant-filters:hover,
        body.dark-mode .btn-more-filters:hover,
        body.dark-mode #btn-more-filters:hover,
        body.dark-mode #btn-more-participant-filters:hover,
        body.theme-dark .btn-more-filters:hover,
        body.theme-dark #btn-more-filters:hover,
        body.theme-dark #btn-more-participant-filters:hover {
            background-color: #2563eb !important;
            color: #ffffff !important;
            border-color: #2563eb !important;
        }

        .btn-download {
            background-color: #2fb344 !important;
            border-color: #2fb344 !important;
            color: #ffffff !important;
            transition: all 0.2s ease;
        }

        .btn-download:hover,
        .btn-download:focus,
        .btn-download:active {
            background-color: #269639 !important;
            border-color: #269639 !important;
            color: #ffffff !important;
        }

        .btn-download:hover i,
        .btn-download:focus i,
        .btn-download:active i {
            color: #ffffff !important;
        }

        .btn-quick-reset {
            transition: all 0.2s ease;
        }

        .btn-quick-reset:hover {
            background-color: #0c5b9e !important;
            color: #ffffff !important;
        }

        [data-bs-theme="dark"] .btn-quick-reset,
        body.dark-mode .btn-quick-reset,
        body.theme-dark .btn-quick-reset {
            background-color: transparent !important;
            color: #94a3b8 !important;
        }

        [data-bs-theme="dark"] .btn-quick-reset:hover,
        body.dark-mode .btn-quick-reset:hover,
        body.theme-dark .btn-quick-reset:hover {
            background-color: #0c5b9e !important;
            color: #ffffff !important;
        }

        .hover-underline:hover,
        .course-link:hover {
            text-decoration: underline !important;
        }
    /* Participant Table Pagination Styling */
    #participant-table-pagination-container {
        display: flex;
        justify-content: flex-end;
        align-items: center;
    }
    #participant-table-pagination-container .pagination {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: 4px;
        width: auto;
        margin: 0;
    }
    #participant-table-pagination-container .page-item {
        flex: 0 0 auto;
    }
    #participant-table-pagination-container .page-link {
        min-width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 8px;
        font-size: 0.8125rem;
        border-radius: 4px;
        color: #495057;
        background-color: #fff;
        border: 1px solid #e2e8f0;
        text-decoration: none;
        transition: all 0.15s ease-in-out;
    }
    #participant-table-pagination-container .page-link:hover {
        background-color: #f1f5f9;
        border-color: #cbd5e1;
        color: #1e293b;
    }
    #participant-table-pagination-container .page-item.active .page-link {
        background-color: #0c5b9e;
        border-color: #0c5b9e;
        color: #fff;
    }
    #participant-table-pagination-container .page-item.disabled .page-link {
        color: #94a3b8;
        background-color: #f8fafc;
        border-color: #e2e8f0;
        cursor: not-allowed;
        opacity: 0.65;
    }

    [data-bs-theme="dark"] #participant-table-pagination-container .page-link,
    body.dark-mode #participant-table-pagination-container .page-link,
    body.theme-dark #participant-table-pagination-container .page-link {
        background-color: #1e293b;
        border-color: #334155;
        color: #cbd5e1;
    }
    [data-bs-theme="dark"] #participant-table-pagination-container .page-link:hover,
    body.dark-mode #participant-table-pagination-container .page-link:hover,
    body.theme-dark #participant-table-pagination-container .page-link:hover {
        background-color: #334155;
        color: #fff;
    }
    [data-bs-theme="dark"] #participant-table-pagination-container .page-item.active .page-link,
    body.dark-mode #participant-table-pagination-container .page-item.active .page-link,
    body.theme-dark #participant-table-pagination-container .page-item.active .page-link {
        background-color: #0c5b9e;
        border-color: #0c5b9e;
        color: #fff;
    }
    [data-bs-theme="dark"] #participant-table-pagination-container .page-item.disabled .page-link,
    body.dark-mode #participant-table-pagination-container .page-item.disabled .page-link,
    body.theme-dark #participant-table-pagination-container .page-item.disabled .page-link {
        background-color: #0f172a;
        border-color: #334155;
        color: #64748b;
    }
    </style>
@endsection

@section('script')
    <script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        $(function() {
            const datatableUrl = @json(route('manage.record.offline.participant.datatable', $participant['raw_identifier']));
            const exportExcelBaseUrl = @json($exportExcelUrl);
            const exportCsvBaseUrl = @json($exportCsvUrl);
            const exportPdfBaseUrl = @json($exportPdfUrl);
            const classificationParam = @json($classificationParam);
            const defaultEntries = 25;
            let participantTable = null;

            // Engagement time helper function
            function formatEngagementTime(val) {
                if (val === null || val === undefined || val === '' || val === '-') {
                    return '<span class="fw-semibold font-monospace">00:00:00</span>';
                }
                if (typeof val === 'string' && val.indexOf(':') !== -1) {
                    return val.indexOf('<') !== -1 ? val : `<span class="fw-semibold font-monospace">${val}</span>`;
                }
                let num = typeof val === 'string' ? parseFloat(val.replace(/[^0-9.-]/g, '')) : Number(val);
                if (!Number.isFinite(num) || num < 0) {
                    return '<span class="fw-semibold font-monospace">00:00:00</span>';
                }
                const totalSeconds = Math.round(num * 3600);
                const hours = Math.floor(totalSeconds / 3600);
                const minutes = Math.floor((totalSeconds % 3600) / 60);
                const seconds = totalSeconds % 60;
                const hms = [
                    String(hours).padStart(2, '0'),
                    String(minutes).padStart(2, '0'),
                    String(seconds).padStart(2, '0')
                ].join(':');
                return `<span class="fw-semibold font-monospace">${hms}</span>`;
            }

            // Function to calculate and update active filter badge counter
            function updateActiveFilterBadge() {
                let activeCount = 0;
                // Section A: Training Information
                if ($('#filter-classification').val() && $('#filter-classification').val() !== 'all') activeCount++;
                if ($('#filter-sub-classification').val()) activeCount++;
                if ($('#filter-category').val() && $('#filter-category').val() !== 'all') activeCount++;
                if ($('#filter-level').val() && $('#filter-level').val() !== 'all') activeCount++;
                if ($('#filter-batch').val()) activeCount++;
                if ($('#filter-delivery-method').val() && $('#filter-delivery-method').val() !== 'all')
                    activeCount++;
                if ($('#filter-sub-delivery-method').val()) activeCount++;
                if ($('#filter-provider').val() && $('#filter-provider').val() !== 'all') activeCount++;
                if ($('#filter-sub-provider').val()) activeCount++;
                if ($('#filter-provider-name').val()) activeCount++;
                if ($('#filter-corporate-kpi').val() && $('#filter-corporate-kpi').val() !== 'all') activeCount++;
                if ($('#filter-sub-corporate-kpi').val()) activeCount++;
                if ($('#filter-venue').val()) activeCount++;
                if ($('#filter-instructor').val()) activeCount++;

                // Section B: Date & Duration
                if ($('#filter-from-date').val()) activeCount++;
                if ($('#filter-to-date').val()) activeCount++;
                if ($('#filter-hours-min').val()) activeCount++;
                if ($('#filter-hours-max').val()) activeCount++;
                if ($('#filter-duration').val() && $('#filter-duration').val() !== 'all') activeCount++;

                // Section C: Training Result
                if ($('#filter-progress-range').val() && $('#filter-progress-range').val() !== 'all') activeCount++;
                if ($('#filter-score-min').val()) activeCount++;
                if ($('#filter-score-max').val()) activeCount++;
                if ($('#filter-certificate').val() && $('#filter-certificate').val() !== 'all') activeCount++;

                // Section D: Attendance
                if ($('#filter-attendance').val() && $('#filter-attendance').val() !== 'all') activeCount++;
                if ($('#filter-attendance-reason').val()) activeCount++;

                // Section E: Obligation / Service Bond
                if ($('#filter-bond-status').val() && $('#filter-bond-status').val() !== 'all') activeCount++;
                if ($('#filter-bond-end-from').val()) activeCount++;
                if ($('#filter-bond-end-to').val()) activeCount++;
                if ($('#filter-bond-amount-min').val()) activeCount++;
                if ($('#filter-bond-amount-max').val()) activeCount++;

                // Section F: Evaluation & Governance
                if ($('#filter-l1-program').val()) activeCount++;
                if ($('#filter-l2-numerical-min').val()) activeCount++;
                if ($('#filter-l2-numerical-max').val()) activeCount++;
                if ($('#filter-l2-verbal').val()) activeCount++;
                if ($('#filter-l3-numerical-min').val()) activeCount++;
                if ($('#filter-l3-numerical-max').val()) activeCount++;
                if ($('#filter-l3-verbal').val()) activeCount++;
                if ($('#filter-pic').val()) activeCount++;

                const $badge = $('#activeParticipantFilterBadge');
                if (activeCount > 0) {
                    $badge.text(activeCount).removeClass('d-none');
                } else {
                    $badge.addClass('d-none').text('0');
                }
            }

            // Initialize DataTable with full canonical columns
            participantTable = $('#participant-training-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: false,
                autoWidth: false,
                scrollX: true,
                pageLength: defaultEntries,
                lengthMenu: [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ],
                order: [
                    [9, 'asc']
                ], // Order by Course Name
                dom: 't',
                ajax: {
                    url: datatableUrl,
                    type: 'GET',
                    data: function(d) {
                        // Quick Filters
                        d.course_name = ($('#participant-course-input').val() || '').trim();
                        d.type = $('#participant-type-select').val();
                        d.status = $('#participant-status-select').val();
                        d.year = $('#participant-year-select').val();

                        // Section A: Training Information
                        d.filter_classification = $('#filter-classification').val();
                        d.sub_classification = $('#filter-sub-classification').val();
                        d.category = $('#filter-category').val();
                        d.level = $('#filter-level').val();
                        d.batch = $('#filter-batch').val();
                        d.delivery_method = $('#filter-delivery-method').val();
                        d.sub_delivery_method = $('#filter-sub-delivery-method').val();
                        d.provider = $('#filter-provider').val();
                        d.sub_provider = $('#filter-sub-provider').val();
                        d.provider_name = $('#filter-provider-name').val();
                        d.corporate_kpi = $('#filter-corporate-kpi').val();
                        d.sub_corporate_kpi = $('#filter-sub-corporate-kpi').val();
                        d.venue = $('#filter-venue').val();
                        d.instructor = $('#filter-instructor').val();

                        // Section B: Date & Duration
                        d.from_date = $('#filter-from-date').val();
                        d.to_date = $('#filter-to-date').val();
                        d.hours_min = $('#filter-hours-min').val();
                        d.hours_max = $('#filter-hours-max').val();
                        d.duration = $('#filter-duration').val();

                        // Section C: Training Result
                        d.progress_range = $('#filter-progress-range').val();
                        d.score_min = $('#filter-score-min').val();
                        d.score_max = $('#filter-score-max').val();
                        d.certificate = $('#filter-certificate').val();

                        // Section D: Attendance
                        d.attendance = $('#filter-attendance').val();
                        d.attendance_reason = $('#filter-attendance-reason').val();

                        // Section E: Obligation / Service Bond
                        d.bond_status = $('#filter-bond-status').val();
                        d.bond_end_from = $('#filter-bond-end-from').val();
                        d.bond_end_to = $('#filter-bond-end-to').val();
                        d.bond_amount_min = $('#filter-bond-amount-min').val();
                        d.bond_amount_max = $('#filter-bond-amount-max').val();

                        // Section F: Evaluation & Governance
                        d.l1_program = $('#filter-l1-program').val();
                        d.l2_numerical_min = $('#filter-l2-numerical-min').val();
                        d.l2_numerical_max = $('#filter-l2-numerical-max').val();
                        d.l2_verbal = $('#filter-l2-verbal').val();
                        d.l3_numerical_min = $('#filter-l3-numerical-min').val();
                        d.l3_numerical_max = $('#filter-l3-numerical-max').val();
                        d.l3_verbal = $('#filter-l3-verbal').val();
                        d.pic = $('#filter-pic').val();

                        // Context param for Course Name link preservation
                        d.classification = classificationParam;

                        d.entries = $('#participant-table-entries-select').val() || defaultEntries;
                        d.length = parseInt($('#participant-table-entries-select').val(), 10) ||
                            defaultEntries;
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'employee_id',
                        name: 'otu.employee_id',
                        className: 'text-center'
                    },
                    {
                        data: 'participant_name',
                        name: 'otu.name',
                        className: 'text-start'
                    },
                    {
                        data: 'team',
                        name: 'otu.team',
                        className: 'text-start'
                    },
                    {
                        data: 'department',
                        name: 'otu.department',
                        className: 'text-start'
                    },
                    {
                        data: 'company',
                        name: 'otu.company',
                        className: 'text-start'
                    },
                    {
                        data: 'course_name',
                        name: 'ot.course_name',
                        className: 'text-center align-middle',
                        render: function(data, type, row) {
                            if (typeof data === 'string' && data.indexOf('d-flex flex-column') !== -1) {
                                return data;
                            }
                            const courseTitle = data || row.course_name || '-';
                            let req = row.requirement || 'Non-Mandatory';
                            if (typeof req === 'string' && req.indexOf('<') !== -1) {
                                req = (req.toLowerCase().indexOf('mandatory') !== -1 && req.toLowerCase().indexOf('non-mandatory') === -1) ? 'Mandatory' : 'Non-Mandatory';
                            }
                            return `<div class="d-flex flex-column align-items-center justify-content-center text-center w-100">
                                <span class="fw-bold text-dark text-center fs-6 mb-1">${courseTitle}</span>
                                <small class="text-muted text-center fs-7">
                                    Requirement: <span class="fw-semibold">${req || 'Non-Mandatory'}</span>
                                </small>
                            </div>`;
                        }
                    },
                    {
                        data: 'type_badge',
                        name: 'ot.type',
                        className: 'text-center'
                    },
                    {
                        data: 'category',
                        name: 'ot.category',
                        className: 'text-start'
                    },
                    {
                        data: 'main_classification',
                        name: 'ot.main_classification',
                        className: 'text-start'
                    },
                    {
                        data: 'sub_classification',
                        name: 'ot.sub_classification',
                        className: 'text-start'
                    },
                    {
                        data: 'delivery_method',
                        name: 'ot.delivery_method',
                        className: 'text-center'
                    },
                    {
                        data: 'sub_delivery_method',
                        name: 'ot.sub_delivery_method',
                        className: 'text-start'
                    },
                    {
                        data: 'provider',
                        name: 'ot.provider',
                        className: 'text-center'
                    },
                    {
                        data: 'provider_name',
                        name: 'ot.provider_name',
                        className: 'text-start'
                    },
                    {
                        data: 'venue',
                        name: 'ot.venue',
                        className: 'text-start'
                    },
                    {
                        data: 'course_duration',
                        name: 'ot.duration_seconds',
                        className: 'text-center'
                    },
                    {
                        data: 'total_recognized_duration',
                        name: 'total_recognized_duration',
                        className: 'text-center',
                        orderable: false
                    },
                    {
                        data: 'course_learning_hours',
                        name: 'ot.learning_hours',
                        className: 'text-center'
                    },
                    {
                        data: 'progress_percent',
                        name: 'otu.progress',
                        className: 'text-center'
                    },
                    {
                        data: 'progress_status',
                        name: 'otu.progress',
                        className: 'text-center'
                    },
                    {
                        data: 'date_merged',
                        name: 'ot.start_date',
                        className: 'text-center',
                        orderable: false
                    },
                    {
                        data: 'time_merged',
                        name: 'ot.start_time',
                        className: 'text-center',
                        orderable: false
                    },
                    {
                        data: 'record_month',
                        name: 'ot.start_date',
                        className: 'text-center',
                        orderable: false
                    },
                    {
                        data: 'requirement',
                        name: 'ot.requirement',
                        className: 'text-center'
                    },
                    {
                        data: 'attendance_badge',
                        name: 'otu.attendance',
                        className: 'text-center'
                    },
                    {
                        data: 'bond_amount',
                        name: 'otu.bond_amount_idr',
                        className: 'text-center'
                    },
                    {
                        data: 'score_l1',
                        name: 'otu.assessment_score',
                        className: 'text-center'
                    },
                    {
                        data: 'score_l2',
                        name: 'otu.l2_numerical',
                        className: 'text-center'
                    },
                    {
                        data: 'score_l3',
                        name: 'otu.l3_numerical',
                        className: 'text-center'
                    },
                    {
                        data: 'pic',
                        name: 'otu.pic',
                        className: 'text-start'
                    },
                    {
                        data: 'total_engagement_time',
                        name: 'otu.learning_hours',
                        className: 'text-center',
                        render: function (data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return data;
                            }
                            return formatEngagementTime(data);
                        }
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                ],
                language: {
                    processing: '<div class="py-4 text-center"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 text-muted">Loading participant training records...</div></div>',
                    emptyTable: '<div class="py-5 text-center text-muted"><i class="ti ti-inbox fs-1 d-block mb-2 text-secondary"></i>No training records found matching the specified filters.</div>'
                },
                drawCallback: function(settings) {
                    const api = this.api();
                    const pageInfo = api.page.info();
                    const formatNum = (num) => (num || 0).toLocaleString();

                    if (pageInfo.recordsTotal > 0 && pageInfo.recordsDisplay > 0) {
                        const start = pageInfo.start + 1;
                        const end = pageInfo.end;
                        const total = pageInfo.recordsDisplay;
                        $('#participant-table-info-container').html(
                            `<span class="text-muted small">Showing ${formatNum(start)} to ${formatNum(end)} of ${formatNum(total)} entries</span>`
                        );
                    } else {
                        $('#participant-table-info-container').html(
                            '<span class="text-muted small">Showing 0 to 0 of 0 entries</span>');
                    }

                    // Render pagination
                    const $paginationContainer = $('#participant-table-pagination-container');
                    $paginationContainer.empty();

                    if (pageInfo.pages > 1) {
                        const currentPage = pageInfo.page;
                        const totalPages = pageInfo.pages;
                        let paginationHtml = '<ul class="pagination pagination-sm mb-0">';
                        const prevDisabled = currentPage === 0 ? 'disabled' : '';
                        paginationHtml += `
                        <li class="page-item ${prevDisabled}">
                            <a class="page-link" href="javascript:void(0);" ${currentPage > 0 ? `data-page="${currentPage - 1}"` : ''} aria-label="Previous">
                                <i class="ti ti-chevron-left"></i>
                            </a>
                        </li>
                    `;

                        const startPage = Math.max(0, currentPage - 2);
                        const endPage = Math.min(totalPages - 1, currentPage + 2);

                        if (startPage > 0) {
                            paginationHtml += `<li class="page-item"><a class="page-link" href="javascript:void(0);" data-page="0">1</a></li>`;
                            if (startPage > 1) {
                                paginationHtml += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
                            }
                        }

                        for (let p = startPage; p <= endPage; p++) {
                            const active = p === currentPage ? 'active' : '';
                            paginationHtml += `
                            <li class="page-item ${active}">
                                <a class="page-link" href="javascript:void(0);" data-page="${p}">${p + 1}</a>
                            </li>
                        `;
                        }

                        if (endPage < totalPages - 1) {
                            if (endPage < totalPages - 2) {
                                paginationHtml += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
                            }
                            paginationHtml += `<li class="page-item"><a class="page-link" href="javascript:void(0);" data-page="${totalPages - 1}">${totalPages}</a></li>`;
                        }

                        const nextDisabled = currentPage >= totalPages - 1 ? 'disabled' : '';
                        paginationHtml += `
                        <li class="page-item ${nextDisabled}">
                            <a class="page-link" href="javascript:void(0);" ${currentPage < totalPages - 1 ? `data-page="${currentPage + 1}"` : ''} aria-label="Next">
                                <i class="ti ti-chevron-right"></i>
                            </a>
                        </li>
                    `;
                        paginationHtml += '</ul>';
                        $paginationContainer.html(paginationHtml);
                    }
                }
            });

            // Pagination click handler
            $(document).on('click', '#participant-table-pagination-container .page-link', function(e) {
                e.preventDefault();
                if ($(this).parent().hasClass('disabled') || $(this).parent().hasClass('active')) {
                    return;
                }
                const page = $(this).data('page');
                if (page !== undefined && page >= 0) {
                    participantTable.page(page).draw('page');
                }
            });

            // Entries select handler
            $('#participant-table-entries-select').on('change', function() {
                const length = parseInt($(this).val(), 10) || defaultEntries;
                participantTable.page.len(length).draw();
            });

            // Apply quick filter button
            $('#btn-apply-participant-filter').on('click', function() {
                participantTable.ajax.reload();
            });

            $('#participant-course-input').on('keyup', function(e) {
                if (e.key === 'Enter') {
                    participantTable.ajax.reload();
                }
            });

            $('#participant-type-select, #participant-status-select, #participant-year-select').on('change',
                function() {
                    participantTable.ajax.reload();
                });

            // More Filters Modal: Reset Function
            function resetMoreParticipantFilters() {
                // Section A: Training Information
                $('#modal-classification').val('all');
                $('#modal-sub-classification').val('');
                $('#modal-category').val('all');
                $('#modal-level').val('all');
                $('#modal-batch').val('');
                $('#modal-delivery-method').val('all');
                $('#modal-sub-delivery-method').val('');
                $('#modal-provider').val('all');
                $('#modal-sub-provider').val('');
                $('#modal-provider-name').val('');
                $('#modal-corporate-kpi').val('all');
                $('#modal-sub-corporate-kpi').val('');
                $('#modal-venue').val('');
                $('#modal-instructor').val('');

                // Section B: Date & Duration
                $('#modal-from-date').val('');
                $('#modal-to-date').val('');
                $('#modal-hours-min').val('');
                $('#modal-hours-max').val('');
                $('input[name="modal_duration"][value="all"]').prop('checked', true);

                // Section C: Training Result
                $('input[name="modal_progress_range"][value="all"]').prop('checked', true);
                $('#modal-score-min').val('');
                $('#modal-score-max').val('');
                $('#modal-certificate').val('all');

                // Section D: Attendance
                $('input[name="modal_attendance"][value="all"]').prop('checked', true);
                $('#modal-attendance-reason').val('');

                // Section E: Obligation / Service Bond
                $('#modal-bond-status').val('all');
                $('#modal-bond-end-from').val('');
                $('#modal-bond-end-to').val('');
                $('#modal-bond-amount-min').val('');
                $('#modal-bond-amount-max').val('');

                // Section F: Evaluation & Governance
                $('#modal-l1-program').val('');
                $('#modal-pic').val('');
                $('#modal-l2-num-min').val('');
                $('#modal-l2-num-max').val('');
                $('#modal-l2-verbal').val('');
                $('#modal-l3-num-min').val('');
                $('#modal-l3-num-max').val('');
                $('#modal-l3-verbal').val('');

                // Reset all hidden inputs
                $('#filter-classification').val('all');
                $('#filter-sub-classification').val('');
                $('#filter-category').val('all');
                $('#filter-level').val('all');
                $('#filter-batch').val('');
                $('#filter-delivery-method').val('all');
                $('#filter-sub-delivery-method').val('');
                $('#filter-provider').val('all');
                $('#filter-sub-provider').val('');
                $('#filter-provider-name').val('');
                $('#filter-corporate-kpi').val('all');
                $('#filter-sub-corporate-kpi').val('');
                $('#filter-venue').val('');
                $('#filter-instructor').val('');
                $('#filter-from-date').val('');
                $('#filter-to-date').val('');
                $('#filter-hours-min').val('');
                $('#filter-hours-max').val('');
                $('#filter-duration').val('all');
                $('#filter-progress-range').val('all');
                $('#filter-score-min').val('');
                $('#filter-score-max').val('');
                $('#filter-certificate').val('all');
                $('#filter-attendance').val('all');
                $('#filter-attendance-reason').val('');
                $('#filter-bond-status').val('all');
                $('#filter-bond-end-from').val('');
                $('#filter-bond-end-to').val('');
                $('#filter-bond-amount-min').val('');
                $('#filter-bond-amount-max').val('');
                $('#filter-l1-program').val('');
                $('#filter-l2-numerical-min').val('');
                $('#filter-l2-numerical-max').val('');
                $('#filter-l2-verbal').val('');
                $('#filter-l3-numerical-min').val('');
                $('#filter-l3-numerical-max').val('');
                $('#filter-l3-verbal').val('');
                $('#filter-pic').val('');

                updateActiveFilterBadge();
            }

            // More Filters Modal: Reset Button
            $('#btn-reset-modal-participant-filters').on('click', function() {
                resetMoreParticipantFilters();
            });

            // More Filters Modal: Apply Button
            $('#btn-apply-modal-participant-filters').on('click', function() {
                // Section A: Training Information
                $('#filter-classification').val($('#modal-classification').val());
                $('#filter-sub-classification').val($('#modal-sub-classification').val());
                $('#filter-category').val($('#modal-category').val());
                $('#filter-level').val($('#modal-level').val());
                $('#filter-batch').val($('#modal-batch').val());
                $('#filter-delivery-method').val($('#modal-delivery-method').val());
                $('#filter-sub-delivery-method').val($('#modal-sub-delivery-method').val());
                $('#filter-provider').val($('#modal-provider').val());
                $('#filter-sub-provider').val($('#modal-sub-provider').val());
                $('#filter-provider-name').val($('#modal-provider-name').val());
                $('#filter-corporate-kpi').val($('#modal-corporate-kpi').val());
                $('#filter-sub-corporate-kpi').val($('#modal-sub-corporate-kpi').val());
                $('#filter-venue').val($('#modal-venue').val());
                $('#filter-instructor').val($('#modal-instructor').val());

                // Section B: Date & Duration
                $('#filter-from-date').val($('#modal-from-date').val());
                $('#filter-to-date').val($('#modal-to-date').val());
                $('#filter-hours-min').val($('#modal-hours-min').val());
                $('#filter-hours-max').val($('#modal-hours-max').val());
                $('#filter-duration').val($('input[name="modal_duration"]:checked').val() || 'all');

                // Section C: Training Result
                $('#filter-progress-range').val($('input[name="modal_progress_range"]:checked').val() ||
                    'all');
                $('#filter-score-min').val($('#modal-score-min').val());
                $('#filter-score-max').val($('#modal-score-max').val());
                $('#filter-certificate').val($('#modal-certificate').val());

                // Section D: Attendance
                $('#filter-attendance').val($('input[name="modal_attendance"]:checked').val() || 'all');
                $('#filter-attendance-reason').val($('#modal-attendance-reason').val());

                // Section E: Obligation / Service Bond
                $('#filter-bond-status').val($('#modal-bond-status').val());
                $('#filter-bond-end-from').val($('#modal-bond-end-from').val());
                $('#filter-bond-end-to').val($('#modal-bond-end-to').val());
                $('#filter-bond-amount-min').val($('#modal-bond-amount-min').val());
                $('#filter-bond-amount-max').val($('#modal-bond-amount-max').val());

                // Section F: Evaluation & Governance
                $('#filter-l1-program').val($('#modal-l1-program').val());
                $('#filter-pic').val($('#modal-pic').val());
                $('#filter-l2-numerical-min').val($('#modal-l2-num-min').val());
                $('#filter-l2-numerical-max').val($('#modal-l2-num-max').val());
                $('#filter-l2-verbal').val($('#modal-l2-verbal').val());
                $('#filter-l3-numerical-min').val($('#modal-l3-num-min').val());
                $('#filter-l3-numerical-max').val($('#modal-l3-num-max').val());
                $('#filter-l3-verbal').val($('#modal-l3-verbal').val());

                updateActiveFilterBadge();
                const modalEl = document.getElementById('moreParticipantFiltersModal');
                if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(
                        modalEl);
                    if (modalInstance) modalInstance.hide();
                } else {
                    $('#moreParticipantFiltersModal').modal('hide');
                }

                participantTable.ajax.reload();
            });

            // Reset all filters (Quick Filters + More Filters)
            $('#btn-reset-participant-filter').on('click', function() {
                // Reset Quick Filter controls
                $('#participant-course-input').val('');
                $('#participant-type-select').val('all');
                $('#participant-status-select').val('all');
                $('#participant-year-select').val('all');

                resetMoreParticipantFilters();
                participantTable.ajax.reload();
            });

            // Helper function to build export URL with all active filters
            function buildExportUrl(baseUrl) {
                const params = new URLSearchParams();

                // Quick Filters
                const courseVal = ($('#participant-course-input').val() || '').trim();
                if (courseVal) params.append('course_name', courseVal);

                const typeVal = $('#participant-type-select').val();
                if (typeVal && typeVal !== 'all') params.append('type', typeVal);

                const statusVal = $('#participant-status-select').val();
                if (statusVal && statusVal !== 'all') params.append('status', statusVal);

                const yearVal = $('#participant-year-select').val();
                if (yearVal && yearVal !== 'all') params.append('year', yearVal);

                // Section A: Training Information
                const classification = $('#filter-classification').val();
                if (classification && classification !== 'all') params.append('filter_classification',
                    classification);

                const subClassification = $('#filter-sub-classification').val();
                if (subClassification) params.append('sub_classification', subClassification);

                const category = $('#filter-category').val();
                if (category && category !== 'all') params.append('category', category);

                const level = $('#filter-level').val();
                if (level && level !== 'all') params.append('level', level);

                const batch = $('#filter-batch').val();
                if (batch) params.append('batch', batch);

                const deliveryMethod = $('#filter-delivery-method').val();
                if (deliveryMethod && deliveryMethod !== 'all') params.append('delivery_method', deliveryMethod);

                const subDeliveryMethod = $('#filter-sub-delivery-method').val();
                if (subDeliveryMethod) params.append('sub_delivery_method', subDeliveryMethod);

                const provider = $('#filter-provider').val();
                if (provider && provider !== 'all') params.append('provider', provider);

                const subProvider = $('#filter-sub-provider').val();
                if (subProvider) params.append('sub_provider', subProvider);

                const providerName = $('#filter-provider-name').val();
                if (providerName) params.append('provider_name', providerName);

                const corporateKpi = $('#filter-corporate-kpi').val();
                if (corporateKpi && corporateKpi !== 'all') params.append('corporate_kpi', corporateKpi);

                const subCorporateKpi = $('#filter-sub-corporate-kpi').val();
                if (subCorporateKpi) params.append('sub_corporate_kpi', subCorporateKpi);

                const venue = $('#filter-venue').val();
                if (venue) params.append('venue', venue);

                const instructor = $('#filter-instructor').val();
                if (instructor) params.append('instructor', instructor);

                // Section B: Date & Duration
                const fromDate = $('#filter-from-date').val();
                if (fromDate) params.append('from_date', fromDate);

                const toDate = $('#filter-to-date').val();
                if (toDate) params.append('to_date', toDate);

                const hoursMin = $('#filter-hours-min').val();
                if (hoursMin) params.append('hours_min', hoursMin);

                const hoursMax = $('#filter-hours-max').val();
                if (hoursMax) params.append('hours_max', hoursMax);

                const duration = $('#filter-duration').val();
                if (duration && duration !== 'all') params.append('duration', duration);

                // Section C: Training Result
                const progressRange = $('#filter-progress-range').val();
                if (progressRange && progressRange !== 'all') params.append('progress_range', progressRange);

                const scoreMin = $('#filter-score-min').val();
                if (scoreMin) params.append('score_min', scoreMin);

                const scoreMax = $('#filter-score-max').val();
                if (scoreMax) params.append('score_max', scoreMax);

                const certificate = $('#filter-certificate').val();
                if (certificate && certificate !== 'all') params.append('certificate', certificate);

                // Section D: Attendance
                const attendance = $('#filter-attendance').val();
                if (attendance && attendance !== 'all') params.append('attendance', attendance);

                const attendanceReason = $('#filter-attendance-reason').val();
                if (attendanceReason) params.append('attendance_reason', attendanceReason);

                // Section E: Obligation / Service Bond
                const bondStatus = $('#filter-bond-status').val();
                if (bondStatus && bondStatus !== 'all') params.append('bond_status', bondStatus);

                const bondEndFrom = $('#filter-bond-end-from').val();
                if (bondEndFrom) params.append('bond_end_from', bondEndFrom);

                const bondEndTo = $('#filter-bond-end-to').val();
                if (bondEndTo) params.append('bond_end_to', bondEndTo);

                const bondAmtMin = $('#filter-bond-amount-min').val();
                if (bondAmtMin) params.append('bond_amount_min', bondAmtMin);

                const bondAmtMax = $('#filter-bond-amount-max').val();
                if (bondAmtMax) params.append('bond_amount_max', bondAmtMax);

                // Section F: Evaluation & Governance
                const l1 = $('#filter-l1-program').val();
                if (l1) params.append('l1_program', l1);

                const pic = $('#filter-pic').val();
                if (pic) params.append('pic', pic);

                const l2NumMin = $('#filter-l2-numerical-min').val();
                if (l2NumMin) params.append('l2_numerical_min', l2NumMin);

                const l2NumMax = $('#filter-l2-numerical-max').val();
                if (l2NumMax) params.append('l2_numerical_max', l2NumMax);

                const l2Verbal = $('#filter-l2-verbal').val();
                if (l2Verbal) params.append('l2_verbal', l2Verbal);

                const l3NumMin = $('#filter-l3-numerical-min').val();
                if (l3NumMin) params.append('l3_numerical_min', l3NumMin);

                const l3NumMax = $('#filter-l3-numerical-max').val();
                if (l3NumMax) params.append('l3_numerical_max', l3NumMax);

                const l3Verbal = $('#filter-l3-verbal').val();
                if (l3Verbal) params.append('l3_verbal', l3Verbal);

                const qs = params.toString();
                return qs ? `${baseUrl}?${qs}` : baseUrl;
            }

            // Export Excel Click Handler
            $('#btn-export-participant-excel').on('click', function(e) {
                e.preventDefault();
                window.location.href = buildExportUrl(exportExcelBaseUrl);
            });

            // Export CSV Click Handler
            $('#btn-export-participant-csv').on('click', function(e) {
                e.preventDefault();
                window.location.href = buildExportUrl(exportCsvBaseUrl);
            });

            // Export PDF Click Handler
            $('#btn-export-participant-pdf').on('click', function(e) {
                e.preventDefault();
                window.location.href = buildExportUrl(exportPdfBaseUrl);
            });

            // Offline Training Detail Modal Logic
            const detailModalEl = document.getElementById('offlineTrainingDetailModal');
            let detailModal = null;
            if (detailModalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                detailModal = new bootstrap.Modal(detailModalEl);
            }

            $(document).on('click', '.btn-view-offline-detail', function() {
                const id = $(this).data('id');
                if (!id) return;

                $('#offline-detail-loading').show();
                $('#offline-detail-content').hide();

                if (detailModal) {
                    detailModal.show();
                } else {
                    $('#offlineTrainingDetailModal').modal('show');
                }

                $.ajax({
                    url: `/manage/record/offline/${id}/detail`,
                    type: 'GET',
                    dataType: 'json',
                    success: function(res) {
                        $('#offline-detail-loading').hide();
                        if (res && res.success && res.data) {
                            const d = res.data;

                            // Header
                            $('#modal-detail-course-name').text(d.course_name || '-');
                            $('#modal-detail-sub-course-name').text(d.sub_course_name || '');
                            $('#modal-detail-type').text(d.type || 'In-House');

                            const progress = parseFloat(d.progress) || 0;
                            const statusBadge = $('#modal-detail-status');
                            if (progress >= 100) {
                                statusBadge.removeClass('bg-warning-lt bg-secondary-lt')
                                    .addClass('bg-success-lt').text('FINISHED');
                            } else if (progress > 0) {
                                statusBadge.removeClass('bg-success-lt bg-secondary-lt')
                                    .addClass('bg-warning-lt').text(
                                        `ONGOING (${Math.round(progress)}%)`);
                            } else {
                                statusBadge.removeClass('bg-success-lt bg-warning-lt').addClass(
                                    'bg-secondary-lt').text('NOT STARTED');
                            }

                            // Section 1: Course Info
                            $('#modal-detail-batch').text(d.batch || '-');
                            $('#modal-detail-category').text(d.category || '-');
                            $('#modal-detail-level').text(d.level || '-');
                            $('#modal-detail-sec-type').text(d.type || '-');

                            // Section 2: Classification & KPI
                            $('#modal-detail-main-class').text(d.main_classification || '-');
                            $('#modal-detail-sub-class').text(d.sub_classification || '-');
                            $('#modal-detail-corporate-kpi').text(d.counted_corporate_kpi ?
                                'Yes' : (d.counted_corporate_kpi === false ? 'No' : '-'));
                            $('#modal-detail-sub-corporate-kpi').text(d.sub_corporate_kpi ||
                                '-');

                            // Helpers for integer formatting without trailing decimals
                            const formatModalHours = function(h) {
                                if (h === null || h === undefined || h === '' || isNaN(h)) return '-';
                                const num = parseFloat(h);
                                return (num % 1 === 0 ? num.toFixed(0) : parseFloat(num.toFixed(2)).toString()) + ' Hours';
                            };
                            const formatModalScore = function(s) {
                                if (s === null || s === undefined || s === '' || isNaN(s)) return '-';
                                const num = parseFloat(s);
                                return (num % 1 === 0 ? num.toFixed(0) : parseFloat(num.toFixed(2)).toString());
                            };

                            // Section 3: Delivery & Provider
                            const rawDeliv = (d.delivery_method || '').toLowerCase().trim();
                            const deliv = ['online', 'e-learning', 'webinar', 'kp-owl', 'kp owl'].includes(rawDeliv) ? 'Online' : 'Offline';
                            $('#modal-detail-delivery-method').text(deliv);
                            let subDeliv = d.sub_delivery_method || d.training_type || d.type || '-';
                            if (!subDeliv || subDeliv === '-' || subDeliv.toLowerCase() === 'offline') {
                                if (rawDeliv && rawDeliv !== 'offline' && rawDeliv !== 'online') {
                                    subDeliv = d.delivery_method;
                                }
                            }
                            $('#modal-detail-sub-delivery-method').text(subDeliv || '-');
                            $('#modal-detail-provider').text(d.provider || '-');
                            $('#modal-detail-sub-provider').text(d.sub_provider || '-');
                            $('#modal-detail-provider-name').text(d.provider_name || '-');
                            $('#modal-detail-venue').text(d.venue || '-');
                            $('#modal-detail-instructor').text(d.instructor || '-');

                            // Section 4:
                            $('#modal-detail-start-date').text(d.start_date || '-');
                            $('#modal-detail-finish-date').text(d.finish_date || '-');
                            $('#modal-detail-duration-seconds').text(d.duration_seconds ?
                                `${d.duration_seconds.toLocaleString()}s` : '-');
                            $('#modal-detail-course-hours').text(formatModalHours(d.course_learning_hours));

                            // Section 5: Participant & Job Info
                            $('#modal-detail-employee-id').text(d.employee_id || '-');
                            $('#modal-detail-participant-name').text(d.participant_name || d
                                .user_name || '-');
                            $('#modal-detail-grade').text(d.grade || '-');
                            $('#modal-detail-job-position').text(d.job_position || '-');
                            $('#modal-detail-team').text(d.team || '-');
                            $('#modal-detail-department').text(d.department || '-');
                            $('#modal-detail-company').text(d.company || '-');

                            // Section 6: Progress & Evaluation
                            const progVal = parseFloat(progress || 0);
                            $('#modal-detail-progress').text((progVal % 1 === 0 ? progVal.toFixed(0) : progVal.toFixed(1)) + '%');
                            $('#modal-detail-learning-hours').text(formatModalHours(d.participant_learning_hours));
                            $('#modal-detail-assessment-score').text(formatModalScore(d.assessment_score));

                            const rawAtt = String(d.attendance ?? '').toLowerCase().trim();
                            let isAttYes = ['1', 'yes', 'true', 'present', 'hadir', 'completed', 'finished'].includes(rawAtt);
                            if (!isAttYes && (rawAtt === '' || d.attendance === null || d.attendance === undefined)) {
                                isAttYes = (progVal >= 100 || !!d.finish_date);
                            }

                            $('#modal-detail-attendance').html(isAttYes ?
                                '<span class="badge bg-success-lt text-success">Yes</span>' :
                                '<span class="badge bg-danger-lt text-danger">No</span>'
                            );

                            $('#modal-detail-attendance-reason').text(d.attendance_reason ||
                                '-');
                            $('#modal-detail-certificate').text(d.certificate || '-');

                            // Section 7: Service Bond & L1-L3
                            $('#modal-detail-bond-status').text(d.bond_status || 'No');
                            $('#modal-detail-bond-end-date').text(d.bond_end_date || '-');
                            $('#modal-detail-bond-amount').text(d.bond_amount ?
                                `Rp ${parseFloat(d.bond_amount).toLocaleString('id-ID')}` :
                                '-');
                            $('#modal-detail-l1-program').text(formatModalScore(d.l1_program));
                            $('#modal-detail-l2-numerical').text(formatModalScore(d.l2_numerical));
                            $('#modal-detail-l2-verbal').text(d.l2_verbal || '-');
                            $('#modal-detail-l3-numerical').text(formatModalScore(d.l3_numerical));
                            $('#modal-detail-l3-verbal').text(d.l3_verbal || '-');
                            $('#modal-detail-pic').text(d.pic || '-');

                            $('#offline-detail-content').fadeIn(150);
                        } else {
                            $('#offlineTrainingDetailModalBody').html(
                                '<div class="py-5 text-center text-danger">Failed to load record details.</div>'
                                );
                        }
                    },
                    error: function() {
                        $('#offline-detail-loading').hide();
                        $('#offlineTrainingDetailModalBody').html(
                            '<div class="py-5 text-center text-danger">An error occurred while loading record details.</div>'
                            );
                    }
                });
            });

            // Delete Record Modal Logic
            const deleteModalEl = document.getElementById('deleteOfflineRecordModal');
            let deleteModal = null;
            if (deleteModalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                deleteModal = new bootstrap.Modal(deleteModalEl);
            }

            $(document).on('click', '.btn-delete-offline-record', function() {
                const id = $(this).data('id');
                const employee = $(this).data('employee') || 'Participant';
                const course = $(this).data('course') || 'Training';
                const nip = $(this).data('nip') || '';

                $('#delete-modal-employee-name').text(employee);
                $('#delete-modal-course-name').text(course);
                if (nip) {
                    $('#delete-modal-nip-wrapper').show();
                    $('#delete-modal-nip').text(nip);
                } else {
                    $('#delete-modal-nip-wrapper').hide();
                }
                $('#btn-confirm-delete-offline-record').data('id', id);

                if (deleteModal) {
                    deleteModal.show();
                } else {
                    $('#deleteOfflineRecordModal').modal('show');
                }
            });

            $('#btn-confirm-delete-offline-record').on('click', function() {
                const id = $(this).data('id');
                if (!id) return;

                const $btn = $(this);
                const originalHtml = $btn.html();
                $btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Deleting...'
                    );

                $.ajax({
                    url: `/manage/record/offline/${id}`,
                    type: 'DELETE',
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    },
                    success: function(res) {
                        $btn.prop('disabled', false).html(originalHtml);
                        if (deleteModal) {
                            deleteModal.hide();
                        } else {
                            $('#deleteOfflineRecordModal').modal('hide');
                        }

                        // Update summary KPI cards if returned
                        if (res && res.participant_summary) {
                            $('#kpi-total-training').text(Number(res.participant_summary
                                .total_training).toLocaleString());
                            $('#kpi-finished').text(Number(res.participant_summary.finished)
                                .toLocaleString());
                            $('#kpi-ongoing').text(Number(res.participant_summary.ongoing)
                                .toLocaleString());
                            $('#kpi-hours').text(parseFloat(res.participant_summary
                                .learning_hours).toFixed(1) + 'h');
                        }

                        participantTable.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).html(originalHtml);
                        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr
                            .responseJSON.message : 'Failed to delete record.';
                        alert(msg);
                    }
                });
            });
        });
    </script>
@endsection
