{{-- resources/views/manage/task/submissions.blade.php --}}
{{-- Responses list table with inline SpeedGrader Workstation (keeping application sidebar intact) --}}
@extends('layouts.sidebar-master')

@section('content')
@php
    $allSubmissions = $submissions->values();
    $needsReviewSubmissions = $allSubmissions->filter(fn($s) => $s->status === 'pending' && ! $s->resubmit_requested)->values();
    $gradedSubmissions = $allSubmissions->filter(fn($s) => $s->status === 'completed')->values();
    $rejectedSubmissions = $allSubmissions->filter(fn($s) => $s->status === 'rejected' || $s->resubmit_requested)->values();
    $resubmitSubmissions = $allSubmissions->filter(fn($s) => $s->resubmit_requested)->values();
    $totalSubmissions = $allSubmissions->count();
    $gradedCount = $gradedSubmissions->count();

    $activeFilter = request('status', 'all');
    if ($activeFilter === 'pending') {
        $activeFilter = 'need_review';
    } elseif ($activeFilter === 'completed') {
        $activeFilter = 'approve';
    }

    // Default active submission: first needs-review, or first submission
    $defaultActive = $needsReviewSubmissions->first() ?? $allSubmissions->first();
    $defaultActiveId = $defaultActive ? $defaultActive->id : 0;

    $submissionsJson = $allSubmissions->map(function ($s, $idx) use ($task) {
        return [
            'id' => $s->id,
            'index' => $idx + 1,
            'learner_name' => $s->user->name ?? 'User #' . $s->user_id,
            'learner_email' => $s->user->email ?? '-',
            'learner_avatar' => strtoupper(substr($s->user->name ?? 'U', 0, 1)),
            'submitted_at' => $s->submitted_at ? $s->submitted_at->format('d M Y, H:i') : '-',
            'submitted_at_short' => $s->submitted_at ? $s->submitted_at->format('d M, H:i') : '-',
            'status' => $s->status,
            'resubmit_requested' => (bool)$s->resubmit_requested,
            'score' => $s->score,
            'feedback' => $s->feedback ?? '',
            'complete_url' => route('manage.task.submissions.complete', [$task, $s]),
            'resubmit_url' => route('manage.task.submissions.resubmit', [$task, $s]),
            'reject_url' => route('manage.task.submissions.reject', [$task, $s]),
        ];
    });
@endphp

