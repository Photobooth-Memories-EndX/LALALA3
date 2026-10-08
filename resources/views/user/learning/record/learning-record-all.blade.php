@php
    $sources = $filterOptions['sources'] ?? ['all' => 'All Sources', 'online' => 'Online', 'offline' => 'Offline'];
    $types = collect($filterOptions['types'] ?? []);
    $classifications = collect($filterOptions['classifications'] ?? []);
    $years = collect($filterOptions['years'] ?? []);

    $filterSource = request('record_filter_source', request('source', 'all'));
    $filterType = request('record_filter_type', request('type', 'all'));
    $filterClassification = request('record_filter_classification', request('classification', 'all'));
    $filterYear = request('record_filter_year', request('year', 'all'));
    $search = request('record_search', request('search', request('q', '')));

    // Feature availability flag: set to true to re-enable combined view when ready
    $isAllViewAvailable = false;
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
    {{-- Filter Card All --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 learning-record-card">
        <div class="card-body p-4 {{ $isAllViewAvailable ? 'border-bottom' : '' }}">
            <div class="record-filter-shell" id="recordAllFilterBar">
                <div class="learning-filter-heading fast-filter-header {{ $isAllViewAvailable ? '' : 'mb-0' }}">
                    <div class="fast-filter-header-copy">
                        <h3 class="card-title fast-filter-title">Quick Filter</h3>
                    </div>
                    <x-learning.record-mode-switch mode="all" />
                </div>

                @if ($isAllViewAvailable)
                <div class="record-fast-filter-section">
                    <div class="fast-filter-toolbar d-flex align-items-center flex-wrap gap-2">
                        <div class="learning-quick-dropdowns learning-fast-filter d-flex align-items-center flex-wrap gap-2">
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
                        <div class="record-toolbar-search flex-grow-1" style="min-width: 220px;">
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
                            <button type="button" class="btn btn-primary btn-learning-primary record-all-apply-btn record-filter-action-btn"
                                    id="recordAllApplyBtn"
                                    title="Search & Apply Filter"
                                    aria-label="Search & Apply Filter">
                                <i class="ti ti-search" aria-hidden="true"></i>
                            </button>

                            <button type="button" class="btn btn-outline-secondary btn-learning-outline record-all-reset-btn record-filter-action-btn"
                                    id="recordAllResetBtn"
                                    title="Reset Filter"
                                    aria-label="Reset Filter">
                                <i class="ti ti-refresh" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        @if ($isAllViewAvailable)
        {{-- Table Combined --}}
        <div class="card-body p-0">
            <div class="table-responsive learning-record-scroll">
                <table class="table table-vcenter table-hover card-table mb-0 learning-record-table"
                       id="learningRecordAllTable" style="width:100%">
                    <thead>
                        <tr>
                            <th class="text-muted text-center" style="width: 4%">No</th>
                            <th class="text-muted" style="width: 7%">Source</th>
                            <th class="text-muted" style="width: 23%">Learning / Training Title</th>
                            <th class="text-muted" style="width: 8%">Type</th>
                            <th class="text-muted" style="width: 8%">Category</th>
                            <th class="text-muted" style="width: 10%">Classification</th>
                            <th class="text-muted" style="width: 7%">Duration</th>
                            <th class="text-muted" style="width: 8%">Learning Hour</th>
                            <th class="text-muted" style="width: 7%">Progress</th>
                            <th class="text-muted" style="width: 10%">Date</th>
                            <th class="text-muted text-center" style="width: 8%">Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $index => $row)
                            <tr class="record-row record-row-{{ $row['source'] }}">
                                <td class="text-center text-muted">{{ $loop->iteration }}</td>
                                <td>
                                    @if ($row['source'] === 'offline')
                                        <span class="badge bg-amber-lt text-amber fw-semibold px-2 py-1" style="font-size: 11px;">
                                            <i class="ti ti-certificate me-1"></i>Offline
                                        </span>
                                    @else
                                        <span class="badge bg-blue-lt text-blue fw-semibold px-2 py-1" style="font-size: 11px;">
                                            <i class="ti ti-device-laptop me-1"></i>Online
                                        </span>
                                    @endif
                                </td>
                                <td class="learning-record-course-cell">
                                    @if (!empty($row['slug']) && ($row['can_open'] ?? true))
                                        <a href="{{ route('content.user.view', $row['slug']) }}" class="learning-record-link fw-semibold text-dark">
                                            {{ $row['title'] }}
                                        </a>
                                    @else
                                        <div class="learning-record-title fw-semibold text-dark">
                                            {{ $row['title'] }}
                                        </div>
                                    @endif
                                    @if (!empty($row['subtitle']))
                                        <div class="text-muted small mt-0.5">
                                            {{ $row['subtitle'] }}
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
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted py-5">
                                    <div class="empty py-3">
                                        <div class="empty-icon text-muted mb-3">
                                            <i class="ti ti-stack-2 fs-1 opacity-50"></i>
                                        </div>
                                        <p class="empty-title fw-bold text-dark mb-1">No Learning Records Found</p>
                                        <p class="empty-subtitle text-muted mb-0 small">
                                            No completed online or offline learning records match your current filter selection.
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
                <span class="text-muted small">Showing {{ $rows->count() }} combined records</span>
            </div>
        @endif
        @else
        {{-- Temporarily Unavailable State --}}
        <div class="card-body text-center py-5">
            <div class="py-5">
                <div class="text-muted mb-3">
                    <i class="ti ti-layout-list text-muted" style="font-size: 3.5rem; opacity: 0.5;"></i>
                </div>
                <h3 class="fw-bold text-dark mb-2">This page is not available yet.</h3>
                <p class="text-muted mb-0" style="max-width: 480px; margin: 0 auto;">
                    We're still preparing the combined learning record view.
                </p>
            </div>
        </div>
        @endif
    </div>
</div>
