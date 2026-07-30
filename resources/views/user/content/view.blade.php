@extends('layouts.sidebar-master')

@php
    $url_ytplayer_js = asset('Tabler/dist/libs/plyr/dist/plyr.min.js?1684106062');
    $url_ytplayer_css = asset('Tabler/dist/libs/plyr/dist/plyr.css?1684106062');

    $contentfile = is_string($content->file) ? json_decode($content->file, true) : [];
    $getcontentfile = is_array($contentfile) ? ($contentfile[0] ?? '') : ($content->file ?? '');
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
                @include('user.content.components.content-description')
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

@include('user.content.components.content-comment', [
    'content' => $content,
    'comment' => $comment,
])

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
            color: #05507D;
        }

        .content-action-btn,
        .comment-action-btn {
            min-height: 42px;
            transition: transform .18s ease, box-shadow .18s ease, background-color .18s ease, color .18s ease, border-color .18s ease;
        }

        .content-action-btn.btn-light:hover,
        .comment-action-btn.btn-light:hover {
            background: #eff6ff;
            color: #05507D;
            border-color: #bfdbfe;
        }

        .comment-action-btn.btn-outline-secondary:hover {
            background: #05507D;
            color: #fff;
            border-color: #05507D;
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

        /* Cleaning Modal Duplicate Definitions */
        .content-comment-modal .modal-dialog {
            display: flex;
            width: min(70vw, 920px);
            max-width: min(70vw, 920px);
            height: 88vh;
            margin-left: auto;
            margin-right: auto;
        }

        .content-comment-modal .modal-content {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            min-height: 0;
            max-height: 100%;
            overflow: hidden;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .24);
        }

        .content-comment-modal .modal-body {
            display: flex;
            flex: 1 1 auto;
            min-height: 0;
            padding: 0;
            background: #f8fafc;
            overflow-y: auto;
        }

        .content-comment-modal .view-comment-wrapper {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            min-height: 0;
            padding: 12px 14px 14px;
            overflow: hidden;
        }

        /* PDF-only controls: keep the toolbar centered and the next action
           anchored to the lower-right edge of the document stage. */
        .content-detail-page .owl-pdf-toolbar {
            display: flex;
            justify-content: center;
        }

        .content-detail-page .owl-pdf-toolbar .pdf-tb-inner {
            width: 100%;
            justify-content: center;
        }

        .content-detail-page .owl-pdf-reader-stage {
            position: relative;
        }

        .content-detail-page .pdf-page-nav {
            position: absolute;
            inset: 0;
            z-index: 4;
            pointer-events: none;
        }

        .content-detail-page .pdf-page-nav button {
            position: absolute;
            bottom: 32px;
            pointer-events: auto;
            border-radius: 999px;
            box-shadow: 0 .75rem 1.5rem rgba(15, 23, 42, .2);
            transition: transform .18s ease, box-shadow .18s ease,
                background-color .18s ease, border-color .18s ease;
        }

        .content-detail-page .pdf-page-nav .pdf-previous-page {
            left: 20px;
        }

        .content-detail-page .pdf-page-nav .pdf-next-page {
            right: 20px;
        }

        .content-detail-page .pdf-page-nav button:hover:not(:disabled) {
            background: #043f63;
            border-color: #043f63;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 1rem 1.75rem rgba(15, 23, 42, .28);
        }

        .content-detail-page .badge-completed,
        .content-detail-page .badge-completed i,
        .content-detail-page .badge-completed svg {
            color: #0f5132 !important;
            fill: #0f5132 !important;
        }

        .content-detail-page .pdf-page-nav button:disabled {
            opacity: .45;
            pointer-events: none;
        }

        @media (max-width: 575.98px) {
            .content-detail-page .pdf-page-nav button {
                bottom: 24px;
                padding: .55rem .7rem;
            }

            .content-detail-page .pdf-page-nav .pdf-previous-page {
                left: 15px;
            }

            .content-detail-page .pdf-page-nav .pdf-next-page {
                right: 15px;
            }

            .content-detail-page .pdf-page-nav-label {
                display: none;
            }
        }

        .content-comment-modal .view-comment-wrapper .comment-ajax-root {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
        }

        .content-comment-modal .view-comment-wrapper .comment-ajax-root > #comment {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            min-height: 0;
            margin: 0 !important;
            overflow: hidden;
        }

        .content-comment-modal .view-comment-wrapper .comment-ajax-root > #comment > .card-body:last-child {
            flex: 1 1 auto;
            overflow-y: auto;
        }

        .overview-text.is-collapsed {
            display: -webkit-box;
            overflow: hidden;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 3;
        }

        .overview-meta-button {
            padding: 0;
            border: 0;
            background: transparent;
            color: inherit;
            cursor: pointer;
        }

        .overview-meta-button:hover {
            color: #05507D;
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
            border-color: #05507D;
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
            background: linear-gradient(135deg, #05507D 0%, #6AAED6 100%);
        }

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
            border-color: #05507D;
            color: #05507D;
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
            border-color: #05507D;
            color: #93c5fd;
        }

        .content-learning-main {
            min-width: 0;
        }

        .content-learning-main .player-frame,
        .content-learning-main video,
        .content-learning-main iframe {
            width: 100%;
        }

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
            background: #05507D;
            color: #fff;
            border-bottom-right-radius: .25rem;
        }

        .ai-suggestion-btn {
            font-size: .82rem;
            font-weight: 500;
            border-radius: .75rem;
            border-color: rgba(32, 107, 196, .25);
            color: #05507D;
            background: rgba(32, 107, 196, .04);
            transition: background-color .18s ease, color .18s ease, border-color .18s ease;
        }

        .ai-suggestion-btn:hover {
            background: #05507D;
            border-color: #05507D;
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
            background: #05507D;
            border-color: #05507D;
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

            .content-comment-modal .modal-dialog {
                width: calc(100% - 1rem);
                max-width: none;
                height: calc(100% - 1rem);
                margin: .5rem;
            }

            .content-comment-modal .view-comment-wrapper {
                padding: 8px;
            }
        }
    </style>
