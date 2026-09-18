@props([
    'assessments',
    'filterOptions' => [],
])

@php
    $assessmentItems = collect($assessments ?? []);
    $assessmentYears = collect($filterOptions['years'] ?? [])->unique()->values();
    $assessmentBatches = collect($filterOptions['batches'] ?? []);
    $assessmentPassingGrades = collect($filterOptions['passingGrades'] ?? []);

    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];

    $quickFilters = [
        'type' => [
            'all' => 'All',
            'content_content' => 'Content (All Content)',
            'content_playlist' => 'Content (Playlist)',
            'trainingplan' => 'Playlist',
            'global' => 'Global',
        ],
        'status' => ['all' => 'All', 'not_started' => 'Pending', 'passed' => 'Passed', 'failed' => 'Failed'],
    ];

    $rawActiveType = request('assessment_filter_type', request('type', 'all'));
    $normalizedType = match ($rawActiveType) {
        'playlist' => 'trainingplan',
        default => $rawActiveType,
    };

    $rawActiveStatus = request('assessment_filter_status', request('status', 'all'));
    $normalizedStatus = match (strtolower(trim($rawActiveStatus))) {
        'pending' => 'not_started',
        default => $rawActiveStatus,
    };

    $rawActiveMonth = request('assessment_filter_month', request('month', 'all'));
    $resolvedMonth = \App\Support\LearningFilterHelper::resolveMonth($rawActiveMonth) ?? $rawActiveMonth;

    $activeFilters = [
        'type' => $normalizedType,
        'status' => $normalizedStatus,
        'year' => request('assessment_filter_year', request('year', 'all')),
        'month' => (string) $resolvedMonth,
        'batch' => request('assessment_filter_batch', request('batch', 'all')),
        'grade' => request('assessment_filter_grade', request('grade', 'all')),
        'score' => request('assessment_filter_score', request('score', 'all')),
        'result' => request('assessment_filter_result', request('result', 'all')),
        'date' => request('assessment_filter_date', request('date', 'all')),
        'availability' => request('assessment_filter_availability', request('availability', 'all')),
    ];
    $activeAdvancedCount = collect($activeFilters)->only(['batch', 'grade', 'score', 'result', 'date', 'availability'])->filter(fn ($value) => $value !== 'all')->count();
@endphp

