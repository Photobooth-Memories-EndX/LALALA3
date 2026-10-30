@php
    $types = collect($filterOptions['types'] ?? []);
    $classifications = collect($filterOptions['classifications'] ?? []);
    $years = collect($filterOptions['years'] ?? []);

    $filterType = request('ongoing_filter_type', request('record_filter_type', request('type', 'all')));
    $filterClassification = request('ongoing_filter_classification', request('record_filter_classification', request('classification', 'all')));
    $filterYear = request('ongoing_filter_year', request('record_filter_year', request('year', 'all')));
    $search = request('ongoing_search', request('record_search', request('search', request('q', ''))));
@endphp

<template data-learning-ongoing-hero>
    <x-shared.hero-learning-ongoing
        mode="non-kp-owl"
        :count="count($ongoingNonKpOwl)"
    />
</template>

<style>
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
        min-width: 1100px !important;
        width: 100% !important;
    }
    .learning-record-table thead th,
    #learningOngoingNonKpOwlTable thead th {
        position: sticky !important;
        top: 0 !important;
        z-index: 10 !important;
        background-color: #f8fafc !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }
    [data-bs-theme="dark"] .learning-record-table thead th,
    [data-bs-theme="dark"] #learningOngoingNonKpOwlTable thead th,
    body.dark-mode .learning-record-table thead th,
    body.dark-mode #learningOngoingNonKpOwlTable thead th,
    body.theme-dark .learning-record-table thead th,
    body.theme-dark #learningOngoingNonKpOwlTable thead th {
        background-color: #1e293b !important;
        color: #94a3b8 !important;
    }
    .learning-record-course-cell {
        vertical-align: middle !important;
    }
    .learning-record-date-row {
        font-family: var(--tblr-font-monospace, monospace);
        font-size: 0.75rem;
        line-height: 1.4;
    }
    .bg-blue-50 {
        background-color: #eff6ff !important;
    }
    .text-blue-600 {
        color: #2563eb !important;
    }
    [data-bs-theme="dark"] .bg-blue-50,
    body.dark-mode .bg-blue-50,
    body.theme-dark .bg-blue-50 {
        background-color: rgba(37, 99, 235, 0.15) !important;
        color: #60a5fa !important;
    }

    /* Clean visible borders matching Content tab filter appearance */
    .ongoing-non-kp-owl-wrapper .border-slate-300 {
        border-color: #cbd5e1 !important;
    }
    .ongoing-non-kp-owl-wrapper .rounded-lg {
        border-radius: 0.5rem !important;
    }
    .ongoing-non-kp-owl-wrapper .learning-fast-filter .learning-fast-filter-control,
    .ongoing-non-kp-owl-wrapper .btn-filter-chip.border-slate-300 {
        border: 1px solid #cbd5e1 !important;
        border-color: #cbd5e1 !important;
        background-color: #ffffff !important;
        border-radius: 0.5rem !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
        color: #475569 !important;
        min-height: 40px !important;
        height: 40px !important;
        padding: 0 12px !important;
        font-size: 13px !important;
        font-weight: 500 !important;
    }
    .ongoing-non-kp-owl-wrapper .learning-fast-filter .learning-fast-filter-control:hover,
    .ongoing-non-kp-owl-wrapper .btn-filter-chip.border-slate-300:hover {
        border-color: #94a3b8 !important;
        background-color: #ffffff !important;
        color: #1e293b !important;
        box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.05) !important;
    }
    .ongoing-non-kp-owl-wrapper .learning-fast-filter .learning-fast-filter-control.active,
    .ongoing-non-kp-owl-wrapper .btn-filter-chip.border-slate-300.active {
        border-color: #2563eb !important;
        background-color: #eff6ff !important;
        color: #2563eb !important;
    }
    .ongoing-non-kp-owl-wrapper .learning-fast-filter .learning-fast-filter-control:focus,
    .ongoing-non-kp-owl-wrapper .learning-fast-filter .learning-fast-filter-control:focus-visible,
    .ongoing-non-kp-owl-wrapper .dropdown.show > .learning-fast-filter-control,
    .ongoing-non-kp-owl-wrapper .dropdown > .learning-fast-filter-control[aria-expanded="true"] {
        border-color: #2563eb !important;
        background-color: #ffffff !important;
        color: #2563eb !important;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15) !important;
        outline: 0;
    }
    .ongoing-non-kp-owl-wrapper #ongoingOfflineSearch.border-slate-300 {
        border: 1px solid #cbd5e1 !important;
        border-color: #cbd5e1 !important;
        background-color: #ffffff !important;
        border-radius: 0.5rem !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
        min-height: 40px !important;
        height: 40px !important;
        padding-left: 2.5rem !important;
        font-size: 13px !important;
    }
    .ongoing-non-kp-owl-wrapper #ongoingOfflineSearch:focus {
        border-color: #2563eb !important;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15) !important;
    }
    [data-bs-theme="dark"] .ongoing-non-kp-owl-wrapper .border-slate-300,
    [data-bs-theme="dark"] .ongoing-non-kp-owl-wrapper .learning-fast-filter .learning-fast-filter-control,
    [data-bs-theme="dark"] .ongoing-non-kp-owl-wrapper .btn-filter-chip.border-slate-300,
    [data-bs-theme="dark"] .ongoing-non-kp-owl-wrapper #ongoingOfflineSearch.border-slate-300,
    body.dark-mode .ongoing-non-kp-owl-wrapper .border-slate-300,
    body.dark-mode .ongoing-non-kp-owl-wrapper .learning-fast-filter .learning-fast-filter-control,
    body.dark-mode .ongoing-non-kp-owl-wrapper .btn-filter-chip.border-slate-300,
    body.dark-mode .ongoing-non-kp-owl-wrapper #ongoingOfflineSearch.border-slate-300,
    body.theme-dark .ongoing-non-kp-owl-wrapper .border-slate-300,
    body.theme-dark .ongoing-non-kp-owl-wrapper .learning-fast-filter .learning-fast-filter-control,
    body.theme-dark .ongoing-non-kp-owl-wrapper .btn-filter-chip.border-slate-300,
    body.theme-dark .ongoing-non-kp-owl-wrapper #ongoingOfflineSearch.border-slate-300 {
        border-color: #334155 !important;
        background-color: #1e293b !important;
        color: #f8fafc !important;
    }
