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

                <div class="pdfviewer">

                    {{-- PDF TOOLBAR --}}
                    <div class="pdf-toolbar">

                        <div class="d-flex flex-wrap gap-2 justify-content-center align-items-center">

                            <button class="btn btn-sm btn-secondary"
                                onclick="pdfViewer.first()">
                                <i class="ti ti-player-skip-back"></i>
                            </button>

                            <button class="btn btn-sm btn-secondary"
                                onclick="pdfViewer.prev(); return false;">
                                <i class="ti ti-chevron-left"></i>
                            </button>

                            <span class="pageno small fw-bold"></span>

                            <button class="btn btn-sm btn-secondary"
                                onclick="pdfViewer.next(); return false;">
                                <i class="ti ti-chevron-right"></i>
                            </button>

                            <button class="btn btn-sm btn-secondary"
                                onclick="pdfViewer.last()">
                                <i class="ti ti-player-skip-forward"></i>
                            </button>

                            <div class="vr mx-2"></div>

                            <button class="btn btn-sm btn-secondary"
                                onclick="pdfViewer.setZoom('out')">
                                <i class="ti ti-zoom-out"></i>
                            </button>

                            <span class="zoomval small fw-bold">100%</span>

                            <button class="btn btn-sm btn-secondary"
                                onclick="pdfViewer.setZoom('in')">
                                <i class="ti ti-zoom-in"></i>
                            </button>

                            <button class="btn btn-sm btn-secondary"
                                onclick="pdfViewer.setZoom('width')">
                                Fit Width
                            </button>

                            <button class="btn btn-sm btn-secondary"
                                onclick="pdfViewer.setZoom('fit')">
                                Fit Page
                            </button>

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

    @php
        $time_duration = explode(':', $content->duration);

        $minutes = (int)$time_duration[0];
        $seconds = (int)$time_duration[1];
    @endphp

    <input
        type="hidden"
        id="player_duration"
        value="{{ ($minutes*60)+$seconds }}"
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