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
        'in_progress' => 'In Progress',
        'submitted'   => 'Pending review',
        'completed'   => 'Completed',
        'rejected'    => 'Rejected',
        'overdue'     => 'Overdue',
        'resubmit'    => 'Rejected',
    ];
    $statusBadges = [
        'in_progress' => 'bg-primary-lt text-primary',
        'submitted'   => 'bg-warning-lt text-warning',
        'completed'   => 'bg-success-lt text-success',
        'rejected'    => 'bg-danger-lt text-danger',
        'overdue'     => 'bg-danger-lt text-danger',
        'resubmit'    => 'bg-danger-lt text-danger',
    ];
    // "Not started" is not a filter tab — display it as In Progress so the
    // status badge always matches one of the tabs.
    $displayStatus = fn ($s) => $s === 'not_started' ? 'in_progress' : $s;
    $parentTypeBadge = [
        'playlist'         => 'bg-primary-lt text-primary',
        'content'          => 'bg-info-lt text-info',
        'offline_training' => 'bg-warning-lt text-warning',
        'standalone'       => 'bg-secondary-lt text-secondary',
        'assessment'       => 'bg-purple-lt text-purple',
    ];
    $parentTypeLabel = [
        'playlist'         => 'Playlist',
        'content'          => 'Content',
        'offline_training' => 'Offline Training',
        'standalone'       => 'Others',
        'assessment'       => 'Assessment',
    ];
    // Drill-down navigation: parent list <-> one parent's tasks.
    // Drill-down navigation: parent list <-> one parent's tasks.
    $baseParams = array_filter([
        'learning_tab'   => 'my-task',
        'my_task_filter' => $filter === 'all' ? null : $filter,
        'my_task_status' => ($filterStatus ?? 'all') === 'all' ? null : $filterStatus,
        'my_task_year'   => ($filterYear ?? 'all') === 'all' ? null : $filterYear,
        'my_task_month'  => ($filterMonth ?? 'all') === 'all' ? null : $filterMonth,
        'my_task_q'      => ($search ?? '') !== '' ? $search : null,
    ]);
    $backUrl = route('user.learning.dashboard', $baseParams);
    $groupTasksUrl = fn ($key) => route('user.learning.dashboard', $baseParams + ['my_task_parent' => $key]);
    $resetUrl = route('user.learning.dashboard', array_filter([
        'learning_tab'   => 'my-task',
        'my_task_parent' => ($activeGroup ?? null) ? $activeGroup['key'] : null,
    ]));
    $tabs = [
        'all'      => ['label' => 'All Tasks'],
        'playlist' => ['label' => 'Playlist'],
        'content'  => ['label' => 'Content'],
        'asesment' => ['label' => 'Assessment'],
        'others'   => ['label' => 'Others'],
    ];
    // Clicking a tab navigates immediately (keeps search + parent context + dropdown filters).
    $tabUrl = fn ($key) => route('user.learning.dashboard', array_filter([
        'learning_tab'   => 'my-task',
        'my_task_filter' => $key === 'all' ? null : $key,
        'my_task_status' => ($filterStatus ?? 'all') === 'all' ? null : $filterStatus,
        'my_task_year'   => ($filterYear ?? 'all') === 'all' ? null : $filterYear,
        'my_task_month'  => ($filterMonth ?? 'all') === 'all' ? null : $filterMonth,
        'my_task_q'      => ($search ?? '') !== '' ? $search : null,
        'my_task_parent' => ($activeGroup ?? null) ? $activeGroup['key'] : null,
    ]));
@endphp

{{-- Flash Alerts --}}
@if (session('success'))
    <div class="alert alert-success alert-dismissible shadow-sm rounded-4 mb-3" role="alert">
        <div class="d-flex align-items-center">
            <i class="ti ti-check fs-2 me-2"></i>
            <div class="fw-semibold">{{ session('success') }}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if (session('info'))
    <div class="alert alert-info alert-dismissible shadow-sm rounded-4 mb-3" role="alert">
        <div class="d-flex align-items-center">
            <i class="ti ti-info-circle fs-2 me-2"></i>
            <div>{{ session('info') }}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if ($errors->any())
    <div class="alert alert-danger alert-dismissible shadow-sm rounded-4 mb-3" role="alert">
        <div class="d-flex align-items-center">
            <i class="ti ti-alert-triangle fs-2 me-2"></i>
            <div>
                <strong>Notice:</strong> There was an error submitting your answers. Please review the open form.
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

{{-- Banner Notifikasi Tugas Perlu Revisi --}}
@if (($summary['resubmit'] ?? 0) > 0)
    <div class="alert alert-warning border-start border-warning border-4 rounded-4 shadow-sm mb-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <span class="avatar bg-warning text-dark rounded-circle shadow-sm">
                    <i class="ti ti-refresh fs-2"></i>
                </span>
                <div>
                    <h4 class="alert-title mb-1 fw-bold text-dark">
                        {{ $summary['resubmit'] }} Task{{ ($summary['resubmit'] ?? 0) > 1 ? 's' : '' }} Require Revision
                    </h4>
                    <div class="text-muted small">
                        The administrator reviewed your answers and requested revisions. Please open tasks marked <strong>Resubmit Requested</strong> to review notes and resubmit your answers.
                    </div>
                </div>
            </div>
            @if ($filter !== 'resubmit')
                <a href="{{ $tabUrl('resubmit') }}" class="btn btn-warning text-dark fw-semibold px-3 py-2 rounded-pill shadow-sm">
                    <i class="ti ti-eye me-1"></i> View Revision Tasks ({{ $summary['resubmit'] }})
                </a>
            @endif
        </div>
    </div>
@endif

