@extends('layouts.sidebar-master')

@php
    $newsMonths = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];
    $newsViews = [
        'all' => 'All Views',
        'most_viewed' => 'Most Viewed',
        'least_viewed' => 'Least Viewed',
    ];
    $newsYears = collect($news->items())
        ->map(fn ($item) => (int) $item->created_at->format('Y'))
        ->when(request('year', 'all') !== 'all', fn ($years) => $years->push((int) request('year')))
        ->unique()
        ->sortDesc()
        ->values();
    $currentNewsSort = request('sort', 'latest');
    $currentNewsViews = request(
        'views',
        in_array($currentNewsSort, ['most_viewed', 'least_viewed'], true) ? $currentNewsSort : 'all'
    );
    $newsSortDate = request('sort_date', $currentNewsSort === 'oldest' ? 'oldest' : 'all');
    $newsSortTitle = request('sort_title', in_array($currentNewsSort, ['az', 'za'], true) ? $currentNewsSort : 'default');
    $newsAdvancedFilterCount = collect([
        $newsSortDate !== 'all',
        $newsSortTitle !== 'default',
        filled(request('author')),
        request('published', 'all') !== 'all',
    ])->filter()->count();
    $newsHasActiveFilters = filled(request('search')) || filled(request('author')) ||
        request('month', 'all') !== 'all' || request('year', 'all') !== 'all' ||
        $currentNewsViews !== 'all' ||
        $newsAdvancedFilterCount > 0;
@endphp

@section('content')

