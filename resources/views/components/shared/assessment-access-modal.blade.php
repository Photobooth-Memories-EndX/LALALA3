@props([
    'assessment' => null,
    'type' => 'global',
    'answeredCount' => 0,
    'totalCount' => 0,
    'actionType' => 'start',
    'isContinue' => false,
    'status' => null,
    'message' => null,
])

@php
    $isInactive = ($status === 'inactive');
    $isExpired = ($status === 'expired');
    $isStatusOnly = $isInactive || $isExpired;

    $headerTitle = 'Start Assessment';
    $headerColor = '#28be58';
    $headerBgColor = 'rgba(40, 190, 88, 0.12)';
    $headerIcon = 'ti ti-player-play';
    $headerSubtitle = 'Confirmation';

    if ($isInactive) {
        $headerTitle = 'Assessment Unavailable';
        $headerColor = '#dc3545';
        $headerBgColor = 'rgba(220, 53, 69, 0.12)';
        $headerIcon = 'ti ti-circle-x';
        $headerSubtitle = 'Inactive';
    } elseif ($isExpired) {
        $headerTitle = 'Assessment Expired';
        $headerColor = '#fd7e14';
        $headerBgColor = 'rgba(253, 126, 20, 0.12)';
        $headerIcon = 'ti ti-calendar-x';
        $headerSubtitle = 'Expired';
    } elseif ($actionType === 'continue' || $isContinue) {
        $headerTitle = 'Continue Assessment';
        $headerColor = '#0c5b9e';
        $headerBgColor = 'rgba(12, 91, 158, 0.12)';
        $headerIcon = 'ti ti-player-play';
        $headerSubtitle = 'Confirmation';
    } elseif ($actionType === 'remedial') {
        $headerTitle = 'REMEDIAL';
        $headerColor = '#f59e0b';
        $headerBgColor = 'rgba(245, 158, 11, 0.12)';
        $headerIcon = 'ti ti-refresh';
        $headerSubtitle = 'Confirmation';
    }

    $defaultStatusMessage = '';
    if ($isInactive) {
        $defaultStatusMessage = 'This assessment is currently inactive and cannot be started.';
    } elseif ($isExpired) {
        $defaultStatusMessage = 'This assessment has expired and can no longer be started.';
    }
    $statusMessage = $message ?? $defaultStatusMessage;
@endphp

