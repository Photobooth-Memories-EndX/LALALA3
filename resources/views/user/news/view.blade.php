@extends('layouts.sidebar-master')

@php
    $commentTotal = method_exists($comment, 'total') ? $comment->total() : (is_countable($comment) ? count($comment) : 0);
@endphp

@section('content')

<div class="page-body owl-article-page content-detail-page">
    <div class="container-xl py-4">

        @php
            $newsBackUrl = request()->query()
                ? route('news.user.index', request()->query())
                : (url()->previous() !== url()->current() ? url()->previous() : route('news.user.index'));
        @endphp
        {{-- BACK BUTTON --}}
        <a href="{{ $newsBackUrl }}" class="btn btn-link text-decoration-none ps-0 mb-3">
            <i class="ti ti-arrow-left me-1"></i> Back
        </a>

        <div class="row owl-article-layout g-4">
            <div class="col-12">

                {{-- 1. BANNER / HERO IMAGE DI PALING ATAS --}}
                <div class="owl-article-hero-wrapper rounded-4 overflow-hidden mb-4 shadow-sm">
                    <div class="news-img-placeholder news-article-hero-placeholder">
                        <i class="ti ti-news"></i>
                    </div>

                    @if(!empty($news->image))
                        <img
                            src="{{ asset('storage/news/' . $news->image) }}"
                            class="news-cover-img"
                            alt="{{ $news->title ?? '' }}"
                            loading="lazy"
                            onerror="this.style.display='none'"
                        >
                    @endif
                </div>

                {{-- MAIN CONTENT CARD / OVERVIEW BOX STRUCTURE LIKE ALL CONTENT --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4 view-content-info-card">
                    <div class="card-body p-4">

                        {{-- 2. JUDUL ARTIKEL --}}
                        <h1 class="fw-bold owl-article-title mb-3">
                            {{ $news->title ?? '-' }}
                        </h1>

                        {{-- 3. META BAR AUTHOR - FORMAT: [Author] - [Tanggal] - [Views] - [Comments] --}}
                        <div class="d-flex flex-wrap align-items-center gap-2 text-muted small mb-4 pb-3 border-bottom owl-border-color">
                            <span class="d-inline-flex align-items-center">
                                <i class="ti ti-user me-1 text-primary"></i>
                                <strong class="text-dark me-1">{{ $news->user->name ?? 'Training Team' }}</strong>
                            </span>
                            <span>-</span>
                            <span class="d-inline-flex align-items-center">
                                <i class="ti ti-calendar me-1"></i>
                                {{ $news->created_at ? $news->created_at->format('d M Y') : '-' }}
                            </span>
                            <span>-</span>
                            <span class="d-inline-flex align-items-center">
                                <i class="ti ti-eye me-1"></i>
                                {{ number_format($news->views ?? 0) }} Views
                            </span>
                            <span>-</span>
                            <button type="button"
                                    class="btn btn-link text-muted p-0 text-decoration-none d-inline-flex align-items-center fw-semibold"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modal-news-comments">
                                <i class="ti ti-message-circle me-1 text-primary"></i>
                                {{ $commentTotal }} Comments
                            </button>
                        </div>

                        {{-- 4. OVERVIEW BOX FOR ARTICLE DESCRIPTION / CONTENT --}}
                        <div class="chapter-description-box mb-0">
                            <h4 class="fs-6 fw-bold mb-3">Overview</h4>
                            <div class="overview-text text-dark" style="font-size: 1.05rem; line-height: 1.8;">
                                {!! nl2br(e($news->description ?? '')) !!}
                            </div>
                        </div>

                    </div>
                </div>

                {{-- RELATED ARTICLES --}}
                @if($related->count() > 0)
                    <div class="row border-top owl-border-color-important owl-explore-section pt-4">
                        <div class="col-12 mb-4">
                            <h2 class="fw-bold owl-text-color owl-explore-heading mb-3">
                                <i class="ti ti-arrow-right me-2 text-primary"></i>Continue exploring
                            </h2>
                            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                                @foreach($related as $article)
                                    <div class="col">
                                        <a
                                            href="{{ route('news.user.view', array_merge(['slug' => $article->slug], request()->query())) }}"
                                            class="card border-0 h-100 owl-explore-card shadow-sm text-decoration-none overflow-hidden owl-text-color rounded-4"
                                        >
                                            <div class="owl-explore-image-wrapper overflow-hidden">
                                                <div class="news-img-placeholder news-explore-placeholder">
                                                    <i class="ti ti-news"></i>
                                                </div>
                                                @if(!empty($article->image))
                                                    <img
                                                        src="{{ asset('storage/news/' . $article->image) }}"
                                                        class="owl-explore-image"
                                                        alt="{{ $article->title ?? '' }}"
                                                        loading="lazy"
                                                        onerror="this.style.display='none'"
                                                    >
                                                @endif
                                            </div>
                                            <div class="card-body owl-surface-bg owl-explore-body p-3">
                                                <small class="text-secondary d-block mb-1">
                                                    {{ $article->created_at ? $article->created_at->format('M d, Y') : '-' }}
                                                </small>
                                                <h5 class="fw-bold owl-text-color owl-explore-title mb-3">
                                                    {{ Str::limit($article->title ?? '', 65) }}
                                                </h5>
                                                <span class="btn btn-sm btn-outline-primary rounded-pill">
                                                    Read more
                                                </span>
                                            </div>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        </div>

    </div>
</div>

@include('user.news.partials.comment-modal', ['news' => $news, 'comment' => $comment])

@endsection

@section('style')
<link rel="stylesheet" href="{{ asset('css/owl-news.css') }}">
@endsection

@section('script')

<button class="dark-mode-toggle" id="darkModeToggle" aria-label="Toggle dark mode" title="Toggle dark mode">
    <i class="ti ti-moon-stars"></i>
</button>

<script>
(function(){
    function initDarkMode() {
        const isDarkMode = localStorage.getItem('owl-dark-mode') === 'true';
        const toggle = document.getElementById('darkModeToggle');
        if (isDarkMode) {
            document.body.classList.add('dark-mode');
            updateToggleIcon(true);
        }
        if (toggle) {
            toggle.addEventListener('click', function() {
                document.body.classList.toggle('dark-mode');
                const isNowDark = document.body.classList.contains('dark-mode');
                localStorage.setItem('owl-dark-mode', isNowDark);
                updateToggleIcon(isNowDark);
            });
        }
    }

    function updateToggleIcon(isDark) {
        const toggle = document.getElementById('darkModeToggle');
        if (toggle) {
            toggle.innerHTML = isDark ? '<i class="ti ti-sun"></i>' : '<i class="ti ti-moon-stars"></i>';
        }
    }

    function initTooltips() {
        if (window.bootstrap) {
            [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
              .forEach(function(el){ new bootstrap.Tooltip(el); });
        }
    }

    function initArticleImages() {
        var articlePage = document.querySelector('.owl-article-page');
        if (!articlePage) return;

        articlePage.classList.add('has-image-transitions');
        articlePage.querySelectorAll('.news-cover-img, .owl-explore-image')
            .forEach(function(image) {
                function showImage() {
                    image.classList.add('is-loaded');
                }

                if (image.complete && image.naturalWidth > 0) {
                    showImage();
                } else {
                    image.addEventListener('load', showImage, { once: true });
                }
            });
    }

    document.addEventListener('DOMContentLoaded', function(){
        initDarkMode();
        initTooltips();
        initArticleImages();
    });
})();
</script>

@endsection
