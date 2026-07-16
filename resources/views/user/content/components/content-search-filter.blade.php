@php
    $filterFromDate = request('from_date');
    $filterToDate = request('to_date');
    $filterDuration = request('duration', 'all');
    $filterTotalPages = array_values(array_filter((array) request('total_pages', [])));

    $durationOptions = [
        'lt10'  => '< 10 Menit',
        '10-30' => '10 - 30 Menit',
        '30-60' => '30 - 60 Menit',
        'gt60'  => '> 1 Jam',
    ];

    $totalPageOptions = [
        'lt10'  => '< 10',
        '10-20' => '10 - 20',
        '21-50' => '21 - 50',
        'gt50'  => '> 50',
    ];

    $modalFilterCount = 0;
    $modalFilterCount += filled($filterAuthor) ? 1 : 0;
    $modalFilterCount += filled($filterFromDate) ? 1 : 0;
    $modalFilterCount += filled($filterToDate) ? 1 : 0;
    $modalFilterCount += $filterDateOrder !== 'newest' ? 1 : 0;
    $modalFilterCount += filled($filterViewOrder) ? 1 : 0;
    $modalFilterCount += $filterDuration !== 'all' ? 1 : 0;
    $modalFilterCount += count($filterTotalPages);

    $activeFilterCount = $modalFilterCount;
    $activeFilterCount += filled($filterSearch) ? 1 : 0;
    $activeFilterCount += filled($filterClassification) ? 1 : 0;
    $activeFilterCount += filled($filterType) ? 1 : 0;
@endphp

