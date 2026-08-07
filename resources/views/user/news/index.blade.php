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

<div class="page-body owl-news-page">

    <div class="container-xl">

        {{-- HERO SECTION --}}
        @include('user.news.partials.hero')

        {{-- SEARCH & FILTER TOOLBAR --}}
        <div class="owl-toolbar-wrapper mb-4">
            <div class="card border-0 owl-toolbar-card">
                <div class="card-body p-3">
                    <form method="GET" id="owlNewsFilterForm" class="record-filter-shell owl-news-filter-form">
                        <div class="record-fast-filter-section">
                            <label class="form-label small text-muted mb-2 learning-fast-filter-label" for="owl-search-input">
                                Fast Filter
                            </label>

                            <div class="fast-filter-toolbar owl-news-filter-toolbar">
                            <div class="record-toolbar-search owl-news-filter-search">
                                <label class="visually-hidden" for="owl-search-input">Search news</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                                    <input type="search" id="owl-search-input" name="search"
                                           class="form-control"
                                           placeholder="Search news title, description or author..."
                                           value="{{ request('search') }}"
                                           aria-label="Search news"
                                           autocomplete="off">
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

                            {{-- 3. Published Period & Date Range (2 Kolom) --}}
                            <div class="learning-filter-group">
                                <div class="fw-semibold mb-2">Published Period</div>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label small text-muted mb-1" for="owlNewsPublished"><i class="ti ti-calendar me-1"></i>Period Shortcut</label>
                                        <select name="published" id="owlNewsPublished" class="form-select rounded-3" form="owlNewsFilterForm">
                                            <option value="all" {{ request('published', 'all') === 'all' ? 'selected' : '' }}>All Time</option>
                                            <option value="today" {{ request('published') === 'today' ? 'selected' : '' }}>Today</option>
                                            <option value="week" {{ request('published') === 'week' ? 'selected' : '' }}>This Week</option>
                                            <option value="month" {{ request('published') === 'month' ? 'selected' : '' }}>This Month</option>
                                            <option value="year" {{ request('published') === 'year' ? 'selected' : '' }}>This Year</option>
                                        </select>
                                    </div>
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

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                    <div>
                        <div class="text-uppercase text-primary small fw-semibold mb-2">AI News Hub</div>
                        <h2 class="fw-bold mb-0">
                            {{ request('search') ? 'Search Results' : 'Hub Highlights' }}
                        </h2>
                    </div>
                </div>

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

    document.addEventListener('DOMContentLoaded', function(){
        // Initialize dark mode
        initDarkMode();

        // Search functionality (Instant Auto-Submit with 400ms debounce)
        var searchInput = document.getElementById('owl-search-input');
        if(searchInput){
            var form = searchInput.closest('form');
            var submitForm = debounce(function(){ if(form) form.submit(); }, 400);
            searchInput.addEventListener('input', submitForm);
            searchInput.addEventListener('keyup', submitForm);
            document.addEventListener('keydown', function(e){
                if(e.key === '/' && document.activeElement.tagName.toLowerCase() !== 'input'){
                    e.preventDefault();
                    searchInput.focus();
                    searchInput.select();
                }
            });
        }

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
@endsection
