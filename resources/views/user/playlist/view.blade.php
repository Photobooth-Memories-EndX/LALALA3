@php
    $url_ytplayer_js = asset('Tabler/dist/libs/plyr/dist/plyr.min.js?1684106062');
    $url_ytplayer_css = asset('Tabler/dist/libs/plyr/dist/plyr.css?1684106062');
@endphp

@extends('layouts.master')

<style>/* Wrapper comment agar tidak mepet */
#comment {
    padding: 20px;
}

#comment .card-header {
    padding: 24px !important;
}

#comment .card-body {
    padding: 0 !important;
}

/* Comment level utama */
.comment-level-0,
.comment-thread-root {
    padding: 20px 24px !important;
}

/* Reply level 1 */
.comment-level-1 {
    margin-left: 48px;
    padding: 16px 20px !important;
    border-left: 3px solid #dbeafe;
    background: #f8fafc;
    border-radius: 14px;
}

/* Reply level 2 */
.comment-level-2 {
    margin-left: 96px;
    padding: 16px 20px !important;
    border-left: 3px solid #bfdbfe;
    background: #f1f5f9;
    border-radius: 14px;
}

/* Reply level 3 */
.comment-level-3 {
    margin-left: 144px;
    padding: 16px 20px !important;
    border-left: 3px solid #93c5fd;
    background: #e2e8f0;
    border-radius: 14px;
}

/* Mobile jangan terlalu menjorok */
@media (max-width: 768px) {
    #comment {
        padding: 12px;
    }

    .comment-level-1 {
        margin-left: 20px;
    }

    .comment-level-2 {
        margin-left: 36px;
    }

    .comment-level-3 {
        margin-left: 52px;
    }
}</style>

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
                    @include('user.playlist.components.view-navigation')
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
@endsection

@section('script')
    <script src="{{ $url_ytplayer_js }}"></script>
    @include('user.playlist.components.view-player-script')
@endsection