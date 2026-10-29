        @php
            $status     = $task->userStatusFor(auth()->user());
            $submission = $task->submissionFor(auth()->user());
            $answersByField = $submission ? $submission->answersByField() : collect();
            $cdl        = $task->deadline;
            $isOverdue  = $status === 'overdue' || ($cdl && $cdl->isPast() && $status !== 'completed');
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
                                    <i class="ti ti-world me-1"></i>Others
                                </span>
                            @endif
                        </div>
                        @if ($submission && $submission->status === 'draft')
                            <span class="badge {{ $isOverdue ? 'bg-danger text-white' : 'bg-warning-lt text-warning' }} px-2.5 py-1 fs-6">
                                <i class="ti {{ $isOverdue ? 'ti-alert-circle' : 'ti-file-pencil' }} me-1"></i>Draft
                            </span>
                        @elseif ($submission && $submission->resubmit_requested)
                            <span class="badge {{ $isOverdue ? 'bg-danger text-white' : 'bg-warning text-dark' }} px-2.5 py-1 fs-6">
                                Rejected
                            </span>
                        @else
                            <span class="badge {{ $statusBadges[$displayStatus($status)] }} px-2.5 py-1 fs-6">
                                {{ $statusLabels[$displayStatus($status)] }}
                            </span>
                        @endif
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
                                    <div class="text-muted" style="font-size:11px;">Users can download this attachment for guidance.</div>
                                </div>
                                <a href="{{ Storage::url($task->attachment) }}" target="_blank" class="btn btn-sm btn-outline-primary ms-2">
                                    <i class="ti ti-download me-1"></i> Download File
                                </a>
                            </div>
                        </div>
                    @endif

                    @if ($task->isCourseAssignment())
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
                                            <i class="ti ti-book-2 me-1"></i>Open Course: {{ $targetPlan->title }}
                                        </a>
                                    </div>
                                @else
                                    <div class="alert alert-warning mb-0">
                                        <i class="ti ti-info-circle me-1"></i> Course assignment link is not configured yet.
                                    </div>
                                @endif
                            @endif
                        </div>
                    @elseif ($submission && ! $submission->resubmit_requested && $submission->status !== 'draft')
                        {{-- ══════════════════════════════════════════════════════════════════════════ --}}
                        {{-- VIEW SUBMISSION (Submitted by user: Completed, Pending, or Rejected) --}}
                        {{-- ══════════════════════════════════════════════════════════════════════════ --}}
                        @if ($status === 'completed' || $submission->status === 'completed')
                            <div class="alert alert-success border-start border-success border-4 rounded-3 shadow-sm mb-3">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="avatar bg-success text-white rounded-circle me-1 mt-1 shadow-sm">
                                        <i class="ti ti-check fs-2"></i>
                                    </span>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
                                            <h4 class="alert-title fw-bold text-dark mb-0">Reviewed by admin</h4>
                                            <span class="badge bg-success text-white">Completed</span>
                                        </div>
                                        <div class="text-muted small mb-2">
                                            Your answers have been reviewed and approved by the administrator.
                                            @if ($submission->submitted_at)
                                                &middot; Submitted {{ $submission->submitted_at->format('d M Y H:i') }}
                                            @endif
                                        </div>
                                        @if (!is_null($submission->score))
                                            <div class="mb-2">
                                                <span class="badge bg-success-lt text-success fw-bold fs-6">Score: {{ $submission->score }}</span>
                                            </div>
                                        @endif
                                        @if ($submission->feedback)
                                            <div class="p-3 bg-white rounded-3 border border-success-subtle text-dark shadow-sm mt-2">
                                                <div class="fw-semibold text-success d-flex align-items-center mb-1">
                                                    <i class="ti ti-message-dots me-1"></i> Admin Feedback:
                                                </div>
                                                <div class="ps-3 border-start border-3 border-success text-dark">
                                                    {{ $submission->feedback }}
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @elseif ($submission->status === 'rejected')
                            <div class="alert alert-danger border-start border-danger border-4 rounded-3 shadow-sm mb-3">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="avatar bg-danger text-white rounded-circle me-1 mt-1 shadow-sm">
                                        <i class="ti ti-circle-x fs-2"></i>
                                    </span>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
                                            <h4 class="alert-title fw-bold text-dark mb-0">
                                                Submission Rejected
                                            </h4>
                                            <span class="badge bg-danger text-white">Completed as Rejected</span>
                                        </div>
                                        <div class="text-muted small mb-2">
                                            The administrator reviewed your answers and marked this task as completed as rejected. This task is now closed.
                                            @if ($submission->submitted_at)
                                                &middot; Submitted {{ $submission->submitted_at->format('d M Y H:i') }}
                                            @endif
                                        </div>
                                        @if (!is_null($submission->score))
                                            <div class="mb-2">
                                                <span class="badge bg-danger-lt text-danger fw-bold fs-6">Score: {{ $submission->score }} / 100</span>
                                            </div>
                                        @endif
                                        @if ($submission->feedback)
                                            <div class="p-3 bg-white rounded-3 border border-danger-subtle text-dark shadow-sm">
                                                <div class="fw-semibold text-danger d-flex align-items-center mb-1">
                                                    <i class="ti ti-message-dots me-1"></i> Admin Feedback:
                                                </div>
                                                <div class="ps-3 border-start border-3 border-danger text-dark">
                                                    {{ $submission->feedback }}
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @else
                            {{-- Pending Review --}}
                            <div class="alert alert-warning border-start border-warning border-4 rounded-3 shadow-sm mb-3">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="avatar bg-warning text-dark rounded-circle me-1 mt-1 shadow-sm">
                                        <i class="ti ti-clock fs-2"></i>
                                    </span>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
                                            <h4 class="alert-title fw-bold text-dark mb-0">Answers Submitted</h4>
                                            <span class="badge bg-warning text-dark">Pending review</span>
                                        </div>
                                        <div class="text-muted small">
                                            Answers submitted {{ $submission->submitted_at?->format('d M Y H:i') }} — waiting for admin review.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Full Submission Review: All Questions and User Answers --}}
                        <div class="mt-4 pt-3 border-top">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                    <i class="ti ti-file-text text-primary"></i> Your Submitted Answers
                                </h5>
                                <span class="badge bg-light text-secondary border">
                                    {{ $task->fields->count() }} {{ Str::plural('Question', $task->fields->count()) }}
                                </span>
                            </div>

                            @if ($answersByField->isNotEmpty() && $task->fields->isNotEmpty())
                                <div class="d-flex flex-column gap-3">
                                    @foreach ($task->fields as $idx => $field)
                                        @php
                                            $ans = $answersByField->get($field->id);
                                        @endphp
                                        <div class="card border rounded-3 shadow-none bg-white">
                                            <div class="card-header bg-light-subtle py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-primary-lt text-primary fw-bold" style="font-size: 11px;">Q{{ $idx + 1 }}</span>
                                                    <span class="fw-bold text-dark fs-6">{{ $field->label }}</span>
                                                    @if ($field->required)
                                                        <span class="text-danger" title="Required">*</span>
                                                    @endif
                                                </div>
                                                <span class="badge bg-secondary-lt text-secondary small">{{ $field->typeLabel() }}</span>
                                            </div>
                                            <div class="card-body p-3">
                                                @if ($field->isFile())
                                                    @if ($ans && $ans->hasFiles())
                                                        <div class="d-flex flex-column gap-2 mb-2">
                                                            @foreach ($ans->filePaths() as $filePath)
                                                                @php
                                                                    $fileName = basename($filePath);
                                                                    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                                                                    $iconClass = match($fileExt) {
                                                                        'pdf' => 'ti ti-file-text text-danger',
                                                                        'doc', 'docx' => 'ti ti-file-description text-primary',
                                                                        'xls', 'xlsx', 'csv' => 'ti ti-file-spreadsheet text-success',
                                                                        'zip', 'rar', '7z' => 'ti ti-file-zip text-warning',
                                                                        'jpg', 'jpeg', 'png', 'webp' => 'ti ti-photo text-info',
                                                                        'mp4', 'mov', 'avi' => 'ti ti-video text-purple',
                                                                        default => 'ti ti-file text-secondary',
                                                                    };
                                                                @endphp
                                                                <div class="p-2.5 rounded-3 border bg-light d-flex align-items-center justify-content-between flex-wrap gap-2">
                                                                    <div class="d-flex align-items-center gap-2 min-w-0">
                                                                        <i class="{{ $iconClass }} fs-1"></i>
                                                                        <div class="min-w-0">
                                                                            <div class="fw-semibold text-dark text-truncate small" style="max-width: 350px;">{{ $fileName }}</div>
                                                                            <div class="text-muted" style="font-size: 11px;">Uploaded file answer</div>
                                                                        </div>
                                                                    </div>
                                                                    <a href="{{ Storage::url($filePath) }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-2 px-3">
                                                                        <i class="ti ti-download me-1"></i> Download / View
                                                                    </a>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                    @if ($field->allow_link && $ans && $ans->answer_text)
                                                        <div class="p-2.5 rounded-3 border bg-light d-flex align-items-center justify-content-between flex-wrap gap-2 {{ $ans && $ans->hasFiles() ? 'mt-2' : '' }}">
                                                            <div class="d-flex align-items-center gap-2 min-w-0">
                                                                <i class="ti ti-link text-info fs-1"></i>
                                                                <div class="min-w-0">
                                                                    <div class="fw-semibold text-dark small">Cloud / Google Drive Link</div>
                                                                    <a href="{{ $ans->answer_text }}" target="_blank" class="text-primary text-truncate d-block small" style="max-width: 350px;">
                                                                        {{ $ans->answer_text }}
                                                                    </a>
                                                                </div>
                                                            </div>
                                                            <a href="{{ $ans->answer_text }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-2 px-3">
                                                                <i class="ti ti-external-link me-1"></i> Open Link
                                                            </a>
                                                        </div>
                                                    @endif
                                                    @if (! $ans || (! $ans->hasFiles() && ! ($field->allow_link && $ans->answer_text)))
                                                        <span class="text-muted fst-italic small">No file or link provided.</span>
                                                    @endif
                                                @elseif ($field->isCheckbox())
                                                    @if ($ans && ! empty($ans->answer_text))
                                                        @php $selected = array_map('trim', explode(',', $ans->answer_text)); @endphp
                                                        <div class="d-flex flex-wrap gap-2">
                                                            @foreach ($selected as $item)
                                                                <span class="badge bg-primary-lt text-primary px-2.5 py-1.5 fs-6 border border-primary-subtle">
                                                                    <i class="ti ti-check me-1 text-primary"></i> {{ $item }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <span class="text-muted fst-italic small">No option selected.</span>
                                                    @endif
                                                @elseif ($field->isChoice() || $field->isDropdown())
                                                    @if ($ans && ! empty($ans->answer_text))
                                                        <span class="badge bg-primary-lt text-primary px-3 py-1.5 fs-6 border border-primary-subtle">
                                                            <i class="ti ti-circle-check me-1"></i> {{ $ans->answer_text }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted fst-italic small">No option selected.</span>
                                                    @endif
                                                @elseif ($field->isScale())
                                                    @if ($ans && ! empty($ans->answer_text))
                                                        <div class="d-flex align-items-center gap-3">
                                                            <span class="badge bg-azure-lt text-azure px-3 py-1.5 fs-5 fw-bold border border-azure-subtle">
                                                                Scale: {{ $ans->answer_text }} / 5
                                                            </span>
                                                            <div class="d-flex align-items-center gap-1">
                                                                @for ($s = 1; $s <= 5; $s++)
                                                                    <span class="badge rounded-circle p-0 d-inline-flex align-items-center justify-content-center {{ (string)$s === (string)$ans->answer_text ? 'bg-primary text-white' : 'bg-light text-muted' }}" style="width: 26px; height: 26px; font-size: 12px;">
                                                                        {{ $s }}
                                                                    </span>
                                                                @endfor
                                                            </div>
                                                        </div>
                                                    @else
                                                        <span class="text-muted fst-italic small">No rating given.</span>
                                                    @endif
                                                @elseif ($field->isRating())
                                                    @if ($ans && ! empty($ans->answer_text))
                                                        @php $rVal = (int) $ans->answer_text; @endphp
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div class="d-flex align-items-center gap-1">
                                                                @for ($r = 1; $r <= 5; $r++)
                                                                    <i class="ti ti-star-filled {{ $r <= $rVal ? 'text-warning' : 'text-muted opacity-25' }} fs-2"></i>
                                                                @endfor
                                                            </div>
                                                            <span class="fw-bold text-dark fs-6 ms-1">{{ $rVal }} / 5 Stars</span>
                                                        </div>
                                                    @else
                                                        <span class="text-muted fst-italic small">No rating given.</span>
                                                    @endif
                                                @else
                                                    @if ($ans && ! empty($ans->answer_text))
                                                        <div class="p-3 rounded-3 bg-light border text-dark" style="white-space: pre-wrap; line-height: 1.6; font-size: 0.95rem;">{{ $ans->answer_text }}</div>
                                                    @else
                                                        <span class="text-muted fst-italic small">No answer entered.</span>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @elseif ($submission && $submission->file_path)
                                <div class="p-3 rounded-3 bg-light border d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-2.5">
                                        <i class="ti ti-file-check text-success fs-1"></i>
                                        <div>
                                            <div class="fw-semibold text-dark">Uploaded File Answer</div>
                                            <div class="text-muted small">Submitted on {{ $submission->submitted_at?->format('d M Y H:i') }}</div>
                                        </div>
                                    </div>
                                    <a href="{{ Storage::url($submission->file_path) }}" target="_blank" class="btn btn-outline-primary btn-sm rounded-2 px-3">
                                        <i class="ti ti-download me-1"></i> Download Submitted File
                                    </a>
                                </div>
                            @elseif ($submission && ! $task->isCourseAssignment())
                                <div class="p-3 rounded-3 bg-light border text-muted small text-center">
                                    No answer file recorded for this submission.
                                </div>
                            @endif
                        </div>
                    @else
                        {{-- ══════════════════════════════════════════════════════════════════════════ --}}
                        {{-- ANSWER FORM (New Submission, Draft Mode, or Resubmit Requested)           --}}
                        {{-- ══════════════════════════════════════════════════════════════════════════ --}}
                        @if ($submission && $submission->resubmit_requested)
                            <div class="alert {{ $isOverdue ? 'alert-danger border-start border-danger' : 'alert-warning border-start border-warning' }} border-4 rounded-3 shadow-sm mb-3">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="avatar {{ $isOverdue ? 'bg-danger text-white' : 'bg-warning text-dark' }} rounded-circle me-1 mt-1 shadow-sm">
                                        <i class="ti {{ $isOverdue ? 'ti-alert-circle' : 'ti-refresh' }} fs-2"></i>
                                    </span>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
                                            <h4 class="alert-title fw-bold text-dark mb-0">
                                                Task Revision Requested
                                            </h4>
                                            @if ($isOverdue)
                                                <div class="d-flex align-items-center gap-1">
                                                    <span class="badge bg-danger text-white"><i class="ti ti-alert-circle me-1"></i>Overdue</span>
                                                    <span class="badge bg-danger text-white">Rejected</span>
                                                </div>
                                            @else
                                                <span class="badge bg-warning text-dark">Rejected</span>
                                            @endif
                                        </div>
                                        <div class="text-muted small mb-2">
                                            The administrator reviewed your answers and requested revisions. Please check the feedback below and submit your revised answers.
                                            @if ($isOverdue)
                                                <div class="text-danger fw-semibold mt-1">
                                                    <i class="ti ti-alert-circle me-1"></i> Warning: This task has passed its deadline{{ $cdl ? ' (' . $cdl->format('d M Y H:i') . ')' : '' }}. Please submit your revision as soon as possible.
                                                </div>
                                            @endif
                                        </div>
                                        @if ($submission->feedback)
                                            <div class="p-3 bg-white rounded-3 border {{ $isOverdue ? 'border-danger-subtle' : 'border-warning-subtle' }} text-dark shadow-sm">
                                                <div class="fw-semibold {{ $isOverdue ? 'text-danger' : 'text-warning-emphasis' }} d-flex align-items-center mb-1">
                                                    <i class="ti ti-message-dots me-1"></i> Admin Feedback &amp; Notes:
                                                </div>
                                                <div class="ps-3 border-start border-3 {{ $isOverdue ? 'border-danger' : 'border-warning' }} text-dark">
                                                    {{ $submission->feedback }}
                                                </div>
                                            </div>
                                        @endif
                                        @if ($answersByField->isNotEmpty())
                                            <div class="mt-3">
                                                <button class="btn btn-sm {{ $isOverdue ? 'btn-outline-danger' : 'btn-outline-warning text-dark' }} d-inline-flex align-items-center gap-1" type="button" data-bs-toggle="collapse" data-bs-target="#previousSubmissionCollapse-{{ $task->id }}">
                                                    <i class="ti ti-history me-1"></i> View Your Previous Answers <i class="ti ti-chevron-down ms-1"></i>
                                                </button>
                                                <div class="collapse mt-2" id="previousSubmissionCollapse-{{ $task->id }}">
                                                    <div class="card card-body bg-white border {{ $isOverdue ? 'border-danger-subtle' : 'border-warning-subtle' }} p-3">
                                                        <div class="fw-bold text-dark small mb-2"><i class="ti ti-archive me-1"></i> Previous Answers Before Revision:</div>
                                                        @foreach ($task->fields as $pIdx => $pField)
                                                            @php $pAns = $answersByField->get($pField->id); @endphp
                                                            <div class="mb-2 pb-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                                                                <div class="fw-semibold small text-dark">Q{{ $pIdx + 1 }}. {{ $pField->label }}</div>
                                                                <div class="small text-muted mt-1">
                                                                    @if ($pAns && $pAns->hasFiles())
                                                                        <div class="mb-1">
                                                                            Files:
                                                                            @foreach ($pAns->filePaths() as $fp)
                                                                                <a href="{{ Storage::url($fp) }}" target="_blank" class="ms-1">{{ basename($fp) }}</a>{{ ! $loop->last ? ', ' : '' }}
                                                                            @endforeach
                                                                        </div>
                                                                    @endif
                                                                    @if ($pAns && $pAns->answer_text)
                                                                        <div>{{ $pAns->answer_text }}</div>
                                                                    @endif
                                                                    @if (! $pAns || (! $pAns->hasFiles() && ! $pAns->answer_text))
                                                                        <span class="fst-italic text-muted">No answer recorded</span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @elseif ($submission && $submission->status === 'draft')
                            <div class="alert {{ $isOverdue ? 'alert-danger border-start border-danger' : 'alert-warning border-start border-warning' }} border-4 rounded-3 shadow-sm mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="avatar {{ $isOverdue ? 'bg-danger text-white' : 'bg-warning text-dark' }} rounded-circle me-1 shadow-sm">
                                        <i class="ti {{ $isOverdue ? 'ti-alert-circle' : 'ti-device-floppy' }} fs-2"></i>
                                    </span>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
                                            <h4 class="alert-title fw-bold text-dark mb-0">Answers Saved as Draft</h4>
                                            @if ($isOverdue)
                                                <div class="d-flex align-items-center gap-1">
                                                    <span class="badge bg-danger text-white"><i class="ti ti-alert-circle me-1"></i>Overdue</span>
                                                    <span class="badge bg-danger-lt text-danger">Draft Mode</span>
                                                </div>
                                            @else
                                                <span class="badge bg-warning text-dark">Draft Mode</span>
                                            @endif
                                        </div>
                                        <div class="text-muted small">
                                            @if ($isOverdue)
                                                <span class="text-danger fw-semibold"><i class="ti ti-alert-circle me-1"></i> This task has passed its deadline{{ $cdl ? ' (' . $cdl->format('d M Y H:i') . ')' : '' }}.</span> You have a saved draft from {{ $submission->updated_at?->format('d M Y H:i') ?? 'earlier' }}. You can continue editing your answers below and submit when you are ready.
                                            @else
                                                Your answers are currently saved as a draft and have not been submitted to the administrator. You can continue editing your answers below and click <strong>Submit Answers</strong> when you are ready.
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @elseif ($isOverdue)
                            <div class="alert alert-danger border-start border-danger border-4 rounded-3 shadow-sm mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="avatar bg-danger text-white rounded-circle me-1 shadow-sm">
                                        <i class="ti ti-alert-circle fs-2"></i>
                                    </span>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
                                            <h4 class="alert-title fw-bold text-dark mb-0">Task is Overdue</h4>
                                            <span class="badge bg-danger text-white"><i class="ti ti-alert-circle me-1"></i>Overdue</span>
                                        </div>
                                        <div class="text-muted small">
                                            The deadline for this task was <strong>{{ $cdl?->format('d M Y H:i') }}</strong>. Please complete and submit your answers as soon as possible.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Dynamic answer form following the task fields --}}
                        <form action="{{ route('my-task.submit', $task) }}" method="POST" enctype="multipart/form-data" class="mt-2">
                            @csrf
                            <input type="hidden" name="active_task_id" value="{{ $task->id }}">
                            <h5 class="fw-semibold text-dark mb-3 d-flex align-items-center gap-1">
                                <i class="ti {{ $submission && $submission->resubmit_requested ? 'ti-refresh text-warning' : ($submission && $submission->status === 'draft' ? 'ti-pencil text-warning' : 'ti-pencil text-primary') }}"></i> {{ $submission && $submission->resubmit_requested ? 'Resubmit Answers' : ($submission && $submission->status === 'draft' ? 'Continue Draft Answers' : 'User Submission Form') }}
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
                                                        Drag &amp; drop {{ $field->maxFiles() > 1 ? 'files' : 'a file' }} here, or <span class="text-primary text-decoration-underline">browse</span>
                                                    </div>
                                                    <div class="text-muted small" style="font-size: 11.5px;">
                                                        Format: <strong>{{ ! empty($field->allowedExtensions()) ? strtoupper(implode(', ', $field->allowedExtensions())) : 'All file formats' }}</strong>
                                                        @if ($field->allowedTypesLabel())
                                                            <span class="badge bg-secondary-lt text-secondary ms-1">{{ $field->allowedTypesLabel() }}</span>
                                                        @endif
                                                        | Maximum <strong>{{ $field->maxFiles() }} file{{ $field->maxFiles() > 1 ? 's' : '' }}</strong>, max. <strong>{{ $field->maxSizeMb() }} MB</strong> per file
                                                    </div>
                                                    @if ($field->maxFiles() > 1)
                                                        <div class="text-muted small mt-1" style="font-size: 11px;">
                                                            <i class="ti ti-info-circle me-1"></i>You can select multiple files at once or add them one by one.
                                                        </div>
                                                    @endif
                                                    @if ($field->allow_link)
                                                        <div class="mt-2">
                                                            <span class="badge bg-info-lt text-info d-inline-flex align-items-center">
                                                                <i class="ti ti-link me-1"></i> Supports Drive Link
                                                            </span>
                                                        </div>
                                                    @endif
                                                </div>

                                                <!-- Dragover Overlay Indicator -->
                                                <div class="dropzone-dragover-indicator d-none position-absolute top-0 start-0 w-100 h-100 rounded-3 d-flex flex-column align-items-center justify-content-center bg-primary-lt" style="pointer-events: none; z-index: 6;">
                                                    <i class="ti ti-download fs-1 text-primary mb-1 animate-bounce"></i>
                                                    <span class="fw-bold text-primary">{{ $field->maxFiles() > 1 ? 'Drop files here to add' : 'Drop file here' }}</span>
                                                </div>

                                                <!-- Selected Files State -->
                                                <div class="dropzone-preview d-none text-start pt-1 position-relative" style="z-index: 6;">
                                                    <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
                                                        <span class="fw-semibold small text-dark d-flex align-items-center gap-1 dropzone-title-indicator">
                                                            <i class="ti ti-circle-check text-success"></i> {{ $field->maxFiles() > 1 ? 'Selected Files:' : 'Selected File:' }}
                                                        </span>
                                                        <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none p-0 dropzone-clear-all-btn d-none" style="font-size: 11.5px;">
                                                            <i class="ti ti-trash me-1"></i>Clear all
                                                        </button>
                                                    </div>
                                                    <div class="selected-files-list d-flex flex-column gap-1"></div>
                                                    <div class="dropzone-add-more-wrap mt-2"></div>
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
                                    @elseif ($field->isCheckbox())
                                        @php
                                            $checkedOpts = (array) old($fieldKey, ! empty($ans->answer_text) ? explode(', ', $ans->answer_text) : []);
                                        @endphp
                                        <div class="d-flex flex-column gap-2 mt-2">
                                            @foreach ($field->options ?? [] as $opt)
                                                <label class="form-check mb-0">
                                                    <input type="checkbox" class="form-check-input @error($fieldKey) is-invalid @enderror"
                                                           name="fields[{{ $field->id }}][]" value="{{ $opt }}"
                                                           {{ in_array($opt, $checkedOpts, true) ? 'checked' : '' }}>
                                                    <span class="form-check-label">{{ $opt }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @elseif ($field->isDropdown())
                                        <select name="fields[{{ $field->id }}]" class="form-select @error($fieldKey) is-invalid @enderror mt-2" {{ $field->required ? 'required' : '' }}>
                                            <option value="">— Select an option —</option>
                                            @foreach ($field->options ?? [] as $opt)
                                                <option value="{{ $opt }}" {{ old($fieldKey, $ans->answer_text ?? null) === $opt ? 'selected' : '' }}>
                                                    {{ $opt }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @elseif ($field->isShortAnswer())
                                        <input type="text" name="fields[{{ $field->id }}]"
                                               class="form-control @error($fieldKey) is-invalid @enderror mt-1"
                                               placeholder="Your answer"
                                               value="{{ old($fieldKey, $ans->answer_text ?? '') }}"
                                               {{ $field->required ? 'required' : '' }}>
                                    @elseif ($field->isScale())
                                        <div class="d-flex align-items-center gap-3 flex-wrap py-2 mt-1">
                                            @for ($s = 1; $s <= 5; $s++)
                                                <label class="form-check form-check-inline text-center mb-0 cursor-pointer">
                                                    <input type="radio" class="form-check-input d-block mx-auto @error($fieldKey) is-invalid @enderror"
                                                           name="fields[{{ $field->id }}]" value="{{ $s }}"
                                                           {{ (string) old($fieldKey, $ans->answer_text ?? '') === (string) $s ? 'checked' : '' }}
                                                           {{ $field->required ? 'required' : '' }}>
                                                    <span class="form-check-label small mt-1 d-block fw-semibold">{{ $s }}</span>
                                                </label>
                                            @endfor
                                        </div>
                                    @elseif ($field->isRating())
                                        <div class="d-flex align-items-center gap-2 py-2 mt-1">
                                            @for ($r = 1; $r <= 5; $r++)
                                                <label class="form-check form-check-inline mb-0 cursor-pointer">
                                                    <input type="radio" class="form-check-input me-1 @error($fieldKey) is-invalid @enderror"
                                                           name="fields[{{ $field->id }}]" value="{{ $r }}"
                                                           {{ (string) old($fieldKey, $ans->answer_text ?? '') === (string) $r ? 'checked' : '' }}
                                                           {{ $field->required ? 'required' : '' }}>
                                                    <span class="form-check-label"><i class="ti ti-star-filled text-warning"></i> {{ $r }}</span>
                                                </label>
                                            @endfor
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
                                <button type="submit" name="action" value="draft" formnovalidate class="btn btn-outline-primary rounded-3 px-3">
                                    <i class="ti ti-device-floppy me-1"></i> Save as Draft
                                </button>
                                <button type="submit" name="action" value="submit" class="btn {{ $submission && $submission->resubmit_requested ? 'btn-warning text-dark fw-semibold' : 'btn-primary' }} rounded-3 px-4">
                                    <i class="ti {{ $submission && $submission->resubmit_requested ? 'ti-refresh' : 'ti-send' }} me-1"></i> {{ $submission && $submission->resubmit_requested ? 'Resubmit Answers' : 'Submit Answers' }}
                                </button>
                            </div>
                        </form>
                    @endif {{-- status: course assignment / view submission / answer form --}}

                    @if (($inModal ?? false) && ($task->isCourseAssignment() || ($submission && ! $submission->resubmit_requested && $submission->status !== 'draft')))
                        <div class="d-flex align-items-center justify-content-end gap-2 mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-outline-secondary rounded-3 px-4" data-bs-dismiss="modal">
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
.task-dropzone.has-files {
    cursor: default !important;
}
.task-dropzone.has-files:hover {
    border-color: #cbd5e1 !important;
    background-color: transparent !important;
}
.task-dropzone:not(.has-files):hover {
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
.dropzone-add-more-btn:hover {
    background-color: rgba(32, 107, 196, 0.08) !important;
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

    function syncInputFiles(dz) {
        var input = dz.querySelector('.task-file-input');
        if (!input) return;
        var dt = new DataTransfer();
        var files = dz._selectedFiles || [];
        for (var i = 0; i < files.length; i++) {
            dt.items.add(files[i]);
        }
        input.files = dt.files;
    }

    function renderDropzone(dz, exceededNotice) {
        var promptEl = dz.querySelector('.dropzone-prompt');
        var previewEl = dz.querySelector('.dropzone-preview');
        var listEl = dz.querySelector('.selected-files-list');
        var addMoreWrap = dz.querySelector('.dropzone-add-more-wrap');
        var errorEl = dz.querySelector('.dropzone-error-msg');
        var input = dz.querySelector('.task-file-input');
        var titleIndicator = dz.querySelector('.dropzone-title-indicator');
        var clearAllBtn = dz.querySelector('.dropzone-clear-all-btn');

        var maxFiles = parseInt(dz.dataset.maxFiles || '1', 10);
        var maxSizeMb = parseFloat(dz.dataset.maxSizeMb || '10');
        var maxSizeBytes = maxSizeMb * 1024 * 1024;
        var allowedExtsStr = (dz.dataset.allowedExts || '').trim();
        var allowedExts = allowedExtsStr ? allowedExtsStr.split(',').map(function (s) { return s.trim().toLowerCase(); }).filter(Boolean) : [];

        var files = dz._selectedFiles || [];

        if (files.length === 0) {
            if (promptEl) promptEl.classList.remove('d-none');
            if (previewEl) previewEl.classList.add('d-none');
            if (listEl) listEl.innerHTML = '';
            if (addMoreWrap) addMoreWrap.innerHTML = '';
            if (errorEl) {
                errorEl.textContent = '';
                errorEl.classList.add('d-none');
            }
            if (input) {
                input.value = '';
                input.style.pointerEvents = 'auto';
                input.setCustomValidity('');
            }
            dz.classList.remove('has-files');
            dz.style.borderColor = '#cbd5e1';
            return;
        }

        if (promptEl) promptEl.classList.add('d-none');
        if (previewEl) previewEl.classList.remove('d-none');
        if (input) input.style.pointerEvents = 'none';
        dz.classList.add('has-files');

        // Title Indicator and Clear All Button
        if (titleIndicator) {
            if (maxFiles > 1) {
                titleIndicator.innerHTML = '<i class="ti ti-files text-primary"></i> Selected Files <span class="badge bg-primary-lt ms-1">' + files.length + ' / ' + maxFiles + '</span>';
            } else {
                titleIndicator.innerHTML = '<i class="ti ti-circle-check text-success"></i> Selected File:';
            }
        }
        if (clearAllBtn) {
            if (maxFiles > 1 && files.length > 0) {
                clearAllBtn.classList.remove('d-none');
            } else {
                clearAllBtn.classList.add('d-none');
            }
        }

        // Render file list
        var errors = [];
        var listHtml = '';

        for (var i = 0; i < files.length; i++) {
            var f = files[i];
            var ext = (f.name || '').split('.').pop().toLowerCase();
            var fileHasError = false;

            if (f.size > maxSizeBytes) {
                errors.push('File "' + f.name + '" (' + formatBytes(f.size) + ') exceeds the maximum size limit of ' + maxSizeMb + ' MB.');
                fileHasError = true;
            }

            if (allowedExts.length > 0 && allowedExts.indexOf(ext) === -1) {
                errors.push('File format "' + f.name + '" is not allowed. Allowed formats: ' + allowedExts.join(', ').toUpperCase());
                fileHasError = true;
            }

            var icon = getFileIconClass(f.name);
            listHtml += '<div class="d-flex align-items-center justify-content-between p-2 rounded-2 bg-white border">' +
                '<div class="d-flex align-items-center gap-2 overflow-hidden me-2">' +
                    '<i class="' + icon + ' fs-2 flex-shrink-0"></i>' +
                    '<div class="text-truncate">' +
                        '<div class="fw-semibold text-dark text-truncate" style="font-size:12px;" title="' + escapeHtml(f.name) + '">' + escapeHtml(f.name) + '</div>' +
                        '<div class="text-muted" style="font-size:11px;">' + formatBytes(f.size) + '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="d-flex align-items-center gap-2 flex-shrink-0">';

            if (!fileHasError) {
                listHtml += '<span class="badge bg-success-lt text-success flex-shrink-0">' +
                    '<i class="ti ti-check me-1"></i> Ready to upload' +
                '</span>';
            } else {
                listHtml += '<span class="badge bg-danger-lt text-danger flex-shrink-0">' +
                    '<i class="ti ti-alert-circle me-1"></i> Invalid' +
                '</span>';
            }

            if (maxFiles === 1) {
                listHtml += '<button type="button" class="btn btn-sm btn-ghost-danger py-0 px-2 rounded-2 dropzone-change-file-btn" data-field-id="' + (dz.dataset.fieldId || '') + '" title="Remove and select again">' +
                        '<i class="ti ti-x me-1"></i> Change File' +
                    '</button>';
            } else {
                listHtml += '<button type="button" class="btn btn-sm btn-ghost-danger py-0 px-2 rounded-2 dropzone-remove-file-btn" data-index="' + i + '" title="Remove this file">' +
                        '<i class="ti ti-x me-1"></i> Remove' +
                    '</button>';
            }

            listHtml += '</div>' +
            '</div>';
        }

        if (listEl) listEl.innerHTML = listHtml;

        // Render Add More section (only when maxFiles > 1)
        if (addMoreWrap) {
            if (maxFiles > 1) {
                if (files.length < maxFiles) {
                    addMoreWrap.innerHTML = '<button type="button" class="btn btn-outline-primary btn-sm w-100 py-2 d-flex align-items-center justify-content-center gap-1 dropzone-add-more-btn" style="border-style: dashed !important; font-weight: 500;">' +
                        '<i class="ti ti-plus"></i> Add Another File <span class="text-muted fw-normal">(' + files.length + ' of ' + maxFiles + ' added)</span>' +
                    '</button>';
                } else {
                    addMoreWrap.innerHTML = '<div class="text-center text-muted small py-1">' +
                        '<i class="ti ti-check-double text-success me-1"></i> Maximum limit reached (' + maxFiles + ' of ' + maxFiles + ' files selected)' +
                    '</div>';
                }
            } else {
                addMoreWrap.innerHTML = '';
            }
        }

        // Inline error handling
        if (errorEl) {
            if (errors.length > 0) {
                errorEl.innerHTML = '<i class="ti ti-alert-triangle me-1"></i> ' + errors.join('<br>');
                errorEl.classList.remove('d-none');
                dz.style.borderColor = '#d63939';
                if (input) input.setCustomValidity(errors[0]);
            } else if (exceededNotice) {
                errorEl.innerHTML = '<i class="ti ti-alert-triangle me-1"></i> ' + escapeHtml(exceededNotice);
                errorEl.classList.remove('d-none');
                dz.style.borderColor = '#2fb344';
                if (input) input.setCustomValidity('');
            } else {
                errorEl.textContent = '';
                errorEl.classList.add('d-none');
                dz.style.borderColor = '#2fb344';
                if (input) input.setCustomValidity('');
            }
        }
    }

    function appendFilesToDropzone(dz, newFiles) {
        if (!newFiles || newFiles.length === 0) return;
        var maxFiles = parseInt(dz.dataset.maxFiles || '1', 10);

        if (!dz._selectedFiles || maxFiles === 1) {
            dz._selectedFiles = [];
        }

        var exceededNotice = null;
        for (var i = 0; i < newFiles.length; i++) {
            var f = newFiles[i];

            // Prevent duplicate file selection
            var isDuplicate = dz._selectedFiles.some(function (existing) {
                return existing.name === f.name && existing.size === f.size && existing.lastModified === f.lastModified;
            });
            if (isDuplicate) continue;

            if (dz._selectedFiles.length >= maxFiles) {
                exceededNotice = 'Maximum ' + maxFiles + ' file' + (maxFiles > 1 ? 's' : '') + ' allowed. Additional files were not added.';
                break;
            }

            dz._selectedFiles.push(f);
        }

        syncInputFiles(dz);
        renderDropzone(dz, exceededNotice);
    }

    function removeFileFromDropzone(dz, index) {
        if (!dz._selectedFiles) return;
        dz._selectedFiles.splice(index, 1);
        syncInputFiles(dz);
        renderDropzone(dz);
    }

    function clearAllFilesFromDropzone(dz) {
        dz._selectedFiles = [];
        syncInputFiles(dz);
        renderDropzone(dz);
    }

    function openFileBrowser(dz, isReplace) {
        var maxFiles = parseInt(dz.dataset.maxFiles || '1', 10);
        var input = dz.querySelector('.task-file-input');
        var accept = input ? input.getAttribute('accept') : '';

        var tempInput = document.createElement('input');
        tempInput.type = 'file';
        if (accept) tempInput.accept = accept;
        if (maxFiles > 1 && !isReplace) {
            tempInput.multiple = true;
        }
        tempInput.style.position = 'fixed';
        tempInput.style.left = '-9999px';
        document.body.appendChild(tempInput);

        tempInput.addEventListener('change', function () {
            if (tempInput.files && tempInput.files.length > 0) {
                if (isReplace) {
                    dz._selectedFiles = [];
                }
                appendFilesToDropzone(dz, tempInput.files);
            }
            if (tempInput.parentNode) {
                tempInput.parentNode.removeChild(tempInput);
            }
        });

        tempInput.addEventListener('cancel', function () {
            if (tempInput.parentNode) {
                tempInput.parentNode.removeChild(tempInput);
            }
        });

        tempInput.click();
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
            var dz = fileWrap.querySelector('.task-dropzone');
            if (dz) {
                clearAllFilesFromDropzone(dz);
            }
        }
    }

    document.addEventListener('change', function (e) {
        if (e.target && e.target.classList && e.target.classList.contains('file-choice-radio')) {
            syncFileChoice(e.target);
        }
        if (e.target && e.target.classList && e.target.classList.contains('task-file-input')) {
            var dz = e.target.closest('.task-dropzone');
            if (dz && e.target.files && e.target.files.length > 0) {
                appendFilesToDropzone(dz, e.target.files);
            }
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
                appendFilesToDropzone(dz, files);
            }
        }
    }, false);

    // Global click handlers for dropzone action buttons
    document.addEventListener('click', function (e) {
        // 1. Change File button (single-file or replace)
        var changeBtn = e.target.closest('.dropzone-change-file-btn, .dropzone-clear-btn');
        if (changeBtn) {
            e.preventDefault();
            e.stopPropagation();
            var dz = changeBtn.closest('.task-dropzone');
            if (dz) {
                openFileBrowser(dz, true);
            }
            return;
        }

        // 2. Remove single file (multi-file)
        var removeBtn = e.target.closest('.dropzone-remove-file-btn');
        if (removeBtn) {
            e.preventDefault();
            e.stopPropagation();
            var dz = removeBtn.closest('.task-dropzone');
            if (dz) {
                var index = parseInt(removeBtn.getAttribute('data-index'), 10);
                removeFileFromDropzone(dz, index);
            }
            return;
        }

        // 3. Clear all files
        var clearAllBtn = e.target.closest('.dropzone-clear-all-btn');
        if (clearAllBtn) {
            e.preventDefault();
            e.stopPropagation();
            var dz = clearAllBtn.closest('.task-dropzone');
            if (dz) {
                clearAllFilesFromDropzone(dz);
            }
            return;
        }

        // 4. Add more files button
        var addMoreBtn = e.target.closest('.dropzone-add-more-btn');
        if (addMoreBtn) {
            e.preventDefault();
            e.stopPropagation();
            var dz = addMoreBtn.closest('.task-dropzone');
            if (dz) {
                openFileBrowser(dz, false);
            }
            return;
        }
    });
})();
</script>
@endonce
