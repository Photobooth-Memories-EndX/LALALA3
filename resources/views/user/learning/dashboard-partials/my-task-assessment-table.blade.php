{{-- Assessment table matching My Learning Assessment 8-column layout --}}
<div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="table-responsive">
        <table class="table table-vcenter table-hover card-table mb-0 assessment-table">
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
                @foreach ($assessmentGroups as $index => $group)
                    @php
                        $task = $group['tasks']->first();
                        if (!$task) continue;
                        $isTaskCompleted = $task->is_completed ?? false;
                        $isTaskPassed = $task->is_passed ?? false;
                        $isTaskExpired = $task->is_expired ?? false;
                        $courseTitle = $group['course_name'] ?? $task->parentTitle();
                    @endphp
                    <tr class="assessment-row">
                        <td class="text-center text-muted align-middle">{{ $index + 1 }}</td>
                        <td class="text-start align-middle">
                            <a href="javascript:void(0);"
                               role="button"
                               class="fw-semibold text-dark text-decoration-none assessment-title-link"
                               data-bs-toggle="modal"
                               data-bs-target="#taskModal-{{ $task->id }}">
                                {{ $task->title ?? '-' }}
                            </a>
                            @if (!empty($courseTitle))
                                <div class="text-muted small d-flex align-items-center gap-1 mt-0.5">
                                    <i class="ti ti-bookmark fs-3"></i>
                                    <span>{{ $courseTitle }}</span>
                                </div>
                            @endif
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
    </div>
</div>
