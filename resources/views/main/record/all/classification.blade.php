@extends('layouts.sidebar-master')

@section('content')
    <div class="page-body manage-record-page">
        <div class="container-xl">
            <!-- TOMBOL BACK RATA KIRI ATAS -->
            <div class="mb-3">
                <a href="{{ route('manage.record.all') }}"
                    class="btn-back d-inline-flex align-items-center gap-1 text-decoration-none fw-medium">
                    <i class="ti ti-arrow-left"></i>
                    <span>Back to All Record</span>
                </a>
            </div>

            <!-- SECTION HEADER: Classification Detail -->
            <div class="card border-0 shadow-sm p-3 p-md-4 mb-4 rounded-3 bg-white record-section-header">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 record-section-icon"
                            style="width: 54px; height: 54px; background-color: #ede9fe; color: #7c3aed; border-radius: 12px;">
                            <i class="ti ti-category fs-1"></i>
                        </div>
                        <div>
                            <h2 class="h3 fw-bold mb-1 text-dark">Classification: {{ $classificationName }}</h2>
                            <p class="text-muted small mb-0">Combined Online &amp; Offline Learning Records for this
                                classification.</p>
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
                    <form id="all-filter-form" onsubmit="return false;">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <!-- 1. Employee / NIP Search Input -->
                            <div class="position-relative flex-grow-1" style="min-width: 220px; max-width: 280px;">
                                <input type="text" id="all-filter-employee" name="employee_id"
                                    class="form-control rounded-3" placeholder="Search Employee / NIP..."
                                    autocomplete="off">
                            </div>

                            <!-- 2. Learning Title Search Input -->
                            <div class="position-relative flex-grow-1" style="min-width: 240px; max-width: 320px;">
                                <input type="text" id="all-filter-title" name="learning_title"
                                    class="form-control rounded-3" placeholder="Search Learning Title..."
                                    autocomplete="off">
                            </div>

                            <!-- 3. Source Filter Dropdown -->
                            <div style="min-width: 140px; flex-shrink: 0;">
                                <select id="all-filter-source" name="source" class="form-select rounded-3">
                                    <option value="all">All Sources</option>
                                    <option value="online">KP OWL (Online)</option>
                                    <option value="offline">Non KP OWL (Offline)</option>
                                </select>
                            </div>

                            <!-- 4. Status Filter Dropdown -->
                            <div style="min-width: 125px; flex-shrink: 0;">
                                <select id="all-filter-status" name="status" class="form-select rounded-3">
                                    <option value="all">All Status</option>
                                    <option value="finished">Finished</option>
                                    <option value="ongoing">Ongoing</option>
                                    <option value="not_started">Not Started</option>
                                </select>
                            </div>

                            <!-- 5. Year Filter Dropdown -->
                            <div style="min-width: 105px; flex-shrink: 0;">
                                <select id="all-filter-year" name="year" class="form-select rounded-3">
                                    <option value="all">All Year</option>
                                    @foreach ($years as $year)
                                        <option value="{{ $year }}">{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- 6. Search Button (Blue primary square button) -->
                            <button type="button" id="btn-all-filter" class="btn btn-primary btn-icon px-3 flex-shrink-0"
                                style="background-color: #003b6d; border-color: #003b6d;" data-bs-toggle="tooltip"
                                data-bs-title="Apply Filter" title="Apply Filter">
                                <i class="ti ti-search fs-2"></i>
                            </button>

                            <!-- 7. More Filters Button (Outlined blue with active badge) -->
                            <button type="button" id="btn-all-more-filters"
                                class="btn btn-outline-primary btn-icon px-3 position-relative btn-more-filters flex-shrink-0"
                                data-bs-toggle="modal" data-bs-target="#moreAllFiltersModal" title="More Filters">
                                <i class="ti ti-adjustments-horizontal fs-2"></i>
                                <span id="activeFilterBadge"
                                    class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle d-none">0</span>
                            </button>

                            <!-- 8. Reset Button (Outlined neutral) -->
                            <button type="button" id="btn-all-reset"
                                class="btn btn-outline-secondary btn-icon px-3 btn-quick-reset flex-shrink-0"
                                data-bs-toggle="tooltip" data-bs-title="Reset Filters" title="Reset Filters">
                                <i class="ti ti-refresh fs-2"></i>
                            </button>

                            <!-- 9. Download Dropdown (Green button with dropdown items) -->
                            @can('learning_management')
                            <div class="dropdown flex-shrink-0">
                                <button type="button" id="btn-all-download"
                                    class="btn btn-success rounded-3 px-3 dropdown-toggle d-inline-flex align-items-center gap-1"
                                    data-bs-toggle="dropdown" aria-expanded="false" title="Export Learning Records">
                                    <i class="ti ti-download me-1"></i>
                                    <span class="btn-download-text">Download</span>
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
                                        <a class="dropdown-item d-flex align-items-center gap-2 py-2 export-format-btn"
                                            href="#" data-format="xlsx">
                                            <i class="ti ti-file-spreadsheet text-success fs-2"></i>
                                            <div>
                                                <div class="fw-semibold">Excel (.xlsx)</div>
                                                <div class="text-muted small" style="font-size: 0.75rem;">Full dataset
                                                    spreadsheet</div>
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center gap-2 py-2 export-format-btn"
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
                                        <a class="dropdown-item d-flex align-items-center gap-2 py-2 export-format-btn"
                                            href="#" data-format="pdf">
                                            <i class="ti ti-file-type-pdf text-danger fs-2"></i>
                                            <div>
                                                <div class="fw-semibold">PDF Landscape (.pdf)</div>
                                                <div class="text-muted small" style="font-size: 0.75rem;">Printable report
                                                    (max 200 rows)</div>
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

            <!-- COMBINED LEARNING RECORDS TABLE CARD -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div
                    class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h4 class="card-title fw-bold text-dark mb-0">Combined Learning Records</h4>
                        <p class="text-muted small mb-0">Participant records for classification
                            <strong>{{ $classificationName }}</strong>.</p>
                    </div>
                </div>

                <div class="card-body p-0">
                    <!-- Top Synchronized Horizontal Scrollbar -->
                    <div class="table-scroll-top-wrapper" id="table-scroll-top-wrapper">
                        <div class="table-scroll-top-inner" id="table-scroll-top-inner"></div>
                    </div>

                    <!-- Primary Table Container with Horizontal & Vertical Scrollbar -->
                    <div class="table-responsive table-scroll-container" id="table-scroll-container">
                        <table class="table table-hover align-middle mb-0" id="all-classification-learning-table">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr>
                                    <th style="width: 45px; text-align: center;">NO</th>
                                    <th class="text-center align-middle whitespace-nowrap px-3" style="width: 1%;">SOURCE</th>
                                    <th style="min-width: 110px;">EMPLOYEE ID</th>
                                    <th style="min-width: 160px;">EMPLOYEE NAME</th>
                                    <th style="min-width: 130px;">TEAM</th>
                                    <th style="min-width: 140px;">DEPARTMENT</th>
                                    <th style="min-width: 130px;">COMPANY</th>
                                    <th class="text-center" style="min-width: 220px; text-align: center;">COURSE TITLE</th>
                                    <th style="min-width: 100px;">TYPE</th>
                                    <th style="min-width: 110px;">CATEGORY</th>
                                    <th style="min-width: 140px;">MAIN CLASSIFICATION</th>
                                    <th style="min-width: 140px;">SUB CLASSIFICATION</th>
                                    <th style="min-width: 120px;">DELIVERY METHOD</th>
                                    <th style="min-width: 130px;">SUB DELIVERY METHOD</th>
                                    <th style="min-width: 110px;">PROVIDER</th>
                                    <th style="min-width: 130px;">PROVIDER NAME</th>
                                    <th style="min-width: 120px;">VENUE</th>
                                    <th style="min-width: 110px;">COURSE DURATION</th>
                                    <th style="min-width: 130px;">TOTAL RECOGNIZED DURATION</th>
                                    <th style="min-width: 110px;">LEARNING HOURS</th>
                                    <th style="min-width: 110px;">% PROGRESS</th>
                                    <th style="min-width: 110px;">PROGRESS STATUS</th>
                                    <th style="min-width: 110px;">START DATE</th>
                                    <th style="min-width: 110px;">END DATE</th>
                                    <th style="min-width: 100px;">START TIME</th>
                                    <th style="min-width: 100px;">END TIME</th>
                                    <th style="min-width: 110px;">RECORD MONTH</th>
                                    <th style="min-width: 110px;">REQUIREMENT</th>
                                    <th style="min-width: 100px;">ATTENDANCE</th>
                                    <th style="min-width: 120px;">SCORE (L1) - COURSE</th>
                                    <th style="min-width: 90px;">SCORE (L2)</th>
                                    <th style="min-width: 90px;">SCORE (L3)</th>
                                    <th style="min-width: 130px;">CERTIFICATE AVAILABILITY</th>

                                    <th style="min-width: 140px;">TOTAL ENGAGEMENT TIME (LH)</th>
                                    <th class="text-end" style="width: 80px;">ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Server-side DataTable rows injected dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Custom Pagination Footer Container -->
                <div class="card-footer bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2"
                    id="all-table-footer">
                    <div class="d-flex align-items-center gap-2">
                        <select id="all-table-entries-select" class="form-select form-select-sm w-auto rounded-3">
                            <option value="10">10 Entries</option>
                            <option value="25" selected>25 Entries</option>
                            <option value="50">50 Entries</option>
                            <option value="100">100 Entries</option>
                        </select>
                        <div class="text-muted small" id="all-table-info">
                            Showing 0 to 0 of 0 entries
                        </div>
                    </div>
                    <div id="all-table-pagination"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- MORE FILTERS MODAL FOR  -->
    <div class="modal fade" id="moreAllFiltersModal" tabindex="-1" aria-labelledby="moreAllFiltersModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow">
                <form id="more-all-filters-modal-form" onsubmit="return false;">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2"
                            id="moreAllFiltersModalLabel">
                            <i class="ti ti-adjustments-horizontal text-primary"></i> More Filters
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body py-3">
                        <!-- GROUP A: Finish Date Range -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark small"><i class="ti ti-calendar me-1"></i> Date
                                Range (Finish Date)</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small text-muted mb-1">From Date</label>
                                    <input type="text" id="modal-from-date" name="from_date"
                                        onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'"
                                        class="form-control rounded-3" placeholder="dd/mm/yyyy"
                                        value="{{ request('from_date') ?? request('date_from') }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small text-muted mb-1">To Date</label>
                                    <input type="text" id="modal-to-date" name="to_date" onfocus="(this.type='date')"
                                        onblur="if(!this.value)this.type='text'" class="form-control rounded-3"
                                        placeholder="dd/mm/yyyy" value="{{ request('to_date') ?? request('date_to') }}">
                                </div>
                            </div>
                        </div>

                        <!-- GROUP B: Progress Range -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark small d-block">Progress Range</label>
                            @php $currProgress = request('progress_range', 'all'); @endphp
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_progress_range"
                                        id="prAll" value="all"
                                        {{ $currProgress === 'all' || blank($currProgress) ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="prAll">All</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_progress_range"
                                        id="pr0" value="0" {{ $currProgress === '0' ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="pr0">0%</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_progress_range"
                                        id="pr25" value="1-25" {{ $currProgress === '1-25' ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="pr25">1 - 25%</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_progress_range"
                                        id="pr50" value="26-50" {{ $currProgress === '26-50' ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="pr50">26 - 50%</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_progress_range"
                                        id="pr75" value="51-75" {{ $currProgress === '51-75' ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="pr75">51 - 75%</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_progress_range"
                                        id="pr99" value="76-99" {{ $currProgress === '76-99' ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="pr99">76 - 99%</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_progress_range"
                                        id="pr100" value="100" {{ $currProgress === '100' ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="pr100">100%</label>
                                </div>
                            </div>
                        </div>

                        <!-- GROUP C: Participant Learning Hours -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark small d-block">Participant Learning Hours</label>
                            @php $currHours = request('learning_hours_range', 'all'); @endphp
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_learning_hours"
                                        id="hrAll" value="all"
                                        {{ $currHours === 'all' || blank($currHours) ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="hrAll">All Hours</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_learning_hours"
                                        id="hrLt05" value="lt05" {{ $currHours === 'lt05' ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="hrLt05">&lt; 0.5 Hour</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_learning_hours"
                                        id="hr051" value="05-1" {{ $currHours === '05-1' ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="hr051">0.5 – &lt; 1 Hour</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_learning_hours"
                                        id="hr12" value="1-2" {{ $currHours === '1-2' ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="hr12">1 – &lt; 2 Hours</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_learning_hours"
                                        id="hr24" value="2-4" {{ $currHours === '2-4' ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="hr24">2 – &lt; 4 Hours</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="modal_learning_hours"
                                        id="hrGte4" value="gte4" {{ $currHours === 'gte4' ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="hrGte4">&ge; 4 Hours</label>
                                </div>
                            </div>
                        </div>

                        <!-- GROUP D & E: Department & Company -->
                        <div class="row g-2">
                            <div class="col-12 col-md-6 mb-2">
                                <label class="form-label fw-bold text-dark small mb-1">Department</label>
                                <select id="modal-department" name="department" class="form-select rounded-3">
                                    <option value="all">All Departments</option>
                                    @foreach ($departments ?? [] as $dept)
                                        <option value="{{ $dept }}"
                                            {{ request('department') === $dept ? 'selected' : '' }}>{{ $dept }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-6 mb-2">
                                <label class="form-label fw-bold text-dark small mb-1">Company</label>
                                <select id="modal-company" name="company" class="form-select rounded-3">
                                    <option value="all">All Companies</option>
                                    @foreach ($companies ?? [] as $comp)
                                        <option value="{{ $comp }}"
                                            {{ request('company') === $comp ? 'selected' : '' }}>{{ $comp }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light d-flex justify-content-end align-items-center gap-2">
                        <button type="button" id="btn-modal-reset"
                            class="btn btn-outline-secondary px-3 d-inline-flex align-items-center gap-1">
                            <i class="ti ti-rotate me-1"></i>
                            <span>Reset</span>
                        </button>
                        <button type="button" id="btn-modal-apply"
                            class="btn btn-primary px-3 d-inline-flex align-items-center gap-1"
                            style="background-color: #0c5b9e; border-color: #0c5b9e;" data-bs-dismiss="modal">
                            <i class="ti ti-check me-1"></i>
                            <span>Apply Filter</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- PDF LIMIT WARNING MODAL -->
    <div class="modal fade" id="pdfLimitWarningModal" tabindex="-1" aria-labelledby="pdfLimitWarningModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2"
                        id="pdfLimitWarningModalLabel">
                        <span class="rounded-circle d-inline-flex align-items-center justify-content-center"
                            style="width: 32px; height: 32px; background-color: #fef3c7; color: #d97706;">
                            <i class="ti ti-file-alert fs-2"></i>
                        </span>
                        <span>PDF Export Limit Exceeded</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-2 pb-3">
                    <p class="text-muted small mb-3">
                        PDF Landscape export is designed for printable reports (A2 Landscape, 46 columns) and is restricted
                        to a maximum of <strong>200 records</strong> to ensure readability and prevent memory limits.
                    </p>
                    <div class="p-3 rounded-3 mb-3 bg-light border">
                        <div class="d-flex align-items-center justify-content-between mb-1 small">
                            <span class="text-muted">Current Filtered Records:</span>
                            <span class="badge bg-danger fs-6" id="pdfLimitCurrentCount">0 records</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between small">
                            <span class="text-muted">Maximum PDF Limit:</span>
                            <span class="badge bg-secondary fs-6">200 records</span>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">
                        To export the complete dataset, please download via <strong>Excel (.xlsx)</strong> or <strong>CSV
                            (.csv)</strong>, or use the filters (Employee, Year, Status, Date Range) to narrow the result
                        under 200 records.
                    </p>
                </div>
                <div class="modal-footer bg-light border-0 d-flex justify-content-end align-items-center gap-2">
                    <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">Close</button>
                    <button type="button" id="btn-pdf-fallback-csv"
                        class="btn btn-outline-primary px-3 d-inline-flex align-items-center gap-1">
                        <i class="ti ti-file-text me-1"></i>
                        <span>Download CSV</span>
                    </button>
                    <button type="button" id="btn-pdf-fallback-excel"
                        class="btn btn-success px-3 d-inline-flex align-items-center gap-1">
                        <i class="ti ti-file-spreadsheet me-1"></i>
                        <span>Download Excel</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- INCLUDE RECORD DETAIL MODAL (CANONICAL 18 FIELDS) -->
    @include('main.record.components.record-detail-modal')

    <!-- INCLUDE OFFLINE DETAIL MODAL -->
    @include('main.record.components.more-information-modal')
    @include('main.record.components.offline-detail-modal')

    <!-- INCLUDE ONLINE DETAIL MODAL -->
    @include('main.record.components.online-detail-modal')

    <!-- INCLUDE OFFLINE DELETE MODAL -->
    @include('main.record.components.offline-delete-modal')

    <!-- INCLUDE ONLINE RECORD DELETE MODAL -->
    @include('main.record.components.online-delete-modal')

    <!-- INCLUDE MASTER ENTITY DELETE MODAL -->
    @include('main.record.components.master-delete-modal')

    <!-- INCLUDE LEARNING EDIT & RESET MODALS -->
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

        /* Table Scroll Containers & Dual Synchronized Scrollbars */
        .table-scroll-top-wrapper {
            overflow-x: auto;
            overflow-y: hidden;
            height: 12px;
            width: 100%;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f8fafc;
            border-bottom: 1px solid #f1f5f9;
        }

        .table-scroll-top-wrapper::-webkit-scrollbar {
            height: 8px;
        }

        .table-scroll-top-wrapper::-webkit-scrollbar-track {
            background: #f8fafc;
        }

        .table-scroll-top-wrapper::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .table-scroll-top-wrapper::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .table-responsive,
        .table-scroll-container {
            max-height: 70vh;
            overflow-y: auto;
            position: relative;
        }

        .table-scroll-container {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            overflow-y: auto;
            max-height: 70vh;
            position: relative;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: #94a3b8 #f1f5f9;
        }

        .table-scroll-container::-webkit-scrollbar {
            height: 10px;
        }

        .table-scroll-container::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        .table-scroll-container::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 5px;
        }

        .table-scroll-container::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }

        /* Ensure Table has Real Full Width */
        #all-classification-learning-table {
            border-collapse: separate;
            border-spacing: 0;
            width: max-content !important;
            min-width: 100%;
            margin-bottom: 0;
        }

        #all-classification-learning-table thead th {
            position: sticky !important;
            top: 0 !important;
            z-index: 10 !important;
            background-color: #f8fafc !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            font-size: 0.72rem;
            letter-spacing: 0.03em;
            font-weight: 700;
            color: #64748b;
            border-bottom: 2px solid #e2e8f0;
            padding: 10px 12px;
            white-space: nowrap;
            text-transform: uppercase;
        }

        #all-classification-learning-table tbody td {
            padding: 10px 12px;
            font-size: 0.825rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            white-space: nowrap;
            background-color: #ffffff;
        }

        #all-classification-learning-table tbody tr:hover td {
            background-color: #f8fafc;
        }

        /* Sticky Frozen Columns - Left (Cols 1-4: No, Source, ID, Employee) */
        #all-classification-learning-table th:nth-child(1),
        #all-classification-learning-table td:nth-child(1) {
            position: sticky;
            left: 0;
            z-index: 2;
            min-width: 40px;
            max-width: 40px;
            width: 40px;
            background-color: #ffffff;
        }

        #all-classification-learning-table th:nth-child(2),
        #all-classification-learning-table td:nth-child(2) {
            position: sticky;
            left: 40px;
            z-index: 2;
            min-width: 85px;
            max-width: 85px;
            width: 85px;
            background-color: #ffffff;
        }

        #all-classification-learning-table th:nth-child(3),
        #all-classification-learning-table td:nth-child(3) {
            position: sticky;
            left: 125px;
            z-index: 2;
            min-width: 75px;
            max-width: 75px;
            width: 75px;
            background-color: #ffffff;
        }

        #all-classification-learning-table th:nth-child(4),
        #all-classification-learning-table td:nth-child(4) {
            position: sticky;
            left: 200px;
            z-index: 2;
            min-width: 260px;
            max-width: 260px;
            width: 260px;
            background-color: #ffffff;
            box-shadow: 4px 0 6px -2px rgba(0, 0, 0, 0.1);
        }

        /* Header Sticky z-index higher than body */
        #all-classification-learning-table thead th:nth-child(1),
        #all-classification-learning-table thead th:nth-child(2),
        #all-classification-learning-table thead th:nth-child(3),
        #all-classification-learning-table thead th:nth-child(4) {
            position: sticky !important;
            top: 0 !important;
            z-index: 12 !important;
            background-color: #f8fafc !important;
        }

        /* Hover row on sticky cells */
        #all-classification-learning-table tbody tr:hover td:nth-child(1),
        #all-classification-learning-table tbody tr:hover td:nth-child(2),
        #all-classification-learning-table tbody tr:hover td:nth-child(3),
        #all-classification-learning-table tbody tr:hover td:nth-child(4) {
            background-color: #f8fafc;
        }

        /* Dark Mode Overrides */
        [data-bs-theme="dark"] .table-scroll-top-wrapper,
        body.dark-mode .table-scroll-top-wrapper,
        body.theme-dark .table-scroll-top-wrapper {
            scrollbar-color: #475569 #1e293b;
            border-color: rgba(255, 255, 255, 0.05);
        }

        [data-bs-theme="dark"] .table-scroll-top-wrapper::-webkit-scrollbar-track,
        body.dark-mode .table-scroll-top-wrapper::-webkit-scrollbar-track,
        body.theme-dark .table-scroll-top-wrapper::-webkit-scrollbar-track {
            background: #1e293b;
        }

        [data-bs-theme="dark"] .table-scroll-top-wrapper::-webkit-scrollbar-thumb,
        body.dark-mode .table-scroll-top-wrapper::-webkit-scrollbar-thumb,
        body.theme-dark .table-scroll-top-wrapper::-webkit-scrollbar-thumb {
            background: #475569;
        }

        [data-bs-theme="dark"] .table-scroll-container,
        body.dark-mode .table-scroll-container,
        body.theme-dark .table-scroll-container {
            scrollbar-color: #475569 #1e293b;
        }

        [data-bs-theme="dark"] .table-scroll-container::-webkit-scrollbar-track,
        body.dark-mode .table-scroll-container::-webkit-scrollbar-track,
        body.theme-dark .table-scroll-container::-webkit-scrollbar-track {
            background: #1e293b;
        }

        [data-bs-theme="dark"] .table-scroll-container::-webkit-scrollbar-thumb,
        body.dark-mode .table-scroll-container::-webkit-scrollbar-thumb,
        body.theme-dark .table-scroll-container::-webkit-scrollbar-thumb {
            background: #475569;
        }

        [data-bs-theme="dark"] #all-classification-learning-table thead th,
        body.dark-mode #all-classification-learning-table thead th,
        body.theme-dark #all-classification-learning-table thead th {
            background-color: #1e293b !important;
            color: #94a3b8 !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
        }

        [data-bs-theme="dark"] #all-classification-learning-table tbody td,
        body.dark-mode #all-classification-learning-table tbody td,
        body.theme-dark #all-classification-learning-table tbody td {
            background-color: #0f172a !important;
            border-color: rgba(255, 255, 255, 0.05) !important;
            color: #cbd5e1 !important;
        }

        [data-bs-theme="dark"] #all-classification-learning-table tbody tr:hover td,
        body.dark-mode #all-classification-learning-table tbody tr:hover td,
        body.theme-dark #all-classification-learning-table tbody tr:hover td {
            background-color: #1e293b !important;
        }

        [data-bs-theme="dark"] #all-classification-learning-table th:nth-child(1),
        [data-bs-theme="dark"] #all-classification-learning-table td:nth-child(1),
        [data-bs-theme="dark"] #all-classification-learning-table th:nth-child(2),
        [data-bs-theme="dark"] #all-classification-learning-table td:nth-child(2),
        [data-bs-theme="dark"] #all-classification-learning-table th:nth-child(3),
        [data-bs-theme="dark"] #all-classification-learning-table td:nth-child(3),
        [data-bs-theme="dark"] #all-classification-learning-table th:nth-child(4),
        [data-bs-theme="dark"] #all-classification-learning-table td:nth-child(4) {
            background-color: #0f172a !important;
        }

        [data-bs-theme="dark"] #all-classification-learning-table thead th:nth-child(1),
        [data-bs-theme="dark"] #all-classification-learning-table thead th:nth-child(2),
        [data-bs-theme="dark"] #all-classification-learning-table thead th:nth-child(3),
        [data-bs-theme="dark"] #all-classification-learning-table thead th:nth-child(4) {
            position: sticky !important;
            top: 0 !important;
            z-index: 12 !important;
            background-color: #1e293b !important;
        }

        #all-classification-learning-table_wrapper > .bottom {
            display: none !important;
        }

        [data-bs-theme="dark"] #all-classification-learning-table tbody tr:hover td:nth-child(1),
        [data-bs-theme="dark"] #all-classification-learning-table tbody tr:hover td:nth-child(2),
        [data-bs-theme="dark"] #all-classification-learning-table tbody tr:hover td:nth-child(3),
        [data-bs-theme="dark"] #all-classification-learning-table tbody tr:hover td:nth-child(4) {
            background-color: #1e293b !important;
        }

        [data-bs-theme="dark"] #all-classification-learning-table th:nth-child(4),
        [data-bs-theme="dark"] #all-classification-learning-table td:nth-child(4) {
            box-shadow: 4px 0 6px -2px rgba(0, 0, 0, 0.4);
        }

        .employee-name-cell {
            line-height: 1.25;
            white-space: normal;
            word-break: normal;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 236px;
        }

        [data-bs-theme="dark"] .employee-name-cell,
        body.dark-mode .employee-name-cell,
        body.theme-dark .employee-name-cell {
            color: #f1f5f9 !important;
        }

        .participant-link,
        .course-link {
            color: #0c5b9e;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            transition: color 0.15s ease, text-decoration 0.15s ease;
        }

        .participant-link:hover,
        .course-link:hover,
        .participant-link:focus,
        .course-link:focus {
            color: #003b6d;
            text-decoration: underline !important;
        }

        [data-bs-theme="dark"] .participant-link,
        [data-bs-theme="dark"] .course-link,
        body.dark-mode .participant-link,
        body.dark-mode .course-link,
        body.theme-dark .participant-link,
        body.theme-dark .course-link {
            color: #60a5fa !important;
        }

        [data-bs-theme="dark"] .participant-link:hover,
        [data-bs-theme="dark"] .course-link:hover,
        body.dark-mode .participant-link:hover,
        body.dark-mode .course-link:hover,
        body.theme-dark .participant-link:hover,
        body.theme-dark .course-link:hover {
            color: #93c5fd !important;
            text-decoration: underline !important;
        }

        .extra-small {
            font-size: 0.75rem;
        }

        /* More Filters Modal Dark Mode & Styling */
        [data-bs-theme="dark"] #moreAllFiltersModal .modal-content,
        body.dark-mode #moreAllFiltersModal .modal-content,
        body.theme-dark #moreAllFiltersModal .modal-content {
            background-color: #1e293b !important;
            color: #cbd5e1 !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }

        [data-bs-theme="dark"] #moreAllFiltersModal .modal-header,
        [data-bs-theme="dark"] #moreAllFiltersModal .modal-footer,
        body.dark-mode #moreAllFiltersModal .modal-header,
        body.dark-mode #moreAllFiltersModal .modal-footer,
        body.theme-dark #moreAllFiltersModal .modal-header,
        body.theme-dark #moreAllFiltersModal .modal-footer {
            background-color: #0f172a !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
        }

        [data-bs-theme="dark"] #moreAllFiltersModal .text-dark,
        body.dark-mode #moreAllFiltersModal .text-dark,
        body.theme-dark #moreAllFiltersModal .text-dark {
            color: #f8fafc !important;
        }

        [data-bs-theme="dark"] #moreAllFiltersModal .form-control,
        [data-bs-theme="dark"] #moreAllFiltersModal .form-select,
        body.dark-mode #moreAllFiltersModal .form-control,
        body.dark-mode #moreAllFiltersModal .form-select,
        body.theme-dark #moreAllFiltersModal .form-control,
        body.theme-dark #moreAllFiltersModal .form-select {
            background-color: #0f172a !important;
            color: #f1f5f9 !important;
            border-color: rgba(255, 255, 255, 0.15) !important;
        }

        [data-bs-theme="dark"] #moreAllFiltersModal .form-check-label,
        body.dark-mode #moreAllFiltersModal .form-check-label,
        body.theme-dark #moreAllFiltersModal .form-check-label {
            color: #cbd5e1 !important;
        }

        /* Quick Filter Dark Mode */
        [data-bs-theme="dark"] #all-filter-form .form-control,
        [data-bs-theme="dark"] #all-filter-form .form-select,
        body.dark-mode #all-filter-form .form-control,
        body.dark-mode #all-filter-form .form-select,
        body.theme-dark #all-filter-form .form-control,
        body.theme-dark #all-filter-form .form-select {
            background-color: #1e293b !important;
            color: #f1f5f9 !important;
            border-color: rgba(255, 255, 255, 0.15) !important;
        }

        /* Export Dropdown Dark Mode */
        [data-bs-theme="dark"] .dropdown-menu,
        body.dark-mode .dropdown-menu,
        body.theme-dark .dropdown-menu {
            background-color: #1e293b !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }

        [data-bs-theme="dark"] .dropdown-item,
        body.dark-mode .dropdown-item,
        body.theme-dark .dropdown-item {
            color: #cbd5e1 !important;
        }

        [data-bs-theme="dark"] .dropdown-item:hover,
        body.dark-mode .dropdown-item:hover,
        body.theme-dark .dropdown-item:hover {
            background-color: #0f172a !important;
            color: #ffffff !important;
        }

        [data-bs-theme="dark"] .dropdown-divider,
        body.dark-mode .dropdown-divider,
        body.theme-dark .dropdown-divider {
            border-color: rgba(255, 255, 255, 0.08) !important;
        }

        /* PDF Limit Modal Dark Mode */
        [data-bs-theme="dark"] #pdfLimitWarningModal .modal-content,
        body.dark-mode #pdfLimitWarningModal .modal-content,
        body.theme-dark #pdfLimitWarningModal .modal-content {
            background-color: #1e293b !important;
            color: #cbd5e1 !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }

        [data-bs-theme="dark"] #pdfLimitWarningModal .modal-header,
        [data-bs-theme="dark"] #pdfLimitWarningModal .modal-footer,
        body.dark-mode #pdfLimitWarningModal .modal-header,
        body.dark-mode #pdfLimitWarningModal .modal-footer,
        body.theme-dark #pdfLimitWarningModal .modal-header,
        body.theme-dark #pdfLimitWarningModal .modal-footer {
            background-color: #0f172a !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
        }

        [data-bs-theme="dark"] #pdfLimitWarningModal .text-dark,
        body.dark-mode #pdfLimitWarningModal .text-dark,
        body.theme-dark #pdfLimitWarningModal .text-dark {
            color: #f8fafc !important;
        }

        [data-bs-theme="dark"] #pdfLimitWarningModal .bg-light,
        body.dark-mode #pdfLimitWarningModal .bg-light,
        body.theme-dark #pdfLimitWarningModal .bg-light {
            background-color: #0f172a !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
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
            min-width: 175px;
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
            background-color: rgba(239, 68, 68, 0.15);
            color: #f87171 !important;
        }

        /* Modal Dark Mode styles for Offline, Online detail, delete & learning edit modals */
        [data-bs-theme="dark"] #offlineTrainingDetailModal .modal-content,
        [data-bs-theme="dark"] #onlineTrainingDetailModal .modal-content,
        [data-bs-theme="dark"] #deleteOfflineRecordModal .modal-content,
        [data-bs-theme="dark"] #deleteOnlineRecordModal .modal-content,
        [data-bs-theme="dark"] #deleteMasterModal .modal-content,
        [data-bs-theme="dark"] #learningEditModal .modal-content,
        [data-bs-theme="dark"] #learningResetModal .modal-content,
        body.dark-mode #offlineTrainingDetailModal .modal-content,
        body.dark-mode #onlineTrainingDetailModal .modal-content,
        body.dark-mode #deleteOfflineRecordModal .modal-content,
        body.dark-mode #deleteOnlineRecordModal .modal-content,
        body.dark-mode #deleteMasterModal .modal-content,
        body.dark-mode #learningEditModal .modal-content,
        body.dark-mode #learningResetModal .modal-content,
        body.theme-dark #offlineTrainingDetailModal .modal-content,
        body.theme-dark #onlineTrainingDetailModal .modal-content,
        body.theme-dark #deleteOfflineRecordModal .modal-content,
        body.theme-dark #deleteOnlineRecordModal .modal-content,
        body.theme-dark #deleteMasterModal .modal-content,
        body.theme-dark #learningEditModal .modal-content,
        body.theme-dark #learningResetModal .modal-content {
            background-color: #1e293b !important;
            color: #f8fafc !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }

        [data-bs-theme="dark"] #offlineTrainingDetailModal .bg-light,
        [data-bs-theme="dark"] #onlineTrainingDetailModal .bg-light,
        [data-bs-theme="dark"] #deleteOfflineRecordModal .bg-light,
        [data-bs-theme="dark"] #deleteOnlineRecordModal .bg-light,
        [data-bs-theme="dark"] #deleteMasterModal .bg-light,
        [data-bs-theme="dark"] #learningEditModal .bg-light,
        [data-bs-theme="dark"] #learningResetModal .bg-light,
        body.dark-mode #offlineTrainingDetailModal .bg-light,
        body.dark-mode #onlineTrainingDetailModal .bg-light,
        body.dark-mode #deleteOfflineRecordModal .bg-light,
        body.dark-mode #deleteOnlineRecordModal .bg-light,
        body.dark-mode #deleteMasterModal .bg-light,
        body.dark-mode #learningEditModal .bg-light,
        body.dark-mode #learningResetModal .bg-light,
        body.theme-dark #offlineTrainingDetailModal .bg-light,
        body.theme-dark #onlineTrainingDetailModal .bg-light,
        body.theme-dark #deleteOfflineRecordModal .bg-light,
        body.theme-dark #deleteOnlineRecordModal .bg-light,
        body.theme-dark #deleteMasterModal .bg-light,
        body.theme-dark #learningEditModal .bg-light,
        body.theme-dark #learningResetModal .bg-light {
            background-color: #0f172a !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
        }

        [data-bs-theme="dark"] #offlineTrainingDetailModal .text-dark,
        [data-bs-theme="dark"] #onlineTrainingDetailModal .text-dark,
        [data-bs-theme="dark"] #deleteOfflineRecordModal .text-dark,
        [data-bs-theme="dark"] #deleteOnlineRecordModal .text-dark,
        [data-bs-theme="dark"] #deleteMasterModal .text-dark,
        [data-bs-theme="dark"] #learningEditModal .text-dark,
        [data-bs-theme="dark"] #learningResetModal .text-dark,
        body.dark-mode #offlineTrainingDetailModal .text-dark,
        body.dark-mode #onlineTrainingDetailModal .text-dark,
        body.dark-mode #deleteOfflineRecordModal .text-dark,
        body.dark-mode #deleteOnlineRecordModal .text-dark,
        body.dark-mode #deleteMasterModal .text-dark,
        body.dark-mode #learningEditModal .text-dark,
        body.dark-mode #learningResetModal .text-dark,
        body.theme-dark #offlineTrainingDetailModal .text-dark,
        body.theme-dark #onlineTrainingDetailModal .text-dark,
        body.theme-dark #deleteOfflineRecordModal .text-dark,
        body.theme-dark #deleteOnlineRecordModal .text-dark,
        body.theme-dark #deleteMasterModal .text-dark,
        body.theme-dark #learningEditModal .text-dark,
        body.theme-dark #learningResetModal .text-dark {
            color: #f8fafc !important;
        }

        /* Quick Filter Custom Button Colors (More Filters & Download) */
        #all-filter-form #btn-all-more-filters,
        #btn-all-more-filters {
            background-color: #045789 !important;
            border-color: #045789 !important;
            color: #ffffff !important;
            transition: background-color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
        }

        #all-filter-form #btn-all-more-filters i,
        #btn-all-more-filters i {
            color: #ffffff !important;
        }

        #all-filter-form #btn-all-more-filters:hover,
        #all-filter-form #btn-all-more-filters:focus,
        #btn-all-more-filters:hover,
        #btn-all-more-filters:focus {
            background-color: #03466D !important;
            border-color: #03466D !important;
            color: #ffffff !important;
            box-shadow: 0 4px 10px rgba(4, 87, 137, 0.25);
        }

        #all-filter-form #btn-all-more-filters:active,
        #all-filter-form #btn-all-more-filters.active,
        #btn-all-more-filters:active,
        #btn-all-more-filters.active {
            background-color: #023956 !important;
            border-color: #023956 !important;
            color: #ffffff !important;
        }

        #all-filter-form #btn-all-more-filters:disabled,
        #btn-all-more-filters:disabled {
            background-color: #045789 !important;
            border-color: #045789 !important;
            color: #ffffff !important;
            opacity: 0.65;
        }

        #all-filter-form #btn-all-download,
        #btn-all-download {
            background-color: #22C55E !important;
            border-color: #22C55E !important;
            color: #ffffff !important;
            font-weight: 500;
            transition: background-color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
        }

        #all-filter-form #btn-all-download i,
        #all-filter-form #btn-all-download .btn-download-text,
        #btn-all-download i,
        #btn-all-download .btn-download-text {
            color: #ffffff !important;
        }

        #all-filter-form #btn-all-download:hover,
        #all-filter-form #btn-all-download:focus,
        #btn-all-download:hover,
        #btn-all-download:focus {
            background-color: #16A34A !important;
            border-color: #16A34A !important;
            color: #ffffff !important;
            box-shadow: 0 4px 10px rgba(34, 197, 94, 0.25);
        }

        #all-filter-form #btn-all-download:active,
        #all-filter-form #btn-all-download.active,
        #all-filter-form #btn-all-download.show,
        #all-filter-form #btn-all-download[aria-expanded="true"],
        #btn-all-download:active,
        #btn-all-download.active,
        #btn-all-download.show,
        #btn-all-download[aria-expanded="true"] {
            background-color: #15803D !important;
            border-color: #15803D !important;
            color: #ffffff !important;
        }

        #all-filter-form #btn-all-download:disabled,
        #btn-all-download:disabled {
            background-color: #22C55E !important;
            border-color: #22C55E !important;
            color: #ffffff !important;
            opacity: 0.65;
        }

        /* Dark Mode Support for Quick Filter Buttons */
        [data-bs-theme="dark"] #all-filter-form #btn-all-more-filters,
        [data-bs-theme="dark"] #btn-all-more-filters,
        body.dark-mode #btn-all-more-filters,
        body.theme-dark #btn-all-more-filters {
            background-color: #045789 !important;
            border-color: #045789 !important;
            color: #ffffff !important;
        }

        [data-bs-theme="dark"] #all-filter-form #btn-all-more-filters i,
        [data-bs-theme="dark"] #btn-all-more-filters i,
        body.dark-mode #btn-all-more-filters i,
        body.theme-dark #btn-all-more-filters i {
            color: #ffffff !important;
        }

        [data-bs-theme="dark"] #all-filter-form #btn-all-more-filters:hover,
        [data-bs-theme="dark"] #all-filter-form #btn-all-more-filters:focus,
        [data-bs-theme="dark"] #btn-all-more-filters:hover,
        [data-bs-theme="dark"] #btn-all-more-filters:focus,
        body.dark-mode #btn-all-more-filters:hover,
        body.theme-dark #btn-all-more-filters:hover {
            background-color: #03466D !important;
            border-color: #03466D !important;
        }

        [data-bs-theme="dark"] #all-filter-form #btn-all-more-filters:active,
        [data-bs-theme="dark"] #btn-all-more-filters:active,
        body.dark-mode #btn-all-more-filters:active,
        body.theme-dark #btn-all-more-filters:active {
            background-color: #023956 !important;
            border-color: #023956 !important;
        }

        [data-bs-theme="dark"] #all-filter-form #btn-all-download,
        [data-bs-theme="dark"] #btn-all-download,
        body.dark-mode #btn-all-download,
        body.theme-dark #btn-all-download {
            background-color: #22C55E !important;
            border-color: #22C55E !important;
            color: #ffffff !important;
        }

        [data-bs-theme="dark"] #all-filter-form #btn-all-download i,
        [data-bs-theme="dark"] #all-filter-form #btn-all-download .btn-download-text,
        [data-bs-theme="dark"] #btn-all-download i,
        [data-bs-theme="dark"] #btn-all-download .btn-download-text,
        body.dark-mode #btn-all-download i,
        body.dark-mode #btn-all-download .btn-download-text,
        body.theme-dark #btn-all-download i,
        body.theme-dark #btn-all-download .btn-download-text {
            color: #ffffff !important;
        }

        [data-bs-theme="dark"] #all-filter-form #btn-all-download:hover,
        [data-bs-theme="dark"] #all-filter-form #btn-all-download:focus,
        [data-bs-theme="dark"] #btn-all-download:hover,
        [data-bs-theme="dark"] #btn-all-download:focus,
        body.dark-mode #btn-all-download:hover,
        body.theme-dark #btn-all-download:hover {
            background-color: #16A34A !important;
            border-color: #16A34A !important;
        }

        [data-bs-theme="dark"] #all-filter-form #btn-all-download:active,
        [data-bs-theme="dark"] #all-filter-form #btn-all-download.show,
        [data-bs-theme="dark"] #all-filter-form #btn-all-download[aria-expanded="true"],
        [data-bs-theme="dark"] #btn-all-download:active,
        [data-bs-theme="dark"] #btn-all-download.show,
        [data-bs-theme="dark"] #btn-all-download[aria-expanded="true"],
        body.dark-mode #btn-all-download:active,
        body.dark-mode #btn-all-download.show,
        body.dark-mode #btn-all-download[aria-expanded="true"],
        body.theme-dark #btn-all-download:active,
        body.theme-dark #btn-all-download.show,
        body.theme-dark #btn-all-download[aria-expanded="true"] {
            background-color: #15803D !important;
            border-color: #15803D !important;
        }
    </style>