{{-- Quick Filter Bar (Matching Reference Layout) --}}
<div class="card border-0 shadow-sm rounded-4 mb-3 my-task-filter-card">
    <div class="card-body p-3 p-md-4">
        {{-- Header: Title & Subtitle --}}
        <div class="mb-3">
            <h2 class="fw-bold mb-1 my-task-filter-title">My Tasks</h2>
            <p class="text-muted small mb-0 my-task-filter-subtitle">Manage and complete your assigned learning activities.</p>
        </div>

        {{-- Category Filter Pills --}}
        <div class="d-flex align-items-center flex-wrap gap-2 mb-3 my-task-pills-row">
            @foreach ($tabs as $key => $tab)
                @php
                    $isActive = $filter === $key;
                @endphp
                <a href="{{ $tabUrl($key) }}"
                   class="my-task-category-pill {{ $isActive ? 'active' : '' }}"
                   title="Filter by {{ $tab['label'] }}">
                    {{ $tab['label'] }}
                </a>
            @endforeach
        </div>

        {{-- Subtle Divider Line --}}
        <hr class="my-3 my-task-divider">

        {{-- Fast Filter Toolbar --}}
        <form method="GET" action="{{ route('user.learning.dashboard') }}" id="myTaskFilterForm" class="d-flex align-items-center flex-wrap gap-2 w-100 my-task-toolbar">
            <input type="hidden" name="learning_tab" value="my-task">
            <input type="hidden" name="my_task_filter" id="myTaskFilterInput" value="{{ $filter }}">
            <input type="hidden" name="my_task_status" id="myTaskStatusInput" value="{{ $filterStatus }}">
            <input type="hidden" name="my_task_year" id="myTaskYearInput" value="{{ $filterYear }}">
            <input type="hidden" name="my_task_month" id="myTaskMonthInput" value="{{ $filterMonth }}">
            @if ($activeGroup ?? null)
                <input type="hidden" name="my_task_parent" value="{{ $activeGroup['key'] }}">
            @endif

            {{-- 1. Dropdown: Status --}}
            <div class="dropdown">
                <button type="button"
                        class="btn my-task-dropdown-btn dropdown-toggle {{ $filterStatus !== 'all' ? 'active' : '' }}"
                        data-bs-toggle="dropdown"
                        data-bs-display="static"
                        aria-expanded="false">
                    Status: <span class="fw-semibold">{{ $statusOptions[$filterStatus] ?? 'All' }}</span>
                </button>
                <div class="dropdown-menu shadow-sm">
                    @foreach ($statusOptions as $val => $lbl)
                        <a href="#" class="dropdown-item my-task-dropdown-item {{ $filterStatus === $val ? 'active' : '' }}"
                           data-param="my_task_status" data-value="{{ $val }}">
                            {{ $lbl }}
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- 3. Dropdown: Year --}}
            <div class="dropdown">
                <button type="button"
                        class="btn my-task-dropdown-btn dropdown-toggle {{ $filterYear !== 'all' ? 'active' : '' }}"
                        data-bs-toggle="dropdown"
                        data-bs-display="static"
                        aria-expanded="false">
                    Year: <span class="fw-semibold">{{ $filterYear === 'all' ? 'All' : $filterYear }}</span>
                </button>
                <div class="dropdown-menu shadow-sm">
                    <a href="#" class="dropdown-item my-task-dropdown-item {{ $filterYear === 'all' ? 'active' : '' }}"
                       data-param="my_task_year" data-value="all">
                        All
                    </a>
                    @foreach ($years as $yr)
                        <a href="#" class="dropdown-item my-task-dropdown-item {{ (string) $filterYear === (string) $yr ? 'active' : '' }}"
                           data-param="my_task_year" data-value="{{ $yr }}">
                            {{ $yr }}
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- 4. Dropdown: Month --}}
            <div class="dropdown">
                <button type="button"
                        class="btn my-task-dropdown-btn dropdown-toggle {{ $filterMonth !== 'all' ? 'active' : '' }}"
                        data-bs-toggle="dropdown"
                        data-bs-display="static"
                        aria-expanded="false">
                    Month: <span class="fw-semibold">{{ $filterMonth === 'all' ? 'All' : ($months[(int) $filterMonth] ?? 'All') }}</span>
                </button>
                <div class="dropdown-menu shadow-sm" style="max-height: 280px; overflow-y: auto;">
                    <a href="#" class="dropdown-item my-task-dropdown-item {{ $filterMonth === 'all' ? 'active' : '' }}"
                       data-param="my_task_month" data-value="all">
                        All
                    </a>
                    @foreach ($months as $num => $monthName)
                        <a href="#" class="dropdown-item my-task-dropdown-item {{ (string) $filterMonth === (string) $num ? 'active' : '' }}"
                           data-param="my_task_month" data-value="{{ $num }}">
                            {{ $monthName }}
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- 5. Search input --}}
            @php
                $isAssessmentTab = in_array($filter, ['asesment', 'assessment'], true) || ($filterType ?? '') === 'assessment';
            @endphp
            <div class="position-relative flex-grow-1 my-task-search-wrapper" style="min-width: 220px;">
                <span class="position-absolute top-50 start-0 translate-middle-y ps-3 text-muted pointer-events-none" style="z-index: 2;">
                    <i class="ti ti-search" style="font-size: 15px;"></i>
                </span>
                <input type="text"
                       name="my_task_q"
                       id="myTaskSearchInput"
                       class="form-control my-task-search-input"
                       placeholder="{{ $isAssessmentTab ? 'Search assessment...' : 'Search tasks...' }}"
                       value="{{ $search ?? '' }}"
                       autocomplete="off">
                @if (!empty($search))
                    <a href="{{ $tabUrl($filter) }}" class="btn btn-link position-absolute top-50 end-0 translate-middle-y pe-2.5 text-muted text-decoration-none my-task-search-clear" title="Clear search" style="z-index: 2;">
                        <i class="ti ti-x"></i>
                    </a>
                @endif
            </div>

            {{-- 6. Blue Search Button --}}
            <button type="submit"
                    class="btn btn-primary my-task-btn-square my-task-btn-search"
                    title="{{ $isAssessmentTab ? 'Search assessment' : 'Search tasks' }}">
                <i class="ti ti-search text-white fs-3"></i>
            </button>

            {{-- 7. Reset Button --}}
            <a href="{{ $resetUrl }}"
               class="btn btn-white my-task-btn-square my-task-btn-reset"
               title="Reset all filters">
                <i class="ti ti-refresh fs-3 text-secondary"></i>
            </a>
        </form>

        {{-- Active Filters indicator (if any filter or search active) --}}
        @php
            $hasActiveFilters = !empty($search) || $filterStatus !== 'all' || $filterYear !== 'all' || $filterMonth !== 'all';
        @endphp
        @if ($hasActiveFilters)
            <div class="d-flex align-items-center flex-wrap gap-2 mt-2.5 pt-2.5 border-top">
                <span class="text-muted small" style="font-size: 11.5px;">Active Filter:</span>
                @if (!empty($search))
                    <span class="badge bg-light text-dark border px-2.5 py-1 rounded-pill d-inline-flex align-items-center gap-1 shadow-none" style="font-size: 11.5px;">
                        <i class="ti ti-search text-muted"></i>
                        <span>"{{ $search }}"</span>
                        <a href="{{ $tabUrl($filter) }}" class="text-muted ms-1 text-decoration-none" title="Remove search filter"><i class="ti ti-x"></i></a>
                    </span>
                @endif
                @if ($filterStatus !== 'all')
                    <span class="badge bg-light text-dark border px-2.5 py-1 rounded-pill d-inline-flex align-items-center gap-1 shadow-none" style="font-size: 11.5px;">
                        <span>Status: {{ $statusOptions[$filterStatus] ?? $filterStatus }}</span>
                    </span>
                @endif
                @if ($filterYear !== 'all')
                    <span class="badge bg-light text-dark border px-2.5 py-1 rounded-pill d-inline-flex align-items-center gap-1 shadow-none" style="font-size: 11.5px;">
                        <span>Year: {{ $filterYear }}</span>
                    </span>
                @endif
                @if ($filterMonth !== 'all')
                    <span class="badge bg-light text-dark border px-2.5 py-1 rounded-pill d-inline-flex align-items-center gap-1 shadow-none" style="font-size: 11.5px;">
                        <span>Month: {{ $months[(int)$filterMonth] ?? $filterMonth }}</span>
                    </span>
                @endif
                <a href="{{ $resetUrl }}" class="text-primary small text-decoration-none ms-1 fw-semibold" style="font-size: 11.5px;">
                    Reset all
                </a>
            </div>
        @endif
    </div>
