@props([
    'assessments',
    'filterOptions' => [],
])

@php
    $assessmentItems = collect($assessments ?? []);
    $assessmentYears = collect($filterOptions['years'] ?? []);
    $assessmentBatches = collect($filterOptions['batches'] ?? []);
    $assessmentPassingGrades = collect($filterOptions['passingGrades'] ?? []);

    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];

    $quickFilters = [
        'type' => ['all' => 'All', 'content' => 'Content', 'playlist' => 'Playlist', 'global' => 'Global'],
        'status' => ['all' => 'All', 'not_started' => 'Pending', 'passed' => 'Passed', 'failed' => 'Failed'],
    ];
    $activeFilters = [
        'type' => request('assessment_filter_type', 'all'),
        'status' => request('assessment_filter_status', 'all'),
        'year' => request('assessment_filter_year', 'all'),
        'month' => request('assessment_filter_month', 'all'),
        'batch' => request('assessment_filter_batch', 'all'),
        'grade' => request('assessment_filter_grade', 'all'),
        'score' => request('assessment_filter_score', 'all'),
        'result' => request('assessment_filter_result', 'all'),
        'date' => request('assessment_filter_date', 'all'),
        'availability' => request('assessment_filter_availability', 'all'),
    ];
    $activeAdvancedCount = collect($activeFilters)->only(['batch', 'grade', 'score', 'result', 'date', 'availability'])->filter(fn ($value) => $value !== 'all')->count();
@endphp

