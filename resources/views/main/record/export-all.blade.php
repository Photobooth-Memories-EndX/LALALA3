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

        @include('main.record.components.record-navigation')

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
                            <div class="input-group w-100">
                                <span class="input-group-text export-source-addon border-end-0 rounded-start-3 px-3 text-muted" id="source-icon-container">
                                    <i class="ti ti-device-laptop text-primary fs-2" id="source-icon-indicator"></i>
                                </span>
                                <select class="form-select export-source-select w-100 rounded-end-3 py-2 ps-2" name="source" id="selected-source" aria-label="Select Data Source">
                                    <option value="online" selected>Online Learning Record (KP OWL)</option>
                                    <option value="offline">Offline Training Record (Non KP OWL)</option>
                                    <option value="all">Combined Learning Record (All Sources)</option>
                                </select>
                            </div>
                            <!-- Dynamic source description -->
                            <div id="source-description-text" class="text-muted small mt-2 d-flex align-items-center gap-1.5" style="font-size: 0.8rem;">
                                <i class="ti ti-info-circle text-muted flex-shrink-0"></i>
                                <span id="source-description-label">Online learning records including Content &amp; Playlist.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 2: LEARNING & PARTICIPANT FILTERS -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 step-card">
                <div class="card-header bg-transparent border-0 pt-4 pb-2 px-4">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 0.75rem;">2</span>
                        <h4 class="card-title fw-bold text-dark mb-0">Employee &amp; Learning Filters</h4>
                    </div>
                    <p class="text-muted small mb-0 mt-1 ms-4 ps-1">Filter by participant details, course title, and classification.</p>
                </div>
                <div class="card-body px-4 pb-4 pt-2">
                    <div class="row g-3">
                        <!-- Employee / NIP Search -->
                        <div class="col-12 col-md-6 col-lg-4">
                            <label class="form-label fw-bold text-dark small" for="filter-employee">
                                <i class="ti ti-user me-1 text-muted"></i> Employee / NIP
                            </label>
                            <input type="text" id="filter-employee" name="employee_id" class="form-control rounded-3"
                                   placeholder="Search NIP or Name..." autocomplete="off">
                            <small class="text-muted" style="font-size: 0.75rem;">Matches NIP or employee name across systems.</small>
                        </div>

                        <!-- Learning Title / Course Name -->
                        <div class="col-12 col-md-6 col-lg-4">
                            <label class="form-label fw-bold text-dark small" for="filter-title">
                                <i class="ti ti-book me-1 text-muted"></i> <span id="label-learning-title">Learning / Course Title</span>
                            </label>
                            <input type="text" id="filter-title" name="learning_title" class="form-control rounded-3"
                                   placeholder="Search course title..." autocomplete="off">
                            <small class="text-muted" style="font-size: 0.75rem;">Matches course, document, video, or playlist title.</small>
                        </div>

                        <!-- Main Classification -->
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

                        <!-- Content Type (Online Only) -->
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

                        <!-- Online Category Source (Online Only) -->
                        <div class="col-12 col-md-6 col-lg-4 filter-online-only" id="wrapper-online-category">
                            <label class="form-label fw-bold text-dark small" for="filter-online-category-select">
                                <i class="ti ti-folders me-1 text-muted"></i> Online Source Category
                            </label>
                            <select id="filter-online-category-select" name="category" class="form-select rounded-3">
                                <option value="all">All (Content &amp; Playlists)</option>
                                <option value="all_content">Single Content Only</option>
                                <option value="playlist">Playlists Only</option>
                            </select>
                        </div>

                        <!-- Training Type (Offline Only) -->
                        <div class="col-12 col-md-6 col-lg-4 filter-offline-only" id="wrapper-training-type" style="display: none;">
                            <label class="form-label fw-bold text-dark small" for="filter-training-type">
                                <i class="ti ti-school me-1 text-muted"></i> Training Type
                            </label>
                            <select id="filter-training-type" name="type" class="form-select rounded-3" disabled>
                                <option value="all">All Training Types</option>
                                @foreach ($trainingTypes as $tt)
                                    <option value="{{ $tt }}">{{ $tt }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Offline Category (Offline Only) -->
                        <div class="col-12 col-md-6 col-lg-4 filter-offline-only" id="wrapper-offline-category" style="display: none;">
                            <label class="form-label fw-bold text-dark small" for="filter-offline-category-select">
                                <i class="ti ti-tag me-1 text-muted"></i> Training Category
                            </label>
                            <select id="filter-offline-category-select" name="category" class="form-select rounded-3" disabled>
                                <option value="all">All Categories</option>
                                @foreach ($offlineCategories as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Batch (Offline Only) -->
                        <div class="col-12 col-md-6 col-lg-4 filter-offline-only" id="wrapper-batch" style="display: none;">
                            <label class="form-label fw-bold text-dark small" for="filter-batch">
                                <i class="ti ti-hash me-1 text-muted"></i> Batch
                            </label>
                            <input type="text" id="filter-batch" name="batch" class="form-control rounded-3" placeholder="e.g. Batch 1" disabled>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 3: ORGANIZATION FILTERS -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 step-card">
                <div class="card-header bg-transparent border-0 pt-4 pb-2 px-4">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 0.75rem;">3</span>
                        <h4 class="card-title fw-bold text-dark mb-0">Organization Filters</h4>
                    </div>
                    <p class="text-muted small mb-0 mt-1 ms-4 ps-1">Scope report by company division, department, team, or employee level.</p>
                </div>
                <div class="card-body px-4 pb-4 pt-2">
                    <div class="row g-3">
                        <!-- Company -->
                        <div class="col-12 col-md-6 col-lg-4">
                            <label class="form-label fw-bold text-dark small" for="filter-company">
                                <i class="ti ti-building me-1 text-muted"></i> Company
                            </label>
                            <select id="filter-company" name="company" class="form-select rounded-3">
                                <option value="all">All Companies</option>
                                @foreach ($companies as $comp)
                                    <option value="{{ $comp }}">{{ $comp }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Department -->
                        <div class="col-12 col-md-6 col-lg-4">
                            <label class="form-label fw-bold text-dark small" for="filter-department">
                                <i class="ti ti-sitemap me-1 text-muted"></i> Department
                            </label>
                            <select id="filter-department" name="department" class="form-select rounded-3">
                                <option value="all">All Departments</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept }}">{{ $dept }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Team -->
                        <div class="col-12 col-md-6 col-lg-4">
                            <label class="form-label fw-bold text-dark small" for="filter-team">
                                <i class="ti ti-users me-1 text-muted"></i> Team / Section
                            </label>
                            <input type="text" id="filter-team" name="team" class="form-control rounded-3" placeholder="Search team...">
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

                        <!-- Job Position (Offline Only) -->
                        <div class="col-12 col-md-6 col-lg-4 filter-offline-only" id="wrapper-job-position" style="display: none;">
                            <label class="form-label fw-bold text-dark small" for="filter-job-position">
                                <i class="ti ti-briefcase me-1 text-muted"></i> Job Position
                            </label>
                            <input type="text" id="filter-job-position" name="job_position" class="form-control rounded-3" placeholder="Search position..." disabled>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 4: PERIOD, STATUS & DELIVERY -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 step-card">
                <div class="card-header bg-transparent border-0 pt-4 pb-2 px-4">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 0.75rem;">4</span>
                        <h4 class="card-title fw-bold text-dark mb-0">Period &amp; Performance Filters</h4>
                    </div>
                    <p class="text-muted small mb-0 mt-1 ms-4 ps-1">Define reporting timeframe, completion progress, and delivery parameters.</p>
                </div>
                <div class="card-body px-4 pb-4 pt-2">
                    <div class="row g-3">
                        <!-- Year -->
                        <div class="col-12 col-md-6 col-lg-3">
                            <label class="form-label fw-bold text-dark small" for="filter-year">
                                <i class="ti ti-calendar-event me-1 text-muted"></i> Reporting Year
                            </label>
                            <select id="filter-year" name="year" class="form-select rounded-3">
                                <option value="all">All Years</option>
                                @foreach ($years as $yr)
                                    <option value="{{ $yr }}" {{ $yr == $currentYear ? 'selected' : '' }}>{{ $yr }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Start Date -->
                        <div class="col-12 col-md-6 col-lg-3">
                            <label class="form-label fw-bold text-dark small" for="filter-from-date">
                                <i class="ti ti-calendar me-1 text-muted"></i> From Date
                            </label>
                            <input type="date" id="filter-from-date" name="from_date" class="form-control rounded-3">
                        </div>

                        <!-- End Date -->
                        <div class="col-12 col-md-6 col-lg-3">
                            <label class="form-label fw-bold text-dark small" for="filter-to-date">
                                <i class="ti ti-calendar me-1 text-muted"></i> To Date
                            </label>
                            <input type="date" id="filter-to-date" name="to_date" class="form-control rounded-3">
                        </div>

                        <!-- Status -->
                        <div class="col-12 col-md-6 col-lg-3">
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

                        <!-- Progress Range -->
                        <div class="col-12 col-md-6 col-lg-3">
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

                        <!-- Participant Learning Hours Range (Combined & Offline) -->
                        <div class="col-12 col-md-6 col-lg-3 filter-hours-wrapper" id="wrapper-hours">
                            <label class="form-label fw-bold text-dark small" for="filter-hours">
                                <i class="ti ti-clock me-1 text-muted"></i> Learning Hours Range
                            </label>
                            <select id="filter-hours" name="learning_hours_range" class="form-select rounded-3">
                                <option value="all">All Hours</option>
                                <option value="lt05">&lt; 0.5 Hour</option>
                                <option value="05-1">0.5 – &lt; 1 Hour</option>
                                <option value="1-2">1 – &lt; 2 Hours</option>
                                <option value="2-4">2 – &lt; 4 Hours</option>
                                <option value="gte4">&ge; 4 Hours</option>
                            </select>
                        </div>

                        <!-- Provider (Offline Only) -->
                        <div class="col-12 col-md-6 col-lg-3 filter-offline-only" id="wrapper-provider" style="display: none;">
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
                        <div class="col-12 col-md-6 col-lg-3 filter-offline-only" id="wrapper-delivery-method" style="display: none;">
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
                        <div class="col-12 col-md-6 col-lg-3 filter-offline-only" id="wrapper-attendance" style="display: none;">
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
                        <div class="col-12 col-md-6 col-lg-3 filter-offline-only" id="wrapper-certificate" style="display: none;">
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
                            <div class="input-group w-100">
                                <span class="input-group-text export-format-addon border-end-0 rounded-start-3 px-3 text-muted" id="format-icon-container">
                                    <i class="ti ti-file-spreadsheet text-success fs-2" id="format-icon-indicator"></i>
                                </span>
                                <select class="form-select export-format-select w-100 rounded-end-3 py-2 ps-2" name="format" id="selected-format" aria-label="Select Export Format">
                                    <option value="xlsx" selected>Excel Spreadsheet (.xlsx)</option>
                                    <option value="csv">CSV Delimited (.csv)</option>
                                    <option value="pdf">PDF Landscape (.pdf)</option>
                                </select>
                            </div>

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

</style>
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

    // 4. RESET FILTERS HANDLER
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            form.reset();
            setSource('online');
            setFormat('xlsx');
            updateSubClassifications('all');
        });
    }

    // Initial load: Set initial states without making automatic preview requests
    setSource('online');
    setFormat('xlsx');
    updateSubClassifications('all');
});
</script>
@endsection
