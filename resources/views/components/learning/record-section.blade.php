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

    // Kolom tabel; 'sort' = key data-sort-* pada baris (learning-record.js),
    // 'type' = cara membandingkan nilai saat sorting (text/number/duration).
    $columns = [
        ['label' => 'No',             'width' => '4%'],
        ['label' => 'Class',          'width' => '23%', 'sort' => 'title',          'type' => 'text'],
        ['label' => 'Type',           'width' => '9%',  'sort' => 'type',           'type' => 'text'],
        ['label' => 'Classification', 'width' => '12%', 'sort' => 'classification', 'type' => 'text'],
        ['label' => 'Duration',       'width' => '8%',  'sort' => 'duration',       'type' => 'duration'],
        ['label' => 'Learning Hour',  'width' => '8%',  'sort' => 'learning-hour',  'type' => 'number'],
        ['label' => 'Progress',       'width' => '9%',  'sort' => 'progress',       'type' => 'number'],
        ['label' => 'Finish Date',    'width' => '12%', 'sort' => 'finish',         'type' => 'text'],
        ['label' => 'Score Test',     'width' => '8%',  'sort' => 'score',          'type' => 'number'],
        ['label' => 'Certificate',    'width' => '11%'],
    ];
@endphp

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body p-4 border-bottom">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h3 class="card-title mb-1">Learning Record</h3>
                <div class="text-muted small">
                    Completed playlists, contents, and certificate status.
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                @if ($certReadyCount > 0)
                    {{-- Klik: terapkan filter "Certificate Ready" + scroll ke tabel (learning-record.js). --}}
                    <button type="button" class="record-cert-ready-card" id="recordCertReadyCard">
                        <i class="ti ti-certificate"></i>
                        <span class="record-cert-ready-count">{{ $certReadyCount }}</span>
                        {{ \Illuminate\Support\Str::plural('Certificate', $certReadyCount) }} Ready
                    </button>
                @endif

                {{-- <span class="badge bg-green-lt text-green">Status: Completed</span> --}}
            </div>
        </div>

        {{-- ── Toolbar filter (client-side — tanpa AJAX, tanpa reload halaman) ── --}}
        <div class="mt-3 record-filter-shell" id="recordFilterBar">
            <div class="record-search-row">
                <div class="w-100">
                    <label class="form-label small text-muted mb-1" for="recordSearch">Search</label>
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                        <input type="text" class="form-control" id="recordSearch"
                               placeholder="Search class, playlist or classification..." autocomplete="off">
                    </div>
                </div>
            </div>

  <div class="row record-fast-filter-row align-items-end">

                {{-- Quick Filter: dua mode tampilan — Content (Video + Document, default)
                     atau Playlist. Keduanya entity berbeda (progress, assessment,
                     certificate) sehingga tidak pernah dicampur dalam satu mode
                     (learning-record.js). --}}
                <div class="col-6 col-md-3 col-xl-2 mb-2">
                    <label class="form-label small text-muted mb-1" for="recordFilterType">Type</label>
                    {{-- Ditambahkan class form-select-sm --}}
                    <select class="form-select form-select-sm" id="recordFilterType" data-record-filter>
                        <option value="all">All</option>
                        <option value="Video">Video</option>
                        <option value="Document">Document</option>
                        <option value="Playlist">Playlist</option>
                    </select>
                </div>

                <div class="col-6 col-md-4 col-xl-3 mb-2">
                    <label class="form-label small text-muted mb-1" for="recordFilterClassification">Classification</label>
                    {{-- Ditambahkan class form-select-sm --}}
                    <select class="form-select form-select-sm" id="recordFilterClassification" data-record-filter>
                        <option value="all">All Classification</option>
                        @foreach ($classifications as $classification)
                            <option value="{{ $classification }}">{{ $classification }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-2 col-xl-2 mb-2">
                    <label class="form-label small text-muted mb-1" for="recordFilterYear">Year</label>
                    {{-- Ditambahkan class form-select-sm --}}
                    <select class="form-select form-select-sm" id="recordFilterYear" data-record-filter>
                        <option value="all">All</option>
                        @foreach ($years as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-3 col-xl-2 mb-2">
                    <label class="form-label small text-muted mb-1" for="recordFilterMonth">Month</label>
                    {{-- Ditambahkan class form-select-sm --}}
                    <select class="form-select form-select-sm" id="recordFilterMonth" data-record-filter>
                        <option value="all">All</option>
                        @foreach ($months as $num => $name)
                            <option value="{{ $num }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

            </div>

            <div class="record-filter-actions">
                {{-- TOMBOL DOWNLOAD EXCEL --}}
                <a href="#" class="btn btn-outline-success record-filter-action-btn" id="btnDownloadExcel">
                    <i class="ti ti-file-spreadsheet"></i>
                    <span>Download Excel</span>
                </a>
                {{-- TOMBOL MORE FILTERS --}}
                <button type="button" class="btn btn-outline-primary record-filter-action-btn" id="recordMoreFiltersToggle"
                        data-toggle="modal" data-target="#recordMoreFiltersModal"
                        aria-controls="recordMoreFiltersModal">
                    <i class="ti ti-adjustments-horizontal"></i>
                    <span>More Filters</span>
                    <span class="badge bg-primary text-white d-none" id="recordMoreFiltersCount"></span>
                </button>
                <button type="button" class="btn btn-primary record-apply-filter record-filter-action-btn">
                    <i class="ti ti-check"></i><span>Apply Filter</span>
                </button>
                <button type="button" class="btn btn-link text-muted record-clear-filter record-filter-reset-link">
                    Reset
                </button>
            </div>

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

                    <div class="record-filter-section">
                        <div class="record-filter-title">Activity Sort</div>
                        @foreach (['newest' => 'Newest Activity', 'oldest' => 'Oldest Activity', 'recent' => 'Recently Accessed', 'longest' => 'Longest Duration', 'shortest' => 'Shortest Duration'] as $value => $label)
                            <label class="form-check">
                                <input class="form-check-input" type="radio" name="recordModalActivitySort" value="{{ $value }}" data-record-modal-filter>
                                <span class="form-check-label">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="record-filter-section">
                        <div class="record-filter-title">Completion Status</div>
                        @foreach (['all' => 'All Progress', 'highest' => 'Highest Progress', 'lowest' => 'Lowest Progress', 'not_started' => 'Not Started', 'in_progress' => 'In Progress', 'almost_done' => 'Almost Done', 'completed' => 'Completed'] as $value => $label)
                            <label class="form-check">
                                <input class="form-check-input" type="radio" name="recordModalCompletion" value="{{ $value }}" data-record-modal-filter {{ $value === 'all' ? 'checked' : '' }}>
                                <span class="form-check-label">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="record-filter-section">
                        <div class="record-filter-title">Learning Status</div>
                        @foreach (['not_started' => 'Not Started', 'ongoing' => 'Ongoing', 'paused' => 'Paused', 'completed' => 'Completed'] as $value => $label)
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" name="recordModalLearningStatus[]" value="{{ $value }}" data-record-modal-filter>
                                <span class="form-check-label">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="record-filter-section">
                        <div class="record-filter-title">Progress</div>
                        @foreach (['0' => '0%', '1-25' => '1-25%', '26-50' => '26-50%', '51-75' => '51-75%', '76-99' => '76-99%', '100' => '100%'] as $value => $label)
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" name="recordModalProgress[]" value="{{ $value }}" data-record-modal-filter>
                                <span class="form-check-label">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

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
                    <button type="button" class="btn btn-link text-muted" id="recordClearFilter">
                        Reset
                    </button>
                    <button type="button" class="btn btn-primary" id="recordApplyFilter">
                        <i class="ti ti-check mr-1"></i>Apply Filter
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
