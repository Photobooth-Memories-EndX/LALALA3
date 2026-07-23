@php
    $url_ytplayer_js = asset('Tabler/dist/libs/plyr/dist/plyr.min.js?1684106062');
    $url_ytplayer_css = asset('Tabler/dist/libs/plyr/dist/plyr.css?1684106062');
@endphp

@extends('layouts.sidebar-master')

@section('content')
    <div class="page-body learning-view-page">
        <div class="container-xl">

            <a href="{{ url()->previous() }}" class="btn btn-link px-0 mb-3">
                <i class="fas fa-arrow-left me-2"></i> Back
            </a>

            @include('user.playlist.components.view-hero')

            <div class="playlist-learning-shell">
                <button type="button"
                        class="restore-toggle-btn restore-left"
                        onclick="toggleLearningPanel('left', this)"
                        aria-controls="content-list"
                        aria-expanded="false"
                        title="Buka Playlist">
                    <i class="fas fa-bars"></i>
                    <span>Content List</span>
                </button>

                <button type="button"
                        class="restore-toggle-btn restore-right"
                        onclick="toggleLearningPanel('right', this)"
                        aria-controls="ai-assistant"
                        aria-expanded="false"
                        title="Buka AI Assistant">
                    <i class="fas fa-robot"></i>
                    <span>AI Assistant</span>
                </button>

                <div id="playlistLearningLayout" class="playlist-learning-layout">

                    <div id="content-list" class="learning-left-panel">
                        @include('user.playlist.components.view-playlist-sidebar')
                    </div>

                    <div class="learning-main-panel">
                        @include('user.playlist.components.view-player')
                        @include('user.playlist.components.view-navigation', [
                            'content' => $content,
                            'content_all' => $content_all,
                            'playlist' => $playlist,
                        ])
                        @include('user.playlist.components.view-content-info')
                    </div>

                    <div id="ai-assistant" class="learning-right-panel">
                        @include('user.playlist.components.view-ai-assistant')
                    </div>

                </div>
            </div>

        </div>
    </div>

    @php
        $commentTotal = method_exists($comment, 'total') ? $comment->total() : $comment->count();
    @endphp

    <div class="modal fade playlist-comment-modal" id="playlistCommentModal" tabindex="-1" aria-labelledby="playlistCommentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title" id="playlistCommentModalLabel">
                        <i class="far fa-comment-dots me-2"></i>
                        {{ $commentTotal }} Comments
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="view-comment-wrapper">
                        <x-comment
                            :content="$content"
                            :comment="$comment"
                            playlistslug="{{ $playlist->slug }}"
                            type="contentplaylist"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('style')
    <link rel="stylesheet" href="{{ $url_ytplayer_css }}">

    @if (in_array($content->type, ['Document', 'SOP/Regulation']))
        <link rel="stylesheet" href="{{ asset('Tabler/dist/pdfjs-viewer.min.css') }}">
    @endif

    @include('user.playlist.components.view-style')

    <style>
        .playlist-comment-modal .modal-dialog {
            width: min(70vw, 920px);
            max-width: min(70vw, 920px);
            height: 88vh;
            margin-left: auto;
            margin-right: auto;
        }

        .playlist-comment-modal .modal-content {
            height: 88vh;
            max-height: 88vh;
            overflow: hidden;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .24);
        }

        .playlist-comment-modal .modal-header {
            flex: 0 0 auto;
            padding: 12px 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        .playlist-comment-modal .modal-title {
            display: inline-flex;
            align-items: center;
            color: #0f172a;
            font-size: 16px;
            font-weight: 800;
        }

        .playlist-comment-modal .modal-body {
            min-height: 0;
            padding: 0;
            background: #f8fafc;
            overflow: hidden;
        }

        .view-comment-wrapper {
            height: 100%;
            min-height: 0;
            padding: 12px 14px 14px;
            background: #f8fafc;
        }

        .view-comment-wrapper .comment-ajax-root {
            height: 100%;
            min-height: 0;
            margin: 0 !important;
        }

        .view-comment-wrapper.is-loading-comments,
        .view-comment-wrapper .comment-ajax-root.is-loading-comments {
            opacity: .65;
            pointer-events: none;
            transition: opacity .18s ease;
        }

        .view-comment-wrapper #comment {
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 0;
            padding: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
            overflow: hidden !important;
        }

        .view-comment-wrapper #comment .card-header {
            flex: 0 0 auto;
            padding: 0 0 10px !important;
            background: transparent !important;
        }

        .view-comment-wrapper #comment .card-body {
            flex: 1 1 auto;
            min-height: 0;
            padding: 0 !important;
            background: transparent !important;
            overflow-y: auto;
            overscroll-behavior: contain;
            scrollbar-width: thin;
        }

        .view-comment-wrapper .comment-count {
            display: none !important;
        }

        .view-comment-wrapper .comment-form > .d-flex {
            gap: 8px !important;
        }

        .view-comment-wrapper .comment-form .avatar,
        .view-comment-wrapper .comment-item-root > .flex-shrink-0 .avatar {
            width: 38px !important;
            height: 38px !important;
            font-size: 13px !important;
        }

        .view-comment-wrapper .comment-item-reply > .flex-shrink-0 .avatar {
            width: 30px !important;
            height: 30px !important;
            font-size: 11px !important;
        }

        .view-comment-wrapper .comment-thread-root {
            padding: 12px 14px !important;
        }

        .view-comment-wrapper .comment-thread {
            margin-bottom: 8px !important;
            padding: 12px 14px !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 12px !important;
            background: #ffffff !important;
            box-shadow: 0 6px 16px rgba(15, 23, 42, .04);
        }

        .view-comment-wrapper .comment-thread:last-child {
            margin-bottom: 0 !important;
        }

        .view-comment-wrapper .comment-thread-reply {
            margin-top: 6px !important;
            padding: 10px 12px !important;
            border-left: 3px solid #bfdbfe !important;
            background: #f8fafc !important;
            border-radius: 10px !important;
        }

        .view-comment-wrapper .comment-bubble {
            background: transparent !important;
            padding: 0 !important;
            border-radius: 0 !important;
        }

        .view-comment-wrapper .comment-name {
            margin-bottom: 2px !important;
            color: #0f172a;
            font-size: 13px !important;
            line-height: 1.25 !important;
        }

        .view-comment-wrapper .comment-text {
            color: #334155;
            font-size: 13px !important;
            line-height: 1.42 !important;
        }

        .view-comment-wrapper .comment-meta {
            margin-top: 4px !important;
            padding-left: 0 !important;
            gap: 8px !important;
            font-size: 11px !important;
            line-height: 1.2 !important;
        }

        .view-comment-wrapper .comment-action {
            font-size: 11px !important;
            gap: 3px !important;
        }

        .view-comment-wrapper .comment-action i {
            font-size: 14px !important;
        }

        .view-comment-wrapper .replies-block,
        .view-comment-wrapper .reply-form {
            margin-left: 34px !important;
        }

        .view-comment-wrapper .replies-block {
            margin-top: 6px !important;
        }

        .view-comment-wrapper .reply-list {
            margin-top: 8px !important;
        }

        .view-comment-wrapper .comment-replies-toggle {
            padding: 4px 8px !important;
            font-size: 12px !important;
        }

        .view-comment-wrapper textarea,
        .view-comment-wrapper .form-control,
        .view-comment-wrapper .modern-comment-input {
            padding: 8px 10px !important;
            border-radius: 9px !important;
            font-size: 13px !important;
            line-height: 1.35 !important;
        }

        .view-comment-wrapper .comment-textarea {
            min-height: 74px !important;
            max-height: 80px !important;
            resize: vertical;
        }

        .view-comment-wrapper .comment-reply-textarea {
            min-height: 38px !important;
            max-height: 56px !important;
        }

        .view-comment-wrapper .comment-submit-row {
            flex-direction: row !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 8px !important;
            margin-top: 6px !important;
        }

        .view-comment-wrapper .comment-submit-row .btn {
            flex: 0 0 auto;
            padding: 5px 12px !important;
        }

        .view-comment-wrapper .pagination {
            margin: 10px 0 0;
            justify-content: center;
        }

        .view-comment-wrapper .comment-pagination {
            padding: 10px 0 0 !important;
        }

        body[data-bs-theme="dark"] .learning-view-page,
        body.dark-mode .learning-view-page {
            background: #0f172a;
        }

        body[data-bs-theme="dark"] .playlist-comment-modal .modal-title,
        body.dark-mode .playlist-comment-modal .modal-title {
            color: #e5e7eb;
        }

        body[data-bs-theme="dark"] .playlist-comment-modal .modal-body,
        body.dark-mode .playlist-comment-modal .modal-body {
            background: #0f172a;
        }

        body[data-bs-theme="dark"] .view-comment-wrapper,
        body.dark-mode .view-comment-wrapper {
            background: #0f172a;
            color: #e5e7eb;
        }

        body[data-bs-theme="dark"] .view-comment-wrapper .card,
        body.dark-mode .view-comment-wrapper .card {
            background: transparent !important;
            color: #e5e7eb !important;
            border-color: rgba(148, 163, 184, .18) !important;
        }

        body[data-bs-theme="dark"] .view-comment-wrapper .comment-thread-reply,
        body.dark-mode .view-comment-wrapper .comment-thread-reply {
            background: #111827 !important;
            border-left-color: #2563eb !important;
        }

        body[data-bs-theme="dark"] .view-comment-wrapper .comment-replies .comment-thread-reply,
        body.dark-mode .view-comment-wrapper .comment-replies .comment-thread-reply {
            background: #1f2937 !important;
            border-left-color: #3b82f6 !important;
        }

        body[data-bs-theme="dark"] .view-comment-wrapper .comment-thread,
        body.dark-mode .view-comment-wrapper .comment-thread {
            background: rgba(15, 23, 42, .72) !important;
            border-color: rgba(148, 163, 184, .16) !important;
        }

        body[data-bs-theme="dark"] .view-comment-wrapper .comment-name,
        body.dark-mode .view-comment-wrapper .comment-name {
            color: #f8fafc;
        }

        body[data-bs-theme="dark"] .view-comment-wrapper .comment-text,
        body.dark-mode .view-comment-wrapper .comment-text {
            color: #cbd5e1;
        }

        body[data-bs-theme="dark"] .view-comment-wrapper textarea,
        body[data-bs-theme="dark"] .view-comment-wrapper .form-control,
        body[data-bs-theme="dark"] .view-comment-wrapper .modern-comment-input,
        body.dark-mode .view-comment-wrapper textarea,
        body.dark-mode .view-comment-wrapper .form-control,
        body.dark-mode .view-comment-wrapper .modern-comment-input {
            background: #0f172a !important;
            color: #e5e7eb !important;
            border-color: #334155 !important;
        }

        body[data-bs-theme="dark"] .view-comment-wrapper .text-muted,
        body.dark-mode .view-comment-wrapper .text-muted {
            color: #94a3b8 !important;
        }

        @media (max-width: 768px) {
            .playlist-comment-modal .modal-dialog {
                display: flex;
                align-items: center;
                width: 90vw;
                max-width: 90vw;
                height: 90vh;
                min-height: 90vh;
                margin: 0 auto;
            }

            .playlist-comment-modal .modal-content {
                height: 90vh;
                max-height: 90vh;
                width: 100%;
            }

            .playlist-comment-modal .modal-body {
                min-height: 0;
                max-height: none;
                overflow: hidden;
            }

            .view-comment-wrapper {
                padding: 10px;
            }

            .view-comment-wrapper .comment-thread {
                margin-bottom: 8px !important;
                padding: 12px !important;
            }

            .view-comment-wrapper .replies-block,
            .view-comment-wrapper .reply-form {
                margin-left: 28px !important;
            }

            .view-comment-wrapper .comment-form .avatar,
            .view-comment-wrapper .comment-item-root > .flex-shrink-0 .avatar {
                width: 36px !important;
                height: 36px !important;
            }

            .view-comment-wrapper .comment-item-reply > .flex-shrink-0 .avatar {
                width: 28px !important;
                height: 28px !important;
            }

            .view-comment-wrapper .comment-textarea {
                min-height: 70px !important;
                max-height: 78px !important;
            }

            .view-comment-wrapper .comment-submit-row {
                flex-direction: row !important;
                align-items: center !important;
            }

            .view-comment-wrapper .comment-submit-row #note_remaining {
                min-width: 0;
                font-size: 11px !important;
            }

            .view-comment-wrapper .comment-submit-row .btn {
                padding: 5px 10px !important;
            }
        }
    </style>
