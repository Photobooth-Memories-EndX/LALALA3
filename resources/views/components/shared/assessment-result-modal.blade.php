@php
    $modalResult = $assesment_result ?? null;
    $modalAssessment = $assesment ?? ($modalResult->assesment ?? null);
    
    $score = (int) ($modalResult->score ?? 0);
    $passingGrade = (int) ($modalAssessment->passing_grade ?? 0);
    $isPassed = (bool) ($modalResult->pass ?? ($score >= $passingGrade));
    
    $rawType = strtolower((string) ($modalAssessment->type ?? 'content'));
    $assessmentType = match ($rawType) {
        'content' => 'Content Assessment',
        'trainingplan', 'playlist' => 'Playlist Assessment',
        'global' => 'Global Assessment',
        default => !empty($modalAssessment->type) ? ucwords(str_replace('_', ' ', $modalAssessment->type)) . ' Assessment' : 'Assessment',
    };

    $isGlobal = ($rawType === 'global');
    $isExpiredNotice = session('is_expired_autosubmit') || !empty($isExpiredAutoSubmit);

    $fromDisplay = (!empty($modalAssessment->fromdate)) ? date('d M Y, H:i', strtotime($modalAssessment->fromdate)) : null;
    $toDisplay   = (!empty($modalAssessment->todate)) ? date('d M Y, H:i', strtotime($modalAssessment->todate)) : null;

    $periodDisplay = '-';
    if ($fromDisplay && $toDisplay) {
        $periodDisplay = $fromDisplay . ' - ' . $toDisplay;
    } elseif ($fromDisplay) {
        $periodDisplay = 'From ' . $fromDisplay;
    } elseif ($toDisplay) {
        $periodDisplay = 'Until ' . $toDisplay;
    }
@endphp

