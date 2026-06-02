@extends('layouts.sidebar-master')

@section('content')

<div class="page-body owl-article-page">

    <div class="container-xl">

        <div class="row gx-5 mb-5 align-items-center">
            <div class="col-lg-8">
                <nav class="owl-breadcrumb mb-4" style="--bs-breadcrumb-divider:'/'">
                    <ol class="breadcrumb bg-transparent px-0 mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('news.user.index') }}" class="text-secondary">News Hub</a></li>
                        <li class="breadcrumb-item active text-dark fw-semibold">{{ Str::limit($news->title, 45) }}</li>
                    </ol>
                </nav>

                <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
                    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill py-2 px-3 text-uppercase small fw-semibold">
                        {{ $news->category ?? 'Technology' }}
                    </span>
                    <span class="text-secondary small d-flex align-items-center gap-2">
                        <i class="ti ti-calendar-event"></i>
                        {{ $news->created_at->format('F d, Y') }}
                    </span>
                    <span class="text-secondary small d-flex align-items-center gap-2">
                        <i class="ti ti-clock"></i>
                        {{ $readTime }} min read
                    </span>
                    <span class="text-secondary small d-flex align-items-center gap-2">
                        <i class="ti ti-eye"></i>
                        {{ number_format($news->views) }} views
                    </span>
                </div>

                <h1 class="display-4 fw-bold owl-article-title mb-4">
                    {{ $news->title }}
                </h1>

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 pb-4 mb-4 border-bottom">
                    <div class="d-flex align-items-center gap-3">
                        <span class="avatar avatar-xl bg-primary bg-opacity-10 text-primary fw-bold rounded-circle">
                            {{ strtoupper(substr($news->user->name ?? 'U', 0, 1)) }}
                        </span>
                        <div>
                            <div class="fw-semibold text-dark">{{ $news->user->name ?? 'Unknown' }}</div>
                            <div class="text-secondary small">{{ $news->user->email ?? 'No email provided' }}</div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-outline-primary rounded-pill btn-sm" data-bs-toggle="tooltip" title="Save article">
                            <i class="ti ti-bookmark me-1"></i> Save
                        </button>
                        <button class="btn btn-primary rounded-pill btn-sm" data-bs-toggle="tooltip" title="Share article">
                            <i class="ti ti-share-2 me-1"></i> Share
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 d-none d-lg-flex justify-content-end">
                <div class="card border-0 owl-article-overview-card rounded-4 shadow-sm p-4 position-sticky top-5">
                    <div class="mb-4">
                        <h5 class="fw-bold mb-2">Article insights</h5>
                        <p class="small text-secondary mb-0">A premium deep dive built for modern learning leaders.</p>
                    </div>
                    <div class="list-unstyled owl-article-overview-list">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-secondary">Estimated read time</span>
                            <span class="fw-semibold">{{ $readTime }} min</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-secondary">Views</span>
                            <span class="fw-semibold">{{ number_format($news->views) }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-secondary">Author</span>
                            <span class="fw-semibold">{{ $news->user->name ?? 'Unknown' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row gx-5 gy-5">
            <div class="col-xl-8">

                <div class="owl-article-hero-wrapper rounded-4 overflow-hidden mb-5">
                    <img src="{{ asset('default-video.jpg') }}"
                         class="w-100 h-100 object-cover"
                         alt="{{ $news->title }}"
                         loading="lazy">
                </div>

                <article class="owl-article-content mb-5">
                    <div class="article-body text-secondary">
                        {!! nl2br(e($news->description)) !!}
                    </div>
                </article>

                <div class="row g-3 mb-5">
                    <div class="col-sm-4">
                        <div class="owl-stat-card p-4 rounded-4 h-100">
                            <div class="text-secondary small mb-2">Engagement</div>
                            <div class="fw-bold display-6 mb-2">{{ number_format($news->views) }}</div>
                            <div class="small text-secondary">view count from enterprise learners</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="owl-stat-card p-4 rounded-4 h-100">
                            <div class="text-secondary small mb-2">Read time</div>
                            <div class="fw-bold display-6 mb-2">{{ $readTime }} min</div>
                            <div class="small text-secondary">designed for focused reading</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="owl-stat-card p-4 rounded-4 h-100">
                            <div class="text-secondary small mb-2">Type</div>
                            <div class="fw-bold display-6 mb-2">Insight</div>
                            <div class="small text-secondary">AI-driven learning update</div>
                        </div>
                    </div>
                </div>

                <div class="owl-quote-block rounded-4 p-4 mb-5">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <span class="avatar bg-white bg-opacity-10 text-white rounded-circle p-3">
                            <i class="ti ti-quotes fs-4"></i>
                        </span>
                        <div>
                            <h5 class="fw-bold mb-2 text-white">Insight highlight</h5>
                            <p class="mb-0 text-white-75 fs-6">"{{ Str::words(strip_tags($news->description), 32) }}..."</p>
                        </div>
                    </div>
                </div>

                <div class="mb-5">
                    <div class="card border-0 owl-discussion-card rounded-4 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                                <div>
                                    <div class="small text-primary text-uppercase fw-semibold mb-1">Diskusi Kursus</div>
                                    <h5 class="fw-bold mb-1">Bagikan pertanyaan atau insight Anda.</h5>
                                    <p class="text-secondary mb-0">Komentar Anda membantu membuat diskusi lebih hidup.</p>
                                </div>
                                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill py-2 px-3">{{ $comments->count() }} Comments</span>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 owl-comment-form-card bg-white shadow-sm rounded-4 p-4 mb-4">
                        <form method="POST" action="{{ route('news.comment.store', $news->slug) }}" class="owl-comment-form">
                            @csrf
                            <div class="d-flex gap-3 align-items-start">
                                <span class="avatar avatar-md bg-primary bg-opacity-10 text-primary fw-bold flex-shrink-0">W</span>
                                <div class="flex-grow-1">
                                    <textarea class="form-control rounded-4" name="comment" rows="4" placeholder="Tulis komentar..." required></textarea>
                                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mt-3">
                                        <small class="text-secondary">1000 characters remaining</small>
                                        <button type="submit" class="btn btn-primary rounded-pill px-4">
                                            <i class="ti ti-send me-2"></i> Submit
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    @if($comments->count() > 0)
                        <div class="owl-comments-list">
                            @foreach($comments as $comment)
                                <div class="card border-0 owl-comment-card mb-3 shadow-sm">
                                    <div class="card-body p-4">
                                        <div class="d-flex gap-3 align-items-start">
                                            <span class="avatar avatar-md bg-primary bg-opacity-10 text-primary fw-bold flex-shrink-0">
                                                {{ strtoupper(substr($comment->user->name ?? 'U', 0, 1)) }}
                                            </span>
                                            <div class="flex-grow-1">
                                                <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-2">
                                                    <div>
                                                        <div class="fw-semibold">{{ $comment->user->name ?? 'Unknown' }}</div>
                                                        <div class="small text-secondary">{{ $comment->created_at->diffForHumans() }}</div>
                                                    </div>
                                                </div>
                                                <p class="mb-3 text-secondary">{{ $comment->comment }}</p>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill">Like</button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill">Reply</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="card border-0 owl-comment-empty-card bg-white shadow-sm p-4 rounded-4">
                            <p class="mb-0 text-secondary">Belum ada komentar. Jadilah yang pertama berbagi insight.</p>
                        </div>
                    @endif
                </div>

            </div>

            <div class="col-xl-4">
                <div class="position-sticky top-5">
                    <div class="card border-0 owl-takeaways-card rounded-4 shadow-sm mb-4 p-4">
                        <div class="mb-4 d-flex align-items-center gap-2">
                            <i class="ti ti-bulb text-warning fs-4"></i>
                            <h5 class="fw-bold mb-0">AI key takeaways</h5>
                        </div>
                        <ul class="list-unstyled mb-0">
                            <li class="d-flex gap-2 align-items-start mb-3">
                                <span class="text-success mt-1"><i class="ti ti-check"></i></span>
                                <span class="text-secondary small">AI personalization unlocks higher learner retention.</span>
                            </li>
                            <li class="d-flex gap-2 align-items-start mb-3">
                                <span class="text-success mt-1"><i class="ti ti-check"></i></span>
                                <span class="text-secondary small">Micro-modules help teams stay nimble and productive.</span>
                            </li>
                            <li class="d-flex gap-2 align-items-start">
                                <span class="text-success mt-1"><i class="ti ti-check"></i></span>
                                <span class="text-secondary small">Data-driven feedback is essential for enterprise upskilling.</span>
                            </li>
                        </ul>
                    </div>

                    <div class="card border-0 owl-share-card rounded-4 shadow-sm mb-4 p-4">
                        <h5 class="fw-bold mb-4">Share article</h5>
                        <div class="d-flex gap-2 flex-wrap">
                            <button class="btn btn-outline-secondary btn-icon rounded-circle" data-bs-toggle="tooltip" title="Share on LinkedIn"><i class="ti ti-brand-linkedin"></i></button>
                            <button class="btn btn-outline-secondary btn-icon rounded-circle" data-bs-toggle="tooltip" title="Share on Twitter"><i class="ti ti-brand-twitter"></i></button>
                            <button class="btn btn-outline-secondary btn-icon rounded-circle" data-bs-toggle="tooltip" title="Copy link"><i class="ti ti-link"></i></button>
                            <button class="btn btn-outline-secondary btn-icon rounded-circle" data-bs-toggle="tooltip" title="Email"><i class="ti ti-mail"></i></button>
                        </div>
                    </div>

                    <div class="card border-0 owl-author-card rounded-4 shadow-sm p-4 text-center">
                        <div class="avatar avatar-xl bg-primary bg-opacity-10 text-primary fw-bold mx-auto mb-3">{{ strtoupper(substr($news->user->name ?? 'U', 0, 1)) }}</div>
                        <h5 class="fw-bold mb-1">{{ $news->user->name ?? 'Unknown' }}</h5>
                        <p class="text-secondary small mb-3">Enterprise learning writer and AI curriculum strategist.</p>
                        <button class="btn btn-outline-primary rounded-pill w-100 btn-sm">Follow author</button>
                    </div>
                </div>
            </div>
        </div>

        @if($related->count() > 0)
            <div class="row mt-6 pt-5 border-top">
                <div class="col-12 mb-4">
                    <h2 class="fw-bold mb-4"><i class="ti ti-arrow-right me-2"></i>Continue exploring</h2>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                        @foreach($related as $article)
                            <div class="col">
                                <a href="{{ route('news.user.view', $article->slug) }}" class="card border-0 h-100 owl-explore-card shadow-sm text-decoration-none text-dark overflow-hidden">
                                    <div class="owl-explore-image-wrapper overflow-hidden">
                                        <img src="{{ asset('default-video.jpg') }}" class="owl-explore-image" alt="{{ $article->title }}" loading="lazy">
                                    </div>
                                    <div class="card-body">
                                        <small class="text-secondary">{{ $article->created_at->format('M d, Y') }}</small>
                                        <h5 class="fw-bold mt-2 mb-3">{{ Str::limit($article->title, 65) }}</h5>
                                        <span class="btn btn-sm btn-outline-primary rounded-pill">Read more</span>
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

@endsection

@section('style')
<link rel="stylesheet" href="{{ asset('css/owl-news.css') }}">
@endsection

@section('script')

<!-- Dark Mode Toggle Button -->
<button class="dark-mode-toggle" id="darkModeToggle" aria-label="Toggle dark mode" title="Toggle dark mode">
    <i class="ti ti-moon-stars"></i>
</button>

<script>
(function(){
    // DARK MODE INITIALIZATION
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

    // BOOTSTRAP TOOLTIPS
    function initTooltips() {
        if (window.bootstrap) {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    }

    // SHARE FUNCTIONALITY
    function initShareButtons() {
        var currentUrl = window.location.href;
        var articleTitle = '{{ $news->title }}';

        document.addEventListener('click', function(e) {
            if (e.target.closest('[data-bs-toggle="tooltip"][title*="LinkedIn"]')) {
                window.open('https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(currentUrl), '_blank', 'width=600,height=400');
            } else if (e.target.closest('[data-bs-toggle="tooltip"][title*="Twitter"]')) {
                window.open('https://twitter.com/intent/tweet?text=' + encodeURIComponent(articleTitle) + '&url=' + encodeURIComponent(currentUrl), '_blank', 'width=600,height=400');
            } else if (e.target.closest('[data-bs-toggle="tooltip"][title*="Copy"]')) {
                navigator.clipboard.writeText(currentUrl).then(function() {
                    alert('Link copied to clipboard!');
                });
            } else if (e.target.closest('[data-bs-toggle="tooltip"][title*="Email"]')) {
                window.location.href = 'mailto:?subject=' + encodeURIComponent(articleTitle) + '&body=' + encodeURIComponent(currentUrl);
            }
        });
    }

    // SAVE ARTICLE FUNCTIONALITY
    function initSaveButtons() {
        var newsId = '{{ $news->id }}';
        var saveButtons = document.querySelectorAll('[title="Save article"], [title="Save for later"]');

        saveButtons.forEach(function(btn) {
            // Check if already saved
            if(localStorage.getItem('owl-saved-article-' + newsId)) {
                btn.classList.add('active');
                btn.setAttribute('aria-pressed', 'true');
            }

            btn.addEventListener('click', function(e) {
                e.preventDefault();
                btn.classList.toggle('active');
                var isSaved = btn.classList.contains('active');
                btn.setAttribute('aria-pressed', isSaved);

                if(isSaved) {
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
