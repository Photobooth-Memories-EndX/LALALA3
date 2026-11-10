@extends('layouts.sidebar-master')

@section('content')
    <div class="page-body manage-record-page">
        <div class="container-xl">
            <!-- TOP BREADCRUMB & BACK BUTTON -->
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <div>
                    <a href="{{ $backUrl }}"
                        class="btn-back d-inline-flex align-items-center gap-1 text-decoration-none fw-medium">
                        <i class="ti ti-arrow-left"></i>
                        <span>{{ $backTitle }}</span>
                    </a>
                </div>
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 small">
                            <li class="breadcrumb-item"><a href="{{ route('manage.record.all') }}"
                                    class="text-decoration-none text-muted">All Record</a></li>
                            @if (!empty($classificationSlug))
                                <li class="breadcrumb-item"><a
                                        href="{{ route('manage.record.all.classification', $classificationSlug) }}"
                                        class="text-decoration-none text-muted">{{ $classificationName }}</a></li>
                            @endif
                            <li class="breadcrumb-item active text-primary fw-semibold" aria-current="page">Participant
                                Detail</li>
                        </ol>
                    </nav>
                </div>
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
                                @if (!empty($participant['grade']) && $participant['grade'] !== '-')
                                    <span class="badge bg-light text-secondary border px-2 py-1">Grade:
                                        {{ $participant['grade'] }}</span>
                                @endif
                            </div>
                            <div class="text-muted small d-flex align-items-center flex-wrap gap-2 pt-1">
                                @if (!empty($participant['job_position']) && $participant['job_position'] !== '-')
                                    <span class="d-inline-flex align-items-center gap-1">
                                        <i class="ti ti-briefcase text-secondary"></i>
                                        <span>{{ $participant['job_position'] }}</span>
                                    </span>
                                @endif
                                @if (!empty($participant['job_position']) && (!empty($participant['department']) || !empty($participant['team'])))
                                    <span class="text-muted">•</span>
                                @endif
                                @if (
                                    (!empty($participant['department']) && $participant['department'] !== '-') ||
                                        (!empty($participant['team']) && $participant['team'] !== '-'))
                                    <span class="d-inline-flex align-items-center gap-1">
                                        <i class="ti ti-building text-secondary"></i>
                                        <span>{{ !empty($participant['team']) && $participant['team'] !== '-' ? $participant['team'] . ' — ' : '' }}{{ !empty($participant['department']) && $participant['department'] !== '-' ? $participant['department'] : '-' }}</span>
                                    </span>
                                @endif
                                @if (!empty($participant['company']) && $participant['company'] !== '-')
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
                <!-- Total Learning -->
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm p-3 rounded-3 bg-white stat-kpi-card h-100">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3"
                                style="width: 44px; height: 44px; background-color: #0f4c81; color: #ffffff;">
                                <i class="ti ti-certificate fs-2"></i>
                            </div>
                            <div>
                                <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Total
                                    Learning</div>
                                <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;" id="kpi-total-training">
                                    {{ number_format($summary['total_learning']) }}</div>
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
            <div class="card border-0 shadow-sm rounded-4 mb-4 quick-filter-card">
                <div class="card-body p-3">
                    <div
                        class="quick-filter-header pb-2 mb-3 border-bottom d-flex align-items-center justify-content-between">
                        <span class="text-secondary small fw-bold text-uppercase"
                            style="letter-spacing: 0.04em; font-size: 0.8rem;">Quick Filter</span>
                    </div>
                    <form id="participant-filter-form" onsubmit="return false;" class="m-0">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <!-- 1. Learning Title Search -->
                            <div class="position-relative flex-grow-1" style="min-width: 220px;">
                                <input type="text" id="participant-course-input" class="form-control rounded-3"
                                    placeholder="Search Learning Title..." autocomplete="off">
                            </div>

                            <!-- 2. Source Dropdown -->
                            <div style="min-width: 140px; flex-shrink: 0;">
                                <select id="participant-source-select" class="form-select rounded-3">
                                    <option value="all">All Sources</option>
                                    <option value="online">KP OWL (Online)</option>
                                    <option value="offline">Non KP OWL (Offline)</option>
                                </select>
                            </div>

                            <!-- 3. Status Dropdown -->
                            <div style="min-width: 130px; flex-shrink: 0;">
                                <select id="participant-status-select" class="form-select rounded-3">
                                    <option value="all">All Status</option>
                                    <option value="finished">Finished</option>
                                    <option value="ongoing">Ongoing</option>
                                    <option value="not_started">Not Started</option>
                                </select>
                            </div>

                            <!-- 4. Year Dropdown -->
                            <div style="min-width: 110px; flex-shrink: 0;">
                                <select id="participant-year-select" class="form-select rounded-3">
                                    <option value="all">All Year</option>
                                    @foreach ($years as $year)
                                        <option value="{{ $year }}">{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- 5. Search Button -->
                            <button type="button" id="btn-participant-filter"
                                class="btn btn-primary btn-icon px-3 flex-shrink-0"
                                style="background-color: #003b6d; border-color: #003b6d;" title="Apply Filter"
                                data-bs-toggle="tooltip" data-bs-title="Apply Filter">
                                <i class="ti ti-search fs-2"></i>
                            </button>

                            <!-- 6. Reset Button -->
                            <button type="button" id="btn-participant-reset"
                                class="btn btn-outline-secondary btn-icon px-3 flex-shrink-0" title="Reset Filters"
                                data-bs-toggle="tooltip" data-bs-title="Reset Filters">
                                <i class="ti ti-refresh fs-2"></i>
                            </button>

                            <!-- 7. Download Dropdown (Light Green) -->
                            @can('learning_management')
                            <div class="dropdown flex-shrink-0 ms-auto">
                                <button type="button" id="btn-participant-download"
                                    class="btn btn-download-light-green rounded-3 px-3 dropdown-toggle d-inline-flex align-items-center gap-1"
                                    data-bs-toggle="dropdown" aria-expanded="false"
                                    title="Export Participant Learning Records">
                                    <i class="ti ti-download me-1"></i>
                                    <span>Download</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 py-2"
                                    style="min-width: 220px;">
                                    <li>
                                        <h6 class="dropdown-header text-uppercase text-muted fw-bold small mb-1"
                                            style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                            Export Options
                                        </h6>
                                    </li>
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center gap-2 py-2 participant-export-btn"
                                            href="#" data-format="excel">
                                            <i class="ti ti-file-spreadsheet text-success fs-2"></i>
                                            <div>
                                                <div class="fw-semibold">Excel (.xlsx)</div>
                                                <div class="text-muted small" style="font-size: 0.75rem;">Full participant
                                                    spreadsheet</div>
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center gap-2 py-2 participant-export-btn"
                                            href="#" data-format="csv">
                                            <i class="ti ti-file-text text-primary fs-2"></i>
                                            <div>
                                                <div class="fw-semibold">CSV (.csv)</div>
                                                <div class="text-muted small" style="font-size: 0.75rem;">Fast streaming
                                                    tabular data</div>
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider my-1">
                                    </li>
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center gap-2 py-2 participant-export-btn"
                                            href="#" data-format="pdf">
                                            <i class="ti ti-file-type-pdf text-danger fs-2"></i>
                                            <div>
                                                <div class="fw-semibold">PDF Landscape (.pdf)</div>
                                                <div class="text-muted small" style="font-size: 0.75rem;">Printable report
                                                </div>
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

            <!-- COMBINED PARTICIPANT RECORDS TABLE CARD -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div
                    class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h4 class="card-title fw-bold text-dark mb-0">Participant Learning Records</h4>
                        <p class="text-muted small mb-0">Combined Online &amp; Offline records for
                            <strong>{{ $participant['name'] }}</strong>.</p>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="all-participant-learning-table">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr>
                                    <th style="width: 45px; text-align: center;">NO</th>
                                    <th class="text-center align-middle whitespace-nowrap px-3" style="width: 1%;">Source</th>
                                    <th style="min-width: 110px;">Employee ID</th>
                                    <th style="min-width: 160px;">Employee Name</th>
                                    <th style="min-width: 130px;">Team</th>
                                    <th style="min-width: 140px;">Department</th>
                                    <th style="min-width: 130px;">Company</th>
                                    <th class="text-center" style="min-width: 220px; text-align: center;">Course Title</th>
                                    <th style="min-width: 100px;">Type</th>
                                    <th style="min-width: 110px;">Category</th>
                                    <th style="min-width: 140px;">Main Classification</th>
                                    <th style="min-width: 140px;">Sub Classification</th>
                                    <th style="min-width: 120px;">Delivery Method</th>
                                    <th style="min-width: 130px;">Sub Delivery Method</th>
                                    <th style="min-width: 130px;">Provider Name</th>
                                    <th style="min-width: 120px;">Venue</th>
                                    <th style="min-width: 110px;">Course Duration</th>
                                    <th style="min-width: 130px;">Total Recognized Duration</th>

                                    <th style="min-width: 110px;">Learning Hours</th>
                                    <th style="min-width: 110px;">% Progress</th>
                                    <th style="min-width: 110px;">Progress Status</th>
                                    <th style="min-width: 140px;">Date Time</th>
                                    <th style="min-width: 110px;">Record Month</th>
                                    <th style="min-width: 110px;">Requirement</th>
                                    <th style="min-width: 100px;">Attendance</th>
                                    <th style="min-width: 120px;">Score (L1) - Course</th>
                                    <th style="min-width: 90px;">Score (L2)</th>
                                    <th style="min-width: 90px;">Score (L3)</th>
                                    <th style="min-width: 130px;">Person In Charge (PIC)</th>
                                    <th style="min-width: 140px;">TOTAL ENGAGEMENT DURATION</th>
                                    <th class="text-end" style="width: 80px;">ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Server-side DataTable rows -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Footer Pagination -->
                <div
                    class="card-footer bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <select id="participant-table-entries-select" class="form-select form-select-sm w-auto rounded-3">
                            <option value="10">10 Entries</option>
                            <option value="25" selected>25 Entries</option>
                            <option value="50">50 Entries</option>
                            <option value="100">100 Entries</option>
                        </select>
                        <div class="text-muted small" id="participant-table-info">
                            Showing 0 to 0 of 0 entries
                        </div>
                    </div>
                    <div id="participant-table-pagination"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- INCLUDE DETAIL MODALS -->
    @include('main.record.components.more-information-modal')
    @include('main.record.components.offline-detail-modal')
    @include('main.record.components.online-detail-modal')
    @include('main.record.components.offline-delete-modal')
    @include('main.record.components.online-delete-modal')
    @include('main.record.components.master-delete-modal')
    @include('main.learning.components.learning-detail-modal')
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

        .course-link {
            color: #0c5b9e;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            transition: color 0.15s ease, text-decoration 0.15s ease;
        }

        .course-link:hover,
        .course-link:focus {
            color: #003b6d;
            text-decoration: underline !important;
        }

        [data-bs-theme="dark"] .course-link,
        body.dark-mode .course-link,
        body.theme-dark .course-link {
            color: #60a5fa !important;
        }

        [data-bs-theme="dark"] .course-link:hover,
        body.dark-mode .course-link:hover,
        body.theme-dark .course-link:hover {
            color: #93c5fd !important;
            text-decoration: underline !important;
        }

        /* Dark Mode Table & Cards */
        [data-bs-theme="dark"] .card,
        body.dark-mode .card,
        body.theme-dark .card {
            background-color: #1e293b !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
            color: #f8fafc !important;
        }

        [data-bs-theme="dark"] .card-header,
        [data-bs-theme="dark"] .card-footer,
        body.dark-mode .card-header,
        body.dark-mode .card-footer,
        body.theme-dark .card-header,
        body.theme-dark .card-footer {
            background-color: #1e293b !important;
        }

        [data-bs-theme="dark"] .text-dark,
        body.dark-mode .text-dark,
        body.theme-dark .text-dark {
            color: #f8fafc !important;
        }

        .table-responsive {
            max-height: 70vh;
            overflow-y: auto;
            position: relative;
        }

        #all-participant-learning-table thead th {
            position: sticky !important;
            top: 0 !important;
            z-index: 10 !important;
            background-color: #f8fafc !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        #all-participant-learning-table_wrapper > .bottom {
            display: none !important;
        }

        [data-bs-theme="dark"] #all-participant-learning-table thead th,
        body.dark-mode #all-participant-learning-table thead th,
        body.theme-dark #all-participant-learning-table thead th {
            background-color: #0f172a !important;
            color: #94a3b8 !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
        }

        [data-bs-theme="dark"] #all-participant-learning-table tbody td,
        body.dark-mode #all-participant-learning-table tbody td,
        body.theme-dark #all-participant-learning-table tbody td {
            background-color: #1e293b !important;
            border-color: rgba(255, 255, 255, 0.05) !important;
            color: #cbd5e1 !important;
        }

        [data-bs-theme="dark"] #all-participant-learning-table tbody tr:hover td,
        body.dark-mode #all-participant-learning-table tbody tr:hover td,
        body.theme-dark #all-participant-learning-table tbody tr:hover td {
            background-color: #334155 !important;
        }

        [data-bs-theme="dark"] .form-control,
        [data-bs-theme="dark"] .form-select,
        body.dark-mode .form-control,
        body.dark-mode .form-select,
        body.theme-dark .form-control,
        body.theme-dark .form-select {
            background-color: #0f172a !important;
            color: #f1f5f9 !important;
            border-color: rgba(255, 255, 255, 0.15) !important;
        }

        /* Action Dropdown & Trigger Button */
        .btn-action-trigger {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            background: transparent;
            border: 1px solid transparent;
            transition: all 0.15s ease;
        }

        .btn-action-trigger:hover,
        .btn-action-trigger:focus,
        .btn-action-trigger[aria-expanded="true"] {
            color: #003b6d;
            background-color: #f1f5f9;
            border-color: #cbd5e1;
        }

        [data-bs-theme="dark"] .btn-action-trigger,
        body.dark-mode .btn-action-trigger,
        body.theme-dark .btn-action-trigger {
            color: #94a3b8;
        }

        [data-bs-theme="dark"] .btn-action-trigger:hover,
        [data-bs-theme="dark"] .btn-action-trigger:focus,
        [data-bs-theme="dark"] .btn-action-trigger[aria-expanded="true"],
        body.dark-mode .btn-action-trigger:hover,
        body.theme-dark .btn-action-trigger:hover {
            color: #f8fafc;
            background-color: #334155;
            border-color: #475569;
        }

        .dropdown-action-menu .dropdown-menu {
            min-width: 180px;
            font-size: 0.815rem;
            z-index: 1060 !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
        }

        .dropdown-action-menu .dropdown-item {
            padding: 7px 14px;
            font-weight: 500;
            transition: background-color 0.12s ease, color 0.12s ease;
        }

        .dropdown-action-menu .dropdown-item:hover {
            background-color: #f8fafc;
        }

        .dropdown-action-menu .dropdown-item.text-danger:hover {
            background-color: #fef2f2;
            color: #dc2626 !important;
        }

        [data-bs-theme="dark"] .dropdown-action-menu .dropdown-menu,
        body.dark-mode .dropdown-action-menu .dropdown-menu,
        body.theme-dark .dropdown-action-menu .dropdown-menu {
            background-color: #1e293b;
            border-color: #334155;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5) !important;
        }

        [data-bs-theme="dark"] .dropdown-action-menu .dropdown-item,
        body.dark-mode .dropdown-action-menu .dropdown-item,
        body.theme-dark .dropdown-action-menu .dropdown-item {
            color: #cbd5e1;
        }

        [data-bs-theme="dark"] .dropdown-action-menu .dropdown-item:hover,
        body.dark-mode .dropdown-action-menu .dropdown-item:hover,
        body.theme-dark .dropdown-action-menu .dropdown-item:hover {
            background-color: #334155;
            color: #f8fafc;
        }

        [data-bs-theme="dark"] .dropdown-action-menu .dropdown-item.text-danger:hover,
        body.dark-mode .dropdown-action-menu .dropdown-item.text-danger:hover,
        body.theme-dark .dropdown-action-menu .dropdown-item.text-danger:hover {
            background-color: rgba(220, 38, 38, 0.15) !important;
            color: #f87171 !important;
        }

        .extra-small {
            font-size: 0.75rem;
        }

        /* Quick Filter Card & Header Divider */
        .quick-filter-card {
            border-radius: 14px !important;
        }

        .quick-filter-header {
            border-bottom: 1px solid #e9ecef !important;
        }

        [data-bs-theme="dark"] .quick-filter-header,
        body.dark-mode .quick-filter-header,
        body.theme-dark .quick-filter-header {
            border-bottom-color: rgba(255, 255, 255, 0.08) !important;
        }

        /* Light Green Download Button */
        .btn-download-light-green {
            background-color: #22c55e !important;
            border-color: #22c55e !important;
            color: #ffffff !important;
            font-weight: 500;
            transition: background-color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .btn-download-light-green:hover,
        .btn-download-light-green:focus {
            background-color: #16a34a !important;
            border-color: #16a34a !important;
            color: #ffffff !important;
            box-shadow: 0 4px 10px rgba(34, 197, 94, 0.25);
        }

        .btn-download-light-green:active,
        .btn-download-light-green.active,
        .btn-download-light-green.show,
        .btn-download-light-green[aria-expanded="true"] {
            background-color: #15803d !important;
            border-color: #15803d !important;
            color: #ffffff !important;
        }

        [data-bs-theme="dark"] .btn-download-light-green,
        body.dark-mode .btn-download-light-green,
        body.theme-dark .btn-download-light-green {
            background-color: #22c55e !important;
            border-color: #22c55e !important;
            color: #ffffff !important;
        }

        [data-bs-theme="dark"] .btn-download-light-green:hover,
        [data-bs-theme="dark"] .btn-download-light-green:focus,
        body.dark-mode .btn-download-light-green:hover,
        body.theme-dark .btn-download-light-green:hover {
            background-color: #16a34a !important;
            border-color: #16a34a !important;
        }

        [data-bs-theme="dark"] .btn-download-light-green:active,
        [data-bs-theme="dark"] .btn-download-light-green.show,
        [data-bs-theme="dark"] .btn-download-light-green[aria-expanded="true"],
        body.dark-mode .btn-download-light-green:active,
        body.theme-dark .btn-download-light-green:active {
            background-color: #15803d !important;
            border-color: #15803d !important;
        }
    </style>
@endsection

@section('script')
    <script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>

    <script>
        $(function() {
            const datatableUrl = @json(route('manage.record.all.participant.datatable', $participant['raw_identifier']));
            const classificationParam = @json($classificationParam ?? '');
            const exportExcelUrl = @json($exportExcelUrl);
            const exportCsvUrl = @json($exportCsvUrl);
            const exportPdfUrl = @json($exportPdfUrl);
            const defaultEntries = 25;

            let participantTable = null;

            if ($.fn.DataTable.isDataTable('#all-participant-learning-table')) {
                $('#all-participant-learning-table').DataTable().destroy();
            }

            participantTable = $('#all-participant-learning-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: false,
                autoWidth: false,
                pageLength: defaultEntries,
                lengthMenu: [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ],
                order: [],
                fixedHeader: true,
                dom: '<"top">rt<"bottom d-flex justify-content-between align-items-center mt-3"l ip><"clear">',
                ajax: {
                    url: datatableUrl,
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: function(d) {
                        d._token = $('meta[name="csrf-token"]').attr('content');
                        d.learning_title = $('#participant-course-input').val().trim();
                        d.source = $('#participant-source-select').val();
                        d.status = $('#participant-status-select').val();
                        d.year = $('#participant-year-select').val();
                        if (classificationParam) {
                            d.classification = classificationParam;
                        }
                        d.entries = $('#participant-table-entries-select').val() || defaultEntries;
                    }
                },
                columns: [
                    {
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'text-muted fw-semibold text-center'
                    },
                    {
                        data: 'source_badge',
                        name: 'source',
                        orderable: true,
                        width: '1%',
                        className: 'text-center align-middle whitespace-nowrap px-3'
                    },
                    {
                        data: 'employee_id',
                        name: 'employee_id',
                        orderable: true,
                        defaultContent: '-'
                    },
                    {
                        data: 'employee_name',
                        name: 'employee_name',
                        orderable: true,
                        defaultContent: '-'
                    },
                    {
                        data: 'team',
                        name: 'team',
                        orderable: true,
                        defaultContent: '-'
                    },
                    {
                        data: 'department',
                        name: 'department',
                        orderable: true,
                        defaultContent: '-'
                    },
                    {
                        data: 'company',
                        name: 'company',
                        orderable: true,
                        defaultContent: '-'
                    },
                    {
                        data: 'course_title',
                        name: 'learning_title',
                        orderable: true,
                        defaultContent: '-',
                        className: 'text-center align-middle',
                        render: function(data, type, row) {
                            if (typeof data === 'string' && data.indexOf('d-flex flex-column') !== -1) {
                                return data;
                            }
                            const courseTitle = data || row.course_title || row.learning_title || '-';
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
                        data: 'type',
                        name: 'type',
                        orderable: true,
                        defaultContent: '-'
                    },
                    {
                        data: 'category',
                        name: 'category',
                        orderable: true,
                        defaultContent: '-'
                    },
                    {
                        data: 'main_classification',
                        name: 'classification',
                        orderable: true,
                        defaultContent: '-'
                    },
                    {
                        data: 'sub_classification',
                        name: 'sub_classification',
                        orderable: true,
                        defaultContent: '-'
                    },
                    {
                        data: 'delivery_method',
                        name: 'delivery_method',
                        orderable: true,
                        defaultContent: '-'
                    },
                    {
                        data: 'sub_delivery_method',
                        name: 'sub_delivery_method',
                        orderable: true,
                        defaultContent: '-'
                    },
                    {
                        data: 'provider_name',
                        name: 'provider_name',
                        orderable: true,
                        defaultContent: '-'
                    },
                    {
                        data: 'venue',
                        name: 'venue',
                        orderable: true,
                        defaultContent: '-'
                    },
                    {
                        data: 'course_duration',
                        name: 'duration_seconds',
                        orderable: true,
                        defaultContent: '-'
                    },
                    {
                        data: 'total_recognized_duration',
                        name: 'recognized_duration_seconds',
                        orderable: true,

                        defaultContent: '-'
                    },
                    {
                        data: 'learning_hours',
                        name: 'learning_hours',
                        orderable: true,
                        defaultContent: '-'
                    },
                    {
                        data: 'progress_percent',
                        name: 'progress',
                        orderable: true
                    },
                    {
                        data: 'progress_status',
                        name: 'status',
                        orderable: true
                    },
                    {
                        data: 'date_time_merged',
                        name: 'start_date',
                        orderable: true
                    },
                    {
                        data: 'record_month',
                        name: 'record_month',
                        orderable: false,
                        defaultContent: '-'
                    },
                    {
                        data: 'requirement',
                        name: 'requirement',
                        orderable: true,
                        defaultContent: '-'
                    },
                    {
                        data: 'attendance',
                        name: 'attendance',
                        orderable: true,
                        defaultContent: '-',
                        render: function (data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                const clean = (data || '').toString().replace(/<[^>]*>?/gm, '').trim().toLowerCase();
                                return (clean === 'yes' || clean === 'present' || clean === '1' || clean === 'true' || clean === 'hadir') ? 'Yes' : 'No';
                            }
                            if (typeof data === 'string' && (data.includes('<span') || data.includes('<badge'))) {
                                if (data.toLowerCase().includes('present') || data.toLowerCase().includes('hadir')) {
                                    return '<span class="badge bg-success-lt text-success fw-bold px-2 py-1">Yes</span>';
                                }
                                if (data.toLowerCase().includes('absent') || data.includes('>-<') || data.toLowerCase().includes('tidak hadir') || data.toLowerCase().includes('not completed')) {
                                    return '<span class="badge bg-danger-lt text-danger fw-bold px-2 py-1">No</span>';
                                }
                                return data;
                            }
                            const raw = String(data ?? '').toLowerCase().trim();
                            const isYes = ['1', 'yes', 'true', 'present', 'hadir', 'completed', 'finished'].includes(raw)
                                || (row && row.source === 'online' && parseFloat(row.progress || 0) >= 100);
                            if (isYes) {
                                return '<span class="badge bg-success-lt text-success fw-bold px-2 py-1">Yes</span>';
                            }
                            return '<span class="badge bg-danger-lt text-danger fw-bold px-2 py-1">No</span>';
                        }
                    },
                    {
                        data: 'score_l1',
                        name: 'score_l1',
                        orderable: true,
                        defaultContent: '-'
                    },
                    {
                        data: 'score_l2',
                        name: 'score_l2',
                        orderable: true,
                        defaultContent: '-'
                    },
                    {
                        data: 'score_l3',
                        name: 'score_l3',
                        orderable: true,
                        defaultContent: '-'
                    },
                    {
                        data: 'pic',
                        name: 'pic',
                        orderable: true,
                        defaultContent: '-'
                    },
                    {
                        data: 'total_engagement_duration',
                        name: 'total_engagement_duration',
                        orderable: true,
                        defaultContent: '-'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-end text-nowrap'
                    },
                ],
                language: {
                    processing: '<div class="py-3 text-center"><div class="spinner-border spinner-border-sm text-primary" role="status"></div><span class="ms-2 small text-muted">Loading learning records...</span></div>',
                    emptyTable: 'No learning records found for this participant.',
                    zeroRecords: 'No matching learning records found.'
                },
                drawCallback: function(settings) {
                    const api = this.api();
                    const pageInfo = api.page.info();

                    $('#participant-table-info').text(
                        pageInfo.recordsDisplay > 0 ?
                        `Showing ${pageInfo.start + 1} to ${pageInfo.end} of ${pageInfo.recordsDisplay.toLocaleString()} entries` :
                        'Showing 0 to 0 of 0 entries'
                    );

                    renderPagination(pageInfo);
                }
            });

            function renderPagination(pageInfo) {
                const $pagination = $('#participant-table-pagination');
                $pagination.empty();

                if (pageInfo.pages <= 1) return;

                let html = '<ul class="pagination pagination-sm mb-0">';
                html += `<li class="page-item ${pageInfo.page === 0 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${pageInfo.page - 1}" aria-label="Previous">&laquo;</a>
                 </li>`;

                const maxVisible = 5;
                let startPage = Math.max(0, pageInfo.page - Math.floor(maxVisible / 2));
                let endPage = Math.min(pageInfo.pages - 1, startPage + maxVisible - 1);
                if (endPage - startPage + 1 < maxVisible) {
                    startPage = Math.max(0, endPage - maxVisible + 1);
                }

                for (let i = startPage; i <= endPage; i++) {
                    html += `<li class="page-item ${i === pageInfo.page ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i + 1}</a>
                     </li>`;
                }

                html += `<li class="page-item ${pageInfo.page >= pageInfo.pages - 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${pageInfo.page + 1}" aria-label="Next">&raquo;</a>
                 </li>`;
                html += '</ul>';

                $pagination.html(html);

                $pagination.find('a.page-link').on('click', function(e) {
                    e.preventDefault();
                    const targetPage = parseInt($(this).data('page'));
                    if (!isNaN(targetPage) && targetPage >= 0 && targetPage < pageInfo.pages &&
                        targetPage !== pageInfo.page) {
                        participantTable.page(targetPage).draw('page');
                    }
                });
            }

            // Initialize Bootstrap tooltips
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                $('[data-bs-toggle="tooltip"]').each(function() {
                    new bootstrap.Tooltip(this);
                });
            }

            // Filter controls
            $('#btn-participant-filter').on('click', function() {
                participantTable.ajax.reload();
            });

            $('#participant-course-input').on('keyup', function(e) {
                if (e.key === 'Enter') {
                    participantTable.ajax.reload();
                }
            });

            $('#participant-source-select, #participant-status-select, #participant-year-select').on('change',
                function() {
                    participantTable.ajax.reload();
                });

            $('#participant-table-entries-select').on('change', function() {
                const len = parseInt($(this).val()) || defaultEntries;
                participantTable.page.len(len).draw();
            });

            $('#btn-participant-reset').on('click', function() {
                $('#participant-course-input').val('');
                $('#participant-source-select').val('all');
                $('#participant-status-select').val('all');
                $('#participant-year-select').val('all');
                participantTable.ajax.reload();
            });

            // Exports
            $('.participant-export-btn').on('click', function(e) {
                e.preventDefault();
                const format = $(this).data('format');
                let baseUrl = exportExcelUrl;
                if (format === 'csv') baseUrl = exportCsvUrl;
                if (format === 'pdf') baseUrl = exportPdfUrl;

                const params = new URLSearchParams();
                const title = $('#participant-course-input').val().trim();
                const source = $('#participant-source-select').val();
                const status = $('#participant-status-select').val();
                const year = $('#participant-year-select').val();

                if (title) params.append('learning_title', title);
                if (source && source !== 'all') params.append('source', source);
                if (status && status !== 'all') params.append('status', status);
                if (year && year !== 'all') params.append('year', year);
                if (classificationParam) params.append('classification', classificationParam);

                const url = baseUrl + (baseUrl.includes('?') ? '&' : '?') + params.toString();
                window.location.href = url;
            });

            const summaryUrl = @json(route('manage.record.all.participant.summary', $participant['raw_identifier']));

            function refreshParticipantKpiSummary() {
                $.ajax({
                    url: summaryUrl,
                    type: 'GET',
                    data: classificationParam ? {
                        classification: classificationParam
                    } : {},
                    dataType: 'json',
                    success: function(res) {
                        if (res && res.success) {
                            $('#kpi-total-training').text(Number(res.total_learning || 0)
                                .toLocaleString());
                            $('#kpi-finished').text(Number(res.finished || 0).toLocaleString());
                            $('#kpi-ongoing').text(Number(res.ongoing || 0).toLocaleString());
                            const hours = parseFloat(res.learning_hours || 0);
                            $('#kpi-hours').text(hours.toFixed(1) + 'h');
                        }
                    }
                });
            }

            // 1. Offline Detail Modal trigger
            $(document).on('click', '.btn-view-offline-detail', function() {
                const participantId = $(this).data('id');
                if (!participantId) return;

                const modalEl = document.getElementById('offlineTrainingDetailModal');
                if (!modalEl) return;

                const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modal.show();

                $('#offline-detail-loading').show();
                $('#offline-detail-content').hide();
                $('#offline-detail-error').hide();

                $.ajax({
                    url: `/manage/record/offline/${participantId}/detail`,
                    type: 'GET',
                    dataType: 'json',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
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

                            // Section 3: Delivery & Provider
                            $('#modal-detail-delivery-method').text(d.delivery_method || '-');
                            $('#modal-detail-sub-delivery-method').text(d.sub_delivery_method ||
                                '-');
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
                            $('#modal-detail-course-hours').text(d.course_learning_hours ?
                                `${parseFloat(d.course_learning_hours).toFixed(1)} Hours` :
                                '-');

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
                            $('#modal-detail-progress').text(`${progress.toFixed(1)}%`);
                            $('#modal-detail-learning-hours').text(d
                                .participant_learning_hours ?
                                `${parseFloat(d.participant_learning_hours).toFixed(1)} Hours` :
                                '-');
                            $('#modal-detail-assessment-score').text(d.assessment_score !==
                                null && d.assessment_score !== undefined ? parseFloat(d
                                    .assessment_score).toFixed(1) : '-');

                            const progVal = parseFloat(d.progress ?? 0);
                            const rawAtt = String(d.attendance ?? '').toLowerCase().trim();
                            let isAttYes = ['1', 'yes', 'true', 'present', 'hadir', 'completed', 'finished'].includes(rawAtt);
                            if (!isAttYes && (rawAtt === '' || d.attendance === null || d.attendance === undefined)) {
                                isAttYes = (progVal >= 100 || !!d.finish_date);
                            }

                            $('#modal-detail-attendance').html(isAttYes ?
                                '<span class="badge bg-success-lt text-success fw-bold px-2 py-1">Yes</span>' :
                                '<span class="badge bg-danger-lt text-danger fw-bold px-2 py-1">No</span>'
                            );

                            if (d.attendance_reason) {
                                $('#modal-detail-attendance-reason').text(d.attendance_reason);
                                $('#modal-detail-reason-container').show();
                            } else {
                                $('#modal-detail-reason-container').hide();
                            }

                            // Section 7: Bond & Kirkpatrick L1-L3
                            $('#modal-detail-bond-status').text(d.bond_status || '-');
                            $('#modal-detail-bond-end-date').text(d.bond_end_date || '-');
                            $('#modal-detail-bond-amount').text(d.bond_amount ?
                                `IDR ${parseFloat(d.bond_amount).toLocaleString('id-ID')}` :
                                '-');
                            $('#modal-detail-l1-program').text(d.l1_program || '-');
                            $('#modal-detail-l2-numerical').text(d.l2_numerical !== null && d
                                .l2_numerical !== undefined ? parseFloat(d.l2_numerical)
                                .toFixed(1) : '-');
                            $('#modal-detail-l2-verbal').text(d.l2_verbal || '-');
                            $('#modal-detail-l3-numerical').text(d.l3_numerical !== null && d
                                .l3_numerical !== undefined ? parseFloat(d.l3_numerical)
                                .toFixed(1) : '-');
                            $('#modal-detail-l3-verbal').text(d.l3_verbal || '-');
                            $('#modal-detail-pic').text(d.pic || '-');

                            $('#offline-detail-content').show();
                        } else {
                            $('#offline-detail-error').text(res.message ||
                                'Failed to load details.').show();
                        }
                    },
                    error: function() {
                        $('#offline-detail-loading').hide();
                        $('#offline-detail-error').text(
                            'An error occurred while loading record details.').show();
                    }
                });
            });

            // 2. Online Detail Modal trigger
            $(document).on('click', '.btn-view-online-detail', function() {
                const id = $(this).data('id');
                const type = $(this).data('type') || 'content';
                if (!id) return;

                const modalEl = document.getElementById('onlineTrainingDetailModal');
                if (!modalEl) return;

                const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modal.show();

                $('#online-detail-loading').show();
                $('#online-detail-content').hide();
                $('#online-detail-error').hide();

                $.ajax({
                    url: `/manage/record/online/${encodeURIComponent(type)}/${encodeURIComponent(id)}/detail`,
                    type: 'GET',
                    dataType: 'json',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(res) {
                        $('#online-detail-loading').hide();
                        if (res && res.success && res.data) {
                            const d = res.data;
                            const emp = d.employee || {};
                            const lr = d.learning || {};
                            const pr = d.progress || {};
                            const ts = d.timestamps || {};

                            // Header
                            $('#online-modal-title').text(lr.title || '-');
                            if (lr.playlist && lr.playlist.title && lr.playlist.title !== '-') {
                                $('#online-modal-playlist-sub').text('Playlist: ' + lr.playlist
                                    .title).show();
                            } else {
                                $('#online-modal-playlist-sub').hide();
                            }

                            $('#online-modal-type-badge').text(lr.type || 'Document');
                            const isFin = pr.is_finished || pr.status === 'Finished';
                            const statusBadge = $('#online-modal-status-badge');
                            if (isFin) {
                                statusBadge.removeClass('bg-warning-lt bg-secondary-lt')
                                    .addClass('bg-success-lt').text('FINISHED');
                            } else if (parseFloat(pr.progress) > 0) {
                                statusBadge.removeClass('bg-success-lt bg-secondary-lt')
                                    .addClass('bg-warning-lt').text(
                                        `ONGOING (${Math.round(pr.progress)}%)`);
                            } else {
                                statusBadge.removeClass('bg-success-lt bg-warning-lt').addClass(
                                    'bg-secondary-lt').text('NOT STARTED');
                            }

                            // Section 1: Employee
                            $('#online-modal-employee-id').text(emp.id_emp || '-');
                            $('#online-modal-employee-name').text(emp.name || '-');
                            $('#online-modal-department').text(emp.department || '-');
                            $('#online-modal-company').text(emp.company || '-');
                            if (emp.profile_url) {
                                $('#online-modal-profile-link').attr('href', emp.profile_url)
                                    .show();
                            } else {
                                $('#online-modal-profile-link').hide();
                            }

                            // Section 2: Content
                            $('#online-modal-category').text(lr.category || '-');
                            $('#online-modal-classification').text(lr.classification || '-');
                            $('#online-modal-source').text(lr.source || 'KP OWL');
                            $('#online-modal-duration').text(lr.duration || '-');

                            // Section 3: Tracking & Progress
                            $('#online-modal-progress-pct').text(
                                `${parseFloat(pr.progress || 0).toFixed(1)}%`);
                            $('#online-modal-progress-bar').css('width',
                                `${Math.min(100, Math.max(0, pr.progress || 0))}%`);
                            $('#online-modal-current-position').text(pr.current_position ||
                                '00:00:00');
                            $('#online-modal-actual-watch').text(pr.actual_watch_time ||
                                '00:00:00');
                            $('#online-modal-afk-duration').text(pr.afk_time || '00:00:00');

                            if (pr.page_viewed !== undefined && pr.page_viewed !== null) {
                                $('#online-modal-pages-viewed').text(
                                    `${pr.page_viewed} / ${pr.total_pages || '-'}`);
                                $('#online-modal-pages-container').show();
                            } else {
                                $('#online-modal-pages-container').hide();
                            }

                            // Section 4: Learning Hours & Completion
                            $('#online-modal-credited-hours').text(pr.credited_hours ||
                                '0.00 Hours');
                            $('#online-modal-learning-status').text(pr.learning_status ||
                                'Valid');
                            $('#online-modal-enrolled-at').text(ts.enrolled_at || '-');
                            $('#online-modal-completed-at').text(ts.completed_at || '-');
                            $('#online-modal-last-activity').text(ts.last_activity || '-');

                            $('#online-detail-content').show();
                        } else {
                            $('#online-detail-error-title').text('Failed to Load Record');
                            $('#online-detail-error-message').text(res.message ||
                                'Record not found or access denied.');
                            $('#online-detail-error').show();
                        }
                    },
                    error: function(xhr) {
                        $('#online-detail-loading').hide();
                        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr
                            .responseJSON.message :
                            'An error occurred while loading record details.';
                        $('#online-detail-error-title').text('Error');
                        $('#online-detail-error-message').text(msg);
                        $('#online-detail-error').show();
                    }
                });
            });

            // 3. Delete Record Modal Logic for Offline records
            $(document).on('click', '.btn-delete-offline-record', function() {
                const id = $(this).data('id');
                const employee = $(this).data('employee') || 'Participant';
                const course = $(this).data('course') || 'Training';
                const nip = $(this).data('nip') || '';

                $('#delete-modal-employee-name').text(employee);
                $('#delete-modal-course-name').text(course);
                if (nip) {
                    $('#delete-modal-nip').text(nip);
                    $('#delete-modal-nip-wrapper').show();
                } else {
                    $('#delete-modal-nip-wrapper').hide();
                }
                $('#btn-confirm-delete-offline-record').data('id', id);

                const deleteModalEl = document.getElementById('deleteOfflineRecordModal');
                if (deleteModalEl) {
                    const deleteModal = bootstrap.Modal.getInstance(deleteModalEl) || new bootstrap.Modal(
                        deleteModalEl);
                    deleteModal.show();
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
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') ||
                            '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    success: function(res) {
                        $btn.prop('disabled', false).html(originalHtml);
                        const deleteModalEl = document.getElementById(
                            'deleteOfflineRecordModal');
                        if (deleteModalEl) {
                            const deleteModal = bootstrap.Modal.getInstance(deleteModalEl);
                            if (deleteModal) deleteModal.hide();
                        }
                        if (participantTable) {
                            participantTable.ajax.reload(null, false);
                        }
                        refreshParticipantKpiSummary();
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).html(originalHtml);
                        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr
                            .responseJSON.message : 'Failed to delete record.';
                        alert(msg);
                    }
                });
            });

            // 4. Edit Online Learning Record Logic (reusing #learningEditModal)
            $(document).on('click', '.btn-edit-online-record', function() {
                const id = $(this).data('id');
                const type = $(this).data('type') || 'content';

                const editModalEl = document.getElementById('learningEditModal');
                if (!editModalEl) return;
                const editModal = bootstrap.Modal.getInstance(editModalEl) || new bootstrap.Modal(
                    editModalEl);
                editModal.show();

                $('#learningEditLoading').show();
                $('#learningEditContent').hide();
                $('#learningEditFetchError').hide();
                $('#learningEditSubmitError').hide();

                $.ajax({
                    url: `/manage/learning/record-detail/${type}/${id}`,
                    type: 'GET',
                    dataType: 'json',
                    success: function(res) {
                        $('#learningEditLoading').hide();
                        if (res && res.status === 'success' && res.data) {
                            const d = res.data;
                            $('#edit-source-type').val(d.source_type);
                            $('#edit-source-id').val(d.source_id);
                            $('#edit-original-finish').val(d.progress.is_finished ? '1' : '0');
                            $('#edit-duration-seconds').val(d.progress.duration_seconds || 0);

                            // Read-only context
                            $('#le-content-title').text(d.learning.title || '-');
                            $('#le-content-type').text(d.learning.type || '-');
                            $('#le-content-source').text(d.learning.source || '-');
                            $('#le-content-classification').text(d.learning.classification ||
                                '-');
                            $('#le-content-category').text(d.learning.category || '-');
                            $('#le-content-duration').text(d.progress.duration || '-');

                            if (d.learning.playlist_name && d.learning.playlist_name !== '-') {
                                $('#le-playlist-name').text(d.learning.playlist_name);
                                $('#le-playlist-container').show();
                            } else {
                                $('#le-playlist-container').hide();
                            }

                            $('#le-emp-name').text(d.employee.name || '-');
                            $('#le-emp-id').text(d.employee.id_emp || '-');
                            $('#le-emp-dept').text(d.employee.department || '-');

                            // Inputs
                            $('#edit-progress').val(d.progress.progress);
                            $('#edit-current-time').val(d.progress.raw_current_time || 0);
                            $('#edit-actual-watch').val(d.progress.raw_actual_watch_seconds ||
                                0);
                            $('#edit-afk-duration').val(d.progress.raw_afk_duration_seconds ||
                                0);

                            $('#learningEditContent').show();
                        } else {
                            $('#learningEditFetchErrorMessage').text(res.message ||
                                'Failed to load record.');
                            $('#learningEditFetchError').show();
                        }
                    },
                    error: function(xhr) {
                        $('#learningEditLoading').hide();
                        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr
                            .responseJSON.message : 'Error fetching record.';
                        $('#learningEditFetchErrorMessage').text(msg);
                        $('#learningEditFetchError').show();
                    }
                });
            });

            $('#learningEditForm').on('submit', function(e) {
                e.preventDefault();
                const type = $('#edit-source-type').val();
                const id = $('#edit-source-id').val();
                const progress = parseInt($('#edit-progress').val(), 10);
                const currentTime = parseInt($('#edit-current-time').val(), 10);
                const actualWatch = parseInt($('#edit-actual-watch').val(), 10);
                const afkDuration = parseInt($('#edit-afk-duration').val(), 10);
                const durationSeconds = parseInt($('#edit-duration-seconds').val(), 10);

                if (durationSeconds > 0 && currentTime > durationSeconds) {
                    $('#learningEditSubmitErrorMessage').text(
                        `Current time (${currentTime}s) cannot exceed duration (${durationSeconds}s).`);
                    $('#learningEditSubmitError').show();
                    return;
                }

                $('#learningEditSubmitError').hide();
                $('#edit-save-spinner').show();
                $('#edit-save-text').text('Saving...');
                $('#btn-save-learning-edit').prop('disabled', true);

                $.ajax({
                    url: `/manage/learning/record/${type}/${id}`,
                    type: 'PUT',
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') ||
                            '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    data: JSON.stringify({
                        progress: progress,
                        current_time: currentTime,
                        actual_watch_seconds: actualWatch,
                        afk_duration_seconds: afkDuration
                    }),
                    success: function(res) {
                        $('#edit-save-spinner').hide();
                        $('#edit-save-text').text('Save Changes');
                        $('#btn-save-learning-edit').prop('disabled', false);

                        const editModalEl = document.getElementById('learningEditModal');
                        if (editModalEl) {
                            const editModal = bootstrap.Modal.getInstance(editModalEl);
                            if (editModal) editModal.hide();
                        }

                        if (participantTable) {
                            participantTable.ajax.reload(null, false);
                        }
                        refreshParticipantKpiSummary();
                    },
                    error: function(xhr) {
                        $('#edit-save-spinner').hide();
                        $('#edit-save-text').text('Save Changes');
                        $('#btn-save-learning-edit').prop('disabled', false);
                        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr
                            .responseJSON.message : 'Failed to update record.';
                        $('#learningEditSubmitErrorMessage').text(msg);
                        $('#learningEditSubmitError').show();
                    }
                });
            });

            // 5. Reset Online Learning Record Logic (reusing #learningResetModal)
            $(document).on('click', '.btn-reset-online-record', function() {
                const id = $(this).data('id');
                const type = $(this).data('type') || 'content';

                const resetModalEl = document.getElementById('learningResetModal');
                if (!resetModalEl) return;
                const resetModal = bootstrap.Modal.getInstance(resetModalEl) || new bootstrap.Modal(
                    resetModalEl);
                resetModal.show();

                $('#learningResetLoading').show();
                $('#learningResetContent').hide();
                $('#learningResetFetchError').hide();
                $('#learningResetSubmitError').hide();

                $('#reset-source-type').val(type);
                $('#reset-source-id').val(id);

                $.ajax({
                    url: `/manage/learning/record-detail/${type}/${id}`,
                    type: 'GET',
                    dataType: 'json',
                    success: function(res) {
                        $('#learningResetLoading').hide();
                        if (res && res.status === 'success' && res.data) {
                            const d = res.data;
                            $('#lr-emp-name').text(d.employee.name || '-');
                            $('#lr-emp-id').text(d.employee.id_emp || '-');
                            $('#lr-content-source').text(d.learning.source || '-');
                            $('#lr-content-title').text(d.learning.title || '-');
                            $('#lr-current-progress').text((d.progress.progress || 0) + '%');
                            $('#lr-learning-hours').text(d.progress.learning_hours || '0 h');

                            const isFin = d.progress.is_finished;
                            $('#lr-status-badge')
                                .removeClass('bg-success-lt bg-warning-lt bg-secondary-lt')
                                .addClass(isFin ? 'bg-success-lt text-success' :
                                    'bg-warning-lt text-warning')
                                .text(d.progress.finish || (isFin ? 'Finished' : 'Ongoing'));

                            if (isFin) {
                                $('#lr-completed-warning').show();
                            } else {
                                $('#lr-completed-warning').hide();
                            }

                            if (d.learning.playlist_name && d.learning.playlist_name !== '-') {
                                $('#lr-playlist-name').text(d.learning.playlist_name);
                                $('#lr-playlist-container').show();
                                $('#lr-playlist-warning').show();
                            } else {
                                $('#lr-playlist-container').hide();
                                $('#lr-playlist-warning').hide();
                            }

                            $('#learningResetContent').show();
                        } else {
                            $('#learningResetFetchErrorMessage').text(res.message ||
                                'Failed to load details.');
                            $('#learningResetFetchError').show();
                        }
                    },
                    error: function(xhr) {
                        $('#learningResetLoading').hide();
                        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr
                            .responseJSON.message : 'Error fetching record.';
                        $('#learningResetFetchErrorMessage').text(msg);
                        $('#learningResetFetchError').show();
                    }
                });
            });

            $('#btn-confirm-learning-reset').on('click', function() {
                const type = $('#reset-source-type').val();
                const id = $('#reset-source-id').val();
                if (!type || !id) return;

                $('#reset-confirm-spinner').show();
                $('#reset-confirm-text').text('Resetting...');
                $('#btn-confirm-learning-reset').prop('disabled', true);
                $('#learningResetSubmitError').hide();

                $.ajax({
                    url: `/manage/learning/record/${type}/${id}/reset`,
                    type: 'POST',
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') ||
                            '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    success: function(res) {
                        $('#reset-confirm-spinner').hide();
                        $('#reset-confirm-text').text('Reset Progress');
                        $('#btn-confirm-learning-reset').prop('disabled', false);

                        const resetModalEl = document.getElementById('learningResetModal');
                        if (resetModalEl) {
                            const resetModal = bootstrap.Modal.getInstance(resetModalEl);
                            if (resetModal) resetModal.hide();
                        }

                        if (participantTable) {
                            participantTable.ajax.reload(null, false);
                        }
                        refreshParticipantKpiSummary();
                    },
                    error: function(xhr) {
                        $('#reset-confirm-spinner').hide();
                        $('#reset-confirm-text').text('Reset Progress');
                        $('#btn-confirm-learning-reset').prop('disabled', false);
                        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr
                            .responseJSON.message : 'Failed to reset record.';
                        $('#learningResetSubmitErrorMessage').text(msg);
                        $('#learningResetSubmitError').show();
                    }
                });
            });

            // 6. Delete Online Learning Record Modal Logic
            $(document).on('click', '.btn-delete-online-record', function() {
                const id = $(this).data('id');
                const type = $(this).data('type') || 'content';
                const employee = $(this).data('employee') || 'Participant';
                const nip = $(this).data('nip') || '';
                const title = $(this).data('title') || 'Content';

                $('#delete-online-content-title').text(title);
                $('#delete-online-employee-name').text(employee);
                $('#delete-online-type-badge').text(type.toUpperCase());
                if (nip) {
                    $('#delete-online-nip').text(nip);
                    $('#delete-online-nip-wrapper').show();
                } else {
                    $('#delete-online-nip-wrapper').hide();
                }

                $('#btn-confirm-delete-online-record').data('id', id).data('type', type);

                const deleteModalEl = document.getElementById('deleteOnlineRecordModal');
                if (deleteModalEl) {
                    const deleteModal = bootstrap.Modal.getInstance(deleteModalEl) || new bootstrap.Modal(
                        deleteModalEl);
                    deleteModal.show();
                }
            });

            $('#btn-confirm-delete-online-record').on('click', function() {
                const id = $(this).data('id');
                const type = $(this).data('type');
                if (!id || !type) return;

                const $btn = $(this);
                const originalHtml = $btn.html();
                $btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Deleting...'
                    );

                $.ajax({
                    url: `/manage/record/online/${type}/${id}`,
                    type: 'DELETE',
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') ||
                            '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    success: function(res) {
                        $btn.prop('disabled', false).html(originalHtml);
                        const deleteModalEl = document.getElementById(
                        'deleteOnlineRecordModal');
                        if (deleteModalEl) {
                            const deleteModal = bootstrap.Modal.getInstance(deleteModalEl);
                            if (deleteModal) deleteModal.hide();
                        }
                        if (participantTable) {
                            participantTable.ajax.reload(null, false);
                        }
                        refreshParticipantKpiSummary();
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).html(originalHtml);
                        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr
                            .responseJSON.message : 'Failed to delete record.';
                        alert(msg);
                    }
                });
            });

            // 7. Delete Master Content / Playlist Modal Logic
            $(document).on('click', '.btn-delete-content-master, .btn-delete-playlist-master', function() {
                const slug = $(this).data('slug');
                const title = $(this).data('title') || 'Master Entity';
                const type = $(this).data('type') || 'content';

                $('#delete-master-title').text(title);
                $('#delete-master-type-badge').text(type.toUpperCase());
                $('#btn-confirm-delete-master').data('slug', slug).data('type', type);

                const deleteModalEl = document.getElementById('deleteMasterModal');
                if (deleteModalEl) {
                    const deleteModal = bootstrap.Modal.getInstance(deleteModalEl) || new bootstrap.Modal(
                        deleteModalEl);
                    deleteModal.show();
                }
            });

            $('#btn-confirm-delete-master').on('click', function() {
                const slug = $(this).data('slug');
                const type = $(this).data('type');
                if (!slug || !type) return;

                const $btn = $(this);
                const originalHtml = $btn.html();
                $btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Deleting...'
                    );

                const url = type === 'content' ?
                    `/manage/content/${slug}` :
                    `/manage/playlist/${slug}`;

                $.ajax({
                    url: url,
                    type: 'DELETE',
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') ||
                            '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    success: function(res) {
                        $btn.prop('disabled', false).html(originalHtml);
                        const deleteModalEl = document.getElementById('deleteMasterModal');
                        if (deleteModalEl) {
                            const deleteModal = bootstrap.Modal.getInstance(deleteModalEl);
                            if (deleteModal) deleteModal.hide();
                        }
                        if (participantTable) {
                            participantTable.ajax.reload(null, false);
                        }
                        refreshParticipantKpiSummary();
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).html(originalHtml);
                        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr
                            .responseJSON.message : 'Failed to delete master entity.';
                        alert(msg);
                    }
                });
            });

            // 8. Close open dropdowns when table scrolls horizontally
            $('#all-participant-learning-table').closest('.table-responsive').on('scroll', function() {
                const openDropdown = $('#all-participant-learning-table .dropdown-menu.show');
                if (openDropdown.length) {
                    const dropdownBtn = openDropdown.closest('.dropdown').find(
                        '[data-bs-toggle="dropdown"]');
                    if (dropdownBtn.length && typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
                        const instance = bootstrap.Dropdown.getInstance(dropdownBtn[0]);
                        if (instance) instance.hide();
                    }
                }
            });
        });
    </script>
@endsection
