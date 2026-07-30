@props([
    // Baris Learning Record (Content + Playlist) dari LearningRecordController::partial().
    'rows' => collect(),
    // Opsi dropdown filter dari LearningRecordController::recordFilterOptions().
    'filterOptions' => [],
])

@php
    $types           = collect($filterOptions['types'] ?? []);
    $classifications = collect($filterOptions['classifications'] ?? []);
    $playlists       = collect($filterOptions['playlists'] ?? []);
    $years           = collect($filterOptions['years'] ?? []);
    $learningHours   = collect($filterOptions['learningHours'] ?? []);

    // Jumlah baris yang siap generate certificate — sumber counter card header.
    $certReadyCount = $rows->where('cert_state', 'ready')->count();

    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];

    $filterType = request('record_filter_type', 'all');
    $filterClassification = request('record_filter_classification', 'all');
    $filterYear = request('record_filter_year', 'all');
    $filterMonth = request('record_filter_month', 'all');

    $typeOptions = [
        'all' => 'All',
        'Video' => 'Video',
        'Document' => 'Document',
        'Playlist' => 'Playlist',
    ];

    // Kolom tabel; 'sort' = key data-sort-* pada baris (learning-record.js),
    // 'type' = cara membandingkan nilai saat sorting (text/number/duration).
    $columns = [
        ['label' => 'No',             'width' => '4%'],
        ['label' => 'Class',          'width' => '23%', 'sort' => 'title',          'type' => 'text'],
        ['label' => 'Type',           'width' => '9%',  'sort' => 'type',           'type' => 'text'],
        ['label' => 'Classification', 'width' => '10%', 'sort' => 'classification', 'type' => 'text'],
        ['label' => 'Duration',       'width' => '8%',  'sort' => 'duration',       'type' => 'duration'],
        ['label' => 'Learning Hour',  'width' => '8%',  'sort' => 'learning-hour',  'type' => 'number'],
        ['label' => 'Progress',       'width' => '9%',  'sort' => 'progress',       'type' => 'number'],
        ['label' => 'Date Time',    'width' => '12%', 'sort' => 'finish',         'type' => 'text'],
        ['label' => 'Assessment Score',     'width' => '10%',  'sort' => 'score',          'type' => 'number'],
        ['label' => 'Certificate',    'width' => '11%'],
    ];