</div>

@if ($activeGroup ?? null)
    {{-- Detail: tasks of one parent --}}
    <div class="mb-3">
        <a href="{{ $backUrl }}" class="text-primary text-decoration-none fw-semibold d-inline-flex align-items-center gap-1">
            <i class="ti ti-arrow-left"></i> Back
        </a>
    </div>
    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge {{ $parentTypeBadge[$activeGroup['parent_type']] ?? 'bg-secondary-lt text-secondary' }}">
                        {{ $parentTypeLabel[$activeGroup['parent_type']] ?? $activeGroup['parent_type'] }}
                    </span>
                    @if ($activeGroup['parent_url'])
                        <a href="{{ $activeGroup['parent_url'] }}" class="fw-semibold text-body text-decoration-none">{{ $activeGroup['parent_name'] }}</a>
                    @else
                        <span class="fw-semibold">{{ $activeGroup['parent_name'] }}</span>
                    @endif
                    @if ($activeGroup['has_overdue'])
                        <span class="badge bg-danger-lt text-danger">Overdue</span>
                    @endif
                </div>
                <span class="small text-muted">{{ $activeGroup['completed'] }}/{{ $activeGroup['total'] }} completed</span>
            </div>
            <div class="progress mt-2" style="height:6px">
                <div class="progress-bar bg-success" role="progressbar"
                     style="width: {{ $activeGroup['total'] ? round($activeGroup['completed'] / $activeGroup['total'] * 100) : 0 }}%"></div>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm rounded-4">
        <div class="table-responsive">
            <table class="table table-vcenter mb-0">
                <thead>
                    <tr>
                        <th class="w-1">NO</th>
                        <th>TASK</th>
                        <th>TYPE</th>
                        <th>DEADLINE</th>
                        <th class="text-center">SCORE</th>
                        <th>STATUS</th>
                        <th class="text-end">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($activeGroup['tasks'] as $i => $task)
                        @php
                            $tStatus = $task->userStatusFor(auth()->user());
                            $tSubmission = $task->submissionFor(auth()->user());
                        @endphp
                        <tr>
                            <td class="text-muted">{{ $i + 1 }}</td>
                            <td>
                                <span class="fw-semibold">{{ $task->title }}</span>
                                @if ($task->description)
                                    <div class="text-dark small" style="color: #000000;">{{ Str::limit($task->description, 80) }}</div>
                                @endif
                            </td>
                            <td><span class="badge bg-primary-lt text-primary">{{ $task->typeLabel() }}</span></td>
                            <td class="text-nowrap">
                                @if ($task->deadline)
                                    @php
                                        $tdl = $task->deadline;
                                        $tdlOverdue = $tStatus === 'overdue' || ($tdl->isPast() && $tStatus !== 'completed');
                                        $tdlAction = in_array($tStatus, ['overdue', 'not_started', 'resubmit', 'in_progress'], true);
                                        $hoursUntil = now()->diffInHours($tdl, false);
                                        $isToday = $tdlAction && ! $tdlOverdue && $hoursUntil <= 24 && $hoursUntil >= 0;
                                        $isUrgent = $tdlAction && ! $tdlOverdue && $hoursUntil <= 48 && $hoursUntil > 24;
                                        $tdlSoon = $tdlAction && ! $tdlOverdue && $tdl->diffInDays(now()) <= 3;
                                    @endphp
                                    @if ($tdlOverdue)
                                        <span class="badge bg-danger"><i class="ti ti-alert-circle me-1"></i>{{ $tdl->format('d M Y H:i') }}</span>
                                    @elseif ($isToday)
                                        <span class="badge bg-danger text-white"><i class="ti ti-flame me-1"></i>Today ({{ $tdl->format('H:i') }})</span>
                                    @elseif ($isUrgent)
                                        <span class="badge bg-warning text-dark"><i class="ti ti-clock-exclamation me-1"></i>Due Soon ({{ $tdl->format('d M') }})</span>
                                    @elseif ($tdlSoon)
                                        <span class="badge bg-warning-lt text-warning"><i class="ti ti-clock me-1"></i>{{ $tdl->format('d M Y H:i') }}</span>
                                    @else
                                        <span class="text-muted">{{ $tdl->format('d M Y H:i') }}</span>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($tSubmission && ! is_null($tSubmission->score))
                                    <span class="badge bg-success">{{ $tSubmission->score }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td><span class="badge {{ $statusBadges[$displayStatus($tStatus)] }}">{{ $statusLabels[$displayStatus($tStatus)] }}</span></td>
                            <td class="text-end">
                                @if ($tStatus === 'resubmit')
                                    <button type="button" class="btn btn-sm btn-warning text-dark fw-semibold shadow-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#taskModal-{{ $task->id }}">
                                        <i class="ti ti-refresh me-1"></i> Revise
                                    </button>
                                @else
                                    <button type="button" class="btn btn-sm {{ in_array($tStatus, ['completed', 'submitted']) ? 'btn-white' : 'btn-success' }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#taskModal-{{ $task->id }}">
                                        <i class="ti {{ in_array($tStatus, ['completed', 'submitted']) ? 'ti-eye' : 'ti-pencil' }} me-1"></i>
                                        {{ in_array($tStatus, ['completed', 'submitted']) ? 'View' : 'Start' }}
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@else


    {{-- Parent list / Assessment table --}}
    @if ($groups->isNotEmpty())
        @if ($filter === 'asesment' || $filterType === 'assessment')
            @include('user.learning.dashboard-partials.my-task-assessment-table', ['assessmentGroups' => $groups])
        @else
            @include('user.learning.dashboard-partials.my-task-parent-table', ['tableGroups' => $groups])
        @endif
    @else
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 text-center text-muted">
                <i class="ti ti-list-check fs-1 d-block mb-2"></i>
                @if ($filter === 'all' && ($search ?? '') === '')
                    No tasks yet. Open a playlist or content you follow first — tasks will appear here after you open it.
                @elseif ($filter === 'asesment')
                    No assessments available at this time.
                @else
                    No tasks match this filter.
                @endif
            </div>
        </div>
    @endif
@endif

{{-- ── Task & Assessment Pop Up Modals (Focused Workspace) ──────────────────── --}}
@php
    $allModalTasks = collect();
    if (isset($groups) && $groups->isNotEmpty()) {
        $allModalTasks = $groups->pluck('tasks')->flatten()->unique('id');
    }
    if (isset($activeGroup) && ! empty($activeGroup['tasks'])) {
        $allModalTasks = $allModalTasks->concat($activeGroup['tasks'])->unique('id');
    }
@endphp

@foreach ($allModalTasks as $task)
    @php
        $modalStatus = $task->userStatusFor(auth()->user());
        $isAssessment = $task->is_assessment ?? false;
    @endphp

    @if ($isAssessment)
        {{-- Assessment Pop Up Modal --}}
        <div class="modal modal-blur fade task-popup-modal"
             id="taskModal-{{ $task->id }}"
             tabindex="-1"
             role="dialog"
             aria-hidden="true"
             data-bs-backdrop="static">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
                <div class="modal-content rounded-4 border-0 shadow-lg">
                    <div class="modal-header bg-light py-2.5 px-4 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2 min-w-0 me-auto">
                            <span class="badge bg-purple-lt text-purple p-1.5 rounded-2">
                                <i class="ti ti-clipboard-check fs-2"></i>
                            </span>
                            <div class="min-w-0">
                                <h5 class="modal-title fw-bold text-dark mb-0 text-truncate" style="max-width: 380px;">{{ $task->title }}</h5>
                                <div class="text-muted small" style="font-size: 11px;">Assessment View</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-shrink-0 ms-auto">
                            {{-- Zoom in / Zoom out toolbar --}}
                            <div class="btn-group btn-group-sm shadow-none border rounded-2 bg-white" role="group" aria-label="Zoom controls">
                                <button type="button" class="btn btn-sm btn-ghost-secondary px-2 py-1 btn-modal-zoom-out" data-target="taskModalZoom-{{ $task->id }}" title="Zoom Out (-)">
                                    <i class="ti ti-zoom-out" style="font-size: 13px;"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-ghost-secondary px-2 py-1 fw-bold text-dark btn-modal-zoom-reset" data-target="taskModalZoom-{{ $task->id }}" title="Reset Zoom (100%)" style="min-width: 44px; font-size: 11px;">
                                    <span id="zoomLevel-{{ $task->id }}">100%</span>
                                </button>
                                <button type="button" class="btn btn-sm btn-ghost-secondary px-2 py-1 btn-modal-zoom-in" data-target="taskModalZoom-{{ $task->id }}" title="Zoom In (+)">
                                    <i class="ti ti-zoom-in" style="font-size: 13px;"></i>
                                </button>
                            </div>

                            {{-- Clean Close Button (Tombol Silang) --}}
                            <button type="button"
                                    class="btn btn-sm btn-icon btn-ghost-secondary rounded-circle ms-1"
                                    data-bs-dismiss="modal"
                                    aria-label="Close"
                                    title="Close"
                                    style="width: 32px; height: 32px; border: none; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="ti ti-x text-muted" style="font-size: 20px; font-weight: bold; line-height: 1;"></i>
                            </button>
                        </div>
                    </div>
                    <div class="modal-body p-4 bg-light-subtle overflow-auto">
                        <div id="taskModalZoom-{{ $task->id }}" class="task-modal-zoom-target" style="transform-origin: top center; transition: transform 0.15s ease-out; width: 100%;">
                            <div class="card border-0 shadow-sm rounded-4 mb-0">
                            <div class="card-body p-4">
                                {{-- Top Badges Row --}}
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="badge bg-purple-lt text-purple px-2.5 py-1 fs-6">
                                            <i class="ti ti-clipboard-check me-1"></i>Assessment
                                        </span>
                                        @if ($task->parentTitle())
                                            <span class="badge bg-secondary-lt text-secondary px-2.5 py-1 fs-6">
                                                <i class="ti ti-bookmark me-1"></i>{{ $task->parentTitle() }}
                                            </span>
                                        @endif
                                    </div>
                                    <div>
                                        @if ($task->is_completed)
                                            @if ($task->is_passed)
                                                <span class="badge bg-success-lt text-success px-2.5 py-1 fs-6">Completed (Passed)</span>
                                            @else
                                                <span class="badge bg-danger-lt text-danger px-2.5 py-1 fs-6">Failed</span>
                                            @endif
                                        @elseif ($task->is_expired)
                                            <span class="badge bg-danger-lt text-danger px-2.5 py-1 fs-6">Overdue</span>
                                        @else
                                            <span class="badge bg-primary-lt text-primary px-2.5 py-1 fs-6">In Progress</span>
                                        @endif
                                    </div>
                                </div>

                                <h2 class="fw-bold text-dark mb-2">{{ $task->title }}</h2>
                                <div class="text-dark mb-4" style="line-height: 1.6; color: #000000;">
                                    {{ $task->description }}
                                </div>

                                {{-- Assessment Metadata Grid --}}
                                <div class="row g-3 mb-4">
                                    <div class="col-6 col-md-3">
                                        <div class="p-3 bg-white border rounded-3 text-center h-100 shadow-none">
                                            <div class="text-muted small mb-1"><i class="ti ti-award me-1"></i>Passing Grade</div>
                                            <div class="fs-3 fw-bold text-dark">{{ $task->passing_grade ?? '-' }}%</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="p-3 bg-white border rounded-3 text-center h-100 shadow-none">
                                            <div class="text-muted small mb-1"><i class="ti ti-clock me-1"></i>Duration</div>
                                            <div class="fs-3 fw-bold text-dark">{{ $task->time_limit ? $task->time_limit . ' Minutes' : 'Flexible' }}</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="p-3 bg-white border rounded-3 text-center h-100 shadow-none">
                                            <div class="text-muted small mb-1"><i class="ti ti-calendar me-1"></i>Deadline</div>
                                            <div class="fs-4 fw-bold text-dark">{{ $task->deadline ? $task->deadline->format('d M Y') : 'No Deadline' }}</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="p-3 bg-white border rounded-3 text-center h-100 shadow-none">
                                            <div class="text-muted small mb-1"><i class="ti ti-star me-1"></i>Your Score</div>
                                            <div class="fs-3 fw-bold {{ !is_null($task->score) ? ($task->is_passed ? 'text-success' : 'text-danger') : 'text-muted' }}">
                                                {{ !is_null($task->score) ? $task->score : '-' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Status Alert Box --}}
                                @if ($task->is_completed)
                                    <div class="alert {{ $task->is_passed ? 'alert-success' : 'alert-danger' }} rounded-3 d-flex align-items-center justify-content-between mb-4">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="ti {{ $task->is_passed ? 'ti-circle-check' : 'ti-alert-circle' }} fs-2"></i>
                                            <div>
                                                <strong>{{ $task->is_passed ? 'Congratulations, you have passed this assessment!' : 'Your score did not meet the passing grade.' }}</strong>
                                                <div class="small">Score achieved: <strong>{{ $task->score }}</strong> (Passing Grade: {{ $task->passing_grade }}%)</div>
                                            </div>
                                        </div>
                                    </div>
                                @elseif ($task->is_expired)
                                    <div class="alert alert-danger rounded-3 d-flex align-items-center gap-2 mb-4">
                                        <i class="ti ti-alert-triangle fs-2"></i>
                                        <div>
                                            <strong>The assessment access period has expired.</strong>
                                            <div class="small">Please contact your administrator or instructor if you need an access extension.</div>
                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-info rounded-3 d-flex align-items-center gap-2 mb-4">
                                        <i class="ti ti-info-circle fs-2"></i>
                                        <div>
                                            <strong>Instructions:</strong>
                                            <div class="small">Please ensure a stable internet connection before starting. Answers will be saved automatically as you progress.</div>
                                        </div>
                                    </div>
                                @endif

                                {{-- Action Buttons --}}
                                <div class="d-flex align-items-center justify-content-end gap-2 pt-3 border-top">
                                    <button type="button" class="btn btn-white" data-bs-dismiss="modal">Close</button>
                                    @if ($task->is_completed)
                                        @if (Route::has('assesment.user.result') && !empty($task->slug))
                                            <a href="{{ route('assesment.user.result', $task->slug) }}" class="btn btn-outline-primary">
                                                <i class="ti ti-chart-bar me-1"></i> View Results
                                            </a>
                                        @endif
                                        @if (!$task->is_passed && !empty($task->slug) && Route::has('assesment.user.view'))
                                            <a href="{{ route('assesment.user.view', $task->slug) }}" class="btn btn-warning text-dark fw-semibold">
                                                <i class="ti ti-refresh me-1"></i> Take Remedial
                                            </a>
                                        @endif
                                    @elseif ($task->is_expired)
                                        <button type="button" class="btn btn-secondary" disabled>
                                            <i class="ti ti-ban me-1"></i> Assessment Expired
                                        </button>
                                    @else
                                        @if (!empty($task->slug) && Route::has('assesment.user.view'))
                                            <a href="{{ route('assesment.user.view', $task->slug) }}" class="btn btn-primary px-4 fw-semibold">
                                                <i class="ti ti-pencil me-1"></i> Start Assessment
                                            </a>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    @else
        {{-- Task Pop Up Modal --}}
        <div class="modal modal-blur fade task-popup-modal"
             id="taskModal-{{ $task->id }}"
             tabindex="-1"
             role="dialog"
             aria-hidden="true"
             data-bs-backdrop="static">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
                <div class="modal-content rounded-4 border-0 shadow-lg">
                    <div class="modal-header bg-light py-2.5 px-4 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2 min-w-0 me-auto">
                            <span class="badge bg-primary-lt text-primary p-1.5 rounded-2">
                                <i class="ti ti-file-text fs-2"></i>
                            </span>
                            <div class="min-w-0">
                                <h5 class="modal-title fw-bold text-dark mb-0 text-truncate" style="max-width: 380px;">{{ $task->title }}</h5>
                                <div class="text-muted small" style="font-size: 11px;">{{ $task->typeLabel() }} View</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-shrink-0 ms-auto">
                            {{-- Zoom in / Zoom out toolbar --}}
                            <div class="btn-group btn-group-sm shadow-none border rounded-2 bg-white" role="group" aria-label="Zoom controls">
                                <button type="button" class="btn btn-sm btn-ghost-secondary px-2 py-1 btn-modal-zoom-out" data-target="taskModalZoom-{{ $task->id }}" title="Zoom Out (-)">
                                    <i class="ti ti-zoom-out" style="font-size: 13px;"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-ghost-secondary px-2 py-1 fw-bold text-dark btn-modal-zoom-reset" data-target="taskModalZoom-{{ $task->id }}" title="Reset Zoom (100%)" style="min-width: 44px; font-size: 11px;">
                                    <span id="zoomLevel-{{ $task->id }}">100%</span>
                                </button>
                                <button type="button" class="btn btn-sm btn-ghost-secondary px-2 py-1 btn-modal-zoom-in" data-target="taskModalZoom-{{ $task->id }}" title="Zoom In (+)">
                                    <i class="ti ti-zoom-in" style="font-size: 13px;"></i>
                                </button>
                            </div>

                            {{-- Clean Close Button (Tombol Silang) --}}
                            <button type="button"
                                    class="btn btn-sm btn-icon btn-ghost-secondary rounded-circle ms-1"
                                    data-bs-dismiss="modal"
                                    aria-label="Close"
                                    title="Close"
                                    style="width: 32px; height: 32px; border: none; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="ti ti-x text-muted" style="font-size: 20px; font-weight: bold; line-height: 1;"></i>
                            </button>
                        </div>
                    </div>
                    <div class="modal-body p-4 bg-light-subtle overflow-auto">
                        <div id="taskModalZoom-{{ $task->id }}" class="task-modal-zoom-target" style="transform-origin: top center; transition: transform 0.15s ease-out; width: 100%;">
                            @include('user.learning.dashboard-partials.my-task-card', ['task' => $task, 'inModal' => true])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach



<script>
(function () {
    // Delegated click handler for Task Modal Zoom In / Zoom Out / Reset
    var modalZoomLevels = {};
    document.addEventListener('click', function (e) {
        var zoomInBtn = e.target.closest('.btn-modal-zoom-in');
        var zoomOutBtn = e.target.closest('.btn-modal-zoom-out');
        var zoomResetBtn = e.target.closest('.btn-modal-zoom-reset');

        var btn = zoomInBtn || zoomOutBtn || zoomResetBtn;
        if (!btn) return;
        e.preventDefault();

        var targetId = btn.getAttribute('data-target');
        if (!targetId) return;

        var targetEl = document.getElementById(targetId);
        if (!targetEl) return;

        var current = modalZoomLevels[targetId] || 1.0;
        if (zoomInBtn) {
            current = Math.min(2.5, Math.round((current + 0.15) * 100) / 100);
        } else if (zoomOutBtn) {
            current = Math.max(0.5, Math.round((current - 0.15) * 100) / 100);
        } else if (zoomResetBtn) {
            current = 1.0;
        }

        modalZoomLevels[targetId] = current;
        targetEl.style.transform = 'scale(' + current + ')';

        var levelBadge = document.querySelector('#' + targetId.replace('taskModalZoom-', 'zoomLevel-'));
        if (levelBadge) {
            levelBadge.textContent = Math.round(current * 100) + '%';
        }
    });

    // 1. If validation error occurred, reopen the modal for the active task
    @if ($errors->any() && old('active_task_id'))
        var errModalEl = document.getElementById('taskModal-{{ old('active_task_id') }}');
        if (errModalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            setTimeout(function () {
                bootstrap.Modal.getOrCreateInstance(errModalEl).show();
            }, 150);
        }
    @endif

    // Helper: Hide all open dropdowns in parentTableAccordion (except an optional excluded one)
    function closeOtherDropdowns(excludeDropdownEl) {
        var accordion = document.getElementById('parentTableAccordion');
        if (!accordion) return;

        var openMenus = accordion.querySelectorAll('.dropdown-menu.show');
        openMenus.forEach(function (menu) {
            var dd = menu.closest('.dropdown');
            if (dd && dd !== excludeDropdownEl) {
                var btn = dd.querySelector('[data-bs-toggle="dropdown"]');
                if (btn && typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
                    var inst = bootstrap.Dropdown.getInstance(btn);
                    if (inst) {
                        inst.hide();
                    }
                }
            }
        });
    }

    // Helper: Instantly close other collapsible rows with ZERO delay (no animation lag)
    function closeOtherCollapses(excludeCollapseEl) {
        var accordion = document.getElementById('parentTableAccordion');
        if (!accordion) return;

        // Remove is-active from any other course-parent-row
        var activeParentRows = accordion.querySelectorAll('.course-parent-row.is-active');
        activeParentRows.forEach(function (pRow) {
            var tgt = pRow.getAttribute('data-collapse-target');
            var tgtEl = tgt ? document.querySelector(tgt) : null;
            if (tgtEl !== excludeCollapseEl) {
                pRow.classList.remove('is-active');
            }
        });

        var openRows = accordion.querySelectorAll('.task-collapse-row.show, .task-collapse-row.collapsing');
        openRows.forEach(function (row) {
            if (row !== excludeCollapseEl) {
                if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
                    var cInst = bootstrap.Collapse.getInstance(row);
                    if (cInst) {
                        try {
                            cInst.dispose();
                        } catch (err) {}
                    }
                }
                row.classList.remove('show', 'collapsing');
                row.style.height = '';

                // Instantly flip chevron down and update aria-expanded
                var rowId = row.id;
                var triggers = accordion.querySelectorAll('[data-bs-target="#' + rowId + '"]');
                triggers.forEach(function (btn) {
                    btn.setAttribute('aria-expanded', 'false');
                    var icon = btn.querySelector('.ti-chevron-up');
                    if (icon) {
                        icon.classList.remove('ti-chevron-up');
                        icon.classList.add('ti-chevron-down');
                    }
                });
            }
        });
    }

    // Capture phase event listener: Intercept click on course row toggle and close other rows INSTANTLY (0ms delay)
    document.addEventListener('click', function (e) {
        var toggleEl = e.target.closest('#parentTableAccordion .course-col-trigger, #parentTableAccordion .btn-task-collapse-toggle');
        if (!toggleEl) return;

        var targetSelector = toggleEl.getAttribute('data-bs-target');
        if (!targetSelector) return;

        var targetRow = document.querySelector(targetSelector);
        var parentRow = toggleEl.closest('.course-parent-row');

        // If the target course is about to be opened, close other courses instantly right away
        if (targetRow && !targetRow.classList.contains('show')) {
            closeOtherCollapses(targetRow);
            closeOtherDropdowns(null);
            if (parentRow) {
                parentRow.classList.add('is-active');
            }
        } else if (targetRow && targetRow.classList.contains('show')) {
            // Target course is being closed
            if (parentRow) {
                parentRow.classList.remove('is-active');
            }
        }
    }, true);

    // 2. Dropdown opening handler: opening dropdown on Course B automatically closes Course A's dropdown
    if (typeof $ !== 'undefined') {
        $(document).off('show.bs.dropdown.mytask').on('show.bs.dropdown.mytask', '#parentTableAccordion .dropdown', function () {
            closeOtherDropdowns(this);

            // Also collapse other courses' inline accordion rows so user is focused on the clicked course
            var currentRow = this.closest('tr');
            var currentTarget = currentRow ? $(currentRow).find('[data-bs-toggle="collapse"]').data('bs-target') : null;
            var currentCollapseEl = currentTarget ? document.querySelector(currentTarget) : null;
            closeOtherCollapses(currentCollapseEl);
        });

        // 3. Collapsible rows (Accordion behavior): opening Course B's row closes Course A's row & all dropdowns
        $(document).off('show.bs.collapse.mytask').on('show.bs.collapse.mytask', '#parentTableAccordion .task-collapse-row', function () {
            closeOtherCollapses(this);
            closeOtherDropdowns(null);

            var targetId = '#' + this.id;
            var parentRow = $('#parentTableAccordion .course-parent-row[data-collapse-target="' + targetId + '"]');
            if (parentRow.length) {
                parentRow.addClass('is-active');
            }

            // Update chevron icons: point up for current, down for others
            var currentId = this.id;
            $('#parentTableAccordion .btn-task-collapse-toggle, #parentTableAccordion .course-col-trigger').each(function () {
                var target = $(this).data('bs-target');
                var icon = $(this).find('.ti-chevron-down, .ti-chevron-up');
                if (target === '#' + currentId) {
                    icon.removeClass('ti-chevron-down').addClass('ti-chevron-up');
                } else {
                    icon.removeClass('ti-chevron-up').addClass('ti-chevron-down');
                }
            });
        });

        $(document).off('hide.bs.collapse.mytask').on('hide.bs.collapse.mytask', '#parentTableAccordion .task-collapse-row', function () {
            var targetId = '#' + this.id;
            var parentRow = $('#parentTableAccordion .course-parent-row[data-collapse-target="' + targetId + '"]');
            if (parentRow.length) {
                parentRow.removeClass('is-active');
            }
            $('#parentTableAccordion [data-bs-target="' + targetId + '"]')
                .find('.ti-chevron-up')
                .removeClass('ti-chevron-up')
                .addClass('ti-chevron-down');
        });

        // 4. When user clicks any task to open modal, close any open dropdowns immediately
        $(document).off('click.mytaskmodal').on('click.mytaskmodal', '[data-bs-toggle="modal"][data-bs-target^="#taskModal-"], .btn-open-task-modal', function () {
            closeOtherDropdowns(null);
        });

        // 5. When any task modal starts to show, ensure all dropdowns are dismissed cleanly
        $(document).off('show.bs.modal.mytaskmodal').on('show.bs.modal.mytaskmodal', '.task-popup-modal', function () {
            closeOtherDropdowns(null);
        });

        // 6. Fast filter dropdown item selection
        $(document).off('click.myTaskDropdown').on('click.myTaskDropdown', '.my-task-dropdown-item', function (e) {
            e.preventDefault();
            var param = $(this).data('param');
            var val = String($(this).data('value'));
            if (param === 'my_task_type') $('#myTaskTypeInput').val(val);
            if (param === 'my_task_status') $('#myTaskStatusInput').val(val);
            if (param === 'my_task_year') $('#myTaskYearInput').val(val);
            if (param === 'my_task_month') $('#myTaskMonthInput').val(val);
            $('#myTaskFilterForm').submit();
        });

        // 7. Active filter pill removal (x)
        $(document).off('click.myTaskRemovePill').on('click.myTaskRemovePill', '.remove-filter-pill', function (e) {
            e.preventDefault();
            var param = $(this).data('param');
            if (param === 'my_task_type') $('#myTaskTypeInput').val('all');
            if (param === 'my_task_status') $('#myTaskStatusInput').val('all');
            if (param === 'my_task_year') $('#myTaskYearInput').val('all');
            if (param === 'my_task_month') $('#myTaskMonthInput').val('all');
            $('#myTaskFilterForm').submit();
        });
    }
})();
</script>

