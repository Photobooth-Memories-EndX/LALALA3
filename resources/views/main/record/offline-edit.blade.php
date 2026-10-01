@extends('layouts.sidebar-master')

@section('content')
<div class="page-body manage-record-edit-page">
    <div class="container-xl">
        <!-- BACK BUTTON -->
        <div class="mb-3">
            <a href="{{ route('manage.record.offline.classification', $classification['slug']) }}" class="btn-back d-inline-flex align-items-center gap-1 text-decoration-none fw-medium">
                <i class="ti ti-arrow-left"></i>
                <span>Back to {{ $classification['name'] }}</span>
            </a>
        </div>

        <!-- SECTION HEADER: Edit Record -->
        <div class="card border-0 shadow-sm p-3 p-md-4 mb-4 rounded-3 bg-white record-section-header">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 record-section-icon"
                         style="width: 54px; height: 54px; background-color: #fef3c7; color: #d97706; border-radius: 12px;">
                        <i class="ti ti-edit fs-1"></i>
                    </div>
                    <div>
                        <h2 class="h3 fw-bold mb-1 text-dark">Edit Offline Training Record</h2>
                        <p class="text-muted small mb-0">Update offline employee training record and results.</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-secondary border px-2.5 py-1.5 fs-4">
                        <i class="ti ti-user me-1"></i> {{ $record->name }} ({{ $record->employee_id }})
                    </span>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="ti ti-check me-2 fs-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <div class="fw-bold mb-1"><i class="ti ti-alert-triangle me-1 fs-2"></i> Please correct the following errors:</div>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- FULL-WIDTH CONTINUOUS VERTICAL SCROLL FORM -->
        <form id="offline-record-edit-form" method="POST" action="{{ route('manage.record.offline.update', $record->id) }}" novalidate>
            @csrf
            @method('PUT')

            <!-- ============================================================= -->
            <!-- SECTION 1: TRAINING INFORMATION -->
            <!-- ============================================================= -->
            <div class="card border-0 shadow-sm rounded-3 mb-4 form-section" id="section-1-training-info">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                        <span class="badge rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.85rem;">1</span>
                        <h4 class="fw-bold mb-0 text-dark">Training Information</h4>
                    </div>

                    <!-- SUBSECTION: GENERAL INFORMATION -->
                    <div class="subsection-header p-2 px-3 rounded-2 mb-3 bg-light-subtle border-start border-primary border-3">
                        <span class="fw-bold text-primary small text-uppercase">General Information</span>
                    </div>

                    @php
                        $shortYear = "'" . substr((string) ($kpiYear ?? date('Y')), -2);
                    @endphp
                    <input type="hidden" id="edit-corporate-kpi-year" name="corporate_kpi_year" value="{{ old('corporate_kpi_year', $training->corporate_kpi_year ?: $kpiYear) }}">

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-8">
                            <label for="edit-course-name" class="form-label small fw-semibold">Course Name <span class="text-danger">*</span></label>
                            <div class="position-relative master-combobox" data-type="course_name">
                                <div class="input-group">
                                    <input type="text" class="form-control rounded-start-2 master-combobox-input @error('course_name') is-invalid @enderror" id="edit-course-name" name="course_name" value="{{ old('course_name', $training->course_name) }}" placeholder="e.g. High Voltage Safety &amp; Operation" required autocomplete="off">
                                    <button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split master-combobox-toggle" title="View options" tabindex="-1">
                                        <span class="visually-hidden">Toggle Dropdown</span>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-add-option-modal" data-type="course_name" data-target="#edit-course-name" data-title="Course Name" title="Add new Course Name" tabindex="-1">
                                        <i class="ti ti-plus"></i>
                                    </button>
                                </div>
                                <div class="dropdown-menu master-combobox-menu shadow-sm w-100 py-1" style="display: none; max-height: 260px; overflow-y: auto; z-index: 1050; position: absolute; top: 100%; left: 0;"></div>
                            </div>
                            @error('course_name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="edit-batch" class="form-label small fw-semibold">Batch</label>
                            <input type="text" class="form-control rounded-2" id="edit-batch" name="batch" value="{{ old('batch', $training->batch) }}" placeholder="e.g. Batch 1">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="edit-sub-course-name" class="form-label small fw-semibold">Sub Course Name</label>
                            <div class="input-group">
                                <input type="text" class="form-control rounded-start-2" id="edit-sub-course-name" name="sub_course_name" value="{{ old('sub_course_name', $training->sub_course_name) }}" placeholder="e.g. Switchgear Troubleshooting Module" list="edit-sub-course-name-datalist" autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary btn-add-option-modal" data-type="sub_course_name" data-target="#edit-sub-course-name" data-datalist="#edit-sub-course-name-datalist" data-title="Sub Course Name" title="Add new Sub Course Name" tabindex="-1">
                                    <i class="ti ti-plus"></i>
                                </button>
                            </div>
                            <datalist id="edit-sub-course-name-datalist">
                                @foreach ($options['sub_course_name'] ?? [] as $opt)
                                    <option value="{{ $opt->value }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="edit-type" class="form-label small fw-semibold">Type</label>
                            <div class="input-group">
                                <select class="form-select rounded-start-2 select2-master-option" id="edit-type" name="type" data-type="type" data-placeholder="Select Type">
                                    <option value="">Select Type</option>
                                    @php
                                        $currentType = old('type', $training->type);
                                        $typeOpts = collect($options['type'] ?? [])->pluck('value')->all();
                                        if ($currentType && !in_array($currentType, $typeOpts, true)) {
                                            $typeOpts[] = $currentType;
                                        }
                                    @endphp
                                    @foreach ($typeOpts as $tOpt)
                                        <option value="{{ $tOpt }}" {{ $currentType === $tOpt ? 'selected' : '' }}>{{ $tOpt }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-secondary btn-add-option-modal" data-type="type" data-target="#edit-type" data-title="Type" title="Add new Type" tabindex="-1">
                                    <i class="ti ti-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="edit-category" class="form-label small fw-semibold">Category</label>
                            <div class="input-group">
                                <input type="text" class="form-control rounded-start-2" id="edit-category" name="category" value="{{ old('category', $training->category) }}" placeholder="e.g. Technical Competency" list="edit-category-datalist" autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary btn-add-option-modal" data-type="category" data-target="#edit-category" data-datalist="#edit-category-datalist" data-title="Category" title="Add new Category" tabindex="-1">
                                    <i class="ti ti-plus"></i>
                                </button>
                            </div>
                            <datalist id="edit-category-datalist">
                                @foreach ($options['category'] ?? [] as $opt)
                                    <option value="{{ $opt->value }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="edit-level" class="form-label small fw-semibold">Level</label>
                            <div class="input-group">
                                <select class="form-select rounded-start-2 select2-master-option" id="edit-level" name="level" data-type="level" data-placeholder="Select Level">
                                    <option value="">Select Level</option>
                                    @php
                                        $currentLevel = old('level', $training->level);
                                        $levelOpts = collect($options['level'] ?? [])->pluck('value')->all();
                                        if ($currentLevel && !in_array($currentLevel, $levelOpts, true)) {
                                            $levelOpts[] = $currentLevel;
                                        }
                                    @endphp
                                    @foreach ($levelOpts as $lOpt)
                                        <option value="{{ $lOpt }}" {{ $currentLevel === $lOpt ? 'selected' : '' }}>{{ $lOpt }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-secondary btn-add-option-modal" data-type="level" data-target="#edit-level" data-title="Level" title="Add new Level" tabindex="-1">
                                    <i class="ti ti-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- SUBSECTION: CLASSIFICATION & KPI -->
                    <div class="subsection-header p-2 px-3 rounded-2 mb-3 bg-light-subtle border-start border-primary border-3">
                        <span class="fw-bold text-primary small text-uppercase">Classification &amp; KPI</span>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6">
                            <label for="edit-main-classification" class="form-label small fw-semibold">Main Classification</label>
                            @php
                                $currentClass = old('main_classification', $training->main_classification);
                                $allClasses = array_unique(array_merge(
                                    $mainClassifications,
                                    ['Basic & Common Duties', 'Leadership', 'Competency', 'Management', 'Technical', 'Quality & Safety', 'General', 'ISO Standard', 'Innovation', 'Job Functional', 'Language', 'Maintenance', 'Other', 'SHE', 'Technology'],
                                    $currentClass ? [$currentClass] : []
                                ));
                                sort($allClasses);
                            @endphp
                            <select class="form-select rounded-2" id="edit-main-classification" name="main_classification">
                                <option value="">Select Classification</option>
                                @foreach ($allClasses as $classOpt)
                                    <option value="{{ $classOpt }}" {{ $currentClass === $classOpt ? 'selected' : '' }}>{{ $classOpt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="edit-sub-classification" class="form-label small fw-semibold">Sub Classification</label>
                            <div class="input-group">
                                <input type="text" class="form-control rounded-start-2" id="edit-sub-classification" name="sub_classification" value="{{ old('sub_classification', $training->sub_classification) }}" placeholder="e.g. Plant Maintenance" list="edit-sub-classification-datalist" autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary btn-add-option-modal" data-type="sub_classification" data-target="#edit-sub-classification" data-datalist="#edit-sub-classification-datalist" data-title="Sub Classification" title="Add new Sub Classification" tabindex="-1">
                                    <i class="ti ti-plus"></i>
                                </button>
                            </div>
                            <datalist id="edit-sub-classification-datalist">
                                @foreach ($options['sub_classification'] ?? [] as $opt)
                                    <option value="{{ $opt->value }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        @php
                            $isKpiChecked = (bool) old('counted_corporate_kpi', $training->counted_corporate_kpi);
                            $selectedKpiYear = old('corporate_kpi_year', $training->corporate_kpi_year ?: ($training->sub_corporate_kpi && preg_match('/^\d{4}$/', (string)$training->sub_corporate_kpi) ? $training->sub_corporate_kpi : ($training->start_date ? $training->start_date->format('Y') : date('Y'))));
                        @endphp
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold d-block" id="label-edit-counted-kpi">Counted as Corporate KPI</label>
                            <div class="d-flex align-items-center gap-3 pt-1">
                                <div class="form-check form-check-inline mb-0">
                                    <input class="form-check-input" type="radio" name="counted_corporate_kpi" id="edit-counted-kpi-no" value="0" {{ !$isKpiChecked ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="edit-counted-kpi-no">No</label>
                                </div>
                                <div class="form-check form-check-inline mb-0">
                                    <input class="form-check-input" type="radio" name="counted_corporate_kpi" id="edit-counted-kpi-yes" value="1" {{ $isKpiChecked ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="edit-counted-kpi-yes">Yes</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6" id="edit-container-kpi-year" style="{{ $isKpiChecked ? '' : 'display: none;' }}">
                            <label for="edit-corporate-kpi-year" class="form-label small fw-semibold">Corporate KPI Year <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select class="form-select rounded-start-2" id="edit-corporate-kpi-year" name="corporate_kpi_year" {{ $isKpiChecked ? '' : 'disabled' }}>
                                    @foreach ($kpiYears ?? [2026, 2025, 2024, 2023, 2022] as $yr)
                                        <option value="{{ $yr }}" {{ $yr == $selectedKpiYear ? 'selected' : '' }}>{{ $yr }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-secondary btn-add-option-modal" data-type="sub_corporate_kpi" data-target="#edit-corporate-kpi-year" data-title="KPI Year" title="Add new KPI Year" tabindex="-1">
                                    <i class="ti ti-plus"></i>
                                </button>
                            </div>
                            <input type="hidden" name="sub_corporate_kpi" id="edit-sub-corporate-kpi" value="{{ $isKpiChecked ? $selectedKpiYear : '' }}">
                        </div>
                    </div>

                    <!-- SUBSECTION: DELIVERY & PROVIDER -->
                    <div class="subsection-header p-2 px-3 rounded-2 mb-3 bg-light-subtle border-start border-primary border-3">
                        <span class="fw-bold text-primary small text-uppercase">Delivery &amp; Provider</span>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6">
                            <label for="edit-delivery-method" class="form-label small fw-semibold">Delivery Method</label>
                            <div class="input-group">
                                <select class="form-select rounded-start-2 select2-master-option" id="edit-delivery-method" name="delivery_method" data-type="delivery_method" data-placeholder="Select Delivery Method">
                                    <option value="">Select Delivery Method</option>
                                    @php
                                        $currentDm = old('delivery_method', $training->delivery_method);
                                        $dmOpts = collect($options['delivery_method'] ?? [])->pluck('value')->all();
                                        if ($currentDm && !in_array($currentDm, $dmOpts, true)) {
                                            $dmOpts[] = $currentDm;
                                        }
                                    @endphp
                                    @foreach ($dmOpts as $dm)
                                        <option value="{{ $dm }}" {{ $currentDm === $dm ? 'selected' : '' }}>{{ $dm }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-secondary btn-add-option-modal" data-type="delivery_method" data-target="#edit-delivery-method" data-title="Delivery Method" title="Add new Delivery Method" tabindex="-1">
                                    <i class="ti ti-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="edit-sub-delivery-method" class="form-label small fw-semibold">Sub Delivery Method</label>
                            <div class="input-group">
                                <input type="text" class="form-control rounded-start-2" id="edit-sub-delivery-method" name="sub_delivery_method" value="{{ old('sub_delivery_method', $training->sub_delivery_method) }}" placeholder="e.g. Hands-on Lab" list="edit-sub-delivery-method-datalist" autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary btn-add-option-modal" data-type="sub_delivery_method" data-target="#edit-sub-delivery-method" data-datalist="#edit-sub-delivery-method-datalist" data-title="Sub Delivery Method" title="Add new Sub Delivery Method" tabindex="-1">
                                    <i class="ti ti-plus"></i>
                                </button>
                            </div>
                            <datalist id="edit-sub-delivery-method-datalist">
                                @foreach ($options['sub_delivery_method'] ?? [] as $opt)
                                    <option value="{{ $opt->value }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="edit-provider" class="form-label small fw-semibold">Provider</label>
                            <div class="input-group">
                                <select class="form-select rounded-start-2 select2-master-option" id="edit-provider" name="provider" data-type="provider" data-placeholder="Select Provider">
                                    <option value="">Select Provider</option>
                                    @php
                                        $currentProv = old('provider', $training->provider);
                                        $provOpts = collect($options['provider'] ?? [])->pluck('value')->all();
                                        if ($currentProv && !in_array($currentProv, $provOpts, true)) {
                                            $provOpts[] = $currentProv;
                                        }
                                    @endphp
                                    @foreach ($provOpts as $prov)
                                        <option value="{{ $prov }}" {{ $currentProv === $prov ? 'selected' : '' }}>{{ $prov }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-secondary btn-add-option-modal" data-type="provider" data-target="#edit-provider" data-title="Provider" title="Add new Provider" tabindex="-1">
                                    <i class="ti ti-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="edit-sub-provider" class="form-label small fw-semibold">Sub Provider</label>
                            <div class="input-group">
                                <select class="form-select rounded-start-2 select2-master-option" id="edit-sub-provider" name="sub_provider" data-type="sub_provider" data-placeholder="Select Sub Provider">
                                    <option value="">Select Sub Provider</option>
                                    @php
                                        $currentSubProv = old('sub_provider', $training->sub_provider);
                                        $subProvOpts = collect($options['sub_provider'] ?? [])->pluck('value')->all();
                                        if ($currentSubProv && !in_array($currentSubProv, $subProvOpts, true)) {
                                            $subProvOpts[] = $currentSubProv;
                                        }
                                    @endphp
                                    @foreach ($subProvOpts as $sp)
                                        <option value="{{ $sp }}" {{ $currentSubProv === $sp ? 'selected' : '' }}>{{ $sp }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-secondary btn-add-option-modal" data-type="sub_provider" data-target="#edit-sub-provider" data-title="Sub Provider" title="Add new Sub Provider" tabindex="-1">
                                    <i class="ti ti-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="edit-provider-name" class="form-label small fw-semibold">Provider Name</label>
                            <div class="input-group">
                                <input type="text" class="form-control rounded-start-2" id="edit-provider-name" name="provider_name" value="{{ old('provider_name', $training->provider_name) }}" placeholder="e.g. PT Schneider Indonesia" list="edit-provider-name-datalist" autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary btn-add-option-modal" data-type="provider_name" data-target="#edit-provider-name" data-datalist="#edit-provider-name-datalist" data-title="Provider Name" title="Add new Provider Name" tabindex="-1">
                                    <i class="ti ti-plus"></i>
                                </button>
                            </div>
                            <datalist id="edit-provider-name-datalist">
                                @foreach ($options['provider_name'] ?? [] as $opt)
                                    <option value="{{ $opt->value }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                    </div>

                    <!-- SUBSECTION: SCHEDULE & DURATION -->
                    <div class="subsection-header p-2 px-3 rounded-2 mb-3 bg-light-subtle border-start border-primary border-3">
                        <span class="fw-bold text-primary small text-uppercase">Schedule &amp; Duration</span>
                    </div>

                    @php
                        $durationFormatted = '';
                        if ($training->duration_seconds) {
                            $durHours = floor($training->duration_seconds / 3600);
                            $durMins = floor(($training->duration_seconds % 3600) / 60);
                            $durSecs = $training->duration_seconds % 60;
                            $durationFormatted = sprintf('%02d:%02d:%02d', $durHours, $durMins, $durSecs);
                        }
                        $startDateFormatted = $training->start_date ? (is_string($training->start_date) ? substr($training->start_date, 0, 10) : $training->start_date->format('Y-m-d')) : '';
                        $finishDateFormatted = $training->finish_date ? (is_string($training->finish_date) ? substr($training->finish_date, 0, 10) : $training->finish_date->format('Y-m-d')) : '';
                        $monthFormatted = $startDateFormatted ? date('M Y', strtotime($startDateFormatted)) : '';
                    @endphp

                    <div class="row g-3">
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="edit-duration" class="form-label small fw-semibold">Duration (HH:MM:SS)</label>
                            <input type="text" class="form-control rounded-2" id="edit-duration" name="duration" value="{{ old('duration', $durationFormatted) }}" placeholder="08:00:00">
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="edit-course-learning-hours" class="form-label small fw-semibold">Course Hours</label>
                            <input type="number" step="0.1" min="0" class="form-control rounded-2" id="edit-course-learning-hours" name="learning_hours" value="{{ old('learning_hours', $training->learning_hours) }}" placeholder="8.0">
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="edit-start-date" class="form-label small fw-semibold">Start Date</label>
                            <input type="date" class="form-control rounded-2 @error('start_date') is-invalid @enderror" id="edit-start-date" name="start_date" value="{{ old('start_date', $startDateFormatted) }}">
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="edit-finish-date" class="form-label small fw-semibold">Finish Date</label>
                            <input type="date" class="form-control rounded-2 @error('finish_date') is-invalid @enderror" id="edit-finish-date" name="finish_date" value="{{ old('finish_date', $finishDateFormatted) }}">
                            @error('finish_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="edit-month" class="form-label small fw-semibold">Month</label>
                            <input type="text" class="form-control rounded-2 bg-light text-muted" id="edit-month" readonly placeholder="Auto from Start Date" value="{{ $monthFormatted }}">
                        </div>
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="edit-venue" class="form-label small fw-semibold">Venue</label>
                            <div class="input-group">
                                <input type="text" class="form-control rounded-start-2" id="edit-venue" name="venue" value="{{ old('venue', $training->venue) }}" placeholder="e.g. Auditorium Hall B" list="edit-venue-datalist" autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary btn-add-option-modal" data-type="venue" data-target="#edit-venue" data-datalist="#edit-venue-datalist" data-title="Venue" title="Add new Venue" tabindex="-1">
                                    <i class="ti ti-plus"></i>
                                </button>
                            </div>
                            <datalist id="edit-venue-datalist">
                                @foreach ($options['venue'] ?? [] as $opt)
                                    <option value="{{ $opt->value }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="edit-instructor" class="form-label small fw-semibold">Instructor</label>
                            <div class="input-group">
                                <input type="text" class="form-control rounded-start-2" id="edit-instructor" name="instructor" value="{{ old('instructor', $training->instructor) }}" placeholder="e.g. Ir. Gunawan, M.T." list="edit-instructor-datalist" autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary btn-add-option-modal" data-type="instructor" data-target="#edit-instructor" data-datalist="#edit-instructor-datalist" data-title="Instructor" title="Add new Instructor" tabindex="-1">
                                    <i class="ti ti-plus"></i>
                                </button>
                            </div>
                            <datalist id="edit-instructor-datalist">
                                @foreach ($options['instructor'] ?? [] as $opt)
                                    <option value="{{ $opt->value }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================= -->
            <!-- SECTION 2: PARTICIPANT INFORMATION -->
            <!-- ============================================================= -->
            <div class="card border-0 shadow-sm rounded-3 mb-4 form-section" id="section-2-participant-info">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                        <span class="badge rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.85rem;">2</span>
                        <h4 class="fw-bold mb-0 text-dark">Participant Information</h4>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="edit-emp-id" class="form-label small fw-semibold">Employee ID / NIP <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-2 @error('employee_id') is-invalid @enderror" id="edit-emp-id" name="employee_id" value="{{ old('employee_id', $record->employee_id) }}" placeholder="e.g. 100234" required>
                            @error('employee_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-sm-6 col-md-5">
                            <label for="edit-emp-name" class="form-label small fw-semibold">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-2 @error('name') is-invalid @enderror" id="edit-emp-name" name="name" value="{{ old('name', $record->name) }}" placeholder="Full Name" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="edit-emp-grade" class="form-label small fw-semibold">Grade</label>
                            <input type="text" class="form-control rounded-2" id="edit-emp-grade" name="grade" value="{{ old('grade', $record->grade) }}" placeholder="e.g. 4A">
                        </div>
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="edit-emp-position" class="form-label small fw-semibold">Job Position</label>
                            <input type="text" class="form-control rounded-2" id="edit-emp-position" name="job_position" value="{{ old('job_position', $record->job_position) }}" placeholder="Job Title">
                        </div>
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="edit-emp-team" class="form-label small fw-semibold">Team</label>
                            <input type="text" class="form-control rounded-2" id="edit-emp-team" name="team" value="{{ old('team', $record->team) }}" placeholder="e.g. Rolling Mill 1">
                        </div>
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="edit-emp-dept" class="form-label small fw-semibold">Department</label>
                            <input type="text" class="form-control rounded-2" id="edit-emp-dept" name="department" value="{{ old('department', $record->department) }}" placeholder="e.g. Maintenance Dept">
                        </div>
                        <div class="col-12">
                            <label for="edit-emp-company" class="form-label small fw-semibold">Company</label>
                            <input type="text" class="form-control rounded-2" id="edit-emp-company" name="company" value="{{ old('company', $record->company) }}" placeholder="Company Entity">
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================= -->
            <!-- SECTION 3: LEARNING RESULTS -->
            <!-- ============================================================= -->
            <div class="card border-0 shadow-sm rounded-3 mb-4 form-section" id="section-3-results">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                        <span class="badge rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.85rem;">3</span>
                        <h4 class="fw-bold mb-0 text-dark">Learning Results</h4>
                    </div>

                    <!-- SUBSECTION: PROGRESS & EVALUATION -->
                    <div class="subsection-header p-2 px-3 rounded-2 mb-3 bg-light-subtle border-start border-primary border-3 d-flex align-items-center justify-content-between">
                        <span class="fw-bold text-primary small text-uppercase">Learning Progress &amp; Assessment</span>
                        @php $progressVal = (float) old('progress', $record->progress ?? 0); @endphp
                        <span class="badge {{ $progressVal >= 100 ? 'bg-success-lt text-success' : ($progressVal > 0 ? 'bg-warning-lt text-warning' : 'bg-secondary-lt') }} fw-bold" id="edit-status-badge">
                            {{ $progressVal >= 100 ? 'FINISHED' : ($progressVal > 0 ? 'ONGOING (' . round($progressVal) . '%)' : 'NOT STARTED') }}
                        </span>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="edit-progress" class="form-label small fw-semibold">Progress (%) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="1" min="0" max="100" class="form-control rounded-start @error('progress') is-invalid @enderror" id="edit-progress" name="progress" value="{{ old('progress', round($progressVal)) }}" required>
                                <span class="input-group-text">%</span>
                                @error('progress')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="edit-participant-learning-hours" class="form-label small fw-semibold">Participant Hours</label>
                            <input type="number" step="0.1" min="0" class="form-control rounded-2" id="edit-participant-learning-hours" name="participant_learning_hours" value="{{ old('participant_learning_hours', $record->learning_hours) }}" placeholder="e.g. 8.0">
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="edit-assessment-score" class="form-label small fw-semibold">Assessment Score</label>
                            <input type="number" step="0.1" min="0" max="100" class="form-control rounded-2 @error('assessment_score') is-invalid @enderror" id="edit-assessment-score" name="assessment_score" value="{{ old('assessment_score', $record->assessment_score) }}" placeholder="e.g. 85.0">
                            @error('assessment_score')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="edit-certificate" class="form-label small fw-semibold">Certificate</label>
                            <input type="text" class="form-control rounded-2" id="edit-certificate" name="certificate" value="{{ old('certificate', $record->certificate) }}" placeholder="e.g. Yes / Certificate No.">
                        </div>
                    </div>

                    <!-- SUBSECTION: ATTENDANCE -->
                    <div class="subsection-header p-2 px-3 rounded-2 mb-3 bg-light-subtle border-start border-primary border-3">
                        <span class="fw-bold text-primary small text-uppercase">Attendance</span>
                    </div>

                    <div class="row g-3 mb-4 align-items-center">
                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold d-block">Attendance <span class="text-danger">*</span></label>
                            <div class="d-flex align-items-center gap-3 pt-1">
                                <div class="form-check form-check-inline mb-0">
                                    <input class="form-check-input" type="radio" name="attendance" id="edit-attendance-yes" value="1" {{ old('attendance', $record->attendance !== false) ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="edit-attendance-yes">Yes (Present)</label>
                                </div>
                                <div class="form-check form-check-inline mb-0">
                                    <input class="form-check-input" type="radio" name="attendance" id="edit-attendance-no" value="0" {{ old('attendance', $record->attendance) === false || old('attendance') === '0' ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="edit-attendance-no">No (Absent)</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-8" id="edit-reason-container" style="{{ (old('attendance', $record->attendance) === false || old('attendance') === '0') ? '' : 'display: none;' }}">
                            <label for="edit-attendance-reason" class="form-label small fw-semibold">Reason (if absent)</label>
                            <input type="text" class="form-control rounded-2" id="edit-attendance-reason" name="attendance_reason" value="{{ old('attendance_reason', $record->attendance_reason) }}" placeholder="Attendance justification...">
                        </div>
                    </div>

                    <!-- SUBSECTION: OBLIGATION -->
                    <div class="subsection-header p-2 px-3 rounded-2 mb-3 bg-light-subtle border-start border-primary border-3">
                        <span class="fw-bold text-primary small text-uppercase">Obligation (Service Bond)</span>
                    </div>

                    @php
                        $isBondYes = in_array(strtolower((string) old('bond_status', $record->bond_status)), ['yes', '1', 'true'], true);
                        $bondEndFormatted = $record->bond_end_date ? (is_string($record->bond_end_date) ? substr($record->bond_end_date, 0, 10) : $record->bond_end_date->format('Y-m-d')) : '';
                    @endphp

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="edit-bond-status" class="form-label small fw-semibold">Bond Status</label>
                            <select class="form-select rounded-2" id="edit-bond-status" name="bond_status">
                                <option value="No" {{ !$isBondYes ? 'selected' : '' }}>No</option>
                                <option value="Yes" {{ $isBondYes ? 'selected' : '' }}>Yes</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="edit-bond-end-date" class="form-label small fw-semibold">Bond End Date</label>
                            <input type="date" class="form-control rounded-2" id="edit-bond-end-date" name="bond_end_date" value="{{ old('bond_end_date', $bondEndFormatted) }}" {{ !$isBondYes ? 'disabled' : '' }}>
                        </div>
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="edit-bond-amount" class="form-label small fw-semibold">Bond Amount (IDR)</label>
                            <input type="number" step="1000" min="0" class="form-control rounded-2" id="edit-bond-amount" name="bond_amount" value="{{ old('bond_amount', $record->bond_amount ? round($record->bond_amount) : '') }}" placeholder="e.g. 5000000" {{ !$isBondYes ? 'disabled' : '' }}>
                        </div>
                    </div>

                    <!-- SUBSECTION: EVALUATION (L1, L2, L3) -->
                    <div class="subsection-header p-2 px-3 rounded-2 mb-3 bg-light-subtle border-start border-primary border-3">
                        <span class="fw-bold text-primary small text-uppercase">Kirkpatrick Evaluation (L1 - L3)</span>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label for="edit-l1-program" class="form-label extra-small fw-bold text-muted text-uppercase mb-1">L1 &bull; Program</label>
                            <input type="text" class="form-control form-control-sm rounded-2" id="edit-l1-program" name="l1_program" value="{{ old('l1_program', $record->l1_program) }}" placeholder="e.g. 4.8 / Very Good">
                        </div>
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="edit-l2-numerical" class="form-label extra-small fw-bold text-muted text-uppercase mb-1">L2 (Post Test) &bull; Numerical</label>
                            <input type="number" step="0.1" min="0" max="100" class="form-control form-control-sm rounded-2" id="edit-l2-numerical" name="l2_numerical" value="{{ old('l2_numerical', $record->l2_numerical) }}" placeholder="Score (0-100)">
                        </div>
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="edit-l2-verbal" class="form-label extra-small fw-bold text-muted text-uppercase mb-1">L2 (Post Test) &bull; Verbal</label>
                            <input type="text" class="form-control form-control-sm rounded-2" id="edit-l2-verbal" name="l2_verbal" value="{{ old('l2_verbal', $record->l2_verbal) }}" placeholder="e.g. Passed">
                        </div>
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="edit-l3-numerical" class="form-label extra-small fw-bold text-muted text-uppercase mb-1">L3 (Project) &bull; Numerical</label>
                            <input type="number" step="0.1" min="0" max="100" class="form-control form-control-sm rounded-2" id="edit-l3-numerical" name="l3_numerical" value="{{ old('l3_numerical', $record->l3_numerical) }}" placeholder="Score (0-100)">
                        </div>
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="edit-l3-verbal" class="form-label extra-small fw-bold text-muted text-uppercase mb-1">L3 (Project) &bull; Verbal</label>
                            <input type="text" class="form-control form-control-sm rounded-2" id="edit-l3-verbal" name="l3_verbal" value="{{ old('l3_verbal', $record->l3_verbal) }}" placeholder="e.g. Implemented in Plant">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="edit-pic" class="form-label extra-small fw-bold text-muted text-uppercase mb-1">L3 (Project) &bull; PIC</label>
                            <input type="text" class="form-control form-control-sm rounded-2" id="edit-pic" name="pic" value="{{ old('pic', $record->pic) }}" placeholder="e.g. HRDC Team">
                        </div>
                    </div>
                </div>
            </div>

            <!-- BOTTOM ACTION AREA -->
            <div class="card border-0 shadow-sm rounded-3 p-3 mb-4 bg-white sticky-bottom-actions">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="text-danger small fw-semibold" id="edit-form-alert" style="display: none;"></div>
                    <div class="d-flex align-items-center gap-2 ms-auto">
                        <a href="{{ route('manage.record.offline.classification', $classification['slug']) }}" class="btn btn-outline-secondary px-3" id="btn-cancel-edit">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 d-inline-flex align-items-center gap-1.5" id="btn-submit-edit" style="background-color: #d97706; border-color: #d97706;">
                            <i class="ti ti-check fs-2"></i>
                            <span>Save Changes</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>

        {{-- MASTER OPTION [+] MODAL --}}
        @include('main.record.components.offline-modal-add-option')
    </div>
</div>
@endsection

@section('style')
<style>
    .manage-record-edit-page {
        background: #f8fafc;
        min-height: 100vh;
    }

    [data-bs-theme="dark"] .manage-record-edit-page,
    body.dark-mode .manage-record-edit-page,
    body.theme-dark .manage-record-edit-page {
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
        border-left: 4px solid #d97706 !important;
    }

    /* SELECT2 IN INPUT GROUP & [+] BUTTON FIX */
    .input-group > .select2-container--bootstrap-5,
    .input-group > .select2-container {
        flex: 1 1 auto;
        width: 1% !important;
    }
    .input-group > .select2-container--bootstrap-5 .select2-selection,
    .input-group > .select2-container .select2-selection {
        border-top-right-radius: 0 !important;
        border-bottom-right-radius: 0 !important;
    }
    .btn-add-option-modal {
        border-color: #d1d5db;
        color: #4b5563;
        background-color: #f9fafb;
        transition: all 0.15s ease-in-out;
    }
    .btn-add-option-modal:hover {
        background-color: #0c5b9e;
        border-color: #0c5b9e;
        color: #ffffff;
    }

    /* MASTER SEARCHABLE COMBOBOX */
    .master-combobox {
        position: relative;
    }
    .master-combobox .dropdown-menu {
        max-height: 250px;
        overflow-y: auto;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
        border-radius: 0.375rem;
        z-index: 1060;
    }
    .master-combobox .dropdown-item {
        padding: 0.5rem 0.75rem;
        font-size: 0.85rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .master-combobox .dropdown-item:hover,
    .master-combobox .dropdown-item:focus {
        background-color: #f0f7ff;
        color: #0c5b9e;
    }
    [data-bs-theme="dark"] .master-combobox .dropdown-menu,
    body.dark-mode .master-combobox .dropdown-menu {
        background-color: #1e293b;
        border-color: #334155;
        color: #f8fafc;
    }
    [data-bs-theme="dark"] .master-combobox .dropdown-item:hover,
    body.dark-mode .master-combobox .dropdown-item:hover {
        background-color: #334155;
        color: #60a5fa;
    }

    /* DARK MODE OVERRIDES */
    [data-bs-theme="dark"] .record-section-header,
    body.dark-mode .record-section-header,
    body.theme-dark .record-section-header,
    [data-bs-theme="dark"] .form-section,
    body.dark-mode .form-section,
    body.theme-dark .form-section,
    [data-bs-theme="dark"] .sticky-bottom-actions,
    body.dark-mode .sticky-bottom-actions,
    body.theme-dark .sticky-bottom-actions {
        background-color: var(--tblr-bg-surface, #1f2937) !important;
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .text-dark,
    body.dark-mode .text-dark,
    body.theme-dark .text-dark {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .bg-light,
    body.dark-mode .bg-light,
    body.theme-dark .bg-light,
    [data-bs-theme="dark"] .bg-light-subtle,
    body.dark-mode .bg-light-subtle,
    body.theme-dark .bg-light-subtle {
        background-color: #111827 !important;
    }
</style>
@endsection

@section('script')
<script>
    $(document).ready(function () {
        // --- Helper: Escape HTML ---
        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            return String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // --- Select2 Initialization for Master Option Dropdowns ---
        if ($.fn.select2) {
            $('.select2-master-option').each(function () {
                const placeholder = $(this).data('placeholder') || 'Select option';
                $(this).select2({
                    theme: 'bootstrap-5',
                    placeholder: placeholder,
                    allowClear: true,
                    width: 'resolve'
                });
            });
        }

        // --- Course Name Master Options & Searchable Combobox ---
        let masterCourseOptions = {!! json_encode(collect($options['course_name'] ?? [])->pluck('value')->values()->all()) !!};

        function initCourseNameCombobox() {
            const $combobox = $('.master-combobox[data-type="course_name"]');
            if (!$combobox.length) return;

            const $input = $combobox.find('.master-combobox-input');
            const $toggle = $combobox.find('.master-combobox-toggle');
            const $menu = $combobox.find('.master-combobox-menu');

            function renderOptions(query) {
                const q = (query || '').toLowerCase().trim();
                const filtered = masterCourseOptions.filter(item => {
                    return !q || item.toLowerCase().includes(q);
                });

                $menu.empty();

                if (filtered.length > 0) {
                    filtered.forEach(item => {
                        const $a = $(`
                            <a href="#" class="dropdown-item d-flex align-items-center justify-content-between py-2 combobox-item" data-value="${escapeHtml(item)}">
                                <span class="text-truncate" style="max-width: 85%;">${escapeHtml(item)}</span>
                                <span class="badge bg-light text-muted border extra-small">master</span>
                            </a>
                        `);
                        $menu.append($a);
                    });
                } else if (!q) {
                    $menu.append('<div class="p-2 text-muted small text-center">No master courses found. Type to create new.</div>');
                }

                if (q) {
                    const exactMatch = masterCourseOptions.some(item => item.toLowerCase() === q);
                    if (!exactMatch) {
                        $menu.append(`
                            <div class="p-2 border-top bg-light-subtle extra-small text-muted">
                                <i class="ti ti-sparkles text-primary me-1"></i>New course will be registered: <strong>"${escapeHtml(query.trim())}"</strong>
                            </div>
                        `);
                    }
                }
            }

            $input.on('focus input', function () {
                renderOptions($(this).val());
                $menu.show();
            });

            $toggle.on('click', function (e) {
                e.stopPropagation();
                if ($menu.is(':visible')) {
                    $menu.hide();
                } else {
                    renderOptions($input.val());
                    $menu.show();
                    $input.focus();
                }
            });

            $menu.on('click', '.combobox-item', function (e) {
                e.preventDefault();
                const val = $(this).data('value');
                $input.val(val).trigger('input').trigger('change');
                $menu.hide();
            });

            $(document).on('click', function (e) {
                if (!$(e.target).closest($combobox).length) {
                    $menu.hide();
                }
            });

            $input.on('keydown', function (e) {
                if (e.key === 'Escape') {
                    $menu.hide();
                }
            });
        }

        initCourseNameCombobox();

        // --- Corporate KPI Toggle & Logic ---
        function syncEditKpiUi() {
            const isKpi = $('input[name="counted_corporate_kpi"]:checked').val() === '1';
            const $container = $('#edit-container-kpi-year');
            const $yearSelect = $('#edit-corporate-kpi-year');
            const $subKpiHidden = $('#edit-sub-corporate-kpi');

            if (isKpi) {
                $container.show();
                $yearSelect.prop('disabled', false);
                $subKpiHidden.val($yearSelect.val());
            } else {
                $container.hide();
                $yearSelect.prop('disabled', true);
                $subKpiHidden.val('');
            }
        }

        $('input[name="counted_corporate_kpi"]').on('change', function () {
            syncEditKpiUi();
        });

        $('#edit-corporate-kpi-year').on('change', function () {
            $('#edit-sub-corporate-kpi').val($(this).val());
        });

        syncEditKpiUi();

        // --- Helper: Get Current KPI Year from Start Date or fallback ---
        function getCurrentKpiYear() {
            const startVal = $('#edit-start-date').val();
            if (startVal && startVal.length >= 4) {
                const y = parseInt(startVal.substring(0, 4), 10);
                if (!isNaN(y) && y >= 1970 && y <= 2100) {
                    return y;
                }
            }
            const selectedYr = $('#edit-corporate-kpi-year').val();
            return selectedYr ? parseInt(selectedYr, 10) : {{ $kpiYear ?? date('Y') }};
        }

        // --- Helper: Update Dynamic Yearly Corporate KPI Options ---
        function updateKpiYearDisplay() {
            const kpiYear = getCurrentKpiYear();
            const $yearSelect = $('#edit-corporate-kpi-year');
            if ($yearSelect.length) {
                let hasYear = false;
                $yearSelect.find('option').each(function () {
                    if (parseInt($(this).val(), 10) === kpiYear) {
                        hasYear = true;
                    }
                });
                if (!hasYear && kpiYear >= 1970 && kpiYear <= 2100) {
                    $yearSelect.prepend(new Option(kpiYear, kpiYear, false, false));
                }
            }
            $('.btn-add-option-modal[data-type="sub_corporate_kpi"]').data('year', kpiYear);
        }

        // Month auto-derive and dynamic KPI year from start date
        $('#edit-start-date').on('change', function () {
            const val = $(this).val();
            if (val) {
                const date = new Date(val);
                if (!isNaN(date.getTime())) {
                    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    $('#edit-month').val(`${monthNames[date.getMonth()]} ${date.getFullYear()}`);
                }
            } else {
                $('#edit-month').val('');
            }

            const $finishDate = $('#edit-finish-date');
            if (val && (!$finishDate.val() || $finishDate.val() < val)) {
                $finishDate.val(val);
            }
            updateKpiYearDisplay();
        });

        // --- MASTER OPTION [+] MODAL LOGIC ---
        $(document).on('click', '.btn-add-option-modal', function (e) {
            e.preventDefault();
            const type = $(this).data('type');
            const title = $(this).data('title') || type;
            const targetSelect = $(this).data('target');
            const targetDatalist = $(this).data('datalist');

            $('#modal-option-type').val(type);
            $('#modal-option-target-select').val(targetSelect || '');
            $('#modal-option-target-datalist').val(targetDatalist || '');
            $('#modal-option-value').val('');
            $('#modal-option-alert').hide().empty();
            $('#modal-option-info').hide().empty();
            $('#modal-add-option-title').text(`Add New ${title}`);
            $('#modal-option-value-label').text(`${title} Value`);

            if (type === 'sub_corporate_kpi') {
                const kpiYear = getCurrentKpiYear();
                $('#modal-option-year').val(kpiYear);
                $('#modal-option-year-display').val(kpiYear);
                $('#modal-option-year-container').show();
            } else {
                $('#modal-option-year').val('');
                $('#modal-option-year-container').hide();
            }

            const modalEl = document.getElementById('modal-add-master-option');
            if (modalEl) {
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
                setTimeout(function () {
                    $('#modal-option-value').focus();
                }, 350);
            }
        });

        function submitMasterOption() {
            const type = $('#modal-option-type').val();
            const value = $('#modal-option-value').val().trim();
            const year = $('#modal-option-year').val();
            const targetSelect = $('#modal-option-target-select').val();
            const targetDatalist = $('#modal-option-target-datalist').val();

            $('#modal-option-alert').hide().empty();
            $('#modal-option-info').hide().empty();

            if (!value) {
                $('#modal-option-alert').text('Option value is required.').show();
                $('#modal-option-value').focus();
                return;
            }

            $('#btn-save-master-option').prop('disabled', true);
            $('#spinner-save-option').show();
            $('#icon-save-option').hide();

            $.ajax({
                url: "{{ route('manage.record.offline.options.store') }}",
                type: 'POST',
                data: JSON.stringify({
                    option_type: type,
                    value: value,
                    year: year ? parseInt(year, 10) : null
                }),
                contentType: 'application/json',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                success: function (res) {
                    $('#btn-save-master-option').prop('disabled', false);
                    $('#spinner-save-option').hide();
                    $('#icon-save-option').show();

                    const optVal = (res.data && res.data.value) ? res.data.value : value;

                    // If target is a <select>
                    if (targetSelect && $(targetSelect).is('select')) {
                        const $sel = $(targetSelect);
                        let optExists = false;
                        $sel.find('option').each(function () {
                            if ($(this).val().toLowerCase() === optVal.toLowerCase()) {
                                optExists = true;
                                $(this).val(optVal).text(optVal);
                            }
                        });
                        if (!optExists) {
                            $sel.append(new Option(optVal, optVal, true, true));
                        } else {
                            $sel.val(optVal);
                        }
                        $sel.trigger('change');
                    } else if (targetSelect && $(targetSelect).is('input')) {
                        // If target is an <input>
                        const $inp = $(targetSelect);
                        $inp.val(optVal).trigger('input').trigger('change');
                    }

                    if (type === 'course_name') {
                        if (!masterCourseOptions.includes(optVal)) {
                            masterCourseOptions.push(optVal);
                            masterCourseOptions.sort();
                        }
                        $('#edit-course-name').val(optVal).trigger('input').trigger('change');
                    }

                    if (type === 'sub_corporate_kpi') {
                        const $kpiSelect = $('#edit-corporate-kpi-year');
                        if ($kpiSelect.length) {
                            let kpiOptExists = false;
                            $kpiSelect.find('option').each(function () {
                                if ($(this).val() === optVal) {
                                    kpiOptExists = true;
                                }
                            });
                            if (!kpiOptExists) {
                                $kpiSelect.prepend(new Option(optVal, optVal, true, true));
                            } else {
                                $kpiSelect.val(optVal);
                            }
                            $('#edit-counted-kpi-yes').prop('checked', true).trigger('change');
                            $kpiSelect.trigger('change');
                        }
                    }

                    // If target has an associated <datalist>
                    if (targetDatalist && $(targetDatalist).length) {
                        const $dl = $(targetDatalist);
                        let existsInDl = false;
                        $dl.find('option').each(function () {
                            if ($(this).val().toLowerCase() === optVal.toLowerCase()) {
                                existsInDl = true;
                            }
                        });
                        if (!existsInDl) {
                            $dl.append(`<option value="${escapeHtml(optVal)}"></option>`);
                        }
                    }

                    // Hide modal
                    const modalEl = document.getElementById('modal-add-master-option');
                    if (modalEl) {
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) {
                            modal.hide();
                        }
                    }
                },
                error: function (xhr) {
                    $('#btn-save-master-option').prop('disabled', false);
                    $('#spinner-save-option').hide();
                    $('#icon-save-option').show();

                    let errMsg = 'Failed to create option.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errMsg = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        const errs = Object.values(xhr.responseJSON.errors).flat();
                        errMsg = errs.join(' ');
                    }
                    $('#modal-option-alert').text(errMsg).show();
                }
            });
        }

        $('#btn-save-master-option').on('click', function () {
            submitMasterOption();
        });

        $('#modal-option-value').on('keypress', function (e) {
            if (e.which === 13) {
                e.preventDefault();
                submitMasterOption();
            }
        });

        // Attendance toggle reason
        $('input[name="attendance"]').on('change', function () {
            if ($('#edit-attendance-no').is(':checked')) {
                $('#edit-reason-container').slideDown(150);
            } else {
                $('#edit-reason-container').slideUp(150);
            }
        });

        // Bond status toggle
        $('#edit-bond-status').on('change', function () {
            const isYes = $(this).val() === 'Yes';
            $('#edit-bond-end-date, #edit-bond-amount').prop('disabled', !isYes);
            if (!isYes) {
                $('#edit-bond-end-date').val('');
                $('#edit-bond-amount').val('');
            }
        });

        // Dynamic progress status badge
        $('#edit-progress').on('input', function () {
            const val = parseFloat($(this).val()) || 0;
            const $badge = $('#edit-status-badge');
            if (val >= 100) {
                $badge.removeClass('bg-warning-lt text-warning bg-secondary-lt text-secondary')
                      .addClass('bg-success-lt text-success')
                      .text('FINISHED');
            } else if (val > 0) {
                $badge.removeClass('bg-success-lt text-success bg-secondary-lt text-secondary')
                      .addClass('bg-warning-lt text-warning')
                      .text(`ONGOING (${Math.round(val)}%)`);
            } else {
                $badge.removeClass('bg-success-lt text-success bg-warning-lt text-warning')
                      .addClass('bg-secondary-lt text-secondary')
                      .text('NOT STARTED');
            }
        });

        // AJAX submit with error feedback
        $('#offline-record-edit-form').on('submit', function (e) {
            e.preventDefault();
            const $btn = $('#btn-submit-edit');
            const $alert = $('#edit-form-alert');
            const originalBtnHtml = $btn.html();

            // Clear previous errors
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();
            $alert.hide().empty();

            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...');

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                },
                success: function (res) {
                    if (res && res.success) {
                        window.location.href = res.redirect_url;
                    } else {
                        $btn.prop('disabled', false).html(originalBtnHtml);
                        $alert.text(res.message || 'Failed to update record.').show();
                    }
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).html(originalBtnHtml);

                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        const errors = xhr.responseJSON.errors;
                        let firstField = null;

                        for (const field in errors) {
                            const input = $(`[name="${field}"]`);
                            if (input.length) {
                                input.addClass('is-invalid');
                                input.after(`<div class="invalid-feedback">${errors[field][0]}</div>`);
                                if (!firstField) firstField = input;
                            }
                        }

                        if (firstField) {
                            $('html, body').animate({
                                scrollTop: firstField.offset().top - 120
                            }, 200);
                        }

                        $alert.text(xhr.responseJSON.message || 'Please fix the highlighted validation errors.').show();
                    } else {
                        const errMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Server error while saving record.';
                        $alert.text(errMsg).show();
                    }
                }
            });
        });
    });
</script>
@endsection
