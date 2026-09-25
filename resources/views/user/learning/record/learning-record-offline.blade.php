@php
    $types = collect($filterOptions['types'] ?? []);
    $classifications = collect($filterOptions['classifications'] ?? []);
    $years = collect($filterOptions['years'] ?? []);

    $filterType = request('record_filter_type', request('type', 'all'));
    $filterClassification = request('record_filter_classification', request('classification', 'all'));
    $filterYear = request('record_filter_year', request('year', 'all'));
    $search = request('record_search', request('search', request('q', '')));
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
    <div class="learning-record-mode-header mb-3">
        <x-learning.record-mode-switch mode="offline" />
    </div>

    {{-- Filter Card Offline --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 learning-record-card">
        <div class="card-body p-4 border-bottom">
            <div class="record-filter-shell" id="recordOfflineFilterBar">
                <div class="record-fast-filter-section mt-1">
                    <label class="form-label small text-muted mb-2 learning-fast-filter-label">Quick Filter</label>

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
                        </div>

                        {{-- Search Input --}}
                        <div class="record-toolbar-search flex-grow-1" style="min-width: 220px;">
                            <label class="visually-hidden" for="recordOfflineSearch">Search offline records</label>
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                                <input type="text" class="form-control" id="recordOfflineSearch"
                                       value="{{ $search }}"
                                       placeholder="Search offline training, type, provider..." autocomplete="off">
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
                        </div>
                    </div>
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
                            <th class="text-muted text-center" style="width: 4%">No</th>
                            <th class="text-muted" style="width: 24%">Training Title</th>
                            <th class="text-muted" style="width: 9%">Type</th>
                            <th class="text-muted" style="width: 9%">Category</th>
                            <th class="text-muted" style="width: 11%">Classification</th>
                            <th class="text-muted" style="width: 8%">Duration</th>
                            <th class="text-muted" style="width: 8%">Learning Hour</th>
                            <th class="text-muted" style="width: 7%">Progress</th>
                            <th class="text-muted" style="width: 10%">Date</th>
                            <th class="text-muted text-center" style="width: 8%">Score</th>
                            <th class="text-muted text-center" style="width: 6%">Certificate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $index => $row)
                            <tr class="record-row record-row-offline">
                                <td class="text-center text-muted">{{ $loop->iteration }}</td>
                                <td class="learning-record-course-cell">
                                    <div class="learning-record-title fw-semibold text-dark">
                                        {{ $row['title'] }}
                                    </div>
                                    @if (!empty($row['subtitle']))
                                        <div class="text-muted small mt-0.5">
                                            {{ $row['subtitle'] }}
                                        </div>
                                    @endif
                                    @if (!empty($row['venue']))
                                        <div class="text-muted small mt-0.5 d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                            <i class="ti ti-map-pin" style="font-size: 0.75rem;"></i>
                                            <span>{{ $row['venue'] }}</span>
                                        </div>
                                    @endif
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
                                <td>
                                    @if ($row['progress'] >= 100)
                                        <span class="badge bg-success-lt text-success fw-bold px-2 py-1">100%</span>
                                    @elseif ($row['progress'] > 0)
                                        <span class="badge bg-warning-lt text-warning fw-bold px-2 py-1">{{ $row['progress'] }}%</span>
                                    @else
                                        <span class="badge bg-secondary-lt fw-bold px-2 py-1">0%</span>
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
                                    @if (!empty($row['certificate']))
                                        <span class="badge bg-success-lt text-success p-1" title="Certificate available">
                                            <i class="ti ti-certificate fs-3"></i>
                                        </span>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted py-5">
                                    <div class="empty py-3">
                                        <div class="empty-icon text-muted mb-3">
                                            <i class="ti ti-certificate fs-1 opacity-50"></i>
                                        </div>
                                        <p class="empty-title fw-bold text-dark mb-1">No Offline Training Records Found</p>
                                        <p class="empty-subtitle text-muted mb-0 small">
                                            You currently do not have any recorded offline training or certification history.
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
