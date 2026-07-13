<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">

    <div class="card-body p-4">

        {{-- TOP META --}}
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">

            <span class="badge bg-primary-lt text-primary px-3 py-2 rounded-pill">
                <i class="ti ti-category me-1"></i>
                {{ $content->classification->classification_name ?? '-' }}
            </span>

            <span class="text-muted small">
                <i class="ti ti-calendar-event me-1"></i>
                {{ date('d F Y', strtotime($content->created_at)) }}
            </span>

            <span class="text-muted small">
                <i class="ti ti-user-circle me-1"></i>
                {{ $content->author ?? 'Training Team' }}
            </span>

        </div>

        {{-- TITLE --}}
        <h1 class="content-detail-title mb-3">
            {{ $content->title }}
        </h1>

        {{-- SUBTITLE --}}
        <div class="text-muted mb-4 content-detail-subtitle">
            Develop your competencies and digital learning skills with OWL LMS learning modules.
        </div>

        {{-- ACTION SECTION --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">

            {{-- LEFT --}}
            <div class="d-flex flex-wrap align-items-center gap-2">

                <span class="content-detail-meta">
                    <i class="ti ti-eye me-1"></i>
                    {{ $content->views ?? 0 }} Views
                </span>

                <span class="content-detail-meta">
                    <i class="ti ti-message-circle me-1"></i>
                    {{ $comment->count() }} Comments
                </span>

                @if($content->duration)
                    <span class="content-detail-meta">
                        <i class="ti ti-clock me-1"></i>
                        {{ \App\Support\DurationFormatter::format($content->duration) }}
                    </span>
                @endif

            </div>

                {{-- RIGHT --}}
                <div class="d-flex flex-wrap align-items-center gap-2">

                    @php
                        $isBookmarked = $isBookmarked ?? false;

                        $bookmarkButtonClass = $isBookmarked ? 'btn-primary' : 'btn-outline-primary';
                        $bookmarkIconClass = $isBookmarked ? 'ti-bookmark-filled' : 'ti-bookmark';
                    @endphp

                    {{-- SIMPAN / BOOKMARK --}}
                    <form action="{{ route('content.user.bookmark', $content->slug) }}" method="POST">
                        @csrf

                        <button type="submit" class="btn {{ $bookmarkButtonClass }} rounded-pill px-3">
                            <i class="ti {{ $bookmarkIconClass }} me-1"></i>
                            {{ $isBookmarked ? 'Tersimpan' : 'Simpan' }}
                        </button>
                    </form>

                    {{-- BAGIKAN --}}
                    <button type="button"
                            class="btn btn-outline-primary rounded-pill px-3"
                            onclick="navigator.clipboard.writeText(window.location.href); this.innerHTML='<i class=&quot;ti ti-check me-1&quot;></i> Tersalin';">
                        <i class="ti ti-share me-1"></i>
                        Bagikan
                    </button>

                    @if ($assesment)
                        <a href="{{ route('assesment.user.view', $assesment->slug) }}"
                        class="btn btn-primary rounded-pill px-4
                        {{ $content_user->progress < 100 || ($assessmentCount >= 1 + $assesment->total_remedials && $assessmentLatestScores < $assesment->passing_grade) || $assessmentLatestScores >= $assesment->passing_grade ? 'disabled' : '' }}">

                            <i class="ti ti-clipboard-check me-1"></i>
                            Course Assessment

                        </a>
                    @endif

                </div>

        </div>

    </div>

</div>