<div class="card border-0 shadow-sm rounded-4 mb-3 assessment-card">
    <div class="card-body p-4 border-bottom">
        <div class="assessment-filter-shell" id="assessmentFilterBar">
            <div class="assessment-fast-filter-section">
                <label class="form-label small text-muted mb-2 learning-fast-filter-label">Quick Filter</label>

                <div class="assessment-fast-filter-toolbar">
                    <div class="assessment-quick-dropdowns">
                        @foreach ($quickFilters as $filter => $options)
                            <div class="dropdown assessment-filter-dropdown assessment-filter-dropdown-{{ $filter }}"
                                 data-assessment-filter="{{ $filter }}" data-selected-value="{{ $activeFilters[$filter] }}">
                                <button type="button" class="btn btn-filter-chip assessment-fast-filter-control dropdown-toggle {{ $activeFilters[$filter] !== 'all' ? 'active' : '' }}"
                                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    {{ ucfirst($filter) }}: <span class="fw-semibold">{{ $options[$activeFilters[$filter]] ?? ($filter === 'type' && $activeFilters[$filter] === 'content' ? 'Content' : 'All') }}</span>
                                </button>
                                <div class="dropdown-menu">
                                    @foreach ($options as $value => $label)
                                        <a href="#" class="dropdown-item assessment-quick-item {{ $activeFilters[$filter] === $value ? 'active' : '' }}"
                                           data-value="{{ $value }}">{{ $label }}</a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        <div class="dropdown assessment-filter-dropdown assessment-filter-dropdown-year"
                             data-assessment-filter="year" data-selected-value="{{ $activeFilters['year'] }}">
                            <button type="button" class="btn btn-filter-chip assessment-fast-filter-control dropdown-toggle {{ $activeFilters['year'] !== 'all' ? 'active' : '' }}"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Year: <span class="fw-semibold">{{ $activeFilters['year'] === 'all' ? 'All' : $activeFilters['year'] }}</span>
                            </button>
                            <div class="dropdown-menu assessment-quick-dropdown-scroll">
                                <a href="#" class="dropdown-item assessment-quick-item {{ $activeFilters['year'] === 'all' ? 'active' : '' }}" data-value="all">All</a>
                                @foreach ($assessmentYears as $year)
                                    <a href="#" class="dropdown-item assessment-quick-item {{ (string) $activeFilters['year'] === (string) $year ? 'active' : '' }}" data-value="{{ $year }}">{{ $year }}</a>
                                @endforeach
                            </div>
                        </div>

                        <div class="dropdown assessment-filter-dropdown assessment-filter-dropdown-month"
                             data-assessment-filter="month" data-selected-value="{{ $activeFilters['month'] }}">
                            <button type="button" class="btn btn-filter-chip assessment-fast-filter-control dropdown-toggle {{ $activeFilters['month'] !== 'all' ? 'active' : '' }}"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Month: <span class="fw-semibold">{{ $activeFilters['month'] === 'all' ? 'All' : ($months[(int) $activeFilters['month']] ?? 'All') }}</span>
                            </button>
                            <div class="dropdown-menu assessment-quick-dropdown-scroll">
                                <a href="#" class="dropdown-item assessment-quick-item {{ $activeFilters['month'] === 'all' ? 'active' : '' }}" data-value="all">All</a>
                                @foreach ($months as $number => $month)
                                    <a href="#" class="dropdown-item assessment-quick-item {{ (string) $activeFilters['month'] === (string) $number ? 'active' : '' }}" data-value="{{ $number }}">{{ $month }}</a>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="assessment-toolbar-search">
                        <label class="visually-hidden" for="assessment-search-input">Search assessments</label>
                        <div class="position-relative assessment-search-container flex-grow-1 search-input-wrapper">
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                                <input type="text"
                                       class="form-control module-search-input"
                                       id="assessment-search-input"
                                       name="assessment_search"
                                       placeholder="Search assessment..."
                                       value="{{ request('assessment_search', request('search', request('q'))) }}"
                                       autocomplete="off">
                            </div>
                            <div id="assessment-search-results" class="dropdown-menu shadow-sm w-100 position-absolute mt-1 module-search-dropdown" style="display: none; max-height: 380px; overflow-y: auto; z-index: 1050; padding: 0;">
                                <div class="autocomplete-list-group list-group list-group-flush"></div>
                                <div class="search-footer border-top bg-light p-2 text-center sticky-bottom"></div>
                            </div>
                        </div>
                    </div>

                    <div class="assessment-filter-actions">
                        <button type="button" class="btn btn-primary btn-learning-primary assessment-filter-action-btn"
                                id="assessmentApplyFilter" title="Apply Filter" aria-label="Apply Filter">
                            <i class="ti ti-search"></i>
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-learning-secondary assessment-filter-action-btn position-relative"
                                data-bs-toggle="modal" data-bs-target="#assessmentMoreFiltersModal"
                                title="More Filters" aria-label="More Filters">
                            <i class="ti ti-adjustments-horizontal"></i>
                            <span class="badge bg-primary text-white {{ $activeAdvancedCount ? '' : 'd-none' }}" id="assessmentMoreFiltersCount">{{ $activeAdvancedCount ?: '' }}</span>
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-learning-outline assessment-filter-action-btn"
                                id="assessmentResetFilter" title="Reset Filter" aria-label="Reset Filter">
                            <i class="ti ti-refresh"></i>
                        </button>
                        <button type="button" class="btn btn-success btn-learning-success assessment-filter-action-btn"
                                id="assessmentDownloadExcel" title="Download Excel" aria-label="Download Excel">
                            <i class="ti ti-download"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-learning.assessment.assessment-table :assessments="$assessmentItems" />

    <div class="modal fade" id="assessmentMoreFiltersModal" tabindex="-1" role="dialog"
         aria-labelledby="assessmentMoreFiltersTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered learning-filter-modal" role="document">
            <div class="modal-content rounded-4 border-0 shadow">
                <!-- Header -->
                <div class="modal-header border-0 pb-0">
                    <h3 class="modal-title fw-semibold d-flex align-items-center gap-1" id="assessmentMoreFiltersTitle" style="font-size: 1.1rem;">
                        <i class="ti ti-adjustments-horizontal text-primary me-1"></i>
                        All Filters
                    </h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Body -->
                <div class="modal-body pt-2">
                    <p class="text-muted extra-small mb-4">
                        All filters can be combined with the search &amp; the filter chip above.
                    </p>

                    <!-- SECTION 1: ASSESSMENT SCORE -->
                    <div class="learning-filter-group">
                        <div class="fw-semibold text-dark small mb-2">
                            Assessment Score
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-3">
                            @foreach (['all' => 'All', '0_59' => '< 60', '60_79' => '60 – 79', '80_100' => '80 – 100'] as $value => $label)
                                <label class="form-check mb-0 cursor-pointer">
                                    <input class="form-check-input" type="radio" name="assessmentModalScore" value="{{ $value }}" {{ $activeFilters['score'] === $value ? 'checked' : '' }}>
                                    <span class="form-check-label">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <hr class="my-3 text-light-subtle">

                    <!-- SECTION 2: BATCH -->
                    <div class="learning-filter-group">
                        <label class="form-label fw-semibold text-dark small mb-2" for="assessmentModalBatch">Batch</label>
                        <select class="form-select rounded-3 w-100" name="assessmentModalBatch" id="assessmentModalBatch">
                            <option value="all" {{ $activeFilters['batch'] === 'all' ? 'selected' : '' }}>All</option>
                            @foreach ($assessmentBatches as $batch)
                                <option value="{{ $batch }}" {{ (string) $activeFilters['batch'] === (string) $batch ? 'selected' : '' }}>Batch {{ $batch }}</option>
                            @endforeach
                        </select>
                    </div>

                    <hr class="my-3 text-light-subtle">

                    <!-- SECTION 3: SORT BY -->
                    <div class="learning-filter-group">
                        <label class="form-label fw-semibold text-dark small mb-2" for="assessmentModalDate">Sort By</label>
                        <select class="form-select rounded-3 w-100" name="assessmentModalDate" id="assessmentModalDate">
                            <option value="all" {{ $activeFilters['date'] === 'all' ? 'selected' : '' }}>All</option>
                            <option value="newest" {{ $activeFilters['date'] === 'newest' ? 'selected' : '' }}>Newest</option>
                            <option value="oldest" {{ $activeFilters['date'] === 'oldest' ? 'selected' : '' }}>Oldest</option>
                            <option value="title_asc" {{ $activeFilters['date'] === 'title_asc' ? 'selected' : '' }}>Ascending (A - Z)</option>
                            <option value="title_desc" {{ $activeFilters['date'] === 'title_desc' ? 'selected' : '' }}>Descending (Z - A)</option>
                            <option value="this_month" {{ $activeFilters['date'] === 'this_month' ? 'selected' : '' }}>This Month</option>
                            <option value="last_month" {{ $activeFilters['date'] === 'last_month' ? 'selected' : '' }}>Last Month</option>
                            <option value="this_year" {{ $activeFilters['date'] === 'this_year' ? 'selected' : '' }}>This Year</option>
                        </select>
                    </div>

                    <hr class="my-3 text-light-subtle">

                    <!-- SECTION 4: PERIOD -->
                    <div class="learning-filter-group">
                        <div class="fw-semibold text-dark small mb-2">
                            Period
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6 mb-2 mb-md-0">
                                <label class="form-label text-muted small mb-1 fw-medium" for="assessmentFilterFromDate" style="font-size: 13px;">From Date</label>
                                <input type="date" class="form-control rounded-3" id="assessmentFilterFromDate" name="assessmentFilterFromDate" value="{{ request('assessment_filter_from_date') }}">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label text-muted small mb-1 fw-medium" for="assessmentFilterToDate" style="font-size: 13px;">To Date</label>
                                <input type="date" class="form-control rounded-3" id="assessmentFilterToDate" name="assessmentFilterToDate" value="{{ request('assessment_filter_to_date') }}">
                            </div>
                        </div>
                    </div>

                    <!-- HIDDEN BACKWARD COMPATIBILITY INPUTS -->
                    <div class="d-none">
                        <!-- Radio Score fallback -->
                        <input type="radio" name="assessmentModalScore" value="none" {{ $activeFilters['score'] === 'none' ? 'checked' : '' }}>

                        <!-- Radio Batch fallback -->
                        <input type="radio" name="assessmentModalBatch" value="all" {{ $activeFilters['batch'] === 'all' ? 'checked' : '' }}>
                        @foreach ($assessmentBatches as $batch)
                            <input type="radio" name="assessmentModalBatch" value="{{ $batch }}" {{ (string) $activeFilters['batch'] === (string) $batch ? 'checked' : '' }}>
                        @endforeach

                        <!-- Radio Sort Date fallback -->
                        <input type="radio" name="assessmentModalDate" value="all" {{ $activeFilters['date'] === 'all' ? 'checked' : '' }}>
                        @foreach (['newest', 'oldest', 'this_month', 'last_month', 'this_year'] as $value)
                            <input type="radio" name="assessmentModalDate" value="{{ $value }}" {{ $activeFilters['date'] === $value ? 'checked' : '' }}>
                        @endforeach

                        <!-- Passing Grade Radio -->
                        <input type="radio" name="assessmentModalGrade" value="all" {{ $activeFilters['grade'] === 'all' ? 'checked' : '' }}>
                        @foreach ($assessmentPassingGrades as $grade)
                            <input type="radio" name="assessmentModalGrade" value="{{ $grade }}" {{ (string) $activeFilters['grade'] === (string) $grade ? 'checked' : '' }}>
                        @endforeach

                        <!-- Result Radio -->
                        <input type="radio" name="assessmentModalResult" value="all" {{ $activeFilters['result'] === 'all' ? 'checked' : '' }}>
                        <input type="radio" name="assessmentModalResult" value="passed" {{ $activeFilters['result'] === 'passed' ? 'checked' : '' }}>
                        <input type="radio" name="assessmentModalResult" value="failed" {{ $activeFilters['result'] === 'failed' ? 'checked' : '' }}>

                        <!-- Availability Radio -->
                        <input type="radio" name="assessmentModalAvailability" value="all" {{ $activeFilters['availability'] === 'all' ? 'checked' : '' }}>
                        <input type="radio" name="assessmentModalAvailability" value="available" {{ $activeFilters['availability'] === 'available' ? 'checked' : '' }}>
                        <input type="radio" name="assessmentModalAvailability" value="upcoming" {{ $activeFilters['availability'] === 'upcoming' ? 'checked' : '' }}>
                        <input type="radio" name="assessmentModalAvailability" value="expired" {{ $activeFilters['availability'] === 'expired' ? 'checked' : '' }}>
                    </div>
                </div>

                <!-- Footer -->
                <div class="modal-footer border-top pt-3 d-flex justify-content-end align-items-center gap-2">
                    <!-- Reset Button (Placed on the right next to Apply Filter) -->
                    <button type="button" id="btn-reset-assessment-modal" class="btn btn-outline-secondary px-3 rounded-3 d-inline-flex align-items-center gap-1 assessment-modal-reset">
                        <i class="ti ti-refresh fs-2"></i> Reset
                    </button>

                    <!-- Apply Filter Button -->
                    <button type="submit" class="btn btn-primary px-4 rounded-3 d-inline-flex align-items-center gap-1 assessment-apply-filter" style="background-color: #003b6d; border-color: #003b6d;" data-bs-dismiss="modal">
                        <i class="ti ti-search fs-2"></i> Apply Filter
                    </button>
                </div>
            </div>
        </div>
    </div>

    <x-shared.assessment-confirmation-modal />
    <x-shared.assessment-continue-modal />
</div>
