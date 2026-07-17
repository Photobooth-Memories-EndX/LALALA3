<div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center gap-2">

            @if ($hasPlaylistAssessment)
                <a href="{{ $playlistProgressComplete && !$playlistAssessmentDone ? route('assesment.user.view', $assesment->slug) : '#' }}"
                   class="btn btn-primary flex-fill {{ !$playlistProgressComplete || $playlistAssessmentDone ? 'disabled' : '' }}"
                   aria-disabled="{{ !$playlistProgressComplete || $playlistAssessmentDone ? 'true' : 'false' }}">
                    <i class="fas fa-clipboard-check me-2"></i>
                    Assessment Playlist
                </a>
            @endif

            <button type="button"
                    class="btn btn-outline-secondary flex-fill"
                    disabled>
                <i class="fas fa-tasks me-2"></i>
                My Task
            </button>

            @if ($playlistCertificateSetting)
                <form method="POST"
                      action="{{ route('certificate.generate.setting', $playlistCertificateSetting) }}"
                      class="flex-fill m-0">
                    @csrf

                    <button type="submit"
                            class="btn btn-success w-100"
                            {{ $canGeneratePlaylistCertificate ? '' : 'disabled' }}>
                        <i class="fas fa-certificate me-2"></i>
                        Certificate
                    </button>
                </form>
            @else
                <button type="button"
                        class="btn btn-outline-secondary flex-fill"
                        disabled>
                    <i class="fas fa-certificate me-2"></i>
                    Certificate
                </button>
            @endif

        </div>
    </div>
</div>
