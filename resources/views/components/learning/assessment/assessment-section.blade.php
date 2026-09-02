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

                        <div class="dropdown assessment-filter-dropdown assessment-filter-dropdown-year"
                             data-assessment-filter="year" data-selected-value="{{ $activeFilters['year'] }}">
                            <button type="button" class="btn btn-filter-chip assessment-fast-filter-control dropdown-toggle {{ $activeFilters['year'] !== 'all' ? 'active' : '' }}"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
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
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
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
                        <div class="position-relative assessment-search-container flex-grow-1">
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                                <input type="text"
                                       class="form-control"
                                       id="assessment-search-input"
                                       name="assessment_search"
                                       placeholder="Search assessment..."
                                       value="{{ request('assessment_search') }}"
                                       autocomplete="off">
                            </div>
                            <div id="assessment-search-results" class="dropdown-menu shadow-sm w-100 overflow-auto position-absolute mt-1" style="display: none; max-height: 300px; z-index: 1050;"></div>
                        </div>
                    </div>

                    <div class="assessment-filter-actions">
                        <button type="button" class="btn btn-primary btn-learning-primary assessment-filter-action-btn"
                                id="assessmentApplyFilter" title="Apply Filter" aria-label="Apply Filter">
                            <i class="ti ti-search"></i>
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-learning-secondary assessment-filter-action-btn position-relative"
                                data-toggle="modal" data-target="#assessmentMoreFiltersModal"
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

    <style>
        #assessmentMoreFiltersModal .modal-dialog {
            max-width: 800px;
            width: 100%;
        }
        @media (max-width: 768px) {
            #assessmentMoreFiltersModal .modal-dialog {
                max-width: 90%;
            }
        }
        @media (max-width: 576px) {
            #assessmentMoreFiltersModal .modal-dialog {
                max-width: 100%;
                margin: 0.5rem;
            }
        }
        #assessmentMoreFiltersModal .modal-content {
            border-radius: 16px !important;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.15) !important;
            padding: 32px !important;
            animation: modalSlideUp 200ms ease-out forwards;
        }
        @keyframes modalSlideUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        #assessmentMoreFiltersModal .modal-close-btn {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            font-size: 1.2rem;
            outline: none;
            cursor: pointer;
        }
        #assessmentMoreFiltersModal .modal-close-btn:hover {
            background: #f1f5f9;
            color: #0f172a;
            border-color: #cbd5e1;
        }
        #assessmentMoreFiltersModal .filter-section-title {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 12px;
        }
        #assessmentMoreFiltersModal .filter-divider {
            border-bottom: 1px solid #E9ECEF;
            padding-bottom: 24px;
            margin-bottom: 32px;
        }
        #assessmentMoreFiltersModal .custom-form-control {
            height: 48px;
            border-radius: 10px;
            border: 1px solid #dfe3e8;
            padding-left: 16px;
            padding-right: 16px;
            font-size: 14px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        #assessmentMoreFiltersModal .custom-form-control:focus {
            border-color: #206bc4;
            box-shadow: 0 0 0 3px rgba(32, 107, 196, 0.15);
        }
        #assessmentMoreFiltersModal .radio-gap {
            gap: 36px;
        }
        #assessmentMoreFiltersModal .modal-footer-custom {
            padding-top: 28px;
            border-top: 1px solid #E9ECEF;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        #assessmentMoreFiltersModal .modal-footer-custom .btn {
            height: 48px;
            border-radius: 10px;
            padding-left: 28px;
            padding-right: 28px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
    </style>

    <div class="modal fade" id="assessmentMoreFiltersModal" tabindex="-1" role="dialog"
         aria-labelledby="assessmentMoreFiltersTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-start pb-3 mb-3">
                    <div>
                        <h2 class="modal-title fw-bold text-dark mb-1" id="assessmentMoreFiltersTitle" style="font-size: 26px;">More Filters</h2>
                        <div class="text-muted" style="font-size: 14px;">Advanced Filter</div>
                    </div>
                    <button type="button" class="modal-close-btn" data-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="modal-body p-0">
                    <!-- SECTION 1: ASSESSMENT SCORE -->
                    <div class="filter-divider">
                        <div class="filter-section-title">
                            Assessment Score
                        </div>
                        <div class="d-flex flex-wrap align-items-center radio-gap">
                            @foreach (['all' => 'All', '0_59' => '< 60', '60_79' => '60 – 79', '80_100' => '80 – 100'] as $value => $label)
                                <label class="form-check form-check-inline mb-0 cursor-pointer">
                                    <input class="form-check-input" type="radio" name="assessmentModalScore" value="{{ $value }}" {{ $activeFilters['score'] === $value ? 'checked' : '' }}>
                                    <span class="form-check-label text-dark fw-medium fs-4 ms-1">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- SECTION 2: BATCH -->
                    <div class="filter-divider">
                        <div class="filter-section-title">
                            Batch
                        </div>
                        <select class="form-select custom-form-control w-100" name="assessmentModalBatch" id="assessmentModalBatch">
                            <option value="all" {{ $activeFilters['batch'] === 'all' ? 'selected' : '' }}>All</option>
                            @foreach ($assessmentBatches as $batch)
                                <option value="{{ $batch }}" {{ (string) $activeFilters['batch'] === (string) $batch ? 'selected' : '' }}>Batch {{ $batch }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- SECTION 3: SORT BY -->
                    <div class="filter-divider">
                        <div class="filter-section-title">
                            Sort By
                        </div>
                        <select class="form-select custom-form-control w-100" name="assessmentModalDate" id="assessmentModalDate">
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

                    <!-- SECTION 4: PERIOD -->
                    <div class="mb-4">
                        <div class="filter-section-title">
                            Period
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6 mb-2 mb-md-0">
                                <label class="form-label text-muted small mb-2 fw-medium" for="assessmentFilterFromDate" style="font-size: 13px;">From Date</label>
                                <input type="date" class="form-control custom-form-control" id="assessmentFilterFromDate" name="assessmentFilterFromDate" value="{{ request('assessment_filter_from_date') }}">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label text-muted small mb-2 fw-medium" for="assessmentFilterToDate" style="font-size: 13px;">To Date</label>
                                <input type="date" class="form-control custom-form-control" id="assessmentFilterToDate" name="assessmentFilterToDate" value="{{ request('assessment_filter_to_date') }}">
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
                <div class="modal-footer-custom">
                    <button type="button" class="btn btn-outline-primary btn-learning-outline" id="assessmentModalReset">
                        <i class="ti ti-refresh me-2"></i><span>Reset</span>
                    </button>
                    <button type="button" class="btn btn-primary btn-learning-primary assessment-apply-filter">
                        <i class="ti ti-search me-2"></i><span>Apply Filter</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <x-shared.assessment-confirmation-modal />
    <x-shared.assessment-continue-modal />
</div>
