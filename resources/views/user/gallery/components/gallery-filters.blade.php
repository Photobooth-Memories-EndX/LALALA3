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
                <div class="gallery-search-container position-relative flex-grow-1">
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                        <input type="text" id="gallery-search-input" name="q" value="{{ $filters['q'] }}"
                            class="form-control" placeholder="Search by title..." autocomplete="off">
                    </div>
                    
                    <div id="gallery-search-results" class="dropdown-menu shadow-sm w-100 position-absolute mt-1 p-0 overflow-hidden" style="display: none; z-index: 2000; background-color: #ffffff;">
                        <!-- Container 1: Scrollable List Area (Only items scroll here) -->
                        <div id="gallery-autocomplete-list" class="list-group list-group-flush" style="max-height: 280px; overflow-y: auto; background-color: #ffffff;"></div>
                        
                        <!-- Container 2: Pinned Sticky Footer (Fixed at bottom outside scroll area) -->
                        <div id="gallery-search-footer" class="border-top bg-light p-2 text-center" style="display: none; background-color: #f8f9fa;"></div>
                    </div>
                </div>
            </div>
            <div class="gallery-filter-actions d-flex align-items-center gap-1 flex-shrink-0">
                <!-- 1. Search Submit Button -->
                <button type="submit" class="btn btn-primary btn-icon px-3" aria-label="Search" title="Search">
                    <i class="ti ti-search fs-2" aria-hidden="true"></i>
                </button>

                <!-- 2. More Filters Modal Trigger Button (Icon Only - No Text) -->
                <button type="button" class="btn btn-outline-primary btn-icon px-3 position-relative" data-bs-toggle="modal" data-bs-target="#galleryFilterModal" aria-controls="galleryFilterModal" aria-label="More Filters" title="More Filters">
                    <i class="ti ti-adjustments-horizontal fs-2" aria-hidden="true"></i>
                    @if($advancedCount > 0)
                        <span class="position-absolute top-0 start-100 translate-middle p-1 bg-primary border border-light rounded-circle">
                            <span class="visually-hidden">{{ $advancedCount }} active filters</span>
                        </span>
                    @endif
                </button>

                <!-- 3. Reset Button -->
                <a href="{{ route('gallery.user.index') }}" class="btn btn-outline-secondary btn-icon px-3" data-gallery-reset aria-label="Reset Filter" title="Reset Filters">
                    <i class="ti ti-refresh fs-2" aria-hidden="true"></i>
                </a>
            </div>
        </div>
        @include('user.gallery.components.gallery-active-filters')
        @include('user.gallery.components.gallery-filter-modal')
    </form>
</section>
