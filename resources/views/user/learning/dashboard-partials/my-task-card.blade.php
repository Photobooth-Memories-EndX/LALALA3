        @php
            $status     = $task->userStatusFor(auth()->user());
            $submission = $task->submissionFor(auth()->user());
            $answersByField = $submission ? $submission->answersByField() : collect();
        @endphp
@if (! ($inModal ?? false))
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
@else
        <div class="task-modal-card bg-white p-4 rounded-4 shadow-sm border">
@endif
                    {{-- Top Badges Row (Placed inside the task card) --}}
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge bg-primary-lt text-primary px-2.5 py-1 fs-6">
                                <i class="ti ti-file-text me-1"></i>{{ $task->typeLabel() }}
                            </span>
                            @if ($task->parentTitle())
                                <span class="badge bg-secondary-lt text-secondary px-2.5 py-1 fs-6">
                                    <i class="ti ti-bookmark me-1"></i>{{ $task->parentTitle() }}
                                </span>
                            @elseif ($task->isStandalone())
                                <span class="badge bg-secondary-lt text-secondary px-2.5 py-1 fs-6">
                                    <i class="ti ti-world me-1"></i>Global Task
                                </span>
                            @endif
                        </div>
                        <span class="badge {{ $statusBadges[$displayStatus($status)] }} px-2.5 py-1 fs-6">
                            {{ $statusLabels[$displayStatus($status)] }}
                        </span>
                    </div>

                    {{-- Title --}}
                    <h2 class="fw-bold text-dark mb-2">{{ $task->title }}</h2>

                    {{-- Meta Row with Divider --}}
                    <div class="d-flex flex-wrap align-items-center gap-3 text-muted small mb-3 pb-3 border-bottom">
                        @if ($task->deadline)
                            @php
                                $cdl = $task->deadline;
                                $cdlOverdue = $status === 'overdue' || ($cdl->isPast() && $status !== 'completed');
                                $hoursUntil = now()->diffInHours($cdl, false);
                                $isToday = in_array($status, ['not_started', 'in_progress', 'resubmit'], true) && ! $cdlOverdue && $hoursUntil <= 24 && $hoursUntil >= 0;
                                $isUrgent = in_array($status, ['not_started', 'in_progress', 'resubmit'], true) && ! $cdlOverdue && $hoursUntil <= 48 && $hoursUntil > 24;
                            @endphp
                            <span class="d-flex align-items-center gap-1">
                                <i class="ti ti-calendar-due text-primary me-1"></i>Deadline: <strong>{{ $cdl->format('d M Y H:i') }}</strong>
                                @if ($cdlOverdue)
                                    <span class="badge bg-danger ms-1"><i class="ti ti-alert-circle me-1"></i>Overdue</span>
                                @elseif ($isToday)
                                    <span class="badge bg-danger text-white ms-1"><i class="ti ti-flame me-1"></i>Due Today ({{ $hoursUntil }} hour{{ $hoursUntil > 1 ? 's' : '' }} left)</span>
                                @elseif ($isUrgent)
                                    <span class="badge bg-warning text-dark ms-1"><i class="ti ti-clock-exclamation me-1"></i>Due Soon</span>
                                @endif
                            </span>
                        @else
                            <span><i class="ti ti-calendar text-muted me-1"></i>No deadline set</span>
                        @endif
                        @if ($task->access_code)
                            <span><i class="ti ti-key text-warning me-1"></i>Access Code: <code>{{ $task->access_code }}</code></span>
                        @endif
                    </div>

                    {{-- Instructions & Overview --}}
                    @if ($task->description)
                        <div class="mb-4">
                            <h5 class="fw-semibold text-dark mb-2 d-flex align-items-center gap-1">
                                <i class="ti ti-notes text-dark"></i> Instructions & Overview
                            </h5>
                            <div class="text-dark" style="white-space: pre-wrap; line-height: 1.6; color: #000000;">{{ $task->description }}</div>
                        </div>
                    @endif

                    {{-- Reference Material --}}
                    @if ($task->attachment)
                        <div class="mb-4">
                            <h5 class="fw-semibold text-dark mb-2 d-flex align-items-center gap-1">
                                <i class="ti ti-paperclip text-primary"></i> Reference Material
                            </h5>
                            <div class="p-3 rounded-3 bg-light border d-inline-flex align-items-center gap-3">
                                <i class="ti ti-file-download text-primary fs-2"></i>
                                <div>
                                    <div class="fw-semibold text-dark small">{{ basename($task->attachment) }}</div>
                                    <div class="text-muted" style="font-size:11px;">Learners can download this attachment for guidance.</div>
                                </div>
                                <a href="{{ Storage::url($task->attachment) }}" target="_blank" class="btn btn-sm btn-outline-primary ms-2">
                                    <i class="ti ti-download me-1"></i> Download File
                                </a>
                            </div>
                        </div>
                    @endif

                    @if ($status === 'completed')
                        <div class="alert alert-success mb-0">
                            <i class="ti ti-check me-1"></i>
                            @if ($task->isCourseAssignment() && $task->link_type === 'lms')
                                Course completed.
                            @else
                                Reviewed by admin.
                            @endif
                            @if ($submission && !is_null($submission->score))
                                <span class="badge bg-success ms-2">Score: {{ $submission->score }}</span>
                            @endif
                        </div>
                        {{-- Summary of submitted answers --}}
                        @if ($answersByField->isNotEmpty() && $task->fields->isNotEmpty())
                            <div class="mt-2 small">
                                @foreach ($task->fields as $field)
                                    @php $ans = $answersByField->get($field->id); @endphp
                                    @if ($ans && ($ans->hasFiles() || $ans->answer_text))
                                        <div class="mb-1">
                                            <span class="fw-semibold">{{ $field->label }}:</span>
                                            @if ($ans->hasFiles())
                                                @foreach ($ans->filePaths() as $p)
                                                    <a href="{{ Storage::url($p) }}" target="_blank">{{ basename($p) }}</a>{{ ! $loop->last ? ', ' : '' }}
                                                @endforeach
                                            @endif
                                            @if ($field->isFile() && $ans->answer_text)
                                                <div class="mt-1">
                                                    <span class="fw-semibold">Drive link:</span>
                                                    <a href="{{ $ans->answer_text }}" target="_blank">{{ Str::limit($ans->answer_text, 60) }}</a>
                                                </div>
                                            @elseif (! $field->isFile() && $ans->answer_text)
                                                <span class="text-muted">{{ Str::limit($ans->answer_text, 100) }}</span>
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @elseif ($submission && $submission->file_path)
                            {{-- Legacy submission (single file, before the question builder): show the uploaded file --}}
                            <div class="mt-2 small">
                                <span class="fw-semibold">Your submitted file:</span>
                                <a href="{{ Storage::url($submission->file_path) }}" target="_blank">Download answer</a>
                                @if ($submission->submitted_at)
                                    <span class="text-muted">— submitted {{ $submission->submitted_at->format('d M Y H:i') }}</span>
                                @endif
                            </div>
                        @elseif ($submission && ! $task->isCourseAssignment())
                            <div class="mt-2 small text-muted">No answer file recorded for this submission.</div>
                        @endif
                        @if ($submission && $submission->feedback)
                            <div class="alert alert-info mt-2 mb-0">
                                <i class="ti ti-message-circle me-1"></i>
                                <strong>Admin feedback:</strong><br>{{ $submission->feedback }}
                            </div>
                        @endif
                          @elseif ($task->isCourseAssignment())
                        <div class="mb-2">
                            <h5 class="fw-semibold text-dark mb-3 d-flex align-items-center gap-1">
                                <i class="ti ti-book-2 text-primary"></i> Course Assignment
                            </h5>
                            @if ($task->link_type === 'external' && $task->external_url)
                                <div class="p-3 bg-light rounded-3 border text-center py-4">
                                    <i class="ti ti-external-link text-primary fs-1 mb-2 d-block"></i>
                                    <div class="fw-bold text-dark mb-1">External Course Link</div>
                                    <div class="text-muted small mb-3">Finish the course — this task will be marked complete automatically.</div>
                                    <a href="{{ $task->external_url }}" target="_blank"
                                       class="btn btn-primary rounded-3 px-4">
                                        <i class="ti ti-external-link me-1"></i>Open External Course
                                    </a>
                                </div>
                            @else
                                @php
                                    $targetPlan = $task->linkedPlan ?? $task->trainingPlan;
                                @endphp
                                @if ($targetPlan)
                                    <div class="p-3 bg-light rounded-3 border text-center py-4">
                                        <i class="ti ti-book-2 text-primary fs-1 mb-2 d-block"></i>
                                        <div class="fw-bold text-dark mb-1">{{ $targetPlan->title }}</div>
                                        <div class="text-muted small mb-3">Finish the course (100% progress) — this task will be marked complete automatically.</div>
                                        <a href="{{ route('user.playlist.show', $targetPlan->slug) }}"
                                           class="btn btn-primary rounded-3 px-4">
                                            <i class="ti ti-book-2 me-1"></i>Open Course
                                        </a>
                                    </div>
                                @else
                                    <div class="alert alert-warning mb-0">
                                        <i class="ti ti-info-circle me-1"></i> Course assignment link is not configured yet.
                                    </div>
                                @endif
                            @endif
                        </div>
                    @else
                        @if ($submission && ! $submission->resubmit_requested)
                            <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div>
                                    <i class="ti ti-clock me-1"></i>
                                    Answers submitted {{ $submission->submitted_at?->format('d M Y H:i') }} — waiting for admin review.
                                </div>
                                <span class="badge bg-warning text-dark">Pending</span>
                            </div>
                        @endif

                        @if (! $submission || $submission->resubmit_requested)
                        @if ($submission && $submission->resubmit_requested)
                            <div class="alert alert-warning border-start border-warning border-4 rounded-3 shadow-sm mb-3">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="avatar bg-warning text-dark rounded-circle me-1 mt-1 shadow-sm">
                                        <i class="ti ti-refresh fs-2"></i>
                                    </span>
                                    <div class="flex-grow-1">
                                        <h4 class="alert-title fw-bold text-dark mb-1">
                                            Task Revision Requested
                                        </h4>
                                        <div class="text-muted small mb-2">
                                            The administrator reviewed your answers and requested revisions. Please check the feedback below and submit your revised answers.
                                        </div>
                                        @if ($submission->feedback)
                                            <div class="p-3 bg-white bg-opacity-75 rounded-3 border border-warning-subtle text-dark shadow-sm">
                                                <div class="fw-semibold text-warning d-flex align-items-center mb-1">
                                                    <i class="ti ti-message-dots me-1"></i> Admin Feedback & Notes:
                                                </div>
                                                <div class="ps-3 border-start border-3 border-warning text-dark">
                                                    {{ $submission->feedback }}
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                        {{-- Dynamic answer form following the task fields --}}
                        <form action="{{ route('my-task.submit', $task) }}" method="POST" enctype="multipart/form-data" class="mt-2">
                            @csrf
                            <input type="hidden" name="active_task_id" value="{{ $task->id }}">
                            <h5 class="fw-semibold text-dark mb-3 d-flex align-items-center gap-1">
                                <i class="ti ti-pencil text-primary"></i> {{ $submission && $submission->resubmit_requested ? 'Resubmit Answers' : 'Learner Submission Form' }}
                            </h5>

                            @foreach ($task->fields as $field)
                                @php
                                    $ans = $answersByField->get($field->id);
                                    $fieldKey = 'fields.' . $field->id;
                                    $hasFile = $ans && $ans->hasFiles();
                                @endphp
                                <div class="mb-4 pb-3 border-bottom">
                                    <label class="form-label fw-semibold text-dark mb-2">
                                        {{ $field->label }}
                                        @if ($field->required)
                                            <span class="text-danger">*</span>
                                        @endif
                                        <span class="text-muted fw-normal small">({{ $field->typeLabel() }})</span>
                                    </label>

                                    @if ($field->isFile())
                                        @php
                                            $existingPaths = $ans ? $ans->filePaths() : [];
                                            $hasSavedLink = $ans && ! empty($ans->answer_text);
                                            $fileChoice = old('file_choice.' . $field->id, $hasSavedLink ? 'link' : 'file');
                                        @endphp
                                        @if (! empty($existingPaths))
                                            <div class="form-hint mb-2">
                                                Current file{{ count($existingPaths) > 1 ? 's' : '' }}:
                                                @foreach ($existingPaths as $p)
                                                    <a href="{{ Storage::url($p) }}" target="_blank">{{ basename($p) }}</a>{{ ! $loop->last ? ',' : '' }}
                                                @endforeach
                                                — upload new {{ count($existingPaths) > 1 ? 'ones' : 'one' }} to replace.
                                            </div>
                                        @endif
                                        @if ($field->allow_link)
                                            <div class="mb-2">
                                                <label class="form-check form-check-inline mb-0">
                                                    <input type="radio" class="form-check-input file-choice-radio"
                                                           name="file_choice[{{ $field->id }}]" value="file"
                                                           data-field="{{ $field->id }}"
                                                           {{ $fileChoice === 'file' ? 'checked' : '' }}>
                                                    <span class="form-check-label">Upload file</span>
                                                </label>
                                                <label class="form-check form-check-inline mb-0">
                                                    <input type="radio" class="form-check-input file-choice-radio"
                                                           name="file_choice[{{ $field->id }}]" value="link"
                                                           data-field="{{ $field->id }}"
                                                           {{ $fileChoice === 'link' ? 'checked' : '' }}>
                                                    <span class="form-check-label">Drive link</span>
                                                </label>
                                            </div>
                                        @endif
                                        <div id="fileWrap{{ $field->id }}" class="task-file-wrap"@if ($field->allow_link && $fileChoice === 'link') style="display:none"@endif>
                                            <div class="task-dropzone border border-2 border-dashed rounded-3 p-4 text-center bg-light-subtle position-relative @error($fieldKey) border-danger @enderror"
                                                 id="dropzone-{{ $field->id }}"
                                                 data-field-id="{{ $field->id }}"
                                                 data-max-files="{{ $field->maxFiles() }}"
                                                 data-max-size-mb="{{ $field->maxSizeMb() }}"
                                                 data-allowed-exts="{{ strtolower(implode(',', $field->allowedExtensions() ?? [])) }}"
                                                 style="cursor: pointer; transition: all 0.2s ease; border-color: @error($fieldKey) #d63939 @else #cbd5e1 @enderror !important;">

                                                <input type="file"
                                                       id="fileInput{{ $field->id }}"
                                                       name="fields[{{ $field->id }}][]"
                                                       class="task-file-input position-absolute top-0 start-0 w-100 h-100 opacity-0"
                                                       style="cursor: pointer; z-index: 5;"
                                                       @if ($field->maxFiles() > 1) multiple @endif
                                                       @if ($field->acceptAttribute()) accept="{{ $field->acceptAttribute() }}" @endif
                                                       {{ $field->required && empty($existingPaths) && ! $field->allow_link ? 'required' : '' }}>

                                                <!-- Default Prompt State -->
                                                <div class="dropzone-prompt">
                                                    <div class="avatar avatar-md rounded-circle bg-primary-lt text-primary mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                                        <i class="ti ti-cloud-upload fs-1"></i>
                                                    </div>
                                                    <div class="fw-semibold text-dark mb-1">
                                                        Tarik &amp; lepas file ke sini, atau <span class="text-primary text-decoration-underline">pilih file</span>
                                                    </div>
                                                    <div class="text-muted small" style="font-size: 11.5px;">
                                                        Format: <strong>{{ ! empty($field->allowedExtensions()) ? strtoupper(implode(', ', $field->allowedExtensions())) : 'Semua format file' }}</strong>
                                                        @if ($field->allowedTypesLabel())
                                                            <span class="badge bg-secondary-lt text-secondary ms-1">{{ $field->allowedTypesLabel() }}</span>
                                                        @endif
                                                        | Maksimal <strong>{{ $field->maxFiles() }} file{{ $field->maxFiles() > 1 ? 's' : '' }}</strong>, maks. <strong>{{ $field->maxSizeMb() }} MB</strong> per file
                                                    </div>
                                                    @if ($field->allow_link)
                                                        <div class="mt-2">
                                                            <span class="badge bg-info-lt text-info d-inline-flex align-items-center">
                                                                <i class="ti ti-link me-1"></i> Mendukung Link Drive
                                                            </span>
                                                        </div>
                                                    @endif
                                                </div>

                                                <!-- Dragover Overlay Indicator -->
                                                <div class="dropzone-dragover-indicator d-none position-absolute top-0 start-0 w-100 h-100 rounded-3 d-flex flex-column align-items-center justify-content-center bg-primary-lt" style="pointer-events: none; z-index: 6;">
                                                    <i class="ti ti-download fs-1 text-primary mb-1 animate-bounce"></i>
                                                    <span class="fw-bold text-primary">Lepaskan file di sini</span>
                                                </div>

                                                <!-- Selected Files State -->
                                                <div class="dropzone-preview d-none text-start pt-1 position-relative" style="z-index: 6;">
                                                    <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
                                                        <span class="fw-semibold small text-dark d-flex align-items-center gap-1">
                                                            <i class="ti ti-circle-check text-success"></i> File Dipilih:
                                                        </span>
                                                        <button type="button" class="btn btn-sm btn-ghost-danger py-0 px-2 rounded-2 dropzone-clear-btn" data-field-id="{{ $field->id }}" title="Hapus dan pilih ulang">
                                                            <i class="ti ti-x me-1"></i> Ganti File
                                                        </button>
                                                    </div>
                                                    <div class="selected-files-list d-flex flex-column gap-1"></div>
                                                </div>

                                                <!-- Inline Error message -->
                                                <div class="dropzone-error-msg alert alert-danger py-1 px-2 mt-2 small text-start d-none" style="z-index: 7; position: relative;"></div>
                                            </div>
                                        </div>
                                        @if ($field->allow_link)
                                            @php $linkKey = 'drive_links.' . $field->id; @endphp
                                            <div id="linkWrap{{ $field->id }}" class="mt-2"@if ($fileChoice === 'file') style="display:none"@endif>
                                                <label class="form-label small mb-1">Paste a Drive link</label>
                                                <input type="url"
                                                       name="drive_links[{{ $field->id }}]"
                                                       class="form-control @error($linkKey) is-invalid @enderror"
                                                       placeholder="https://drive.google.com/..."
                                                       value="{{ old($linkKey, $ans->answer_text ?? '') }}">
                                                <div class="form-hint small mt-1 text-muted">
                                                    <i class="ti ti-info-circle me-1"></i>Make sure the Google Drive / OneDrive link permission is set to <em>"Anyone with the link can view"</em> so the administrator can review your answer.
                                                </div>
                                                @error($linkKey)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        @endif
                                    @elseif ($field->isChoice())
                                        <div class="d-flex flex-column gap-2 mt-2">
                                            @foreach ($field->options ?? [] as $opt)
                                                @php
                                                    $checked = old($fieldKey, $ans->answer_text ?? null) === $opt;
                                                @endphp
                                                <label class="form-check mb-0">
                                                    <input type="radio" class="form-check-input @error($fieldKey) is-invalid @enderror"
                                                           name="fields[{{ $field->id }}]" value="{{ $opt }}"
                                                           {{ $checked ? 'checked' : '' }}
                                                           {{ $field->required ? 'required' : '' }}>
                                                    <span class="form-check-label">{{ $opt }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @else
                                        <textarea name="fields[{{ $field->id }}]" rows="3"
                                                  class="form-control @error($fieldKey) is-invalid @enderror"
                                                  placeholder="Type your answer here..."
                                                  {{ $field->required ? 'required' : '' }}>{{ old($fieldKey, $ans->answer_text ?? '') }}</textarea>
                                    @endif

                                    @error($fieldKey)
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    @php
                                        $nestedErrs = [];
                                        foreach ($errors->keys() as $ek) {
                                            if (str_starts_with($ek, $fieldKey . '.')) {
                                                foreach ((array) $errors->get($ek) as $em) $nestedErrs[] = $em;
                                            }
                                        }
                                        $nestedErrs = array_unique($nestedErrs);
                                    @endphp
                                    @foreach ($nestedErrs as $ne)
                                        <div class="invalid-feedback d-block">{{ $ne }}</div>
                                    @endforeach
                                </div>
                            @endforeach

                            @if ($task->fields->isEmpty())
                                <div class="alert alert-warning">This task has no questions yet.</div>
                            @endif

                            <div class="d-flex align-items-center justify-content-end gap-2 mt-4 pt-3 border-top">
                                @if ($inModal ?? false)
                                    <button type="button" class="btn btn-outline-secondary rounded-3 px-3" data-bs-dismiss="modal">
                                        Close
                                    </button>
                                @endif
                                <button type="submit" class="btn btn-primary rounded-3 px-4">
                                    <i class="ti ti-send me-1"></i> {{ $submission && $submission->resubmit_requested ? 'Resubmit Answers' : 'Submit Answers' }}
                                </button>
                            </div>
                        </form>

                        @endif {{-- show form only when not submitted yet or resubmit was requested --}}
                    @endif {{-- status: completed / course assignment / answer form --}}

                    @if (($inModal ?? false) && ($status === 'completed' || $task->isCourseAssignment() || ($submission && ! $submission->resubmit_requested)))
                        <div class="d-flex align-items-center justify-content-end gap-2 mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-outline-secondary rounded-3 px-3" data-bs-dismiss="modal">
                                Close
                            </button>
                        </div>
                    @endif
@if (! ($inModal ?? false))
                </div>
            </div>
        </div>
@else
        </div>
@endif

@once
<style>
.task-dropzone {
    border-color: #cbd5e1 !important;
    transition: all 0.2s ease-in-out;
}
.task-dropzone:hover {
    border-color: #206bc4 !important;
    background-color: #f8fafc !important;
}
.task-dropzone.is-dragover {
    border-color: #206bc4 !important;
    background-color: #f0f7ff !important;
    transform: scale(1.005);
}
.task-dropzone .animate-bounce {
    animation: dropzoneBounce 1s infinite;
}
@keyframes dropzoneBounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}
</style>
<script>
(function () {
    function formatBytes(bytes) {
        if (!bytes || bytes === 0) return '0 B';
        var k = 1024;
        var sizes = ['B', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    function getFileIconClass(filename) {
        var ext = (filename || '').split('.').pop().toLowerCase();
        if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'].indexOf(ext) !== -1) return 'ti ti-photo text-info';
        if (['pdf'].indexOf(ext) !== -1) return 'ti ti-file-text text-danger';
        if (['doc', 'docx'].indexOf(ext) !== -1) return 'ti ti-file-description text-primary';
        if (['xls', 'xlsx', 'csv'].indexOf(ext) !== -1) return 'ti ti-file-spreadsheet text-success';
        if (['zip', 'rar', '7z', 'tar', 'gz'].indexOf(ext) !== -1) return 'ti ti-file-zip text-warning';
        if (['mp4', 'mov', 'avi', 'mkv', 'webm'].indexOf(ext) !== -1) return 'ti ti-video text-purple';
        return 'ti ti-file text-secondary';
    }

    function updateDropzonePreview(input) {
        var dropzone = input.closest('.task-dropzone');
        if (!dropzone) return;

        var promptEl = dropzone.querySelector('.dropzone-prompt');
        var previewEl = dropzone.querySelector('.dropzone-preview');
        var listEl = dropzone.querySelector('.selected-files-list');
        var errorEl = dropzone.querySelector('.dropzone-error-msg');
        var maxSizeMb = parseFloat(dropzone.dataset.maxSizeMb || '10');
        var maxSizeBytes = maxSizeMb * 1024 * 1024;
        var allowedExtsStr = (dropzone.dataset.allowedExts || '').trim();
        var allowedExts = allowedExtsStr ? allowedExtsStr.split(',').map(function (s) { return s.trim().toLowerCase(); }).filter(Boolean) : [];

        var files = input.files;
        if (!files || files.length === 0) {
            if (promptEl) promptEl.classList.remove('d-none');
            if (previewEl) previewEl.classList.add('d-none');
            if (listEl) listEl.innerHTML = '';
            if (errorEl) {
                errorEl.textContent = '';
                errorEl.classList.add('d-none');
            }
            dropzone.style.borderColor = '#cbd5e1';
            input.setCustomValidity('');
            return;
        }

        var errors = [];
        var listHtml = '';

        for (var i = 0; i < files.length; i++) {
            var f = files[i];
            var ext = (f.name || '').split('.').pop().toLowerCase();

            if (f.size > maxSizeBytes) {
                errors.push('File "' + f.name + '" (' + formatBytes(f.size) + ') melebihi batas maksimal ' + maxSizeMb + ' MB.');
            }

            if (allowedExts.length > 0 && allowedExts.indexOf(ext) === -1) {
                errors.push('Format file "' + f.name + '" tidak diizinkan. Hanya format: ' + allowedExts.join(', ').toUpperCase());
            }

            var icon = getFileIconClass(f.name);
            listHtml += '<div class="d-flex align-items-center justify-content-between p-2 rounded-2 bg-white border">' +
                '<div class="d-flex align-items-center gap-2 overflow-hidden me-2">' +
                    '<i class="' + icon + ' fs-2 flex-shrink-0"></i>' +
                    '<div class="text-truncate">' +
                        '<div class="fw-semibold text-dark text-truncate" style="font-size:12px;">' + escapeHtml(f.name) + '</div>' +
                        '<div class="text-muted" style="font-size:11px;">' + formatBytes(f.size) + '</div>' +
                    '</div>' +
                '</div>' +
                '<span class="badge bg-success-lt text-success flex-shrink-0">' +
                    '<i class="ti ti-check me-1"></i> Siap upload' +
                '</span>' +
            '</div>';
        }

        if (listEl) listEl.innerHTML = listHtml;
        if (promptEl) promptEl.classList.add('d-none');
        if (previewEl) previewEl.classList.remove('d-none');

        if (errorEl) {
            if (errors.length > 0) {
                errorEl.innerHTML = '<i class="ti ti-alert-triangle me-1"></i> ' + errors.join('<br>');
                errorEl.classList.remove('d-none');
                dropzone.style.borderColor = '#d63939';
                input.setCustomValidity(errors[0]);
            } else {
                errorEl.textContent = '';
                errorEl.classList.add('d-none');
                dropzone.style.borderColor = '#2fb344';
                input.setCustomValidity('');
            }
        }
    }

    function syncFileChoice(radio) {
        var fieldId = radio.getAttribute('data-field');
        var fileWrap = document.getElementById('fileWrap' + fieldId);
        var linkWrap = document.getElementById('linkWrap' + fieldId);
        var showFile = radio.value === 'file';
        if (fileWrap) fileWrap.style.display = showFile ? '' : 'none';
        if (linkWrap) linkWrap.style.display = showFile ? 'none' : '';
        // Bersihkan input yang disembunyikan agar tidak ikut tervalidasi/terkirim.
        if (showFile && linkWrap) {
            var linkInput = linkWrap.querySelector('input[name^="drive_links"]');
            if (linkInput) linkInput.value = '';
        } else if (!showFile && fileWrap) {
            var fileInput = fileWrap.querySelector('input[type="file"]');
            if (fileInput) {
                fileInput.value = '';
                updateDropzonePreview(fileInput);
            }
        }
    }

    document.addEventListener('change', function (e) {
        if (e.target && e.target.classList && e.target.classList.contains('file-choice-radio')) {
            syncFileChoice(e.target);
        }
        if (e.target && e.target.classList && e.target.classList.contains('task-file-input')) {
            updateDropzonePreview(e.target);
        }
    });

    // Drag and Drop Event Listeners
    ['dragenter', 'dragover'].forEach(function (eventName) {
        document.addEventListener(eventName, function (e) {
            var dz = e.target.closest('.task-dropzone');
            if (dz) {
                e.preventDefault();
                dz.classList.add('is-dragover');
                var ind = dz.querySelector('.dropzone-dragover-indicator');
                if (ind) ind.classList.remove('d-none');
            }
        }, false);
    });

    ['dragleave', 'dragend'].forEach(function (eventName) {
        document.addEventListener(eventName, function (e) {
            var dz = e.target.closest('.task-dropzone');
            if (dz && (!e.relatedTarget || !dz.contains(e.relatedTarget))) {
                dz.classList.remove('is-dragover');
                var ind = dz.querySelector('.dropzone-dragover-indicator');
                if (ind) ind.classList.add('d-none');
            }
        }, false);
    });

    document.addEventListener('drop', function (e) {
        var dz = e.target.closest('.task-dropzone');
        if (dz) {
            e.preventDefault();
            dz.classList.remove('is-dragover');
            var ind = dz.querySelector('.dropzone-dragover-indicator');
            if (ind) ind.classList.add('d-none');

            var files = e.dataTransfer ? e.dataTransfer.files : null;
            if (files && files.length > 0) {
                var input = dz.querySelector('.task-file-input');
                if (input) {
                    var maxFiles = parseInt(dz.dataset.maxFiles || '1', 10);
                    var dt = new DataTransfer();
                    var count = Math.min(files.length, maxFiles);
                    for (var i = 0; i < count; i++) {
                        dt.items.add(files[i]);
                    }
                    input.files = dt.files;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        }
    }, false);

    // Click on "Ganti File" button
    document.addEventListener('click', function (e) {
        var clearBtn = e.target.closest('.dropzone-clear-btn');
        if (clearBtn) {
            e.preventDefault();
            e.stopPropagation();
            var dz = clearBtn.closest('.task-dropzone');
            if (dz) {
                var input = dz.querySelector('.task-file-input');
                if (input) {
                    input.value = '';
                    updateDropzonePreview(input);
                }
            }
        }
    });
})();
</script>
@endonce
