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
        ['label' => 'Class',          'width' => '26%', 'sort' => 'title',          'type' => 'text'],
        ['label' => 'Type',           'width' => '9%',  'sort' => 'type',           'type' => 'text'],
        ['label' => 'Classification', 'width' => '12%', 'sort' => 'classification', 'type' => 'text'],
        ['label' => 'Duration',       'width' => '8%',  'sort' => 'duration',       'type' => 'duration'],
        ['label' => 'Progress',       'width' => '9%',  'sort' => 'progress',       'type' => 'number'],
        ['label' => 'Finish Date',    'width' => '12%', 'sort' => 'finish',         'type' => 'text'],
        ['label' => 'Score Test',     'width' => '8%',  'sort' => 'score',          'type' => 'number'],
        ['label' => 'Certificate',    'width' => '12%'],
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

                <span class="badge bg-green-lt text-green">Status: Completed</span>
            </div>
        </div>

        {{-- ── Toolbar filter (client-side — tanpa AJAX, tanpa reload halaman) ── --}}
        <div class="mt-3" id="recordFilterBar">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-5 col-xl-4">
                    <label class="form-label small text-muted mb-1" for="recordSearch">Search</label>
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                        <input type="text" class="form-control" id="recordSearch"
                               placeholder="Search class or classification..." autocomplete="off">
                    </div>
                </div>

                {{-- Quick Filter: dua mode tampilan — Content (Video + Document, default)
                     atau Playlist. Keduanya entity berbeda (progress, assessment,
                     certificate) sehingga tidak pernah dicampur dalam satu mode
                     (learning-record.js). --}}
                <div class="col-12 col-md">
                    <label class="form-label small text-muted mb-1">Quick Filter</label>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="record-chip active" data-record-quick="all">All</button>
                        <button type="button" class="record-chip" data-record-quick="content">Content</button>
                        <button type="button" class="record-chip" data-record-quick="playlist">Playlist</button>
                    </div>
                </div>

                <div class="col-12 col-md-auto">
                    <button type="button" class="btn btn-outline-primary" id="recordMoreFiltersToggle"
                            aria-expanded="false" aria-controls="recordMoreFilters">
                        <i class="ti ti-adjustments-horizontal me-1"></i>
                        More Filters
                        <span class="badge bg-primary text-white ms-1 d-none" id="recordMoreFiltersCount"></span>
                        <i class="ti ti-chevron-down ms-1 record-more-caret"></i>
                    </button>
                </div>
            </div>

            {{-- Panel More Filters — seluruh filter lanjutan lama dipindah ke sini
                 (tidak dihapus). Tersembunyi default, dibuka slideDown (learning-record.js). --}}
            <div class="record-more-filters mt-3" id="recordMoreFilters">
                {{-- data-record-mode: filter hanya relevan untuk mode tsb — mode lain
                     menyembunyikannya dan me-reset nilainya (learning-record.js). --}}
                <div class="row g-2">
                    <div class="col-6 col-md-4 col-xl-2" data-record-mode="content">
                        <label class="form-label small text-muted mb-1" for="recordFilterType">Type</label>
                        <select class="form-select" id="recordFilterType" data-record-filter>
                            <option value="all">All Type</option>
                            @foreach ($types as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-4 col-xl-2" data-record-mode="content">
                        <label class="form-label small text-muted mb-1" for="recordFilterClassification">Classification</label>
                        <select class="form-select" id="recordFilterClassification" data-record-filter>
                            <option value="all">All Classification</option>
                            @foreach ($classifications as $classification)
                                <option value="{{ $classification }}">{{ $classification }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-4 col-xl-2" data-record-mode="playlist">
                        <label class="form-label small text-muted mb-1" for="recordFilterPlaylist">Playlist</label>
                        <select class="form-select" id="recordFilterPlaylist" data-record-filter>
                            <option value="all">All Playlist</option>
                            @foreach ($playlists as $playlist)
                                <option value="{{ $playlist['id'] }}">{{ $playlist['title'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-4 col-xl-2">
                        <label class="form-label small text-muted mb-1" for="recordFilterYear">Year</label>
                        <select class="form-select" id="recordFilterYear" data-record-filter>
                            <option value="all">Year</option>
                            @foreach ($years as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-4 col-xl-2">
                        <label class="form-label small text-muted mb-1" for="recordFilterMonth">Month</label>
                        <select class="form-select" id="recordFilterMonth" data-record-filter>
                            <option value="all">Month</option>
                            @foreach ($months as $num => $name)
                                <option value="{{ $num }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-4 col-xl-2">
                        <label class="form-label small text-muted mb-1" for="recordFilterCertificate">Certificate</label>
                        <select class="form-select" id="recordFilterCertificate" data-record-filter>
                            <option value="all">All Certificate</option>
                            <option value="ready">Certificate Ready</option>
                            <option value="none">Not Available</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-3">
                    <button type="button" class="btn btn-outline-secondary" id="recordClearFilter">
                        <i class="ti ti-refresh me-1"></i>Reset
                    </button>
                    <button type="button" class="btn btn-primary" id="recordApplyFilter">
                        <i class="ti ti-check me-1"></i>Apply
                    </button>
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
                                    {{ $column['label'] }}
                                    <span class="record-sort-icons" aria-hidden="true"><span class="record-sort-asc">&#9650;</span><span class="record-sort-desc">&#9660;</span></span>
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
                            <td colspan="9" class="text-center text-muted py-4">
                                No completed learning found.
                            </td>
                        </tr>
                    @endforelse

                    @if ($rows->isNotEmpty())
                        {{-- Ditampilkan jQuery saat kombinasi filter tidak menghasilkan baris. --}}
                        <tr id="recordNoMatchRow" class="d-none">
                            <td colspan="9" class="text-center text-muted py-4">
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
