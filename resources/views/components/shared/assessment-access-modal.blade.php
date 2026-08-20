@props(['assessment' => null, 'type' => 'global', 'answeredCount' => 0, 'totalCount' => 0, 'actionType' => 'start'])

<div class="modal fade" id="assessmentAccessModal" tabindex="-1" role="dialog"
     aria-labelledby="assessmentAccessModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 480px !important; width: 100%;">
        <div class="modal-content rounded-4 border-0 shadow">

            {{-- Header --}}
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="modal-title-icon d-inline-flex align-items-center justify-content-center rounded-circle p-2"
                         id="assessmentAccessModalIconBox"
                         style="width: 40px; height: 40px; background-color: rgba(40, 190, 88, 0.12); color: #28be58;">
                        <i class="ti ti-player-play fs-2" id="assessmentAccessModalIconInner"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="assessmentAccessModalTitle" style="color: #28be58 !important;">Start Assessment</h5>
                        <div class="text-muted small" id="assessmentAccessModalSubtitle">Confirmation</div>
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
                <h6 class="fw-bold text-dark text-uppercase mb-2" style="font-size: 0.85rem; letter-spacing: 0.5px; color: #000000 !important;">
                    INFORMATION
                </h6>
                <div class="bg-light rounded-3 p-3 mb-3">
                    <div class="assessment-title-wrapper mb-2 pb-2 border-bottom">
                        <div class="text-dark small fw-bold text-uppercase mb-1" id="assessmentAccessModalTypeBadge" style="font-size: 0.78rem;">
                            {{ $type === 'global' ? 'ASSESSMENT GLOBAL' : 'ASSESSMENT CONTENT' }}
                        </div>
                        <h5 class="fw-bold text-dark mb-0 fs-5" id="assessmentAccessModalName" style="font-size: 1.1rem !important; line-height: 1.4;">
                            {{ $assessment ? $assessment->title : '-' }}
                        </h5>
                    </div>
                    <div class="row g-2 small text-secondary">
                        <div class="col-6">
                            <strong class="text-dark">Date & Time:</strong>
                            <div class="text-truncate" id="assessmentAccessModalDateTime">
                                @if($assessment)
                                    {{ $assessment->formatted_start_date ?? ($assessment->fromdate ? date('d M Y, H:i', strtotime($assessment->fromdate)) : '-') }} – {{ $assessment->formatted_end_date ?? ($assessment->todate ? date('d M Y, H:i', strtotime($assessment->todate)) : '-') }}
                                @else
                                    <span id="assessmentAccessModalFromDate">-</span><span id="assessmentAccessModalToDate" class="d-none">-</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-6">
                            <strong class="text-dark">Passing Grade:</strong>
                            <div><span class="fw-semibold text-primary" id="assessmentAccessModalPassingGrade">{{ $assessment ? $assessment->passing_grade : '-' }}</span></div>
                        </div>
                        <div class="col-6">
                            <strong class="text-dark">Duration:</strong>
                            <div><span id="assessmentAccessModalDuration">{{ $assessment ? ($assessment->duration ?? ($assessment->time_limit_minutes ?? 60)) : '-' }} Minutes</span></div>
                        </div>
                        <div class="col-6" id="assessmentAccessModalBatchContainer">
                            <strong class="text-dark">Batch:</strong>
                            <div><span id="assessmentAccessModalBatch">{{ $assessment ? ($assessment->batch_name ?? ($assessment->batch ? (str_contains(strtolower($assessment->batch), 'batch') ? $assessment->batch : 'Batch ' . $assessment->batch) : 'Batch 1')) : '-' }}</span></div>
                        </div>
                        <div class="col-6" id="assessmentAccessModalAttemptContainer" style="display: none;">
                            <strong class="text-dark">Attempt:</strong>
                            <div><span id="assessmentAccessModalAttempt">-</span></div>
                        </div>
                        <div class="col-12 mt-1" id="assessmentAccessModalProgressContainer" style="{{ $actionType === 'continue' ? '' : 'display: none;' }}">
                            <strong class="text-dark">Progress:</strong>
                            <span class="fw-semibold" id="assessmentAccessModalProgress" style="color: #0c5b9e;">{{ $answeredCount }} / {{ $totalCount }} answered</span>
                        </div>
                    </div>
                </div>

                <!-- NOTICE SECTION -->
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

            {{-- Footer --}}
            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                <button type="button" class="btn btn-light rounded-pill px-4 text-secondary" data-dismiss="modal" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="btnStartAssessmentAccess" class="btn rounded-pill px-4 fw-semibold text-white shadow-sm d-inline-flex align-items-center gap-1"
                        style="background-color: #28be58 !important; border-color: #28be58 !important; color: #ffffff !important;">
                    <i class="ti ti-player-play fs-6"></i>
                    <span>Start Test</span>
                </button>
            </div>

        </div>
    </div>
</div>
