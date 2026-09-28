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
    <div class="learning-filter-heading fast-filter-header">
        <div class="fast-filter-header-copy">
            <h3 class="card-title fast-filter-title">Quick Filter</h3>
        </div>
        <x-learning.record-mode-switch mode="online" />
    </div>

    {{-- ── Fast Filter Toolbar (client-side — tanpa AJAX, tanpa reload halaman) ── --}}
    <div class="record-fast-filter-section">
        <div class="fast-filter-toolbar">
            <div class="learning-quick-dropdowns learning-fast-filter">
                {{-- Dropdown Type --}}
                <div class="dropdown">
                    <button type="button"
                            class="btn btn-filter-chip learning-fast-filter-control dropdown-toggle {{ $filterType !== 'all' ? 'active' : '' }}"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">
                        Type: <span class="fw-semibold">{{ $typeOptions[$filterType] ?? 'All' }}</span>
                    </button>

                    <div class="dropdown-menu">
                        @foreach ($typeOptions as $value => $label)
                            <a href="#"
                               class="dropdown-item record-quick-item {{ $filterType === $value ? 'active' : '' }}"
                               data-param="record_filter_type"
                               data-value="{{ $value }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Dropdown Classification --}}
                <div class="dropdown">
                    <button type="button"
                            class="btn btn-filter-chip learning-fast-filter-control dropdown-toggle {{ $filterClassification !== 'all' ? 'active' : '' }}"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">
                        Classification:
                        <span class="fw-semibold">
                            {{ $filterClassification === 'all' ? 'All' : $filterClassification }}
                        </span>
                    </button>

                    <div class="dropdown-menu learning-quick-dropdown-scroll">
                        <a href="#"
                           class="dropdown-item record-quick-item {{ $filterClassification === 'all' ? 'active' : '' }}"
                           data-param="record_filter_classification"
                           data-value="all">
                            All Classification
                        </a>

                        @foreach ($classifications as $classification)
                            <a href="#"
                               class="dropdown-item record-quick-item {{ strcasecmp((string) $filterClassification, (string) $classification) === 0 ? 'active' : '' }}"
                               data-param="record_filter_classification"
                               data-value="{{ $classification }}">
                                {{ $classification }}
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Dropdown Year --}}
                <div class="dropdown">
                    <button type="button"
                            class="btn btn-filter-chip learning-fast-filter-control dropdown-toggle {{ $filterYear !== 'all' ? 'active' : '' }}"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">
                        Year:
                        <span class="fw-semibold">
                            {{ $filterYear === 'all' ? 'All' : $filterYear }}
                        </span>
                    </button>

                    <div class="dropdown-menu">
                        <a href="#"
                           class="dropdown-item record-quick-item {{ $filterYear === 'all' ? 'active' : '' }}"
                           data-param="record_filter_year"
                           data-value="all">
                            All
                        </a>

                        @foreach ($years as $year)
                            <a href="#"
                               class="dropdown-item record-quick-item {{ (string) $filterYear === (string) $year ? 'active' : '' }}"
                               data-param="record_filter_year"
                               data-value="{{ $year }}">
                                {{ $year }}
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Dropdown Month --}}
                <div class="dropdown">
                    <button type="button"
                            class="btn btn-filter-chip learning-fast-filter-control dropdown-toggle {{ $filterMonth !== 'all' ? 'active' : '' }}"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">
                        Month:
                        <span class="fw-semibold">
                            {{ $filterMonth === 'all' ? 'All' : ($months[(int) $filterMonth] ?? 'All') }}
                        </span>
                    </button>

                    <div class="dropdown-menu learning-quick-dropdown-scroll">
                        <a href="#"
                           class="dropdown-item record-quick-item {{ $filterMonth === 'all' ? 'active' : '' }}"
                           data-param="record_filter_month"
                           data-value="all">
                            All
                        </a>

                        @foreach ($months as $num => $name)
                            <a href="#"
                               class="dropdown-item record-quick-item {{ (string) $filterMonth === (string) $num ? 'active' : '' }}"
                               data-param="record_filter_month"
                               data-value="{{ $num }}">
                                {{ $name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- 1. Search --}}
            <div class="record-toolbar-search">
                <label class="visually-hidden" for="recordSearch">Search learning records</label>
                <div class="input-icon">
                    <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                    <input type="text" class="form-control" id="recordSearch"
                           placeholder="Search course, playlist or classification..." autocomplete="off">
                </div>
            </div>

            <div class="record-filter-actions d-flex align-items-center gap-2">
                {{-- 1. Search Button --}}
                <button type="button" class="btn btn-primary btn-learning-primary record-apply-filter record-filter-action-btn"
                        id="recordApplyFilterBtn"
                        title="Search & Apply Filter"
                        aria-label="Search & Apply Filter">
                    <i class="ti ti-search" aria-hidden="true"></i>
                </button>

                {{-- 2. More Filter Button --}}
                <button type="button" class="btn btn-outline-primary btn-learning-secondary record-filter-action-btn" id="recordMoreFiltersToggle"
                        data-bs-toggle="modal" data-bs-target="#recordMoreFiltersModal"
                        data-toggle="modal" data-target="#recordMoreFiltersModal"
                        aria-controls="recordMoreFiltersModal"
                        title="More Filters"
                        aria-label="More Filters">
                    <i class="ti ti-adjustments-horizontal" aria-hidden="true"></i>
                    <span class="badge bg-primary text-white d-none ms-1" id="recordMoreFiltersCount"></span>
                </button>

                {{-- 3. Reset Button --}}
                <button type="button" class="btn btn-outline-secondary btn-learning-outline record-clear-filter record-filter-action-btn"
                        title="Reset Filter"
                        aria-label="Reset Filter">
                    <i class="ti ti-refresh" aria-hidden="true"></i>
                </button>

                {{-- 4. Download Excel Button --}}
                <a href="{{ route('user.learning.record.export', request()->all()) }}"
                   data-export-url="{{ route('user.learning.record.export') }}"
                   class="btn btn-outline-success btn-learning-success record-filter-action-btn"
                   id="recordDownloadExcel"
                   title="Download Excel"
                   aria-label="Download Excel">
                    <i class="ti ti-download" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </div>

    {{-- Hidden inputs --}}
    <input type="hidden" name="record_filter_type" id="hidden_record_filter_type" value="{{ $filterType }}">
    <input type="hidden" name="record_filter_classification" id="hidden_record_filter_classification" value="{{ $filterClassification }}">
    <input type="hidden" name="record_filter_year" id="hidden_record_filter_year" value="{{ $filterYear }}">
    <input type="hidden" name="record_filter_month" id="hidden_record_filter_month" value="{{ $filterMonth }}">

    {{-- Modal Filter --}}
    <x-learning.record-filter-modal :filter-options="$filterOptions" />
</div>