<div class="card border-0 shadow-sm rounded-4 mb-3 overflow-hidden">
    <div class="card-body p-4 border-bottom">
        <div class="record-filter-shell assessment-filter-shell" id="assessmentFilterBar">
            <x-learning.assessment.assessment-header>
                <label class="form-label small text-muted mb-1" for="assessmentSearch">Search</label>
                <div class="input-icon">
                    <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                    <input type="search"
                           class="form-control"
                           id="assessmentSearch"
                           placeholder="Search assessment..."
                           value="{{ request('assessment_search') }}"
                           autocomplete="off">
                </div>
            </x-learning.assessment.assessment-header>

            <div class="learning-record-filter-row mt-3 mb-0">
                <div class="learning-record-fast-filter">
                    <label class="form-label small text-muted mb-2 learning-fast-filter-label">Fast Filter</label>

                    <div class="d-flex flex-wrap learning-quick-dropdowns assessment-quick-dropdowns learning-fast-filter">
                        @foreach ($quickFilters as $filter => $options)
                            <div class="dropdown" data-assessment-filter="{{ $filter }}" data-selected-value="{{ $activeFilters[$filter] }}">
                                <button type="button" class="btn btn-filter-chip learning-fast-filter-control dropdown-toggle {{ $activeFilters[$filter] !== 'all' ? 'active' : '' }}"
                                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    {{ ucfirst($filter) }}: <span class="fw-semibold">{{ $options[$activeFilters[$filter]] ?? 'All' }}</span>
                                </button>
                                <div class="dropdown-menu">
                                    @foreach ($options as $value => $label)
                                        <a href="#" class="dropdown-item assessment-quick-item {{ $activeFilters[$filter] === $value ? 'active' : '' }}"
                                           data-value="{{ $value }}">{{ $label }}</a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        <div class="dropdown" data-assessment-filter="year" data-selected-value="{{ $activeFilters['year'] }}">
                            <button type="button" class="btn btn-filter-chip learning-fast-filter-control dropdown-toggle {{ $activeFilters['year'] !== 'all' ? 'active' : '' }}"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Year: <span class="fw-semibold">{{ $activeFilters['year'] === 'all' ? 'All' : $activeFilters['year'] }}</span>
                            </button>
                            <div class="dropdown-menu learning-quick-dropdown-scroll">
                                <a href="#" class="dropdown-item assessment-quick-item {{ $activeFilters['year'] === 'all' ? 'active' : '' }}" data-value="all">All</a>
                                @foreach ($assessmentYears as $year)
                                    <a href="#" class="dropdown-item assessment-quick-item {{ (string) $activeFilters['year'] === (string) $year ? 'active' : '' }}" data-value="{{ $year }}">{{ $year }}</a>
                                @endforeach
                            </div>
                        </div>

                        <div class="dropdown" data-assessment-filter="month" data-selected-value="{{ $activeFilters['month'] }}">
                            <button type="button" class="btn btn-filter-chip learning-fast-filter-control dropdown-toggle {{ $activeFilters['month'] !== 'all' ? 'active' : '' }}"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Month: <span class="fw-semibold">{{ $activeFilters['month'] === 'all' ? 'All' : ($months[(int) $activeFilters['month']] ?? 'All') }}</span>
                            </button>
                            <div class="dropdown-menu learning-quick-dropdown-scroll">
                                <a href="#" class="dropdown-item assessment-quick-item {{ $activeFilters['month'] === 'all' ? 'active' : '' }}" data-value="all">All</a>
                                @foreach ($months as $number => $month)
                                    <a href="#" class="dropdown-item assessment-quick-item {{ (string) $activeFilters['month'] === (string) $number ? 'active' : '' }}" data-value="{{ $number }}">{{ $month }}</a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="record-filter-actions assessment-filter-actions">
                    <button type="button" class="btn btn-outline-success btn-learning-success record-filter-action-btn" id="assessmentDownloadExcel">
                        <i class="ti ti-download"></i><span>Download Excel</span>
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-learning-secondary record-filter-action-btn"
                            data-toggle="modal" data-target="#assessmentMoreFiltersModal">
                        <i class="ti ti-adjustments-horizontal"></i><span>More Filters</span>
                        <span class="badge bg-primary text-white {{ $activeAdvancedCount ? '' : 'd-none' }}" id="assessmentMoreFiltersCount">{{ $activeAdvancedCount ?: '' }}</span>
                    </button>
                    <button type="button" class="btn btn-primary btn-learning-primary record-filter-action-btn" id="assessmentApplyFilter">
                        <i class="ti ti-check"></i><span>Apply Filter</span>
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-learning-outline record-filter-action-btn" id="assessmentResetFilter">
                        <i class="ti ti-refresh"></i><span>Reset</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <x-learning.assessment.assessment-table :assessments="$assessmentItems" />

    <div class="modal fade" id="assessmentMoreFiltersModal" tabindex="-1" role="dialog"
         aria-labelledby="assessmentMoreFiltersTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content assessment-filter-modal">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0" id="assessmentMoreFiltersTitle">More Filters</h5>
                        <div class="text-muted small">Advanced Filter</div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body assessment-more-filters">
                    <div class="assessment-filter-section">
                        <div class="assessment-filter-title">Batch</div>
                        <div class="assessment-filter-options">
                            <label class="form-check"><input class="form-check-input" type="radio" name="assessmentModalBatch" value="all" {{ $activeFilters['batch'] === 'all' ? 'checked' : '' }}><span class="form-check-label">All</span></label>
                            @foreach ($assessmentBatches as $batch)
                                <label class="form-check"><input class="form-check-input" type="radio" name="assessmentModalBatch" value="{{ $batch }}" {{ (string) $activeFilters['batch'] === (string) $batch ? 'checked' : '' }}><span class="form-check-label">{{ $batch }}</span></label>
                            @endforeach
                        </div>
                    </div>

                    <div class="assessment-filter-section">
                        <div class="assessment-filter-title">Passing Grade</div>
                        <div class="assessment-filter-options">
                            <label class="form-check"><input class="form-check-input" type="radio" name="assessmentModalGrade" value="all" {{ $activeFilters['grade'] === 'all' ? 'checked' : '' }}><span class="form-check-label">All</span></label>
                            @foreach ($assessmentPassingGrades as $grade)
                                <label class="form-check"><input class="form-check-input" type="radio" name="assessmentModalGrade" value="{{ $grade }}" {{ (string) $activeFilters['grade'] === (string) $grade ? 'checked' : '' }}><span class="form-check-label">{{ $grade }}</span></label>
                            @endforeach
                        </div>
                    </div>

                    <div class="assessment-filter-section">
                        <div class="assessment-filter-title">Assessment Score</div>
                        <div class="assessment-filter-options">
                            @foreach (['all' => 'All', 'none' => 'No Score', '0_59' => '0 - 59', '60_79' => '60 - 79', '80_100' => '80 - 100'] as $value => $label)
                                <label class="form-check"><input class="form-check-input" type="radio" name="assessmentModalScore" value="{{ $value }}" {{ $activeFilters['score'] === $value ? 'checked' : '' }}><span class="form-check-label">{{ $label }}</span></label>
                            @endforeach
                        </div>
                    </div>

                    <div class="assessment-filter-section">
                        <div class="assessment-filter-title">Assessment Result</div>
                        <div class="assessment-filter-options">
                            @foreach (['all' => 'All', 'passed' => 'Passed', 'failed' => 'Failed'] as $value => $label)
                                <label class="form-check"><input class="form-check-input" type="radio" name="assessmentModalResult" value="{{ $value }}" {{ $activeFilters['result'] === $value ? 'checked' : '' }}><span class="form-check-label">{{ $label }}</span></label>
                            @endforeach
                        </div>
                    </div>

                    <div class="assessment-filter-section">
                        <div class="assessment-filter-title">Date</div>
                        <div class="assessment-filter-options">
                            @foreach (['all' => 'All', 'newest' => 'Newest', 'oldest' => 'Oldest', 'this_month' => 'This Month', 'last_month' => 'Last Month', 'this_year' => 'This Year'] as $value => $label)
                                <label class="form-check"><input class="form-check-input" type="radio" name="assessmentModalDate" value="{{ $value }}" {{ $activeFilters['date'] === $value ? 'checked' : '' }}><span class="form-check-label">{{ $label }}</span></label>
                            @endforeach
                        </div>
                    </div>

                    <div class="assessment-filter-section">
                        <div class="assessment-filter-title">Availability</div>
                        <div class="assessment-filter-options">
                            @foreach (['all' => 'All', 'available' => 'Available', 'upcoming' => 'Upcoming', 'expired' => 'Expired'] as $value => $label)
                                <label class="form-check"><input class="form-check-input" type="radio" name="assessmentModalAvailability" value="{{ $value }}" {{ $activeFilters['availability'] === $value ? 'checked' : '' }}><span class="form-check-label">{{ $label }}</span></label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-primary btn-learning-outline" id="assessmentModalReset">
                        <i class="ti ti-refresh"></i><span>Reset</span>
                    </button>
                    <button type="button" class="btn btn-primary btn-learning-primary assessment-apply-filter">
                        <i class="ti ti-check"></i><span>Apply Filter</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
