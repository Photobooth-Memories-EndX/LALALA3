@extends('layouts.sidebar-master')

@section('content')
<div class="page-body">
    <div class="container-xl">
        <!-- Back Navigation Link -->
        <div class="mb-3">
            <a href="{{ url('/manage/training-schedule') }}" class="btn btn-link p-0 text-decoration-none d-inline-flex align-items-center gap-1 fw-semibold text-primary">
                <i class="ti ti-arrow-left fs-2"></i>
                <span>Back</span>
            </a>
        </div>

        <!-- Hero Header Card -->
        <div class="card border-0 shadow-sm p-4 rounded-4 bg-white mb-4">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" 
                     style="width: 56px; height: 56px; background-color: #eef2ff; color: #003b6d;">
                    <i class="ti ti-calendar-plus" style="font-size: 1.75rem !important;"></i>
                </div>
                <div>
                    <h2 class="h3 fw-bold mb-1 text-dark">Add New Training Schedule</h2>
                    <p class="text-muted small mb-0">Set up and publish a new PTKP corporate training schedule, syllabus structure, and participant capacity.</p>
                </div>
            </div>
        </div>

        <!-- Create Training Form -->
        <form id="createTrainingForm" novalidate>
            <div class="row g-4">
                
                {{-- LEFT COLUMN: Basic Info & Schedule Timing --}}
                <div class="col-lg-8 col-12">
                    
                    {{-- 1. Basic Information Card --}}
                    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                        <div class="card-header bg-white border-bottom border-light-subtle py-3 px-4">
                            <h5 class="card-title fw-bold text-dark mb-0 d-inline-flex align-items-center gap-2">
                                <i class="ti ti-info-circle text-primary fs-2"></i>
                                <span>Basic Information</span>
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-bold text-dark required small">Training Title</label>
                                    <input type="text" id="inputTrainingTitle" class="form-control form-control-lg rounded-3" 
                                           placeholder="e.g. Leadership Development Program for Supervisors" required>
                                    <div class="invalid-feedback">Please provide a valid training title.</div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <label class="form-label fw-bold text-dark required small">Training Category</label>
                                    <select id="inputTrainingCategory" class="form-select rounded-3" required>
                                        <option value="" disabled selected>Select Category...</option>
                                        <option value="Soft Skill">Soft Skill</option>
                                        <option value="Technical Skill">Technical Skill</option>
                                        <option value="Compliance & Safety">Compliance & Safety</option>
                                        <option value="Leadership">Leadership</option>
                                    </select>
                                    <div class="invalid-feedback">Please select a category.</div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <label class="form-label fw-bold text-dark small">Training Code / Batch</label>
                                    <input type="text" id="inputTrainingCode" class="form-control rounded-3" 
                                           placeholder="e.g. PTKP-LDP-01 (Batch 1 / 2026)">
                                </div>

                                <div class="col-md-6 col-12">
                                    <label class="form-label fw-bold text-dark required small">Instructor / Trainer</label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-user text-muted"></i>
                                        </span>
                                        <input type="text" id="inputTrainingInstructor" class="form-control rounded-3" 
                                               placeholder="e.g. Dr. Hendra Wijaya, M.M." required>
                                    </div>
                                    <div class="invalid-feedback">Please enter the instructor name.</div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <label class="form-label fw-bold text-dark required small">Location / Venue</label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-map-pin text-muted"></i>
                                        </span>
                                        <input type="text" id="inputTrainingLocation" class="form-control rounded-3" 
                                               placeholder="e.g. Learning Center Room 302 / Zoom Online" required>
                                    </div>
                                    <div class="invalid-feedback">Please enter the location or venue.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 2. Schedule Timing & Dates --}}
                    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                        <div class="card-header bg-white border-bottom border-light-subtle py-3 px-4">
                            <h5 class="card-title fw-bold text-dark mb-0 d-inline-flex align-items-center gap-2">
                                <i class="ti ti-clock text-primary fs-2"></i>
                                <span>Schedule Timing & Dates</span>
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <div class="col-md-6 col-12">
                                    <label class="form-label fw-bold text-dark required small">Start Date</label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon"><i class="ti ti-calendar text-muted"></i></span>
                                        <input type="date" id="inputTrainingStartDate" class="form-control rounded-3" required>
                                    </div>
                                    <div class="invalid-feedback">Please select the start date.</div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <label class="form-label fw-bold text-dark small">End Date</label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon"><i class="ti ti-calendar text-muted"></i></span>
                                        <input type="date" id="inputTrainingEndDate" class="form-control rounded-3">
                                    </div>
                                    <div class="form-text extra-small">Leave blank if the training is a single-day session.</div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <label class="form-label fw-bold text-dark required small">Start Time</label>
                                    <input type="time" id="inputTrainingStartTime" class="form-control rounded-3" value="08:00" required>
                                </div>

                                <div class="col-md-6 col-12">
                                    <label class="form-label fw-bold text-dark required small">End Time</label>
                                    <input type="time" id="inputTrainingEndTime" class="form-control rounded-3" value="16:00" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 3. Dynamic Syllabus Builder --}}
                    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                        <div class="card-header bg-white border-bottom border-light-subtle py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <h5 class="card-title fw-bold text-dark mb-1 d-inline-flex align-items-center gap-2">
                                    <i class="ti ti-list-check text-primary fs-2"></i>
                                    <span>Training Syllabus & Learning Modules</span>
                                </h5>
                                <div class="text-muted extra-small">Outline topics, modules, and learning milestones that will be covered in this training.</div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1-5 fw-semibold d-inline-flex align-items-center gap-1" id="btnAddSyllabusPoint">
                                <i class="ti ti-plus"></i>
                                <span>Add Syllabus Point</span>
                            </button>
                        </div>
                        <div class="card-body p-4">
                            <div class="d-flex flex-column gap-3" id="syllabusPointsContainer">
                                
                                {{-- Initial Point 1 --}}
                                <div class="card border border-light-subtle rounded-3 p-3 bg-light-subtle syllabus-point-card shadow-none">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-md-2 col-3">
                                            <label class="form-label extra-small text-muted mb-1 fw-bold">Point #</label>
                                            <input type="text" class="form-control form-control-sm rounded-3 text-center fw-bold bg-white syllabus-point-num" value="Point 1" readonly>
                                        </div>
                                        <div class="col-md-4 col-9">
                                            <label class="form-label extra-small text-muted mb-1 fw-bold">Topic / Module Title</label>
                                            <input type="text" class="form-control form-control-sm rounded-3 bg-white syllabus-title-input" 
                                                   value="Introduction & Core Principles" placeholder="e.g. Module 1: Introduction">
                                        </div>
                                        <div class="col-md-5 col-10">
                                            <label class="form-label extra-small text-muted mb-1 fw-bold">Highlights / Description</label>
                                            <input type="text" class="form-control form-control-sm rounded-3 bg-white syllabus-desc-input" 
                                                   value="Foundational principles, corporate alignment, and objective review." placeholder="Key learning outcomes...">
                                        </div>
                                        <div class="col-md-1 col-2 text-end">
                                            <label class="form-label extra-small text-muted mb-1 d-none d-md-block">&nbsp;</label>
                                            <button type="button" class="btn btn-sm btn-icon btn-outline-danger rounded-3 btn-remove-point" title="Remove point" disabled>
                                                <i class="ti ti-trash fs-2"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                {{-- Initial Point 2 --}}
                                <div class="card border border-light-subtle rounded-3 p-3 bg-light-subtle syllabus-point-card shadow-none">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-md-2 col-3">
                                            <label class="form-label extra-small text-muted mb-1 fw-bold">Point #</label>
                                            <input type="text" class="form-control form-control-sm rounded-3 text-center fw-bold bg-white syllabus-point-num" value="Point 2" readonly>
                                        </div>
                                        <div class="col-md-4 col-9">
                                            <label class="form-label extra-small text-muted mb-1 fw-bold">Topic / Module Title</label>
                                            <input type="text" class="form-control form-control-sm rounded-3 bg-white syllabus-title-input" 
                                                   value="Practical Application & Case Studies" placeholder="e.g. Module 2: Case Studies">
                                        </div>
                                        <div class="col-md-5 col-10">
                                            <label class="form-label extra-small text-muted mb-1 fw-bold">Highlights / Description</label>
                                            <input type="text" class="form-control form-control-sm rounded-3 bg-white syllabus-desc-input" 
                                                   value="Interactive workshops, real case analysis, and group presentations." placeholder="Key learning outcomes...">
                                        </div>
                                        <div class="col-md-1 col-2 text-end">
                                            <label class="form-label extra-small text-muted mb-1 d-none d-md-block">&nbsp;</label>
                                            <button type="button" class="btn btn-sm btn-icon btn-outline-danger rounded-3 btn-remove-point" title="Remove point">
                                                <i class="ti ti-trash fs-2"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    {{-- 4. Overview & Objectives --}}
                    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                        <div class="card-header bg-white border-bottom border-light-subtle py-3 px-4">
                            <h5 class="card-title fw-bold text-dark mb-0 d-inline-flex align-items-center gap-2">
                                <i class="ti ti-file-text text-primary fs-2"></i>
                                <span>Course Overview & Objectives</span>
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <textarea id="inputTrainingDescription" class="form-control rounded-3" rows="4" 
                                      placeholder="Provide an overview of the training goals, prerequisites, target participants, and intended outcomes..."></textarea>
                        </div>
                    </div>

                </div>

                {{-- RIGHT COLUMN: Publishing Settings & Quick Summary --}}
                <div class="col-lg-4 col-12">
                    
                    {{-- Capacity & Registration Settings --}}
                    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                        <div class="card-header bg-white border-bottom border-light-subtle py-3 px-4">
                            <h5 class="card-title fw-bold text-dark mb-0 d-inline-flex align-items-center gap-2">
                                <i class="ti ti-settings text-primary fs-2"></i>
                                <span>Registration & Quota</span>
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark required small">Participant Quota (Seats)</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon"><i class="ti ti-users text-muted"></i></span>
                                    <input type="number" id="inputTrainingQuota" class="form-control rounded-3" value="30" min="1" max="500" required>
                                </div>
                                <div class="form-text extra-small">Maximum number of approved participants allowed.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark required small">Registration Status</label>
                                <select id="inputTrainingStatus" class="form-select rounded-3" required>
                                    <option value="open" selected>Open Registration (Active)</option>
                                    <option value="draft">Draft (Hidden from Users)</option>
                                    <option value="closed">Closed Registration</option>
                                </select>
                            </div>

                            <div class="border rounded-3 p-3 bg-light-subtle mt-4">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="ti ti-shield-check text-success fs-2"></i>
                                    <span class="fw-bold text-dark small">PTKP Approval Workflow</span>
                                </div>
                                <p class="text-muted extra-small mb-0">
                                    Employee applications will be collected in the Participant Tracking list and will require HR/Lead approval before seat confirmation.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Actions Card --}}
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary py-2-5 rounded-3 fw-semibold d-inline-flex align-items-center justify-content-center gap-2" 
                                    style="background-color: #003b6d; border-color: #003b6d;">
                                <i class="ti ti-check fs-2"></i>
                                <span>Save Training Schedule</span>
                            </button>
                            <a href="{{ url('/manage/training-schedule') }}" class="btn btn-outline-secondary py-2-5 rounded-3 fw-semibold">
                                Cancel
                            </a>
                        </div>
                    </div>

                </div>

            </div>
        </form>
    </div>