<style>
/* ── My Task Filter UI (Matching Reference Screenshot) ───────────────────────── */
.my-task-filter-card {
    background: #ffffff;
    border: 1px solid #f1f5f9;
    overflow: visible !important;
    position: relative !important;
    z-index: 40 !important;
}

.my-task-filter-card .card-body {
    overflow: visible !important;
    position: relative;
}

#learning-tab-content > .my-task-filter-card {
    overflow: visible !important;
}

.my-task-filter-title {
    color: #0f172a;
    font-size: 1.25rem;
    font-weight: 700;
    line-height: 1.2;
}

.my-task-filter-subtitle {
    color: #64748b;
    font-size: 0.875rem;
}

/* Category Pills */
.my-task-pills-row {
    margin-top: 0.75rem;
}

.my-task-category-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.45rem 1.25rem;
    border-radius: 50rem;
    font-size: 0.8125rem;
    font-weight: 500;
    line-height: 1.2;
    text-decoration: none;
    white-space: nowrap;
    background-color: #f1f5f9;
    color: #334155;
    border: none;
    transition: all 0.15s ease-in-out;
}

.my-task-category-pill:hover {
    background-color: #e2e8f0;
    color: #0f172a;
    text-decoration: none;
}

/* Active State: Soft sky blue with bright blue text (1:1 with reference) */
.my-task-category-pill.active {
    background-color: #e0f2fe !important;
    color: #0284c7 !important;
    font-weight: 600;
}