<div class="modal fade" id="assessmentAccessModal" tabindex="-1" role="dialog"
     aria-labelledby="assessmentAccessModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable px-2 px-sm-0" role="document" style="max-width: 480px; margin: 1.75rem auto;">
        <div class="modal-content rounded-4 border-0 shadow">

            {{-- Header --}}
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="modal-title-icon d-inline-flex align-items-center justify-content-center rounded-circle p-2"
                         id="assessmentAccessModalIconBox"
                         style="width: 40px; height: 40px; background-color: {{ $headerBgColor }}; color: {{ $headerColor }};">
                        <i class="{{ $headerIcon }} fs-2" id="assessmentAccessModalIconInner"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="assessmentAccessModalTitle" style="color: {{ $headerColor }} !important;">
                            {{ $headerTitle }}
                        </h5>
                        <div class="text-muted small" id="assessmentAccessModalSubtitle">{{ $headerSubtitle }}</div>
                    </div>
                </div>
                <button type="button"
                        class="btn-close close modal-close-btn border-0 bg-transparent text-muted ms-auto"
                        data-dismiss="modal"
                        data-bs-dismiss="modal"
                        aria-label="Close">
                    <i class="ti ti-x fs-3"></i>
                </button>
            </div>

            {{-- Body --}}
            <div class="modal-body px-4 py-3">
                <!-- INFORMATION SECTION -->
                <h6 class="fw-bold text-dark text-uppercase mb-2" id="assessmentAccessModalInfoHeading" style="font-size: 0.85rem; letter-spacing: 0.5px; color: #000000 !important;">
                    INFORMATION
                </h6>
                <div class="bg-light rounded-3 p-3 mb-3" id="assessmentAccessModalInfoBox">
                    <!-- ASSESSMENT TITLE / CONTENT HEADER -->
                    <div class="assessment-title-wrapper mb-3 pb-2 border-bottom" id="assessmentAccessModalTitleWrapper">
                        <div class="text-dark small fw-bold text-uppercase mb-1" id="assessmentAccessModalTypeBadge" style="font-size: 0.75rem; color: #212529 !important; letter-spacing: 0.5px;">
                            {{ $type === 'global' ? 'ASSESSMENT GLOBAL' : 'ASSESSMENT CONTENT' }}
                        </div>
                        <h5 class="fw-bold text-dark mb-0 fs-5" id="assessmentAccessModalName" style="font-size: 1.1rem !important; line-height: 1.4; color: #1a1d20 !important;">
                            {{ $assessment?->title ?? '-' }}
                        </h5>
                    </div>

                    <!-- STATUS MESSAGE SECTION (INACTIVE / EXPIRED) -->
                    <div id="assessmentAccessModalStatusBox" style="{{ $isStatusOnly ? '' : 'display: none;' }}">
                        <div class="alert {{ $isInactive ? 'alert-danger' : 'alert-warning' }} border-0 rounded-3 p-3 mb-0 d-flex align-items-start gap-2" id="assessmentAccessModalStatusAlert">
                            <i class="{{ $isInactive ? 'ti ti-alert-triangle' : 'ti ti-clock-off' }} fs-2 flex-shrink-0 mt-1" id="assessmentAccessModalStatusAlertIcon"></i>
                            <div>
                                <strong class="d-block fw-bold mb-1" id="assessmentAccessModalStatusAlertTitle" style="font-size: 0.9rem;">
                                    {{ $isInactive ? 'Assessment Inactive' : 'Assessment Expired' }}
                                </strong>
                                <span class="small" id="assessmentAccessModalStatusText" style="line-height: 1.4;">
                                    {{ $statusMessage }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- DETAILS GRID (ACTIVE ONLY) -->
                    <div class="row g-2" id="assessmentAccessModalDetailsGrid" style="{{ $isStatusOnly ? 'display: none;' : '' }}">
                        <!-- Date & Time (Stacked) -->
                        <div class="col-12 col-sm-6">
                            <strong class="text-dark d-block mb-1" style="font-size: 0.75rem; color: #212529 !important;">DATE & TIME</strong>
                            <div class="text-dark small" id="assessmentAccessModalDateTime" style="color: #212529 !important; line-height: 1.35;">
                                @if($assessment)
                                    <div>{{ $assessment?->formatted_start_date ?? ($assessment?->fromdate ? date('d M Y, H:i', strtotime($assessment->fromdate)) : '-') }}</div>
                                    <div>{{ $assessment?->formatted_end_date ?? ($assessment?->todate ? date('d M Y, H:i', strtotime($assessment->todate)) : '-') }}</div>
                                @else
                                    <div id="assessmentAccessModalFromDate">-</div>
                                    <div id="assessmentAccessModalToDate" class="d-none">-</div>
                                @endif
                            </div>
                        </div>

                        <!-- Passing Grade -->
                        <div class="col-6 col-sm-6">
                            <strong class="text-dark d-block mb-1" style="font-size: 0.75rem; color: #212529 !important;">PASSING GRADE</strong>
                            <div class="text-dark fw-bold small" id="assessmentAccessModalPassingGrade" style="color: #212529 !important;">
                                {{ $assessment?->passing_grade ?? '-' }}
                            </div>
                        </div>

                        <!-- Duration -->
                        <div class="col-6 col-sm-6">
                            <strong class="text-dark d-block mb-1" style="font-size: 0.75rem; color: #212529 !important;">DURATION</strong>
                            <div class="text-dark small" id="assessmentAccessModalDuration" style="color: #212529 !important;">
                                {{ $assessment ? ($assessment->duration ?? ($assessment->time_limit_minutes ?? 60)) : '-' }} Minutes
                            </div>
                        </div>

                        <!-- Batch (Global) atau Attempt (Content) -->
                        <div class="col-12 col-sm-6" id="assessmentAccessModalBatchContainer" style="{{ $type === 'global' ? '' : 'display: none;' }}">
                            <strong class="text-dark d-block mb-1" style="font-size: 0.75rem; color: #212529 !important;">BATCH</strong>
                            <div class="text-dark small" id="assessmentAccessModalBatch" style="color: #212529 !important;">
                                {{ $assessment ? ($assessment->batch_name ?? ($assessment->batch ? (str_contains(strtolower($assessment->batch), 'batch') ? $assessment->batch : 'Batch ' . $assessment->batch) : 'Batch 1')) : '-' }}
                            </div>
                        </div>
                        <div class="col-12 col-sm-6" id="assessmentAccessModalAttemptContainer" style="{{ $type !== 'global' ? '' : 'display: none;' }}">
                            <strong class="text-dark d-block mb-1" style="font-size: 0.75rem; color: #212529 !important;">ATTEMPT</strong>
                            <div class="text-dark small" id="assessmentAccessModalAttempt" style="color: #212529 !important;">
                                {{ $assessment ? ($assessment->current_attempt ?? 1) . ' / ' . ($assessment->max_attempts ?? 1) : '-' }}
                            </div>
                        </div>

                        <!-- Progress (Khusus Continue Modal) -->
                        <div class="col-12 border-top pt-2 mt-2" id="assessmentAccessModalProgressContainer" style="{{ ($actionType === 'continue' || (isset($isContinue) && $isContinue)) ? '' : 'display: none;' }}">
                            <strong class="text-dark d-inline-block me-2" style="font-size: 0.75rem; color: #212529 !important;">PROGRESS:</strong>
                            <span class="text-dark fw-bold small" id="assessmentAccessModalProgress" style="color: #212529 !important;">
                                {{ $answeredCount ?? 0 }} / {{ $totalCount ?? 10 }} answered
                            </span>
                        </div>
                    </div>
                </div>

                <!-- NOTICE SECTION (ACTIVE ONLY) -->
                <div id="assessmentAccessModalNoticeSection" style="{{ $isStatusOnly ? 'display: none;' : '' }}">
                    <h6 class="fw-bold text-dark text-uppercase mb-2" style="font-size: 0.85rem; letter-spacing: 0.5px; color: #000000 !important;">
                        NOTICE
                    </h6>
                    <div id="assessmentAccessModalNoticeWrapper">
                        <ol class="small text-dark ps-3 mb-0" id="assessmentAccessNoticeStart" style="color: #212529 !important; {{ $actionType === 'start' ? '' : 'display: none;' }}">
                            <li>Make sure you are in a safe and conducive environment, and that your device is ready to use.</li>
                            <li><em>Once started, the assessment cannot be exited until it is completed or the time expires.</em></li>
                            <li>The assessment will take place according to the stated date & time.</li>
                            <li>If an error occurs on your device or the connection is interrupted, your answers will be saved. Re-enter the assessment to continue as long as time is still available.</li>
                        </ol>
                        <ol class="small text-dark ps-3 mb-0" id="assessmentAccessNoticeContinue" style="color: #212529 !important; {{ $actionType === 'continue' ? '' : 'display: none;' }}">
                            <li>Make sure you are in a safe and conducive environment, and that your device is ready to use.</li>
                            <li><em>Once resumed, the assessment cannot be exited until it is completed or the time expires.</em></li>
                            <li>The assessment will take place according to the stated date & time.</li>
                            <li>If an error occurs on your device or the connection is interrupted, your answers will be saved. Re-enter the assessment to continue as long as time is still available.</li>
                        </ol>
                        <ol class="small text-dark ps-3 mb-0" id="assessmentAccessNoticeRemedial" style="color: #212529 !important; {{ $actionType === 'remedial' ? '' : 'display: none;' }}">
                            <li>Make sure you are in a safe and conducive environment, and that your device is ready to use.</li>
                            <li><em>Once started, remedial cannot be exited until it is completed or the time expires.</em></li>
                            <li>The assessment will take place according to the stated date & time.</li>
                            <li>If an error occurs on your device or the connection is interrupted, your answers will be saved. Re-enter the assessment to continue as long as time is still available.</li>
                        </ol>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                <!-- Action buttons for Active (Start / Continue / Remedial) -->
                <div id="assessmentAccessModalActiveFooter" class="d-flex justify-content-end gap-2 w-100" style="{{ $isStatusOnly ? 'display: none !important;' : '' }}">
                    <button type="button" class="btn btn-danger text-white fw-semibold px-3 py-2" data-dismiss="modal" data-bs-dismiss="modal" style="background-color: #dc3545 !important; border-color: #dc3545 !important;">
                        <i class="ti ti-x me-1"></i> Cancel
                    </button>
                    <button type="button" id="btnStartAssessmentAccess" class="btn btn-success text-white fw-semibold px-3 py-2 d-inline-flex align-items-center gap-1"
                            style="background-color: #28a745 !important; border-color: #28a745 !important;">
                        <i class="ti ti-player-play fs-6"></i>
                        <span>Start Test</span>
                    </button>
                </div>

                <!-- Single Close button for Inactive / Expired -->
                <div id="assessmentAccessModalStatusFooter" class="w-100" style="{{ $isStatusOnly ? '' : 'display: none !important;' }}">
                    <button type="button" class="btn btn-primary w-100 rounded-3 py-2 fw-semibold" data-dismiss="modal" data-bs-dismiss="modal" id="btnAssessmentAccessClose">
                        Close
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>
