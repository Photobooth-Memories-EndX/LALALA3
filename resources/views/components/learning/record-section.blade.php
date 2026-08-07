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
        ['label' => 'Course',        'width' => '23%', 'sort' => 'title',          'type' => 'text'],
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

<style>
.learning-classification-badge {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    text-align: center !important;
    white-space: normal !important;
    word-break: break-word !important;
    line-height: 1.15 !important;
    padding: 6px 12px !important;
    min-height: 28px !important;
    max-width: 140px !important;
    border-radius: 999px !important;
    vertical-align: middle !important;
    font-weight: 500 !important;
}
.btn-learning-certificate,
.btn-learning-certificate-disabled {
    width: 36px !important;
    height: 36px !important;
    padding: 0 !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    border-radius: 8px !important;
    font-size: 1.1rem !important;
    box-sizing: border-box !important;
}
.btn-learning-certificate-disabled {
    background-color: #d1d5db !important;
    border: 1px solid #bfc6cf !important;
    color: #4b5563 !important;
    opacity: 1 !important;
    cursor: not-allowed !important;
    pointer-events: auto !important;
    box-shadow: none !important;
}
.btn-learning-certificate-disabled:hover,
.btn-learning-certificate-disabled:focus,
.btn-learning-certificate-disabled:active {
    background-color: #d1d5db !important;
    border-color: #bfc6cf !important;
    color: #4b5563 !important;
    box-shadow: none !important;
}
.learning-score-empty-pill {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    min-width: 32px !important;
    height: 22px !important;
    padding: 0 10px !important;
    border-radius: 999px !important;
    background: #eef1f4 !important;
    border: 1px solid #d6dce3 !important;
    color: #7b8794 !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    line-height: 1 !important;
}
.learning-score-pill {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    min-width: 32px !important;
    height: 22px !important;
    padding: 0 10px !important;
    border-radius: 999px !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    line-height: 1 !important;
}
.record-filter-section {
    border-color: #f1f3f5 !important;
}
.record-filter-section .form-label {
    letter-spacing: 0.5px;
    font-size: 0.725rem;
}
.record-filter-action-btn {
    width: 38px !important;
    height: 38px !important;
    padding: 0 !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    border-radius: 8px !important;
    font-size: 1.1rem !important;
    box-sizing: border-box !important;
    flex-shrink: 0 !important;
}
.record-filter-action-btn i {
    font-size: 1.1rem !important;
    line-height: 1 !important;
    display: inline-block !important;
    color: inherit !important;
}
</style>

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
                        <a href="{{ route('user.learning.record.export') }}"
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
                        <div class="modal-body record-more-filters p-4" id="recordMoreFilters">
                            <div class="record-filter-sections d-flex flex-column gap-3">

                                {{-- 1. LEARNING PROGRAM --}}
                                <div class="record-filter-section border-bottom pb-3">
                                    <label class="form-label fw-bold text-uppercase text-muted small mb-2">LEARNING PROGRAM</label>
                                    <div class="d-flex flex-wrap gap-3">
                                        @foreach (['all' => 'All Learning', 'mandatory' => 'Mandatory Learning', 'optional' => 'Optional Learning'] as $value => $label)
                                            <label class="form-check form-check-inline mb-0">
                                                <input class="form-check-input" type="radio" name="recordModalProgram" value="{{ $value }}" data-record-modal-filter {{ $value === 'all' ? 'checked' : '' }}>
                                                <span class="form-check-label">{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- 2. COMPLETION STATUS --}}
                                <div class="record-filter-section border-bottom pb-3">
                                    <label class="form-label fw-bold text-uppercase text-muted small mb-2">COMPLETION STATUS</label>
                                    <div class="d-flex flex-wrap gap-3">
                                        <label class="form-check form-check-inline mb-0">
                                            <input class="form-check-input" type="checkbox" name="recordModalLearningStatus[]" value="completed" data-record-modal-filter>
                                            <span class="form-check-label">Completed</span>
                                        </label>
                                        <label class="form-check form-check-inline mb-0">
                                            <input class="form-check-input" type="checkbox" name="recordModalLearningStatus[]" value="passed" data-record-modal-filter>
                                            <span class="form-check-label">Passed</span>
                                        </label>
                                        <label class="form-check form-check-inline mb-0">
                                            <input class="form-check-input" type="checkbox" name="recordModalLearningStatus[]" value="failed" data-record-modal-filter>
                                            <span class="form-check-label">Failed</span>
                                        </label>
                                    </div>
                                </div>

                                {{-- 3. COMPLETION DATE --}}
                                <div class="record-filter-section border-bottom pb-3">
                                    <label class="form-label fw-bold text-uppercase text-muted small mb-2">COMPLETION DATE</label>
                                    <div class="mb-2">
                                        <div class="text-muted small mb-1 fw-semibold">Year:</div>
                                        <div class="d-flex flex-wrap gap-2">
                                            <label class="form-check form-check-inline mb-0">
                                                <input class="form-check-input" type="radio" name="recordModalYear" value="all" data-record-modal-filter checked>
                                                <span class="form-check-label">All</span>
                                            </label>
                                            @foreach ($years as $year)
                                                <label class="form-check form-check-inline mb-0">
                                                    <input class="form-check-input" type="radio" name="recordModalYear" value="{{ $year }}" data-record-modal-filter>
                                                    <span class="form-check-label">{{ $year }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-muted small mb-1 fw-semibold">Month:</div>
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach ($months as $num => $name)
                                                <label class="form-check form-check-inline mb-0">
                                                    <input class="form-check-input" type="checkbox" name="recordModalMonth[]" value="{{ $num }}" data-record-modal-filter>
                                                    <span class="form-check-label">{{ substr($name, 0, 3) }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                {{-- 4. SORT BY --}}
                                <div class="record-filter-section border-bottom pb-3">
                                    <label class="form-label fw-bold text-uppercase text-muted small mb-2">SORT BY</label>
                                    <div style="max-width: 250px;">
                                        <select class="form-select form-select-sm" id="recordModalActivitySortSelect" onchange="
                                            var val = this.value;
                                            var radio = document.querySelector('input[name=\'recordModalActivitySort\'][value=\'' + val + '\']');
                                            if (radio) { radio.checked = true; $(radio).trigger('change'); }
                                        ">
                                            <option value="all">All</option>
                                            <option value="oldest">Oldest</option>
                                            <option value="newest">Newest</option>
                                            <option value="ascending">Ascending</option>
                                            <option value="descending">Descending</option>
                                        </select>
                                        <div class="d-none">
                                            <input type="radio" name="recordModalActivitySort" value="all" checked data-record-modal-filter>
                                            <input type="radio" name="recordModalActivitySort" value="oldest" data-record-modal-filter>
                                            <input type="radio" name="recordModalActivitySort" value="newest" data-record-modal-filter>
                                            <input type="radio" name="recordModalActivitySort" value="ascending" data-record-modal-filter>
                                            <input type="radio" name="recordModalActivitySort" value="descending" data-record-modal-filter>
                                        </div>
                                    </div>
                                </div>

                                {{-- 5. DURATION --}}
                                <div class="record-filter-section border-bottom pb-3">
                                    <label class="form-label fw-bold text-uppercase text-muted small mb-2">DURATION</label>
                                    <div class="d-flex flex-wrap gap-3">
                                        <label class="form-check form-check-inline mb-0">
                                            <input class="form-check-input" type="checkbox" name="recordModalDurationCheck[]" value="0-600" data-record-modal-filter onchange="
                                                var checked = Array.from(document.querySelectorAll('input[name=\'recordModalDurationCheck[]\']:checked')).map(function(c){ return c.value; });
                                                var val = checked.length ? checked[0] : 'all';
                                                var radio = document.querySelector('input[name=\'recordModalDuration\'][value=\'' + val + '\']');
                                                if (radio) { radio.checked = true; $(radio).trigger('change'); }
                                            ">
                                            <span class="form-check-label">&lt; 10 Minutes</span>
                                        </label>
                                        <label class="form-check form-check-inline mb-0">
                                            <input class="form-check-input" type="checkbox" name="recordModalDurationCheck[]" value="600-1800" data-record-modal-filter onchange="
                                                var checked = Array.from(document.querySelectorAll('input[name=\'recordModalDurationCheck[]\']:checked')).map(function(c){ return c.value; });
                                                var val = checked.length ? checked[0] : 'all';
                                                var radio = document.querySelector('input[name=\'recordModalDuration\'][value=\'' + val + '\']');
                                                if (radio) { radio.checked = true; $(radio).trigger('change'); }
                                            ">
                                            <span class="form-check-label">10 - 30 Minutes</span>
                                        </label>
                                        <label class="form-check form-check-inline mb-0">
                                            <input class="form-check-input" type="checkbox" name="recordModalDurationCheck[]" value="1800-3600" data-record-modal-filter onchange="
                                                var checked = Array.from(document.querySelectorAll('input[name=\'recordModalDurationCheck[]\']:checked')).map(function(c){ return c.value; });
                                                var val = checked.length ? checked[0] : 'all';
                                                var radio = document.querySelector('input[name=\'recordModalDuration\'][value=\'' + val + '\']');
                                                if (radio) { radio.checked = true; $(radio).trigger('change'); }
                                            ">
                                            <span class="form-check-label">30 - 60 Minutes</span>
                                        </label>
                                        <label class="form-check form-check-inline mb-0">
                                            <input class="form-check-input" type="checkbox" name="recordModalDurationCheck[]" value="gt3600" data-record-modal-filter onchange="
                                                var checked = Array.from(document.querySelectorAll('input[name=\'recordModalDurationCheck[]\']:checked')).map(function(c){ return c.value; });
                                                var val = checked.length ? checked[0] : 'all';
                                                var radio = document.querySelector('input[name=\'recordModalDuration\'][value=\'' + val + '\']');
                                                if (radio) { radio.checked = true; $(radio).trigger('change'); }
                                            ">
                                            <span class="form-check-label">&gt; 1 Hour</span>
                                        </label>
                                    </div>
                                    <div class="d-none">
                                        <input type="radio" name="recordModalDuration" value="all" checked data-record-modal-filter>
                                        <input type="radio" name="recordModalDuration" value="0-600" data-record-modal-filter>
                                        <input type="radio" name="recordModalDuration" value="600-1800" data-record-modal-filter>
                                        <input type="radio" name="recordModalDuration" value="1800-3600" data-record-modal-filter>
                                        <input type="radio" name="recordModalDuration" value="gt3600" data-record-modal-filter>
                                    </div>
                                </div>

                                {{-- 6. LEARNING HOUR --}}
                                <div class="record-filter-section border-bottom pb-3">
                                    <label class="form-label fw-bold text-uppercase text-muted small mb-2">LEARNING HOUR</label>
                                    <div class="d-flex flex-wrap gap-3">
                                        @foreach (['all' => 'All', '0-1' => '< 1 Hour', '1-3' => '1 - 3 Hours', 'gt3' => '> 3 Hours'] as $value => $label)
                                            <label class="form-check form-check-inline mb-0">
                                                <input class="form-check-input" type="radio" name="recordModalLearningHour" value="{{ $value }}" data-record-modal-filter {{ $value === 'all' ? 'checked' : '' }}>
                                                <span class="form-check-label">{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- 7. SCORE TEST --}}
                                <div class="record-filter-section border-bottom pb-3">
                                    <label class="form-label fw-bold text-uppercase text-muted small mb-2">SCORE TEST</label>
                                    <div class="d-flex flex-wrap gap-3">
                                        @foreach (['all' => 'All', 'none' => 'No Assessment', 'lt60' => '< 60', '60-79' => '60 - 79', '80-100' => '80 - 100'] as $value => $label)
                                            <label class="form-check form-check-inline mb-0">
                                                <input class="form-check-input" type="radio" name="recordModalScoreTest" value="{{ $value }}" data-record-modal-filter {{ $value === 'all' ? 'checked' : '' }}>
                                                <span class="form-check-label">{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- 8. CERTIFICATE --}}
                                <div class="record-filter-section">
                                    <label class="form-label fw-bold text-uppercase text-muted small mb-2">CERTIFICATE</label>
                                    <div class="d-flex flex-wrap gap-3">
                                        @foreach (['all' => 'All', 'ready' => 'Available', 'none' => 'Not Available'] as $value => $label)
                                            <label class="form-check form-check-inline mb-0">
                                                <input class="form-check-input" type="radio" name="recordModalCertificate" value="{{ $value }}" data-record-modal-filter {{ $value === 'all' ? 'checked' : '' }}>
                                                <span class="form-check-label">{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="modal-footer record-filter-modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-learning-outline" id="recordClearFilter">
                        <i class="ti ti-refresh"></i><span>Reset</span>
                    </button>
                    <button type="button" class="btn btn-primary btn-learning-primary" id="recordApplyFilter">
                        <i class="ti ti-search"></i><span>Apply Filter</span>
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
