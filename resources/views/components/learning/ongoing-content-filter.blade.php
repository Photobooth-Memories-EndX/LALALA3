@php
    $userId = auth()->id();

    // Opsi Type diambil langsung dari data asli (content.type milik user) — hanya
    // type content (Video, Document, dll); tidak ada unsur Playlist di mode ini.
    $typeOptions = \App\Models\Content::whereHas('contentuser', fn ($q) => $q->where('user_id', $userId))
        ->whereNotNull('type')
        ->where('type', '!=', '')
        ->distinct()
        ->orderBy('type')
        ->pluck('type');

    // Nilai filter multi-select yang sedang aktif.
    $selType       = (array) request('ongoing_type', []);
    $selStatus     = (array) request('ongoing_learning_status', []);
    $selProg       = (array) request('ongoing_progress', []);
    $selDur        = (array) request('ongoing_duration', []);
    $selMonth      = (array) request('ongoing_month', []);
    $selCurrent    = (array) request('ongoing_current_time', []);
    $selLastPage   = (array) request('ongoing_last_page', []);
    $selTotalPages = (array) request('ongoing_total_pages', []);

    // Nilai filter single-value (dipilih di dalam modal All Filters).
    $curProgram      = request('ongoing_status', 'all');
    $curYear         = request('ongoing_year', 'all');
    $curActivitySort = request('ongoing_activity_sort', 'newest_activity');
    $curProgressSort = request('ongoing_progress_sort', 'all');

    // ── Quick dropdown filters (Type / Status / Year / Month) ───────────────────
    // Status cepat: all|not_started|ongoing|completed|paused (param ongoing_quick_status).
    $quickStatusOptions = [
        'all'         => 'All',
        'not_started' => 'Not Started',
        'ongoing'     => 'On Going',
        'completed'   => 'Completed',
        // 'paused'      => 'Paused',
    ];
    $curQuickStatus = (string) request('ongoing_quick_status', 'all');
    if (! array_key_exists($curQuickStatus, $quickStatusOptions)) {
        $curQuickStatus = 'all';
    }

    // Opsi Year otomatis dari data learning user — tidak hardcode.
    $quickYearOptions = \App\Models\ContentUser::where('user_id', $userId)
        ->selectRaw('DISTINCT YEAR(created_at) as learning_year')
        ->orderByDesc('learning_year')
        ->pluck('learning_year')
        ->filter()
        ->values();

    $curLearningHour = request('ongoing_learning_hour', 'all');
    $curDateFrom     = request('ongoing_date_from', request('ongoing_from_date'));
    $curDateTo       = request('ongoing_date_to', request('ongoing_to_date'));

    // Hitung jumlah filter aktif di dalam modal.
    $advancedCount = 0;
    $advancedCount += $curProgram !== 'all' ? 1 : 0;
    $advancedCount += $curYear !== 'all' ? 1 : 0;
    $advancedCount += $curActivitySort !== 'newest_activity' ? 1 : 0;
    $advancedCount += $curProgressSort !== 'all' ? 1 : 0;
    $advancedCount += $curLearningHour !== 'all' ? 1 : 0;
    $advancedCount += filled($curDateFrom) ? 1 : 0;
    $advancedCount += filled($curDateTo) ? 1 : 0;
    $advancedCount += count(array_filter($selStatus));
    $advancedCount += count(array_filter($selProg));
    $advancedCount += count(array_filter($selDur));
    $advancedCount += count(array_filter($selMonth));
    $advancedCount += count(array_filter($selCurrent));
    $advancedCount += count(array_filter($selLastPage));
    $advancedCount += count(array_filter($selTotalPages));

    // ── Opsi filter ─────────────────────────────────────────────────────────────
    $programOptions = [
        'all'       => 'All Learning',
        'mandatory' => 'Mandatory Learning',
        'optional'  => 'Optional Learning',
    ];

    $activitySortOptions = [
        'newest_activity'   => 'Newest Activity',
        'oldest_activity'   => 'Oldest Activity',
        'recently_accessed' => 'Recently Accessed',
        'duration_longest'  => 'Longest Duration',
        'duration_shortest' => 'Shortest Duration',
    ];

    $progressSortOptions = [
        'all'              => 'All Progress',
        'progress_highest' => 'Highest Progress',
        'progress_lowest'  => 'Lowest Progress',
        'not_started'      => 'Not Started 0%',
        'in_progress'      => 'In Progress 1% - 99%',
        'almost_done'      => 'Almost Done 80% - 99%',
        'completed'        => 'Completed',
    ];

    $learningStatusOptions = [
        'not_started' => 'Not Started',
        'in_progress' => 'Sedang Berlangsung',
        'paused'      => 'Paused',
        'completed'   => 'Completed',
    ];

    $progressOptions = [
        '0'     => '0%',
        '1-25'  => '1 - 25%',
        '26-50' => '26 - 50%',
        '51-75' => '51 - 75%',
        '76-99' => '76 - 99%',
        '100'   => '100%',
    ];

    $durationOptions = [
        'lt10'  => '< 10 Menit',
        '10-30' => '10 - 30 Menit',
        '30-60' => '30 - 60 Menit',
        'gt60'  => '> 1 Jam',
    ];

    $currentTimeOptions = [
        'start'  => 'Baru Dimulai',
        'middle' => 'Sedang Belajar',
        'almost' => 'Hampir Selesai',
    ];

    $lastPageOptions = [
        '1-10'  => 'Page 1 - 10',
        '11-20' => 'Page 11 - 20',
        '21-50' => 'Page 21 - 50',
        'gt50'  => '> 50',
    ];

    $totalPagesOptions = [
        'lt10'  => '< 10',
        '10-20' => '10 - 20',
        '21-50' => '21 - 50',
        'gt50'  => '> 50',
    ];

    $monthOptions = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];

    // Query saat ini dipakai untuk membangun URL chip (toggle) tanpa JavaScript,
    // sekaligus mempertahankan semua filter/pencarian lain yang aktif.
    $baseQuery    = request()->except('ongoing_page');
    $selTypeClean = array_values(array_filter($selType));

    $monthSel = array_map('strval', $selMonth);
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
                    class="my-task-category-pill ongoing-mode-btn active"
                    data-mode="content"
                    title="Filter by Content">
                <i class="ti ti-file-text me-1"></i> Content
            </button>
            <button type="button"
                    class="my-task-category-pill ongoing-mode-btn"
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
            <button type="button"
                    class="my-task-category-pill ongoing-mode-btn"
                    data-mode="non-kp-owl"
                    title="Filter by Non KP OWL">
                <i class="ti ti-certificate me-1"></i> Non KP OWL
            </button>
        </div>

        {{-- Subtle Divider Line --}}
        <hr class="my-3 my-task-divider">

        {{-- ── Quick dropdown filters (Type / Status / Year / Month) ──────────────
             Pilihan langsung diterapkan via AJAX (dashboard-script.blade.php),
             memakai param & endpoint yang sama dengan filter lain. --}}
        @php
            $quickTypeLabel = empty($selTypeClean)
                ? 'All'
                : (count($selTypeClean) === 1
                    ? ucwords(str_replace('_', ' ', $selTypeClean[0]))
                    : count($selTypeClean) . ' Types');

            $activeQuickMonths = array_values(array_filter($selMonth));
            $quickMonthLabel = empty($activeQuickMonths)
                ? 'All'
                : (count($activeQuickMonths) === 1
                    ? ($monthOptions[(int) $activeQuickMonths[0]] ?? $activeQuickMonths[0])
                    : count($activeQuickMonths) . ' Months');
        @endphp

        <form action="{{ route('user.learning.dashboard') }}" method="GET" id="ongoingFilterForm" class="ongoing-filter-shell">
            {{-- Quick Status ikut terkirim saat submit form/Apply Filter (state konsisten). --}}
            @if ($curQuickStatus !== 'all')
                <input type="hidden" name="ongoing_quick_status" value="{{ $curQuickStatus }}">
            @endif

            {{-- Fast Filter Toolbar --}}
            <div class="d-flex align-items-center flex-wrap gap-2 w-100 my-task-toolbar">
                {{-- 1. Dropdown Type (hanya type content: Video, Document, dll) --}}
                <div class="dropdown">
                    <button type="button"
                            class="btn my-task-dropdown-btn dropdown-toggle {{ !empty($selTypeClean) ? 'active' : '' }}"
                            data-bs-toggle="dropdown"
                            data-bs-display="static"
                            aria-expanded="false">
                        Type: <span class="fw-semibold">{{ $quickTypeLabel }}</span>
                    </button>
                    <div class="dropdown-menu shadow-sm">
                        <a href="#"
                           class="dropdown-item my-task-dropdown-item ongoing-quick-item {{ empty($selTypeClean) ? 'active' : '' }}"
                           data-param="ongoing_type" data-array="1" data-value="">All</a>
                        @foreach ($typeOptions as $typeOption)
                            <a href="#"
                               class="dropdown-item my-task-dropdown-item ongoing-quick-item {{ $selTypeClean === [(string) $typeOption] ? 'active' : '' }}"
                               data-param="ongoing_type" data-array="1" data-value="{{ $typeOption }}">
                                {{ ucwords(str_replace('_', ' ', $typeOption)) }}
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- 2. Dropdown Status --}}
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

                {{-- 3. Dropdown Year (otomatis dari data) --}}
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

                {{-- 4. Dropdown Month --}}
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

                {{-- 5. Search input with live autocomplete dropdown --}}
                <div class="position-relative flex-grow-1 my-task-search-wrapper search-ongoing-container" style="min-width: 220px;">
                    <span class="position-absolute top-50 start-0 translate-middle-y ps-3 text-muted pointer-events-none" style="z-index: 2;">
                        <i class="ti ti-search" style="font-size: 15px;"></i>
                    </span>
                    <input type="text"
                           name="ongoing_search"
                           id="ongoing-search-input"
                           value="{{ request('ongoing_search', request('q')) }}"
                           class="form-control my-task-search-input module-search-input"
                           aria-label="Search classes or categories"
                           placeholder="Search classes or categories..."
                           autocomplete="off">
                    @if (request()->filled('ongoing_search') || request()->filled('q'))
                        <a href="{{ route('user.learning.dashboard') }}" class="btn btn-link position-absolute top-50 end-0 translate-middle-y pe-2.5 text-muted text-decoration-none my-task-search-clear" title="Clear search" style="z-index: 2;">
                            <i class="ti ti-x"></i>
                        </a>
                    @endif
                    <div id="ongoing-search-results" class="dropdown-menu shadow-sm w-100 position-absolute mt-1 module-search-dropdown" style="display: none; max-height: 380px; overflow-y: auto; z-index: 1050; padding: 0;">
                        <div class="autocomplete-list-group list-group list-group-flush"></div>
                        <div class="search-footer border-top bg-light p-2 text-center sticky-bottom"></div>
                    </div>
                </div>

                {{-- 6. Square Action Buttons --}}
                <button type="submit"
                        class="btn btn-primary my-task-btn-square my-task-btn-search"
                        title="Apply Filter"
                        aria-label="Apply Filter">
                    <i class="ti ti-search text-white fs-3"></i>
                </button>

                <button type="button"
                        class="btn btn-white my-task-btn-square my-task-btn-filter position-relative"
                        data-bs-toggle="modal"
                        data-bs-target="#ongoingMoreFiltersModal"
                        title="More Filters"
                        aria-label="More Filters">
                    <i class="ti ti-adjustments-horizontal fs-3 text-secondary"></i>
                    @if ($advancedCount > 0)
                        <span class="badge bg-primary text-white">{{ $advancedCount }}</span>
                    @endif
                </button>

                <a href="{{ route('user.learning.dashboard') }}"
                   class="btn btn-white my-task-btn-square my-task-btn-reset"
                   title="Reset Filter"
                   aria-label="Reset Filter">
                    <i class="ti ti-refresh fs-3 text-secondary"></i>
                </a>

                <x-learning.ongoing-export mode="{{ request('ongoing_mode') === 'mooc' ? 'mooc' : 'content' }}" />
            </div>

            {{-- Active Filters indicator --}}
            @php
                $hasActiveOngoingFilters = !empty(request('ongoing_search', request('q'))) || !empty($selTypeClean) || $curQuickStatus !== 'all' || $curYear !== 'all' || !empty($activeQuickMonths) || $advancedCount > 0;
            @endphp
            @if ($hasActiveOngoingFilters)
                <div class="d-flex align-items-center flex-wrap gap-2 mt-2.5 pt-2.5 border-top">
                    <span class="text-muted small" style="font-size: 11.5px;">Active Filter:</span>
                    @if (!empty(request('ongoing_search', request('q'))))
                        <span class="badge bg-light text-dark border px-2.5 py-1 rounded-pill d-inline-flex align-items-center gap-1 shadow-none" style="font-size: 11.5px;">
                            <i class="ti ti-search text-muted"></i>
                            <span>"{{ request('ongoing_search', request('q')) }}"</span>
                        </span>
                    @endif
                    @if (!empty($selTypeClean))
                        <span class="badge bg-light text-dark border px-2.5 py-1 rounded-pill d-inline-flex align-items-center gap-1 shadow-none" style="font-size: 11.5px;">
                            <span>Type: {{ $quickTypeLabel }}</span>
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
                    @if ($advancedCount > 0)
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 rounded-pill d-inline-flex align-items-center gap-1 shadow-none" style="font-size: 11.5px;">
                            <i class="ti ti-adjustments-horizontal"></i>
                            <span>{{ $advancedCount }} More Filters</span>
                        </span>
                    @endif
                    <a href="{{ route('user.learning.dashboard') }}" class="text-primary small text-decoration-none ms-1 fw-semibold" style="font-size: 11.5px;">
                        Reset all
                    </a>
                </div>
            @endif

            {{-- ── Modal "All Filters" (Tokopedia bottom-sheet di mobile) ───────────────── --}}
            <div class="modal fade" id="ongoingMoreFiltersModal" tabindex="-1" aria-hidden="true">
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
                                All filters can be combined with the search. &amp; the filter chip above.
                            </p>

                            {{-- Type (juga tersedia sebagai chip di atas) --}}
                            @if ($typeOptions->isNotEmpty())
                                <div class="learning-filter-group d-none">
                                    <div class="fw-semibold mb-2">Type</div>
                                    <div class="d-flex flex-wrap gap-3">
                                        @foreach ($typeOptions as $typeOption)
                                            <label class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                       name="ongoing_type[]" value="{{ $typeOption }}"
                                                       {{ in_array($typeOption, $selType) ? 'checked' : '' }}>
                                                <span class="form-check-label">{{ ucwords(str_replace('_', ' ', $typeOption)) }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                <hr class="my-3 d-none">
                            @endif

                            {{-- 1. Learning Program (single: ongoing_status) --}}
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

                            {{-- 2. Learning Progress (multi: ongoing_progress[]) --}}
                            <div class="learning-filter-group">
                                <div class="fw-semibold mb-2">Learning Progress</div>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach ($progressOptions as $val => $label)
                                        <label class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="ongoing_progress[]" value="{{ $val }}"
                                                   {{ in_array($val, $selProg) ? 'checked' : '' }}>
                                            <span class="form-check-label">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <hr class="my-3">

                            {{-- 3. Sort By (single: ongoing_activity_sort) --}}
                            <div class="learning-filter-group">
                                <label class="form-label fw-semibold mb-2" for="ongoing_activity_sort">Sort By</label>
                                <select class="form-select" name="ongoing_activity_sort" id="ongoing_activity_sort">
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
                                        <label class="form-label small text-muted mb-1" for="ongoing_date_from">From Date</label>
                                        <div class="input-icon">
                                            <span class="input-icon-addon"><i class="ti ti-calendar"></i></span>
                                            <input class="form-control" id="ongoing_date_from" name="ongoing_date_from" type="date"
                                                   value="{{ request('ongoing_date_from', request('ongoing_from_date')) }}">
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small text-muted mb-1" for="ongoing_date_to">To Date</label>
                                        <div class="input-icon">
                                            <span class="input-icon-addon"><i class="ti ti-calendar"></i></span>
                                            <input class="form-control" id="ongoing_date_to" name="ongoing_date_to" type="date"
                                                   value="{{ request('ongoing_date_to', request('ongoing_to_date')) }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-3">

                            {{-- 5. Duration (multi: ongoing_duration[]) --}}
                            <div class="learning-filter-group">
                                <div class="fw-semibold mb-2">Duration</div>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach ($durationOptions as $val => $label)
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

                            {{-- 6. Learning Hour (single: ongoing_learning_hour) --}}
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

                            {{-- Year --}}
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

                            {{-- Completion Status / Progress --}}
                            <div class="learning-filter-group d-none">
                                <div class="fw-semibold mb-2">Completion Status / Progress</div>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach ($progressSortOptions as $val => $label)
                                        <label class="form-check">
                                            <input class="form-check-input" type="radio"
                                                   name="ongoing_progress_sort" value="{{ $val }}"
                                                   {{ $curProgressSort === $val ? 'checked' : '' }}>
                                            <span class="form-check-label">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <hr class="my-3 d-none">

                            {{-- Learning Status --}}
                            <div class="learning-filter-group d-none">
                                <div class="fw-semibold mb-2">Learning Status</div>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach ($learningStatusOptions as $val => $label)
                                        <label class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="ongoing_learning_status[]" value="{{ $val }}"
                                                   {{ in_array($val, $selStatus) ? 'checked' : '' }}>
                                            <span class="form-check-label">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <hr class="my-3 d-none">

                            {{-- Month --}}
                            <div class="learning-filter-group d-none">
                                <div class="fw-semibold mb-2">Month</div>
                                <div class="row g-2">
                                    @foreach ($monthOptions as $num => $label)
                                        <div class="col-6 col-md-4 col-lg-3">
                                            <label class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                       name="ongoing_month[]" value="{{ $num }}"
                                                       {{ in_array((string) $num, $monthSel) ? 'checked' : '' }}>
                                                <span class="form-check-label">{{ $label }}</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <hr class="my-3 d-none">

                            {{-- Current Learning Time --}}
                            <div class="learning-filter-group d-none">
                                <div class="fw-semibold mb-2">Current Learning Time</div>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach ($currentTimeOptions as $val => $label)
                                        <label class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="ongoing_current_time[]" value="{{ $val }}"
                                                   {{ in_array($val, $selCurrent) ? 'checked' : '' }}>
                                            <span class="form-check-label">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <hr class="my-3 d-none">

                            {{-- Last Page --}}
                            <div class="learning-filter-group d-none">
                                <div class="fw-semibold mb-2">Last Page</div>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach ($lastPageOptions as $val => $label)
                                        <label class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="ongoing_last_page[]" value="{{ $val }}"
                                                   {{ in_array($val, $selLastPage) ? 'checked' : '' }}>
                                            <span class="form-check-label">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <hr class="my-3 d-none">

                            {{-- Total Pages --}}
                            <div class="learning-filter-group d-none">
                                <div class="fw-semibold mb-2">Total Pages</div>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach ($totalPagesOptions as $val => $label)
                                        <label class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="ongoing_total_pages[]" value="{{ $val }}"
                                                   {{ in_array($val, $selTotalPages) ? 'checked' : '' }}>
                                            <span class="form-check-label">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer border-0 pt-0">
                            <a href="{{ route('user.learning.dashboard') }}"
                               class="btn btn-outline-secondary btn-learning-outline">
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