@endphp

        <div class="card border-0 shadow-sm rounded-4 learning-record-card">
            <div class="card-body p-4 border-bottom">
        <div class="record-filter-shell" id="recordFilterBar">
            {{-- <div>
                <h3 class="card-title mb-1">Learning Record</h3>
                <div class="text-muted small">
                    Completed playlists, contents, and certificate status.
                </div>

        </div> --}}

        {{-- ── Toolbar filter (client-side — tanpa AJAX, tanpa reload halaman) ── --}}
            <div class="record-fast-filter-section mt-3">
                <label class="form-label small text-muted mb-2 learning-fast-filter-label">Fast Filter</label>

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
                                   class="dropdown-item record-quick-item {{ (string) $filterClassification === (string) $classification ? 'active' : '' }}"
                                   data-param="record_filter_classification"
                                   data-value="{{ e($classification) }}">
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

                    <div class="record-toolbar-search">
                        <label class="visually-hidden" for="recordSearch">Search learning records</label>
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                            <input type="text" class="form-control" id="recordSearch"
                                   placeholder="Search class, playlist or classification..." autocomplete="off">
                        </div>
                    </div>

                    <div class="record-filter-actions">
                    {{-- TOMBOL DOWNLOAD EXCEL --}}
                    <a href="{{ route('user.learning.record.export') }}"
                       data-export-url="{{ route('user.learning.record.export') }}"
                       class="btn btn-outline-success btn-learning-success record-filter-action-btn"
                       id="recordDownloadExcel"
                       title="Download Excel"
                       aria-label="Download Excel">
                        <i class="ti ti-download"></i>
                    </a>
                    {{-- TOMBOL MORE FILTERS --}}
                    <button type="button" class="btn btn-outline-primary btn-learning-secondary record-filter-action-btn" id="recordMoreFiltersToggle"
                            data-toggle="modal" data-target="#recordMoreFiltersModal"
                            aria-controls="recordMoreFiltersModal"
                            title="More Filters"
                            aria-label="More Filters">
                        <i class="ti ti-adjustments-horizontal"></i>
                        <span class="badge bg-primary text-white d-none" id="recordMoreFiltersCount"></span>
                    </button>
                    <button type="button" class="btn btn-primary btn-learning-primary record-apply-filter record-filter-action-btn"
                            title="Apply Filter"
                            aria-label="Apply Filter">
                        <i class="ti ti-check"></i>
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-learning-outline record-clear-filter record-filter-action-btn"
                            title="Reset Filter"
                            aria-label="Reset Filter">
                        <i class="ti ti-refresh"></i>
                    </button>
                    </div>
                </div>
            </div>

            {{-- Hidden inputs untuk menjaga state form saat disubmit jika menggunakan form tradisional --}}
            <input type="hidden" name="record_filter_type" id="hidden_record_filter_type" value="{{ $filterType }}">
            <input type="hidden" name="record_filter_classification" id="hidden_record_filter_classification" value="{{ $filterClassification }}">
            <input type="hidden" name="record_filter_year" id="hidden_record_filter_year" value="{{ $filterYear }}">
            <input type="hidden" name="record_filter_month" id="hidden_record_filter_month" value="{{ $filterMonth }}">

            {{-- Panel More Filters — seluruh filter lanjutan lama dipindah ke sini
                 (tidak dihapus). Tersembunyi default, dibuka slideDown (learning-record.js). --}}
            <div class="modal fade" id="recordMoreFiltersModal" tabindex="-1" role="dialog" aria-labelledby="recordMoreFiltersTitle" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content record-filter-modal">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title mb-0" id="recordMoreFiltersTitle">More Filters</h5>
                                <div class="text-muted small">Refine the visible Learning Record rows.</div>
                            </div>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body record-more-filters" id="recordMoreFilters">
                {{-- data-record-mode: filter hanya relevan untuk mode tsb — mode lain
                     menyembunyikannya dan me-reset nilainya (learning-record.js). --}}
                <div class="record-filter-sections">
                    <div class="record-filter-section">
                        <div class="record-filter-title">Type</div>
                        @foreach (['all' => 'All', 'Document' => 'Document', 'Video' => 'Video', 'Playlist' => 'Playlist'] as $value => $label)
                            <label class="form-check">
                                <input class="form-check-input record-modal-checkbox" type="checkbox" name="recordModalType[]" value="{{ $value }}" data-record-modal-filter {{ $value === 'all' ? 'checked' : '' }}>
                                <span class="form-check-label">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="record-filter-section">
                        <div class="record-filter-title">Learning Program</div>
                        @foreach (['all' => 'All Learning', 'mandatory' => 'Mandatory Learning', 'optional' => 'Optional Learning'] as $value => $label)
                            <label class="form-check">
                                <input class="form-check-input" type="radio" name="recordModalProgram" value="{{ $value }}" data-record-modal-filter {{ $value === 'all' ? 'checked' : '' }}>
                                <span class="form-check-label">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="record-filter-section">
                        <div class="record-filter-title">Year</div>
                        <label class="form-check">
                            <input class="form-check-input" type="radio" name="recordModalYear" value="all" data-record-modal-filter checked>
                            <span class="form-check-label">All</span>
                        </label>
                        @foreach ($years as $year)
                            <label class="form-check">
                                <input class="form-check-input" type="radio" name="recordModalYear" value="{{ $year }}" data-record-modal-filter>
                                <span class="form-check-label">{{ $year }}</span>
                            </label>
                        @endforeach
                    </div>

                    {{-- <div class="record-filter-section">
                        <div class="record-filter-title">Activity Sort</div>
                        @foreach (['newest' => 'Newest Activity', 'oldest' => 'Oldest Activity', 'recent' => 'Recently Accessed', 'longest' => 'Longest Duration', 'shortest' => 'Shortest Duration'] as $value => $label)
                            <label class="form-check">
                                <input class="form-check-input" type="radio" name="recordModalActivitySort" value="{{ $value }}" data-record-modal-filter>
                                <span class="form-check-label">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div> --}}
{{-- 
                    <div class="record-filter-section">
                        <div class="record-filter-title">Completion Status</div>
                        @foreach (['all' => 'All Progress', 'highest' => 'Highest Progress', 'lowest' => 'Lowest Progress', 'not_started' => 'Not Started', 'in_progress' => 'In Progress', 'almost_done' => 'Almost Done', 'completed' => 'Completed'] as $value => $label)
                            <label class="form-check">
                                <input class="form-check-input" type="radio" name="recordModalCompletion" value="{{ $value }}" data-record-modal-filter {{ $value === 'all' ? 'checked' : '' }}>
                                <span class="form-check-label">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div> --}}

                    <div class="record-filter-section">
                        <div class="record-filter-title">Learning Status</div>
                        @foreach (['not_started' => 'Not Started', 'ongoing' => 'Ongoing', 'paused' => 'Paused', 'completed' => 'Completed'] as $value => $label)
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" name="recordModalLearningStatus[]" value="{{ $value }}" data-record-modal-filter>
                                <span class="form-check-label">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    {{-- <div class="record-filter-section">
                        <div class="record-filter-title">Progress</div>
                        @foreach (['0' => '0%', '1-25' => '1-25%', '26-50' => '26-50%', '51-75' => '51-75%', '76-99' => '76-99%', '100' => '100%'] as $value => $label)
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" name="recordModalProgress[]" value="{{ $value }}" data-record-modal-filter>
                                <span class="form-check-label">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div> --}}

                    <div class="record-filter-section">
                        <div class="record-filter-title">Month</div>
                        @foreach ($months as $num => $name)
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" name="recordModalMonth[]" value="{{ $num }}" data-record-modal-filter>
                                <span class="form-check-label">{{ $name }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="record-filter-section">
                        <div class="record-filter-title">Duration</div>
                        @foreach (['all' => 'All', '0-1800' => '00:00:00 - 00:30:00', '1801-3600' => '00:30:01 - 01:00:00', '3601-5400' => '01:00:01 - 01:30:00', '5401-7200' => '01:30:01 - 02:00:00', 'gt7200' => '> 02:00:00'] as $value => $label)
                            <label class="form-check">
                                <input class="form-check-input" type="radio" name="recordModalDuration" value="{{ $value }}" data-record-modal-filter {{ $value === 'all' ? 'checked' : '' }}>
                                <span class="form-check-label">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="record-filter-section">
                        <div class="record-filter-title">Learning Hour</div>
                        @foreach (['all' => 'All', '0.5' => '0.5 Hour', '1' => '1 Hour', '1.5' => '1.5 Hours', '2' => '2 Hours', '2.5' => '2.5 Hours', '3' => '3 Hours', 'gt3' => '> 3 Hours'] as $value => $label)
                            <label class="form-check">
                                <input class="form-check-input" type="radio" name="recordModalLearningHour" value="{{ $value }}" data-record-modal-filter {{ $value === 'all' ? 'checked' : '' }}>
                                <span class="form-check-label">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="record-filter-section">
                        <div class="record-filter-title">Score Test</div>
                        @foreach (['all' => 'All', 'none' => 'No Assessment', 'lt60' => '< 60', '60-79' => '60 - 79', '80-100' => '80 - 100'] as $value => $label)
                            <label class="form-check">
                                <input class="form-check-input" type="radio" name="recordModalScoreTest" value="{{ $value }}" data-record-modal-filter {{ $value === 'all' ? 'checked' : '' }}>
                                <span class="form-check-label">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="record-filter-section">
                        <div class="record-filter-title">Certificate</div>
                        @foreach (['all' => 'All', 'ready' => 'Ready', 'none' => 'Not Ready'] as $value => $label)
                            <label class="form-check">
                                <input class="form-check-input" type="radio" name="recordModalCertificate" value="{{ $value }}" data-record-modal-filter {{ $value === 'all' ? 'checked' : '' }}>
                                <span class="form-check-label">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                        </div>
                        <div class="modal-footer record-filter-modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-learning-outline" id="recordClearFilter">
                        <i class="ti ti-refresh"></i><span>Reset</span>
                    </button>
                    <button type="button" class="btn btn-primary btn-learning-primary" id="recordApplyFilter">
                        <i class="ti ti-check"></i><span>Apply Filter</span>
                    </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body p-0">

        <div class="table-responsive learning-record-scroll">
            <table class="table table-vcenter table-hover card-table mb-0 learning-record-table"
                   id="learningRecordTable" style="width:100%">
                <thead>
                    <tr>
                        @foreach ($columns as $column)
                            @if (isset($column['sort']))
                                <th class="text-muted record-sortable"
                                    style="width: {{ $column['width'] }}"
                                    data-sort-key="{{ $column['sort'] }}"
                                    data-sort-type="{{ $column['type'] }}">
                                    <span class="record-sort-header">
                                        <span class="record-sort-label">{{ $column['label'] }}</span>
                                        <span class="record-sort-icons" aria-hidden="true"><span class="record-sort-asc">&#9650;</span><span class="record-sort-desc">&#9660;</span></span>
                                    </span>
                                </th>
                            @else
                                <th class="text-muted" style="width: {{ $column['width'] }}">{{ $column['label'] }}</th>
                            @endif
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <x-learning.record-row :row="$row" :index="$loop->iteration" />
                    @empty
                        <tr id="recordEmptyRow">
                            <td colspan="10" class="text-center text-muted py-4">
                                No completed learning found.
                            </td>
                        </tr>
                    @endforelse

                    @if ($rows->isNotEmpty())
                        {{-- Ditampilkan jQuery saat kombinasi filter tidak menghasilkan baris. --}}
                        <tr id="recordNoMatchRow" class="d-none">
                            <td colspan="10" class="text-center text-muted py-4">
                                No records match your search or filter.
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    @if ($rows->isNotEmpty())
        <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <select class="form-select form-select-sm w-auto" id="recordPageLength">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
                <span class="text-muted small" id="recordTableInfo"></span>
            </div>

            <ul class="pagination pagination-sm mb-0" id="recordPagination"></ul>
        </div>
    @endif
</div>
