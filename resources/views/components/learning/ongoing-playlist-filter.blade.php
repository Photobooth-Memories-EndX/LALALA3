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
    $activeQuickMonths = array_values(array_filter((array) request('ongoing_month', [])));
    $quickMonthLabel = empty($activeQuickMonths)
        ? 'All'
        : (count($activeQuickMonths) === 1
            ? ($monthOptions[(int) $activeQuickMonths[0]] ?? $activeQuickMonths[0])
            : count($activeQuickMonths) . ' Months');

    // Reset mode playlist: bersihkan seluruh filter tetapi tetap di mode playlist.
    $resetUrl = route('user.learning.dashboard') . '?' . http_build_query(['ongoing_mode' => 'playlist']);
@endphp

<div class="card border-0 shadow-sm rounded-4 mb-4 learning-filter-card ongoing-filter-card">
    <div class="card-body p-4">
        <form action="{{ route('user.learning.dashboard') }}" method="GET" id="ongoingPlaylistFilterForm" class="ongoing-filter-shell">
            <input type="hidden" name="ongoing_mode" value="playlist">
            @if ($curQuickStatus !== 'all')
                <input type="hidden" name="ongoing_quick_status" value="{{ $curQuickStatus }}">
            @endif

            <div class="ongoing-filter-heading mb-3">
                <h3 class="card-title mb-1">Learning On Going</h3>
                <div class="text-muted small">Your Enrolled Playlists</div>
            </div>

            {{-- Playlist tidak memiliki filter Type/More Filters; pertahankan field existing. --}}
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
                        <button type="submit"
                                class="btn btn-primary btn-learning-primary ongoing-filter-action-btn"
                                title="Apply Filter"
                                aria-label="Apply Filter">
                            <i class="ti ti-check" aria-hidden="true"></i>
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
        </form>
    </div>
</div>