<style>
    /* Review Submission Modal Dialog */
    #reviewSubmissionModal .modal-dialog {
        max-width: 1280px;
        margin-top: 1.25rem;
        margin-bottom: 1.25rem;
    }
    #reviewSubmissionModal .modal-content {
        height: calc(100vh - 2.5rem);
        max-height: calc(100vh - 2.5rem);
        border-radius: 12px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    #reviewSubmissionModal .modal-body {
        height: calc(100vh - 120px);
        max-height: calc(100vh - 120px);
        overflow: hidden !important;
        flex: 1 1 auto;
    }
    /* Left task view canvas: no outer scrollbar */
    #reviewSubmissionModal .review-canvas-col {
        height: 100%;
        max-height: 100%;
        overflow-y: auto;
        background: #f8fafc;
        padding: 1.25rem;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    #reviewSubmissionModal .review-canvas-col::-webkit-scrollbar {
        display: none;
    }
    /* Right grading panel: fixed & pinned, no scrollbar, full height flex */
    #reviewSubmissionModal .review-panel-col {
        height: 100%;
        max-height: 100%;
        overflow-y: auto;
        background: #ffffff;
        padding: 1.25rem 1.5rem;
        border-left: 1px solid #e2e8f0;
        scrollbar-width: none;
        -ms-overflow-style: none;
        display: flex;
        flex-direction: column;
    }
    #reviewSubmissionModal .review-panel-col::-webkit-scrollbar {
        display: none;
    }

    /* Zoom and Mouse Control States */
    #filePreviewContent {
        user-select: none;
        -webkit-user-select: none;
        overflow: hidden !important;
    }
    #filePreviewContent.is-dragging {
        cursor: grabbing !important;
    }
    #filePreviewZoomWrapper {
        user-select: none;
        -webkit-user-select: none;
    }
    #filePreviewZoomWrapper img {
        user-select: none;
        -webkit-user-select: none;
        -webkit-user-drag: none;
    }
    .preview-image-clickable {
        transition: filter 0.2s ease;
    }
    .preview-image-clickable:hover {
        filter: brightness(1.05);
    }

    /* Quick Filter Pills */
    .my-task-pills-row {
        margin-top: 0.15rem;
    }
    .my-task-category-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.4rem 1.15rem;
        border-radius: 50rem;
        font-size: 0.8125rem;
        font-weight: 500;
        line-height: 1.2;
        text-decoration: none;
        white-space: nowrap;
        background-color: #f1f5f9;
        color: #334155;
        border: none;
        cursor: pointer;
        transition: all 0.15s ease-in-out;
    }
    .my-task-category-pill:hover {
        background-color: #e2e8f0;
        color: #0f172a;
        text-decoration: none;
    }
    .my-task-category-pill.active {
        background-color: #e0f2fe !important;
        color: #0284c7 !important;
        font-weight: 600;
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

    /* Standard OWL green download button */
    .btn-download {
        background-color: #2fb344 !important;
        border-color: #2fb344 !important;
        color: #ffffff !important;
        transition: all 0.2s ease;
        height: 38px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-weight: 600;
        border-radius: 8px !important;
    }
    .btn-download:hover,
    .btn-download:focus,
    .btn-download:active {
        background-color: #269639 !important;
        border-color: #269639 !important;
        color: #ffffff !important;
    }
    .btn-download:hover i,
    .btn-download:focus i,
    .btn-download:active i {
        color: #ffffff !important;
    }
</style>

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- 1. STANDARD PAGE: Responses Table List                                 --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
<div id="responsesTableView">
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
                        @elseif ($task->type === 'file_submission')
                            <span class="ms-2">· File Submission</span>
                        @else
                            <span class="ms-2">· {{ $task->fields->count() }} {{ Str::plural('question', $task->fields->count()) }}</span>
                        @endif
                    </div>
                </div>

                {{-- Download Submissions (Direct Excel Download) --}}
                <div class="col-auto ms-auto d-print-none">
                    <a href="{{ route('manage.task.submissions.export', [$task, 'format' => 'xlsx']) }}"
                       class="btn btn-success rounded-3 px-3 btn-download shadow-sm fw-semibold text-decoration-none"
                       id="btnDownloadExcel"
                       title="Download Responses (Excel)">
                        <i class="ti ti-download me-1"></i> Download
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ti ti-check fs-2 text-success"></i>
                        <div>{{ session('success') }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ti ti-alert-circle fs-2 text-danger"></i>
                        <div>{{ session('error') }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card shadow-sm border-0">
                <div class="card-body border-bottom py-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        {{-- Quick Filters: All, Need Review, Approve, Rejected --}}
                        <div class="d-flex align-items-center flex-wrap gap-2 my-task-pills-row" id="submissionQuickFilters">
                            <button type="button"
                                    class="my-task-category-pill {{ $activeFilter === 'all' ? 'active' : '' }}"
                                    data-filter="all"
                                    title="Filter by All Submissions">
                                All
                                <span class="badge bg-secondary-lt ms-1.5 rounded-pill">{{ $counts['total'] ?? $totalSubmissions }}</span>
                            </button>
                            <button type="button"
                                    class="my-task-category-pill {{ $activeFilter === 'need_review' ? 'active' : '' }}"
                                    data-filter="need_review"
                                    title="Filter by Need Review">
                                Need Review
                                <span class="badge bg-warning-lt ms-1.5 rounded-pill">{{ $counts['pending'] ?? $needsReviewSubmissions->count() }}</span>
                            </button>
                            <button type="button"
                                    class="my-task-category-pill {{ $activeFilter === 'approve' ? 'active' : '' }}"
                                    data-filter="approve"
                                    title="Filter by Approve">
                                Approve
                                <span class="badge bg-success-lt ms-1.5 rounded-pill">{{ $counts['completed'] ?? $gradedSubmissions->count() }}</span>
                            </button>
                            <button type="button"
                                    class="my-task-category-pill {{ $activeFilter === 'rejected' ? 'active' : '' }}"
                                    data-filter="rejected"
                                    title="Filter by Rejected">
                                Rejected
                                <span class="badge bg-danger-lt ms-1.5 rounded-pill">{{ $counts['rejected'] ?? $rejectedSubmissions->count() }}</span>
                            </button>
                        </div>

                        {{-- Search Filter Input --}}
                        <div class="position-relative" style="min-width: 220px;">
                            <span class="position-absolute top-50 start-0 translate-middle-y ps-2.5 text-muted pointer-events-none" style="z-index: 2;">
                                <i class="ti ti-search" style="font-size: 14px;"></i>
                            </span>
                            <input type="text"
                                   id="responsesSearchInput"
                                   class="form-control form-control-sm ps-4 rounded-pill"
                                   placeholder="Search user..."
                                   style="font-size: 12.5px;">
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter table-hover mb-0" id="submissionsTable">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Submitted</th>
                                <th>Status</th>
                                <th>Score</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($submissions as $submission)
                                @php
                                    $rowStatus = 'need_review';
                                    if ($submission->status === 'completed') {
                                        $rowStatus = 'approve';
                                    } elseif ($submission->status === 'rejected' || $submission->resubmit_requested) {
                                        $rowStatus = 'rejected';
                                    }
                                @endphp
                                <tr class="submission-table-row"
                                    data-submission-id="{{ $submission->id }}"
                                    data-status="{{ $rowStatus }}"
                                    data-user-name="{{ strtolower($submission->user?->name ?? '') }}"
                                    data-user-email="{{ strtolower($submission->user?->email ?? '') }}">
                                    <td>
                                        <div class="d-flex align-items-center gap-2.5">
                                            <div class="avatar avatar-sm rounded-circle bg-primary-lt text-primary fw-bold">
                                                {{ strtoupper(substr($submission->user?->name ?? 'U', 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark">{{ $submission->user?->name ?? 'User #' . $submission->user_id }}</div>
                                                <div class="text-muted small">{{ $submission->user?->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>{{ $submission->submitted_at?->format('d M Y, H:i') ?? '-' }}</div>
                                        @if ($task->deadline && $submission->submitted_at && $submission->submitted_at->gt($task->deadline))
                                            <span class="badge bg-danger-lt text-danger mt-1">Late</span>
                                        @endif
                                    </td>
                                    <td class="submission-row-status">
                                        @if ($submission->status === 'completed')
                                            <span class="badge bg-success-lt text-success"><i class="ti ti-check me-1"></i>Approve</span>
                                        @elseif ($submission->resubmit_requested)
                                            <span class="badge bg-warning-lt text-warning"><i class="ti ti-refresh me-1"></i>Rejected (Resubmit)</span>
                                        @elseif ($submission->status === 'rejected')
                                            <span class="badge bg-danger-lt text-danger"><i class="ti ti-x me-1"></i>Completed as Rejected</span>
                                        @else
                                            <span class="badge bg-warning text-dark"><i class="ti ti-clock me-1"></i>Pending Review</span>
                                        @endif
                                    </td>
                                    <td class="submission-row-score">
                                        @if (!is_null($submission->score))
                                            <span class="badge bg-primary-lt text-primary fs-5 fw-bold">{{ $submission->score }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                        @if ($submission->feedback)
                                            <div class="text-muted small mt-1 text-truncate" style="max-width: 180px;" title="{{ $submission->feedback }}">
                                                <i class="ti ti-message-circle me-1"></i>{{ Str::limit($submission->feedback, 40) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button type="button"
                                                class="btn btn-sm btn-primary submission-menu-btn d-inline-flex align-items-center gap-1.5 px-3 py-1.5 shadow-sm"
                                                onclick="openWorkstation({{ $submission->id }})"
                                                data-bs-toggle="modal"
                                                data-bs-target="#reviewSubmissionModal"
                                                title="Review Submission">
                                            <i class="ti ti-clipboard-check fs-2"></i>
                                            <span>Review Submission</span>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        <div class="empty">
                                            <div class="empty-icon"><i class="ti ti-inbox fs-1 text-muted"></i></div>
                                            <p class="empty-title">No responses yet</p>
                                            <p class="empty-subtitle text-muted">Users haven't submitted answers for this task yet.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                            <tr id="noFilteredSubmissionsRow" class="d-none">
                                <td colspan="5" class="text-center text-muted py-5">
                                    <div class="empty">
                                        <div class="empty-icon"><i class="ti ti-filter-off fs-1 text-muted"></i></div>
                                        <p class="empty-title">No submissions found</p>
                                        <p class="empty-subtitle text-muted">No submissions match the selected filter.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- 2. REVIEW SUBMISSION MODAL POPUP                                       --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="reviewSubmissionModal" tabindex="-1" aria-labelledby="reviewSubmissionModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            {{-- Modal Header: Clean, Spacious Title & User Switcher --}}
            <div class="modal-header border-bottom py-3 px-4 bg-white d-flex align-items-center justify-content-between flex-shrink-0" style="min-height: 70px;">
                {{-- Left: Task Information --}}
                <div class="d-flex align-items-center gap-3 min-w-0" style="flex: 1 1 0; max-width: 42%;">
                    <div class="avatar avatar-sm rounded-circle bg-primary-lt text-primary fw-bold flex-shrink-0" style="width: 38px; height: 38px;">
                        <i class="ti ti-clipboard-check fs-2"></i>
                    </div>
                    <div class="min-w-0">
                        <h5 class="modal-title fw-bold text-dark text-truncate mb-0" id="reviewSubmissionModalLabel" style="font-size: 15px; line-height: 1.25;">
                            Review Submission
                        </h5>
                        <div class="text-muted text-truncate d-flex align-items-center gap-1.5 mt-1" style="font-size: 12px; line-height: 1.2;">
                            <span class="text-secondary fw-semibold text-truncate" title="{{ $task->title }}" style="max-width: 240px;">{{ $task->title }}</span>
                            <span class="text-muted">&bull;</span>
                            <span class="badge bg-primary-lt text-primary px-1.5 py-0.5 rounded-pill flex-shrink-0" style="font-size: 10.5px; font-weight: 600;">{{ $task->typeLabel() }}</span>
                        </div>
                    </div>
                </div>

                {{-- Center: User Switcher [ < ] [ USER NAME ] [ > ] (No wrapping pill container) --}}
                <div class="d-flex align-items-center justify-content-center gap-2 text-center px-2" style="flex: 1 1 0;">
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary rounded-circle p-0 d-inline-flex align-items-center justify-content-center flex-shrink-0"
                            id="prevSubmissionBtn"
                            title="Previous User (Left Arrow)"
                            style="width: 28px; height: 28px; border-color: #cbd5e1; color: #64748b; background-color: #ffffff;">
                        <i class="ti ti-chevron-left" style="font-size: 14px;"></i>
                    </button>

                    <div class="text-center px-2" style="min-width: 130px; max-width: 280px;">
                        <div class="fw-bold text-dark text-truncate text-uppercase" id="headerLearnerName" style="font-size: 13.5px; letter-spacing: 0.3px; line-height: 1.25;">
                            {{ $defaultActive ? ($defaultActive->user->name ?? 'User') : 'No Submissions' }}
                        </div>
                        <div class="text-muted" id="headerSubmissionCount" style="font-size: 11px; line-height: 1.2; margin-top: 1px;">
                            Submission {{ $totalSubmissions > 0 ? 1 : 0 }} of {{ $totalSubmissions }}
                        </div>
                    </div>

                    <button type="button"
                            class="btn btn-sm btn-outline-secondary rounded-circle p-0 d-inline-flex align-items-center justify-content-center flex-shrink-0"
                            id="nextSubmissionBtn"
                            title="Next User (Right Arrow)"
                            style="width: 28px; height: 28px; border-color: #cbd5e1; color: #64748b; background-color: #ffffff;">
                        <i class="ti ti-chevron-right" style="font-size: 14px;"></i>
                    </button>
                </div>

                {{-- Right: Balancer spacer + Close Button --}}
                <div class="d-flex align-items-center justify-content-end gap-2" style="flex: 1 1 0; max-width: 40%;">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" title="Close & Return to Review">
                        <span class="visually-hidden">Close &amp; Return to Review</span>
                    </button>
                </div>
            </div>

            {{-- Modal Body: 2-Column Review (Left: Canvas/Answers, Right: Grading Form) --}}
            <div class="modal-body p-0 position-relative">
                {{-- Floating Toast Notification for Auto-Advance --}}
                <div id="gradingToast" class="alert alert-success border-0 shadow-lg position-absolute d-none" style="top: 16px; left: 50%; transform: translateX(-50%); z-index: 1050; padding: 8px 18px; border-radius: 50px; font-size: 13px; font-weight: 500;">
                    <i class="ti ti-circle-check me-1 fs-2 align-middle"></i> <span id="gradingToastText">Saved!</span>
                </div>
                <div class="row g-0 h-100">
                    {{-- ── COLUMN 1: Main Submission Viewer & Canvas ── --}}
                    <div class="col-lg-8 review-canvas-col" id="filePreviewModal">
                        <div id="workstationCanvasBody">
                @forelse ($allSubmissions as $sub)
                    <div class="submission-pane d-none" id="submission-pane-{{ $sub->id }}" data-submission-id="{{ $sub->id }}">
                        <div id="submissionContent-{{ $sub->id }}">
                        @php
                            $answers = $sub->answers;
                        @endphp

                        @if ($task->needsFields())
                            {{-- Google Form-style dynamic fields --}}
                            @foreach ($task->fields as $fIdx => $field)
                                @php
                                    $ans = $answers->get($field->id);
                                    $files = $ans?->filePaths() ?? [];
                                    $answerText = $ans?->answer_text ?? null;
                                @endphp

                                <div class="card border rounded-3 p-3.5 bg-white mb-3 shadow-xs task-question-card" style="border-color: #e2e8f0 !important;">
                                    {{-- Question Header matching SpeedGrader --}}
                                    @php
                                        $fLabel = $field->label;
                                        if ($task->type === 'file_submission' && in_array(strtolower(trim($fLabel)), ['file submission', 'question', 'question 1', 'question #1', ''], true)) {
                                            $fLabel = 'Submitted File';
                                        }
                                    @endphp
                                    <div class="d-flex align-items-center gap-2 mb-2.5 flex-wrap">
                                        @if ($task->type === 'file_submission')
                                            <span class="d-inline-flex align-items-center justify-content-center text-white fw-bold rounded-2 flex-shrink-0"
                                                  style="background-color: #0f2942; width: 22px; height: 22px; font-size: 11.5px; border-radius: 5px;">
                                                <i class="ti ti-file-upload fs-6"></i>
                                            </span>
                                        @else
                                            <span class="d-inline-flex align-items-center justify-content-center text-white fw-bold rounded-2 flex-shrink-0"
                                                  style="background-color: #0f2942; width: 22px; height: 22px; font-size: 11.5px; border-radius: 5px;">
                                                {{ $fIdx + 1 }}
                                            </span>
                                        @endif
                                        <h6 class="fw-bold mb-0 text-truncate" style="font-size: 14px; color: #0f2942;">
                                            {{ $fLabel }}
                                        </h6>
                                        <span class="text-muted" style="font-size: 12.5px;">
                                            &bull; {{ $field->typeLabel() }}
                                        </span>
                                    </div>

                                    {{-- File upload responses --}}
                                    @if ($field->isFile())
                                        @if (! empty($files))
                                            @foreach ($files as $fPath)
                                                @php
                                                    $fPathNorm = str_replace('\\', '/', $fPath);
                                                    $fName = basename($fPathNorm);
                                                    $fUrl = Storage::url($fPathNorm);
                                                    $fExt = strtolower(pathinfo($fName, PATHINFO_EXTENSION));
                                                    $isImg = in_array($fExt, ['jpg','jpeg','png','gif','webp','svg','bmp']);
                                                    $isPdf = ($fExt === 'pdf');
                                                    $isVid = in_array($fExt, ['mp4','webm','ogg','mov','mkv']);
                                                    $isAud = in_array($fExt, ['mp3','wav','m4a','aac']);
                                                    $isTxt = in_array($fExt, ['txt','csv','log','json','xml','md','sql','html','css','js']);
                                                    $isDoc = in_array($fExt, ['doc','docx','xls','xlsx','ppt','pptx']);

                                                    $fSize = 'Submission File';
                                                    try {
                                                        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($fPathNorm)) {
                                                            $bytes = \Illuminate\Support\Facades\Storage::disk('public')->size($fPathNorm);
                                                            if ($bytes >= 1048576) {
                                                                $fSize = number_format($bytes / 1048576, 1) . ' MB';
                                                            } elseif ($bytes >= 1024) {
                                                                $fSize = number_format($bytes / 1024, 1) . ' KB';
                                                            } else {
                                                                $fSize = $bytes . ' B';
                                                            }
                                                        } elseif (\Illuminate\Support\Facades\Storage::exists($fPathNorm)) {
                                                            $bytes = \Illuminate\Support\Facades\Storage::size($fPathNorm);
                                                            if ($bytes >= 1048576) {
                                                                $fSize = number_format($bytes / 1048576, 1) . ' MB';
                                                            } elseif ($bytes >= 1024) {
                                                                $fSize = number_format($bytes / 1024, 1) . ' KB';
                                                            } else {
                                                                $fSize = $bytes . ' B';
                                                            }
                                                        }
                                                    } catch (\Throwable $e) {}

                                                    $fKind = $isImg ? 'Image' : ($isPdf ? 'PDF Document' : ($isDoc ? 'Office Document' : ($isVid ? 'Video' : ($isAud ? 'Audio' : 'File'))));

                                                    if ($isDoc || in_array($fExt, ['docx','doc','xlsx','xls','csv','ppt','pptx'])) {
                                                        $previewUrl = route('manage.task.submissions.preview-doc', [$task, 'path' => $fPathNorm]);
                                                        $tabUrl = $previewUrl;
                                                        $isPpt = in_array($fExt, ['ppt', 'pptx']);
                                                        $iconClass = in_array($fExt, ['doc','docx']) ? 'ti-file-description' : ($isPpt ? 'ti-presentation' : 'ti-file-spreadsheet');
                                                        $iconBg = in_array($fExt, ['doc','docx']) ? 'bg-primary-lt' : ($isPpt ? 'bg-orange-lt' : 'bg-success-lt');
                                                        $iconColor = in_array($fExt, ['doc','docx']) ? 'text-primary' : ($isPpt ? 'text-orange' : 'text-success');
                                                    } elseif ($isPdf) {
                                                        $previewUrl = $fUrl;
                                                        $tabUrl = $fUrl;
                                                        $iconClass = 'ti-file-type-pdf';
                                                        $iconBg = 'bg-danger-lt';
                                                        $iconColor = 'text-danger';
                                                    } elseif ($isImg) {
                                                        $previewUrl = $fUrl;
                                                        $tabUrl = $fUrl;
                                                        $iconClass = 'ti-photo';
                                                        $iconBg = 'bg-azure-lt';
                                                        $iconColor = 'text-azure';
                                                    } elseif ($isVid) {
                                                        $previewUrl = $fUrl;
                                                        $tabUrl = $fUrl;
                                                        $iconClass = 'ti-video';
                                                        $iconBg = 'bg-purple-lt';
                                                        $iconColor = 'text-purple';
                                                    } elseif ($isAud) {
                                                        $previewUrl = $fUrl;
                                                        $tabUrl = $fUrl;
                                                        $iconClass = 'ti-music';
                                                        $iconBg = 'bg-warning-lt';
                                                        $iconColor = 'text-warning';
                                                    } else {
                                                        $previewUrl = $fUrl;
                                                        $tabUrl = $fUrl;
                                                        $iconClass = 'ti-file';
                                                        $iconBg = 'bg-secondary-lt';
                                                        $iconColor = 'text-secondary';
                                                    }
                                                @endphp

                                                <div class="card border rounded-3 overflow-hidden shadow-xs mb-0 file-submission-card" style="border-color: #cbd5e1 !important;" data-file-url="{{ $fUrl }}" data-file-name="{{ $fName }}">
                                                    {{-- Unified Card Header with Info & Action Buttons --}}
                                                    <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                                                        <div class="d-flex align-items-center gap-2 min-w-0">
                                                            <span class="avatar avatar-xs rounded {{ $iconBg }} {{ $iconColor }}">
                                                                <i class="ti {{ $iconClass }}"></i>
                                                            </span>
                                                            <div class="min-w-0">
                                                                <div class="fw-bold text-dark text-truncate" style="font-size: 13px; max-width: 320px;" title="{{ $fName }}">{{ $fName }}</div>
                                                                <div class="text-muted small" style="font-size: 11px;">
                                                                    <span class="badge bg-secondary-lt text-secondary px-1.5 py-0.5 rounded">{{ strtoupper($fExt) }}</span>
                                                                    <span class="mx-1">&bull;</span> {{ $fKind }} &bull; {{ $fSize }}
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="d-flex align-items-center gap-1.5 flex-shrink-0">
                                                            {{-- Open in New Tab Button --}}
                                                            <a href="{{ $tabUrl }}"
                                                               target="_blank"
                                                               rel="noopener noreferrer"
                                                               class="btn btn-sm btn-white text-dark shadow-xs border rounded-2 px-2.5 py-1 text-decoration-none"
                                                               title="Open in new tab">
                                                                <i class="ti ti-external-link me-1 text-muted"></i> <span style="font-size: 12px; font-weight: 500;">Open in new tab</span>
                                                            </a>

                                                            {{-- Download Button --}}
                                                            <a href="{{ $fUrl }}"
                                                               download="{{ $fName }}"
                                                               class="btn btn-sm btn-white text-dark shadow-xs border rounded-2 px-2.5 py-1 text-decoration-none"
                                                               title="Download file">
                                                                <i class="ti ti-download me-1 text-muted"></i> <span style="font-size: 12px; font-weight: 500;">Download</span>
                                                            </a>
                                                        </div>
                                                    </div>

                                                    {{-- In-line Embedded Viewer Body --}}
                                                    <div class="card-body p-0 bg-light-subtle position-relative">
                                                        @if ($isImg)
                                                            <div class="p-3 d-flex align-items-center justify-content-center text-center preview-img-container"
                                                                 style="min-height: 380px; max-height: calc(100vh - 290px); background-color: #f1f5f9; overflow: hidden;">
                                                                <img src="{{ $fUrl }}" alt="{{ $fName }}" class="img-fluid rounded shadow-sm" style="max-height: calc(100vh - 310px); max-width: 95%; object-fit: contain; background: #ffffff;">
                                                            </div>
                                                        @elseif ($isPdf)
                                                            <div style="height: calc(100vh - 290px); min-height: 420px; background: #fff;">
                                                                <iframe src="{{ $fUrl }}#toolbar=1" style="width: 100%; height: 100%; border: none;" title="{{ $fName }}"></iframe>
                                                            </div>
                                                        @elseif ($isDoc || in_array($fExt, ['docx','doc','xlsx','xls','csv']))
                                                            <div class="doc-iframe-wrapper position-relative" style="height: calc(100vh - 290px); min-height: 420px; background: #f8fafc; border-radius: 6px; overflow: hidden;">
                                                                <iframe src="{{ route('manage.task.submissions.preview-doc', [$task, 'path' => $fPathNorm]) }}"
                                                                        class="doc-preview-iframe"
                                                                        style="width: 100%; height: 100%; border: none; display: block;"
                                                                        title="{{ $fName }}"
                                                                        loading="lazy"></iframe>
                                                            </div>
                                                        @elseif ($isVid)
                                                            <div class="p-3 bg-black d-flex align-items-center justify-content-center">
                                                                <video src="{{ $fUrl }}" controls class="rounded w-100" style="max-height: calc(100vh - 290px);"></video>
                                                            </div>
                                                        @elseif ($isAud)
                                                            <div class="p-4 bg-white text-center">
                                                                <audio src="{{ $fUrl }}" controls class="w-100" style="max-width: 500px;"></audio>
                                                            </div>
                                                        @elseif ($isTxt)
                                                            <div style="height: calc(100vh - 290px); min-height: 420px; background: #fff;">
                                                                <iframe src="{{ $fUrl }}" style="width: 100%; height: 100%; border: none;" title="{{ $fName }}"></iframe>
                                                            </div>
                                                        @else
                                                            <div class="p-4 text-center">
                                                                <i class="ti ti-file-description fs-1 text-muted d-block mb-2"></i>
                                                                <div class="fw-semibold text-dark mb-1">{{ $fName }}</div>
                                                                <div class="text-muted small mb-3">Direct preview not supported for {{ strtoupper($fExt) }} files.</div>
                                                                <a href="{{ $fUrl }}" download="{{ $fName }}" class="btn btn-sm btn-primary">
                                                                    <i class="ti ti-download me-1"></i> Download {{ $fName }}
                                                                </a>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif

                                        {{-- Google Drive / Cloud Link Response --}}
                                        @if (! empty($answerText))
                                            <div class="card border rounded-3 p-3 bg-white mb-3 shadow-xs" style="border-color: #cbd5e1 !important; border-left: 4px solid #206bc4 !important;">
                                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                                    <div class="d-flex align-items-center gap-2.5 min-w-0">
                                                        <span class="avatar avatar-sm rounded-circle bg-primary-lt text-primary">
                                                            <i class="ti ti-brand-google-drive fs-2"></i>
                                                        </span>
                                                        <div class="min-w-0">
                                                            <div class="fw-bold text-dark" style="font-size: 13px;">Cloud / Google Drive Submission Link</div>
                                                            <a href="{{ $answerText }}" target="_blank" rel="noopener noreferrer" class="text-primary text-truncate d-block small" style="max-width: 450px;">
                                                                {{ $answerText }}
                                                            </a>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-1.5 flex-shrink-0">
                                                        <a href="{{ $answerText }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-primary rounded-2 px-3 py-1 text-decoration-none">
                                                            <i class="ti ti-external-link me-1"></i> Open in new tab
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        @elseif (empty($files))
                                            <div class="text-muted fst-italic small py-2 px-1">— No file or link submitted —</div>
                                        @endif
                                    @endif

                                    {{-- Text answers / Paragraph / Choices / Rating --}}
                                    @if (! $field->isFile())
                                        @if ($field->isRating())
                                            @php $rVal = (int) $answerText; @endphp
                                            @if ($rVal > 0)
                                                <div class="d-flex align-items-center gap-1.5 py-1">
                                                    @for ($r = 1; $r <= 5; $r++)
                                                        <i class="ti {{ $r <= $rVal ? 'ti-star-filled text-warning' : 'ti-star text-muted opacity-30' }} fs-2" style="font-size: 1.5rem !important;"></i>
                                                    @endfor
                                                </div>
                                            @else
                                                <div class="text-muted fst-italic small py-2 px-1">— No rating given —</div>
                                            @endif
                                        @elseif ($answerText !== null && $answerText !== '')
                                            <div class="p-3 bg-light rounded-2 border text-dark" style="font-size: 13px; line-height: 1.6; white-space: pre-wrap;">{{ $answerText }}</div>
                                        @else
                                            <div class="text-muted fst-italic small py-2 px-1">— Not answered —</div>
                                        @endif
                                    @endif
                                </div>
                            @endforeach
                        @else
                            {{-- Legacy single file / text response --}}
                            <div class="card border rounded-3 p-4 bg-white mb-4 shadow-xs" style="border-color: #e2e8f0 !important;">
                                <h6 class="fw-bold text-dark mb-3">User Response</h6>
                                @if ($sub->answer)
                                    <div class="p-3 bg-light rounded-2 border text-dark mb-3" style="font-size: 13px; line-height: 1.6; white-space: pre-wrap;">{{ $sub->answer }}</div>
                                @endif
                                @if ($sub->file_path)
                                    @php
                                        $fPathNorm = str_replace('\\', '/', $sub->file_path);
                                        $fName = basename($fPathNorm);
                                        $fUrl = Storage::url($fPathNorm);
                                        $fExt = strtolower(pathinfo($fName, PATHINFO_EXTENSION));
                                        $isImg = in_array($fExt, ['jpg','jpeg','png','gif','webp','svg','bmp']);
                                        $isPdf = ($fExt === 'pdf');
                                        $isVid = in_array($fExt, ['mp4','webm','ogg','mov','mkv']);
                                        $isAud = in_array($fExt, ['mp3','wav','m4a','aac']);
                                        $isTxt = in_array($fExt, ['txt','csv','log','json','xml','md','sql','html','css','js']);
                                        $isDoc = in_array($fExt, ['doc','docx','xls','xlsx','ppt','pptx']);

                                        $fSize = 'Submission File';
                                        try {
                                            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($fPathNorm)) {
                                                $bytes = \Illuminate\Support\Facades\Storage::disk('public')->size($fPathNorm);
                                                if ($bytes >= 1048576) {
                                                    $fSize = number_format($bytes / 1048576, 1) . ' MB';
                                                } elseif ($bytes >= 1024) {
                                                    $fSize = number_format($bytes / 1024, 1) . ' KB';
                                                } else {
                                                    $fSize = $bytes . ' B';
                                                }
                                            } elseif (\Illuminate\Support\Facades\Storage::exists($fPathNorm)) {
                                                $bytes = \Illuminate\Support\Facades\Storage::size($fPathNorm);
                                                if ($bytes >= 1048576) {
                                                    $fSize = number_format($bytes / 1048576, 1) . ' MB';
                                                } elseif ($bytes >= 1024) {
                                                    $fSize = number_format($bytes / 1024, 1) . ' KB';
                                                } else {
                                                    $fSize = $bytes . ' B';
                                                }
                                            }
                                        } catch (\Throwable $e) {}

                                        $fKind = $isImg ? 'Image' : ($isPdf ? 'PDF Document' : ($isDoc ? 'Office Document' : ($isVid ? 'Video' : ($isAud ? 'Audio' : 'File'))));

                                        if ($isDoc || in_array($fExt, ['docx','doc','xlsx','xls','csv','ppt','pptx'])) {
                                            $previewUrl = route('manage.task.submissions.preview-doc', [$task, 'path' => $fPathNorm]);
                                            $tabUrl = $previewUrl;
                                            $isPpt = in_array($fExt, ['ppt', 'pptx']);
                                            $iconClass = in_array($fExt, ['doc','docx']) ? 'ti-file-description' : ($isPpt ? 'ti-presentation' : 'ti-file-spreadsheet');
                                            $iconBg = in_array($fExt, ['doc','docx']) ? 'bg-primary-lt' : ($isPpt ? 'bg-orange-lt' : 'bg-success-lt');
                                            $iconColor = in_array($fExt, ['doc','docx']) ? 'text-primary' : ($isPpt ? 'text-orange' : 'text-success');
                                        } elseif ($isPdf) {
                                            $previewUrl = $fUrl;
                                            $tabUrl = $fUrl;
                                            $iconClass = 'ti-file-type-pdf';
                                            $iconBg = 'bg-danger-lt';
                                            $iconColor = 'text-danger';
                                        } elseif ($isImg) {
                                            $previewUrl = $fUrl;
                                            $tabUrl = $fUrl;
                                            $iconClass = 'ti-photo';
                                            $iconBg = 'bg-azure-lt';
                                            $iconColor = 'text-azure';
                                        } elseif ($isVid) {
                                            $previewUrl = $fUrl;
                                            $tabUrl = $fUrl;
                                            $iconClass = 'ti-video';
                                            $iconBg = 'bg-purple-lt';
                                            $iconColor = 'text-purple';
                                        } elseif ($isAud) {
                                            $previewUrl = $fUrl;
                                            $tabUrl = $fUrl;
                                            $iconClass = 'ti-music';
                                            $iconBg = 'bg-warning-lt';
                                            $iconColor = 'text-warning';
                                        } else {
                                            $previewUrl = $fUrl;
                                            $tabUrl = $fUrl;
                                            $iconClass = 'ti-file';
                                            $iconBg = 'bg-secondary-lt';
                                            $iconColor = 'text-secondary';
                                        }
                                    @endphp

                                    <div class="card border rounded-3 overflow-hidden shadow-xs mb-0 file-submission-card" style="border-color: #cbd5e1 !important;" data-file-url="{{ $fUrl }}" data-file-name="{{ $fName }}">
                                        {{-- Unified Card Header with Info & Action Buttons --}}
                                        <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                                            <div class="d-flex align-items-center gap-2 min-w-0">
                                                <span class="avatar avatar-xs rounded {{ $iconBg }} {{ $iconColor }}">
                                                    <i class="ti {{ $iconClass }}"></i>
                                                </span>
                                                <div class="min-w-0">
                                                    <div class="fw-bold text-dark text-truncate" style="font-size: 13px; max-width: 320px;" title="{{ $fName }}">{{ $fName }}</div>
                                                    <div class="text-muted small" style="font-size: 11px;">
                                                        <span class="badge bg-secondary-lt text-secondary px-1.5 py-0.5 rounded">{{ strtoupper($fExt) }}</span>
                                                        <span class="mx-1">&bull;</span> {{ $fKind }} &bull; {{ $fSize }}
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="d-flex align-items-center gap-1.5 flex-shrink-0">
                                                {{-- Open in New Tab Button --}}
                                                <a href="{{ $tabUrl }}"
                                                   target="_blank"
                                                   rel="noopener noreferrer"
                                                   class="btn btn-sm btn-white text-dark shadow-xs border rounded-2 px-2.5 py-1 text-decoration-none"
                                                   title="Open in new tab">
                                                    <i class="ti ti-external-link me-1 text-muted"></i> <span style="font-size: 12px; font-weight: 500;">Open in new tab</span>
                                                </a>

                                                {{-- Download Button --}}
                                                <a href="{{ $fUrl }}"
                                                   download="{{ $fName }}"
                                                   class="btn btn-sm btn-white text-dark shadow-xs border rounded-2 px-2.5 py-1 text-decoration-none"
                                                   title="Download file">
                                                    <i class="ti ti-download me-1 text-muted"></i> <span style="font-size: 12px; font-weight: 500;">Download</span>
                                                </a>
                                            </div>
                                        </div>

                                        {{-- In-line Embedded Viewer Body --}}
                                        <div class="card-body p-0 bg-light-subtle position-relative">
                                            @if ($isImg)
                                                <div class="p-3 d-flex align-items-center justify-content-center text-center preview-img-container"
                                                     style="min-height: 380px; max-height: calc(100vh - 250px); background-color: #f1f5f9; overflow: hidden;">
                                                    <img src="{{ $fUrl }}" alt="{{ $fName }}" class="img-fluid rounded shadow-sm" style="max-height: calc(100vh - 270px); max-width: 95%; object-fit: contain; background: #ffffff;">
                                                </div>
                                            @elseif ($isPdf)
                                                <div style="height: calc(100vh - 250px); min-height: 450px; background: #fff;">
                                                    <iframe src="{{ $fUrl }}#toolbar=1" style="width: 100%; height: 100%; border: none;" title="{{ $fName }}"></iframe>
                                                </div>
                                            @elseif ($isDoc || in_array($fExt, ['docx','doc','xlsx','xls','csv']))
                                                <div class="doc-iframe-wrapper position-relative" style="height: calc(100vh - 250px); min-height: 450px; background: #f8fafc; border-radius: 6px; overflow: hidden;">
                                                    <iframe src="{{ route('manage.task.submissions.preview-doc', [$task, 'path' => $fPathNorm]) }}"
                                                            class="doc-preview-iframe"
                                                            style="width: 100%; height: 100%; border: none; display: block;"
                                                            title="{{ $fName }}"
                                                            loading="lazy"></iframe>
                                                </div>
                                            @elseif ($isVid)
                                                <div class="p-3 bg-black d-flex align-items-center justify-content-center">
                                                    <video src="{{ $fUrl }}" controls class="rounded w-100" style="max-height: calc(100vh - 250px);"></video>
                                                </div>
                                            @elseif ($isAud)
                                                <div class="p-4 bg-white text-center">
                                                    <audio src="{{ $fUrl }}" controls class="w-100" style="max-width: 500px;"></audio>
                                                </div>
                                            @elseif ($isTxt)
                                                <div style="height: calc(100vh - 250px); min-height: 420px; background: #fff;">
                                                    <iframe src="{{ $fUrl }}" style="width: 100%; height: 100%; border: none;" title="{{ $fName }}"></iframe>
                                                </div>
                                            @else
                                                <div class="p-4 text-center">
                                                    <i class="ti ti-file-description fs-1 text-muted d-block mb-2"></i>
                                                    <div class="fw-semibold text-dark mb-1">{{ $fName }}</div>
                                                    <div class="text-muted small mb-3">Direct preview not supported for {{ strtoupper($fExt) }} files.</div>
                                                    <a href="{{ $fUrl }}" download="{{ $fName }}" class="btn btn-sm btn-primary">
                                                        <i class="ti ti-download me-1"></i> Download {{ $fName }}
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5">
                        <i class="ti ti-inbox fs-1 text-muted d-block mb-2"></i>
                        <h5 class="fw-bold text-dark">No Submissions Yet</h5>
                        <p class="text-muted small">When users submit their answers, they will appear here.</p>
                        <div id="filePreviewZoomWrapper" class="d-none"></div>
                    </div>
                @endforelse
                        </div>
                    </div>

                    {{-- ── COLUMN 2: Right Grading Panel ── --}}
                    <div class="col-lg-4 review-panel-col d-flex flex-column h-100">
                        <div class="d-flex flex-column flex-grow-1 h-100">
                            {{-- Top Pinned: Submission Details & Grading Title --}}
                            <div class="flex-shrink-0">
                                {{-- Submission Details Section --}}
                                <div class="mb-3">
                                    <div class="text-uppercase text-muted fw-bold mb-2" style="font-size: 11px; letter-spacing: 0.5px;">
                                        SUBMISSION DETAILS
                                    </div>
                                    <div class="d-flex flex-column gap-2" style="font-size: 13px;">
                                        <div class="d-flex justify-content-between align-items-baseline">
                                            <span class="text-muted">User</span>
                                            <span class="fw-bold text-dark text-end" id="panelLearnerName">-</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-baseline">
                                            <span class="text-muted">Email</span>
                                            <span class="text-dark text-truncate text-end ms-2" id="panelLearnerEmail" style="max-width: 170px;">-</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-baseline">
                                            <span class="text-muted">Submitted</span>
                                            <span class="text-dark text-end" id="panelSubmittedAt">-</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted">Status</span>
                                            <span id="panelStatusBadge" class="badge bg-warning text-dark">Pending Review</span>
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-3 text-muted" style="opacity: 0.2;">

                                {{-- Grading Section Header --}}
                                <div class="text-uppercase text-muted fw-bold mb-2.5" style="font-size: 11px; letter-spacing: 0.5px;">
                                    VALIDATION
                                </div>

                                {{-- Banner Ketika Sudah di-Grading (Tidak Bisa di-Grading Lagi) --}}
                                <div id="alreadyGradedBanner" class="p-3 rounded-3 border mb-3 d-none">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <i id="gradedBannerIcon" class="ti ti-lock fs-2"></i>
                                        <h6 id="gradedBannerTitle" class="fw-bold mb-0">Validation Closed</h6>
                                    </div>
                                    <div id="gradedBannerDesc" class="text-muted" style="font-size: 11.5px;">
                                        This submission has already been validated and cannot be changed.
                                    </div>
                                </div>
                            </div>

                            {{-- Form with Full Height Flex Layout --}}
                            <form id="reviewSubmissionForm" method="POST" action="" class="d-flex flex-column flex-grow-1 h-100">
                                @csrf
                                <input type="hidden" name="reject_type" id="modalRejectTypeHiddenInput" value="resubmit">
                                <input type="hidden" name="reason" id="modalReasonHiddenInput">
                                <input type="hidden" id="modalCompleteUrl" value="">
                                <input type="hidden" id="modalResubmitUrl" value="">
                                <input type="hidden" id="modalRejectUrl" value="">

                                {{-- 1. Dropdown Decision (Approve / Rejected) di Atas --}}
                                <div id="actionButtonsContainer" class="mb-3 flex-shrink-0">
                                    <label class="form-label fw-semibold text-dark mb-1.5" for="selectGradingDecision" style="font-size: 12.5px;">
                                        Decision <span class="text-danger">*</span>
                                    </label>
                                    <div class="position-relative">
                                        <select id="selectGradingDecision"
                                                class="form-select fw-semibold shadow-xs"
                                                style="border-radius: 8px; font-size: 13.5px; padding: 0.65rem 0.85rem; border-color: #cbd5e1;">
                                            <option value="">— Select Decision —</option>
                                            <option value="approve">✓ Approve</option>
                                            <option value="rejected">✕ Rejected</option>
                                        </select>
                                    </div>
                                    <div id="gradingDecisionHint" class="text-muted text-center mt-2 small" style="font-size: 11.5px;">
                                        Select <strong class="text-success">Approve</strong> or <strong class="text-danger">Rejected</strong> above to validate.
                                    </div>

                                    {{-- Hidden compatibility elements for tests & automated triggers --}}
                                    <button type="button" id="btnActionApprove" class="d-none">Approve</button>
                                    <button type="button" id="btnActionReject" class="d-none">Rejected</button>
                                </div>

                                {{-- 2. Section Approve: Kolom nilai di atas, tombol submit di bawah kanan panel --}}
                                <div id="gradingApproveSection" class="d-none flex-column flex-grow-1 justify-content-between">
                                    <div class="pt-1">
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1.5">
                                                <label class="form-label fw-semibold text-dark mb-0" style="font-size: 12.5px;">
                                                    Score (0–100)
                                                </label>
                                                <span class="text-muted small" style="font-size: 11px;">Optional</span>
                                            </div>
                                            <div class="input-group">
                                                <input type="number"
                                                       id="modalScoreInputApprove"
                                                       class="form-control form-control-lg fw-bold"
                                                       min="0"
                                                       max="100"
                                                       placeholder="0"
                                                       style="font-size: 16px;">
                                                <span class="input-group-text bg-light text-muted fw-semibold" style="font-size: 13px;">/ 100</span>
                                            </div>
                                            <div class="form-hint mt-1" style="font-size: 11px;">Enter a score between 0 and 100, or leave blank.</div>
                                        </div>
                                    </div>

                                    {{-- Tombol Submit di Bawah Kanan Halaman Pop-up --}}
                                    <div class="d-flex justify-content-end align-items-center mt-auto pt-3 border-top">
                                        <button type="button"
                                                class="btn btn-success px-4 py-2 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm"
                                                id="btnConfirmApproveAction"
                                                style="border-radius: 8px; font-size: 13px;">
                                            <i class="ti ti-check fs-2"></i>
                                            <span id="btnConfirmApproveActionText">Submit</span>
                                        </button>
                                    </div>
                                </div>

                                {{-- 3. Section Rejected: Kolom nilai & feedback, tombol di bawah kanan panel --}}
                                <div id="gradingRejectSection" class="d-none flex-column flex-grow-1 justify-content-between">
                                    <div class="pt-1">
                                        <div class="mb-2.5">
                                            <div class="d-flex justify-content-between align-items-center mb-1.5">
                                                <label class="form-label fw-semibold text-dark mb-0" style="font-size: 12.5px;">
                                                    Score (0–100)
                                                </label>
                                                <span class="text-muted small" style="font-size: 11px;">Optional</span>
                                            </div>
                                            <div class="input-group">
                                                <input type="number"
                                                       id="modalScoreInputReject"
                                                       class="form-control form-control-lg fw-bold"
                                                       min="0"
                                                       max="100"
                                                       placeholder="0"
                                                       style="font-size: 16px;">
                                                <span class="input-group-text bg-light text-muted fw-semibold" style="font-size: 13px;">/ 100</span>
                                            </div>
                                            <div class="form-hint mt-1" style="font-size: 11px;">Optional score if completing as rejected.</div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1.5">
                                                <label class="form-label fw-semibold text-dark mb-0" style="font-size: 12.5px;">
                                                    Feedback / Notes <span class="text-danger">*</span>
                                                </label>
                                                <span class="badge bg-danger-lt text-danger" style="font-size: 10.5px;">Required</span>
                                            </div>
                                            <textarea name="feedback"
                                                      id="modalFeedbackInput"
                                                      rows="3"
                                                      class="form-control"
                                                      placeholder="Write feedback or revision notes for user..."
                                                      style="font-size: 12.5px; resize: vertical;"></textarea>
                                            <div class="invalid-feedback" id="feedbackInvalidFeedback" style="font-size: 11.5px;">
                                                Feedback is required when rejecting a submission.
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Di Bawah Kanan Halaman Pop-up: Request Resubmit dan Completed as Rejected --}}
                                    <div id="rejectedOptionsWrapper" class="d-flex justify-content-end align-items-center gap-2 mt-auto pt-3 border-top flex-wrap">
                                        <button type="button"
                                                id="btnSubmitResubmit"
                                                class="btn btn-warning text-dark px-3.5 py-2 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm"
                                                style="border-radius: 8px; font-size: 13px;">
                                            <i class="ti ti-refresh fs-2"></i>
                                            <span id="btnSubmitResubmitText">Request Resubmit</span>
                                        </button>
                                        <button type="button"
                                                id="btnSubmitCompletedRejected"
                                                class="btn btn-danger px-3.5 py-2 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm"
                                                style="border-radius: 8px; font-size: 13px;">
                                            <i class="ti ti-circle-x fs-2"></i>
                                            <span id="btnSubmitCompletedRejectedText">Completed as Rejected</span>
                                        </button>
                                    </div>
                                </div>

                                {{-- Hidden input keeping score synced for form / tests compatibility --}}
                                <input type="hidden" name="score" id="modalScoreInput" value="">

                                {{-- Hidden buttons kept for test compatibility & programmatic submission --}}
                                <button type="submit" id="modalSubmitBtn" class="d-none">Submit</button>
                                <button type="button" id="modalResubmitBtn" class="d-none">Request Resubmit</button>

                                <div id="defaultCancelWrapper" class="d-none"></div>
                            </form>
                        </div>
                    </div>
        </div>
    </div>
</div>
</div>
</div>

{{-- ── Fullscreen Pop-up Modal for Submission File Preview ── --}}
<div class="modal fade" id="fileFullscreenModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 100000;">
    <div class="modal-dialog modal-fullscreen" role="document">
        <div class="modal-content border-0 rounded-0 bg-dark text-white d-flex flex-column h-100">
            {{-- Fullscreen Header Bar --}}
            <div class="modal-header py-2.5 px-4 bg-dark border-bottom border-dark-subtle d-flex align-items-center justify-content-between flex-shrink-0" style="border-color: #334155 !important;">
                <div class="d-flex align-items-center gap-2.5 min-w-0">
                    <span class="avatar avatar-xs bg-primary text-white rounded" id="fsModalIconWrap">
                        <i class="ti ti-file" id="fsModalIcon"></i>
                    </span>
                    <div class="min-w-0">
                        <h5 class="modal-title fw-bold text-white text-truncate mb-0" id="fsModalTitle" style="font-size: 14px; max-width: 500px;">File Preview</h5>
                        <div class="text-secondary small" style="font-size: 11px;" id="fsModalSubtitle">Document Preview</div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <a href="#" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-light px-2.5 py-1 text-decoration-none" id="fsModalOpenTabBtn" title="Open in new tab">
                        <i class="ti ti-external-link me-1"></i> Open in new tab
                    </a>
                    <a href="#" download class="btn btn-sm btn-outline-light px-2.5 py-1 text-decoration-none" id="fsModalDownloadBtn" title="Download file">
                        <i class="ti ti-download me-1"></i> Download
                    </a>
                    <button type="button" class="btn btn-sm btn-icon btn-dark text-white rounded-circle ms-2" data-bs-dismiss="modal" aria-label="Close" title="Close Fullscreen (Esc)" style="width: 32px; height: 32px; border: 1px solid #475569;">
                        <i class="ti ti-x fs-2"></i>
                    </button>
                </div>
            </div>

            {{-- Fullscreen Canvas Body --}}
            <div class="modal-body p-0 d-flex align-items-center justify-content-center overflow-auto position-relative flex-grow-1" id="fsModalBody" style="background-color: #0b1120;">
                {{-- Dynamically populated: <img>, <iframe>, or <video> --}}
            </div>
        </div>
    </div>
</div>

{{-- ── Confirmation Modal Before Submitting Grading (Approve) ── --}}
<div class="modal modal-blur fade" id="reviewConfirmModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 99999;">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 12px; right: 12px; z-index: 10;"></button>
            <div id="confirmModalStatus" class="modal-status bg-success"></div>
            <div class="modal-body text-center py-4 px-3">
                <div id="confirmModalIconWrapper" class="avatar avatar-lg bg-success-lt text-success rounded-circle mb-3 d-inline-flex align-items-center justify-content-center shadow-xs" style="width: 56px; height: 56px;">
                    <i id="confirmModalIcon" class="ti ti-check fs-1"></i>
                </div>
                <h4 class="fw-bold text-dark mb-1" id="confirmModalTitle">Approve Submission</h4>
                <p class="text-muted small mb-3" id="confirmModalMessage">
                    Are you sure you want to approve this submission?
                </p>
                <div class="p-2.5 rounded-3 bg-light text-start border mb-3" id="confirmModalSummary" style="font-size: 12px;">
                    <!-- Filled dynamically -->
                </div>
                <div id="confirmModalWarning" class="small fw-semibold text-danger" style="font-size: 11.5px;">
                    <i class="ti ti-lock me-1"></i>Validation cannot be changed once approved.
                </div>
            </div>
            <div class="modal-footer bg-light px-3 py-2 border-top d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal" id="btnCancelConfirmModal">
                    Cancel
                </button>
                <button type="button" class="btn btn-success btn-sm px-3 d-inline-flex align-items-center gap-1.5 shadow-xs" id="btnConfirmModalSubmit">
                    <i class="ti ti-check" id="btnConfirmModalSubmitIcon"></i>
                    <span id="btnConfirmModalSubmitText">Approve</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Rejection Modal: Request Resubmit OR Completed as Rejected ── --}}
<div class="modal modal-blur fade" id="reviewRejectModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 99999;">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 520px;">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-status bg-danger"></div>
            <div class="modal-header border-bottom py-3 px-4 bg-light-subtle d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="avatar avatar-sm bg-danger-lt text-danger rounded-circle d-inline-flex align-items-center justify-content-center shadow-xs">
                        <i class="ti ti-alert-triangle fs-2"></i>
                    </div>
                    <h4 class="modal-title fw-bold text-dark mb-0">Reject Submission</h4>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="btnCloseRejectModal"></button>
            </div>

            <div class="modal-body p-4">
                {{-- User Summary Info --}}
                <div class="p-2.5 rounded-3 bg-light border mb-3 d-flex align-items-center justify-content-between" style="font-size: 12px;">
                    <div>
                        <span class="text-muted d-block" style="font-size: 11px;">User</span>
                        <strong class="text-dark" id="rejectModalLearnerName">-</strong>
                    </div>
                    <div class="text-end">
                        <span class="text-muted d-block" style="font-size: 11px;">Submitted At</span>
                        <span class="text-dark fw-medium" id="rejectModalSubmittedAt">-</span>
                    </div>
                </div>

                {{-- Feedback / Revision Notes --}}
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark mb-1 d-flex justify-content-between align-items-center" style="font-size: 12.5px;">
                        <span>Feedback / Revision Notes <span class="text-danger">*</span></span>
                        <span class="badge bg-danger-lt text-danger fw-semibold" style="font-size: 10px; letter-spacing: 0.3px;">REQUIRED</span>
                    </label>
                    <textarea class="form-control"
                              id="rejectModalFeedbackInput"
                              rows="3"
                              placeholder="Enter feedback or revision notes for the user..."
                              style="font-size: 12.5px; resize: vertical;"></textarea>
                    <div class="invalid-feedback" id="rejectModalFeedbackError" style="font-size: 11.5px;">
                        Feedback or revision notes are required when rejecting a submission.
                    </div>
                </div>

                {{-- Rejected Action Buttons (Direct buttons, no card wrappers) --}}
                <div id="rejectedOptionsWrapperModal" class="d-flex gap-2.5 pt-1">
                    <button type="button"
                            id="btnSubmitResubmitModal"
                            class="btn btn-warning text-dark flex-fill py-2.5 fw-semibold d-inline-flex align-items-center justify-content-center gap-2 shadow-xs"
                            style="border-radius: 8px; font-size: 13px;">
                        <i class="ti ti-refresh fs-2"></i>
                        <span>Request Resubmit</span>
                    </button>
                    <button type="button"
                            id="btnSubmitCompletedRejectedModal"
                            class="btn btn-danger flex-fill py-2.5 fw-semibold d-inline-flex align-items-center justify-content-center gap-2 shadow-xs"
                            style="border-radius: 8px; font-size: 13px;">
                        <i class="ti ti-circle-x fs-2"></i>
                        <span>Completed as Rejected</span>
                    </button>
                </div>
            </div>

            <div class="modal-footer bg-light px-4 py-2.5 border-top d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal" id="btnCancelRejectModal">
                    Cancel
                </button>
                <div class="text-muted" style="font-size: 11.5px;">
                    <i class="ti ti-lock me-1"></i>Validation cannot be changed once processed.
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- 4. INTERACTION SCRIPTS: Workstation, Zoom/Pan, Bulk Complete          --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
<script>
(function () {
    // HTML entity escaping helper to safely interpolate strings in confirmation modals
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    var submissionsData = @json($submissionsJson);
    var defaultActiveId = {{ $defaultActiveId }};
    var currentSubmissionIndex = 0;
    var currentZoom = 1;
    var currentRotation = 0;
    var isDragging = false;
    var startX = 0, startY = 0;
    var translateX = 0, translateY = 0;

    // ── Open / Switch Views (Table View with Review Modal Popup) ──
    window.openWorkstation = function (submissionId) {
        try {
            if (submissionId !== undefined && submissionId !== null) {
                var targetId = parseInt(submissionId, 10);
                var targetIdx = submissionsData.findIndex(function (s) { return s.id === targetId; });
                if (targetIdx !== -1) {
                    setActiveSubmissionByIndex(targetIdx);
                }
            } else if (submissionsData.length > 0) {
                if (currentSubmissionIndex < 0 || currentSubmissionIndex >= submissionsData.length) {
                    setActiveSubmissionByIndex(0);
                }
            }
        } catch (err) {
            console.error('Error setting active submission in openWorkstation:', err);
        }

        var modalEl = document.getElementById('reviewSubmissionModal');
        if (modalEl) {
            var BsModal = getBootstrapModal();
            if (BsModal) {
                try {
                    if (typeof BsModal.getOrCreateInstance === 'function') {
                        BsModal.getOrCreateInstance(modalEl).show();
                    } else {
                        var inst = BsModal.getInstance ? BsModal.getInstance(modalEl) : null;
                        (inst || new BsModal(modalEl)).show();
                    }
                } catch (e) {
                    try { (new BsModal(modalEl)).show(); } catch (e2) {}
                }
            } else if (typeof $ !== 'undefined' && $.fn && typeof $.fn.modal === 'function') {
                $(modalEl).modal('show');
            }
        }
    };

    window.closeWorkstation = function () {
        var modalEl = document.getElementById('reviewSubmissionModal');
        if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var instance = bootstrap.Modal.getInstance(modalEl);
            if (instance) instance.hide();
        }
    };

    window.showWorkstationView = function (submissionId) {
        window.openWorkstation(submissionId);
    };

    window.showTableView = function () {
        window.closeWorkstation();
    };

    // ── Zoom & Pan Transform Engine ─────────────────────────────────
    function getActiveZoomWrapper() {
        var activePane = document.querySelector('.submission-pane:not(.d-none)');
        if (!activePane) return null;
        return activePane.querySelector('.preview-img-container img') || activePane.querySelector('#filePreviewZoomWrapper');
    }

    function getActivePreviewContainer() {
        var activePane = document.querySelector('.submission-pane:not(.d-none)');
        if (!activePane) return null;
        return activePane.querySelector('.preview-img-container') || activePane.querySelector('#filePreviewContent');
    }

    function applyTransform() {
        var wrapper = getActiveZoomWrapper();
        if (!wrapper) return;
        wrapper.style.transform = 'translate(' + translateX + 'px, ' + translateY + 'px) scale(' + currentZoom + ') rotate(' + currentRotation + 'deg)';
        var zoomLevelSpan = document.getElementById('previewZoomLevel');
        if (zoomLevelSpan) {
            zoomLevelSpan.textContent = Math.round(currentZoom * 100) + '%';
        }
    }

    function resetZoomAndPan() {
        currentZoom = 1;
        currentRotation = 0;
        translateX = 0;
        translateY = 0;
        applyTransform();
        var container = getActivePreviewContainer();
        if (container) {
            container.style.cursor = 'zoom-in';
            container.classList.remove('is-dragging');
        }
    }

    function updateCursor() {
        var container = getActivePreviewContainer();
        if (!container) return;
        if (currentZoom > 1.05) {
            container.style.cursor = isDragging ? 'grabbing' : 'grab';
        } else {
            container.style.cursor = 'zoom-in';
        }
    }

    // Attach Mouse Events to all file preview containers
    function initCanvasInteractions() {
        document.querySelectorAll('.preview-img-container, #filePreviewContent').forEach(function (container) {
            // Direct Mouse Wheel Zoom
            container.addEventListener('wheel', function (e) {
                e.preventDefault();
                var delta = e.deltaY < 0 ? 0.2 : -0.2;
                currentZoom = Math.min(5, Math.max(0.25, Math.round((currentZoom + delta) * 100) / 100));
                applyTransform();
                updateCursor();
            }, { passive: false });

            // Mouse Drag & Pan
            container.addEventListener('mousedown', function (e) {
                if (e.button !== 0) return; // Only left click
                if (currentZoom > 1.0) {
                    isDragging = true;
                    startX = e.clientX - translateX;
                    startY = e.clientY - translateY;
                    container.classList.add('is-dragging');
                    container.style.cursor = 'grabbing';
                }
            });

            // Double Click Quick Toggle (100% <-> 200%)
            container.addEventListener('dblclick', function (e) {
                e.preventDefault();
                if (currentZoom > 1.05) {
                    resetZoomAndPan();
                } else {
                    currentZoom = 2;
                    translateX = 0;
                    translateY = 0;
                    applyTransform();
                    updateCursor();
                }
            });
        });

        window.addEventListener('mousemove', function (e) {
            if (!isDragging) return;
            translateX = e.clientX - startX;
            translateY = e.clientY - startY;
            applyTransform();
        });

        window.addEventListener('mouseup', function () {
            if (isDragging) {
                isDragging = false;
                var container = getActivePreviewContainer();
                if (container) {
                    container.classList.remove('is-dragging');
                    updateCursor();
                }
            }
        });
    }

    initCanvasInteractions();

    // ── Fullscreen Pop-up Modal Functionality ───────────────────────
    window.openFullscreenModal = function (fileUrl, previewUrl, fileName, fileExt, fileKind, fileSize) {
        var modalEl = document.getElementById('fileFullscreenModal');
        if (!modalEl) return;

        var titleEl = document.getElementById('fsModalTitle');
        var subtitleEl = document.getElementById('fsModalSubtitle');
        var openTabBtn = document.getElementById('fsModalOpenTabBtn');
        var downloadBtn = document.getElementById('fsModalDownloadBtn');
        var bodyEl = document.getElementById('fsModalBody');
        var iconEl = document.getElementById('fsModalIcon');

        if (titleEl) titleEl.textContent = fileName || 'File Preview';
        if (subtitleEl) subtitleEl.textContent = (fileExt ? fileExt.toUpperCase() : '') + ' ' + (fileKind || 'File') + (fileSize ? ' • ' + fileSize : '');
        if (openTabBtn) openTabBtn.href = previewUrl || fileUrl;
        if (downloadBtn) {
            downloadBtn.href = fileUrl;
            downloadBtn.setAttribute('download', fileName || 'submission');
        }

        var ext = (fileExt || '').toLowerCase();
        var isImg = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'].includes(ext);
        var isPdf = (ext === 'pdf');
        var isVid = ['mp4', 'webm', 'ogg', 'mov', 'mkv'].includes(ext);
        var isAud = ['mp3', 'wav', 'm4a', 'aac'].includes(ext);
        var isDoc = ['doc', 'docx', 'xls', 'xlsx', 'csv'].includes(ext);
        var isTxt = ['txt', 'log', 'json', 'xml', 'sql', 'md'].includes(ext);

        if (iconEl) {
            iconEl.className = 'ti ' + (isImg ? 'ti-photo' : (isPdf ? 'ti-file-type-pdf' : (isDoc ? 'ti-file-description' : (isVid ? 'ti-video' : (isAud ? 'ti-music' : 'ti-file')))));
        }

        bodyEl.innerHTML = '';
        if (isImg) {
            bodyEl.className = 'modal-body p-0 d-flex align-items-center justify-content-center overflow-auto position-relative flex-grow-1';
            bodyEl.style.height = 'calc(100vh - 54px)';
            var img = document.createElement('img');
            img.src = fileUrl;
            img.alt = fileName;
            img.className = 'img-fluid rounded shadow-lg';
            img.style.maxHeight = '92vh';
            img.style.maxWidth = '95vw';
            img.style.objectFit = 'contain';
            img.style.backgroundColor = '#1e293b';
            bodyEl.appendChild(img);
        } else if (isPdf || isDoc || isTxt) {
            bodyEl.className = 'modal-body p-0 overflow-hidden position-relative flex-grow-1 d-flex flex-column';
            bodyEl.style.height = 'calc(100vh - 54px)';
            var iframe = document.createElement('iframe');
            iframe.src = previewUrl || fileUrl;
            iframe.style.width = '100%';
            iframe.style.height = '100%';
            iframe.style.border = 'none';
            iframe.style.flex = '1 1 auto';
            iframe.title = fileName;
            bodyEl.appendChild(iframe);
        } else if (isVid) {
            bodyEl.className = 'modal-body p-0 d-flex align-items-center justify-content-center overflow-auto position-relative flex-grow-1';
            bodyEl.style.height = 'calc(100vh - 54px)';
            var video = document.createElement('video');
            video.src = fileUrl;
            video.controls = true;
            video.autoplay = true;
            video.style.maxWidth = '95vw';
            video.style.maxHeight = '90vh';
            bodyEl.appendChild(video);
        } else if (isAud) {
            bodyEl.className = 'modal-body p-0 d-flex align-items-center justify-content-center overflow-auto position-relative flex-grow-1';
            bodyEl.style.height = 'calc(100vh - 54px)';
            var audio = document.createElement('audio');
            audio.src = fileUrl;
            audio.controls = true;
            audio.style.width = '80%';
            audio.style.maxWidth = '600px';
            bodyEl.appendChild(audio);
        } else {
            bodyEl.className = 'modal-body p-0 d-flex align-items-center justify-content-center overflow-auto position-relative flex-grow-1';
            bodyEl.style.height = 'calc(100vh - 54px)';
            bodyEl.innerHTML = '<div class="text-center p-5 text-white">' +
                '<i class="ti ti-file fs-1 d-block mb-3"></i>' +
                '<h4>' + escapeHtml(fileName || 'File') + '</h4>' +
                '<p class="text-secondary">Fullscreen preview is not directly available for this format.</p>' +
                '<a href="' + fileUrl + '" download="' + escapeHtml(fileName || 'file') + '" class="btn btn-primary mt-2"><i class="ti ti-download me-1"></i> Download File</a>' +
                '</div>';
        }

        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    };

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-open-fullscreen');
        if (btn) {
            e.preventDefault();
            var fileUrl = btn.getAttribute('data-file-url');
            var previewUrl = btn.getAttribute('data-preview-url') || fileUrl;
            var fileName = btn.getAttribute('data-file-name') || '';
            var fileExt = btn.getAttribute('data-file-ext') || '';
            var fileKind = btn.getAttribute('data-file-kind') || '';
            var fileSize = btn.getAttribute('data-file-size') || '';
            openFullscreenModal(fileUrl, previewUrl, fileName, fileExt, fileKind, fileSize);
        }
    });

    var fsModalEl = document.getElementById('fileFullscreenModal');
    if (fsModalEl) {
        fsModalEl.addEventListener('hidden.bs.modal', function () {
            var bodyEl = document.getElementById('fsModalBody');
            if (bodyEl) bodyEl.innerHTML = '';
        });
    }

    // ── Active Submission Switcher ──────────────────────────────────
    function setActiveSubmissionByIndex(idx) {
        if (!submissionsData || submissionsData.length === 0) return;
        if (idx < 0) idx = 0;
        if (idx >= submissionsData.length) idx = submissionsData.length - 1;

        currentSubmissionIndex = idx;
        var sub = submissionsData[idx];

        // 1. Update Header Nav & Learner Name
        var headerLearner = document.getElementById('headerLearnerName');
        var headerCount = document.getElementById('headerSubmissionCount');
        var prevBtn = document.getElementById('prevSubmissionBtn');
        var nextBtn = document.getElementById('nextSubmissionBtn');

        if (headerLearner) headerLearner.textContent = sub.learner_name;
        if (headerCount) headerCount.textContent = 'Submission ' + (idx + 1) + ' of ' + submissionsData.length;
        if (prevBtn) prevBtn.disabled = (idx === 0);
        if (nextBtn) nextBtn.disabled = (idx === submissionsData.length - 1);

        // 2. Sidebar active state
        document.querySelectorAll('.learner-item').forEach(function (item) {
            var itemId = parseInt(item.getAttribute('data-submission-id'));
            if (itemId === sub.id) {
                item.classList.add('active');
                item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            } else {
                item.classList.remove('active');
            }
        });

        // 3. Switch Canvas Pane
        document.querySelectorAll('.submission-pane').forEach(function (pane) {
            var paneId = parseInt(pane.getAttribute('data-submission-id'));
            if (paneId === sub.id) {
                pane.classList.remove('d-none');
            } else {
                pane.classList.add('d-none');
            }
        });

        resetZoomAndPan();

        // 4. Right Grading Panel Details
        var panelName = document.getElementById('panelLearnerName');
        var panelEmail = document.getElementById('panelLearnerEmail');
        var panelDate = document.getElementById('panelSubmittedAt');
        var panelStatus = document.getElementById('panelStatusBadge');
        var scoreInput = document.getElementById('modalScoreInput');
        var feedbackInput = document.getElementById('modalFeedbackInput');
        var form = document.getElementById('reviewSubmissionForm');
        var completeUrlHidden = document.getElementById('modalCompleteUrl');
        var resubmitUrlHidden = document.getElementById('modalResubmitUrl');
        var rejectUrlHidden = document.getElementById('modalRejectUrl');

        if (panelName) panelName.textContent = sub.learner_name;
        if (panelEmail) panelEmail.textContent = sub.learner_email;
        if (panelDate) panelDate.textContent = sub.submitted_at;

        var isApproved = (sub.status === 'completed');
        var isRejected = (sub.status === 'rejected' || sub.resubmit_requested);
        var isGraded = isApproved || isRejected;

        if (panelStatus) {
            if (isApproved) {
                panelStatus.className = 'badge bg-success-lt text-success';
                panelStatus.textContent = 'Approved';
            } else if (sub.resubmit_requested) {
                panelStatus.className = 'badge bg-warning-lt text-warning';
                panelStatus.textContent = 'Rejected (Resubmit)';
            } else if (sub.status === 'rejected') {
                panelStatus.className = 'badge bg-danger-lt text-danger';
                panelStatus.textContent = 'Completed as Rejected';
            } else {
                panelStatus.className = 'badge bg-warning text-dark';
                panelStatus.textContent = 'Pending Review';
            }
        }

        if (scoreInput) {
            scoreInput.value = (sub.score !== null && sub.score !== undefined) ? sub.score : '';
            scoreInput.disabled = isGraded;
            scoreInput.readOnly = isGraded;
        }

        if (feedbackInput) {
            feedbackInput.value = sub.feedback || '';
            feedbackInput.disabled = isGraded;
            feedbackInput.readOnly = isGraded;
            feedbackInput.classList.remove('is-invalid');
        }

        var feedbackBadge = document.getElementById('feedbackRequiredBadge');
        if (feedbackBadge) {
            feedbackBadge.classList.add('d-none');
        }

        if (completeUrlHidden) completeUrlHidden.value = sub.complete_url;
        if (resubmitUrlHidden) resubmitUrlHidden.value = sub.resubmit_url;
        if (rejectUrlHidden) rejectUrlHidden.value = sub.reject_url;

        // 5. Grading Controls State (Jika sudah di-grading tidak bisa di-grading lagi)
        var actionButtonsContainer = document.getElementById('actionButtonsContainer');
        var alreadyGradedBanner = document.getElementById('alreadyGradedBanner');
        var gradedBannerIcon = document.getElementById('gradedBannerIcon');
        var gradedBannerTitle = document.getElementById('gradedBannerTitle');
        var gradedBannerDesc = document.getElementById('gradedBannerDesc');
        var btnActionApprove = document.getElementById('btnActionApprove');
        var btnActionApproveText = document.getElementById('btnActionApproveText');
        var btnActionReject = document.getElementById('btnActionReject');

        if (isGraded) {
            if (actionButtonsContainer) actionButtonsContainer.classList.add('d-none');
            var approveSecGraded = document.getElementById('gradingApproveSection');
            var rejectSecGraded = document.getElementById('gradingRejectSection');
            var decisionHintGraded = document.getElementById('gradingDecisionHint');
            if (approveSecGraded) {
                approveSecGraded.classList.add('d-none');
                approveSecGraded.classList.remove('d-flex');
            }
            if (rejectSecGraded) {
                rejectSecGraded.classList.add('d-none');
                rejectSecGraded.classList.remove('d-flex');
            }
            if (decisionHintGraded) decisionHintGraded.classList.add('d-none');
            var defaultCancelGraded = document.getElementById('defaultCancelWrapper');
            if (defaultCancelGraded) defaultCancelGraded.classList.add('d-none');
            if (alreadyGradedBanner) {
                alreadyGradedBanner.classList.remove('d-none');
                if (isApproved) {
                    alreadyGradedBanner.className = 'p-3 rounded-3 border mb-3 alert-success border-success-subtle bg-success-lt';
                    if (gradedBannerIcon) gradedBannerIcon.className = 'ti ti-circle-check fs-2 text-success flex-shrink-0';
                    if (gradedBannerTitle) {
                        gradedBannerTitle.className = 'fw-bold text-success mb-0';
                        gradedBannerTitle.textContent = 'Submission Approved';
                    }
                    if (gradedBannerDesc) {
                        gradedBannerDesc.className = 'text-muted small mt-1';
                        gradedBannerDesc.textContent = 'This submission has already been approved and cannot be re-validated.';
                    }
                } else if (sub.resubmit_requested) {
                    alreadyGradedBanner.className = 'p-3 rounded-3 border mb-3 alert-warning border-warning-subtle bg-warning-lt';
                    if (gradedBannerIcon) gradedBannerIcon.className = 'ti ti-refresh fs-2 text-warning flex-shrink-0';
                    if (gradedBannerTitle) {
                        gradedBannerTitle.className = 'fw-bold text-warning mb-0';
                        gradedBannerTitle.textContent = 'Resubmit Requested';
                    }
                    if (gradedBannerDesc) {
                        gradedBannerDesc.className = 'text-muted small mt-1';
                        gradedBannerDesc.textContent = 'Revision notes were sent. Waiting for the user to resubmit answers.';
                    }
                } else {
                    alreadyGradedBanner.className = 'p-3 rounded-3 border mb-3 alert-danger border-danger-subtle bg-danger-lt';
                    if (gradedBannerIcon) gradedBannerIcon.className = 'ti ti-circle-x fs-2 text-danger flex-shrink-0';
                    if (gradedBannerTitle) {
                        gradedBannerTitle.className = 'fw-bold text-danger mb-0';
                        gradedBannerTitle.textContent = 'Completed as Rejected';
                    }
                    if (gradedBannerDesc) {
                        gradedBannerDesc.className = 'text-muted small mt-1';
                        gradedBannerDesc.textContent = 'This submission was marked as completed as rejected and cannot be re-validated.';
                    }
                }
            }
        } else {
            // Pending review - Allow grading
            if (alreadyGradedBanner) alreadyGradedBanner.classList.add('d-none');
            if (actionButtonsContainer) actionButtonsContainer.classList.remove('d-none');

            var approveSec = document.getElementById('gradingApproveSection');
            var rejectSec = document.getElementById('gradingRejectSection');
            var decisionHint = document.getElementById('gradingDecisionHint');
            if (approveSec) {
                approveSec.classList.add('d-none');
                approveSec.classList.remove('d-flex');
            }
            if (rejectSec) {
                rejectSec.classList.add('d-none');
                rejectSec.classList.remove('d-flex');
            }
            if (decisionHint) decisionHint.classList.remove('d-none');
            var defaultCancelActive = document.getElementById('defaultCancelWrapper');
            if (defaultCancelActive) defaultCancelActive.classList.remove('d-none');

            var scoreApp = document.getElementById('modalScoreInputApprove');
            var scoreRej = document.getElementById('modalScoreInputReject');
            if (scoreApp) scoreApp.value = '';
            if (scoreRej) scoreRej.value = '';
            if (scoreInput) scoreInput.value = '';
            if (feedbackInput) {
                feedbackInput.value = '';
                feedbackInput.classList.remove('is-invalid');
            }

            if (typeof resetGradingMode === 'function') {
                resetGradingMode();
            } else {
                var selDec = document.getElementById('selectGradingDecision');
                if (selDec) {
                    selDec.value = '';
                    selDec.className = 'form-select fw-semibold shadow-xs';
                    selDec.style.borderColor = '#cbd5e1';
                }
            }
        }

        if (typeof hideRejectModal === 'function') hideRejectModal();
        if (typeof hideConfirmModal === 'function') hideConfirmModal();
    }

    // ── Bootstrap Modal Helpers & Attach to body ─────────────────────
    function getBootstrapModal() {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            return bootstrap.Modal;
        }
        if (window.bootstrap && window.bootstrap.Modal) {
            return window.bootstrap.Modal;
        }
        return null;
    }

    // Confirmation Modal (Approve)
    var confirmModalEl = document.getElementById('reviewConfirmModal');
    var confirmModalInstance = null;
    var pendingSubmitCallback = null;

    if (confirmModalEl && confirmModalEl.parentNode !== document.body) {
        document.body.appendChild(confirmModalEl);
    }

    function showConfirmModal() {
        if (confirmModalEl && confirmModalEl.parentNode !== document.body) {
            document.body.appendChild(confirmModalEl);
        }

        var BsModal = getBootstrapModal();
        if (confirmModalEl && BsModal) {
            confirmModalInstance = BsModal.getInstance(confirmModalEl) || new BsModal(confirmModalEl);
            confirmModalInstance.show();
        } else if (confirmModalEl && typeof $ !== 'undefined' && $.fn && $.fn.modal) {
            $(confirmModalEl).modal('show');
        } else {
            var msg = document.getElementById('confirmModalMessage')?.textContent || 'Confirm submission?';
            if (confirm(msg)) {
                if (typeof pendingSubmitCallback === 'function') {
                    pendingSubmitCallback();
                }
            }
        }
    }

    function hideConfirmModal() {
        var BsModal = getBootstrapModal();
        if (confirmModalInstance) {
            confirmModalInstance.hide();
        } else if (confirmModalEl && BsModal) {
            var inst = BsModal.getInstance(confirmModalEl);
            if (inst) inst.hide();
        } else if (confirmModalEl && typeof $ !== 'undefined' && $.fn && $.fn.modal) {
            $(confirmModalEl).modal('hide');
        }
    }

    var btnCancelConfirmModal = document.getElementById('btnCancelConfirmModal');
    if (btnCancelConfirmModal) {
        btnCancelConfirmModal.addEventListener('click', function () {
            hideConfirmModal();
        });
    }

    var btnConfirmModalSubmit = document.getElementById('btnConfirmModalSubmit');
    if (btnConfirmModalSubmit) {
        btnConfirmModalSubmit.addEventListener('click', function () {
            if (typeof pendingSubmitCallback === 'function') {
                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Processing...';
                pendingSubmitCallback();
            }
        });
    }

    // Rejection Modal (Request Resubmit / Completed as Rejected)
    var rejectModalEl = document.getElementById('reviewRejectModal');
    var rejectModalInstance = null;

    if (rejectModalEl && rejectModalEl.parentNode !== document.body) {
        document.body.appendChild(rejectModalEl);
    }

    function showRejectModal() {
        if (rejectModalEl && rejectModalEl.parentNode !== document.body) {
            document.body.appendChild(rejectModalEl);
        }

        var BsModal = getBootstrapModal();
        if (rejectModalEl && BsModal) {
            rejectModalInstance = BsModal.getInstance(rejectModalEl) || new BsModal(rejectModalEl);
            rejectModalInstance.show();
        } else if (rejectModalEl && typeof $ !== 'undefined' && $.fn && $.fn.modal) {
            $(rejectModalEl).modal('show');
        }
    }

    function hideRejectModal() {
        var BsModal = getBootstrapModal();
        if (rejectModalInstance) {
            rejectModalInstance.hide();
        } else if (rejectModalEl && BsModal) {
            var inst = BsModal.getInstance(rejectModalEl);
            if (inst) inst.hide();
        } else if (rejectModalEl && typeof $ !== 'undefined' && $.fn && $.fn.modal) {
            $(rejectModalEl).modal('hide');
        }
    }

    var btnCancelRejectModal = document.getElementById('btnCancelRejectModal');
    if (btnCancelRejectModal) {
        btnCancelRejectModal.addEventListener('click', function () {
            hideRejectModal();
        });
    }

    // Two-way feedback synchronization between modal & right sidebar
    var rejectFeedbackInput = document.getElementById('rejectModalFeedbackInput');
    var sidebarFeedbackInput = document.getElementById('modalFeedbackInput');
    var rejectFeedbackError = document.getElementById('rejectModalFeedbackError');

    if (rejectFeedbackInput && sidebarFeedbackInput) {
        rejectFeedbackInput.addEventListener('input', function () {
            sidebarFeedbackInput.value = this.value;
            if (this.value.trim()) {
                this.classList.remove('is-invalid');
                if (rejectFeedbackError) {
                    rejectFeedbackError.classList.add('d-none');
                    rejectFeedbackError.style.display = 'none';
                }
            }
        });
        sidebarFeedbackInput.addEventListener('input', function () {
            rejectFeedbackInput.value = this.value;
            if (this.value.trim() && rejectFeedbackInput) {
                rejectFeedbackInput.classList.remove('is-invalid');
                if (rejectFeedbackError) {
                    rejectFeedbackError.classList.add('d-none');
                    rejectFeedbackError.style.display = 'none';
                }
            }
        });
    }

    // ── Score Inputs Synchronization ──
    var scoreInputApprove = document.getElementById('modalScoreInputApprove');
    var scoreInputReject = document.getElementById('modalScoreInputReject');
    var scoreInputHidden = document.getElementById('modalScoreInput');

    function syncScoreInputs(val) {
        if (scoreInputHidden) scoreInputHidden.value = val;
        if (scoreInputApprove && scoreInputApprove.value !== val) scoreInputApprove.value = val;
        if (scoreInputReject && scoreInputReject.value !== val) scoreInputReject.value = val;
    }

    if (scoreInputApprove) {
        scoreInputApprove.addEventListener('input', function () {
            syncScoreInputs(this.value);
        });
    }
    if (scoreInputReject) {
        scoreInputReject.addEventListener('input', function () {
            syncScoreInputs(this.value);
        });
    }

    var sidebarFeedback = document.getElementById('modalFeedbackInput');
    if (sidebarFeedback) {
        sidebarFeedback.addEventListener('input', function () {
            if (this.value.trim()) {
                this.classList.remove('is-invalid');
            }
        });
    }

    // ── Toggle Modes: Approve vs Rejected at Top ──
    window.resetGradingMode = function () {
        var approveSec = document.getElementById('gradingApproveSection');
        var rejectSec = document.getElementById('gradingRejectSection');
        var decisionHint = document.getElementById('gradingDecisionHint');
        var defaultCancel = document.getElementById('defaultCancelWrapper');
        var selectDecision = document.getElementById('selectGradingDecision');

        if (approveSec) {
            approveSec.classList.add('d-none');
            approveSec.classList.remove('d-flex');
        }
        if (rejectSec) {
            rejectSec.classList.add('d-none');
            rejectSec.classList.remove('d-flex');
        }
        if (decisionHint) decisionHint.classList.remove('d-none');
        if (defaultCancel) defaultCancel.classList.remove('d-none');

        if (selectDecision) {
            selectDecision.value = '';
            selectDecision.className = 'form-select fw-semibold shadow-xs';
            selectDecision.style.borderColor = '#cbd5e1';
        }

        var scoreApp = document.getElementById('modalScoreInputApprove');
        var scoreRej = document.getElementById('modalScoreInputReject');
        var hiddenScore = document.getElementById('modalScoreInput');
        var feedbackInput = document.getElementById('modalFeedbackInput');
        if (scoreApp) scoreApp.value = '';
        if (scoreRej) scoreRej.value = '';
        if (hiddenScore) hiddenScore.value = '';
        if (feedbackInput) {
            feedbackInput.value = '';
            feedbackInput.classList.remove('is-invalid');
        }
    };

    window.selectApproveMode = function () {
        var sub = submissionsData[currentSubmissionIndex];
        if (!sub) return;
        if (sub.status === 'completed' || sub.status === 'rejected' || sub.resubmit_requested) {
            alert('This submission has already been validated and cannot be changed.');
            return;
        }

        var selectDecision = document.getElementById('selectGradingDecision');
        if (selectDecision) {
            selectDecision.value = 'approve';
            selectDecision.className = 'form-select fw-semibold shadow-xs border-success text-success bg-success-lt';
            selectDecision.style.borderColor = '';
        }

        var approveSec = document.getElementById('gradingApproveSection');
        var rejectSec = document.getElementById('gradingRejectSection');
        var decisionHint = document.getElementById('gradingDecisionHint');

        if (approveSec) {
            approveSec.classList.remove('d-none');
            approveSec.classList.add('d-flex');
        }
        if (rejectSec) {
            rejectSec.classList.add('d-none');
            rejectSec.classList.remove('d-flex');
        }
        if (decisionHint) decisionHint.classList.add('d-none');
        var defaultCancel = document.getElementById('defaultCancelWrapper');
        if (defaultCancel) defaultCancel.classList.add('d-none');

        var scoreInputApprove = document.getElementById('modalScoreInputApprove');
        if (scoreInputApprove) {
            setTimeout(function () { scoreInputApprove.focus(); }, 120);
        }
    };

    window.selectRejectMode = function () {
        var sub = submissionsData[currentSubmissionIndex];
        if (!sub) return;
        if (sub.status === 'completed' || sub.status === 'rejected' || sub.resubmit_requested) {
            alert('This submission has already been validated and cannot be changed.');
            return;
        }

        var selectDecision = document.getElementById('selectGradingDecision');
        if (selectDecision) {
            selectDecision.value = 'rejected';
            selectDecision.className = 'form-select fw-semibold shadow-xs border-danger text-danger bg-danger-lt';
            selectDecision.style.borderColor = '';
        }

        var approveSec = document.getElementById('gradingApproveSection');
        var rejectSec = document.getElementById('gradingRejectSection');
        var decisionHint = document.getElementById('gradingDecisionHint');

        if (rejectSec) {
            rejectSec.classList.remove('d-none');
            rejectSec.classList.add('d-flex');
        }
        if (approveSec) {
            approveSec.classList.add('d-none');
            approveSec.classList.remove('d-flex');
        }
        if (decisionHint) decisionHint.classList.add('d-none');
        var defaultCancel2 = document.getElementById('defaultCancelWrapper');
        if (defaultCancel2) defaultCancel2.classList.add('d-none');

        var feedbackInput = document.getElementById('modalFeedbackInput');
        if (feedbackInput) {
            setTimeout(function () { feedbackInput.focus(); }, 120);
        }
    };

    // 1. Dropdown Decision Event
    var selectGradingDecision = document.getElementById('selectGradingDecision');
    if (selectGradingDecision) {
        selectGradingDecision.addEventListener('change', function () {
            if (this.value === 'approve') {
                selectApproveMode();
            } else if (this.value === 'rejected') {
                selectRejectMode();
            } else {
                resetGradingMode();
            }
        });
    }

    // Compatibility triggers for automated tests / legacy calls
    var btnActionApprove = document.getElementById('btnActionApprove');
    if (btnActionApprove) {
        btnActionApprove.addEventListener('click', function () {
            selectApproveMode();
        });
    }

    var btnActionReject = document.getElementById('btnActionReject');
    if (btnActionReject) {
        btnActionReject.addEventListener('click', function () {
            selectRejectMode();
        });
    }

    // 2. Confirm Approve Action (Inside Approve Section)
    var btnConfirmApproveAction = document.getElementById('btnConfirmApproveAction');
    if (btnConfirmApproveAction) {
        btnConfirmApproveAction.addEventListener('click', function () {
            var sub = submissionsData[currentSubmissionIndex];
            if (!sub) return;
            if (sub.status === 'completed' || sub.status === 'rejected' || sub.resubmit_requested) {
                alert('This submission has already been validated and cannot be changed.');
                return;
            }

            var scoreApp = document.getElementById('modalScoreInputApprove');
            var scoreVal = scoreApp ? scoreApp.value.trim() : '';

            var confirmStatus = document.getElementById('confirmModalStatus');
            var confirmIconWrapper = document.getElementById('confirmModalIconWrapper');
            var confirmIcon = document.getElementById('confirmModalIcon');
            var confirmTitle = document.getElementById('confirmModalTitle');
            var confirmMsg = document.getElementById('confirmModalMessage');
            var confirmSummary = document.getElementById('confirmModalSummary');
            var confirmWarning = document.getElementById('confirmModalWarning');
            var confirmBtn = document.getElementById('btnConfirmModalSubmit');
            var confirmBtnIcon = document.getElementById('btnConfirmModalSubmitIcon');
            var confirmBtnText = document.getElementById('btnConfirmModalSubmitText');

            if (confirmStatus) confirmStatus.className = 'modal-status bg-success';
            if (confirmIconWrapper) confirmIconWrapper.className = 'avatar avatar-lg bg-success-lt text-success rounded-circle mb-3 d-inline-flex align-items-center justify-content-center shadow-xs';
            if (confirmIcon) confirmIcon.className = 'ti ti-check fs-1';
            if (confirmTitle) confirmTitle.textContent = 'Approve Submission';
            if (confirmMsg) confirmMsg.textContent = 'Are you sure you want to approve this user\'s submission?';

            if (confirmSummary) {
                confirmSummary.innerHTML =
                    '<div class="d-flex justify-content-between mb-1.5"><span class="text-muted">User:</span><strong class="text-dark">' + escapeHtml(sub.learner_name) + '</strong></div>' +
                    '<div class="d-flex justify-content-between mb-1.5"><span class="text-muted">Score:</span><strong class="text-dark">' + (scoreVal !== '' ? scoreVal + ' / 100' : '<span class="text-muted fw-normal fst-italic">None (optional)</span>') + '</strong></div>';
            }

            if (confirmWarning) {
                confirmWarning.className = 'small fw-semibold text-danger';
                confirmWarning.innerHTML = '<i class="ti ti-lock me-1"></i>Validation cannot be changed once approved.';
            }

            if (confirmBtn) {
                confirmBtn.className = 'btn btn-success btn-sm px-3 d-inline-flex align-items-center gap-1.5 shadow-xs';
                confirmBtn.disabled = false;
            }
            if (confirmBtnIcon) confirmBtnIcon.className = 'ti ti-check';
            if (confirmBtnText) confirmBtnText.textContent = 'Submit';

            pendingSubmitCallback = function () {
                var completeUrl = sub.complete_url || document.getElementById('modalCompleteUrl').value;
                var payload = {
                    score: scoreVal,
                    feedback: '',
                };

                var confirmBtn = document.getElementById('btnConfirmModalSubmit');
                if (confirmBtn) {
                    confirmBtn.disabled = true;
                    confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Approving...';
                }

                submitGradingAjax(completeUrl, payload, function () {
                    hideConfirmModal();
                    if (confirmBtn) {
                        confirmBtn.disabled = false;
                        confirmBtn.innerHTML = '<i class="ti ti-check" id="btnConfirmModalSubmitIcon"></i> <span id="btnConfirmModalSubmitText">Submit</span>';
                    }
                });
            };

            showConfirmModal();
        });
    }

    // 3. Request Resubmit Action (Inside Rejected Section)
    var btnSubmitResubmit = document.getElementById('btnSubmitResubmit');
    if (btnSubmitResubmit) {
        btnSubmitResubmit.addEventListener('click', function () {
            var sub = submissionsData[currentSubmissionIndex];
            if (!sub) return;
            if (sub.status === 'completed' || sub.status === 'rejected' || sub.resubmit_requested) {
                alert('This submission has already been validated and cannot be changed.');
                return;
            }

            var feedbackInput = document.getElementById('modalFeedbackInput');
            var feedbackVal = feedbackInput ? feedbackInput.value.trim() : '';

            if (!feedbackVal) {
                if (feedbackInput) {
                    feedbackInput.classList.add('is-invalid');
                    feedbackInput.focus();
                }
                var errorDiv = document.getElementById('feedbackInvalidFeedback');
                if (errorDiv) {
                    errorDiv.textContent = 'Feedback or revision notes are required when requesting a resubmit.';
                }
                return;
            }

            if (feedbackInput) feedbackInput.classList.remove('is-invalid');

            var btn = this;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Submitting...';

            var rejectUrl = sub.reject_url || document.getElementById('modalRejectUrl').value;
            if (!rejectUrl) {
                rejectUrl = sub.resubmit_url || document.getElementById('modalResubmitUrl').value;
            }

            var payload = {
                reject_type: 'resubmit',
                feedback: feedbackVal,
                reason: feedbackVal,
            };

            submitGradingAjax(rejectUrl, payload, function () {
                btn.disabled = false;
                btn.innerHTML = '<i class="ti ti-refresh fs-2"></i> <span id="btnSubmitResubmitText">Request Resubmit</span>';
            });
        });
    }

    // 4. Completed as Rejected Action (Inside Rejected Section)
    var btnSubmitCompletedRejected = document.getElementById('btnSubmitCompletedRejected');
    if (btnSubmitCompletedRejected) {
        btnSubmitCompletedRejected.addEventListener('click', function () {
            var sub = submissionsData[currentSubmissionIndex];
            if (!sub) return;
            if (sub.status === 'completed' || sub.status === 'rejected' || sub.resubmit_requested) {
                alert('This submission has already been validated and cannot be changed.');
                return;
            }

            var feedbackInput = document.getElementById('modalFeedbackInput');
            var feedbackVal = feedbackInput ? feedbackInput.value.trim() : '';
            var scoreInputRej = document.getElementById('modalScoreInputReject');
            var scoreVal = scoreInputRej ? scoreInputRej.value.trim() : '';

            if (!feedbackVal) {
                if (feedbackInput) {
                    feedbackInput.classList.add('is-invalid');
                    feedbackInput.focus();
                }
                var errorDiv = document.getElementById('feedbackInvalidFeedback');
                if (errorDiv) {
                    errorDiv.textContent = 'Feedback or reason is required when completing as rejected.';
                }
                return;
            }

            if (feedbackInput) feedbackInput.classList.remove('is-invalid');

            var btn = this;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Submitting...';

            var rejectUrl = sub.reject_url || document.getElementById('modalRejectUrl').value;
            var payload = {
                reject_type: 'completed_rejected',
                feedback: feedbackVal,
                score: scoreVal,
            };

            submitGradingAjax(rejectUrl, payload, function () {
                btn.disabled = false;
                btn.innerHTML = '<i class="ti ti-circle-x fs-2"></i> <span id="btnSubmitCompletedRejectedText">Completed as Rejected</span>';
            });
        });
    }

    // Also support fallback buttons in reviewRejectModal if invoked
    var btnSubmitResubmitModal = document.getElementById('btnSubmitResubmitModal');
    if (btnSubmitResubmitModal) {
        btnSubmitResubmitModal.addEventListener('click', function () {
            var rejectFeedback = document.getElementById('rejectModalFeedbackInput');
            if (rejectFeedback && sidebarFeedback) {
                sidebarFeedback.value = rejectFeedback.value;
            }
            if (btnSubmitResubmit) btnSubmitResubmit.click();
            hideRejectModal();
        });
    }
    var btnSubmitCompletedRejectedModal = document.getElementById('btnSubmitCompletedRejectedModal');
    if (btnSubmitCompletedRejectedModal) {
        btnSubmitCompletedRejectedModal.addEventListener('click', function () {
            var rejectFeedback = document.getElementById('rejectModalFeedbackInput');
            if (rejectFeedback && sidebarFeedback) {
                sidebarFeedback.value = rejectFeedback.value;
            }
            if (btnSubmitCompletedRejected) btnSubmitCompletedRejected.click();
            hideRejectModal();
        });
    }

    // Programmatic resubmit compatibility for tests
    var modalResubmitBtn = document.getElementById('modalResubmitBtn');
    if (modalResubmitBtn) {
        modalResubmitBtn.addEventListener('click', function () {
            var sub = submissionsData[currentSubmissionIndex];
            if (!sub) return;
            var feedbackInput = document.getElementById('modalFeedbackInput');
            var feedbackVal = feedbackInput ? feedbackInput.value.trim() : '';
            var form = document.getElementById('reviewSubmissionForm');
            var rejectTypeHidden = document.getElementById('modalRejectTypeHiddenInput');
            if (rejectTypeHidden) rejectTypeHidden.value = 'resubmit';
            var reasonHidden = document.getElementById('modalReasonHiddenInput');
            if (reasonHidden) reasonHidden.value = feedbackVal;
            var rejectUrl = sub.reject_url || document.getElementById('modalRejectUrl').value;
            if (!rejectUrl) rejectUrl = sub.resubmit_url || document.getElementById('modalResubmitUrl').value;
            form.action = rejectUrl;
            var scoreInput = document.getElementById('modalScoreInput');
            if (scoreInput) scoreInput.disabled = true;
            form.submit();
        });
    }

    // Sidebar items click inside workstation
    document.querySelectorAll('.learner-item').forEach(function (item) {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            var targetId = parseInt(this.getAttribute('data-submission-id'));
            var targetIdx = submissionsData.findIndex(function (s) { return s.id === targetId; });
            if (targetIdx !== -1) {
                setActiveSubmissionByIndex(targetIdx);
            }
        });
    });

    // Header Prev / Next Buttons
    var prevBtn = document.getElementById('prevSubmissionBtn');
    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            if (currentSubmissionIndex > 0) {
                setActiveSubmissionByIndex(currentSubmissionIndex - 1);
            }
        });
    }

    var nextBtn = document.getElementById('nextSubmissionBtn');
    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            if (currentSubmissionIndex < submissionsData.length - 1) {
                setActiveSubmissionByIndex(currentSubmissionIndex + 1);
            }
        });
    }

    // Keyboard Navigation (ESC to close, Left/Right arrow to switch in modal)
    window.addEventListener('keydown', function (e) {
        var modalEl = document.getElementById('reviewSubmissionModal');
        var isModalOpen = modalEl && modalEl.classList.contains('show');

        if (!isModalOpen) return;

        // Skip arrows if user is typing in input or textarea
        if (['INPUT', 'TEXTAREA'].indexOf(document.activeElement.tagName) !== -1) return;

        if (e.key === 'ArrowLeft' && currentSubmissionIndex > 0) {
            e.preventDefault();
            setActiveSubmissionByIndex(currentSubmissionIndex - 1);
        } else if (e.key === 'ArrowRight' && currentSubmissionIndex < submissionsData.length - 1) {
            e.preventDefault();
            setActiveSubmissionByIndex(currentSubmissionIndex + 1);
        }
    });

    var form = document.getElementById('reviewSubmissionForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            var sub = submissionsData[currentSubmissionIndex];
            if (!sub) return;
            if (!this.action || this.action === '' || this.action === window.location.href) {
                var completeUrl = sub.complete_url || document.getElementById('modalCompleteUrl').value;
                this.action = completeUrl;
            }
        });
    }

    var feedbackInput = document.getElementById('modalFeedbackInput');
    if (feedbackInput) {
        feedbackInput.addEventListener('input', function () {
            if (this.value.trim() !== '') {
                this.classList.remove('is-invalid');
            }
        });
    }


    // ── AJAX Grading & Auto-Advance Engine ──────────────────────────
    function submitGradingAjax(actionUrl, payload, onFinish) {
        var csrfToken = document.querySelector('meta[name="csrf-token"]')
            ? document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            : (document.querySelector('input[name="_token"]') ? document.querySelector('input[name="_token"]').value : '');

        var formData = new FormData();
        formData.append('_token', csrfToken);
        for (var key in payload) {
            if (payload.hasOwnProperty(key) && payload[key] !== null && payload[key] !== undefined) {
                formData.append(key, payload[key]);
            }
        }

        fetch(actionUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(function (res) {
            return res.json().then(function (data) {
                return { status: res.status, ok: res.ok, data: data };
            });
        })
        .then(function (result) {
            if (!result.ok || !result.data.success) {
                var errMsg = (result.data && result.data.message) ? result.data.message : 'Failed to submit validation.';
                alert(errMsg);
                if (onFinish) onFinish(false);
                return;
            }

            var resData = result.data;
            var sub = submissionsData[currentSubmissionIndex];
            if (sub && resData.submission) {
                var prevStatus = sub.status;
                var prevResubmit = sub.resubmit_requested;

                sub.status = resData.submission.status;
                sub.resubmit_requested = Boolean(resData.submission.resubmit_requested);
                sub.score = resData.submission.score;
                sub.feedback = resData.submission.feedback || '';

                // 1. Update the table row in #submissionsTable
                var row = document.querySelector('.submission-table-row[data-submission-id="' + sub.id + '"]');
                if (row) {
                    var newRowStatus = 'need_review';
                    if (sub.status === 'completed') {
                        newRowStatus = 'approve';
                    } else if (sub.status === 'rejected' || sub.resubmit_requested) {
                        newRowStatus = 'rejected';
                    }
                    row.setAttribute('data-status', newRowStatus);

                    var statusTd = row.querySelector('.submission-row-status');
                    if (statusTd) {
                        if (sub.status === 'completed') {
                            statusTd.innerHTML = '<span class="badge bg-success-lt text-success"><i class="ti ti-check me-1"></i>Approve</span>';
                        } else if (sub.resubmit_requested) {
                            statusTd.innerHTML = '<span class="badge bg-warning-lt text-warning"><i class="ti ti-refresh me-1"></i>Rejected (Resubmit)</span>';
                        } else if (sub.status === 'rejected') {
                            statusTd.innerHTML = '<span class="badge bg-danger-lt text-danger"><i class="ti ti-x me-1"></i>Completed as Rejected</span>';
                        }
                    }

                    var scoreTd = row.querySelector('.submission-row-score');
                    if (scoreTd) {
                        if (sub.score !== null && sub.score !== undefined && sub.score !== '') {
                            scoreTd.innerHTML = '<span class="badge bg-primary-lt text-primary fs-5 fw-bold">' + sub.score + '</span>';
                        } else {
                            scoreTd.innerHTML = '<span class="text-muted">—</span>';
                        }
                    }
                }

                // 2. Update pill badges
                updatePillBadges(prevStatus, prevResubmit, sub.status, sub.resubmit_requested);

                // 3. Re-render active submission view to show graded status
                setActiveSubmissionByIndex(currentSubmissionIndex);

                // 4. Auto-advance to next student needing review!
                var nextPendingIdx = findNextPendingSubmissionIndex(currentSubmissionIndex);
                if (nextPendingIdx !== -1) {
                    showAutoAdvanceToast('Evaluated ' + sub.learner_name + '! Advancing to next user...');
                    setTimeout(function () {
                        setActiveSubmissionByIndex(nextPendingIdx);
                    }, 600);
                } else {
                    showAutoAdvanceToast('All submissions evaluated! 🎉');
                }
            }

            if (onFinish) onFinish(true);
        })
        .catch(function (err) {
            console.error('AJAX grading failed, falling back:', err);
            // Fallback: standard form submit
            var form = document.getElementById('reviewSubmissionForm');
            if (form) {
                form.action = actionUrl;
                form.submit();
            }
        });
    }

    function findNextPendingSubmissionIndex(startIdx) {
        if (!submissionsData || submissionsData.length === 0) return -1;
        for (var i = startIdx + 1; i < submissionsData.length; i++) {
            if (submissionsData[i].status === 'pending' && !submissionsData[i].resubmit_requested) {
                return i;
            }
        }
        for (var j = 0; j < startIdx; j++) {
            if (submissionsData[j].status === 'pending' && !submissionsData[j].resubmit_requested) {
                return j;
            }
        }
        return -1;
    }

    function updatePillBadges(prevStatus, prevResubmit, newStatus, newResubmit) {
        var prevCat = (prevStatus === 'pending' && !prevResubmit) ? 'need_review' : (prevStatus === 'completed' ? 'approve' : 'rejected');
        var newCat = (newStatus === 'pending' && !newResubmit) ? 'need_review' : (newStatus === 'completed' ? 'approve' : 'rejected');

        if (prevCat === newCat) return;

        var pillPrev = document.querySelector('#submissionQuickFilters [data-filter="' + prevCat + '"] .badge');
        var pillNew = document.querySelector('#submissionQuickFilters [data-filter="' + newCat + '"] .badge');

        if (pillPrev) {
            var c1 = parseInt(pillPrev.textContent) || 0;
            pillPrev.textContent = Math.max(0, c1 - 1);
        }
        if (pillNew) {
            var c2 = parseInt(pillNew.textContent) || 0;
            pillNew.textContent = c2 + 1;
        }
    }

    function showAutoAdvanceToast(msg) {
        var toastEl = document.getElementById('gradingToast');
        var toastText = document.getElementById('gradingToastText');
        if (!toastEl || !toastText) return;
        toastText.textContent = msg;
        toastEl.classList.remove('d-none');
        setTimeout(function () {
            toastEl.classList.add('d-none');
        }, 2500);
    }

    // ── Table Quick Filter (All, Need Review, Approve, Rejected) + Search ──
    var currentFilterStatus = '{{ $activeFilter }}';
    var filterPills = document.querySelectorAll('#submissionQuickFilters .my-task-category-pill');
    var tableRows = document.querySelectorAll('.submission-table-row');
    var noFilteredRow = document.getElementById('noFilteredSubmissionsRow');
    var searchInput = document.getElementById('responsesSearchInput');

    function applyTableFilter() {
        var query = searchInput ? searchInput.value.trim().toLowerCase() : '';
        var visibleCount = 0;

        tableRows.forEach(function (row) {
            var rowStatus = row.getAttribute('data-status');
            var userName = row.getAttribute('data-user-name') || '';
            var userEmail = row.getAttribute('data-user-email') || '';

            var matchesStatus = (currentFilterStatus === 'all' || rowStatus === currentFilterStatus);
            var matchesSearch = (!query || userName.indexOf(query) !== -1 || userEmail.indexOf(query) !== -1);

            if (matchesStatus && matchesSearch) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (noFilteredRow) {
            if (visibleCount === 0 && tableRows.length > 0) {
                noFilteredRow.classList.remove('d-none');
            } else {
                noFilteredRow.classList.add('d-none');
            }
        }
    }

    filterPills.forEach(function (pill) {
        pill.addEventListener('click', function () {
            var targetFilter = this.getAttribute('data-filter') || 'all';

            // If page was loaded with a server query param that filtered the rows,
            // navigate to the filtered URL so server supplies the requested rows.
            var hasServerFilter = '{{ request('status') }}' !== '' && '{{ request('status') }}' !== 'all';
            if (hasServerFilter && targetFilter !== '{{ $activeFilter }}') {
                var navUrl = new URL(window.location);
                if (targetFilter === 'all') {
                    navUrl.searchParams.delete('status');
                } else {
                    navUrl.searchParams.set('status', targetFilter);
                }
                window.location.href = navUrl.toString();
                return;
            }

            filterPills.forEach(function (p) { p.classList.remove('active'); });
            this.classList.add('active');
            currentFilterStatus = targetFilter;

            if (window.history && window.history.replaceState) {
                var url = new URL(window.location);
                if (currentFilterStatus === 'all') {
                    url.searchParams.delete('status');
                } else {
                    url.searchParams.set('status', currentFilterStatus);
                }
                window.history.replaceState({}, '', url);
            }

            applyTableFilter();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            applyTableFilter();
        });
    }

    if (currentFilterStatus !== 'all') {
        applyTableFilter();
    }

    // Set initial active state if data exists
    if (submissionsData.length > 0) {
        var initialIdx = submissionsData.findIndex(function (s) { return s.id === defaultActiveId; });
        if (initialIdx === -1) initialIdx = 0;
        setActiveSubmissionByIndex(initialIdx);
    }
})();
</script>
@endsection
