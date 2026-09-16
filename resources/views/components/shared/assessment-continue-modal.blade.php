@props(['assessment' => null, 'type' => 'content', 'answeredCount' => 0, 'totalCount' => 0, 'isContinue' => true])

@php
    $isAssessmentBlocked = false;
    if ($assessment) {
        $isInactiveProp = strtoupper(trim((string)($assessment->status ?? ''))) === 'NOT';
        $hasPeriod = method_exists($assessment, 'hasAccessPeriod') ? $assessment->hasAccessPeriod() : (!empty($assessment->has_access_period));
        $isExpiredProp = false;
        if ($hasPeriod && !empty($assessment->todate)) {
            $isExpiredProp = now()->format('Y-m-d H:i:s') > \Carbon\Carbon::parse($assessment->todate)->format('Y-m-d H:i:s');
        }
        $isAssessmentBlocked = $isInactiveProp || $isExpiredProp;
    }
@endphp

<div class="modal fade" id="continueAssessmentModal" tabindex="-1" role="dialog" aria-labelledby="continueAssessmentModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document" style="max-width: 480px !important; width: 100%;">
        <div class="modal-content rounded-4 border-0 shadow assessment-modal-shell">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="modal-title-icon d-inline-flex align-items-center justify-content-center rounded-circle p-2 assessment-continue-icon-box" 
                         id="continueModalHeaderIconBox"
                         style="width: 40px; height: 40px; background-color: rgba(12, 91, 158, 0.12); color: #0c5b9e;">
                        <i class="ti ti-player-play fs-2"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 assessment-continue-title" id="continueAssessmentModalTitle" style="color: #0c5b9e !important;">
                            Continue Assessment
                        </h5>
                        <div class="text-muted small assessment-modal-subtitle">Confirmation</div>
                    </div>
                </div>
                <button type="button" 
                        class="btn-close close modal-close-btn border-0 bg-transparent text-muted ms-auto assessment-modal-close" 
                        data-dismiss="modal"
                        data-bs-dismiss="modal" 
                        aria-label="Close">
                    <i class="ti ti-x fs-3"></i>
                </button>
            </div>
            <div class="modal-body px-4 py-3">
                <!-- INFORMATION SECTION -->
                <h6 class="fw-bold text-dark text-uppercase mb-2 assessment-modal-section-title" style="font-size: 0.85rem; letter-spacing: 0.5px;">
                    INFORMATION
                </h6>
                <div class="bg-light rounded-3 p-3 mb-3 assessment-info-card">
                    <!-- ASSESSMENT TITLE / CONTENT HEADER -->
                    <div class="assessment-title-wrapper mb-3 pb-2 border-bottom assessment-card-divider">
                        <div class="text-dark small fw-bold text-uppercase mb-1 assessment-card-badge" 
                             id="continueModalTypeHeader" 
                             style="font-size: 0.75rem; letter-spacing: 0.5px;">
                            {{ $type === 'global' ? 'ASSESSMENT GLOBAL' : 'ASSESSMENT CONTENT' }}
                        </div>
                        <h5 class="fw-bold text-dark mb-0 fs-5 assessment-card-name" 
                             id="continueModalName" 
                             style="font-size: 1.1rem !important; line-height: 1.4;">
                            {{ $assessment?->title ?? '-' }}
                        </h5>
                    </div>

                    <!-- DETAILS GRID -->
                    <div class="row g-2">
                        <!-- Date & Time (Stacked) -->
                        <div class="col-12 col-sm-6">
                            <strong class="text-dark d-block mb-1 assessment-card-label" style="font-size: 0.75rem;">DATE & TIME</strong>
                            <div class="text-dark small assessment-card-value" id="continueModalDateTime" style="line-height: 1.35;">
                                <div>
                                    {{ $assessment?->formatted_start_date ?? ($assessment?->fromdate ? date('d M Y, H:i', strtotime($assessment->fromdate)) : '-') }}
                                </div>
                                <div>
                                    {{ $assessment?->formatted_end_date ?? ($assessment?->todate ? date('d M Y, H:i', strtotime($assessment->todate)) : '-') }}
                                </div>
                            </div>
                        </div>

                        <!-- Passing Grade -->
                        <div class="col-6 col-sm-6">
                            <strong class="text-dark d-block mb-1 assessment-card-label" style="font-size: 0.75rem;">PASSING GRADE</strong>
                            <div class="text-dark fw-bold small assessment-card-value" id="continueModalPassingGrade">
                                {{ $assessment?->passing_grade ?? '-' }}
                            </div>
                        </div>

                        <!-- Duration -->
                        <div class="col-6 col-sm-6">
                            <strong class="text-dark d-block mb-1 assessment-card-label" style="font-size: 0.75rem;">DURATION</strong>
                            <div class="text-dark small assessment-card-value" id="continueModalDuration">
                                {{ $assessment ? ($assessment->duration ?? ($assessment->time_limit_minutes ?? 60)) : '-' }} Minutes
                            </div>
                        </div>

                        <!-- Batch (Global) atau Attempt (Content) -->
                        <div class="col-12 col-sm-6" id="continueModalBatchContainer" style="{{ $type === 'global' ? '' : 'display: none;' }}">
                            <strong class="text-dark d-block mb-1 assessment-card-label" style="font-size: 0.75rem;">BATCH</strong>
                            <div class="text-dark small assessment-card-value" id="continueModalBatch">
                                {{ $assessment ? ($assessment->batch_name ?? ($assessment->batch ? (str_contains(strtolower($assessment->batch), 'batch') ? $assessment->batch : 'Batch ' . $assessment->batch) : 'Batch 1')) : '-' }}
                            </div>
                        </div>
                        <div class="col-12 col-sm-6" id="continueModalAttemptContainer" style="{{ $type !== 'global' ? '' : 'display: none;' }}">
                            <strong class="text-dark d-block mb-1 assessment-card-label" style="font-size: 0.75rem;">ATTEMPT</strong>
                            <div class="text-dark small assessment-card-value" id="continueModalAttempt">
                                {{ $assessment ? ($assessment->current_attempt ?? 1) . ' / ' . ($assessment->max_attempts ?? 1) : '-' }}
                            </div>
                        </div>

                        <!-- Progress (Khusus Continue Modal) -->
                        <div class="col-12 border-top pt-2 mt-2 assessment-card-divider assessment-progress-row" id="continueModalProgressContainer">
                            <strong class="text-dark d-inline-block me-2 assessment-card-label" style="font-size: 0.75rem;">PROGRESS:</strong>
                            <span class="text-dark fw-bold small assessment-card-value" id="continueModalProgress">
                                {{ $answeredCount ?? 0 }} / {{ $totalCount ?? 10 }} answered
                            </span>
                        </div>
                    </div>
                </div>

                <!-- NOTICE SECTION -->
                <h6 class="fw-bold text-dark text-uppercase mb-2 assessment-modal-section-title" id="continueModalNoticeTitle" style="font-size: 0.85rem; letter-spacing: 0.5px;">
                    NOTICE
                </h6>
                <div id="continueModalNoticeWrapper">
                    <ol class="small text-dark ps-3 mb-0 assessment-modal-list" id="continueNoticeGlobal" style="{{ $type === 'global' ? '' : 'display: none;' }}">
                        <li>Make sure you are in a safe and conducive environment, and that your device is ready to use.</li>
                        <li><em>Once resumed, the assessment cannot be exited until it is completed or the time expires.</em></li>
                        <li>The assessment will take place according to the stated date & time.</li>
                        <li>If an error occurs on your device or the connection is interrupted, your answers will be saved. Re-enter the assessment to continue as long as time is still available.</li>
                    </ol>
                    <ol class="small text-dark ps-3 mb-0 assessment-modal-list" id="continueNoticeContent" style="{{ $type !== 'global' ? '' : 'display: none;' }}">
                        <li>Ensure yourself, your device, and your environment are in a safe and conducive condition.</li>
                        <li>Assessment will continue using the remaining available time.</li>
                        <li><em>Once resumed, the assessment cannot be exited until it is completed or time expires.</em></li>
                        <li>If an error or technical issue occurs, your answers are saved. Re-enter immediately to continue as long as time remains.</li>
                        <li>Perform your best by utilizing the available opportunity.</li>
                    </ol>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                <button type="button" class="btn btn-light rounded-pill px-4 text-secondary assessment-btn-cancel" data-dismiss="modal" data-bs-dismiss="modal">
                    Cancel
                </button>
                <a href="{{ ($assessment && !empty($assessment->slug) && !$isAssessmentBlocked) ? route('assesment.user.view', $assessment->slug) : '#' }}"
                   id="btnConfirmContinueAssessment"
                   class="btn rounded-pill px-4 fw-semibold text-white shadow-sm d-inline-flex align-items-center gap-1 assessment-btn-continue {{ $isAssessmentBlocked ? 'disabled' : '' }}"
                   @if($isAssessmentBlocked) disabled aria-disabled="true" tabindex="-1" style="background-color: #6c757d !important; border-color: #6c757d !important; color: #ffffff !important; cursor: not-allowed !important; pointer-events: none;" @else style="background-color: #0c5b9e !important; border-color: #0c5b9e !important; color: #ffffff !important;" @endif>
                    <i class="ti ti-player-play fs-6"></i> Continue Assessment
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    /* ==========================================================================
       Assessment Continue Modal Theme Styling (Light & Dark Mode)
       ========================================================================== */
    .assessment-modal-section-title {
        color: #000000;
    }
    .assessment-card-badge {
        color: #212529;
    }
    .assessment-card-name {
        color: #1a1d20;
    }
    .assessment-card-label {
        color: #212529;
    }
    .assessment-card-value {
        color: #212529;
    }
    .assessment-modal-list {
        color: #212529;
    }

    /* Dark Mode Overrides */
    [data-bs-theme="dark"] #continueAssessmentModal .assessment-modal-shell,
    body.dark-mode #continueAssessmentModal .assessment-modal-shell,
    body[data-bs-theme="dark"] #continueAssessmentModal .assessment-modal-shell,
    [data-theme="dark"] #continueAssessmentModal .assessment-modal-shell,
    body.theme-dark #continueAssessmentModal .assessment-modal-shell {
        background-color: var(--tblr-bg-surface, #182433) !important;
        border: 1px solid rgba(255, 255, 255, 0.12) !important;
        box-shadow: 0 16px 36px rgba(0, 0, 0, 0.45) !important;
        color: var(--tblr-body-color, #fcfdfe) !important;
    }

    [data-bs-theme="dark"] #continueAssessmentModal .assessment-continue-title,
    body.dark-mode #continueAssessmentModal .assessment-continue-title,
    body[data-bs-theme="dark"] #continueAssessmentModal .assessment-continue-title,
    [data-theme="dark"] #continueAssessmentModal .assessment-continue-title,
    body.theme-dark #continueAssessmentModal .assessment-continue-title {
        color: #38bdf8 !important;
    }

    [data-bs-theme="dark"] #continueAssessmentModal .assessment-continue-icon-box,
    body.dark-mode #continueAssessmentModal .assessment-continue-icon-box,
    body[data-bs-theme="dark"] #continueAssessmentModal .assessment-continue-icon-box,
    [data-theme="dark"] #continueAssessmentModal .assessment-continue-icon-box,
    body.theme-dark #continueAssessmentModal .assessment-continue-icon-box {
        background-color: rgba(56, 189, 248, 0.16) !important;
        color: #38bdf8 !important;
    }

    [data-bs-theme="dark"] #continueAssessmentModal .assessment-modal-subtitle,
    body.dark-mode #continueAssessmentModal .assessment-modal-subtitle,
    body[data-bs-theme="dark"] #continueAssessmentModal .assessment-modal-subtitle,
    [data-theme="dark"] #continueAssessmentModal .assessment-modal-subtitle,
    body.theme-dark #continueAssessmentModal .assessment-modal-subtitle {
        color: #94a3b8 !important;
    }

    [data-bs-theme="dark"] #continueAssessmentModal .assessment-modal-close,
    body.dark-mode #continueAssessmentModal .assessment-modal-close,
    body[data-bs-theme="dark"] #continueAssessmentModal .assessment-modal-close,
    [data-theme="dark"] #continueAssessmentModal .assessment-modal-close,
    body.theme-dark #continueAssessmentModal .assessment-modal-close {
        color: #94a3b8 !important;
        opacity: 0.85;
    }

    [data-bs-theme="dark"] #continueAssessmentModal .assessment-modal-close:hover,
    body.dark-mode #continueAssessmentModal .assessment-modal-close:hover,
    body[data-bs-theme="dark"] #continueAssessmentModal .assessment-modal-close:hover,
    [data-theme="dark"] #continueAssessmentModal .assessment-modal-close:hover,
    body.theme-dark #continueAssessmentModal .assessment-modal-close:hover {
        color: #ffffff !important;
        opacity: 1;
    }

    [data-bs-theme="dark"] #continueAssessmentModal .assessment-modal-close .ti,
    body.dark-mode #continueAssessmentModal .assessment-modal-close .ti,
    body[data-bs-theme="dark"] #continueAssessmentModal .assessment-modal-close .ti,
    [data-theme="dark"] #continueAssessmentModal .assessment-modal-close .ti,
    body.theme-dark #continueAssessmentModal .assessment-modal-close .ti {
        color: inherit !important;
    }

    [data-bs-theme="dark"] #continueAssessmentModal .assessment-modal-section-title,
    body.dark-mode #continueAssessmentModal .assessment-modal-section-title,
    body[data-bs-theme="dark"] #continueAssessmentModal .assessment-modal-section-title,
    [data-theme="dark"] #continueAssessmentModal .assessment-modal-section-title,
    body.theme-dark #continueAssessmentModal .assessment-modal-section-title {
        color: #e2e8f0 !important;
    }

    [data-bs-theme="dark"] #continueAssessmentModal .assessment-info-card,
    body.dark-mode #continueAssessmentModal .assessment-info-card,
    body[data-bs-theme="dark"] #continueAssessmentModal .assessment-info-card,
    [data-theme="dark"] #continueAssessmentModal .assessment-info-card,
    body.theme-dark #continueAssessmentModal .assessment-info-card {
        background-color: #1f2e42 !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
    }

    [data-bs-theme="dark"] #continueAssessmentModal .assessment-card-divider,
    body.dark-mode #continueAssessmentModal .assessment-card-divider,
    body[data-bs-theme="dark"] #continueAssessmentModal .assessment-card-divider,
    [data-theme="dark"] #continueAssessmentModal .assessment-card-divider,
    body.theme-dark #continueAssessmentModal .assessment-card-divider {
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    [data-bs-theme="dark"] #continueAssessmentModal .assessment-card-badge,
    body.dark-mode #continueAssessmentModal .assessment-card-badge,
    body[data-bs-theme="dark"] #continueAssessmentModal .assessment-card-badge,
    [data-theme="dark"] #continueAssessmentModal .assessment-card-badge,
    body.theme-dark #continueAssessmentModal .assessment-card-badge {
        color: #94a3b8 !important;
    }

    [data-bs-theme="dark"] #continueAssessmentModal .assessment-card-name,
    body.dark-mode #continueAssessmentModal .assessment-card-name,
    body[data-bs-theme="dark"] #continueAssessmentModal .assessment-card-name,
    [data-theme="dark"] #continueAssessmentModal .assessment-card-name,
    body.theme-dark #continueAssessmentModal .assessment-card-name {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] #continueAssessmentModal .assessment-card-label,
    body.dark-mode #continueAssessmentModal .assessment-card-label,
    body[data-bs-theme="dark"] #continueAssessmentModal .assessment-card-label,
    [data-theme="dark"] #continueAssessmentModal .assessment-card-label,
    body.theme-dark #continueAssessmentModal .assessment-card-label {
        color: #94a3b8 !important;
    }

    [data-bs-theme="dark"] #continueAssessmentModal .assessment-card-value,
    body.dark-mode #continueAssessmentModal .assessment-card-value,
    body[data-bs-theme="dark"] #continueAssessmentModal .assessment-card-value,
    [data-theme="dark"] #continueAssessmentModal .assessment-card-value,
    body.theme-dark #continueAssessmentModal .assessment-card-value {
        color: #f1f5f9 !important;
    }

    [data-bs-theme="dark"] #continueAssessmentModal .assessment-modal-list,
    body.dark-mode #continueAssessmentModal .assessment-modal-list,
    body[data-bs-theme="dark"] #continueAssessmentModal .assessment-modal-list,
    [data-theme="dark"] #continueAssessmentModal .assessment-modal-list,
    body.theme-dark #continueAssessmentModal .assessment-modal-list {
        color: #cbd5e1 !important;
    }

    [data-bs-theme="dark"] #continueAssessmentModal .assessment-modal-list li::marker,
    body.dark-mode #continueAssessmentModal .assessment-modal-list li::marker,
    body[data-bs-theme="dark"] #continueAssessmentModal .assessment-modal-list li::marker,
    [data-theme="dark"] #continueAssessmentModal .assessment-modal-list li::marker,
    body.theme-dark #continueAssessmentModal .assessment-modal-list li::marker {
        color: #94a3b8 !important;
    }

    [data-bs-theme="dark"] #continueAssessmentModal .assessment-modal-list em,
    body.dark-mode #continueAssessmentModal .assessment-modal-list em,
    body[data-bs-theme="dark"] #continueAssessmentModal .assessment-modal-list em,
    [data-theme="dark"] #continueAssessmentModal .assessment-modal-list em,
    body.theme-dark #continueAssessmentModal .assessment-modal-list em {
        color: #f1f5f9 !important;
    }

    /* Dark Mode Cancel Button */
    [data-bs-theme="dark"] #continueAssessmentModal .assessment-btn-cancel,
    body.dark-mode #continueAssessmentModal .assessment-btn-cancel,
    body[data-bs-theme="dark"] #continueAssessmentModal .assessment-btn-cancel,
    [data-theme="dark"] #continueAssessmentModal .assessment-btn-cancel,
    body.theme-dark #continueAssessmentModal .assessment-btn-cancel {
        background-color: #253549 !important;
        border: 1px solid rgba(255, 255, 255, 0.18) !important;
        color: #e2e8f0 !important;
    }

    [data-bs-theme="dark"] #continueAssessmentModal .assessment-btn-cancel:hover,
    body.dark-mode #continueAssessmentModal .assessment-btn-cancel:hover,
    body[data-bs-theme="dark"] #continueAssessmentModal .assessment-btn-cancel:hover,
    [data-theme="dark"] #continueAssessmentModal .assessment-btn-cancel:hover,
    body.theme-dark #continueAssessmentModal .assessment-btn-cancel:hover {
        background-color: #2e415a !important;
        color: #ffffff !important;
    }

    /* Dark Mode Continue Button */
    [data-bs-theme="dark"] #continueAssessmentModal .assessment-btn-continue:not(.disabled):not([disabled]),
    body.dark-mode #continueAssessmentModal .assessment-btn-continue:not(.disabled):not([disabled]),
    body[data-bs-theme="dark"] #continueAssessmentModal .assessment-btn-continue:not(.disabled):not([disabled]),
    [data-theme="dark"] #continueAssessmentModal .assessment-btn-continue:not(.disabled):not([disabled]),
    body.theme-dark #continueAssessmentModal .assessment-btn-continue:not(.disabled):not([disabled]) {
        background-color: #0284c7 !important;
        border-color: #0284c7 !important;
        color: #ffffff !important;
    }

    [data-bs-theme="dark"] #continueAssessmentModal .assessment-btn-continue:not(.disabled):not([disabled]):hover,
    body.dark-mode #continueAssessmentModal .assessment-btn-continue:not(.disabled):not([disabled]):hover,
    body[data-bs-theme="dark"] #continueAssessmentModal .assessment-btn-continue:not(.disabled):not([disabled]):hover {
        background-color: #0369a1 !important;
        border-color: #0369a1 !important;
    }

    [data-bs-theme="dark"] #continueAssessmentModal .assessment-btn-continue.disabled,
    [data-bs-theme="dark"] #continueAssessmentModal .assessment-btn-continue[disabled],
    body.dark-mode #continueAssessmentModal .assessment-btn-continue.disabled,
    body.dark-mode #continueAssessmentModal .assessment-btn-continue[disabled] {
        background-color: #334155 !important;
        border-color: #334155 !important;
        color: #94a3b8 !important;
    }
</style>