</div>
@endsection

@section('style')
<style>
    .required:after {
        content: " *";
        color: #dc3545;
        font-weight: bold;
    }
    .btn-icon {
        width: 32px;
        height: 32px;
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
    const btnAddPoint = document.getElementById('btnAddSyllabusPoint');
    const pointsContainer = document.getElementById('syllabusPointsContainer');

    // Renumber syllabus points and manage delete button states
    function renumberPoints() {
        if (!pointsContainer) return;
        const cards = pointsContainer.querySelectorAll('.syllabus-point-card');
        cards.forEach((card, idx) => {
            const numInput = card.querySelector('.syllabus-point-num');
            if (numInput) {
                numInput.value = `Point ${idx + 1}`;
            }
            const delBtn = card.querySelector('.btn-remove-point');
            if (delBtn) {
                delBtn.disabled = (cards.length === 1 && idx === 0);
            }
        });
    }

    // Add new syllabus point
    if (btnAddPoint && pointsContainer) {
        btnAddPoint.addEventListener('click', function () {
            const currentCount = pointsContainer.querySelectorAll('.syllabus-point-card').length;
            const newIndex = currentCount + 1;
            const newCard = document.createElement('div');
            newCard.className = 'card border border-light-subtle rounded-3 p-3 bg-light-subtle syllabus-point-card shadow-none';
            newCard.innerHTML = `
                <div class="row g-2 align-items-center">
                    <div class="col-md-2 col-3">
                        <label class="form-label extra-small text-muted mb-1 fw-bold">Point #</label>
                        <input type="text" class="form-control form-control-sm rounded-3 text-center fw-bold bg-white syllabus-point-num" value="Point ${newIndex}" readonly>
                    </div>
                    <div class="col-md-4 col-9">
                        <label class="form-label extra-small text-muted mb-1 fw-bold">Topic / Module Title</label>
                        <input type="text" class="form-control form-control-sm rounded-3 bg-white syllabus-title-input" placeholder="e.g. Advanced Practical Scenarios">
                    </div>
                    <div class="col-md-5 col-10">
                        <label class="form-label extra-small text-muted mb-1 fw-bold">Highlights / Description</label>
                        <input type="text" class="form-control form-control-sm rounded-3 bg-white syllabus-desc-input" placeholder="Key learning outcomes...">
                    </div>
                    <div class="col-md-1 col-2 text-end">
                        <label class="form-label extra-small text-muted mb-1 d-none d-md-block">&nbsp;</label>
                        <button type="button" class="btn btn-sm btn-icon btn-outline-danger rounded-3 btn-remove-point" title="Remove point">
                            <i class="ti ti-trash fs-2"></i>
                        </button>
                    </div>
                </div>
            `;
            pointsContainer.appendChild(newCard);
            renumberPoints();
        });

        // Delegate remove point click
        pointsContainer.addEventListener('click', function (e) {
            const delBtn = e.target.closest('.btn-remove-point');
            if (delBtn && !delBtn.disabled) {
                const card = delBtn.closest('.syllabus-point-card');
                if (card) {
                    card.remove();
                    renumberPoints();
                }
            }
        });
    }

    // Form submission
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            if (!form.checkValidity()) {
                e.stopPropagation();
                form.classList.add('was-validated');
                return;
            }

            const title = document.getElementById('inputTrainingTitle')?.value || 'Training Schedule';

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Training Schedule Saved!',
                    text: `"${title}" has been successfully created and published.`,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = "{{ url('/manage/training-schedule') }}";
                });
            } else {
                alert(`Training "${title}" has been successfully created!`);
                window.location.href = "{{ url('/manage/training-schedule') }}";
            }
        });
    }
});
</script>
@endpush