/* Divider */
.my-task-divider {
    border-top: 1px solid #e2e8f0;
    opacity: 0.7;
    margin: 1rem 0;
}

/* Toolbar & Dropdowns */
.my-task-toolbar {
    gap: 0.5rem;
    overflow: visible !important;
    position: relative;
}

.my-task-toolbar .dropdown {
    position: relative;
}

.my-task-toolbar .dropdown-menu {
    z-index: 1060 !important;
    min-width: 175px;
    border-radius: 10px !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.15), 0 8px 10px -6px rgba(15, 23, 42, 0.08) !important;
    padding: 0.35rem 0;
}

.my-task-toolbar .dropdown-item {
    font-size: 0.8125rem;
    padding: 0.45rem 1rem;
    color: #334155;
    transition: all 0.15s ease-in-out;
}

.my-task-toolbar .dropdown-item:hover {
    background-color: #f1f5f9;
    color: #0f172a;
}

.my-task-toolbar .dropdown-item.active,
.my-task-toolbar .dropdown-item:active {
    background-color: #e0f2fe !important;
    color: #0284c7 !important;
    font-weight: 600;
}

.my-task-dropdown-btn {
    height: 38px;
    padding: 0.45rem 0.85rem;
    font-size: 0.8125rem;
    font-weight: 400;
    color: #334155;
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px !important;
    display: inline-flex;
    align-items: center;
    justify-content: space-between;
    transition: all 0.15s ease-in-out;
    box-shadow: none !important;
}

