@extends('layouts.sidebar-master')

@section('content')
<div class="page-body manage-record-page">
    <div class="container-xl">
        <!-- TOMBOL BACK RATA KIRI ATAS -->
        <div class="mb-3">
            <a href="{{ route('manage.index') }}" class="btn-back d-inline-flex align-items-center gap-1 text-decoration-none fw-medium">
                <i class="ti ti-arrow-left"></i>
                <span>Back</span>
            </a>
        </div>

        @include('main.record.components.record-hero')

        <!-- SECTION HEADER: Export KP OWL -->
        <div class="card border-0 shadow-sm p-3 p-md-4 mb-4 rounded-3 bg-white record-section-header">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 record-section-icon"
                         style="width: 54px; height: 54px; background-color: #dcfce7; color: #15803d; border-radius: 12px;">
                        <i class="ti ti-download fs-1"></i>
                    </div>
                    <div>
                        <h2 class="h3 fw-bold mb-1 text-dark">Export KP OWL</h2>
                        <p class="text-muted small mb-0">Learning Record Export Center — Configure source, filters, and generate comprehensive reports.</p>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success-lt text-success px-3 py-1.5 rounded-pill fw-semibold" style="font-size: 0.8rem;">
                        <i class="ti ti-shield-check me-1"></i> Verified Export Hub
                    </span>
                </div>
            </div>
        </div>

        @include('main.record.components.record-navigation')

        <!-- EXPORT CENTER MAIN FORM CONTAINER -->
        <form id="export-center-form" action="{{ route('manage.record.export-all.download') }}" method="GET" target="_blank">
            <!-- STEP 1: CHOOSE DATA SOURCE -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 step-card">
                <div class="card-header bg-transparent border-0 pt-4 pb-2 px-4">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 0.75rem;">1</span>
                        <h4 class="card-title fw-bold text-dark mb-0">Choose Data Source</h4>
                    </div>
                    <p class="text-muted small mb-0 mt-1 ms-4 ps-1">Select the learning repository to export.</p>
                </div>
                <div class="card-body px-4 pb-4 pt-2">
                    <div class="row">
                        <div class="col-12">
                            <label class="form-label fw-bold text-dark small mb-1.5" for="selected-source">
                                <i class="ti ti-database me-1 text-muted"></i> Select Data Source
                            </label>
                            <select class="form-select export-source-select w-100 rounded-3 py-2 px-3" name="source" id="selected-source" aria-label="Select Data Source">
                                <option value="online" selected>Online Learning Record (KP OWL)</option>
                                <option value="offline">Offline Learning Record (Non KP OWL)</option>
                                <option value="all">Combined Learning Record (All Sources)</option>
                            </select>
                            <!-- Dynamic source description -->
                            <div id="source-description-text" class="text-muted small mt-2 d-flex align-items-center gap-1.5" style="font-size: 0.8rem;">
                                <i class="ti ti-info-circle text-muted flex-shrink-0"></i>
                                <span id="source-description-label">Online learning records including Content &amp; Playlist.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 2: LEARNING FILTERS -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 step-card">
                <div class="card-header bg-transparent border-0 pt-4 pb-2 px-4">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 0.75rem;">2</span>
                        <h4 class="card-title fw-bold text-dark mb-0">Learning Filters</h4>
                    </div>
                    <p class="text-muted small mb-0 mt-1 ms-4 ps-1">Filter by course title, classification, status, progress, and training parameters.</p>
                </div>
                <div class="card-body px-4 pb-4 pt-2">
                    <div class="row g-3">
                        <!-- 1. Main Classification -->
                        <div class="col-12 col-md-6 col-lg-4">
                            <label class="form-label fw-bold text-dark small" for="filter-classification">
                                <i class="ti ti-category me-1 text-muted"></i> Main Classification
                            </label>
                            <select id="filter-classification" name="classification" class="form-select rounded-3">
                                <option value="all">All Classifications</option>
                                @foreach ($classifications as $class)
                                    <option value="{{ $class }}">{{ $class }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- 2. Content Type (Online & Combined) / Training Type (Offline) -->
                        <div class="col-12 col-md-6 col-lg-4 filter-online-only" id="wrapper-content-type">
                            <label class="form-label fw-bold text-dark small" for="filter-content-type">
                                <i class="ti ti-file-text me-1 text-muted"></i> Content Type
                            </label>
                            <select id="filter-content-type" name="type" class="form-select rounded-3">
                                <option value="all">All Types</option>
                                <option value="Video">Video</option>
                                <option value="Document">Document</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6 col-lg-4 filter-offline-only" id="wrapper-training-type" style="display: none;">
                            <label class="form-label fw-bold text-dark small" for="filter-training-type">
                                <i class="ti ti-school me-1 text-muted"></i> Content Type
                            </label>
                            <select id="filter-training-type" name="type" class="form-select rounded-3" disabled>
                                <option value="all">All Training Types</option>
                                @foreach ($trainingTypes as $tt)
                                    <option value="{{ $tt }}">{{ $tt }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- 3. Category (Online Source Category / Offline Category) -->
                        <div class="col-12 col-md-6 col-lg-4 filter-online-only" id="wrapper-online-category">
                            <label class="form-label fw-bold text-dark small" for="filter-online-category-select">
                                <i class="ti ti-folders me-1 text-muted"></i> Category
                            </label>
                            <select id="filter-online-category-select" name="category" class="form-select rounded-3">
                                <option value="all">All (Content &amp; Playlists)</option>
                                <option value="all_content">Single Content Only</option>
                                <option value="playlist">Playlists Only</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6 col-lg-4 filter-offline-only" id="wrapper-offline-category" style="display: none;">
                            <label class="form-label fw-bold text-dark small" for="filter-offline-category-select">
                                <i class="ti ti-tag me-1 text-muted"></i> Category
                            </label>
                            <select id="filter-offline-category-select" name="category" class="form-select rounded-3" disabled>
                                <option value="all">All Categories</option>
                                @foreach ($offlineCategories as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- 4. Learning Title (Content / Playlist / Course Title search) -->
                        <div class="col-12 col-md-6 col-lg-4">
                            <label class="form-label fw-bold text-dark small" for="filter-title">
                                <i class="ti ti-book me-1 text-muted"></i> <span id="label-learning-title">Learning / Course Title</span>
                            </label>
                            <input type="text" id="filter-title" name="learning_title" class="form-control rounded-3"
                                   placeholder="Search course title..." autocomplete="off">
                            <small class="text-muted" style="font-size: 0.75rem;">Matches course, document, video, or playlist title.</small>
                        </div>

                        <!-- 5. Completion Status -->
                        <div class="col-12 col-md-6 col-lg-4">
                            <label class="form-label fw-bold text-dark small" for="filter-status">
                                <i class="ti ti-chart-dots me-1 text-muted"></i> Completion Status
                            </label>
                            <select id="filter-status" name="status" class="form-select rounded-3">
                                <option value="all">All Status</option>
                                <option value="finished">Finished</option>
                                <option value="ongoing">Ongoing</option>
                                <option value="not_started">Not Started</option>
                            </select>
                        </div>

                        <!-- 6. Progress Range -->
                        <div class="col-12 col-md-6 col-lg-4">
                            <label class="form-label fw-bold text-dark small" for="filter-progress">
                                <i class="ti ti-percentage me-1 text-muted"></i> Progress Range
                            </label>
                            <select id="filter-progress" name="progress_range" class="form-select rounded-3">
                                <option value="all">All Progress</option>
                                <option value="0">0% (Not Started)</option>
                                <option value="1-25">1% - 25%</option>
                                <option value="26-50">26% - 50%</option>
                                <option value="51-75">51% - 75%</option>
                                <option value="76-99">76% - 99%</option>
                                <option value="100">100% (Completed)</option>
                            </select>
                        </div>

                        <!-- Sub Classification (Offline & Combined) -->
                        <div class="col-12 col-md-6 col-lg-4 filter-sub-classification-wrapper" id="wrapper-sub-classification" style="display: none;">
                            <label class="form-label fw-bold text-dark small" for="filter-sub-classification">
                                <i class="ti ti-subtask me-1 text-muted"></i> Sub Classification
                            </label>
                            <select id="filter-sub-classification" name="sub_classification" class="form-select rounded-3">
                                <option value="all">All Sub Classifications</option>
                            </select>
                            <small class="text-muted" style="font-size: 0.75rem;">Dynamically updates based on chosen Main Classification.</small>
                        </div>

                        <!-- Batch (Offline Only) -->
                        <div class="col-12 col-md-6 col-lg-4 filter-offline-only" id="wrapper-batch" style="display: none;">
                            <label class="form-label fw-bold text-dark small" for="filter-batch">
                                <i class="ti ti-hash me-1 text-muted"></i> Batch
                            </label>
                            <input type="text" id="filter-batch" name="batch" class="form-control rounded-3" placeholder="e.g. Batch 1" disabled>
                        </div>

                        <!-- Provider (Offline Only) -->
                        <div class="col-12 col-md-6 col-lg-4 filter-offline-only" id="wrapper-provider" style="display: none;">
                            <label class="form-label fw-bold text-dark small" for="filter-provider">
                                <i class="ti ti-building-store me-1 text-muted"></i> Provider
                            </label>
                            <select id="filter-provider" name="provider" class="form-select rounded-3" disabled>
                                <option value="all">All Providers</option>
                                @foreach ($providers as $prov)
                                    <option value="{{ $prov }}">{{ $prov }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Delivery Method (Offline Only) -->
                        <div class="col-12 col-md-6 col-lg-4 filter-offline-only" id="wrapper-delivery-method" style="display: none;">
                            <label class="form-label fw-bold text-dark small" for="filter-delivery-method">
                                <i class="ti ti-truck-delivery me-1 text-muted"></i> Delivery Method
                            </label>
                            <select id="filter-delivery-method" name="delivery_method" class="form-select rounded-3" disabled>
                                <option value="all">All Delivery Methods</option>
                                @foreach ($deliveryMethods as $dm)
                                    <option value="{{ $dm }}">{{ $dm }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Attendance (Offline Only) -->
                        <div class="col-12 col-md-6 col-lg-4 filter-offline-only" id="wrapper-attendance" style="display: none;">
                            <label class="form-label fw-bold text-dark small" for="filter-attendance">
                                <i class="ti ti-user-check me-1 text-muted"></i> Attendance
                            </label>
                            <select id="filter-attendance" name="attendance" class="form-select rounded-3" disabled>
                                <option value="all">All Attendance</option>
                                <option value="present">Present (Attended)</option>
                                <option value="absent">Absent</option>
                            </select>
                        </div>

                        <!-- Certificate (Offline Only) -->
                        <div class="col-12 col-md-6 col-lg-4 filter-offline-only" id="wrapper-certificate" style="display: none;">
                            <label class="form-label fw-bold text-dark small" for="filter-certificate">
                                <i class="ti ti-certificate-2 me-1 text-muted"></i> Certificate
                            </label>
                            <select id="filter-certificate" name="certificate" class="form-select rounded-3" disabled>
                                <option value="all">All Status</option>
                                <option value="yes">Available / Issued</option>
                                <option value="no">Not Issued</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 3: LEARNERS FILTERS -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 step-card">
                <div class="card-header bg-transparent border-0 pt-4 pb-2 px-4">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 0.75rem;">3</span>
                        <h4 class="card-title fw-bold text-dark mb-0">3. Learners Filters</h4>
                    </div>
                    <p class="text-muted small mb-0 mt-1 ms-4 ps-1">Scope report by learner details, group, company, division, department, team, and employee.</p>
                </div>
                <div class="card-body px-4 pb-4 pt-2">
                    <div class="row g-3 align-items-start">
                        <!-- Left Input (Stage 1): Filter Scope Type Dropdown -->
                        <div class="col-12 col-md-4 col-lg-4">
                            <label class="form-label fw-bold text-dark small" for="filter-scope-type">
                                <i class="ti ti-adjustments me-1 text-muted"></i> Filter Scope Type
                            </label>
                            <select id="filter-scope-type" name="filter_scope_type" class="form-select rounded-3">
                                <option value="company" selected>Company</option>
                                <option value="division">Division</option>
                                <option value="department">Department</option>
                                <option value="team">Team / Section</option>
                                <option value="group">Group</option>
                                <option value="employee">Employee / Learners (NIP / Name)</option>
                            </select>
                            <small class="text-muted" style="font-size: 0.75rem;">Select attribute to scope reporting.</small>
                        </div>

                        <!-- Right Input (Stage 2): Dynamic Multi-Select / Search Input -->
                        <div class="col-12 col-md-8 col-lg-8">
                            <label class="form-label fw-bold text-dark small" for="filter-scope-values">
                                <i class="ti ti-filter me-1 text-muted"></i> <span id="label-scope-values">Select Company</span>
                            </label>
                            <select id="filter-scope-values" name="filter_scope_values[]" class="form-select rounded-3" multiple="multiple" style="width: 100%;">
                                <!-- Dynamically populated by JavaScript -->
                            </select>
                            <small class="text-muted d-block mt-1" id="help-scope-values" style="font-size: 0.75rem;">Choose one or more items to filter records.</small>
                        </div>

                        <!-- Job Position (Offline Only) -->
                        <div class="col-12 col-md-6 col-lg-4 filter-offline-only" id="wrapper-job-position" style="display: none;">
                            <label class="form-label fw-bold text-dark small" for="filter-job-position">
                                <i class="ti ti-briefcase me-1 text-muted"></i> Job Position
                            </label>
                            <input type="text" id="filter-job-position" name="job_position" class="form-control rounded-3" placeholder="Search position..." disabled>
                        </div>

                        <!-- Grade (Offline Only) -->
                        <div class="col-12 col-md-6 col-lg-4 filter-offline-only" id="wrapper-grade" style="display: none;">
                            <label class="form-label fw-bold text-dark small" for="filter-grade">
                                <i class="ti ti-award me-1 text-muted"></i> Grade / Level
                            </label>
                            <select id="filter-grade" name="grade" class="form-select rounded-3" disabled>
                                <option value="all">All Grades</option>
                                @foreach ($grades as $g)
                                    <option value="{{ $g }}">{{ $g }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 4: PERIOD FILTERS -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 step-card">
                <div class="card-header bg-transparent border-0 pt-4 pb-2 px-4">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 0.75rem;">4</span>
                        <h4 class="card-title fw-bold text-dark mb-0">Period Filters</h4>
                    </div>
                    <p class="text-muted small mb-0 mt-1 ms-4 ps-1">Define reporting timeframe dynamically by month, year, or custom date range.</p>
                </div>
                <div class="card-body px-4 pb-4 pt-2">
                    <div class="row g-3 align-items-start">
                        <!-- Primary Dropdown: Period Type -->
                        <div class="col-12 col-md-6 col-lg-4" id="wrapper-period-type">
                            <label class="form-label fw-bold text-dark small" for="filter-period-type">
                                <i class="ti ti-calendar-time me-1 text-muted"></i> Period Type
                            </label>
                            <select id="filter-period-type" name="period_type" class="form-select rounded-3">
                                <option value="Year" selected>Yearly (Year)</option>
                                <option value="Month">Monthly (Month)</option>
                                <option value="Custom">Custom Range (Custom)</option>
                            </select>
                            <small class="text-muted" style="font-size: 0.75rem;">Choose reporting timeframe mode.</small>
                        </div>

                        <!-- Dynamic Dropdown: Select Month (Visible when Month is selected) -->
                        <div class="col-12 col-md-6 col-lg-4 filter-period-element" id="wrapper-period-month" style="display: none;">
                            <label class="form-label fw-bold text-dark small" for="filter-month">
                                <i class="ti ti-calendar me-1 text-muted"></i> Select Month
                            </label>
                            <select id="filter-month" name="month" class="form-select rounded-3">
                                @php
                                    $currentMonthNum = (int) date('n');
                                    $monthNames = [
                                        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                                    ];
                                @endphp
                                @foreach ($monthNames as $num => $name)
                                    <option value="{{ $num }}" {{ $num === $currentMonthNum ? 'selected' : '' }}>
                                        {{ $name }} ({{ sprintf('%02d', $num) }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted" style="font-size: 0.75rem;">Month of reporting period.</small>
                        </div>

                        <!-- Dynamic Dropdown: Select Year (Visible when Year or Month is selected) -->
                        <div class="col-12 col-md-6 col-lg-4 filter-period-element" id="wrapper-period-year">
                            <label class="form-label fw-bold text-dark small" for="filter-year">
                                <i class="ti ti-calendar-event me-1 text-muted"></i> Select Year
                            </label>
                            <select id="filter-year" name="year" class="form-select rounded-3">
                                <option value="">All Years</option>
                                @foreach ($years as $yr)
                                    <option value="{{ $yr }}" {{ $yr == $currentYear ? 'selected' : '' }}>{{ $yr }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted" style="font-size: 0.75rem;">Reporting year.</small>
                        </div>

                        <!-- Dynamic Date Picker: From Date (Visible when Custom is selected) -->
                        <div class="col-12 col-md-6 col-lg-4 filter-period-element" id="wrapper-period-from" style="display: none;">
                            <label class="form-label fw-bold text-dark small" for="filter-from-date">
                                <i class="ti ti-calendar-stats me-1 text-muted"></i> From Date
                            </label>
                            <input type="date" id="filter-from-date" name="from_date" class="form-control rounded-3" disabled>
                            <small class="text-muted" style="font-size: 0.75rem;">Custom start date.</small>
                        </div>

                        <!-- Dynamic Date Picker: To Date (Visible when Custom is selected) -->
                        <div class="col-12 col-md-6 col-lg-4 filter-period-element" id="wrapper-period-to" style="display: none;">
                            <label class="form-label fw-bold text-dark small" for="filter-to-date">
                                <i class="ti ti-calendar-stats me-1 text-muted"></i> To Date
                            </label>
                            <input type="date" id="filter-to-date" name="to_date" class="form-control rounded-3" disabled>
                            <small class="text-muted" style="font-size: 0.75rem;">Custom finish date.</small>
                        </div>
                    </div>

                    <!-- Active Timeframe Indicator Badge -->
                    <div id="period-summary-badge" class="mt-3 p-2.5 px-3 rounded-3 bg-light border text-dark small d-flex align-items-center gap-2" style="font-size: 0.825rem;">
                        <i class="ti ti-clock-play text-primary fs-3 flex-shrink-0"></i>
                        <span><strong>Selected Timeframe:</strong> <span id="period-summary-text">01 Jan {{ $currentYear }} - 31 Dec {{ $currentYear }}</span></span>
                    </div>

                    <!-- Date Range Error Alert -->
                    <div id="date-range-error" class="alert alert-danger py-2 px-3 mt-3 d-none d-flex align-items-center gap-2 rounded-3" role="alert">
                        <i class="ti ti-alert-circle fs-3 flex-shrink-0"></i>
                        <span><strong>Invalid Date Range:</strong> From Date cannot be later than To Date.</span>
                    </div>
                </div>
            </div>

            <!-- STEP 5: EXPORT FORMAT SELECTION -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 step-card">
                <div class="card-header bg-transparent border-0 pt-4 pb-2 px-4">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 0.75rem;">5</span>
                        <h4 class="card-title fw-bold text-dark mb-0">Choose Export Format</h4>
                    </div>
                    <p class="text-muted small mb-0 mt-1 ms-4 ps-1">Select the target file format for your generated report.</p>
                </div>
                <div class="card-body px-4 pb-4 pt-2">
                    <div class="row">
                        <div class="col-12">
                            <label class="form-label fw-bold text-dark small mb-1.5" for="selected-format">
                                <i class="ti ti-file-export me-1 text-muted"></i> Select Export Format
                            </label>
                            <select class="form-select export-format-select w-100 rounded-3 py-2 px-3" name="format" id="selected-format" aria-label="Select Export Format">
                                <option value="xlsx" selected>Excel Spreadsheet (.xlsx)</option>
                                <option value="csv">CSV File (.csv)</option>
                                <option value="pdf">PDF Document (.pdf)</option>
                            </select>

                            <!-- Dynamic format description -->
                            <div id="format-description-text" class="text-muted small mt-2 d-flex align-items-center gap-1.5" style="font-size: 0.8rem;">
                                <i class="ti ti-info-circle text-muted flex-shrink-0"></i>
                                <span id="format-description-label">Formatted table with full columns &amp; styled headers.</span>
                            </div>

                            <!-- PDF Safe Record Limit Notice -->
                            <div id="pdf-limit-notice" class="mt-2.5 p-2.5 px-3 rounded-3 bg-warning-lt border border-warning-subtle text-dark small d-none" style="font-size: 0.8rem;">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="ti ti-alert-triangle text-warning fs-3 flex-shrink-0 mt-0.5"></i>
                                    <div>
                                        <span class="fw-semibold text-warning-emphasis">PDF Record Limit:</span>
                                        <span class="text-muted">Printable landscape layout supports maximum <strong>500 records</strong>. For larger exports, please use <strong>Excel (.xlsx)</strong> or <strong>CSV (.csv)</strong>.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ACTION BAR: RESET & GENERATE EXPORT -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 action-bar-card">
                <div class="card-body p-3 p-md-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <div class="fw-bold text-dark" id="action-summary-text">KP OWL (Online) &middot; Excel (.xlsx)</div>
                        <div class="text-muted small" id="action-summary-sub">Ready to generate export with configured filters.</div>
                    </div>

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <button type="button" id="btn-reset-filters" class="btn btn-outline-secondary rounded-3 px-3 d-inline-flex align-items-center gap-1.5">
                            <i class="ti ti-refresh fs-2"></i>
                            <span>Reset Filter</span>
                        </button>

                        <button type="submit" id="btn-export-download" class="btn btn-success rounded-3 px-4 d-inline-flex align-items-center gap-2 fw-semibold">
                            <i class="ti ti-download fs-2"></i>
                            <span>Generate Export</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('style')
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

    .record-section-header,
    .step-card,
    .action-bar-card {
        border-radius: 14px !important;
        border: 1px solid #e2e8f0 !important;
        background: #ffffff;
    }

    [data-bs-theme="dark"] .record-section-header,
    [data-bs-theme="dark"] .step-card,
    [data-bs-theme="dark"] .action-bar-card,
    body.dark-mode .record-section-header,
    body.dark-mode .step-card,
    body.dark-mode .action-bar-card {
        background-color: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    [data-bs-theme="dark"] .text-dark,
    body.dark-mode .text-dark {
        color: #f8fafc !important;
    }

    /* DATA SOURCE & EXPORT FORMAT DROPDOWN FULL-WIDTH STYLING */
    .export-source-addon,
    .export-format-addon {
        background-color: #f8fafc;
        border-color: #d8e2ef;
        transition: border-color 0.2s ease;
    }
    .export-source-select,
    .export-format-select {
        border-color: #d8e2ef;
        font-weight: 500;
        cursor: pointer;
        width: 100% !important;
        min-width: 0;
        max-width: none;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .export-source-select:focus,
    .export-format-select:focus {
        border-color: #0c5b9e;
        box-shadow: 0 0 0 0.25rem rgba(12, 91, 158, 0.15);
    }
    .input-group:focus-within .export-source-addon,
    .input-group:focus-within .export-format-addon {
        border-color: #0c5b9e;
    }

    [data-bs-theme="dark"] .export-source-addon,
    [data-bs-theme="dark"] .export-format-addon,
    body.dark-mode .export-source-addon,
    body.dark-mode .export-format-addon {
        background-color: #1e293b;
        border-color: rgba(255, 255, 255, 0.12);
    }
    [data-bs-theme="dark"] .export-source-select,
    [data-bs-theme="dark"] .export-format-select,
    body.dark-mode .export-source-select,
    body.dark-mode .export-format-select {
        background-color: #0f172a;
        border-color: rgba(255, 255, 255, 0.12);
        color: #f8fafc;
    }
    [data-bs-theme="dark"] .export-source-select:focus,
    [data-bs-theme="dark"] .export-format-select:focus,
    body.dark-mode .export-source-select:focus,
    body.dark-mode .export-format-select:focus {
        border-color: #60a5fa;
        box-shadow: 0 0 0 0.25rem rgba(96, 165, 250, 0.2);
    }
    [data-bs-theme="dark"] .input-group:focus-within .export-source-addon,
    [data-bs-theme="dark"] .input-group:focus-within .export-format-addon,
    body.dark-mode .input-group:focus-within .export-source-addon,
    body.dark-mode .input-group:focus-within .export-format-addon {
        border-color: #60a5fa;
    }

    /* SELECT2 CUSTOMIZATION FOR TWO-STAGE FILTER */
    .select2-container--bootstrap-5 .select2-selection--multiple {
        min-height: 42px;
        border-radius: 0.5rem;
        border-color: #d8e2ef;
        padding: 3px 6px;
    }
    .select2-container--bootstrap-5.select2-container--focus .select2-selection--multiple {
        border-color: #0c5b9e;
        box-shadow: 0 0 0 0.25rem rgba(12, 91, 158, 0.15);
    }
    .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
        background-color: #e0f2fe;
        color: #0369a1;
        border: 1px solid #bae6fd;
        border-radius: 0.375rem;
        padding: 3px 8px;
        font-size: 0.825rem;
        font-weight: 500;
        margin-top: 4px;
        margin-bottom: 4px;
    }
    .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__remove {
        color: #0284c7;
        margin-right: 5px;
    }
    .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__remove:hover {
        color: #b91c1c;
    }
    .select2-container--bootstrap-5 .select2-dropdown {
        border-color: #d8e2ef;
        border-radius: 0.5rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        z-index: 1060;
    }
    [data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-selection--multiple,
    body.dark-mode .select2-container--bootstrap-5 .select2-selection--multiple {
        background-color: #0f172a;
        border-color: rgba(255, 255, 255, 0.15);
        color: #f8fafc;
    }
    [data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice,
    body.dark-mode .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
        background-color: #1e3a5f;
        color: #93c5fd;
        border-color: #2563eb;
    }
    [data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-dropdown,
    body.dark-mode .select2-container--bootstrap-5 .select2-dropdown {
        background-color: #1e293b;
        border-color: rgba(255, 255, 255, 0.15);
        color: #f8fafc;
    }
    [data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-results__option--highlighted[aria-selected],
    body.dark-mode .select2-container--bootstrap-5 .select2-results__option--highlighted[aria-selected] {
        background-color: #0c5b9e;
        color: #ffffff;
    }

</style>
<link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/select2/select2-bootstrap-5-theme.min.css') }}">
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sourceSelect = document.getElementById('selected-source');
    const sourceIconIndicator = document.getElementById('source-icon-indicator');
    const sourceDescLabel = document.getElementById('source-description-label');

    const formatSelect = document.getElementById('selected-format');
    const formatIconIndicator = document.getElementById('format-icon-indicator');
    const formatDescLabel = document.getElementById('format-description-label');
    const pdfLimitNotice = document.getElementById('pdf-limit-notice');

    const subClassSelect = document.getElementById('filter-sub-classification');
    const mainClassSelect = document.getElementById('filter-classification');

    const resetBtn = document.getElementById('btn-reset-filters');
    const form = document.getElementById('export-center-form');
    const actionSummaryText = document.getElementById('action-summary-text');

    // Embedded mapping of Sub Classifications by Main Classification
    const subClassMap = @json($subClassMap ?? []);

    // 1. DATA SOURCE SELECTION HANDLER
    const sourceMeta = {
        'online': {
            icon: 'ti ti-device-laptop',
            iconColor: 'text-primary',
            desc: 'Online learning records including Content & Playlist.'
        },
        'offline': {
            icon: 'ti ti-certificate',
            iconColor: 'text-warning',
            desc: 'Offline & external training records.'
        },
        'all': {
            icon: 'ti ti-stack-2',
            iconColor: 'text-purple',
            desc: 'Combined Online & Offline records.'
        }
    };

    function setSource(source) {
        if (!sourceMeta[source]) {
            source = 'online';
        }

        if (sourceSelect && sourceSelect.value !== source) {
            sourceSelect.value = source;
        }

        const meta = sourceMeta[source];
        if (sourceIconIndicator) {
            sourceIconIndicator.className = `${meta.icon} ${meta.iconColor} fs-2`;
        }
        if (sourceDescLabel) {
            sourceDescLabel.textContent = meta.desc;
        }

        const onlineElements = document.querySelectorAll('.filter-online-only');
        const offlineElements = document.querySelectorAll('.filter-offline-only');
        const subClassWrapper = document.getElementById('wrapper-sub-classification');
        const titleLabel = document.getElementById('label-learning-title');

        if (source === 'online') {
            if (titleLabel) titleLabel.textContent = 'Learning Title (Content / Playlist)';
            onlineElements.forEach(el => {
                el.style.display = '';
                const ctrl = el.querySelector('select, input');
                if (ctrl) ctrl.disabled = false;
            });
            offlineElements.forEach(el => {
                el.style.display = 'none';
                const ctrl = el.querySelector('select, input');
                if (ctrl) ctrl.disabled = true;
            });
            if (subClassWrapper) {
                subClassWrapper.style.display = 'none';
                if (subClassSelect) subClassSelect.disabled = true;
            }
        } else if (source === 'offline') {
            if (titleLabel) titleLabel.textContent = 'Course / Training Name';
            onlineElements.forEach(el => {
                el.style.display = 'none';
                const ctrl = el.querySelector('select, input');
                if (ctrl) ctrl.disabled = true;
            });
            offlineElements.forEach(el => {
                el.style.display = '';
                const ctrl = el.querySelector('select, input');
                if (ctrl) ctrl.disabled = false;
            });
            if (subClassWrapper) {
                subClassWrapper.style.display = '';
                if (subClassSelect) subClassSelect.disabled = false;
            }
        } else {
            // source === 'all' (Combined)
            if (titleLabel) titleLabel.textContent = 'Learning / Course Title';
            onlineElements.forEach(el => {
                el.style.display = '';
                const ctrl = el.querySelector('select, input');
                if (ctrl) ctrl.disabled = false;
            });
            offlineElements.forEach(el => {
                el.style.display = 'none';
                const ctrl = el.querySelector('select, input');
                if (ctrl) ctrl.disabled = true;
            });
            if (subClassWrapper) {
                subClassWrapper.style.display = '';
                if (subClassSelect) subClassSelect.disabled = false;
            }
        }

        updateActionSummary();
    }

    if (sourceSelect) {
        sourceSelect.addEventListener('change', function () {
            setSource(this.value);
        });
    }

    // 2. EXPORT FORMAT SELECTION HANDLER
    const formatMeta = {
        'xlsx': {
            icon: 'ti ti-file-spreadsheet',
            iconColor: 'text-success',
            desc: 'Formatted table with full columns & styled headers.',
            showPdfLimit: false
        },
        'csv': {
            icon: 'ti ti-file-text',
            iconColor: 'text-primary',
            desc: 'Fast streaming download with UTF-8 BOM encoding.',
            showPdfLimit: false
        },
        'pdf': {
            icon: 'ti ti-file-type-pdf',
            iconColor: 'text-danger',
            desc: 'Printable landscape layout.',
            showPdfLimit: true
        }
    };

    function setFormat(format) {
        if (!formatMeta[format]) {
            format = 'xlsx';
        }

        if (formatSelect && formatSelect.value !== format) {
            formatSelect.value = format;
        }

        const meta = formatMeta[format];
        if (formatIconIndicator) {
            formatIconIndicator.className = `${meta.icon} ${meta.iconColor} fs-2`;
        }
        if (formatDescLabel) {
            formatDescLabel.textContent = meta.desc;
        }
        if (pdfLimitNotice) {
            if (meta.showPdfLimit) {
                pdfLimitNotice.classList.remove('d-none');
            } else {
                pdfLimitNotice.classList.add('d-none');
            }
        }

        updateActionSummary();
    }

    if (formatSelect) {
        formatSelect.addEventListener('change', function () {
            setFormat(this.value);
        });
    }

    function updateActionSummary() {
        const srcNames = {
            'online': 'KP OWL (Online)',
            'offline': 'Non KP OWL (Offline)',
            'all': 'All Sources'
        };
        const fmtNames = {
            'xlsx': 'Excel (.xlsx)',
            'csv': 'CSV (.csv)',
            'pdf': 'PDF (.pdf)'
        };

        const currentSrc = srcNames[sourceSelect ? sourceSelect.value : 'online'] || 'KP OWL (Online)';
        const currentFmt = fmtNames[formatSelect ? formatSelect.value : 'xlsx'] || 'Excel (.xlsx)';
        if (actionSummaryText) {
            actionSummaryText.innerHTML = `${currentSrc} &middot; ${currentFmt}`;
        }
    }

    // 3. DEPENDENT FILTER: MAIN CLASSIFICATION -> SUB CLASSIFICATION
    function updateSubClassifications(selectedMain) {
        if (!subClassSelect) return;

        subClassSelect.innerHTML = '<option value="all">All Sub Classifications</option>';

        if (!selectedMain || selectedMain === 'all') {
            // Load all sub classifications from embedded map
            let allSubs = [];
            Object.values(subClassMap).forEach(arr => {
                if (Array.isArray(arr)) allSubs = allSubs.concat(arr);
            });
            allSubs = [...new Set(allSubs)].sort();
            allSubs.forEach(sub => {
                const opt = document.createElement('option');
                opt.value = sub;
                opt.textContent = sub;
                subClassSelect.appendChild(opt);
            });
            return;
        }

        // Check embedded map first
        if (subClassMap[selectedMain] && subClassMap[selectedMain].length > 0) {
            subClassMap[selectedMain].forEach(sub => {
                const opt = document.createElement('option');
                opt.value = sub;
                opt.textContent = sub;
                subClassSelect.appendChild(opt);
            });
        } else {
            // Fallback: fetch dynamically via endpoint
            fetch(`{{ route('manage.record.export-all.sub-classifications') }}?classification=${encodeURIComponent(selectedMain)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(json => {
                if (json.success && json.data) {
                    json.data.forEach(sub => {
                        const opt = document.createElement('option');
                        opt.value = sub;
                        opt.textContent = sub;
                        subClassSelect.appendChild(opt);
                    });
                }
            })
            .catch(() => {});
        }
    }

    if (mainClassSelect) {
        mainClassSelect.addEventListener('change', function () {
            updateSubClassifications(this.value);
        });
    }

    // 3b. DYNAMIC TWO-STAGE LEARNERS FILTERS (STAGE 1 & STAGE 2)
    const scopeTypeSelect = document.getElementById('filter-scope-type');
    const scopeValuesSelect = document.getElementById('filter-scope-values');
    const $scopeValuesSelect = $(scopeValuesSelect);
    const labelScopeValues = document.getElementById('label-scope-values');
    const helpScopeValues = document.getElementById('help-scope-values');

    const staticScopeOptions = {
        company: @json($companies ?? []),
        division: @json($divisions ?? []),
        department: @json($departments ?? []),
        team: @json($teams ?? []),
        group: @json($groups ?? [])
    };

    const scopeMeta = {
        company: {
            label: 'Company Values',
            placeholder: 'Select or search companies...',
            help: 'Select one or more companies using tag badges.'
        },
        division: {
            label: 'Division Values',
            placeholder: 'Select or search divisions...',
            help: 'Select one or more divisions using tag badges.'
        },
        department: {
            label: 'Department Values',
            placeholder: 'Select or search departments...',
            help: 'Select one or more departments using tag badges.'
        },
        team: {
            label: 'Team / Section Values',
            placeholder: 'Select or search teams / sections...',
            help: 'Select one or more teams / sections using tag badges.'
        },
        group: {
            label: 'Group Values',
            placeholder: 'Select or search groups...',
            help: 'Select one or more groups using tag badges.'
        },
        employee: {
            label: 'Employee / Learners (NIP / Name)',
            placeholder: 'Type to search learner name or NIP...',
            help: 'Search by NIP or Name across online and offline records.'
        }
    };

    function initScopeSelect2(scopeType) {
        if (!$scopeValuesSelect.length) return;

        // Destroy existing Select2 instance if already initialized
        if ($scopeValuesSelect.hasClass('select2-hidden-accessible')) {
            $scopeValuesSelect.select2('destroy');
        }
        $scopeValuesSelect.empty();

        const meta = scopeMeta[scopeType] || scopeMeta.company;
        if (labelScopeValues) labelScopeValues.textContent = meta.label;
        if (helpScopeValues) helpScopeValues.textContent = meta.help;

        if (scopeType === 'employee') {
            // Employee mode: AJAX dynamic search
            $scopeValuesSelect.select2({
                theme: 'bootstrap-5',
                placeholder: meta.placeholder,
                allowClear: true,
                closeOnSelect: false,
                width: '100%',
                minimumInputLength: 1,
                ajax: {
                    url: "{{ route('manage.record.export-all.scope-options') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            type: 'employee',
                            q: params.term || ''
                        };
                    },
                    processResults: function (data) {
                        const items = data.results || data.data || [];
                        return {
                            results: items
                        };
                    },
                    cache: true
                }
            });
        } else {
            // Standard mode: populate distinct options from staticScopeOptions
            const items = staticScopeOptions[scopeType] || [];
            if (items.length > 0) {
                items.forEach(function (item) {
                    const opt = new Option(item, item, false, false);
                    $scopeValuesSelect.append(opt);
                });
                $scopeValuesSelect.select2({
                    theme: 'bootstrap-5',
                    placeholder: meta.placeholder,
                    allowClear: true,
                    closeOnSelect: false,
                    width: '100%'
                });
            } else {
                fetch(`{{ route('manage.record.export-all.scope-options') }}?type=${encodeURIComponent(scopeType)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(json => {
                    const opts = json.results || json.data || [];
                    opts.forEach(function (o) {
                        const val = o.id || o;
                        const text = o.text || o;
                        const opt = new Option(text, val, false, false);
                        $scopeValuesSelect.append(opt);
                    });
                    $scopeValuesSelect.select2({
                        theme: 'bootstrap-5',
                        placeholder: meta.placeholder,
                        allowClear: true,
                        closeOnSelect: false,
                        width: '100%'
                    });
                })
                .catch(() => {
                    $scopeValuesSelect.select2({
                        theme: 'bootstrap-5',
                        placeholder: meta.placeholder,
                        allowClear: true,
                        closeOnSelect: false,
                        width: '100%'
                    });
                });
            }
        }
    }

    if (scopeTypeSelect) {
        scopeTypeSelect.addEventListener('change', function () {
            initScopeSelect2(this.value);
        });
    }

    // 4. DYNAMIC PERIOD FILTERS HANDLER (LinkedIn / Time Range Style)
    const periodTypeSelect = document.getElementById('filter-period-type');
    const wrapperPeriodMonth = document.getElementById('wrapper-period-month');
    const wrapperPeriodYear = document.getElementById('wrapper-period-year');
    const wrapperPeriodFrom = document.getElementById('wrapper-period-from');
    const wrapperPeriodTo = document.getElementById('wrapper-period-to');

    const monthSelect = document.getElementById('filter-month');
    const yearSelect = document.getElementById('filter-year');
    const fromDateInput = document.getElementById('filter-from-date');
    const toDateInput = document.getElementById('filter-to-date');
    const dateRangeError = document.getElementById('date-range-error');
    const periodSummaryText = document.getElementById('period-summary-text');

    function getDaysInMonth(year, month) {
        return new Date(year, month, 0).getDate();
    }

    function padZero(num) {
        return String(num).padStart(2, '0');
    }

    function updatePeriodDisplayAndDates() {
        if (!periodTypeSelect) return;
        const type = periodTypeSelect.value;
        const yVal = yearSelect ? yearSelect.value : '';
        const mVal = monthSelect ? parseInt(monthSelect.value, 10) : (new Date().getMonth() + 1);
        const currentYearNum = new Date().getFullYear();
        const yearNum = (yVal && yVal !== 'all') ? parseInt(yVal, 10) : currentYearNum;

        const monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        const daysInMonth = getDaysInMonth(yearNum, mVal);

        if (type === 'Month') {
            const fromDateStr = `${yearNum}-${padZero(mVal)}-01`;
            const toDateStr = `${yearNum}-${padZero(mVal)}-${padZero(daysInMonth)}`;

            if (fromDateInput) {
                fromDateInput.value = fromDateStr;
                fromDateInput.disabled = false;
            }
            if (toDateInput) {
                toDateInput.value = toDateStr;
                toDateInput.disabled = false;
            }
            if (yearSelect) yearSelect.disabled = false;

            if (periodSummaryText) {
                periodSummaryText.textContent = `${monthNames[mVal]} ${yearNum} (${fromDateStr} to ${toDateStr})`;
            }
        } else if (type === 'Year') {
            if (fromDateInput) {
                fromDateInput.value = '';
                fromDateInput.disabled = true;
            }
            if (toDateInput) {
                toDateInput.value = '';
                toDateInput.disabled = true;
            }
            if (yearSelect) yearSelect.disabled = false;

            if (periodSummaryText) {
                periodSummaryText.textContent = (yVal && yVal !== 'all') ? `01 Jan ${yVal} - 31 Dec ${yVal}` : 'All Years';
            }
        } else if (type === 'Custom') {
            if (fromDateInput) fromDateInput.disabled = false;
            if (toDateInput) toDateInput.disabled = false;
            if (yearSelect) {
                yearSelect.disabled = true;
            }

            const fromVal = fromDateInput ? fromDateInput.value : '';
            const toVal = toDateInput ? toDateInput.value : '';
            if (periodSummaryText) {
                if (fromVal && toVal) {
                    periodSummaryText.textContent = `Custom Range: ${fromVal} to ${toVal}`;
                } else if (fromVal) {
                    periodSummaryText.textContent = `From ${fromVal} onwards`;
                } else if (toVal) {
                    periodSummaryText.textContent = `Up to ${toVal}`;
                } else {
                    periodSummaryText.textContent = 'Custom Range (Select dates)';
                }
            }
        }
    }

    function setPeriodType(type) {
        if (!periodTypeSelect) return;
        periodTypeSelect.value = type;

        // Dynamic visibility per requirement:
        // - IF "Month": Show dropdowns for "Select Month" + "Select Year".
        // - IF "Year": Show dropdown for "Select Year" only.
        // - IF "Custom": Show date pickers for "From Date" and "To Date".
        if (wrapperPeriodMonth) wrapperPeriodMonth.style.display = (type === 'Month') ? '' : 'none';
        if (wrapperPeriodYear) wrapperPeriodYear.style.display = (type === 'Custom') ? 'none' : '';
        if (wrapperPeriodFrom) wrapperPeriodFrom.style.display = (type === 'Custom') ? '' : 'none';
        if (wrapperPeriodTo) wrapperPeriodTo.style.display = (type === 'Custom') ? '' : 'none';

        updatePeriodDisplayAndDates();
        validateDateRange();
    }

    if (periodTypeSelect) {
        periodTypeSelect.addEventListener('change', function () {
            setPeriodType(this.value);
        });
    }

    if (monthSelect) {
        monthSelect.addEventListener('change', function () {
            updatePeriodDisplayAndDates();
        });
    }

    if (yearSelect) {
        yearSelect.addEventListener('change', function () {
            updatePeriodDisplayAndDates();
        });
    }

    // 5. DATE RANGE VALIDATION (From Date must not be later than To Date when Custom range is active)
    function validateDateRange() {
        if (!periodTypeSelect) return true;
        const currentType = periodTypeSelect.value;
        if (currentType !== 'Custom') {
            if (dateRangeError) dateRangeError.classList.add('d-none');
            if (fromDateInput) fromDateInput.classList.remove('is-invalid');
            if (toDateInput) toDateInput.classList.remove('is-invalid');
            return true;
        }

        if (!fromDateInput || !toDateInput) return true;
        const fromVal = fromDateInput.value;
        const toVal = toDateInput.value;

        if (fromVal && toVal && fromVal > toVal) {
            if (dateRangeError) dateRangeError.classList.remove('d-none');
            fromDateInput.classList.add('is-invalid');
            toDateInput.classList.add('is-invalid');
            return false;
        } else {
            if (dateRangeError) dateRangeError.classList.add('d-none');
            fromDateInput.classList.remove('is-invalid');
            toDateInput.classList.remove('is-invalid');
            return true;
        }
    }

    if (fromDateInput) {
        fromDateInput.addEventListener('change', function () {
            if (toDateInput && this.value) {
                toDateInput.min = this.value;
            } else if (toDateInput) {
                toDateInput.removeAttribute('min');
            }
            if (periodTypeSelect && periodTypeSelect.value === 'Custom') {
                updatePeriodDisplayAndDates();
            }
            validateDateRange();
        });
    }

    if (toDateInput) {
        toDateInput.addEventListener('change', function () {
            if (fromDateInput && this.value) {
                fromDateInput.max = this.value;
            } else if (fromDateInput) {
                fromDateInput.removeAttribute('max');
            }
            if (periodTypeSelect && periodTypeSelect.value === 'Custom') {
                updatePeriodDisplayAndDates();
            }
            validateDateRange();
        });
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            updatePeriodDisplayAndDates();
            if (!validateDateRange()) {
                e.preventDefault();
                fromDateInput.focus();
                return false;
            }
        });
    }

    // 6. RESET FILTERS HANDLER
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            form.reset();
            if (dateRangeError) dateRangeError.classList.add('d-none');
            if (fromDateInput) {
                fromDateInput.classList.remove('is-invalid');
                fromDateInput.removeAttribute('max');
            }
            if (toDateInput) {
                toDateInput.classList.remove('is-invalid');
                toDateInput.removeAttribute('min');
            }
            setSource('online');
            setFormat('xlsx');
            setPeriodType('Year');
            updateSubClassifications('all');
            if (scopeTypeSelect) {
                scopeTypeSelect.value = 'company';
            }
            initScopeSelect2('company');
        });
    }

    // Initial load: Set initial states without making automatic preview requests
    setSource('online');
    setFormat('xlsx');
    setPeriodType('Year');
    updateSubClassifications('all');
    initScopeSelect2(scopeTypeSelect ? scopeTypeSelect.value : 'company');
    validateDateRange();
});
</script>
@endsection
