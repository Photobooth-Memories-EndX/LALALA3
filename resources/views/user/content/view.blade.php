@extends('layouts.sidebar-master')

@php
    $url_ytplayer_js = asset('Tabler/dist/libs/plyr/dist/plyr.min.js?1684106062');
    $url_ytplayer_css = asset('Tabler/dist/libs/plyr/dist/plyr.css?1684106062');

    $contentfile = json_decode($content->file);
    $getcontentfile = $contentfile[0] ?? '';
@endphp

@section('content')
<div class="page-body content-detail-page">
    <div class="container-xl">

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary rounded-pill px-3 content-back-btn">
                <i class="ti ti-arrow-left me-1"></i>
                Back
            </a>

            <button type="button"
                id="contentSidebarToggle"
                class="content-sidebar-toggle"
                aria-controls="contentLearningSidebar"
                aria-expanded="true">
                <i class="ti ti-layout-sidebar-right-collapse me-1" id="contentSidebarToggleIcon"></i>
                <span id="contentSidebarToggleLabel">Hide Sidebar</span>
            </button>
        </div>

        <div class="row g-4 content-learning-layout" id="contentLearningLayout">
            <div class="col-lg-8 col-xl-9 content-learning-main" id="contentLearningMain">
                @include('user.content.components.content-player')
                @include('user.content.components.content-detail-header', [
                    'discussionCount' => $discussionCount,
                    'bookmarkCount' => $bookmarkCount,
                    'isBookmarked' => $isBookmarked,
                ])
                @include('user.content.components.content-description')

                {{-- Comment modern --}}
                {{-- @include('user.content.components.content-comment') --}}

                <x-comment :content="$content" :comment="$comment" :discussion-count="$discussionCount" type="content" />
            </div>

            <div class="col-lg-4 col-xl-3 content-learning-sidebar" id="contentLearningSidebar">
                @include('user.content.components.content-learning-sidebar', [
                    'discussionCount' => $discussionCount,
                    'bookmarkCount' => $bookmarkCount,
                    'isBookmarked' => $isBookmarked,
                ])
            </div>
        </div>

    </div>
</div>

<script>
    (function () {
        var STORAGE_KEY = 'contentLearningSidebarCollapsed';

        var layout = document.getElementById('contentLearningLayout');
        var main = document.getElementById('contentLearningMain');
        var sidebar = document.getElementById('contentLearningSidebar');
        var toggleBtn = document.getElementById('contentSidebarToggle');
        var toggleIcon = document.getElementById('contentSidebarToggleIcon');
        var toggleLabel = document.getElementById('contentSidebarToggleLabel');

        if (!layout || !main || !sidebar || !toggleBtn) return;

        function applyState(collapsed) {
            layout.classList.toggle('sidebar-collapsed', collapsed);
            sidebar.classList.toggle('is-collapsed', collapsed);
            sidebar.classList.toggle('d-none', collapsed);

            main.classList.toggle('col-lg-8', !collapsed);
            main.classList.toggle('col-xl-9', !collapsed);
            main.classList.toggle('col-12', collapsed);

            toggleIcon.className = collapsed
                ? 'ti ti-layout-sidebar-right-expand me-1'
                : 'ti ti-layout-sidebar-right-collapse me-1';
            toggleLabel.textContent = collapsed ? 'Show Sidebar' : 'Hide Sidebar';
            toggleBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        }

        var collapsed = false;
        try {
            collapsed = localStorage.getItem(STORAGE_KEY) === '1';
        } catch (e) {}

        applyState(collapsed);

        toggleBtn.addEventListener('click', function () {
            var nowCollapsed = !layout.classList.contains('sidebar-collapsed');
            applyState(nowCollapsed);
            try {
                localStorage.setItem(STORAGE_KEY, nowCollapsed ? '1' : '0');
            } catch (e) {}
        });
    })();
</script>
@endsection

