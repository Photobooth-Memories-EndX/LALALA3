@extends('layouts.sidebar-master')

@section('content')

<div class="page-body owl-article-page">
    <div class="container-fluid px-4 px-xl-5">

        {{-- ============================================================
             BREADCRUMB + META
             ============================================================ --}}
        <div class="row mb-5">
            <div class="col-12">

                <nav class="owl-breadcrumb mb-4">
                    <ol class="breadcrumb bg-transparent px-0 mb-0">
                        <li class="breadcrumb-item">
                            <a href="{{ route('news.user.index') }}" class="text-secondary">News Hub</a>
                        </li>
                        <li class="breadcrumb-item active fw-semibold owl-text-color">
                            {{ Str::limit($news->title ?? '', 45) }}
                        </li>
                    </ol>
                </nav>

                {{-- META PILLS --}}
                <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
                    <span class="owl-news-pill">
                        <i class="ti ti-calendar-event"></i>
                        {{ $news->created_at ? $news->created_at->format('F d, Y') : '-' }}
                    </span>
                    <span class="owl-news-pill">
                        <i class="ti ti-clock"></i>
                        {{ $readTime }} min read
                    </span>
                    <span class="owl-news-pill">
                        <i class="ti ti-eye"></i>
                        {{ number_format($news->views ?? 0) }} views
                    </span>
                </div>

                {{-- TITLE --}}
                <h1 class="fw-bold owl-article-title mb-4">
                    {{ $news->title ?? '-' }}
                </h1>

                {{-- AUTHOR --}}
                <div class="d-flex align-items-center gap-3 pb-4 mb-4 border-bottom owl-border-color">
                    <div
                        class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0 owl-article-author-avatar"
                    >
                        {{ strtoupper(substr($news->user->name ?? 'U', 0, 1)) }}
                    </div>
                    <div>
                        <h5 class="mb-1 fw-semibold owl-text-color">
                            {{ $news->user->name ?? 'Training Team' }}
                        </h5>
                        <div class="text-secondary small">
                            {{ $news->user->email ?? '' }}
                        </div>
                    </div>
                </div>

                {{-- ARTICLE INSIGHTS --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4 owl-insights-card">
                    <div class="card-body p-4">
                        <h5 class="fw-semibold mb-4 owl-text-color">Article Insights</h5>
                        <div class="row text-center gy-3">
                            <div class="col-md-4">
                                <div class="text-secondary small">Read Time</div>
                                <div class="fw-bold fs-5 owl-text-color">{{ $readTime }} min</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-secondary small">Views</div>
                                <div class="fw-bold fs-5 owl-text-color">{{ number_format($news->views ?? 0) }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-secondary small">Author</div>
                                <div class="fw-bold owl-text-color">{{ $news->user->name ?? 'Training Team' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">

                        {{-- HERO IMAGE --}}
                        <div class="owl-article-hero-wrapper rounded-4 overflow-hidden mb-5">

                            {{-- Placeholder always behind --}}
                            <div class="news-img-placeholder news-article-hero-placeholder">
                                <i class="ti ti-news"></i>
                            </div>

                            {{-- Real image on top --}}
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

                        {{-- ARTICLE BODY --}}
                        <article class="owl-article-content mb-5">
                            <div class="article-body text-dark">
                                {!! nl2br(e($news->description ?? '')) !!}
                            </div>
                        </article>

                        {{-- ============================================================
                             COMMENTS
                             ============================================================ --}}
                        <div class="mb-5">

                            <x-comment :content="$news" :comment="$comment" type="news" />

                        </div>{{-- end comments --}}

                    </div>
                </div>

                {{-- ============================================================
                     RELATED ARTICLES
                     ============================================================ --}}
                @if($related->count() > 0)
                    <div class="row mt-6 pt-5 border-top owl-border-color-important">
                        <div class="col-12 mb-4">
                            <h2 class="fw-bold mb-4 owl-text-color">
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
                                            <div class="card-body owl-surface-bg">
                                                <small class="text-secondary">
                                                    {{ $article->created_at ? $article->created_at->format('M d, Y') : '-' }}
                                                </small>
                                                <h5 class="fw-bold mt-2 mb-3 owl-text-color">
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

    function initShareButtons() {
        var currentUrl = window.location.href;
        var articleTitle = '{{ addslashes($news->title ?? '') }}';
        document.addEventListener('click', function(e) {
            if (e.target.closest('[title*="LinkedIn"]')) {
                window.open('https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(currentUrl), '_blank', 'width=600,height=400');
            } else if (e.target.closest('[title*="Twitter"]')) {
                window.open('https://twitter.com/intent/tweet?text=' + encodeURIComponent(articleTitle) + '&url=' + encodeURIComponent(currentUrl), '_blank', 'width=600,height=400');
            } else if (e.target.closest('[title*="Copy"]')) {
                navigator.clipboard && navigator.clipboard.writeText(currentUrl).then(function(){ alert('Link copied!'); });
            } else if (e.target.closest('[title*="Email"]')) {
                window.location.href = 'mailto:?subject=' + encodeURIComponent(articleTitle) + '&body=' + encodeURIComponent(currentUrl);
            }
        });
    }

    function initSaveButtons() {
        var newsId = '{{ $news->id }}';
        document.querySelectorAll('[title="Save article"], [title="Save for later"]').forEach(function(btn) {
            if (localStorage.getItem('owl-saved-article-' + newsId)) {
                btn.classList.add('active');
                btn.setAttribute('aria-pressed', 'true');
            }
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                btn.classList.toggle('active');
                var isSaved = btn.classList.contains('active');
                btn.setAttribute('aria-pressed', isSaved);
                if (isSaved) {
                    localStorage.setItem('owl-saved-article-' + newsId, 'true');
                } else {
                    localStorage.removeItem('owl-saved-article-' + newsId);
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function(){
        initDarkMode();
        initTooltips();
        initShareButtons();
        initSaveButtons();
    });
})();
</script>

@endsection