</style>

<div class="learning-section-wrapper ongoing-non-kp-owl-wrapper">
    <div class="learning-section-padding py-4 px-2 px-md-3 learning-dashboard-container">
        {{-- Quick Filter Card --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4 learning-filter-card ongoing-filter-card">
            <div class="card-body p-4">
                <div class="fast-filter-header-copy mb-2">
                    <h3 class="card-title fast-filter-title mb-0">Quick Filter</h3>
                </div>
                <x-learning.ongoing-mode-switch mode="non-kp-owl" :show-download="true" />

                {{-- Filter Toolbar: Type, Classification, Year, Search Bar --}}
                <div class="record-fast-filter-section ongoing-fast-filter-section pt-2 border-top">
                    <div class="fast-filter-toolbar d-flex align-items-center flex-wrap gap-2">
                        <div class="learning-quick-dropdowns learning-fast-filter d-flex align-items-center flex-wrap gap-2">
                            {{-- Dropdown Type --}}
                            <div class="dropdown">
                                <button type="button"
                                        class="btn btn-filter-chip learning-fast-filter-control border border-slate-300 rounded-lg bg-white shadow-sm dropdown-toggle {{ $filterType !== 'all' ? 'active' : '' }}"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                    Type: <span class="fw-semibold">{{ $filterType === 'all' ? 'All' : $filterType }}</span>
                                </button>
                                <div class="dropdown-menu">
                                    <a href="#"
                                       class="dropdown-item ongoing-offline-filter-item {{ $filterType === 'all' ? 'active' : '' }}"
                                       data-param="ongoing_filter_type"
                                       data-value="all">
                                        All Types
                                    </a>
                                    @foreach ($types as $typeOption)
                                        <a href="#"
                                           class="dropdown-item ongoing-offline-filter-item {{ (string) $filterType === (string) $typeOption ? 'active' : '' }}"
                                           data-param="ongoing_filter_type"
                                           data-value="{{ $typeOption }}">
                                            {{ $typeOption }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Dropdown Classification --}}
                            <div class="dropdown">
                                <button type="button"
                                        class="btn btn-filter-chip learning-fast-filter-control border border-slate-300 rounded-lg bg-white shadow-sm dropdown-toggle {{ $filterClassification !== 'all' ? 'active' : '' }}"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                    Classification:
                                    <span class="fw-semibold">
                                        {{ $filterClassification === 'all' ? 'All' : $filterClassification }}
                                    </span>
                                </button>
                                <div class="dropdown-menu learning-quick-dropdown-scroll" style="max-height: 280px; overflow-y: auto;">
                                    <a href="#"
                                       class="dropdown-item ongoing-offline-filter-item {{ $filterClassification === 'all' ? 'active' : '' }}"
                                       data-param="ongoing_filter_classification"
                                       data-value="all">
                                        All Classification
                                    </a>
                                    @foreach ($classifications as $classificationOption)
                                        <a href="#"
                                           class="dropdown-item ongoing-offline-filter-item {{ (string) $filterClassification === (string) $classificationOption ? 'active' : '' }}"
                                           data-param="ongoing_filter_classification"
                                           data-value="{{ $classificationOption }}">
                                            {{ $classificationOption }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Dropdown Year --}}
                            <div class="dropdown">
                                <button type="button"
                                        class="btn btn-filter-chip learning-fast-filter-control border border-slate-300 rounded-lg bg-white shadow-sm dropdown-toggle {{ $filterYear !== 'all' ? 'active' : '' }}"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                    Year:
                                    <span class="fw-semibold">
                                        {{ $filterYear === 'all' ? 'All' : $filterYear }}
                                    </span>
                                </button>
                                <div class="dropdown-menu">
                                    <a href="#"
                                       class="dropdown-item ongoing-offline-filter-item {{ $filterYear === 'all' ? 'active' : '' }}"
                                       data-param="ongoing_filter_year"
                                       data-value="all">
                                        All Years
                                    </a>
                                    @foreach ($years as $yearOption)
                                        <a href="#"
                                           class="dropdown-item ongoing-offline-filter-item {{ (string) $filterYear === (string) $yearOption ? 'active' : '' }}"
                                           data-param="ongoing_filter_year"
                                           data-value="{{ $yearOption }}">
                                            {{ $yearOption }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Search Bar --}}
                        <div class="record-toolbar-search ongoing-toolbar-search flex-grow-1" style="min-width: 240px;">
                            <label class="visually-hidden" for="ongoingOfflineSearch">Search offline records</label>
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                                <input type="text" class="form-control border border-slate-300 rounded-lg bg-white shadow-sm" id="ongoingOfflineSearch"
                                       name="ongoing_search"
                                       value="{{ $search }}"
                                       placeholder="Search offline training, batch, provider..." autocomplete="off">
                            </div>
                        </div>

                        {{-- Action Buttons (Search & Reset) --}}
                        <div class="record-filter-actions ongoing-filter-actions d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-primary btn-learning-primary ongoing-offline-apply-btn record-filter-action-btn"
                                    id="ongoingOfflineApplyBtn"
                                    title="Search & Apply Filter"
                                    aria-label="Search & Apply Filter">
                                <i class="ti ti-search" aria-hidden="true"></i>
                            </button>

                            <button type="button" class="btn btn-outline-secondary btn-learning-outline ongoing-offline-reset-btn record-filter-action-btn"
                                    id="ongoingOfflineResetBtn"
                                    title="Reset Filter"
                                    aria-label="Reset Filter">
                                <i class="ti ti-refresh" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Table or Coming Soon Card --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4 learning-record-card">
            @if ($ongoingNonKpOwl->isEmpty())
                <div class="card-body text-center py-5">
                    <div class="mb-3">
                        <span class="badge bg-blue-50 text-blue-600 px-3 py-2 rounded-pill fs-7 fw-semibold">
                            <i class="bi bi-clock-history ti ti-clock-hour-4 me-1"></i> Feature Coming Soon
                        </span>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">No Ongoing Non-KP OWL Trainings Active</h5>
                    <p class="text-muted fs-7 mb-0">Offline trainings and external scheduled sessions will appear here once active.</p>
                </div>
            @else
                <div class="card-body p-0">
                    <div class="table-responsive learning-record-scroll">
                        <table class="table table-vcenter table-hover card-table mb-0 learning-record-table" id="learningOngoingNonKpOwlTable" style="width:100%">
                            <thead>
                                <tr>
                                    <th class="text-muted text-center" style="width: 4%">NO</th>
                                    <th class="text-muted text-center" style="width: 25%">COURSE TITLE</th>
                                    <th class="text-muted text-center" style="width: 8%">TYPE</th>
                                    <th class="text-muted text-center" style="width: 9%">CATEGORY</th>
                                    <th class="text-muted text-center" style="width: 12%">MAIN CLASSIFICATION</th>
                                    <th class="text-muted text-center" style="width: 9%">COURSE DURATION</th>
                                    <th class="text-muted text-center" style="width: 9%">LEARNING HOUR</th>
                                    <th class="text-muted text-center" style="width: 8%">PROGRESS</th>
                                    <th class="text-muted text-center" style="width: 12%">DATE TIME</th>
                                    <th class="text-muted text-center" style="width: 8%">ATTENDANCE</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($ongoingNonKpOwl as $index => $row)
                                    <tr>
                                        <td class="text-center text-muted record-row-index">{{ $loop->iteration }}</td>
                                        <td class="learning-record-course-cell text-center align-middle">
                                            <div class="d-flex flex-column align-items-center justify-content-center text-center w-100">
                                                <span class="fw-bold text-dark text-center fs-6 mb-1">
                                                    {{ $row['title'] ?? '-' }}
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
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary-lt learning-record-type-badge">
                                                {{ $row['type'] ?: '-' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary-lt text-secondary learning-record-badge">
                                                {{ $row['category'] ?: 'Non KP-OWL' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-blue-lt learning-classification-badge">
                                                {{ $row['classification'] ?? ($row['main_classification'] ?? 'Unclassified') }}
                                            </span>
                                        </td>
                                        <td class="text-center text-nowrap">{{ $row['duration'] ?? '-' }}</td>
                                        <td class="text-center text-nowrap fw-semibold text-primary">
                                            {{ number_format((float)($row['learning_hours'] ?? ($row['learning_hour_value'] ?? 0)), 2) }} h
                                        </td>
                                        <td class="text-center">
                                            @php $prog = (int) ($row['progress'] ?? 0); @endphp
                                            @if ($prog >= 100)
                                                <span class="badge bg-success-lt text-success fw-bold px-2 py-1">100%</span>
                                            @elseif ($prog > 0)
                                                <span class="badge bg-warning-lt text-warning fw-bold px-2 py-1">{{ $prog }}%</span>
                                            @else
                                                <span class="badge bg-secondary-lt fw-bold px-2 py-1">0%</span>
                                            @endif
                                        </td>
                                        @php
                                            $startDt = $row['start_datetime'] ?? ($row['start_date'] ?? '');
                                            $endDt = $row['end_datetime'] ?? ($row['finish_date'] ?? '');
                                        @endphp
                                        <td class="text-muted small learning-record-datetime text-center">
                                            <div class="text-xs text-muted d-inline-block text-start">
                                                <div class="learning-record-date-row text-nowrap">Start : {{ $startDt !== '' ? \Carbon\Carbon::parse($startDt)->format('d/m/y H:i:s') : '-' }}</div>
                                                <div class="learning-record-date-row text-nowrap">End : {{ $endDt !== '' ? \Carbon\Carbon::parse($endDt)->format('d/m/y H:i:s') : '-' }}</div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $rawAtt = $row['attendance_status'] ?? ($row['attendance'] ?? 'No');
                                                $statusKey = strtolower(trim((string) $rawAtt));
                                                $isAttYes = ($rawAtt === true || $rawAtt === 1 || in_array($statusKey, ['1', 'true', 'present', 'yes'], true));
                                                $attStatus = $isAttYes ? 'Yes' : 'No';
                                                $badgeClass = $isAttYes ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger';
                                            @endphp
                                            <span class="badge {{ $badgeClass }} fw-bold px-2 py-1">{{ $attStatus }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
