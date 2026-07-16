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
        'ongoing'     => 'Ongoing',
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

    // Hitung jumlah filter aktif di dalam modal (Type tidak dihitung karena tampil sbg chip).
    $advancedCount = 0;
    $advancedCount += $curProgram !== 'all' ? 1 : 0;
    $advancedCount += $curYear !== 'all' ? 1 : 0;
    $advancedCount += $curActivitySort !== 'newest_activity' ? 1 : 0;
    $advancedCount += $curProgressSort !== 'all' ? 1 : 0;
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

<div class="card border-0 shadow-sm rounded-4 mb-4 learning-filter-card">
    <div class="card-body p-4">
        <form action="{{ route('user.learning.dashboard') }}" method="GET" id="ongoingFilterForm">

            {{-- Quick Status ikut terkirim saat submit form/Apply Filter (state konsisten). --}}
            @if ($curQuickStatus !== 'all')
                <input type="hidden" name="ongoing_quick_status" value="{{ $curQuickStatus }}">
            @endif

            <div class="row g-3 align-items-start mb-3 learning-filter-header">
                <div class="col-12 col-lg-6">
                    <h3 class="card-title mb-1">Learning On Going</h3>
                    <div class="text-muted small">
                        Your Enrolled Classes
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label small text-muted mb-1" for="ongoing_search">Search</label>
                    <div class="input-icon">
                        <span class="input-icon-addon">
                            <i class="ti ti-search"></i>
                        </span>
                        <input type="text"
                               name="ongoing_search"
                               id="ongoing_search"
                               value="{{ request('ongoing_search') }}"
                               class="form-control rounded-3"
                               placeholder="Search classes or categories">
                    </div>
                </div>
            </div>

            <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-lg-between gap-3 mb-3 learning-filter-mode-row">
                {{-- Mode switch: Content (aktif) | Playlist --}}
                <x-learning.ongoing-mode-switch mode="content" />

                <div class="learning-filter-mode-actions">
                    <div class="d-flex gap-2 learning-filter-actions justify-content-lg-end">
                        <button type="button"
                                class="btn btn-outline-primary rounded-3 flex-fill flex-lg-grow-0 position-relative"
                                data-bs-toggle="modal"
                                data-bs-target="#ongoingMoreFiltersModal">
                            <i class="ti ti-adjustments-horizontal me-1"></i>
                            More Filters
                            @if ($advancedCount > 0)
                                <span class="badge bg-primary text-white ms-1">{{ $advancedCount }}</span>
                            @endif
                        </button>

                        <button type="submit" class="btn btn-primary rounded-3 flex-fill flex-lg-grow-0">
                            <i class="ti ti-filter me-1"></i>
                            Filter
                        </button>

                        <a href="{{ route('user.learning.dashboard') }}"
                           class="btn btn-outline-secondary rounded-3 flex-fill flex-lg-grow-0">
                            <i class="ti ti-refresh me-1"></i>
                            Reset
                        </a>
                    </div>
                </div>
            </div>

            {{-- ── Quick dropdown filters (Type / Status / Year / Month) ──────────────
                 Pilihan langsung diterapkan via AJAX (dashboard-script.blade.php),
                 memakai param & endpoint yang sama dengan filter lain. --}}
            @php
                $quickTypeLabel = empty($selTypeClean)
                    ? null
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

            <div class="d-flex flex-wrap flex-lg-nowrap gap-2 learning-quick-dropdowns learning-filter-dropdown-row">
                {{-- Dropdown Type (hanya type content: Video, Document, dll) --}}
                <div class="dropdown">
                    <button type="button"
                            class="btn btn-outline-secondary rounded-3 dropdown-toggle {{ !empty($selTypeClean) ? 'active' : '' }}"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">
                        Type{!! $quickTypeLabel ? ': <span class="fw-semibold">' . e($quickTypeLabel) . '</span>' : '' !!}
                    </button>
                    <div class="dropdown-menu">
                        {{-- Klik item aktif = batalkan pilihan (toggle) — tanpa opsi "All". --}}
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
                <div class="dropdown">
                    <button type="button"
                            class="btn btn-outline-secondary rounded-3 dropdown-toggle {{ $curQuickStatus !== 'all' ? 'active' : '' }}"
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
                <div class="dropdown">
                    <button type="button"
                            class="btn btn-outline-secondary rounded-3 dropdown-toggle {{ $curYear !== 'all' ? 'active' : '' }}"
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
                <div class="dropdown">
                    <button type="button"
                            class="btn btn-outline-secondary rounded-3 dropdown-toggle {{ !empty($activeQuickMonths) ? 'active' : '' }}"
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
                                Semua filter dapat dikombinasikan dengan pencarian &amp; chip type di atas.
                            </p>

                            {{-- Type (juga tersedia sebagai chip di atas) --}}
                            @if ($typeOptions->isNotEmpty())
                                <div class="learning-filter-group">
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
                                <hr class="my-3">
                            @endif

                            {{-- Learning Program (single: ongoing_status) --}}
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

                            {{-- Year (single: ongoing_year, otomatis dari database) --}}
                            <div class="learning-filter-group">
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

                            <hr class="my-3">

                            {{-- Activity Sort (single: ongoing_activity_sort) --}}
                            <div class="learning-filter-group">
                                <div class="fw-semibold mb-2">Activity Sort</div>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach ($activitySortOptions as $val => $label)
                                        <label class="form-check">
                                            <input class="form-check-input" type="radio"
                                                   name="ongoing_activity_sort" value="{{ $val }}"
                                                   {{ $curActivitySort === $val ? 'checked' : '' }}>
                                            <span class="form-check-label">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <hr class="my-3">

                            {{-- Completion Status / Progress (single: ongoing_progress_sort) --}}
                            <div class="learning-filter-group">
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

                            <hr class="my-3">

                            {{-- Learning Status (multi: ongoing_learning_status[]) --}}
                            <div class="learning-filter-group">
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

                            <hr class="my-3">

                            {{-- Progress (multi: ongoing_progress[]) --}}
                            <div class="learning-filter-group">
                                <div class="fw-semibold mb-2">Progress</div>
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

                            {{-- Month (multi: ongoing_month[]) --}}
                            <div class="learning-filter-group">
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

                            <hr class="my-3">

                            {{-- Duration (multi: ongoing_duration[]) --}}
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

                            {{-- Current Learning Time (multi: ongoing_current_time[]) --}}
                            <div class="learning-filter-group">
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

                            <hr class="my-3">

                            {{-- Last Page (multi: ongoing_last_page[]) --}}
                            <div class="learning-filter-group">
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

                            <hr class="my-3">

                            {{-- Total Pages (multi: ongoing_total_pages[]) --}}
                            <div class="learning-filter-group">
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
                               class="btn btn-outline-secondary rounded-3">
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