@section('style')
    @if (($content->type == 'Video') || ($content->type == 'YouTube'))
        <link rel="stylesheet" href="{{ $url_ytplayer_css }}">
    @endif

    @if ($content->type == 'Document' || $content->type == 'SOP/Regulation')
        <link rel="stylesheet" href="{{ asset('Tabler/dist/pdfjs-viewer.min.css') }}">
        <link rel="stylesheet" href="{{ asset('css/owl-pdf-reader.css') }}">
    @endif

    <style>
        .content-detail-page {
            background: linear-gradient(180deg, #f8fbff 0%, #f3f7fc 100%);
        }

        body[data-bs-theme="dark"] .content-detail-page,
        body.dark-mode .content-detail-page {
            background: linear-gradient(180deg, #0b1220 0%, #111827 100%);
        }

        .content-hero-card,
        .comment-shell,
        .modern-player-shell,
        .glass-surface {
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            background: rgba(255, 255, 255, 0.82);
            border: 1px solid rgba(148, 163, 184, 0.18);
        }

        .content-hero-actions {
            min-width: 2px;
        }

        .content-meta-chip,
        .content-stat-chip {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            border-radius: 999px;
            padding: .5rem .9rem;
            font-size: .875rem;
            font-weight: 600;
            color: #475569;
            background: #f8fafc;
        }

        .content-stat-chip {
            background: rgba(32, 107, 196, .08);
            color: #1d4ed8;
        }

        .content-action-btn,
        .comment-action-btn {
            min-height: 42px;
            transition: transform .18s ease, box-shadow .18s ease, background-color .18s ease, color .18s ease, border-color .18s ease;
        }

        .content-action-btn.btn-light:hover,
        .comment-action-btn.btn-light:hover {
            background: #eff6ff;
            color: #1d4ed8;
            border-color: #bfdbfe;
        }

        .comment-action-btn.btn-outline-secondary:hover {
            background: #206bc4;
            color: #fff;
            border-color: #206bc4;
        }

        .content-action-btn:hover,
        .comment-action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 .75rem 1.5rem rgba(15, 23, 42, .08);
        }

        .min-w-0 {
            min-width: 0;
        }

        .content-detail-title {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.25;
            color: #111827;
        }

        .content-detail-subtitle {
            font-size: 15px;
            line-height: 1.7;
        }

        .comment-panel .card,
        .content-sidebar-card,
        .modern-player-shell {
            border-radius: 1.5rem !important;
        }

        .comment-composer {
            max-width: 100%;
        }

        .comment-avatar {
            box-shadow: 0 .5rem 1rem rgba(15, 23, 42, .08);
        }

        .modern-comment-input {
            border-radius: 1.25rem;
            padding: 1rem 1.1rem;
            resize: none;
            min-height: 120px;
            border: 1px solid #dbe4f0;
            background: #fff;
            transition: border-color .18s ease, box-shadow .18s ease;
        }

        .modern-comment-input:focus {
            border-color: #206bc4;
            box-shadow: 0 0 0 .25rem rgba(32, 107, 196, .12);
        }

        .comment-thread-root {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(148, 163, 184, .16);
        }

        .comment-thread-reply {
            margin-top: .8rem;
            padding-left: 1.25rem;
            border-left: 2px solid rgba(32, 107, 196, .18);
        }

        .comment-card {
            background: rgba(248, 250, 252, .9);
            border: 1px solid rgba(148, 163, 184, .16);
            border-radius: 1.25rem;
            padding: 1rem 1.1rem;
            box-shadow: 0 .5rem 1rem rgba(15, 23, 42, .03);
        }

        .comment-card-reply {
            background: rgba(255, 255, 255, .88);
        }

        .comment-body {
            color: #334155;
            line-height: 1.7;
            word-break: break-word;
        }

        .reply-form-shell {
            border-left: 2px solid rgba(32, 107, 196, .2);
            padding-left: 1rem;
        }

        .comment-replies {
            margin-top: .9rem;
            padding-left: .35rem;
        }

        .comment-replies-nested {
            margin-top: .75rem;
        }

        .sidebar-stat-card {
            padding: 1rem;
            border-radius: 1rem;
            background: rgba(248, 250, 252, .95);
            border: 1px solid rgba(148, 163, 184, .14);
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .sidebar-stat-card:hover,
        .content-sidebar-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 1rem 2rem rgba(15, 23, 42, .08);
        }

        .sidebar-progress {
            height: .7rem;
            border-radius: 999px;
            background: #e2e8f0;
        }

        .tips-card {
            background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 100%);
        }

        /* ── Sidebar toggle button ─────────────────────────────────── */
        .content-sidebar-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            min-height: 42px;
            padding: .55rem 1.15rem;
            border: 1px solid #e9edf5;
            border-radius: 999px;
            background: rgba(255, 255, 255, .85);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: #475569;
            font-size: .875rem;
            font-weight: 600;
            line-height: 1;
            cursor: pointer;
            box-shadow: 0 .25rem .75rem rgba(15, 23, 42, .05);
            transition: transform .18s ease, box-shadow .18s ease, background-color .18s ease, color .18s ease, border-color .18s ease;
        }

        .content-sidebar-toggle i {
            font-size: 1.1rem;
            color: inherit;
            transition: color .18s ease;
        }

        .content-sidebar-toggle:hover {
            background: #eff6ff;
            border-color: #206bc4;
            color: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 .75rem 1.5rem rgba(15, 23, 42, .1);
        }

        .content-sidebar-toggle:focus,
        .content-sidebar-toggle:focus-visible {
            outline: none;
            box-shadow: 0 0 0 .25rem rgba(32, 107, 196, .18);
        }

        .content-sidebar-toggle:active {
            transform: translateY(0);
        }

        body[data-bs-theme="dark"] .content-sidebar-toggle,
        body.dark-mode .content-sidebar-toggle {
            background: rgba(17, 24, 39, .82);
            border-color: rgba(148, 163, 184, .18);
            color: #e5e7eb;
        }

        body[data-bs-theme="dark"] .content-sidebar-toggle:hover,
        body.dark-mode .content-sidebar-toggle:hover {
            background: rgba(32, 107, 196, .18);
            border-color: #206bc4;
            color: #93c5fd;
        }

        /* ── Collapsible sidebar layout ────────────────────────────── */
        .content-learning-main {
            min-width: 0;
        }

        .content-learning-main .player-frame,
        .content-learning-main video,
        .content-learning-main iframe {
            width: 100%;
        }

        /* ── AI Assistant card ─────────────────────────────────────── */
        .ai-assistant-card .ai-online-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #2fb344;
            animation: ai-online-pulse 1.6s ease infinite;
        }

        @keyframes ai-online-pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(47, 179, 68, .45); }
            50% { box-shadow: 0 0 0 4px rgba(47, 179, 68, 0); }
        }

        .ai-chat-messages {
            max-height: 240px;
            overflow-y: auto;
        }

        .ai-chat-bubble {
            max-width: 90%;
            padding: .65rem .9rem;
            border-radius: 1rem;
            font-size: .875rem;
            line-height: 1.55;
            word-break: break-word;
        }

        .ai-chat-bubble-assistant {
            align-self: flex-start;
            background: rgba(32, 107, 196, .08);
            color: #1e293b;
            border-bottom-left-radius: .25rem;
        }

        .ai-chat-bubble-user {
            align-self: flex-end;
            background: #206bc4;
            color: #fff;
            border-bottom-right-radius: .25rem;
        }

        .ai-suggestion-btn {
            font-size: .82rem;
            font-weight: 500;
            border-radius: .75rem;
            border-color: rgba(32, 107, 196, .25);
            color: #1d4ed8;
            background: rgba(32, 107, 196, .04);
            transition: background-color .18s ease, color .18s ease, border-color .18s ease;
        }

        .ai-suggestion-btn:hover {
            background: #206bc4;
            border-color: #206bc4;
            color: #fff;
        }

        .ai-chat-input {
            border-radius: .75rem;
        }

        body[data-bs-theme="dark"] .ai-chat-bubble-assistant,
        body.dark-mode .ai-chat-bubble-assistant {
            background: rgba(255, 255, 255, .08);
            color: #e5e7eb;
        }

        body[data-bs-theme="dark"] .ai-suggestion-btn,
        body.dark-mode .ai-suggestion-btn {
            background: rgba(255, 255, 255, .04);
            border-color: rgba(148, 163, 184, .25);
            color: #93c5fd;
        }

        body[data-bs-theme="dark"] .ai-suggestion-btn:hover,
        body.dark-mode .ai-suggestion-btn:hover {
            background: #206bc4;
            border-color: #206bc4;
            color: #fff;
        }

        .player-frame,
        .player-frame iframe,
        .player-frame video {
            border-radius: 1.25rem;
            overflow: hidden;
        }

        .player-frame {
            background: #0f172a;
        }

        .player-skeleton {
            position: absolute;
            inset: 0;
            z-index: 2;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: .75rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, rgba(15, 23, 42, .95), rgba(30, 41, 59, .9));
        }

        .player-skeleton-block {
            height: 1rem;
            border-radius: 999px;
            background: linear-gradient(90deg, rgba(255,255,255,.08) 25%, rgba(255,255,255,.18) 37%, rgba(255,255,255,.08) 63%);
            background-size: 400% 100%;
            animation: content-skeleton 1.4s ease infinite;
        }

        .player-skeleton-block-lg {
            width: 72%;
            height: 1.5rem;
        }

        .player-skeleton-block-sm {
            width: 46%;
        }

        .comment-empty-icon {
            font-size: 48px;
        }

        @keyframes content-skeleton {
            0% { background-position: 0 0; }
            100% { background-position: 400% 0; }
        }

        body[data-bs-theme="dark"] .content-hero-card,
        body[data-bs-theme="dark"] .comment-shell,
        body[data-bs-theme="dark"] .modern-player-shell,
        body[data-bs-theme="dark"] .glass-surface,
        body.dark-mode .content-hero-card,
        body.dark-mode .comment-shell,
        body.dark-mode .modern-player-shell,
        body.dark-mode .glass-surface {
            background: rgba(17, 24, 39, .82);
            border-color: rgba(148, 163, 184, .12);
        }

        body[data-bs-theme="dark"] .content-meta-chip,
        body[data-bs-theme="dark"] .content-stat-chip,
        body.dark-mode .content-meta-chip,
        body.dark-mode .content-stat-chip {
            background: rgba(255, 255, 255, .06);
            color: #e5e7eb;
        }

        body[data-bs-theme="dark"] .content-detail-title,
        body.dark-mode .content-detail-title,
        body[data-bs-theme="dark"] .content-detail-subtitle,
        body.dark-mode .content-detail-subtitle,
        body[data-bs-theme="dark"] .comment-body,
        body.dark-mode .comment-body {
            color: #e5e7eb;
        }

        body[data-bs-theme="dark"] .comment-card,
        body.dark-mode .comment-card,
        body[data-bs-theme="dark"] .sidebar-stat-card,
        body.dark-mode .sidebar-stat-card {
            background: rgba(15, 23, 42, .72);
            border-color: rgba(148, 163, 184, .12);
        }

        body[data-bs-theme="dark"] .modern-comment-input,
        body.dark-mode .modern-comment-input {
            background: rgba(15, 23, 42, .72);
            color: #f8fafc;
            border-color: rgba(148, 163, 184, .18);
        }

        @media (max-width: 768px) {
            .content-detail-title {
                font-size: 1.45rem;
            }

            .content-hero-actions {
                min-width: 100%;
            }

            .comment-thread-root {
                padding: .9rem .75rem;
            }

            .comment-card {
                padding: .95rem .9rem;
            }

            .comment-thread-reply {
                padding-left: .9rem;
            }

            .comment-empty-icon {
                font-size: 42px;
            }
        }
    </style>
