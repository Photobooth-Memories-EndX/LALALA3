{{-- resources/views/manage/task/submissions.blade.php --}}
{{-- Review submissions: admin views user answers per question, downloads files, marks complete. --}}
@extends('layouts.sidebar-master')

@section('content')
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        <a href="{{ route('manage.task.index') }}">Task Management</a>
                        &nbsp;/&nbsp;
                        <a href="{{ route('manage.task.parent', $task->parentRouteParams()) }}">{{ $task->parentName() }}</a>
                    </div>
                    <h2 class="page-title">Submissions: {{ $task->title }}</h2>
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
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form method="GET" class="mb-3">
                <select name="status" class="form-select w-auto d-inline-block" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pending review</option>
                    <option value="completed" @selected(request('status') === 'completed')>Completed</option>
                </select>
            </form>

            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
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
                                                        @if ($task->isAssessment() && $field->isChoice())
                                                            @php $ok = $answer && $answer->answer_text === $field->correct_answer; @endphp
                                                            <span class="badge {{ $ok ? 'bg-success' : 'bg-danger' }} ms-1">
                                                                {{ $ok ? 'Correct' : 'Wrong' }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    @if ($answer && $answer->file_path)
                                                        <a href="{{ Storage::url($answer->file_path) }}" target="_blank"
                                                            class="btn btn-sm btn-outline-secondary">
                                                            <i class="ti ti-download"></i> Download file
                                                        </a>
                                                        <div class="text-muted small">{{ basename($answer->file_path) }}</div>
                                                    @elseif ($answer && $answer->answer_text)
                                                        <div class="text-muted small">{{ Str::limit($answer->answer_text, 150) }}</div>
                                                        @if ($task->isAssessment() && $field->isChoice() && !$ok)
                                                            <div class="text-success small">Correct answer: {{ $field->correct_answer }}</div>
                                                        @endif
                                                    @else
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
                                        {{-- Score/feedback form: marks pending submissions complete,
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
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No submissions yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <a href="{{ route('manage.task.parent', $task->parentRouteParams()) }}" class="btn btn-outline-secondary mt-3">
                <i class="ti ti-arrow-left"></i> Back to task list
            </a>
        </div>
    </div>
@endsection
