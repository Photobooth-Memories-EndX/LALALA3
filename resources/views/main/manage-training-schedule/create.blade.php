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
            <h3 class="fw-bold text-dark mb-1">Add New Training Schedule</h3>
            <p class="text-muted small mb-0">Set up corporate training details, syllabus structure, target audience, and participant capacity.</p>
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

        <form id="createTrainingForm" action="{{ route('manage.training-schedule.store') }}" method="POST" class="d-flex flex-column gap-4 col-12" novalidate>
            @csrf

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
                        <input type="text" name="title" id="inputTrainingTitle" class="form-control rounded-3" placeholder="e.g. Leadership Development Program for Supervisors" value="{{ old('title') }}" required>
                        <div class="invalid-feedback">Please provide a valid training title.</div>
                    </div>

                    <!-- Sub Course Name -->
                    <div class="col-md-6 mb-2">
                        <label class="form-label fw-semibold text-dark">Sub Course Name</label>
                        <input type="text" name="sub_course_name" id="inputSubCourseName" class="form-control rounded-3" placeholder="e.g. Supervisory Leadership Module / Advanced Analytics" value="{{ old('sub_course_name') }}">
                    </div>
                    
                    <!-- Course Overview / Description -->
                    <div class="col-12 mb-2">
                        <label class="form-label fw-semibold text-dark">Course Overview (Description)</label>
                        <textarea name="description" class="form-control rounded-3" rows="3" placeholder="Provide a summary of the training goals and overall learning journey...">{{ old('description') }}</textarea>
                    </div>

                    <!-- Learning Objectives -->
                    <div class="col-12">
                        <label class="form-label fw-semibold text-dark">Learning Objectives (Tujuan Pelatihan - Itemized Points)</label>
                        <textarea name="objectives" class="form-control rounded-3" rows="3" placeholder="• Enhance leadership capabilities&#10;• Improve team decision making...">{{ old('objectives') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- 2. TRAINING SYLLABUS & LEARNING MODULES (TOP SECTION) -->
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
                    <!-- Syllabus Row 1 -->
                    <div class="row g-2 align-items-center syllabus-row">
                        <div class="col-md-2 col-12">
                            <input type="text" class="form-control rounded-3 text-center bg-light fw-bold syllabus-point-badge" value="Point 1" readonly>
                        </div>
                        <div class="col-md-4 col-12">
                            <input type="text" name="syllabus_title[]" class="form-control rounded-3" placeholder="Topic / Module Title" value="Introduction & Core Principles">
                        </div>
                        <div class="col-md-5 col-10">
                            <input type="text" name="syllabus_desc[]" class="form-control rounded-3" placeholder="Highlights / Key Points Covered" value="Foundational principles, corporate alignment, and objective review.">
                        </div>
                        <div class="col-md-1 col-2 text-center">
                            <button type="button" class="btn btn-outline-danger btn-sm rounded-circle btn-remove-syllabus" title="Remove point" disabled>
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                    </div>
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
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label fw-semibold text-dark mb-0">Main Classification</label>
                            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 btn-add-option-modal" style="font-size: 0.75rem; height: 22px; border-radius: 4px;" data-type="classification" data-target="#select-main-classification" data-title="Main Classification" title="Add Main Classification">
                                <i class="ti ti-plus"></i>
                            </button>
                        </div>
                        <select name="main_classification" id="select-main-classification" class="form-select rounded-3">
                            <option value="">Select Main Classification...</option>
                            @foreach ($classifications ?? [] as $cls)
                                <option value="{{ $cls->classification_name }}" {{ old('main_classification') == $cls->classification_name ? 'selected' : '' }}>
                                    {{ $cls->classification_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Sub Classification -->
                    <div class="col-md-4">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label fw-semibold text-dark mb-0">Sub Classification</label>
                            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 btn-add-option-modal" style="font-size: 0.75rem; height: 22px; border-radius: 4px;" data-type="sub_classification" data-target="#select-sub-classification" data-title="Sub Classification" title="Add Sub Classification">
                                <i class="ti ti-plus"></i>
                            </button>
                        </div>
                        <select name="sub_classification" id="select-sub-classification" class="form-select rounded-3">
                            <option value="">Select Sub Classification...</option>
                            @foreach ($options['sub_classification'] ?? [] as $subOpt)
                                <option value="{{ $subOpt->value }}" {{ old('sub_classification') == $subOpt->value ? 'selected' : '' }}>
                                    {{ $subOpt->value }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Training Category -->
                    <div class="col-md-4">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label fw-semibold text-dark mb-0">Training Category <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 btn-add-option-modal" style="font-size: 0.75rem; height: 22px; border-radius: 4px;" data-type="category" data-target="#select-category" data-title="Training Category" title="Add Training Category">
                                <i class="ti ti-plus"></i>
                            </button>
                        </div>
                        <select name="category" id="select-category" class="form-select rounded-3" required>
                            <option value="">Select Category...</option>
                            @foreach ($options['category'] ?? [] as $catOpt)
                                <option value="{{ $catOpt->value }}" {{ old('category') == $catOpt->value ? 'selected' : '' }}>
                                    {{ $catOpt->value }}
                                </option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Please select a category.</div>
                    </div>

                    <!-- Training Type -->
                    <div class="col-md-4">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label fw-semibold text-dark mb-0">Training Type</label>
                            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 btn-add-option-modal" style="font-size: 0.75rem; height: 22px; border-radius: 4px;" data-type="type" data-target="#select-type" data-title="Training Type" title="Add Training Type">
                                <i class="ti ti-plus"></i>
                            </button>
                        </div>
                        <select name="type" id="select-type" class="form-select rounded-3">
                            <option value="">Select Training Type...</option>
                            @foreach ($options['type'] ?? [] as $typeOpt)
                                <option value="{{ $typeOpt->value }}" {{ old('type') == $typeOpt->value ? 'selected' : '' }}>
                                    {{ $typeOpt->value }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Competency Level -->
                    <div class="col-md-4">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label fw-semibold text-dark mb-0">Competency Level</label>
                            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 btn-add-option-modal" style="font-size: 0.75rem; height: 22px; border-radius: 4px;" data-type="level" data-target="#select-level" data-title="Competency Level" title="Add Competency Level">
                                <i class="ti ti-plus"></i>
                            </button>
                        </div>
                        <select name="level" id="select-level" class="form-select rounded-3">
                            <option value="">Select Level...</option>
                            @foreach ($options['level'] ?? [] as $lvlOpt)
                                <option value="{{ $lvlOpt->value }}" {{ old('level') == $lvlOpt->value ? 'selected' : '' }}>
                                    {{ $lvlOpt->value }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Training Code / Batch -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-dark">Training Code / Batch <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control rounded-3" placeholder="e.g. PTKP-LDP-01 (Batch 1 / 2026)" value="{{ old('code') }}" required>
                        <div class="invalid-feedback">Please enter the training code or batch.</div>
                    </div>

                    <!-- Instructor / Trainer -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Instructor / Trainer <span class="text-danger">*</span></label>
                        <input type="text" name="instructor" class="form-control rounded-3" placeholder="e.g. Dr. Hendra Wijaya, M.M." value="{{ old('instructor') }}" required>
                        <div class="invalid-feedback">Please enter the instructor or trainer name.</div>
                    </div>

                    <!-- Location / Venue -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Location / Venue <span class="text-danger">*</span></label>
                        <input type="text" name="location" class="form-control rounded-3" placeholder="e.g. Learning Center Room 302 / Zoom Online" value="{{ old('location') }}" required>
                        <div class="invalid-feedback">Please enter the training location or venue.</div>
                    </div>

                    <!-- Target Employee -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Target Employee <span class="text-danger">*</span></label>
                        <input type="text" name="target_employee" class="form-control rounded-3" placeholder="e.g. Staff to Supervisor, Maintenance Division" value="{{ old('target_employee') }}" required>
                        <div class="invalid-feedback">Please specify the target employee or audience.</div>
                    </div>

                    <!-- Prerequisites -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Prerequisites / Persyaratan</label>
                        <input type="text" name="prerequisites" class="form-control rounded-3" placeholder="e.g. Minimum 1 year tenure, Basic Excel knowledge" value="{{ old('prerequisites') }}">
                    </div>

                    <!-- Participant Quota -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Participant Quota (Seats) <span class="text-danger">*</span></label>
                        <input type="number" name="quota" class="form-control rounded-3" placeholder="30" min="1" max="1000" value="{{ old('quota', 30) }}" required>
                        <div class="invalid-feedback">Please provide a valid participant quota.</div>
                    </div>

                    <!-- Registration Status -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-dark">Registration Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select rounded-3" required>
                            <option value="Open Registration" {{ old('status', 'Open Registration') == 'Open Registration' ? 'selected' : '' }}>Open Registration [Active]</option>
                            <option value="Running" {{ old('status') == 'Running' ? 'selected' : '' }}>Running [In Progress]</option>
                            <option value="Closed" {{ old('status') == 'Closed' ? 'selected' : '' }}>Closed / Full</option>
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
                    <!-- Row 1: Dates -->
                    <div class="col-md-6">
                        <label for="start_date" class="form-label fw-semibold text-dark">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" id="start_date" class="form-control rounded-3" value="{{ old('start_date') }}" required>
                        <div class="invalid-feedback">Please select the start date.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="end_date" class="form-label fw-semibold text-dark">End Date</label>
                        <input type="date" name="end_date" id="end_date" class="form-control rounded-3" value="{{ old('end_date') }}">
                        <div class="form-text text-muted small">Leave blank if this is a single-day session.</div>
                    </div>

                    <!-- Row 2: Times -->
                    <div class="col-md-6">
                        <label for="start_time" class="form-label fw-semibold text-dark">Start Time <span class="text-danger">*</span></label>
                        <input type="time" name="start_time" id="start_time" class="form-control rounded-3" value="{{ old('start_time', '08:00') }}" required>
                        <div class="invalid-feedback">Please specify the start time.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="end_time" class="form-label fw-semibold text-dark">End Time <span class="text-danger">*</span></label>
                        <input type="time" name="end_time" id="end_time" class="form-control rounded-3" value="{{ old('end_time', '16:00') }}" required>
                        <div class="invalid-feedback">Please specify the end time.</div>
                    </div>

                    <!-- Row 3: Registration Deadline Date & Time -->
                    <div class="col-md-6">
                        <label for="registration_close_date" class="form-label fw-semibold text-dark">Registration Close Date</label>
                        <input type="date" name="registration_close_date" id="registration_close_date" class="form-control rounded-3" 
                               value="{{ old('registration_close_date') }}">
                        <small class="text-muted d-block mt-1">Leave blank to default to Start Date.</small>
                    </div>
                    <div class="col-md-6">
                        <label for="registration_close_time" class="form-label fw-semibold text-dark">Registration Close Time</label>
                        <input type="time" name="registration_close_time" id="registration_close_time" class="form-control rounded-3" 
                               value="{{ old('registration_close_time', '23:59') }}">
                        <small class="text-muted d-block mt-1">Defaults to 23:59 if left blank. Registration will automatically close at this exact time.</small>
                    </div>
                </div>
            </div>

            <!-- 5. SAVE & CANCEL ACTION BUTTONS -->
            <div class="d-flex align-items-center justify-content-end gap-3 pt-2 mb-5">
                <a href="{{ url('/manage/training-schedule') }}" class="btn btn-outline-secondary rounded-3 px-4 py-2 fw-semibold">Cancel</a>
                <button type="submit" class="btn text-white rounded-3 px-5 py-2 fw-semibold" style="background-color: #003b6d; border-color: #003b6d;">
                    <i class="ti ti-check me-1"></i> Save Training Schedule
                </button>
            </div>
        </form>
        @include('main.manage-training-schedule.components.schedule-modal-add-option')
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
    const form = document.getElementById('createTrainingForm');
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
        if (val) {
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
            const isSel = (keepCurrentVal && item.value === currentVal);
            if (isSel) foundCurrent = true;
            $subSelect.append(new Option(item.text, item.value, false, isSel));
        });

        if (keepCurrentVal && currentVal && !foundCurrent) {
            $subSelect.append(new Option(currentVal, currentVal, true, true));
        } else if (!keepCurrentVal) {
            $subSelect.val('');
        }
    }

    $('#select-main-classification').on('change', function () {
        const mainVal = $(this).val();
        updateSubClassificationDropdown(mainVal, false);
    });

    // Check old values on reload / validation error
    const initialMain = $('#select-main-classification').val();
    const oldSubVal = "{{ old('sub_classification') }}";
    if (initialMain && window.subClassificationMap[initialMain]) {
        updateSubClassificationDropdown(initialMain, true);
        if (oldSubVal) {
            $('#select-sub-classification').val(oldSubVal);
        }
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
