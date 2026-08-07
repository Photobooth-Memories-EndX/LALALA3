@extends('layouts.sidebar-master')

@section('content')
@php
    $setting = $certificate->certificateSetting;
    $hasCustomTemplate = $setting
        && !(bool) $setting->use_default_template
        && (
            !empty($setting->certificate_template)
            || !empty($setting->custom_background)
            || !empty($setting->background_image)
        );
    $documentUrl = route('certificate.viewer.document', $certificate->credential_id);
@endphp

<div class="certificate-viewer-page">
    <main class="certificate-viewer-shell" aria-labelledby="certificateViewerTitle">
        <header class="certificate-viewer-header">
            <a href="{{ route('user.learning.dashboard', ['learning_tab' => 'certificate']) }}"
               class="btn btn-outline-secondary certificate-viewer-back"
               title="Back to Certificates">
                <i class="ti ti-arrow-left" aria-hidden="true"></i>
                <span class="d-none d-md-inline">Back to Certificates</span>
            </a>

            <div class="certificate-viewer-title-wrap">
                <div class="certificate-viewer-status">
                    <span class="badge bg-green-lt text-green">
                        <i class="ti ti-circle-check me-1" aria-hidden="true"></i>
                        Certificate Generated
                    </span>
                </div>
                <h1 id="certificateViewerTitle" class="certificate-viewer-title">
                    {{ $certificate->title }}
                </h1>
                <div class="certificate-viewer-meta">
                    {{ $certificate->certificate_number }}
                </div>
            </div>

            <div class="certificate-viewer-header-spacer" aria-hidden="true"></div>
        </header>

        <section class="certificate-viewer-stage" id="certificateViewerStage" aria-label="PDF certificate document">
            <div class="certificate-viewer-loading" id="certificateViewerLoading" role="status">
                <span class="spinner-border text-primary" aria-hidden="true"></span>
                <span>Loading certificate document...</span>
            </div>

            <div class="certificate-viewer-error d-none" id="certificateViewerError" role="alert">
                <i class="ti ti-alert-circle text-danger" style="font-size: 2.5rem;" aria-hidden="true"></i>
                <div class="fw-bold mt-2 mb-1" style="font-size: 1.1rem;">Failed to load document</div>
                <div class="text-secondary small mb-3" id="certificateViewerErrorMessage">An error occurred while loading the PDF certificate.</div>
                <button type="button" class="btn btn-sm btn-primary" id="certificateViewerRetry">
                    <i class="ti ti-refresh me-1" aria-hidden="true"></i> Retry Loading
                </button>
            </div>

            <div id="pdfViewerContainer" class="pdf-viewer-container">
                <div id="pdfCanvasWrapper" class="pdf-canvas-wrapper">
                    <canvas id="pdfCanvas"></canvas>
                </div>
            </div>
        </section>

        <footer class="certificate-viewer-toolbar" aria-label="Certificate viewer toolbar">
            <div class="certificate-viewer-controls">
                <!-- Page Navigation -->
                <div class="pdf-control-group pdf-page-controls">
                    <button type="button" class="btn btn-icon btn-outline-secondary" id="pdfPrevPage" title="Previous Page" disabled>
                        <i class="ti ti-chevron-left" aria-hidden="true"></i>
                    </button>
                    <div class="pdf-page-display">
                        <input type="number" id="pdfPageInput" class="form-control form-control-sm pdf-page-input" value="1" min="1" max="1" aria-label="Page number">
                        <span class="pdf-page-slash">/</span>
                        <span id="pdfPageCount" class="pdf-page-total">1</span>
                    </div>
                    <button type="button" class="btn btn-icon btn-outline-secondary" id="pdfNextPage" title="Next Page" disabled>
                        <i class="ti ti-chevron-right" aria-hidden="true"></i>
                    </button>
                </div>

                <!-- Zoom Controls -->
                <div class="pdf-control-group pdf-zoom-controls">
                    <button type="button" class="btn btn-icon btn-outline-secondary" id="pdfZoomOut" title="Zoom Out (Ctrl + Scroll Down)">
                        <i class="ti ti-minus" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary pdf-zoom-reset-btn" id="pdfZoomFit" title="Fit to Width / Reset Zoom">
                        <span id="pdfZoomLevel">Fit</span>
                    </button>
                    <button type="button" class="btn btn-icon btn-outline-secondary" id="pdfZoomIn" title="Zoom In (Ctrl + Scroll Up)">
                        <i class="ti ti-plus" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div class="certificate-viewer-toolbar-primary">
                <a href="{{ route('certificate.download.pdf', $certificate->credential_id) }}"
                   class="btn btn-primary certificate-viewer-action"
                   title="Download PDF">
                    <i class="ti ti-download" aria-hidden="true"></i>
                    <span>Download PDF</span>
                </a>

                <button type="button" class="btn btn-outline-secondary certificate-viewer-action" id="certificateViewerPrint" title="Print">
                    <i class="ti ti-printer" aria-hidden="true"></i>
                    <span>Print</span>
                </button>

                <a href="{{ $documentUrl }}"
                   target="_blank"
                   rel="noopener"
                   class="btn btn-outline-secondary certificate-viewer-action"
                   title="Open in New Tab">
                    <i class="ti ti-external-link" aria-hidden="true"></i>
                    <span>Open in New Tab</span>
                </a>

                <button type="button" class="btn btn-outline-secondary certificate-viewer-action" id="certificateViewerFullscreen" title="Full Screen">
                    <i class="ti ti-maximize" aria-hidden="true"></i>
                    <span>Full Screen</span>
                </button>

                <div class="dropdown dropup certificate-viewer-more-actions">
                    <button type="button"
                            class="btn btn-outline-secondary dropdown-toggle certificate-viewer-action"
                            data-toggle="dropdown"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            title="More options">
                        <i class="ti ti-dots" aria-hidden="true"></i>
                        <span>More</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right dropdown-menu-end">
                        <a href="{{ route('certificate.download', $certificate->credential_id) }}" class="dropdown-item">
                            <i class="ti ti-file-type-html me-2" aria-hidden="true"></i>
                            Download HTML
                        </a>
                        <a href="{{ route('certificate.verify', $certificate->credential_id) }}"
                           target="_blank"
                           rel="noopener"
                           class="dropdown-item">
                            <i class="ti ti-shield-check me-2" aria-hidden="true"></i>
                            Verify Certificate
                        </a>
                        @if(!empty($certificate->linkedin_url))
                            <a href="{{ $certificate->linkedin_url }}"
                               target="_blank"
                               rel="noopener"
                               class="dropdown-item">
                                <i class="ti ti-brand-linkedin me-2" aria-hidden="true"></i>
                                Add to LinkedIn
                            </a>
                        @endif
                        @if($hasCustomTemplate)
                            <a href="{{ route('certificate.download.custom', $certificate->credential_id) }}" class="dropdown-item">
                                <i class="ti ti-certificate me-2" aria-hidden="true"></i>
                                Download Custom Certificate
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </footer>
    </main>
