@php
    // Filter options are derived only from the paginated TrainingPlan data already
    // loaded by PlaylistUserController. No query is executed from this view.
    $playlistFilterItems = collect($trainingplan->items());
    $playlistMonths = $playlistFilterItems
        ->mapWithKeys(fn ($playlist) => [
            (int) $playlist->created_at->format('n') => $playlist->created_at->format('F'),
        ])
        ->sortKeys();
    $playlistYears = $playlistFilterItems
        ->map(fn ($playlist) => (int) $playlist->created_at->format('Y'))
        ->unique()
        ->sortDesc()
        ->values();
    $playlistClassifications = $playlistFilterItems
        ->flatMap(fn ($playlist) => $playlist->trainingplandetail
            ->map(fn ($detail) => optional($detail->content)->classification)
        )
        ->filter(fn ($classification) => !empty($classification?->id))
        ->unique('id')
        ->sortBy('classification_name')
        ->values();
    $playlistAuthors = $playlistFilterItems
        ->flatMap(fn ($playlist) => $playlist->trainingplandetail
            ->map(fn ($detail) => optional($detail->content)->author)
        )
        ->filter(fn ($author) => !empty(trim($author ?? '')))
        ->unique()
        ->sort()
        ->values();

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

    $learningHourOptions = [
        'lt1h' => '< 1 Hour',
        '1-3h' => '1 - 3 hours',
        'gt3h' => '> 3 Hours',
    ];
@endphp

