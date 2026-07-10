@props([
    // Daftar Playlist (Training Plan) on-going milik user, dari controller (bebas N+1).
    'playlists' => null,
])

@php
    $userId = auth()->id();
    $playlists = $playlists ?? collect();

    // Opsi Type diambil langsung dari data asli (content.type milik user).
    $typeOptions = \App\Models\Content::whereHas('contentuser', fn ($q) => $q->where('user_id', $userId))
        ->whereNotNull('type')
        ->where('type', '!=', '')
        ->distinct()
        ->orderBy('type')
        ->pluck('type');

    // "playlist" adalah pseudo-type: menampilkan card Playlist (Training Plan), bukan content.type asli.
    // Hanya ditawarkan bila user memang punya playlist yang sedang dikerjakan.
    if ($playlists->isNotEmpty() && ! $typeOptions->contains('playlist')) {
        $typeOptions = $typeOptions->push('playlist')->values();
    }

    // Nilai filter multi-select yang sedang aktif.
    $selType       = (array) request('ongoing_type', []);
    $selStatus     = (array) request('ongoing_learning_status', []);
    $selProg       = (array) request('ongoing_progress', []);
    $selDur        = (array) request('ongoing_duration', []);
    $selMonth      = (array) request('ongoing_month', []);
    $selCurrent    = (array) request('ongoing_current_time', []);
    $selLastPage   = (array) request('ongoing_last_page', []);
    $selTotalPages = (array) request('ongoing_total_pages', []);
    $selPlaylist   = array_map('strval', (array) request('ongoing_playlist', []));

    // Map id => judul playlist untuk label chip.
    $playlistTitles = $playlists->pluck('title', 'id');

    // Nilai filter single-value (dropdown lama yang kini dipindah ke dalam modal).
    $curProgram      = request('ongoing_status', 'all');
    $curYear         = request('ongoing_year', 'all');
    $curActivitySort = request('ongoing_activity_sort', 'newest_activity');
    $curProgressSort = request('ongoing_progress_sort', 'all');

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
    $advancedCount += count(array_filter($selPlaylist));

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

    $chipAllUrl = route('user.learning.dashboard')
        . (($q = collect($baseQuery)->except('ongoing_type')->all()) ? '?' . http_build_query($q) : '');

    $monthSel = array_map('strval', $selMonth);
@endphp

