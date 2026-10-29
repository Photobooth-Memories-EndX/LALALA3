@php
    $types = collect($filterOptions['types'] ?? []);
    $classifications = collect($filterOptions['classifications'] ?? []);
    $years = collect($filterOptions['years'] ?? []);
    $categories = collect($filterOptions['categories'] ?? []);
    $subClassifications = collect($filterOptions['subClassifications'] ?? []);
    $batches = collect($filterOptions['batches'] ?? []);
    $deliveryMethods = collect($filterOptions['deliveryMethods'] ?? []);
    $providers = collect($filterOptions['providers'] ?? []);

    $filterType = request('record_filter_type', request('type', 'all'));
    $filterClassification = request('record_filter_classification', request('classification', 'all'));
    $filterYear = request('record_filter_year', request('year', 'all'));
    $search = request('record_search', request('search', request('q', '')));

    // More Filters
    $filterCategory = request('record_filter_category', request('category', 'all'));
    $filterSubClassification = request('record_filter_sub_classification', request('sub_classification', 'all'));
    $filterBatch = request('record_filter_batch', request('batch', 'all'));
    $filterDeliveryMethod = request('record_filter_delivery_method', request('delivery_method', 'all'));
    $filterProvider = request('record_filter_provider', request('provider', 'all'));
    $filterAttendance = request('record_filter_attendance', request('attendance', 'all'));
    $filterCertificate = request('record_filter_certificate', request('certificate', 'all'));

    $activeMoreFilterCount = 0;
    if ($filterCategory !== 'all') $activeMoreFilterCount++;
    if ($filterSubClassification !== 'all') $activeMoreFilterCount++;
    if ($filterBatch !== 'all') $activeMoreFilterCount++;
    if ($filterDeliveryMethod !== 'all') $activeMoreFilterCount++;
    if ($filterProvider !== 'all') $activeMoreFilterCount++;
    if ($filterAttendance !== 'all') $activeMoreFilterCount++;
    if ($filterCertificate !== 'all') $activeMoreFilterCount++;

    $isFiltered = ($search !== '' || $filterType !== 'all' || $filterClassification !== 'all' || $filterYear !== 'all' || $activeMoreFilterCount > 0);
    $hasOfflineRecords = (bool) ($hasOfflineRecords ?? false);
@endphp

<template data-learning-record-hero>
    <x-shared.hero-learning-record
        recordMode="offline"
        :stats="$recordStats ?? [
            ['label' => 'Course Completed', 'value' => number_format($recordStats['courseCompleted'] ?? 0)],
            ['label' => 'Playlist Completed', 'value' => number_format($recordStats['playlistCompleted'] ?? 0)],
            ['label' => 'Learning Hours', 'value' => number_format($recordStats['totalLearningHours'] ?? 0, 2)],
        ]"
    />
