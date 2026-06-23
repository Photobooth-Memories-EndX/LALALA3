@php
    $commentTotal = method_exists($comment, 'total') ? $comment->total() : $comment->count();
@endphp

<div class="card border-0 shadow-sm rounded-4 mb-3">
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

                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-blue-lt text-blue">
                        {{ $content->classification->classification_name ?? 'Learning' }}
                    </span>

                    <span class="badge bg-green-lt text-green">
                        {{ $content->type ?? 'Content' }}
                    </span>
                </div>
            </div>

            <button type="button"
                    class="btn btn-icon btn-outline-secondary rounded-3"
                    onclick="shareContent()">
                <i class="fas fa-share-alt"></i>
            </button>
        </div>

        {{-- Meta --}}
        <div class="d-flex flex-wrap gap-2 mb-3">
            <span class="btn btn-light border-0 shadow-none text-muted">
                <i class="fas fa-eye me-2"></i>
                {{ $content->views ?? 0 }} Views
            </span>

            <a href="#comment" class="btn btn-light border-0 shadow-none text-muted">
                <i class="far fa-comment-dots me-2"></i>
                {{ $commentTotal }} Comments
            </a>

            <span class="btn btn-light border-0 shadow-none text-muted">
                <i class="far fa-clock me-2"></i>
                {{ $content->duration ?? '-' }}
            </span>
        </div>

        {{-- Overview --}}
        <div class="chapter-description-box mb-4">
            <h4 class="fs-5 fw-bold mb-2">Overview</h4>

            <p class="mb-3 text-muted">
                {!! nl2br($content->description) !!}
            </p>

            <h4 class="fs-5 fw-bold mb-3">What you'll learn:</h4>

            <div class="row g-3">
                @foreach ([
                    'Memahami materi utama pada chapter ini',
                    'Menyelesaikan progress pembelajaran',
                    'Meningkatkan pemahaman materi',
                    'Siap lanjut ke chapter berikutnya',
                ] as $learnItem)
                    <div class="col-md-6">
                        <div class="learn-item">
                            <i class="fas fa-check-circle text-success"></i>
                            <span>{{ $learnItem }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Playlist Actions --}}
        <div class="border-top pt-3 mt-4">
            <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3">

                {{-- Status Info --}}
                <div class="d-flex flex-wrap align-items-center gap-2">

                    @if ($playlistProgressComplete)
                        <span class="badge bg-success-lt text-success px-3 py-2">
                            <i class="fas fa-check-circle me-1"></i>
                            Semua Content Selesai
                        </span>
                    @else
                        <span class="badge bg-secondary-lt text-secondary px-3 py-2">
                            <i class="fas fa-lock me-1"></i>
                            Progress Belum 100%
                        </span>
                    @endif

                    @if ($hasPlaylistAssessment)
                        @if ($playlistAssessmentDone)
                            <span class="badge bg-success-lt text-success px-3 py-2">
                                <i class="fas fa-tasks me-1"></i>
                                Playlist Assessment Selesai
                            </span>
                        @else
                            <span class="badge bg-primary-lt text-primary px-3 py-2">
                                <i class="fas fa-tasks me-1"></i>
                                Playlist Assessment Tersedia
                            </span>
                        @endif
                    @else
                        <span class="badge bg-secondary-lt text-secondary px-3 py-2">
                            <i class="fas fa-info-circle me-1"></i>
                            Playlist Assessment Tidak Ada
                        </span>
                    @endif

                    @if ($playlistCertificateSetting)
                        <span class="badge bg-green-lt text-green px-3 py-2">
                            <i class="fas fa-certificate me-1"></i>
                            Sertifikat Tersedia
                        </span>
                    @else
                        <span class="badge bg-secondary-lt text-secondary px-3 py-2">
                            <i class="fas fa-certificate me-1"></i>
                            Sertifikat Belum Dikonfigurasi
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
                                Cetak Sertifikat Playlist
                            </button>
                        </form>
                    @endif

                </div>

            </div>

            @if (!$canGeneratePlaylistCertificate)
                <div class="text-muted small mt-2">
                    Sertifikat dapat dicetak setelah seluruh syarat playlist terpenuhi.
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