@extends('layouts.sidebar-master')

@php
    $commentTotal = $news->total_comments_count ?? ($news->allComments ? $news->allComments->count() : $news->allComments()->count());
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
                @if(!empty($news->image))
                    <div class="my-3 text-center">
                        <img
                            src="{{ asset('storage/news/' . $news->image) }}"
                            class="img-fluid rounded-4 shadow-sm d-block mx-auto w-100 h-auto"
                            alt="{{ $news->title ?? '' }}"
                            loading="lazy"
                            onerror="this.style.display='none'"
                            style="max-width: 100%; height: auto !important; object-fit: contain;"
                        >
                    </div>
                @endif

                {{-- MAIN CONTENT CARD (PLAYLIST STYLE) --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white p-4 p-md-5">
                    <!-- 1. Meta Atas Judul -->
                    <div class="text-muted small mb-1">
                        {{ $news->created_at ? $news->created_at->format('d F Y') : '-' }} created by {{ $news->user->name ?? 'Training Team' }}
                    </div>

                    <!-- 2. Judul (Tanpa Tombol Share) -->
                    <h2 class="fw-bold text-dark fs-3 mb-2">{{ $news->title ?? '-' }}</h2>

                    <!-- 3. Meta Stats Bawah Judul -->
                    <div class="d-flex align-items-center gap-2 text-muted small mb-4">
                        <button type="button" class="btn btn-link text-muted p-0 text-decoration-none d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modal-news-comments">
                            <i class="ti ti-message-dots"></i>
                            <span><span class="news-comment-total-count">{{ $commentTotal }}</span> Comments</span>
                        </button>
                        <span>•</span>
                        <span><i class="ti ti-eye me-1"></i> {{ number_format($news->views ?? 0) }} Views</span>
                    </div>

                    <!-- 4. Kontainer Overview Style Playlist -->
                    <div class="rounded-4 p-4 p-md-5" style="background-color: #f8fafc; border: 1px solid #f1f5f9;">
                        <div class="text-muted small fw-bold mb-3">Overview</div>
                        <div class="news-content text-dark fs-5 lh-lg" style="color: #212529 !important; font-size: 1.05rem !important;">
                            {!! nl2br(e($news->description ?? '')) !!}
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

function shareNews() {
    if (navigator.share) {
        navigator.share({
            title: {{ json_encode($news->title ?? 'News') }},
            url: window.location.href
        }).catch(function() {});
    } else {
        navigator.clipboard.writeText(window.location.href).then(function() {
            alert('Article link copied to clipboard!');
        });
    }
}
</script>

@endsection
