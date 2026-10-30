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

<div class="card border-0 shadow-sm rounded-4 mb-4 learning-filter-card ongoing-filter-card">
    <div class="card-body p-4">
        <form action="{{ route('user.learning.dashboard') }}" method="GET" id="ongoingPlaylistFilterForm" class="ongoing-filter-shell">
            <input type="hidden" name="ongoing_mode" value="playlist">

            <div class="ongoing-filter-heading fast-filter-header">
                <div class="fast-filter-header-copy">
                    <h3 class="card-title fast-filter-title">Quick Filter</h3>
                    {{-- <p class="fast-filter-subtitle">Quickly filter your learning playlists.</p> --}}
                </div>
                <x-learning.ongoing-mode-switch mode="playlist" :show-download="false" />
            </div>

            {{-- Playlist tidak memiliki filter Type; filter lainnya tersedia di toolbar dan modal. --}}
            <div class="ongoing-fast-filter-section">
                <div class="ongoing-fast-filter-toolbar">
                    <div class="learning-quick-dropdowns learning-filter-dropdown-row learning-fast-filter ongoing-quick-dropdowns">
                        <div class="dropdown ongoing-filter-dropdown ongoing-filter-dropdown-status">
                            <button type="button"
                                    class="btn btn-filter-chip learning-fast-filter-control ongoing-fast-filter-control dropdown-toggle {{ $curQuickStatus !== 'all' ? 'active' : '' }}"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                Status: <span class="fw-semibold">{{ $quickStatusOptions[$curQuickStatus] }}</span>
                            </button>
                            <div class="dropdown-menu">
                                @foreach ($quickStatusOptions as $val => $label)
                                    <a href="#"
                                       class="dropdown-item ongoing-quick-item {{ $curQuickStatus === $val ? 'active' : '' }}"
                                       data-param="ongoing_quick_status" data-value="{{ $val }}">
                                        {{ $label }}
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        <div class="dropdown ongoing-filter-dropdown ongoing-filter-dropdown-year">
                            <button type="button"
                                    class="btn btn-filter-chip learning-fast-filter-control ongoing-fast-filter-control dropdown-toggle {{ $curYear !== 'all' ? 'active' : '' }}"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                Year: <span class="fw-semibold">{{ $curYear === 'all' ? 'All' : $curYear }}</span>
                            </button>
                            <div class="dropdown-menu">
                                <a href="#" class="dropdown-item ongoing-quick-item {{ $curYear === 'all' ? 'active' : '' }}"
                                   data-param="ongoing_year" data-value="all">All</a>
                                @foreach ($quickYearOptions as $yearOption)
                                    <a href="#"
                                       class="dropdown-item ongoing-quick-item {{ (string) $curYear === (string) $yearOption ? 'active' : '' }}"
                                       data-param="ongoing_year" data-value="{{ $yearOption }}">
                                        {{ $yearOption }}
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        <div class="dropdown ongoing-filter-dropdown ongoing-filter-dropdown-month">
                            <button type="button"
                                    class="btn btn-filter-chip learning-fast-filter-control ongoing-fast-filter-control dropdown-toggle {{ !empty($activeQuickMonths) ? 'active' : '' }}"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                Month: <span class="fw-semibold">{{ $quickMonthLabel }}</span>
                            </button>
                            <div class="dropdown-menu learning-quick-dropdown-scroll">
                                <a href="#" class="dropdown-item ongoing-quick-item {{ empty($activeQuickMonths) ? 'active' : '' }}"
                                   data-param="ongoing_month" data-array="1" data-value="">All</a>
                                @foreach ($monthOptions as $num => $label)
                                    <a href="#"
                                       class="dropdown-item ongoing-quick-item {{ $activeQuickMonths === [(string) $num] ? 'active' : '' }}"
                                       data-param="ongoing_month" data-array="1" data-value="{{ $num }}">
                                        {{ $label }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="ongoing-toolbar-search">
                        <div class="position-relative search-input-wrapper search-ongoing-container flex-grow-1 w-100">
                            <div class="input-icon">
                                <span class="input-icon-addon">
                                    <i class="ti ti-search" aria-hidden="true"></i>
                                </span>
                                <input type="text"
                                       name="ongoing_search"
                                       id="ongoing-search-input"
                                       value="{{ request('ongoing_search', request('q')) }}"
                                       class="form-control module-search-input"
                                       aria-label="Search playlists"
                                       placeholder="Search playlists..."
                                       autocomplete="off">
                            </div>
                            <div id="ongoing-search-results" class="dropdown-menu shadow-sm w-100 position-absolute mt-1 module-search-dropdown" style="display: none; max-height: 380px; overflow-y: auto; z-index: 1050; padding: 0;">
                                <div class="autocomplete-list-group list-group list-group-flush"></div>
                                <div class="search-footer border-top bg-light p-2 text-center sticky-bottom"></div>
                            </div>
                        </div>
                    </div>

                    <div class="ongoing-filter-actions" role="group" aria-label="Filter actions">
                        <button type="submit"
                                class="btn btn-primary btn-learning-primary ongoing-filter-action-btn"
                                title="Apply Filter"
                                aria-label="Apply Filter">
                            <i class="ti ti-search" aria-hidden="true"></i>
                        </button>

                        <button type="button"
                                class="btn btn-outline-primary btn-learning-secondary ongoing-filter-action-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#ongoingPlaylistMoreFiltersModal"
                                title="More Filters"
                                aria-label="More Filters">
                            <i class="ti ti-adjustments-horizontal" aria-hidden="true"></i>
                            @if ($playlistFilterCount > 0)
                                <span class="badge bg-primary text-white ongoing-filter-count">{{ $playlistFilterCount }}</span>
                            @endif
                        </button>

                        <a href="{{ $resetUrl }}"
                           class="btn btn-outline-secondary btn-learning-outline ongoing-filter-action-btn"
                           title="Reset Filter"
                           aria-label="Reset Filter">
                            <i class="ti ti-refresh" aria-hidden="true"></i>
                        </a>
                    </div>

                </div>
            </div>

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
