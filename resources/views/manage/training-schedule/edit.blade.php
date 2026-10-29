@extends('layouts.sidebar-master')

@section('content')
<div class="page-body">
    <div class="container-fluid px-4 py-3">
        <!-- Back Navigation Link -->
        <a href="{{ url('/manage/training-schedule') }}" class="btn btn-link p-0 mb-3 text-decoration-none d-inline-flex align-items-center gap-1 fw-semibold text-primary">
            <i class="ti ti-arrow-left fs-2"></i>
            <span>Back</span>
        </a>

        <!-- Title Header Card -->
        <div class="card border border-slate-200 rounded-2xl p-4 p-md-5 mb-4 bg-white shadow-sm">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h3 class="fw-bold text-dark mb-1">Edit Training Schedule</h3>
                    <p class="text-muted small mb-0">Update corporate training details, syllabus structure, target audience, and participant capacity.</p>
                </div>
                <div>
                    <span class="badge bg-primary-lt text-primary px-3 py-2 rounded-pill fw-semibold">
                        Code: {{ $training->training_code ?? $training->batch ?? 'PTKP-TR-' . $training->id }}
                    </span>
                </div>
            </div>
        </div>

        @if (isset($errors) && $errors->any())
            <div class="alert alert-danger rounded-4 shadow-sm mb-4">
                <div class="fw-bold mb-1"><i class="ti ti-alert-circle me-1"></i> Please check the following errors:</div>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="editTrainingForm" action="{{ route('manage.training-schedule.update', $training->id) }}" method="POST" class="d-flex flex-column gap-4 col-12" novalidate>
            @csrf
            @method('PUT')

            <!-- 1. COURSE OVERVIEW & OBJECTIVES (WITH TITLE FIELD AT TOP) -->
            <div class="card form-section-card border border-slate-200 rounded-2xl p-6 bg-white shadow-sm transition-all duration-200 w-100 position-relative">
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <h4 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2" style="font-size: 1.25rem;">
                        <i class="ti ti-file-text text-primary fs-2"></i>
                        <span>Course Overview & Objectives</span>
                    </h4>
                    <span class="badge bg-blue-600 text-white rounded-pill px-3 py-1 fs-7 active-section-badge"><i class="bi bi-pencil ti ti-pencil me-1"></i> Active Section</span>
                </div>
                <div class="row g-3">
                    <!-- Training Title Placed Here at the Top -->
                    <div class="col-md-6 mb-2">
                        <label class="form-label fw-semibold text-dark">Training Title <span class="text-danger">*</span></label>
                        @php $curTitle = old('title', $training->title ?? $training->course_name); @endphp
                        <select name="title" id="select-training-title" class="form-select rounded-3 w-100" data-option-type="course_name" data-option-title="Training Title" required>
                            <option value="">Select Training Title...</option>
                            @foreach ($options['title'] ?? [] as $titleOpt)
                                <option value="{{ $titleOpt->value }}" {{ $curTitle == $titleOpt->value ? 'selected' : '' }}>
                                    {{ $titleOpt->value }}
                                </option>
                            @endforeach
                            @if ($curTitle && !collect($options['title'] ?? [])->pluck('value')->contains($curTitle))
                                <option value="{{ $curTitle }}" selected>{{ $curTitle }} (Current)</option>
                            @endif
                            <option value="__add_new__" class="fw-bold text-primary">+ Add New...</option>
                        </select>
                        <div class="invalid-feedback">Please provide or select a valid training title.</div>
                    </div>

                    <!-- Sub Course Name -->
                    <div class="col-md-6 mb-2">
                        <label class="form-label fw-semibold text-dark">Sub Course Name</label>
                        @php $curSubCourse = old('sub_course_name', $training->sub_course_name); @endphp
                        <select name="sub_course_name" id="select-sub-course-name" class="form-select rounded-3 w-100" data-option-type="sub_course_name" data-option-title="Sub Course Name">
                            <option value="">Select Sub Course Name (Optional)...</option>
                            @foreach ($options['sub_course_name'] ?? [] as $subCourseOpt)
                                <option value="{{ $subCourseOpt->value }}" {{ $curSubCourse == $subCourseOpt->value ? 'selected' : '' }}>
                                    {{ $subCourseOpt->value }}
                                </option>
                            @endforeach
                            @if ($curSubCourse && !collect($options['sub_course_name'] ?? [])->pluck('value')->contains($curSubCourse))
                                <option value="{{ $curSubCourse }}" selected>{{ $curSubCourse }} (Current)</option>
                            @endif
                            <option value="__add_new__" class="fw-bold text-primary">+ Add New...</option>
                        </select>
                    </div>
                    
                    <!-- Course Overview / Description -->
                    <div class="col-12 mb-2">
                        <label class="form-label fw-semibold text-dark">Course Overview (Description)</label>
                        <textarea name="description" class="form-control rounded-3" rows="3" placeholder="Provide a summary of the training goals and overall learning journey...">{{ old('description', $training->description) }}</textarea>
                    </div>

                    <!-- Learning Objectives -->
                    <div class="col-12">
                        <label class="form-label fw-semibold text-dark">Learning Objectives (Itemized Points)</label>
                        <textarea name="objectives" class="form-control rounded-3" rows="3" placeholder="• Enhance leadership capabilities&#10;• Improve team decision making...">{{ old('objectives', $training->learning_objectives) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- 2. TRAINING SYLLABUS & LEARNING MODULES (TOP SECTION) -->
            @php
                $syllabusList = [];
                if (old('syllabus_title')) {
                    $oldTitles = old('syllabus_title', []);
                    $oldDescs = old('syllabus_desc', []);
                    foreach ($oldTitles as $idx => $ot) {
                        $syllabusList[] = [
                            'topic'       => $ot,
                            'description' => $oldDescs[$idx] ?? '',
                        ];
                    }
                } elseif (!empty($training->syllabus) && is_array($training->syllabus)) {
                    $syllabusList = $training->syllabus;
                }
                if (empty($syllabusList)) {
                    $syllabusList = [
                        ['topic' => '', 'description' => '']
                    ];
                }
            @endphp
            <div class="card form-section-card border border-slate-200 rounded-2xl p-6 bg-white shadow-sm transition-all duration-200 w-100 position-relative">
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <h4 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2" style="font-size: 1.25rem;">
                        <i class="ti ti-list-check text-primary fs-2"></i>
                        <span>Training Syllabus & Learning Modules</span>
                    </h4>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-blue-600 text-white rounded-pill px-3 py-1 fs-7 active-section-badge"><i class="bi bi-pencil ti ti-pencil me-1"></i> Active Section</span>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" id="btn-add-syllabus">
                            <i class="ti ti-plus me-1"></i> Add Syllabus Point
                        </button>
                    </div>
                </div>
                <div id="syllabus-container" class="d-flex flex-column gap-2">
                    @foreach ($syllabusList as $idx => $sItem)
                        @php
                            $pointNum = $idx + 1;
                            $sTopic = is_array($sItem) ? ($sItem['topic'] ?? $sItem['title'] ?? '') : '';
                            $sDesc = is_array($sItem) ? ($sItem['description'] ?? $sItem['desc'] ?? '') : '';
                        @endphp
                        <div class="row g-2 align-items-center syllabus-row">
                            <div class="col-md-2 col-12">
                                <input type="text" class="form-control rounded-3 text-center bg-light fw-bold syllabus-point-badge" value="Point {{ $pointNum }}" readonly>
                            </div>
                            <div class="col-md-4 col-12">
                                <input type="text" name="syllabus_title[]" class="form-control rounded-3" placeholder="Topic / Module Title" value="{{ $sTopic }}">
                            </div>
                            <div class="col-md-5 col-10">
                                <input type="text" name="syllabus_desc[]" class="form-control rounded-3" placeholder="Highlights / Key Points Covered" value="{{ $sDesc }}">
                            </div>
                            <div class="col-md-1 col-2 text-center">
                                <button type="button" class="btn btn-outline-danger btn-sm rounded-circle btn-remove-syllabus" title="Remove point" {{ count($syllabusList) === 1 ? 'disabled' : '' }}>
                                    <i class="ti ti-trash"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- 3. BASIC INFORMATION & CAPACITY -->
            <div class="card form-section-card border border-slate-200 rounded-2xl p-6 bg-white shadow-sm transition-all duration-200 w-100 position-relative">
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <h4 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2" style="font-size: 1.25rem;">
                        <i class="ti ti-info-circle text-primary fs-2"></i>
                        <span>Classification, Basic Information & Capacity</span>
                    </h4>
                    <span class="badge bg-blue-600 text-white rounded-pill px-3 py-1 fs-7 active-section-badge"><i class="bi bi-pencil ti ti-pencil me-1"></i> Active Section</span>
                </div>
                <div class="row g-3">
                    <!-- Main Classification -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-dark">Main Classification</label>
                        @php $curMainClass = old('main_classification', $training->main_classification); @endphp
                        <select name="main_classification" id="select-main-classification" class="form-select rounded-3 w-100" data-option-type="classification" data-option-title="Main Classification">
                            <option value="">Select Main Classification...</option>
                            @foreach ($classifications ?? [] as $cls)
                                <option value="{{ $cls->classification_name }}" {{ $curMainClass == $cls->classification_name ? 'selected' : '' }}>
                                    {{ $cls->classification_name }}
                                </option>
                            @endforeach
                            @if ($curMainClass && !collect($classifications ?? [])->pluck('classification_name')->contains($curMainClass))
                                <option value="{{ $curMainClass }}" selected>{{ $curMainClass }} (Current)</option>
                            @endif
                            <option value="__add_new__" class="fw-bold text-primary">+ Add New...</option>
                        </select>
                    </div>

                    <!-- Sub Classification -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-dark">Sub Classification</label>
                        @php $curSubClass = old('sub_classification', $training->sub_classification); @endphp
                        <select name="sub_classification" id="select-sub-classification" class="form-select rounded-3 w-100" data-option-type="sub_classification" data-option-title="Sub Classification">
                            <option value="">Select Sub Classification...</option>
                            @foreach ($options['sub_classification'] ?? [] as $subOpt)
                                <option value="{{ $subOpt->value }}" {{ $curSubClass == $subOpt->value ? 'selected' : '' }}>
                                    {{ $subOpt->value }}
                                </option>
                            @endforeach
                            @if ($curSubClass && !collect($options['sub_classification'] ?? [])->pluck('value')->contains($curSubClass))
                                <option value="{{ $curSubClass }}" selected>{{ $curSubClass }} (Current)</option>
                            @endif
                            <option value="__add_new__" class="fw-bold text-primary">+ Add New...</option>
                        </select>
                    </div>

                    <!-- Training Category -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-dark">Training Category <span class="text-danger">*</span></label>
                        @php $currentCategory = old('category', $training->category); @endphp
                        <select name="category" id="select-category" class="form-select rounded-3 w-100" data-option-type="category" data-option-title="Training Category" required>
                            <option value="">Select Category...</option>
                            @foreach ($options['category'] ?? [] as $catOpt)
                                <option value="{{ $catOpt->value }}" {{ $currentCategory == $catOpt->value ? 'selected' : '' }}>
                                    {{ $catOpt->value }}
                                </option>
                            @endforeach
                            @if ($currentCategory && !collect($options['category'] ?? [])->pluck('value')->contains($currentCategory))
                                <option value="{{ $currentCategory }}" selected>{{ $currentCategory }} (Current)</option>
                            @endif
                            <option value="__add_new__" class="fw-bold text-primary">+ Add New...</option>
                        </select>
                        <div class="invalid-feedback">Please select a category.</div>
                    </div>

                    <!-- Training Type -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-dark">Training Type</label>
                        @php $curType = old('type', $training->type); @endphp
                        <select name="type" id="select-type" class="form-select rounded-3 w-100" data-option-type="type" data-option-title="Training Type">
                            <option value="">Select Training Type...</option>
                            @foreach ($options['type'] ?? [] as $typeOpt)
                                <option value="{{ $typeOpt->value }}" {{ $curType == $typeOpt->value ? 'selected' : '' }}>
                                    {{ $typeOpt->value }}
                                </option>
                            @endforeach
                            @if ($curType && !collect($options['type'] ?? [])->pluck('value')->contains($curType))
                                <option value="{{ $curType }}" selected>{{ $curType }} (Current)</option>
                            @endif
                            <option value="__add_new__" class="fw-bold text-primary">+ Add New...</option>
                        </select>
                    </div>

                    <!-- Competency Level -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-dark">Competency Level</label>
                        @php $curLevel = old('level', $training->level); @endphp
                        <select name="level" id="select-level" class="form-select rounded-3 w-100" data-option-type="level" data-option-title="Competency Level">
                            <option value="">Select Level...</option>
                            @foreach ($options['level'] ?? [] as $lvlOpt)
                                <option value="{{ $lvlOpt->value }}" {{ $curLevel == $lvlOpt->value ? 'selected' : '' }}>
                                    {{ $lvlOpt->value }}
                                </option>
                            @endforeach
                            @if ($curLevel && !collect($options['level'] ?? [])->pluck('value')->contains($curLevel))
                                <option value="{{ $curLevel }}" selected>{{ $curLevel }} (Current)</option>
                            @endif
                            <option value="__add_new__" class="fw-bold text-primary">+ Add New...</option>
                        </select>
                    </div>

                    <!-- Batch -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-dark">Batch <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control rounded-3" placeholder="e.g. Batch 1 / 2026" value="{{ old('code', $training->batch ?? $training->training_code) }}" required>
                        <div class="invalid-feedback">Please enter the batch.</div>
                    </div>

                    <!-- Instructor / Trainer -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Instructor / Trainer <span class="text-danger">*</span></label>
                        @php $curInstructor = old('instructor', $training->instructor); @endphp
                        <select name="instructor" id="select-instructor" class="form-select rounded-3 w-100" data-option-type="instructor" data-option-title="Instructor / Trainer" required>
                            <option value="">Select Instructor / Trainer...</option>
                            @foreach ($options['instructor'] ?? [] as $instOpt)
                                <option value="{{ $instOpt->value }}" {{ $curInstructor == $instOpt->value ? 'selected' : '' }}>
                                    {{ $instOpt->value }}
                                </option>
                            @endforeach
                            @if ($curInstructor && !collect($options['instructor'] ?? [])->pluck('value')->contains($curInstructor))
                                <option value="{{ $curInstructor }}" selected>{{ $curInstructor }} (Current)</option>
                            @endif
                            <option value="__add_new__" class="fw-bold text-primary">+ Add New...</option>
                        </select>
                        <div class="invalid-feedback">Please select or provide an instructor or trainer.</div>
                    </div>

                    <!-- Location / Venue -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Location / Venue <span class="text-danger">*</span></label>
                        @php $curLocation = old('location', $training->location ?? $training->venue); @endphp
                        <select name="location" id="select-location" class="form-select rounded-3 w-100" data-option-type="venue" data-option-title="Location / Venue" required>
                            <option value="">Select Location / Venue...</option>
                            @foreach ($options['location'] ?? [] as $locOpt)
                                <option value="{{ $locOpt->value }}" {{ $curLocation == $locOpt->value ? 'selected' : '' }}>
                                    {{ $locOpt->value }}
                                </option>
                            @endforeach
                            @if ($curLocation && !collect($options['location'] ?? [])->pluck('value')->contains($curLocation))
                                <option value="{{ $curLocation }}" selected>{{ $curLocation }} (Current)</option>
                            @endif
                            <option value="__add_new__" class="fw-bold text-primary">+ Add New...</option>
                        </select>
                        <div class="invalid-feedback">Please select or provide a training location or venue.</div>
                    </div>

                    <!-- Target Employee -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Target Employee <span class="text-danger">*</span></label>
                        @php $curTarget = old('target_employee', $training->target_employee); @endphp
                        <select name="target_employee" id="select-target-employee" class="form-select rounded-3 w-100" data-option-type="target_employee" data-option-title="Target Employee" required>
                            <option value="">Select Target Employee...</option>
                            @foreach ($options['target_employee'] ?? [] as $tgtOpt)
                                <option value="{{ $tgtOpt->value }}" {{ $curTarget == $tgtOpt->value ? 'selected' : '' }}>
                                    {{ $tgtOpt->value }}
                                </option>
                            @endforeach
                            @if ($curTarget && !collect($options['target_employee'] ?? [])->pluck('value')->contains($curTarget))
                                <option value="{{ $curTarget }}" selected>{{ $curTarget }} (Current)</option>
                            @endif
                            <option value="__add_new__" class="fw-bold text-primary">+ Add New...</option>
                        </select>
                        <div class="invalid-feedback">Please select or specify the target employee or audience.</div>
                    </div>

                    <!-- Requirements -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Requirements</label>
                        @php $curPrereq = old('prerequisites', $training->prerequisites); @endphp
                        <select name="prerequisites" id="select-prerequisites" class="form-select rounded-3 w-100" data-option-type="prerequisites" data-option-title="Requirements">
                            <option value="">Select Requirements (Optional)...</option>
                            @foreach ($options['prerequisites'] ?? [] as $preOpt)
                                <option value="{{ $preOpt->value }}" {{ $curPrereq == $preOpt->value ? 'selected' : '' }}>
                                    {{ $preOpt->value }}
                                </option>
                            @endforeach
                            @if ($curPrereq && !collect($options['prerequisites'] ?? [])->pluck('value')->contains($curPrereq))
                                <option value="{{ $curPrereq }}" selected>{{ $curPrereq }} (Current)</option>
                            @endif
                            <option value="__add_new__" class="fw-bold text-primary">+ Add New...</option>
                        </select>
                    </div>

                    <!-- Participant Quota -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Participant Quota (Seats) <span class="text-danger">*</span></label>
                        <input type="number" name="quota" class="form-control rounded-3" placeholder="30" min="1" max="1000" value="{{ old('quota', $training->quota) }}" required>
                        <div class="invalid-feedback">Please provide a valid participant quota.</div>
                    </div>

                    <!-- Registration Status -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Registration Status <span class="text-danger">*</span></label>
                        @php $currentStatus = old('status', $training->status); @endphp
                        <select name="status" class="form-select rounded-3" required>
                            <option value="Open Registration" {{ $currentStatus == 'Open Registration' || $currentStatus == 'Open' ? 'selected' : '' }}>Open Registration [Active]</option>
                            <option value="Running" {{ $currentStatus == 'Running' ? 'selected' : '' }}>Running [In Progress]</option>
                            <option value="Closed" {{ $currentStatus == 'Closed' ? 'selected' : '' }}>Closed / Full</option>
                            <option value="Draft" {{ $currentStatus == 'Draft' ? 'selected' : '' }}>Draft</option>
                        </select>
                        <div class="invalid-feedback">Please select a registration status.</div>
                    </div>
                </div>
            </div>

            <!-- 4. SCHEDULE TIMING & DATES -->
            <div class="card form-section-card border border-slate-200 rounded-2xl p-6 bg-white shadow-sm transition-all duration-200 w-100 position-relative">
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <h4 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2" style="font-size: 1.25rem;">
                        <i class="ti ti-calendar-event text-primary fs-2"></i>
                        <span>Schedule Timing & Dates</span>
                    </h4>
                    <span class="badge bg-blue-600 text-white rounded-pill px-3 py-1 fs-7 active-section-badge"><i class="bi bi-pencil ti ti-pencil me-1"></i> Active Section</span>
                </div>
                <div class="row g-3">
                    <!-- Row 1: Dates -->
                    <div class="col-md-6">
                        <label for="start_date" class="form-label fw-semibold text-dark">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" id="start_date" class="form-control rounded-3" value="{{ old('start_date', $training->start_date ? \Carbon\Carbon::parse($training->start_date)->format('Y-m-d') : '') }}" required>
                        <div class="invalid-feedback">Please select the start date.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="end_date" class="form-label fw-semibold text-dark">End Date</label>
                        <input type="date" name="end_date" id="end_date" class="form-control rounded-3" value="{{ old('end_date', $training->end_date ? \Carbon\Carbon::parse($training->end_date)->format('Y-m-d') : '') }}">
                        <div class="form-text text-muted small">Leave blank if this is a single-day session.</div>
                    </div>

                    <!-- Row 2: Times -->
                    <div class="col-md-6">
                        <label for="start_time" class="form-label fw-semibold text-dark">Start Time <span class="text-danger">*</span></label>
                        <input type="time" name="start_time" id="start_time" class="form-control rounded-3" value="{{ old('start_time', $training->start_time ? substr($training->start_time, 0, 5) : '08:00') }}" required>
                        <div class="invalid-feedback">Please specify the start time.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="end_time" class="form-label fw-semibold text-dark">End Time <span class="text-danger">*</span></label>
                        <input type="time" name="end_time" id="end_time" class="form-control rounded-3" value="{{ old('end_time', $training->end_time ? substr($training->end_time, 0, 5) : '16:00') }}" required>
                        <div class="invalid-feedback">Please specify the end time.</div>
                    </div>

                    <!-- Row 3: Registration Deadline Date & Time -->
                    <div class="col-md-6">
                        <label for="registration_close_date" class="form-label fw-semibold text-dark">Registration Close Date</label>
                        <input type="date" name="registration_close_date" id="registration_close_date" class="form-control rounded-3" 
                               value="{{ old('registration_close_date', $training->registration_close_date ? \Carbon\Carbon::parse($training->registration_close_date)->format('Y-m-d') : '') }}">
                        <small class="text-muted d-block mt-1">Leave blank to default to Start Date.</small>
                    </div>
                    <div class="col-md-6">
                        <label for="registration_close_time" class="form-label fw-semibold text-dark">Registration Close Time</label>
                        <input type="time" name="registration_close_time" id="registration_close_time" class="form-control rounded-3" 
                               value="{{ old('registration_close_time', $training->registration_close_time ? substr($training->registration_close_time, 0, 5) : '23:59') }}">
                        <small class="text-muted d-block mt-1">Defaults to 23:59 if left blank. Registration will automatically close at this exact time.</small>
                    </div>
                </div>
            </div>

            <!-- 5. SAVE & CANCEL ACTION BUTTONS -->
            <div class="d-flex align-items-center justify-content-end gap-3 pt-2 mb-5">
                <a href="{{ url('/manage/training-schedule') }}" class="btn btn-outline-secondary rounded-3 px-4 py-2 fw-semibold">Cancel</a>
                <button type="submit" name="action" value="draft" class="btn btn-outline-secondary rounded-3 px-4 py-2 fw-semibold">
                    <i class="ti ti-file me-1"></i> Save Draft
                </button>
                <button type="submit" name="action" value="publish" class="btn text-white rounded-3 px-5 py-2 fw-semibold" style="background-color: #003b6d; border-color: #003b6d;">
                    <i class="ti ti-check me-1"></i> Save &amp; Publish
                </button>
            </div>
        </form>
        @include('main.manage-training-schedule.components.schedule-modal-add-option')
    </div>
</div>
@endsection

@section('style')
<style>
        .form-section-card {
        border: 1px solid #e2e8f0 !important;
        border-radius: 1rem !important; /* rounded-2xl */
        padding: 1.5rem !important; /* p-6 */
        background-color: #ffffff !important;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px 0 rgba(0, 0, 0, 0.03) !important;
        transition: all 0.2s ease-in-out !important;
        position: relative;
    }

    .form-section-card:focus-within,
    .form-section-card.is-active-section {
        border: 2px solid #2563eb !important;
        box-shadow: 0 0 15px rgba(37, 99, 235, 0.2) !important;
    }

    .form-section-card .active-section-badge {
        display: none !important;
        background-color: #2563eb !important;
        color: #ffffff !important;
        font-weight: 600;
        box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
    }

    .form-section-card:focus-within .active-section-badge,
    .form-section-card.is-active-section .active-section-badge {
        display: inline-flex !important;
        align-items: center;
        animation: activeSectionFade 0.2s ease-in-out;
    }

    @keyframes activeSectionFade {
        from { opacity: 0; transform: translateY(-2px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .btn-remove-syllabus {
        width: 34px;
        height: 34px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
</style>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
        // Active section highlight tracking on focusin / click
    const sectionCards = document.querySelectorAll('.form-section-card');
    sectionCards.forEach(card => {
        card.addEventListener('focusin', function () {
            sectionCards.forEach(c => c.classList.remove('is-active-section'));
            card.classList.add('is-active-section');
        });
    });
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.form-section-card')) {
            sectionCards.forEach(c => c.classList.remove('is-active-section'));
        }
    });
    const form = document.getElementById('editTrainingForm');
    const btnAddSyllabus = document.getElementById('btn-add-syllabus');
    const syllabusContainer = document.getElementById('syllabus-container');

    // Renumber syllabus points and manage delete button states
    function renumberSyllabusPoints() {
        if (!syllabusContainer) return;
        const rows = syllabusContainer.querySelectorAll('.syllabus-row');
        rows.forEach((row, idx) => {
            const badge = row.querySelector('.syllabus-point-badge');
            if (badge) {
                badge.value = `Point ${idx + 1}`;
            }
            const delBtn = row.querySelector('.btn-remove-syllabus');
            if (delBtn) {
                delBtn.disabled = (rows.length === 1);
            }
        });
    }

    // Add new syllabus row
    if (btnAddSyllabus && syllabusContainer) {
        btnAddSyllabus.addEventListener('click', function () {
            const currentCount = syllabusContainer.querySelectorAll('.syllabus-row').length;
            const newIndex = currentCount + 1;
            const newRow = document.createElement('div');
            newRow.className = 'row g-2 align-items-center syllabus-row';
            newRow.innerHTML = `
                <div class="col-md-2 col-12">
                    <input type="text" class="form-control rounded-3 text-center bg-light fw-bold syllabus-point-badge" value="Point ${newIndex}" readonly>
                </div>
                <div class="col-md-4 col-12">
                    <input type="text" name="syllabus_title[]" class="form-control rounded-3" placeholder="Topic / Module Title">
                </div>
                <div class="col-md-5 col-10">
                    <input type="text" name="syllabus_desc[]" class="form-control rounded-3" placeholder="Highlights / Key Points Covered">
                </div>
                <div class="col-md-1 col-2 text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm rounded-circle btn-remove-syllabus" title="Remove point">
                        <i class="ti ti-trash"></i>
                    </button>
                </div>
            `;
            syllabusContainer.appendChild(newRow);
            renumberSyllabusPoints();
        });

        // Delegate delete syllabus row
        syllabusContainer.addEventListener('click', function (e) {
            const delBtn = e.target.closest('.btn-remove-syllabus');
            if (delBtn && !delBtn.disabled) {
                const row = delBtn.closest('.syllabus-row');
                if (row) {
                    row.remove();
                    renumberSyllabusPoints();
                }
            }
        });
    }

    // --- CLASSIFICATION DEPENDENCY LOGIC ---
    window.subClassificationMap = @json($subClassificationMap ?? []);
    window.allSubOptions = [];
    $('#select-sub-classification option').each(function () {
        const val = $(this).val();
        if (val && val !== '__add_new__') {
            window.allSubOptions.push({
                value: val,
                text: $(this).text().trim()
            });
        }
    });

    function updateSubClassificationDropdown(selectedMain, keepCurrentVal = false) {
        const $subSelect = $('#select-sub-classification');
        const currentVal = $subSelect.val();
        const mapped = (selectedMain && window.subClassificationMap[selectedMain]) ? window.subClassificationMap[selectedMain] : null;

        $subSelect.empty().append('<option value="">Select Sub Classification...</option>');

        const itemsToRender = (mapped && mapped.length > 0)
            ? window.allSubOptions.filter(item => mapped.includes(item.value))
            : window.allSubOptions;

        let foundCurrent = false;
        itemsToRender.forEach(item => {
            if (item.value === '__add_new__') return;
            const isSel = (keepCurrentVal && item.value === currentVal);
            if (isSel) foundCurrent = true;
            $subSelect.append(new Option(item.text, item.value, false, isSel));
        });

        if (keepCurrentVal && currentVal && currentVal !== '__add_new__' && !foundCurrent) {
            $subSelect.append(new Option(currentVal, currentVal, true, true));
        } else if (!keepCurrentVal) {
            $subSelect.val('');
        }

        $subSelect.append('<option value="__add_new__" class="fw-bold text-primary">+ Add New...</option>');
    }

    $('#select-main-classification').on('change', function () {
        const mainVal = $(this).val();
        if (mainVal === '__add_new__') return;
        updateSubClassificationDropdown(mainVal, false);
    });

    // Initialize edit selection: keep existing sub_classification
    const initialMain = $('#select-main-classification').val();
    const existingSubVal = "{{ old('sub_classification', $training->sub_classification) }}";
    if (initialMain && window.subClassificationMap[initialMain]) {
        updateSubClassificationDropdown(initialMain, true);
        if (existingSubVal) {
            $('#select-sub-classification').val(existingSubVal);
        }
    }

    // Handle form submission: validate then let it submit to backend
    if (form) {
        let clickedAction = 'publish';
        form.querySelectorAll('button[type="submit"]').forEach(btn => {
            btn.addEventListener('click', function () {
                clickedAction = this.value || 'publish';
            });
        });

        form.addEventListener('submit', function (e) {
            if (clickedAction === 'draft') {
                const titleVal = document.getElementById('select-training-title')?.value;
                if (!titleVal || !titleVal.trim()) {
                    e.preventDefault();
                    e.stopPropagation();
                    alert('Please select or enter a Training Title before saving as draft.');
                    return;
                }
                return; // Allow draft submission
            }

            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                form.classList.add('was-validated');
            }
        });
    }
});
</script>
@endpush
