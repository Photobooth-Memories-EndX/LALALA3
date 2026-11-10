@props([
    'filterOptions' => [],
])

@php
    $classifications = collect($filterOptions['classifications'] ?? []);
    $years           = collect($filterOptions['years'] ?? []);
    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];

    $filterType = request('record_filter_type') ?? request('type') ?? 'all';
    $rawFilterClassification = request('record_filter_classification') ?? request('classification') ?? 'all';
    $filterClassification = $rawFilterClassification !== 'all' ? html_entity_decode((string) $rawFilterClassification, ENT_QUOTES | ENT_HTML5, 'UTF-8') : 'all';
    $filterYear = request('record_filter_year') ?? request('year') ?? 'all';
    $filterMonth = request('record_filter_month') ?? request('month') ?? 'all';

    $typeOptions = [
        'all' => 'All',
        'Video' => 'Video',
        'Document' => 'Document',
        'Playlist' => 'Playlist',
    ];
@endphp

<div class="record-filter-shell" id="recordFilterBar">
    {{-- Header: Title & Subtitle --}}
    <div class="mb-3">
        <h2 class="fw-bold mb-1 my-task-filter-title">Learning Record</h2>
        <p class="text-muted small mb-0 my-task-filter-subtitle">Review your complete history of completed courses, playlists, and learning hours.</p>
    </div>

    {{-- Category Filter Pills --}}
    <div class="d-flex align-items-center flex-wrap gap-2 mb-3 my-task-pills-row">
        <button type="button"
                class="my-task-category-pill record-mode-btn"
                data-mode="all"
                title="All Record">
            <i class="ti ti-stack-2 me-1"></i> All Record
        </button>
        <button type="button"
                class="my-task-category-pill record-mode-btn active"
                data-mode="online"
                title="KP OWL">
            <i class="ti ti-device-laptop me-1"></i> KP OWL
        </button>
        <button type="button"
                class="my-task-category-pill record-mode-btn"
                data-mode="offline"
                title="Non KP OWL">
            <i class="ti ti-certificate me-1"></i> Non KP OWL
        </button>
    </div>

    {{-- Subtle Divider Line --}}
    <hr class="my-3 my-task-divider">

    {{-- ── Fast Filter Toolbar (client-side — tanpa AJAX, tanpa reload halaman) ── --}}
    <div class="d-flex align-items-center flex-wrap gap-2 w-100 my-task-toolbar learning-quick-dropdowns">
        {{-- 1. Dropdown Type --}}
        <div class="dropdown">
            <button type="button"
                    class="btn my-task-dropdown-btn dropdown-toggle {{ $filterType !== 'all' ? 'active' : '' }}"
                    data-bs-toggle="dropdown"
                    data-bs-display="static"
                    aria-expanded="false">
                Type: <span class="fw-semibold">{{ $typeOptions[$filterType] ?? 'All' }}</span>
            </button>
            <div class="dropdown-menu shadow-sm">
                @foreach ($typeOptions as $value => $label)
                    <a href="#"
                       class="dropdown-item my-task-dropdown-item record-quick-item {{ $filterType === $value ? 'active' : '' }}"
                       data-param="record_filter_type"
                       data-value="{{ $value }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- 2. Dropdown Classification --}}
        <div class="dropdown">
            <button type="button"
                    class="btn my-task-dropdown-btn dropdown-toggle {{ $filterClassification !== 'all' ? 'active' : '' }}"
                    data-bs-toggle="dropdown"
                    data-bs-display="static"
                    aria-expanded="false">
                Classification:
                <span class="fw-semibold">
                    {{ $filterClassification === 'all' ? 'All' : $filterClassification }}
                </span>
            </button>
            <div class="dropdown-menu shadow-sm" style="max-height: 280px; overflow-y: auto;">
                <a href="#"
                   class="dropdown-item my-task-dropdown-item record-quick-item {{ $filterClassification === 'all' ? 'active' : '' }}"
                   data-param="record_filter_classification"
                   data-value="all">
                    All Classification
                </a>
                @foreach ($classifications as $classification)
                    <a href="#"
                       class="dropdown-item my-task-dropdown-item record-quick-item {{ strcasecmp((string) $filterClassification, (string) $classification) === 0 ? 'active' : '' }}"
                       data-param="record_filter_classification"
                       data-value="{{ $classification }}">
                        {{ $classification }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- 3. Dropdown Year --}}
        <div class="dropdown">
            <button type="button"
                    class="btn my-task-dropdown-btn dropdown-toggle {{ $filterYear !== 'all' ? 'active' : '' }}"
                    data-bs-toggle="dropdown"
                    data-bs-display="static"
                    aria-expanded="false">
                Year:
                <span class="fw-semibold">
                    {{ $filterYear === 'all' ? 'All' : $filterYear }}
                </span>
            </button>
            <div class="dropdown-menu shadow-sm">
                <a href="#"
                   class="dropdown-item my-task-dropdown-item record-quick-item {{ $filterYear === 'all' ? 'active' : '' }}"
                   data-param="record_filter_year"
                   data-value="all">
                    All
                </a>
                @foreach ($years as $year)
                    <a href="#"
                       class="dropdown-item my-task-dropdown-item record-quick-item {{ (string) $filterYear === (string) $year ? 'active' : '' }}"
                       data-param="record_filter_year"
                       data-value="{{ $year }}">
                        {{ $year }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- 4. Dropdown Month --}}
        <div class="dropdown">
            <button type="button"
                    class="btn my-task-dropdown-btn dropdown-toggle {{ $filterMonth !== 'all' ? 'active' : '' }}"
                    data-bs-toggle="dropdown"
                    data-bs-display="static"
                    aria-expanded="false">
                Month:
                <span class="fw-semibold">
                    {{ $filterMonth === 'all' ? 'All' : ($months[(int) $filterMonth] ?? 'All') }}
                </span>
            </button>
            <div class="dropdown-menu shadow-sm" style="max-height: 280px; overflow-y: auto;">
                <a href="#"
                   class="dropdown-item my-task-dropdown-item record-quick-item {{ $filterMonth === 'all' ? 'active' : '' }}"
                   data-param="record_filter_month"
                   data-value="all">
                    All
                </a>
                @foreach ($months as $num => $name)
                    <a href="#"
                       class="dropdown-item my-task-dropdown-item record-quick-item {{ (string) $filterMonth === (string) $num ? 'active' : '' }}"
                       data-param="record_filter_month"
                       data-value="{{ $num }}">
                        {{ $name }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- 5. Search Input --}}
        <div class="position-relative flex-grow-1 my-task-search-wrapper" style="min-width: 220px;">
            <span class="position-absolute top-50 start-0 translate-middle-y ps-3 text-muted pointer-events-none" style="z-index: 2;">
                <i class="ti ti-search" style="font-size: 15px;"></i>
            </span>
            <input type="text" class="form-control my-task-search-input" id="recordSearch"
                   value="{{ request('record_search', request('search', request('q', ''))) }}"
                   placeholder="Search course, playlist or classification..." autocomplete="off">
        </div>

        {{-- 6. Square Action Buttons --}}
        <button type="button" class="btn btn-primary my-task-btn-square my-task-btn-search record-apply-filter"
                id="recordApplyFilterBtn"
                title="Search & Apply Filter"
                aria-label="Search & Apply Filter">
            <i class="ti ti-search text-white fs-3" aria-hidden="true"></i>
        </button>

        <button type="button" class="btn btn-white my-task-btn-square my-task-btn-filter position-relative" id="recordMoreFiltersToggle"
                data-bs-toggle="modal" data-bs-target="#recordMoreFiltersModal"
                data-toggle="modal" data-target="#recordMoreFiltersModal"
                aria-controls="recordMoreFiltersModal"
                title="More Filters"
                aria-label="More Filters">
            <i class="ti ti-adjustments-horizontal fs-3 text-secondary" aria-hidden="true"></i>
            <span class="badge bg-primary text-white d-none ms-1" id="recordMoreFiltersCount"></span>
        </button>

        <button type="button" class="btn btn-white my-task-btn-square my-task-btn-reset record-clear-filter"
                title="Reset Filter"
                aria-label="Reset Filter">
            <i class="ti ti-refresh fs-3 text-secondary" aria-hidden="true"></i>
        </button>

        <a href="{{ route('user.learning.record.export', request()->all()) }}"
           data-export-url="{{ route('user.learning.record.export') }}"
           class="btn btn-white my-task-btn-square my-task-btn-export"
           id="recordDownloadExcel"
           title="Download Excel"
           aria-label="Download Excel">
            <i class="ti ti-download fs-3 text-secondary" aria-hidden="true"></i>
        </a>
    </div>

    {{-- Hidden inputs --}}
    <input type="hidden" name="record_filter_type" id="hidden_record_filter_type" value="{{ $filterType }}">
    <input type="hidden" name="record_filter_classification" id="hidden_record_filter_classification" value="{{ $filterClassification }}">
    <input type="hidden" name="record_filter_year" id="hidden_record_filter_year" value="{{ $filterYear }}">
    <input type="hidden" name="record_filter_month" id="hidden_record_filter_month" value="{{ $filterMonth }}">

    {{-- Modal Filter --}}
    <x-learning.record-filter-modal :filter-options="$filterOptions" />
</div>
