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
                } elseif (in_array($content->type, ['YouTube', 'MOOC'])) {
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
                        'totalPages' => (int) ($content->total_page ?? 0),
                        'currentPage' => (int) ($content_user->last_page ?? 1),
                    ])

                    {{-- PDF VIEWER --}}
                    <div class="owl-pdf-reader-stage">
                        <div class="pdfjs-viewer owl-pdf-viewer content-pdfjs-viewer"></div>
                    </div>

                </div>

            {{-- MOOC / EXTERNAL COURSE --}}
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

                <div class="mooc-course-container p-4 p-md-5 text-center bg-surface position-relative rounded-3">
                    @if ($thumbnailUrl)
                        <div class="mb-4 mx-auto overflow-hidden rounded-4 shadow-sm" style="max-width: 520px; aspect-ratio: 16/9; background: #000;">
                            <img src="{{ $thumbnailUrl }}" alt="{{ $content->title }}" class="w-100 h-100" style="object-fit: cover;">
                        </div>
                    @else
                        <div class="mb-4 mx-auto rounded-4 d-flex align-items-center justify-content-center shadow-sm" style="max-width: 520px; aspect-ratio: 16/9; background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: #ffffff;">
                            <div class="text-center p-3">
                                <i class="ti ti-world" style="font-size: 3.5rem; opacity: 0.9;"></i>
                                <div class="small fw-bold mt-2 text-uppercase" style="letter-spacing: 0.08em;">External Learning Course</div>
                            </div>
                        </div>
                    @endif

                    <div class="d-inline-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-indigo-lt text-indigo px-3 py-1.5 rounded-pill fw-semibold">
                            <i class="ti ti-world me-1"></i> External Course
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
                            <i class="ti ti-user me-1"></i> Provider / Author: <strong>{{ $content->author }}</strong>
                        </p>
                    @endif

                    @if ($content->description)
                        <p class="text-muted mx-auto mb-4" style="max-width: 680px; line-height: 1.6;">
                            {{ $content->description }}
                        </p>
                    @endif

                    <div class="d-flex flex-wrap align-items-center justify-content-center gap-3 mt-4">
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
                        <i class="ti ti-info-circle me-1"></i> This course will open in a new tab on an external platform.
                    </div>
                </div>

            @endif

        </div>

    </div>

</div>

{{-- DURATION --}}
@if ($content->type == 'Video')
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
    value="{{ $content_user->progress ?? 0 }}"
>

<input
    type="hidden"
    data-url="{{ route('content.user.progress', $content->slug) }}"
    id="player_paused"
>