.my-task-dropdown-btn:hover {
    background-color: #f8fafc;
    border-color: #cbd5e1;
    color: #0f172a;
}

.my-task-dropdown-btn:focus,
.my-task-dropdown-btn.show {
    border-color: #0284c7;
    box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.15) !important;
}

.my-task-dropdown-btn.active {
    border-color: #0284c7;
    color: #0284c7;
    background-color: #f0f9ff;
}

.my-task-dropdown-btn::after {
    margin-left: 0.55rem;
    vertical-align: 0.15em;
}

/* Search input */
.my-task-search-input {
    height: 38px;
    border-radius: 8px !important;
    padding-left: 2.25rem;
    padding-right: 2rem;
    font-size: 0.8125rem;
    border: 1px solid #e2e8f0;
    background-color: #ffffff;
    color: #334155;
    transition: all 0.15s ease-in-out;
}

.my-task-search-input:focus {
    background-color: #ffffff;
    border-color: #0284c7;
    box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.15);
}

.my-task-search-input::placeholder {
    color: #94a3b8;
}

/* Square Action Buttons (38px x 38px, rounded 8px) */
.my-task-btn-square {
    width: 38px;
    height: 38px;
    padding: 0;
    border-radius: 8px !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: none !important;
    transition: all 0.15s ease-in-out;
}