<div class="card border-0 shadow-sm rounded-4 mb-4 learning-filter-card content-filter-card">
    <div class="card-body p-4">
        <form method="GET" action="{{ route('content.user.index') }}" id="contentFilterForm">
            <div class="mb-3">
                <h3 class="card-title mb-1">All Content</h3>
                <div class="text-muted small">
                    Find learning materials by search, type, and category.
                </div>
            </div>

            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-4 col-xl-3">
                    <label class="form-label small text-muted mb-1" for="contentSearch">Search</label>
                    <div class="input-icon">
                        <span class="input-icon-addon">
                            <i class="ti ti-search"></i>
                        </span>
                        <input type="text"
                               name="s"
                               id="contentSearch"
                               value="{{ $filterSearch }}"
                               class="form-control rounded-3"
                               placeholder="Search content...">
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-3 col-xl-3">
                    <label class="form-label small text-muted mb-1" for="contentFilterClassification">Category / Classification</label>
                    <select name="classification" id="contentFilterClassification" class="form-select rounded-3" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        @foreach ($classifications as $classification)
                            <option value="{{ $classification->id }}" {{ $filterClassification == $classification->id ? 'selected' : '' }}>
                                {{ $classification->classification_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-sm-6 col-lg-2 col-xl-2">
                    <label class="form-label small text-muted mb-1" for="contentFilterType">Type</label>
                    <select name="type" id="contentFilterType" class="form-select rounded-3" onchange="this.form.submit()">
                        <option value="">All Types</option>
                        <option value="Document" {{ $filterType == 'Document' ? 'selected' : '' }}>Document</option>
                        <option value="Video" {{ $filterType == 'Video' ? 'selected' : '' }}>Video</option>
                        <option value="YouTube" {{ $filterType == 'YouTube' ? 'selected' : '' }}>YouTube</option>
                    </select>
                </div>

                <div class="col-12 col-lg-3 col-xl-4">
                    <div class="d-flex gap-2 learning-filter-actions justify-content-xl-end">
                        <button type="submit" class="btn btn-primary rounded-3 flex-fill flex-xl-grow-0">
                            <i class="ti ti-search me-1"></i>
                            Search
                        </button>

                        <button type="button"
                                class="btn btn-outline-primary rounded-3 flex-fill flex-xl-grow-0 position-relative"
                                data-bs-toggle="modal"
                                data-bs-target="#contentMoreFiltersModal">
                            <i class="ti ti-adjustments-horizontal me-1"></i>
                            More Filters
                            @if ($modalFilterCount > 0)
                                <span class="badge bg-primary text-white ms-1">{{ $modalFilterCount }}</span>
                            @endif
                        </button>

                        <a href="{{ route('content.user.index') }}"
                           class="btn content-reset-btn rounded-3 flex-fill flex-xl-grow-0 {{ $activeFilterCount > 0 ? 'is-active' : '' }}">
                            <i class="ti ti-refresh me-1"></i>
                            Reset
                        </a>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="contentMoreFiltersModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered learning-filter-modal">
                    <div class="modal-content rounded-4 border-0 shadow">
                        <div class="modal-header border-0 pb-0">
                            <h3 class="modal-title fw-bold">
                                <i class="ti ti-adjustments-horizontal me-1 text-primary"></i>
                                More Filters
                            </h3>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body pt-2">
                            {{-- <p class="text-muted small mb-4">
                                Semua filter di bawah hanya menggunakan data Content yang relevan.
                            </p> --}}

                            <div class="learning-filter-group">
                                <div class="fw-semibold mb-2">Author</div>
                                <select name="author" id="contentFilterAuthor" class="form-select rounded-3">
                                    <option value="">All Authors</option>
                                    @foreach ($authors as $author)
                                        <option value="{{ $author }}" {{ $filterAuthor == $author ? 'selected' : '' }}>
                                            {{ $author }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <hr class="my-3">

                            <div class="learning-filter-group">
                                <div class="fw-semibold mb-2">Date Range</div>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small text-muted mb-1" for="contentFilterFromDate">From Date</label>
                                        <input type="date"
                                               name="from_date"
                                               id="contentFilterFromDate"
                                               value="{{ $filterFromDate }}"
                                               class="form-control rounded-3">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small text-muted mb-1" for="contentFilterToDate">To Date</label>
                                        <input type="date"
                                               name="to_date"
                                               id="contentFilterToDate"
                                               value="{{ $filterToDate }}"
                                               class="form-control rounded-3">
                                    </div>
                                </div>
                            </div>

                            <hr class="my-3">

                            <div class="learning-filter-group">
                                <div class="fw-semibold mb-2">Sort Date</div>
                                <div class="d-flex flex-wrap gap-3">
                                    <label class="form-check">
                                        <input class="form-check-input" type="radio"
                                               name="date_order" value="newest"
                                               {{ $filterDateOrder === 'newest' ? 'checked' : '' }}>
                                        <span class="form-check-label">Newest</span>
                                    </label>
                                    <label class="form-check">
                                        <input class="form-check-input" type="radio"
                                               name="date_order" value="oldest"
                                               {{ $filterDateOrder === 'oldest' ? 'checked' : '' }}>
                                        <span class="form-check-label">Oldest</span>
                                    </label>
                                </div>
                            </div>

                            <hr class="my-3">

                            <div class="learning-filter-group">
                                <div class="fw-semibold mb-2">Sort Views</div>
                                <div class="d-flex flex-wrap gap-3">
                                    <label class="form-check">
                                        <input class="form-check-input" type="radio"
                                               name="view_order" value=""
                                               {{ blank($filterViewOrder) ? 'checked' : '' }}>
                                        <span class="form-check-label">Default</span>
                                    </label>
                                    <label class="form-check">
                                        <input class="form-check-input" type="radio"
                                               name="view_order" value="most_viewed"
                                               {{ $filterViewOrder === 'most_viewed' ? 'checked' : '' }}>
                                        <span class="form-check-label">Most Viewed</span>
                                    </label>
                                    <label class="form-check">
                                        <input class="form-check-input" type="radio"
                                               name="view_order" value="least_viewed"
                                               {{ $filterViewOrder === 'least_viewed' ? 'checked' : '' }}>
                                        <span class="form-check-label">Least Viewed</span>
                                    </label>
                                </div>
                            </div>

                            <hr class="my-3">

                            <div class="learning-filter-group">
                                <div class="fw-semibold mb-2">Duration</div>
                                <div class="d-flex flex-wrap gap-3">
                                    <label class="form-check">
                                        <input class="form-check-input" type="radio"
                                               name="duration" value="all"
                                               {{ $filterDuration === 'all' ? 'checked' : '' }}>
                                        <span class="form-check-label">All Durations</span>
                                    </label>
                                    @foreach ($durationOptions as $value => $label)
                                        <label class="form-check">
                                            <input class="form-check-input" type="radio"
                                                   name="duration" value="{{ $value }}"
                                                   {{ $filterDuration === $value ? 'checked' : '' }}>
                                            <span class="form-check-label">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <div class="form-hint mt-2">
                                    {{-- Mendukung format 04:38, 30:00, 60:00, dan 01:25:30. --}}
                                </div>
                            </div>

                            <hr class="my-3">

                            <div class="learning-filter-group">
                                <div class="fw-semibold mb-2">Total Pages</div>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach ($totalPageOptions as $value => $label)
                                        <label class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="total_pages[]" value="{{ $value }}"
                                                   {{ in_array($value, $filterTotalPages, true) ? 'checked' : '' }}>
                                            <span class="form-check-label">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <div class="form-hint mt-2">
                                    {{-- UI disiapkan tanpa mengubah struktur database atau query existing. --}}
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer border-0 pt-0">
                            <a href="{{ route('content.user.index') }}"
                               class="btn content-reset-btn rounded-3 {{ $activeFilterCount > 0 ? 'is-active' : '' }}">
                                <i class="ti ti-refresh me-1"></i>
                                Reset
                            </a>
                            <button type="submit" class="btn btn-primary rounded-3">
                                <i class="ti ti-check me-1"></i>
                                Apply Filter
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<x-learning.ongoing-filter-style />