<div class="card playlist-filter-card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <label class="form-label small text-muted mb-2 playlist-fast-filter-label" for="playlistStatusFilter">
            QUICK FILTER
        </label>

        <div class="playlist-filter-toolbar">
            <div class="playlist-filter-field playlist-filter-field-status">
                <select id="playlistStatusFilter" class="form-select playlist-filter-control" aria-label="Status">
                    <option value="all">Status</option>
                    <option value="not_started">Not Started</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                </select>
            </div>

            <div class="playlist-filter-field playlist-filter-field-year">
                <select id="playlistYearFilter" class="form-select playlist-filter-control" aria-label="Year">
                    <option value="all">Year</option>
                    @foreach ($playlistYears as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
            </div>

            <div class="playlist-filter-field playlist-filter-field-month">
                <select id="playlistMonthFilter" class="form-select playlist-filter-control" aria-label="Month">
                    <option value="all">Month</option>
                    @foreach ($playlistMonths as $monthNumber => $monthName)
                        <option value="{{ $monthNumber }}">{{ $monthName }}</option>
                    @endforeach
                </select>
            </div>

            <div class="playlist-filter-search">
                <label class="sr-only" for="playlistSearch">Search playlist</label>
                <div class="input-icon">
                    <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                    <input type="search" id="playlistSearch" class="form-control"
                           placeholder="Search playlist..." autocomplete="off">
                </div>
            </div>

            <div class="playlist-filter-actions">
                <button type="button" class="btn btn-primary playlist-filter-action-btn"
                        id="playlistSearchAction" title="Search" aria-label="Search">
                    <i class="ti ti-search"></i>
                </button>

                <button type="button"
                        class="btn btn-outline-primary playlist-filter-action-btn position-relative"
                        id="playlistMoreFiltersButton"
                        data-bs-toggle="modal" data-bs-target="#playlistMoreFiltersModal"
                        data-toggle="modal" data-target="#playlistMoreFiltersModal"
                        title="More Filters" aria-label="More Filters">
                    <i class="ti ti-adjustments-horizontal"></i>
                    <span class="badge bg-primary text-white playlist-filter-count d-none"
                          id="playlistMoreFiltersCount"></span>
                </button>

                <button type="button" class="btn btn-outline-primary playlist-filter-action-btn"
                        id="playlistResetFilter" title="Reset Filter" aria-label="Reset Filter">
                    <i class="ti ti-refresh"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="playlistMoreFiltersModal" tabindex="-1" aria-hidden="true">
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
                {{-- Author --}}
                <div class="learning-filter-group">
                    <div class="fw-semibold mb-2">Author</div>
                    <select id="playlistFilterAuthor" class="form-select rounded-3">
                        <option value="">All Authors</option>
                        @foreach ($playlistAuthors as $author)
                            <option value="{{ $author }}">{{ $author }}</option>
                        @endforeach
                    </select>
                </div>

                <hr class="my-3">

                {{-- Classification --}}
                <div class="learning-filter-group">
                    <div class="fw-semibold mb-2">Classification</div>
                    <select id="playlistClassificationFilter" class="form-select rounded-3">
                        <option value="all">All Classifications</option>
                        @foreach ($playlistClassifications as $classification)
                            <option value="{{ $classification->id }}">
                                {{ $classification->classification_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <hr class="my-3">

                {{-- Created Date --}}
                <div class="learning-filter-group">
                    <div class="fw-semibold mb-2">Created Date</div>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label small text-muted mb-1" for="playlistFilterCreatedFromDate">From Date</label>
                            <input type="text" id="playlistFilterCreatedFromDate" class="form-control rounded-3" placeholder="dd/mm/yyyy" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small text-muted mb-1" for="playlistFilterCreatedToDate">To Date</label>
                            <input type="text" id="playlistFilterCreatedToDate" class="form-control rounded-3" placeholder="dd/mm/yyyy" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'">
                        </div>
                    </div>
                </div>

                <hr class="my-3">

                {{-- Period --}}
                <div class="learning-filter-group">
                    <div class="fw-semibold mb-2">Period</div>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label small text-muted mb-1" for="playlistFilterDeadlineFromDate">From Date</label>
                            <input type="text" id="playlistFilterDeadlineFromDate" class="form-control rounded-3" placeholder="dd/mm/yyyy" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small text-muted mb-1" for="playlistFilterDeadlineToDate">To Date</label>
                            <input type="text" id="playlistFilterDeadlineToDate" class="form-control rounded-3" placeholder="dd/mm/yyyy" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'">
                        </div>
                    </div>
                </div>

                <hr class="my-3">

                {{-- Sort By --}}
                <div class="learning-filter-group">
                    <div class="fw-semibold mb-2">Sort By</div>
                    <select id="playlistFilterSort" class="form-select rounded-3">
                        <option value="all" selected>All</option>
                        <option value="oldest">Oldest</option>
                        <option value="newest">Newest</option>
                        <option value="asc">Ascending</option>
                        <option value="desc">Descending</option>
                    </select>
                </div>

                <hr class="my-3">

                {{-- Sort Views --}}
                <div class="learning-filter-group">
                    <div class="fw-semibold mb-2">Sort Views</div>
                    <div class="d-flex flex-wrap gap-3">
                        <label class="form-check">
                            <input class="form-check-input" type="radio" name="playlist_view_order" value="" checked>
                            <span class="form-check-label">Default</span>
                        </label>
                        <label class="form-check">
                            <input class="form-check-input" type="radio" name="playlist_view_order" value="most_viewed">
                            <span class="form-check-label">Most Viewed</span>
                        </label>
                        <label class="form-check">
                            <input class="form-check-input" type="radio" name="playlist_view_order" value="least_viewed">
                            <span class="form-check-label">Least Viewed</span>
                        </label>
                    </div>
                </div>

                <hr class="my-3">

                {{-- Playlist Duration --}}
                <div class="learning-filter-group">
                    <div class="fw-semibold mb-2">Playlist Duration</div>
                    <div class="d-flex flex-wrap gap-3">
                        <label class="form-check">
                            <input class="form-check-input" type="radio" name="playlist_duration" value="all" checked>
                            <span class="form-check-label">All Durations</span>
                        </label>
                        @foreach ($durationOptions as $val => $label)
                            <label class="form-check">
                                <input class="form-check-input" type="radio" name="playlist_duration" value="{{ $val }}">
                                <span class="form-check-label">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <hr class="my-3">

                {{-- Total Content --}}
                <div class="learning-filter-group">
                    <div class="fw-semibold mb-2">Total Content</div>
                    <div class="d-flex flex-wrap gap-3">
                        @foreach ($totalPageOptions as $val => $label)
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" name="playlist_total_pages[]" value="{{ $val }}">
                                <span class="form-check-label">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <hr class="my-3">

                {{-- LEARNING HOUR --}}
                <div class="learning-filter-group">
                    <div class="fw-semibold mb-2">Learning Hour</div>
                    <div class="d-flex flex-wrap gap-3">
                        <label class="form-check">
                            <input class="form-check-input" type="radio" name="playlist_learning_hour" value="all" checked>
                            <span class="form-check-label">All</span>
                        </label>
                        @foreach ($learningHourOptions as $val => $label)
                            <label class="form-check">
                                <input class="form-check-input" type="radio" name="playlist_learning_hour" value="{{ $val }}">
                                <span class="form-check-label">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn content-reset-btn rounded-3" id="playlistResetMoreFilters">
                    <i class="ti ti-refresh me-1"></i>
                    Reset
                </button>
                <button type="button" class="btn btn-primary rounded-3" id="playlistApplyMoreFilters" data-bs-dismiss="modal" data-dismiss="modal">
                    <i class="ti ti-check me-1"></i>
                    Apply Filter
                </button>
            </div>
        </div>
    </div>
</div>