</template>
<style>
    .record-offline-title-link {
        display: inline-block;
        background: transparent;
        border: 0;
        padding: 0;
        margin: 0;
        font-family: inherit;
        font-size: inherit;
        font-weight: 600;
        line-height: 1.35;
        color: inherit;
        text-align: left;
        text-decoration: none;
        cursor: pointer;
        transition: color 0.15s ease-in-out, text-decoration-color 0.15s ease-in-out;
    }
    .record-offline-title-link:hover {
        color: var(--tblr-primary, #206bc4) !important;
        text-decoration: underline !important;
    }
    .record-offline-title-link:focus-visible {
        outline: 2px solid var(--tblr-primary, #206bc4);
        outline-offset: 2px;
        border-radius: 2px;
    }
    button.attendance-detail-trigger {
        border: 0;
        cursor: pointer;
        font-family: inherit;
        text-decoration: none;
        user-select: none;
        transition: transform 0.15s ease, box-shadow 0.15s ease, filter 0.15s ease;
    }
    button.attendance-detail-trigger:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.12);
        filter: brightness(0.95);
    }
    button.attendance-detail-trigger:active {
        transform: translateY(0);
        box-shadow: none;
        filter: brightness(0.9);
    }
    button.attendance-detail-trigger:focus-visible {
        outline: 2px solid var(--tblr-primary, #206bc4);
        outline-offset: 2px;
        border-radius: 4px;
    }
    /* Table Sticky Freeze Header & Scrollable Container */
    .table-responsive.learning-record-scroll,
    .learning-record-scroll {
        max-height: 70vh !important;
        overflow-y: auto !important;
        overflow-x: auto !important;
        position: relative !important;
        width: 100% !important;
        -webkit-overflow-scrolling: touch;
    }
    .learning-record-table {
        min-width: 1280px !important;
        width: 100% !important;
    }
    .learning-record-table thead th,
    #learningRecordOfflineTable thead th {
        position: sticky !important;
        top: 0 !important;
        z-index: 10 !important;
        background-color: #f8fafc !important; /* Solid neutral background so rows pass underneath cleanly */
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05); /* Subtle bottom border line when sticky */
    }
    [data-bs-theme="dark"] .learning-record-table thead th,
    [data-bs-theme="dark"] #learningRecordOfflineTable thead th,
    body.dark-mode .learning-record-table thead th,
    body.dark-mode #learningRecordOfflineTable thead th,
    body.theme-dark .learning-record-table thead th,
    body.theme-dark #learningRecordOfflineTable thead th {
        background-color: #1e293b !important;
        color: #94a3b8 !important;
    }

    /* Certificate Action Buttons (Standardized with Online Reference) */
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
    .btn-learning-certificate {
        background-color: #16a34a !important;
        border: 1px solid #16a34a !important;
        color: #ffffff !important;
        box-shadow: 0 2px 6px rgba(22, 163, 74, 0.18) !important;
        cursor: pointer !important;
        line-height: 1 !important;
        text-decoration: none !important;
        transition: transform 200ms ease, box-shadow 200ms ease, background-color 200ms ease, border-color 200ms ease !important;
    }
    .btn-learning-certificate > i {
        margin: 0 !important;
        font-size: 1.15rem !important;
        line-height: 1 !important;
        color: #ffffff !important;
    }
    .btn-learning-certificate:hover,
    .btn-learning-certificate:focus-visible {
        border-color: #15803d !important;
        background-color: #15803d !important;
        color: #ffffff !important;
        box-shadow: 0 4px 10px rgba(22, 163, 74, 0.22) !important;
        transform: translateY(-1px);
    }
    .btn-learning-certificate:active {
        border-color: #166534 !important;
        background-color: #166534 !important;
        box-shadow: 0 1px 3px rgba(22, 101, 52, 0.2) !important;
        transform: scale(0.96);
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

    /* Polished More Filter Modal Dialog (Standardized with Online Reference) */
    #recordOfflineMoreFilterModal .modal-dialog {
        max-width: 760px;
    }
    #recordOfflineMoreFilterModal .record-filter-modal {
        border-radius: 16px !important;
        border: none !important;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1) !important;
        overflow: hidden;
    }
    #recordOfflineMoreFilterModal .modal-header {
        background-color: #ffffff !important;
        border-bottom: 1px solid #f0f2f5 !important;
        padding: 1.25rem 2rem !important;
    }
    #recordOfflineMoreFilterModal .modal-title {
        font-size: 1.25rem !important;
        font-weight: 700 !important;
        color: #1e293b !important;
    }
    #recordOfflineMoreFilterModal .modal-body {
        max-height: 72vh;
        overflow-y: auto;
        padding: 1.5rem 2rem !important;
        background-color: #ffffff !important;
    }
    #recordOfflineMoreFilterModal .modal-body::-webkit-scrollbar {
        width: 6px;
    }
    #recordOfflineMoreFilterModal .modal-body::-webkit-scrollbar-track {
        background: #f8fafc;
        border-radius: 999px;
    }
    #recordOfflineMoreFilterModal .modal-body::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 999px;
    }
    #recordOfflineMoreFilterModal .modal-body::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    #recordOfflineMoreFilterModal .record-filter-select {
        width: 100% !important;
        border-radius: 8px !important;
        border: 1px solid #d1d5db !important;
        padding: 0.5rem 0.85rem !important;
        font-size: 0.875rem !important;
        color: #334155 !important;
        background-color: #ffffff !important;
        height: 42px !important;
    }
    #recordOfflineMoreFilterModal .record-filter-select:focus {
        border-color: #206bc4 !important;
        box-shadow: 0 0 0 3px rgba(32, 107, 196, 0.15) !important;
    }
    #recordOfflineMoreFilterModal .modal-footer {
        background-color: #ffffff !important;
        border-top: 1px solid #f0f2f5 !important;
        padding: 1rem 2rem !important;
    }
    #recordOfflineMoreFilterModal .modal-footer .btn {
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
    :is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode) #recordOfflineMoreFilterModal .record-filter-modal {
        background-color: var(--tblr-bg-surface-dark, #1e293b) !important;
        border-color: #334155 !important;
    }
    :is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode) #recordOfflineMoreFilterModal .modal-header {
        background-color: var(--tblr-bg-surface-dark, #1e293b) !important;
        border-bottom-color: #334155 !important;
    }
    :is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode) #recordOfflineMoreFilterModal .modal-title {
        color: #f1f5f9 !important;
    }
    :is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode) #recordOfflineMoreFilterModal .modal-header .btn-close {
        filter: invert(1) grayscale(100%) brightness(200%);
    }
    :is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode) #recordOfflineMoreFilterModal .modal-body,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode) #recordOfflineMoreFilterModal .record-more-filters {
        background-color: var(--tblr-bg-surface-dark, #1e293b) !important;
        color: #f1f5f9 !important;
    }
    :is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode) #recordOfflineMoreFilterModal .modal-body::-webkit-scrollbar-track {
        background: #0f172a;
    }
    :is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode) #recordOfflineMoreFilterModal .modal-body::-webkit-scrollbar-thumb {
        background: #475569;
    }
    :is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode) #recordOfflineMoreFilterModal .learning-filter-group .form-label {
        color: #f1f5f9 !important;
    }
    :is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode) #recordOfflineMoreFilterModal .record-filter-select {
        background-color: #192d45 !important;
        border-color: rgba(255, 255, 255, 0.12) !important;
        color: #f1f5f9 !important;
    }
    :is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode) #recordOfflineMoreFilterModal .modal-footer {
        background-color: #152235 !important;
        border-top: 1px solid rgba(255, 255, 255, 0.08) !important;
    }

    /* Compact Dynamic Quick Filter & Toolbar Alignment for Offline Record */
    #recordOfflineFilterBar .fast-filter-toolbar {
        display: flex !important;
        align-items: center !important;
        flex-wrap: nowrap !important;
        gap: 8px !important;
        width: 100% !important;
    }
    #recordOfflineFilterBar .learning-quick-dropdowns {
        display: flex !important;
        align-items: center !important;
        flex-wrap: nowrap !important;
        flex: 0 1 auto !important;
        gap: 6px !important;
        width: auto !important;
    }
    #recordOfflineFilterBar .learning-quick-dropdowns .dropdown {
        flex: 0 0 auto !important;
    }
    #recordOfflineFilterBar .learning-quick-dropdowns .dropdown-toggle {
        width: auto !important;
        width: max-content !important;
        min-width: 0 !important;
        max-width: 220px !important;
        padding: 0.375rem 0.65rem !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 4px !important;
        white-space: nowrap !important;
        font-size: 0.8125rem !important;
        line-height: 1.4 !important;
    }
    #recordOfflineFilterBar .learning-quick-dropdowns .dropdown-toggle .fw-semibold {
        display: inline-block !important;
        max-width: 125px !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
        vertical-align: middle !important;
    }
    #recordOfflineFilterBar .learning-quick-dropdowns .dropdown-toggle::after {
        margin-left: 4px !important;
        flex-shrink: 0 !important;
    }
    #recordOfflineFilterBar .learning-quick-dropdowns .dropdown-menu {
        min-width: 100% !important;
        width: max-content !important;
        max-width: 320px !important;
    }
    #recordOfflineFilterBar .record-toolbar-search {
        flex: 1 1 auto !important;
        min-width: 140px !important;
        width: auto !important;
    }
    #recordOfflineFilterBar .record-toolbar-search .form-control {
        width: 100% !important;
        min-height: 38px !important;
        height: 38px !important;
    }
    #recordOfflineFilterBar .record-filter-actions {
        flex: 0 0 auto !important;
        display: flex !important;
        align-items: center !important;
        gap: 6px !important;
    }

    @media (max-width: 767.98px) {
        #recordOfflineFilterBar .fast-filter-toolbar {
            flex-wrap: wrap !important;
            gap: 8px !important;
        }
        #recordOfflineFilterBar .learning-quick-dropdowns {
            width: 100% !important;
            flex: 1 1 100% !important;
            flex-wrap: wrap !important;
        }
        #recordOfflineFilterBar .record-toolbar-search {
            width: 100% !important;
            min-width: 100% !important;
            flex: 1 1 100% !important;
        }
        #recordOfflineFilterBar .record-filter-actions {
            width: 100% !important;
            justify-content: flex-end !important;
        }
    }
