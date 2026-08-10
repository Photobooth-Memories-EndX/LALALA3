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
        'ongoing'     => 'Sedang Berlangsung',
        'completed'   => 'Completed',
        'paused'      => 'Paused',
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

<div class="card border-0 shadow-sm rounded-4 mb-4 learning-filter-card ongoing-filter-card">
    <div class="card-body p-4">
        <form action="{{ route('user.learning.dashboard') }}" method="GET" id="ongoingFilterForm" class="ongoing-filter-shell">

            {{-- Quick Status ikut terkirim saat submit form/Apply Filter (state konsisten). --}}
            @if ($curQuickStatus !== 'all')
                <input type="hidden" name="ongoing_quick_status" value="{{ $curQuickStatus }}">
            @endif

            <div class="ongoing-filter-heading fast-filter-header">
                <div class="fast-filter-header-copy">
                    <h3 class="card-title fast-filter-title">Fast Filter</h3>
                    {{-- <p class="fast-filter-subtitle">Quickly filter your learning content.</p> --}}
                </div>
                <x-learning.ongoing-mode-switch mode="content" />
            </div>

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

            <div class="ongoing-fast-filter-section">
            <div class="ongoing-fast-filter-toolbar">
            <div class="learning-quick-dropdowns learning-filter-dropdown-row learning-fast-filter ongoing-quick-dropdowns">
                {{-- Dropdown Type (hanya type content: Video, Document, dll) --}}
                <div class="dropdown ongoing-filter-dropdown ongoing-filter-dropdown-type">
                    <button type="button"
                            class="btn btn-filter-chip learning-fast-filter-control ongoing-fast-filter-control dropdown-toggle {{ !empty($selTypeClean) ? 'active' : '' }}"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">
                        Type: <span class="fw-semibold">{{ $quickTypeLabel }}</span>
                    </button>
                    <div class="dropdown-menu">
                        <a href="#"
                           class="dropdown-item ongoing-quick-item {{ empty($selTypeClean) ? 'active' : '' }}"
                           data-param="ongoing_type" data-array="1" data-value="">All</a>
                        @foreach ($typeOptions as $typeOption)
                            <a href="#"
                               class="dropdown-item ongoing-quick-item {{ $selTypeClean === [(string) $typeOption] ? 'active' : '' }}"
                               data-param="ongoing_type" data-array="1" data-value="{{ $typeOption }}">
                                {{ ucwords(str_replace('_', ' ', $typeOption)) }}
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Dropdown Status --}}
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

                {{-- Dropdown Year (otomatis dari data) --}}
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

                {{-- Dropdown Month --}}
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
                <div class="input-icon">
                    <span class="input-icon-addon">
                        <i class="ti ti-search" aria-hidden="true"></i>
                    </span>
                    <input type="text"
                           name="ongoing_search"
                           id="ongoing_search"
                           value="{{ request('ongoing_search') }}"
                           class="form-control"
                           aria-label="Search classes or categories"
                           placeholder="Search classes or categories...">
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
                        data-bs-target="#ongoingMoreFiltersModal"
                        title="More Filters"
                        aria-label="More Filters">
                    <i class="ti ti-adjustments-horizontal" aria-hidden="true"></i>
                    @if ($advancedCount > 0)
                        <span class="badge bg-primary text-white ongoing-filter-count">{{ $advancedCount }}</span>
                    @endif
                </button>

                <a href="{{ route('user.learning.dashboard') }}"
                   class="btn btn-outline-secondary btn-learning-outline ongoing-filter-action-btn"
                   title="Reset Filter"
                   aria-label="Reset Filter">
                    <i class="ti ti-refresh" aria-hidden="true"></i>
                </a>
            </div>

            </div>
            </div>

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
