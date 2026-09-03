@php
    $sortOptions = [
        'latest' => 'Latest', 'oldest' => 'Oldest',
        'most_viewed' => 'Most Viewed', 'least_viewed' => 'Least Viewed',
        'most_liked' => 'Most Liked', 'least_liked' => 'Least Liked',
        'az' => 'A–Z', 'za' => 'Z–A',
    ];
    $monthOptions = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];
    $monthLabel = count($filters['month']) === 1
        ? ($monthOptions[$filters['month'][0]] ?? 'All')
        : (count($filters['month']) > 1 ? count($filters['month']) . ' selected' : 'All');
    $yearLabel = count($filters['year']) === 1
        ? (string) $filters['year'][0]
        : (count($filters['year']) > 1 ? count($filters['year']) . ' selected' : 'All');
    $viewsLabel = $filters['views'] ? ($sortOptions[$filters['views']] ?? 'All') : 'All';
    $advancedCount = count($filters['category'])
        + (int) !empty($filters['uploader'])
        + (int) !empty($filters['date_from'])
        + (int) !empty($filters['date_to'])
        + (int) !empty($filters['likes']);
@endphp

<section class="gallery-filter-card mb-4" aria-labelledby="gallery-filter-title" data-gallery-filter-region>
    <h2 id="gallery-filter-title" class="visually-hidden">Search and filter gallery</h2>
    <form action="{{ route('gallery.user.index') }}" method="GET" class="gallery-filter-form" data-gallery-form>
        <label class="form-label small text-muted mb-2 gallery-fast-filter-label" for="gallery-search">Quick Filter</label>
        <div class="gallery-filter-toolbar">
            <div class="gallery-quick-dropdowns">
                {{-- 1. Year --}}
                <div class="dropdown">
                    <button type="button" class="btn gallery-fast-filter-control dropdown-toggle {{ $filters['year'] ? 'active' : '' }}"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="ti ti-calendar-event me-1"></i>Year: <span class="fw-semibold" data-gallery-filter-label="year">{{ $yearLabel }}</span>
                    </button>
                    <div class="dropdown-menu">
                        <a href="#" class="dropdown-item gallery-quick-filter-item {{ !$filters['year'] ? 'active' : '' }}" data-filter="year" data-value="">All</a>
                        @foreach($years as $year)
                            <a href="#" class="dropdown-item gallery-quick-filter-item {{ count($filters['year']) === 1 && $filters['year'][0] === $year ? 'active' : '' }}"
                                data-filter="year" data-value="{{ $year }}">{{ $year }}</a>
                        @endforeach
                    </div>
                </div>

                {{-- 2. Month --}}
                <div class="dropdown">
                    <button type="button" class="btn gallery-fast-filter-control dropdown-toggle {{ $filters['month'] ? 'active' : '' }}"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="ti ti-calendar me-1"></i>Month: <span class="fw-semibold" data-gallery-filter-label="month">{{ $monthLabel }}</span>
                    </button>
                    <div class="dropdown-menu gallery-quick-dropdown-scroll">
                        <a href="#" class="dropdown-item gallery-quick-filter-item {{ !$filters['month'] ? 'active' : '' }}" data-filter="month" data-value="">All</a>
                        @foreach($monthOptions as $month => $label)
                            <a href="#" class="dropdown-item gallery-quick-filter-item {{ count($filters['month']) === 1 && $filters['month'][0] === $month ? 'active' : '' }}"
                                data-filter="month" data-value="{{ $month }}">{{ $label }}</a>
                        @endforeach
                    </div>
                </div>

                {{-- 3. Views --}}
                <div class="dropdown">
                    <button type="button" class="btn gallery-fast-filter-control dropdown-toggle {{ $filters['views'] ? 'active' : '' }}"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        Views: <span class="fw-semibold" data-gallery-filter-label="views">{{ $viewsLabel }}</span>
                    </button>
                    <div class="dropdown-menu">
                        @foreach(['' => 'All Views', 'most_viewed' => 'Most Viewed', 'least_viewed' => 'Least Viewed'] as $value => $label)
                            <a href="#" class="dropdown-item gallery-quick-filter-item {{ ($filters['views'] ?? '') === $value ? 'active' : '' }}"
                                data-filter="views" data-value="{{ $value }}">{{ $label }}</a>
                        @endforeach
                    </div>
                </div>
            </div>

            <input type="hidden" name="views" value="{{ $filters['views'] }}" data-gallery-views>
            <input type="hidden" name="sort" value="{{ $filters['sort'] }}" data-gallery-sort>

            <div class="gallery-filter-search">
                <label class="visually-hidden" for="gallery-search-input">Search gallery by title</label>
                <div class="position-relative gallery-search-container flex-grow-1 search-input-wrapper">
                    <div class="input-icon">
                        <span class="input-icon-addon" aria-hidden="true"><i class="ti ti-search"></i></span>
                        <input id="gallery-search-input" type="search" name="q" value="{{ $filters['q'] }}"
                            class="form-control module-search-input" placeholder="Search by title..." autocomplete="off">
                    </div>
                    <div id="gallery-search-results" class="dropdown-menu shadow-sm w-100 position-absolute mt-1 module-search-dropdown" style="display: none; max-height: 380px; overflow-y: auto; z-index: 1050; padding: 0;">
                        <div class="autocomplete-list-group list-group list-group-flush"></div>
                        <div class="search-footer border-top bg-light p-2 text-center sticky-bottom"></div>
                    </div>
                </div>
            </div>
            <div class="gallery-filter-actions">
                <button type="submit" class="btn btn-primary gallery-action-btn" aria-label="Search" title="Search">
                    <i class="ti ti-search" aria-hidden="true"></i>
                </button>
                <a href="#galleryFilterModal" class="btn gallery-action-btn gallery-action-secondary" role="button"
                    data-bs-toggle="modal" data-bs-target="#galleryFilterModal"
                    aria-controls="galleryFilterModal" aria-label="More Filters" title="More Filters">
                    <i class="ti ti-adjustments-horizontal" aria-hidden="true"></i>
                    @if($advancedCount > 0)
                        <span class="gallery-filter-count" aria-label="{{ $advancedCount }} advanced filters active">{{ $advancedCount }}</span>
                    @endif
                </a>
                {{-- <button type="submit" class="btn gallery-action-btn gallery-action-apply" aria-label="Apply Filter" title="Apply Filter">
                    <i class="ti ti-check" aria-hidden="true"></i>
                </button> --}}
                <a href="{{ route('gallery.user.index') }}" class="btn gallery-action-btn gallery-action-reset"
                    data-gallery-reset aria-label="Reset Filter" title="Reset Filter">
                    <i class="ti ti-refresh" aria-hidden="true"></i>
                </a>
            </div>
        </div>
        @include('user.gallery.components.gallery-active-filters')
        @include('user.gallery.components.gallery-filter-modal')
    </form>
</section>
