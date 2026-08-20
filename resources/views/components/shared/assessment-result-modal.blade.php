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
@endphp

<div class="modal fade" id="assessmentResultModal" tabindex="-1" role="dialog" aria-labelledby="assessmentResultModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 pb-0 pt-4 px-4 position-relative">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-2 rounded-3 {{ $isPassed ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="{{ $isPassed ? 'ti ti-trophy' : 'ti ti-alert-circle' }} fs-2"></i>
                    </div>
                    <div>
                        <h4 class="modal-title fw-bold mb-0 text-dark" id="assessmentResultModalTitle">Assessment Result</h4>
                        <div class="text-muted small">Summary Information</div>
                    </div>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                @if($isExpiredNotice)
                    <div class="alert alert-warning border-0 rounded-3 mb-3 p-3 d-flex align-items-start gap-3">
                        <i class="ti ti-clock-off fs-2 text-warning flex-shrink-0 mt-1"></i>
                        <div>
                            <strong class="d-block text-dark fw-bold mb-1">Assessment Time Expired</strong>
                            <span class="small text-muted">Your assessment time has expired and your answers have been submitted automatically.</span>
                        </div>
                    </div>
                @endif

                <div class="card border-0 bg-light rounded-4 p-4 text-center mb-3">
                    <div class="text-muted small text-uppercase fw-semibold mb-1">Your Score</div>
                    <div class="{{ $isPassed ? 'text-success' : 'text-danger' }} display-4 fw-extrabold mb-1" style="font-size: 3.5rem; line-height: 1; font-weight: 800;">
                        {{ $score }}
                    </div>
                    <div class="mt-2">
                        @if ($isPassed)
                            <span class="badge bg-success-subtle text-success px-3 py-2 fs-6 rounded-pill fw-bold">
                                <i class="ti ti-check me-1"></i>Passed
                            </span>
                        @else
                            <span class="badge bg-danger-subtle text-danger px-3 py-2 fs-6 rounded-pill fw-bold">
                                <i class="ti ti-x me-1"></i>Failed
                            </span>
                        @endif
                    </div>
                    <div class="text-muted small mt-2">
                        Passing Grade: <strong>{{ $passingGrade }}</strong>
                    </div>
                </div>

                <div class="row g-2">
                    <div class="col-12">
                        <div class="p-3 rounded-3 bg-light">
                            <div class="text-muted small">Assessment Title</div>
                            <div class="fw-bold text-dark">{{ $modalAssessment->title ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="p-3 rounded-3 bg-light">
                            <div class="text-muted small">Type</div>
                            <div class="fw-semibold text-dark">{{ $assessmentType }}</div>
                        </div>
                    </div>

                    @if ($isGlobal)
                        <div class="col-6">
                            <div class="p-3 rounded-3 bg-light">
                                <div class="text-muted small">Batch</div>
                                <div class="fw-semibold text-dark">{{ $modalResult->batch ?? $modalAssessment->batch ?? '-' }}</div>
                            </div>
                        </div>
                    @elseif (!empty($currentAttempt) && !empty($maxAttempts))
                        <div class="col-6">
                            <div class="p-3 rounded-3 bg-light">
                                <div class="text-muted small">Attempt</div>
                                <div class="fw-semibold text-dark">{{ $currentAttempt }} / {{ $maxAttempts }}</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="modal-footer border-0 pt-0 pb-4 px-4">
                <button type="button" class="btn btn-primary w-100 rounded-3 py-2 fw-semibold" data-bs-dismiss="modal" data-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