</style>

<div class="learning-record-shell">
    {{-- Filter Card Offline --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 learning-record-card">
        <div class="card-body p-4 border-bottom">
            <div class="record-filter-shell" id="recordOfflineFilterBar">
                <div class="learning-filter-heading fast-filter-header">
                    <div class="fast-filter-header-copy">
                        <h3 class="card-title fast-filter-title">Quick Filter</h3>
                    </div>
                    <x-learning.record-mode-switch mode="offline" />
                </div>

                <div class="record-fast-filter-section">
                    <div class="fast-filter-toolbar">
                        <div class="learning-quick-dropdowns learning-fast-filter">
                            {{-- Dropdown Type --}}
                            <div class="dropdown">
                                <button type="button"
                                        class="btn btn-filter-chip learning-fast-filter-control dropdown-toggle {{ $filterType !== 'all' ? 'active' : '' }}"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                    Type: <span class="fw-semibold">{{ $filterType === 'all' ? 'All' : $filterType }}</span>
                                </button>
                                <div class="dropdown-menu">
                                    <a href="#"
                                       class="dropdown-item record-offline-filter-item {{ $filterType === 'all' ? 'active' : '' }}"
                                       data-param="record_filter_type"
                                       data-value="all">
                                        All Types
                                    </a>
                                    @foreach ($types as $typeOption)
                                        <a href="#"
                                           class="dropdown-item record-offline-filter-item {{ (string) $filterType === (string) $typeOption ? 'active' : '' }}"
                                           data-param="record_filter_type"
                                           data-value="{{ $typeOption }}">
                                            {{ $typeOption }}
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
                                       class="dropdown-item record-offline-filter-item {{ $filterClassification === 'all' ? 'active' : '' }}"
                                       data-param="record_filter_classification"
                                       data-value="all">
                                        All Classification
                                    </a>
                                    @foreach ($classifications as $classificationOption)
                                        <a href="#"
                                           class="dropdown-item record-offline-filter-item {{ (string) $filterClassification === (string) $classificationOption ? 'active' : '' }}"
                                           data-param="record_filter_classification"
                                           data-value="{{ $classificationOption }}">
                                            {{ $classificationOption }}
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
                                       class="dropdown-item record-offline-filter-item {{ $filterYear === 'all' ? 'active' : '' }}"
                                       data-param="record_filter_year"
                                       data-value="all">
                                        All Years
                                    </a>
                                    @foreach ($years as $yearOption)
                                        <a href="#"
                                           class="dropdown-item record-offline-filter-item {{ (string) $filterYear === (string) $yearOption ? 'active' : '' }}"
                                           data-param="record_filter_year"
                                           data-value="{{ $yearOption }}">
                                            {{ $yearOption }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Search Input --}}
                        <div class="record-toolbar-search">
                            <label class="visually-hidden" for="recordOfflineSearch">Search offline records</label>
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                                <input type="text" class="form-control" id="recordOfflineSearch"
                                       value="{{ $search }}"
                                       placeholder="Search offline training, batch, provider..." autocomplete="off">
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="record-filter-actions d-flex align-items-center gap-2">
                            {{-- 1. Search Button --}}
                            <button type="button" class="btn btn-primary btn-learning-primary record-offline-apply-btn record-filter-action-btn"
                                    id="recordOfflineApplyBtn"
                                    title="Search & Apply Filter"
                                    aria-label="Search & Apply Filter">
                                <i class="ti ti-search" aria-hidden="true"></i>
                            </button>

                            {{-- 2. More Filter Button --}}
                            <button type="button"
                                    class="btn btn-outline-primary btn-learning-secondary record-filter-action-btn position-relative {{ $activeMoreFilterCount > 0 ? 'active' : '' }}"
                                    id="recordOfflineMoreFilterBtn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#recordOfflineMoreFilterModal"
                                    title="More Filters"
                                    aria-label="More Filters">
                                <i class="ti ti-adjustments-horizontal" aria-hidden="true"></i>
                                @if ($activeMoreFilterCount > 0)
                                    <span class="badge bg-primary text-white position-absolute top-0 start-100 translate-middle rounded-pill" style="font-size: 10px; min-width: 18px; height: 18px; padding: 0 4px; display: inline-flex; align-items: center; justify-content: center;">{{ $activeMoreFilterCount }}</span>
                                @endif
                            </button>

                            {{-- 3. Reset Button --}}
                            <button type="button" class="btn btn-outline-secondary btn-learning-outline record-offline-reset-btn record-filter-action-btn"
                                    id="recordOfflineResetBtn"
                                    title="Reset Filter"
                                    aria-label="Reset Filter">
                                <i class="ti ti-refresh" aria-hidden="true"></i>
                            </button>

                            {{-- Hidden export anchor for test / script compatibility --}}
                            <a href="{{ route('user.learning.record.export', array_merge(request()->all(), ['source' => 'offline'])) }}"
                               id="recordOfflineExportBtn"
                               class="d-none"></a>
                        </div>
                    </div>

                    {{-- Active More Filter Badges --}}
                    @if ($activeMoreFilterCount > 0)
                        <div class="d-flex align-items-center flex-wrap gap-1 mt-2 pt-2 border-top">
                            <span class="text-muted small me-1">Active filters:</span>
                            @if ($filterCategory !== 'all')
                                <span class="badge bg-blue-lt d-inline-flex align-items-center gap-1">
                                    Category: {{ $filterCategory }}
                                    <a href="#" class="text-reset text-decoration-none record-offline-clear-single-filter" data-param="record_filter_category">&times;</a>
                                </span>
                            @endif
                            @if ($filterSubClassification !== 'all')
                                <span class="badge bg-blue-lt d-inline-flex align-items-center gap-1">
                                    Sub Class: {{ $filterSubClassification }}
                                    <a href="#" class="text-reset text-decoration-none record-offline-clear-single-filter" data-param="record_filter_sub_classification">&times;</a>
                                </span>
                            @endif
                            @if ($filterBatch !== 'all')
                                <span class="badge bg-blue-lt d-inline-flex align-items-center gap-1">
                                    Batch: {{ $filterBatch }}
                                    <a href="#" class="text-reset text-decoration-none record-offline-clear-single-filter" data-param="record_filter_batch">&times;</a>
                                </span>
                            @endif
                            @if ($filterDeliveryMethod !== 'all')
                                <span class="badge bg-blue-lt d-inline-flex align-items-center gap-1">
                                    Method: {{ $filterDeliveryMethod }}
                                    <a href="#" class="text-reset text-decoration-none record-offline-clear-single-filter" data-param="record_filter_delivery_method">&times;</a>
                                </span>
                            @endif
                            @if ($filterProvider !== 'all')
                                <span class="badge bg-blue-lt d-inline-flex align-items-center gap-1">
                                    Provider: {{ $filterProvider }}
                                    <a href="#" class="text-reset text-decoration-none record-offline-clear-single-filter" data-param="record_filter_provider">&times;</a>
                                </span>
                            @endif
                            @if ($filterAttendance !== 'all')
                                <span class="badge bg-blue-lt d-inline-flex align-items-center gap-1">
                                    Attendance: {{ ucfirst($filterAttendance) }}
                                    <a href="#" class="text-reset text-decoration-none record-offline-clear-single-filter" data-param="record_filter_attendance">&times;</a>
                                </span>
                            @endif
                            @if ($filterCertificate !== 'all')
                                <span class="badge bg-blue-lt d-inline-flex align-items-center gap-1">
                                    Certificate: {{ ucfirst($filterCertificate) }}
                                    <a href="#" class="text-reset text-decoration-none record-offline-clear-single-filter" data-param="record_filter_certificate">&times;</a>
                                </span>
                            @endif
                            <a href="#" class="small text-danger ms-1 text-decoration-none" id="recordOfflineClearAllMoreFilters">Clear all</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Table or Empty State Offline --}}
        @if ($rows->isEmpty())
            <div class="card-body text-center py-5">
                <div class="py-4">
                    <div class="text-muted mb-3">
                        @if ($hasOfflineRecords && $isFiltered)
                            <i class="ti ti-search-off text-muted" style="font-size: 3rem; opacity: 0.5;"></i>
                        @else
                            <i class="ti ti-folder-off text-muted" style="font-size: 3rem; opacity: 0.5;"></i>
                        @endif
                    </div>
                    @if ($hasOfflineRecords && $isFiltered)
                        <h4 class="fw-bold text-dark mb-1">No matching records found.</h4>
                        <p class="text-muted mb-3">Try adjusting your filters or search criteria.</p>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="recordOfflineEmptyResetBtn">
                            <i class="ti ti-refresh me-1"></i> Reset Filters
                        </button>
                    @else
                        <h4 class="fw-bold text-dark mb-1">No offline learning records found.</h4>
                        <p class="text-muted mb-0">Your offline training records will appear here when they become available.</p>
                    @endif
                </div>
            </div>
        @else
            <div class="card-body p-0">
                <div class="table-responsive learning-record-scroll">
                    <table class="table table-vcenter table-hover card-table mb-0 learning-record-table"
                           id="learningRecordOfflineTable" style="width:100%">
                        <thead>
                            <tr>
                                <th class="text-muted text-center record-sortable" style="width: 3%" data-sort-key="no" data-sort-type="number">
                                    <span class="record-sort-header">
                                        <span class="record-sort-label">No</span>
                                        <span class="record-sort-icons" aria-hidden="true"><span class="record-sort-asc">&#9650;</span><span class="record-sort-desc">&#9660;</span></span>
                                    </span>
                                </th>
                                <th class="text-muted text-center record-sortable" style="width: 24%" data-sort-key="title" data-sort-type="text">
                                    <span class="record-sort-header">
                                        <span class="record-sort-label">Course Title</span>
                                        <span class="record-sort-icons" aria-hidden="true"><span class="record-sort-asc">&#9650;</span><span class="record-sort-desc">&#9660;</span></span>
                                    </span>
                                </th>
                                <th class="text-muted text-center record-sortable" style="width: 7%" data-sort-key="type" data-sort-type="text">
                                    <span class="record-sort-header">
                                        <span class="record-sort-label">Type</span>
                                        <span class="record-sort-icons" aria-hidden="true"><span class="record-sort-asc">&#9650;</span><span class="record-sort-desc">&#9660;</span></span>
                                    </span>
                                </th>
                                <th class="text-muted text-center record-sortable" style="width: 7%" data-sort-key="category" data-sort-type="text">
                                    <span class="record-sort-header">
                                        <span class="record-sort-label">Category</span>
                                        <span class="record-sort-icons" aria-hidden="true"><span class="record-sort-asc">&#9650;</span><span class="record-sort-desc">&#9660;</span></span>
                                    </span>
                                </th>
                                <th class="text-muted text-center record-sortable" style="width: 9%" data-sort-key="classification" data-sort-type="text">
                                    <span class="record-sort-header">
                                        <span class="record-sort-label">Main Classification</span>
                                        <span class="record-sort-icons" aria-hidden="true"><span class="record-sort-asc">&#9650;</span><span class="record-sort-desc">&#9660;</span></span>
                                    </span>
                                </th>
                                <th class="text-muted text-center record-sortable" style="width: 7%" data-sort-key="duration" data-sort-type="duration">
                                    <span class="record-sort-header">
                                        <span class="record-sort-label">Course Duration</span>
                                        <span class="record-sort-icons" aria-hidden="true"><span class="record-sort-asc">&#9650;</span><span class="record-sort-desc">&#9660;</span></span>
                                    </span>
                                </th>
                                <th class="text-muted text-center record-sortable" style="width: 8%" data-sort-key="learning-hour" data-sort-type="number">
                                    <span class="record-sort-header">
                                        <span class="record-sort-label">Learning Hour</span>
                                        <span class="record-sort-icons" aria-hidden="true"><span class="record-sort-asc">&#9650;</span><span class="record-sort-desc">&#9660;</span></span>
                                    </span>
                                </th>
                                <th class="text-muted text-center record-sortable" style="width: 7%" data-sort-key="progress" data-sort-type="number">
                                    <span class="record-sort-header">
                                        <span class="record-sort-label">Progress</span>
                                        <span class="record-sort-icons" aria-hidden="true"><span class="record-sort-asc">&#9650;</span><span class="record-sort-desc">&#9660;</span></span>
                                    </span>
                                </th>
                                <th class="text-muted record-sortable" style="width: 11%" data-sort-key="finish" data-sort-type="text">
                                    <span class="record-sort-header">
                                        <span class="record-sort-label">Date Time</span>
                                        <span class="record-sort-icons" aria-hidden="true"><span class="record-sort-asc">&#9650;</span><span class="record-sort-desc">&#9660;</span></span>
                                    </span>
                                </th>
                                <th class="text-muted text-center record-sortable" style="width: 6%" data-sort-key="attendance" data-sort-type="text">
                                    <span class="record-sort-header">
                                        <span class="record-sort-label">Attendance</span>
                                        <span class="record-sort-icons" aria-hidden="true"><span class="record-sort-asc">&#9650;</span><span class="record-sort-desc">&#9660;</span></span>
                                    </span>
                                </th>
                                <th class="text-muted text-center record-sortable" style="width: 6%" data-sort-key="score" data-sort-type="number">
                                    <span class="record-sort-header">
                                        <span class="record-sort-label">Assessment Score (L2)</span>
                                        <span class="record-sort-icons" aria-hidden="true"><span class="record-sort-asc">&#9650;</span><span class="record-sort-desc">&#9660;</span></span>
                                    </span>
                                </th>

                                <th class="text-muted text-center" style="width: 5%">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $index => $row)
                            @php
                                $offSortAttRaw = $row['attendance_status'] ?? $row['attendance'] ?? 'No';
                                $offSortAtt = ($offSortAttRaw === true || $offSortAttRaw === 1 || in_array(strtolower(trim((string) $offSortAttRaw)), ['1', 'true', 'present', 'yes'], true)) ? 'yes' : 'no';
                                $offSortScore = $row['score'] ?? $row['assessment_score'] ?? '';
                                $offSortCert = (($row['has_certificate'] ?? false) || ($row['cert_state'] ?? '') === 'ready') ? 'ready' : 'none';
                            @endphp
                            <tr class="record-row record-row-offline"
                                data-sort-no="{{ $loop->iteration }}"
                                data-sort-title="{{ mb_strtolower((string) ($row['title'] ?? '-')) }}"
                                data-sort-type="-"
                                data-sort-category="{{ mb_strtolower((string) ($row['category'] ?? '-')) }}"
                                data-sort-classification="{{ mb_strtolower((string) ($row['classification'] ?? '-')) }}"
                                data-sort-duration="{{ $row['duration_sort'] ?? ($row['duration_seconds'] ?? -1) }}"
                                data-sort-learning-hour="{{ $row['learning_hour_value'] ?? 0 }}"
                                data-sort-progress="{{ (int) ($row['progress'] ?? 0) }}"
                                data-sort-finish="{{ $row['end_datetime'] ?? ($row['finish_date'] ?? '') }}"
                                data-sort-attendance="{{ $offSortAtt }}"
                                data-sort-score="{{ $offSortScore }}"
                                data-sort-certificate="{{ $offSortCert }}">
                                <td class="text-center text-muted record-row-index">{{ $loop->iteration }}</td>
                                <td class="learning-record-course-cell text-center align-middle">
                                    <div class="d-flex flex-column align-items-center justify-content-center text-center w-100">
                                        <span class="fw-bold text-dark text-center fs-6 mb-1">
                                            <button type="button"
                                                    class="record-offline-title-link btn-record-offline-detail text-center text-dark fw-bold p-0 border-0 bg-transparent"
                                                    data-record-id="{{ $row['id'] }}"
                                                    title="Click to view details">
                                                {{ $row['title'] }}
                                            </button>
                                        </span>
                                        @php
                                            $offReq = strtolower(trim((string) ($row['requirement'] ?? 'non-mandatory')));
                                            $isOffMand = !str_contains($offReq, 'non-mandatory') && !str_contains($offReq, 'non mandatory') && !str_contains($offReq, 'optional') && str_contains($offReq, 'mandatory');
                                            $offReqLabel = $isOffMand ? 'Mandatory' : 'Non-Mandatory';
                                        @endphp
                                        <small class="text-muted text-center fs-7">
                                            Requirement: <span class="fw-semibold">{{ $offReqLabel }}</span>
                                        </small>
                                    </div>
                                    {{-- Hidden JSON storage for instant zero-N+1 modal population --}}
                                    <script type="application/json" id="record-offline-data-{{ $row['id'] }}">@json($row)</script>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary-lt learning-record-type-badge">
                                        -
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary-lt text-secondary learning-record-badge">
                                        {{ $row['category'] ?: '—' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-blue-lt learning-classification-badge">
                                        {{ $row['classification'] }}
                                    </span>
                                </td>
                                <td class="text-center text-nowrap">{{ $row['duration'] }}</td>
                                <td class="text-center text-nowrap fw-semibold text-primary">{{ number_format((float)($row['learning_hours'] ?? $row['learning_hour_value'] ?? $row['learning_hour'] ?? 0), 2) }} h</td>
                                <td class="text-center">
                                    @if ($row['progress'] >= 100)
                                        <span class="badge bg-success-lt text-success fw-bold px-2 py-1">100%</span>
                                    @elseif ($row['progress'] > 0)
                                        <span class="badge bg-warning-lt text-warning fw-bold px-2 py-1">{{ $row['progress'] }}%</span>
                                    @else
                                        <span class="badge bg-secondary-lt fw-bold px-2 py-1">0%</span>
                                    @endif
                                </td>
                                @php
                                    $offStartDt = $row['start_datetime'] ?? ($row['start_date'] ?? '');
                                    $offEndDt = $row['end_datetime'] ?? ($row['finish_date'] ?? '');
                                @endphp
                                <td class="text-muted small learning-record-datetime">
                                    <div class="text-xs text-muted">
                                        <div class="learning-record-date-row text-nowrap">Start : {{ $offStartDt !== '' ? \Carbon\Carbon::parse($offStartDt)->format('d/m/y H:i:s') : '-' }}</div>
                                        <div class="learning-record-date-row text-nowrap">End : {{ $offEndDt !== '' ? \Carbon\Carbon::parse($offEndDt)->format('d/m/y H:i:s') : '-' }}</div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    @php
                                        $rawAtt = $row['attendance_status'] ?? $row['attendance'] ?? 'No';
                                        $statusKey = strtolower(trim((string) $rawAtt));
                                        $isAttYes = ($rawAtt === true || $rawAtt === 1 || in_array($statusKey, ['1', 'true', 'present', 'yes'], true));
                                        $attStatus = $isAttYes ? 'Yes' : 'No';
                                        $badgeClass = $isAttYes ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger';
                                        $rawReason = $row['raw_attendance_reason'] ?? ($row['attendance_reason'] !== '-' ? $row['attendance_reason'] : '');
                                        $displayReason = $row['attendance_reason_display'] ?? '';
                                    @endphp
                                    <button type="button"
                                            class="badge {{ $badgeClass }} fw-bold px-2 py-1 attendance-detail-trigger text-decoration-none"
                                            data-record-id="{{ $row['id'] }}"
                                            data-training-title="{{ $row['course_name'] ?: $row['title'] }}"
                                            data-attendance="{{ $attStatus }}"
                                            data-attendance-reason="{{ $rawReason }}"
                                            data-attendance-reason-display="{{ $displayReason }}"
                                            data-training-date="{{ $row['date_display'] ?: '-' }}"
                                            data-venue="{{ $row['venue'] ?: '-' }}"
                                            title="Click to view attendance detail"
                                            aria-label="View attendance detail for {{ $row['title'] }}">
                                        {{ $attStatus }}
                                    </button>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex flex-column align-items-center justify-content-center text-center">
                                        @php
                                            $offScoreVal = $row['score'] ?? $row['assessment_score'] ?? null;
                                        @endphp
                                        @if ($offScoreVal !== null)
                                            @php
                                                $offMinScore = (float) ($row['min_score'] ?? 70);
                                                $offPassed = (float) $offScoreVal >= $offMinScore;
                                            @endphp
                                            @if ($offPassed)
                                                <span class="badge rounded-pill learning-record-badge learning-score-pill" style="background-color: #dcfce7; color: #15803d; font-weight: 600;">Passed</span>
                                                <div class="small fw-semibold mt-1" style="color: #15803d;">Score : {{ $offScoreVal ?? '-' }}</div>
                                            @else
                                                <span class="badge bg-red-lt text-red learning-record-badge learning-score-pill">FAILED</span>
                                                <div class="small text-danger mt-1">Score : {{ $offScoreVal ?? '-' }}</div>
                                                <div class="small text-danger">Passing Grade : {{ (int) $offMinScore }}</div>
                                            @endif
                                        @else
                                            <span class="learning-score-empty-pill">-</span>
                                        @endif
                                    </div>
                                </td>
                                                                <td class="text-center">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-icon btn-ghost-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ti ti-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2 btn-record-detail"
                                                   href="#"
                                                   data-bs-toggle="modal"
                                                   data-bs-target="#recordDetailModal"
                                                   data-record-id="{{ $row['id'] }}"
                                                   data-record-source="offline">
                                                    <i class="ti ti-info-circle"></i> More Information
                                                </a>
                                            </li>
                                            <li>
                                                @if ((($row['has_certificate'] ?? false) || ($row['cert_state'] ?? '') === 'ready') && !empty($row['cert_action_url']))
                                                    <a class="dropdown-item d-flex align-items-center gap-2 text-success" href="{{ $row['cert_action_url'] }}" target="_blank">
                                                        <i class="ti ti-certificate"></i> Generate Certificate
                                                    </a>
                                                @else
                                                    <a class="dropdown-item d-flex align-items-center gap-2 disabled text-muted" href="#" onclick="return false;" style="cursor: not-allowed;">
                                                        <i class="ti ti-certificate"></i> Generate Certificate
                                                    </a>
                                                @endif
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if ($rows->isNotEmpty())
            <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-2">
                    <select class="form-select form-select-sm w-auto" id="recordOfflinePageLength">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <span class="text-muted small" id="recordOfflineTableInfo"></span>
                </div>

                <ul class="pagination pagination-sm mb-0" id="recordOfflinePagination"></ul>
            </div>
        @endif
        @endif
    </div>
</div>

{{-- ========================================================================= --}}
{{-- MODAL: MORE FILTER OFFLINE RECORD                                         --}}
{{-- ========================================================================= --}}
<div class="modal fade" id="recordOfflineMoreFilterModal" tabindex="-1" role="dialog" aria-labelledby="recordOfflineMoreFilterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered learning-filter-modal" role="document">
        <div class="modal-content rounded-4 border-0 shadow record-filter-modal">
            <div class="modal-header border-0 pb-0">
                <h3 class="modal-title fw-semibold d-flex align-items-center gap-1" id="recordOfflineMoreFilterModalLabel" style="font-size: 1.1rem;">
                    <i class="ti ti-adjustments-horizontal text-primary me-1"></i>
                    <span>More Filters — Offline Record</span>
                </h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2 record-more-filters" id="recordOfflineMoreFilters">
                <p class="text-muted extra-small mb-3">
                    All filters can be combined with the search and quick filters above.
                </p>

                <form id="recordOfflineMoreFilterForm">
                    <div class="record-filter-sections d-flex flex-column">

                        {{-- 1. Category --}}
                        <div class="learning-filter-group">
                            <label class="form-label fw-semibold text-dark small mb-2" for="mf_category">Category</label>
                            <div class="w-100">
                                <select class="form-select rounded-3 record-filter-select w-100" id="mf_category" name="record_filter_category">
                                    <option value="all" {{ $filterCategory === 'all' ? 'selected' : '' }}>All Categories</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat }}" {{ (string) $filterCategory === (string) $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <hr class="my-3 text-light-subtle">

                        {{-- 2. Sub Classification --}}
                        <div class="learning-filter-group">
                            <label class="form-label fw-semibold text-dark small mb-2" for="mf_sub_classification">Sub Classification</label>
                            <div class="w-100">
                                <select class="form-select rounded-3 record-filter-select w-100" id="mf_sub_classification" name="record_filter_sub_classification">
                                    <option value="all" {{ $filterSubClassification === 'all' ? 'selected' : '' }}>All Sub Classifications</option>
                                    @foreach ($subClassifications as $subClass)
                                        <option value="{{ $subClass }}" {{ (string) $filterSubClassification === (string) $subClass ? 'selected' : '' }}>{{ $subClass }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <hr class="my-3 text-light-subtle">

                        {{-- 3. Batch --}}
                        <div class="learning-filter-group">
                            <label class="form-label fw-semibold text-dark small mb-2" for="mf_batch">Batch</label>
                            <div class="w-100">
                                <select class="form-select rounded-3 record-filter-select w-100" id="mf_batch" name="record_filter_batch">
                                    <option value="all" {{ $filterBatch === 'all' ? 'selected' : '' }}>All Batches</option>
                                    @foreach ($batches as $bat)
                                        <option value="{{ $bat }}" {{ (string) $filterBatch === (string) $bat ? 'selected' : '' }}>{{ $bat }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <hr class="my-3 text-light-subtle">

                        {{-- 4. Delivery Method --}}
                        <div class="learning-filter-group">
                            <label class="form-label fw-semibold text-dark small mb-2" for="mf_delivery_method">Delivery Method</label>
                            <div class="w-100">
                                <select class="form-select rounded-3 record-filter-select w-100" id="mf_delivery_method" name="record_filter_delivery_method">
                                    <option value="all" {{ $filterDeliveryMethod === 'all' ? 'selected' : '' }}>All Methods</option>
                                    @foreach ($deliveryMethods as $method)
                                        <option value="{{ $method }}" {{ (string) $filterDeliveryMethod === (string) $method ? 'selected' : '' }}>{{ $method }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <hr class="my-3 text-light-subtle">

                        {{-- 5. Provider --}}
                        <div class="learning-filter-group">
                            <label class="form-label fw-semibold text-dark small mb-2" for="mf_provider">Provider</label>
                            <div class="w-100">
                                <select class="form-select rounded-3 record-filter-select w-100" id="mf_provider" name="record_filter_provider">
                                    <option value="all" {{ $filterProvider === 'all' ? 'selected' : '' }}>All Providers</option>
                                    @foreach ($providers as $prov)
                                        <option value="{{ $prov }}" {{ (string) $filterProvider === (string) $prov ? 'selected' : '' }}>{{ $prov }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <hr class="my-3 text-light-subtle">

                        {{-- 6. Attendance --}}
                        <div class="learning-filter-group">
                            <label class="form-label fw-semibold text-dark small mb-2" for="mf_attendance">Attendance</label>
                            <div class="w-100">
                                <select class="form-select rounded-3 record-filter-select w-100" id="mf_attendance" name="record_filter_attendance">
                                    <option value="all" {{ $filterAttendance === 'all' ? 'selected' : '' }}>All</option>
                                    <option value="present" {{ strtolower($filterAttendance) === 'present' ? 'selected' : '' }}>Present</option>
                                    <option value="absent" {{ strtolower($filterAttendance) === 'absent' ? 'selected' : '' }}>Absent</option>
                                </select>
                            </div>
                        </div>

                        <hr class="my-3 text-light-subtle">

                        {{-- 7. Certificate --}}
                        <div class="learning-filter-group">
                            <label class="form-label fw-semibold text-dark small mb-2" for="mf_certificate">Certificate</label>
                            <div class="w-100">
                                <select class="form-select rounded-3 record-filter-select w-100" id="mf_certificate" name="record_filter_certificate">
                                    <option value="all" {{ $filterCertificate === 'all' ? 'selected' : '' }}>All</option>
                                    <option value="certified" {{ in_array(strtolower($filterCertificate), ['certified', 'yes'], true) ? 'selected' : '' }}>Certified (Yes)</option>
                                    <option value="uncertified" {{ in_array(strtolower($filterCertificate), ['uncertified', 'no'], true) ? 'selected' : '' }}>Not Certified (No)</option>
                                </select>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
            <div class="modal-footer border-top pt-3 d-flex justify-content-end align-items-center gap-2">
                <!-- Reset Button (Placed right next to Apply Filter) -->
                <button type="button" class="btn btn-outline-secondary px-3 rounded-3 d-inline-flex align-items-center gap-1" id="btnResetMoreFilterModal">
                    <i class="ti ti-refresh fs-2"></i> Reset
                </button>

                <!-- Apply Filter Button -->
                <button type="button" class="btn btn-primary px-4 rounded-3 d-inline-flex align-items-center gap-1" id="btnApplyMoreFilterModal" style="background-color: #003b6d; border-color: #003b6d;">
                    <i class="ti ti-search fs-2"></i> Apply Filter
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ========================================================================= --}}
{{-- MODAL: ATTENDANCE DETAIL                                                  --}}
{{-- ========================================================================= --}}
<x-learning.record-attendance-modal />

{{-- ========================================================================= --}}
{{-- MODAL: VIEW DETAIL OFFLINE RECORD                                         --}}
{{-- ========================================================================= --}}
<x-learning.record-offline-detail-modal />

{{-- MODAL: FULL RECORD DETAIL (STANDARDIZED) --}}
<x-shared.record-detail-modal />
