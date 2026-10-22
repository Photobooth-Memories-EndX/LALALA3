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
        'overdue'     => 'Overdue',
        'resubmit'    => 'Resubmit Requested',
    ];
    $statusBadges = [
        'in_progress' => 'bg-primary-lt text-primary',
        'submitted'   => 'bg-info-lt text-info',
        'completed'   => 'bg-success-lt text-success',
        'overdue'     => 'bg-danger-lt text-danger',
        'resubmit'    => 'bg-warning-lt text-warning fw-bold',
    ];
    // "Not started" is not a filter tab — display it as In Progress so the
    // status badge always matches one of the tabs.
    $displayStatus = fn ($s) => $s === 'not_started' ? 'in_progress' : $s;
    $parentTypeBadge = [
        'playlist'         => 'bg-primary-lt text-primary',
        'content'          => 'bg-info-lt text-info',
        'offline_training' => 'bg-warning-lt text-warning',
        'standalone'       => 'bg-secondary-lt text-secondary',
    ];
    $parentTypeLabel = [
        'playlist'         => 'Playlist',
        'content'          => 'Content',
        'offline_training' => 'Offline Training',
        'standalone'       => 'Global Task',
    ];
    // Drill-down navigation: parent list <-> one parent's tasks.
    $baseParams = array_filter([
        'learning_tab'   => 'my-task',
        'my_task_status' => $filter === 'all' ? null : $filter,
        'my_task_q'      => ($search ?? '') !== '' ? $search : null,
    ]);
    $backUrl = route('user.learning.dashboard', $baseParams);
    $groupTasksUrl = fn ($key) => route('user.learning.dashboard', $baseParams + ['my_task_parent' => $key]);
    $resetUrl = route('user.learning.dashboard', array_filter([
        'learning_tab'   => 'my-task',
        'my_task_parent' => ($activeGroup ?? null) ? $activeGroup['key'] : null,
    ]));
    $tabs = [
        'all'         => ['label' => 'All',           'icon' => 'ti-layers'],
        'in_progress' => ['label' => 'In Progress',  'icon' => 'ti-loader-2'],
        'submitted'   => ['label' => 'Pending review','icon' => 'ti-clock'],
        'completed'   => ['label' => 'Completed',     'icon' => 'ti-check'],
        'overdue'     => ['label' => 'Overdue',       'icon' => 'ti-alert-triangle'],
    ];
    if (($summary['resubmit'] ?? 0) > 0) {
        $tabs['resubmit'] = [
            'label' => 'Perlu Revisi (' . $summary['resubmit'] . ')',
            'icon'  => 'ti-refresh',
        ];
    }
    // Clicking a tab navigates immediately (keeps search + parent context).
    $tabUrl = fn ($key) => route('user.learning.dashboard', array_filter([
        'learning_tab'   => 'my-task',
        'my_task_status' => $key === 'all' ? null : $key,
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
                <strong>Perhatian:</strong> Terdapat kesalahan pada pengisian form jawaban tugas. Silakan periksa kembali form yang terbuka.
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
                        Ada {{ $summary['resubmit'] }} Tugas Memerlukan Revisi
                    </h4>
                    <div class="text-muted small">
                        Admin telah memeriksa dan meminta perbaikan jawaban pada tugas Anda. Silakan buka tugas bertanda <strong>Resubmit Requested</strong> untuk melihat catatan dan mengirim ulang jawaban.
                    </div>
                </div>
            </div>
            @if ($filter !== 'resubmit')
                <a href="{{ $tabUrl('resubmit') }}" class="btn btn-warning text-dark fw-semibold px-3 py-2 rounded-pill shadow-sm">
                    <i class="ti ti-eye me-1"></i> Lihat Tugas Revisi ({{ $summary['resubmit'] }})
                </a>
            @endif
        </div>
    </div>
@endif

{{-- Access code: unlock standalone tasks shared with specific users --}}
@if (($showRedeemCard ?? false) && Route::has('my-task.redeem'))
    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body py-2 px-3">
            @if (session('redeem_notice'))
                <div class="alert alert-{{ session('redeem_notice')['type'] === 'success' ? 'success' : 'danger' }} py-2 mb-2">
                    {{ session('redeem_notice')['text'] }}
                </div>
                @php session()->forget('redeem_notice'); @endphp
            @endif
            <form method="POST" action="{{ route('my-task.redeem') }}" class="d-flex flex-wrap gap-2 align-items-center">
                @csrf
                <span class="text-muted small d-flex align-items-center">
                    <i class="ti ti-key me-1"></i>Have an access code? <span class="ms-1 d-none d-md-inline">You can also open the shared link / scan the QR code.</span>
                </span>
                <input type="text" name="code" class="form-control form-control-sm text-uppercase"
                       style="max-width:160px" placeholder="Enter code" maxlength="12" required>
                <button type="submit" class="btn btn-sm btn-primary">Unlock</button>
            </form>
        </div>
    </div>
@endif

{{-- Quick Filter --}}
<div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body">
        <div class="text-muted small mb-2">Quick Filter</div>
        <div class="d-flex flex-wrap gap-2 mb-2">
            @foreach ($tabs as $key => $tab)
                <a href="{{ $tabUrl($key) }}"
                   class="btn {{ $filter === $key ? 'btn-primary' : 'btn-white' }}">
                    <i class="ti {{ $tab['icon'] }} me-1"></i>{{ $tab['label'] }}
                </a>
            @endforeach
        </div>
        <form method="GET" action="{{ route('user.learning.dashboard') }}" class="d-flex flex-wrap gap-2 align-items-center">
            <input type="hidden" name="learning_tab" value="my-task">
            <input type="hidden" name="my_task_status" value="{{ $filter }}">
            @if ($activeGroup ?? null)
                <input type="hidden" name="my_task_parent" value="{{ $activeGroup['key'] }}">
            @endif
            <div class="input-icon flex-grow-1" style="min-width:180px">
                <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                <input type="text" name="my_task_q" class="form-control" placeholder="Search..." value="{{ $search ?? '' }}">
            </div>
            <button type="submit" class="btn btn-primary" title="Search"><i class="ti ti-search"></i></button>
            <a href="{{ $resetUrl }}" class="btn btn-white" title="Reset filter"><i class="ti ti-refresh"></i></a>
        </form>
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
                                        <span class="badge bg-danger text-white"><i class="ti ti-flame me-1"></i>Hari Ini ({{ $tdl->format('H:i') }})</span>
                                    @elseif ($isUrgent)
                                        <span class="badge bg-warning text-dark"><i class="ti ti-clock-exclamation me-1"></i>H-1 / H-2 ({{ $tdl->format('d M') }})</span>
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
                                        <i class="ti ti-refresh me-1"></i> Revisi
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


    {{-- Parent list --}}
    @if ($groups->isNotEmpty())
        @include('user.learning.dashboard-partials.my-task-parent-table', ['tableGroups' => $groups])
    @else
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 text-center text-muted">
                <i class="ti ti-list-check fs-1 d-block mb-2"></i>
                @if ($filter === 'all' && ($search ?? '') === '')
                    No tasks yet. Open a playlist or content you follow first — tasks will appear here after you open it.
                @else
                    No tasks match this filter.
                @endif
            </div>
        </div>
    @endif
@endif

{{-- ── Task Pop Up Modals (Focused Workspace) ──────────────────── --}}
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
    @endphp
    <div class="modal modal-blur fade task-popup-modal"
         id="taskModal-{{ $task->id }}"
         tabindex="-1"
         role="dialog"
         aria-hidden="true"
         data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge bg-primary-lt text-primary px-2.5 py-1 fs-6">
                            <i class="ti ti-file-text me-1"></i>{{ $task->typeLabel() }}
                        </span>
                        @if ($task->parentTitle())
                            <span class="badge bg-secondary-lt text-secondary px-2 py-1 fs-6">
                                <i class="ti ti-bookmark me-1"></i>{{ $task->parentTitle() }}
                            </span>
                        @elseif ($task->isStandalone())
                            <span class="badge bg-secondary-lt text-secondary px-2 py-1 fs-6">
                                <i class="ti ti-world me-1"></i>Global Task
                            </span>
                        @endif
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge {{ $statusBadges[$displayStatus($modalStatus)] }} px-2.5 py-1 fs-6">
                            {{ $statusLabels[$displayStatus($modalStatus)] }}
                        </span>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body p-4 bg-light-subtle">
                    @include('user.learning.dashboard-partials.my-task-card', ['task' => $task, 'inModal' => true])
                </div>
            </div>
        </div>
    </div>
@endforeach

<script>
(function () {
    // If validation error occurred, reopen the modal for the active task
    @if ($errors->any() && old('active_task_id'))
        var errModalEl = document.getElementById('taskModal-{{ old('active_task_id') }}');
        if (errModalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            setTimeout(function () {
                bootstrap.Modal.getOrCreateInstance(errModalEl).show();
            }, 150);
        }
    @endif
})();
</script>
