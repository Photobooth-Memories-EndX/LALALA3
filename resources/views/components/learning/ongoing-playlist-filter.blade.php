@php
    $userId = auth()->id();

    // Status playlist diturunkan dari progress module; tidak ada "Paused" di playlist.
    $quickStatusOptions = [
        'all'         => 'All',
        'not_started' => 'Not Started',
        'ongoing'     => 'Ongoing',
        'completed'   => 'Completed',
    ];
    $curQuickStatus = (string) request('ongoing_quick_status', 'all');
    if (! array_key_exists($curQuickStatus, $quickStatusOptions)) {
        $curQuickStatus = 'all';
    }

    $quickYearOptions = \App\Models\ContentUser::where('user_id', $userId)
        ->selectRaw('DISTINCT YEAR(created_at) as learning_year')
        ->orderByDesc('learning_year')
        ->pluck('learning_year')
        ->filter()
        ->values();

    $monthOptions = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];

    $curYear = request('ongoing_year', 'all');
    $activeQuickMonths = array_values(array_map('strval', array_filter((array) request('ongoing_month', []))));
    $selectedProgress = array_values(array_map('strval', array_filter((array) request('ongoing_progress', []))));
    $quickMonthLabel = empty($activeQuickMonths)
        ? 'All'
        : (count($activeQuickMonths) === 1
            ? ($monthOptions[(int) $activeQuickMonths[0]] ?? $activeQuickMonths[0])
            : count($activeQuickMonths) . ' Months');

    $progressOptions = [
        '0'     => '0%',
        '1-25'  => '1 - 25%',
        '26-50' => '26 - 50%',
        '51-75' => '51 - 75%',
        '76-99' => '76 - 99%',
        '100'   => '100%',
    ];

    $curLearningHour = request('ongoing_learning_hour', 'all');
    $curDateFrom     = request('ongoing_date_from', request('ongoing_from_date'));
    $curDateTo       = request('ongoing_date_to', request('ongoing_to_date'));
    $curActivitySort = request('ongoing_activity_sort', 'newest_activity');
    $curProgram      = request('ongoing_status', 'all');
    $selDur          = (array) request('ongoing_duration', []);

    $playlistFilterCount = ($curQuickStatus !== 'all' ? 1 : 0)
        + ($curProgram !== 'all' ? 1 : 0)
        + ($curYear !== 'all' ? 1 : 0)
        + ($curActivitySort !== 'newest_activity' ? 1 : 0)
        + ($curLearningHour !== 'all' ? 1 : 0)
        + (filled($curDateFrom) ? 1 : 0)
        + (filled($curDateTo) ? 1 : 0)
        + count($activeQuickMonths)
        + count($selectedProgress)
        + count(array_filter($selDur));

    // Reset mode playlist: bersihkan seluruh filter tetapi tetap di mode playlist.
    $resetUrl = route('user.learning.dashboard') . '?' . http_build_query(['ongoing_mode' => 'playlist']);
@endphp

