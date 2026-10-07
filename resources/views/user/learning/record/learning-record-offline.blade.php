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
@endphp

<template data-learning-record-hero>
    <x-shared.hero-learning-record
        :stats="[
            ['label' => 'Course Completed', 'value' => number_format($recordStats['courseCompleted'] ?? 0)],
            ['label' => 'Playlist Completed', 'value' => number_format($recordStats['playlistCompleted'] ?? 0)],
            ['label' => 'Learning Hours', 'value' => number_format($recordStats['totalLearningHours'] ?? 0, 2)],
        ]"
    />
</template>

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
                    <div class="fast-filter-toolbar d-flex align-items-center flex-wrap gap-2">
                        <div class="learning-quick-dropdowns learning-fast-filter d-flex align-items-center flex-wrap gap-2">
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

                            {{-- More Filter Trigger Button --}}
                            <button type="button"
                                    class="btn btn-filter-chip learning-fast-filter-control d-inline-flex align-items-center gap-1 {{ $activeMoreFilterCount > 0 ? 'active' : '' }}"
                                    data-bs-toggle="modal"
                                    data-bs-target="#recordOfflineMoreFilterModal"
                                    id="recordOfflineMoreFilterBtn"
                                    title="More Filters">
                                <i class="ti ti-adjustments-horizontal"></i>
                                <span>More Filter</span>
                                @if ($activeMoreFilterCount > 0)
                                    <span class="badge bg-primary text-white rounded-pill ms-1" style="font-size: 10px;">{{ $activeMoreFilterCount }}</span>
                                @endif
                            </button>
                        </div>

                        {{-- Search Input --}}
                        <div class="record-toolbar-search flex-grow-1" style="min-width: 200px;">
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
                            <button type="button" class="btn btn-primary btn-learning-primary record-offline-apply-btn record-filter-action-btn"
                                    id="recordOfflineApplyBtn"
                                    title="Search & Apply Filter"
                                    aria-label="Search & Apply Filter">
                                <i class="ti ti-search" aria-hidden="true"></i>
                            </button>

                            <button type="button" class="btn btn-outline-secondary btn-learning-outline record-offline-reset-btn record-filter-action-btn"
                                    id="recordOfflineResetBtn"
                                    title="Reset Filter"
                                    aria-label="Reset Filter">
                                <i class="ti ti-refresh" aria-hidden="true"></i>
                            </button>

                            <a href="{{ route('user.learning.record.export', array_merge(request()->all(), ['source' => 'offline'])) }}"
                               class="btn btn-outline-success btn-learning-outline record-filter-action-btn"
                               id="recordOfflineExportBtn"
                               title="Export Offline Training Record"
                               aria-label="Export Offline Training Record">
                                <i class="ti ti-download" aria-hidden="true"></i>
                            </a>
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

        {{-- Table Offline --}}
        <div class="card-body p-0">
            <div class="table-responsive learning-record-scroll">
                <table class="table table-vcenter table-hover card-table mb-0 learning-record-table"
                       id="learningRecordOfflineTable" style="width:100%">
                    <thead>
                        <tr>
                            <th class="text-muted text-center" style="width: 3%">No</th>
                            <th class="text-muted" style="width: 22%">Training Title</th>
                            <th class="text-muted" style="width: 8%">Type</th>
                            <th class="text-muted" style="width: 8%">Category</th>
                            <th class="text-muted" style="width: 10%">Classification</th>
                            <th class="text-muted" style="width: 7%">Duration</th>
                            <th class="text-muted" style="width: 8%">Learning Hour</th>
                            <th class="text-muted text-center" style="width: 7%">Progress</th>
                            <th class="text-muted text-center" style="width: 7%">Attendance</th>
                            <th class="text-muted" style="width: 9%">Date</th>
                            <th class="text-muted text-center" style="width: 6%">Score</th>
                            <th class="text-muted text-center" style="width: 5%">Certificate</th>
                            <th class="text-muted text-center" style="width: 4%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $index => $row)
                            <tr class="record-row record-row-offline">
                                <td class="text-center text-muted">{{ $loop->iteration }}</td>
                                <td class="learning-record-course-cell">
                                    <a href="javascript:void(0)"
                                       class="learning-record-title fw-semibold text-dark text-decoration-none btn-record-offline-detail d-block"
                                       data-record-id="{{ $row['id'] }}"
                                       title="Click to view details">
                                        {{ $row['title'] }}
                                    </a>
                                    @if (!empty($row['subtitle']))
                                        <div class="text-muted small mt-0.5">
                                            {{ $row['subtitle'] }}
                                        </div>
                                    @endif
                                    @if (!empty($row['venue']) && $row['venue'] !== '-')
                                        <div class="text-muted small mt-0.5 d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                            <i class="ti ti-map-pin" style="font-size: 0.75rem;"></i>
                                            <span>{{ $row['venue'] }}</span>
                                        </div>
                                    @endif
                                    {{-- Hidden JSON storage for instant zero-N+1 modal population --}}
                                    <script type="application/json" id="record-offline-data-{{ $row['id'] }}">@json($row)</script>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-lt learning-record-type-badge">
                                        {{ $row['type'] }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-muted border px-2 py-1" style="font-size: 11px;">
                                        {{ $row['category'] }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-blue-lt learning-classification-badge">
                                        {{ $row['classification'] }}
                                    </span>
                                </td>
                                <td class="text-nowrap">{{ $row['duration'] }}</td>
                                <td class="text-nowrap fw-semibold text-primary">{{ $row['learning_hour'] }}</td>
                                <td class="text-center">
                                    @if ($row['progress'] >= 100)
                                        <span class="badge bg-success-lt text-success fw-bold px-2 py-1">100%</span>
                                    @elseif ($row['progress'] > 0)
                                        <span class="badge bg-warning-lt text-warning fw-bold px-2 py-1">{{ $row['progress'] }}%</span>
                                    @else
                                        <span class="badge bg-secondary-lt fw-bold px-2 py-1">0%</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($row['attendance_status'] === 'Present')
                                        <span class="badge bg-success-lt text-success fw-bold px-2 py-1">Present</span>
                                    @elseif ($row['attendance_status'] === 'Absent')
                                        <span class="badge bg-danger-lt text-danger fw-bold px-2 py-1">Absent</span>
                                    @else
                                        <span class="badge bg-secondary-lt fw-bold px-2 py-1">-</span>
                                    @endif
                                </td>
                                <td class="text-nowrap small text-muted">{{ $row['date_display'] }}</td>
                                <td class="text-center">
                                    @if ($row['score'] !== null)
                                        <span class="learning-score-pill {{ $row['score'] >= 70 ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger' }}">
                                            {{ $row['score'] }}
                                        </span>
                                    @else
                                        <span class="learning-score-empty-pill">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if (!empty($row['certificate']) && strtolower($row['certificate']) === 'yes')
                                        <span class="badge bg-success-lt text-success p-1" title="Certificate issued">
                                            <i class="ti ti-certificate fs-3"></i>
                                        </span>
                                    @elseif (!empty($row['certificate']) && strtolower($row['certificate']) === 'no')
                                        <span class="badge bg-danger-lt text-danger px-1 py-0.5" style="font-size: 10px;" title="No certificate">No</span>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td class="text-center text-nowrap">
                                    <button type="button"
                                            class="btn btn-sm btn-icon btn-ghost-primary btn-record-offline-detail"
                                            data-record-id="{{ $row['id'] }}"
                                            title="View Training Record Detail"
                                            aria-label="View Training Record Detail">
                                        <i class="ti ti-eye fs-3"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center text-muted py-5">
                                    <div class="empty py-3">
                                        <div class="empty-icon text-muted mb-3">
                                            <i class="ti ti-certificate fs-1 opacity-50"></i>
                                        </div>
                                        <p class="empty-title fw-bold text-dark mb-1">No Offline Training Records Found</p>
                                        <p class="empty-subtitle text-muted mb-0 small">
                                            You currently do not have any recorded offline training matching the selected filters.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($rows->isNotEmpty())
            <div class="card-footer d-flex align-items-center justify-content-between py-2 px-4">
                <span class="text-muted small">Showing {{ $rows->count() }} records</span>
            </div>
        @endif
    </div>
</div>

{{-- ========================================================================= --}}
{{-- MODAL: MORE FILTER OFFLINE RECORD                                         --}}
{{-- ========================================================================= --}}
<div class="modal fade" id="recordOfflineMoreFilterModal" tabindex="-1" aria-labelledby="recordOfflineMoreFilterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="recordOfflineMoreFilterModalLabel">
                    <i class="ti ti-adjustments-horizontal text-primary fs-2"></i>
                    <span>More Filters — Offline Record</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <form id="recordOfflineMoreFilterForm">
                    <div class="row g-3">
                        {{-- Category --}}
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-muted mb-1" for="mf_category">Category</label>
                            <select class="form-select form-select-sm" id="mf_category" name="record_filter_category">
                                <option value="all" {{ $filterCategory === 'all' ? 'selected' : '' }}>All Categories</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat }}" {{ (string) $filterCategory === (string) $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Sub Classification --}}
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-muted mb-1" for="mf_sub_classification">Sub Classification</label>
                            <select class="form-select form-select-sm" id="mf_sub_classification" name="record_filter_sub_classification">
                                <option value="all" {{ $filterSubClassification === 'all' ? 'selected' : '' }}>All Sub Classifications</option>
                                @foreach ($subClassifications as $subClass)
                                    <option value="{{ $subClass }}" {{ (string) $filterSubClassification === (string) $subClass ? 'selected' : '' }}>{{ $subClass }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Batch --}}
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted mb-1" for="mf_batch">Batch</label>
                            <select class="form-select form-select-sm" id="mf_batch" name="record_filter_batch">
                                <option value="all" {{ $filterBatch === 'all' ? 'selected' : '' }}>All Batches</option>
                                @foreach ($batches as $bat)
                                    <option value="{{ $bat }}" {{ (string) $filterBatch === (string) $bat ? 'selected' : '' }}>{{ $bat }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Delivery Method --}}
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted mb-1" for="mf_delivery_method">Delivery Method</label>
                            <select class="form-select form-select-sm" id="mf_delivery_method" name="record_filter_delivery_method">
                                <option value="all" {{ $filterDeliveryMethod === 'all' ? 'selected' : '' }}>All Methods</option>
                                @foreach ($deliveryMethods as $method)
                                    <option value="{{ $method }}" {{ (string) $filterDeliveryMethod === (string) $method ? 'selected' : '' }}>{{ $method }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Provider --}}
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-muted mb-1" for="mf_provider">Provider</label>
                            <select class="form-select form-select-sm" id="mf_provider" name="record_filter_provider">
                                <option value="all" {{ $filterProvider === 'all' ? 'selected' : '' }}>All Providers</option>
                                @foreach ($providers as $prov)
                                    <option value="{{ $prov }}" {{ (string) $filterProvider === (string) $prov ? 'selected' : '' }}>{{ $prov }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Attendance --}}
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted mb-1" for="mf_attendance">Attendance</label>
                            <select class="form-select form-select-sm" id="mf_attendance" name="record_filter_attendance">
                                <option value="all" {{ $filterAttendance === 'all' ? 'selected' : '' }}>All</option>
                                <option value="present" {{ strtolower($filterAttendance) === 'present' ? 'selected' : '' }}>Present</option>
                                <option value="absent" {{ strtolower($filterAttendance) === 'absent' ? 'selected' : '' }}>Absent</option>
                            </select>
                        </div>

                        {{-- Certificate Status --}}
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted mb-1" for="mf_certificate">Certificate</label>
                            <select class="form-select form-select-sm" id="mf_certificate" name="record_filter_certificate">
                                <option value="all" {{ $filterCertificate === 'all' ? 'selected' : '' }}>All</option>
                                <option value="certified" {{ in_array(strtolower($filterCertificate), ['certified', 'yes'], true) ? 'selected' : '' }}>Certified (Yes)</option>
                                <option value="uncertified" {{ in_array(strtolower($filterCertificate), ['uncertified', 'no'], true) ? 'selected' : '' }}>Not Certified (No)</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top py-2 px-3 d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnResetMoreFilterModal">
                    <i class="ti ti-rotate me-1"></i> Reset More Filters
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm" id="btnApplyMoreFilterModal">
                        <i class="ti ti-check me-1"></i> Apply Filters
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ========================================================================= --}}
{{-- MODAL: VIEW DETAIL OFFLINE RECORD                                         --}}
{{-- ========================================================================= --}}
<div class="modal fade" id="recordOfflineDetailModal" tabindex="-1" aria-labelledby="recordOfflineDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-bottom py-3 bg-light">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-certificate text-primary fs-1"></i>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="recordOfflineDetailModalLabel">Training Record Detail</h5>
                        <div class="text-muted small" id="detailCourseSubHeader">-</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4">
                {{-- 1. TRAINING INFORMATION --}}
                <div class="card border mb-3 rounded-3 shadow-none">
                    <div class="card-header bg-light-subtle py-2 px-3 border-bottom d-flex align-items-center gap-2">
                        <i class="ti ti-school text-primary fs-3"></i>
                        <h6 class="card-title mb-0 fw-bold fs-4 text-dark">Training Information</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2">
                            <div class="col-md-8 col-12">
                                <span class="text-muted small d-block">Course Name</span>
                                <span class="fw-semibold text-dark fs-4" id="dtCourseName">-</span>
                            </div>
                            <div class="col-md-4 col-12">
                                <span class="text-muted small d-block">Sub Course Name</span>
                                <span class="fw-medium text-dark" id="dtSubCourseName">-</span>
                            </div>
                            <div class="col-md-3 col-6">
                                <span class="text-muted small d-block">Type</span>
                                <span class="badge bg-secondary-lt" id="dtType">-</span>
                            </div>
                            <div class="col-md-3 col-6">
                                <span class="text-muted small d-block">Category</span>
                                <span class="badge bg-light text-muted border" id="dtCategory">-</span>
                            </div>
                            <div class="col-md-3 col-6">
                                <span class="text-muted small d-block">Level</span>
                                <span class="fw-medium text-dark" id="dtLevel">-</span>
                            </div>
                            <div class="col-md-3 col-6">
                                <span class="text-muted small d-block">Batch</span>
                                <span class="fw-medium text-dark" id="dtBatch">-</span>
                            </div>
                            <div class="col-md-6 col-12">
                                <span class="text-muted small d-block">Main Classification</span>
                                <span class="badge bg-blue-lt" id="dtClassification">-</span>
                            </div>
                            <div class="col-md-6 col-12">
                                <span class="text-muted small d-block">Sub Classification</span>
                                <span class="fw-medium text-dark" id="dtSubClassification">-</span>
                            </div>
                            <div class="col-md-6 col-12">
                                <span class="text-muted small d-block">Delivery Method</span>
                                <span class="fw-medium text-dark" id="dtDeliveryMethod">-</span>
                            </div>
                            <div class="col-md-6 col-12">
                                <span class="text-muted small d-block">Sub Delivery Method</span>
                                <span class="fw-medium text-dark" id="dtSubDeliveryMethod">-</span>
                            </div>
                            <div class="col-md-6 col-12">
                                <span class="text-muted small d-block">Provider</span>
                                <span class="fw-medium text-dark" id="dtProvider">-</span>
                            </div>
                            <div class="col-md-6 col-12">
                                <span class="text-muted small d-block">Provider Name</span>
                                <span class="fw-medium text-dark" id="dtProviderName">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. SCHEDULE & VENUE --}}
                <div class="card border mb-3 rounded-3 shadow-none">
                    <div class="card-header bg-light-subtle py-2 px-3 border-bottom d-flex align-items-center gap-2">
                        <i class="ti ti-calendar-time text-primary fs-3"></i>
                        <h6 class="card-title mb-0 fw-bold fs-4 text-dark">Schedule & Venue</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2">
                            <div class="col-md-6 col-12">
                                <span class="text-muted small d-block">Date</span>
                                <span class="fw-medium text-dark" id="dtDateDisplay">-</span>
                            </div>
                            <div class="col-md-6 col-12">
                                <span class="text-muted small d-block">Training Hours / Time</span>
                                <span class="fw-medium text-dark" id="dtTimeDisplay">-</span>
                            </div>
                            <div class="col-md-6 col-12">
                                <span class="text-muted small d-block">Duration</span>
                                <span class="fw-medium text-dark" id="dtDuration">-</span>
                            </div>
                            <div class="col-md-6 col-12">
                                <span class="text-muted small d-block">Credited Learning Hours</span>
                                <span class="fw-bold text-primary" id="dtLearningHours">-</span>
                            </div>
                            <div class="col-md-6 col-12">
                                <span class="text-muted small d-block">Venue / Location</span>
                                <span class="fw-medium text-dark" id="dtVenue">-</span>
                            </div>
                            <div class="col-md-6 col-12">
                                <span class="text-muted small d-block">Instructor / Facilitator</span>
                                <span class="fw-medium text-dark" id="dtInstructor">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 3. MY LEARNING RESULT --}}
                <div class="card border mb-3 rounded-3 shadow-none">
                    <div class="card-header bg-light-subtle py-2 px-3 border-bottom d-flex align-items-center gap-2">
                        <i class="ti ti-award text-success fs-3"></i>
                        <h6 class="card-title mb-0 fw-bold fs-4 text-dark">My Learning Result</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-3 col-6">
                                <span class="text-muted small d-block">Progress</span>
                                <span class="fw-bold fs-4" id="dtProgressBadge">-</span>
                            </div>
                            <div class="col-md-3 col-6">
                                <span class="text-muted small d-block">Attendance</span>
                                <span class="fw-bold fs-4" id="dtAttendanceBadge">-</span>
                            </div>
                            <div class="col-md-3 col-6">
                                <span class="text-muted small d-block">Assessment Score</span>
                                <span class="fw-bold fs-4" id="dtAssessmentScore">-</span>
                            </div>
                            <div class="col-md-3 col-6">
                                <span class="text-muted small d-block">Certificate Status</span>
                                <span class="fw-bold fs-4" id="dtCertificateStatus">-</span>
                            </div>
                            <div class="col-12 mt-2" id="dtAttendanceReasonContainer" style="display: none;">
                                <div class="alert alert-warning py-2 px-3 mb-0 d-flex align-items-start gap-2">
                                    <i class="ti ti-alert-circle fs-3 mt-0.5"></i>
                                    <div>
                                        <strong class="d-block small">Attendance Reason / Justification:</strong>
                                        <span id="dtAttendanceReason" class="small">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 4. EVALUATION (KIRKPATRICK) --}}
                <div class="card border mb-3 rounded-3 shadow-none">
                    <div class="card-header bg-light-subtle py-2 px-3 border-bottom d-flex align-items-center gap-2">
                        <i class="ti ti-chart-bar text-info fs-3"></i>
                        <h6 class="card-title mb-0 fw-bold fs-4 text-dark">Evaluation (Kirkpatrick Results)</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2">
                            <div class="col-md-4 col-12">
                                <span class="text-muted small d-block">L1 Program (Reaction)</span>
                                <span class="fw-medium text-dark" id="dtL1Program">-</span>
                            </div>
                            <div class="col-md-4 col-12">
                                <span class="text-muted small d-block">L2 Numerical (Post Test)</span>
                                <span class="fw-medium text-dark" id="dtL2Numerical">-</span>
                            </div>
                            <div class="col-md-4 col-12">
                                <span class="text-muted small d-block">L2 Verbal (Post Test Result)</span>
                                <span class="fw-medium text-dark" id="dtL2Verbal">-</span>
                            </div>
                            <div class="col-md-4 col-12">
                                <span class="text-muted small d-block">L3 Numerical (Project)</span>
                                <span class="fw-medium text-dark" id="dtL3Numerical">-</span>
                            </div>
                            <div class="col-md-4 col-12">
                                <span class="text-muted small d-block">L3 Verbal (Implementation)</span>
                                <span class="fw-medium text-dark" id="dtL3Verbal">-</span>
                            </div>
                            <div class="col-md-4 col-12">
                                <span class="text-muted small d-block">PIC Administrator</span>
                                <span class="fw-medium text-dark" id="dtPic">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 5. EMPLOYEE SNAPSHOT (AT RECORD TIME) --}}
                <div class="card border mb-3 rounded-3 shadow-none">
                    <div class="card-header bg-light-subtle py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <i class="ti ti-user-check text-secondary fs-3"></i>
                            <h6 class="card-title mb-0 fw-bold fs-4 text-dark">Employee Snapshot (At Record Time)</h6>
                        </div>
                        <span class="text-muted small" style="font-size: 0.72rem;">Historical Profile</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2">
                            <div class="col-md-4 col-6">
                                <span class="text-muted small d-block">Employee ID</span>
                                <span class="fw-medium text-dark" id="dtEmployeeId">-</span>
                            </div>
                            <div class="col-md-4 col-6">
                                <span class="text-muted small d-block">Participant Name</span>
                                <span class="fw-medium text-dark" id="dtParticipantName">-</span>
                            </div>
                            <div class="col-md-4 col-6">
                                <span class="text-muted small d-block">Grade</span>
                                <span class="fw-medium text-dark" id="dtGrade">-</span>
                            </div>
                            <div class="col-md-4 col-6">
                                <span class="text-muted small d-block">Job Position</span>
                                <span class="fw-medium text-dark" id="dtJobPosition">-</span>
                            </div>
                            <div class="col-md-4 col-6">
                                <span class="text-muted small d-block">Team</span>
                                <span class="fw-medium text-dark" id="dtTeam">-</span>
                            </div>
                            <div class="col-md-4 col-6">
                                <span class="text-muted small d-block">Department</span>
                                <span class="fw-medium text-dark" id="dtDepartment">-</span>
                            </div>
                            <div class="col-12">
                                <span class="text-muted small d-block">Company</span>
                                <span class="fw-medium text-dark" id="dtCompany">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 6. SERVICE BOND (OBLIGATION) --}}
                <div class="card border mb-0 rounded-3 shadow-none">
                    <div class="card-header bg-light-subtle py-2 px-3 border-bottom d-flex align-items-center gap-2">
                        <i class="ti ti-file-certificate text-warning fs-3"></i>
                        <h6 class="card-title mb-0 fw-bold fs-4 text-dark">Service Bond & Obligation</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-4 col-12">
                                <span class="text-muted small d-block">Bond Status</span>
                                <span class="fw-medium text-dark" id="dtBondStatus">-</span>
                            </div>
                            <div class="col-md-4 col-12">
                                <span class="text-muted small d-block">Bond End Date</span>
                                <span class="fw-medium text-dark" id="dtBondEndDate">-</span>
                            </div>
                            <div class="col-md-4 col-12">
                                <span class="text-muted small d-block">Bond Amount</span>
                                <span class="fw-bold text-dark" id="dtBondAmount">-</span>
                            </div>
                            <div class="col-12 mt-2" id="dtBondNotice" style="display: none;">
                                <div class="alert alert-info py-2 px-3 mb-0 small d-flex align-items-center gap-2">
                                    <i class="ti ti-info-circle fs-3"></i>
                                    <span>This training entails an active corporate service bond agreement.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top py-2 px-3">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