</div>
@endsection

@section('style')
<style>
    /* DESKTOP BASE STYLES */
    .certificate-viewer-page {
        min-height: calc(100vh - 56px);
        padding: 16px;
        background: #eef1f5;
        display: flex;
        flex-direction: column;
    }

    .certificate-viewer-shell {
        display: flex;
        flex-direction: column;
        width: min(100%, 1440px);
        height: calc(100vh - 88px);
        min-height: 540px;
        margin: 0 auto;
        overflow: hidden;
        border: 1px solid var(--tblr-border-color, #dce1e7);
        border-radius: 14px;
        background: var(--tblr-bg-surface, #fff);
        box-shadow: 0 18px 45px rgba(15, 23, 42, .12);
    }

    .certificate-viewer-header {
        position: relative;
        z-index: 3;
        display: grid;
        align-items: center;
        padding: 12px 16px;
        border-bottom: 1px solid var(--tblr-border-color, #dce1e7);
        background: var(--tblr-bg-surface, #fff);
        grid-template-columns: minmax(170px, 1fr) minmax(0, 2fr) minmax(170px, 1fr);
        gap: 16px;
        flex-shrink: 0;
    }

    .certificate-viewer-back {
        width: max-content;
        max-width: 100%;
    }

    .certificate-viewer-title-wrap {
        min-width: 0;
        text-align: center;
    }

    .certificate-viewer-status {
        margin-bottom: 3px;
    }

    .certificate-viewer-title {
        overflow: hidden;
        margin: 0;
        color: var(--tblr-body-color, #1f2937);
        font-size: 15px;
        font-weight: 700;
        line-height: 1.3;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .certificate-viewer-meta {
        margin-top: 2px;
        color: var(--tblr-secondary, #626976);
        font-size: 11px;
        line-height: 1.25;
    }

    .certificate-viewer-stage {
        position: relative;
        flex: 1 1 auto;
        min-height: 0;
        overflow: hidden;
        background: #474b4e;
    }

    .pdf-viewer-container {
        position: absolute;
        inset: 0;
        overflow: auto;
        width: 100%;
        height: 100%;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding: 20px;
        box-sizing: border-box;
        -webkit-overflow-scrolling: touch;
        touch-action: pan-x pan-y;
    }

    .pdf-canvas-wrapper {
        position: relative;
        display: flex;
        justify-content: center;
        align-items: center;
        margin: auto;
        background: #ffffff;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
        border-radius: 4px;
        transition: transform 0.15s ease-out;
    }

    #pdfCanvas {
        display: block;
        max-width: none;
        height: auto;
        border-radius: 4px;
    }

    .certificate-viewer-loading {
        position: absolute;
        inset: 0;
        z-index: 5;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        gap: 12px;
        background: #eef1f5;
        color: #667382;
        transition: opacity .18s ease, visibility .18s ease;
    }

    .certificate-viewer-loading.is-hidden {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }

    .certificate-viewer-error {
        position: absolute;
        inset: 0;
        z-index: 5;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 24px;
        background: var(--tblr-bg-surface, #fff);
        text-align: center;
    }

    .certificate-viewer-toolbar {
        position: relative;
        z-index: 4;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 16px;
        border-top: 1px solid var(--tblr-border-color, #dce1e7);
        background: var(--tblr-bg-surface, #fff);
        box-shadow: 0 -8px 22px rgba(15, 23, 42, .06);
        flex-shrink: 0;
        flex-wrap: wrap;
    }

    .certificate-viewer-controls {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .pdf-control-group {
        display: flex;
        align-items: center;
        gap: 4px;
        background: var(--tblr-bg-surface-secondary, #f8fafc);
        padding: 3px 6px;
        border-radius: 8px;
        border: 1px solid var(--tblr-border-color, #e2e8f0);
    }

    .pdf-page-display {
        display: flex;
        align-items: center;
        gap: 4px;
        padding: 0 4px;
        font-size: 13px;
        font-weight: 600;
        color: var(--tblr-body-color, #334155);
    }

    .pdf-page-input {
        width: 44px;
        height: 28px;
        padding: 0 4px;
        text-align: center;
        font-size: 13px;
        font-weight: 600;
        border-radius: 6px;
        -moz-appearance: textfield;
    }

    .pdf-page-input::-webkit-outer-spin-button,
    .pdf-page-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .pdf-page-slash {
        color: var(--tblr-secondary, #94a3b8);
    }

    .pdf-zoom-reset-btn {
        min-width: 60px;
        font-size: 12px;
        font-weight: 600;
        height: 28px;
        padding: 0 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .btn-icon {
        width: 28px;
        height: 28px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
    }

    .certificate-viewer-toolbar-primary {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: 8px;
    }

    .certificate-viewer-action {
        display: inline-flex;
        min-height: 38px;
        align-items: center;
        justify-content: center;
        gap: 7px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
    }

    .certificate-viewer-more-actions .dropdown-menu {
        min-width: 230px;
    }

    .certificate-viewer-more-actions .dropdown-item {
        display: flex;
        align-items: center;
        min-height: 38px;
    }

    .certificate-viewer-stage:fullscreen,
    .certificate-viewer-stage:-webkit-full-screen {
        width: 100vw;
        height: 100vh;
        background: #2a2d2f;
    }

    .certificate-viewer-stage:fullscreen .pdf-viewer-container,
    .certificate-viewer-stage:-webkit-full-screen .pdf-viewer-container {
        width: 100vw;
        height: 100vh;
    }

    /* MOBILE RESPONSIVE (< 768px) */
    @media (max-width: 767.98px) {
        body {
            overflow: hidden !important;
        }

        .page-wrapper {
            padding: 0 !important;
            margin: 0 !important;
        }

        .certificate-viewer-page {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1050;
            width: 100vw;
            height: 100vh;
            height: 100dvh;
            padding: 0;
            margin: 0;
            overflow: hidden;
            background: var(--tblr-bg-surface, #fff);
        }

        .certificate-viewer-shell {
            width: 100%;
            height: 100%;
            min-height: 0;
            margin: 0;
            border: 0;
            border-radius: 0;
            box-shadow: none;
            display: flex;
            flex-direction: column;
        }

        .certificate-viewer-header {
            display: flex;
            align-items: center;
            height: 52px;
            min-height: 52px;
            max-height: 56px;
            padding: 6px 12px;
            padding-top: max(6px, env(safe-area-inset-top));
            grid-template-columns: none;
            gap: 10px;
            flex-shrink: 0;
        }

        .certificate-viewer-back {
            width: 36px;
            height: 36px;
            min-height: 36px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border-radius: 8px;
        }

        .certificate-viewer-title-wrap {
            flex: 1;
            min-width: 0;
            text-align: left;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .certificate-viewer-header-spacer,
        .certificate-viewer-status {
            display: none !important;
        }

        .certificate-viewer-title {
            font-size: 14px;
            line-height: 1.2;
            margin: 0;
        }

        .certificate-viewer-meta {
            font-size: 11px;
            margin-top: 1px;
        }

        .certificate-viewer-stage {
            flex: 1 1 auto;
            min-height: 0;
            position: relative;
            overflow: hidden;
            background: #3e4245;
        }

        .pdf-viewer-container {
            padding: 8px;
        }

        .certificate-viewer-toolbar {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 8px 12px;
            padding-bottom: max(8px, env(safe-area-inset-bottom));
            flex-shrink: 0;
            background: var(--tblr-bg-surface, #fff);
            box-shadow: 0 -4px 16px rgba(0, 0, 0, .08);
        }

        .certificate-viewer-controls {
            width: 100%;
            justify-content: space-between;
        }

        .certificate-viewer-toolbar-primary {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
            flex-wrap: nowrap;
        }

        .certificate-viewer-action {
            flex: 1;
            min-width: 0;
            height: 38px;
            min-height: 38px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
        }

        .certificate-viewer-action i {
            font-size: 1.2rem;
            margin: 0 !important;
        }

        .certificate-viewer-action span {
            display: none !important;
        }

        .certificate-viewer-more-actions {
            flex: 1;
            min-width: 0;
            display: inline-flex;
        }

        .certificate-viewer-more-actions .certificate-viewer-action {
            width: 100%;
        }

        .certificate-viewer-more-actions .dropdown-toggle::after {
            display: none !important;
        }

        .certificate-viewer-more-actions .dropdown-menu {
            position: absolute;
            bottom: 100%;
            top: auto;
            right: 0;
            left: auto;
            margin-bottom: 8px;
            min-width: 220px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .15);
        }
    }

    /* DARK MODE STYLES */
    [data-bs-theme="dark"] .certificate-viewer-page,
    body.dark-mode .certificate-viewer-page {
        background: #0f1724;
    }

    [data-bs-theme="dark"] .certificate-viewer-shell,
    [data-bs-theme="dark"] .certificate-viewer-header,
    [data-bs-theme="dark"] .certificate-viewer-toolbar,
    body.dark-mode .certificate-viewer-shell,
    body.dark-mode .certificate-viewer-header,
    body.dark-mode .certificate-viewer-toolbar {
        border-color: rgba(255, 255, 255, .09);
        background: #152235;
    }

    [data-bs-theme="dark"] .certificate-viewer-title,
    body.dark-mode .certificate-viewer-title {
        color: #f1f5f9;
    }

    [data-bs-theme="dark"] .certificate-viewer-loading,
    [data-bs-theme="dark"] .certificate-viewer-error,
    body.dark-mode .certificate-viewer-loading,
    body.dark-mode .certificate-viewer-error {
        background: #192d45;
        color: #cbd5e1;
    }

    [data-bs-theme="dark"] .pdf-control-group,
    body.dark-mode .pdf-control-group {
        background: #1e2e45;
        border-color: rgba(255, 255, 255, 0.1);
    }

    [data-bs-theme="dark"] .pdf-page-display,
    [data-bs-theme="dark"] .pdf-page-input,
    body.dark-mode .pdf-page-display,
    body.dark-mode .pdf-page-input {
        color: #f1f5f9;
        background: #152235;
        border-color: rgba(255, 255, 255, 0.15);
    }
</style>
@endsection

@section('script')
<script src="{{ asset('vendor/pdfjs/pdf.min.js') }}"></script>
<script>
    if (typeof pdfjsLib === 'undefined') {
        document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"><\/script>');
    }
</script>
<script>
    class CertificatePdfViewer {
        constructor(config) {
            this.url = config.url;
            this.container = document.getElementById('pdfViewerContainer');
            this.wrapper = document.getElementById('pdfCanvasWrapper');
            this.canvas = document.getElementById('pdfCanvas');
            this.ctx = this.canvas ? this.canvas.getContext('2d') : null;
            this.loadingEl = document.getElementById('certificateViewerLoading');
            this.errorEl = document.getElementById('certificateViewerError');
            this.errorMsgEl = document.getElementById('certificateViewerErrorMessage');
            this.retryBtn = document.getElementById('certificateViewerRetry');

            // Toolbar elements
            this.prevBtn = document.getElementById('pdfPrevPage');
            this.nextBtn = document.getElementById('pdfNextPage');
            this.pageInput = document.getElementById('pdfPageInput');
            this.pageCountEl = document.getElementById('pdfPageCount');

            this.zoomOutBtn = document.getElementById('pdfZoomOut');
            this.zoomInBtn = document.getElementById('pdfZoomIn');
            this.zoomFitBtn = document.getElementById('pdfZoomFit');
            this.zoomLevelEl = document.getElementById('pdfZoomLevel');

            this.printBtn = document.getElementById('certificateViewerPrint');
            this.fullscreenBtn = document.getElementById('certificateViewerFullscreen');

            // State variables
            this.pdfDoc = null;
            this.pageNum = 1;
            this.pageRendering = false;
            this.pageNumPending = null;
            this.renderTask = null;
            this.scale = 1.0;
            this.fitScale = 1.0;
            this.isFitWidth = true;

            // Touch & Pinch state
            this.touchStartDist = 0;
            this.touchStartScale = 1.0;

            this.init();
        }

        init() {
            if (!this.canvas) return;

            if (typeof pdfjsLib === 'undefined') {
                this.showError('PDF.js library could not be loaded.');
                return;
            }

            // Configure Worker
            if (pdfjsLib.GlobalWorkerOptions) {
                pdfjsLib.GlobalWorkerOptions.workerSrc = "{{ asset('vendor/pdfjs/pdf.worker.min.js') }}";
            }

            this.bindEvents();
            this.loadDocument();
        }

        loadDocument() {
            this.showLoading();

            const loadingTask = pdfjsLib.getDocument({
                url: this.url,
                withCredentials: true
            });

            loadingTask.promise.then(pdfDoc => {
                this.pdfDoc = pdfDoc;
                if (this.pageCountEl) this.pageCountEl.textContent = pdfDoc.numPages;
                if (this.pageInput) {
                    this.pageInput.max = pdfDoc.numPages;
                    this.pageInput.value = 1;
                }

                this.hideLoading();
                this.calculateFitScale().then(() => {
                    this.renderPage(this.pageNum);
                });
            }).catch(err => {
                console.error('PDF loading error:', err);
                this.showError((err && err.message) ? err.message : 'Failed to load PDF certificate.');
            });
        }

        calculateFitScale() {
            if (!this.pdfDoc || !this.container) return Promise.resolve(1.0);

            return this.pdfDoc.getPage(this.pageNum).then(page => {
                const viewport = page.getViewport({ scale: 1.0 });
                const padding = window.innerWidth < 768 ? 16 : 40;
                const containerWidth = Math.max(this.container.clientWidth - padding, 240);
                this.fitScale = containerWidth / viewport.width;

                if (this.isFitWidth) {
                    this.scale = this.fitScale;
                }
                return this.scale;
            });
        }

        renderPage(num) {
            if (!this.pdfDoc) return;
            this.pageRendering = true;

            if (this.renderTask) {
                this.renderTask.cancel();
                this.renderTask = null;
            }

            this.pdfDoc.getPage(num).then(page => {
                const dpr = window.devicePixelRatio || 1;
                const viewport = page.getViewport({ scale: this.scale });

                this.canvas.width = Math.floor(viewport.width * dpr);
                this.canvas.height = Math.floor(viewport.height * dpr);

                this.canvas.style.width = Math.floor(viewport.width) + 'px';
                this.canvas.style.height = Math.floor(viewport.height) + 'px';

                const renderContext = {
                    canvasContext: this.ctx,
                    viewport: page.getViewport({ scale: this.scale * dpr })
                };

                const renderTask = page.render(renderContext);
                this.renderTask = renderTask;

                renderTask.promise.then(() => {
                    this.pageRendering = false;
                    this.renderTask = null;

                    if (this.pageNumPending !== null) {
                        const pending = this.pageNumPending;
                        this.pageNumPending = null;
                        this.renderPage(pending);
                    }
                }).catch(err => {
                    if (err && err.name === 'RenderingCancelledException') {
                        return;
                    }
                    console.error('Page render error:', err);
                    this.pageRendering = false;
                });
            }).catch(err => {
                console.error('Get page error:', err);
                this.pageRendering = false;
            });

            this.updateControls();
        }

        queueRenderPage(num) {
            if (this.pageRendering) {
                this.pageNumPending = num;
            } else {
                this.renderPage(num);
            }
        }

        updateControls() {
            if (this.pageInput) this.pageInput.value = this.pageNum;
            if (this.prevBtn) this.prevBtn.disabled = this.pageNum <= 1;
            if (this.nextBtn) this.nextBtn.disabled = !this.pdfDoc || this.pageNum >= this.pdfDoc.numPages;

            if (this.zoomLevelEl) {
                const percent = Math.round(this.scale * 100);
                this.zoomLevelEl.textContent = this.isFitWidth ? 'Fit' : `${percent}%`;
            }
        }

        goToPrevPage() {
            if (this.pageNum <= 1) return;
            this.pageNum--;
            this.queueRenderPage(this.pageNum);
        }

        goToNextPage() {
            if (!this.pdfDoc || this.pageNum >= this.pdfDoc.numPages) return;
            this.pageNum++;
            this.queueRenderPage(this.pageNum);
        }

        zoomIn() {
            this.isFitWidth = false;
            this.setScale(Math.min(this.scale * 1.2, 4.0));
        }

        zoomOut() {
            this.isFitWidth = false;
            this.setScale(Math.max(this.scale / 1.2, 0.25));
        }

        zoomFit() {
            this.isFitWidth = true;
            this.calculateFitScale().then(() => {
                this.renderPage(this.pageNum);
            });
        }

        setScale(newScale) {
            if (Math.abs(this.scale - newScale) < 0.005) return;
            this.scale = newScale;
            this.queueRenderPage(this.pageNum);
        }

        showLoading() {
            if (this.loadingEl) this.loadingEl.classList.remove('is-hidden');
            if (this.errorEl) this.errorEl.classList.add('d-none');
        }

        hideLoading() {
            if (this.loadingEl) this.loadingEl.classList.add('is-hidden');
        }

        showError(msg) {
            if (this.loadingEl) this.loadingEl.classList.add('is-hidden');
            if (this.errorEl) {
                if (this.errorMsgEl) this.errorMsgEl.textContent = msg;
                this.errorEl.classList.remove('d-none');
            }
        }

        bindEvents() {
            if (this.prevBtn) this.prevBtn.addEventListener('click', () => this.goToPrevPage());
            if (this.nextBtn) this.nextBtn.addEventListener('click', () => this.goToNextPage());

            if (this.pageInput) {
                this.pageInput.addEventListener('change', (e) => {
                    let val = parseInt(e.target.value, 10);
                    if (isNaN(val) || val < 1) val = 1;
                    if (this.pdfDoc && val > this.pdfDoc.numPages) val = this.pdfDoc.numPages;
                    this.pageNum = val;
                    this.queueRenderPage(this.pageNum);
                });
            }

            if (this.zoomOutBtn) this.zoomOutBtn.addEventListener('click', () => this.zoomOut());
            if (this.zoomInBtn) this.zoomInBtn.addEventListener('click', () => this.zoomIn());
            if (this.zoomFitBtn) this.zoomFitBtn.addEventListener('click', () => this.zoomFit());

            if (this.retryBtn) this.retryBtn.addEventListener('click', () => this.loadDocument());

            // Mouse Wheel Zoom
            if (this.container) {
                this.container.addEventListener('wheel', (e) => {
                    if (e.ctrlKey || e.metaKey) {
                        e.preventDefault();
                        if (e.deltaY < 0) {
                            this.zoomIn();
                        } else if (e.deltaY > 0) {
                            this.zoomOut();
                        }
                    }
                }, { passive: false });

                // Touch Pinch Zoom
                this.container.addEventListener('touchstart', (e) => {
                    if (e.touches.length === 2) {
                        this.touchStartDist = Math.hypot(
                            e.touches[0].pageX - e.touches[1].pageX,
                            e.touches[0].pageY - e.touches[1].pageY
                        );
                        this.touchStartScale = this.scale;
                    }
                }, { passive: true });

                this.container.addEventListener('touchmove', (e) => {
                    if (e.touches.length === 2 && this.touchStartDist > 0) {
                        const dist = Math.hypot(
                            e.touches[0].pageX - e.touches[1].pageX,
                            e.touches[0].pageY - e.touches[1].pageY
                        );
                        const factor = dist / this.touchStartDist;
                        const newScale = Math.min(Math.max(this.touchStartScale * factor, 0.3), 3.5);
                        this.isFitWidth = false;
                        this.setScale(newScale);
                    }
                }, { passive: true });

                this.container.addEventListener('touchend', (e) => {
                    if (e.touches.length < 2) {
                        this.touchStartDist = 0;
                    }
                }, { passive: true });
            }

            // Print button action
            if (this.printBtn) {
                this.printBtn.addEventListener('click', () => {
                    const printFrame = document.createElement('iframe');
                    printFrame.style.position = 'fixed';
                    printFrame.style.right = '0';
                    printFrame.style.bottom = '0';
                    printFrame.style.width = '0';
                    printFrame.style.height = '0';
                    printFrame.style.border = '0';
                    printFrame.src = this.url;
                    document.body.appendChild(printFrame);

                    printFrame.onload = () => {
                        setTimeout(() => {
                            try {
                                printFrame.contentWindow.focus();
                                printFrame.contentWindow.print();
                            } catch (err) {
                                window.open(this.url, '_blank', 'noopener');
                            } finally {
                                setTimeout(() => {
                                    if (document.body.contains(printFrame)) {
                                        document.body.removeChild(printFrame);
                                    }
                                }, 2000);
                            }
                        }, 300);
                    };
                });
            }

            // Window Resize & Orientation Change
            let resizeTimeout;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(() => {
                    if (this.pdfDoc) {
                        this.calculateFitScale().then(() => {
                            this.renderPage(this.pageNum);
                        });
                    }
                }, 180);
            });

            // Fullscreen Handler
            const stage = document.getElementById('certificateViewerStage');
            if (this.fullscreenBtn && stage) {
                this.fullscreenBtn.addEventListener('click', () => {
                    if (document.fullscreenElement || document.webkitFullscreenElement) {
                        if (document.exitFullscreen) {
                            document.exitFullscreen();
                        } else if (document.webkitExitFullscreen) {
                            document.webkitExitFullscreen();
                        }
                    } else {
                        const requestFullscreen = stage.requestFullscreen || stage.webkitRequestFullscreen;
                        if (requestFullscreen) requestFullscreen.call(stage);
                    }
                });

                const handleFullscreenChange = () => {
                    const isFullscreen = Boolean(document.fullscreenElement || document.webkitFullscreenElement);
                    const icon = this.fullscreenBtn.querySelector('i');
                    const label = this.fullscreenBtn.querySelector('span');
                    if (icon) {
                        icon.className = 'ti ' + (isFullscreen ? 'ti-minimize' : 'ti-maximize');
                    }
                    if (label) {
                        label.textContent = isFullscreen ? 'Exit Full Screen' : 'Full Screen';
                    }

                    setTimeout(() => {
                        if (this.pdfDoc) {
                            this.calculateFitScale().then(() => {
                                this.renderPage(this.pageNum);
                            });
                        }
                    }, 150);
                };

                document.addEventListener('fullscreenchange', handleFullscreenChange);
                document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        new CertificatePdfViewer({
            url: @json($documentUrl)
        });
    });
</script>
@endsection
