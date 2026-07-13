<div class="card border-0 shadow-sm overflow-hidden mb-4">

    <div class="card-body p-0">

        @php
            $contentfile = json_decode($content->file);
        @endphp

        <div id="contentContainer">

            @php
                if (isset($contentfile[0])) {
                    $getcontentfile = $contentfile[0];
                    $displaycontent = 'display:block;';
                } elseif ($content->type == 'YouTube') {
                    $getcontentfile = '';
                    $displaycontent = 'display:none;';
                } else {
                    $getcontentfile = '';
                    $displaycontent = 'display:none;';
                    echo '<div class="p-5 text-center">No file exist</div>';
                }
            @endphp

            {{-- VIDEO --}}
            @if ($content->type == 'Video')

                <video
                    id="player"
                    playsinline
                    controls
                    style="width:100%; height:auto; {{ $displaycontent }}"
                >
                    <source
                        src="{{ asset('storage/files/content/' . $content->id . '/' . $getcontentfile) }}"
                        type="video/mp4"
                    >

                    Your browser does not support video.
                </video>

                <input
                    type="hidden"
                    name="replacecontent"
                    id="replacecontent"
                    value="{{ $getcontentfile }}"
                >

            {{-- DOCUMENT --}}
            @elseif ($content->type == 'Document' || $content->type == 'SOP/Regulation')

                <div class="pdfviewer" id="pdfviewer-shell">

                    {{-- PDF TOOLBAR --}}
                    <div class="pdf-toolbar" id="pdf-toolbar-main" role="toolbar" aria-label="PDF Controls">
                        <div class="pdf-tb-inner">

                            {{-- Group 1: Navigation --}}
                            <div class="pdf-tb-group">
                                <button class="btn btn-sm btn-secondary pdf-tb-btn"
                                    onclick="pdfViewer.first()"
                                    title="First Page (Home)">
                                    <i class="ti ti-player-skip-back"></i>
                                </button>
                                <button class="btn btn-sm btn-secondary pdf-tb-btn"
                                    onclick="pdfViewer.prev(); return false;"
                                    title="Previous Page (← / PageUp / Shift+Space)">
                                    <i class="ti ti-chevron-left"></i>
                                </button>
                                <span class="pageno pdf-tb-pageno small fw-bold"></span>
                                <button class="btn btn-sm btn-secondary pdf-tb-btn"
                                    onclick="pdfViewer.next(); return false;"
                                    title="Next Page (→ / PageDown / Space)">
                                    <i class="ti ti-chevron-right"></i>
                                </button>
                                <button class="btn btn-sm btn-secondary pdf-tb-btn"
                                    onclick="pdfViewer.last()"
                                    title="Last Page (End)">
                                    <i class="ti ti-player-skip-forward"></i>
                                </button>
                            </div>

                            <div class="pdf-tb-sep"></div>

                            {{-- Group 2: Zoom --}}
                            <div class="pdf-tb-group">
                                <button class="btn btn-sm btn-secondary pdf-tb-btn"
                                    onclick="pdfViewer.setZoom('out')"
                                    title="Zoom Out (Ctrl+-)">
                                    <i class="ti ti-zoom-out"></i>
                                </button>
                                <span class="zoomval pdf-tb-zoomval small fw-bold">100%</span>
                                <button class="btn btn-sm btn-secondary pdf-tb-btn"
                                    onclick="pdfViewer.setZoom('in')"
                                    title="Zoom In (Ctrl++)">
                                    <i class="ti ti-zoom-in"></i>
                                </button>
                                <button class="btn btn-sm btn-secondary pdf-tb-btn"
                                    onclick="pdfViewer.setZoom('width')"
                                    title="Fit Width (F)">
                                    <i class="ti ti-arrows-horizontal"></i>
                                    <span class="pdf-tb-label">W</span>
                                </button>
                                <button class="btn btn-sm btn-secondary pdf-tb-btn"
                                    onclick="pdfViewer.setZoom('fit')"
                                    title="Fit Page (Ctrl+0)">
                                    <i class="ti ti-arrows-maximize"></i>
                                    <span class="pdf-tb-label">P</span>
                                </button>
                            </div>

                            <div class="pdf-tb-sep pdf-tb-sep-md"></div>

                            {{-- Group 3: Rotate + Download + Print + Fullscreen --}}
                            <div class="pdf-tb-group">
                                <button class="btn btn-sm btn-secondary pdf-tb-btn pdf-tb-hide-xs"
                                    onclick="pdfRotateLeft()"
                                    title="Rotate Left">
                                    <i class="ti ti-rotate-2"></i>
                                </button>
                                <button class="btn btn-sm btn-secondary pdf-tb-btn pdf-tb-hide-xs"
                                    onclick="pdfRotateRight()"
                                    title="Rotate Right">
                                    <i class="ti ti-rotate-clockwise-2"></i>
                                </button>
                                <a id="pdf-download-btn" href="#" download
                                    class="btn btn-sm btn-secondary pdf-tb-btn"
                                    title="Download PDF">
                                    <i class="ti ti-download"></i>
                                </a>
                                <button class="btn btn-sm btn-secondary pdf-tb-btn pdf-tb-hide-xs"
                                    onclick="pdfDoPrint()"
                                    title="Print PDF">
                                    <i class="ti ti-printer"></i>
                                </button>
                                <button class="btn btn-sm btn-secondary pdf-tb-btn"
                                    onclick="pdfToggleFullscreen()"
                                    id="pdf-fs-btn"
                                    title="Fullscreen">
                                    <i class="ti ti-maximize" id="pdf-fs-icon"></i>
                                </button>
                            </div>

                        </div>
                    </div>

                    {{-- PDF VIEWER --}}
                    <div class="pdfjs-viewer"></div>

                </div>

            {{-- YOUTUBE --}}
            @elseif ($content->type == 'YouTube')

                <div
                    id="player"
                    data-plyr-provider="youtube"
                    data-plyr-embed-id="{{ $content->link }}"
                ></div>

            @endif

        </div>

    </div>

</div>

{{-- DURATION --}}
@if (($content->type == 'Video') || ($content->type == 'YouTube'))
    <input
        type="hidden"
        id="player_duration"
        value="{{ \App\Support\DurationFormatter::toSeconds($content->duration) ?? 0 }}"
    >

@endif

{{-- PROGRESS --}}
<input
    type="hidden"
    id="player_progress"
    value="{{ $content_user->progress }}"
>

<input
    type="hidden"
    data-url="{{ route('content.user.progress', $content->slug) }}"
    id="player_paused"
>
