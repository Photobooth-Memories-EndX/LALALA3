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
@endphp

<div class="card playlist-filter-card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <label class="form-label small text-muted mb-2 playlist-fast-filter-label" for="playlistStatusFilter">
            Fast Filter
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

            <div class="playlist-filter-field playlist-filter-field-month">
                <select id="playlistMonthFilter" class="form-select playlist-filter-control" aria-label="Month">
                    <option value="all">Month</option>
                    @foreach ($playlistMonths as $monthNumber => $monthName)
                        <option value="{{ $monthNumber }}">{{ $monthName }}</option>
                    @endforeach
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
                        data-toggle="modal" data-target="#playlistMoreFiltersModal"
                        title="More Filters" aria-label="More Filters">
                    <i class="ti ti-adjustments-horizontal"></i>
                    <span class="badge bg-primary text-white playlist-filter-count d-none"
                          id="playlistMoreFiltersCount"></span>
                </button>

                <button type="button" class="btn btn-primary playlist-filter-action-btn"
                        id="playlistApplyFilter" title="Apply Filter" aria-label="Apply Filter">
                    <i class="ti ti-check"></i>
                </button>

                <button type="button" class="btn btn-outline-primary playlist-filter-action-btn"
                        id="playlistResetFilter" title="Reset Filter" aria-label="Reset Filter">
                    <i class="ti ti-refresh"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="playlistMoreFiltersModal" tabindex="-1" role="dialog"
     aria-labelledby="playlistMoreFiltersTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content playlist-filter-modal">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0" id="playlistMoreFiltersTitle">More Filters</h5>
                    <div class="text-muted small">Playlist display options</div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body playlist-more-filters">
                <div class="playlist-more-filter-field">
                    <label class="form-label" for="playlistClassificationFilter">Classification</label>
                    <select id="playlistClassificationFilter" class="form-select">
                        <option value="all">All</option>
                        @foreach ($playlistClassifications as $classification)
                            <option value="{{ $classification->id }}">
                                {{ $classification->classification_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="playlist-more-filter-field">
                    <label class="form-label" for="playlistDurationFilter">Duration</label>
                    <select id="playlistDurationFilter" class="form-select">
                        <option value="all">All</option>
                        <option value="0_30">0 - 30 Minutes</option>
                        <option value="30_60">30 - 60 Minutes</option>
                        <option value="60_120">1 - 2 Hours</option>
                        <option value="over_120">More than 2 Hours</option>
                    </select>
                </div>

                <div class="playlist-more-filter-field">
                    <label class="form-label" for="playlistDeadlineFilter">Deadline</label>
                    <select id="playlistDeadlineFilter" class="form-select">
                        <option value="all">All</option>
                        <option value="has_deadline">Has Deadline</option>
                        <option value="no_deadline">No Deadline</option>
                    </select>
                </div>

                <div class="playlist-more-filter-field">
                    <label class="form-label" for="playlistMaterialsFilter">Learning Materials</label>
                    <select id="playlistMaterialsFilter" class="form-select">
                        <option value="all">All</option>
                        <option value="1_5">1 - 5</option>
                        <option value="6_10">6 - 10</option>
                        <option value="11_20">11 - 20</option>
                        <option value="over_20">&gt; 20</option>
                    </select>
                </div>

                <div class="playlist-more-filter-field">
                    <label class="form-label" for="playlistSort">Sort Playlist</label>
                    <select id="playlistSort" class="form-select">
                        <option value="az">A - Z</option>
                        <option value="za">Z - A</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="playlistApplyMoreFilters" data-dismiss="modal">
                    Apply
                </button>
            </div>
        </div>
    </div>
</div>
