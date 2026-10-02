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
                <form id="offline-filter-form" onsubmit="return false;">
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

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <!-- Employee Search -->
                        <div class="position-relative flex-grow-1" style="min-width: 200px; max-width: 260px;">
                            <input type="text" id="offline-employee-input" class="form-control rounded-3" 
                                   placeholder="Search employee or NIP..." autocomplete="off" 
                                   value="{{ request('employee_id') ?? request('employee') }}">
                        </div>

                        <!-- Learning Title / Course Name Search -->
                        <div class="position-relative flex-grow-1" style="min-width: 220px; max-width: 280px;">
                            <input type="text" id="offline-course-input" class="form-control rounded-3" 
                                   placeholder="Search learning title..." autocomplete="off" 
                                   value="{{ request('course_name') ?? (request('learning_title') ?? request('title')) }}">
                        </div>

                        <!-- Type Dropdown -->
                        <div style="min-width: 125px; flex-shrink: 0;">
                            <select class="form-select rounded-3" id="offline-type-select">
                                <option value="all" {{ request('type', 'all') === 'all' ? 'selected' : '' }}>Type: All</option>
                                @foreach ($trainingTypes as $type)
                                    <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Status Dropdown -->
                        <div style="min-width: 120px; flex-shrink: 0;">
                            <select class="form-select rounded-3" id="offline-status-select">
                                <option value="all" {{ request('status', 'all') === 'all' ? 'selected' : '' }}>All Status</option>
                                <option value="finished" {{ in_array(request('status'), ['finished', 'finish', '1']) ? 'selected' : '' }}>Finished</option>
                                <option value="ongoing" {{ in_array(request('status'), ['ongoing', '0']) ? 'selected' : '' }}>Ongoing</option>
                                <option value="not_started" {{ in_array(request('status'), ['not_started', 'not-started']) ? 'selected' : '' }}>Not Started</option>
                            </select>
                        </div>

                        <!-- Year Dropdown -->
                        <div style="min-width: 110px; flex-shrink: 0;">
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
                        <button type="button" id="btn-reset-offline-filter" class="btn btn-outline-secondary btn-icon px-3 flex-shrink-0" 
                                data-bs-toggle="tooltip" data-bs-title="Reset Filters" title="Reset Filters">
                            <i class="ti ti-refresh fs-2"></i>
                        </button>

                        <!-- 4. Button: Download Excel -->
                        <button type="button" id="btn-download-export" class="btn btn-success rounded-3 px-3 btn-download flex-shrink-0" title="Download Excel">
                            <i class="ti ti-download me-1"></i> Download
                        </button>
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
                                <th class="text-center" style="min-width: 105px;">STATUS</th>
                                <th class="text-center" style="min-width: 90px;">NIP</th>
                                <th style="min-width: 180px;">PARTICIPANT</th>
                                <th class="text-center" style="min-width: 75px;">GRADE</th>
                                <th style="min-width: 160px;">JOB POSITION</th>
                                <th style="min-width: 140px;">TEAM</th>
                                <th style="min-width: 160px;">DEPARTMENT</th>
                                <th style="min-width: 160px;">COMPANY</th>
                                <th style="min-width: 220px;">COURSE NAME</th>
                                <th style="min-width: 180px;">SUB COURSE NAME</th>
                                <th class="text-center" style="min-width: 100px;">TYPE</th>
                                <th style="min-width: 130px;">CATEGORY</th>
                                <th class="text-center" style="min-width: 90px;">LEVEL</th>
                                <th class="text-center" style="min-width: 85px;">BATCH</th>
                                <th style="min-width: 150px;">MAIN CLASSIFICATION</th>
                                <th style="min-width: 150px;">SUB CLASSIFICATION</th>
                                <th class="text-center" style="min-width: 110px;">COUNTED CORPORATE KPI</th>
                                <th style="min-width: 160px;">SUB CORPORATE KPI</th>
                                <th class="text-center" style="min-width: 120px;">DELIVERY METHOD</th>
                                <th style="min-width: 140px;">SUB DELIVERY METHOD</th>
                                <th class="text-center" style="min-width: 100px;">PROVIDER</th>
                                <th style="min-width: 120px;">SUB PROVIDER</th>
                                <th style="min-width: 160px;">PROVIDER NAME</th>
                                <th style="min-width: 140px;">VENUE</th>
                                <th style="min-width: 140px;">INSTRUCTOR</th>
                                <th class="text-center" style="min-width: 95px;">DURATION</th>
                                <th class="text-center" style="min-width: 110px;">TRAINING HOURS</th>
                                <th class="text-center" style="min-width: 105px;">START DATE</th>
                                <th class="text-center" style="min-width: 105px;">FINISH DATE</th>
                                <th class="text-center" style="min-width: 95px;">MONTH</th>
                                <th class="text-center" style="min-width: 115px;">PARTICIPANT HOURS</th>
                                <th class="text-center" style="min-width: 110px;">PROGRESS</th>
                                <th class="text-center" style="min-width: 80px;">SCORE</th>
                                <th class="text-center" style="min-width: 110px;">CERTIFICATE</th>
                                <th class="text-center" style="min-width: 105px;">ATTENDANCE</th>
                                <th style="min-width: 180px;">ATTENDANCE REASON</th>
                                <th class="text-center" style="min-width: 95px;">BOND STATUS</th>
                                <th class="text-center" style="min-width: 110px;">BOND END DATE</th>
                                <th class="text-end" style="min-width: 125px;">BOND AMOUNT</th>
                                <th class="text-center" style="min-width: 120px;">L1 PROGRAM</th>
                                <th class="text-center" style="min-width: 105px;">L2 NUMERICAL</th>
                                <th style="min-width: 140px;">L2 VERBAL</th>
                                <th class="text-center" style="min-width: 105px;">L3 NUMERICAL</th>
                                <th style="min-width: 160px;">L3 VERBAL</th>
                                <th style="min-width: 130px;">PIC</th>
                                <th class="text-center" style="min-width: 105px; width: 105px;">ACTION</th>
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
<div class="modal fade" id="deleteOfflineRecordModal" tabindex="-1" aria-labelledby="deleteOfflineRecordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-body text-center p-4">
                <div class="text-danger mb-3">
                    <span class="avatar avatar-lg bg-danger-lt rounded-circle">
                        <i class="ti ti-trash fs-1"></i>
                    </span>
                </div>
                <h4 class="modal-title fw-bold text-dark mb-2" id="deleteOfflineRecordModalLabel">Delete Training Record?</h4>
                <p class="text-muted small mb-3">
                    Are you sure you want to delete the record for <strong id="delete-modal-employee-name" class="text-dark"></strong> in <em id="delete-modal-course-name"></em>? This action will remove the participant from this training.
                </p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-secondary px-3 rounded-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger px-3 rounded-2 d-inline-flex align-items-center gap-1" id="btn-confirm-delete-offline-record">
                        <i class="ti ti-trash"></i>
                        <span>Delete</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

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

    /* Action buttons */
    .btn-icon {
        width: 30px;
        height: 30px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
    }

    .hover-underline:hover,
    .participant-link:hover,
    .course-link:hover {
        text-decoration: underline !important;
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
            order: [[29, 'desc']],
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
                { data: 'status_badge', name: 'otu.progress', className: 'text-center' },
                { data: 'employee_id', name: 'otu.employee_id', className: 'text-center' },
                { data: 'participant_name', name: 'otu.name', className: 'text-start' },
                { data: 'grade', name: 'otu.grade', className: 'text-center' },
                { data: 'job_position', name: 'otu.job_position', className: 'text-start' },
                { data: 'team', name: 'otu.team', className: 'text-start' },
                { data: 'department', name: 'otu.department', className: 'text-start' },
                { data: 'company', name: 'otu.company', className: 'text-start' },
                { data: 'course_name', name: 'ot.course_name', className: 'text-start' },
                { data: 'sub_course_name', name: 'ot.sub_course_name', className: 'text-start' },
                { data: 'type_badge', name: 'ot.type', className: 'text-center' },
                { data: 'category', name: 'ot.category', className: 'text-start' },
                { data: 'level', name: 'ot.level', className: 'text-center' },
                { data: 'batch', name: 'ot.batch', className: 'text-center' },
                { data: 'main_classification', name: 'ot.main_classification', className: 'text-start' },
                { data: 'sub_classification', name: 'ot.sub_classification', className: 'text-start' },
                { data: 'counted_corporate_kpi', name: 'ot.counted_corporate_kpi', className: 'text-center' },
                { data: 'sub_corporate_kpi', name: 'ot.sub_corporate_kpi', className: 'text-start' },
                { data: 'delivery_method', name: 'ot.delivery_method', className: 'text-center' },
                { data: 'sub_delivery_method', name: 'ot.sub_delivery_method', className: 'text-start' },
                { data: 'provider', name: 'ot.provider', className: 'text-center' },
                { data: 'sub_provider', name: 'ot.sub_provider', className: 'text-start' },
                { data: 'provider_name', name: 'ot.provider_name', className: 'text-start' },
                { data: 'venue', name: 'ot.venue', className: 'text-start' },
                { data: 'instructor', name: 'ot.instructor', className: 'text-start' },
                { data: 'duration', name: 'ot.duration_seconds', className: 'text-center font-monospace' },
                { data: 'course_learning_hours', name: 'ot.learning_hours', className: 'text-center' },
                { data: 'start_date', name: 'ot.start_date', className: 'text-center' },
                { data: 'finish_date', name: 'ot.finish_date', className: 'text-center' },
                { data: 'month', name: 'month', orderable: false, searchable: false, className: 'text-center' },
                { data: 'learning_hours', name: 'otu.learning_hours', className: 'text-center' },
                { data: 'progress_badge', name: 'otu.progress', className: 'text-center' },
                { data: 'assessment_score', name: 'otu.assessment_score', className: 'text-center' },
                { data: 'certificate_badge', name: 'otu.certificate', className: 'text-center' },
                { data: 'attendance_badge', name: 'otu.attendance', className: 'text-center' },
                { data: 'attendance_reason', name: 'otu.attendance_reason', className: 'text-start' },
                { data: 'bond_status', name: 'otu.bond_status', className: 'text-center' },
                { data: 'bond_end_date', name: 'otu.bond_end_date', className: 'text-center' },
                { data: 'bond_amount', name: 'otu.bond_amount', className: 'text-end' },
                { data: 'l1_program', name: 'otu.l1_program', className: 'text-center' },
                { data: 'l2_numerical', name: 'otu.l2_numerical', className: 'text-center' },
                { data: 'l2_verbal', name: 'otu.l2_verbal', className: 'text-start' },
                { data: 'l3_numerical', name: 'otu.l3_numerical', className: 'text-center' },
                { data: 'l3_verbal', name: 'otu.l3_verbal', className: 'text-start' },
                { data: 'pic', name: 'otu.pic', className: 'text-start' },
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

                    const nextDisabled = pageInfo.page >= pageInfo.pages - 1 ? 'disabled' : '';
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

        // Export Download Handler
        $('#btn-download-export').on('click', function (e) {
            e.preventDefault();
            const params = new URLSearchParams();

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

            const downloadUrl = downloadBaseUrl + (downloadBaseUrl.indexOf('?') !== -1 ? '&' : '?') + params.toString();
            window.open(downloadUrl, '_blank');
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

                        // Section 4: Schedule & Duration
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

                        if (d.attendance_reason) {
                            $('#modal-detail-attendance-reason').text(d.attendance_reason);
                            $('#modal-detail-reason-container').show();
                        } else {
                            $('#modal-detail-reason-container').hide();
                        }

                        $('#modal-detail-certificate').text(d.certificate || '-');
                        $('#modal-detail-l1-program').text(d.l1_program || '-');
                        $('#modal-detail-l2-num').text(d.l2_numerical !== null && d.l2_numerical !== undefined ? parseFloat(d.l2_numerical).toFixed(2) : '-');
                        $('#modal-detail-l2-verbal').text(d.l2_verbal || '-');
                        $('#modal-detail-l3-eval').text(`${d.l3_numerical ?? '-'} / ${d.l3_verbal ?? '-'}`);

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
    });
</script>
@endsection