.my-task-btn-search {
    background-color: #0054a6 !important;
    border-color: #0054a6 !important;
}

.my-task-btn-search:hover {
    background-color: #004085 !important;
    border-color: #004085 !important;
}

.my-task-btn-filter,
.my-task-btn-reset {
    background-color: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    color: #334155 !important;
}

.my-task-btn-filter:hover,
.my-task-btn-reset:hover {
    background-color: #f8fafc !important;
    border-color: #cbd5e1 !important;
    color: #0f172a !important;
}

/* Dark mode adjustments */
[data-bs-theme="dark"] .my-task-filter-card,
body.dark-mode .my-task-filter-card {
    background: #1e293b;
    border-color: #334155;
}

[data-bs-theme="dark"] .my-task-filter-title,
body.dark-mode .my-task-filter-title {
    color: #f8fafc !important;
}

[data-bs-theme="dark"] .my-task-filter-subtitle,
body.dark-mode .my-task-filter-subtitle {
    color: #94a3b8 !important;
}

[data-bs-theme="dark"] .my-task-category-pill,
body.dark-mode .my-task-category-pill {
    background-color: #334155;
    color: #cbd5e1;
}

[data-bs-theme="dark"] .my-task-category-pill:hover,
body.dark-mode .my-task-category-pill:hover {
    background-color: #475569;
    color: #ffffff;
}

