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
                {{-- SECTION 1: Total Questions Used & Total Question Bank --}}
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="assessment-filter-group">
                            <label class="form-label fw-semibold mb-1" for="assessmentFilterQuestionUsed">
                                Total Questions Used
                            </label>
                            <select id="assessmentFilterQuestionUsed" name="question_used" class="form-select form-control rounded-3">
                                <option value="">All Questions</option>
                                @foreach ($questionUsedFilters as $value => $label)
                                    <option value="{{ $value }}" {{ request('question_used', request('questions')) == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="assessment-filter-group">
                            <label class="form-label fw-semibold mb-1" for="assessmentFilterQuestionBank">
                                Total Question Bank
                            </label>
                            <select id="assessmentFilterQuestionBank" name="question_bank" class="form-select form-control rounded-3">
                                <option value="">All Question Banks</option>
                                @foreach ($questionBankFilters as $val => $label)
                                    <option value="{{ $val }}" {{ request('question_bank') == $val ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <hr class="my-3">

                {{-- SECTION 2: Period --}}
                <div class="assessment-filter-group">
                    <div class="fw-semibold mb-2">Period</div>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label small text-muted mb-1" for="assessmentFilterPeriodFrom">From Date</label>
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="ti ti-calendar"></i></span>
                                <input type="date"
                                       id="assessmentFilterPeriodFrom"
                                       name="period_from"
                                       class="form-control rounded-3"
                                       value="{{ request('period_from', request('schedule_from')) }}">
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small text-muted mb-1" for="assessmentFilterPeriodTo">To Date</label>
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="ti ti-calendar"></i></span>
                                <input type="date"
                                       id="assessmentFilterPeriodTo"
                                       name="period_to"
                                       class="form-control rounded-3"
                                       value="{{ request('period_to', request('schedule_to')) }}">
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-3">

                {{-- SECTION 3: Duration --}}
                <div class="assessment-filter-group">
                    <label class="form-label fw-semibold mb-1" for="assessmentFilterDuration">
                        Duration
                    </label>
                    <select id="assessmentFilterDuration" name="duration" class="form-select form-control rounded-3">
                        <option value="">All Durations</option>
                        @foreach ($durationFilters as $val => $label)
                            <option value="{{ $val }}" {{ request('duration') == $val ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <hr class="my-3">

                {{-- SECTION 4: Sort By --}}
                <div class="assessment-filter-group">
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
