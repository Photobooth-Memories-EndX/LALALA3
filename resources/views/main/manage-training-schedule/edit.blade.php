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
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
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
            <div class="card border-0 shadow-sm rounded-4 p-4 w-100">
                <h5 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                    <i class="ti ti-file-text text-primary fs-3"></i>
                    <span>Course Overview & Objectives</span>
                </h5>
                <div class="row g-3">
                    <!-- Training Title Placed Here at the Top -->
                    <div class="col-md-6 mb-2">
                        <label class="form-label fw-semibold text-dark">Training Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="inputTrainingTitle" class="form-control rounded-3" placeholder="e.g. Leadership Development Program for Supervisors" value="{{ old('title', $training->title) }}" required>
                        <div class="invalid-feedback">Please provide a valid training title.</div>
                    </div>

                    <!-- Sub Course Name -->
                    <div class="col-md-6 mb-2">
                        <label class="form-label fw-semibold text-dark">Sub Course Name</label>
                        <input type="text" name="sub_course_name" id="inputSubCourseName" class="form-control rounded-3" placeholder="e.g. Supervisory Leadership Module / Advanced Analytics" value="{{ old('sub_course_name', $training->sub_course_name) }}">
                    </div>
                    
                    <!-- Course Overview / Description -->
                    <div class="col-12 mb-2">
                        <label class="form-label fw-semibold text-dark">Course Overview (Description)</label>
                        <textarea name="description" class="form-control rounded-3" rows="3" placeholder="Provide a summary of the training goals and overall learning journey...">{{ old('description', $training->description) }}</textarea>
                    </div>

                    <!-- Learning Objectives -->
                    <div class="col-12">
                        <label class="form-label fw-semibold text-dark">Learning Objectives (Tujuan Pelatihan - Itemized Points)</label>
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
            <div class="card border-0 shadow-sm rounded-4 p-4 w-100">
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="ti ti-list-check text-primary fs-3"></i>
                        <span>Training Syllabus & Learning Modules</span>
                    </h5>
                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" id="btn-add-syllabus">
                        <i class="ti ti-plus me-1"></i> Add Syllabus Point
                    </button>
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
            <div class="card border-0 shadow-sm rounded-4 p-4 w-100">
                <h5 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                    <i class="ti ti-info-circle text-primary fs-3"></i>
                    <span>Classification, Basic Information & Capacity</span>
                </h5>
                <div class="row g-3">
                    <!-- Main Classification -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-dark">Main Classification</label>
                        @php $curMainClass = old('main_classification', $training->main_classification); @endphp
                        <select name="main_classification" class="form-select rounded-3">
                            <option value="">Select Main Classification...</option>
                            <option value="Basic & Common Duties" {{ $curMainClass == 'Basic & Common Duties' ? 'selected' : '' }}>Basic & Common Duties</option>
                            <option value="Compliance" {{ $curMainClass == 'Compliance' ? 'selected' : '' }}>Compliance</option>
                            <option value="Innovation" {{ $curMainClass == 'Innovation' ? 'selected' : '' }}>Innovation</option>
                            <option value="Job Functional" {{ $curMainClass == 'Job Functional' ? 'selected' : '' }}>Job Functional</option>
                            <option value="Language" {{ $curMainClass == 'Language' ? 'selected' : '' }}>Language</option>
                            <option value="Leadership" {{ $curMainClass == 'Leadership' ? 'selected' : '' }}>Leadership</option>
                            <option value="Maintenance" {{ $curMainClass == 'Maintenance' ? 'selected' : '' }}>Maintenance</option>
                            <option value="ISO Standard" {{ $curMainClass == 'ISO Standard' ? 'selected' : '' }}>ISO Standard</option>
                            <option value="SHE" {{ $curMainClass == 'SHE' ? 'selected' : '' }}>SHE</option>
                        </select>
                    </div>

                    <!-- Sub Classification -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-dark">Sub Classification</label>
                        <input type="text" name="sub_classification" class="form-control rounded-3" placeholder="e.g. 5S & Workplace Safety, Quality Assurance" value="{{ old('sub_classification', $training->sub_classification) }}">
                    </div>

                    <!-- Training Category -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-dark">Training Category <span class="text-danger">*</span></label>
                        @php $currentCategory = old('category', $training->category); @endphp
                        <select name="category" class="form-select rounded-3" required>
                            <option value="">Select Category...</option>
                            <option value="Compliance & Safety" {{ $currentCategory == 'Compliance & Safety' ? 'selected' : '' }}>Compliance & Safety</option>
                            <option value="Core Competency" {{ $currentCategory == 'Core Competency' ? 'selected' : '' }}>Core Competency</option>
                            <option value="Environmental" {{ $currentCategory == 'Environmental' ? 'selected' : '' }}>Environmental</option>
                            <option value="General Competency" {{ $currentCategory == 'General Competency' ? 'selected' : '' }}>General Competency</option>
                            <option value="Innovation" {{ $currentCategory == 'Innovation' ? 'selected' : '' }}>Innovation</option>
                            <option value="Job Functional" {{ $currentCategory == 'Job Functional' ? 'selected' : '' }}>Job Functional</option>
                            <option value="Leadership" {{ $currentCategory == 'Leadership' ? 'selected' : '' }}>Leadership</option>
                            <option value="Maintenance" {{ $currentCategory == 'Maintenance' ? 'selected' : '' }}>Maintenance</option>
                            <option value="Quality" {{ $currentCategory == 'Quality' ? 'selected' : '' }}>Quality</option>
                            <option value="Safety" {{ $currentCategory == 'Safety' ? 'selected' : '' }}>Safety</option>
                            <option value="Soft Skill" {{ $currentCategory == 'Soft Skill' ? 'selected' : '' }}>Soft Skill</option>
                            <option value="Technical" {{ $currentCategory == 'Technical' ? 'selected' : '' }}>Technical</option>
                            @if ($currentCategory && !in_array($currentCategory, ['Compliance & Safety', 'Core Competency', 'Environmental', 'General Competency', 'Innovation', 'Job Functional', 'Leadership', 'Maintenance', 'Quality', 'Safety', 'Soft Skill', 'Technical']))
                                <option value="{{ $currentCategory }}" selected>{{ $currentCategory }} (Current)</option>
                            @endif
                        </select>
                        <div class="invalid-feedback">Please select a category.</div>
                    </div>

                    <!-- Training Type -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-dark">Training Type</label>
                        @php $curType = old('type', $training->type); @endphp
                        <select name="type" class="form-select rounded-3">
                            <option value="">Select Training Type...</option>
                            <option value="In-House" {{ $curType == 'In-House' ? 'selected' : '' }}>In-House</option>
                            <option value="Workshop" {{ $curType == 'Workshop' ? 'selected' : '' }}>Workshop</option>
                            <option value="Certification" {{ $curType == 'Certification' ? 'selected' : '' }}>Certification</option>
                            <option value="Public" {{ $curType == 'Public' ? 'selected' : '' }}>Public</option>
                        </select>
                    </div>

                    <!-- Competency Level -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-dark">Competency Level</label>
                        @php $curLevel = old('level', $training->level); @endphp
                        <select name="level" class="form-select rounded-3">
                            <option value="">Select Level...</option>
                            <option value="Basic" {{ $curLevel == 'Basic' ? 'selected' : '' }}>Basic</option>
                            <option value="Intermediate" {{ $curLevel == 'Intermediate' ? 'selected' : '' }}>Intermediate</option>
                            <option value="Advanced" {{ $curLevel == 'Advanced' ? 'selected' : '' }}>Advanced</option>
                        </select>
                    </div>

                    <!-- Training Code / Batch -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-dark">Training Code / Batch <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control rounded-3" placeholder="e.g. PTKP-LDP-01 (Batch 1 / 2026)" value="{{ old('code', $training->training_code ?? $training->batch) }}" required>
                        <div class="invalid-feedback">Please enter the training code or batch.</div>
                    </div>

                    <!-- Instructor / Trainer -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Instructor / Trainer <span class="text-danger">*</span></label>
                        <input type="text" name="instructor" class="form-control rounded-3" placeholder="e.g. Dr. Hendra Wijaya, M.M." value="{{ old('instructor', $training->instructor) }}" required>
                        <div class="invalid-feedback">Please enter the instructor or trainer name.</div>
                    </div>

                    <!-- Location / Venue -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Location / Venue <span class="text-danger">*</span></label>
                        <input type="text" name="location" class="form-control rounded-3" placeholder="e.g. Learning Center Room 302 / Zoom Online" value="{{ old('location', $training->location) }}" required>
                        <div class="invalid-feedback">Please enter the training location or venue.</div>
                    </div>

                    <!-- Target Employee -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Target Employee <span class="text-danger">*</span></label>
                        <input type="text" name="target_employee" class="form-control rounded-3" placeholder="e.g. Staff to Supervisor, Maintenance Division" value="{{ old('target_employee', $training->target_employee) }}" required>
                        <div class="invalid-feedback">Please specify the target employee or audience.</div>
                    </div>

                    <!-- Prerequisites -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Prerequisites / Persyaratan</label>
                        <input type="text" name="prerequisites" class="form-control rounded-3" placeholder="e.g. Minimum 1 year tenure, Basic Excel knowledge" value="{{ old('prerequisites', $training->prerequisites) }}">
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
                            <option value="Open Registration" {{ $currentStatus == 'Open Registration' ? 'selected' : '' }}>Open Registration [Active]</option>
                            <option value="Running" {{ $currentStatus == 'Running' ? 'selected' : '' }}>Running [In Progress]</option>
                            <option value="Closed" {{ $currentStatus == 'Closed' ? 'selected' : '' }}>Closed / Full</option>
                        </select>
                        <div class="invalid-feedback">Please select a registration status.</div>
                    </div>
                </div>
            </div>

            <!-- 4. SCHEDULE TIMING & DATES -->
            <div class="card border-0 shadow-sm rounded-4 p-4 w-100">
                <h5 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                    <i class="ti ti-calendar-event text-primary fs-3"></i>
                    <span>Schedule Timing & Dates</span>
                </h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control rounded-3" value="{{ old('start_date', $training->start_date ? \Carbon\Carbon::parse($training->start_date)->format('Y-m-d') : '') }}" required>
                        <div class="invalid-feedback">Please select the start date.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">End Date</label>
                        <input type="date" name="end_date" class="form-control rounded-3" value="{{ old('end_date', $training->end_date ? \Carbon\Carbon::parse($training->end_date)->format('Y-m-d') : '') }}">
                        <div class="form-text text-muted small">Leave blank if this is a single-day session.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Start Time <span class="text-danger">*</span></label>
                        <input type="time" name="start_time" class="form-control rounded-3" value="{{ old('start_time', $training->start_time ? substr($training->start_time, 0, 5) : '08:00') }}" required>
                        <div class="invalid-feedback">Please specify the start time.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">End Time <span class="text-danger">*</span></label>
                        <input type="time" name="end_time" class="form-control rounded-3" value="{{ old('end_time', $training->end_time ? substr($training->end_time, 0, 5) : '16:00') }}" required>
                        <div class="invalid-feedback">Please specify the end time.</div>
                    </div>
                </div>
            </div>

            <!-- 5. SAVE & CANCEL ACTION BUTTONS -->
            <div class="d-flex align-items-center justify-content-end gap-3 pt-2 mb-5">
                <a href="{{ url('/manage/training-schedule') }}" class="btn btn-outline-secondary rounded-3 px-4 py-2 fw-semibold">Cancel</a>
                <button type="submit" class="btn text-white rounded-3 px-5 py-2 fw-semibold" style="background-color: #003b6d; border-color: #003b6d;">
                    <i class="ti ti-check me-1"></i> Update Training Schedule
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('style')
<style>
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

    // Handle form submission: validate then let it submit to backend
    if (form) {
        form.addEventListener('submit', function (e) {
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
