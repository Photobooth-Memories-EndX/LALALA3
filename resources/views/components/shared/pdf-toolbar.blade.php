@php
    $pdfToolbarId = $toolbarId ?? 'pdf-toolbar-main';
    $pdfToolbarClass = $toolbarClass ?? '';
    $pdfViewerVariable = $viewerVariable ?? 'pdfViewer';
    $pdfActionPrefix = $actionPrefix ?? 'pdf';
    $pdfDownloadButtonId = $downloadButtonId ?? 'pdf-download-btn';
    $pdfFullscreenButtonId = $fullscreenButtonId ?? 'pdf-fs-btn';
    $pdfFullscreenIconId = $fullscreenIconId ?? 'pdf-fs-icon';
    $pdfShowReadingMode = (bool) ($showReadingMode ?? false);
    $pdfReadingModeButtonId = $readingModeButtonId ?? 'pdf-reading-mode-btn';
    $pdfReadingModeIconId = $readingModeIconId ?? 'pdf-reading-mode-icon';
    $pdfReadingModeLabelId = $readingModeLabelId ?? 'pdf-reading-mode-label';
    $pdfPreviousButtonId = $previousButtonId ?? 'pdf-previous-page';
    $pdfNextButtonId = $nextButtonId ?? 'pdf-next-page';
    $pdfShowPageTiming = (bool) ($showPageTiming ?? false);
    $rawDuration = $pageDuration
        ?? ($duration_page
        ?? (isset($content) ? ($content->page_duration ?? ($content->duration_page ?? null)) : null));
    $pdfPageDuration = $rawDuration !== null ? (int) $rawDuration : 30;
    $pdfIsScrollMode = isset($isScrollMode)
        ? (bool) $isScrollMode
        : ($rawDuration !== null && (int) $rawDuration === 0);
    $pdfShowPagination = isset($showPagination)
        ? (bool) $showPagination
        : !$pdfIsScrollMode;
    $pdfTotalPages = isset($totalPages)
        ? (int) $totalPages
        : (isset($content->total_page) ? (int) $content->total_page : null);
    $pdfCurrentPage = isset($currentPage)
        ? (int) $currentPage
        : (isset($content_user->last_page) && (int) $content_user->last_page > 0 ? (int) $content_user->last_page : 1);
    $pdfInitialPageno = ($pdfTotalPages && $pdfTotalPages > 0) ? "{$pdfCurrentPage} / {$pdfTotalPages}" : "-- / --";
@endphp

<div class="pdf-toolbar {{ $pdfToolbarClass }}" id="{{ $pdfToolbarId }}" role="toolbar" aria-label="PDF Controls">
    <div class="pdf-tb-inner">
        <div class="pdf-tb-group">
            <button type="button" class="btn btn-sm btn-secondary pdf-tb-btn"
                onclick="{{ $pdfViewerVariable }}.setZoom('out'); return false;"
                title="Zoom Out (Ctrl+-)">
                <i class="ti ti-zoom-out"></i>
            </button>
            <span class="zoomval pdf-tb-zoomval small fw-bold">100%</span>
            <button type="button" class="btn btn-sm btn-secondary pdf-tb-btn"
                onclick="{{ $pdfViewerVariable }}.setZoom('in'); return false;"
                title="Zoom In (Ctrl++)">
                <i class="ti ti-zoom-in"></i>
            </button>
            <button type="button" class="btn btn-sm btn-secondary pdf-tb-btn"
                onclick="{{ $pdfViewerVariable }}.setZoom('width'); return false;"
                title="Fit Width (F)">
                <i class="ti ti-arrows-horizontal"></i>
                <span class="pdf-tb-label">W</span>
            </button>
            <button type="button" class="btn btn-sm btn-secondary pdf-tb-btn"
                onclick="{{ $pdfViewerVariable }}.setZoom('fit'); return false;"
                title="Fit Page (Ctrl+0)">
                <i class="ti ti-arrows-maximize"></i>
                <span class="pdf-tb-label">P</span>
            </button>
        </div>

        <div class="pdf-tb-sep pdf-tb-sep-md"></div>

        <div class="pdf-tb-group">
            <button type="button" class="btn btn-sm btn-secondary pdf-tb-btn pdf-tb-hide-xs"
                onclick="{{ $pdfActionPrefix }}RotateLeft(); return false;"
                title="Rotate Left">
                <i class="ti ti-rotate-2"></i>
            </button>
            <button type="button" class="btn btn-sm btn-secondary pdf-tb-btn pdf-tb-hide-xs"
                onclick="{{ $pdfActionPrefix }}RotateRight(); return false;"
                title="Rotate Right">
                <i class="ti ti-rotate-clockwise-2"></i>
            </button>
            @if ($pdfShowReadingMode)
                <button type="button" class="btn btn-sm btn-secondary pdf-tb-btn pdf-tb-mode-btn"
                    onclick="{{ $pdfActionPrefix }}ToggleReadingMode(); return false;"
                    id="{{ $pdfReadingModeButtonId }}"
                    title="Switch to Landscape layout"
                    aria-pressed="false">
                    <i class="ti ti-rectangle-vertical" id="{{ $pdfReadingModeIconId }}"></i>
                    <span class="pdf-tb-label pdf-tb-mode-label" id="{{ $pdfReadingModeLabelId }}">Portrait</span>
                </button>
            @endif
            <!-- <a id="{{ $pdfDownloadButtonId }}" href="#" download
                class="btn btn-sm btn-secondary pdf-tb-btn"
                title="Download PDF">
                <i class="ti ti-download"></i>
            </a> -->
            <!-- <button type="button" class="btn btn-sm btn-secondary pdf-tb-btn pdf-tb-hide-xs"
                onclick="{{ $pdfActionPrefix }}DoPrint(); return false;"
                title="Print PDF">
                <i class="ti ti-printer"></i>
            </button> -->
            <button type="button" class="btn btn-sm btn-secondary pdf-tb-btn"
                onclick="{{ $pdfActionPrefix }}ToggleFullscreen(); return false;"
                id="{{ $pdfFullscreenButtonId }}"
                title="Fullscreen">
                <i class="ti ti-maximize" id="{{ $pdfFullscreenIconId }}"></i>
            </button>
        </div>
    </div>
