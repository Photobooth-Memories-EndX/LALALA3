@php
    $sources = $filterOptions['sources'] ?? ['all' => 'All Sources', 'online' => 'Online', 'offline' => 'Offline'];
    $types = collect($filterOptions['types'] ?? []);
    $categories = collect($filterOptions['categories'] ?? []);
    $classifications = collect($filterOptions['classifications'] ?? []);
    $years = collect($filterOptions['years'] ?? []);
    $subClassifications = collect($filterOptions['subClassifications'] ?? []);
    $deliveryMethods = collect($filterOptions['deliveryMethods'] ?? []);
    $providers = collect($filterOptions['providers'] ?? []);

    $filterSource = request('record_filter_source', request('source', 'all'));
    $filterType = request('record_filter_type', request('type', 'all'));
    $filterClassification = request('record_filter_classification', request('classification', 'all'));
    $filterYear = request('record_filter_year', request('year', 'all'));
    $search = request('record_search', request('search', request('q', '')));

    // More Filters - General
    $filterCategory = request('record_filter_category', request('category', 'all'));
    $filterStatus = request('record_filter_status', request('status', request('progress_status', 'all')));
    $filterDurationMin = request('record_filter_duration_min', request('duration_min', ''));
    $filterDurationMax = request('record_filter_duration_max', request('duration_max', ''));
    $filterLhMin = request('record_filter_lh_min', request('lh_min', ''));
    $filterLhMax = request('record_filter_lh_max', request('lh_max', ''));
    $filterCertificate = request('record_filter_certificate', request('certificate', 'all'));

    // More Filters - Offline Specific
    $filterAttendance = request('record_filter_attendance', request('attendance', 'all'));
    $filterSubClassification = request('record_filter_sub_classification', request('sub_classification', 'all'));
    $filterDeliveryMethod = request('record_filter_delivery_method', request('delivery_method', 'all'));
    $filterProvider = request('record_filter_provider', request('provider', 'all'));

    $activeMoreFilterCount = 0;
    if ($filterCategory !== 'all' && $filterCategory !== '') $activeMoreFilterCount++;
    if ($filterStatus !== 'all' && $filterStatus !== '') $activeMoreFilterCount++;
    if ($filterDurationMin !== '' && $filterDurationMin !== null) $activeMoreFilterCount++;
    if ($filterDurationMax !== '' && $filterDurationMax !== null) $activeMoreFilterCount++;
    if ($filterLhMin !== '' && $filterLhMin !== null) $activeMoreFilterCount++;
    if ($filterLhMax !== '' && $filterLhMax !== null) $activeMoreFilterCount++;
    if ($filterCertificate !== 'all' && $filterCertificate !== '') $activeMoreFilterCount++;
    if ($filterAttendance !== 'all' && $filterAttendance !== '') $activeMoreFilterCount++;
    if ($filterSubClassification !== 'all' && $filterSubClassification !== '') $activeMoreFilterCount++;
    if ($filterDeliveryMethod !== 'all' && $filterDeliveryMethod !== '') $activeMoreFilterCount++;
    if ($filterProvider !== 'all' && $filterProvider !== '') $activeMoreFilterCount++;

    $isFiltered = ($search !== '' || $filterSource !== 'all' || $filterType !== 'all' || $filterClassification !== 'all' || $filterYear !== 'all' || $activeMoreFilterCount > 0);
    $hasAnyRecords = (bool) ($hasAnyRecords ?? ($rows->isNotEmpty() || $isFiltered));
@endphp

<template data-learning-record-hero>
    <x-shared.hero-learning-record
        recordMode="all"
        :stats="$recordStats ?? [
            ['label' => 'Course Completed', 'value' => number_format($recordStats['courseCompleted'] ?? 0)],
            ['label' => 'Playlist Completed', 'value' => number_format($recordStats['playlistCompleted'] ?? 0)],
            ['label' => 'Learning Hours', 'value' => number_format($recordStats['totalLearningHours'] ?? 0, 2)],
        ]"
    />
</template>

