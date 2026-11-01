@extends('layouts.sidebar-master')

@section('content')
<div class="page-body manage-record-page">
    <div class="container-xl">
        <!-- TOMBOL BACK RATA KIRI ATAS -->
        <div class="mb-3">
            <a href="{{ route('manage.record.offline') }}" class="btn-back d-inline-flex align-items-center gap-1 text-decoration-none fw-medium">
                <i class="ti ti-arrow-left"></i>
                <span>Back to Non KP OWL</span>
            </a>
        </div>

        <!-- SECTION HEADER: Classification Detail -->
        <div class="card border-0 shadow-sm p-3 p-md-4 mb-4 rounded-3 bg-white record-section-header">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 record-section-icon"
                         style="width: 54px; height: 54px; background-color: #fef3c7; color: #d97706; border-radius: 12px;">
                        <i class="ti ti-certificate fs-1"></i>
                    </div>
                    <div>
                        <h2 class="h3 fw-bold mb-1 text-dark">Classification: {{ $classificationName }}</h2>
                        <p class="text-muted small mb-0">Monitor offline training records and participant progress for this classification.</p>
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
                <form id="offline-filter-form" class="w-100" onsubmit="return false;">
                    <!-- Hidden inputs from More Filters Modal to preserve in datatable & export queries -->
                    <input type="hidden" id="filter-from-date" name="from_date" value="{{ request('from_date') ?? request('date_from') }}">
                    <input type="hidden" id="filter-to-date" name="to_date" value="{{ request('to_date') ?? request('date_to') }}">
                    <input type="hidden" id="filter-progress-range" name="progress_range" value="{{ request('progress_range', 'all') }}">
                    <input type="hidden" id="filter-duration" name="duration" value="{{ request('duration', 'all') }}">
                    <input type="hidden" id="filter-attendance" name="attendance" value="{{ request('attendance', 'all') }}">
                    <input type="hidden" id="filter-score-min" name="score_min" value="{{ request('score_min') }}">
                    <input type="hidden" id="filter-score-max" name="score_max" value="{{ request('score_max') }}">
                    <input type="hidden" id="filter-hours-min" name="hours_min" value="{{ request('hours_min') ?? request('learning_hours_min') }}">
                    <input type="hidden" id="filter-hours-max" name="hours_max" value="{{ request('hours_max') ?? request('learning_hours_max') }}">

                    <div class="d-flex align-items-center gap-2 flex-wrap w-100">
                        <!-- Employee Search -->
                        <div class="position-relative flex-grow-1" style="min-width: 180px;">
                            <input type="text" id="offline-employee-input" class="form-control rounded-3 w-100"
                                   placeholder="Search employee or NIP..." autocomplete="off"
                                   value="{{ request('employee_id') ?? request('employee') }}">
                        </div>

                        <!-- Learning Title / Course Name Search -->
                        <div class="position-relative flex-grow-1" style="min-width: 200px;">
                            <input type="text" id="offline-course-input" class="form-control rounded-3 w-100"
                                   placeholder="Search learning title..." autocomplete="off"
                                   value="{{ request('course_name') ?? (request('learning_title') ?? request('title')) }}">
                        </div>

                        <!-- Type Dropdown -->
                        <div class="flex-shrink-0" style="min-width: 125px;">
                            <select class="form-select rounded-3" id="offline-type-select">
                                <option value="all" {{ request('type', 'all') === 'all' ? 'selected' : '' }}>Type: All</option>
                                @foreach ($trainingTypes as $type)
                                    <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Status Dropdown -->
                        <div class="flex-shrink-0" style="min-width: 120px;">
                            <select class="form-select rounded-3" id="offline-status-select">
                                <option value="all" {{ request('status', 'all') === 'all' ? 'selected' : '' }}>All Status</option>
                                <option value="finished" {{ in_array(request('status'), ['finished', 'finish', '1']) ? 'selected' : '' }}>Finished</option>
                                <option value="ongoing" {{ in_array(request('status'), ['ongoing', '0']) ? 'selected' : '' }}>Ongoing</option>
                                <option value="not_started" {{ in_array(request('status'), ['not_started', 'not-started']) ? 'selected' : '' }}>Not Started</option>
                            </select>
                        </div>

                        <!-- Year Dropdown -->
                        <div class="flex-shrink-0" style="min-width: 105px;">
                            <select class="form-select rounded-3" id="offline-year-select">
                                <option value="all" {{ request('year', 'all') === 'all' ? 'selected' : '' }}>All Year</option>
                                @foreach ($years as $year)
                                    <option value="{{ $year }}" {{ (string) request('year') === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- 1. Icon Button: Apply Filter -->
                        <button type="button" id="btn-apply-offline-filter" class="btn btn-primary btn-icon px-3 flex-shrink-0"
                                style="background-color: #003b6d; border-color: #003b6d;" data-bs-toggle="tooltip" data-bs-title="Apply Filter" title="Apply Filter">
                            <i class="ti ti-search fs-2"></i>
                        </button>

                        <!-- 2. Icon Button: More Filters -->
                        <button type="button" id="btn-more-offline-filters" class="btn btn-outline-primary btn-icon px-3 position-relative btn-more-filters flex-shrink-0"
                                data-bs-toggle="modal" data-bs-target="#moreOfflineFiltersModal" title="More Filters">
                            <i class="ti ti-adjustments-horizontal fs-2"></i>
                            <span id="activeFilterBadge" class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle d-none">0</span>
                        </button>

                        <!-- 3. Icon Button: Reset -->
                        <button type="button" id="btn-reset-offline-filter" class="btn btn-outline-secondary btn-icon px-3 btn-quick-reset flex-shrink-0"
                                data-bs-toggle="tooltip" data-bs-title="Reset Filters" title="Reset Filters">
                            <i class="ti ti-refresh fs-2"></i>
                        </button>

                        <!-- 4. Download Dropdown (Excel, CSV, PDF Landscape) -->
                        <div class="dropdown flex-shrink-0">
                            <button class="btn btn-success dropdown-toggle rounded-3 px-3 btn-download d-inline-flex align-items-center gap-1"
                                    type="button" id="btn-download-export" data-bs-toggle="dropdown" aria-expanded="false" title="Export Records">
                                <i class="ti ti-download me-1"></i>
                                <span class="btn-download-text">Download</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 py-2" aria-labelledby="btn-download-export" style="min-width: 220px; z-index: 1060;">
                                <li>
                                    <h6 class="dropdown-header text-uppercase text-muted fw-bold small mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                        Export Options
                                    </h6>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2 export-format-btn" href="javascript:void(0);" data-format="xlsx" id="btn-export-classification-excel">
                                        <i class="ti ti-file-spreadsheet text-success fs-2"></i>
                                        <div>
                                            <div class="fw-semibold">Excel (.xlsx)</div>
                                            <div class="small text-muted" style="font-size: 0.72rem;">Full dataset spreadsheet</div>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2 export-format-btn" href="javascript:void(0);" data-format="csv" id="btn-export-classification-csv">
                                        <i class="ti ti-file-text text-primary fs-2"></i>
                                        <div>
                                            <div class="fw-semibold">CSV (.csv)</div>
                                            <div class="small text-muted" style="font-size: 0.72rem;">Standard UTF-8 tabular data</div>
                                        </div>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider my-1"></li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2 export-format-btn" href="javascript:void(0);" data-format="pdf" id="btn-export-classification-pdf">
                                        <i class="ti ti-file-type-pdf text-danger fs-2"></i>
                                        <div>
                                            <div class="fw-semibold">PDF Landscape (.pdf)</div>
                                            <div class="small text-muted" style="font-size: 0.72rem;">Printable A3 landscape report</div>
                                        </div>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- SERVER-SIDE DATATABLE CARD -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive offline-table-responsive">
                    <table id="offline-classification-table" class="table table-vcenter table-striped card-table">
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
                                <th class="text-center" style="min-width: 120px;">SCORE (L1) - COURSE</th>
                                <th class="text-center" style="min-width: 105px;">SCORE (L2)</th>
                                <th class="text-center" style="min-width: 105px;">SCORE (L3)</th>
                                <th class="text-center" style="min-width: 130px;">CERTIFICATE AVAILABILITY</th>
                                <th style="min-width: 130px;">PERSON IN CHARGE (PIC)</th>
                                <th class="text-center" style="min-width: 120px;">TOTAL ENGAGEMENT TIME (LH)</th>
                                <th class="text-center" style="min-width: 60px; width: 60px;">ACTION</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="card-footer d-flex flex-wrap align-items-center justify-content-between p-3 border-top">
                    <div class="d-flex align-items-center gap-2">
                        <select class="form-select form-select-sm w-auto rounded-3" id="offline-table-entries-select">
                            <option value="10" {{ $entries == 10 ? 'selected' : '' }}>10 Entries</option>
                            <option value="25" {{ $entries == 25 || empty($entries) ? 'selected' : '' }}>25 Entries</option>
                            <option value="50" {{ $entries == 50 ? 'selected' : '' }}>50 Entries</option>
                            <option value="100" {{ $entries == 100 ? 'selected' : '' }}>100 Entries</option>
                        </select>
                        <div id="offline-table-info-container"></div>
                    </div>
                    <div id="offline-table-pagination-container"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MORE FILTERS MODAL -->
<div class="modal fade" id="moreOfflineFiltersModal" tabindex="-1" aria-labelledby="moreOfflineFiltersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <form id="more-offline-filters-modal-form" onsubmit="return false;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="moreOfflineFiltersModalLabel">
                        <i class="ti ti-adjustments-horizontal text-primary"></i> More Filters
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <!-- Date Range (from_date & to_date) -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small"><i class="ti ti-calendar me-1"></i> Date Range (Training Date)</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small text-muted mb-1">From Date</label>
                                <input type="text" id="modal-from-date" name="from_date" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'" class="form-control rounded-3" placeholder="dd/mm/yyyy" value="{{ request('from_date') ?? request('date_from') }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted mb-1">To Date</label>
                                <input type="text" id="modal-to-date" name="to_date" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'" class="form-control rounded-3" placeholder="dd/mm/yyyy" value="{{ request('to_date') ?? request('date_to') }}">
                            </div>
                        </div>
                    </div>

                    <!-- Progress Range (progress_range) -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small d-block"><i class="ti ti-chart-bar me-1"></i> Progress Range</label>
                        @php $currProgress = request('progress_range', 'all'); @endphp
                        <div class="d-flex flex-wrap gap-3 align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_progress_range" id="prAll" value="all" {{ ($currProgress === 'all' || blank($currProgress)) ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="prAll">All</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_progress_range" id="pr0" value="0" {{ $currProgress === '0' ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="pr0">0%</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_progress_range" id="pr25" value="1-25" {{ $currProgress === '1-25' ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="pr25">1 - 25%</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_progress_range" id="pr50" value="26-50" {{ $currProgress === '26-50' ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="pr50">26 - 50%</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_progress_range" id="pr75" value="51-75" {{ $currProgress === '51-75' ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="pr75">51 - 75%</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_progress_range" id="pr99" value="76-99" {{ $currProgress === '76-99' ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="pr99">76 - 99%</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_progress_range" id="pr100" value="100" {{ $currProgress === '100' ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="pr100">100%</label>
                            </div>
                        </div>
                    </div>

                    <!-- Duration (duration) -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small d-block"><i class="ti ti-clock me-1"></i> Duration</label>
                        @php $currDur = request('duration', 'all'); @endphp
                        <div class="d-flex flex-wrap gap-3 align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_duration" id="durAll" value="all" {{ ($currDur === 'all' || blank($currDur)) ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="durAll">All Durations</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_duration" id="dur10" value="lt10" {{ in_array($currDur, ['lt10', 'under_10', '< 10 Minutes']) ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="dur10">&lt; 10 Minutes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_duration" id="dur30" value="10-30" {{ in_array($currDur, ['10-30', '10_30', '10 - 30 Minutes']) ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="dur30">10 - 30 Minutes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_duration" id="dur60" value="30-60" {{ in_array($currDur, ['30-60', '30_60', '30 - 60 Minutes']) ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="dur60">30 - 60 Minutes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_duration" id="durAbove60" value="gt60" {{ in_array($currDur, ['gt60', 'above_60', '> 1 Hour']) ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="durAbove60">&gt; 1 Hour</label>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small d-block"><i class="ti ti-user-check me-1"></i> Attendance</label>
                        @php $currAtt = request('attendance', 'all'); @endphp
                        <div class="d-flex flex-wrap gap-3 align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_attendance" id="attAll" value="all" {{ ($currAtt === 'all' || blank($currAtt)) ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="attAll">All</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_attendance" id="attPresent" value="present" {{ in_array($currAtt, ['present', '1']) ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="attPresent">Present</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_attendance" id="attAbsent" value="absent" {{ in_array($currAtt, ['absent', '0']) ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="attAbsent">Absent</label>
                            </div>
                        </div>
                    </div>

                    <!-- Assessment Score & Learning Hours Range in 2 Columns -->
                    <div class="row g-3">
                        <!-- Assessment Score Range -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small"><i class="ti ti-award me-1"></i> Assessment Score</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" step="0.1" id="modal-score-min" name="score_min" class="form-control rounded-3" placeholder="Min Score" value="{{ request('score_min') }}">
                                </div>
                                <div class="col-6">
                                    <input type="number" step="0.1" id="modal-score-max" name="score_max" class="form-control rounded-3" placeholder="Max Score" value="{{ request('score_max') }}">
                                </div>
                            </div>
                        </div>

                        <!-- Learning Hours Range -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small"><i class="ti ti-hourglass-empty me-1"></i> Learning Hours</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" step="0.1" id="modal-hours-min" name="hours_min" class="form-control rounded-3" placeholder="Min Hours" value="{{ request('hours_min') ?? request('learning_hours_min') }}">
                                </div>
                                <div class="col-6">
                                    <input type="number" step="0.1" id="modal-hours-max" name="hours_max" class="form-control rounded-3" placeholder="Max Hours" value="{{ request('hours_max') ?? request('learning_hours_max') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light d-flex justify-content-end align-items-center gap-2">
                    <button type="button" id="btn-modal-reset" class="btn btn-outline-secondary px-3 d-inline-flex align-items-center gap-1" data-bs-dismiss="modal">
                        <i class="ti ti-rotate me-1"></i>
                        <span>Reset</span>
                    </button>
                    <button type="button" id="btn-modal-apply" class="btn btn-primary px-3 d-inline-flex align-items-center gap-1" style="background-color: #0c5b9e; border-color: #0c5b9e;" data-bs-dismiss="modal">
                        <i class="ti ti-check me-1"></i>
                        <span>Apply Filter</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('main.record.components.offline-detail-modal')

<!-- DELETE CONFIRMATION MODAL -->
@include('main.record.components.offline-delete-modal')
@include('main.record.components.more-information-modal')
@include('main.record.components.offline-import-modal')

@endsection

@section('style')
<link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">

<style>
        /* IMPORT MODAL STYLING */
    #offlineImportModal .modal-content {
        border-radius: 16px;
        overflow: hidden;
    }
    #offlineImportModal .bg-amber-subtle {
        background-color: #fef3c7 !important;
    }
    #offlineImportModal .border-primary {
        border-color: #d97706 !important;
    }
    [data-bs-theme="dark"] #offlineImportModal .modal-content,
    body.dark-mode #offlineImportModal .modal-content,
    body.theme-dark #offlineImportModal .modal-content {
        background-color: #1e293b;
        color: #f1f5f9;
        border-color: #334155;
    }
    [data-bs-theme="dark"] #offlineImportModal .bg-light,
    body.dark-mode #offlineImportModal .bg-light,
    body.theme-dark #offlineImportModal .bg-light {
        background-color: #0f172a !important;
    }

    .manage-record-page {
        background: #fff;
    }

    [data-bs-theme="dark"] .manage-record-page,
    body.dark-mode .manage-record-page,
    body.theme-dark     /* IMPORT MODAL STYLING */
    #offlineImportModal .modal-content {
        border-radius: 16px;
        overflow: hidden;
    }
    #offlineImportModal .bg-amber-subtle {
        background-color: #fef3c7 !important;
    }
    #offlineImportModal .border-primary {
        border-color: #d97706 !important;
    }
    [data-bs-theme="dark"] #offlineImportModal .modal-content,
    body.dark-mode #offlineImportModal .modal-content,
    body.theme-dark #offlineImportModal .modal-content {
        background-color: #1e293b;
        color: #f1f5f9;
        border-color: #334155;
    }
    [data-bs-theme="dark"] #offlineImportModal .bg-light,
    body.dark-mode #offlineImportModal .bg-light,
    body.theme-dark #offlineImportModal .bg-light {
        background-color: #0f172a !important;
    }

    .manage-record-page {
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

    #offline-classification-table {
        font-size: 13px;
        width: max-content !important;
        min-width: 100% !important;
        margin-bottom: 0;
    }

    #offline-classification-table thead th {
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

    #offline-classification-table tbody td {
        vertical-align: middle;
        padding: 10px 14px;
        white-space: nowrap !important;
    }

    [data-bs-theme="dark"] #offline-classification-table thead th,
    body.dark-mode #offline-classification-table thead th,
    body.theme-dark #offline-classification-table thead th {
        background: #1e293b !important;
        color: #94a3b8 !important;
        border-bottom-color: #334155 !important;
    }

    [data-bs-theme="dark"] #offline-classification-table tbody td,
    body.dark-mode #offline-classification-table tbody td,
    body.theme-dark #offline-classification-table tbody td {
        border-bottom-color: #334155 !important;
        color: #e2e8f0 !important;
    }

    #moreOfflineFiltersModal {
        z-index: 1060 !important;
    }

    /* Modal dark mode styling */
    [data-bs-theme="dark"] #offlineTrainingDetailModal .modal-content,
    [data-bs-theme="dark"] #moreOfflineFiltersModal .modal-content,
    [data-bs-theme="dark"] #deleteOfflineRecordModal .modal-content,
    body.dark-mode #offlineTrainingDetailModal .modal-content,
    body.dark-mode #moreOfflineFiltersModal .modal-content,
    body.dark-mode #deleteOfflineRecordModal .modal-content,
    body.theme-dark #offlineTrainingDetailModal .modal-content,
    body.theme-dark #moreOfflineFiltersModal .modal-content,
    body.theme-dark #deleteOfflineRecordModal .modal-content {
        background-color: var(--tblr-bg-surface, #1f2937) !important;
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] #offlineTrainingDetailModal .bg-light,
    [data-bs-theme="dark"] #moreOfflineFiltersModal .bg-light,
    [data-bs-theme="dark"] #deleteOfflineRecordModal .bg-light,
    body.dark-mode #offlineTrainingDetailModal .bg-light,
    body.dark-mode #moreOfflineFiltersModal .bg-light,
    body.dark-mode #deleteOfflineRecordModal .bg-light,
    body.theme-dark #offlineTrainingDetailModal .bg-light,
    body.theme-dark #moreOfflineFiltersModal .bg-light,
    body.theme-dark #deleteOfflineRecordModal .bg-light {
        background-color: #111827 !important;
    }

    [data-bs-theme="dark"] #offlineTrainingDetailModal .text-dark,
    [data-bs-theme="dark"] #moreOfflineFiltersModal .text-dark,
    [data-bs-theme="dark"] #deleteOfflineRecordModal .text-dark,
    body.dark-mode #offlineTrainingDetailModal .text-dark,
    body.dark-mode #moreOfflineFiltersModal .text-dark,
    body.dark-mode #deleteOfflineRecordModal .text-dark,
    body.theme-dark #offlineTrainingDetailModal .text-dark,
    body.theme-dark #moreOfflineFiltersModal .text-dark,
    body.theme-dark #deleteOfflineRecordModal .text-dark {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] #moreOfflineFiltersModal .form-control,
    [data-bs-theme="dark"] #moreOfflineFiltersModal .form-select,
    body.dark-mode #moreOfflineFiltersModal .form-control,
    body.dark-mode #moreOfflineFiltersModal .form-select,
    body.theme-dark #moreOfflineFiltersModal .form-control,
    body.theme-dark #moreOfflineFiltersModal .form-select {
        background-color: #111827 !important;
        color: #f8fafc !important;
        border-color: #374151 !important;
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
    #btn-more-offline-filters {
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
    #btn-more-offline-filters:hover,
    #btn-more-offline-filters:focus,
    #btn-more-offline-filters:active {
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
    #btn-more-offline-filters:hover i,
    #btn-more-offline-filters:focus i,
    #btn-more-offline-filters:active i {
        color: #ffffff !important;
    }

    [data-bs-theme="dark"] .btn-more-filters,
    [data-bs-theme="dark"] #btn-more-filters,
    [data-bs-theme="dark"] #btn-more-offline-filters,
    body.dark-mode .btn-more-filters,
    body.dark-mode #btn-more-filters,
    body.dark-mode #btn-more-offline-filters,
    body.theme-dark .btn-more-filters,
    body.theme-dark #btn-more-filters,
    body.theme-dark #btn-more-offline-filters {
        background-color: transparent !important;
        border-color: #3b82f6 !important;
        color: #60a5fa !important;
    }
    [data-bs-theme="dark"] .btn-more-filters:hover,
    [data-bs-theme="dark"] #btn-more-filters:hover,
    [data-bs-theme="dark"] #btn-more-offline-filters:hover,
    body.dark-mode .btn-more-filters:hover,
    body.dark-mode #btn-more-filters:hover,
    body.dark-mode #btn-more-offline-filters:hover,
    body.theme-dark .btn-more-filters:hover,
    body.theme-dark #btn-more-filters:hover,
    body.theme-dark #btn-more-offline-filters:hover {
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
    .participant-link:hover,
    .course-link:hover {
        text-decoration: underline !important;
    }
    /* Offline Table Pagination Styling */
    #offline-table-pagination-container {
        display: flex;
        justify-content: flex-end;
        align-items: center;
    }
    #offline-table-pagination-container .pagination {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: 4px;
        width: auto;
        margin: 0;
    }
    #offline-table-pagination-container .page-item {
        flex: 0 0 auto;
    }
    #offline-table-pagination-container .page-link {
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
    #offline-table-pagination-container .page-link:hover {
        background-color: #f1f5f9;
        border-color: #cbd5e1;
        color: #1e293b;
    }
    #offline-table-pagination-container .page-item.active .page-link {
        background-color: #0c5b9e;
        border-color: #0c5b9e;
        color: #fff;
    }
    #offline-table-pagination-container .page-item.disabled .page-link {
        color: #94a3b8;
        background-color: #f8fafc;
        border-color: #e2e8f0;
        cursor: not-allowed;
        opacity: 0.65;
    }

    [data-bs-theme="dark"] #offline-table-pagination-container .page-link,
    body.dark-mode #offline-table-pagination-container .page-link,
    body.theme-dark #offline-table-pagination-container .page-link {
        background-color: #1e293b;
        border-color: #334155;
        color: #cbd5e1;
    }
    [data-bs-theme="dark"] #offline-table-pagination-container .page-link:hover,
    body.dark-mode #offline-table-pagination-container .page-link:hover,
    body.theme-dark #offline-table-pagination-container .page-link:hover {
        background-color: #334155;
        color: #fff;
    }
    [data-bs-theme="dark"] #offline-table-pagination-container .page-item.active .page-link,
    body.dark-mode #offline-table-pagination-container .page-item.active .page-link,
    body.theme-dark #offline-table-pagination-container .page-item.active .page-link {
        background-color: #0c5b9e;
        border-color: #0c5b9e;
        color: #fff;
    }
    [data-bs-theme="dark"] #offline-table-pagination-container .page-item.disabled .page-link,
    body.dark-mode #offline-table-pagination-container .page-item.disabled .page-link,
    body.theme-dark #offline-table-pagination-container .page-item.disabled .page-link {
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
    $(function () {
        const datatableUrl = @json(route('manage.record.offline.classification.datatable', $slug));
        const downloadBaseUrl = @json($downloadUrl);
        const classificationSlug = @json($slug);
        const defaultEntries = 25;

        let offlineTable = null;

        // Function to update active filter counter badge
        function updateActiveFilterBadge() {
            let activeCount = 0;
            if ($('#filter-from-date').val()) activeCount++;
            if ($('#filter-to-date').val()) activeCount++;
            if ($('#filter-progress-range').val() && $('#filter-progress-range').val() !== 'all') activeCount++;
            if ($('#filter-duration').val() && $('#filter-duration').val() !== 'all') activeCount++;
            if ($('#filter-attendance').val() && $('#filter-attendance').val() !== 'all') activeCount++;
            if ($('#filter-score-min').val()) activeCount++;
            if ($('#filter-score-max').val()) activeCount++;
            if ($('#filter-hours-min').val()) activeCount++;
            if ($('#filter-hours-max').val()) activeCount++;

            const badge = $('#activeFilterBadge');
            if (activeCount > 0) {
                badge.text(activeCount).removeClass('d-none');
            } else {
                badge.addClass('d-none');
            }
        }

        // Helper function to safely hide More Filters modal
        function hideMoreFiltersModal() {
            const modalEl = document.getElementById('moreOfflineFiltersModal');
            if (modalEl) {
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const modalInstance = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }
                if ($.fn.modal) {
                    $('#moreOfflineFiltersModal').modal('hide');
                }
            }
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css({ overflow: '', paddingRight: '' });
        }

        // Initialize badge on page load
        updateActiveFilterBadge();

        // Initialize DataTable
        if ($.fn.DataTable.isDataTable('#offline-classification-table')) {
            $('#offline-classification-table').DataTable().destroy();
        }

        offlineTable = $('#offline-classification-table').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            autoWidth: false,
            pageLength: defaultEntries,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [],
            dom: 't',
            ajax: {
                url: datatableUrl,
                type: 'GET',
                data: function (d) {
                    d.classification = classificationSlug;
                    d.employee_id = $('#offline-employee-input').val().trim();
                    d.course_name = $('#offline-course-input').val().trim();
                    d.type = $('#offline-type-select').val();
                    d.status = $('#offline-status-select').val();
                    d.year = $('#offline-year-select').val();

                    // More Filters
                    d.from_date = $('#filter-from-date').val();
                    d.to_date = $('#filter-to-date').val();
                    d.progress_range = $('#filter-progress-range').val();
                    d.duration = $('#filter-duration').val();
                    d.attendance = $('#filter-attendance').val();
                    d.score_min = $('#filter-score-min').val();
                    d.score_max = $('#filter-score-max').val();
                    d.hours_min = $('#filter-hours-min').val();
                    d.hours_max = $('#filter-hours-max').val();

                    d.entries = $('#offline-table-entries-select').val() || defaultEntries;
                    d.length = parseInt($('#offline-table-entries-select').val(), 10) || defaultEntries;
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                { data: 'employee_id', name: 'otu.employee_id', className: 'text-center' },
                { data: 'participant_name', name: 'otu.name', className: 'text-start' },
                { data: 'team', name: 'otu.team', className: 'text-start' },
                { data: 'department', name: 'otu.department', className: 'text-start' },
                { data: 'company', name: 'otu.company', className: 'text-start' },
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
                { data: 'type_badge', name: 'ot.type', className: 'text-center' },
                { data: 'category', name: 'ot.category', className: 'text-start' },
                { data: 'main_classification', name: 'ot.main_classification', className: 'text-start' },
                { data: 'sub_classification', name: 'ot.sub_classification', className: 'text-start' },
                { data: 'delivery_method', name: 'ot.delivery_method', className: 'text-center' },
                { data: 'sub_delivery_method', name: 'ot.sub_delivery_method', className: 'text-start' },
                { data: 'provider', name: 'ot.provider', className: 'text-center' },
                { data: 'provider_name', name: 'ot.provider_name', className: 'text-start' },
                { data: 'venue', name: 'ot.venue', className: 'text-start' },
                { data: 'course_duration', name: 'ot.duration_seconds', className: 'text-center' },
                { data: 'total_recognized_duration', name: 'total_recognized_duration', className: 'text-center', orderable: false },
                { data: 'course_learning_hours', name: 'ot.learning_hours', className: 'text-center' },
                { data: 'progress_percent', name: 'otu.progress', className: 'text-center' },
                { data: 'progress_status', name: 'otu.progress', className: 'text-center' },
                { data: 'date_merged', name: 'ot.start_date', className: 'text-center', orderable: false },
                { data: 'time_merged', name: 'ot.start_time', className: 'text-center', orderable: false },
                { data: 'record_month', name: 'ot.start_date', className: 'text-center', orderable: false },
                { data: 'requirement', name: 'ot.requirement', className: 'text-center' },
                { data: 'attendance_badge', name: 'otu.attendance', className: 'text-center' },
                { data: 'score_l1', name: 'otu.assessment_score', className: 'text-center' },
                { data: 'score_l2', name: 'otu.l2_numerical', className: 'text-center' },
                { data: 'score_l3', name: 'otu.l3_numerical', className: 'text-center' },
                { data: 'certificate_availability', name: 'otu.certificate', className: 'text-center' },
                { data: 'pic', name: 'otu.pic', className: 'text-start' },
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
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
            ],
            language: {
                processing: '<div class="py-4 text-center"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 text-muted">Loading offline training data...</div></div>',
                emptyTable: '<div class="py-5 text-center text-muted"><i class="ti ti-inbox fs-1 d-block mb-2 text-secondary"></i>No offline training records found for this classification.</div>'
            },
            drawCallback: function (settings) {
                const api = this.api();
                const pageInfo = api.page.info();
                const formatNum = (num) => (num || 0).toLocaleString();
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

                if (pageInfo.recordsTotal > 0 && pageInfo.recordsDisplay > 0) {
                    const start = pageInfo.start + 1;
                    const end = pageInfo.end;
                    const total = pageInfo.recordsDisplay;
                    $('#offline-table-info-container').html(
                        `<span class="text-muted small">Showing ${formatNum(start)} to ${formatNum(end)} of ${formatNum(total)} entries</span>`
                    );
                } else {
                    $('#offline-table-info-container').html('<span class="text-muted small">Showing 0 to 0 of 0 entries</span>');
                }

                // Render pagination
                const $paginationContainer = $('#offline-table-pagination-container');
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

                // Reinitialize Bootstrap tooltips for action buttons
                if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                    const tooltipTriggerList = [].slice.call(document.querySelectorAll('#offline-classification-table [data-bs-toggle="tooltip"]'));
                    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                        bootstrap.Tooltip.getOrCreateInstance(tooltipTriggerEl);
                    });
                }
            }
        });

        // Entries dropdown change
        $('#offline-table-entries-select').on('change', function () {
            const newLength = parseInt($(this).val(), 10);
            offlineTable.page.len(newLength).page(0).draw(false);
        });

        // Pagination click event
        $(document).on('click', '#offline-table-pagination-container .page-link', function (e) {
            e.preventDefault();
            const page = $(this).data('page');
            if (page !== undefined && !$(this).parent().hasClass('disabled') && !$(this).parent().hasClass('active')) {
                offlineTable.page(page).draw('page');
            }
        });

        // Quick Filter Apply & Input Listeners
        $('#btn-apply-offline-filter').on('click', function () {
            offlineTable.ajax.reload(null, true);
        });

        $('#offline-employee-input, #offline-course-input').on('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                offlineTable.ajax.reload(null, true);
            }
        });

        $('#offline-type-select, #offline-status-select, #offline-year-select').on('change', function () {
            offlineTable.ajax.reload(null, true);
        });

        // More Filters Modal: Apply
        $('#btn-modal-apply').on('click', function () {
            const fromDate = $('#modal-from-date').val() || '';
            const toDate = $('#modal-to-date').val() || '';
            const progressRange = $('input[name="modal_progress_range"]:checked').val() || 'all';
            const duration = $('input[name="modal_duration"]:checked').val() || 'all';
            const attendance = $('input[name="modal_attendance"]:checked').val() || 'all';
            const scoreMin = $('#modal-score-min').val() || '';
            const scoreMax = $('#modal-score-max').val() || '';
            const hoursMin = $('#modal-hours-min').val() || '';
            const hoursMax = $('#modal-hours-max').val() || '';

            $('#filter-from-date').val(fromDate);
            $('#filter-to-date').val(toDate);
            $('#filter-progress-range').val(progressRange);
            $('#filter-duration').val(duration);
            $('#filter-attendance').val(attendance);
            $('#filter-score-min').val(scoreMin);
            $('#filter-score-max').val(scoreMax);
            $('#filter-hours-min').val(hoursMin);
            $('#filter-hours-max').val(hoursMax);

            updateActiveFilterBadge();
            hideMoreFiltersModal();
            offlineTable.ajax.reload(null, true);
        });

        // More Filters Modal: Reset
        $('#btn-modal-reset').on('click', function () {
            $('#modal-from-date').val('');
            $('#modal-to-date').val('');
            $('input[name="modal_progress_range"][value="all"]').prop('checked', true);
            $('input[name="modal_duration"][value="all"]').prop('checked', true);
            $('input[name="modal_attendance"][value="all"]').prop('checked', true);
            $('#modal-score-min').val('');
            $('#modal-score-max').val('');
            $('#modal-hours-min').val('');
            $('#modal-hours-max').val('');

            $('#filter-from-date').val('');
            $('#filter-to-date').val('');
            $('#filter-progress-range').val('all');
            $('#filter-duration').val('all');
            $('#filter-attendance').val('all');
            $('#filter-score-min').val('');
            $('#filter-score-max').val('');
            $('#filter-hours-min').val('');
            $('#filter-hours-max').val('');

            updateActiveFilterBadge();
            hideMoreFiltersModal();
            offlineTable.ajax.reload(null, true);
        });

        // Quick Filter Reset (Clears Quick Filters + More Filters)
        $('#btn-reset-offline-filter').on('click', function () {
            // Reset Quick Filters
            $('#offline-employee-input').val('');
            $('#offline-course-input').val('');
            $('#offline-type-select').val('all');
            $('#offline-status-select').val('all');
            $('#offline-year-select').val('all');

            // Reset More Filters Modal Inputs
            $('#modal-from-date').val('');
            $('#modal-to-date').val('');
            $('input[name="modal_progress_range"][value="all"]').prop('checked', true);
            $('input[name="modal_duration"][value="all"]').prop('checked', true);
            $('input[name="modal_attendance"][value="all"]').prop('checked', true);
            $('#modal-score-min').val('');
            $('#modal-score-max').val('');
            $('#modal-hours-min').val('');
            $('#modal-hours-max').val('');

            // Reset Hidden Inputs
            $('#filter-from-date').val('');
            $('#filter-to-date').val('');
            $('#filter-progress-range').val('all');
            $('#filter-duration').val('all');
            $('#filter-attendance').val('all');
            $('#filter-score-min').val('');
            $('#filter-score-max').val('');
            $('#filter-hours-min').val('');
            $('#filter-hours-max').val('');

            updateActiveFilterBadge();
            offlineTable.ajax.reload(null, true);
        });

        // Build URL parameters representing all active filters
        function buildClassificationExportParams(format) {
            const params = new URLSearchParams();

            if (format) {
                params.append('format', format);
            }

            const empVal = $('#offline-employee-input').val().trim();
            if (empVal) params.append('employee_id', empVal);

            const courseVal = $('#offline-course-input').val().trim();
            if (courseVal) params.append('course_name', courseVal);

            const typeVal = $('#offline-type-select').val();
            if (typeVal && typeVal !== 'all') params.append('type', typeVal);

            const statusVal = $('#offline-status-select').val();
            if (statusVal && statusVal !== 'all') params.append('status', statusVal);

            const yearVal = $('#offline-year-select').val();
            if (yearVal && yearVal !== 'all') params.append('year', yearVal);

            const fromDate = $('#filter-from-date').val();
            if (fromDate) params.append('from_date', fromDate);

            const toDate = $('#filter-to-date').val();
            if (toDate) params.append('to_date', toDate);

            const progressRange = $('#filter-progress-range').val();
            if (progressRange && progressRange !== 'all') params.append('progress_range', progressRange);

            const duration = $('#filter-duration').val();
            if (duration && duration !== 'all') params.append('duration', duration);

            const attendance = $('#filter-attendance').val();
            if (attendance && attendance !== 'all') params.append('attendance', attendance);

            const scoreMin = $('#filter-score-min').val();
            if (scoreMin) params.append('score_min', scoreMin);

            const scoreMax = $('#filter-score-max').val();
            if (scoreMax) params.append('score_max', scoreMax);

            const hoursMin = $('#filter-hours-min').val();
            if (hoursMin) params.append('hours_min', hoursMin);

            const hoursMax = $('#filter-hours-max').val();
            if (hoursMax) params.append('hours_max', hoursMax);

            return params;
        }

        // Export Download Trigger Handler
        function triggerExportDownload(format) {
            const $btn = $('#btn-download-export');
            const $icon = $btn.find('i');
            const $text = $btn.find('.btn-download-text');
            const originalText = $text.text() || 'Download';
            const originalIconClass = $icon.attr('class') || 'ti ti-download me-1';

            // Visual feedback
            $btn.prop('disabled', true);
            $icon.attr('class', 'spinner-border spinner-border-sm me-1');
            $text.text('Exporting...');

            const params = buildClassificationExportParams(format);
            const downloadUrl = downloadBaseUrl + (downloadBaseUrl.indexOf('?') !== -1 ? '&' : '?') + params.toString();
            window.location.href = downloadUrl;

            setTimeout(function () {
                $btn.prop('disabled', false);
                $icon.attr('class', originalIconClass);
                $text.text(originalText);
            }, 2500);
        }

        // Dropdown Export Format Click Handler
        $(document).on('click', '.export-format-btn', function (e) {
            e.preventDefault();
            const format = $(this).data('format') || 'xlsx';
            triggerExportDownload(format);
        });

        // Detail Modal Logic
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
                            statusBadge.removeClass('bg-success-lt bg-secondary-lt').addClass('bg-warning-lt').text('ONGOING');
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

                        // Section 4: Schedule Duration & Program Evaluation
                        $('#modal-detail-start-date').text(d.start_date || '-');
                        $('#modal-detail-finish-date').text(d.finish_date || '-');
                        $('#modal-detail-duration-seconds').text(d.duration_seconds ? `${d.duration_seconds.toLocaleString()}s` : '-');
                        $('#modal-detail-course-hours').text(formatModalHours(d.course_learning_hours));

                        // Section 5: Participant & Job Info
                        $('#modal-detail-employee-id').text(d.employee_id || '-');
                        $('#modal-detail-participant-name').text(d.participant_name || d.user_name || '-');
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

                        $('#modal-detail-attendance').html(isAttYes
                            ? '<span class="badge bg-success-lt text-success">Yes</span>'
                            : '<span class="badge bg-danger-lt text-danger">No</span>'
                        );

                        if (d.attendance_reason) {
                            $('#modal-detail-attendance-reason').text(d.attendance_reason);
                            $('#modal-detail-reason-container').show();
                        } else {
                            $('#modal-detail-reason-container').hide();
                        }

                        $('#modal-detail-certificate').text(d.certificate || '-');
                        $('#modal-detail-l1-program').text(formatModalScore(d.l1_program));
                        $('#modal-detail-l2-num').text(formatModalScore(d.l2_numerical));
                        $('#modal-detail-l2-verbal').text(d.l2_verbal || '-');
                        const l3Num = formatModalScore(d.l3_numerical);
                        const l3Verb = d.l3_verbal || '-';
                        $('#modal-detail-l3-eval').text(`${l3Num} / ${l3Verb}`);

                        // Section 7: Service Bond & Administration
                        $('#modal-detail-bond-status').text(d.bond_status || '-');
                        $('#modal-detail-bond-end-date').text(d.bond_end_date || '-');
                        $('#modal-detail-bond-amount').text(d.bond_amount ? `Rp ${parseFloat(d.bond_amount).toLocaleString()}` : '-');
                        $('#modal-detail-pic').text(d.pic || '-');

                        $('#offline-detail-content').show();
                    }
                },
                error: function () {
                    $('#offline-detail-loading').html('<div class="text-danger small py-3">Failed to load training details.</div>');
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

            $('#delete-modal-employee-name').text(employee);
            $('#delete-modal-course-name').text(course);
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
                    offlineTable.ajax.reload(null, false);
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).html(originalHtml);
                    const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to delete record.';
                    alert(msg);
                }
            });
        });

        // Click event handler for .btn-more-info / More Information
        $(document).on('click', '.btn-more-info', function (e) {
            e.preventDefault();
            const recordId = $(this).data('id') || $(this).data('record-id') || $(this).attr('data-id') || $(this).attr('data-record-id');
            const source = ($(this).data('source') || $(this).data('record-source') || $(this).attr('data-source') || $(this).attr('data-record-source') || 'offline').toString().toLowerCase();
            if (!recordId) return;

            const modalEl = document.getElementById('moreInformationModal');
            if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }

            $('#more-info-loading').show();
            $('#more-info-content').hide();

            // Fetch the record detail via $.ajax() from /manage/record/detail/{id}
            $.ajax({
                url: '/manage/record/detail/' + encodeURIComponent(recordId) + (source ? '?source=' + encodeURIComponent(source) : ''),
                type: 'GET',
                dataType: 'json',
                success: function (response) {
                    $('#more-info-loading').hide();
                    $('#more-info-content').show();
                    if (window.populateMoreInformationModal) {
                        window.populateMoreInformationModal(response);
                    } else {
                        const d = response.data || response;
                        $('#modal-badge-source').text(d.source || 'NON KP OWL');
                        $('#modal-course-title').text(d.course_title || '-');
                        $('#modal-employee-id').text(d.employee_id || '-');
                        $('#modal-employee-name').text(d.employee_name || '-');
                        $('#modal-grade').text(d.grade || '-');
                        $('#modal-job-position').text(d.job_position || '-');
                        $('#modal-instructor').text(d.instructor || '-');
                        $('#modal-bond-status').text(d.bond_status || 'No');
                        $('#modal-bond-duration').text(d.bond_duration || '-');
                        $('#modal-bond-end-date').text(d.bond_end_date || '-');
                        $('#modal-bond-amount').text(d.bond_amount || '-');
                    }
                },
                error: function () {
                    $('#more-info-loading').hide();
                    $('#more-info-content').show();
                }
            });
        });
    });
</script>
@endsection
