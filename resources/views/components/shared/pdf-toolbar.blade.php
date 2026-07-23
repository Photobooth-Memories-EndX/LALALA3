@php
    $pdfToolbarId = $toolbarId ?? 'pdf-toolbar-main';
    $pdfToolbarClass = $toolbarClass ?? '';
    $pdfViewerVariable = $viewerVariable ?? 'pdfViewer';
    $pdfActionPrefix = $actionPrefix ?? 'pdf';
    $pdfDownloadButtonId = $downloadButtonId ?? 'pdf-download-btn';
    $pdfFullscreenButtonId = $fullscreenButtonId ?? 'pdf-fs-btn';
    $pdfFullscreenIconId = $fullscreenIconId ?? 'pdf-fs-icon';
@endphp

<div class="pdf-toolbar {{ $pdfToolbarClass }}" id="{{ $pdfToolbarId }}" role="toolbar" aria-label="PDF Controls">
    <div class="pdf-tb-inner">
        <div class="pdf-tb-group">
            {{-- <button type="button" class="btn btn-sm btn-secondary pdf-tb-btn"
                onclick="{{ $pdfViewerVariable }}.first(); return false;"
                title="First Page (Home)">
                <i class="ti ti-player-skip-back"></i>
            </button> --}}
            <button type="button" class="btn btn-sm btn-secondary pdf-tb-btn"
                onclick="{{ $pdfViewerVariable }}.prev(); return false;"
                title="Previous Page (Arrow Left / PageUp / Shift+Space)">
                <i class="ti ti-chevron-left"></i>
            </button>
            <span class="pageno pdf-tb-pageno small fw-bold"></span>
            <button type="button" class="btn btn-sm btn-secondary pdf-tb-btn"
                onclick="{{ $pdfViewerVariable }}.next(); return false;"
                title="Next Page (Arrow Right / PageDown / Space)">
                <i class="ti ti-chevron-right"></i>
            {{-- </button>
            <button type="button" class="btn btn-sm btn-secondary pdf-tb-btn"
                onclick="{{ $pdfViewerVariable }}.last(); return false;"
                title="Last Page (End)">
                <i class="ti ti-player-skip-forward"></i>
            </button> --}}
        </div>

        <div class="pdf-tb-sep"></div>

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
            <a id="{{ $pdfDownloadButtonId }}" href="#" download
                class="btn btn-sm btn-secondary pdf-tb-btn"
                title="Download PDF">
                <i class="ti ti-download"></i>
            </a>
            <button type="button" class="btn btn-sm btn-secondary pdf-tb-btn pdf-tb-hide-xs"
                onclick="{{ $pdfActionPrefix }}DoPrint(); return false;"
                title="Print PDF">
                <i class="ti ti-printer"></i>
            </button>
            <button type="button" class="btn btn-sm btn-secondary pdf-tb-btn"
                onclick="{{ $pdfActionPrefix }}ToggleFullscreen(); return false;"
                id="{{ $pdfFullscreenButtonId }}"
                title="Fullscreen">
                <i class="ti ti-maximize" id="{{ $pdfFullscreenIconId }}"></i>
            </button>
        </div>
    </div>
</div>
