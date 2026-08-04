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

    $playlistFilterCount = ($curQuickStatus !== 'all' ? 1 : 0)
        + ($curYear !== 'all' ? 1 : 0)
        + count($activeQuickMonths)
        + count($selectedProgress);

    // Reset mode playlist: bersihkan seluruh filter tetapi tetap di mode playlist.
    $resetUrl = route('user.learning.dashboard') . '?' . http_build_query(['ongoing_mode' => 'playlist']);
@endphp

<div class="card border-0 shadow-sm rounded-4 mb-4 learning-filter-card ongoing-filter-card">
    <div class="card-body p-4">
        <form action="{{ route('user.learning.dashboard') }}" method="GET" id="ongoingPlaylistFilterForm" class="ongoing-filter-shell">
            <input type="hidden" name="ongoing_mode" value="playlist">

            <div class="ongoing-filter-heading mb-3">
                <h3 class="card-title mb-1">Learning On Going</h3>
                <div class="text-muted small">Your Enrolled Playlists</div>
            </div>

            {{-- Playlist tidak memiliki filter Type; filter lainnya tersedia di toolbar dan modal. --}}
            <div class="form-label small text-muted mb-2 learning-fast-filter-label">Fast Filter</div>
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
                        <div class="input-icon">
                            <span class="input-icon-addon">
                                <i class="ti ti-search" aria-hidden="true"></i>
                            </span>
                            <input type="text"
                                   name="ongoing_search"
                                   id="ongoing_search"
                                   value="{{ request('ongoing_search') }}"
                                   class="form-control"
                                   aria-label="Search playlists"
                                   placeholder="Search playlists...">
                        </div>
                    </div>

                    <div class="ongoing-filter-actions" role="group" aria-label="Filter actions">
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

                        <button type="submit"
                                class="btn btn-primary btn-learning-primary ongoing-filter-action-btn"
                                title="Apply Filter"
                                aria-label="Apply Filter">
                            <i class="ti ti-search" aria-hidden="true"></i>
                        </button>

                        <a href="{{ $resetUrl }}"
                           class="btn btn-outline-secondary btn-learning-outline ongoing-filter-action-btn"
                           title="Reset Filter"
                           aria-label="Reset Filter">
                            <i class="ti ti-refresh" aria-hidden="true"></i>
                        </a>
                    </div>

                    <x-learning.ongoing-mode-switch mode="playlist" />
                </div>
            </div>

            <div class="modal fade" id="ongoingPlaylistMoreFiltersModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered learning-filter-modal">
                    <div class="modal-content rounded-4 border-0 shadow">
                        <div class="modal-header border-0 pb-0">
                            <h3 class="modal-title fw-bold">
                                <i class="ti ti-adjustments-horizontal me-1 text-primary"></i>
                                Playlist Filters
                            </h3>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body pt-2">
                            <p class="text-muted small mb-4">
                                Filter playlist dapat dikombinasikan dengan pencarian di toolbar.
                            </p>

                            <div class="learning-filter-group">
                                <div class="fw-semibold mb-2">Playlist Status</div>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach ($quickStatusOptions as $value => $label)
                                        <label class="form-check">
                                            <input class="form-check-input" type="radio"
                                                   name="ongoing_quick_status" value="{{ $value }}"
                                                   {{ $curQuickStatus === $value ? 'checked' : '' }}>
                                            <span class="form-check-label">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <hr class="my-3">

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

                            <div class="learning-filter-group">
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

                            <hr class="my-3">

                            <div class="learning-filter-group">
                                <div class="fw-semibold mb-2">Progress</div>
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
                        </div>

                        <div class="modal-footer border-0 pt-0">
                            <a href="{{ $resetUrl }}" class="btn btn-outline-secondary btn-learning-outline">
                                <i class="ti ti-refresh"></i>
                                Reset
                            </a>
                            <button type="button" class="btn btn-primary btn-learning-primary" data-bs-dismiss="modal">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
