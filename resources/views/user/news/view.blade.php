@extends('layouts.sidebar-master')

@section('content')

<div class="page-body owl-article-page">
    <div class="container-fluid px-4 px-xl-5">

        <div class="row mb-5">
            <div class="col-12">
                <nav class="owl-breadcrumb mb-4" style="--bs-breadcrumb-divider:'/'">
                    <ol class="breadcrumb bg-transparent px-0 mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('news.user.index') }}" class="text-secondary">News Hub</a></li>
                        <li class="breadcrumb-item active text-dark fw-semibold">{{ Str::limit($news->title, 45) }}</li>
                    </ol>
                </nav>

                <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
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

                <h1 class="fw-bold owl-article-title mb-4">
                    {{ $news->title }}
                </h1>

              <div class="d-flex align-items-center gap-3 pb-4 mb-4 border-bottom">

                <div class="rounded-circle bg-primary text-white
                            d-flex align-items-center justify-content-center
                            fw-bold"
                    style="width:70px;height:70px;font-size:28px">

                    {{ strtoupper(substr($news->user->name,0,1)) }}

                </div>

                <div>
                    <h5 class="mb-1 fw-semibold">
                        {{ $news->user->name }}
                    </h5>

                    <div class="text-secondary">
                        {{ $news->user->email }}
                    </div>
                </div>
            </div>
               <div class="card border-0 shadow-sm rounded-4 mb-4">

    <div class="card-body">

        <h5 class="fw-semibold mb-4">
            Article Insights
        </h5>

        <div class="row text-center gy-3">

            <div class="col-md-4">
                <div class="text-secondary small">
                    Read Time
                </div>

                <div class="fw-bold fs-5">
                    {{ $readTime }} min
                </div>
            </div>

            <div class="col-md-4">
                <div class="text-secondary small">
                    Views
                </div>

                <div class="fw-bold fs-5">
                    {{ number_format($news->views) }}
                </div>
            </div>

            <div class="col-md-4">
                <div class="text-secondary small">
                    Author
                </div>

                <div class="fw-bold">
                    {{ $news->user->name }}
                </div>
            </div>

        </div>

    </div>

