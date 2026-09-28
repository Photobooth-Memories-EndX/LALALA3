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

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-8">
                            <label for="edit-course-name" class="form-label small fw-semibold">Course Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-2 @error('course_name') is-invalid @enderror" id="edit-course-name" name="course_name" value="{{ old('course_name', $training->course_name) }}" placeholder="e.g. High Voltage Safety &amp; Operation" required>
                            @error('course_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="edit-batch" class="form-label small fw-semibold">Batch</label>
                            <input type="text" class="form-control rounded-2" id="edit-batch" name="batch" value="{{ old('batch', $training->batch) }}" placeholder="e.g. Batch 1">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="edit-sub-course-name" class="form-label small fw-semibold">Sub Course Name</label>
                            <input type="text" class="form-control rounded-2" id="edit-sub-course-name" name="sub_course_name" value="{{ old('sub_course_name', $training->sub_course_name) }}" placeholder="e.g. Switchgear Troubleshooting Module">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="edit-type" class="form-label small fw-semibold">Type</label>
                            <select class="form-select rounded-2" id="edit-type" name="type">
                                <option value="">Select Type</option>
                                @foreach ($trainingTypes as $type)
                                    <option value="{{ $type }}" {{ old('type', $training->type) === $type ? 'selected' : '' }}>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="edit-category" class="form-label small fw-semibold">Category</label>
                            <input type="text" class="form-control rounded-2" id="edit-category" name="category" value="{{ old('category', $training->category) }}" placeholder="e.g. Technical Competency" list="category-datalist">
                            <datalist id="category-datalist">
                                <option value="Technical Competency">
                                <option value="Leadership Program">
                                <option value="Quality &amp; Safety">
                                <option value="Management Skill">
                                <option value="General Training">
                            </datalist>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="edit-level" class="form-label small fw-semibold">Level</label>
                            <select class="form-select rounded-2" id="edit-level" name="level">
                                <option value="">Select Level</option>
                                <option value="Basic" {{ old('level', $training->level) === 'Basic' ? 'selected' : '' }}>Basic</option>
                                <option value="Intermediate" {{ old('level', $training->level) === 'Intermediate' ? 'selected' : '' }}>Intermediate</option>
                                <option value="Advanced" {{ old('level', $training->level) === 'Advanced' ? 'selected' : '' }}>Advanced</option>
                                <option value="All" {{ old('level', $training->level) === 'All' ? 'selected' : '' }}>All</option>
                            </select>
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
                            <input type="text" class="form-control rounded-2" id="edit-sub-classification" name="sub_classification" value="{{ old('sub_classification', $training->sub_classification) }}" placeholder="e.g. Plant Maintenance">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold d-block">Counted as '26 Corporate KPI</label>
                            <div class="d-flex align-items-center gap-3 pt-1">
                                <div class="form-check form-check-inline mb-0">
                                    <input class="form-check-input" type="radio" name="counted_corporate_kpi" id="edit-counted-kpi-yes" value="1" {{ old('counted_corporate_kpi', $training->counted_corporate_kpi) ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="edit-counted-kpi-yes">Yes</label>
                                </div>
                                <div class="form-check form-check-inline mb-0">
                                    <input class="form-check-input" type="radio" name="counted_corporate_kpi" id="edit-counted-kpi-no" value="0" {{ !old('counted_corporate_kpi', $training->counted_corporate_kpi) ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="edit-counted-kpi-no">No</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="edit-sub-corporate-kpi" class="form-label small fw-semibold">Sub '26 Corporate KPI</label>
                            <input type="text" class="form-control rounded-2" id="edit-sub-corporate-kpi" name="sub_corporate_kpi" value="{{ old('sub_corporate_kpi', $training->sub_corporate_kpi) }}" placeholder="e.g. Safety Incident Reduction">
                        </div>
                    </div>

                    <!-- SUBSECTION: DELIVERY & PROVIDER -->
                    <div class="subsection-header p-2 px-3 rounded-2 mb-3 bg-light-subtle border-start border-primary border-3">
                        <span class="fw-bold text-primary small text-uppercase">Delivery &amp; Provider</span>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6">
                            <label for="edit-delivery-method" class="form-label small fw-semibold">Delivery Method</label>
                            <select class="form-select rounded-2" id="edit-delivery-method" name="delivery_method">
                                <option value="">Select Delivery Method</option>
                                @foreach (['Classroom', 'Practical', 'Hybrid', 'Workshop', 'Hands-on', 'Seminar', 'E-Learning'] as $dm)
                                    <option value="{{ $dm }}" {{ old('delivery_method', $training->delivery_method) === $dm ? 'selected' : '' }}>{{ $dm }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="edit-sub-delivery-method" class="form-label small fw-semibold">Sub Delivery Method</label>
                            <input type="text" class="form-control rounded-2" id="edit-sub-delivery-method" name="sub_delivery_method" value="{{ old('sub_delivery_method', $training->sub_delivery_method) }}" placeholder="e.g. Hands-on Lab">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="edit-provider" class="form-label small fw-semibold">Provider</label>
                            <select class="form-select rounded-2" id="edit-provider" name="provider">
                                <option value="">Select Provider</option>
                                <option value="Internal" {{ old('provider', $training->provider) === 'Internal' ? 'selected' : '' }}>Internal</option>
                                <option value="External" {{ old('provider', $training->provider) === 'External' ? 'selected' : '' }}>External</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="edit-sub-provider" class="form-label small fw-semibold">Sub Provider</label>
                            <select class="form-select rounded-2" id="edit-sub-provider" name="sub_provider">
                                <option value="">Select Sub Provider</option>
                                @foreach (['Vendor', 'University', 'Certified Center', 'Internal Trainer', 'Other'] as $sp)
                                    <option value="{{ $sp }}" {{ old('sub_provider', $training->sub_provider) === $sp ? 'selected' : '' }}>{{ $sp }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="edit-provider-name" class="form-label small fw-semibold">Provider Name</label>
                            <input type="text" class="form-control rounded-2" id="edit-provider-name" name="provider_name" value="{{ old('provider_name', $training->provider_name) }}" placeholder="e.g. PT Schneider Indonesia">
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
                            <input type="text" class="form-control rounded-2" id="edit-venue" name="venue" value="{{ old('venue', $training->venue) }}" placeholder="e.g. Auditorium Hall B">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="edit-instructor" class="form-label small fw-semibold">Instructor</label>
                            <input type="text" class="form-control rounded-2" id="edit-instructor" name="instructor" value="{{ old('instructor', $training->instructor) }}" placeholder="e.g. Ir. Gunawan, M.T.">
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
        // Month auto-derive from start date
        $('#edit-start-date').on('change', function () {
            const val = $(this).val();
            if (val) {
                const date = new Date(val);
                if (!isNaN(date.getTime())) {
                    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    $('#edit-month').val(`${monthNames[date.getMonth()]} ${date.getFullYear()}`);
                    return;
                }
            }
            $('#edit-month').val('');
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
