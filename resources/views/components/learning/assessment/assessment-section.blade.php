@props([
    'assessments',
])

@php
    $assessmentItems = collect($assessments ?? []);
    $assessmentYears = $assessmentItems
        ->map(function ($assessment) {
            $date = $assessment->fromdate ?? null;
            $timestamp = $date ? strtotime((string) $date) : false;

            return $timestamp ? (int) date('Y', $timestamp) : null;
        })
        ->filter()
        ->unique()
        ->sortDesc()
        ->values();
    $assessmentBatches = $assessmentItems->pluck('batch')->filter(fn ($value) => $value !== '')->unique()->sort()->values();
    $assessmentPassingGrades = $assessmentItems->pluck('passing_grade')->filter(fn ($value) => $value !== '')->unique()->sort()->values();

    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];

    $quickFilters = [
        'type' => ['all' => 'All', 'content' => 'Content', 'playlist' => 'Playlist', 'global' => 'Global'],
        'status' => ['all' => 'All', 'not_started' => 'Not Started', 'passed' => 'Passed', 'failed' => 'Failed'],
    ];
@endphp

<div class="card border-0 shadow-sm rounded-4 mb-3 overflow-hidden">
    <div class="card-body p-4 border-bottom">
        <x-learning.assessment.assessment-header />

        <div class="mt-3 record-filter-shell assessment-filter-shell" id="assessmentFilterBar">
            <div class="record-search-row">
                <label class="form-label small text-muted mb-1" for="assessmentSearch">Search</label>
                <div class="input-icon">
                    <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                    <input type="search"
                           class="form-control"
                           id="assessmentSearch"
                           placeholder="Search assessment..."
                           autocomplete="off">
                </div>
            </div>

            <div class="learning-record-filter-row mb-0">
                <div class="learning-record-fast-filter">
                    <label class="form-label small text-muted mb-2">Fast Filter</label>

                    <div class="d-flex flex-wrap gap-2 learning-quick-dropdowns assessment-quick-dropdowns">
                        @foreach ($quickFilters as $filter => $options)
                            <div class="dropdown" data-assessment-filter="{{ $filter }}">
                                <button type="button" class="btn btn-outline-secondary rounded-3 dropdown-toggle"
                                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    {{ ucfirst($filter) }}: <span class="fw-semibold">All</span>
                                </button>
                                <div class="dropdown-menu">
                                    @foreach ($options as $value => $label)
                                        <a href="#" class="dropdown-item assessment-quick-item {{ $value === 'all' ? 'active' : '' }}"
                                           data-value="{{ $value }}">{{ $label }}</a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        <div class="dropdown" data-assessment-filter="year">
                            <button type="button" class="btn btn-outline-secondary rounded-3 dropdown-toggle"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Year: <span class="fw-semibold">All</span>
                            </button>
                            <div class="dropdown-menu learning-quick-dropdown-scroll">
                                <a href="#" class="dropdown-item assessment-quick-item active" data-value="all">All</a>
                                @foreach ($assessmentYears as $year)
                                    <a href="#" class="dropdown-item assessment-quick-item" data-value="{{ $year }}">{{ $year }}</a>
                                @endforeach
                            </div>
                        </div>

                        <div class="dropdown" data-assessment-filter="month">
                            <button type="button" class="btn btn-outline-secondary rounded-3 dropdown-toggle"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Month: <span class="fw-semibold">All</span>
                            </button>
                            <div class="dropdown-menu learning-quick-dropdown-scroll">
                                <a href="#" class="dropdown-item assessment-quick-item active" data-value="all">All</a>
                                @foreach ($months as $number => $month)
                                    <a href="#" class="dropdown-item assessment-quick-item" data-value="{{ $number }}">{{ $month }}</a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="record-filter-actions assessment-filter-actions">
                    <button type="button" class="btn btn-outline-success record-filter-action-btn" id="assessmentDownloadExcel">
                        <i class="ti ti-file-spreadsheet"></i><span>Download Excel</span>
                    </button>
                    <button type="button" class="btn btn-outline-primary record-filter-action-btn"
                            data-toggle="modal" data-target="#assessmentMoreFiltersModal">
                        <i class="ti ti-adjustments-horizontal"></i><span>More Filters</span>
                        <span class="badge bg-primary text-white d-none" id="assessmentMoreFiltersCount"></span>
                    </button>
                    <button type="button" class="btn btn-primary record-filter-action-btn" id="assessmentApplyFilter">
                        <i class="ti ti-check"></i><span>Apply Filter</span>
                    </button>
                    <button type="button" class="btn btn-outline-primary record-filter-action-btn" id="assessmentResetFilter">
                        Reset
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
                            <label class="form-check"><input class="form-check-input" type="radio" name="assessmentModalBatch" value="all" checked><span class="form-check-label">All</span></label>
                            @foreach ($assessmentBatches as $batch)
                                <label class="form-check"><input class="form-check-input" type="radio" name="assessmentModalBatch" value="{{ $batch }}"><span class="form-check-label">{{ $batch }}</span></label>
                            @endforeach
                        </div>
                    </div>

                    <div class="assessment-filter-section">
                        <div class="assessment-filter-title">Passing Grade</div>
                        <div class="assessment-filter-options">
                            <label class="form-check"><input class="form-check-input" type="radio" name="assessmentModalGrade" value="all" checked><span class="form-check-label">All</span></label>
                            @foreach ($assessmentPassingGrades as $grade)
                                <label class="form-check"><input class="form-check-input" type="radio" name="assessmentModalGrade" value="{{ $grade }}"><span class="form-check-label">{{ $grade }}</span></label>
                            @endforeach
                        </div>
                    </div>

                    <div class="assessment-filter-section">
                        <div class="assessment-filter-title">Assessment Score</div>
                        <div class="assessment-filter-options">
                            @foreach (['all' => 'All', 'none' => 'No Score', '0_59' => '0 - 59', '60_79' => '60 - 79', '80_100' => '80 - 100'] as $value => $label)
                                <label class="form-check"><input class="form-check-input" type="radio" name="assessmentModalScore" value="{{ $value }}" {{ $value === 'all' ? 'checked' : '' }}><span class="form-check-label">{{ $label }}</span></label>
                            @endforeach
                        </div>
                    </div>

                    <div class="assessment-filter-section">
                        <div class="assessment-filter-title">Assessment Result</div>
                        <div class="assessment-filter-options">
                            @foreach (['all' => 'All', 'passed' => 'Passed', 'failed' => 'Failed'] as $value => $label)
                                <label class="form-check"><input class="form-check-input" type="radio" name="assessmentModalResult" value="{{ $value }}" {{ $value === 'all' ? 'checked' : '' }}><span class="form-check-label">{{ $label }}</span></label>
                            @endforeach
                        </div>
                    </div>

                    <div class="assessment-filter-section">
                        <div class="assessment-filter-title">Date</div>
                        <div class="assessment-filter-options">
                            @foreach (['newest' => 'Newest', 'oldest' => 'Oldest', 'this_month' => 'This Month', 'last_month' => 'Last Month', 'this_year' => 'This Year'] as $value => $label)
                                <label class="form-check"><input class="form-check-input" type="radio" name="assessmentModalDate" value="{{ $value }}"><span class="form-check-label">{{ $label }}</span></label>
                            @endforeach
                        </div>
                    </div>

                    <div class="assessment-filter-section">
                        <div class="assessment-filter-title">Availability</div>
                        <div class="assessment-filter-options">
                            @foreach (['all' => 'All', 'available' => 'Available', 'upcoming' => 'Upcoming', 'expired' => 'Expired'] as $value => $label)
                                <label class="form-check"><input class="form-check-input" type="radio" name="assessmentModalAvailability" value="{{ $value }}" {{ $value === 'all' ? 'checked' : '' }}><span class="form-check-label">{{ $label }}</span></label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-primary" id="assessmentModalReset">Reset</button>
                    <button type="button" class="btn btn-primary assessment-apply-filter">Apply Filter</button>
                </div>
            </div>
        </div>
    </div>
</div>
