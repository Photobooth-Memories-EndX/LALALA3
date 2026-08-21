<div class="modal fade" id="assessmentMoreFiltersModal" tabindex="-1" aria-labelledby="assessmentMoreFiltersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered assessment-filter-modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h3 class="modal-title fw-bold" id="assessmentMoreFiltersModalLabel">
                    <i class="ti ti-adjustments-horizontal me-1 text-primary"></i>
                    More Filters
                </h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-3 pb-2">
                <p class="text-muted small mb-3">
                    Refine your assessment list with advanced filters.
                </p>

                {{-- ROW 1: Questions & Passing Grade --}}
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <div class="assessment-filter-group">
                            <label class="form-label fw-semibold mb-1" for="assessmentFilterQuestions">
                                Questions
                            </label>
                            <select id="assessmentFilterQuestions" name="questions" class="form-select form-control rounded-3">
                                <option value="">All Questions</option>
                                @foreach ($questionFilters as $value => $label)
                                    <option value="{{ $value }}" {{ request('questions') == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="assessment-filter-group">
                            <label class="form-label fw-semibold mb-1" for="assessmentFilterPassingGrade">
                                Passing Grade
                            </label>
                            <select id="assessmentFilterPassingGrade" name="passing_grade" class="form-select form-control rounded-3">
                                <option value="">All Grades</option>
                                @foreach ($passingGradeOptions as $val => $label)
                                    <option value="{{ $val }}" {{ request('passing_grade') == $val ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <hr class="my-3">

                {{-- ROW 2: Created Date --}}
                <div class="assessment-filter-group mb-3">
                    <div class="fw-semibold mb-2">Created Date</div>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label small text-muted mb-1" for="assessmentFilterCreatedFrom">From Date</label>
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="ti ti-calendar"></i></span>
                                <input type="date"
                                       id="assessmentFilterCreatedFrom"
                                       name="created_from"
                                       class="form-control rounded-3"
                                       value="{{ request('created_from') }}">
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small text-muted mb-1" for="assessmentFilterCreatedTo">To Date</label>
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="ti ti-calendar"></i></span>
                                <input type="date"
                                       id="assessmentFilterCreatedTo"
                                       name="created_to"
                                       class="form-control rounded-3"
                                       value="{{ request('created_to') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-3">

                {{-- ROW 3: Schedule / Access Period --}}
                <div class="assessment-filter-group mb-3">
                    <div class="fw-semibold mb-2">Schedule / Access Period</div>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label small text-muted mb-1" for="assessmentFilterScheduleFrom">From Date</label>
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="ti ti-calendar"></i></span>
                                <input type="date"
                                       id="assessmentFilterScheduleFrom"
                                       name="schedule_from"
                                       class="form-control rounded-3"
                                       value="{{ request('schedule_from') }}">
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small text-muted mb-1" for="assessmentFilterScheduleTo">To Date</label>
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="ti ti-calendar"></i></span>
                                <input type="date"
                                       id="assessmentFilterScheduleTo"
                                       name="schedule_to"
                                       class="form-control rounded-3"
                                       value="{{ request('schedule_to') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-3">

                {{-- ROW 4: Sort By --}}
                <div class="assessment-filter-group mb-3">
                    <label class="form-label fw-semibold mb-1" for="assessmentFilterSort">
                        Sort By
                    </label>
                    <select id="assessmentFilterSort" name="sort" class="form-select form-control rounded-3">
                        @foreach ($sortOptions as $val => $label)
                            <option value="{{ $val }}" {{ request('sort', 'newest') == $val ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <hr class="my-3">

                {{-- ROW 5: Entries per Page --}}
                <div class="assessment-filter-group">
                    <label class="form-label fw-semibold mb-1" for="assessmentFilterEntries">
                        Entries per Page
                    </label>
                    <select id="assessmentFilterEntries" name="entries" class="form-select form-control rounded-3">
                        <option value="10" {{ request('entries', 15) == 10 ? 'selected' : '' }}>10</option>
                        <option value="15" {{ request('entries', 15) == 15 ? 'selected' : '' }}>15</option>
                        <option value="20" {{ request('entries', 15) == 20 ? 'selected' : '' }}>20</option>
                        <option value="25" {{ request('entries', 15) == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ request('entries', 15) == 50 ? 'selected' : '' }}>50</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary rounded-3" id="assessmentResetMoreFilters">
                    <i class="ti ti-refresh me-1"></i>
                    Reset
                </button>
                <button type="submit" class="btn btn-primary rounded-3" id="assessmentApplyMoreFilters">
                    <i class="ti ti-check me-1"></i>
                    Apply Filter
                </button>
            </div>
        </div>
    </div>
</div>