@endsection

@section('script')
    <script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>

    <script>
        $(function() {
            const datatableUrl = @json(route('manage.record.all.classification.datatable', $slug));
            const downloadBaseUrl = @json(route('manage.record.all.classification.download', $slug));
            window.CLASSIFICATION_DETAIL_ENDPOINT_TEMPLATE = '/manage/record/all/classification/{{ $slug }}/__SOURCE__/__ID__/details';
            const defaultEntries = 25;

            let allTable = null;

            // 1. Initialize Combined Classification DataTable
            if ($.fn.DataTable.isDataTable('#all-classification-learning-table')) {
                $('#all-classification-learning-table').DataTable().destroy();
            }

            const renderSafeText = function(data) {
                if (data === null || data === undefined || data === '' || data === '-') {
                    return '<span class="text-muted">-</span>';
                }
                return $('<div>').text(data).html();
            };

            allTable = $('#all-classification-learning-table').DataTable({
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
                    type: 'GET',
                    data: function(d) {
                        d.employee_id = $('#all-filter-employee').val().trim();
                        d.learning_title = $('#all-filter-title').val().trim();
                        d.source = $('#all-filter-source').val();
                        d.status = $('#all-filter-status').val();
                        d.year = $('#all-filter-year').val();
                        // More Filters Modal parameters
                        d.from_date = $('#modal-from-date').val() || '';
                        d.to_date = $('#modal-to-date').val() || '';
                        d.progress_range = $('input[name="modal_progress_range"]:checked').val() ||
                            'all';
                        d.learning_hours_range = $('input[name="modal_learning_hours"]:checked')
                        .val() || 'all';
                        d.department = $('#modal-department').val() || 'all';
                        d.company = $('#modal-company').val() || 'all';
                        d.entries = $('#all-table-entries-select').val() || defaultEntries;
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
                        data: 'provider',
                        name: 'provider',
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
                        data: 'progress_bar',
                        name: 'progress',
                        orderable: true
                    },
                    {
                        data: 'progress_status',
                        name: 'status',
                        orderable: true
                    },
                    {
                        data: 'start_date',
                        name: 'start_date',
                        orderable: true,
                        defaultContent: '-'
                    },
                    {
                        data: 'end_date',
                        name: 'finish_date',
                        orderable: true,
                        defaultContent: '-'
                    },
                    {
                        data: 'start_time',
                        name: 'start_time',
                        orderable: true,
                        defaultContent: '-'
                    },
                    {
                        data: 'end_time',
                        name: 'end_time',
                        orderable: true,
                        defaultContent: '-'
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
                        data: 'certificate_availability',
                        name: 'certificate',
                        orderable: true,
                        defaultContent: '-'
                    },
                    {
                        data: 'total_engagement_time',
                        name: 'total_engagement_time',
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
                    emptyTable: 'No learning records found matching the criteria.',
                    zeroRecords: 'No matching learning records found.'
                },
                drawCallback: function(settings) {
                    const api = this.api();
                    const pageInfo = api.page.info();

                    // Sync custom pagination controls
                    $('#all-table-info').text(
                        pageInfo.recordsDisplay > 0 ?
                        `Showing ${pageInfo.start + 1} to ${pageInfo.end} of ${pageInfo.recordsDisplay.toLocaleString()} entries` :
                        'Showing 0 to 0 of 0 entries'
                    );

                    renderCustomPagination(pageInfo);
                    setTimeout(syncTopScroll, 60);
                }
            });

            // Top scrollbar synchronization
            const $topScroll = $('#table-scroll-top-wrapper');
            const $tableScroll = $('#table-scroll-container');
            const $topInner = $('#table-scroll-top-inner');

            function syncTopScroll() {
                const tableWidth = $('#all-classification-learning-table').outerWidth();
                $topInner.css('width', tableWidth + 'px');
            }

            let isSyncingTop = false;
            let isSyncingBottom = false;

            $topScroll.on('scroll', function() {
                if (!isSyncingTop) {
                    isSyncingBottom = true;
                    $tableScroll.scrollLeft($(this).scrollLeft());
                }
                isSyncingTop = false;
            });

            $tableScroll.on('scroll', function() {
                if (!isSyncingBottom) {
                    isSyncingTop = true;
                    $topScroll.scrollLeft($(this).scrollLeft());
                }
                isSyncingBottom = false;
            });

            $(window).on('resize', syncTopScroll);

            // Custom pagination renderer
            function renderCustomPagination(info) {
                const totalPages = info.pages;
                const currentPage = info.page;
                const $container = $('#all-table-pagination');

                if (totalPages <= 1) {
                    $container.empty();
                    return;
                }

                let html = '<ul class="pagination pagination-sm mb-0">';
                html += `<li class="page-item ${currentPage === 0 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">&laquo;</a>
                 </li>`;

                const startPage = Math.max(0, currentPage - 2);
                const endPage = Math.min(totalPages - 1, currentPage + 2);

                if (startPage > 0) {
                    html += `<li class="page-item"><a class="page-link" href="#" data-page="0">1</a></li>`;
                    if (startPage > 1) {
                        html += `<li class="page-item disabled"><span class="page-link">&hellip;</span></li>`;
                    }
                }

                for (let p = startPage; p <= endPage; p++) {
                    html += `<li class="page-item ${p === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${p}">${p + 1}</a>
                     </li>`;
                }

                if (endPage < totalPages - 1) {
                    if (endPage < totalPages - 2) {
                        html += `<li class="page-item disabled"><span class="page-link">&hellip;</span></li>`;
                    }
                    html +=
                        `<li class="page-item"><a class="page-link" href="#" data-page="${totalPages - 1}">${totalPages}</a></li>`;
                }

                html += `<li class="page-item ${currentPage >= totalPages - 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">&raquo;</a>
                 </li>`;
                html += '</ul>';

                $container.html(html);
            }

            // Pagination link click
            $(document).on('click', '#all-table-pagination .page-link', function(e) {
                e.preventDefault();
                const targetPage = parseInt($(this).data('page'), 10);
                if (!isNaN(targetPage) && allTable) {
                    allTable.page(targetPage).draw('page');
                }
            });

            // Entries count select change
            $('#all-table-entries-select').on('change', function() {
                const entries = parseInt($(this).val(), 10) || defaultEntries;
                if (allTable) {
                    allTable.page.len(entries).draw();
                }
            });

            // Function to calculate and update active modal filter counter badge
            function updateActiveFilterBadge() {
                let activeCount = 0;
                if ($('#modal-from-date').val()) activeCount++;
                if ($('#modal-to-date').val()) activeCount++;
                const prog = $('input[name="modal_progress_range"]:checked').val();
                if (prog && prog !== 'all') activeCount++;
                const hrs = $('input[name="modal_learning_hours"]:checked').val();
                if (hrs && hrs !== 'all') activeCount++;
                const dept = $('#modal-department').val();
                if (dept && dept !== 'all') activeCount++;
                const comp = $('#modal-company').val();
                if (comp && comp !== 'all') activeCount++;

                const $badge = $('#activeFilterBadge');
                if (activeCount > 0) {
                    $badge.text(activeCount).removeClass('d-none');
                    $('#btn-all-more-filters').addClass('btn-primary text-white').removeClass(
                    'btn-outline-primary');
                } else {
                    $badge.addClass('d-none');
                    $('#btn-all-more-filters').removeClass('btn-primary text-white').addClass(
                    'btn-outline-primary');
                }
            }

            // Helper to safely dismiss modal and cleanup backdrop
            function hideMoreFiltersModal() {
                const modalEl = document.getElementById('moreAllFiltersModal');
                if (modalEl) {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        const modalInstance = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal
                            .getOrCreateInstance(modalEl);
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                    }
                    if ($.fn.modal) {
                        $('#moreAllFiltersModal').modal('hide');
                    }
                }
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open').css({
                    overflow: '',
                    paddingRight: ''
                });
            }

            // Initialize badge on load
            updateActiveFilterBadge();

            // Quick Filter Trigger (Search button click)
            $('#btn-all-filter').on('click', function() {
                if (allTable) {
                    allTable.page(0).ajax.reload();
                }
            });

            // Filter submit (Enter key inside search inputs)
            $('#all-filter-form').on('submit', function(e) {
                e.preventDefault();
                if (allTable) {
                    allTable.page(0).ajax.reload();
                }
            });

            // Quick dropdowns instant reload
            $('#all-filter-source, #all-filter-status, #all-filter-year').on('change', function() {
                if (allTable) {
                    allTable.page(0).ajax.reload();
                }
            });

            // Modal Apply Filter
            $('#btn-modal-apply').on('click', function() {
                updateActiveFilterBadge();
                hideMoreFiltersModal();
                if (allTable) {
                    allTable.page(0).ajax.reload();
                }
            });

            // Modal Reset (resets modal-only fields)
            $('#btn-modal-reset').on('click', function() {
                $('#modal-from-date').val('');
                $('#modal-to-date').val('');
                $('input[name="modal_progress_range"][value="all"]').prop('checked', true);
                $('input[name="modal_learning_hours"][value="all"]').prop('checked', true);
                $('#modal-department').val('all');
                $('#modal-company').val('all');

                updateActiveFilterBadge();
                hideMoreFiltersModal();
                if (allTable) {
                    allTable.page(0).ajax.reload();
                }
            });

            // Global Reset (resets both Quick Filter and Modal inputs)
            $('#btn-all-reset').on('click', function() {
                $('#all-filter-employee').val('');
                $('#all-filter-title').val('');
                $('#all-filter-source').val('all');
                $('#all-filter-status').val('all');
                $('#all-filter-year').val('all');

                $('#modal-from-date').val('');
                $('#modal-to-date').val('');
                $('input[name="modal_progress_range"][value="all"]').prop('checked', true);
                $('input[name="modal_learning_hours"][value="all"]').prop('checked', true);
                $('#modal-department').val('all');
                $('#modal-company').val('all');

                updateActiveFilterBadge();
                if (allTable) {
                    allTable.page(0).ajax.reload();
                }
            });

            // Helper function to build active filter query parameters
            function buildActiveExportParams(format) {
                const params = new URLSearchParams();
                if (format) params.append('format', format);

                const empVal = $('#all-filter-employee').val().trim();
                if (empVal) params.append('employee_id', empVal);

                const titleVal = $('#all-filter-title').val().trim();
                if (titleVal) params.append('learning_title', titleVal);

                const sourceVal = $('#all-filter-source').val();
                if (sourceVal && sourceVal !== 'all') params.append('source', sourceVal);

                const statusVal = $('#all-filter-status').val();
                if (statusVal && statusVal !== 'all') params.append('status', statusVal);

                const yearVal = $('#all-filter-year').val();
                if (yearVal && yearVal !== 'all') params.append('year', yearVal);

                const fromDate = $('#modal-from-date').val();
                if (fromDate) params.append('from_date', fromDate);

                const toDate = $('#modal-to-date').val();
                if (toDate) params.append('to_date', toDate);

                const progressRange = $('input[name="modal_progress_range"]:checked').val();
                if (progressRange && progressRange !== 'all') params.append('progress_range', progressRange);

                const hoursRange = $('input[name="modal_learning_hours"]:checked').val();
                if (hoursRange && hoursRange !== 'all') params.append('learning_hours_range', hoursRange);

                const deptVal = $('#modal-department').val();
                if (deptVal && deptVal !== 'all') params.append('department', deptVal);

                const compVal = $('#modal-company').val();
                if (compVal && compVal !== 'all') params.append('company', compVal);

                return params;
            }

            function triggerExportDownload(format) {
                const $btn = $('#btn-all-download');
                const $icon = $btn.find('i');
                const $text = $btn.find('.btn-download-text');
                const originalText = $text.text();
                const originalIconClass = $icon.attr('class');

                // Loading state
                $btn.prop('disabled', true);
                $icon.attr('class', 'spinner-border spinner-border-sm me-1');
                $text.text('Exporting...');

                const params = buildActiveExportParams(format);
                const fullUrl = downloadBaseUrl + (downloadBaseUrl.indexOf('?') !== -1 ? '&' : '?') + params
                    .toString();
                window.location.href = fullUrl;

                setTimeout(function() {
                    $btn.prop('disabled', false);
                    $icon.attr('class', originalIconClass);
                    $text.text(originalText);
                }, 2500);
            }

            // Export format selection handler
            $(document).on('click', '.export-format-btn', function(e) {
                e.preventDefault();
                const format = $(this).data('format') || 'xlsx';

                if (format === 'pdf') {
                    let currentCount = 0;
                    if (allTable && typeof allTable.page.info === 'function') {
                        const info = allTable.page.info();
                        currentCount = (info && typeof info.recordsDisplay !== 'undefined') ? info
                            .recordsDisplay : 0;
                    }

                    if (currentCount > 200) {
                        $('#pdfLimitCurrentCount').text(currentCount.toLocaleString() + ' records');
                        const modalEl = document.getElementById('pdfLimitWarningModal');
                        if (modalEl) {
                            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(
                                modalEl);
                            modal.show();
                        }
                        return;
                    }
                }

                triggerExportDownload(format);
            });

            // Fallback buttons inside PDF Limit Warning Modal
            $('#btn-pdf-fallback-excel').on('click', function() {
                const modalEl = document.getElementById('pdfLimitWarningModal');
                if (modalEl) {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                }
                triggerExportDownload('xlsx');
            });

            $('#btn-pdf-fallback-csv').on('click', function() {
                const modalEl = document.getElementById('pdfLimitWarningModal');
                if (modalEl) {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                }
                triggerExportDownload('csv');
            });

            // 2. Detail Modal for Offline records
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
                                    .addClass('bg-warning-lt').text('ONGOING');
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
                            const rawDeliv = (d.delivery_method || '').toLowerCase().trim();
                            const isOnline = ['online', 'e-learning', 'webinar', 'kp-owl', 'kp owl'].includes(rawDeliv) || (d.source && d.source.toLowerCase() === 'online');
                            const deliv = isOnline ? 'Online' : 'Offline';
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

            // 3. Detail Modal for Online records
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
                            const progressVal = parseFloat(pr.progress) || 0;
                            const statusBadge = $('#online-modal-status-badge');
                            if (pr.is_finished || progressVal >= 100) {
                                statusBadge.removeClass('bg-warning-lt bg-secondary-lt')
                                    .addClass('bg-success-lt').text('FINISHED');
                            } else if (progressVal > 0) {
                                statusBadge.removeClass('bg-success-lt bg-secondary-lt')
                                    .addClass('bg-warning-lt').text('ONGOING');
                            } else {
                                statusBadge.removeClass('bg-success-lt bg-warning-lt').addClass(
                                    'bg-secondary-lt').text('NOT STARTED');
                            }

                            // Section 1: Employee
                            $('#online-modal-employee-id').text(emp.id_emp || '-');
                            $('#online-modal-employee-name').text(emp.name || '-');
                            $('#online-modal-employee-email').text(emp.email || '-');
                            $('#online-modal-employee-department').text(emp.department || '-');
                            $('#online-modal-employee-division').text(emp.division || '-');
                            $('#online-modal-employee-team').text(emp.team || '-');
                            $('#online-modal-employee-company').text(emp.company || '-');

                            // Section 2: Content
                            $('#online-modal-content-title').text(lr.title || '-');
                            $('#online-modal-content-type').text(lr.type || '-');
                            $('#online-modal-classification').text(lr.classification || '-');
                            $('#online-modal-enrollment-type').text(lr.enrollment ||
                                'All Content');
                            $('#online-modal-catalog-duration').text(lr.duration || '-');
                            $('#online-modal-catalog-hours').text(lr.learning_hours || '-');
                            $('#online-modal-author').text(lr.author || '-');

                            if (lr.playlist && lr.playlist.title && lr.playlist.title !== '-') {
                                $('#online-modal-playlist-title').text(lr.playlist.title);
                                $('#online-modal-playlist-duration').text(lr.playlist
                                    .duration || '-');
                                $('#online-modal-playlist-container').show();
                            } else {
                                $('#online-modal-playlist-container').hide();
                            }

                            // Section 3: Learning Activity & Tracking
                            const clampedProgress = Math.min(100, Math.max(0, Math.round(
                                progressVal)));
                            $('#online-modal-progress-bar').css('width', clampedProgress + '%');
                            $('#online-modal-progress-text').text(clampedProgress + '%');
                            $('#online-modal-finish-status').text(pr.finish_status || '-');
                            $('#online-modal-current-time').text(pr.current_time || '-');
                            $('#online-modal-tracked-duration').text(pr.tracked_duration ||
                            '-');
                            $('#online-modal-actual-watch').text(pr.actual_watch || '-');
                            $('#online-modal-afk-duration').text(pr.afk_duration || '-');

                            if (pr.pages !== null && pr.pages !== undefined) {
                                $('#online-modal-pages').text(pr.pages);
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

            // 4. Delete Record Modal Logic for Offline records
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
                        if (typeof allTable !== 'undefined') {
                            allTable.ajax.reload(null, false);
                        }
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).html(originalHtml);
                        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr
                            .responseJSON.message : 'Failed to delete record.';
                        alert(msg);
                    }
                });
            });

            // 5. Edit Online Learning Record Logic (reusing #learningEditModal)
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

                        if (typeof allTable !== 'undefined') {
                            allTable.ajax.reload(null, false);
                        }
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

            // 6. Reset Online Learning Record Logic (reusing #learningResetModal)
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

                        if (typeof allTable !== 'undefined') {
                            allTable.ajax.reload(null, false);
                        }
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

            // 7. Delete Online Learning Record Modal Logic
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
                        if (typeof allTable !== 'undefined') {
                            allTable.ajax.reload(null, false);
                        }
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).html(originalHtml);
                        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr
                            .responseJSON.message : 'Failed to delete record.';
                        alert(msg);
                    }
                });
            });

            // 8. Delete Master Content / Playlist Modal Logic
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
                        if (typeof allTable !== 'undefined') {
                            allTable.ajax.reload(null, false);
                        }
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).html(originalHtml);
                        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr
                            .responseJSON.message : 'Failed to delete master entity.';
                        alert(msg);
                    }
                });
            });

            // 5. Close open dropdowns when table scrolls horizontally to prevent floating orphan menus
            $('#table-scroll-container').on('scroll', function() {
                const openDropdown = $('#all-classification-learning-table .dropdown-menu.show');
                if (openDropdown.length) {
                    const dropdownBtn = openDropdown.closest('.dropdown').find(
                        '[data-bs-toggle="dropdown"]');
                    if (dropdownBtn.length && typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
                        const instance = bootstrap.Dropdown.getInstance(dropdownBtn[0]);
                        if (instance) instance.hide();
                    }
                }
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
