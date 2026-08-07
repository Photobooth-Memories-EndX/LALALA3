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
/* Classification Badges */
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

/* Certificate Action Buttons */
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

/* Assessment Score Badges */
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

/* Filter Toolbar & Modal */
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

/* Polished More Filter Modal Dialog (Matching Learning Ongoing Reference) */
.record-filter-modal {
    border-radius: 16px !important;
    border: none !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1) !important;
    overflow: hidden;
}
.record-filter-modal .modal-header {
    background-color: #ffffff !important;
    border-bottom: 1px solid #f0f2f5 !important;
    padding: 1.25rem 2rem !important;
}
.record-filter-modal .modal-title {
    font-size: 1.25rem !important;
    font-weight: 700 !important;
    color: #1e293b !important;
}
.record-filter-modal .modal-body {
    max-height: 72vh;
    overflow-y: auto;
    padding: 1.5rem 2rem !important;
    background-color: #ffffff !important;
}

/* Custom Smooth Scrollbar for Modal Body */
.record-filter-modal .modal-body::-webkit-scrollbar {
    width: 6px;
}
.record-filter-modal .modal-body::-webkit-scrollbar-track {
    background: #f8fafc;
    border-radius: 999px;
}
.record-filter-modal .modal-body::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 999px;
}
.record-filter-modal .modal-body::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Modal Divider Sections (Matching Learning Ongoing Reference Image) */
.record-filter-divider-section {
    padding-bottom: 1.25rem;
    margin-bottom: 1.25rem;
    border-bottom: 1px solid #f0f2f5;
}
.record-filter-section-title {
    font-size: 0.75rem !important;
    font-weight: 700 !important;
    letter-spacing: 0.5px !important;
    text-transform: uppercase !important;
    color: #475569 !important;
    margin-bottom: 0.85rem !important;
}
.record-filter-sublabel {
    font-size: 0.85rem !important;
    font-weight: 600 !important;
    color: #334155 !important;
    margin-bottom: 0.5rem !important;
}

/* Form Check / Input Customization inside Modal */
.record-filter-divider-section .form-check {
    margin-bottom: 0 !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.45rem !important;
    margin-right: 1.5rem !important;
}
.record-filter-divider-section .form-check-label {
    font-size: 0.875rem !important;
    color: #334155 !important;
    font-weight: 500 !important;
    cursor: pointer !important;
    user-select: none !important;
}
.record-filter-divider-section .form-check-input {
    cursor: pointer !important;
    width: 1.05rem !important;
    height: 1.05rem !important;
    margin-top: 0 !important;
}

/* Sort By Full Width Select */
.record-filter-select {
    width: 100% !important;
    border-radius: 8px !important;
    border: 1px solid #d1d5db !important;
    padding: 0.5rem 0.85rem !important;
    font-size: 0.875rem !important;
    color: #334155 !important;
    background-color: #ffffff !important;
    height: 42px !important;
}
.record-filter-select:focus {
    border-color: #206bc4 !important;
    box-shadow: 0 0 0 3px rgba(32, 107, 196, 0.15) !important;
}

/* Modal Footer Buttons */
.record-filter-modal-footer {
    background-color: #ffffff !important;
    border-top: 1px solid #f0f2f5 !important;
    padding: 1rem 2rem !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
}
.record-filter-modal-footer .btn {
    min-height: 40px !important;
    border-radius: 8px !important;
    padding: 0.5rem 1.25rem !important;
    font-weight: 600 !important;
    font-size: 0.875rem !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 0.5rem !important;
}
</style>

        <div class="card border-0 shadow-sm rounded-4 learning-record-card">
            <div class="card-body p-4 border-bottom">
                <x-learning.record-filter :filter-options="$filterOptions" />
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
