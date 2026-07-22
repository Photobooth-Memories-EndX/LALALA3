@php
    $commentTotal = method_exists($comment, 'total') ? $comment->total() : $comment->count();
@endphp

<div class="card border-0 shadow-sm rounded-4 mb-3 view-content-info-card">
    <div class="card-body p-4">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
            <div class="min-w-0">
                <div class="text-muted small mb-1">
                    {{ date('d F Y', strtotime($content->created_at)) }}
                    by {{ $content->author ?? '-' }}
                </div>

                <h2 class="mb-2 fs-2 fw-bold">
                    {{ $content->title }}
                </h2>

                <div class="overview-inline-meta text-muted">
                    <button type="button"
                            class="overview-meta-button"
                            data-bs-toggle="modal"
                            data-bs-target="#playlistCommentModal">
                        <i class="far fa-comment-dots me-1"></i>
                        {{ $commentTotal }} Comments
                    </button>
                    <span class="overview-meta-separator">&bull;</span>
                    <span>
                        <i class="far fa-clock me-1"></i>
                        {{ \App\Support\DurationFormatter::format($content->duration) }}
                    </span>
                </div>
            </div>

            <button type="button"
                    class="btn btn-icon btn-outline-secondary rounded-3"
                    onclick="shareContent()">
                <i class="fas fa-share-alt"></i>
            </button>
        </div>

        {{-- Overview --}}
        <div class="chapter-description-box mb-4">
            <h4 class="fs-6 fw-bold mb-2">Overview</h4>

            <div class="overview-text is-collapsed text-muted" id="playlistOverviewText">
                {!! nl2br($content->description) !!}
            </div>

            <button type="button"
                    class="btn btn-link px-0 py-1 mt-2 overview-toggle"
                    data-read-more="Read More"
                    data-read-less="Read Less">
                Read More
            </button>
        </div>

        {{-- Playlist Actions --}}
        <div class="border-top pt-3 mt-4">
            <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3">

                {{-- Status Info --}}
                <div class="d-flex flex-wrap align-items-center gap-2">

                    @if ($playlistProgressComplete)
                        <span class="badge bg-success-lt text-success px-3 py-2">
                            <i class="fas fa-check-circle me-1"></i>
                            All Content Completed
                        </span>
                    @else
                        <span class="badge bg-secondary-lt text-secondary px-3 py-2">
                            <i class="fas fa-lock me-1"></i>
                            Incomplete Progress
                        </span>
                    @endif

                    @if ($hasPlaylistAssessment)
                        @if ($playlistAssessmentDone)
                            <span class="badge bg-success-lt text-success px-3 py-2">
                                <i class="fas fa-tasks me-1"></i>
                                Playlist Assessment Completed
                            </span>
                        @else
                            <span class="badge bg-primary-lt text-primary px-3 py-2">
                                <i class="fas fa-tasks me-1"></i>
                                Playlist Assessment Available
                            </span>
                        @endif
                    @else
                        <span class="badge bg-secondary-lt text-secondary px-3 py-2">
                            <i class="fas fa-info-circle me-1"></i>
                            No Playlist Assessment
                        </span>
                    @endif

                    @if ($playlistCertificateSetting)
                        <span class="badge bg-green-lt text-green px-3 py-2">
                            <i class="fas fa-certificate me-1"></i>
                            Certificate Available
                        </span>
                    @else
                        <span class="badge bg-secondary-lt text-secondary px-3 py-2">
                            <i class="fas fa-certificate me-1"></i>
                            Certificate Not Configured
                        </span>
                    @endif

                </div>

                {{-- Action Buttons --}}
                <div class="d-flex flex-wrap align-items-center gap-2">

                    @if ($hasPlaylistAssessment)
                        <a href="{{ $playlistProgressComplete && !$playlistAssessmentDone ? route('assesment.user.view', $assesment->slug) : '#' }}"
                           class="btn btn-primary {{ !$playlistProgressComplete || $playlistAssessmentDone ? 'disabled' : '' }}"
                           aria-disabled="{{ !$playlistProgressComplete || $playlistAssessmentDone ? 'true' : 'false' }}">
                            <i class="fas fa-tasks me-2"></i>
                            {{ $playlistAssessmentDone ? 'Assessment Selesai' : 'Kerjakan Assessment' }}
                        </a>
                    @endif

                    @if ($playlistCertificateSetting)
                        <form method="POST"
                              action="{{ route('certificate.generate.setting', $playlistCertificateSetting) }}"
                              class="d-inline-block m-0">
                            @csrf

                            <button type="submit"
                                    class="btn btn-success"
                                    {{ $canGeneratePlaylistCertificate ? '' : 'disabled' }}>
                                <i class="fas fa-certificate me-2"></i>
                                Download Playlist Certificate
                            </button>
                        </form>
                    @endif

                </div>

            </div>

            @if (!$canGeneratePlaylistCertificate)
                <div class="text-muted small mt-2">
                    The certificate will be available after all playlist requirements have been completed.
                </div>
            @endif
        </div>

        {{-- Debug block (hanya muncul di APP_DEBUG=true) JANGAN DI HAPUS BUAT DEBUGGER --}}
        {{--
        @if (config('app.debug'))
            <div class="alert alert-info small mt-3">
                <strong>Debug Certificate:</strong><br>
                Progress: {{ $percentageProgressPlaylist ?? 'null' }}%<br>
                Progress Complete (>=100): {{ ($playlistProgressComplete ?? false) ? 'YES' : 'NO' }}<br>
                Has Playlist Assessment: {{ ($hasPlaylistAssessment ?? false) ? 'YES' : 'NO' }}<br>
                Playlist Assessment Done: {{ ($playlistAssessmentDone ?? false) ? 'YES' : 'NO' }}<br>
                Needs Playlist Assessment: {{ ($needsPlaylistAssessment ?? false) ? 'YES' : 'NO' }}<br>
                Can Generate Certificate: {{ ($canGeneratePlaylistCertificate ?? false) ? 'YES' : 'NO' }}<br>
                Has Certificate Setting: {{ ($playlistCertificateSetting ?? null) ? 'YES (ID: '.$playlistCertificateSetting->id.')' : 'NO' }}
            </div>
        @endif
        --}}

    </div>
</div>
