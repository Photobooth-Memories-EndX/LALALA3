<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">
    <div class="card-body p-0">

        @php
            $contentfile    = json_decode($content->file ?? 'null');
            $getcontentfile = is_array($contentfile) ? ($contentfile[0] ?? '') : '';
        @endphp

        <div id="contentContainer" class="learning-player">
            @if (in_array($content->type, ['Video', 'YouTube']))
                <div class="fullscreen-player-overlays" aria-hidden="true">
                    <span class="fullscreen-pill fullscreen-pill-left">
                        <i class="fas fa-list-ul me-1"></i>
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

            @elseif ($content->type == 'YouTube')
                <div id="player"
                     data-plyr-provider="youtube"
                     data-plyr-embed-id="{{ $content->link }}">
                </div>

            @elseif (in_array($content->type, ['Document', 'SOP/Regulation']))
                @if ($getcontentfile)
                    <div class="playlist-pdfviewer" id="playlist-pdf-shell">
                        <div class="d-flex align-items-center justify-content-center flex-wrap gap-2 p-2 border-bottom playlist-pdf-toolbar">
                            <button type="button" class="btn btn-sm btn-secondary"
                                onclick="playlistPdfViewer.prev(); return false;" title="Previous Page">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <span class="pageno small fw-bold px-2"></span>
                            <button type="button" class="btn btn-sm btn-secondary"
                                onclick="playlistPdfViewer.next(); return false;" title="Next Page">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary"
                                onclick="playlistPdfViewer.setZoom('out')" title="Zoom Out">
                                <i class="fas fa-search-minus"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary"
                                onclick="playlistPdfViewer.setZoom('in')" title="Zoom In">
                                <i class="fas fa-search-plus"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary"
                                onclick="playlistPdfViewer.setZoom('width')" title="Fit Width">
                                <i class="fas fa-arrows-alt-h"></i>
                            </button>
                        </div>
                        <div class="pdfjs-viewer playlist-pdfjs-viewer" style="height:560px; overflow:hidden;"></div>
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

            @if (in_array($content->type, ['Video', 'YouTube']))
                <input type="hidden" id="player_duration" value="{{ \App\Support\DurationFormatter::toSeconds($content->duration) ?? 0 }}">
            @endif

            <input type="hidden" id="player_progress" value="{{ $content_user->progress ?? 0 }}">
            <input type="hidden" data-url="{{ route('content.user.progress', $content->slug) }}" id="player_paused">

        </div>
    </div>
</div>
