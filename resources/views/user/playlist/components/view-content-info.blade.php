@php
    $commentTotal = method_exists($comment, 'total') ? $comment->total() : $comment->count();
@endphp

<div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body p-4">

        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
            <div>
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

            <div class="d-flex gap-2">
                {{-- <button type="button" class="btn btn-icon btn-outline-secondary rounded-3">
                    <i class="far fa-bookmark"></i>
                </button> --}}

            <button type="button"
                    class="btn btn-icon btn-outline-secondary rounded-3"
                    onclick="shareContent()">
                <i class="fas fa-share-alt"></i>
            </button>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mb-3">
            <a href="#" class="btn btn-light border-0 shadow-none text-muted">
                <i class="fas fa-eye me-2"></i>
                {{ $content->views ?? 0 }} Views
            </a>

            <a href="#comment" class="btn btn-light border-0 shadow-none text-muted">
                <i class="far fa-comment-dots me-2"></i>
                {{ $commentTotal }} Comments
            </a>

            <span class="btn btn-light border-0 shadow-none text-muted">
                <i class="far fa-clock me-2"></i>
                {{ $content->duration ?? '-' }}
            </span>
        </div>

        <div class="chapter-description-box">
            <h4 class="fs-5 fw-bold mb-2">Overview</h4>

            <p class="mb-3 text-muted">
                {!! nl2br($content->description) !!}
            </p>

            <h4 class="fs-5 fw-bold mb-3">What you'll learn:</h4>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="learn-item">
                        <i class="fas fa-check-circle text-success"></i>
                        <span>Memahami materi utama pada chapter ini</span>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="learn-item">
                        <i class="fas fa-check-circle text-success"></i>
                        <span>Menyelesaikan progress pembelajaran</span>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="learn-item">
                        <i class="fas fa-check-circle text-success"></i>
                        <span>Meningkatkan pemahaman materi</span>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="learn-item">
                        <i class="fas fa-check-circle text-success"></i>
                        <span>Siap lanjut ke chapter berikutnya</span>
                    </div>
                </div>
            </div>
        </div>

{{-- Playlist Assessment & Sertifikat --}}
<div class="d-flex flex-wrap gap-2">

    {{-- 1. Status progress --}}
    @if ($playlistProgressComplete)
        <button type="button" class="btn btn-success" disabled>
            <i class="fas fa-check me-2"></i>
            Semua Content Selesai
        </button>
    @else
        <button type="button" class="btn btn-secondary" disabled>
            <i class="fas fa-lock me-2"></i>
            Selesaikan Semua Content Terlebih Dahulu
        </button>
    @endif


    {{-- 2. Tombol Playlist Assessment --}}
    @if ($hasPlaylistAssessment)
        <a href="{{ $playlistProgressComplete && !$playlistAssessmentDone ? route('assesment.user.view', $assesment->slug) : '#' }}"
           class="btn btn-primary {{ !$playlistProgressComplete || $playlistAssessmentDone ? 'disabled' : '' }}"
           aria-disabled="{{ !$playlistProgressComplete || $playlistAssessmentDone ? 'true' : 'false' }}">
            <i class="fas fa-tasks me-2"></i>
            {{ $playlistAssessmentDone ? 'Playlist Assessment Selesai' : 'Kerjakan Playlist Assessment' }}
        </a>
    @else
        <button type="button" class="btn btn-secondary" disabled>
            <i class="fas fa-info-circle me-2"></i>
            Playlist Assessment Tidak Ada
        </button>
    @endif


    {{-- 3. Tombol Cetak Sertifikat --}}
    @if ($playlistCertificateSetting)
        <form method="POST"
              action="{{ route('certificate.generate.setting', $playlistCertificateSetting) }}"
              class="d-inline">
            @csrf
            <button type="submit"
                    class="btn btn-success"
                    {{ $canGeneratePlaylistCertificate ? '' : 'disabled' }}>
                <i class="fas fa-certificate me-2"></i>
                Cetak Sertifikat Playlist
            </button>
        </form>
    @else
        <button type="button" class="btn btn-secondary" disabled>
            <i class="fas fa-certificate me-2"></i>
            Sertifikat Belum Dikonfigurasi
        </button>
    @endif

</div>
        {{-- Debug block (hanya muncul di APP_DEBUG=true) --}}
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

    </div>
</div>