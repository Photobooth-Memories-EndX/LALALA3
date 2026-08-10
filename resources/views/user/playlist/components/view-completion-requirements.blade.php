@php
    $displayLearningProgress = $playlistUiState['learning_progress']
        ?? $percentageProgressPlaylist;
    $certificateEligible = (bool) ($playlistUiState['certificate_available']
        ?? $playlistCertificateEligibility?->eligible
        ?? false);
    $playlistAssessmentAvailable = (bool) ($playlistUiState['playlist_assessment_available']
        ?? ($hasPlaylistAssessment && ($assessmentAccess || $playlistAssessmentDone)));
    $contentAssessmentAvailable = (bool) ($playlistUiState['content_assessment_available']
        ?? ($contentAssessmentTotal > 0));
@endphp

<section class="border-top pt-3 mt-4" aria-labelledby="playlistCompletionRequirementsTitle">
    <div class="mb-3">
        <h3 class="fs-5 fw-bold mb-1" id="playlistCompletionRequirementsTitle">
            Playlist Completion Requirements
        </h3>
        <div class="text-muted small">
            Complete each applicable requirement to unlock your playlist certificate.
        </div>
    </div>

    <div class="row g-2">
        <div class="col-12 col-md-6">
            <div class="h-100 d-flex align-items-center justify-content-between gap-3 border rounded-3 px-3 py-2">
                <span class="small fw-semibold">
                    <i class="ti ti-progress-check me-1" aria-hidden="true"></i>
                    Learning Progress
                </span>

                <span class="badge bg-blue-lt text-blue">
                    <i class="ti ti-chart-bar me-1" aria-hidden="true"></i>
                    100%
                </span>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="h-100 d-flex align-items-center justify-content-between gap-3 border rounded-3 px-3 py-2">
                <span class="small fw-semibold">
                    <i class="ti ti-clipboard-data me-1" aria-hidden="true"></i>
                    Assessment Content
                </span>

                @if ($contentAssessmentAvailable)
                    <span class="badge bg-success-lt text-success">
                        <i class="ti ti-circle-check me-1" aria-hidden="true"></i>
                        Available
                    </span>
                @else
                    <span class="badge bg-danger-lt text-danger">
                        <i class="ti ti-circle-x me-1" aria-hidden="true"></i>
                        Not Available
                    </span>
                @endif
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="h-100 d-flex align-items-center justify-content-between gap-3 border rounded-3 px-3 py-2">
                <span class="small fw-semibold">
                    <i class="ti ti-clipboard-check me-1" aria-hidden="true"></i>
                    Playlist Assessment
                </span>

                @if ($playlistAssessmentAvailable)
                    <span class="badge bg-success-lt text-success">
                        <i class="ti ti-circle-check me-1" aria-hidden="true"></i>
                        Available
                    </span>
                @else
                    <span class="badge bg-danger-lt text-danger">
                        <i class="ti ti-circle-x me-1" aria-hidden="true"></i>
                        Not Available
                    </span>
                @endif
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="h-100 d-flex align-items-center justify-content-between gap-3 border rounded-3 px-3 py-2">
                <span class="small fw-semibold">
                    <i class="ti ti-list-check me-1" aria-hidden="true"></i>
                    My Task
                </span>

                {{-- UI-only placeholder. No My Task backend or query exists yet. --}}
                <span class="badge bg-danger-lt text-danger">
                    <i class="ti ti-circle-x me-1" aria-hidden="true"></i>
                    Not Available
                </span>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="h-100 d-flex align-items-center justify-content-between gap-3 border rounded-3 px-3 py-2">
                <span class="small fw-semibold">
                    <i class="ti ti-certificate me-1" aria-hidden="true"></i>
                    Certificate
                </span>

                @if ($certificateEligible)
                    <span class="badge bg-success-lt text-success">
                        <i class="ti ti-circle-check me-1" aria-hidden="true"></i>
                        Available
                    </span>
                @else
                    <span class="badge bg-danger-lt text-danger">
                        <i class="ti ti-circle-x me-1" aria-hidden="true"></i>
                        Not Available
                    </span>
                @endif
            </div>
        </div>
    </div>
</section>
