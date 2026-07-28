@extends('layouts.sidebar-master')

@section('content')

<div class="page-body owl-news-page">

    <div class="container-xl">

        {{-- HERO SECTION --}}
        @include('user.news.partials.hero')

        {{-- PAGE HEADER --}}
        <div class="mb-3 pb-2 border-bottom" style="border-color: var(--owl-border-color);">
            <h1 class="fw-bold mb-1">OWL Learning Hub</h1>
            <p class="text-secondary mb-0" style="font-size:1rem;">Explore enterprise insights, industry trends, and AI-powered learning strategies curated for modern organizations.</p>
        </div>

        {{-- SEARCH & FILTER TOOLBAR --}}
        <div class="owl-toolbar-wrapper mb-2 sticky-top">
            <div class="card border-0 owl-toolbar-card">
                <div class="card-body py-3 px-4">
                    <form method="GET" class="row g-3 align-items-end">

                        {{-- SEARCH BAR --}}
                        <div class="col-lg-7">
                            <div class="search-wrapper position-relative">
                                <i class="ti ti-search search-icon" aria-hidden="true"></i>
                                <input type="text"
                                       id="owl-search-input"
                                       name="search"
                                       class="form-control owl-search-input"
                                       placeholder="Search insights, articles, or trends..."
                                       value="{{ request('search') }}"
                                       aria-label="Search articles"
                                       autocomplete="off">
                            </div>
                        </div>

                        {{-- SORT DROPDOWN --}}
                        <div class="col-md-4 col-lg-3">
                            <select name="sort"
                                    class="form-select owl-select"
                                    onchange="this.form.submit()"
                                    aria-label="Sort articles">
                                <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Latest update</option>
                                <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oldest update</option>
                                <option value="most_viewed" {{ request('sort') == 'most_viewed' ? 'selected' : '' }}>Most viewed</option>
                                <option value="least_viewed" {{ request('sort') == 'least_viewed' ? 'selected' : '' }}>Least viewed</option>
                            </select>
                        </div>

                        {{-- AUTHOR FILTER --}}
                        <div class="col-md-4 col-lg-2">
                            <select name="author"
                                    class="form-select owl-select"
                                    onchange="this.form.submit()"
                                    aria-label="Filter by author">
                                <option value="">All authors</option>
                                @foreach($authors as $a)
                                    <option value="{{ $a->id }}" {{ request('author') == $a->id ? 'selected' : '' }}>
                                        {{ $a->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        {{-- CONTENT --}}
        {{-- news-layout: flex-start so sidebar doesn't stretch full height --}}
        <div class="row gx-4 gy-4 news-layout">

            {{-- NEWS GRID --}}
            <div class="col-xl-9 col-lg-8 news-main">

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                    <div>
                        <div class="text-uppercase text-primary small fw-semibold mb-2">AI News Hub</div>
                        <h2 class="fw-bold mb-0">
                            {{ request('search') ? 'Search Results' : 'Hub Highlights' }}
                        </h2>
                    </div>
                    @if(request('search') || request('sort') != 'latest' || request('author'))
                        <a href="{{ route('news.user.index') }}" class="btn btn-sm btn-outline-primary rounded-pill px-4">
                            Reset filters
                        </a>
                    @endif
                </div>

                @if($news->count() > 0)

                    {{-- 3 cols on xl, 2 cols on md–lg, 1 on mobile --}}
                    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                        @foreach($news as $item)
                            @include('user.news.partials.card')
                        @endforeach
                    </div>

                    {{-- PAGINATION --}}
                    <nav class="mt-5">
                        <div class="d-flex justify-content-center">
                            {{ $news->links('pagination::owl') }}
                        </div>
                    </nav>

                @else

                    @include('user.news.partials.empty-state')

                @endif

            </div>

            {{-- SIDEBAR --}}
            {{-- Outer sticky wrapper removed — sidebar.blade.php owns its own .owl-sidebar-sticky --}}
            <div class="col-xl-3 col-lg-4 news-sidebar">
                @include('user.news.partials.sidebar', ['trending' => $trending])
            </div>

        </div>

    </div>

</div>

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

    // SEARCH & DEBOUNCE
    function debounce(fn, ms){
        let t;
        return function(...a){
            clearTimeout(t);
            t = setTimeout(()=> fn.apply(this,a), ms);
        };
    }

    document.addEventListener('DOMContentLoaded', function(){
        // Initialize dark mode
        initDarkMode();

        // Search functionality
        var searchInput = document.getElementById('owl-search-input');
        if(searchInput){
            var form = searchInput.closest('form');
            var submit = debounce(function(){ if(form) form.submit(); }, 600);
            searchInput.addEventListener('input', submit);
            document.addEventListener('keydown', function(e){
                if(e.key === '/' && document.activeElement.tagName.toLowerCase() !== 'input'){
                    e.preventDefault();
                    searchInput.focus();
                    searchInput.select();
                }
            });
        }

        // Tooltips
        if(window.bootstrap){
            var t = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            t.map(function(el){ return new bootstrap.Tooltip(el); });
        }

        // Save button state
        document.querySelectorAll('.owl-save-btn').forEach(function(btn){
            // Check if saved in localStorage
            var newsId = btn.closest('.owl-news-card')?.getAttribute('data-news-id');
            if(newsId && localStorage.getItem('owl-saved-' + newsId)) {
                btn.setAttribute('aria-pressed', 'true');
            } else {
                btn.setAttribute('aria-pressed','false');
            }

            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var isPressed = btn.getAttribute('aria-pressed') === 'true';
                btn.setAttribute('aria-pressed', !isPressed);

                if(newsId) {
                    if(!isPressed) {
                        localStorage.setItem('owl-saved-' + newsId, 'true');
                    } else {
                        localStorage.removeItem('owl-saved-' + newsId);
                    }
                }
            });
        });
    });
})();
</script>

@endsection

@section('style')
<link rel="stylesheet" href="{{ asset('css/owl-news.css') }}">
@endsection