[data-bs-theme="dark"] .my-task-category-pill.active,
body.dark-mode .my-task-category-pill.active {
    background-color: rgba(2, 132, 199, 0.25) !important;
    color: #38bdf8 !important;
}

[data-bs-theme="dark"] .my-task-divider,
body.dark-mode .my-task-divider {
    border-color: #334155;
}

[data-bs-theme="dark"] .my-task-dropdown-btn,
body.dark-mode .my-task-dropdown-btn {
    background-color: #1e293b;
    border-color: #334155;
    color: #cbd5e1;
}

[data-bs-theme="dark"] .my-task-search-input,
body.dark-mode .my-task-search-input {
    background-color: #1e293b;
    border-color: #334155;
    color: #f8fafc;
}

[data-bs-theme="dark"] .my-task-btn-reset,
body.dark-mode .my-task-btn-reset {
    background-color: #1e293b !important;
    border-color: #334155 !important;
    color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .my-task-toolbar .dropdown-menu,
body.dark-mode .my-task-toolbar .dropdown-menu {
    background-color: #1e293b !important;
    border-color: #334155 !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5) !important;
}
[data-bs-theme="dark"] .my-task-toolbar .dropdown-item,
body.dark-mode .my-task-toolbar .dropdown-item {
    color: #cbd5e1 !important;
}
[data-bs-theme="dark"] .my-task-toolbar .dropdown-item:hover,
body.dark-mode .my-task-toolbar .dropdown-item:hover {
    background-color: #334155 !important;
    color: #ffffff !important;
}
[data-bs-theme="dark"] .my-task-toolbar .dropdown-item.active,
body.dark-mode .my-task-toolbar .dropdown-item.active {
    background-color: rgba(2, 132, 199, 0.3) !important;
    color: #38bdf8 !important;
}

#parentTableAccordion .task-collapse-row,
#parentTableAccordion .task-collapse-row.collapsing {
    transition: none !important;
}

/* Active Course Highlight */
.course-parent-row.is-active > td {
    background-color: rgba(32, 107, 196, 0.05) !important;
}
.course-parent-row.is-active.is-assessment-group > td {
    background-color: rgba(121, 82, 179, 0.05) !important;
}
.course-parent-row.is-active > td:first-child,
.course-parent-row.is-active + .task-collapse-row > td:first-child {
    box-shadow: inset 4px 0 0 0 var(--tblr-primary, #206bc4) !important;
}
.course-parent-row.is-active.is-assessment-group > td:first-child,
.course-parent-row.is-active.is-assessment-group + .task-collapse-row > td:first-child {
    box-shadow: inset 4px 0 0 0 #7952b3 !important;
}
.course-parent-row.is-active .course-title-text {
    color: var(--tblr-primary, #206bc4) !important;
}
.course-parent-row.is-active.is-assessment-group .course-title-text {
    color: #7952b3 !important;
}
.course-parent-row.is-active .active-course-badge {
    display: inline-flex !important;
}
.course-parent-row.is-active .btn-task-collapse-toggle {
    background-color: var(--tblr-primary, #206bc4) !important;
    border-color: var(--tblr-primary, #206bc4) !important;
    color: #ffffff !important;
}
.course-parent-row.is-active.is-assessment-group .btn-task-collapse-toggle {
    background-color: #7952b3 !important;
    border-color: #7952b3 !important;
    color: #ffffff !important;
}

/* Dark mode support */
[data-bs-theme="dark"] .course-parent-row.is-active > td,
body.dark-mode .course-parent-row.is-active > td {
    background-color: rgba(56, 189, 248, 0.12) !important;
}
[data-bs-theme="dark"] .course-parent-row.is-active.is-assessment-group > td,
body.dark-mode .course-parent-row.is-active.is-assessment-group > td {
    background-color: rgba(168, 85, 247, 0.12) !important;
}
[data-bs-theme="dark"] .course-parent-row.is-active > td:first-child,
[data-bs-theme="dark"] .course-parent-row.is-active + .task-collapse-row > td:first-child,
body.dark-mode .course-parent-row.is-active > td:first-child,
body.dark-mode .course-parent-row.is-active + .task-collapse-row > td:first-child {
    box-shadow: inset 4px 0 0 0 #38bdf8 !important;
}
[data-bs-theme="dark"] .course-parent-row.is-active.is-assessment-group > td:first-child,
[data-bs-theme="dark"] .course-parent-row.is-active.is-assessment-group + .task-collapse-row > td:first-child,
body.dark-mode .course-parent-row.is-active.is-assessment-group > td:first-child,
body.dark-mode .course-parent-row.is-active.is-assessment-group + .task-collapse-row > td:first-child {
    box-shadow: inset 4px 0 0 0 #c084fc !important;
}
[data-bs-theme="dark"] .course-parent-row.is-active .course-title-text,
body.dark-mode .course-parent-row.is-active .course-title-text {
    color: #38bdf8 !important;
}
[data-bs-theme="dark"] .course-parent-row.is-active.is-assessment-group .course-title-text,
body.dark-mode .course-parent-row.is-active.is-assessment-group .course-title-text {
    color: #c084fc !important;
}
</style>
