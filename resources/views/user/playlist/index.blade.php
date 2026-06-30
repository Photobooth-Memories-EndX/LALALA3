@extends('layouts.sidebar-master')

@section('content')
<div class="page-body">
    <div class="container-xl">

        @include('user.playlist.components.playlist-hero')

        @include('user.playlist.components.playlist-continue-learning')

        @include('user.playlist.components.playlist-filters')

        @if(count($trainingplan) > 0)

            @include('user.playlist.components.playlist-grid')

            <div class="playlist-pagination mt-4 d-flex justify-content-center">
                {{ $trainingplan->links() }}
            </div>

        @else

            @include('user.playlist.components.playlist-empty')

        @endif

    </div>
</div>
@endsection

@section('style')
<style>


    .playlist-pagination nav {
        width: 100%;
    }

    .playlist-pagination nav > div:first-child {
        display: none !important;
    }

    .playlist-pagination nav > div:last-child {
        display: flex !important;
        justify-content: center !important;
        width: 100% !important;
    }

    .playlist-pagination .pagination {
        justify-content: center !important;
        margin-bottom: 0 !important;
    }
    .rounded-4 {
        border-radius: 20px !important;
    }

    .playlist-hero {
        background:
            radial-gradient(circle at top right, rgba(32, 107, 196, .16), transparent 35%),
            linear-gradient(135deg, rgba(32, 107, 196, .08), var(--tblr-bg-surface));
        border: 1px solid var(--tblr-border-color);
    }

    .playlist-card {
        transition: all .2s ease;
    }

    .playlist-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 14px 30px rgba(15, 23, 42, .12) !important;
    }

    .playlist-thumbnail {
        height: 180px;
        object-fit: cover;
        background: var(--tblr-bg-surface-secondary);
    }

    .playlist-card-image {
        width: 100%;
        height: 180px;
        object-fit: cover;
        display: block;
    }

    .playlist-card-placeholder {
        width: 100%;
        height: 180px;
        background: #f3f6fb;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .playlist-card-placeholder-icon {
        width: 64px;
        height: 64px;
        border-radius: 8px;
        background: #e8eef7;
        border: 1px solid #cbd8ea;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .playlist-card-placeholder-icon i {
        font-size: 30px;
        color: #2563eb;
    }

    .playlist-card-placeholder-text {
        font-size: 13px;
        color: #64748b;
    }

    [data-bs-theme="dark"] .playlist-card-placeholder,
    body.theme-dark .playlist-card-placeholder,
    body.dark .playlist-card-placeholder {
        background: #111827;
    }

    [data-bs-theme="dark"] .playlist-card-placeholder-icon,
    body.theme-dark .playlist-card-placeholder-icon,
    body.dark .playlist-card-placeholder-icon {
        background: #1f2937;
        border-color: rgba(255, 255, 255, .12);
    }

    .playlist-title {
        font-size: 16px;
        font-weight: 700;
        min-height: 44px;
    }

    .playlist-content-list {
        min-height: 72px;
    }

    .playlist-filter-card {
        border: 1px solid var(--tblr-border-color);
    }

    [data-bs-theme="dark"] .playlist-hero,
    body.theme-dark .playlist-hero,
    body.dark .playlist-hero {
        background:
            radial-gradient(circle at top right, rgba(32, 107, 196, .25), transparent 35%),
            linear-gradient(135deg, rgba(32, 107, 196, .12), var(--tblr-bg-surface));
    }

    .playlist-learning-path-wrap {
    padding-left: 2px;
    padding-right: 2px;
}

.playlist-continue-card {
    background:
        radial-gradient(circle at top right, rgba(255,255,255,.18), transparent 30%),
        linear-gradient(135deg, #0d5be1 0%, #2563eb 50%, #3b82f6 100%);
    color: #fff;
}

.text-white-75 {
    color: rgba(255,255,255,.78);
}

.playlist-progress-badge {
    min-width: 112px;
    padding: 14px 16px;
    border-radius: 18px;
    text-align: center;
    background: rgba(255,255,255,.18);
    color: #fff;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.18);
}

.playlist-progress-badge .small {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .05em;
    opacity: .9;
}

.playlist-white-progress {
    height: 8px;
    background: rgba(255,255,255,.22);
    border-radius: 999px;
}

.playlist-path-card {
    background:
        radial-gradient(circle at top right, rgba(32,107,196,.16), transparent 35%),
        linear-gradient(135deg, rgba(32,107,196,.08), var(--tblr-bg-surface));
    border: 1px solid var(--tblr-border-color) !important;
}

.playlist-continue-title {
    max-width: 560px;
    line-height: 1.25;
}

@media (max-width: 991.98px) {
    .playlist-progress-badge {
        min-width: 96px;
    }
}
</style>
@endsection

@section('script')
@include('user.playlist.components.playlist-script')
@endsection