@endsection

@section('script')
    <script src="{{ asset('js/learning-progress.js') }}"></script>

    <script>
        document.addEventListener('click', function (event) {
            var button = event.target.closest('.overview-toggle');
            if (!button) return;

            var overview = document.querySelector(button.dataset.overviewTarget || '#contentOverviewText');
            if (!overview) return;

            overview.classList.toggle('is-collapsed');
            var expanded = !overview.classList.contains('is-collapsed');
            button.textContent = expanded
                ? button.dataset.readLess
                : button.dataset.readMore;
        });
    </script>

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

            let maxPagesRead = Math.max(1, {{ (int) ($content_user->last_page ?? 0) }});
            var pdfPreviousPageButton = document.getElementById('pdf-previous-page');
            var pdfNextPageButton = document.getElementById('pdf-next-page');

            function updatePdfPageButtons(activePage, totalPages) {
                if (!pdfPreviousPageButton && !pdfNextPageButton) return;

                var currentPage = Math.max(1, parseInt(activePage, 10) || 1);
                var pageCount = Math.max(0, parseInt(totalPages, 10) || 0);
                var isFirstPage = currentPage <= 1;
                var isLastPage = pageCount > 0 && currentPage >= pageCount;

                if (pdfPreviousPageButton) {
                    $('#pdf-previous-page').prop('disabled', isFirstPage);
                    pdfPreviousPageButton.setAttribute('aria-hidden', isFirstPage ? 'true' : 'false');
                }

                if (pdfNextPageButton) {
                    $('#pdf-next-page').prop('disabled', !pageCount || isLastPage);
                    pdfNextPageButton.setAttribute('aria-hidden', isLastPage ? 'true' : 'false');
                }
            }

            function updateDocumentProgressUi(readPage, totalPages) {
                var progress = totalPages > 0
                    ? Math.min(100, Math.round((readPage / totalPages) * 100))
                    : 0;

                $('.pages-read-text').text(readPage + '/' + totalPages + ' pages read');
                $('#contentProgressValue').text(progress + '%');
                $('#contentProgressBar').css('width', progress + '%');
            }

            var pdfViewer = window.pdfViewer = new PDFjsViewer(
                $(".content-pdfjs-viewer"),
                contentPdfReader.getViewerOptions({
                    onDocumentReady: function () {
                        var totalPages = pdfViewer.getPageCount();

                        var lastPage = {{ (int) ($content_user->last_page ?? 0) }};

                        if (!lastPage) {
                            var progression = {{ (int) $content_user->progress }};
                            lastPage = parseInt((progression * totalPages) / 100);
                        }

                        maxPagesRead = Math.max(maxPagesRead, lastPage || 1);

                        var initialActivePage = 1;

                        if (lastPage > 1 && lastPage < totalPages) {
                            initialActivePage = lastPage;
                            pdfViewer.scrollToPage(lastPage);
                        }

                        progressTracker.send({ total_pages: totalPages }, true);

                        updatePdfPageButtons(initialActivePage, totalPages);

                        hideContentPlayerSkeleton();
                    },

                    onActivePageChanged: function (page, pageno) {
                        var totalPages = this.getPageCount();
                        var activePage = parseInt(pageno, 10) || 1;

                        // The top label represents the page currently visible.
                        contentPdfReader.updatePageLabel(activePage, totalPages);
                        updatePdfPageButtons(activePage, totalPages);

                        // Sidebar progress is monotonic: going backward must
                        // never reduce the highest page already reached.
                        if (activePage <= maxPagesRead) return;

                        maxPagesRead = activePage;
                        updateDocumentProgressUi(maxPagesRead, totalPages);

                        progressTracker.sendDebounced({
                            last_page: maxPagesRead,
                            total_pages: totalPages
                        });
                    }
                }));

            contentPdfReader.attach(pdfViewer, pdfFile);

            if (pdfPreviousPageButton) {
                pdfPreviousPageButton.addEventListener('click', function () {
                    if (this.disabled || !pdfViewer || typeof pdfViewer.prev !== 'function') return;
                    pdfViewer.prev();
                });
            }

            if (pdfNextPageButton) {
                pdfNextPageButton.addEventListener('click', function () {
                    if (this.disabled || !pdfViewer || typeof pdfViewer.next !== 'function') return;
                    pdfViewer.next();
                });
            }

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

                // Throttled timeupdate untuk mencegah spam ke server
                let lastProgressSync = 0;
                player.on('timeupdate', function() {
                    if (!player.duration) return;

                    // Mengirim update setiap 5 detik sekali saja saat diputar
                    if (Math.abs(player.currentTime - lastProgressSync) >= 5) {
                        lastProgressSync = player.currentTime;
                        progressTracker.sendDebounced({
                            current_time: player.currentTime,
                            duration_seconds: player.duration
                        });
                    }
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
                        if (navigator.clipboard && window.isSecureContext) {
                            await navigator.clipboard.writeText(window.location.href);
                        } else {
                            // Fallback jika dikunjungi lewat non-HTTPS / HTTP IP lokal
                            const textArea = document.createElement('textarea');
                            textArea.value = window.location.href;
                            document.body.appendChild(textArea);
                            textArea.select();
                            document.execCommand('copy');
                            document.body.removeChild(textArea);
                        }
                        showContentToast('Link copied!');
                    } catch (error) {
                        showContentToast('Copy failed');
                    }
                });
            });
        });
    </script>
@endsection
