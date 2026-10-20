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

    // Kolom tabel; 'sort' = key data-sort-* pada baris (learning-record.js),
    // 'type' = cara membandingkan nilai saat sorting (text/number/duration).
    $columns = [
        ['label' => 'No',                  'width' => '4%',  'sort' => 'no',             'type' => 'number'],
        ['label' => 'Course Title',        'width' => '22%', 'sort' => 'title',          'type' => 'text'],
        ['label' => 'Type',                'width' => '7%',  'sort' => 'type',           'type' => 'text'],
        ['label' => 'Category',            'width' => '7%',  'sort' => 'category',       'type' => 'text'],
        ['label' => 'Main Classification', 'width' => '9%',  'sort' => 'classification', 'type' => 'text'],
        ['label' => 'Duration',            'width' => '7%',  'sort' => 'duration',       'type' => 'duration'],
        ['label' => 'Learning Hour',       'width' => '8%',  'sort' => 'learning-hour',  'type' => 'number'],
        ['label' => 'Progress',            'width' => '7%',  'sort' => 'progress',       'type' => 'number'],
        ['label' => 'Date Time',           'width' => '11%', 'sort' => 'finish',         'type' => 'text'],
        ['label' => 'Attendance',          'width' => '8%',  'sort' => 'attendance',     'type' => 'text'],
        ['label' => 'Score (L2)',          'width' => '7%',  'sort' => 'score',          'type' => 'number'],
        ['label' => 'Certificate',         'width' => '7%',  'sort' => 'certificate',    'type' => 'text'],
    ];
@endphp

<style>
/* Standardized Learning Record Badges */
.learning-record-badge {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    text-align: center !important;
    vertical-align: middle !important;
    min-height: 26px !important;
    min-width: 44px !important;
    padding: 4px 10px !important;
    border-radius: 999px !important;
    font-size: 11.5px !important;
    font-weight: 600 !important;
    line-height: 1.2 !important;
    white-space: nowrap !important;
    box-sizing: border-box !important;
    max-width: 100% !important;
}
/* Compact Learning Record Type Badges (Video, Document, Playlist) under Title */
.learning-record-type-badge {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    text-align: center !important;
    vertical-align: middle !important;
    min-height: 20px !important;
    min-width: unset !important;
    padding: 2px 8px !important;
    border-radius: 4px !important;
    font-size: 11px !important;
    font-weight: 600 !important;
    line-height: 1.2 !important;
    white-space: nowrap !important;
    box-sizing: border-box !important;
    text-decoration: none !important;
}
.learning-record-type-badge.record-playlist-link:hover {
    opacity: 0.85;
    text-decoration: none !important;
}
.learning-classification-badge {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    text-align: center !important;
    vertical-align: middle !important;
    white-space: normal !important;
    overflow-wrap: break-word !important;
    word-break: normal !important;
    max-width: 120px !important;
    min-height: 26px !important;
    padding: 4px 8px !important;
    font-size: 11.5px !important;
    font-weight: 600 !important;
    line-height: 1.18 !important;
    border-radius: 999px !important;
    box-sizing: border-box !important;
}

/* Table Horizontal Scroll & Full Course Title Readability */
.learning-record-scroll {
    overflow-x: auto !important;
    -webkit-overflow-scrolling: touch;
    width: 100% !important;
}
.learning-record-table {
    min-width: 1280px !important;
    width: 100% !important;
}
.learning-record-course-cell {
    min-width: 280px;
    white-space: normal !important;
}
.learning-record-title {
    white-space: normal !important;
    word-break: normal !important;
    overflow-wrap: break-word !important;
    line-height: 1.4 !important;
}
.learning-record-link {
    white-space: normal !important;
    word-break: normal !important;
    overflow-wrap: break-word !important;
    text-decoration: none;
    display: inline;
}
.learning-record-link:hover {
    text-decoration: underline;
}
.learning-record-program {
    font-size: 0.8125rem;
    font-weight: 500;
    color: #64748b;
    line-height: 1.3;
    margin-top: 3px;
    display: block;
}

/* Category Preview Trigger */
.category-preview-trigger {
    cursor: pointer !important;
    user-select: none;
    transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
}
.category-preview-trigger:hover {
    opacity: 0.9;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.12);
}