<div class="modal fade" id="assessmentResultModal" tabindex="-1" role="dialog" aria-labelledby="assessmentResultModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden assessment-modal-shell assessment-result-shell">
            <div class="modal-header border-0 pb-0 pt-4 px-4 position-relative">
                <div class="d-flex align-items-center gap-3">
                    <div id="assessmentResultModalIconBox" class="p-2 rounded-3 {{ $isPassed ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} d-flex align-items-center justify-content-center assessment-result-icon-box" style="width: 48px; height: 48px;">
                        <i id="assessmentResultModalIcon" class="{{ $isPassed ? 'ti ti-trophy' : 'ti ti-alert-circle' }} fs-2"></i>
                    </div>
                    <div>
                        <h4 class="modal-title fw-bold mb-0 text-dark assessment-result-title" id="assessmentResultModalTitle">Assessment Result</h4>
                        <div class="text-muted small assessment-modal-subtitle">Summary Information</div>
                    </div>
                </div>
                <button type="button" class="btn-close ms-auto assessment-result-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <div id="assessmentResultModalExpiredNotice" class="alert alert-warning border-0 rounded-3 mb-3 p-3 {{ $isExpiredNotice ? 'd-flex' : 'd-none' }} align-items-start gap-3 assessment-result-expired-alert">
                    <i class="ti ti-clock-off fs-2 text-warning flex-shrink-0 mt-1 assessment-result-expired-icon"></i>
                    <div>
                        <strong class="d-block text-dark fw-bold mb-1 assessment-result-expired-title">Assessment Time Expired</strong>
                        <span class="small text-muted assessment-result-expired-text">Your assessment time has expired and your answers have been submitted automatically.</span>
                    </div>
                </div>

                <div class="card border-0 bg-light rounded-4 p-4 text-center mb-3 assessment-score-card">
                    <div class="text-muted small text-uppercase fw-semibold mb-1 assessment-score-label">Your Score</div>
                    <div id="assessmentResultModalScore" class="{{ $isPassed ? 'text-success' : 'text-danger' }} display-4 fw-extrabold mb-1 assessment-score-value" style="font-size: 3.5rem; line-height: 1; font-weight: 800;">
                        {{ $score }}
                    </div>
                    <div id="assessmentResultModalBadgeContainer" class="mt-2">
                        @if ($isPassed)
                            <span class="badge bg-success-subtle text-success px-3 py-2 fs-6 rounded-pill fw-bold assessment-result-badge">
                                <i class="ti ti-check me-1"></i>Passed
                            </span>
                        @else
                            <span class="badge bg-danger-subtle text-danger px-3 py-2 fs-6 rounded-pill fw-bold assessment-result-badge">
                                <i class="ti ti-x me-1"></i>Failed
                            </span>
                        @endif
                    </div>
                    <div class="text-muted small mt-2 assessment-passing-grade-text">
                        Passing Grade: <strong id="assessmentResultModalPassingGrade" class="assessment-passing-grade-value">{{ $passingGrade }}</strong>
                    </div>
                </div>

                <div class="row g-2">
                    <div class="col-12">
                        <div class="p-3 rounded-3 bg-light assessment-result-item">
                            <div class="text-muted small assessment-result-item-label">Assessment Title</div>
                            <div class="fw-bold text-dark assessment-result-item-value" id="assessmentResultModalName">{{ $modalAssessment->title ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="p-3 rounded-3 bg-light assessment-result-item">
                            <div class="text-muted small assessment-result-item-label">Type</div>
                            <div class="fw-semibold text-dark assessment-result-item-value" id="assessmentResultModalType">{{ $assessmentType }}</div>
                        </div>
                    </div>

                    <div class="col-6 {{ $isGlobal ? '' : 'd-none' }}" id="assessmentResultModalBatchRow">
                        <div class="p-3 rounded-3 bg-light assessment-result-item">
                            <div class="text-muted small assessment-result-item-label">Batch</div>
                            <div class="fw-semibold text-dark assessment-result-item-value" id="assessmentResultModalBatch">{{ $modalResult->batch ?? $modalAssessment->batch ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="col-6 {{ !$isGlobal && !empty($currentAttempt) && !empty($maxAttempts) ? '' : 'd-none' }}" id="assessmentResultModalAttemptRow">
                        <div class="p-3 rounded-3 bg-light assessment-result-item">
                            <div class="text-muted small assessment-result-item-label">Attempt</div>
                            <div class="fw-semibold text-dark assessment-result-item-value" id="assessmentResultModalAttempt">{{ $currentAttempt ?? 1 }} / {{ $maxAttempts ?? 1 }}</div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="p-3 rounded-3 bg-light assessment-result-item">
                            <div class="text-muted small assessment-result-item-label">Assessment Period</div>
                            <div class="fw-semibold text-dark assessment-result-item-value" id="assessmentResultModalPeriod">{{ $periodDisplay }}</div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="p-3 rounded-3 {{ $isPassed ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} assessment-result-status-card" id="assessmentResultModalStatusRow">
                            <div class="text-muted small assessment-result-status-label">Status</div>
                            <div class="fw-bold assessment-result-status-message" id="assessmentResultModalStatusMessage">
                                {{ $isPassed ? 'Congratulations, you have passed this test.' : 'Sorry, you have not passed this test.' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0 pb-4 px-4">
                <button type="button" class="btn btn-primary w-100 rounded-3 py-2 fw-semibold assessment-btn-close-action" data-bs-dismiss="modal" data-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* ==========================================================================
       Assessment Result Modal Theme Styling (Light & Dark Mode)
       ========================================================================== */
    .assessment-result-title {
        color: #1a1d20;
    }
    .assessment-result-item-label {
        color: #6c757d;
    }
    .assessment-result-item-value {
        color: #212529;
    }

    /* Dark Mode Overrides */
    [data-bs-theme="dark"] #assessmentResultModal .assessment-modal-shell,
    body.dark-mode #assessmentResultModal .assessment-modal-shell,
    body[data-bs-theme="dark"] #assessmentResultModal .assessment-modal-shell,
    [data-theme="dark"] #assessmentResultModal .assessment-modal-shell,
    body.theme-dark #assessmentResultModal .assessment-modal-shell {
        background-color: var(--tblr-bg-surface, #182433) !important;
        border: 1px solid rgba(255, 255, 255, 0.12) !important;
        box-shadow: 0 16px 36px rgba(0, 0, 0, 0.45) !important;
        color: var(--tblr-body-color, #fcfdfe) !important;
    }

    [data-bs-theme="dark"] #assessmentResultModal .assessment-result-title,
    body.dark-mode #assessmentResultModal .assessment-result-title,
    body[data-bs-theme="dark"] #assessmentResultModal .assessment-result-title,
    [data-theme="dark"] #assessmentResultModal .assessment-result-title,
    body.theme-dark #assessmentResultModal .assessment-result-title {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] #assessmentResultModal .assessment-modal-subtitle,
    body.dark-mode #assessmentResultModal .assessment-modal-subtitle,
    body[data-bs-theme="dark"] #assessmentResultModal .assessment-modal-subtitle,
    [data-theme="dark"] #assessmentResultModal .assessment-modal-subtitle,
    body.theme-dark #assessmentResultModal .assessment-modal-subtitle {
        color: #94a3b8 !important;
    }

    [data-bs-theme="dark"] #assessmentResultModal .assessment-result-close,
    body.dark-mode #assessmentResultModal .assessment-result-close,
    body[data-bs-theme="dark"] #assessmentResultModal .assessment-result-close,
    [data-theme="dark"] #assessmentResultModal .assessment-result-close,
    body.theme-dark #assessmentResultModal .assessment-result-close {
        filter: invert(1) grayscale(100%) brightness(200%);
        opacity: 0.8;
    }

    [data-bs-theme="dark"] #assessmentResultModal .assessment-result-close:hover,
    body.dark-mode #assessmentResultModal .assessment-result-close:hover,
    body[data-bs-theme="dark"] #assessmentResultModal .assessment-result-close:hover,
    [data-theme="dark"] #assessmentResultModal .assessment-result-close:hover,
    body.theme-dark #assessmentResultModal .assessment-result-close:hover {
        opacity: 1;
    }

    /* Icon Box */
    [data-bs-theme="dark"] #assessmentResultModal #assessmentResultModalIconBox.bg-success-subtle,
    body.dark-mode #assessmentResultModal #assessmentResultModalIconBox.bg-success-subtle,
    body[data-bs-theme="dark"] #assessmentResultModal #assessmentResultModalIconBox.bg-success-subtle,
    [data-theme="dark"] #assessmentResultModal #assessmentResultModalIconBox.bg-success-subtle,
    body.theme-dark #assessmentResultModal #assessmentResultModalIconBox.bg-success-subtle {
        background-color: rgba(40, 190, 88, 0.2) !important;
        color: #34d399 !important;
    }

    [data-bs-theme="dark"] #assessmentResultModal #assessmentResultModalIconBox.bg-danger-subtle,
    body.dark-mode #assessmentResultModal #assessmentResultModalIconBox.bg-danger-subtle,
    body[data-bs-theme="dark"] #assessmentResultModal #assessmentResultModalIconBox.bg-danger-subtle,
    [data-theme="dark"] #assessmentResultModal #assessmentResultModalIconBox.bg-danger-subtle,
    body.theme-dark #assessmentResultModal #assessmentResultModalIconBox.bg-danger-subtle {
        background-color: rgba(239, 51, 64, 0.2) !important;
        color: #f87171 !important;
    }

    /* Expired Notice */
    [data-bs-theme="dark"] #assessmentResultModal .assessment-result-expired-alert,
    body.dark-mode #assessmentResultModal .assessment-result-expired-alert,
    body[data-bs-theme="dark"] #assessmentResultModal .assessment-result-expired-alert,
    [data-theme="dark"] #assessmentResultModal .assessment-result-expired-alert,
    body.theme-dark #assessmentResultModal .assessment-result-expired-alert {
        background-color: rgba(255, 184, 28, 0.16) !important;
        border: 1px solid rgba(255, 184, 28, 0.35) !important;
        color: #fef08a !important;
    }

    [data-bs-theme="dark"] #assessmentResultModal .assessment-result-expired-icon,
    body.dark-mode #assessmentResultModal .assessment-result-expired-icon,
    body[data-bs-theme="dark"] #assessmentResultModal .assessment-result-expired-icon {
        color: #fbbf24 !important;
    }

    [data-bs-theme="dark"] #assessmentResultModal .assessment-result-expired-title,
    body.dark-mode #assessmentResultModal .assessment-result-expired-title,
    body[data-bs-theme="dark"] #assessmentResultModal .assessment-result-expired-title {
        color: #fef08a !important;
    }

    [data-bs-theme="dark"] #assessmentResultModal .assessment-result-expired-text,
    body.dark-mode #assessmentResultModal .assessment-result-expired-text,
    body[data-bs-theme="dark"] #assessmentResultModal .assessment-result-expired-text {
        color: #fef3c7 !important;
    }

    /* Score Card */
    [data-bs-theme="dark"] #assessmentResultModal .assessment-score-card,
    body.dark-mode #assessmentResultModal .assessment-score-card,
    body[data-bs-theme="dark"] #assessmentResultModal .assessment-score-card,
    [data-theme="dark"] #assessmentResultModal .assessment-score-card,
    body.theme-dark #assessmentResultModal .assessment-score-card {
        background-color: #1f2e42 !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
    }

    [data-bs-theme="dark"] #assessmentResultModal .assessment-score-label,
    body.dark-mode #assessmentResultModal .assessment-score-label,
    body[data-bs-theme="dark"] #assessmentResultModal .assessment-score-label,
    [data-theme="dark"] #assessmentResultModal .assessment-score-label,
    body.theme-dark #assessmentResultModal .assessment-score-label {
        color: #94a3b8 !important;
    }

    [data-bs-theme="dark"] #assessmentResultModal #assessmentResultModalScore.text-success,
    body.dark-mode #assessmentResultModal #assessmentResultModalScore.text-success,
    body[data-bs-theme="dark"] #assessmentResultModal #assessmentResultModalScore.text-success {
        color: #34d399 !important;
    }

    [data-bs-theme="dark"] #assessmentResultModal #assessmentResultModalScore.text-danger,
    body.dark-mode #assessmentResultModal #assessmentResultModalScore.text-danger,
    body[data-bs-theme="dark"] #assessmentResultModal #assessmentResultModalScore.text-danger {
        color: #f87171 !important;
    }

    [data-bs-theme="dark"] #assessmentResultModal .badge.bg-success-subtle,
    body.dark-mode #assessmentResultModal .badge.bg-success-subtle,
    body[data-bs-theme="dark"] #assessmentResultModal .badge.bg-success-subtle,
    [data-theme="dark"] #assessmentResultModal .badge.bg-success-subtle,
    body.theme-dark #assessmentResultModal .badge.bg-success-subtle {
        background-color: rgba(40, 190, 88, 0.22) !important;
        color: #34d399 !important;
        border: 1px solid rgba(40, 190, 88, 0.35) !important;
    }

    [data-bs-theme="dark"] #assessmentResultModal .badge.bg-danger-subtle,
    body.dark-mode #assessmentResultModal .badge.bg-danger-subtle,
    body[data-bs-theme="dark"] #assessmentResultModal .badge.bg-danger-subtle,
    [data-theme="dark"] #assessmentResultModal .badge.bg-danger-subtle,
    body.theme-dark #assessmentResultModal .badge.bg-danger-subtle {
        background-color: rgba(239, 51, 64, 0.22) !important;
        color: #f87171 !important;
        border: 1px solid rgba(239, 51, 64, 0.35) !important;
    }

    [data-bs-theme="dark"] #assessmentResultModal .assessment-passing-grade-text,
    body.dark-mode #assessmentResultModal .assessment-passing-grade-text,
    body[data-bs-theme="dark"] #assessmentResultModal .assessment-passing-grade-text {
        color: #94a3b8 !important;
    }

    [data-bs-theme="dark"] #assessmentResultModal .assessment-passing-grade-value,
    body.dark-mode #assessmentResultModal .assessment-passing-grade-value,
    body[data-bs-theme="dark"] #assessmentResultModal .assessment-passing-grade-value {
        color: #f1f5f9 !important;
    }

    /* Result Details Grid */
    [data-bs-theme="dark"] #assessmentResultModal .assessment-result-item,
    body.dark-mode #assessmentResultModal .assessment-result-item,
    body[data-bs-theme="dark"] #assessmentResultModal .assessment-result-item,
    [data-theme="dark"] #assessmentResultModal .assessment-result-item,
    body.theme-dark #assessmentResultModal .assessment-result-item {
        background-color: #1f2e42 !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
    }

    [data-bs-theme="dark"] #assessmentResultModal .assessment-result-item-label,
    body.dark-mode #assessmentResultModal .assessment-result-item-label,
    body[data-bs-theme="dark"] #assessmentResultModal .assessment-result-item-label,
    [data-theme="dark"] #assessmentResultModal .assessment-result-item-label,
    body.theme-dark #assessmentResultModal .assessment-result-item-label {
        color: #94a3b8 !important;
    }

    [data-bs-theme="dark"] #assessmentResultModal .assessment-result-item-value,
    body.dark-mode #assessmentResultModal .assessment-result-item-value,
    body[data-bs-theme="dark"] #assessmentResultModal .assessment-result-item-value,
    [data-theme="dark"] #assessmentResultModal .assessment-result-item-value,
    body.theme-dark #assessmentResultModal .assessment-result-item-value {
        color: #f1f5f9 !important;
    }

    /* Status Message Card */
    [data-bs-theme="dark"] #assessmentResultModal #assessmentResultModalStatusRow.bg-success-subtle,
    body.dark-mode #assessmentResultModal #assessmentResultModalStatusRow.bg-success-subtle,
    body[data-bs-theme="dark"] #assessmentResultModal #assessmentResultModalStatusRow.bg-success-subtle,
    [data-theme="dark"] #assessmentResultModal #assessmentResultModalStatusRow.bg-success-subtle,
    body.theme-dark #assessmentResultModal #assessmentResultModalStatusRow.bg-success-subtle {
        background-color: rgba(40, 190, 88, 0.18) !important;
        border: 1px solid rgba(40, 190, 88, 0.35) !important;
        color: #34d399 !important;
    }

    [data-bs-theme="dark"] #assessmentResultModal #assessmentResultModalStatusRow.bg-danger-subtle,
    body.dark-mode #assessmentResultModal #assessmentResultModalStatusRow.bg-danger-subtle,
    body[data-bs-theme="dark"] #assessmentResultModal #assessmentResultModalStatusRow.bg-danger-subtle,
    [data-theme="dark"] #assessmentResultModal #assessmentResultModalStatusRow.bg-danger-subtle,
    body.theme-dark #assessmentResultModal #assessmentResultModalStatusRow.bg-danger-subtle {
        background-color: rgba(239, 51, 64, 0.18) !important;
        border: 1px solid rgba(239, 51, 64, 0.35) !important;
        color: #f87171 !important;
    }

    [data-bs-theme="dark"] #assessmentResultModal .assessment-result-status-label,
    body.dark-mode #assessmentResultModal .assessment-result-status-label,
    body[data-bs-theme="dark"] #assessmentResultModal .assessment-result-status-label,
    [data-theme="dark"] #assessmentResultModal .assessment-result-status-label,
    body.theme-dark #assessmentResultModal .assessment-result-status-label {
        color: rgba(255, 255, 255, 0.75) !important;
    }

    [data-bs-theme="dark"] #assessmentResultModal #assessmentResultModalStatusRow.bg-success-subtle .assessment-result-status-message,
    body.dark-mode #assessmentResultModal #assessmentResultModalStatusRow.bg-success-subtle .assessment-result-status-message,
    body[data-bs-theme="dark"] #assessmentResultModal #assessmentResultModalStatusRow.bg-success-subtle .assessment-result-status-message {
        color: #34d399 !important;
    }

    [data-bs-theme="dark"] #assessmentResultModal #assessmentResultModalStatusRow.bg-danger-subtle .assessment-result-status-message,
    body.dark-mode #assessmentResultModal #assessmentResultModalStatusRow.bg-danger-subtle .assessment-result-status-message,
    body[data-bs-theme="dark"] #assessmentResultModal #assessmentResultModalStatusRow.bg-danger-subtle .assessment-result-status-message {
        color: #f87171 !important;
    }

    /* Footer Close Button */
    [data-bs-theme="dark"] #assessmentResultModal .assessment-btn-close-action,
    body.dark-mode #assessmentResultModal .assessment-btn-close-action,
    body[data-bs-theme="dark"] #assessmentResultModal .assessment-btn-close-action,
    [data-theme="dark"] #assessmentResultModal .assessment-btn-close-action,
    body.theme-dark #assessmentResultModal .assessment-btn-close-action {
        background-color: #0c5b9e !important;
        border-color: #0c5b9e !important;
        color: #ffffff !important;
    }

    [data-bs-theme="dark"] #assessmentResultModal .assessment-btn-close-action:hover,
    body.dark-mode #assessmentResultModal .assessment-btn-close-action:hover,
    body[data-bs-theme="dark"] #assessmentResultModal .assessment-btn-close-action:hover {
        background-color: #09477d !important;
        border-color: #09477d !important;
    }
</style>
