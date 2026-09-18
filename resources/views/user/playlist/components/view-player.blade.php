<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3 view-player-card">
    <div class="card-body p-0">

        @php
            $contentfile    = json_decode($content->file ?? 'null');
            $getcontentfile = is_array($contentfile) ? ($contentfile[0] ?? '') : '';
        @endphp

        <div id="contentContainer" class="learning-player">
            @if ($content->type === 'Video')
                <div class="fullscreen-player-overlays" aria-hidden="true">
                    <span class="fullscreen-pill fullscreen-pill-left">
                        <i class="fas fa-bars me-1"></i>
                        Content List
                    </span>
                    <span class="fullscreen-pill fullscreen-pill-right">
                        <i class="fas fa-robot me-1"></i>
                        AI Assistant
                    </span>
                </div>
            @endif

            @if ($content->type == 'Video')
                <video id="player" playsinline controls
                    @if (!$getcontentfile) style="display:none;" @endif>
                    @if ($getcontentfile)
                        <source src="{{ asset('storage/files/content/' . $content->id . '/' . $getcontentfile) }}"
                                type="video/mp4">
                    @endif
                    Your browser does not support the video tag.
                </video>

            @elseif (in_array($content->type, ['YouTube', 'MOOC']))
                @php
                    $rawLink = trim((string) ($content->link ?? ''));
                    if (str_starts_with($rawLink, 'http://') || str_starts_with($rawLink, 'https://')) {
                        $courseUrl = $rawLink;
                    } elseif (filled($rawLink)) {
                        $courseUrl = 'https://www.youtube.com/watch?v=' . $rawLink;
                    } else {
                        $courseUrl = '#';
                    }

                    $thumbnailPath = public_path('storage/files/content/' . $content->id . '/thumb.jpg');
                    $thumbnailUrl = file_exists($thumbnailPath)
                        ? asset('storage/files/content/' . $content->id . '/thumb.jpg')
                        : null;
                @endphp

                <div class="mooc-playlist-container p-4 p-md-5 text-center bg-surface position-relative rounded-3">
                    @if ($thumbnailUrl)
                        <div class="mb-4 mx-auto overflow-hidden rounded-4 shadow-sm" style="max-width: 480px; aspect-ratio: 16/9; background: #000;">
                            <img src="{{ $thumbnailUrl }}" alt="{{ $content->title }}" class="w-100 h-100" style="object-fit: cover;">
                        </div>
                    @else
                        <div class="mb-4 mx-auto rounded-4 d-flex align-items-center justify-content-center shadow-sm" style="max-width: 480px; aspect-ratio: 16/9; background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: #ffffff;">
                            <div class="text-center p-3">
                                <i class="ti ti-world" style="font-size: 3rem; opacity: 0.9;"></i>
                                <div class="small fw-bold mt-2 text-uppercase" style="letter-spacing: 0.08em;">External Learning Course</div>
                            </div>
                        </div>
                    @endif

                    <div class="d-inline-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-indigo-lt text-indigo px-3 py-1.5 rounded-pill fw-semibold">
                            <i class="ti ti-world me-1"></i> MOOC
                        </span>
                        @if ($content->classification)
                            <span class="badge bg-secondary-lt px-3 py-1.5 rounded-pill">
                                {{ $content->classification->classification_name }}
                            </span>
                        @endif
                    </div>

                    <h2 class="fw-bold mb-2 text-dark">{{ $content->title }}</h2>

                    @if ($content->author)
                        <p class="text-muted mb-3">
                            <i class="ti ti-user me-1"></i> Author / Provider: <strong>{{ $content->author }}</strong>
                        </p>
                    @endif

                    @if ($content->description)
                        <p class="text-muted mx-auto mb-4" style="max-width: 640px; line-height: 1.6;">
                            {{ $content->description }}
                        </p>
                    @endif

                    <div class="d-flex flex-wrap align-items-center justify-content-center gap-3 mt-3">
                        @if ($courseUrl !== '#')
                            <a href="{{ $courseUrl }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="btn btn-primary btn-lg rounded-pill px-4 shadow-sm d-inline-flex align-items-center gap-2">
                                <i class="ti ti-external-link fs-2"></i>
                                <span class="fw-semibold">Open External Course</span>
                            </a>
                        @else
                            <button type="button" class="btn btn-secondary btn-lg rounded-pill px-4" disabled>
                                <i class="ti ti-link-off me-2"></i> No Course URL Available
                            </button>
                        @endif
                    </div>

                    <div class="text-muted small mt-3">
                        <i class="ti ti-info-circle me-1"></i> Opens external platform in a new tab.
                    </div>
                </div>

            @elseif (in_array($content->type, ['Document', 'SOP/Regulation']))
                @if ($getcontentfile)
                    @php
                        $rawDuration = $content->page_duration ?? ($content->duration_page ?? null);
                        $durationValue = $rawDuration !== null ? (int) $rawDuration : 30;
                        $isPlaylistScrollMode = ($content->type === 'Document' && $durationValue === 0);
                        $playlistShellClass = $isPlaylistScrollMode ? 'owl-pdf-scroll-mode' : 'owl-pdf-single-page';
                        $playlistPageDurationVal = $isPlaylistScrollMode ? 0 : $durationValue;
                    @endphp

                    <div class="pdfviewer playlist-pdfviewer owl-pdf-reader owl-pdf-mode-portrait {{ $playlistShellClass }}"
                        id="playlist-pdf-shell"
                        data-reading-mode="{{ $isPlaylistScrollMode ? 'scroll' : 'pagination' }}">
                        @include('components.shared.pdf-toolbar', [
                            'toolbarId' => 'playlist-pdf-toolbar-main',
                            'toolbarClass' => 'playlist-pdf-toolbar owl-pdf-toolbar',
                            'viewerVariable' => 'playlistPdfViewer',
                            'actionPrefix' => 'playlistPdf',
                            'downloadButtonId' => 'playlist-pdf-download-btn',
                            'fullscreenButtonId' => 'playlist-pdf-fs-btn',
                            'fullscreenIconId' => 'playlist-pdf-fs-icon',
                            'showReadingMode' => true,
                            'readingModeButtonId' => 'playlist-pdf-mode-btn',
                            'readingModeIconId' => 'playlist-pdf-mode-icon',
                            'readingModeLabelId' => 'playlist-pdf-mode-label',
                            'previousButtonId' => 'playlist-pdf-previous-page',
                            'nextButtonId' => 'playlist-pdf-next-page',
                            'showPageTiming' => $content->type === 'Document',
                            'pageDuration' => $playlistPageDurationVal,
                            'isScrollMode' => $isPlaylistScrollMode,
                            'showPagination' => !$isPlaylistScrollMode,
                            'totalPages' => (int) ($content->total_page ?? 0),
                            'currentPage' => (int) ($content_user->last_page ?? 1),
                        ])
                        <div class="playlist-pdf-reader-stage owl-pdf-reader-stage">
                            <div class="pdfjs-viewer playlist-pdfjs-viewer owl-pdf-viewer"></div>
                        </div>
                    </div>
                @else
                    <div class="d-flex align-items-center justify-content-center p-5 text-muted">
                        <div class="text-center">
                            <i class="fas fa-file-alt fa-3x mb-3 text-primary opacity-50"></i>
                            <div class="fw-semibold">Document Content</div>
                            <div class="small mt-1">No document file available for this chapter.</div>
                        </div>
                    </div>
                @endif

            @else
                <div class="d-flex align-items-center justify-content-center p-5 text-muted">
                    <div class="text-center">
                        <i class="fas fa-exclamation-triangle fa-3x mb-3 text-warning opacity-50"></i>
                        <div class="fw-semibold">No media available</div>
                    </div>
                </div>
            @endif

            @if ($content->type === 'Video')
                <input type="hidden" id="player_duration" value="{{ \App\Support\DurationFormatter::toSeconds($content->duration) ?? 0 }}">
            @endif

            <input type="hidden" id="player_progress" value="{{ $content_user->progress ?? 0 }}">
            <input type="hidden" data-url="{{ route('content.user.progress', $content->slug) }}" id="player_paused">

        </div>
    </div>
</div>
