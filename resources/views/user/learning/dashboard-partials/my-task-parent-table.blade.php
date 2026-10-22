{{-- Parent (course/placement) table with task dropdown & pop up modal triggers --}}
<div class="card border-0 shadow-sm rounded-4">
    <div class="table-responsive">
        <table class="table table-vcenter mb-0">
            <thead>
                <tr>
                    <th class="w-1">NO</th>
                    <th>COURSE / PLACEMENT</th>
                    <th>TYPE</th>
                    <th class="text-center">PROGRESS</th>
                    <th>DEADLINE</th>
                    <th>STATUS</th>
                    <th class="text-end">TASKS & ACTION</th>
                </tr>
            </thead>
            <tbody id="parentTableAccordion">
                @foreach ($tableGroups as $i => $group)
                    @php
                        $isAssessmentGroup = ($group['parent_type'] ?? '') === 'assessment';
                        $firstTask = $group['tasks']->first();
                    @endphp
                    <tr>
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td>
                            @if ($isAssessmentGroup)
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar avatar-xs bg-purple-lt text-purple rounded">
                                        <i class="ti ti-clipboard-check"></i>
                                    </span>
                                    <div>
                                        <span class="fw-bold text-dark">{{ $group['parent_name'] }}</span>
                                        @if (!empty($group['course_name']))
                                            <div class="text-muted small">{{ $group['course_name'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            @elseif ($group['parent_url'])
                                <a href="{{ $group['parent_url'] }}" class="fw-bold text-dark text-decoration-none d-inline-flex align-items-center gap-1">
                                    <i class="ti ti-book-2 text-primary"></i> {{ $group['parent_name'] }}
                                </a>
                            @else
                                <span class="fw-bold text-dark d-inline-flex align-items-center gap-1">
                                    <i class="ti ti-bookmark text-primary"></i> {{ $group['parent_name'] }}
                                </span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $parentTypeBadge[$group['parent_type']] ?? 'bg-secondary-lt text-secondary' }}">
                                {{ $parentTypeLabel[$group['parent_type']] ?? $group['parent_type'] }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="fw-semibold small">{{ $group['completed'] }}/{{ $group['total'] }} Completed</span>
                            <div class="progress mx-auto mt-1" style="height:4px;max-width:90px">
                                <div class="progress-bar bg-success" role="progressbar"
                                     style="width: {{ $group['total'] ? round($group['completed'] / $group['total'] * 100) : 0 }}%"></div>
                            </div>
                        </td>
                        <td class="text-nowrap">
                            @if ($group['nearest_deadline'])
                                @php
                                    $gdl = $group['nearest_deadline'];
                                    $gdlOverdue = $gdl->isPast();
                                    $gdlSoon = ! $gdlOverdue && $gdl->diffInDays(now()) <= 3;
                                @endphp
                                @if ($gdlOverdue)
                                    <span class="badge bg-danger"><i class="ti ti-alert-circle me-1"></i>{{ $gdl->format('d M Y') }}</span>
                                @elseif ($gdlSoon)
                                    <span class="badge bg-warning text-dark"><i class="ti ti-clock me-1"></i>{{ $gdl->format('d M Y') }}</span>
                                @else
                                    <span class="text-muted">{{ $gdl->format('d M Y') }}</span>
                                @endif
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if ($isAssessmentGroup && $firstTask && ($firstTask->is_completed ?? false))
                                @if ($firstTask->is_passed ?? false)
                                    <span class="badge bg-success-lt text-success">Completed</span>
                                @else
                                    <span class="badge bg-danger-lt text-danger">Failed</span>
                                @endif
                            @elseif (in_array($filter, ['submitted', 'completed', 'overdue'], true))
                                {{-- Legacy task-status tab --}}
                                <span class="badge {{ $statusBadges[$filter] ?? 'bg-secondary-lt text-secondary' }}">{{ $statusLabels[$filter] ?? $filter }}</span>
                            @elseif ($group['has_overdue'])
                                <span class="badge bg-danger-lt text-danger">Overdue</span>
                            @elseif ($group['completed'] === $group['total'] && $group['total'] > 0)
                                <span class="badge bg-success-lt text-success">Completed</span>
                            @else
                                <span class="badge bg-primary-lt text-primary">In Progress</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex align-items-center gap-1">
                                {{-- Dropdown Task / Assessment List --}}
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle d-inline-flex align-items-center gap-1 shadow-sm px-2.5 py-1.5"
                                            type="button"
                                            id="dropdownCourse-{{ $i }}"
                                            data-bs-toggle="dropdown"
                                            data-bs-popper-config='{"strategy":"fixed"}'
                                            aria-expanded="false">
                                        <i class="ti {{ $isAssessmentGroup ? 'ti-clipboard-check' : 'ti-list-check' }} me-1"></i>
                                        <span>{{ $isAssessmentGroup ? 'Select Assessment' : 'Select Tasks' }}</span>
                                        <span class="badge bg-white text-primary ms-1 px-1.5 py-0.5 rounded-pill fw-bold">{{ $group['total'] }}</span>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-2"
                                         aria-labelledby="dropdownCourse-{{ $i }}"
                                         style="min-width: 340px; max-width: 440px; z-index: 1060;">
                                        <div class="px-3 py-2 border-bottom mb-2 d-flex justify-content-between align-items-center bg-light-subtle rounded-top-3">
                                            <span class="fw-bold text-dark small d-flex align-items-center gap-1">
                                                <i class="ti {{ $isAssessmentGroup ? 'ti-clipboard-check text-purple' : 'ti-checklist text-primary' }}"></i>
                                                {{ $isAssessmentGroup ? 'Assessment' : 'Tasks' }}: {{ Str::limit($group['parent_name'], 25) }}
                                            </span>
                                            <span class="badge bg-success-lt text-success">{{ $group['completed'] }}/{{ $group['total'] }} Completed</span>
                                        </div>
                                        <div class="d-flex flex-column gap-1" style="max-height: 320px; overflow-y: auto;">
                                            @foreach ($group['tasks'] as $task)
                                                @php
                                                    $tStatus = $task->userStatusFor(auth()->user());
                                                    $tSubmission = $task->submissionFor(auth()->user());
                                                    $isPending = in_array($tStatus, ['not_started', 'in_progress', 'resubmit', 'overdue']);
                                                    $isTaskAssessment = $task->is_assessment ?? false;
                                                @endphp
                                                <button type="button"
                                                        class="dropdown-item py-2 px-3 rounded-3 text-start d-flex align-items-center justify-content-between gap-2 btn-open-task-modal"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#taskModal-{{ $task->id }}">
                                                    <div class="me-2 text-wrap" style="max-width: 220px;">
                                                        <div class="fw-semibold text-dark text-truncate" title="{{ $task->title }}">
                                                            <i class="ti {{ $isTaskAssessment ? 'ti-clipboard-text text-purple' : 'ti-file-pencil text-primary' }} me-1"></i>{{ $task->title }}
                                                        </div>
                                                        <div class="d-flex align-items-center gap-1 text-muted" style="font-size: 11px;">
                                                            <span class="badge {{ $isTaskAssessment ? 'bg-purple-lt text-purple' : 'bg-secondary-lt' }} px-1 py-0" style="font-size: 10px;">{{ $task->typeLabel() }}</span>
                                                            @if ($task->deadline)
                                                                <span>&middot; {{ $task->deadline->format('d M') }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="d-flex flex-column align-items-end flex-shrink-0 gap-1">
                                                        @if ($isTaskAssessment && ($task->is_completed ?? false))
                                                            <span class="badge {{ ($task->is_passed ?? false) ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger' }}" style="font-size: 10px;">
                                                                {{ ($task->is_passed ?? false) ? 'Passed' : 'Failed' }}
                                                            </span>
                                                        @else
                                                            <span class="badge {{ $statusBadges[$displayStatus($tStatus)] ?? 'bg-secondary-lt text-secondary' }}" style="font-size: 10px;">
                                                                {{ $statusLabels[$displayStatus($tStatus)] ?? $displayStatus($tStatus) }}
                                                            </span>
                                                        @endif
                                                        @if ($isPending)
                                                            <span class="text-primary fw-semibold" style="font-size: 10px;">
                                                                {{ $isTaskAssessment ? 'Start' : 'Submit' }} <i class="ti ti-arrow-right"></i>
                                                            </span>
                                                        @else
                                                            <span class="text-muted" style="font-size: 10px;">View <i class="ti ti-eye"></i></span>
                                                        @endif
                                                    </div>
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                {{-- Collapsible Chevron to Expand Tasks Inline --}}
                                <button type="button" class="btn btn-sm btn-icon btn-outline-secondary btn-task-collapse-toggle"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#collapseTasks-{{ $i }}"
                                        aria-expanded="false"
                                        title="Show list below row">
                                    <i class="ti ti-chevron-down"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    {{-- Collapsible Task List Sub-row --}}
                    <tr class="collapse bg-light-subtle task-collapse-row" id="collapseTasks-{{ $i }}" data-bs-parent="#parentTableAccordion">
                        <td colspan="7" class="p-3">
                            <div class="card border rounded-3 p-3 bg-white shadow-none mb-0">
                                <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                                    <span class="fw-bold text-dark small d-flex align-items-center gap-1">
                                        <i class="ti {{ $isAssessmentGroup ? 'ti-clipboard-check text-purple' : 'ti-list-check text-primary' }}"></i>
                                        {{ $isAssessmentGroup ? 'Assessment' : 'Task' }} List: {{ $group['parent_name'] }}
                                    </span>
                                    <span class="text-muted small">Click <strong>{{ $isAssessmentGroup ? 'Start Assessment' : 'Submit / View Task' }}</strong> to open the popup modal</span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-vcenter mb-0">
                                        <thead>
                                            <tr>
                                                <th class="w-1">NO</th>
                                                <th>{{ $isAssessmentGroup ? 'ASSESSMENT TITLE' : 'TASK TITLE' }}</th>
                                                <th>TYPE</th>
                                                <th>DEADLINE</th>
                                                <th class="text-center">SCORE</th>
                                                <th>STATUS</th>
                                                <th class="text-end">ACTION</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($group['tasks'] as $tIdx => $task)
                                                @php
                                                    $tStatus = $task->userStatusFor(auth()->user());
                                                    $tSubmission = $task->submissionFor(auth()->user());
                                                    $isTaskAssessment = $task->is_assessment ?? false;
                                                @endphp
                                                <tr>
                                                    <td class="text-muted">{{ $tIdx + 1 }}</td>
                                                    <td>
                                                        <span class="fw-semibold text-dark">{{ $task->title }}</span>
                                                        @if ($task->description)
                                                            <div class="text-muted small" style="color: #000000;">{{ Str::limit($task->description, 60) }}</div>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge {{ $isTaskAssessment ? 'bg-purple-lt text-purple' : 'bg-primary-lt text-primary' }}">
                                                            {{ $task->typeLabel() }}
                                                        </span>
                                                    </td>
                                                    <td class="text-nowrap small">
                                                        {{ $task->deadline ? $task->deadline->format('d M Y H:i') : '-' }}
                                                    </td>
                                                    <td class="text-center small">
                                                        @if (!is_null($task->score ?? null))
                                                            <span class="fw-bold {{ ($task->is_passed ?? false) ? 'text-success' : 'text-danger' }}">
                                                                {{ $task->score }}
                                                            </span>
                                                        @elseif ($tSubmission && ! is_null($tSubmission->score))
                                                            {{ $tSubmission->score }}
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($isTaskAssessment && ($task->is_completed ?? false))
                                                            <span class="badge {{ ($task->is_passed ?? false) ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger' }}">
                                                                {{ ($task->is_passed ?? false) ? 'Passed' : 'Failed' }}
                                                            </span>
                                                        @else
                                                            <span class="badge {{ $statusBadges[$displayStatus($tStatus)] ?? 'bg-secondary-lt text-secondary' }}">
                                                                {{ $statusLabels[$displayStatus($tStatus)] ?? $displayStatus($tStatus) }}
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td class="text-end">
                                                        <button type="button"
                                                                class="btn btn-sm {{ in_array($tStatus, ['completed', 'submitted']) ? 'btn-outline-secondary' : 'btn-primary' }} rounded-3"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#taskModal-{{ $task->id }}">
                                                            <i class="ti {{ in_array($tStatus, ['completed', 'submitted']) ? 'ti-eye' : 'ti-pencil' }} me-1"></i>
                                                            {{ in_array($tStatus, ['completed', 'submitted']) ? 'View' : ($isTaskAssessment ? 'Start' : 'Submit') }}
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
