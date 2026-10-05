{{-- Hero tab My Task (injected into #learning-hero-region by dashboard-script) --}}
<template data-learning-my-task-hero>
    <x-shared.hero-learning-my-task :stats="[
        ['label' => 'Total Tasks', 'value' => $summary['total'] ?? 0],
        ['label' => 'Not Started', 'value' => $summary['not_started'] ?? 0],
        ['label' => 'Completed', 'value' => $summary['completed'] ?? 0],
        ['label' => 'Overdue', 'value' => $summary['overdue'] ?? 0],
    ]" />
</template>

@php
    $statusLabels = [
        'all'         => 'All',
        'not_started' => 'Not started',
        'submitted'   => 'Pending review',
        'completed'   => 'Completed',
        'overdue'     => 'Overdue',
    ];
    $statusBadges = [
        'not_started' => 'bg-secondary-lt text-secondary',
        'submitted'   => 'bg-warning-lt text-warning',
        'completed'   => 'bg-success-lt text-success',
        'overdue'     => 'bg-danger-lt text-danger',
    ];
    $filterUrl = fn ($key) => route('user.learning.dashboard', array_filter([
        'learning_tab'   => 'my-task',
        'my_task_status' => $key === 'all' ? null : $key,
    ]));
@endphp

{{-- Status filter --}}
<div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap gap-2">
            @foreach ($statusLabels as $key => $label)
                <a href="{{ $filterUrl($key) }}"
                   class="btn {{ $filter === $key ? 'btn-primary' : 'btn-white' }} btn-sm">
                    {{ $label }}
                    @if ($key !== 'all')
                        <span class="badge {{ $filter === $key ? 'bg-white text-primary' : 'bg-primary-lt text-primary' }} ms-1">{{ $summary[$key] ?? 0 }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
</div>

{{-- Task list --}}
<div class="row g-3">
    @forelse ($tasks as $task)
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
                            <h3 class="card-title mb-1">
                                @if ($task->trainingPlan)
                                    <a href="{{ route('user.playlist.show', $task->trainingPlan->slug) }}" class="text-body text-decoration-none">{{ $task->title }}</a>
                                @else
                                    {{ $task->title }}
                                @endif
                            </h3>
                            <div class="text-muted small">
                                <i class="ti ti-book-2 me-1"></i>{{ $task->parentName() }}
                                @if ($task->deadline)
                                    <span class="ms-2"><i class="ti ti-calendar-due me-1"></i>Deadline: {{ $task->deadline->format('d M Y H:i') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-1">
                            <span class="badge bg-primary-lt text-primary">{{ $task->typeLabel() }}</span>
                            <span class="badge {{ $statusBadges[$status] }}">{{ $statusLabels[$status] }}</span>
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
                            {{ $task->isAssessment() ? 'Quiz auto-scored.' : 'Reviewed by admin.' }}
                            @if ($submission && !is_null($submission->score))
                                <span class="badge bg-success ms-2">Score: {{ $submission->score }}</span>
                            @endif
                        </div>
                        {{-- Summary of submitted answers --}}
                        @if ($answersByField->isNotEmpty() && $task->fields->isNotEmpty())
                            <div class="mt-2 small">
                                @foreach ($task->fields as $field)
                                    @php $ans = $answersByField->get($field->id); @endphp
                                    @if ($ans && ($ans->file_path || $ans->answer_text))
                                        <div class="mb-1">
                                            <span class="fw-semibold">{{ $field->label }}:</span>
                                            @if ($ans->file_path)
                                                <a href="{{ Storage::url($ans->file_path) }}" target="_blank">View file</a>
                                            @else
                                                <span class="text-muted">{{ Str::limit($ans->answer_text, 100) }}</span>
                                            @endif
                                            @if ($task->isAssessment() && $field->isChoice())
                                                @php $isCorrect = $ans->answer_text === $field->correct_answer; @endphp
                                                <span class="badge {{ $isCorrect ? 'bg-success' : 'bg-danger' }} ms-1">
                                                    {{ $isCorrect ? 'Correct' : 'Wrong — correct: ' . $field->correct_answer }}
                                                </span>
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
                        {{-- ── Course Assignment: open the linked course, then confirm ── --}}
                        @if ($submission)
                            <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2 mb-0">
                                <div>
                                    <i class="ti ti-clock me-1"></i>
                                    Marked as done {{ $submission->submitted_at?->format('d M Y H:i') }} — waiting for admin review.
                                </div>
                                <span class="badge bg-warning text-dark">Pending</span>
                            </div>
                        @else
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                @if ($task->link_type === 'lms' && $task->linkedPlan)
                                    <a href="{{ route('user.playlist.show', $task->linkedPlan->slug) }}"
                                       class="btn btn-primary">
                                        <i class="ti ti-book-2 me-1"></i>Open Course: {{ $task->linkedPlan->title }}
                                    </a>
                                @elseif ($task->link_type === 'external' && $task->external_url)
                                    <a href="{{ $task->external_url }}" target="_blank"
                                       class="btn btn-primary">
                                        <i class="ti ti-external-link me-1"></i>Open External Course
                                    </a>
                                @endif
                                <form action="{{ route('my-task.submit', $task) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-success">
                                        <i class="ti ti-check me-1"></i>Mark as Complete
                                    </button>
                                </form>
                            </div>
                            <div class="form-hint mt-1">Open the course above, finish it, then click “Mark as Complete”.</div>
                        @endif
                    @else
                        @if ($submission)
                            <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div>
                                    <i class="ti ti-clock me-1"></i>
                                    Answers submitted {{ $submission->submitted_at?->format('d M Y H:i') }} — waiting for admin review.
                                </div>
                                <span class="badge bg-warning text-dark">Pending</span>
                            </div>
                        @endif

                        @if ($task->isAssessment())
                            <div class="alert alert-info">
                                <i class="ti ti-bulb me-1"></i>
                                This quiz has {{ $task->fields->count() }} multiple-choice questions and is scored automatically.
                            </div>
                        @endif

                        {{-- Dynamic answer form following the task fields --}}
                        <form action="{{ route('my-task.submit', $task) }}" method="POST" enctype="multipart/form-data" class="mt-2">
                            @csrf
                            @if (! $task->isAssessment())
                                <div class="mb-2 fw-semibold small">
                                    {{ $submission ? 'Update answers (only changed fields are replaced)' : 'Submit answers' }}
                                </div>
                            @endif

                            @foreach ($task->fields as $field)
                                @php
                                    $ans = $answersByField->get($field->id);
                                    $fieldKey = 'fields.' . $field->id;
                                    $hasFile = $ans && $ans->file_path;
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
                                        @if ($hasFile)
                                            <div class="form-hint mb-1">
                                                Current file:
                                                <a href="{{ Storage::url($ans->file_path) }}" target="_blank">view</a>
                                                — upload a new one to replace it.
                                            </div>
                                        @endif
                                        <input type="file"
                                               name="fields[{{ $field->id }}]"
                                               class="form-control @error($fieldKey) is-invalid @enderror"
                                               {{ $field->required && ! $hasFile ? 'required' : '' }}>
                                        <div class="form-hint">Max 5 MB per file.</div>
                                    @elseif ($field->isChoice())
                                        @foreach ($field->options ?? [] as $opt)
                                            @php
                                                $checked = old($fieldKey, $ans->answer_text ?? null) === $opt;
                                            @endphp
                                            <label class="form-check">
                                                <input type="radio" class="form-check-input @error($fieldKey) is-invalid @enderror"
                                                       name="fields[{{ $field->id }}]" value="{{ $opt }}"
                                                       {{ $checked ? 'checked' : '' }}
                                                       {{ $field->required || $task->isAssessment() ? 'required' : '' }}>
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
                                </div>
                            @endforeach

                            @if ($task->fields->isEmpty())
                                <div class="alert alert-warning">This task has no questions yet.</div>
                            @else
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-upload me-1"></i>{{ $task->isAssessment() ? 'Submit Quiz' : ($submission ? 'Resubmit' : 'Submit') }}
                                </button>
                            @endif
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 text-center text-muted">
                    <i class="ti ti-list-check fs-1 d-block mb-2"></i>
                    @if ($filter === 'all')
                        No tasks yet. Open a course you follow first — tasks will appear here after you click the course.
                    @else
                        No tasks with this filter.
                    @endif
                </div>
            </div>
        </div>
    @endforelse
</div>
