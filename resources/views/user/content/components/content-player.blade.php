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

                <div class="pdfviewer owl-pdf-reader owl-pdf-mode-portrait owl-pdf-single-page"
                    id="pdfviewer-shell">

                    {{-- PDF TOOLBAR --}}
                    @include('components.shared.pdf-toolbar', [
                        'toolbarClass' => 'owl-pdf-toolbar',
                        'showReadingMode' => true,
                    ])

                    {{-- PDF VIEWER --}}
                    <div class="owl-pdf-reader-stage">
                        <div class="pdfjs-viewer owl-pdf-viewer content-pdfjs-viewer"></div>

                        <div class="pdf-page-nav" aria-label="PDF page navigation">
                            <button type="button"
                                    id="pdf-previous-page"
                                    class="btn btn-primary pdf-previous-page"
                                    aria-label="Previous Page"
                                    title="Previous Page"
                                    disabled>
                                <i class="ti ti-chevron-left me-1"></i>
                                <span class="pdf-page-nav-label">Previous Page</span>
                            </button>

                            <button type="button"
                                    id="pdf-next-page"
                                    class="btn btn-primary pdf-next-page"
                                    aria-label="Next Page"
                                    title="Next Page"
                                    disabled>
                                <span class="pdf-page-nav-label">Next Page</span>
                                <i class="ti ti-chevron-right ms-1"></i>
                            </button>
                        </div>
                    </div>

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
