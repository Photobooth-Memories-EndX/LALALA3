@extends('layouts.sidebar-master')

@section('content')

<div class="page-body owl-article-page">
    <div class="container-fluid px-4 px-xl-5">

        {{-- BACK BUTTON --}}
        <a href="{{ route('news.user.index') }}" class="btn btn-link text-decoration-none ps-0 mb-3">
            <i class="ti ti-arrow-left me-1"></i> Back
        </a>

        <div class="row owl-article-layout">
            <div class="col-12 px-0 ms-0 ps-0">

                {{-- 1. BANNER / HERO IMAGE DI PALING ATAS --}}
                <div class="owl-article-hero-wrapper rounded-4 overflow-hidden mb-4">
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

                {{-- 2. JUDUL ARTIKEL --}}
                <h1 class="fw-bold owl-article-title mb-3 px-0 ms-0 text-start">
                    {{ $news->title ?? '-' }}
                </h1>

                {{-- 3. META BAR (TANGGAL & VIEWS - TANPA MIN READ) --}}
                <div class="d-flex flex-wrap gap-2 align-items-center owl-article-meta mb-3 px-0 ms-0 text-start">
                    <span class="owl-news-pill">
                        <i class="ti ti-calendar-event"></i>
                        {{ $news->created_at ? $news->created_at->format('F d, Y') : '-' }}
                    </span>
                    <span class="owl-news-pill">
                        <i class="ti ti-eye"></i>
                        {{ number_format($news->views ?? 0) }} views
                    </span>
                </div>

                {{-- PROFIL AUTHOR --}}
                <div class="d-flex align-items-center gap-3 border-bottom owl-border-color owl-article-author pb-3 mb-4 px-0 ms-0 text-start">
                    <div
                        class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0 owl-article-author-avatar"
                    >
                        {{ strtoupper(substr($news->user->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="owl-article-author-copy">
                        <h5 class="mb-1 fw-semibold owl-text-color owl-article-author-name">
                            {{ $news->user->name ?? 'Training Team' }}
                        </h5>
                        <div class="text-secondary small owl-article-author-email">
                            {{ $news->user->email ?? '' }}
                        </div>
                    </div>
                </div>

                {{-- 4. BODY CONTENT / DESKRIPSI ARTIKEL (RATA KIRI SEJAJAR DENGAN HEADER) --}}
                <article class="owl-article-content mb-5 px-0 ms-0 text-start" style="margin-left: 0 !important; padding-left: 0 !important;">
                    <div class="article-body text-dark px-0 ms-0 text-start" style="font-size: 1.05rem; line-height: 1.8; margin-left: 0 !important; padding-left: 0 !important;">
                        {!! nl2br(e($news->description ?? '')) !!}
                    </div>
                </article>

                        {{-- ============================================================
                             COMMENTS
                             ============================================================ --}}
                        <div class="owl-comment-area">

                            <x-comment :content="$news" :comment="$comment" type="news" />

                        </div>{{-- end comments --}}

                    </div>
                </div>

                {{-- ============================================================
                     RELATED ARTICLES
                     ============================================================ --}}
                @if($related->count() > 0)
                    <div class="row border-top owl-border-color-important owl-explore-section">
                        <div class="col-12 mb-4">
                            <h2 class="fw-bold owl-text-color owl-explore-heading">
                                <i class="ti ti-arrow-right me-2"></i>Continue exploring
                            </h2>
                            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                                @foreach($related as $article)
                                    <div class="col">
                                        <a
                                            href="{{ route('news.user.view', $article->slug) }}"
                                            class="card border-0 h-100 owl-explore-card shadow-sm text-decoration-none overflow-hidden owl-text-color"
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
                                            <div class="card-body owl-surface-bg owl-explore-body">
                                                <small class="text-secondary">
                                                    {{ $article->created_at ? $article->created_at->format('M d, Y') : '-' }}
                                                </small>
                                                <h5 class="fw-bold owl-text-color owl-explore-title">
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

            </div>{{-- col-12 --}}
        </div>{{-- row --}}

    </div>{{-- container --}}
</div>{{-- page-body --}}

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
