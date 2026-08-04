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
               class="btn btn-outline-secondary certificate-viewer-back">
                <i class="ti ti-arrow-left" aria-hidden="true"></i>
                <span>Back to Certificates</span>
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
            <iframe
                id="certificateViewerFrame"
                class="certificate-viewer-frame"
                src="{{ $documentUrl }}#toolbar=1&navpanes=0&view=FitH"
                title="{{ $certificate->title }} PDF certificate"
                allow="fullscreen"
            ></iframe>
        </section>

        <footer class="certificate-viewer-toolbar" aria-label="Certificate viewer toolbar">
            <div class="certificate-viewer-toolbar-primary">
                <a href="{{ route('certificate.download.pdf', $certificate->credential_id) }}"
                   class="btn btn-primary certificate-viewer-action">
                    <i class="ti ti-download" aria-hidden="true"></i>
                    <span>Download PDF</span>
                </a>

                <button type="button" class="btn btn-outline-secondary certificate-viewer-action" id="certificateViewerPrint">
                    <i class="ti ti-printer" aria-hidden="true"></i>
                    <span>Print</span>
                </button>

                <a href="{{ $documentUrl }}#toolbar=1&navpanes=0&view=FitH"
                   target="_blank"
                   rel="noopener"
                   class="btn btn-outline-secondary certificate-viewer-action">
                    <i class="ti ti-external-link" aria-hidden="true"></i>
                    <span>Open in New Tab</span>
                </a>

                <button type="button" class="btn btn-outline-secondary certificate-viewer-action" id="certificateViewerFullscreen">
                    <i class="ti ti-maximize" aria-hidden="true"></i>
                    <span>Full Screen</span>
                </button>
            </div>

            <div class="dropdown certificate-viewer-more-actions">
                <button type="button"
                        class="btn btn-outline-secondary dropdown-toggle certificate-viewer-action"
                        data-toggle="dropdown"
                        aria-expanded="false">
                    <i class="ti ti-dots" aria-hidden="true"></i>
                    <span>More</span>
                </button>
                <div class="dropdown-menu dropdown-menu-right">
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
        </footer>
    </main>
</div>
@endsection

@section('style')
<style>
    .certificate-viewer-page {
        min-height: calc(100vh - 56px);
        padding: 16px;
        background: #eef1f5;
    }

    .certificate-viewer-shell {
        display: grid;
        width: min(100%, 1440px);
        min-height: calc(100vh - 88px);
        margin: 0 auto;
        grid-template-rows: auto minmax(420px, 1fr) auto;
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
        min-height: 0;
        overflow: hidden;
        background: #dfe3e8;
    }

    .certificate-viewer-frame {
        position: absolute;
        inset: 0;
        z-index: 1;
        display: block;
        width: 100%;
        height: 100%;
        border: 0;
        background: #fff;
    }

    .certificate-viewer-loading {
        position: absolute;
        inset: 0;
        z-index: 2;
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

    .certificate-viewer-toolbar {
        position: sticky;
        bottom: 0;
        z-index: 4;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 12px 16px;
        border-top: 1px solid var(--tblr-border-color, #dce1e7);
        background: var(--tblr-bg-surface, #fff);
        box-shadow: 0 -8px 22px rgba(15, 23, 42, .06);
    }

    .certificate-viewer-toolbar-primary {
        display: flex;
        align-items: center;
        justify-content: center;
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
        background: #1f2937;
    }

    @media (max-width: 767.98px) {
        .certificate-viewer-page {
            padding: 0;
        }

        .certificate-viewer-shell {
            min-height: calc(100vh - 56px);
            border-right: 0;
            border-left: 0;
            border-radius: 0;
            grid-template-rows: auto minmax(360px, 1fr) auto;
        }

        .certificate-viewer-header {
            grid-template-columns: auto minmax(0, 1fr);
            gap: 10px;
        }

        .certificate-viewer-header-spacer,
        .certificate-viewer-back span,
        .certificate-viewer-status {
            display: none;
        }

        .certificate-viewer-title-wrap {
            text-align: left;
        }

        .certificate-viewer-toolbar {
            align-items: stretch;
            justify-content: flex-start;
            overflow-x: auto;
            padding: 10px;
        }

        .certificate-viewer-toolbar-primary {
            flex-wrap: nowrap;
        }

        .certificate-viewer-action {
            min-width: 42px;
        }

        .certificate-viewer-action span {
            display: none;
        }
    }

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
    body.dark-mode .certificate-viewer-loading {
        background: #192d45;
        color: #cbd5e1;
    }
</style>
@endsection

@section('script')
<script>
    $(function () {
        var frame = document.getElementById('certificateViewerFrame');
        var stage = document.getElementById('certificateViewerStage');
        var loading = document.getElementById('certificateViewerLoading');
        var printButton = document.getElementById('certificateViewerPrint');
        var fullscreenButton = document.getElementById('certificateViewerFullscreen');

        frame.addEventListener('load', function () {
            loading.classList.add('is-hidden');
        });

        printButton.addEventListener('click', function () {
            try {
                frame.contentWindow.focus();
                frame.contentWindow.print();
            } catch (error) {
                window.open(@json($documentUrl), '_blank', 'noopener');
            }
        });

        fullscreenButton.addEventListener('click', function () {
            if (document.fullscreenElement) {
                document.exitFullscreen();
                return;
            }

            var requestFullscreen = stage.requestFullscreen || stage.webkitRequestFullscreen;
            if (requestFullscreen) requestFullscreen.call(stage);
        });

        document.addEventListener('fullscreenchange', function () {
            var isFullscreen = Boolean(document.fullscreenElement);
            var icon = fullscreenButton.querySelector('i');
            var label = fullscreenButton.querySelector('span');
            icon.className = 'ti ' + (isFullscreen ? 'ti-minimize' : 'ti-maximize');
            label.textContent = isFullscreen ? 'Exit Full Screen' : 'Full Screen';
        });
    });
</script>
@endsection