<div class="card border-0 shadow-sm rounded-4 mb-4 learning-filter-card">
    <div class="card-body p-4">
        <form action="{{ route('user.learning.dashboard') }}" method="GET" id="ongoingFilterForm">

            <div class="mb-3">
                <h3 class="card-title mb-1">Learning On Going</h3>
                <div class="text-muted small">
                    Your Enrolled Classes
                </div>
            </div>

            {{-- Search bar tetap di atas seperti semula --}}
            <div class="mb-3">
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

            <div class="row g-2 align-items-end">
                {{-- Quick filter chips (Type) — link toggle, tanpa JS, di bawah Search --}}
                <div class="col-12 col-lg">
                    <label class="form-label small text-muted mb-1">Filter Cepat</label>
                    <div class="learning-chip-row d-flex flex-nowrap flex-lg-wrap gap-2 pb-1">
                        <a href="{{ $chipAllUrl }}"
                           class="learning-chip {{ empty($selTypeClean) ? 'active' : '' }}">
                            All
                        </a>

                        @foreach ($typeOptions as $typeOption)
                            @php
                                $isActiveChip = in_array($typeOption, $selTypeClean);
                                $nextTypes = $isActiveChip
                                    ? array_values(array_diff($selTypeClean, [$typeOption]))
                                    : array_merge($selTypeClean, [$typeOption]);
                                $chipQuery = collect($baseQuery)->except('ongoing_type')->all();
                                if (!empty($nextTypes)) {
                                    $chipQuery['ongoing_type'] = $nextTypes;
                                }
                                $chipUrl = route('user.learning.dashboard')
                                    . (!empty($chipQuery) ? '?' . http_build_query($chipQuery) : '');
                            @endphp

                            <a href="{{ $chipUrl }}" class="learning-chip {{ $isActiveChip ? 'active' : '' }}">
                                {{ ucwords(str_replace('_', ' ', $typeOption)) }}
                            </a>
                        @endforeach

                        {{-- Chip Month aktif — bisa dihapus satu per satu tanpa reset filter lain --}}
                        @foreach (array_filter($selMonth) as $mNum)
                            @php
                                $mNum = (int) $mNum;
                                $otherMonths = array_values(array_diff(array_map('intval', array_filter($selMonth)), [$mNum]));
                                $mQuery = collect($baseQuery)->except('ongoing_month')->all();
                                if (!empty($otherMonths)) {
                                    $mQuery['ongoing_month'] = $otherMonths;
                                }
                                $mUrl = route('user.learning.dashboard')
                                    . (!empty($mQuery) ? '?' . http_build_query($mQuery) : '');
                            @endphp
                            <a href="{{ $mUrl }}" class="learning-chip active" title="Hapus filter bulan {{ $monthOptions[$mNum] ?? $mNum }}">
                                {{ $monthOptions[$mNum] ?? $mNum }}
                                <i class="ti ti-x ms-1"></i>
                            </a>
                        @endforeach

                        {{-- Chip Year aktif --}}
                        @if ($curYear !== 'all')
                            @php
                                $yQuery = collect($baseQuery)->except('ongoing_year')->all();
                                $yUrl = route('user.learning.dashboard')
                                    . (!empty($yQuery) ? '?' . http_build_query($yQuery) : '');
                            @endphp
                            <a href="{{ $yUrl }}" class="learning-chip active" title="Hapus filter tahun {{ $curYear }}">
                                {{ $curYear }}
                                <i class="ti ti-x ms-1"></i>
                            </a>
                        @endif

                        {{-- Chip Playlist aktif — bisa dihapus satu per satu tanpa reset filter lain --}}
                        @foreach (array_filter($selPlaylist) as $plId)
                            @php
                                $otherPlaylists = array_values(array_diff($selPlaylist, [(string) $plId]));
                                $plQuery = collect($baseQuery)->except('ongoing_playlist')->all();
                                if (!empty($otherPlaylists)) {
                                    $plQuery['ongoing_playlist'] = $otherPlaylists;
                                }
                                $plChipUrl = route('user.learning.dashboard')
                                    . (!empty($plQuery) ? '?' . http_build_query($plQuery) : '');
                                $plLabel = $playlistTitles[$plId] ?? ('Playlist #' . $plId);
                            @endphp
                            <a href="{{ $plChipUrl }}" class="learning-chip active" title="Hapus filter playlist {{ $plLabel }}">
                                <i class="ti ti-playlist me-1"></i>{{ $plLabel }}
                                <i class="ti ti-x ms-1"></i>
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Aksi: More Filters, Filter, Reset --}}
                <div class="col-12 col-lg-auto">
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

                            {{-- Playlist (multi: ongoing_playlist[]) — hanya playlist yang sedang dikerjakan --}}
                            @if ($playlists->isNotEmpty())
                                <div class="learning-filter-group">
                                    <div class="fw-semibold mb-2">Playlist</div>
                                    <div class="d-flex flex-wrap gap-3">
                                        @foreach ($playlists as $playlist)
                                            <label class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                       name="ongoing_playlist[]" value="{{ $playlist->id }}"
                                                       {{ in_array((string) $playlist->id, $selPlaylist, true) ? 'checked' : '' }}>
                                                <span class="form-check-label">{{ $playlist->title }}</span>
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
                                    @foreach (range(date('Y'), 2020) as $yearOption)
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

{{-- Style ditaruh inline (bukan @push) karena komponen ini selalu dirender via AJAX,
     sehingga @stack di layout utama tidak ikut ter-render. Tag <style> tetap berlaku
     saat di-inject melalui innerHTML. --}}
<style>
    .learning-chip-row {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
    }
    .learning-chip-row::-webkit-scrollbar { height: 4px; }
    .learning-chip {
        display: inline-flex;
        align-items: center;
        white-space: nowrap;
        cursor: pointer;
        padding: .375rem .875rem;
        border-radius: 999px;
        border: 1px solid var(--tblr-border-color, #dbe0e6);
        background: #fff;
        color: var(--tblr-body-color, #4b5563);
        font-size: .8125rem;
        font-weight: 500;
        margin-bottom: 0;
        text-decoration: none;
        transition: all .15s ease-in-out;
        user-select: none;
    }
    .learning-chip:hover {
        border-color: var(--tblr-primary, #206bc4);
        color: var(--tblr-primary, #206bc4);
    }
    .learning-chip.active {
        background: var(--tblr-primary, #206bc4);
        border-color: var(--tblr-primary, #206bc4);
        color: #fff;
    }
    .learning-filter-group { margin-bottom: 0; }
    .learning-filter-modal .form-check { margin-bottom: 0; }
    @media (max-width: 575.98px) {
        /* Mobile: modal muncul dari bawah seperti bottom-sheet Tokopedia. */
        .learning-filter-modal {
            margin: 0;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            max-width: 100%;
        }
        .learning-filter-modal .modal-content {
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
            max-height: 88vh;
        }
    }
</style>