@endsection

@section('script')
<script>
    (function () {
        var layout  = document.getElementById('playlistLearningLayout');
        var LS_LEFT  = 'owl_playlist_left_collapsed';
        var LS_RIGHT = 'owl_playlist_right_collapsed';
        var mobileQuery = window.matchMedia('(max-width: 991px)');
        var lastMobileState = null;
        var leftButton = document.querySelector('.learning-view-page .restore-left');
        var rightButton = document.querySelector('.learning-view-page .restore-right');
        var leftPanel = document.getElementById('content-list');
        var rightPanel = document.getElementById('ai-assistant');

        function applyDesktopState() {
            if (!layout) return;

            layout.classList.toggle('left-collapsed', localStorage.getItem(LS_LEFT) === '1');
            layout.classList.toggle('right-collapsed', localStorage.getItem(LS_RIGHT) === '1');
        }

        function isMobileView() {
            return mobileQuery.matches;
        }

        function syncPanelAccessibility() {
            var leftOpen = isMobileView()
                ? layout.classList.contains('mobile-left-open')
                : !layout.classList.contains('left-collapsed');
            var rightOpen = isMobileView()
                ? layout.classList.contains('mobile-right-open')
                : !layout.classList.contains('right-collapsed');

            if (leftButton) leftButton.setAttribute('aria-expanded', leftOpen ? 'true' : 'false');
            if (rightButton) rightButton.setAttribute('aria-expanded', rightOpen ? 'true' : 'false');
            if (leftPanel) leftPanel.setAttribute('aria-hidden', leftOpen ? 'false' : 'true');
            if (rightPanel) rightPanel.setAttribute('aria-hidden', rightOpen ? 'false' : 'true');
        }

        function resetResponsiveState(force) {
            if (!layout) return;

            var mobile = isMobileView();
            if (!force && mobile === lastMobileState) return;
            lastMobileState = mobile;

            layout.classList.remove('mobile-left-open', 'mobile-right-open');

            if (mobile) {
                layout.classList.remove('left-collapsed', 'right-collapsed');
            } else {
                applyDesktopState();
            }

            syncPanelAccessibility();
        }

        function toggleMobilePanel(side) {
            var targetClass = side === 'left' ? 'mobile-left-open' : 'mobile-right-open';
            var wasOpen = layout.classList.contains(targetClass);

            layout.classList.remove('mobile-left-open', 'mobile-right-open');
            if (!wasOpen) layout.classList.add(targetClass);

            syncPanelAccessibility();
        }

        function toggleLearningPanel(side) {
            if (!layout) return;

            if (isMobileView()) {
                toggleMobilePanel(side);
                return;
            }

            if (side === 'left') {
                layout.classList.toggle('left-collapsed');
                localStorage.setItem(LS_LEFT, layout.classList.contains('left-collapsed') ? '1' : '0');
            }
            if (side === 'right') {
                layout.classList.toggle('right-collapsed');
                localStorage.setItem(LS_RIGHT, layout.classList.contains('right-collapsed') ? '1' : '0');
            }

            syncPanelAccessibility();
        }

        window.toggleLearningPanel = toggleLearningPanel;
        resetResponsiveState(true);
        window.addEventListener('resize', function () {
            resetResponsiveState(false);
        });

        function syncPlayerFullscreenState() {
            var player = document.getElementById('contentContainer');
            var overlay = document.querySelector('.fullscreen-player-overlays');
            if (!player) return;

            var fullscreenElement = document.fullscreenElement || document.webkitFullscreenElement || null;
            var isFullscreen = fullscreenElement && (fullscreenElement === player || fullscreenElement.contains(player) || player.contains(fullscreenElement));
            player.classList.toggle('is-fullscreen', !!isFullscreen);

            if (!overlay) return;
            if (isFullscreen && fullscreenElement && !fullscreenElement.contains(overlay)) {
                fullscreenElement.appendChild(overlay);
            } else if (!isFullscreen && overlay.parentElement !== player) {
                player.insertBefore(overlay, player.firstChild);
            }
        }

        document.addEventListener('fullscreenchange', syncPlayerFullscreenState);
        document.addEventListener('webkitfullscreenchange', syncPlayerFullscreenState);

        $(document).on('click', '.overview-toggle', function () {
            var $button = $(this);
            var $overview = $('#playlistOverviewText');
            var expanded = !$overview.toggleClass('is-collapsed').hasClass('is-collapsed');
            $button.text(expanded ? $button.data('read-less') : $button.data('read-more'));
        });

    })();
</script>
    <script src="{{ asset('js/learning-progress.js') }}"></script>
    <script src="{{ $url_ytplayer_js }}"></script>

    @if (in_array($content->type, ['Document', 'SOP/Regulation']))
        <script src="{{ asset('js/pdf.min.js') }}"></script>
        <script src="{{ asset('js/pdfjs-viewer.min.js') }}"></script>
    @endif

    @include('user.playlist.components.view-player-script')
@endsection