<style>
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

    /* Score Badges */
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

    /* Table Horizontal Scroll */
    .learning-record-scroll {
        overflow-x: auto !important;
        overflow-y: visible !important;
        max-height: none !important;
        -webkit-overflow-scrolling: touch;
        width: 100% !important;
    }
    .learning-record-table {
        min-width: 1280px !important;
        width: 100% !important;
    }
    /* Training Title Column (Standardized 4-tier Layout for Online & Offline) */
    .learning-record-course-cell {
        min-width: 280px !important;
        max-width: 360px !important;
        width: 24% !important;
        white-space: normal !important;
    }
    .record-title-stack {
        width: 100%;
        min-width: 0;
    }
    .record-title-line {
        width: 100%;
        min-width: 0;
        line-height: 1.4;
    }
    .learning-record-title,
    .record-offline-title-link {
        font-family: inherit;
        font-size: 0.875rem;
        font-weight: 600;
        line-height: 1.4;
        color: inherit;
        text-align: left;
        white-space: normal !important;
        word-break: normal !important;
        overflow-wrap: break-word !important;
    }
    .record-offline-title-link {
        display: inline-block;
        background: transparent;
        border: 0;
        padding: 0;
        margin: 0;
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
    .learning-record-link {
        font-size: 0.875rem;
        font-weight: 600;
        line-height: 1.4;
        white-space: normal !important;
        word-break: normal !important;
        overflow-wrap: break-word !important;
        text-decoration: none;
        display: inline-block;
        transition: color 0.15s ease-in-out, text-decoration-color 0.15s ease-in-out;
    }
    .learning-record-link:hover {
        color: var(--tblr-primary, #206bc4) !important;
        text-decoration: underline !important;
    }
    .learning-record-title-text {
        font-size: 0.875rem;
        font-weight: 600;
        line-height: 1.4;
        white-space: normal !important;
        word-break: normal !important;
        overflow-wrap: break-word !important;
    }
    .record-subtitle-line {
        font-size: 0.785rem !important;
        line-height: 1.35;
        color: #64748b !important;
        word-break: normal;
        overflow-wrap: break-word;
    }
    .record-badge-line {
        display: block;
        line-height: 1;
    }
    .record-source-badge {
        font-size: 10.5px !important;
        font-weight: 600 !important;
        padding: 2.5px 7px !important;
        border-radius: 4px !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 3px !important;
        line-height: 1.2 !important;
        letter-spacing: 0.2px;
    }
    .record-source-badge i {
        font-size: 11px !important;
        line-height: 1 !important;
    }
    .record-venue-line {
        font-size: 0.75rem !important;
        line-height: 1.35;
        color: #64748b !important;
        word-break: normal;
        overflow-wrap: break-word;
        max-width: 100%;
    }
    .record-venue-line i {
        font-size: 0.825rem;
        color: #94a3b8;
    }
    .record-venue-text {
        color: #64748b;
        word-break: normal;
        overflow-wrap: break-word;
    }

    /* Dark Mode Theme Support for Course Cell */
    :is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode) .learning-record-course-cell .record-offline-title-link,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode) .learning-record-course-cell .learning-record-link,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode) .learning-record-course-cell .learning-record-title-text,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode) .learning-record-course-cell .learning-record-title {
        color: #f1f5f9 !important;
    }
    :is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode) .learning-record-course-cell .learning-record-link:hover,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode) .learning-record-course-cell .record-offline-title-link:hover {
        color: #60a5fa !important;
    }
    :is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode) .learning-record-course-cell .record-subtitle-line,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode) .learning-record-course-cell .record-venue-line,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode) .learning-record-course-cell .record-venue-text {
        color: #94a3b8 !important;
    }

    /* Action Toolbar buttons */
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

    /* More Filter Modal */
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
    .record-filter-modal .modal-body {
        max-height: 72vh;
        overflow-y: auto;
        padding: 1.5rem 2rem !important;
        background-color: #ffffff !important;
    }
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
    .record-filter-input {
        height: 42px !important;
        border-radius: 8px !important;
        font-size: 0.875rem !important;
        color: #334155 !important;
        border: 1px solid #d1d5db !important;
        padding: 0.5rem 0.85rem !important;
    }
    .record-filter-input:focus {
        border-color: #206bc4 !important;
        box-shadow: 0 0 0 3px rgba(32, 107, 196, 0.15) !important;
    }
    .input-group .record-filter-input:first-child {
        border-top-right-radius: 0 !important;
        border-bottom-right-radius: 0 !important;
    }
    .input-group .record-filter-input:last-child {
        border-top-left-radius: 0 !important;
        border-bottom-left-radius: 0 !important;
    }
    :is([data-bs-theme="dark"], [data-theme="dark"], body.dark-mode) .record-filter-input {
        background-color: #192d45 !important;
        border-color: rgba(255, 255, 255, 0.12) !important;
        color: #f1f5f9 !important;
    }
    .record-filter-modal-footer {
        background-color: #ffffff !important;
        border-top: 1px solid #f0f2f5 !important;
        padding: 1rem 2rem !important;
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
    }

    /* Compact Dynamic Quick Filter & Toolbar Alignment for All Record */
    #recordAllFilterBar .fast-filter-toolbar {
        display: flex !important;
        align-items: center !important;
        flex-wrap: nowrap !important;
        gap: 8px !important;
        width: 100% !important;
    }
    #recordAllFilterBar .learning-quick-dropdowns {
        display: flex !important;
        align-items: center !important;
        flex-wrap: nowrap !important;
        flex: 0 1 auto !important;
        gap: 6px !important;
        width: auto !important;
    }
    #recordAllFilterBar .learning-quick-dropdowns .dropdown {
        flex: 0 0 auto !important;
    }
    #recordAllFilterBar .learning-quick-dropdowns .dropdown-toggle {
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
    #recordAllFilterBar .learning-quick-dropdowns .dropdown-toggle .fw-semibold {
        display: inline-block !important;
        max-width: 125px !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
        vertical-align: middle !important;
    }
    #recordAllFilterBar .learning-quick-dropdowns .dropdown-toggle::after {
        margin-left: 4px !important;
        flex-shrink: 0 !important;
    }
    #recordAllFilterBar .learning-quick-dropdowns .dropdown-menu {
        min-width: 100% !important;
        width: max-content !important;
        max-width: 320px !important;
    }
    #recordAllFilterBar .record-toolbar-search {
        flex: 1 1 auto !important;
        min-width: 140px !important;
        width: auto !important;
    }
    #recordAllFilterBar .record-toolbar-search .form-control {
        width: 100% !important;
        min-height: 38px !important;
        height: 38px !important;
    }
    #recordAllFilterBar .record-filter-actions {
        flex: 0 0 auto !important;
        display: flex !important;
        align-items: center !important;
        gap: 6px !important;
    }

    @media (max-width: 767.98px) {
        #recordAllFilterBar .fast-filter-toolbar {
            flex-wrap: wrap !important;
            gap: 8px !important;
        }
        #recordAllFilterBar .learning-quick-dropdowns {
            width: 100% !important;
            flex: 1 1 100% !important;
            flex-wrap: wrap !important;
        }
        #recordAllFilterBar .record-toolbar-search {
            width: 100% !important;
            min-width: 100% !important;
            flex: 1 1 100% !important;
        }
        #recordAllFilterBar .record-filter-actions {
            width: 100% !important;
            justify-content: flex-end !important;
        }
    }
