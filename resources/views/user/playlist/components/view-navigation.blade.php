@php
    $certificateEligible = (bool) ($playlistUiState['certificate_available']
        ?? $playlistCertificateEligibility?->eligible
        ?? false);
    $playlistAssessmentAvailable = (bool) ($playlistUiState['playlist_assessment_available']
        ?? $hasPlaylistAssessment);
    $certificateDebugOverride = $playlistDebugState['certificate'] ?? null;
    $playlistAssessmentDebugOverride = $playlistDebugState['playlist_assessment'] ?? null;
    $certificateUnavailableReason = $playlistCertificateEligibility?->reason
        ?? 'Certificate is not configured for this playlist.';
@endphp

<div class="card border-0 shadow-sm rounded-4 mb-3 view-navigation-card">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center gap-2">

            @if ($playlistAssessmentDebugOverride !== null)
                <button type="button"
                        class="btn btn-primary flex-fill"
                        aria-disabled="{{ $playlistAssessmentAvailable ? 'false' : 'true' }}"
                        title="Session debug preview only; assessment access is unchanged."
                        @disabled(!$playlistAssessmentAvailable)>
                    <i class="ti ti-clipboard-check me-2" aria-hidden="true"></i>
                    Assessment Playlist
                </button>
            @elseif ($hasPlaylistAssessment)
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

            @if ($certificateDebugOverride !== null)
                @if ($certificateDebugOverride === 'available' && $playlistCertificateSetting)
                    <a href="{{ route('playlist.debug.certificate.preview', [
                            'trainingplan' => $playlist,
                            'content' => $content->slug,
                        ]) }}"
                       class="btn btn-warning certificate-action-button flex-fill"
                       title="Open the session-only debug certificate preview.">
                        <i class="ti ti-eye me-2" aria-hidden="true"></i>
                        Preview Certificate
                        <span class="badge bg-orange-lt text-orange ms-2">DEBUG</span>
                    </a>
                @else
                    <button type="button"
                            class="btn btn-success certificate-action-button flex-fill"
                            aria-disabled="true"
                            title="{{ $playlistCertificateSetting
                                ? 'Certificate debug preview is not available.'
                                : 'An active playlist certificate setting is required.' }}"
                            disabled>
                        <i class="ti ti-certificate me-2" aria-hidden="true"></i>
                        Generate Certificate
                    </button>
                @endif
            @elseif ($playlistCertificateSetting)
                <form method="POST"
                      action="{{ route('certificate.generate.setting', $playlistCertificateSetting) }}"
                      class="certificate-action-wrapper flex-fill m-0 {{ $certificateEligible ? '' : 'is-disabled' }}"
                      @if (!$certificateEligible) title="{{ $certificateUnavailableReason }}" @endif>
                    @csrf

                    <button type="submit"
                            class="btn btn-success certificate-action-button w-100"
                            aria-disabled="{{ $certificateEligible ? 'false' : 'true' }}"
                            @disabled(!$certificateEligible)>
                        <i class="ti ti-certificate me-2" aria-hidden="true"></i>
                        Generate Certificate
                    </button>
                </form>
            @else
                <span class="certificate-action-wrapper is-disabled d-block flex-fill"
                       tabindex="0"
                       title="{{ $certificateUnavailableReason }}">
                    <button type="button"
                            class="btn btn-success certificate-action-button w-100"
                            aria-disabled="true"
                            disabled>
                        <i class="ti ti-certificate me-2" aria-hidden="true"></i>
                        Generate Certificate
                    </button>
                </span>
            @endif

        </div>
    </div>
</div>
