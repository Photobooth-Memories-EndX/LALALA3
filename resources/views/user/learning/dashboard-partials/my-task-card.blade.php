        @php
            $status     = $task->userStatusFor(auth()->user());
            $submission = $task->submissionFor(auth()->user());
            $answersByField = $submission ? $submission->answersByField() : collect();
        @endphp
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
                        <div>
                            <h3 class="card-title mb-1">{{ $task->title }}</h3>
                            @if ($task->deadline)
                                @php
                                    $cdl = $task->deadline;
                                    $cdlOverdue = $status === 'overdue' || ($cdl->isPast() && $status !== 'completed');
                                    $hoursUntil = now()->diffInHours($cdl, false);
                                    $isToday = in_array($status, ['not_started', 'in_progress', 'resubmit'], true) && ! $cdlOverdue && $hoursUntil <= 24 && $hoursUntil >= 0;
                                    $isUrgent = in_array($status, ['not_started', 'in_progress', 'resubmit'], true) && ! $cdlOverdue && $hoursUntil <= 48 && $hoursUntil > 24;
                                @endphp
                                <div class="text-muted small d-flex align-items-center flex-wrap gap-1">
                                    <span><i class="ti ti-calendar-due me-1"></i>Deadline: {{ $cdl->format('d M Y H:i') }}</span>
                                    @if ($cdlOverdue)
                                        <span class="badge bg-danger ms-1"><i class="ti ti-alert-circle me-1"></i>Overdue</span>
                                    @elseif ($isToday)
                                        <span class="badge bg-danger text-white ms-1"><i class="ti ti-flame me-1"></i>Tenggat Hari Ini ({{ $hoursUntil }} jam lagi)</span>
                                    @elseif ($isUrgent)
                                        <span class="badge bg-warning text-dark ms-1"><i class="ti ti-clock-exclamation me-1"></i>Mendekati Tenggat (H-1 / H-2)</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="d-flex flex-wrap gap-1">
                            <span class="badge bg-primary-lt text-primary">{{ $task->typeLabel() }}</span>
                            <span class="badge {{ $statusBadges[$displayStatus($status)] }}">{{ $statusLabels[$displayStatus($status)] }}</span>
                        </div>
                    </div>

                    @if ($task->description)
                        <p class="text-muted mb-2">{{ $task->description }}</p>
                    @endif

                    @if ($task->attachment)
                        <div class="mb-3">
                            <a href="{{ Storage::url($task->attachment) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-download me-1"></i>Download task attachment
                            </a>
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
                        @if ($task->link_type === 'external' && $task->external_url)
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <a href="{{ $task->external_url }}" target="_blank"
                                   class="btn btn-primary">
                                    <i class="ti ti-external-link me-1"></i>Open External Course
                                </a>
                            </div>
                            <div class="form-hint mt-1">Finish the course — this task will be marked complete automatically.</div>
                        @else
                            @php
                                $targetPlan = $task->linkedPlan ?? $task->trainingPlan;
                            @endphp
                            @if ($targetPlan)
                                {{-- ── Course Assignment (LMS course): auto-completes at 100% progress ── --}}
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <a href="{{ route('user.playlist.show', $targetPlan->slug) }}"
                                       class="btn btn-primary">
                                        <i class="ti ti-book-2 me-1"></i>Open Course: {{ $targetPlan->title }}
                                    </a>
                                </div>
                                <div class="form-hint mt-1">Finish the course (100% progress) — this task will be marked complete automatically.</div>
                            @else
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <span class="text-muted"><i class="ti ti-info-circle me-1"></i>Course assignment link is not configured yet.</span>
                                </div>
                            @endif
                        @endif
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
                                            Permintaan Revisi Tugas
                                        </h4>
                                        <div class="text-muted small mb-2">
                                            Admin telah memeriksa jawaban Anda dan meminta perbaikan. Silakan periksa catatan dari Admin di bawah ini dan kirimkan kembali jawaban yang sudah direvisi.
                                        </div>
                                        @if ($submission->feedback)
                                            <div class="p-3 bg-white bg-opacity-75 rounded-3 border border-warning-subtle text-dark shadow-sm">
                                                <div class="fw-semibold text-warning d-flex align-items-center mb-1">
                                                    <i class="ti ti-message-dots me-1"></i> Catatan & Feedback dari Admin:
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
                            <div class="mb-2 fw-semibold small">
                                {{ $submission && $submission->resubmit_requested ? 'Resubmit answers' : 'Submit answers' }}
                            </div>

                            @foreach ($task->fields as $field)
                                @php
                                    $ans = $answersByField->get($field->id);
                                    $fieldKey = 'fields.' . $field->id;
                                    $hasFile = $ans && $ans->hasFiles();
                                @endphp
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">
                                        {{ $field->label }}
                                        @if ($field->required)
                                            <span class="text-danger">*</span>
                                        @endif
                                        <span class="text-muted fw-normal">({{ $field->typeLabel() }})</span>
                                    </label>

                                    @if ($field->isFile())
                                        @php
                                            $existingPaths = $ans ? $ans->filePaths() : [];
                                            $hasSavedLink = $ans && ! empty($ans->answer_text);
                                            $fileChoice = old('file_choice.' . $field->id, $hasSavedLink ? 'link' : 'file');
                                        @endphp
                                        @if (! empty($existingPaths))
                                            <div class="form-hint mb-1">
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
                                        <div id="fileWrap{{ $field->id }}"@if ($field->allow_link && $fileChoice === 'link') style="display:none"@endif>
                                            <input type="file"
                                                   name="fields[{{ $field->id }}][]"
                                                   class="form-control @error($fieldKey) is-invalid @enderror"
                                                   @if ($field->maxFiles() > 1) multiple @endif
                                                   @if ($field->acceptAttribute()) accept="{{ $field->acceptAttribute() }}" @endif
                                                   {{ $field->required && empty($existingPaths) && ! $field->allow_link ? 'required' : '' }}>
                                            <div class="p-2 mt-2 bg-light-subtle rounded-3 border d-flex flex-wrap align-items-center justify-content-between gap-2 text-muted small">
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="ti ti-file-upload text-primary fs-3"></i>
                                                    <div>
                                                        <div>
                                                            <strong>Format diizinkan:</strong>
                                                            @if (! empty($field->allowedExtensions()))
                                                                <span class="text-dark fw-semibold">{{ strtoupper(implode(', ', $field->allowedExtensions())) }}</span>
                                                                @if ($field->allowedTypesLabel())
                                                                    <span class="badge bg-secondary-lt text-secondary ms-1">{{ $field->allowedTypesLabel() }}</span>
                                                                @endif
                                                            @else
                                                                <span class="text-dark">Semua format file</span>
                                                            @endif
                                                        </div>
                                                        <div class="text-muted">
                                                            Maksimal <strong>{{ $field->maxFiles() }} file</strong>, maks. <strong>{{ $field->maxSizeMb() }} MB</strong> per file.
                                                        </div>
                                                    </div>
                                                </div>
                                                @if ($field->allow_link)
                                                    <span class="badge bg-info-lt text-info d-inline-flex align-items-center">
                                                        <i class="ti ti-link me-1"></i> Mendukung Link Drive
                                                    </span>
                                                @endif
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
                                                    <i class="ti ti-info-circle me-1"></i>Pastikan izin akses tautan Google Drive / OneDrive diatur ke <em>"Anyone with the link can view"</em> agar Admin dapat memeriksa jawaban Anda.
                                                </div>
                                                @error($linkKey)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        @endif
                                    @elseif ($field->isChoice())
                                        @foreach ($field->options ?? [] as $opt)
                                            @php
                                                $checked = old($fieldKey, $ans->answer_text ?? null) === $opt;
                                            @endphp
                                            <label class="form-check">
                                                <input type="radio" class="form-check-input @error($fieldKey) is-invalid @enderror"
                                                       name="fields[{{ $field->id }}]" value="{{ $opt }}"
                                                       {{ $checked ? 'checked' : '' }}
                                                       {{ $field->required ? 'required' : '' }}>
                                                <span class="form-check-label">{{ $opt }}</span>
                                            </label>
                                        @endforeach
                                    @else
                                        <textarea name="fields[{{ $field->id }}]" rows="2"
                                                  class="form-control @error($fieldKey) is-invalid @enderror"
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

                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-upload me-1"></i>{{ $submission && $submission->resubmit_requested ? 'Resubmit' : 'Submit' }}
                            </button>
                        </form>

                        @endif {{-- show form only when not submitted yet or resubmit was requested --}}
                    @endif {{-- status: completed / course assignment / answer form --}}
                </div>
            </div>
        </div>

@once
<script>
(function () {
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
            if (fileInput) fileInput.value = '';
        }
    }
    document.addEventListener('change', function (e) {
        if (e.target && e.target.classList && e.target.classList.contains('file-choice-radio')) {
            syncFileChoice(e.target);
        }
    });
})();
</script>
@endonce
