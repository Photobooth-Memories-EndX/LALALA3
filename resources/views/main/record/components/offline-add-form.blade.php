<!-- OFFLINE ADD TRAINING FORM (PHASE 2F.3.1 DEDICATED PAGE) -->
<form id="offline-manual-add-form" onsubmit="return false;" novalidate>

    <!-- ============================================================= -->
    <!-- SECTION 1: TRAINING INFORMATION -->
    <!-- ============================================================= -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 form-section" id="section-1-training-info">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                <span
                    class="badge rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center"
                    style="width: 28px; height: 28px; font-size: 0.85rem;">1</span>
                <h4 class="fw-bold mb-0 text-dark">Training Information</h4>
            </div>

            <!-- SUBSECTION: GENERAL INFORMATION -->
            <div class="subsection-header p-2 px-3 rounded-2 mb-3 bg-light-subtle border-start border-primary border-3">
                <span class="fw-bold text-primary small text-uppercase">General Information</span>
            </div>

            @php
                $shortYear = "'" . substr((string) ($currentYear ?? date('Y')), -2);
                $defaultL2Verbal = ['Passed', 'Failed', 'Excellent', 'Very Good', 'Good', 'Satisfactory'];
                $dbL2Verbal = \App\Models\OfflineTrainingUser::whereNotNull('l2_verbal')
                    ->where('l2_verbal', '!=', '')
                    ->distinct()
                    ->pluck('l2_verbal')
                    ->all();
                $l2VerbalOptions = array_values(array_unique(array_filter(array_merge($defaultL2Verbal, $dbL2Verbal))));

                $defaultL3Verbal = ['Implemented successfully in plant', 'Project completed on schedule'];
                $dbL3Verbal = \App\Models\OfflineTrainingUser::whereNotNull('l3_verbal')
                    ->where('l3_verbal', '!=', '')
                    ->distinct()
                    ->pluck('l3_verbal')
                    ->all();
                $l3VerbalOptions = array_values(array_unique(array_filter(array_merge($defaultL3Verbal, $dbL3Verbal))));

                $defaultPic = ['HRDC Team', 'HRDC Training Team'];
                $dbPic = \App\Models\OfflineTrainingUser::whereNotNull('pic')
                    ->where('pic', '!=', '')
                    ->distinct()
                    ->pluck('pic')
                    ->all();
                $picOptions = array_values(array_unique(array_filter(array_merge($defaultPic, $dbPic))));
            @endphp
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-8">
                    <label for="add-course-name" class="form-label small fw-semibold">Course Name <span
                            class="text-danger">*</span></label>
                    <div class="position-relative master-combobox" data-type="course_name">
                        <div class="input-group">
                            <input type="text" class="form-control rounded-start-2 master-combobox-input"
                                id="add-course-name" name="course_name"
                                placeholder="e.g. High Voltage Safety &amp; Operation" required autocomplete="off">
                            <button type="button"
                                class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split master-combobox-toggle"
                                title="View options" tabindex="-1">
                                <span class="visually-hidden">Toggle Dropdown</span>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-add-option-modal"
                                data-type="course_name" data-target="#add-course-name" data-title="Course Name"
                                title="Add new Course Name" tabindex="-1">
                                <i class="ti ti-plus"></i>
                            </button>
                        </div>
                        <div class="dropdown-menu master-combobox-menu shadow-sm w-100 py-1"
                            style="display: none; max-height: 260px; overflow-y: auto; z-index: 1050; position: absolute; top: 100%; left: 0;">
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <label for="add-batch" class="form-label small fw-semibold">Batch</label>
                    <input type="text" class="form-control rounded-2" id="add-batch" name="batch"
                        placeholder="e.g. Batch 1">
                </div>
                <div class="col-12">
                    <label for="add-sub-course-name" class="form-label small fw-semibold">Sub Course Name</label>
                    <div class="input-group">
                        <select class="form-select rounded-start-2 select2-master-option" id="add-sub-course-name"
                            name="sub_course_name" data-type="sub_course_name" data-placeholder="Select Sub Course Name"
                            data-tags="true">
                            <option value="">Select Sub Course Name</option>
                            @foreach ($options['sub_course_name'] ?? [] as $opt)
                                <option value="{{ $opt->value }}"
                                    {{ old('sub_course_name') === $opt->value ? 'selected' : '' }}>{{ $opt->value }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline-secondary btn-add-option-modal"
                            data-type="sub_course_name" data-target="#add-sub-course-name" data-title="Sub Course Name"
                            title="Add new Sub Course Name" tabindex="-1">
                            <i class="ti ti-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <label for="res-l1-program" class="form-label small fw-semibold">L1 Program</label>
                    <input type="text" class="form-control rounded-2" id="res-l1-program" name="l1_program"
                        placeholder="e.g. 4.8 / Very Good">
                </div>
                <div class="col-12 col-md-6">
                    <label for="add-type" class="form-label small fw-semibold">Type</label>
                    <div class="input-group">
                        <select class="form-select rounded-start-2 select2-master-option" id="add-type" name="type"
                            data-type="type" data-placeholder="Select Type" data-allow-clear="false">
                            <option value="">Select Type</option>
                            @foreach ($options['type'] ?? [] as $opt)
                                <option value="{{ $opt->value }}" {{ $opt->value === 'In-House' ? 'selected' : '' }}>
                                    {{ $opt->value }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline-secondary btn-add-option-modal" data-type="type"
                            data-target="#add-type" data-title="Type" title="Add new Type" tabindex="-1">
                            <i class="ti ti-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <label for="add-category" class="form-label small fw-semibold">Category</label>
                    <div class="input-group">
                        <select class="form-select rounded-start-2 select2-master-option" id="add-category"
                            name="category" data-type="category" data-placeholder="Select Category" data-tags="true">
                            <option value="">Select Category</option>
                            @foreach ($options['category'] ?? [] as $opt)
                                <option value="{{ $opt->value }}"
                                    {{ old('category') === $opt->value ? 'selected' : '' }}>{{ $opt->value }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline-secondary btn-add-option-modal"
                            data-type="category" data-target="#add-category" data-title="Category"
                            title="Add new Category" tabindex="-1">
                            <i class="ti ti-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <label for="add-level" class="form-label small fw-semibold">Level</label>
                    <div class="input-group">
                        <select class="form-select rounded-start-2 select2-master-option" id="add-level"
                            name="level" data-type="level" data-placeholder="Select Level">
                            <option value="">Select Level</option>
                            @foreach ($options['level'] ?? [] as $opt)
                                <option value="{{ $opt->value }}">{{ $opt->value }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline-secondary btn-add-option-modal"
                            data-type="level" data-target="#add-level" data-title="Level" title="Add new Level"
                            tabindex="-1">
                            <i class="ti ti-plus"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- SUBSECTION: CLASSIFICATION & KPI -->
            <div
                class="subsection-header p-2 px-3 rounded-2 mb-3 bg-light-subtle border-start border-primary border-3">
                <span class="fw-bold text-primary small text-uppercase">Classification &amp; KPI</span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12 col-md-6">
                    <label for="add-main-classification" class="form-label small fw-semibold">Main
                        Classification</label>
                    <select class="form-select rounded-2 select2-master-option" id="add-main-classification"
                        name="main_classification" data-placeholder="Select Classification">
                        <option value="">Select Classification</option>
                        @foreach ($classifications ?? [] as $cls)
                            <option value="{{ $cls->classification_name }}">{{ $cls->classification_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label for="add-sub-classification" class="form-label small fw-semibold">Sub
                        Classification</label>
                    <div class="input-group">
                        <select class="form-select rounded-start-2 select2-master-option" id="add-sub-classification"
                            name="sub_classification" data-type="sub_classification"
                            data-placeholder="Select Sub Classification" data-tags="true">
                            <option value="">Select Sub Classification</option>
                            @foreach ($options['sub_classification'] ?? [] as $opt)
                                <option value="{{ $opt->value }}"
                                    {{ old('sub_classification') === $opt->value ? 'selected' : '' }}>
                                    {{ $opt->value }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline-secondary btn-add-option-modal"
                            data-type="sub_classification" data-target="#add-sub-classification"
                            data-title="Sub Classification" title="Add new Sub Classification" tabindex="-1">
                            <i class="ti ti-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold d-block" id="label-counted-corporate-kpi">Counted as
                        Corporate KPI</label>
                    <div class="d-flex align-items-center gap-3 pt-1">
                        <div class="form-check form-check-inline mb-0">
                            <input class="form-check-input" type="radio" name="counted_corporate_kpi"
                                id="add-counted-kpi-no" value="0" checked>
                            <label class="form-check-label small" for="add-counted-kpi-no">No</label>
                        </div>
                        <div class="form-check form-check-inline mb-0">
                            <input class="form-check-input" type="radio" name="counted_corporate_kpi"
                                id="add-counted-kpi-yes" value="1">
                            <label class="form-check-label small" for="add-counted-kpi-yes">Yes</label>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6" id="container-kpi-year" style="display: none;">
                    <label for="add-corporate-kpi-year" class="form-label small fw-semibold">Corporate KPI Year <span
                            class="text-danger">*</span></label>
                    <div class="input-group">
                        <select class="form-select rounded-start-2" id="add-corporate-kpi-year"
                            name="corporate_kpi_year" disabled>
                            @foreach ($kpiYears ?? [2026, 2025, 2024, 2023, 2022] as $yr)
                                <option value="{{ $yr }}"
                                    {{ $yr == ($currentYear ?? date('Y')) ? 'selected' : '' }}>{{ $yr }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline-secondary btn-add-option-modal"
                            data-type="sub_corporate_kpi" data-target="#add-corporate-kpi-year" data-title="KPI Year"
                            title="Add new KPI Year" tabindex="-1">
                            <i class="ti ti-plus"></i>
                        </button>
                    </div>
                    <input type="hidden" name="sub_corporate_kpi" id="add-sub-corporate-kpi" value="">
                </div>
            </div>

            <!-- SUBSECTION: DELIVERY & PROVIDER -->
            <div
                class="subsection-header p-2 px-3 rounded-2 mb-3 bg-light-subtle border-start border-primary border-3">
                <span class="fw-bold text-primary small text-uppercase">Delivery &amp; Provider</span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12 col-md-6">
                    <label for="add-delivery-method" class="form-label small fw-semibold">Delivery Method</label>
                    <div class="input-group">
                        <select class="form-select rounded-start-2 select2-master-option" id="add-delivery-method"
                            name="delivery_method" data-type="delivery_method"
                            data-placeholder="Select Delivery Method" data-allow-clear="false">
                            <option value="">Select Delivery Method</option>
                            @foreach ($options['delivery_method'] ?? [] as $opt)
                                <option value="{{ $opt->value }}"
                                    {{ $opt->value === 'Classroom' ? 'selected' : '' }}>{{ $opt->value }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline-secondary btn-add-option-modal"
                            data-type="delivery_method" data-target="#add-delivery-method"
                            data-title="Delivery Method" title="Add new Delivery Method" tabindex="-1">
                            <i class="ti ti-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <label for="add-sub-delivery-method" class="form-label small fw-semibold">Sub Delivery
                        Method</label>
                    <div class="input-group">
                        <select class="form-select rounded-start-2 select2-master-option" id="add-sub-delivery-method"
                            name="sub_delivery_method" data-type="sub_delivery_method"
                            data-placeholder="Select Sub Delivery Method" data-tags="true">
                            <option value="">Select Sub Delivery Method</option>
                            @foreach ($options['sub_delivery_method'] ?? [] as $opt)
                                <option value="{{ $opt->value }}"
                                    {{ old('sub_delivery_method') === $opt->value ? 'selected' : '' }}>
                                    {{ $opt->value }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline-secondary btn-add-option-modal"
                            data-type="sub_delivery_method" data-target="#add-sub-delivery-method"
                            data-title="Sub Delivery Method" title="Add new Sub Delivery Method" tabindex="-1">
                            <i class="ti ti-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <label for="add-provider" class="form-label small fw-semibold">Provider</label>
                    <div class="input-group">
                        <select class="form-select rounded-start-2 select2-master-option" id="add-provider"
                            name="provider" data-type="provider" data-placeholder="Select Provider"
                            data-allow-clear="false">
                            <option value="">Select Provider</option>
                            @foreach ($options['provider'] ?? [] as $opt)
                                <option value="{{ $opt->value }}"
                                    {{ $opt->value === 'External' ? 'selected' : '' }}>{{ $opt->value }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline-secondary btn-add-option-modal"
                            data-type="provider" data-target="#add-provider" data-title="Provider"
                            title="Add new Provider" tabindex="-1">
                            <i class="ti ti-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <label for="add-sub-provider" class="form-label small fw-semibold">Sub Provider</label>
                    <div class="input-group">
                        <select class="form-select rounded-start-2 select2-master-option" id="add-sub-provider"
                            name="sub_provider" data-type="sub_provider" data-placeholder="Select Sub Provider"
                            data-allow-clear="false">
                            <option value="">Select Sub Provider</option>
                            @foreach ($options['sub_provider'] ?? [] as $opt)
                                <option value="{{ $opt->value }}" {{ $opt->value === 'Vendor' ? 'selected' : '' }}>
                                    {{ $opt->value }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline-secondary btn-add-option-modal"
                            data-type="sub_provider" data-target="#add-sub-provider" data-title="Sub Provider"
                            title="Add new Sub Provider" tabindex="-1">
                            <i class="ti ti-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="col-12">
                    <label for="add-provider-name" class="form-label small fw-semibold">Provider Name</label>
                    <div class="input-group">
                        <select class="form-select rounded-start-2 select2-master-option" id="add-provider-name"
                            name="provider_name" data-type="provider_name" data-placeholder="Select Provider Name"
                            data-tags="true">
                            <option value="">Select Provider Name</option>
                            @foreach ($options['provider_name'] ?? [] as $opt)
                                <option value="{{ $opt->value }}"
                                    {{ old('provider_name') === $opt->value ? 'selected' : '' }}>{{ $opt->value }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline-secondary btn-add-option-modal"
                            data-type="provider_name" data-target="#add-provider-name" data-title="Provider Name"
                            title="Add new Provider Name" tabindex="-1">
                            <i class="ti ti-plus"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- SUBSECTION: SCHEDULE & DURATION PROGRAM EVALUATION -->
            <div
                class="subsection-header p-2 px-3 rounded-2 mb-3 bg-light-subtle border-start border-primary border-3">
                <span class="fw-bold text-primary small text-uppercase">Schedule &amp; Duration Program Evaluation</span>
            </div>

            <div class="row g-3">
                <div class="col-12 col-sm-6 col-md-4">
                    <label for="add-duration" class="form-label small fw-semibold">Duration</label>
                    <input type="text" class="form-control rounded-2" id="add-duration" name="duration"
                        placeholder="12:00:00" value="08:00:00">
                </div>
                <div class="col-12 col-sm-6 col-md-4">
                    <label for="add-learning-hours" class="form-label small fw-semibold">Learning Hours</label>
                    <input type="number" step="0.1" min="0" class="form-control rounded-2"
                        id="add-learning-hours" name="learning_hours" placeholder="12.00" value="8.0">
                </div>
                <div class="col-12 col-sm-6 col-md-4">
                    <label for="add-month" class="form-label small fw-semibold">Month</label>
                    <input type="text" class="form-control rounded-2 bg-light text-muted" id="add-month" readonly
                        placeholder="Auto from Start Date">
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label for="add-start-date" class="form-label small fw-semibold">Start Date</label>
                    <input type="date" class="form-control rounded-2" id="add-start-date" name="start_date">
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label for="add-start-time" class="form-label small fw-semibold">Start Time</label>
                    <input type="time" class="form-control rounded-2" id="add-start-time" name="start_time"
                        value="08:00">
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label for="add-finish-date" class="form-label small fw-semibold">Finish Date</label>
                    <input type="date" class="form-control rounded-2" id="add-finish-date" name="finish_date">
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label for="add-end-time" class="form-label small fw-semibold">Finish Time</label>
                    <input type="time" class="form-control rounded-2" id="add-end-time" name="end_time"
                        value="16:00">
                </div>
                <div class="col-12 col-md-6">
                    <label for="add-venue" class="form-label small fw-semibold">Venue</label>
                    <div class="input-group">
                        <select class="form-select rounded-start-2 select2-master-option" id="add-venue"
                            name="venue" data-type="venue" data-placeholder="Select Venue" data-tags="true">
                            <option value="">Select Venue</option>
                            @foreach ($options['venue'] ?? [] as $opt)
                                <option value="{{ $opt->value }}"
                                    {{ old('venue') === $opt->value ? 'selected' : '' }}>{{ $opt->value }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline-secondary btn-add-option-modal"
                            data-type="venue" data-target="#add-venue" data-title="Venue" title="Add new Venue"
                            tabindex="-1">
                            <i class="ti ti-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <label for="add-instructor" class="form-label small fw-semibold">Instructor</label>
                    <div class="input-group">
                        <select class="form-select rounded-start-2 select2-master-option" id="add-instructor"
                            name="instructor" data-type="instructor" data-placeholder="Select Instructor"
                            data-tags="true">
                            <option value="">Select Instructor</option>
                            @foreach ($options['instructor'] ?? [] as $opt)
                                <option value="{{ $opt->value }}"
                                    {{ old('instructor') === $opt->value ? 'selected' : '' }}>{{ $opt->value }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline-secondary btn-add-option-modal"
                            data-type="instructor" data-target="#add-instructor" data-title="Instructor"
                            title="Add new Instructor" tabindex="-1">
                            <i class="ti ti-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================= -->
    <!-- SECTION 2: PARTICIPANTS -->
    <!-- ============================================================= -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 form-section" id="section-2-participants">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span
                        class="badge rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center"
                        style="width: 28px; height: 28px; font-size: 0.85rem;">2</span>
                    <h4 class="fw-bold mb-0 text-dark">Participants</h4>
                </div>
                <span class="badge bg-blue-lt fw-semibold px-2 py-1" id="badge-participants-count"
                    style="font-size: 0.85rem;">0 Enrolled</span>
            </div>
            <p class="text-muted small mb-3">Search and add employees to this training event, or input manually for
                non-LMS participants.</p>

            <!-- SEARCH BAR & MANUAL TOGGLE -->
            <div class="card border p-3 rounded-3 mb-3 bg-light-subtle">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-8 position-relative">
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="ti ti-search text-muted"></i></span>
                            <input type="text" class="form-control" id="participant-search-input"
                                placeholder="Search Employee ID, NIP, or Name..." autocomplete="off">
                            <button type="button" class="btn btn-primary px-3"
                                id="btn-search-participant">Search</button>
                        </div>
                        <!-- Autocomplete Results Dropdown -->
                        <div class="dropdown-menu w-100 shadow p-1 mt-1" id="participant-search-results"
                            style="display: none; max-height: 240px; overflow-y: auto; z-index: 1050;">
                            <!-- Injected dynamically -->
                        </div>
                    </div>
                    <div class="col-12 col-md-4 text-md-end">
                        <button type="button"
                            class="btn btn-outline-secondary d-inline-flex align-items-center gap-1.5"
                            id="btn-toggle-manual-participant">
                            <i class="ti ti-user-plus"></i>
                            <span>Input Manually</span>
                        </button>
                    </div>
                </div>

                <!-- INLINE MANUAL PARTICIPANT FORM (COLLAPSIBLE) -->
                <div id="manual-participant-card" class="mt-3 p-3 bg-white rounded-3 border" style="display: none;">
                    <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
                        <span class="fw-semibold small text-primary">Manual Participant Information</span>
                        <button type="button" class="btn-close btn-sm" id="btn-cancel-manual-participant"
                            aria-label="Close"></button>
                    </div>
                    <div class="row g-2">
                        <div class="col-12 col-sm-6 col-md-3">
                            <label class="form-label extra-small fw-semibold mb-1">Employee ID / NIP <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm rounded-2" id="manual-emp-id"
                                placeholder="e.g. 100234">
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <label class="form-label extra-small fw-semibold mb-1">Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm rounded-2" id="manual-emp-name"
                                placeholder="Full Name">
                        </div>
                        <div class="col-12 col-sm-6 col-md-2">
                            <label class="form-label extra-small fw-semibold mb-1">Grade</label>
                            <input type="text" class="form-control form-control-sm rounded-2"
                                id="manual-emp-grade" placeholder="e.g. 4A">
                        </div>
                        <div class="col-12 col-sm-6 col-md-4">
                            <label class="form-label extra-small fw-semibold mb-1">Job Position</label>
                            <input type="text" class="form-control form-control-sm rounded-2"
                                id="manual-emp-position" placeholder="Job Title">
                        </div>
                        <div class="col-12 col-sm-6 col-md-4">
                            <label class="form-label extra-small fw-semibold mb-1">Team</label>
                            <input type="text" class="form-control form-control-sm rounded-2" id="manual-emp-team"
                                placeholder="e.g. Rolling Mill 1">
                        </div>
                        <div class="col-12 col-sm-6 col-md-4">
                            <label class="form-label extra-small fw-semibold mb-1">Department</label>
                            <input type="text" class="form-control form-control-sm rounded-2" id="manual-emp-dept"
                                placeholder="e.g. Maintenance Dept">
                        </div>
                        <div class="col-12 col-sm-6 col-md-4">
                            <label class="form-label extra-small fw-semibold mb-1">Company</label>
                            <input type="text" class="form-control form-control-sm rounded-2"
                                id="manual-emp-company" value="PT Krakatau Posco" placeholder="Company Entity">
                        </div>
                    </div>
                    <div class="text-end mt-2">
                        <button type="button" class="btn btn-sm btn-primary px-3" id="btn-add-manual-participant">
                            <i class="ti ti-check me-1"></i> Add to Training
                        </button>
                    </div>
                </div>
            </div>

            <!-- SELECTED PARTICIPANTS TABLE -->
            <div class="table-responsive border rounded-3">
                <table class="table table-hover align-middle mb-0" id="selected-participants-table">
                    <thead class="bg-light text-muted extra-small text-uppercase">
                        <tr>
                            <th style="width: 40px;" class="text-center">No</th>
                            <th style="width: 120px;">Employee ID</th>
                            <th>Name</th>
                            <th style="width: 80px;">Grade</th>
                            <th>Job Position</th>
                            <th>Team</th>
                            <th>Department</th>
                            <th>Company</th>
                            <th class="text-end" style="width: 60px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="selected-participants-tbody">
                        <tr id="empty-participants-row">
                            <td colspan="9" class="text-center text-muted py-4 small">
                                <i class="ti ti-users fs-2 d-block mb-1 text-secondary"></i>
                                No participants added yet. Search employee or click Input Manually.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ============================================================= -->
    <!-- SECTION 3: LEARNING RESULTS -->
    <!-- ============================================================= -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 form-section" id="section-3-results">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span
                        class="badge rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center"
                        style="width: 28px; height: 28px; font-size: 0.85rem;">3</span>
                    <h4 class="fw-bold mb-0 text-dark">Learning Results</h4>
                </div>
                <span class="text-muted small">Configure individual results, attendance &amp; scores per
                    participant</span>
            </div>

            <!-- PARTICIPANT SELECTOR SWITCHER -->
            <div
                class="card border p-2 px-3 rounded-3 mb-3 bg-light-subtle d-flex flex-row align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <label for="select-active-participant" class="small fw-semibold mb-0 text-nowrap">Active
                        Participant:</label>
                    <select class="form-select form-select-sm rounded-2" id="select-active-participant"
                        style="min-width: 240px; max-width: 360px;">
                        <option value="">No participants enrolled</option>
                    </select>
                    <span class="text-muted extra-small" id="participant-indicator-text">0 of 0</span>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-2" id="btn-prev-participant"
                        title="Previous Participant" disabled>
                        <i class="ti ti-chevron-left"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary px-2" id="btn-next-participant"
                        title="Next Participant" disabled>
                        <i class="ti ti-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- EMPTY NOTICE (IF 0 PARTICIPANTS) -->
            <div id="no-participants-alert" class="alert alert-info d-flex align-items-center gap-2 mb-0">
                <i class="ti ti-info-circle fs-3"></i>
                <div class="small">Please enroll at least one participant in Section 2 above to configure learning
                    results.</div>
            </div>

            <!-- ACTIVE PARTICIPANT RESULTS FORM -->
            <div id="active-participant-form-container" style="display: none;">
                <!-- SUBSECTION: LEARNING PROGRESS -->
                <div
                    class="subsection-header p-2 px-3 rounded-2 mb-3 bg-light-subtle border-start border-primary border-3 d-flex align-items-center justify-content-between">
                    <span class="fw-bold text-primary small text-uppercase">Learning Progress</span>
                    <span class="badge bg-success-lt fw-bold" id="res-status-badge">FINISHED</span>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-sm-6 col-md-4">
                        <label for="res-progress" class="form-label small fw-semibold">Progress (%) <span
                                class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" step="1" min="0" max="100"
                                class="form-control rounded-start" id="res-progress" value="100">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4">
                        <label for="res-assessment-score" class="form-label small fw-semibold">Assessment
                            Score</label>
                        <input type="number" step="0.1" min="0" max="100"
                            class="form-control rounded-2" id="res-assessment-score" placeholder="e.g. 85.0">
                    </div>
                    <div class="col-12 col-sm-6 col-md-4">
                        <label for="res-certificate" class="form-label small fw-semibold">Certificate</label>
                        <select class="form-select rounded-2" id="res-certificate">
                            <option value="Yes" selected>Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4" id="res-cert-issued-container">
                        <label for="res-certificate-issued-date" class="form-label small fw-semibold">Certificate Issued Date</label>
                        <input type="date" class="form-control rounded-2" id="res-certificate-issued-date">
                    </div>
                    <div class="col-12 col-sm-6 col-md-4" id="res-cert-expired-container">
                        <label for="res-certificate-expired-date" class="form-label small fw-semibold">Certificate Expired Date</label>
                        <input type="date" class="form-control rounded-2" id="res-certificate-expired-date">
                    </div>
                </div>

                <!-- SUBSECTION: ATTENDANCE -->
                <div
                    class="subsection-header p-2 px-3 rounded-2 mb-3 bg-light-subtle border-start border-primary border-3">
                    <span class="fw-bold text-primary small text-uppercase">Attendance</span>
                </div>

                <div class="row g-3 mb-4 align-items-center">
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold d-block">Attendance <span
                                class="text-danger">*</span></label>
                        <div class="d-flex align-items-center gap-3 pt-1">
                            <div class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="radio" name="res_attendance"
                                    id="res-attendance-yes" value="1" checked>
                                <label class="form-check-label small" for="res-attendance-yes">Yes (Present)</label>
                            </div>
                            <div class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="radio" name="res_attendance"
                                    id="res-attendance-no" value="0">
                                <label class="form-check-label small" for="res-attendance-no">No (Absent)</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-8" id="res-reason-container">
                        <label for="res-attendance-reason" class="form-label small fw-semibold">Reason (if
                            absent)</label>
                        <input type="text" class="form-control rounded-2" id="res-attendance-reason"
                            placeholder="Attendance justification...">
                    </div>
                </div>

                <!-- SUBSECTION: OBLIGATION -->
                <div
                    class="subsection-header p-2 px-3 rounded-2 mb-3 bg-light-subtle border-start border-primary border-3">
                    <span class="fw-bold text-primary small text-uppercase">Obligation (Service Bond)</span>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-sm-6 col-md-3">
                        <label for="res-bond-status" class="form-label small fw-semibold">Bond Status</label>
                        <select class="form-select rounded-2" id="res-bond-status">
                            <option value="No" selected>No</option>
                            <option value="Yes">Yes</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <label for="res-bond-duration" class="form-label small fw-semibold">Bond Duration (months)</label>
                        <input type="number" step="1" min="1" class="form-control rounded-2" id="res-bond-duration" placeholder="e.g. 12" disabled>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <label for="res-bond-end-date" class="form-label small fw-semibold">Bond End Date</label>
                        <input type="date" class="form-control rounded-2" id="res-bond-end-date" disabled>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <label for="res-bond-amount" class="form-label small fw-semibold">Bond Amount (IDR)</label>
                        <input type="number" step="1000" min="0" class="form-control rounded-2"
                            id="res-bond-amount" placeholder="e.g. 5000000" disabled>
                    </div>
                </div>

                <!-- SUBSECTION: EVALUATION (L2, L3) -->
                <div
                    class="subsection-header p-2 px-3 rounded-2 mb-3 bg-light-subtle border-start border-primary border-3">
                    <span class="fw-bold text-primary small text-uppercase">Kirkpatrick Evaluation (L2 - L3)</span>
                </div>

                <div class="row g-3 mb-3">
                    <!-- L2 Numerical -->
                    <div class="col-12 col-sm-6 col-md-6">
                        <label for="res-l2-numerical"
                            class="form-label extra-small fw-bold text-muted text-uppercase mb-1">L2 (Post Test) &bull;
                            Numerical</label>
                        <input type="number" step="0.1" min="0" max="100"
                            class="form-control form-control-sm rounded-2" id="res-l2-numerical"
                            placeholder="Score (0-100)">
                    </div>
                    <!-- L2 Verbal -->
                    <div class="col-12 col-sm-6 col-md-6">
                        <label for="res-l2-verbal"
                            class="form-label extra-small fw-bold text-muted text-uppercase mb-1">L2 (Post Test) &bull;
                            Verbal</label>
                        <select class="form-select form-select-sm rounded-2 select2-searchable-field"
                            id="res-l2-verbal" data-placeholder="Select or type verbal result" data-tags="true">
                            <option value="">Select or type verbal result</option>
                            @foreach ($l2VerbalOptions as $opt)
                                <option value="{{ $opt }}">{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <!-- L3 Numerical -->
                    <div class="col-12 col-sm-6 col-md-4">
                        <label for="res-l3-numerical"
                            class="form-label extra-small fw-bold text-muted text-uppercase mb-1">L3 (Project) &bull;
                            Numerical</label>
                        <input type="number" step="0.1" min="0" max="100"
                            class="form-control form-control-sm rounded-2" id="res-l3-numerical"
                            placeholder="Score (0-100)">
                    </div>
                    <!-- L3 Verbal -->
                    <div class="col-12 col-sm-6 col-md-4">
                        <label for="res-l3-verbal"
                            class="form-label extra-small fw-bold text-muted text-uppercase mb-1">L3 (Project) &bull;
                            Verbal</label>
                        <select class="form-select form-select-sm rounded-2 select2-searchable-field"
                            id="res-l3-verbal" data-placeholder="Select or type project verbal" data-tags="true">
                            <option value="">Select or type project verbal</option>
                            @foreach ($l3VerbalOptions as $opt)
                                <option value="{{ $opt }}">{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <!-- L3 PIC -->
                    <div class="col-12 col-md-4">
                        <label for="res-pic" class="form-label extra-small fw-bold text-muted text-uppercase mb-1">L3
                            (Project) &bull; PIC</label>
                        <select class="form-select form-select-sm rounded-2 select2-searchable-field" id="res-pic"
                            data-placeholder="Select or type PIC" data-tags="true">
                            <option value="">Select or type PIC</option>
                            @foreach ($picOptions as $opt)
                                <option value="{{ $opt }}">{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- SAVE RESULT BUTTON -->
                <div
                    class="d-flex align-items-center justify-content-between p-2 px-3 rounded-2 bg-light border mt-3 flex-wrap gap-2">
                    <span class="text-muted extra-small" id="save-result-status-msg">Changes update live in
                        memory.</span>
                    <button type="button" class="btn btn-sm btn-primary px-3"
                        id="btn-save-participant-result">
                        <i class="ti ti-device-floppy me-1"></i> Save and Submit
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================= -->
    <!-- SECTION 4: REVIEW & SAVE -->
    <!-- ============================================================= -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 form-section" id="section-4-review">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span
                        class="badge rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center"
                        style="width: 28px; height: 28px; font-size: 0.85rem;">4</span>
                    <div>
                        <h4 class="fw-bold mb-0 text-dark">Review &amp; Save</h4>
                        <div class="text-muted small">Review all information before saving to the database.</div>
                    </div>
                </div>

                <!-- Compact Section Navigation / Edit Buttons -->
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button type="button"
                        class="btn btn-sm btn-outline-secondary rounded-2 d-inline-flex align-items-center gap-1.5"
                        id="btn-review-edit-1" title="Go to Training Information">
                        <i class="ti ti-edit"></i>
                        <span>Edit Training</span>
                    </button>
                    <button type="button"
                        class="btn btn-sm btn-outline-secondary rounded-2 d-inline-flex align-items-center gap-1.5"
                        id="btn-review-edit-2" title="Go to Participants">
                        <i class="ti ti-users"></i>
                        <span>Edit Participants</span>
                    </button>
                    <button type="button"
                        class="btn btn-sm btn-outline-secondary rounded-2 d-inline-flex align-items-center gap-1.5"
                        id="btn-review-edit-3" title="Go to Learning Results">
                        <i class="ti ti-clipboard-check"></i>
                        <span>Edit Results</span>
                    </button>
                </div>
            </div>

            <!-- PREVIEW META HEADER -->
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-1.5">
                        <i class="ti ti-table text-primary"></i>
                        <span>Full Record Preview</span>
                    </h5>
                    <span class="badge bg-light text-secondary border px-2 py-1" id="rev-table-columns-count">44 Columns</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-blue-lt fw-semibold px-2.5 py-1.5" id="rev-part-count-badge">
                        <span id="rev-part-count">0</span> Participant Records
                    </span>
                </div>
            </div>

            <!-- EMPTY PARTICIPANT ALERT -->
            <div id="rev-empty-state" class="alert alert-warning border-0 rounded-3 py-4 text-center my-3"
                style="display: none;">
                <div class="avatar avatar-md rounded-circle mx-auto mb-2"
                    style="background-color: #fef3c7; color: #d97706; width: 48px; height: 48px;">
                    <i class="ti ti-users-minus fs-2"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1">No participants have been added</h5>
                <p class="text-muted small mb-3">Please enroll at least one participant in Section 2 to preview the
                    full record rows.</p>
                <button type="button" class="btn btn-sm btn-primary rounded-2 px-3" id="btn-review-empty-edit-part">
                    <i class="ti ti-user-plus me-1"></i> Edit Participants
                </button>
            </div>

            <!-- HORIZONTAL SCROLL RECORD TABLE WRAPPER -->
            <div id="rev-table-container"
                class="table-responsive offline-review-scroll-wrapper rounded-3 bg-white mb-2 position-relative">
                <table class="table table-hover table-striped align-middle mb-0" id="offline-review-record-table">
                    <thead>
                        <tr>
                            @php
                                $schemaCols =
                                    $schemaColumns ?? \App\Services\Record\OfflineTrainingExcelSchema::columns();
                            @endphp
                            @foreach ($schemaCols as $col)
                                @php
                                    $colIdx = $col['col_index'];
                                    $header = $col['header'];
                                    $group = $col['group'] ?? '';
                                    $align = in_array($col['type'], ['number', 'date', 'duration', 'boolean'])
                                        ? 'text-center'
                                        : 'text-start';
                                    if ($colIdx === 1) {
                                        $align = 'text-center';
                                    }
                                    if ($col['key'] === 'bond_amount') {
                                        $align = 'text-end';
                                    }

                                    $stickyClass = '';
                                    if ($colIdx === 1) {
                                        $stickyClass = 'sticky-col sticky-col-1';
                                    } elseif ($colIdx === 2) {
                                        $stickyClass = 'sticky-col sticky-col-2';
                                    }

                                    $displayHeader = strtoupper($header);
                                @endphp
                                <th class="{{ $align }} {{ $stickyClass }}"
                                    data-col-index="{{ $colIdx }}" title="{{ $col['note'] ?? $header }}">
                                    @if (!empty($group))
                                        <div class="col-group-label">{{ $group }}</div>
                                    @else
                                        <div class="col-group-label">&nbsp;</div>
                                    @endif
                                    <div class="col-title">{{ $displayHeader }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody id="offline-review-tbody">
                        <!-- Populated dynamically via JS: 1 row per participant with training data repeated -->
                    </tbody>
                </table>
            </div>

            <!-- TABLE FOOTER STATUS / SCROLL HINT -->
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 text-muted small pt-1">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-info-circle text-primary"></i>
                    <span id="rev-footer-summary">0 participant records ready to save</span>
                </div>
                <div class="d-flex align-items-center gap-1 extra-small text-secondary">
                    <i class="ti ti-arrows-left-right"></i>
                    <span>Scroll horizontally to review all 44 canonical columns</span>
                </div>
            </div>
        </div>
    </div>

    <!-- BOTTOM ACTION AREA -->
    <div class="card border-0 shadow-sm rounded-3 p-3 mb-4 bg-white sticky-bottom-actions">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="text-danger small fw-semibold" id="add-training-alert" style="display: none;"></div>
            <div class="d-flex align-items-center gap-2 ms-auto">
                <a href="{{ route('manage.record.offline') }}" class="btn btn-outline-secondary px-3"
                    id="btn-cancel-add-training">Cancel</a>
                <button type="button" class="btn btn-primary px-4 d-inline-flex align-items-center gap-1.5"
                    id="btn-submit-add-training" style="background-color: #d97706; border-color: #d97706;">
                    <i class="ti ti-check fs-2"></i>
                    <span>Save Training</span>
                </button>
            </div>
        </div>
</form>

@include('main.record.components.offline-modal-add-option')