@endsection

@section('script')
    <script src="{{ asset('js/learning-progress.js') }}"></script>

    @if ($content->type == 'Document' || $content->type == 'SOP/Regulation')
        <script src="{{ asset('js/pdf.min.js') }}"></script>
        <script src="{{ asset('js/pdfjs-viewer.min.js') }}"></script>
        <script src="{{ asset('js/owl-pdf-reader.js') }}"></script>

        <script>
            function hideContentPlayerSkeleton() {
                $('.player-skeleton').addClass('d-none');
            }

            let fileName = "{{ $getcontentfile }}";

            let fileNameFixed = fileName
                .replaceAll(' ', '%20')
                .replaceAll('&amp;', '&');

            let fileLocation =
                "{{ asset('storage/files/content/' . $content->id . '/' . ':fileContent') }}";

            let pdfFile = fileLocation.replace(':fileContent', fileNameFixed);

            var pdfjsLib = window["pdfjs-dist/build/pdf"];

            pdfjsLib.GlobalWorkerOptions.workerSrc =
                "{{ asset('js/pdf.worker.min.js') }}";

            const progressTracker = new OwlLearningProgress({
                url: "{{ route('content.user.progress.update') }}",
                csrf: "{{ csrf_token() }}",
                contentId: {{ (int) $content->id }},
                type: 'document',
                onUpdate: window.owlUpdateLearningUi
            });

            var contentPdfReader = new OwlPdfReader({
                shellId: 'pdfviewer-shell',
                viewerSelector: '.content-pdfjs-viewer'
            });

            var pdfViewer = window.pdfViewer = new PDFjsViewer(
                $(".content-pdfjs-viewer"),
                contentPdfReader.getViewerOptions({
                    onDocumentReady: function () {
                        var totalPages = pdfViewer.getPageCount();

                        // Resume: prefer the exact saved page, fall back to the
                        // legacy progress-percentage estimate.
                        var lastPage = {{ (int) ($content_user->last_page ?? 0) }};

                        if (!lastPage) {
                            var progression = {{ (int) $content_user->progress }};
                            lastPage = parseInt((progression * totalPages) / 100);
                        }

                        if (lastPage > 1 && lastPage < totalPages) {
                            pdfViewer.scrollToPage(lastPage);
                        }

                        progressTracker.send({ total_pages: totalPages }, true);

                        hideContentPlayerSkeleton();
                    },

                    onActivePageChanged: function (page, pageno) {
                        progressTracker.sendDebounced({
                            last_page: pageno,
                            total_pages: this.getPageCount()
                        });
                    }
                }));

            contentPdfReader.attach(pdfViewer, pdfFile);

            window.pdfRotateLeft = function () {
                return contentPdfReader.rotate(-90);
            };
            window.pdfRotateRight = function () {
                return contentPdfReader.rotate(90);
            };
            window.pdfToggleReadingMode = function () {
                return contentPdfReader.toggleReadingMode();
            };
            window.pdfDoPrint = function () {
                return contentPdfReader.print();
            };
            window.pdfToggleFullscreen = function () {
                return contentPdfReader.toggleFullscreen();
            };

            if (fileName) {
                pdfViewer.loadDocument(pdfFile).then(function () {
                    pdfViewer.setZoom("width");
                    hideContentPlayerSkeleton();
                });
            }

        </script>
    @endif

    @if (($content->type == 'Video') || ($content->type == 'YouTube'))
        <script src="{{ $url_ytplayer_js }}"></script>

        <script>
            $(document).ready(function() {
                function hideContentPlayerSkeleton() {
                    $('.player-skeleton').addClass('d-none');
                }

                @if ($content_user->progress >= 100)
                    const player = new Plyr('#player');
                @else
                    const player = new Plyr('#player', {
                        listeners: {
                            seek(e) {
                                e.preventDefault();
                                return false;
                            }
                        }
                    });
                @endif

                const progressTracker = new OwlLearningProgress({
                    url: "{{ route('content.user.progress.update') }}",
                    csrf: "{{ csrf_token() }}",
                    contentId: {{ (int) $content->id }},
                    type: 'video',
                    onUpdate: window.owlUpdateLearningUi
                });

                player.on('loadeddata', onDataLoaded);
                player.on('ready', function() {
                    hideContentPlayerSkeleton();
                });

                function onDataLoaded() {
                    // Resume: prefer the exact saved position, fall back to the
                    // legacy progress-percentage estimate.
                    var resumeSeconds = {{ (int) ($content_user->current_time ?? 0) }};

                    if (!resumeSeconds) {
                        var progression = $('#player_progress').val();
                        var durasi = $('#player_duration').val();
                        resumeSeconds = (progression * durasi) / 100;
                    }

                    if (resumeSeconds > 0 && resumeSeconds < player.duration) {
                        player.currentTime = resumeSeconds;
                    }

                    player.off('loadeddata', onDataLoaded);
                    hideContentPlayerSkeleton();
                }

                player.on('loadedmetadata', function() {
                    if (player.duration) {
                        progressTracker.send({ duration_seconds: player.duration }, true);
                    }
                });

                player.on('play', function() {
                    progressTracker.send({
                        status: 'in_progress',
                        current_time: player.currentTime,
                        duration_seconds: player.duration
                    }, true);
                });

                player.on('timeupdate', function() {
                    if (!player.duration) return;

                    progressTracker.send({
                        current_time: player.currentTime,
                        duration_seconds: player.duration
                    });
                });

                player.on('pause', function() {
                    if (player.ended) return;

                    progressTracker.send({
                        status: 'paused',
                        current_time: player.currentTime,
                        duration_seconds: player.duration
                    }, true);
                });

                player.on('ended', function() {
                    progressTracker.send({
                        status: 'completed',
                        current_time: player.duration,
                        duration_seconds: player.duration
                    }, true);
                });
            });
        </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function showContentToast(message) {
                let container = document.querySelector('.content-toast-container');

                if (!container) {
                    container = document.createElement('div');
                    container.className = 'content-toast-container position-fixed top-0 end-0 p-3';
                    container.style.zIndex = '1080';
                    document.body.appendChild(container);
                }

                const toast = document.createElement('div');
                toast.className = 'alert alert-success shadow-lg mb-2 content-toast-item';
                toast.style.transition = 'opacity .2s ease';
                toast.innerHTML = '<div class="d-flex align-items-center gap-2"><i class="ti ti-check"></i><span>' + message + '</span></div>';

                container.appendChild(toast);

                setTimeout(function () {
                    toast.style.opacity = '0';
                    setTimeout(function () {
                        toast.remove();
                    }, 220);
                }, 1600);
            }

            document.querySelectorAll('[data-copy-content-url]').forEach(function (button) {
                button.addEventListener('click', async function () {
                    try {
                        await navigator.clipboard.writeText(window.location.href);
                        showContentToast('Link copied!');
                    } catch (error) {
                        showContentToast('Copy failed');
                    }
                });
            });
        });
    </script>
@endsection
