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

        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary mb-3 rounded-pill px-3 content-back-btn">
            <i class="ti ti-arrow-left me-1"></i>
            Back
        </a>

        <div class="row g-4">
            <div class="col-lg-8">
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

            <div class="col-lg-4">
                @include('user.content.components.content-learning-sidebar', [
                    'discussionCount' => $discussionCount,
                    'bookmarkCount' => $bookmarkCount,
                    'isBookmarked' => $isBookmarked,
                ])
            </div>
        </div>

    </div>
</div>
@endsection

@section('style')
    @if (($content->type == 'Video') || ($content->type == 'YouTube'))
        <link rel="stylesheet" href="{{ $url_ytplayer_css }}">
    @endif

    @if ($content->type == 'Document' || $content->type == 'SOP/Regulation')
        <link rel="stylesheet" href="{{ asset('Tabler/dist/pdfjs-viewer.min.css') }}">
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
            min-width: 240px;
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

        .pdf-toolbar {
            padding: 14px;
            background: rgba(248, 250, 252, .92);
            border-bottom: 1px solid #e5e7eb;
            border-radius: 1rem 1rem 0 0;
            backdrop-filter: blur(12px);
            z-index: 3;
        }

        .pdfjs-viewer {
            height: 800px !important;
            background: #f1f5f9;
            border-radius: 0 0 1rem 1rem;
            overflow: hidden;
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

        body[data-bs-theme="dark"] .pdf-toolbar,
        body.dark-mode .pdf-toolbar {
            background: rgba(15, 23, 42, .92);
            border-color: rgba(255, 255, 255, .08);
        }

        body[data-bs-theme="dark"] .pdfjs-viewer,
        body.dark-mode .pdfjs-viewer {
            background: #0f172a;
        }

        @media (max-width: 768px) {
            .content-detail-title {
                font-size: 1.45rem;
            }

            .pdfjs-viewer {
                height: 600px !important;
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
    @if ($content->type == 'Document' || $content->type == 'SOP/Regulation')
        <script src="{{ asset('js/pdf.min.js') }}"></script>
        <script src="{{ asset('js/pdfjs-viewer.min.js') }}"></script>

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

            let pdfViewer = new PDFjsViewer($(".pdfjs-viewer"), {
                onDocumentReady: () => {
                    var progression = {{ (int) $content_user->progress }};
                    var current_page = (progression * pdfViewer.getPageCount()) / 100;

                    if (parseInt(current_page) < pdfViewer.getPageCount()) {
                        pdfViewer.scrollToPage(parseInt(current_page));
                    }

                    hideContentPlayerSkeleton();
                },

                onZoomChange: function (zoom) {
                    zoom = parseInt(zoom * 10000) / 100;
                    $(".zoomval").text(zoom + "%");
                },

                onActivePageChanged: function (page, pageno) {
                    $(".pageno").text(pageno + "/" + this.getPageCount());

                    $.ajax({
                        type: "PUT",
                        url: $('#player_paused').data('url'),
                        data: {
                            'current_time': pageno,
                            'duration': this.getPageCount(),
                            '_token': '{{ csrf_token() }}'
                        },
                        success: function(data) {
                            console.log(data.success);
                        }
                    });
                }
            });

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

                player.on('loadeddata', onDataLoaded);
                player.on('ready', function() {
                    hideContentPlayerSkeleton();
                });

                function onDataLoaded() {
                    var progression = $('#player_progress').val();
                    var durasi = $('#player_duration').val();
                    var current_player_time = (progression * durasi) / 100;

                    if (current_player_time < durasi) {
                        player.currentTime = current_player_time;
                    }

                    if (current_player_time <= player.duration) {
                        player.off('loadeddata', onDataLoaded);
                        player.currentTime = current_player_time;
                    }

                    hideContentPlayerSkeleton();
                }

                player.on('pause', function() {
                    $.ajax({
                        type: "PUT",
                        url: $('#player_paused').data('url'),
                        data: {
                            'current_time': player.currentTime,
                            'duration': player.duration,
                            '_token': '{{ csrf_token() }}'
                        },
                        success: function(data) {
                            console.log(data.success);
                        }
                    });
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