<div class="container-xl py-4 owl-news-page">

        {{-- HERO SECTION --}}
        @include('user.news.partials.hero')

        {{-- SEARCH & FILTER TOOLBAR --}}
        <div class="owl-toolbar-wrapper mb-4">
            <div class="card border-0 owl-toolbar-card">
                <div class="card-body p-3">
                    <form method="GET" id="owlNewsFilterForm" class="record-filter-shell owl-news-filter-form">
                        <div class="record-fast-filter-section">
                            <label class="form-label small text-muted mb-2 learning-fast-filter-label" for="owl-search-input">
                                Quick Filter
                            </label>

                            <div class="fast-filter-toolbar owl-news-filter-toolbar">
                            <div class="record-toolbar-search owl-news-filter-search">
                                <label class="visually-hidden" for="news-search-input">Search news</label>
                                <div class="position-relative news-search-container flex-grow-1 search-input-wrapper">
                                    <div class="input-icon">
                                        <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                                        <input type="search" id="news-search-input" name="search"
                                               class="form-control module-search-input"
                                               placeholder="Search news title, description or author..."
                                               value="{{ request('q', request('search')) }}"
                                               aria-label="Search news"
                                               autocomplete="off">
                                    </div>
                                    <div id="news-search-results" class="dropdown-menu shadow-sm w-100 position-absolute mt-1 module-search-dropdown" style="display: none; max-height: 380px; overflow-y: auto; z-index: 1050; padding: 0;">
                                        <div class="autocomplete-list-group list-group list-group-flush"></div>
                                        <div class="search-footer border-top bg-light p-2 text-center sticky-bottom"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="learning-quick-dropdowns learning-fast-filter owl-news-quick-dropdowns">
                                <div class="dropdown">
                                    <button type="button"
                                            class="btn btn-filter-chip learning-fast-filter-control dropdown-toggle {{ request('month', 'all') !== 'all' ? 'active' : '' }}"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-calendar me-1"></i>Month: <span class="fw-semibold" data-news-filter-label="month">{{ request('month', 'all') === 'all' ? 'All' : ($newsMonths[(int) request('month')] ?? 'All') }}</span>
                                    </button>
                                    <div class="dropdown-menu learning-quick-dropdown-scroll">
                                        <a href="#" class="dropdown-item news-quick-filter-item {{ request('month', 'all') === 'all' ? 'active' : '' }}" data-filter="month" data-value="all">All</a>
                                        @foreach ($newsMonths as $monthNumber => $monthName)
                                            <a href="#" class="dropdown-item news-quick-filter-item {{ (string) request('month', 'all') === (string) $monthNumber ? 'active' : '' }}" data-filter="month" data-value="{{ $monthNumber }}">{{ $monthName }}</a>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="dropdown">
                                    <button type="button"
                                        class="btn btn-filter-chip learning-fast-filter-control dropdown-toggle {{ request('year', 'all') !== 'all' ? 'active' : '' }}"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-calendar-event me-1"></i>Year: <span class="fw-semibold" data-news-filter-label="year">{{ request('year', 'all') === 'all' ? 'All' : request('year') }}</span>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a href="#" class="dropdown-item news-quick-filter-item {{ request('year', 'all') === 'all' ? 'active' : '' }}" data-filter="year" data-value="all">All</a>
                                        @foreach ($newsYears as $year)
                                            <a href="#" class="dropdown-item news-quick-filter-item {{ (string) request('year', 'all') === (string) $year ? 'active' : '' }}" data-filter="year" data-value="{{ $year }}">{{ $year }}</a>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="dropdown">
                                    <button type="button"
                                        class="btn btn-filter-chip learning-fast-filter-control dropdown-toggle {{ $currentNewsViews !== 'all' ? 'active' : '' }}"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        Views: <span class="fw-semibold" data-news-filter-label="views">{{ $newsViews[$currentNewsViews] ?? 'All Views' }}</span>
                                    </button>
                                    <div class="dropdown-menu">
                                        @foreach($newsViews as $viewValue => $viewLabel)
                                            <a href="#" class="dropdown-item news-quick-filter-item {{ $currentNewsViews === $viewValue ? 'active' : '' }}" data-filter="views" data-value="{{ $viewValue }}">{{ $viewLabel }}</a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="month" id="owlNewsMonth" value="{{ request('month', 'all') }}">
                            <input type="hidden" name="year" id="owlNewsYear" value="{{ request('year', 'all') }}">
                            <input type="hidden" name="views" id="owlNewsViews" value="{{ $currentNewsViews }}">
                            <input type="hidden" name="sort" id="owlNewsSort" value="{{ $currentNewsSort }}">

                            <div class="record-filter-actions owl-news-filter-actions">
                                <button type="submit" class="btn btn-primary btn-learning-primary record-filter-action-btn owl-news-filter-action-btn"
                                        title="Search" aria-label="Search">
                                    <i class="ti ti-search"></i>
                                </button>

                                <button type="button" class="btn btn-outline-primary btn-learning-secondary record-filter-action-btn owl-news-filter-action-btn position-relative"
                                        data-toggle="modal" data-target="#owlNewsMoreFiltersModal"
                                        title="More Filters" aria-label="More Filters">
                                    <i class="ti ti-adjustments-horizontal"></i>
                                    <span class="badge bg-primary text-white owl-news-filter-count {{ $newsAdvancedFilterCount > 0 ? '' : 'd-none' }}"
                                          id="newsMoreFiltersCount">{{ $newsAdvancedFilterCount ?: '' }}</span>
                                </button>

                                {{-- <button type="submit" class="btn btn-primary btn-learning-primary record-filter-action-btn owl-news-filter-action-btn"
                                        title="Apply Filter" aria-label="Apply Filter">
                                    <i class="ti ti-check"></i>
                                </button> --}}

                                <a href="{{ route('news.user.index') }}"
                                   class="btn btn-outline-primary btn-learning-outline record-filter-action-btn owl-news-filter-action-btn {{ $newsHasActiveFilters ? 'is-active' : '' }}"
                                   title="Reset Filter" aria-label="Reset Filter">
                                    <i class="ti ti-refresh"></i>
                                </a>
                            </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="modal fade" id="owlNewsMoreFiltersModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered learning-filter-modal">
                    <div class="modal-content rounded-4 border-0 shadow">
                        <div class="modal-header border-0 pb-0">
                            <h3 class="modal-title fw-bold">
                                <i class="ti ti-adjustments-horizontal me-1 text-primary"></i>
                                More Filters
                            </h3>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body pt-2">
                            {{-- 1. Author (Full Width) --}}
                            <div class="learning-filter-group mb-3">
                                <label class="form-label fw-semibold" for="owlNewsAuthor">Author</label>
                                <select name="author" id="owlNewsAuthor" class="form-select rounded-3" form="owlNewsFilterForm">
                                    <option value="">All Authors</option>
                                    @foreach($authors as $author)
                                        <option value="{{ $author->id }}" {{ (string) request('author') === (string) $author->id ? 'selected' : '' }}>{{ $author->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <hr class="my-3">

                            {{-- 2. Category / Topic & Sort by (2 Kolom) --}}
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold" for="owlNewsCategory">Category / Topic</label>
                                    <select name="category" id="owlNewsCategory" class="form-select rounded-3" form="owlNewsFilterForm">
                                        <option value="all" {{ request('category', 'all') === 'all' ? 'selected' : '' }}>All Categories</option>
                                        <option value="Technology" {{ request('category') === 'Technology' ? 'selected' : '' }}>Technology</option>
                                        <option value="Leadership" {{ request('category') === 'Leadership' ? 'selected' : '' }}>Leadership</option>
                                        <option value="Announcement" {{ request('category') === 'Announcement' ? 'selected' : '' }}>Announcement</option>
                                        <option value="General" {{ request('category') === 'General' ? 'selected' : '' }}>General</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold" for="owlNewsSortSelect">Sort by</label>
                                    <select name="sort" id="owlNewsSortSelect" class="form-select rounded-3" form="owlNewsFilterForm" onchange="document.getElementById('owlNewsSort').value = this.value">
                                        <option value="latest" {{ request('sort', 'latest') === 'latest' ? 'selected' : '' }}>Newest</option>
                                        <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest</option>
                                        <option value="most_viewed" {{ request('sort') === 'most_viewed' || request('views') === 'most_viewed' ? 'selected' : '' }}>Most Viewed</option>
                                        <option value="least_viewed" {{ request('sort') === 'least_viewed' || request('views') === 'least_viewed' ? 'selected' : '' }}>Least Viewed</option>
                                        <option value="az" {{ request('sort') === 'az' || request('sort_title') === 'az' ? 'selected' : '' }}>Title (A - Z)</option>
                                        <option value="za" {{ request('sort') === 'za' || request('sort_title') === 'za' ? 'selected' : '' }}>Title (Z - A)</option>
                                    </select>
                                </div>
                            </div>

                            <hr class="my-3">

                            {{-- 3. Date Range (2 Kolom) --}}
                            <div class="learning-filter-group">
                                <label class="form-label fw-semibold mb-2"><i class="ti ti-calendar me-1"></i> Period</label>
                                <input type="hidden" name="published" id="owlNewsPublished" value="{{ request('published', 'all') }}" form="owlNewsFilterForm">
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small text-muted mb-1" for="owlNewsDateFrom"><i class="ti ti-calendar-event me-1"></i>From Date</label>
                                        <input type="{{ request('date_from') ? 'date' : 'text' }}"
                                               name="date_from" id="owlNewsDateFrom"
                                               class="form-control rounded-3"
                                               value="{{ request('date_from') }}"
                                               placeholder="dd/mm/yyyy"
                                               onfocus="(this.type='date')"
                                               onblur="if(!this.value)this.type='text'"
                                               form="owlNewsFilterForm">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small text-muted mb-1" for="owlNewsDateTo"><i class="ti ti-calendar-event me-1"></i>To Date</label>
                                        <input type="{{ request('date_to') ? 'date' : 'text' }}"
                                               name="date_to" id="owlNewsDateTo"
                                               class="form-control rounded-3"
                                               value="{{ request('date_to') }}"
                                               placeholder="dd/mm/yyyy"
                                               onfocus="(this.type='date')"
                                               onblur="if(!this.value)this.type='text'"
                                               form="owlNewsFilterForm">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <a href="{{ route('news.user.index') }}" class="btn btn-outline-secondary rounded-3 me-2">
                                <i class="ti ti-refresh me-1"></i> Reset
                            </a>
                            <button type="submit" class="btn btn-primary rounded-3" form="owlNewsFilterForm">
                                <i class="ti ti-check me-1"></i> Apply Filter
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- CONTENT --}}
        {{-- news-layout: flex-start so sidebar doesn't stretch full height --}}
        <div class="row gx-4 gy-4 news-layout">

            {{-- NEWS GRID --}}
            <div class="col-xl-9 col-lg-8 news-main">



                @if($news->count() > 0)

                    {{-- 3 cols on xl, 2 cols on md–lg, 1 on mobile --}}
                    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4 owl-news-grid" id="owlNewsGrid">
                        @foreach($news as $item)
                            @include('user.news.partials.card')
                        @endforeach
                    </div>

                    <div class="d-none mt-4" id="owlNewsClientEmpty">
                        @include('user.news.partials.empty-state')
                    </div>

                    {{-- PAGINATION --}}
                    @if($news->hasPages() || $news->total() > 0)
                        <div class="d-flex flex-column flex-sm-row align-items-center justify-content-center gap-3 mt-5 pt-3 border-top owl-border-color text-muted small w-100 text-center">
                            <div>
                                Showing <b>{{ $news->firstItem() ?? 0 }}</b> to <b>{{ $news->lastItem() ?? 0 }}</b> of <b>{{ $news->total() }}</b> results
                            </div>
                            <ul class="pagination pagination-sm m-0 align-items-center gap-1">
                                {{-- Previous Link --}}
                                <li class="page-item {{ $news->onFirstPage() ? 'disabled' : '' }}">
                                    <a class="page-link rounded-3 border-0 bg-transparent text-secondary" href="{{ $news->previousPageUrl() }}">&lsaquo;</a>
                                </li>
                                {{-- Pagination Elements --}}
                                @foreach ($news->getUrlRange(1, $news->lastPage()) as $page => $url)
                                    <li class="page-item">
                                        <a class="page-link rounded-3 px-3 py-1.5 {{ $page == $news->currentPage() ? 'bg-primary text-white fw-bold' : 'border-0 bg-transparent text-secondary' }}" href="{{ $url }}">{{ $page }}</a>
                                    </li>
                                @endforeach
                                {{-- Next Link --}}
                                <li class="page-item {{ !$news->hasMorePages() ? 'disabled' : '' }}">
                                    <a class="page-link rounded-3 border-0 bg-transparent text-secondary" href="{{ $news->nextPageUrl() }}">&rsaquo;</a>
                                </li>
                            </ul>
                        </div>
                    @endif

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

    function applyClientNewsFilters() {
        var grid = document.getElementById('owlNewsGrid');
        if (!grid) return;

        var month = document.getElementById('owlNewsMonth')?.value || 'all';
        var year = document.getElementById('owlNewsYear')?.value || 'all';
        var published = document.getElementById('owlNewsPublished')?.value || 'all';
        var sort = document.getElementById('owlNewsSort')?.value || 'latest';
        var cards = Array.prototype.slice.call(grid.querySelectorAll('.owl-news-card'));
        var now = new Date();
        var today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        var weekStart = new Date(today);
        var dayFromMonday = (today.getDay() + 6) % 7;
        weekStart.setDate(today.getDate() - dayFromMonday);
        var visibleCount = 0;

        cards.forEach(function(card) {
            var date = new Date(card.dataset.newsCreated + 'T00:00:00');
            var matchesMonth = month === 'all' || String(date.getMonth() + 1) === month;
            var matchesYear = year === 'all' || String(date.getFullYear()) === year;
            var matchesPublished = published === 'all' ||
                (published === 'today' && date.getTime() === today.getTime()) ||
                (published === 'week' && date >= weekStart && date <= now) ||
                (published === 'month' && date.getMonth() === now.getMonth() && date.getFullYear() === now.getFullYear()) ||
                (published === 'year' && date.getFullYear() === now.getFullYear());
            var isVisible = matchesMonth && matchesYear && matchesPublished;
            var column = card.closest('.col');

            if (column) column.classList.toggle('d-none', !isVisible);
            if (isVisible) visibleCount++;
        });

        if (sort === 'az' || sort === 'za') {
            cards.sort(function(a, b) {
                var titleA = a.querySelector('.owl-card-title')?.textContent.trim().toLowerCase() || '';
                var titleB = b.querySelector('.owl-card-title')?.textContent.trim().toLowerCase() || '';
                return sort === 'az' ? titleA.localeCompare(titleB) : titleB.localeCompare(titleA);
            });
            cards.forEach(function(card) {
                var column = card.closest('.col');
                if (column) grid.appendChild(column);
            });
        }

        document.getElementById('owlNewsClientEmpty')?.classList.toggle('d-none', visibleCount > 0);
    }

    function escapeNewsHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    document.addEventListener('DOMContentLoaded', function(){
        // Initialize dark mode
        initDarkMode();

        // ── Live Autocomplete Search for News Filter ────────────────────────────
        function initNewsAutocomplete() {
            var searchInput = document.getElementById('news-search-input') || document.getElementById('owl-search-input');
            var searchResults = document.getElementById('news-search-results');
            var form = searchInput ? searchInput.closest('form') : null;
            var debounceTimer = null;
            var submitTimer = null;

            if (!searchInput || !searchResults) return;

            var listEl = searchResults.querySelector('.autocomplete-list-group') || searchResults;
            var footerEl = searchResults.querySelector('.search-footer');

            searchInput.addEventListener('input', function () {
                var value = this.value.trim();
                clearTimeout(debounceTimer);
                clearTimeout(submitTimer);

                if (value.length < 1) {
                    searchResults.style.display = 'none';
                    if (listEl) listEl.innerHTML = '';
                    if (footerEl) footerEl.style.display = 'none';
                    submitTimer = setTimeout(function () {
                        if (form) form.submit();
                    }, 400);
                    return;
                }

                debounceTimer = setTimeout(function () {
                    fetch('/news/search-autocomplete?q=' + encodeURIComponent(value), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(function (response) {
                        if (!response.ok) throw new Error('HTTP ' + response.status);
                        return response.json();
                    })
                    .then(function (data) {
                        var html = '';
                        if (data && data.length > 0) {
                            data.forEach(function (item) {
                                var subDetails = [];
                                if (item.author) subDetails.push('By: ' + escapeNewsHtml(item.author));
                                if (item.created_at) subDetails.push(escapeNewsHtml(item.created_at));
                                if (item.views !== undefined) subDetails.push(escapeNewsHtml(item.views) + ' views');

                                var itemUrl = item.slug ? '/news/' + encodeURIComponent(item.slug) : '#';

                                html += '<a href="' + itemUrl + '" class="dropdown-item py-2 px-3 text-wrap border-bottom-subtle news-search-item" data-title="' + escapeNewsHtml(item.title) + '">' +
                                    '<div class="d-flex align-items-center justify-content-between mb-1">' +
                                        '<span class="text-dark small fw-semibold text-truncate me-2">' + escapeNewsHtml(item.title) + '</span>' +
                                        '<span class="badge bg-primary-lt text-primary small flex-shrink-0">NEWS</span>' +
                                    '</div>' +
                                    (subDetails.length > 0 ? '<div class="text-muted text-truncate" style="font-size: 0.75rem;">' + subDetails.join(' &bull; ') + '</div>' : '') +
                                '</a>';
                            });
                        }
                        if (listEl) listEl.innerHTML = html;

                        var keyword = searchInput.value.trim();
                        if (footerEl) {
                            if (data && data.length > 0) {
                                footerEl.innerHTML = `
                                    <a href="/news?q=${encodeURIComponent(keyword)}" class="dropdown-item fw-semibold text-primary py-2 d-flex align-items-center justify-content-center gap-2">
                                        <i class="ti ti-search"></i>
                                        <span>See all results for "${keyword}"</span>
                                    </a>
                                `;
                                footerEl.style.display = 'block';
                            } else {
                                footerEl.innerHTML = '<div class="dropdown-item text-muted disabled py-2">No items found</div>';
                                footerEl.style.display = 'block';
                            }
                        }
                        searchResults.style.display = 'block';
                    })
                    .catch(function () {
                        searchResults.style.display = 'none';
                    });
                }, 250);
            });

            searchInput.addEventListener('focus', function () {
                if (this.value.trim().length >= 1 && ((listEl && listEl.children.length > 0) || (footerEl && footerEl.style.display !== 'none'))) {
                    searchResults.style.display = 'block';
                }
            });

            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    searchResults.style.display = 'none';
                }
            });

            document.addEventListener('click', function (e) {
                var target = e.target.closest('.news-search-item');
                if (target) {
                    e.preventDefault();
                    var title = target.getAttribute('data-title') || '';
                    searchInput.value = title;
                    searchResults.style.display = 'none';
                    if (form) form.submit();
                    return;
                }

                if (searchInput && searchResults && !searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                    searchResults.style.display = 'none';
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === '/' && document.activeElement.tagName.toLowerCase() !== 'input') {
                    e.preventDefault();
                    searchInput.focus();
                    searchInput.select();
                }
            });
        }

        initNewsAutocomplete();

        function syncNewsQuickFilter(filter, value) {
            var capitalizedFilter = filter.charAt(0).toUpperCase() + filter.slice(1);
            var input = document.getElementById('owlNews' + capitalizedFilter);
            var modalFilter = document.getElementById('owlNewsModal' + capitalizedFilter);
            var matchingItem = Array.prototype.find.call(
                document.querySelectorAll('.news-quick-filter-item[data-filter="' + filter + '"]'),
                function(option) {
                    return option.dataset.value === value;
                }
            );
            var dropdown = matchingItem?.closest('.dropdown');
            var button = dropdown?.querySelector('.learning-fast-filter-control');
            var label = button?.querySelector('[data-news-filter-label]');

            if (input) input.value = value;
            if (modalFilter && modalFilter.value !== value) modalFilter.value = value;
            if (filter === 'views') {
                if (value === 'most_viewed' || value === 'least_viewed') {
                    if (sortInput) sortInput.value = value;
                    if (sortDate) sortDate.value = 'all';
                    if (sortTitle) sortTitle.value = 'default';
                } else if (sortInput && (sortInput.value === 'most_viewed' || sortInput.value === 'least_viewed')) {
                    sortInput.value = 'latest';
                }
            }
            dropdown?.querySelectorAll('.news-quick-filter-item').forEach(function(option) {
                option.classList.toggle('active', option.dataset.value === value);
            });
            if (label) {
                label.textContent = value === '' || value === 'all'
                    ? (filter === 'views' ? 'All Views' : 'All')
                    : matchingItem.textContent.trim();
            }
            button?.classList.toggle('active', value !== '' && value !== 'all');
        }

        document.querySelectorAll('.news-quick-filter-item').forEach(function(item) {
            item.addEventListener('click', function(event) {
                event.preventDefault();
                syncNewsQuickFilter(item.dataset.filter, item.dataset.value ?? '');
                var filterForm = document.getElementById('owlNewsFilterForm');
                if (filterForm) filterForm.submit();
            });
        });

        document.querySelectorAll('[data-news-modal-filter]').forEach(function(select) {
            select.addEventListener('change', function() {
                syncNewsQuickFilter(select.dataset.newsModalFilter, select.value);
            });
        });

        var sortDate = document.getElementById('owlNewsSortDate');
        var sortTitle = document.getElementById('owlNewsSortTitle');
        var sortInput = document.getElementById('owlNewsSort');

        function syncNewsSort(changedFilter) {
            if (!sortDate || !sortTitle || !sortInput) return;

            syncNewsQuickFilter('views', 'all');

            if (changedFilter === 'date' && sortDate.value !== 'all') {
                sortTitle.value = 'default';
            } else if (changedFilter === 'title' && sortTitle.value !== 'default') {
                sortDate.value = 'all';
            }

            if (sortDate.value === 'oldest') {
                sortInput.value = 'oldest';
            } else if (sortDate.value === 'newest') {
                sortInput.value = 'latest';
            } else if (sortTitle.value === 'az' || sortTitle.value === 'za') {
                sortInput.value = sortTitle.value;
            } else {
                sortInput.value = 'latest';
            }
        }

        sortDate?.addEventListener('change', function() { syncNewsSort('date'); });
        sortTitle?.addEventListener('change', function() { syncNewsSort('title'); });

        applyClientNewsFilters();

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
<style>
.owl-news-card .owl-card-title {
    font-size: 0.95rem !important;
    font-weight: 600 !important;
    color: #2c3e50;
    line-height: 1.4 !important;
    min-height: 2.8rem;
    display: -webkit-box !important;
    -webkit-line-clamp: 2 !important;
    -webkit-box-orient: vertical !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    margin-bottom: 0.5rem !important;
}
.owl-news-card .owl-card-title a,
.owl-news-card .owl-card-title-link {
    font-size: 0.95rem !important;
    font-weight: 600 !important;
    color: inherit !important;
    line-height: 1.4 !important;
}
[data-bs-theme="dark"] .owl-news-card .owl-card-title,
body.dark-mode .owl-news-card .owl-card-title {
    color: #e2e8f0 !important;
}

/* Global & Module Autocomplete Search Dropdown Containers */
.dropdown-menu.search-results-dropdown,
.search-autocomplete-container,
#news-search-results {
    background-color: #ffffff !important;
    background: #ffffff !important;
    border-radius: 8px !important;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12) !important;
    border: 1px solid #e2e8f0 !important;
    overflow: hidden !important;
}

/* Scrollable List Container (Where items are rendered) */
.autocomplete-list-group,
#news-autocomplete-list {
    background-color: #ffffff !important;
    background: #ffffff !important;
}

/* Autocomplete Items */
#news-search-results .dropdown-item,
.dropdown-menu .dropdown-item,
.autocomplete-list-group .dropdown-item {
    background-color: #ffffff !important;
    color: #333333 !important;
}

#news-search-results .dropdown-item:hover,
#news-search-results .dropdown-item:focus,
.dropdown-menu .dropdown-item:hover,
.dropdown-menu .dropdown-item:focus {
    background-color: #f8f9fa !important;
}

/* Sticky Bottom Footer Area */
.search-footer,
#news-search-footer {
    background-color: #f8f9fa !important;
    border-top: 1px solid #e2e8f0 !important;
}
</style>
@endsection
