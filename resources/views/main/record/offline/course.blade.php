@extends('layouts.sidebar-master')

@section('content')
<div class="page-body manage-record-page">
    <div class="container-xl">
        <!-- BACK BUTTON -->
        <div class="mb-3">
            <a href="{{ $backUrl }}" class="btn-back d-inline-flex align-items-center gap-1 text-decoration-none fw-medium">
                <i class="ti ti-arrow-left"></i>
                <span>{{ $backTitle }}</span>
            </a>
        </div>

        <!-- SIMPLIFIED COURSE HEADER CARD (No detailed metadata grid) -->
        <div class="card border-0 shadow-sm p-3 p-md-4 mb-4 rounded-3 bg-white record-section-header">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-start gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 record-section-icon"
                         style="width: 56px; height: 56px; background-color: #fef3c7; color: #d97706;">
                        <i class="ti ti-book fs-1"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <h2 class="h3 fw-bold mb-0 text-dark" id="course-name">{{ $training->course_name ?: '-' }}</h2>
                            @if (!empty($training->type))
                                <span class="badge bg-light text-secondary border px-2 py-1">{{ $training->type }}</span>
                            @endif
                            @if (!empty($training->main_classification))
                                <span class="badge bg-primary-lt fw-bold px-2 py-1">{{ $training->main_classification }}</span>
                            @endif
                            @if (!empty($training->batch))
                                <span class="badge bg-secondary-lt fw-semibold px-2 py-1">Batch: {{ $training->batch }}</span>
                            @endif
                        </div>
                        @if (!empty($training->sub_course_name))
                            <div class="text-muted fw-medium fs-4 mb-0" id="sub-course-name">{{ $training->sub_course_name }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- COURSE SUMMARY CARDS -->
        <div class="row g-3 mb-4">
            <!-- Total Participants -->
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm p-3 rounded-3 bg-white stat-kpi-card h-100">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3"
                             style="width: 44px; height: 44px; background-color: #0f4c81; color: #ffffff;">
                            <i class="ti ti-users fs-2"></i>
                        </div>
                        <div>
                            <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Total Participants</div>
                            <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;" id="kpi-total-participants">{{ number_format($summary['total_participants']) }}</div>
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
                            <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Finished</div>
                            <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;" id="kpi-finished">{{ number_format($summary['finished']) }}</div>
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
                            <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Ongoing</div>
                            <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;" id="kpi-ongoing">{{ number_format($summary['ongoing']) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Average Progress -->
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm p-3 rounded-3 bg-white stat-kpi-card h-100">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3"
                             style="width: 44px; height: 44px; background-color: #0284c7; color: #ffffff;">
                            <i class="ti ti-chart-pie fs-2"></i>
                        </div>
                        <div>
                            <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Avg. Progress</div>
                            <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;" id="kpi-avg-progress">{{ number_format($summary['avg_progress'], 1) }}%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- QUICK FILTER CARD -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-3">
                <div class="text-secondary small fw-semibold mb-2" style="color: #6c757d !important; font-size: 0.85rem;">
                    Quick Filter
                </div>
                <form id="course-filter-form" class="w-100" onsubmit="return false;">
                    <div class="d-flex align-items-center gap-2 flex-wrap w-100">
                        <!-- Search Participant / NIP -->
                        <div class="position-relative flex-grow-1" style="min-width: 200px;">
                            <input type="text" id="course-participant-input" class="form-control rounded-3 w-100"
                                   placeholder="Search participant or NIP..." autocomplete="off">
                        </div>

                        <!-- Type Dropdown -->
                        <div class="flex-shrink-0" style="min-width: 125px;">
                            <select class="form-select rounded-3" id="course-type-select">
                                <option value="all">Type: All</option>
                                @foreach ($trainingTypes as $type)
                                    <option value="{{ $type }}">{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Status Dropdown -->
                        <div class="flex-shrink-0" style="min-width: 120px;">
                            <select class="form-select rounded-3" id="course-status-select">
                                <option value="all">All Status</option>
                                <option value="finished">Finished</option>
                                <option value="ongoing">Ongoing</option>
                                <option value="not_started">Not Started</option>
                            </select>
                        </div>

                        <!-- Year Dropdown -->
                        <div class="flex-shrink-0" style="min-width: 105px;">
                            <select class="form-select rounded-3" id="course-year-select">
                                <option value="all">All Year</option>
                                @foreach ($years as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Search Button -->
                        <button type="button" id="btn-apply-course-filter" class="btn btn-primary btn-icon px-3 flex-shrink-0"
                                style="background-color: #003b6d; border-color: #003b6d;" data-bs-toggle="tooltip" data-bs-title="Apply Filter" title="Apply Filter">
                            <i class="ti ti-search fs-2"></i>
                        </button>

                        <!-- More Filters Button with Counter Badge -->
                        <button type="button" id="btn-more-course-filters" class="btn btn-outline-primary btn-icon px-3 position-relative btn-more-filters flex-shrink-0"
                                data-bs-toggle="modal" data-bs-target="#moreCourseFiltersModal" title="More Filters">
                            <i class="ti ti-adjustments-horizontal fs-2"></i>
                            <span id="activeCourseFilterBadge" class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle d-none">0</span>
                        </button>

                        <!-- Reset Button -->
                        <button type="button" id="btn-reset-course-filter" class="btn btn-outline-secondary btn-icon px-3 btn-quick-reset flex-shrink-0"
                                data-bs-toggle="tooltip" data-bs-title="Reset Filters" title="Reset Filters">
                            <i class="ti ti-refresh fs-2"></i>
                        </button>

                        <!-- Export Dropdown (Excel, CSV, PDF Landscape) -->
                        <div class="dropdown flex-shrink-0">
                            <button class="btn btn-success dropdown-toggle rounded-3 px-3 btn-download"
                                    type="button" id="courseExportDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="Export Records">
                                <i class="ti ti-download me-1"></i> Download
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3" aria-labelledby="courseExportDropdown">
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:void(0);" id="btn-export-course-excel">
                                        <i class="ti ti-file-spreadsheet text-success fs-2"></i>
                                        <div>
                                            <div class="fw-semibold">Excel (.xlsx)</div>
                                            <div class="small text-muted" style="font-size: 0.72rem;">Canonical 44-column spreadsheet</div>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:void(0);" id="btn-export-course-csv">
                                        <i class="ti ti-file-text text-primary fs-2"></i>
                                        <div>
                                            <div class="fw-semibold">CSV (.csv)</div>
                                            <div class="small text-muted" style="font-size: 0.72rem;">Standard comma-separated format</div>
                                        </div>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider my-1"></li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:void(0);" id="btn-export-course-pdf">
                                        <i class="ti ti-file-type-pdf text-danger fs-2"></i>
                                        <div>
                                            <div class="fw-semibold">PDF Landscape (.pdf)</div>
                                            <div class="small text-muted" style="font-size: 0.72rem;">Structured A3 landscape report</div>
                                        </div>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- FULL RAW CANONICAL RECORD TABLE CARD -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h3 class="card-title fw-bold mb-0 text-dark">Course Records / Participants</h3>
                    <div class="text-muted small">Canonical Non KP OWL record view for this training session (~44 HRDC canonical fields).</div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive offline-table-responsive">
                    <table id="course-participants-table" class="table table-vcenter table-striped card-table">
                        <thead>
                            <tr>
                                <th class="text-center" style="min-width: 45px; width: 45px;">NO</th>
                                <th class="text-center" style="min-width: 90px;">EMPLOYEE ID</th>
                                <th style="min-width: 180px;">EMPLOYEE NAME</th>
                                <th style="min-width: 140px;">TEAM</th>
                                <th style="min-width: 160px;">DEPARTMENT</th>
                                <th style="min-width: 160px;">COMPANY</th>
                                <th style="min-width: 220px;">COURSE TITLE</th>
                                <th class="text-center" style="min-width: 100px;">TYPE</th>
                                <th style="min-width: 130px;">CATEGORY</th>
                                <th style="min-width: 150px;">MAIN CLASSIFICATION</th>
                                <th style="min-width: 150px;">SUB CLASSIFICATION</th>
                                <th class="text-center" style="min-width: 120px;">DELIVERY METHOD</th>
                                <th style="min-width: 140px;">SUB DELIVERY METHOD</th>
                                <th style="min-width: 160px;">PROVIDER NAME</th>
                                <th style="min-width: 140px;">VENUE</th>
                                <th class="text-center" style="min-width: 100px;">COURSE DURATION</th>
                                <th class="text-center" style="min-width: 110px;">LEARNING HOURS</th>
                                <th class="text-center" style="min-width: 110px;">% PROGRESS</th>
                                <th class="text-center" style="min-width: 110px;">PROGRESS STATUS</th>
                                <th class="text-center" style="min-width: 140px;">DATE TIME</th>
                                <th class="text-center" style="min-width: 100px;">RECORD MONTH</th>
                                <th class="text-center" style="min-width: 110px;">REQUIREMENT</th>
                                <th class="text-center" style="min-width: 105px;">ATTENDANCE</th>
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
                        <select class="form-select form-select-sm w-auto rounded-3" id="course-table-entries-select">
                            <option value="10">10 Entries</option>
                            <option value="25" selected>25 Entries</option>
                            <option value="50">50 Entries</option>
                            <option value="100">100 Entries</option>
                        </select>
                        <div id="course-table-info-container"></div>
                    </div>
                    <div id="course-table-pagination-container"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MORE FILTERS MODAL -->
<div class="modal fade" id="moreCourseFiltersModal" tabindex="-1" aria-labelledby="moreCourseFiltersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <form id="more-course-filters-modal-form" onsubmit="return false;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="moreCourseFiltersModalLabel">
                        <i class="ti ti-adjustments-horizontal text-primary"></i> More Filters
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <!-- SECTION A: EMPLOYEE / JOB INFORMATION -->
                    <div class="text-uppercase fw-bold text-muted small mb-2 border-bottom pb-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                        A. Employee / Job Information
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small" for="modal-course-grade">
                                <i class="ti ti-id me-1"></i> Grade
                            </label>
                            <select class="form-select rounded-3" id="modal-course-grade" name="grade">
                                <option value="all">All Grades</option>
                                @foreach ($grades as $g)
                                    <option value="{{ $g }}">{{ $g }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small" for="modal-course-team">
                                <i class="ti ti-users me-1"></i> Team
                            </label>
                            <select class="form-select rounded-3" id="modal-course-team" name="team">
                                <option value="all">All Teams</option>
                                @foreach ($teams as $t)
                                    <option value="{{ $t }}">{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small" for="modal-course-job-position">
                                <i class="ti ti-briefcase me-1"></i> Job Position
                            </label>
                            <input type="text" class="form-control rounded-3" id="modal-course-job-position" name="job_position" placeholder="Search job position...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small" for="modal-course-department">
                                <i class="ti ti-building me-1"></i> Department
                            </label>
                            <select class="form-select rounded-3" id="modal-course-department" name="department">
                                <option value="all">All Departments</option>
                                @foreach ($departments as $d)
                                    <option value="{{ $d }}">{{ $d }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold text-dark small" for="modal-course-company">
                                <i class="ti ti-building-community me-1"></i> Company
                            </label>
                            <select class="form-select rounded-3" id="modal-course-company" name="company">
                                <option value="all">All Companies</option>
                                @foreach ($companies as $c)
                                    <option value="{{ $c }}">{{ $c }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- SECTION B: TRAINING RESULT -->
                    <div class="text-uppercase fw-bold text-muted small mb-2 border-bottom pb-1 pt-2" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                        B. Training Result
                    </div>
                    <!-- Progress Range -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small d-block"><i class="ti ti-chart-bar me-1"></i> Progress Range</label>
                        <div class="d-flex flex-wrap gap-3 align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_course_progress_range" id="cprAll" value="all" checked>
                                <label class="form-check-label text-dark" for="cprAll">All</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_course_progress_range" id="cpr0" value="0">
                                <label class="form-check-label text-dark" for="cpr0">0%</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_course_progress_range" id="cpr25" value="1-25">
                                <label class="form-check-label text-dark" for="cpr25">1 - 25%</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_course_progress_range" id="cpr50" value="26-50">
                                <label class="form-check-label text-dark" for="cpr50">26 - 50%</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_course_progress_range" id="cpr75" value="51-75">
                                <label class="form-check-label text-dark" for="cpr75">51 - 75%</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_course_progress_range" id="cpr99" value="76-99">
                                <label class="form-check-label text-dark" for="cpr99">76 - 99%</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_course_progress_range" id="cpr100" value="100">
                                <label class="form-check-label text-dark" for="cpr100">100%</label>
                            </div>
                        </div>
                    </div>

                    <!-- Learning Hours & Assessment Score -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small"><i class="ti ti-hourglass-empty me-1"></i> Learning Hours Range</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" step="0.1" min="0" id="modal-course-hours-min" name="hours_min" class="form-control rounded-3" placeholder="Min Hours">
                                </div>
                                <div class="col-6">
                                    <input type="number" step="0.1" min="0" id="modal-course-hours-max" name="hours_max" class="form-control rounded-3" placeholder="Max Hours">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small"><i class="ti ti-award me-1"></i> Assessment Score Range</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" step="0.1" min="0" max="100" id="modal-course-score-min" name="score_min" class="form-control rounded-3" placeholder="Min Score">
                                </div>
                                <div class="col-6">
                                    <input type="number" step="0.1" min="0" max="100" id="modal-course-score-max" name="score_max" class="form-control rounded-3" placeholder="Max Score">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Certificate -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small d-block"><i class="ti ti-file-certificate me-1"></i> Certificate</label>
                        <div class="d-flex flex-wrap gap-3 align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_course_certificate" id="certAll" value="all" checked>
                                <label class="form-check-label text-dark" for="certAll">All</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_course_certificate" id="certYes" value="yes">
                                <label class="form-check-label text-dark" for="certYes">Yes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_course_certificate" id="certNo" value="no">
                                <label class="form-check-label text-dark" for="certNo">No</label>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION C: ATTENDANCE -->
                    <div class="text-uppercase fw-bold text-muted small mb-2 border-bottom pb-1 pt-2" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                        C. Attendance
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small d-block"><i class="ti ti-user-check me-1"></i> Attendance</label>
                            <div class="d-flex flex-wrap gap-3 align-items-center mt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_course_attendance" id="attAll" value="all" checked>
                                    <label class="form-check-label text-dark" for="attAll">All</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_course_attendance" id="attPresent" value="present">
                                    <label class="form-check-label text-dark" for="attPresent">Present</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_course_attendance" id="attAbsent" value="absent">
                                    <label class="form-check-label text-dark" for="attAbsent">Absent</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small" for="modal-course-attendance-reason">
                                <i class="ti ti-notes me-1"></i> Attendance Reason
                            </label>
                            <input type="text" class="form-control rounded-3" id="modal-course-attendance-reason" name="attendance_reason" placeholder="Search reason...">
                        </div>
                    </div>

                    <!-- SECTION D: OBLIGATION / SERVICE BOND -->
                    <div class="text-uppercase fw-bold text-muted small mb-2 border-bottom pb-1 pt-2" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                        D. Obligation / Service Bond
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark small d-block"><i class="ti ti-shield me-1"></i> Bond Status</label>
                            <div class="d-flex flex-wrap gap-3 align-items-center mt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_course_bond_status" id="bondAll" value="all" checked>
                                    <label class="form-check-label text-dark" for="bondAll">All</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_course_bond_status" id="bondYes" value="yes">
                                    <label class="form-check-label text-dark" for="bondYes">Yes</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_course_bond_status" id="bondNo" value="no">
                                    <label class="form-check-label text-dark" for="bondNo">No</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark small"><i class="ti ti-calendar-event me-1"></i> Bond End Date</label>
                            <div class="row g-1">
                                <div class="col-6">
                                    <input type="text" id="modal-course-bond-from" name="bond_end_from" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'" class="form-control rounded-3" placeholder="From">
                                </div>
                                <div class="col-6">
                                    <input type="text" id="modal-course-bond-to" name="bond_end_to" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'" class="form-control rounded-3" placeholder="To">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark small"><i class="ti ti-currency-dollar me-1"></i> Bond Amount (IDR)</label>
                            <div class="row g-1">
                                <div class="col-6">
                                    <input type="number" min="0" id="modal-course-bond-amount-min" name="bond_amount_min" class="form-control rounded-3" placeholder="Min">
                                </div>
                                <div class="col-6">
                                    <input type="number" min="0" id="modal-course-bond-amount-max" name="bond_amount_max" class="form-control rounded-3" placeholder="Max">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION E: EVALUATION -->
                    <div class="text-uppercase fw-bold text-muted small mb-2 border-bottom pb-1 pt-2" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                        E. Evaluation
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small" for="modal-course-l1">
                                <i class="ti ti-message-2 me-1"></i> L1 Program
                            </label>
                            <input type="text" class="form-control rounded-3" id="modal-course-l1" name="l1_program" placeholder="e.g. Very Good">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small" for="modal-course-pic">
                                <i class="ti ti-user-circle me-1"></i> PIC
                            </label>
                            <input type="text" class="form-control rounded-3" id="modal-course-pic" name="pic" placeholder="Search PIC...">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small"><i class="ti ti-math me-1"></i> L2 Post Test (Numerical)</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" step="0.1" min="0" max="100" id="modal-course-l2-num-min" name="l2_numerical_min" class="form-control rounded-3" placeholder="Min">
                                </div>
                                <div class="col-6">
                                    <input type="number" step="0.1" min="0" max="100" id="modal-course-l2-num-max" name="l2_numerical_max" class="form-control rounded-3" placeholder="Max">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small" for="modal-course-l2-verbal">
                                <i class="ti ti-file-description me-1"></i> L2 Verbal
                            </label>
                            <input type="text" class="form-control rounded-3" id="modal-course-l2-verbal" name="l2_verbal" placeholder="e.g. Passed">
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small"><i class="ti ti-math me-1"></i> L3 Project (Numerical)</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" step="0.1" min="0" max="100" id="modal-course-l3-num-min" name="l3_numerical_min" class="form-control rounded-3" placeholder="Min">
                                </div>
                                <div class="col-6">
                                    <input type="number" step="0.1" min="0" max="100" id="modal-course-l3-num-max" name="l3_numerical_max" class="form-control rounded-3" placeholder="Max">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small" for="modal-course-l3-verbal">
                                <i class="ti ti-file-description me-1"></i> L3 Verbal
                            </label>
                            <input type="text" class="form-control rounded-3" id="modal-course-l3-verbal" name="l3_verbal" placeholder="e.g. Implemented successfully">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-3" id="btn-reset-modal-course-filters">
                        <i class="ti ti-refresh me-1"></i> Reset
                    </button>
                    <button type="button" class="btn btn-primary rounded-3 px-4" id="btn-apply-modal-course-filters" style="background-color: #003b6d; border-color: #003b6d;">
                        <i class="ti ti-check me-1"></i> Apply Filter
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DETAIL MODAL FOR RECORD VIEW -->
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

    #course-participants-table {
        font-size: 13px;
        width: max-content !important;
        min-width: 100% !important;
        margin-bottom: 0;
    }

    #course-participants-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap !important;
        vertical-align: middle;
        padding: 11px 12px;
        border-bottom: 1px solid #e2e8f0;
    }

    #course-participants-table tbody td {
        vertical-align: middle;
        padding: 9px 12px;
        white-space: nowrap !important;
    }

    [data-bs-theme="dark"] #course-participants-table thead th,
    body.dark-mode #course-participants-table thead th,
    body.theme-dark #course-participants-table thead th {
        background: #1e293b !important;
        color: #94a3b8 !important;
        border-bottom-color: #334155 !important;
    }

    [data-bs-theme="dark"] #course-participants-table tbody td,
    body.dark-mode #course-participants-table tbody td,
    body.theme-dark #course-participants-table tbody td {
        border-bottom-color: #334155 !important;
        color: #e2e8f0 !important;
    }

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
    #btn-more-course-filters {
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
    #btn-more-course-filters:hover,
    #btn-more-course-filters:focus,
    #btn-more-course-filters:active {
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
    #btn-more-course-filters:hover i,
    #btn-more-course-filters:focus i,
    #btn-more-course-filters:active i {
        color: #ffffff !important;
    }

    [data-bs-theme="dark"] .btn-more-filters,
    [data-bs-theme="dark"] #btn-more-filters,
    [data-bs-theme="dark"] #btn-more-course-filters,
    body.dark-mode .btn-more-filters,
    body.dark-mode #btn-more-filters,
    body.dark-mode #btn-more-course-filters,
    body.theme-dark .btn-more-filters,
    body.theme-dark #btn-more-filters,
    body.theme-dark #btn-more-course-filters {
        background-color: transparent !important;
        border-color: #3b82f6 !important;
        color: #60a5fa !important;
    }
    [data-bs-theme="dark"] .btn-more-filters:hover,
    [data-bs-theme="dark"] #btn-more-filters:hover,
    [data-bs-theme="dark"] #btn-more-course-filters:hover,
    body.dark-mode .btn-more-filters:hover,
    body.dark-mode #btn-more-filters:hover,
    body.dark-mode #btn-more-course-filters:hover,
    body.theme-dark .btn-more-filters:hover,
    body.theme-dark #btn-more-filters:hover,
    body.theme-dark #btn-more-course-filters:hover {
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

    #moreCourseFiltersModal {
        z-index: 1060 !important;
    }

    [data-bs-theme="dark"] #moreCourseFiltersModal .modal-content,
    body.dark-mode #moreCourseFiltersModal .modal-content,
    body.theme-dark #moreCourseFiltersModal .modal-content {
        background-color: var(--tblr-bg-surface, #1f2937) !important;
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] #moreCourseFiltersModal .bg-light,
    body.dark-mode #moreCourseFiltersModal .bg-light,
    body.theme-dark #moreCourseFiltersModal .bg-light {
        background-color: #111827 !important;
    }

    [data-bs-theme="dark"] #moreCourseFiltersModal .text-dark,
    body.dark-mode #moreCourseFiltersModal .text-dark,
    body.theme-dark #moreCourseFiltersModal .text-dark {
        color: #f8fafc !important;
    }

    .hover-underline:hover,
    .participant-link:hover {
        text-decoration: underline !important;
    }