</div>
            <div class="row">
                <div class="col-12">

                <div class="owl-article-hero-wrapper rounded-4 overflow-hidden mb-5">
                    <img src="{{ $news->image ? asset('storage/news/'.$news->image): 'https://picsum.photos/1200/700?random='.$news->id }}"
                         class="w-100 h-100 object-cover"
                         alt="{{ $news->title }}"
                         loading="lazy">
                </div>

                <article class="owl-article-content mb-5">
                    <div class="article-body text-secondary">
                        {!! nl2br(e($news->description)) !!}
                    </div>
                </article>


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
                    <form method="POST"
                        action="{{ route('news.comment.store', $news->slug) }}"
                        class="owl-comment-form">
                        @csrf

                        <div class="d-flex gap-3 align-items-start">

                            {{-- Avatar User Login --}}
                            <span class="avatar avatar-md bg-primary text-white fw-bold flex-shrink-0 rounded-circle">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U',0,1)) }}
                            </span>

                            <div class="flex-grow-1">

                                <textarea
                                    class="form-control rounded-4"
                                    name="description"
                                    rows="4"
                                    placeholder="Tulis komentar..."
                                    required></textarea>

                                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mt-3">

                                    <small class="text-secondary">
                                        Bagikan insight atau pertanyaan Anda
                                    </small>

                                    <button type="submit"
                                            class="btn btn-primary rounded-pill px-4">
                                        <i class="ti ti-send me-2"></i>
                                        Submit
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

                                {{-- Avatar Komentar --}}
                                <span class="avatar avatar-sm bg-primary text-white fw-bold rounded-circle">
                                    {{ strtoupper(substr($comment->user->name ?? 'U',0,1)) }}
                                </span>

                                <div class="flex-grow-1">

                                    <div class="d-flex justify-content-between align-items-start mb-2">

                                        <div>
                                            <div class="fw-semibold">
                                                {{ $comment->user->name ?? 'Unknown' }}
                                            </div>

                                            <div class="small text-secondary">
                                                {{ $comment->created_at->diffForHumans() }}
                                            </div>
                                        </div>

                                    </div>

                                    {{-- Isi Komentar --}}
                                    <p class="mb-3 text-secondary">
                                        {{ $comment->description }}
                                    </p>

                                    {{-- Like + Reply --}}
                                    <div class="d-flex flex-wrap gap-2">

                                        <form action="{{ route('comment.like',$comment->id) }}"
                                            method="POST">
                                            @csrf

                                            <button type="submit"
                                                    class="btn btn-sm rounded-pill {{ $comment->is_liked ? 'btn-primary' : 'btn-outline-secondary' }}">

                                                👍 {{ $comment->likes_count }}

                                            </button>
                                        </form>

                                        <button
                                            class="btn btn-sm btn-outline-secondary rounded-pill"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#reply{{ $comment->id }}">

                                            Reply

                                        </button>

                                    </div>

                                    {{-- Form Reply --}}
                                    <div class="collapse mt-3"
                                        id="reply{{ $comment->id }}">

                                        <form method="POST"
                                            action="{{ route('news.comment.store', $news->slug) }}">

                                            @csrf

                                            <input type="hidden"
                                                name="parent_id"
                                                value="{{ $comment->id }}">

                                            <textarea
                                                class="form-control mb-2"
                                                rows="2"
                                                name="description"
                                                placeholder="Write a reply..."
                                                required></textarea>

                                            <button
                                                type="submit"
                                                class="btn btn-primary btn-sm">

                                                Submit Reply

                                            </button>

                                        </form>

                                    </div>

                                    {{-- Replies --}}
                                    @if($comment->replies->count())

                                        <div class="mt-4 ms-4 border-start ps-3">

                                            @foreach($comment->replies as $reply)

                                            <div class="mb-3">

                                                <div class="d-flex gap-2">

                                                    <span class="avatar avatar-sm bg-secondary text-white rounded-circle">
                                                        {{ strtoupper(substr($reply->user->name ?? 'U',0,1)) }}
                                                    </span>

                                                    <div>

                                                        <div class="fw-semibold">
                                                            {{ $reply->user->name }}
                                                        </div>

                                                        <div class="small text-muted">
                                                            {{ $reply->created_at->diffForHumans() }}
                                                        </div>

                                                        <div class="mt-1">
                                                            {{ $reply->description }}
                                                        </div>

                                                    </div>

                                                </div>

                                            </div>

                                            @endforeach

                                        </div>

                                    @endif

                                </div>

                            </div>

                        </div>
                    </div>

                    @endforeach

                </div>

                @else

                <div class="card border-0 owl-comment-empty-card bg-white shadow-sm p-4 rounded-4">
                    <p class="mb-0 text-secondary">
                        Belum ada komentar. Jadilah yang pertama berbagi insight.
                    </p>
                </div>

                @endif


                </div>

            </div>
        </div>

        @if($related->count() > 0)
            <div class="row mt-6 pt-5 border-top">
                <div class="col-12 mb-4">
                    <h2 class="fw-bold mb-4"><i class="ti ti-arrow-right me-2"></i>Continue exploring</h2>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                        @foreach($related as $article)
                            <div class="col">
                                <a href="{{ route('news.user.view', $article->slug) }}" class="card border-0 h-100 owl-explore-card shadow-sm text-decoration-none text-dark overflow-hidden">
                                    <div class="owl-explore-image-wrapper overflow-hidden">
                                        <img src="https://picsum.photos/500/300?random={{ $article->id }}" class="owl-explore-image" alt="{{ $article->title }}" loading="lazy">
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
