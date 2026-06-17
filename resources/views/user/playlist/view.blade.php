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

            <div class="row g-3 align-items-start">
                <div class="col-xl-3 col-lg-4">
                    @include('user.playlist.components.view-playlist-sidebar')
                </div>

                <div class="col-xl-6 col-lg-8">
                    @include('user.playlist.components.view-player')
                    @include('user.playlist.components.view-content-info')
                    @include('user.playlist.components.view-navigation', [
                        'content' => $content,
                        'content_all' => $content_all,
                        'playlist' => $playlist,
                    ])
                </div>

                <div class="col-xl-3 col-lg-12">
                    @include('user.playlist.components.view-ai-assistant')
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
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
    @include('user.playlist.components.view-style')

    <style>
        .view-comment-wrapper {
            max-width: 1120px;
            margin: 32px auto 0;
            padding: 28px 36px;
            border-radius: 24px;
            background: #ffffff;
        }

        .view-comment-wrapper #comment {
            padding: 0 !important;
            color: inherit;
        }

        .view-comment-wrapper #comment .card-header {
            padding: 24px !important;
        }

        .view-comment-wrapper #comment .card-body {
            padding: 0 !important;
        }

        .view-comment-wrapper .comment-thread-root {
            padding: 20px 24px !important;
        }

        .view-comment-wrapper .comment-thread-reply {
            margin-top: 16px !important;
            padding: 16px 20px 16px 24px !important;
            border-left: 3px solid #bfdbfe !important;
            background: #f8fafc !important;
            border-radius: 14px !important;
        }

        .view-comment-wrapper .comment-replies {
            margin-top: 16px !important;
            padding-left: 48px !important;
        }

        .view-comment-wrapper .comment-replies-nested {
            margin-top: 14px !important;
            padding-left: 48px !important;
        }

        .view-comment-wrapper .comment-replies .comment-replies {
            padding-left: 48px !important;
        }

        .view-comment-wrapper .comment-card {
            padding: 16px 20px !important;
            border-radius: 14px !important;
        }

        .view-comment-wrapper .comment-card-reply {
            background: rgba(255, 255, 255, .88) !important;
        }

        .view-comment-wrapper textarea,
        .view-comment-wrapper .form-control,
        .view-comment-wrapper .modern-comment-input {
            padding: 14px 18px !important;
            border-radius: 12px !important;
        }

        .view-comment-wrapper .pagination {
            margin: 24px 0 0;
            justify-content: center;
        }

        body[data-bs-theme="dark"] .learning-view-page,
        body.dark-mode .learning-view-page {
            background: #0f172a;
            color: #e5e7eb;
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

        body[data-bs-theme="dark"] .view-comment-wrapper .comment-replies .comment-replies .comment-thread-reply,
        body.dark-mode .view-comment-wrapper .comment-replies .comment-replies .comment-thread-reply {
            background: #26364f !important;
            border-left-color: #60a5fa !important;
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
                padding: 18px 14px;
                margin-top: 24px;
            }

            .view-comment-wrapper .comment-thread-root {
                padding: 14px 12px !important;
            }

            .view-comment-wrapper .comment-replies,
            .view-comment-wrapper .comment-replies-nested,
            .view-comment-wrapper .comment-replies .comment-replies {
                padding-left: 18px !important;
            }

            .view-comment-wrapper .comment-thread-reply {
                padding: 14px 12px 14px 16px !important;
            }
        }

        
    </style>
@endsection

@section('script')
    <script src="{{ $url_ytplayer_js }}"></script>
    @include('user.playlist.components.view-player-script')
@endsection