{{-- resources/views/manage/task/submissions.blade.php --}}
{{-- Review responses: admin views user answers per question, downloads files, marks complete. --}}
@extends('layouts.sidebar-master')

@section('content')
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <div class="mb-2">
                <a href="{{ route('manage.task.parent', $task->parentRouteParams()) }}" class="text-primary text-decoration-none fw-semibold d-inline-flex align-items-center gap-1">
                    <i class="ti ti-arrow-left"></i> Back
                </a>
            </div>
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        <a href="{{ route('manage.task.index') }}">Task Management</a>
                        &nbsp;/&nbsp;
                        <a href="{{ route('manage.task.parent', $task->parentRouteParams()) }}">{{ $task->parentName() }}</a>
                    </div>
                    <h2 class="page-title">Responses: {{ $task->title }}</h2>
                    <div class="text-muted">
                        <span class="badge bg-primary-lt text-primary">{{ $task->typeLabel() }}</span>
                        @if ($task->deadline)
                            <span class="ms-2">Deadline: {{ $task->deadline->format('d M Y H:i') }}</span>
                        @else
                            <span class="ms-2">No deadline</span>
                        @endif
                        @if ($task->isCourseAssignment())
                            <span class="ms-2">·
                                @if ($task->link_type === 'lms' && $task->linkedPlan)
                                    Target: {{ $task->linkedPlan->title }}
                                @elseif ($task->external_url)
                                    Target: <a href="{{ $task->external_url }}" target="_blank">{{ Str::limit($task->external_url, 50) }}</a>
                                @endif
                            </span>
                        @else
                            <span class="ms-2">· {{ $task->fields->count() }} questions</span>
                        @endif
                    </div>
                </div>
                <div class="col-auto ms-auto d-flex align-items-center gap-2">
                    <a href="{{ route('manage.task.submissions.export', $task) }}" class="btn btn-outline-success">
                        <i class="ti ti-file-spreadsheet me-1"></i> Export to Excel
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <form method="GET" class="d-flex align-items-center gap-2">
                    <select name="status" class="form-select w-auto d-inline-block" onchange="this.form.submit()">
                        <option value="">All statuses ({{ $submissions->count() }})</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pending review</option>
                        <option value="completed" @selected(request('status') === 'completed')>Completed</option>
                    </select>
                </form>

                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-primary" id="btnOpenBulkModal" data-bs-toggle="modal" data-bs-target="#bulkCompleteModal" disabled>
                        <i class="ti ti-checks me-1"></i> Mark Selected Complete (<span id="selectedCount">0</span>)
                    </button>
                </div>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th class="w-1">
                                    <input type="checkbox" id="selectAllCheckbox" class="form-check-input" title="Select All">
                                </th>
                                <th>User</th>
                                <th>Submitted</th>
                                <th>Answers per Question</th>
                                <th>Status</th>
                                <th>Score</th>
                                <th class="w-1">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($submissions as $submission)
                                @php
                                    $answersByField = $submission->answers->keyBy('task_field_id');
                                @endphp
                                <tr>
                                    <td>
                                        <input type="checkbox" name="submission_ids[]" value="{{ $submission->id }}"
                                               class="form-check-input submission-checkbox">
                                    </td>
                                    <td>
                                        <div class="fw-bold">{{ $submission->user?->name ?? 'User #' . $submission->user_id }}</div>
                                        <div class="text-muted small">{{ $submission->user?->email }}</div>
                                    </td>
                                    <td>
                                        {{ $submission->submitted_at?->format('d M Y H:i') ?? '-' }}
                                        @if ($task->deadline && $submission->submitted_at && $submission->submitted_at->gt($task->deadline))
                                            <span class="badge bg-danger ms-1">Late</span>
                                        @endif
                                    </td>
                                    <td style="min-width: 260px;">
                                        @if ($task->isCourseAssignment())
                                            <span class="text-muted small">
                                                <i class="ti ti-check me-1"></i>User confirmed completing the linked course.
                                            </span>
                                        @elseif ($task->fields->isNotEmpty())
                                            @foreach ($task->fields as $field)
                                                @php $answer = $answersByField->get($field->id); @endphp
                                                <div class="mb-2">
                                                    <div class="fw-semibold small">
                                                        {{ $field->label }}
                                                        @if ($field->required)
                                                            <span class="text-danger">*</span>
                                                        @endif
                                                    </div>
                                                    @php $hasAnyAnswer = $answer && ($answer->hasFiles() || $answer->answer_text); @endphp
                                                    @if ($answer && $answer->hasFiles())
                                                        @foreach ($answer->filePaths() as $p)
                                                            <a href="{{ Storage::url($p) }}" target="_blank"
                                                                class="btn btn-sm btn-outline-secondary mb-1">
                                                                <i class="ti ti-download"></i> {{ basename($p) }}
                                                            </a>
                                                        @endforeach
                                                    @endif
                                                    @if ($answer && $field->isFile() && $answer->answer_text)
                                                        <div>
                                                            <a href="{{ $answer->answer_text }}" target="_blank"
                                                                class="btn btn-sm btn-outline-secondary mb-1">
                                                                <i class="ti ti-link"></i> Open Drive link
                                                            </a>
                                                        </div>
                                                    @elseif ($answer && ! $field->isFile() && $answer->answer_text)
                                                        <div class="text-muted small">{{ Str::limit($answer->answer_text, 150) }}</div>
                                                    @elseif (! $hasAnyAnswer)
                                                        <span class="text-muted small">— not answered —</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        @else
                                            {{-- Fallback for legacy data (before the Google Form revision) --}}
                                            @if ($submission->answer)
                                                <div class="text-muted small mb-1">{{ Str::limit($submission->answer, 120) }}</div>
                                            @endif
                                            @if ($submission->file_path)
                                                <a href="{{ Storage::url($submission->file_path) }}" target="_blank"
                                                    class="btn btn-sm btn-outline-secondary">
                                                    <i class="ti ti-download"></i> Download file
                                                </a>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        @if ($submission->status === 'completed')
                                            <span class="badge bg-success">Completed</span>
                                        @else
                                            <span class="badge bg-info">Pending review</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (!is_null($submission->score))
                                            <span class="badge bg-primary fs-5">{{ $submission->score }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                        @if ($submission->feedback)
                                            <div class="text-muted small mt-1" style="max-width: 200px;"
                                                title="{{ $submission->feedback }}">
                                                <i class="ti ti-message-circle me-1"></i>{{ Str::limit($submission->feedback, 60) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        {{-- Score/feedback form: marks pending responses complete,
                                            and lets admin adjust scores (e.g. quiz auto-scores). --}}
                                        <form action="{{ route('manage.task.submissions.complete', [$task, $submission]) }}"
                                            method="POST" style="min-width: 220px;"
                                            onsubmit="return confirm('{{ $submission->status !== 'completed' ? 'Mark this submission as complete?' : 'Update the score/feedback?' }}')">
                                            @csrf
                                            <div class="input-group input-group-sm mb-2">
                                                <span class="input-group-text">Score</span>
                                                <input type="number" name="score" class="form-control"
                                                    min="0" max="100" placeholder="0–100"
                                                    value="{{ old('score', $submission->score) }}">
                                            </div>
                                            <textarea name="feedback" rows="2" class="form-control form-control-sm mb-2"
                                                placeholder="Feedback for user (optional)">{{ old('feedback', $submission->feedback) }}</textarea>
                                            <button class="btn btn-sm {{ $submission->status !== 'completed' ? 'btn-success' : 'btn-outline-primary' }} w-100">
                                                <i class="ti ti-check"></i>
                                                {{ $submission->status !== 'completed' ? 'Mark as complete' : 'Update score' }}
                                            </button>
                                        </form>
                                        @if (! $submission->resubmit_requested)
                                            <form action="{{ route('manage.task.submissions.resubmit', [$task, $submission]) }}"
                                                method="POST" class="mt-2"
                                                onsubmit="return confirm('Ask this user to resubmit their answers?')">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-warning w-100">
                                                    <i class="ti ti-refresh me-1"></i>Request Resubmit
                                                </button>
                                            </form>
                                        @else
                                            <div class="mt-2"><span class="badge bg-warning text-dark w-100"><i class="ti ti-refresh me-1"></i>Resubmit requested</span></div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        No responses yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    {{-- Modal Bulk Complete --}}
    <div class="modal fade" id="bulkCompleteModal" tabindex="-1" aria-labelledby="bulkCompleteModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <form action="{{ route('manage.task.submissions.bulk-complete', $task) }}" method="POST" id="bulkCompleteForm">
                    @csrf
                    <div id="bulkSubmissionInputs"></div>
                    <div class="modal-header border-0 pb-0 px-4 pt-4">
                        <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="bulkCompleteModalTitle">
                            <i class="ti ti-checks text-success"></i> Bulk Mark as Complete
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body px-4 py-3">
                        <p class="text-muted mb-3">
                            You are about to mark <strong id="modalSelectedCount">0</strong> selected submission(s) as completed.
                        </p>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Score (Optional)</label>
                            <input type="number" name="score" class="form-control" min="0" max="100" placeholder="0–100 for all selected (or leave blank)">
                            <div class="form-hint">Leave blank if you don't want to set a fixed score.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Feedback (Optional)</label>
                            <textarea name="feedback" rows="3" class="form-control" placeholder="Admin feedback for all selected users..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success px-4">
                            <i class="ti ti-check me-1"></i> Confirm & Complete
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var selectAllCheckbox = document.getElementById('selectAllCheckbox');
        var itemCheckboxes = document.querySelectorAll('.submission-checkbox');
        var btnOpenBulkModal = document.getElementById('btnOpenBulkModal');
        var selectedCountSpan = document.getElementById('selectedCount');
        var modalSelectedCount = document.getElementById('modalSelectedCount');
        var bulkSubmissionInputs = document.getElementById('bulkSubmissionInputs');

        function updateSelectionState() {
            var checkedItems = document.querySelectorAll('.submission-checkbox:checked');
            var count = checkedItems.length;

            if (selectedCountSpan) selectedCountSpan.textContent = count;
            if (modalSelectedCount) modalSelectedCount.textContent = count;
            if (btnOpenBulkModal) btnOpenBulkModal.disabled = (count === 0);

            if (selectAllCheckbox) {
                if (itemCheckboxes.length > 0 && count === itemCheckboxes.length) {
                    selectAllCheckbox.checked = true;
                    selectAllCheckbox.indeterminate = false;
                } else if (count > 0) {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = true;
                } else {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = false;
                }
            }

            // Sync hidden inputs for modal form
            if (bulkSubmissionInputs) {
                bulkSubmissionInputs.innerHTML = '';
                checkedItems.forEach(function (chk) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'submission_ids[]';
                    input.value = chk.value;
                    bulkSubmissionInputs.appendChild(input);
                });
            }
        }

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function () {
                var isChecked = this.checked;
                itemCheckboxes.forEach(function (chk) {
                    chk.checked = isChecked;
                });
                updateSelectionState();
            });
        }

        itemCheckboxes.forEach(function (chk) {
            chk.addEventListener('change', updateSelectionState);
        });

        updateSelectionState();
    });
    </script>
@endsection
