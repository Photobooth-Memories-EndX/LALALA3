@props(['assessment' => null, 'type' => 'content', 'answeredCount' => 0, 'totalCount' => 0, 'isContinue' => true])

<div class="modal fade" id="continueAssessmentModal" tabindex="-1" role="dialog" aria-labelledby="continueAssessmentModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document" style="max-width: 480px !important; width: 100%;">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="modal-title-icon d-inline-flex align-items-center justify-content-center rounded-circle p-2" 
                         id="continueModalHeaderIconBox"
                         style="width: 40px; height: 40px; background-color: rgba(12, 91, 158, 0.12); color: #0c5b9e;">
                        <i class="ti ti-player-play fs-2"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="continueAssessmentModalTitle" style="color: #0c5b9e !important;">
                            Continue Assessment
                        </h5>
                        <div class="text-muted small">Confirmation</div>
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
            <div class="modal-body px-4 py-3">
                <!-- INFORMATION SECTION -->
                <h6 class="fw-bold text-dark text-uppercase mb-2" style="font-size: 0.85rem; letter-spacing: 0.5px; color: #000000 !important;">
                    INFORMATION
                </h6>
                <div class="bg-light rounded-3 p-3 mb-3">
                    <!-- ASSESSMENT TITLE / CONTENT HEADER -->
                    <div class="assessment-title-wrapper mb-3 pb-2 border-bottom">
                        <div class="text-dark small fw-bold text-uppercase mb-1" 
                             id="continueModalTypeHeader" 
                             style="font-size: 0.75rem; color: #212529 !important; letter-spacing: 0.5px;">
                            {{ $type === 'global' ? 'ASSESSMENT GLOBAL' : 'ASSESSMENT CONTENT' }}
                        </div>
                        <h5 class="fw-bold text-dark mb-0 fs-5" 
                             id="continueModalName" 
                             style="font-size: 1.1rem !important; line-height: 1.4; color: #1a1d20 !important;">
                            {{ $assessment?->title ?? '-' }}
                        </h5>
                    </div>

                    <!-- DETAILS GRID (MEMANFAATKAN VARIABEL ASLI) -->
                    <div class="row g-2">
                        <!-- Date & Time (Stacked) -->
                        <div class="col-12 col-sm-6">
                            <strong class="text-dark d-block mb-1" style="font-size: 0.75rem; color: #212529 !important;">DATE & TIME</strong>
                            <div class="text-dark small" id="continueModalDateTime" style="color: #212529 !important; line-height: 1.35;">
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
                            <strong class="text-dark d-block mb-1" style="font-size: 0.75rem; color: #212529 !important;">PASSING GRADE</strong>
                            <div class="text-dark fw-bold small" id="continueModalPassingGrade" style="color: #212529 !important;">
                                {{ $assessment?->passing_grade ?? '-' }}
                            </div>
                        </div>

                        <!-- Duration -->
                        <div class="col-6 col-sm-6">
                            <strong class="text-dark d-block mb-1" style="font-size: 0.75rem; color: #212529 !important;">DURATION</strong>
                            <div class="text-dark small" id="continueModalDuration" style="color: #212529 !important;">
                                {{ $assessment ? ($assessment->duration ?? ($assessment->time_limit_minutes ?? 60)) : '-' }} Minutes
                            </div>
                        </div>

                        <!-- Batch (Global) atau Attempt (Content) -->
                        <div class="col-12 col-sm-6" id="continueModalBatchContainer" style="{{ $type === 'global' ? '' : 'display: none;' }}">
                            <strong class="text-dark d-block mb-1" style="font-size: 0.75rem; color: #212529 !important;">BATCH</strong>
                            <div class="text-dark small" id="continueModalBatch" style="color: #212529 !important;">
                                {{ $assessment ? ($assessment->batch_name ?? ($assessment->batch ? (str_contains(strtolower($assessment->batch), 'batch') ? $assessment->batch : 'Batch ' . $assessment->batch) : 'Batch 1')) : '-' }}
                            </div>
                        </div>
                        <div class="col-12 col-sm-6" id="continueModalAttemptContainer" style="{{ $type !== 'global' ? '' : 'display: none;' }}">
                            <strong class="text-dark d-block mb-1" style="font-size: 0.75rem; color: #212529 !important;">ATTEMPT</strong>
                            <div class="text-dark small" id="continueModalAttempt" style="color: #212529 !important;">
                                {{ $assessment ? ($assessment->current_attempt ?? 1) . ' / ' . ($assessment->max_attempts ?? 1) : '-' }}
                            </div>
                        </div>

                        <!-- Progress (Khusus Continue Modal) -->
                        <div class="col-12 border-top pt-2 mt-2" id="continueModalProgressContainer">
                            <strong class="text-dark d-inline-block me-2" style="font-size: 0.75rem; color: #212529 !important;">PROGRESS:</strong>
                            <span class="text-dark fw-bold small" id="continueModalProgress" style="color: #212529 !important;">
                                {{ $answeredCount ?? 0 }} / {{ $totalCount ?? 10 }} answered
                            </span>
                        </div>
                    </div>
                </div>

                <!-- NOTICE SECTION -->
                <h6 class="fw-bold text-dark text-uppercase mb-2" id="continueModalNoticeTitle" style="font-size: 0.85rem; letter-spacing: 0.5px; color: #000000 !important;">
                    NOTICE
                </h6>
                <div id="continueModalNoticeWrapper">
                    <ol class="small text-dark ps-3 mb-0" id="continueNoticeGlobal" style="color: #212529 !important; {{ $type === 'global' ? '' : 'display: none;' }}">
                        <li>Make sure you are in a safe and conducive environment, and that your device is ready to use.</li>
                        <li><em>Once resumed, the assessment cannot be exited until it is completed or the time expires.</em></li>
                        <li>The assessment will take place according to the stated date & time.</li>
                        <li>If an error occurs on your device or the connection is interrupted, your answers will be saved. Re-enter the assessment to continue as long as time is still available.</li>
                    </ol>
                    <ol class="small text-dark ps-3 mb-0" id="continueNoticeContent" style="color: #212529 !important; {{ $type !== 'global' ? '' : 'display: none;' }}">
                        <li>Ensure yourself, your device, and your environment are in a safe and conducive condition.</li>
                        <li>Assessment will continue using the remaining available time.</li>
                        <li><em>Once resumed, the assessment cannot be exited until it is completed or time expires.</em></li>
                        <li>If an error or technical issue occurs, your answers are saved. Re-enter immediately to continue as long as time remains.</li>
                        <li>Perform your best by utilizing the available opportunity.</li>
                    </ol>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                <button type="button" class="btn btn-light rounded-pill px-4 text-secondary" data-dismiss="modal" data-bs-dismiss="modal">
                    Cancel
                </button>
                <a href="{{ ($assessment && !empty($assessment->slug)) ? route('assesment.user.view', $assessment->slug) : '#' }}"
                   id="btnConfirmContinueAssessment"
                   class="btn rounded-pill px-4 fw-semibold text-white shadow-sm d-inline-flex align-items-center gap-1"
                   style="background-color: #0c5b9e !important; border-color: #0c5b9e !important; color: #ffffff !important;">
                    <i class="ti ti-player-play fs-6"></i> Continue Assessment
                </a>
            </div>
        </div>
    </div>
</div>
