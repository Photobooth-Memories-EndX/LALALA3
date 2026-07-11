@php
    $userId = auth()->id();

    // Status playlist diturunkan dari progress module — tidak ada "Paused" di playlist.
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

    // Opsi Year otomatis dari data aktivitas learning user — tidak hardcode.
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

    $activeQuickMonths = array_values(array_filter((array) request('ongoing_month', [])));
    $quickMonthLabel = empty($activeQuickMonths)
        ? 'All'
        : (count($activeQuickMonths) === 1
            ? ($monthOptions[(int) $activeQuickMonths[0]] ?? $activeQuickMonths[0])
            : count($activeQuickMonths) . ' Months');

    // Reset mode playlist: bersihkan seluruh filter tetapi tetap di mode playlist.
    $resetUrl = route('user.learning.dashboard') . '?' . http_build_query(['ongoing_mode' => 'playlist']);
@endphp

<div class="card border-0 shadow-sm rounded-4 mb-4 learning-filter-card">
    <div class="card-body p-4">
        <form action="{{ route('user.learning.dashboard') }}" method="GET" id="ongoingPlaylistFilterForm">

            {{-- Mode & quick status ikut terkirim saat submit form (state konsisten). --}}
            <input type="hidden" name="ongoing_mode" value="playlist">
            @if ($curQuickStatus !== 'all')
                <input type="hidden" name="ongoing_quick_status" value="{{ $curQuickStatus }}">
            @endif

            <div class="mb-3">
                <h3 class="card-title mb-1">Learning On Going</h3>
                <div class="text-muted small">
                    Your Enrolled Playlists
                </div>
            </div>

            {{-- Mode switch: Content | Playlist (aktif) --}}
            <x-learning.ongoing-mode-switch mode="playlist" />

            {{-- Search bar --}}
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
                           placeholder="Search playlists">
                </div>
            </div>

            <div class="row g-2 align-items-end">
                {{-- Quick dropdown filters (Status / Year / Month) — diterapkan via AJAX. --}}
                <div class="col-12 col-lg">
                    <div class="d-flex flex-wrap gap-2 learning-quick-dropdowns">
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
                </div>

                {{-- Aksi: Filter, Reset --}}
                <div class="col-12 col-lg-auto">
                    <div class="d-flex gap-2 learning-filter-actions justify-content-lg-end">
                        <button type="submit" class="btn btn-primary rounded-3 flex-fill flex-lg-grow-0">
                            <i class="ti ti-filter me-1"></i>
                            Filter
                        </button>

                        <a href="{{ $resetUrl }}"
                           class="btn btn-outline-secondary rounded-3 flex-fill flex-lg-grow-0">
                            <i class="ti ti-refresh me-1"></i>
                            Reset
                        </a>
                    </div>
                </div>
            </div>

        </form>
    </div>
</div>

<x-learning.ongoing-filter-style />
