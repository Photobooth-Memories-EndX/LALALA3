@php
    // Filter options are derived only from the paginated TrainingPlan data already
    // loaded by PlaylistUserController. No query is executed from this view.
    $playlistFilterItems = collect($trainingplan->items());
    $playlistMonths = [
        '01' => 'January',
        '02' => 'February',
        '03' => 'March',
        '04' => 'April',
        '05' => 'May',
        '06' => 'June',
        '07' => 'July',
        '08' => 'August',
        '09' => 'September',
        '10' => 'October',
        '11' => 'November',
        '12' => 'December',
    ];
    $playlistYears = $playlistFilterItems
        ->map(fn ($playlist) => (int) $playlist->created_at->format('Y'))
        ->push((int) date('Y'))
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
            Quick Filter
        </label>

        <div class="playlist-filter-toolbar">
            <div class="playlist-filter-field playlist-filter-field-status">
                <select id="playlistStatusFilter" name="status" class="form-select playlist-filter-control" aria-label="Status">
                    <option value="">All Status</option>
                    <option value="not_started">Not Started</option>
                    <option value="in_progress">On Going</option>
                    <option value="completed">Completed</option>
                </select>
            </div>

            <div class="playlist-filter-field playlist-filter-field-year">
                <select id="playlistYearFilter" name="year" class="form-select playlist-filter-control" aria-label="Year">
                    <option value="">All Year</option>
                    @foreach ($playlistYears as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
            </div>

            <div class="playlist-filter-field playlist-filter-field-month">
                <select id="playlistMonthFilter" name="month" class="form-select playlist-filter-control" aria-label="Month">
                    <option value="">All Month</option>
                    @foreach ($playlistMonths as $monthNumber => $monthName)
                        <option value="{{ $monthNumber }}">{{ $monthName }}</option>
                    @endforeach
                </select>
            </div>

            <div class="playlist-filter-search">
                <label class="sr-only" for="playlist-search-input">Search playlist</label>
                <div class="position-relative playlist-search-container flex-grow-1 search-input-wrapper">
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                        <input type="search" id="playlist-search-input" class="form-control module-search-input"
                               placeholder="Search playlist..." value="{{ request('q', request('search')) }}" autocomplete="off">
                    </div>
                    <div id="playlist-search-results" class="dropdown-menu shadow-sm w-100 position-absolute mt-1 module-search-dropdown" style="display: none; max-height: 380px; overflow-y: auto; z-index: 1050; padding: 0;">
                        <div class="autocomplete-list-group list-group list-group-flush"></div>
                        <div class="search-footer border-top bg-light p-2 text-center sticky-bottom"></div>
                    </div>
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
                    <span class="badge bg-primary text-white playlist-filter-count {{ ($playlistAdvancedFilterCount ?? 0) > 0 ? '' : 'd-none' }}"
                          id="playlistMoreFiltersCount">{{ ($playlistAdvancedFilterCount ?? 0) > 0 ? $playlistAdvancedFilterCount : '' }}</span>
                </button>

                <button type="button" class="btn btn-outline-primary playlist-filter-action-btn"
                        id="playlistResetFilter" title="Reset Filter" aria-label="Reset Filter">
                    <i class="ti ti-refresh"></i>
                </button>
            </div>
        </div>
    </div>
</div>

@include('user.playlist.partials.modal-filter')