<div class="card border-0 shadow-sm rounded-4 mb-3 my-task-filter-card ongoing-filter-card">
    <div class="card-body p-3 p-md-4">
        {{-- Header: Title & Subtitle --}}
        <div class="mb-3">
            <h2 class="fw-bold mb-1 my-task-filter-title">Learning On Going</h2>
            <p class="text-muted small mb-0 my-task-filter-subtitle">Track and continue your in-progress courses, playlists, and learning modules.</p>
        </div>

        {{-- Category Filter Pills --}}
        <div class="d-flex align-items-center flex-wrap gap-2 mb-3 my-task-pills-row">
            <button type="button"
                    class="my-task-category-pill ongoing-mode-btn"
                    data-mode="content"
                    title="Filter by Content">
                <i class="ti ti-file-text me-1"></i> Content
            </button>
            <button type="button"
                    class="my-task-category-pill ongoing-mode-btn active"
                    data-mode="playlist"
                    title="Filter by Playlist">
                <i class="ti ti-list-details me-1"></i> Playlist
            </button>
            <button type="button"
                    class="my-task-category-pill ongoing-mode-btn"
                    data-mode="mooc"
                    title="Filter by MOOC">
                <i class="ti ti-world me-1"></i> MOOC
            </button>
        </div>

        {{-- Subtle Divider Line --}}
        <hr class="my-3 my-task-divider">

        <form action="{{ route('user.learning.dashboard') }}" method="GET" id="ongoingPlaylistFilterForm" class="ongoing-filter-shell">
            <input type="hidden" name="ongoing_mode" value="playlist">

            {{-- Fast Filter Toolbar --}}
            <div class="d-flex align-items-center flex-wrap gap-2 w-100 my-task-toolbar">
                {{-- 1. Dropdown Status --}}
                <div class="dropdown">
                    <button type="button"
                            class="btn my-task-dropdown-btn dropdown-toggle {{ $curQuickStatus !== 'all' ? 'active' : '' }}"
                            data-bs-toggle="dropdown"
                            data-bs-display="static"
                            aria-expanded="false">
                        Status: <span class="fw-semibold">{{ $quickStatusOptions[$curQuickStatus] }}</span>
                    </button>
                    <div class="dropdown-menu shadow-sm">
                        @foreach ($quickStatusOptions as $val => $label)
                            <a href="#"
                               class="dropdown-item my-task-dropdown-item ongoing-quick-item {{ $curQuickStatus === $val ? 'active' : '' }}"
                               data-param="ongoing_quick_status" data-value="{{ $val }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- 2. Dropdown Year --}}
                <div class="dropdown">
                    <button type="button"
                            class="btn my-task-dropdown-btn dropdown-toggle {{ $curYear !== 'all' ? 'active' : '' }}"
                            data-bs-toggle="dropdown"
                            data-bs-display="static"
                            aria-expanded="false">
                        Year: <span class="fw-semibold">{{ $curYear === 'all' ? 'All' : $curYear }}</span>
                    </button>
                    <div class="dropdown-menu shadow-sm">
                        <a href="#" class="dropdown-item my-task-dropdown-item ongoing-quick-item {{ $curYear === 'all' ? 'active' : '' }}"
                           data-param="ongoing_year" data-value="all">All</a>
                        @foreach ($quickYearOptions as $yearOption)
                            <a href="#"
                               class="dropdown-item my-task-dropdown-item ongoing-quick-item {{ (string) $curYear === (string) $yearOption ? 'active' : '' }}"
                               data-param="ongoing_year" data-value="{{ $yearOption }}">
                                {{ $yearOption }}
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- 3. Dropdown Month --}}
                <div class="dropdown">
                    <button type="button"
                            class="btn my-task-dropdown-btn dropdown-toggle {{ !empty($activeQuickMonths) ? 'active' : '' }}"
                            data-bs-toggle="dropdown"
                            data-bs-display="static"
                            aria-expanded="false">
                        Month: <span class="fw-semibold">{{ $quickMonthLabel }}</span>
                    </button>
                    <div class="dropdown-menu shadow-sm" style="max-height: 280px; overflow-y: auto;">
                        <a href="#" class="dropdown-item my-task-dropdown-item ongoing-quick-item {{ empty($activeQuickMonths) ? 'active' : '' }}"
                           data-param="ongoing_month" data-array="1" data-value="">All</a>
                        @foreach ($monthOptions as $num => $label)
                            <a href="#"
                               class="dropdown-item my-task-dropdown-item ongoing-quick-item {{ $activeQuickMonths === [(string) $num] ? 'active' : '' }}"
                               data-param="ongoing_month" data-array="1" data-value="{{ $num }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- 4. Search input with live autocomplete dropdown --}}
                <div class="position-relative flex-grow-1 my-task-search-wrapper search-ongoing-container" style="min-width: 220px;">
                    <span class="position-absolute top-50 start-0 translate-middle-y ps-3 text-muted pointer-events-none" style="z-index: 2;">
                        <i class="ti ti-search" style="font-size: 15px;"></i>
                    </span>
                    <input type="text"
                           name="ongoing_search"
                           id="ongoing-search-input"
                           value="{{ request('ongoing_search', request('q')) }}"
                           class="form-control my-task-search-input module-search-input"
                           aria-label="Search playlists"
                           placeholder="Search playlists..."
                           autocomplete="off">
                    @if (request()->filled('ongoing_search') || request()->filled('q'))
                        <a href="{{ $resetUrl }}" class="btn btn-link position-absolute top-50 end-0 translate-middle-y pe-2.5 text-muted text-decoration-none my-task-search-clear" title="Clear search" style="z-index: 2;">
                            <i class="ti ti-x"></i>
                        </a>
                    @endif
                    <div id="ongoing-search-results" class="dropdown-menu shadow-sm w-100 position-absolute mt-1 module-search-dropdown" style="display: none; max-height: 380px; overflow-y: auto; z-index: 1050; padding: 0;">
                        <div class="autocomplete-list-group list-group list-group-flush"></div>
                        <div class="search-footer border-top bg-light p-2 text-center sticky-bottom"></div>
                    </div>
                </div>

                {{-- 5. Square Action Buttons --}}
                <button type="submit"
                        class="btn btn-primary my-task-btn-square my-task-btn-search"
                        title="Apply Filter"
                        aria-label="Apply Filter">
                    <i class="ti ti-search text-white fs-3"></i>
                </button>

                <button type="button"
                        class="btn btn-white my-task-btn-square my-task-btn-filter position-relative"
                        data-bs-toggle="modal"
                        data-bs-target="#ongoingPlaylistMoreFiltersModal"
                        title="More Filters"
                        aria-label="More Filters">
                    <i class="ti ti-adjustments-horizontal fs-3 text-secondary"></i>
                    @if ($playlistFilterCount > 0)
                        <span class="badge bg-primary text-white">{{ $playlistFilterCount }}</span>
                    @endif
                </button>

                <a href="{{ $resetUrl }}"
                   class="btn btn-white my-task-btn-square my-task-btn-reset"
                   title="Reset Filter"
                   aria-label="Reset Filter">
                    <i class="ti ti-refresh fs-3 text-secondary"></i>
                </a>

                <x-learning.ongoing-export mode="playlist" />
            </div>

            {{-- Active Filters indicator --}}
            @php
                $hasActivePlaylistFilters = !empty(request('ongoing_search', request('q'))) || $curQuickStatus !== 'all' || $curYear !== 'all' || !empty($activeQuickMonths) || $playlistFilterCount > 0;
            @endphp
            @if ($hasActivePlaylistFilters)
                <div class="d-flex align-items-center flex-wrap gap-2 mt-2.5 pt-2.5 border-top">
                    <span class="text-muted small" style="font-size: 11.5px;">Active Filter:</span>
                    @if (!empty(request('ongoing_search', request('q'))))
                        <span class="badge bg-light text-dark border px-2.5 py-1 rounded-pill d-inline-flex align-items-center gap-1 shadow-none" style="font-size: 11.5px;">
                            <i class="ti ti-search text-muted"></i>
                            <span>"{{ request('ongoing_search', request('q')) }}"</span>
                        </span>
                    @endif
                    @if ($curQuickStatus !== 'all')
                        <span class="badge bg-light text-dark border px-2.5 py-1 rounded-pill d-inline-flex align-items-center gap-1 shadow-none" style="font-size: 11.5px;">
                            <span>Status: {{ $quickStatusOptions[$curQuickStatus] }}</span>
                        </span>
                    @endif
                    @if ($curYear !== 'all')
                        <span class="badge bg-light text-dark border px-2.5 py-1 rounded-pill d-inline-flex align-items-center gap-1 shadow-none" style="font-size: 11.5px;">
                            <span>Year: {{ $curYear }}</span>
                        </span>
                    @endif
                    @if (!empty($activeQuickMonths))
                        <span class="badge bg-light text-dark border px-2.5 py-1 rounded-pill d-inline-flex align-items-center gap-1 shadow-none" style="font-size: 11.5px;">
                            <span>Month: {{ $quickMonthLabel }}</span>
                        </span>
                    @endif
                    @if ($playlistFilterCount > 0)
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 rounded-pill d-inline-flex align-items-center gap-1 shadow-none" style="font-size: 11.5px;">
                            <i class="ti ti-adjustments-horizontal"></i>
                            <span>{{ $playlistFilterCount }} More Filters</span>
                        </span>
                    @endif
                    <a href="{{ $resetUrl }}" class="text-primary small text-decoration-none ms-1 fw-semibold" style="font-size: 11.5px;">
                        Reset all
                    </a>
                </div>
            @endif

            <div class="modal fade" id="ongoingPlaylistMoreFiltersModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered learning-filter-modal">
                    <div class="modal-content rounded-4 border-0 shadow">
                        <div class="modal-header border-0 pb-0">
                            <h3 class="modal-title fw-bold">
                                <i class="ti ti-adjustments-horizontal me-1 text-primary"></i>
                                All Filters
                            </h3>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body pt-2">
                            <p class="text-muted small mb-4">
                                All filters can be combined with search and quick filters above.
                            </p>

                            {{-- 1. Learning Program (single: ongoing_status) --}}
                            @php
                                $curProgram = request('ongoing_status', 'all');
                                $programOptions = [
                                    'all'       => 'All Learning',
                                    'mandatory' => 'Mandatory Learning',
                                    'optional'  => 'Optional Learning',
                                ];
                            @endphp
                            <div class="learning-filter-group">
                                <div class="fw-semibold mb-2">Learning Program</div>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach ($programOptions as $val => $label)
                                        <label class="form-check">
                                            <input class="form-check-input" type="radio"
                                                   name="ongoing_status" value="{{ $val }}"
                                                   {{ $curProgram === $val ? 'checked' : '' }}>
                                            <span class="form-check-label">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <hr class="my-3">

                            {{-- 2. Learning Progress --}}
                            <div class="learning-filter-group">
                                <div class="fw-semibold mb-2">Learning Progress</div>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach ($progressOptions as $value => $label)
                                        <label class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="ongoing_progress[]" value="{{ $value }}"
                                                   {{ in_array((string) $value, $selectedProgress, true) ? 'checked' : '' }}>
                                            <span class="form-check-label">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <hr class="my-3">

                            {{-- 3. Sort By --}}
                            @php
                                $curActivitySort = request('ongoing_activity_sort', 'newest_activity');
                            @endphp
                            <div class="learning-filter-group">
                                <label class="form-label fw-semibold mb-2" for="ongoing_playlist_activity_sort">Sort By</label>
                                <select class="form-select" name="ongoing_activity_sort" id="ongoing_playlist_activity_sort">
                                    <option value="newest_activity" {{ $curActivitySort === 'newest_activity' ? 'selected' : '' }}>Newest</option>
                                    <option value="oldest_activity" {{ $curActivitySort === 'oldest_activity' ? 'selected' : '' }}>Oldest</option>
                                    <option value="duration_longest" {{ $curActivitySort === 'duration_longest' ? 'selected' : '' }}>Duration Longest</option>
                                    <option value="duration_shortest" {{ $curActivitySort === 'duration_shortest' ? 'selected' : '' }}>Duration Shortest</option>
                                    <option value="progress_highest" {{ $curActivitySort === 'progress_highest' ? 'selected' : '' }}>Highest Progress</option>
                                    <option value="progress_lowest" {{ $curActivitySort === 'progress_lowest' ? 'selected' : '' }}>Lowest Progress</option>
                                </select>
                            </div>

                            <hr class="my-3">

                            {{-- 4. Period (Date Range) --}}
                            <div class="learning-filter-group">
                                <div class="fw-semibold mb-2">Period</div>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small text-muted mb-1" for="ongoing_playlist_date_from">From Date</label>
                                        <div class="input-icon">
                                            <span class="input-icon-addon"><i class="ti ti-calendar"></i></span>
                                            <input class="form-control" id="ongoing_playlist_date_from" name="ongoing_date_from" type="date"
                                                   value="{{ request('ongoing_date_from', request('ongoing_from_date')) }}">
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small text-muted mb-1" for="ongoing_playlist_date_to">To Date</label>
                                        <div class="input-icon">
                                            <span class="input-icon-addon"><i class="ti ti-calendar"></i></span>
                                            <input class="form-control" id="ongoing_playlist_date_to" name="ongoing_date_to" type="date"
                                                   value="{{ request('ongoing_date_to', request('ongoing_to_date')) }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-3">

                            {{-- 5. Duration --}}
                            @php
                                $selDur = (array) request('ongoing_duration', []);
                                $durationOptionsPlaylist = [
                                    'lt10'  => '< 10 Minutes',
                                    '10-30' => '10 – 30 Minutes',
                                    '30-60' => '30 – 60 Minutes',
                                    'gt60'  => '> 1 Hour',
                                ];
                            @endphp
                            <div class="learning-filter-group">
                                <div class="fw-semibold mb-2">Duration</div>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach ($durationOptionsPlaylist as $val => $label)
                                        <label class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="ongoing_duration[]" value="{{ $val }}"
                                                   {{ in_array($val, $selDur) ? 'checked' : '' }}>
                                            <span class="form-check-label">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <hr class="my-3">

                            {{-- 6. Learning Hour --}}
                            @php
                                $learningHourOptions = [
                                    'all' => 'All',
                                    'lt1' => '< 1 Hour',
                                    '1-3' => '1 – 3 Hours',
                                    'gt3' => '> 3 Hours',
                                ];
                                $curLearningHour = request('ongoing_learning_hour', 'all');
                            @endphp
                            <div class="learning-filter-group">
                                <div class="fw-semibold mb-2">Learning Hour</div>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach ($learningHourOptions as $val => $label)
                                        <label class="form-check">
                                            <input class="form-check-input" type="radio"
                                                   name="ongoing_learning_hour" value="{{ $val }}"
                                                   {{ $curLearningHour === $val ? 'checked' : '' }}>
                                            <span class="form-check-label">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Hidden Legacy Filter Sections (Preserving HTML, Blade Variables, IDs, Inputs, JS Hooks) --}}

                            <div class="learning-filter-group d-none">
                                <div class="fw-semibold mb-2">Playlist Status</div>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach ($quickStatusOptions as $value => $label)
                                        <label class="form-check">
                                            <input class="form-check-input" type="radio"
                                                   name="ongoing_quick_status" value="{{ $value }}"
                                                   {{ $curQuickStatus === $value ? 'checked' : '' }}>
                                            <span class="form-check-label">{{ $value === 'all' ? 'All Playlist' : $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <hr class="my-3 d-none">

                            <div class="learning-filter-group d-none">
                                <div class="fw-semibold mb-2">Year</div>
                                <div class="d-flex flex-wrap gap-3">
                                    <label class="form-check">
                                        <input class="form-check-input" type="radio"
                                               name="ongoing_year" value="all"
                                               {{ $curYear === 'all' ? 'checked' : '' }}>
                                        <span class="form-check-label">All Years</span>
                                    </label>
                                    @foreach ($quickYearOptions as $yearOption)
                                        <label class="form-check">
                                            <input class="form-check-input" type="radio"
                                                   name="ongoing_year" value="{{ $yearOption }}"
                                                   {{ (string) $curYear === (string) $yearOption ? 'checked' : '' }}>
                                            <span class="form-check-label">{{ $yearOption }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <hr class="my-3 d-none">

                            <div class="learning-filter-group d-none">
                                <div class="fw-semibold mb-2">Month</div>
                                <div class="row g-2">
                                    @foreach ($monthOptions as $number => $label)
                                        <div class="col-6 col-md-4 col-lg-3">
                                            <label class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                       name="ongoing_month[]" value="{{ $number }}"
                                                       {{ in_array((string) $number, $activeQuickMonths, true) ? 'checked' : '' }}>
                                                <span class="form-check-label">{{ $label }}</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer border-0 pt-0">
                            <a href="{{ $resetUrl }}" class="btn btn-outline-secondary btn-learning-outline">
                                <i class="ti ti-refresh"></i>
                                Reset
                            </a>
                            <button type="submit" class="btn btn-primary btn-learning-primary">
                                <i class="ti ti-search"></i>
                                Apply Filter
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
