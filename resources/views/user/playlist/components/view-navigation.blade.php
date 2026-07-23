@php
    $certificateEligible = (bool) ($playlistCertificateEligibility?->eligible ?? false);
    $certificateUnavailableReason = $playlistCertificateEligibility?->reason
        ?? 'Certificate is not configured for this playlist.';
@endphp

<div class="card border-0 shadow-sm rounded-4 mb-3 view-navigation-card">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center gap-2">

            @if ($hasPlaylistAssessment)
                <a href="{{ route('assesment.user.view', $assesment->slug) }}"
                   class="btn btn-primary flex-fill">
                    <i class="ti ti-clipboard-check me-2" aria-hidden="true"></i>
                    Assessment Playlist
                </a>
            @else
                <span class="d-block flex-fill"
                      title="No active playlist assessment is available.">
                    <button type="button"
                            class="btn btn-primary w-100"
                            disabled>
                        <i class="ti ti-clipboard-check me-2" aria-hidden="true"></i>
                        Assessment Playlist
                    </button>
                </span>
            @endif

            <button type="button"
                    class="btn btn-outline-secondary flex-fill"
                    title="My Task is not available yet."
                    disabled>
                <i class="ti ti-list-check me-2" aria-hidden="true"></i>
                My Task
            </button>

            @if ($playlistCertificateSetting && $certificateEligible)
                <form method="POST"
                      action="{{ route('certificate.generate.setting', $playlistCertificateSetting) }}"
                      class="flex-fill m-0">
                    @csrf

                    <button type="submit"
                            class="btn btn-success w-100">
                        <i class="ti ti-certificate me-2" aria-hidden="true"></i>
                        Generate Certificate
                    </button>
                </form>
            @else
                <span class="d-block flex-fill"
                      tabindex="0"
                      title="{{ $certificateUnavailableReason }}">
                    <button type="button"
                            class="btn btn-success w-100"
                            disabled>
                        <i class="ti ti-certificate me-2" aria-hidden="true"></i>
                        Generate Certificate
                    </button>
                </span>
            @endif

        </div>
    </div>
</div>
