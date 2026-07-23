@php
    $certificateEligible = (bool) ($playlistCertificateEligibility?->eligible ?? false);
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

                @if ($playlistProgressComplete)
                    <span class="badge bg-success-lt text-success">
                        <i class="ti ti-circle-check me-1" aria-hidden="true"></i>
                        Complete Progress
                    </span>
                @else
                    <span class="badge bg-danger-lt text-danger">
                        <i class="ti ti-circle-x me-1" aria-hidden="true"></i>
                        Incomplete Progress
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
                <span class="badge bg-secondary-lt text-secondary">
                    <i class="ti ti-circle me-1" aria-hidden="true"></i>
                    No My Task Assessment
                </span>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="h-100 d-flex align-items-center justify-content-between gap-3 border rounded-3 px-3 py-2">
                <span class="small fw-semibold">
                    <i class="ti ti-clipboard-check me-1" aria-hidden="true"></i>
                    Playlist Assessment
                </span>

                @if (!$hasPlaylistAssessment)
                    <span class="badge bg-secondary-lt text-secondary">
                        <i class="ti ti-circle me-1" aria-hidden="true"></i>
                        No Playlist Assessment
                    </span>
                @elseif ($playlistAssessmentDone)
                    <span class="badge bg-success-lt text-success">
                        <i class="ti ti-circle-check me-1" aria-hidden="true"></i>
                        Playlist Assessment Completed
                    </span>
                @else
                    <span class="badge bg-warning-lt text-warning">
                        <i class="ti ti-alert-circle me-1" aria-hidden="true"></i>
                        Have a Playlist Assessment to Complete
                    </span>
                @endif
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
                        Certificate Available
                    </span>
                @else
                    <span class="badge bg-danger-lt text-danger">
                        <i class="ti ti-circle-x me-1" aria-hidden="true"></i>
                        Certificate Not Available
                    </span>
                @endif
            </div>
        </div>
    </div>
</section>
