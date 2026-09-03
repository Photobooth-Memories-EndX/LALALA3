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
                @php
                    $rawDuration = $content->page_duration ?? ($content->duration_page ?? null);
                    $durationValue = $rawDuration !== null ? (int) $rawDuration : 30;
                    $isScrollMode = ($content->type === 'Document' && $durationValue === 0);
                    $shellModeClass = $isScrollMode ? 'owl-pdf-scroll-mode' : 'owl-pdf-single-page';
                    $pageDurationVal = $isScrollMode ? 0 : $durationValue;
                @endphp

                <div class="pdfviewer owl-pdf-reader owl-pdf-mode-portrait {{ $shellModeClass }}"
                    id="pdfviewer-shell"
                    data-reading-mode="{{ $isScrollMode ? 'scroll' : 'pagination' }}">

                    {{-- PDF TOOLBAR --}}
                    @include('components.shared.pdf-toolbar', [
                        'toolbarClass' => 'owl-pdf-toolbar',
                        'showReadingMode' => true,
                        'showPageTiming' => $content->type === 'Document',
                        'pageDuration' => $pageDurationVal,
                        'isScrollMode' => $isScrollMode,
                        'showPagination' => !$isScrollMode,
                    ])

                    {{-- PDF VIEWER --}}
                    <div class="owl-pdf-reader-stage">
                        <div class="pdfjs-viewer owl-pdf-viewer content-pdfjs-viewer"></div>
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