</style>

<div class="learning-record-shell">
    {{-- Filter Card All --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 learning-record-card">
        <div class="card-body p-4 border-bottom">
            <div class="record-filter-shell" id="recordAllFilterBar">
                <div class="learning-filter-heading fast-filter-header">
                    <div class="fast-filter-header-copy">
                        <h3 class="card-title fast-filter-title">Quick Filter</h3>
                    </div>
                    <x-learning.record-mode-switch mode="all" />
                </div>

                <div class="record-fast-filter-section">
                    <div class="fast-filter-toolbar">
                        <div class="learning-quick-dropdowns learning-fast-filter">
                            {{-- Dropdown Source (Online | Offline) --}}
                            <div class="dropdown">
                                <button type="button"
                                        class="btn btn-filter-chip learning-fast-filter-control dropdown-toggle {{ $filterSource !== 'all' ? 'active' : '' }}"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                    Source: <span class="fw-semibold">{{ ucfirst($filterSource) }}</span>
                                </button>
                                <div class="dropdown-menu">
                                    @foreach ($sources as $sKey => $sLabel)
                                        <a href="#"
                                           class="dropdown-item record-all-filter-item {{ (string) $filterSource === (string) $sKey ? 'active' : '' }}"
                                           data-param="record_filter_source"
                                           data-value="{{ $sKey }}">
                                            {{ $sLabel }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>

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
                                       class="dropdown-item record-all-filter-item {{ $filterType === 'all' ? 'active' : '' }}"
                                       data-param="record_filter_type"
                                       data-value="all">
                                        All Types
                                    </a>
                                    @foreach ($types as $typeOption)
                                        <a href="#"
                                           class="dropdown-item record-all-filter-item {{ (string) $filterType === (string) $typeOption ? 'active' : '' }}"
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
                                       class="dropdown-item record-all-filter-item {{ $filterClassification === 'all' ? 'active' : '' }}"
                                       data-param="record_filter_classification"
                                       data-value="all">
                                        All Classification
                                    </a>
                                    @foreach ($classifications as $classificationOption)
                                        <a href="#"
                                           class="dropdown-item record-all-filter-item {{ (string) $filterClassification === (string) $classificationOption ? 'active' : '' }}"
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
                                       class="dropdown-item record-all-filter-item {{ $filterYear === 'all' ? 'active' : '' }}"
                                       data-param="record_filter_year"
                                       data-value="all">
                                        All Years
                                    </a>
                                    @foreach ($years as $yearOption)
                                        <a href="#"
                                           class="dropdown-item record-all-filter-item {{ (string) $filterYear === (string) $yearOption ? 'active' : '' }}"
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
                            <label class="visually-hidden" for="recordAllSearch">Search all learning records</label>
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                                <input type="text" class="form-control" id="recordAllSearch"
                                       value="{{ $search }}"
                                       placeholder="Search title, classification, provider, or source..." autocomplete="off">
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="record-filter-actions d-flex align-items-center gap-2">
                            {{-- 1. Search Button --}}
                            <button type="button" class="btn btn-primary btn-learning-primary record-all-apply-btn record-filter-action-btn"
                                    id="recordAllApplyBtn"
                                    title="Search & Apply Filter"
                                    aria-label="Search & Apply Filter">
                                <i class="ti ti-search" aria-hidden="true"></i>
                            </button>

                            {{-- 2. More Filter Button --}}
                            <button type="button"
                                    class="btn btn-outline-primary btn-learning-secondary record-filter-action-btn position-relative {{ $activeMoreFilterCount > 0 ? 'active' : '' }}"
                                    id="recordAllMoreFilterBtn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#recordAllMoreFilterModal"
                                    title="More Filters"
                                    aria-label="More Filters">
                                <i class="ti ti-adjustments-horizontal" aria-hidden="true"></i>
                                @if ($activeMoreFilterCount > 0)
                                    <span class="badge bg-primary text-white position-absolute top-0 start-100 translate-middle rounded-pill" style="font-size: 10px; min-width: 18px; height: 18px; padding: 0 4px; display: inline-flex; align-items: center; justify-content: center;">{{ $activeMoreFilterCount }}</span>
                                @endif
                            </button>

                            {{-- 3. Reset Button --}}
                            <button type="button" class="btn btn-outline-secondary btn-learning-outline record-all-reset-btn record-filter-action-btn"
                                    id="recordAllResetBtn"
                                    title="Reset Filter"
                                    aria-label="Reset Filter">
                                <i class="ti ti-refresh" aria-hidden="true"></i>
                            </button>

                            {{-- Hidden export anchor for test / script compatibility --}}
                            <a href="{{ route('user.learning.record.export', array_merge(request()->all(), ['source' => 'all'])) }}"
                               id="recordAllExportBtn"
                               class="d-none"></a>
                        </div>
                    </div>

                    {{-- Active More Filter Badges --}}
                    @if ($activeMoreFilterCount > 0)
                        <div class="record-all-active-filters d-flex align-items-center flex-wrap gap-1 mt-2 pt-2 border-top">
                            <span class="text-muted small me-1">Active filters:</span>
                            @if ($filterCategory !== 'all' && $filterCategory !== '')
                                <span class="badge bg-blue-lt d-inline-flex align-items-center gap-1">
                                    Category: {{ $filterCategory }}
                                    <a href="#" class="text-reset text-decoration-none record-all-clear-single-filter" data-param="record_filter_category">&times;</a>
                                </span>
                            @endif
                            @if ($filterStatus !== 'all' && $filterStatus !== '')
                                <span class="badge bg-blue-lt d-inline-flex align-items-center gap-1">
                                    Status: {{ match($filterStatus) { 'completed' => 'Completed', 'in_progress' => 'In Progress', 'not_started' => 'Not Started', default => ucfirst($filterStatus) } }}
                                    <a href="#" class="text-reset text-decoration-none record-all-clear-single-filter" data-param="record_filter_status">&times;</a>
                                </span>
                            @endif
                            @if ($filterDurationMin !== '' && $filterDurationMin !== null)
                                <span class="badge bg-blue-lt d-inline-flex align-items-center gap-1">
                                    Min Dur: {{ $filterDurationMin }}m
                                    <a href="#" class="text-reset text-decoration-none record-all-clear-single-filter" data-param="record_filter_duration_min">&times;</a>
                                </span>
                            @endif
                            @if ($filterDurationMax !== '' && $filterDurationMax !== null)
                                <span class="badge bg-blue-lt d-inline-flex align-items-center gap-1">
                                    Max Dur: {{ $filterDurationMax }}m
                                    <a href="#" class="text-reset text-decoration-none record-all-clear-single-filter" data-param="record_filter_duration_max">&times;</a>
                                </span>
                            @endif
                            @if ($filterLhMin !== '' && $filterLhMin !== null)
                                <span class="badge bg-blue-lt d-inline-flex align-items-center gap-1">
                                    Min LH: {{ $filterLhMin }}h
                                    <a href="#" class="text-reset text-decoration-none record-all-clear-single-filter" data-param="record_filter_lh_min">&times;</a>
                                </span>
                            @endif
                            @if ($filterLhMax !== '' && $filterLhMax !== null)
                                <span class="badge bg-blue-lt d-inline-flex align-items-center gap-1">
                                    Max LH: {{ $filterLhMax }}h
                                    <a href="#" class="text-reset text-decoration-none record-all-clear-single-filter" data-param="record_filter_lh_max">&times;</a>
                                </span>
                            @endif
                            @if ($filterCertificate !== 'all' && $filterCertificate !== '')
                                <span class="badge bg-blue-lt d-inline-flex align-items-center gap-1">
                                    Certificate: {{ in_array(strtolower($filterCertificate), ['certified', 'yes', '1', 'ready'], true) ? 'Available' : 'None' }}
                                    <a href="#" class="text-reset text-decoration-none record-all-clear-single-filter" data-param="record_filter_certificate">&times;</a>
                                </span>
                            @endif
                            @if ($filterAttendance !== 'all' && $filterAttendance !== '')
                                <span class="badge bg-amber-lt d-inline-flex align-items-center gap-1">
                                    Attendance: {{ ucfirst($filterAttendance) }}
                                    <a href="#" class="text-reset text-decoration-none record-all-clear-single-filter" data-param="record_filter_attendance">&times;</a>
                                </span>
                            @endif
                            @if ($filterSubClassification !== 'all' && $filterSubClassification !== '')
                                <span class="badge bg-amber-lt d-inline-flex align-items-center gap-1">
                                    Sub Class: {{ $filterSubClassification }}
                                    <a href="#" class="text-reset text-decoration-none record-all-clear-single-filter" data-param="record_filter_sub_classification">&times;</a>
                                </span>
                            @endif
                            @if ($filterDeliveryMethod !== 'all' && $filterDeliveryMethod !== '')
                                <span class="badge bg-amber-lt d-inline-flex align-items-center gap-1">
                                    Method: {{ $filterDeliveryMethod }}
                                    <a href="#" class="text-reset text-decoration-none record-all-clear-single-filter" data-param="record_filter_delivery_method">&times;</a>
                                </span>
                            @endif
                            @if ($filterProvider !== 'all' && $filterProvider !== '')
                                <span class="badge bg-amber-lt d-inline-flex align-items-center gap-1">
                                    Provider: {{ $filterProvider }}
                                    <a href="#" class="text-reset text-decoration-none record-all-clear-single-filter" data-param="record_filter_provider">&times;</a>
                                </span>
                            @endif
                            <a href="#" class="small text-danger ms-1 text-decoration-none" id="recordAllClearAllMoreFilters">Clear all</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Table or Empty State All --}}
        @if ($rows->isEmpty())
            <div class="card-body text-center py-5">
                <div class="py-4">
                    <div class="text-muted mb-3">
                        @if ($hasAnyRecords && $isFiltered)
                            <i class="ti ti-search-off text-muted" style="font-size: 3rem; opacity: 0.5;"></i>
                        @else
                            <i class="ti ti-folder-off text-muted" style="font-size: 3rem; opacity: 0.5;"></i>
                        @endif
                    </div>
                    @if ($hasAnyRecords && $isFiltered)
                        <h4 class="fw-bold text-dark mb-1">No records match your current filters.</h4>
                        <p class="text-muted mb-3">Try adjusting your filters or search criteria.</p>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="recordAllEmptyResetBtn">
                            <i class="ti ti-refresh me-1"></i> Reset Filters
                        </button>
                    @else
                        <h4 class="fw-bold text-dark mb-1">No learning records available.</h4>
                        <p class="text-muted mb-0">Your online and offline learning records will appear here once available.</p>
                    @endif
                </div>
            </div>
        @else
            <div class="card-body p-0">
                <div class="table-responsive learning-record-scroll">
                    <table class="table table-vcenter table-hover card-table mb-0 learning-record-table"
                           id="learningRecordAllTable" style="width:100%">
                        <thead>
                            <tr>
                                <th class="text-muted text-center record-sortable" style="width: 3%" data-sort-key="no" data-sort-type="number">
                                    <span class="record-sort-header">
                                        <span class="record-sort-label">No</span>
                                        <span class="record-sort-icons" aria-hidden="true"><span class="record-sort-asc">&#9650;</span><span class="record-sort-desc">&#9660;</span></span>
                                    </span>
                                </th>
                                <th class="text-muted record-sortable" style="width: 24%; min-width: 260px;" data-sort-key="title" data-sort-type="text">
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
                                        <span class="record-sort-label">Duration</span>
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
                                $allSortAttRaw = $row['attendance_status'] ?? $row['attendance'] ?? 'No';
                                $allSortAtt = ($allSortAttRaw === true || $allSortAttRaw === 1 || in_array(strtolower(trim((string) $allSortAttRaw)), ['1', 'true', 'present', 'yes'], true)) ? 'yes' : 'no';
                                $allSortScore = $row['score'] ?? $row['assessment_score'] ?? '';
                                $allSortCert = (($row['has_certificate'] ?? false) || ($row['cert_state'] ?? '') === 'ready') ? 'ready' : 'none';
                                $allRowType = ($row['source'] ?? '') === 'offline' ? '-' : (string) ($row['type'] ?? '-');
                            @endphp
                            <tr class="record-row record-row-{{ $row['source'] }}"
                                data-sort-no="{{ $loop->iteration }}"
                                data-sort-title="{{ mb_strtolower((string) ($row['title'] ?? '-')) }}"
                                data-sort-type="{{ mb_strtolower($allRowType) }}"
                                data-sort-category="{{ mb_strtolower((string) ($row['category'] ?? '-')) }}"
                                data-sort-classification="{{ mb_strtolower((string) ($row['classification'] ?? '-')) }}"
                                data-sort-duration="{{ $row['duration_sort'] ?? ($row['duration_seconds'] ?? -1) }}"
                                data-sort-learning-hour="{{ $row['learning_hour_value'] ?? 0 }}"
                                data-sort-progress="{{ (int) ($row['progress'] ?? 0) }}"
                                data-sort-finish="{{ $row['end_datetime'] ?? ($row['finish_date'] ?? '') }}"
                                data-sort-attendance="{{ $allSortAtt }}"
                                data-sort-score="{{ $allSortScore }}"
                                data-sort-certificate="{{ $allSortCert }}">
                                <td class="text-center text-muted record-row-index">{{ $loop->iteration }}</td>
                                <td class="learning-record-course-cell">
                                    @php
                                        $isAllOnline = ($row['source'] ?? '') === 'online';
                                        $allSourceLabel = $isAllOnline ? 'KP OWL' : 'Non-KP OWL';
                                        $allSourceBadgeClass = $isAllOnline ? 'bg-primary-subtle text-primary' : 'bg-warning-subtle text-warning';
                                        $allRawReq = strtolower(trim((string) ($row['requirement'] ?? $row['subtitle'] ?? 'non-mandatory')));
                                        $isAllMand = !str_contains($allRawReq, 'non-mandatory') && !str_contains($allRawReq, 'non mandatory') && !str_contains($allRawReq, 'optional') && str_contains($allRawReq, 'mandatory');
                                        $allReqLabel = $isAllMand ? 'Mandatory' : 'Non-Mandatory';
                                    @endphp
                                    <div class="fw-semibold text-dark">
                                        @if ($row['source'] === 'offline')
                                            <button type="button"
                                                    class="record-offline-title-link btn-record-offline-detail text-start text-dark fw-semibold p-0 border-0 bg-transparent"
                                                    data-record-id="{{ $row['id'] }}"
                                                    title="Click to view details">
                                                {{ $row['title'] }}
                                            </button>
                                            <script type="application/json" id="record-offline-data-{{ $row['id'] }}">@json($row)</script>
                                        @else
                                            @if (($row['can_open'] ?? true))
                                                @if (!empty($row['slug']))
                                                    <a href="{{ route('content.user.view', $row['slug']) }}" class="learning-record-link fw-semibold text-dark text-decoration-none">
                                                        {{ $row['title'] }}
                                                    </a>
                                                @elseif (!empty($row['playlist_url']))
                                                    <a href="{{ $row['playlist_url'] }}" class="learning-record-link fw-semibold text-dark text-decoration-none">
                                                        {{ $row['title'] }}
                                                    </a>
                                                @else
                                                    <span>{{ $row['title'] }}</span>
                                                @endif
                                            @else
                                                <a href="javascript:void(0);"
                                                   class="learning-record-link learning-record-blocked-trigger fw-semibold text-muted text-decoration-none"
                                                   data-status="{{ $row['access_status'] ?? 'INACTIVE' }}"
                                                   data-message="{{ $row['modal_message'] ?? 'This content is currently inactive.' }}"
                                                   data-title="{{ $row['title'] }}"
                                                   role="button">
                                                    {{ $row['title'] }}
                                                </a>
                                            @endif
                                            <script type="application/json" id="record-online-data-{{ $row['id'] }}">@json($row)</script>
                                        @endif
                                    </div>
                                    <div class="d-flex align-items-center gap-2 text-xs text-muted mt-1 flex-wrap">
                                        <span>Requirement: <span class="badge bg-light text-dark">{{ $allReqLabel }}</span></span>
                                        <span class="badge {{ $allSourceBadgeClass }} record-source-badge">
                                            {{ $allSourceLabel }}
                                        </span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <x-learning.record-type-badge :type="$row['type']" :url="(($row['can_open'] ?? true) ? ($row['playlist_url'] ?? null) : null)" />
                                </td>
                                <td class="text-center">
                                    @if (($row['category'] ?? '') === 'Playlist')
                                        <span class="badge bg-green-lt text-green learning-record-badge">Playlist</span>
                                    @elseif (($row['category'] ?? '') === 'Content')
                                        <span class="badge bg-blue-lt text-blue learning-record-badge">Content</span>
                                    @else
                                        <span class="badge bg-secondary-lt text-secondary learning-record-badge">
                                            {{ $row['category'] ?: '—' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <x-learning.record-classification-badge :row="$row" />
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
                                    $allStartDt = $row['start_datetime'] ?? ($row['start_date'] ?? '');
                                    $allEndDt = $row['end_datetime'] ?? ($row['finish_date'] ?? '');
                                @endphp
                                <td class="text-muted small learning-record-datetime">
                                    <div class="text-xs text-muted">
                                        <div class="learning-record-date-row text-nowrap">Start : {{ $allStartDt !== '' ? \Carbon\Carbon::parse($allStartDt)->format('d/m/y H:i:s') : '-' }}</div>
                                        <div class="learning-record-date-row text-nowrap">Finish : {{ $allEndDt !== '' ? \Carbon\Carbon::parse($allEndDt)->format('d/m/y H:i:s') : '-' }}</div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if ($row['source'] === 'offline')
                                        @php
                                            $rawAtt = $row['attendance_status'] ?? $row['attendance'] ?? 'No';
                                            $statusKey = strtolower(trim((string) $rawAtt));
                                            $isAttYes = ($rawAtt === true || $rawAtt === 1 || in_array($statusKey, ['1', 'true', 'present', 'yes'], true));
                                            $attStatus = $isAttYes ? 'Yes' : 'No';
                                            $badgeClass = $isAttYes ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger';
                                            $rawReason = $row['raw_attendance_reason'] ?? (($row['attendance_reason'] ?? '-') !== '-' ? $row['attendance_reason'] : '');
                                            $displayReason = $row['attendance_reason_display'] ?? '';
                                        @endphp
                                        <button type="button"
                                                class="badge {{ $badgeClass }} fw-bold px-2 py-1 attendance-detail-trigger text-decoration-none"
                                                data-record-id="{{ $row['id'] }}"
                                                data-training-title="{{ $row['title'] }}"
                                                data-attendance="{{ $attStatus }}"
                                                data-attendance-reason="{{ $rawReason }}"
                                                data-attendance-reason-display="{{ $displayReason }}"
                                                data-training-date="{{ $row['date_display'] ?: '-' }}"
                                                data-venue="{{ $row['venue'] ?: '-' }}"
                                                title="Click to view attendance detail"
                                                aria-label="View attendance detail for {{ $row['title'] }}">
                                            {{ $attStatus }}
                                        </button>
                                    @else
                                        <x-learning.record-attendance-cell :row="$row" />
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex flex-column align-items-center justify-content-center text-center">
                                        @php
                                            $allScoreVal = $row['score'] ?? $row['assessment_score'] ?? null;
                                        @endphp
                                        @if ($allScoreVal !== null)
                                            @php
                                                $allMinScore = (float) ($row['min_score'] ?? 70);
                                                $allPassed = (float) $allScoreVal >= $allMinScore;
                                            @endphp
                                            @if ($allPassed)
                                                <span class="badge rounded-pill learning-record-badge learning-score-pill" style="background-color: #dcfce7; color: #15803d; font-weight: 600;">Passed</span>
                                                <div class="small fw-semibold mt-1" style="color: #15803d;">Score : {{ $allScoreVal ?? '-' }}</div>
                                            @else
                                                <span class="badge bg-red-lt text-red learning-record-badge learning-score-pill">FAILED</span>
                                                <div class="small text-danger mt-1">Score : {{ $allScoreVal ?? '-' }}</div>
                                                <div class="small text-danger">Passing Grade : {{ (int) $allMinScore }}</div>
                                            @endif
                                        @else
                                            <span class="learning-score-empty-pill">—</span>
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
                                                   data-record-source="{{ $row['source'] ?? 'offline' }}">
                                                    <i class="ti ti-info-circle"></i> More Information
                                                </a>
                                            </li>
                                            <li>
                                                @if ((($row['has_certificate'] ?? false) || ($row['cert_state'] ?? '') === 'ready') && !empty($row['cert_action_url']))
                                                    @if (($row['source'] ?? '') === 'online' && ($row['cert_is_form'] ?? false))
                                                        <form method="POST" action="{{ $row['cert_action_url'] }}" class="m-0 p-0">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item d-flex align-items-center gap-2 text-success border-0 bg-transparent w-100">
                                                                <i class="ti ti-certificate"></i> Generate Certificate
                                                            </button>
                                                        </form>
                                                    @else
                                                        <a class="dropdown-item d-flex align-items-center gap-2 text-success" href="{{ $row['cert_action_url'] }}" target="_blank">
                                                            <i class="ti ti-certificate"></i> Generate Certificate
                                                        </a>
                                                    @endif
                                                @else
                                                    <a class="dropdown-item d-flex align-items-center gap-2 disabled text-muted" href="#" onclick="return false;" style="cursor: not-allowed;">
                                                        <i class="ti ti-certificate"></i> Generate Certificate
                                                    </a>
                                                @endif
                                            </li>
                                        </ul>
                                    </div>
                                    @if (($row['source'] ?? '') === 'online')
                                        <script type="application/json" id="record-online-data-{{ $row['id'] }}">@json($row)</script>
                                    @endif
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
                        <select class="form-select form-select-sm w-auto" id="recordAllPageLength">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                        <span class="text-muted small" id="recordAllTableInfo"></span>
                    </div>

                    <ul class="pagination pagination-sm mb-0" id="recordAllPagination"></ul>
                </div>
            @endif
        @endif
    </div>
</div>

{{-- ========================================================================= --}}
{{-- MODAL: MORE FILTER ALL RECORD                                             --}}
{{-- ========================================================================= --}}
<div class="modal fade" id="recordAllMoreFilterModal" tabindex="-1" role="dialog" aria-labelledby="recordAllMoreFilterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered learning-filter-modal" role="document">
        <div class="modal-content rounded-4 border-0 shadow record-filter-modal">
            <div class="modal-header border-bottom py-3 px-4">
                <h3 class="modal-title fw-semibold d-flex align-items-center gap-2 mb-0" id="recordAllMoreFilterModalLabel" style="font-size: 1.15rem;">
                    <i class="ti ti-adjustments-horizontal text-primary" style="font-size: 1.35rem;"></i>
                    <span>More Filters — All Record</span>
                </h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <form id="recordAllMoreFilterForm" onsubmit="event.preventDefault(); $('#btnApplyAllMoreFilterModal').trigger('click');">
                    {{-- ── SECTION 1: GENERAL FILTERS (ONLINE & OFFLINE) ── --}}
                    <div class="mb-4">
                        <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                            <i class="ti ti-layers-intersect text-primary fs-3"></i>
                            <h6 class="text-uppercase fw-bold text-dark mb-0" style="font-size: 0.78rem; letter-spacing: 0.6px;">
                                General Filters <span class="text-muted fw-normal text-none font-sans" style="font-size: 0.75rem; text-transform: none;">(Online &amp; Offline)</span>
                            </h6>
                        </div>

                        <div class="row g-3">
                            {{-- 1. Program Category --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold text-dark small mb-1.5" for="recordAllModalCategory">Program Category</label>
                                <select class="form-select record-filter-select rounded-3" name="record_filter_category" id="recordAllModalCategory">
                                    <option value="all" {{ $filterCategory === 'all' ? 'selected' : '' }}>All Categories</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat }}" {{ (string) $filterCategory === (string) $cat ? 'selected' : '' }}>
                                            {{ $cat }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- 2. Learning Status / Progress --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold text-dark small mb-1.5" for="recordAllModalStatus">Learning Status</label>
                                <select class="form-select record-filter-select rounded-3" name="record_filter_status" id="recordAllModalStatus">
                                    <option value="all" {{ $filterStatus === 'all' ? 'selected' : '' }}>All Status</option>
                                    <option value="completed" {{ $filterStatus === 'completed' ? 'selected' : '' }}>Completed (100%)</option>
                                    <option value="in_progress" {{ $filterStatus === 'in_progress' ? 'selected' : '' }}>In Progress (1% – 99%)</option>
                                    <option value="not_started" {{ $filterStatus === 'not_started' ? 'selected' : '' }}>Not Started (0%)</option>
                                </select>
                            </div>

                            {{-- 3. Duration Range (Minutes) --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold text-dark small mb-1.5">Duration Range (Minutes)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control record-filter-input" name="record_filter_duration_min" id="recordAllModalDurMin"
                                           value="{{ $filterDurationMin }}" placeholder="Min (mins)" min="0" step="1">
                                    <span class="input-group-text bg-light text-muted px-2 py-0" style="font-size: 0.8rem;">to</span>
                                    <input type="number" class="form-control record-filter-input" name="record_filter_duration_max" id="recordAllModalDurMax"
                                           value="{{ $filterDurationMax }}" placeholder="Max (mins)" min="0" step="1">
                                </div>
                            </div>

                            {{-- 4. Learning Hours Range --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold text-dark small mb-1.5">Learning Hours Range</label>
                                <div class="input-group">
                                    <input type="number" class="form-control record-filter-input" name="record_filter_lh_min" id="recordAllModalLhMin"
                                           value="{{ $filterLhMin }}" placeholder="Min Hours" min="0" step="0.1">
                                    <span class="input-group-text bg-light text-muted px-2 py-0" style="font-size: 0.8rem;">to</span>
                                    <input type="number" class="form-control record-filter-input" name="record_filter_lh_max" id="recordAllModalLhMax"
                                           value="{{ $filterLhMax }}" placeholder="Max Hours" min="0" step="0.1">
                                </div>
                            </div>

                            {{-- 5. Certificate Status --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold text-dark small mb-1.5" for="recordAllModalCertificate">Certificate Status</label>
                                <select class="form-select record-filter-select rounded-3" name="record_filter_certificate" id="recordAllModalCertificate">
                                    <option value="all" {{ $filterCertificate === 'all' ? 'selected' : '' }}>All Certificate Status</option>
                                    <option value="certified" {{ in_array(strtolower($filterCertificate), ['certified', 'yes', '1', 'ready'], true) ? 'selected' : '' }}>Certificate Available / Issued</option>
                                    <option value="uncertified" {{ in_array(strtolower($filterCertificate), ['uncertified', 'no', '0', 'none'], true) ? 'selected' : '' }}>No Certificate</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- ── SECTION 2: OFFLINE-SPECIFIC FILTERS ── --}}
                    <div class="mb-2">
                        <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                            <i class="ti ti-certificate text-amber fs-3"></i>
                            <h6 class="text-uppercase fw-bold text-dark mb-0" style="font-size: 0.78rem; letter-spacing: 0.6px;">
                                Offline Filters <span class="text-muted fw-normal text-none font-sans" style="font-size: 0.75rem; text-transform: none;">(Applies to Offline records)</span>
                            </h6>
                            <span class="badge bg-amber-lt ms-auto" style="font-size: 10px;">Offline Only</span>
                        </div>

                        <div class="row g-3">
                            {{-- 6. Attendance Status --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold text-dark small mb-1.5" for="recordAllModalAttendance">Attendance Status</label>
                                <select class="form-select record-filter-select rounded-3" name="record_filter_attendance" id="recordAllModalAttendance">
                                    <option value="all" {{ $filterAttendance === 'all' ? 'selected' : '' }}>All Attendance</option>
                                    <option value="present" {{ strtolower($filterAttendance) === 'present' ? 'selected' : '' }}>Present / Attended</option>
                                    <option value="absent" {{ strtolower($filterAttendance) === 'absent' ? 'selected' : '' }}>Absent</option>
                                </select>
                            </div>

                            {{-- 7. Sub Classification --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold text-dark small mb-1.5" for="recordAllModalSubClassification">Sub Classification</label>
                                <select class="form-select record-filter-select rounded-3" name="record_filter_sub_classification" id="recordAllModalSubClassification">
                                    <option value="all" {{ $filterSubClassification === 'all' ? 'selected' : '' }}>All Sub Classifications</option>
                                    @foreach ($subClassifications as $subClass)
                                        <option value="{{ $subClass }}" {{ (string) $filterSubClassification === (string) $subClass ? 'selected' : '' }}>
                                            {{ $subClass }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- 8. Delivery Method --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold text-dark small mb-1.5" for="recordAllModalDeliveryMethod">Delivery Method</label>
                                <select class="form-select record-filter-select rounded-3" name="record_filter_delivery_method" id="recordAllModalDeliveryMethod">
                                    <option value="all" {{ $filterDeliveryMethod === 'all' ? 'selected' : '' }}>All Delivery Methods</option>
                                    @foreach ($deliveryMethods as $method)
                                        <option value="{{ $method }}" {{ (string) $filterDeliveryMethod === (string) $method ? 'selected' : '' }}>
                                            {{ $method }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- 9. Provider --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold text-dark small mb-1.5" for="recordAllModalProvider">Provider</label>
                                <select class="form-select record-filter-select rounded-3" name="record_filter_provider" id="recordAllModalProvider">
                                    <option value="all" {{ $filterProvider === 'all' ? 'selected' : '' }}>All Providers</option>
                                    @foreach ($providers as $prov)
                                        <option value="{{ $prov }}" {{ (string) $filterProvider === (string) $prov ? 'selected' : '' }}>
                                            {{ $prov }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer border-top py-3 px-4 d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-secondary px-3 rounded-3 d-inline-flex align-items-center gap-1" id="btnResetAllMoreFilterModal">
                    <i class="ti ti-refresh fs-3"></i> Reset Filters
                </button>
                <button type="button" class="btn btn-primary px-4 rounded-3 d-inline-flex align-items-center gap-1" id="btnApplyAllMoreFilterModal" style="background-color: #003b6d; border-color: #003b6d;">
                    <i class="ti ti-check fs-3"></i> Apply Filters
                </button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL: ATTENDANCE DETAIL --}}
<x-learning.record-attendance-modal />

{{-- MODAL: OFFLINE TRAINING DETAIL --}}
<x-learning.record-offline-detail-modal />

{{-- MODAL: BLOCKED / INACTIVE ONLINE CONTENT --}}
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

{{-- MODAL: FULL RECORD DETAIL (STANDARDIZED) --}}
<x-shared.record-detail-modal />
