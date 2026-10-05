{{-- Card "Course Tasks" — daftar task di course ini.
    Cara pasang: tambahkan baris ini di view.blade.php,
    tepat di bawah @include('user.playlist.components.view-navigation', [...]):

        @include('user.playlist.components.view-course-tasks')
--}}
@php
    $courseTasks = collect();
    $courseTaskUser = auth()->user();
    if (class_exists(\App\Models\Task::class) && $courseTaskUser && isset($playlist)) {
        $courseTasks = \App\Models\Task::with(['submissions' => fn ($q) => $q->where('user_id', $courseTaskUser->id)])
            ->where('training_plan_id', $playlist->id)
            ->published()
            ->orderByRaw('deadline IS NULL, deadline ASC')
            ->get();
    }
    $courseTaskBadges = [
        'not_started' => 'bg-secondary-lt text-secondary',
        'submitted'   => 'bg-warning-lt text-warning',
        'completed'   => 'bg-success-lt text-success',
        'overdue'     => 'bg-danger-lt text-danger',
    ];
    $courseTaskLabels = [
        'not_started' => 'Not started',
        'submitted'   => 'Pending review',
        'completed'   => 'Completed',
        'overdue'     => 'Overdue',
    ];
@endphp

@if ($courseTasks->isNotEmpty())
    <div class="card border-0 shadow-sm rounded-4 mb-4" id="course-tasks">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                <h3 class="card-title mb-0">
                    <i class="ti ti-list-check me-2"></i>Course Tasks
                </h3>
                <a href="{{ url('/my-learning?learning_tab=my-task') }}" class="btn btn-sm btn-outline-primary">
                    Open My Task<i class="ti ti-arrow-right ms-1"></i>
                </a>
            </div>

            <div class="list-group list-group-flush">
                @foreach ($courseTasks as $courseTask)
                    @php
                        $ctStatus = $courseTask->userStatusFor($courseTaskUser);
                        $ctSubmission = $courseTask->submissionFor($courseTaskUser);
                    @endphp
                    <div class="list-group-item px-0 d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="me-3">
                            <div class="fw-semibold">{{ $courseTask->title }}</div>
                            <div class="text-muted small">
                                <span class="badge bg-primary-lt text-primary me-1">{{ \App\Models\Task::TYPES[$courseTask->type] ?? $courseTask->type }}</span>
                                @if ($courseTask->deadline)
                                    <i class="ti ti-calendar-due me-1"></i>Deadline: {{ $courseTask->deadline->format('d M Y H:i') }}
                                @else
                                    <i class="ti ti-calendar me-1"></i>No deadline
                                @endif
                                @if ($ctSubmission && ! is_null($ctSubmission->score))
                                    <span class="badge bg-success-lt text-success ms-2">Score: {{ $ctSubmission->score }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge {{ $courseTaskBadges[$ctStatus] ?? 'bg-secondary-lt text-secondary' }}">{{ $courseTaskLabels[$ctStatus] ?? $ctStatus }}</span>
                            <a href="{{ url('/my-learning?learning_tab=my-task') }}"
                               class="btn btn-sm {{ $ctStatus === 'completed' ? 'btn-outline-secondary' : 'btn-primary' }}">
                                {{ $ctStatus === 'completed' ? 'View' : 'Work on it' }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
