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

            <div id="playlistLearningLayout" class="playlist-learning-layout">

                {{-- Restore buttons: hanya muncul saat panel di-collapse, posisi absolute di tepi layout --}}
                <button type="button"
                        class="restore-toggle-btn restore-left"
                        onclick="toggleLearningPanel('left')"
                        title="Buka Playlist">
                    <i class="fas fa-list-ul"></i>
                </button>

                <button type="button"
                        class="restore-toggle-btn restore-right"
                        onclick="toggleLearningPanel('right')"
                        title="Buka AI Assistant">
                    <i class="fas fa-robot"></i>
                </button>

                <div class="learning-left-panel">
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

                <div class="learning-right-panel">
                    @include('user.playlist.components.view-ai-assistant')
                </div>

            </div>

        </div>
    </div>

    <div class="modal fade playlist-comment-modal" id="playlistCommentModal" tabindex="-1" aria-labelledby="playlistCommentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title" id="playlistCommentModalLabel">
                        <i class="far fa-comment-dots me-2"></i>
                        Comments
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
        .view-comment-wrapper {
            margin-top: 16px;
            padding: 24px 28px;
            border-radius: 20px;
            background: #ffffff;
            box-shadow: 0 1px 6px rgba(15, 23, 42, .07);
        }

        .view-comment-wrapper #comment {
            padding: 0 !important;
        }

        .view-comment-wrapper #comment .card-header {
            padding: 18px 0 !important;
        }

        .view-comment-wrapper #comment .card-body {
            padding: 0 !important;
        }

        .view-comment-wrapper .comment-thread-root {
            padding: 16px 20px !important;
        }

        .view-comment-wrapper .comment-thread-reply {
            margin-top: 12px !important;
            padding: 12px 16px 12px 18px !important;
            border-left: 3px solid #bfdbfe !important;
            background: #f8fafc !important;
            border-radius: 12px !important;
        }

        .view-comment-wrapper .comment-replies {
            margin-top: 12px !important;
            padding-left: 40px !important;
        }

        .view-comment-wrapper .comment-replies-nested {
            margin-top: 10px !important;
            padding-left: 40px !important;
        }

        .view-comment-wrapper .comment-replies .comment-replies {
            padding-left: 40px !important;
        }

        .view-comment-wrapper .comment-card {
            padding: 14px 18px !important;
            border-radius: 12px !important;
        }

        .view-comment-wrapper .comment-card-reply {
            background: rgba(255, 255, 255, .88) !important;
        }

        .view-comment-wrapper textarea,
        .view-comment-wrapper .form-control,
        .view-comment-wrapper .modern-comment-input {
            padding: 12px 16px !important;
            border-radius: 10px !important;
        }

        .view-comment-wrapper .pagination {
            margin: 20px 0 0;
            justify-content: center;
        }

        body[data-bs-theme="dark"] .learning-view-page,
        body.dark-mode .learning-view-page {
            background: #0f172a;
        }

        body[data-bs-theme="dark"] .view-comment-wrapper,
        body.dark-mode .view-comment-wrapper {
            background: #162235;
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

        body[data-bs-theme="dark"] .view-comment-wrapper .comment-card,
        body.dark-mode .view-comment-wrapper .comment-card {
            background: rgba(15, 23, 42, .72) !important;
            border-color: rgba(148, 163, 184, .16) !important;
        }

        body[data-bs-theme="dark"] .view-comment-wrapper .comment-card-reply,
        body.dark-mode .view-comment-wrapper .comment-card-reply {
            background: rgba(17, 24, 39, .88) !important;
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
            .view-comment-wrapper {
                padding: 16px 14px;
                margin-top: 12px;
            }

            .view-comment-wrapper .comment-replies,
            .view-comment-wrapper .comment-replies-nested,
            .view-comment-wrapper .comment-replies .comment-replies {
                padding-left: 16px !important;
            }

            .view-comment-wrapper .comment-thread-reply {
                padding: 12px 10px 12px 14px !important;
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

        function applyState() {
            if (!layout) return;
            if (localStorage.getItem(LS_LEFT)  === '1') layout.classList.add('left-collapsed');
            if (localStorage.getItem(LS_RIGHT) === '1') layout.classList.add('right-collapsed');
        }

        function toggleLearningPanel(side) {
            if (!layout) return;
            if (side === 'left') {
                layout.classList.toggle('left-collapsed');
                localStorage.setItem(LS_LEFT, layout.classList.contains('left-collapsed') ? '1' : '0');
            }
            if (side === 'right') {
                layout.classList.toggle('right-collapsed');
                localStorage.setItem(LS_RIGHT, layout.classList.contains('right-collapsed') ? '1' : '0');
            }
        }

        window.toggleLearningPanel = toggleLearningPanel;
        applyState();

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