</div>

<nav class="pdf-page-navigation {{ $pdfIsScrollMode ? 'is-scroll-mode' : '' }}" aria-label="PDF page navigation">
    @if ($pdfShowPagination)
    <button type="button"
            class="btn btn-primary pdf-page-navigation-btn pdf-page-navigation-previous"
            id="{{ $pdfPreviousButtonId }}"
            data-pdf-nav="previous"
            aria-label="Previous Page"
            disabled
            onclick="{{ $pdfViewerVariable }}.prev(); return false;"
            title="Previous Page (Arrow Left / PageUp / Shift+Space)">
        <i class="ti ti-chevron-left" aria-hidden="true"></i>
        <span>Previous</span>
    </button>
    @endif

    <div class="pdf-page-navigation-status" aria-live="polite">
        <span class="pdf-tb-reading-stat pdf-tb-stat-page">
            <span class="pdf-tb-stat-label">Page</span>
            <strong class="pageno pdf-tb-pageno">{{ $pdfInitialPageno }}</strong>
        </span>

        @if ($pdfShowPagination && $pdfShowPageTiming && $pdfPageDuration > 0)
            <div class="pdf-tb-reading-timing"
                 data-page-duration="{{ $pdfPageDuration }}"
                 aria-label="PDF minimum page reading time">
                <span class="pdf-tb-reading-stat pdf-tb-stat-minimum">
                    <span class="pdf-tb-stat-label">Minimum Time</span>
                    <strong><span class="pdf-tb-minimum-time-value">{{ $pdfPageDuration }}</span> sec</strong>
                </span>
                <span class="pdf-tb-reading-stat pdf-tb-stat-countdown">
                    <span class="pdf-tb-stat-label">Next Page In</span>
                    <strong><span class="pdf-tb-remaining-time-value">{{ $pdfPageDuration }}</span> sec</strong>
                </span>
            </div>
        @elseif ($pdfShowPagination && $pdfShowPageTiming && $pdfPageDuration === 0)
            <div class="pdf-tb-reading-timing is-scroll-mode"
                 data-page-duration="0"
                 aria-label="PDF self-paced reading mode">
                <span class="pdf-tb-reading-stat pdf-tb-stat-self-paced">
                    <i class="ti ti-flame text-primary" aria-hidden="true"></i>
                    <span class="pdf-tb-stat-label">Mode</span>
                    <strong>Self-paced</strong>
                </span>
            </div>
        @endif
    </div>

    @if ($pdfShowPagination)
    <button type="button"
            class="btn btn-primary pdf-page-navigation-btn pdf-page-navigation-next"
            id="{{ $pdfNextButtonId }}"
            data-pdf-nav="next"
            aria-label="Next Page"
            disabled
            onclick="{{ $pdfViewerVariable }}.next(); return false;"
            title="Next Page (Arrow Right / PageDown / Space)">
        <span>Next</span>
        <i class="ti ti-chevron-right" aria-hidden="true"></i>
    </button>
    @endif
</nav>