/* Learning Record Course Hover Popup */
.learning-record-hover-popup {
    position: fixed;
    z-index: 99999;
    background: #ffffff;
    border: 1px solid #dce1e7;
    border-radius: 8px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.14), 0 8px 10px -6px rgba(0, 0, 0, 0.08);
    max-width: 380px;
    min-width: 240px;
    font-size: 0.875rem;
    line-height: 1.4;
    color: #1e293b;
    pointer-events: auto;
    box-sizing: border-box;
}
/* Invisible hover bridges to guarantee continuous hover transition between trigger and popup */
.learning-record-hover-popup::before {
    content: '';
    position: absolute;
    top: -8px;
    left: 0;
    right: 0;
    height: 8px;
    background: transparent;
}
.learning-record-hover-popup::after {
    content: '';
    position: absolute;
    bottom: -8px;
    left: 0;
    right: 0;
    height: 8px;
    background: transparent;
}
.course-preview-popover {
    padding: 12px 14px;
    box-sizing: border-box;
}
.course-preview-popover.course-preview-content {
    padding: 12px 14px;
}
.course-preview-header {
    margin-bottom: 8px;
    padding-bottom: 6px;
    border-bottom: 1px solid #f1f5f9;
}
.course-preview-content .course-preview-header {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}
.course-preview-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #0f172a;
    line-height: 1.35;
    word-break: break-word;
}
.course-preview-body {
    max-height: 220px;
    overflow-y: auto;
    overflow-x: hidden;
    padding-right: 4px;
    box-sizing: border-box;
}
.course-preview-body::-webkit-scrollbar {
    width: 5px;
}
.course-preview-body::-webkit-scrollbar-track {
    background: #f8fafc;
    border-radius: 4px;
}
.course-preview-body::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
.course-preview-body::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
.course-preview-playlist-list {
    margin: 0;
    padding: 0;
    list-style: none;
}
.course-preview-playlist-item {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 5px 6px;
    border-radius: 4px;
    transition: background-color 0.15s ease;
    box-sizing: border-box;
}
.course-preview-playlist-item:hover {
    background-color: #f1f5f9;
}
.course-preview-playlist-number {
    flex: 0 0 auto;
    min-width: 22px;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #64748b;
    line-height: 1.4;
    text-align: right;
    user-select: none;
}
.course-preview-playlist-link {
    flex: 1 1 auto;
    min-width: 0;
    color: #206bc4;
    text-decoration: none;
    font-size: 0.8125rem;
    line-height: 1.4;
    display: block;
    word-break: break-word;
}
.course-preview-playlist-link:hover {
    color: #1a569d;
    text-decoration: underline;
}

/* Dark mode overrides for hover popup */
body.dark-mode .learning-record-hover-popup,
[data-bs-theme="dark"] .learning-record-hover-popup {
    background: #1e293b;
    border-color: #334155;
    color: #f1f5f9;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.4);
}
body.dark-mode .course-preview-header,
[data-bs-theme="dark"] .course-preview-header {
    border-bottom-color: #334155;
}
body.dark-mode .course-preview-title,
[data-bs-theme="dark"] .course-preview-title {
    color: #f8fafc;
}
body.dark-mode .course-preview-body::-webkit-scrollbar-track,
[data-bs-theme="dark"] .course-preview-body::-webkit-scrollbar-track {
    background: #1e293b;
}
body.dark-mode .course-preview-body::-webkit-scrollbar-thumb,
[data-bs-theme="dark"] .course-preview-body::-webkit-scrollbar-thumb {
    background: #475569;
}
body.dark-mode .course-preview-playlist-item:hover,
[data-bs-theme="dark"] .course-preview-playlist-item:hover {
    background-color: #334155;
}
body.dark-mode .course-preview-playlist-number,
[data-bs-theme="dark"] .course-preview-playlist-number {
    color: #94a3b8;
}
body.dark-mode .course-preview-playlist-link,
[data-bs-theme="dark"] .course-preview-playlist-link {
    color: #60a5fa;
}
body.dark-mode .course-preview-playlist-link:hover,
[data-bs-theme="dark"] .course-preview-playlist-link:hover {
    color: #93c5fd;
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
                            <td colspan="12" class="text-center text-muted py-4">
                                No completed learning found.
                            </td>
                        </tr>
                    @endforelse

                    @if ($rows->isNotEmpty())
                        {{-- Ditampilkan jQuery saat kombinasi filter tidak menghasilkan baris. --}}
                        <tr id="recordNoMatchRow" class="d-none">
                            <td colspan="12" class="text-center text-muted py-4">
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

{{-- Inactive / Expired Content Modal --}}
<div class="modal modal-blur fade" id="learningRecordBlockedModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
            <div class="modal-status bg-warning"></div>
            <div class="modal-body text-center py-4">
                <i class="ti ti-alert-triangle text-warning" style="font-size: 3rem;" aria-hidden="true"></i>
                <h3 class="blocked-modal-title mt-3 mb-2 fw-bold">Content Inactive</h3>
                <div class="text-secondary mb-2 fw-medium blocked-course-title"></div>
                <div class="text-muted small blocked-modal-message">This content is currently inactive.</div>
            </div>
            <div class="modal-footer">
                <div class="w-100">
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal" data-dismiss="modal">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

