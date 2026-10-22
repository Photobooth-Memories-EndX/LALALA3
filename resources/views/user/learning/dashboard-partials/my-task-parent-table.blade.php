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
                    <th class="text-end">ACTION</th>
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
                        <td class="course-col-trigger"
                            data-bs-toggle="collapse"
                            data-bs-target="#collapseTasks-{{ $i }}"
                            role="button"
                            aria-expanded="false"
                            style="cursor: pointer;"
                            title="Click to view tasks below">
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar avatar-xs {{ $isAssessmentGroup ? 'bg-purple-lt text-purple' : 'bg-primary-lt text-primary' }} rounded">
                                        <i class="ti {{ $isAssessmentGroup ? 'ti-clipboard-check' : 'ti-book-2' }}"></i>
                                    </span>
                                    <div>
                                        <div class="fw-bold text-dark course-title-text d-inline-flex align-items-center gap-1">
                                            <span>{{ $group['parent_name'] }}</span>
                                            <i class="ti ti-chevron-down text-muted small ms-1 transition-transform"></i>
                                        </div>
                                        @if (!empty($group['course_name']))
                                            <div class="text-muted small">{{ $group['course_name'] }}</div>
                                        @endif
                                    </div>
                                </div>
                                <span class="badge bg-secondary-lt text-secondary px-1.5 py-0.5 rounded-pill small" title="{{ $group['total'] }} {{ $isAssessmentGroup ? 'Assessment' : 'Tasks' }}">
                                    {{ $group['total'] }}
                                </span>
                            </div>
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
                                @if ($group['parent_url'])
                                    <a href="{{ $group['parent_url'] }}"
                                       class="btn btn-sm btn-icon btn-ghost-secondary"
                                       title="Open course details"
                                       target="_blank"
                                       onclick="event.stopPropagation();">
                                        <i class="ti ti-external-link"></i>
                                    </a>
                                @endif
                                <button type="button"
                                        class="btn btn-sm btn-icon btn-outline-secondary btn-task-collapse-toggle"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#collapseTasks-{{ $i }}"
                                        aria-expanded="false"
                                        title="Show tasks below">
                                    <i class="ti ti-chevron-down transition-transform"></i>
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
                                    @if ($isAssessmentGroup)
                                        <table class="table table-sm table-vcenter mb-0">
                                            <thead>
                                                <tr>
                                                    <th class="text-center align-middle" style="width: 50px;">NO</th>
                                                    <th class="text-start align-middle">ASSESSMENT</th>
                                                    <th class="text-center align-middle" style="width: 155px;">TYPE</th>
                                                    <th class="text-center align-middle" style="width: 110px;">CATEGORY</th>
                                                    <th class="text-start align-middle" style="width: 170px;">DATE & TIME</th>
                                                    <th class="text-center align-middle" style="width: 95px;">SCORE</th>
                                                    <th class="text-center align-middle" style="width: 130px;">STATUS</th>
                                                    <th class="text-center align-middle" style="width: 135px;">ACTION</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($group['tasks'] as $tIdx => $task)
                                                    @php
                                                        $isTaskCompleted = $task->is_completed ?? false;
                                                        $isTaskPassed = $task->is_passed ?? false;
                                                        $isTaskExpired = $task->is_expired ?? false;
                                                    @endphp
                                                    <tr>
                                                        <td class="text-center text-muted align-middle">{{ $tIdx + 1 }}</td>
                                                        <td class="text-start align-middle">
                                                            <a href="javascript:void(0);"
                                                               role="button"
                                                               class="fw-semibold text-dark text-decoration-none"
                                                               data-bs-toggle="modal"
                                                               data-bs-target="#taskModal-{{ $task->id }}">
                                                                {{ $task->title ?? '-' }}
                                                            </a>
                                                            @if (!empty($task->description))
                                                                <div class="text-muted small mt-0.5" style="color: #666666;">
                                                                    {{ Str::limit($task->description, 80) }}
                                                                </div>
                                                            @endif
                                                        </td>
                                                        <td class="text-center align-middle">
                                                            <x-learning.assessment.assessment-type
                                                                :type="$task->filterType()"
                                                                :label="$task->typeDisplay()"
                                                            />
                                                        </td>
                                                        <td class="text-center align-middle">
                                                            <x-learning.assessment.assessment-category
                                                                :category="$task->categoryDisplay()"
                                                            />
                                                        </td>
                                                        <td class="text-start align-middle small lh-sm">
                                                            @if (method_exists($task, 'takenDateDisplay') && $task->takenDateDisplay())
                                                                <div>
                                                                    <span class="text-muted fw-medium">Taken:</span> {{ $task->takenDateDisplay() }}
                                                                </div>
                                                            @endif

                                                            @if (method_exists($task, 'hasAccessPeriod') && $task->hasAccessPeriod() && $task->fromDateDisplay() && $task->toDateDisplay())
                                                                <div class="{{ (method_exists($task, 'takenDateDisplay') && $task->takenDateDisplay()) ? 'mt-1 text-muted' : '' }}">
                                                                    <span class="text-muted fw-medium">{{ (method_exists($task, 'takenDateDisplay') && $task->takenDateDisplay()) ? 'Period:' : 'From:' }}</span> {{ $task->fromDateDisplay() }}
                                                                    @if (method_exists($task, 'takenDateDisplay') && $task->takenDateDisplay())
                                                                        – {{ $task->toDateDisplay() }}
                                                                    @else
                                                                        </div>
                                                                        <div>
                                                                            <span class="text-muted fw-medium">To:</span> {{ $task->toDateDisplay() }}
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            @endif

                                                            @if (method_exists($task, 'hasTimeLimit') && $task->hasTimeLimit())
                                                                <div class="{{ ((method_exists($task, 'hasAccessPeriod') && $task->hasAccessPeriod()) || (method_exists($task, 'takenDateDisplay') && $task->takenDateDisplay())) ? 'mt-1' : '' }}">
                                                                    <span class="badge bg-blue-lt text-blue"><i class="ti ti-clock me-1"></i>{{ $task->time_limit }} mins</span>
                                                                </div>
                                                            @elseif ((!method_exists($task, 'hasAccessPeriod') || !$task->hasAccessPeriod()) && (!method_exists($task, 'takenDateDisplay') || !$task->takenDateDisplay()))
                                                                <span class="text-muted">No Deadline</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center align-middle">
                                                            @if (!is_null($task->score))
                                                                <div class="d-inline-flex flex-column align-items-center">
                                                                    <span class="fw-bold fs-3 {{ $isTaskPassed ? 'text-success' : 'text-danger' }}">
                                                                        {{ $task->score }}
                                                                    </span>
                                                                    @if ($task->passing_grade)
                                                                        <span class="text-muted" style="font-size: 11px;">
                                                                            Passing: {{ $task->passing_grade }}%
                                                                        </span>
                                                                    @endif
                                                                </div>
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center align-middle">
                                                            <x-learning.assessment.assessment-status
                                                                :final-result="$task->res ?? null"
                                                                :is-completed="$isTaskCompleted"
                                                                :is-passed="$isTaskPassed"
                                                                :passing-grade="$task->passing_grade"
                                                            />
                                                        </td>
                                                        <td class="text-center align-middle">
                                                            @if ($isTaskCompleted)
                                                                @if ($isTaskPassed)
                                                                    <button type="button"
                                                                            class="btn btn-sm btn-white rounded-3 shadow-none border px-3"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#taskModal-{{ $task->id }}">
                                                                        <i class="ti ti-eye me-1"></i> View
                                                                    </button>
                                                                @else
                                                                    <button type="button"
                                                                            class="btn btn-sm btn-warning text-dark fw-semibold rounded-3 shadow-sm px-3"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#taskModal-{{ $task->id }}">
                                                                        <i class="ti ti-refresh me-1"></i> Remedial
                                                                    </button>
                                                                @endif
                                                            @elseif ($isTaskExpired)
                                                                <button type="button"
                                                                        class="btn btn-sm btn-outline-danger rounded-3 px-3"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#taskModal-{{ $task->id }}">
                                                                    <i class="ti ti-clock-off me-1"></i> Expired
                                                                </button>
                                                            @else
                                                                <button type="button"
                                                                        class="btn btn-sm btn-success rounded-3 shadow-sm px-3"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#taskModal-{{ $task->id }}">
                                                                    <i class="ti ti-player-play me-1"></i> Start
                                                                </button>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @else
                                        <table class="table table-sm table-vcenter mb-0">
                                            <thead>
                                                <tr>
                                                    <th class="w-1">NO</th>
                                                    <th>TASK TITLE</th>
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
                                                            <span class="badge bg-primary-lt text-primary">
                                                                {{ $task->typeLabel() }}
                                                            </span>
                                                        </td>
                                                        <td class="text-nowrap small">
                                                            {{ $task->deadline ? $task->deadline->format('d M Y H:i') : '-' }}
                                                        </td>
                                                        <td class="text-center small">
                                                            @if ($tSubmission && ! is_null($tSubmission->score))
                                                                {{ $tSubmission->score }}
                                                            @else
                                                                -
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span class="badge {{ $statusBadges[$displayStatus($tStatus)] ?? 'bg-secondary-lt text-secondary' }}">
                                                                {{ $statusLabels[$displayStatus($tStatus)] ?? $displayStatus($tStatus) }}
                                                            </span>
                                                        </td>
                                                        <td class="text-end">
                                                            <button type="button"
                                                                    class="btn btn-sm {{ in_array($tStatus, ['completed', 'submitted']) ? 'btn-outline-secondary' : 'btn-primary' }} rounded-3"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#taskModal-{{ $task->id }}">
                                                                <i class="ti {{ in_array($tStatus, ['completed', 'submitted']) ? 'ti-eye' : 'ti-pencil' }} me-1"></i>
                                                                {{ in_array($tStatus, ['completed', 'submitted']) ? 'View' : 'Submit' }}
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<style>
.course-col-trigger:hover .course-title-text {
    color: var(--tblr-primary, #206bc4) !important;
}
.course-col-trigger:hover {
    background-color: rgba(32, 107, 196, 0.04);
}
.transition-transform {
    transition: transform 0.2s ease-in-out;
}
</style>