</style>
@endsection

@section('script')
<script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>
<script>
    $(function () {
        const datatableUrl = @json(route('manage.record.offline.course.datatable', $training->id));
        const exportExcelBaseUrl = @json($exportExcelUrl);
        const exportCsvBaseUrl = @json($exportCsvUrl);
        const exportPdfBaseUrl = @json($exportPdfUrl);
        const classificationParam = @json($classificationParam);
        const defaultEntries = 25;
        let courseTable = null;

        // Initialize Bootstrap tooltips
        $('[data-bs-toggle="tooltip"]').tooltip();

        // 1. Build comprehensive parameters for AJAX & Export
        function buildCourseFilterParams() {
            const params = {};
            const participant = ($('#course-participant-input').val() || '').trim();
            if (participant) params.participant = participant;

            const type = $('#course-type-select').val();
            if (type && type !== 'all') params.type = type;

            const status = $('#course-status-select').val();
            if (status && status !== 'all') params.status = status;

            const year = $('#course-year-select').val();
            if (year && year !== 'all') params.year = year;

            const grade = $('#modal-course-grade').val();
            if (grade && grade !== 'all') params.grade = grade;

            const jobPosition = ($('#modal-course-job-position').val() || '').trim();
            if (jobPosition) params.job_position = jobPosition;

            const team = $('#modal-course-team').val();
            if (team && team !== 'all') params.team = team;

            const department = $('#modal-course-department').val();
            if (department && department !== 'all') params.department = department;

            const company = $('#modal-course-company').val();
            if (company && company !== 'all') params.company = company;

            const pr = $('input[name="modal_course_progress_range"]:checked').val();
            if (pr && pr !== 'all') params.progress_range = pr;

            const hoursMin = ($('#modal-course-hours-min').val() || '').trim();
            if (hoursMin) params.hours_min = hoursMin;
            const hoursMax = ($('#modal-course-hours-max').val() || '').trim();
            if (hoursMax) params.hours_max = hoursMax;

            const scoreMin = ($('#modal-course-score-min').val() || '').trim();
            if (scoreMin) params.score_min = scoreMin;
            const scoreMax = ($('#modal-course-score-max').val() || '').trim();
            if (scoreMax) params.score_max = scoreMax;

            const cert = $('input[name="modal_course_certificate"]:checked').val();
            if (cert && cert !== 'all') params.certificate = cert;

            const att = $('input[name="modal_course_attendance"]:checked').val();
            if (att && att !== 'all') params.attendance = att;
            const attReason = ($('#modal-course-attendance-reason').val() || '').trim();
            if (attReason) params.attendance_reason = attReason;

            const bond = $('input[name="modal_course_bond_status"]:checked').val();
            if (bond && bond !== 'all') params.bond_status = bond;
            const bondFrom = ($('#modal-course-bond-from').val() || '').trim();
            if (bondFrom) params.bond_end_from = bondFrom;
            const bondTo = ($('#modal-course-bond-to').val() || '').trim();
            if (bondTo) params.bond_end_to = bondTo;
            const bondAmtMin = ($('#modal-course-bond-amount-min').val() || '').trim();
            if (bondAmtMin) params.bond_amount_min = bondAmtMin;
            const bondAmtMax = ($('#modal-course-bond-amount-max').val() || '').trim();
            if (bondAmtMax) params.bond_amount_max = bondAmtMax;

            const l1 = ($('#modal-course-l1').val() || '').trim();
            if (l1) params.l1_program = l1;

            const l2NumMin = ($('#modal-course-l2-num-min').val() || '').trim();
            if (l2NumMin) params.l2_numerical_min = l2NumMin;
            const l2NumMax = ($('#modal-course-l2-num-max').val() || '').trim();
            if (l2NumMax) params.l2_numerical_max = l2NumMax;
            const l2v = ($('#modal-course-l2-verbal').val() || '').trim();
            if (l2v) params.l2_verbal = l2v;

            const l3NumMin = ($('#modal-course-l3-num-min').val() || '').trim();
            if (l3NumMin) params.l3_numerical_min = l3NumMin;
            const l3NumMax = ($('#modal-course-l3-num-max').val() || '').trim();
            if (l3NumMax) params.l3_numerical_max = l3NumMax;
            const l3v = ($('#modal-course-l3-verbal').val() || '').trim();
            if (l3v) params.l3_verbal = l3v;

            const pic = ($('#modal-course-pic').val() || '').trim();
            if (pic) params.pic = pic;

            return params;
        }

        // 2. Active filter counter badge for More Filters
        function updateActiveFilterBadge() {
            let count = 0;
            if ($('#modal-course-grade').val() && $('#modal-course-grade').val() !== 'all') count++;
            if (($('#modal-course-job-position').val() || '').trim() !== '') count++;
            if ($('#modal-course-team').val() && $('#modal-course-team').val() !== 'all') count++;
            if ($('#modal-course-department').val() && $('#modal-course-department').val() !== 'all') count++;
            if ($('#modal-course-company').val() && $('#modal-course-company').val() !== 'all') count++;

            const pr = $('input[name="modal_course_progress_range"]:checked').val();
            if (pr && pr !== 'all') count++;

            if (($('#modal-course-hours-min').val() || '').trim() !== '') count++;
            if (($('#modal-course-hours-max').val() || '').trim() !== '') count++;
            if (($('#modal-course-score-min').val() || '').trim() !== '') count++;
            if (($('#modal-course-score-max').val() || '').trim() !== '') count++;

            const cert = $('input[name="modal_course_certificate"]:checked').val();
            if (cert && cert !== 'all') count++;

            const att = $('input[name="modal_course_attendance"]:checked').val();
            if (att && att !== 'all') count++;
            if (($('#modal-course-attendance-reason').val() || '').trim() !== '') count++;

            const bond = $('input[name="modal_course_bond_status"]:checked').val();
            if (bond && bond !== 'all') count++;
            if (($('#modal-course-bond-from').val() || '').trim() !== '') count++;
            if (($('#modal-course-bond-to').val() || '').trim() !== '') count++;
            if (($('#modal-course-bond-amount-min').val() || '').trim() !== '') count++;
            if (($('#modal-course-bond-amount-max').val() || '').trim() !== '') count++;

            if (($('#modal-course-l1').val() || '').trim() !== '') count++;
            if (($('#modal-course-l2-num-min').val() || '').trim() !== '') count++;
            if (($('#modal-course-l2-num-max').val() || '').trim() !== '') count++;
            if (($('#modal-course-l2-verbal').val() || '').trim() !== '') count++;
            if (($('#modal-course-l3-num-min').val() || '').trim() !== '') count++;
            if (($('#modal-course-l3-num-max').val() || '').trim() !== '') count++;
            if (($('#modal-course-l3-verbal').val() || '').trim() !== '') count++;
            if (($('#modal-course-pic').val() || '').trim() !== '') count++;

            const $badge = $('#activeCourseFilterBadge');
            if (count > 0) {
                $badge.text(count).removeClass('d-none');
            } else {
                $badge.text('0').addClass('d-none');
            }
        }

        // 3. Helper to construct query string with active filters for exports
        function getCourseFilterQueryString() {
            const params = new URLSearchParams();
            const filters = buildCourseFilterParams();
            for (const [key, val] of Object.entries(filters)) {
                if (val !== undefined && val !== null && val !== '') {
                    params.append(key, val);
                }
            }
            const qs = params.toString();
            return qs ? '?' + qs : '';
        }

        // Initialize DataTable with full canonical columns
        courseTable = $('#course-participants-table').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            autoWidth: false,
            scrollX: true,
            pageLength: defaultEntries,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [[3, 'asc']], // Order by Participant Name
            dom: 't',
            ajax: {
                url: datatableUrl,
                type: 'GET',
                data: function (d) {
                    if (classificationParam) {
                        d.classification = classificationParam;
                    }
                    d.entries = $('#course-table-entries-select').val() || defaultEntries;
                    d.length = parseInt($('#course-table-entries-select').val(), 10) || defaultEntries;
                    Object.assign(d, buildCourseFilterParams());
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                { data: 'employee_id', name: 'otu.employee_id', className: 'text-center' },
                { data: 'participant_name', name: 'otu.name', className: 'text-start' },
                { data: 'team', name: 'otu.team', className: 'text-start' },
                { data: 'department', name: 'otu.department', className: 'text-start' },
                { data: 'company', name: 'otu.company', className: 'text-start' },
                { data: 'course_name', name: 'ot.course_name', className: 'text-start' },
                { data: 'type_badge', name: 'ot.type', className: 'text-center' },
                { data: 'category', name: 'ot.category', className: 'text-start' },
                { data: 'main_classification', name: 'ot.main_classification', className: 'text-start' },
                { data: 'sub_classification', name: 'ot.sub_classification', className: 'text-start' },
                { data: 'delivery_method', name: 'ot.delivery_method', className: 'text-center' },
                { data: 'sub_delivery_method', name: 'ot.sub_delivery_method', className: 'text-start' },
                { data: 'provider_name', name: 'ot.provider_name', className: 'text-start' },
                { data: 'venue', name: 'ot.venue', className: 'text-start' },
                { data: 'course_duration', name: 'ot.duration_seconds', className: 'text-center' },
                { data: 'course_learning_hours', name: 'ot.learning_hours', className: 'text-center' },
                { data: 'progress_percent', name: 'otu.progress', className: 'text-center' },
                { data: 'progress_status', name: 'otu.progress', className: 'text-center' },
                { data: 'date_time_merged', name: 'ot.start_date', className: 'text-center', orderable: false },
                { data: 'record_month', name: 'ot.start_date', className: 'text-center', orderable: false },
                { data: 'requirement', name: 'ot.requirement', className: 'text-center' },
                { data: 'attendance_badge', name: 'otu.attendance', className: 'text-center' },
                { data: 'score_l1', name: 'otu.assessment_score', className: 'text-center' },
                { data: 'score_l2', name: 'otu.l2_numerical', className: 'text-center' },
                { data: 'score_l3', name: 'otu.l3_numerical', className: 'text-center' },
                { data: 'pic', name: 'otu.pic', className: 'text-start' },
                { data: 'total_engagement_time', name: 'otu.learning_hours', className: 'text-center' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
            ],
            language: {
                processing: '<div class="py-4 text-center"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 text-muted">Loading participant data...</div></div>',
                emptyTable: '<div class="py-5 text-center text-muted"><i class="ti ti-inbox fs-1 d-block mb-2 text-secondary"></i>No participants enrolled in this training session.</div>'
            },
            drawCallback: function (settings) {
                const api = this.api();
                const pageInfo = api.page.info();
                const formatNum = (num) => (num || 0).toLocaleString();

                if (pageInfo.recordsTotal > 0 && pageInfo.recordsDisplay > 0) {
                    const start = pageInfo.start + 1;
                    const end = pageInfo.end;
                    const total = pageInfo.recordsDisplay;
                    $('#course-table-info-container').html(
                        `<span class="text-muted small">Showing ${formatNum(start)} to ${formatNum(end)} of ${formatNum(total)} entries</span>`
                    );
                } else {
                    $('#course-table-info-container').html('<span class="text-muted small">Showing 0 to 0 of 0 entries</span>');
                }

                // Render pagination
                const $paginationContainer = $('#course-table-pagination-container');
                $paginationContainer.empty();

                if (pageInfo.pages > 1) {
                    let paginationHtml = '<ul class="pagination pagination-sm mb-0">';
                    const prevDisabled = pageInfo.page === 0 ? 'disabled' : '';
                    paginationHtml += `
                        <li class="page-item ${prevDisabled}">
                            <a class="page-link" href="javascript:void(0);" data-page="${pageInfo.page - 1}">
                                <i class="ti ti-chevron-left"></i>
                            </a>
                        </li>
                    `;

                    for (let p = 0; p < pageInfo.pages; p++) {
                        const active = p === pageInfo.page ? 'active' : '';
                        paginationHtml += `
                            <li class="page-item ${active}">
                                <a class="page-link" href="javascript:void(0);" data-page="${p}">${p + 1}</a>
                            </li>
                        `;
                    }

                    const nextDisabled = pageInfo.page === (pageInfo.pages - 1) ? 'disabled' : '';
                    paginationHtml += `
                        <li class="page-item ${nextDisabled}">
                            <a class="page-link" href="javascript:void(0);" data-page="${pageInfo.page + 1}">
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
        $(document).on('click', '#course-table-pagination-container .page-link', function (e) {
            e.preventDefault();
            const page = $(this).data('page');
            if (page !== undefined && page >= 0) {
                courseTable.page(page).draw('page');
            }
        });

        // Entries select handler
        $('#course-table-entries-select').on('change', function () {
            const length = parseInt($(this).val(), 10) || defaultEntries;
            courseTable.page.len(length).draw();
        });

        // Quick filter triggers
        $('#btn-apply-course-filter').on('click', function () {
            courseTable.ajax.reload();
        });

        $('#course-participant-input').on('keyup', function (e) {
            if (e.key === 'Enter') {
                courseTable.ajax.reload();
            }
        });

        $('#course-type-select, #course-status-select, #course-year-select').on('change', function () {
            courseTable.ajax.reload();
        });

        // More Filters Modal: Apply
        $('#btn-apply-modal-course-filters').on('click', function () {
            updateActiveFilterBadge();
            const modalEl = document.getElementById('moreCourseFiltersModal');
            if (modalEl) {
                const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modalInstance.hide();
            }
            courseTable.ajax.reload();
        });

        // More Filters Modal: Reset (resets advanced modal filters only)
        $('#btn-reset-modal-course-filters').on('click', function () {
            $('#modal-course-grade').val('all');
            $('#modal-course-job-position').val('');
            $('#modal-course-team').val('all');
            $('#modal-course-department').val('all');
            $('#modal-course-company').val('all');
            $('#cprAll').prop('checked', true);
            $('#modal-course-hours-min, #modal-course-hours-max').val('');
            $('#modal-course-score-min, #modal-course-score-max').val('');
            $('#certAll').prop('checked', true);
            $('#attAll').prop('checked', true);
            $('#modal-course-attendance-reason').val('');
            $('#bondAll').prop('checked', true);
            $('#modal-course-bond-from, #modal-course-bond-to').val('');
            $('#modal-course-bond-amount-min, #modal-course-bond-amount-max').val('');
            $('#modal-course-l1').val('');
            $('#modal-course-l2-num-min, #modal-course-l2-num-max').val('');
            $('#modal-course-l2-verbal').val('');
            $('#modal-course-l3-num-min, #modal-course-l3-num-max').val('');
            $('#modal-course-l3-verbal').val('');
            $('#modal-course-pic').val('');

            updateActiveFilterBadge();
        });

        // Main Reset: Clears quick filters AND advanced filters, resets badge, reloads table
        $('#btn-reset-course-filter').on('click', function () {
            $('#course-participant-input').val('');
            $('#course-type-select').val('all');
            $('#course-status-select').val('all');
            $('#course-year-select').val('all');

            // Reset modal advanced filters
            $('#btn-reset-modal-course-filters').trigger('click');

            courseTable.ajax.reload();
        });

        // Export Click Handlers (forwarding identical active filters)
        $('#btn-export-course-excel').on('click', function () {
            window.location.href = exportExcelBaseUrl + getCourseFilterQueryString();
        });

        $('#btn-export-course-csv').on('click', function () {
            window.location.href = exportCsvBaseUrl + getCourseFilterQueryString();
        });

        $('#btn-export-course-pdf').on('click', function () {
            window.location.href = exportPdfBaseUrl + getCourseFilterQueryString();
        });

        // Offline Training Detail Modal Logic
        const detailModalEl = document.getElementById('offlineTrainingDetailModal');
        let detailModal = null;
        if (detailModalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            detailModal = new bootstrap.Modal(detailModalEl);
        }

        $(document).on('click', '.btn-view-offline-detail', function () {
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
                success: function (res) {
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
                            statusBadge.removeClass('bg-warning-lt bg-secondary-lt').addClass('bg-success-lt').text('FINISHED');
                        } else if (progress > 0) {
                            statusBadge.removeClass('bg-success-lt bg-secondary-lt').addClass('bg-warning-lt').text(`ONGOING (${Math.round(progress)}%)`);
                        } else {
                            statusBadge.removeClass('bg-success-lt bg-warning-lt').addClass('bg-secondary-lt').text('NOT STARTED');
                        }

                        // Section 1: Course Info
                        $('#modal-detail-batch').text(d.batch || '-');
                        $('#modal-detail-category').text(d.category || '-');
                        $('#modal-detail-level').text(d.level || '-');
                        $('#modal-detail-sec-type').text(d.type || '-');

                        // Section 2: Classification & KPI
                        $('#modal-detail-main-class').text(d.main_classification || '-');
                        $('#modal-detail-sub-class').text(d.sub_classification || '-');
                        $('#modal-detail-corporate-kpi').text(d.counted_corporate_kpi ? 'Yes' : (d.counted_corporate_kpi === false ? 'No' : '-'));
                        $('#modal-detail-sub-corporate-kpi').text(d.sub_corporate_kpi || '-');

                        // Section 3: Delivery & Provider
                        $('#modal-detail-delivery-method').text(d.delivery_method || '-');
                        $('#modal-detail-sub-delivery-method').text(d.sub_delivery_method || '-');
                        $('#modal-detail-provider').text(d.provider || '-');
                        $('#modal-detail-sub-provider').text(d.sub_provider || '-');
                        $('#modal-detail-provider-name').text(d.provider_name || '-');
                        $('#modal-detail-venue').text(d.venue || '-');
                        $('#modal-detail-instructor').text(d.instructor || '-');

                        // Section 4: Schedule Duration & Program Evaluation
                        $('#modal-detail-start-date').text(d.start_date || '-');
                        $('#modal-detail-finish-date').text(d.finish_date || '-');
                        $('#modal-detail-duration-seconds').text(d.duration_seconds ? `${d.duration_seconds.toLocaleString()}s` : '-');
                        $('#modal-detail-course-hours').text(d.course_learning_hours ? `${parseFloat(d.course_learning_hours).toFixed(1)} Hours` : '-');

                        // Section 5: Participant & Job Info
                        $('#modal-detail-employee-id').text(d.employee_id || '-');
                        $('#modal-detail-participant-name').text(d.participant_name || d.user_name || '-');
                        $('#modal-detail-grade').text(d.grade || '-');
                        $('#modal-detail-job-position').text(d.job_position || '-');
                        $('#modal-detail-team').text(d.team || '-');
                        $('#modal-detail-department').text(d.department || '-');
                        $('#modal-detail-company').text(d.company || '-');

                        // Section 6: Progress & Evaluation
                        $('#modal-detail-progress').text(`${progress.toFixed(1)}%`);
                        $('#modal-detail-learning-hours').text(d.participant_learning_hours ? `${parseFloat(d.participant_learning_hours).toFixed(1)} Hours` : '-');
                        $('#modal-detail-assessment-score').text(d.assessment_score !== null && d.assessment_score !== undefined ? parseFloat(d.assessment_score).toFixed(1) : '-');

                        if (d.attendance === null || d.attendance === undefined) {
                            $('#modal-detail-attendance').text('-');
                        } else {
                            $('#modal-detail-attendance').html(d.attendance
                                ? '<span class="badge bg-success-lt text-success">Present</span>'
                                : '<span class="badge bg-danger-lt text-danger">Absent</span>'
                            );
                        }

                        $('#modal-detail-attendance-reason').text(d.attendance_reason || '-');
                        $('#modal-detail-certificate').text(d.certificate || '-');

                        // Section 7: Service Bond & L1-L3
                        $('#modal-detail-bond-status').text(d.bond_status || 'No');
                        $('#modal-detail-bond-end-date').text(d.bond_end_date || '-');
                        $('#modal-detail-bond-amount').text(d.bond_amount ? `Rp ${parseFloat(d.bond_amount).toLocaleString('id-ID')}` : '-');
                        $('#modal-detail-l1-program').text(d.l1_program || '-');
                        $('#modal-detail-l2-numerical').text(d.l2_numerical !== null && d.l2_numerical !== undefined ? parseFloat(d.l2_numerical).toFixed(1) : '-');
                        $('#modal-detail-l2-verbal').text(d.l2_verbal || '-');
                        $('#modal-detail-l3-numerical').text(d.l3_numerical !== null && d.l3_numerical !== undefined ? parseFloat(d.l3_numerical).toFixed(1) : '-');
                        $('#modal-detail-l3-verbal').text(d.l3_verbal || '-');
                        $('#modal-detail-pic').text(d.pic || '-');

                        $('#offline-detail-content').fadeIn(150);
                    } else {
                        $('#offlineTrainingDetailModalBody').html('<div class="py-5 text-center text-danger">Failed to load record details.</div>');
                    }
                },
                error: function () {
                    $('#offline-detail-loading').hide();
                    $('#offlineTrainingDetailModalBody').html('<div class="py-5 text-center text-danger">An error occurred while loading record details.</div>');
                }
            });
        });

        // Delete Record Modal Logic
        const deleteModalEl = document.getElementById('deleteOfflineRecordModal');
        let deleteModal = null;
        if (deleteModalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            deleteModal = new bootstrap.Modal(deleteModalEl);
        }

        $(document).on('click', '.btn-delete-offline-record', function () {
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

        $('#btn-confirm-delete-offline-record').on('click', function () {
            const id = $(this).data('id');
            if (!id) return;

            const $btn = $(this);
            const originalHtml = $btn.html();
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Deleting...');

            $.ajax({
                url: `/manage/record/offline/${id}`,
                type: 'DELETE',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                },
                success: function (res) {
                    $btn.prop('disabled', false).html(originalHtml);
                    if (deleteModal) {
                        deleteModal.hide();
                    } else {
                        $('#deleteOfflineRecordModal').modal('hide');
                    }

                    // Update summary KPI cards if returned
                    if (res && res.course_summary) {
                        $('#kpi-total-participants').text(Number(res.course_summary.total_participants).toLocaleString());
                        $('#kpi-finished').text(Number(res.course_summary.finished).toLocaleString());
                        $('#kpi-ongoing').text(Number(res.course_summary.ongoing).toLocaleString());
                        $('#kpi-progress').text(res.course_summary.avg_progress + '%');
                    }

                    courseTable.ajax.reload(null, false);
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).html(originalHtml);
                    const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to delete record.';
                    alert(msg);
                }
            });
        });
    });
</script>
@endsection
