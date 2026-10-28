{{-- Shared create & edit form — task type aware, streamlined (2026-10-01).
    6 types: File Submission | Course Assignment | Assessment/Quiz | Survey | Essay/Reflection | Action Plan
    NOTE: this is a partial included via @include, so it must NOT @extend any layout. --}}
@php
    $defaultParentType = request('parent_type')
        ?? (request('scope') === 'non_kp' ? 'offline_training' : 'playlist');
    if (! array_key_exists($defaultParentType, \App\Models\Task::PARENT_TYPES)) {
        $defaultParentType = 'playlist';
    }
    $currentType = old('type', $task->type ?? 'action_plan');
    $currentLinkType = old('link_type', $task->link_type ?? 'lms');
    $currentParentType = old('parent_type', $task->parent_type ?? ($lockedParent['type'] ?? $defaultParentType));
    $currentParentId = old('parent_id', isset($task) ? $task->parentId() : ($lockedParent['id'] ?? ''));
    $currentSurveyId = old('survey_id', $task->survey_id ?? '');
    $linkedCourse = old('linked_training_plan_id', $task->linked_training_plan_id ?? '');
    $isLocked = !empty($lockedParent);
@endphp
<form method="POST"
      action="{{ isset($task) ? route('manage.task.update', $task) : route('manage.task.store') }}"
      enctype="multipart/form-data"
      id="taskForm">
    @csrf
    @if (isset($task))
        @method('PUT')
    @endif

    <style>
        .form-section-card {
            border: 1px solid #e2e8f0 !important;
            border-left: 6px solid transparent !important;
            border-radius: 16px !important;
            background: #ffffff !important;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
            margin-bottom: 1.5rem;
            transition: border-color 0.25s ease, border-left-color 0.25s ease, box-shadow 0.25s ease;
            position: relative;
        }
        .form-section-card:not(.is-active):hover {
            border-color: #cbd5e1 !important;
            border-left-color: #cbd5e1 !important;
        }
        .form-section-card.is-active {
            border-color: #cbd5e1 #cbd5e1 #cbd5e1 var(--tblr-primary, #206bc4) !important;
            border-left: 6px solid var(--tblr-primary, #206bc4) !important;
            box-shadow: 0 10px 25px -5px rgba(32, 107, 196, 0.12), 0 8px 10px -6px rgba(32, 107, 196, 0.08), 0 2px 6px rgba(0, 0, 0, 0.04) !important;
        }
        .form-section-card .card-header {
            border-bottom: 1px solid #f1f5f9 !important;
            transition: background-color 0.25s ease;
        }
        .form-section-card.is-active .card-header {
            background-color: rgba(32, 107, 196, 0.03) !important;
        }
        .section-badge-num {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            background-color: #e0f2fe;
            color: #0284c7;
            font-weight: 800;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.25s ease;
        }
        .form-section-card.is-active .section-badge-num {
            background-color: var(--tblr-primary, #206bc4) !important;
            color: #ffffff !important;
            box-shadow: 0 2px 8px rgba(32, 107, 196, 0.35);
            transform: scale(1.05);
        }
        .section-active-indicator {
            display: none !important;
        }
        .form-section-card.is-active .section-active-indicator {
            display: inline-flex !important;
            animation: activeIndicatorFade 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes activeIndicatorFade {
            from {
                opacity: 0;
                transform: translateY(-2px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        .form-field-sm {
            max-width: 320px;
        }
        .form-field-md {
            max-width: 440px;
        }
        .form-field-lg {
            max-width: 600px;
        }
        .form-field-xl {
            max-width: 740px;
        }
        .field-card:not(.field-card-clean) {
            border: 1px solid #e2e8f0 !important;
            border-left: 4px solid transparent !important;
            border-radius: 12px !important;
            background: #ffffff !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
            transition: all 0.2s ease;
            max-width: 820px;
        }
        .field-card:not(.field-card-clean):hover {
            border-color: #cbd5e1 !important;
            border-left-color: #cbd5e1 !important;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
        }
        .field-card:not(.field-card-clean):focus-within {
            border-color: #cbd5e1 !important;
            border-left: 4px solid var(--tblr-primary, #206bc4) !important;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
        }
        .field-card.field-card-clean {
            border: none !important;
            border-left: none !important;
            background: transparent !important;
            box-shadow: none !important;
            padding: 0 !important;
            margin: 0 !important;
            border-radius: 0 !important;
            max-width: 740px;
        }
        .field-card.field-card-clean:hover,
        .field-card.field-card-clean:focus-within {
            border: none !important;
            border-left: none !important;
            box-shadow: none !important;
            background: transparent !important;
        }
        [data-bs-theme="dark"] .field-card.field-card-clean,
        body.dark-mode .field-card.field-card-clean,
        body.theme-dark .field-card.field-card-clean {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
        }

        /* Google Forms style for Essay question in Section 4 */
        .gform-essay-block {
            max-width: 740px;
        }
        .gform-question-input {
            background-color: #f8fafc !important;
            border: none !important;
            border-bottom: 1.5px solid #64748b !important;
            border-radius: 4px 4px 0 0 !important;
            padding: 13px 16px !important;
            font-size: 1.05rem !important;
            font-weight: 500 !important;
            color: var(--tblr-body-color, #1e293b) !important;
            width: 100% !important;
            box-shadow: none !important;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }
        .gform-question-input:focus {
            background-color: #f1f5f9 !important;
            border-bottom: 2px solid var(--tblr-primary, #206bc4) !important;
            box-shadow: none !important;
            outline: none !important;
        }
        .gform-question-input.is-invalid {
            border-bottom: 2px solid var(--tblr-danger, #d63939) !important;
            background-color: rgba(214, 57, 57, 0.04) !important;
        }
        .gform-question-input::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }
        .gform-answer-preview {
            padding: 4px 2px;
        }
        .gform-answer-text {
            font-size: 0.95rem;
            color: #64748b;
            user-select: none;
            margin-bottom: 6px;
        }
        .gform-answer-line {
            border-bottom: 1.5px dotted #94a3b8;
            max-width: 420px;
            height: 1px;
        }
        [data-bs-theme="dark"] .gform-question-input,
        body.dark-mode .gform-question-input,
        body.theme-dark .gform-question-input {
            background-color: rgba(255, 255, 255, 0.05) !important;
            border-bottom-color: rgba(255, 255, 255, 0.25) !important;
            color: #f8fafc !important;
        }
        [data-bs-theme="dark"] .gform-question-input:focus,
        body.dark-mode .gform-question-input:focus,
        body.theme-dark .gform-question-input:focus {
            background-color: rgba(255, 255, 255, 0.08) !important;
            border-bottom-color: var(--tblr-primary, #38bdf8) !important;
        }
        [data-bs-theme="dark"] .gform-answer-text,
        body.dark-mode .gform-answer-text,
        body.theme-dark .gform-answer-text {
            color: #94a3b8;
        }
        [data-bs-theme="dark"] .gform-answer-line,
        body.dark-mode .gform-answer-line,
        body.theme-dark .gform-answer-line {
            border-bottom-color: #64748b;
        }

        /* Google Forms options input style */
        .gform-opt-input {
            border: none !important;
            border-bottom: 1.5px solid #cbd5e1 !important;
            border-radius: 0 !important;
            padding: 6px 4px !important;
            background: transparent !important;
            box-shadow: none !important;
            font-size: 0.95rem !important;
            color: var(--tblr-body-color, #1e293b) !important;
            transition: border-color 0.2s ease;
        }
        .gform-opt-input:focus {
            border-bottom-color: var(--tblr-primary, #206bc4) !important;
            outline: none !important;
            box-shadow: none !important;
        }
        .gform-opt-input::placeholder {
            color: #94a3b8;
        }
        [data-bs-theme="dark"] .gform-opt-input,
        body.dark-mode .gform-opt-input,
        body.theme-dark .gform-opt-input {
            border-bottom-color: rgba(255, 255, 255, 0.2) !important;
            color: #f8fafc !important;
        }
        [data-bs-theme="dark"] .gform-opt-input:focus,
        body.dark-mode .gform-opt-input:focus,
        body.theme-dark .gform-opt-input:focus {
            border-bottom-color: var(--tblr-primary, #38bdf8) !important;
        }

        [data-bs-theme="dark"] .form-section-card,
        body.dark-mode .form-section-card,
        body.theme-dark .form-section-card {
            background: #1e293b !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
            border-left-color: transparent !important;
        }
        [data-bs-theme="dark"] .form-section-card:not(.is-active):hover,
        body.dark-mode .form-section-card:not(.is-active):hover,
        body.theme-dark .form-section-card:not(.is-active):hover {
            border-color: rgba(255, 255, 255, 0.2) !important;
            border-left-color: rgba(255, 255, 255, 0.2) !important;
        }
        [data-bs-theme="dark"] .form-section-card.is-active,
        body.dark-mode .form-section-card.is-active,
        body.theme-dark .form-section-card.is-active {
            background: #1e293b !important;
            border-color: rgba(255, 255, 255, 0.18) !important;
            border-left: 6px solid var(--tblr-primary, #38bdf8) !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.45) !important;
        }
        [data-bs-theme="dark"] .form-section-card.is-active .card-header,
        body.dark-mode .form-section-card.is-active .card-header,
        body.theme-dark .form-section-card.is-active .card-header {
            background: rgba(56, 189, 248, 0.06) !important;
        }
        [data-bs-theme="dark"] .section-badge-num,
        body.dark-mode .section-badge-num,
        body.theme-dark .section-badge-num {
            background-color: rgba(14, 165, 233, 0.15) !important;
            color: #38bdf8 !important;
        }
        [data-bs-theme="dark"] .form-section-card.is-active .section-badge-num,
        body.dark-mode .form-section-card.is-active .section-badge-num,
        body.theme-dark .form-section-card.is-active .section-badge-num {
            background-color: #0284c7 !important;
            color: #ffffff !important;
        }
        [data-bs-theme="dark"] .field-card,
        body.dark-mode .field-card,
        body.theme-dark .field-card {
            background: #0f172a !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
            border-left-color: transparent !important;
        }
        [data-bs-theme="dark"] .field-card:focus-within,
        body.dark-mode .field-card:focus-within,
        body.theme-dark .field-card:focus-within {
            border-color: rgba(255, 255, 255, 0.18) !important;
            border-left-color: var(--tblr-primary, #38bdf8) !important;
        }

        /* Red highlight for unfilled required columns on submit */
        .form-control.is-invalid,
        .form-select.is-invalid,
        .picker-btn-invalid {
            border-color: #dc2626 !important;
            background-color: #fef2f2 !important;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.15) !important;
        }
        .form-control.is-invalid:focus,
        .form-select.is-invalid:focus,
        .picker-btn-invalid:focus {
            border-color: #b91c1c !important;
            box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.25) !important;
        }
        [data-bs-theme="dark"] .form-control.is-invalid,
        [data-bs-theme="dark"] .form-select.is-invalid,
        [data-bs-theme="dark"] .picker-btn-invalid,
        body.dark-mode .form-control.is-invalid,
        body.dark-mode .form-select.is-invalid,
        body.dark-mode .picker-btn-invalid,
        body.theme-dark .form-control.is-invalid,
        body.theme-dark .form-select.is-invalid,
        body.theme-dark .picker-btn-invalid {
            border-color: #ef4444 !important;
            background-color: rgba(239, 68, 68, 0.12) !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.25) !important;
        }
    </style>

    @if ($isLocked)
        <input type="hidden" name="parent_type" value="{{ $lockedParent['type'] }}">
        <input type="hidden" name="parent_id" value="{{ $lockedParent['id'] }}">
    @else
        {{-- ======================================================== --}}
        {{-- SECTION 1: TASK PLACEMENT (UNLOCKED ONLY)                 --}}
        {{-- ======================================================== --}}
        <div class="card form-section-card is-active" data-section="1">
            <div class="card-header bg-white py-3 px-4 d-flex align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="section-badge-num"><i class="ti ti-map-pin"></i></span>
                    <div>
                        <h3 class="card-title fw-bold text-dark mb-0">1. Task Placement</h3>
                        <div class="text-muted small">Select where this task will be placed and assigned to learners.</div>
                    </div>
                </div>
                <span class="badge bg-primary text-white fw-semibold px-2.5 py-1 rounded-pill section-active-indicator align-items-center gap-1 shadow-sm">
                    <i class="ti ti-pencil"></i> Active Section
                </span>
            </div>
            <div class="card-body p-4">
                {{-- Placement Category --}}
                <div class="mb-3 form-field-md">
                    <label class="form-label required" for="parent_type">Placement Category</label>
                    <select class="form-select @error('parent_type') is-invalid @enderror"
                            id="parent_type" name="parent_type" required>
                        @foreach (\App\Models\Task::PARENT_TYPES as $key => $label)
                            <option value="{{ $key }}" {{ $currentParentType === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-hint mt-1">Choose whether this task belongs to a Playlist, Content, Offline Training, or Others.</div>
                    @error('parent_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                {{-- Target Placement Picker --}}
                @php
                    $currentPlacementLabel = \App\Models\Task::PARENT_TYPES[$currentParentType] ?? 'Playlist';
                @endphp
                <div class="mb-3 form-field-lg">
                    <label class="form-label required" id="parentPickerLabel">Select {{ $currentPlacementLabel }}</label>
                    <div class="position-relative" id="parentPicker">
                        <input type="hidden" name="parent_id" id="parent_id" value="{{ $currentParentId }}"
                               data-url-template="{{ route('manage.task.parents', ['type' => '__TYPE__']) }}">
                        <button type="button"
                                class="form-select text-start d-flex justify-content-between align-items-center"
                                id="parentPickerBtn" aria-haspopup="listbox" aria-expanded="false">
                            <span id="parentPickerText">{{ $selectedParentName ?: ('— Select ' . $currentPlacementLabel . ' —') }}</span>
                            <i class="ti ti-chevron-down ms-2"></i>
                        </button>
                        <div class="card position-absolute w-100 shadow mt-1" id="parentPanel"
                             style="display:none; z-index:1050;">
                            <div class="p-2 border-bottom">
                                <input type="search" id="parentPanelSearch" class="form-control"
                                       placeholder="Type to search..." autocomplete="off">
                            </div>
                            <div id="parentPanelList" class="overflow-auto" style="max-height:240px;" role="listbox"></div>
                        </div>
                    </div>
                    <div class="invalid-feedback" id="parentIdError" style="display:none">Please select the target item where this task will be placed.</div>
                    @error('parent_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                {{-- Others helper notice when Standalone is chosen --}}
                <div id="standalonePlacementNotice" class="alert alert-info py-2 px-3 rounded-3 form-field-lg mb-0" style="{{ $currentParentType === 'standalone' ? '' : 'display:none' }}">
                    <i class="ti ti-world me-1"></i> Others is open to all learners and not tied to a specific playlist or course. You can configure an optional access passcode at the bottom of the form.
                </div>
            </div>
        </div>
    @endif

    {{-- ======================================================== --}}
    {{-- SECTION 2: BASIC TASK INFORMATION                        --}}
    {{-- ======================================================== --}}
    <div class="card form-section-card {{ $isLocked ? 'is-active' : '' }}" data-section="{{ $isLocked ? '1' : '2' }}">
        <div class="card-header bg-white py-3 px-4 d-flex align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <span class="section-badge-num"><i class="ti ti-file-text"></i></span>
                <div>
                    <h3 class="card-title fw-bold text-dark mb-0">{{ $isLocked ? '1. ' : '2. ' }}Basic Task Information</h3>
                    <div class="text-muted small">Define the task type, title, and instructions for learners.</div>
                </div>
            </div>
            <span class="badge bg-primary text-white fw-semibold px-2.5 py-1 rounded-pill section-active-indicator align-items-center gap-1 shadow-sm">
                <i class="ti ti-pencil"></i> Active Section
            </span>
        </div>
        <div class="card-body p-4">
            {{-- Task Type --}}
            <div class="mb-3 form-field-md">
                <label class="form-label required" for="type">Task Type</label>
                <select class="form-select @error('type') is-invalid @enderror"
                        id="type" name="type" required>
                    @foreach (\App\Models\Task::TYPES as $key => $label)
                        <option value="{{ $key }}" {{ $currentType === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <div class="form-hint mt-1" id="typeHint"></div>
            </div>

            {{-- Task Title --}}
            <div class="mb-3 form-field-lg">
                <label class="form-label required" for="title">Task Title</label>
                <input type="text" class="form-control @error('title') is-invalid @enderror"
                       id="title" name="title"
                       value="{{ old('title', $task->title ?? '') }}"
                       placeholder="e.g. Safety Field Observation Report & Workshop Practice" required>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- Description & Instructions --}}
            <div class="form-field-xl">
                <label class="form-label" for="description">Description & Instructions</label>
                <textarea class="form-control @error('description') is-invalid @enderror"
                          id="description" name="description"
                          rows="3"
                          placeholder="Provide clear guidelines, requirements, or submission instructions for learners...">{{ old('description', $task->description ?? '') }}</textarea>
                @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    {{-- ======================================================== --}}
    {{-- SECTION 3: TIMELINE & SUPPORTING MATERIALS               --}}
    {{-- ======================================================== --}}
    <div class="card form-section-card" data-section="{{ $isLocked ? '2' : '3' }}">
        <div class="card-header bg-white py-3 px-4 d-flex align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <span class="section-badge-num"><i class="ti ti-calendar-time"></i></span>
                <div>
                    <h3 class="card-title fw-bold text-dark mb-0" id="section3Title">{{ $isLocked ? '2. ' : '3. ' }}{{ $currentType === 'file_submission' ? 'Timeline & Supporting Files' : 'Timeline' }}</h3>
                    <div class="text-muted small" id="section3Subtitle">{{ $currentType === 'file_submission' ? 'Set the submission deadline and attach assignment templates or guidance materials (optional).' : 'Set the submission deadline for learners (optional).' }}</div>
                </div>
            </div>
            <span class="badge bg-primary text-white fw-semibold px-2.5 py-1 rounded-pill section-active-indicator align-items-center gap-1 shadow-sm">
                <i class="ti ti-pencil"></i> Active Section
            </span>
        </div>
        <div class="card-body p-4">
            {{-- Submission Deadline --}}
            <div class="mb-3 form-field-sm">
                <label class="form-label" for="deadline">Submission Deadline</label>
                <div class="input-icon">
                    <span class="input-icon-addon">
                        <i class="ti ti-calendar-event"></i>
                    </span>
                    <input type="datetime-local" class="form-control @error('deadline') is-invalid @enderror"
                           id="deadline" name="deadline"
                           value="{{ old('deadline', isset($task) && $task->deadline ? $task->deadline->format('Y-m-d\TH:i') : '') }}">
                </div>
                <small class="text-muted d-block mt-1">Leave blank if this task has no submission deadline.</small>
                @error('deadline')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>

            {{-- Attachment / Task Template (Only for File Submission) --}}
            <div class="form-field-md" id="attachmentWrapper" style="{{ $currentType === 'file_submission' ? '' : 'display:none;' }}">
                <label class="form-label" for="attachment">Attachment / Task Template (Optional)</label>
                <input type="file" class="form-control @error('attachment') is-invalid @enderror"
                       id="attachment" name="attachment" {{ $currentType === 'file_submission' ? '' : 'disabled' }}>
                @if (isset($task) && $task->attachment)
                    <div class="form-hint mt-1 d-flex align-items-center gap-1">
                        <i class="ti ti-file-check text-success"></i>
                        <span>Saved file:</span>
                        <a href="{{ asset('storage/' . $task->attachment) }}" target="_blank" class="fw-semibold">View File</a>
                    </div>
                @else
                    <small class="text-muted d-block mt-1">PDF, DOCX, ZIP, XLSX up to 5 MB as a reference for learners.</small>
                @endif
                @error('attachment')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    {{-- ======================================================== --}}
    {{-- SECTION 4: SUBMISSION SETUP / QUESTIONS                  --}}
    {{-- ======================================================== --}}
    {{-- ── 4A: Course Link section (only for Course Assignment) ── --}}
    <div id="linkSection" class="card form-section-card" data-section="{{ $isLocked ? '3' : '4' }}" style="display:none">
        <div class="card-header bg-white py-3 px-4 d-flex align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <span class="section-badge-num"><i class="ti ti-link"></i></span>
                <div>
                    <h3 class="card-title fw-bold text-dark mb-0">{{ $isLocked ? '3. ' : '4. ' }}Target Course Assignment</h3>
                    <div class="text-muted small">Specify the LMS course or external course URL that learners must complete.</div>
                </div>
            </div>
            <span class="badge bg-primary text-white fw-semibold px-2.5 py-1 rounded-pill section-active-indicator align-items-center gap-1 shadow-sm">
                <i class="ti ti-pencil"></i> Active Section
            </span>
        </div>
        <div class="card-body p-4">
            <div class="mb-3 form-field-lg">
                <label class="form-label fw-semibold">Course Destination Type</label>
                <div class="d-flex flex-column gap-2">
                    <label class="form-check mb-0">
                        <input type="radio" class="form-check-input" name="link_type" value="lms"
                               {{ $currentLinkType === 'lms' ? 'checked' : '' }}>
                        <span class="form-check-label fw-semibold">Course in OWL LMS (Auto-Complete 100%)</span>
                    </label>
                    <label class="form-check mb-0">
                        <input type="radio" class="form-check-input" name="link_type" value="external"
                               {{ $currentLinkType === 'external' ? 'checked' : '' }}>
                        <span class="form-check-label fw-semibold">External Course Link (URL)</span>
                    </label>
                </div>
                @error('link_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>

            <div id="linkLmsWrap" class="form-field-lg">
                <label class="form-label required" for="linked_training_plan_id">Target Course in LMS</label>
                <select class="form-select d-none @error('linked_training_plan_id') is-invalid @enderror"
                        id="linked_training_plan_id" name="linked_training_plan_id" tabindex="-1" aria-hidden="true">
                    <option value="">— Select Target Course —</option>
                    @foreach ($trainingPlans as $trainingPlan)
                        <option value="{{ $trainingPlan->id }}"
                            {{ (string) $linkedCourse === (string) $trainingPlan->id ? 'selected' : '' }}>
                            {{ $trainingPlan->title }}
                        </option>
                    @endforeach
                </select>
                <div class="position-relative" id="coursePicker">
                    <button type="button"
                            class="form-select text-start d-flex justify-content-between align-items-center"
                            id="coursePickerBtn" aria-haspopup="listbox" aria-expanded="false">
                        <span id="coursePickerText">— Select Target Course —</span>
                        <i class="ti ti-chevron-down ms-2"></i>
                    </button>
                    <div class="card position-absolute w-100 shadow mt-1" id="coursePanel"
                         style="display:none; z-index:1050;">
                        <div class="p-2 border-bottom">
                            <input type="search" id="coursePanelSearch" class="form-control"
                                   placeholder="Type course name to search..." autocomplete="off">
                        </div>
                        <div id="coursePanelList" class="overflow-auto" style="max-height:240px;" role="listbox"></div>
                    </div>
                </div>
                <div class="invalid-feedback" id="courseIdError" style="display:none">Please select the target course in LMS.</div>
                @error('linked_training_plan_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>

            <div id="linkExternalWrap" class="form-field-lg" style="display:none">
                <label class="form-label required" for="external_url">External Course URL</label>
                <input type="url" class="form-control @error('external_url') is-invalid @enderror"
                       id="external_url" name="external_url"
                       placeholder="https://example.com/course/..."
                       value="{{ old('external_url', $task->external_url ?? '') }}">
                <small class="text-muted d-block mt-1">Enter complete URL including https://</small>
                @error('external_url')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    {{-- ── 4B: Target Survey section (only for Survey) ── --}}
    <div id="surveySection" class="card form-section-card" data-section="{{ $isLocked ? '3' : '4' }}" style="display:none">
        <div class="card-header bg-white py-3 px-4 d-flex align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <span class="section-badge-num"><i class="ti ti-forms"></i></span>
                <div>
                    <h3 class="card-title fw-bold text-dark mb-0">{{ $isLocked ? '3. ' : '4. ' }}Target Survey Assignment</h3>
                    <div class="text-muted small">Select the survey form that learners must complete for this task.</div>
                </div>
            </div>
            <span class="badge bg-primary text-white fw-semibold px-2.5 py-1 rounded-pill section-active-indicator align-items-center gap-1 shadow-sm">
                <i class="ti ti-pencil"></i> Active Section
            </span>
        </div>
        <div class="card-body p-4">
            <div class="form-field-md">
                <label class="form-label" for="survey_id">
                    Survey Form
                    <span class="badge bg-secondary-lt text-secondary fw-semibold ms-1">Available Soon</span>
                </label>
                <select class="form-select @error('survey_id') is-invalid @enderror"
                        id="survey_id" name="survey_id">
                    <option value="">— Select Survey (No surveys available yet) —</option>
                    @if ($currentSurveyId)
                        <option value="{{ $currentSurveyId }}" selected>Survey #{{ $currentSurveyId }}</option>
                    @endif
                </select>
                <div class="form-hint mt-2 text-muted">
                    <i class="ti ti-info-circle text-primary me-1"></i>
                    The survey dropdown is ready for integration. Once the Survey module is active, published surveys will automatically appear here for selection.
                </div>
                @error('survey_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    {{-- ── 4C: Questions section (for types with question builder) ── --}}
    <div id="builderSection" class="card form-section-card" data-section="{{ $isLocked ? '3' : '4' }}">
        <div class="card-header bg-white py-3 px-4 d-flex align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <span class="section-badge-num"><i class="ti ti-list-check" id="builderBadgeIcon"></i></span>
                <div>
                    <h3 class="card-title fw-bold text-dark mb-0" id="builderTitle">{{ $isLocked ? '3. ' : '4. ' }}Questions & Submission Form Builder</h3>
                    <div class="text-muted small" id="builderHint"></div>
                </div>
            </div>
            <span class="badge bg-primary text-white fw-semibold px-2.5 py-1 rounded-pill section-active-indicator align-items-center gap-1 shadow-sm">
                <i class="ti ti-pencil"></i> Active Section
            </span>
        </div>
        <div class="card-body p-4">
            <div id="fieldsWrap"></div>

            <div id="addQuestionWrap" class="mt-3" style="display:none;">
                <button type="button" class="btn btn-outline-primary rounded-3 px-3 d-inline-flex align-items-center gap-2" id="btnAddField">
                    <i class="ti ti-plus"></i> Add Question
                </button>
            </div>

            @error('fields')
                <div class="alert alert-danger mt-2">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- ======================================================== --}}
    {{-- ACCESS CODE SETTING (Only for Global Task / Standalone)   --}}
    {{-- ======================================================== --}}
    <div id="accessCodeWrap" class="card form-section-card" data-section="{{ $isLocked ? '4' : '5' }}" style="{{ $currentParentType === 'standalone' ? '' : 'display:none' }}">
        <div class="card-header bg-white py-3 px-4 d-flex align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <span class="section-badge-num"><i class="ti ti-key"></i></span>
                <div>
                    <h3 class="card-title fw-bold text-dark mb-0">{{ $isLocked ? '4. ' : '5. ' }}Access & Security (Optional)</h3>
                    <div class="text-muted small">Configure an optional access passcode to unlock this task.</div>
                </div>
            </div>
            <span class="badge bg-primary text-white fw-semibold px-2.5 py-1 rounded-pill section-active-indicator align-items-center gap-1 shadow-sm">
                <i class="ti ti-pencil"></i> Active Section
            </span>
        </div>
        <div class="card-body p-4">
            <div class="form-field-sm">
                <label class="form-label" for="access_code">Unique Access Code</label>
                <input type="text" name="access_code" id="access_code"
                       class="form-control text-uppercase @error('access_code') is-invalid @enderror"
                       value="{{ old('access_code', $task->access_code ?? '') }}"
                       maxlength="8" placeholder="e.g. TASK01"
                       autocomplete="off">
                <small class="text-muted d-block mt-2">Optional (max 8 characters). If left blank, learners can access this task directly via secret link or QR code.</small>
                @error('access_code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    {{-- ======================================================== --}}
    {{-- SECTION 6: ACTION BAR / FOOTER                           --}}
    {{-- ======================================================== --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 p-3 bg-white">
        <div class="d-flex align-items-center justify-content-end flex-wrap gap-2">
            <a href="{{ route('manage.task.index') }}" class="btn btn-outline-secondary rounded-3 px-3">Cancel</a>

            <button type="button" class="btn btn-outline-dark rounded-3 px-3" id="btnPreviewTask">
                <i class="ti ti-eye me-1"></i> Preview
            </button>

            @if (isset($task))
                <button type="submit" class="btn btn-primary rounded-3 px-4">
                    <i class="ti ti-device-floppy me-1"></i> Save Changes
                </button>
            @else
                <button type="submit" name="action" value="draft" class="btn btn-outline-secondary rounded-3 px-3" title="Save as draft and stay on this page to create another task">
                    <i class="ti ti-device-floppy me-1"></i> Save as Draft
                </button>
                <button type="submit" name="action" value="draft_exit" class="btn btn-warning text-dark fw-semibold rounded-3 px-3 shadow-sm" title="Save as draft and return to task list">
                    <i class="ti ti-file-export me-1"></i> Save as Draft & Exit
                </button>
                <button type="submit" name="action" value="publish" class="btn btn-primary rounded-3 px-4">
                    <i class="ti ti-send me-1"></i> Publish Task
                </button>
            @endif
        </div>
    </div>
</form>

{{-- ======================================================== --}}
{{-- MODAL: LEARNER TASK PREVIEW                              --}}
{{-- ======================================================== --}}
<div class="modal modal-blur fade" id="taskPreviewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">
            <div class="modal-header bg-light py-2.5 px-4 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary text-white p-1.5 rounded-3"><i class="ti ti-eye fs-2"></i></span>
                    <div>
                        <h4 class="modal-title fw-bold text-dark mb-0">Learner View Preview</h4>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    {{-- Zoom Controls --}}
                    <div class="btn-group btn-group-sm shadow-none border rounded-2 bg-white" role="group" id="taskPreviewZoomToolbar" aria-label="Zoom controls">
                        <button type="button" class="btn btn-sm btn-ghost-secondary px-2 py-1" id="taskPreviewZoomOutBtn" title="Zoom Out (-)">
                            <i class="ti ti-zoom-out" style="font-size: 14px;"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-ghost-secondary px-2 py-1 fw-bold text-dark" id="taskPreviewZoomResetBtn" title="Reset Zoom (100%)" style="min-width: 48px; font-size: 11.5px;">
                            <span id="taskPreviewZoomLevel">100%</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-ghost-secondary px-2 py-1" id="taskPreviewZoomInBtn" title="Zoom In (+)">
                            <i class="ti ti-zoom-in" style="font-size: 14px;"></i>
                        </button>
                    </div>

                    {{-- Clean & Aligned Close Button --}}
                    <button type="button"
                            class="btn btn-sm btn-icon btn-ghost-secondary rounded-circle ms-1"
                            data-bs-dismiss="modal"
                            aria-label="Close"
                            title="Close Preview"
                            style="width: 32px; height: 32px; border: none; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="ti ti-x text-muted" style="font-size: 20px; font-weight: bold; line-height: 1;"></i>
                    </button>
                </div>
            </div>
            <div class="modal-body p-4 bg-light-subtle overflow-auto">
                <div id="taskPreviewZoomWrapper" style="transform-origin: top center; transition: transform 0.15s ease-out; width: 100%;">
                    {{-- User View Card (Identical to Learner My Task Card) --}}
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    {{-- Top Badges Row --}}
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge bg-primary-lt text-primary px-2 py-1 fs-6" id="prevTypeBadge">Task</span>
                            <span class="badge bg-secondary-lt text-secondary px-2 py-1 fs-6" id="prevPlacementBadge">
                                <i class="ti ti-world me-1" id="prevPlacementIcon"></i><span id="prevPlacementText">Others</span>
                            </span>
                        </div>
                        <span class="badge bg-secondary-lt text-secondary px-2 py-1 fs-6" id="prevStatusBadge">Not Started</span>
                    </div>

                    {{-- Title --}}
                    <h2 class="fw-bold text-dark mb-2" id="prevTitle">Task Title</h2>

                    {{-- Meta Row with Divider --}}
                    <div class="d-flex flex-wrap align-items-center gap-3 text-muted small mb-3 pb-3 border-bottom" id="prevMetaRow">
                        <span class="d-flex align-items-center gap-1" id="prevDeadlineWrap">
                            <i class="ti ti-calendar-due text-primary me-1"></i>Deadline: <strong id="prevDeadlineText">No deadline set</strong>
                            <span class="badge bg-danger ms-1" id="prevOverdueBadge" style="display:none;"><i class="ti ti-alert-circle me-1"></i>Overdue</span>
                        </span>
                        <span id="prevAccessCodeWrap" style="display:none;"><i class="ti ti-key text-warning me-1"></i>Access Code: <code id="prevAccessCodeText"></code></span>
                    </div>

                    {{-- Instructions & Overview --}}
                    <div class="mb-4" id="prevDescWrap" style="display:none;">
                        <h5 class="fw-semibold text-dark mb-2 d-flex align-items-center gap-1">
                            <i class="ti ti-notes text-dark"></i> Instructions & Overview
                        </h5>
                        <div class="text-dark" id="prevDescription" style="white-space: pre-wrap; line-height: 1.6; color: #000000;"></div>
                    </div>

                    {{-- Reference Material --}}
                    <div class="mb-4" id="prevAttachmentWrap" style="display:none;">
                        <h5 class="fw-semibold text-dark mb-2 d-flex align-items-center gap-1">
                            <i class="ti ti-paperclip text-primary"></i> Reference Material
                        </h5>
                        <div class="p-3 rounded-3 bg-light border d-inline-flex align-items-center gap-3">
                            <i class="ti ti-file-download text-primary fs-2"></i>
                            <div>
                                <div class="fw-semibold text-dark small" id="prevAttachmentName">Reference file</div>
                                <div class="text-muted" style="font-size:11px;">Learners can download this attachment for guidance.</div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary ms-2" disabled>
                                <i class="ti ti-download me-1"></i> Download File
                            </button>
                        </div>
                    </div>

                    {{-- Submission Section --}}
                    <div class="mb-2" id="prevSubmissionWrap">
                        <div id="prevSubmissionSection"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white py-3 px-4 border-top d-flex justify-content-end align-items-center gap-2">
                <button type="button" class="btn btn-outline-secondary rounded-3 px-3" data-bs-dismiss="modal">
                    <i class="ti ti-arrow-left me-1"></i> Back to Edit
                </button>
            </div>
        </div>
    </div>
</div>

@once
<script>
    (function () {
        var FIELD_TYPES   = @json(\App\Models\TaskField::TYPES);
        var TASK_TYPES    = @json(\App\Models\Task::TYPES);
        var FIELD_BY_TASK = @json(\App\Models\Task::FIELD_TYPES_BY_TASK);
        // Types without a question builder (their cards would sit hidden and
        // their `required` inputs would silently block form submission).
        function typeHasBuilder(t) { return t !== 'course_assignment' && t !== 'survey'; }
        var TYPE_HINTS    = @json(\App\Models\Task::TYPE_HINTS);
        var FILE_TYPE_GROUPS  = @json(\App\Models\TaskField::FILE_TYPE_GROUPS);
        var MAX_FILES_OPTIONS = @json(\App\Models\TaskField::MAX_FILES_OPTIONS);
        var MAX_SIZE_OPTIONS  = @json(\App\Models\TaskField::MAX_SIZE_OPTIONS);
        var DEFAULT_MAX_SIZE_MB = @json(\App\Models\TaskField::DEFAULT_MAX_SIZE_MB);

        var typeSelect    = document.getElementById('type');
        var typeHint      = document.getElementById('typeHint');
        var linkSection   = document.getElementById('linkSection');
        var surveySection = document.getElementById('surveySection');
        var builderSection= document.getElementById('builderSection');
        var builderHint   = document.getElementById('builderHint');
        var wrap          = document.getElementById('fieldsWrap');
        var linkedSelect  = document.getElementById('linked_training_plan_id');
        var fieldIndex    = 0;

        var BUILDER_HINTS = {
            file_submission: 'Specify file submission requirements and guidelines for learners.',
            survey: 'Collect opinions — multiple choice or written answers, no scoring.',
            essay: 'Specify the essay question or reflection prompt for learners to answer.',
            action_plan: 'Design action plan questions and prompts for learners using customizable Google Form question types.'
        };

        function esc(s) {
            var d = document.createElement('div');
            d.textContent = (s === null || s === undefined) ? '' : s;
            return d.innerHTML;
        }

        function currentType() { return typeSelect.value; }
        function allowedTypes() { return FIELD_BY_TASK[currentType()] || []; }

        function typeOptions(selected) {
            var html = '';
            var labels = {
                'short_answer': 'Short answer',
                'textarea':     'Paragraph',
                'choice':       'Multiple choice',
                'checkbox':     'Checkboxes',
                'dropdown':     'Dropdown',
                'file':         'File upload',
                'scale':        'Linear scale (1-5)',
                'rating':       'Rating (1-5 stars)'
            };
            allowedTypes().forEach(function (key) {
                var lbl = labels[key] || FIELD_TYPES[key] || key;
                html += '<option value="' + key + '"' + (key === selected ? ' selected' : '') + '>'
                    + esc(lbl) + '</option>';
            });
            return html;
        }

        // Type column: a dropdown when several types are allowed,
        // otherwise a fixed badge (no dropdown needed).
        function typeColHTML(i, selType) {
            var allowed = allowedTypes();
            if (allowed.length === 1) {
                return '<input type="hidden" name="fields[' + i + '][type]" value="' + allowed[0] + '">' +
                       '<span class="badge bg-blue-lt py-1.5 px-2.5 rounded-2">' + esc(FIELD_TYPES[allowed[0]]) + '</span>';
            }
            return '<select class="form-select field-type-select rounded-2" name="fields[' + i + '][type]">' +
                   typeOptions(selType) + '</select>';
        }

        // ── Options editor (Multiple choice, Checkboxes, Dropdown) ──────
        function addOptionRow(listEl, i, value, ftype) {
            ftype = ftype || 'choice';
            var idx = listEl.querySelectorAll('.option-row').length;
            var row = document.createElement('div');
            row.className = 'd-flex align-items-center gap-2 option-row';

            var prefixHtml = '';
            if (ftype === 'checkbox') {
                prefixHtml = '<i class="ti ti-square text-muted fs-3 flex-shrink-0 option-prefix-icon"></i>';
            } else if (ftype === 'dropdown') {
                prefixHtml = '<span class="text-muted fw-bold small flex-shrink-0 option-prefix-num" style="width:20px;">' + (idx + 1) + '.</span>';
            } else {
                prefixHtml = '<i class="ti ti-circle text-muted fs-3 flex-shrink-0 option-prefix-icon"></i>';
            }

            row.innerHTML =
                prefixHtml +
                '<input type="text" class="form-control gform-opt-input flex-grow-1" name="fields[' + i + '][options][]" ' +
                    'placeholder="Option ' + (idx + 1) + '" value="' + esc(value || '') + '">' +
                '<button type="button" class="btn btn-sm btn-link text-muted p-1 btnRemoveOption" title="Remove option">' +
                    '<i class="ti ti-x fs-3"></i>' +
                '</button>';
            listEl.appendChild(row);
            reindexOptions(listEl, ftype);
        }

        function reindexOptions(listEl, ftype) {
            ftype = ftype || 'choice';
            var rows = listEl.querySelectorAll('.option-row');
            rows.forEach(function (row, pos) {
                var input = row.querySelector('input[type="text"]');
                if (input) input.placeholder = 'Option ' + (pos + 1);
                var numSpan = row.querySelector('.option-prefix-num');
                if (numSpan) numSpan.textContent = (pos + 1) + '.';
                var icon = row.querySelector('.option-prefix-icon');
                if (icon) {
                    if (ftype === 'checkbox') {
                        icon.className = 'ti ti-square text-muted fs-3 flex-shrink-0 option-prefix-icon';
                    } else {
                        icon.className = 'ti ti-circle text-muted fs-3 flex-shrink-0 option-prefix-icon';
                    }
                }
            });
        }

        function toggleQuestionTypeUI(card) {
            var ftype = card.dataset.ftype;
            var shortAns = card.querySelector('.previewShortAnswer');
            var paragraph = card.querySelector('.previewParagraph');
            var optEditor = card.querySelector('.optionsEditor');
            var fileSet = card.querySelector('.fileSettings');
            var scale = card.querySelector('.previewScale');
            var rating = card.querySelector('.previewRating');

            if (shortAns) shortAns.style.display = (ftype === 'short_answer') ? '' : 'none';
            if (paragraph) paragraph.style.display = (ftype === 'textarea') ? '' : 'none';
            if (fileSet) fileSet.style.display = (ftype === 'file') ? '' : 'none';
            if (scale) scale.style.display = (ftype === 'scale') ? '' : 'none';
            if (rating) rating.style.display = (ftype === 'rating') ? '' : 'none';

            var isOpt = (ftype === 'choice' || ftype === 'checkbox' || ftype === 'dropdown');
            if (optEditor) {
                optEditor.style.display = isOpt ? '' : 'none';
                if (isOpt) {
                    var list = optEditor.querySelector('.optionsList');
                    if (list && list.children.length === 0) {
                        addOptionRow(list, card.dataset.index, '', ftype);
                        addOptionRow(list, card.dataset.index, '', ftype);
                    } else if (list) {
                        reindexOptions(list, ftype);
                    }
                }
            }
        }

        // ── File-upload settings (ala Google Form) ──────────────────────
        function fileTypeSelectHTML(i, selected) {
            var html = '<option value="">All types</option>';
            Object.keys(FILE_TYPE_GROUPS).forEach(function (key) {
                html += '<option value="' + key + '"' + (key === selected ? ' selected' : '') + '>' +
                    esc(FILE_TYPE_GROUPS[key].label) + '</option>';
            });
            return html;
        }

        function selectOptionsHTML(options, selected, suffix) {
            var html = '';
            options.forEach(function (v) {
                html += '<option value="' + v + '"' + (String(v) === String(selected) ? ' selected' : '') + '>' +
                    v + (suffix || '') + '</option>';
            });
            return html;
        }

        function fileSettingsHTML(i, data) {
            return '<div class="fileSettings mt-3 p-3 bg-light-subtle rounded-3 border" style="display:none">' +
                '<div class="row g-3">' +
                    '<div class="col-12 col-md-4">' +
                        '<label class="form-label small fw-semibold text-dark mb-1">Allowed file types</label>' +
                        '<select class="form-select form-select-sm" name="fields[' + i + '][file_types]">' +
                            fileTypeSelectHTML(i, data.file_types || '') +
                        '</select>' +
                    '</div>' +
                    '<div class="col-6 col-md-4">' +
                        '<label class="form-label small fw-semibold text-dark mb-1">Max number of files</label>' +
                        '<select class="form-select form-select-sm" name="fields[' + i + '][max_files]">' +
                            selectOptionsHTML(MAX_FILES_OPTIONS, data.max_files || 1, '') +
                        '</select>' +
                    '</div>' +
                    '<div class="col-6 col-md-4">' +
                        '<label class="form-label small fw-semibold text-dark mb-1">Max file size</label>' +
                        '<select class="form-select form-select-sm" name="fields[' + i + '][max_size_mb]">' +
                            selectOptionsHTML(MAX_SIZE_OPTIONS, data.max_size_mb || DEFAULT_MAX_SIZE_MB, ' MB') +
                        '</select>' +
                    '</div>' +
                '</div>' +
                '<div class="mt-2 pt-2 border-top">' +
                    '<label class="form-check mb-0">' +
                        '<input type="checkbox" class="form-check-input" name="fields[' + i + '][allow_link]" value="1"' +
                            (data.allow_link ? ' checked' : '') + '>' +
                        '<span class="form-check-label small text-muted">Allow Google Drive / cloud link as an alternative to file upload</span>' +
                    '</label>' +
                '</div>' +
            '</div>';
        }

        // ── One question card ───────────────────────────────────────────
        function addField(data) {
            data = data || {};
            var i = fieldIndex++;
            var allowed = allowedTypes();
            var selType = allowed.indexOf(data.type) !== -1 ? data.type : (allowed[0] || 'textarea');
            var isEssay = (currentType() === 'essay');

            var card = document.createElement('div');
            card.dataset.index = i;
            card.dataset.ftype = selType;

            if (isEssay) {
                card.className = 'field-card field-card-clean form-field-xl';
                card.innerHTML =
                    '<input type="hidden" name="fields[' + i + '][id]" value="' + esc(data.id || '') + '">' +
                    '<input type="hidden" name="fields[' + i + '][type]" value="textarea">' +
                    '<input type="hidden" name="fields[' + i + '][required]" value="1">' +
                    '<div class="d-none typeCol"><input type="hidden" name="fields[' + i + '][type]" value="textarea"></div>' +
                    '<div class="gform-essay-block">' +
                        '<input type="text" class="form-control gform-question-input" id="field_label_' + i + '" name="fields[' + i + '][label]" ' +
                            'placeholder="Question" value="' + esc(data.label || '') + '" maxlength="255" required>' +
                        '<div class="gform-answer-preview mt-4">' +
                            '<div class="gform-answer-text">Long-answer text</div>' +
                            '<div class="gform-answer-line"></div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="optionsEditor mt-2" style="display:none"><div class="optionsList"></div><div class="optionsHint"></div></div>' +
                    '<div class="fileSettings mt-2" style="display:none"></div>';
            } else {
                card.className = 'border rounded-3 p-4 mb-3 field-card bg-white';
                card.innerHTML =
                    '<input type="hidden" name="fields[' + i + '][id]" value="' + esc(data.id || '') + '">' +
                    '<div class="d-flex align-items-start gap-3 flex-wrap flex-md-nowrap mb-3">' +
                        '<div class="flex-grow-1" style="min-width: 240px;">' +
                            '<input type="text" class="form-control gform-question-input" name="fields[' + i + '][label]" ' +
                                'placeholder="Question" value="' + esc(data.label || '') + '" maxlength="255" required>' +
                        '</div>' +
                        '<div class="typeCol" style="width: 220px; flex-shrink: 0;">' +
                            typeColHTML(i, selType) +
                        '</div>' +
                    '</div>' +
                    '<div class="previewShortAnswer mt-2" style="display:none;">' +
                        '<div class="gform-answer-preview py-2">' +
                            '<div class="gform-answer-text">Short-answer text</div>' +
                            '<div class="gform-answer-line" style="max-width: 280px;"></div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="previewParagraph mt-2" style="display:none;">' +
                        '<div class="gform-answer-preview py-2">' +
                            '<div class="gform-answer-text">Long-answer text</div>' +
                            '<div class="gform-answer-line" style="max-width: 440px;"></div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="optionsEditor mt-3" style="display:none;">' +
                        '<div class="optionsList d-flex flex-column gap-2 mb-2"></div>' +
                        '<button type="button" class="btn btn-sm btn-link text-primary text-decoration-none p-0 btnAddOption d-inline-flex align-items-center gap-1 fw-medium">' +
                            '<i class="ti ti-plus"></i> Add option' +
                        '</button>' +
                        '<div class="form-hint mt-2 optionsHint text-muted small" style="display:none;"></div>' +
                    '</div>' +
                    fileSettingsHTML(i, data) +
                    '<div class="previewScale mt-3 p-3 bg-light-subtle rounded-3 border" style="display:none;">' +
                        '<div class="text-muted small mb-2"><i class="ti ti-arrows-left-right me-1"></i> Scale preview (Learners will select a rating from 1 to 5):</div>' +
                        '<div class="d-flex align-items-center gap-3 flex-wrap">' +
                            '<span class="text-muted small fw-medium">1 (Lowest)</span>' +
                            '<div class="d-flex align-items-center gap-2">' +
                                '<span class="badge bg-white text-dark border px-3 py-2 rounded-pill shadow-xs">1</span>' +
                                '<span class="badge bg-white text-dark border px-3 py-2 rounded-pill shadow-xs">2</span>' +
                                '<span class="badge bg-white text-dark border px-3 py-2 rounded-pill shadow-xs">3</span>' +
                                '<span class="badge bg-white text-dark border px-3 py-2 rounded-pill shadow-xs">4</span>' +
                                '<span class="badge bg-white text-dark border px-3 py-2 rounded-pill shadow-xs">5</span>' +
                            '</div>' +
                            '<span class="text-muted small fw-medium">5 (Highest)</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="previewRating mt-3 p-3 bg-light-subtle rounded-3 border" style="display:none;">' +
                        '<div class="text-muted small mb-2"><i class="ti ti-star me-1"></i> Star rating preview (Learners will select 1 to 5 stars):</div>' +
                        '<div class="d-flex align-items-center gap-1 text-warning fs-2">' +
                            '<i class="ti ti-star-filled"></i>' +
                            '<i class="ti ti-star-filled"></i>' +
                            '<i class="ti ti-star-filled"></i>' +
                            '<i class="ti ti-star-filled"></i>' +
                            '<i class="ti ti-star-filled"></i>' +
                            '<span class="text-muted small ms-2 fs-6">5-star rating scale</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="border-top pt-3 mt-4 d-flex align-items-center justify-content-between flex-wrap gap-2">' +
                        '<div class="text-muted small fw-semibold questionNumberBadge">' +
                            '<span class="badge bg-light text-secondary border px-2.5 py-1 rounded-2">Question #<span class="qNumText">1</span></span>' +
                        '</div>' +
                        '<div class="d-flex align-items-center gap-3">' +
                            '<button type="button" class="btn btn-outline-danger btn-sm rounded-2 btnRemoveField px-2.5 py-1" title="Delete question">' +
                                '<i class="ti ti-trash me-1"></i> Delete' +
                            '</button>' +
                            '<div class="vr" style="height: 20px;"></div>' +
                            '<label class="form-check form-switch mb-0 d-inline-flex align-items-center gap-2">' +
                                '<input type="checkbox" class="form-check-input field-required-toggle" name="fields[' + i + '][required]" value="1"' +
                                    (data.required !== false ? ' checked' : '') + '>' +
                                '<span class="form-check-label small text-muted">Required</span>' +
                            '</label>' +
                        '</div>' +
                    '</div>';
            }
            wrap.appendChild(card);

            if (!isEssay) {
                var list = card.querySelector('.optionsList');
                if (list) {
                    var opts = (data.options && data.options.length) ? data.options : ['', ''];
                    opts.forEach(function (opt) {
                        addOptionRow(list, i, opt, selType);
                    });
                }
                toggleQuestionTypeUI(card);
            }
            updateQuestionNumbers();
        }

        function updateQuestionNumbers() {
            var cards = wrap.querySelectorAll('.field-card');
            cards.forEach(function (c, idx) {
                var numText = c.querySelector('.qNumText');
                if (numText) numText.textContent = (idx + 1);
                var btn = c.querySelector('.btnRemoveField');
                if (btn) btn.style.display = (cards.length > 1) ? '' : 'none';
            });
        }

        // ── Events (delegation) ─────────────────────────────────────────
        var btnAddField = document.getElementById('btnAddField');
        if (btnAddField) {
            btnAddField.addEventListener('click', function () {
                addField();
            });
        }

        wrap.addEventListener('click', function (e) {
            var rmField = e.target.closest('.btnRemoveField');
            if (rmField) {
                if (wrap.querySelectorAll('.field-card').length <= 1) {
                    alert('A task needs at least 1 question.');
                    return;
                }
                rmField.closest('.field-card').remove();
                updateQuestionNumbers();
                return;
            }
            var addOpt = e.target.closest('.btnAddOption');
            if (addOpt) {
                var card = addOpt.closest('.field-card');
                addOptionRow(card.querySelector('.optionsList'), card.dataset.index, '', card.dataset.ftype);
                return;
            }
            var rmOpt = e.target.closest('.btnRemoveOption');
            if (rmOpt) {
                var list = rmOpt.closest('.optionsList');
                if (list.querySelectorAll('.option-row').length <= 2) {
                    alert('This question needs at least 2 options.');
                    return;
                }
                var card = rmOpt.closest('.field-card');
                rmOpt.closest('.option-row').remove();
                reindexOptions(list, card ? card.dataset.ftype : 'choice');
            }
        });

        wrap.addEventListener('change', function (e) {
            if (e.target.classList.contains('field-type-select')) {
                var card = e.target.closest('.field-card');
                card.dataset.ftype = e.target.value;
                toggleQuestionTypeUI(card);
            }
        });

        // ── Placement picker: type dropdown + searchable item dropdown ────
        var parentTypeSelect = document.getElementById('parent_type');
        var parentIdInput    = document.getElementById('parent_id');
        var parentPicker     = document.getElementById('parentPicker');
        var parentPickerBtn  = document.getElementById('parentPickerBtn');
        var parentPickerText = document.getElementById('parentPickerText');
        var parentPickerLabel= document.getElementById('parentPickerLabel');
        var parentPanel      = document.getElementById('parentPanel');
        var parentPanelSearch= document.getElementById('parentPanelSearch');
        var parentPanelList  = document.getElementById('parentPanelList');
        var parentIdError    = document.getElementById('parentIdError');
        var PARENT_LABELS    = @json(\App\Models\Task::PARENT_TYPES);
        var currentPlacement = @json($currentParentType);

        function placementType() { return currentPlacement; }

        function getPickerPlaceholder(placement) {
            var label = PARENT_LABELS[placement] || 'Item';
            return '— Select ' + label + ' —';
        }

        function setPickerLabel() {
            var label = PARENT_LABELS[currentPlacement] || 'Item';
            if (parentPickerLabel) parentPickerLabel.textContent = 'Select ' + label;
        }

        // Standalone tasks are not tied to any placement → hide the item picker,
        // show the (optional, manual) access code field instead.
        function syncPlacementPicker() {
            var isStandalone = currentPlacement === 'standalone';
            if (parentPickerLabel) parentPickerLabel.style.display = isStandalone ? 'none' : '';
            if (parentPicker) parentPicker.style.display = isStandalone ? 'none' : '';
            var accessCodeWrap = document.getElementById('accessCodeWrap');
            if (accessCodeWrap) accessCodeWrap.style.display = isStandalone ? '' : 'none';
            var standaloneNotice = document.getElementById('standalonePlacementNotice');
            if (standaloneNotice) standaloneNotice.style.display = isStandalone ? '' : 'none';
            ensureValidActiveSection();
        }

        function renderParentItems(items) {
            parentPanelList.innerHTML = '';
            if (!items.length) {
                var emptyMsg = currentPlacement === 'offline_training'
                    ? 'No offline training records found.'
                    : 'No results found.';
                parentPanelList.innerHTML = '<div class="p-3 text-muted text-center">' + emptyMsg + '</div>';
                return;
            }
            items.forEach(function (it) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'list-group-item list-group-item-action';
                b.textContent = it.title;
                b.setAttribute('role', 'option');
                if (String(it.id) === String(parentIdInput.value)) b.classList.add('active');
                b.addEventListener('click', function () { pickParent(it.id, it.title); });
                parentPanelList.appendChild(b);
            });
        }

        function pickParent(id, title) {
            parentIdInput.value = id;
            parentPickerText.textContent = title;
            if (parentIdError) parentIdError.style.display = 'none';
            if (parentPickerBtn) parentPickerBtn.classList.remove('is-invalid', 'picker-btn-invalid');
            closeParentPanel();
        }

        function openParentPanel() {
            if (!parentPanel) return;
            parentPanel.style.display = '';
            parentPickerBtn.setAttribute('aria-expanded', 'true');
            parentPanelSearch.value = '';
            var label = PARENT_LABELS[currentPlacement] || 'item';
            parentPanelSearch.placeholder = 'Type to search ' + label.toLowerCase() + '...';
            parentPanelSearch.disabled = false;
            loadParentItems('');
            setTimeout(function () { parentPanelSearch.focus(); }, 50);
        }

        function closeParentPanel() {
            if (!parentPanel) return;
            parentPanel.style.display = 'none';
            parentPickerBtn.setAttribute('aria-expanded', 'false');
        }

        var parentSearchTimer = null;
        function loadParentItems(query) {
            var url = parentIdInput.dataset.urlTemplate.replace('__TYPE__', currentPlacement)
                + '?q=' + encodeURIComponent(query || '');
            parentPanelList.innerHTML = '<div class="p-3 text-muted text-center">Loading...</div>';
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(renderParentItems)
                .catch(function () {
                    parentPanelList.innerHTML = '<div class="p-3 text-muted text-center">Failed to load. Try again.</div>';
                });
        }

        if (parentTypeSelect) {
            parentTypeSelect.addEventListener('change', function () {
                currentPlacement = parentTypeSelect.value;
                parentIdInput.value = '';
                parentPickerText.textContent = getPickerPlaceholder(currentPlacement);
                setPickerLabel();
                syncPlacementPicker();
            });
        }
        if (parentPickerBtn) {
            parentPickerBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                if (parentPanel.style.display === 'none') openParentPanel();
                else closeParentPanel();
            });
            document.addEventListener('click', function (e) {
                if (parentPicker && !parentPicker.contains(e.target)) closeParentPanel();
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeParentPanel();
            });
        }
        if (parentPanelSearch) {
            parentPanelSearch.addEventListener('click', function (e) { e.stopPropagation(); });
            parentPanelSearch.addEventListener('input', function () {
                clearTimeout(parentSearchTimer);
                var q = parentPanelSearch.value;
                parentSearchTimer = setTimeout(function () { loadParentItems(q); }, 300);
            });
        }

        // ── Target course: searchable dropdown (built from existing <option> elements) ──
        var coursePickerBtn   = document.getElementById('coursePickerBtn');
        var coursePickerText  = document.getElementById('coursePickerText');
        var coursePicker      = document.getElementById('coursePicker');
        var coursePanel       = document.getElementById('coursePanel');
        var coursePanelSearch = document.getElementById('coursePanelSearch');
        var coursePanelList   = document.getElementById('coursePanelList');

        function courseOptions() {
            var out = [];
            if (!linkedSelect) return out;
            Array.prototype.forEach.call(linkedSelect.options, function (o) {
                if (!o.value) return; // skip placeholder
                out.push({ id: o.value, title: o.textContent.trim() });
            });
            return out;
        }

        function syncCoursePickerText() {
            if (!linkedSelect || !coursePickerText) return;
            var sel = linkedSelect.options[linkedSelect.selectedIndex];
            coursePickerText.textContent = (sel && sel.value)
                ? sel.textContent.trim()
                : '— Select target course —';
        }

        function renderCourseItems(items) {
            coursePanelList.innerHTML = '';
            if (!items.length) {
                coursePanelList.innerHTML = '<div class="p-3 text-muted text-center">No results found.</div>';
                return;
            }
            items.forEach(function (it) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'list-group-item list-group-item-action';
                b.textContent = it.title;
                b.setAttribute('role', 'option');
                if (linkedSelect && String(linkedSelect.value) === String(it.id)) b.classList.add('active');
                b.addEventListener('click', function () {
                    linkedSelect.value = it.id;
                    linkedSelect.dispatchEvent(new Event('change'));
                    if (coursePickerBtn) coursePickerBtn.classList.remove('is-invalid', 'picker-btn-invalid');
                    var courseErr = document.getElementById('courseIdError');
                    if (courseErr) courseErr.style.display = 'none';
                    closeCoursePanel();
                });
                coursePanelList.appendChild(b);
            });
        }

        function filterCourseItems(q) {
            q = (q || '').toLowerCase().trim();
            var items = courseOptions();
            if (q) items = items.filter(function (it) {
                return it.title.toLowerCase().indexOf(q) !== -1;
            });
            renderCourseItems(items);
        }

        function openCoursePanel() {
            if (!coursePanel) return;
            coursePanel.style.display = '';
            coursePickerBtn.setAttribute('aria-expanded', 'true');
            coursePanelSearch.value = '';
            filterCourseItems('');
            setTimeout(function () { coursePanelSearch.focus(); }, 50);
        }

        function closeCoursePanel() {
            if (!coursePanel) return;
            coursePanel.style.display = 'none';
            coursePickerBtn.setAttribute('aria-expanded', 'false');
        }

        if (coursePickerBtn && linkedSelect) {
            linkedSelect.addEventListener('change', syncCoursePickerText);
            coursePickerBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                if (coursePanel.style.display === 'none') openCoursePanel();
                else closeCoursePanel();
            });
            document.addEventListener('click', function (e) {
                if (coursePicker && !coursePicker.contains(e.target)) closeCoursePanel();
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeCoursePanel();
            });
            if (coursePanelSearch) {
                coursePanelSearch.addEventListener('click', function (e) { e.stopPropagation(); });
                coursePanelSearch.addEventListener('input', function () {
                    filterCourseItems(coursePanelSearch.value);
                });
            }
            syncCoursePickerText();
        }
        // Highlight unfilled required columns on submit and clear on input
        var taskFormEl = document.getElementById('taskForm');

        function highlightInvalidFields() {
            // Title
            var titleEl = document.getElementById('title');
            if (titleEl && !titleEl.value.trim()) {
                titleEl.classList.add('is-invalid');
            }
            // Dynamic question prompts
            if (wrap) {
                wrap.querySelectorAll('.field-card input[name$="[label]"], .field-card textarea[name$="[label]"]').forEach(function (inp) {
                    if (!inp.value.trim()) {
                        inp.classList.add('is-invalid');
                    }
                });
            }
            // Placement picker
            if (parentIdInput && !parentIdInput.value && currentPlacement !== 'standalone') {
                if (parentPickerBtn) parentPickerBtn.classList.add('is-invalid', 'picker-btn-invalid');
            }
            // Course Assignment picker
            if (currentType() === 'course_assignment') {
                var linkTypeRadio = document.querySelector('input[name="link_type"]:checked');
                var isLms = !linkTypeRadio || linkTypeRadio.value === 'lms';
                if (isLms && (!linkedSelect || !linkedSelect.value)) {
                    if (coursePickerBtn) coursePickerBtn.classList.add('is-invalid', 'picker-btn-invalid');
                }
            }
        }

        if (taskFormEl) {
            // When admin clicks submit (Publish Task / Save as Draft / Save Changes), highlight empty fields in red
            taskFormEl.querySelectorAll('button[type="submit"]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    highlightInvalidFields();
                });
            });

            // Native invalid event (when browser checks required fields)
            taskFormEl.addEventListener('invalid', function (e) {
                if (e.target && e.target.classList) {
                    e.target.classList.add('is-invalid');
                }
            }, true);

            // Remove red highlight as soon as user types or changes value
            taskFormEl.addEventListener('input', function (e) {
                if (e.target && e.target.classList && e.target.classList.contains('is-invalid')) {
                    e.target.classList.remove('is-invalid');
                }
            });
            taskFormEl.addEventListener('change', function (e) {
                if (e.target && e.target.classList && e.target.classList.contains('is-invalid')) {
                    e.target.classList.remove('is-invalid');
                }
            });

            // Guard: don't submit without a picked parent or course (hidden inputs skip browser validation)
            taskFormEl.addEventListener('submit', function (e) {
                // Standalone tasks are not tied to any placement → no parent needed.
                if (parentIdInput && !parentIdInput.value && currentPlacement !== 'standalone') {
                    e.preventDefault();
                    if (parentPickerBtn) parentPickerBtn.classList.add('is-invalid', 'picker-btn-invalid');
                    if (parentIdError) parentIdError.style.display = '';
                    parentPickerBtn.focus();
                    openParentPanel();
                    return;
                }

                if (currentType() === 'course_assignment') {
                    var linkTypeRadio = document.querySelector('input[name="link_type"]:checked');
                    var isLms = !linkTypeRadio || linkTypeRadio.value === 'lms';
                    if (isLms && (!linkedSelect || !linkedSelect.value)) {
                        e.preventDefault();
                        if (coursePickerBtn) coursePickerBtn.classList.add('is-invalid', 'picker-btn-invalid');
                        var courseErr = document.getElementById('courseIdError');
                        if (courseErr) courseErr.style.display = '';
                        coursePickerBtn.focus();
                        openCoursePanel();
                        return;
                    }
                }
            });
        }
        setPickerLabel();
        syncPlacementPicker();

        // ── Course Assignment: link type radios ─────────────────────────
        document.querySelectorAll('input[name="link_type"]').forEach(function (r) {
            r.addEventListener('change', refreshLinkWraps);
        });
        function refreshLinkWraps() {
            var linkType = document.querySelector('input[name="link_type"]:checked');
            var isLms = !linkType || linkType.value === 'lms';
            document.getElementById('linkLmsWrap').style.display = isLms ? '' : 'none';
            document.getElementById('linkExternalWrap').style.display = isLms ? 'none' : '';
        }

        // ── Task type switching ─────────────────────────────────────────
        function refreshTypeUI() {
            var t = currentType();
            typeHint.textContent = TYPE_HINTS[t] || '';
            var isLink   = (t === 'course_assignment');
            var isSurvey = (t === 'survey');
            var isFileSubmission = (t === 'file_submission');
            var isEssay  = (t === 'essay');
            var hasBuilder = typeHasBuilder(t);

            linkSection.style.display       = isLink ? '' : 'none';
            if (surveySection) surveySection.style.display = isSurvey ? '' : 'none';
            builderSection.style.display    = hasBuilder ? '' : 'none';

            var addQuestionWrap = document.getElementById('addQuestionWrap');
            if (addQuestionWrap) {
                addQuestionWrap.style.display = (hasBuilder && !isEssay) ? '' : 'none';
            }

            var isLockedParent = {{ !empty($lockedParent) ? 'true' : 'false' }};
            var builderPrefix = isLockedParent ? '3. ' : '4. ';
            var timelinePrefix = isLockedParent ? '2. ' : '3. ';

            var builderTitleEl = document.getElementById('builderTitle');
            var builderBadgeIcon = document.getElementById('builderBadgeIcon');
            if (isEssay) {
                if (builderTitleEl) builderTitleEl.textContent = builderPrefix + 'Essay Question';
                if (builderHint) builderHint.textContent = 'Specify the essay question or reflection prompt for learners to answer.';
                if (builderBadgeIcon) builderBadgeIcon.className = 'ti ti-writing';
            } else if (t === 'action_plan') {
                if (builderTitleEl) builderTitleEl.textContent = builderPrefix + 'Action Plan Form Builder';
                if (builderHint) builderHint.textContent = BUILDER_HINTS.action_plan;
                if (builderBadgeIcon) builderBadgeIcon.className = 'ti ti-clipboard-list';
            } else if (isFileSubmission) {
                if (builderTitleEl) builderTitleEl.textContent = builderPrefix + 'File Submission Requirements';
                if (builderHint) builderHint.textContent = BUILDER_HINTS.file_submission;
                if (builderBadgeIcon) builderBadgeIcon.className = 'ti ti-cloud-upload';
            } else {
                if (builderTitleEl) builderTitleEl.textContent = builderPrefix + 'Questions & Submission Form Builder';
                if (builderHint) builderHint.textContent = BUILDER_HINTS[t] || '';
                if (builderBadgeIcon) builderBadgeIcon.className = 'ti ti-list-check';
            }

            // Timeline & Supporting Files (Section 2 when locked, Section 3 when unlocked)
            var attachmentWrapper = document.getElementById('attachmentWrapper');
            var attachmentInput   = document.getElementById('attachment');
            var section3Title     = document.getElementById('section3Title');
            var section3Subtitle  = document.getElementById('section3Subtitle');

            if (attachmentWrapper) {
                attachmentWrapper.style.display = isFileSubmission ? '' : 'none';
            }
            if (attachmentInput) {
                attachmentInput.disabled = !isFileSubmission;
                if (!isFileSubmission) {
                    attachmentInput.value = '';
                }
            }
            if (section3Title) {
                section3Title.textContent = timelinePrefix + (isFileSubmission ? 'Timeline & Supporting Files' : 'Timeline');
            }
            if (section3Subtitle) {
                section3Subtitle.textContent = isFileSubmission
                    ? 'Set the submission deadline and attach assignment templates or guidance materials (optional).'
                    : 'Set the submission deadline for learners (optional).';
            }

            ensureValidActiveSection();

            if (!hasBuilder) {
                // These types have no question builder: disable every input
                // inside the hidden cards. Hidden `required` inputs would
                // otherwise block form submission silently (browser refuses
                // to submit, no visible error). Disabled inputs are also
                // excluded from the POST.
                wrap.querySelectorAll('input, select, textarea, button').forEach(function (el) {
                    el.disabled = true;
                });
                if (isLink) {
                    refreshLinkWraps();
                }
                return;
            }

            // Builder visible again: re-enable the cards' inputs.
            wrap.querySelectorAll('input, select, textarea, button').forEach(function (el) {
                el.disabled = false;
            });

            var wasEssay = wrap.querySelector('.gform-essay-block') !== null;

            if (isEssay !== wasEssay && wrap.querySelectorAll('.field-card').length > 0) {
                // Re-render fields in the new format while preserving existing labels/data
                var currentData = [];
                wrap.querySelectorAll('.field-card').forEach(function(c) {
                    var labelInp = c.querySelector('input[name$="[label]"], textarea[name$="[label]"]');
                    var idInp = c.querySelector('input[name$="[id]"]');
                    var reqInp = c.querySelector('input[name$="[required]"]');
                    currentData.push({
                        id: idInp ? idInp.value : '',
                        label: labelInp ? labelInp.value : '',
                        type: isEssay ? 'textarea' : (c.dataset.ftype || allowedTypes()[0]),
                        required: reqInp ? reqInp.checked : true
                    });
                });
                if (isEssay && currentData.length > 1) {
                    currentData = [currentData[0]];
                }
                wrap.innerHTML = '';
                fieldIndex = 0;
                currentData.forEach(function(d) {
                    addField(d);
                });
                return;
            }

            // Rebuild each card's type column for the newly allowed set.
            var allowed = allowedTypes();
            wrap.querySelectorAll('.field-card').forEach(function (card) {
                var cur = card.dataset.ftype;
                var next = allowed.indexOf(cur) !== -1 ? cur : allowed[0];
                card.dataset.ftype = next;
                var typeColEl = card.querySelector('.typeCol');
                if (typeColEl) {
                    typeColEl.innerHTML = typeColHTML(card.dataset.index, next);
                }
                toggleQuestionTypeUI(card);
            });
            updateQuestionNumbers();
        }
        typeSelect.addEventListener('change', function () {
            refreshTypeUI();
            // Coming back to a type with a builder and an empty builder → start with 1 card.
            // (Never auto-add for types without a builder: a hidden card's
            // `required` input would silently block submit.)
            var t = currentType();
            if (typeHasBuilder(t) && wrap.querySelectorAll('.field-card').length === 0) {
                addField();
            }
        });

        // ── Preview modal generation & actions ──────────────────────────
        function isQuiz() { return false; }

        function renderTaskPreview() {
            var titleInput = document.getElementById('title');
            var titleVal = (titleInput ? titleInput.value.trim() : '') || 'Untitled Task';
            var prevTitle = document.getElementById('prevTitle');
            if (prevTitle) prevTitle.textContent = titleVal;

            // Type badge
            var typeSel = document.getElementById('type');
            var typeText = (typeSel && typeSel.selectedIndex >= 0) ? typeSel.options[typeSel.selectedIndex].text : 'Task';
            var prevType = document.getElementById('prevTypeBadge');
            if (prevType) prevType.textContent = typeText;

            // Placement
            var prevPlacementText = document.getElementById('prevPlacementText');
            var prevPlacementIcon = document.getElementById('prevPlacementIcon');
            if (currentPlacement === 'standalone') {
                if (prevPlacementText) prevPlacementText.textContent = 'Others';
                if (prevPlacementIcon) prevPlacementIcon.className = 'ti ti-world me-1';
            } else {
                var pickerText = parentPickerText ? parentPickerText.textContent.trim() : '';
                var cleanTarget = (pickerText && pickerText.indexOf('— Select') === -1) ? pickerText : 'Unassigned';
                if (prevPlacementText) prevPlacementText.textContent = cleanTarget;
                if (prevPlacementIcon) prevPlacementIcon.className = 'ti ti-bookmark me-1';
            }

            // Deadline and status badges
            var deadlineInput = document.getElementById('deadline');
            var deadlineWrap = document.getElementById('prevDeadlineWrap');
            var deadlineText = document.getElementById('prevDeadlineText');
            var overdueBadge = document.getElementById('prevOverdueBadge');
            var statusBadge = document.getElementById('prevStatusBadge');

            if (deadlineInput && deadlineInput.value) {
                var d = new Date(deadlineInput.value);
                if (!isNaN(d.getTime())) {
                    var day = ('0' + d.getDate()).slice(-2);
                    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                    var month = months[d.getMonth()];
                    var year = d.getFullYear();
                    var hours = ('0' + d.getHours()).slice(-2);
                    var minutes = ('0' + d.getMinutes()).slice(-2);
                    deadlineText.textContent = day + ' ' + month + ' ' + year + ' ' + hours + ':' + minutes;

                    var isPast = d.getTime() < Date.now();
                    if (isPast) {
                        overdueBadge.style.display = '';
                        statusBadge.className = 'badge bg-danger text-white px-2 py-1 fs-6';
                        statusBadge.textContent = 'Overdue';
                    } else {
                        overdueBadge.style.display = 'none';
                        statusBadge.className = 'badge bg-secondary-lt text-secondary px-2 py-1 fs-6';
                        statusBadge.textContent = 'Not Started';
                    }
                } else {
                    deadlineText.textContent = deadlineInput.value;
                    overdueBadge.style.display = 'none';
                    statusBadge.className = 'badge bg-secondary-lt text-secondary px-2 py-1 fs-6';
                    statusBadge.textContent = 'Not Started';
                }
            } else {
                deadlineText.textContent = 'No deadline set';
                overdueBadge.style.display = 'none';
                statusBadge.className = 'badge bg-secondary-lt text-secondary px-2 py-1 fs-6';
                statusBadge.textContent = 'Not Started';
            }

            // Access Code
            var accessCodeInput = document.getElementById('access_code');
            var accessCodeWrap = document.getElementById('prevAccessCodeWrap');
            var accessCodeText = document.getElementById('prevAccessCodeText');
            if (currentPlacement === 'standalone' && accessCodeInput && accessCodeInput.value.trim()) {
                if (accessCodeWrap) accessCodeWrap.style.display = '';
                if (accessCodeText) accessCodeText.textContent = accessCodeInput.value.trim().toUpperCase();
            } else {
                if (accessCodeWrap) accessCodeWrap.style.display = 'none';
            }

            // Description / Instructions
            var descInput = document.getElementById('description');
            var descWrap = document.getElementById('prevDescWrap');
            var descEl = document.getElementById('prevDescription');
            var dVal = (descInput && descInput.value.trim()) ? descInput.value.trim() : '';
            if (dVal) {
                if (descEl) descEl.textContent = dVal;
                if (descWrap) descWrap.style.display = '';
            } else {
                if (descWrap) descWrap.style.display = 'none';
            }

            // Attachment / Reference Material (only for File Submission)
            var isFileSubmission = currentType() === 'file_submission';
            var attachInput = document.getElementById('attachment');
            var attachWrap = document.getElementById('prevAttachmentWrap');
            var attachName = document.getElementById('prevAttachmentName');
            var hasSavedAttachment = @json(isset($task) && $task->attachment ? basename($task->attachment) : '');
            var hasNewFile = attachInput && attachInput.files && attachInput.files.length > 0;
            if (isFileSubmission && (hasNewFile || hasSavedAttachment)) {
                if (attachName) attachName.textContent = hasNewFile ? attachInput.files[0].name : hasSavedAttachment;
                if (attachWrap) attachWrap.style.display = '';
            } else {
                if (attachWrap) attachWrap.style.display = 'none';
            }

            // Submission section
            var subWrap = document.getElementById('prevSubmissionSection');
            if (!subWrap) return;
            subWrap.innerHTML = '';
            var t = currentType();

            if (t === 'course_assignment') {
                var linkType = document.querySelector('input[name="link_type"]:checked');
                var isLms = !linkType || linkType.value === 'lms';
                if (isLms) {
                    var courseName = (coursePickerText ? coursePickerText.textContent.trim() : '') || 'Selected Course';
                    if (courseName.indexOf('— Select') !== -1) courseName = 'Not yet selected';
                    subWrap.innerHTML =
                        '<h5 class="fw-semibold text-dark mb-3 d-flex align-items-center gap-1">' +
                            '<i class="ti ti-book-2 text-primary"></i> Course Assignment' +
                        '</h5>' +
                        '<div class="p-3 bg-light rounded-3 border text-center py-4">' +
                            '<i class="ti ti-book-2 text-primary fs-1 mb-2 d-block"></i>' +
                            '<div class="fw-bold text-dark mb-1">' + esc(courseName) + '</div>' +
                            '<div class="text-muted small mb-3">Finish the course (100% progress) — this task will be marked complete automatically.</div>' +
                            '<button type="button" class="btn btn-primary rounded-3 px-4" disabled>' +
                                '<i class="ti ti-book-2 me-1"></i>Open Course: ' + esc(courseName) +
                            '</button>' +
                        '</div>' +
                        '<div class="form-hint mt-2 text-center text-muted">Finish the course (100% progress) — this task will be marked complete automatically.</div>';
                } else {
                    subWrap.innerHTML =
                        '<h5 class="fw-semibold text-dark mb-3 d-flex align-items-center gap-1">' +
                            '<i class="ti ti-book-2 text-primary"></i> Course Assignment' +
                        '</h5>' +
                        '<div class="p-3 bg-light rounded-3 border text-center py-4">' +
                            '<i class="ti ti-external-link text-primary fs-1 mb-2 d-block"></i>' +
                            '<div class="fw-bold text-dark mb-1">External Course Link</div>' +
                            '<div class="text-muted small mb-3">Finish the course — this task will be marked complete automatically.</div>' +
                            '<button type="button" class="btn btn-primary rounded-3 px-4" disabled>' +
                                '<i class="ti ti-external-link me-1"></i>Open External Course' +
                            '</button>' +
                        '</div>' +
                        '<div class="form-hint mt-2 text-center text-muted">Finish the course — this task will be marked complete automatically.</div>';
                }
            } else if (t === 'survey') {
                var surveySel = document.getElementById('survey_id');
                var surveyText = (surveySel && surveySel.selectedIndex >= 0 && surveySel.value)
                    ? surveySel.options[surveySel.selectedIndex].text
                    : 'Survey Form';
                subWrap.innerHTML =
                    '<h5 class="fw-semibold text-dark mb-3 d-flex align-items-center gap-1">' +
                        '<i class="ti ti-pencil text-primary"></i> Survey Form' +
                    '</h5>' +
                    '<div class="p-3 bg-light rounded-3 border text-center py-4">' +
                        '<i class="ti ti-forms text-primary fs-1 mb-2 d-block"></i>' +
                        '<div class="fw-bold text-dark mb-1">' + esc(surveyText) + '</div>' +
                        '<div class="text-muted small mb-3">Complete the survey form — this task will be marked complete automatically.</div>' +
                        '<button type="button" class="btn btn-primary rounded-3 px-4" disabled>' +
                            '<i class="ti ti-forms me-1"></i>Open Survey: ' + esc(surveyText) +
                        '</button>' +
                    '</div>' +
                    '<div class="form-hint mt-2 text-center text-muted">Complete the survey form — this task will be marked complete automatically.</div>';
            } else {
                var fieldCards = wrap ? wrap.querySelectorAll('.field-card') : [];
                var html = '<h5 class="fw-semibold text-dark mb-3 d-flex align-items-center gap-1">' +
                    '<i class="ti ti-pencil text-primary"></i> Learner Submission Form' +
                '</h5>';

                if (!fieldCards.length) {
                    html += '<div class="alert alert-warning">This task has no questions yet.</div>';
                } else {
                    fieldCards.forEach(function (card, idx) {
                        var labelInput = card.querySelector('input[name$="[label]"], textarea[name$="[label]"]');
                        var qTitle = (labelInput ? labelInput.value.trim() : '') || ('Question ' + (idx + 1));
                        var ftype = card.dataset.ftype;
                        var typeLabel = FIELD_TYPES[ftype] || 'Question';
                        var isRequired = true;
                        var reqChk = card.querySelector('input[name$="[required]"]');
                        if (reqChk) isRequired = reqChk.checked;

                        html += '<div class="mb-4 pb-3 border-bottom">' +
                            '<label class="form-label fw-semibold text-dark mb-2">' +
                                esc(qTitle) + (isRequired ? ' <span class="text-danger">*</span>' : '') + ' ' +
                                '<span class="text-muted fw-normal small">(' + esc(typeLabel) + ')</span>' +
                            '</label>';

                        if (ftype === 'file') {
                            var groupSel = card.querySelector('select[name$="[file_types]"]');
                            var groupKey = groupSel ? groupSel.value : '';
                            var groupInfo = FILE_TYPE_GROUPS[groupKey];
                            var maxFilesSel = card.querySelector('select[name$="[max_files]"]');
                            var maxFiles = maxFilesSel ? maxFilesSel.value : '1';
                            var maxSizeSel = card.querySelector('select[name$="[max_size_mb]"]');
                            var maxSize = maxSizeSel ? maxSizeSel.value : '10';
                            var allowLinkChk = card.querySelector('input[name$="[allow_link]"]');
                            var allowLink = allowLinkChk ? allowLinkChk.checked : false;

                            var extDisplay = 'All file formats';
                            var badgeHtml = '';
                            if (groupInfo && groupInfo.extensions && groupInfo.extensions.length) {
                                extDisplay = groupInfo.extensions.join(', ').toUpperCase();
                                badgeHtml = '<span class="badge bg-secondary-lt text-secondary ms-1">' + esc(groupInfo.label) + '</span>';
                            }

                            if (allowLink) {
                                html += '<div class="mb-2">' +
                                    '<label class="form-check form-check-inline mb-0">' +
                                        '<input type="radio" class="form-check-input" checked disabled>' +
                                        '<span class="form-check-label">Upload file</span>' +
                                    '</label>' +
                                    '<label class="form-check form-check-inline mb-0">' +
                                        '<input type="radio" class="form-check-input" disabled>' +
                                        '<span class="form-check-label">Drive link</span>' +
                                    '</label>' +
                                '</div>';
                            }

                            html += '<div>' +
                                '<div class="border border-2 border-dashed rounded-3 p-4 text-center bg-light-subtle" style="border-color:#cbd5e1!important;">' +
                                    '<div class="avatar avatar-md rounded-circle bg-primary-lt text-primary mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">' +
                                        '<i class="ti ti-cloud-upload fs-1"></i>' +
                                    '</div>' +
                                    '<div class="fw-semibold text-dark mb-1">' +
                                        'Drag &amp; drop files here, or <span class="text-primary text-decoration-underline">browse</span>' +
                                    '</div>' +
                                    '<div class="text-muted small" style="font-size: 11.5px;">' +
                                        'Format: <strong>' + esc(extDisplay) + '</strong>' + badgeHtml +
                                        ' | Maximum <strong>' + maxFiles + ' file' + (maxFiles > 1 ? 's' : '') + '</strong>, max. <strong>' + maxSize + ' MB</strong> per file' +
                                    '</div>' +
                                    (allowLink ? '<div class="mt-2"><span class="badge bg-info-lt text-info d-inline-flex align-items-center"><i class="ti ti-link me-1"></i> Supports Drive Link</span></div>' : '') +
                                '</div>' +
                            '</div>';
                        } else if (ftype === 'choice') {
                            var opts = card.querySelectorAll('.option-row input[type="text"]');
                            html += '<div class="d-flex flex-column gap-2 mt-2">';
                            opts.forEach(function (optInput, oIdx) {
                                var optVal = optInput.value.trim() || ('Option ' + (oIdx + 1));
                                html += '<label class="form-check mb-0">' +
                                    '<input type="radio" class="form-check-input" disabled>' +
                                    '<span class="form-check-label">' + esc(optVal) + '</span>' +
                                '</label>';
                            });
                            html += '</div>';
                        } else if (ftype === 'checkbox') {
                            var chkOpts = card.querySelectorAll('.option-row input[type="text"]');
                            html += '<div class="d-flex flex-column gap-2 mt-2">';
                            chkOpts.forEach(function (optInput, oIdx) {
                                var optVal = optInput.value.trim() || ('Option ' + (oIdx + 1));
                                html += '<label class="form-check mb-0">' +
                                    '<input type="checkbox" class="form-check-input" disabled>' +
                                    '<span class="form-check-label">' + esc(optVal) + '</span>' +
                                '</label>';
                            });
                            html += '</div>';
                        } else if (ftype === 'dropdown') {
                            var dropOpts = card.querySelectorAll('.option-row input[type="text"]');
                            html += '<select class="form-select" disabled style="background:#f8fafc; max-width:320px;">' +
                                '<option>— Choose —</option>';
                            dropOpts.forEach(function (optInput, oIdx) {
                                var optVal = optInput.value.trim() || ('Option ' + (oIdx + 1));
                                html += '<option>' + esc(optVal) + '</option>';
                            });
                            html += '</select>';
                        } else if (ftype === 'short_answer') {
                            html += '<input type="text" class="form-control" placeholder="Your answer" disabled style="background:#f8fafc; max-width:400px;">';
                        } else if (ftype === 'scale') {
                            html += '<div class="d-flex align-items-center gap-3 flex-wrap py-2">' +
                                '<span class="text-muted small fw-medium">1 (Lowest)</span>' +
                                '<div class="d-flex align-items-center gap-3">' +
                                    [1, 2, 3, 4, 5].map(function (n) {
                                        return '<label class="form-check form-check-inline text-center m-0">' +
                                            '<div class="small fw-bold text-dark mb-1">' + n + '</div>' +
                                            '<input type="radio" class="form-check-input m-0" disabled>' +
                                        '</label>';
                                    }).join('') +
                                '</div>' +
                                '<span class="text-muted small fw-medium">5 (Highest)</span>' +
                            '</div>';
                        } else if (ftype === 'rating') {
                            html += '<div class="d-flex align-items-center gap-1 text-warning fs-1 py-1">' +
                                '<i class="ti ti-star-filled"></i>' +
                                '<i class="ti ti-star-filled"></i>' +
                                '<i class="ti ti-star-filled"></i>' +
                                '<i class="ti ti-star-filled"></i>' +
                                '<i class="ti ti-star"></i>' +
                                '<span class="text-muted small ms-2 fs-6">(Rating from 1 to 5 stars)</span>' +
                            '</div>';
                        } else {
                            html += '<textarea rows="3" class="form-control" placeholder="Write your answer here..." disabled style="background:#f8fafc;"></textarea>';
                        }

                        html += '</div>';
                    });

                    html += '<div class="d-flex align-items-center justify-content-end gap-2 mt-4 pt-3 border-top">' +
                        '<button type="button" class="btn btn-primary rounded-3 px-4" disabled>' +
                            '<i class="ti ti-send me-1"></i> Submit Answers' +
                        '</button>' +
                    '</div>';
                }

                subWrap.innerHTML = html;
            }
        }

        // ── Task Preview Zoom Controls ──
        var taskPrevZoom = 1.0;
        function applyTaskPrevZoom(z) {
            taskPrevZoom = Math.max(0.5, Math.min(2.5, Math.round(z * 10) / 10));
            var wrap = document.getElementById('taskPreviewZoomWrapper');
            var lvl = document.getElementById('taskPreviewZoomLevel');
            if (wrap) wrap.style.transform = 'scale(' + taskPrevZoom + ')';
            if (lvl) lvl.textContent = Math.round(taskPrevZoom * 100) + '%';
        }

        var tZoomIn = document.getElementById('taskPreviewZoomInBtn');
        if (tZoomIn) {
            tZoomIn.addEventListener('click', function (e) {
                e.preventDefault();
                applyTaskPrevZoom(taskPrevZoom + 0.15);
            });
        }
        var tZoomOut = document.getElementById('taskPreviewZoomOutBtn');
        if (tZoomOut) {
            tZoomOut.addEventListener('click', function (e) {
                e.preventDefault();
                applyTaskPrevZoom(taskPrevZoom - 0.15);
            });
        }
        var tZoomReset = document.getElementById('taskPreviewZoomResetBtn');
        if (tZoomReset) {
            tZoomReset.addEventListener('click', function (e) {
                e.preventDefault();
                applyTaskPrevZoom(1.0);
            });
        }

        var btnPreview = document.getElementById('btnPreviewTask');
        if (btnPreview) {
            btnPreview.addEventListener('click', function () {
                applyTaskPrevZoom(1.0);
                renderTaskPreview();
                var modalEl = document.getElementById('taskPreviewModal');
                if (modalEl) {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    } else if (typeof $ !== 'undefined') {
                        $(modalEl).modal('show');
                    }
                }
            });
        }

        // ── Active Section Tracking (Google Forms Style) ────────────────
        function activateSection(card) {
            if (!card || !card.classList.contains('form-section-card')) return;
            if (card.style.display === 'none') return;
            if (card.classList.contains('is-active')) return;

            document.querySelectorAll('.form-section-card.is-active').forEach(function (el) {
                el.classList.remove('is-active');
            });
            card.classList.add('is-active');
        }

        function ensureValidActiveSection() {
            var cur = document.querySelector('.form-section-card.is-active');
            if (!cur || cur.style.display === 'none' || cur.offsetParent === null) {
                var allSections = document.querySelectorAll('.form-section-card');
                for (var i = 0; i < allSections.length; i++) {
                    if (allSections[i].style.display !== 'none' && allSections[i].offsetParent !== null) {
                        activateSection(allSections[i]);
                        break;
                    }
                }
            }
        }

        document.addEventListener('focusin', function (e) {
            var sec = e.target.closest('.form-section-card');
            if (sec) activateSection(sec);
        });

        document.addEventListener('click', function (e) {
            var sec = e.target.closest('.form-section-card');
            if (sec) activateSection(sec);
        });

        // ── Initial render ──────────────────────────────────────────────
        var initialFields = @json($initialFields ?? []);
        refreshTypeUI();
        setPickerLabel();
        syncPlacementPicker();
        ensureValidActiveSection();
        // No builder for course_assignment → never seed cards there.
        if (typeHasBuilder(currentType())) {
            initialFields.forEach(function (f) { addField(f); });
            if (initialFields.length === 0) addField();
        }
    })();
</script>
@endonce
