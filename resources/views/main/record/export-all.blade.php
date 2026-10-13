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
            <!-- Hidden inputs for active source & format -->
            <input type="hidden" name="source" id="selected-source" value="online">
            <input type="hidden" name="format" id="selected-format" value="xlsx">

            <!-- STEP 1: CHOOSE DATA SOURCE (COMPACT) -->
            <div class="card border-0 shadow-sm rounded-4 mb-3 step-card">
                <div class="card-header bg-transparent border-0 pt-3 pb-1 px-3 px-md-4">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 0.75rem;">1</span>
                        <h4 class="card-title fw-bold text-dark mb-0" style="font-size: 1rem;">Choose Data Source</h4>
                        <span class="text-muted small ms-2 d-none d-sm-inline">&bull; Select the learning repository to export</span>
                    </div>
                </div>
                <div class="card-body px-3 px-md-4 pb-3 pt-2">
                    <div class="row g-2.5 g-md-3">
                        <!-- Card 1: KP OWL (Online) -->
                        <div class="col-12 col-md-4">
                            <div class="source-card h-100 position-relative active" data-source="online" id="source-card-online" role="button">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="export-source-icon" style="background-color: #e0f2fe; color: #0284c7;">
                                        <i class="ti ti-device-laptop"></i>
                                    </span>
                                    <span class="badge bg-blue-lt text-blue rounded-pill px-2.5 py-1 fw-semibold" style="font-size: 0.725rem;">KP OWL</span>
                                </div>
                                <h4 class="fw-bold mb-1 text-dark source-title" style="font-size: 0.95rem;">Online Learning Record</h4>
                                <p class="text-muted small mb-0" style="font-size: 0.8rem; line-height: 1.35;">
                                    OWL Content &amp; Playlist records
                                </p>
                                <div class="source-check-icon">
                                    <i class="ti ti-circle-check-filled text-primary fs-3"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Non KP OWL (Offline) -->
                        <div class="col-12 col-md-4">
                            <div class="source-card h-100 position-relative" data-source="offline" id="source-card-offline" role="button">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="export-source-icon" style="background-color: #fef3c7; color: #d97706;">
                                        <i class="ti ti-certificate"></i>
                                    </span>
                                    <span class="badge bg-amber-lt text-amber rounded-pill px-2.5 py-1 fw-semibold" style="font-size: 0.725rem;">Non KP OWL</span>
                                </div>
                                <h4 class="fw-bold mb-1 text-dark source-title" style="font-size: 0.95rem;">Offline Training Record</h4>
                                <p class="text-muted small mb-0" style="font-size: 0.8rem; line-height: 1.35;">
                                    Offline &amp; external training records
                                </p>
                                <div class="source-check-icon">
                                    <i class="ti ti-circle-check-filled text-primary fs-3"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: All Record (Combined) -->
                        <div class="col-12 col-md-4">
                            <div class="source-card h-100 position-relative" data-source="all" id="source-card-all" role="button">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="export-source-icon" style="background-color: #ede9fe; color: #7c3aed;">
                                        <i class="ti ti-stack-2"></i>
                                    </span>
                                    <span class="badge bg-purple-lt text-purple rounded-pill px-2.5 py-1 fw-semibold" style="font-size: 0.725rem;">All Sources</span>
                                </div>
                                <h4 class="fw-bold mb-1 text-dark source-title" style="font-size: 0.95rem;">Combined Learning Record</h4>
                                <p class="text-muted small mb-0" style="font-size: 0.8rem; line-height: 1.35;">
                                    Combined Online &amp; Offline records
                                </p>
                                <div class="source-check-icon">
                                    <i class="ti ti-circle-check-filled text-primary fs-3"></i>
                                </div>
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
                    <div class="row g-3">
                        <!-- Format 1: Excel (.xlsx) -->
                        <div class="col-12 col-md-4">
                            <div class="format-card p-3 rounded-3 h-100 position-relative active" data-format="xlsx" id="format-card-xlsx" role="button">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="avatar avatar-md rounded-3 flex-shrink-0" style="background-color: #dcfce7; color: #15803d;">
                                        <i class="ti ti-file-spreadsheet fs-2"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <div class="fw-bold text-dark">Excel Spreadsheet (.xlsx)</div>
                                        <div class="text-muted small" style="font-size: 0.785rem;">Formatted table with full columns &amp; styled headers.</div>
                                    </div>
                                </div>
                                <div class="format-check-icon">
                                    <i class="ti ti-circle-check-filled text-success fs-2"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Format 2: CSV (.csv) -->
                        <div class="col-12 col-md-4">
                            <div class="format-card p-3 rounded-3 h-100 position-relative" data-format="csv" id="format-card-csv" role="button">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="avatar avatar-md rounded-3 flex-shrink-0" style="background-color: #e0f2fe; color: #0284c7;">
                                        <i class="ti ti-file-text fs-2"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <div class="fw-bold text-dark">CSV Delimited (.csv)</div>
                                        <div class="text-muted small" style="font-size: 0.785rem;">Fast streaming download with UTF-8 BOM encoding.</div>
                                    </div>
                                </div>
                                <div class="format-check-icon">
                                    <i class="ti ti-circle-check-filled text-success fs-2"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Format 3: PDF Landscape (.pdf) -->
                        <div class="col-12 col-md-4">
                            <div class="format-card p-3 rounded-3 h-100 position-relative" data-format="pdf" id="format-card-pdf" role="button">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="avatar avatar-md rounded-3 flex-shrink-0" style="background-color: #fee2e2; color: #b91c1c;">
                                        <i class="ti ti-file-type-pdf fs-2"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <div class="fw-bold text-dark">PDF Landscape (.pdf)</div>
                                        <div class="text-muted small" style="font-size: 0.785rem;">Printable landscape layout (Safe max 200–500 records).</div>
                                    </div>
                                </div>
                                <div class="format-check-icon">
                                    <i class="ti ti-circle-check-filled text-success fs-2"></i>
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
                        <div class="fw-bold text-dark" id="action-summary-text">KP OWL (Online) &bull; Excel (.xlsx)</div>
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

    /* DATA SOURCE CARD ICON CONTAINER & GLYPH (COMPACT) */
    .export-source-icon {
        width: 44px !important;
        height: 44px !important;
        min-width: 44px !important;
        min-height: 44px !important;
        border-radius: 10px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        flex-shrink: 0;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .export-source-icon i {
        font-size: 22px !important;
        line-height: 1 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    .export-source-icon svg {
        width: 22px !important;
        height: 22px !important;
    }

    [data-bs-theme="dark"] .export-source-icon,
    body.dark-mode .export-source-icon {
        filter: brightness(0.95);
    }

    /* SOURCE CARDS INTERACTIVE STYLING */
    .source-card {
        border: 2px solid #e2e8f0;
        background: #ffffff;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        user-select: none;
        padding: 14px 16px !important;
        border-radius: 12px !important;
    }
    .source-card:hover {
        border-color: #93c5fd;
        transform: translateY(-2px);
        box-shadow: 0 6px 14px rgba(12, 91, 158, 0.08);
    }
    .source-card.active {
        border-color: #0c5b9e;
        background: #f0f7ff;
        box-shadow: 0 6px 16px rgba(12, 91, 158, 0.12);
    }
    .source-check-icon {
        position: absolute;
        top: 14px;
        right: 14px;
        opacity: 0;
        transform: scale(0.5);
        transition: all 0.2s ease;
    }
    .source-card.active .source-check-icon {
        opacity: 1;
        transform: scale(1);
    }

    [data-bs-theme="dark"] .source-card,
    body.dark-mode .source-card {
        border-color: rgba(255, 255, 255, 0.12);
        background: #0f172a;
    }
    [data-bs-theme="dark"] .source-card:hover,
    body.dark-mode .source-card:hover {
        border-color: #3b82f6;
    }
    [data-bs-theme="dark"] .source-card.active,
    body.dark-mode .source-card.active {
        border-color: #60a5fa;
        background: #1e293b;
    }

    /* FORMAT CARDS INTERACTIVE STYLING */
    .format-card {
        border: 2px solid #e2e8f0;
        background: #ffffff;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        user-select: none;
    }
    .format-card:hover {
        border-color: #86efac;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(22, 163, 74, 0.08);
    }
    .format-card.active {
        border-color: #16a34a;
        background: #f0fdf4;
        box-shadow: 0 6px 16px rgba(22, 163, 74, 0.12);
    }
    .format-check-icon {
        position: absolute;
        top: 12px;
        right: 12px;
        opacity: 0;
        transform: scale(0.5);
        transition: all 0.2s ease;
    }
    .format-card.active .format-check-icon {
        opacity: 1;
        transform: scale(1);
    }

    [data-bs-theme="dark"] .format-card,
    body.dark-mode .format-card {
        border-color: rgba(255, 255, 255, 0.12);
        background: #0f172a;
    }
    [data-bs-theme="dark"] .format-card:hover,
    body.dark-mode .format-card:hover {
        border-color: #22c55e;
    }
    [data-bs-theme="dark"] .format-card.active,
    body.dark-mode .format-card.active {
        border-color: #22c55e;
        background: #1e293b;
    }

</style>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sourceInput = document.getElementById('selected-source');
    const formatInput = document.getElementById('selected-format');
    const sourceCards = document.querySelectorAll('.source-card');
    const formatCards = document.querySelectorAll('.format-card');

    const subClassSelect = document.getElementById('filter-sub-classification');
    const mainClassSelect = document.getElementById('filter-classification');

    const resetBtn = document.getElementById('btn-reset-filters');
    const form = document.getElementById('export-center-form');
    const actionSummaryText = document.getElementById('action-summary-text');

    // Embedded mapping of Sub Classifications by Main Classification
    const subClassMap = @json($subClassMap ?? []);

    // 1. DATA SOURCE SELECTION HANDLER
    function setSource(source) {
        sourceInput.value = source;

        sourceCards.forEach(card => {
            if (card.dataset.source === source) {
                card.classList.add('active');
            } else {
                card.classList.remove('active');
            }
        });

        const onlineElements = document.querySelectorAll('.filter-online-only');
        const offlineElements = document.querySelectorAll('.filter-offline-only');
        const subClassWrapper = document.getElementById('wrapper-sub-classification');
        const titleLabel = document.getElementById('label-learning-title');

        if (source === 'online') {
            titleLabel.textContent = 'Learning Title (Content / Playlist)';
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
            titleLabel.textContent = 'Course / Training Name';
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
            titleLabel.textContent = 'Learning / Course Title';
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

    sourceCards.forEach(card => {
        card.addEventListener('click', function () {
            setSource(this.dataset.source);
        });
    });

    // 2. EXPORT FORMAT SELECTION HANDLER
    function setFormat(format) {
        formatInput.value = format;

        formatCards.forEach(card => {
            if (card.dataset.format === format) {
                card.classList.add('active');
            } else {
                card.classList.remove('active');
            }
        });

        updateActionSummary();
    }

    formatCards.forEach(card => {
        card.addEventListener('click', function () {
            setFormat(this.dataset.format);
        });
    });

    function updateActionSummary() {
        const srcNames = {
            'online': 'KP OWL (Online)',
            'offline': 'Non KP OWL (Offline)',
            'all': 'All Record (Combined)'
        };
        const fmtNames = {
            'xlsx': 'Excel (.xlsx)',
            'csv': 'CSV (.csv)',
            'pdf': 'PDF Landscape (.pdf)'
        };

        const currentSrc = srcNames[sourceInput.value] || 'KP OWL';
        const currentFmt = fmtNames[formatInput.value] || 'Excel (.xlsx)';
        if (actionSummaryText) {
            actionSummaryText.innerHTML = `${currentSrc} &bull; ${currentFmt}`;
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
