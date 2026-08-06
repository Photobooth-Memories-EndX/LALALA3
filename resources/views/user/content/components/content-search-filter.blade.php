@php
    $filterDuration = request('duration', 'all');
    $filterLearningHour = request('learning_hour', 'all');
    $filterTotalPages = array_values(array_filter((array) request('total_pages', [])));
    $selectedMonth = request('month', '');
    $selectedYear = request('year', '');

    $durationOptions = [
        'lt10'  => '< 10 Menit',
        '10-30' => '10 - 30 Menit',
        '30-60' => '30 - 60 Menit',
        'gt60'  => '> 1 Jam',
    ];

    $learningHourOptions = [
        'lt1h' => '< 1 Hour',
        '1-3h' => '1 - 3 hours',
        'gt3h' => '> 3 Hours',
    ];

    $modalFilterCount = 0;
    $modalFilterCount += filled($filterAuthor) ? 1 : 0;
    $modalFilterCount += filled($filterCreatedFromDate) ? 1 : 0;
    $modalFilterCount += filled($filterCreatedToDate) ? 1 : 0;
    $modalFilterCount += filled($filterDeadlineFromDate) ? 1 : 0;
    $modalFilterCount += filled($filterDeadlineToDate) ? 1 : 0;
    $modalFilterCount += $filterDeadlineStatus !== 'all' ? 1 : 0;
    $modalFilterCount += ($filterSort && $filterSort !== 'all' && $filterSort !== 'newest_course') ? 1 : 0;
    $modalFilterCount += filled($filterViewOrder) ? 1 : 0;
    $modalFilterCount += $filterDuration !== 'all' ? 1 : 0;
    $modalFilterCount += $filterLearningHour !== 'all' ? 1 : 0;

    $activeFilterCount = $modalFilterCount;
    $activeFilterCount += filled($filterSearch) ? 1 : 0;
    $activeFilterCount += filled($filterClassification) ? 1 : 0;
    $activeFilterCount += filled($filterType) ? 1 : 0;
    $activeFilterCount += filled($selectedMonth) ? 1 : 0;
    $activeFilterCount += filled($selectedYear) ? 1 : 0;

    $monthOptions = collect(range(1, 12))->mapWithKeys(function ($month) {
        $value = str_pad((string) $month, 2, '0', STR_PAD_LEFT);
        return [$value => \Carbon\Carbon::create(null, $month, 1)->format('F')];
    });

    $yearNow = (int) \Carbon\Carbon::now()->format('Y');
    $yearOptions = range($yearNow, $yearNow - 5);
@endphp

<div class="card border-0 shadow-sm rounded-4 mb-4 learning-filter-card content-filter-card">
    <div class="card-body p-3 p-lg-4">
        <form method="GET" action="{{ route('content.user.index') }}" id="contentFilterForm"
              data-default-year="{{ $yearNow }}">
            @include('user.content.components.content-filter-toolbar')

            <div class="modal fade" id="contentMoreFiltersModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered learning-filter-modal">
                    <div class="modal-content rounded-4 border-0 shadow">
                        <div class="modal-header border-0 pb-0">
                            <h3 class="modal-title fw-bold">
                                <i class="ti ti-adjustments-horizontal me-1 text-primary"></i>
                                More Filters
                            </h3>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body pt-2">
                            {{-- Author --}}
                            <div class="learning-filter-group">
                                <div class="fw-semibold mb-2">Author</div>
                                <select name="author" id="contentFilterAuthor" class="form-select rounded-3">
                                    <option value="">All Authors</option>
                                    @foreach ($authors as $author)
                                        <option value="{{ $author }}" {{ $filterAuthor == $author ? 'selected' : '' }}>
                                            {{ $author }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <hr class="my-3">

                            {{-- Created Date --}}
                            <div class="learning-filter-group">
                                <div class="fw-semibold mb-2">Created Date</div>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small text-muted mb-1" for="contentFilterCreatedFromDate">From Date</label>
                                        <input type="{{ $filterCreatedFromDate ? 'date' : 'text' }}"
                                               name="created_from_date"
                                               id="contentFilterCreatedFromDate"
                                               value="{{ $filterCreatedFromDate }}"
                                               class="form-control rounded-3"
                                               placeholder="dd/mm/yyyy"
                                               onfocus="(this.type='date')"
                                               onblur="if(!this.value)this.type='text'">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small text-muted mb-1" for="contentFilterCreatedToDate">To Date</label>
                                        <input type="{{ $filterCreatedToDate ? 'date' : 'text' }}"
                                               name="created_to_date"
                                               id="contentFilterCreatedToDate"
                                               value="{{ $filterCreatedToDate }}"
                                               class="form-control rounded-3"
                                               placeholder="dd/mm/yyyy"
                                               onfocus="(this.type='date')"
                                               onblur="if(!this.value)this.type='text'">
                                    </div>
                                </div>
                            </div>

                            <hr class="my-3">

                            {{-- Period --}}
                            <div class="learning-filter-group">
                                <div class="fw-semibold mb-2">Period</div>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small text-muted mb-1" for="contentFilterDeadlineFromDate">From Date</label>
                                        <input type="{{ $filterDeadlineFromDate ? 'date' : 'text' }}"
                                               name="deadline_from_date"
                                               id="contentFilterDeadlineFromDate"
                                               value="{{ $filterDeadlineFromDate }}"
                                               class="form-control rounded-3"
                                               placeholder="dd/mm/yyyy"
                                               onfocus="(this.type='date')"
                                               onblur="if(!this.value)this.type='text'">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small text-muted mb-1" for="contentFilterDeadlineToDate">To Date</label>
                                        <input type="{{ $filterDeadlineToDate ? 'date' : 'text' }}"
                                               name="deadline_to_date"
                                               id="contentFilterDeadlineToDate"
                                               value="{{ $filterDeadlineToDate }}"
                                               class="form-control rounded-3"
                                               placeholder="dd/mm/yyyy"
                                               onfocus="(this.type='date')"
                                               onblur="if(!this.value)this.type='text'">
                                    </div>
                                </div>
                            </div>

                            <hr class="my-3">

                            {{-- Deadline & Sort by --}}
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold" for="contentFilterDeadlineStatus">Deadline</label>
                                    <select name="deadline_status" id="contentFilterDeadlineStatus" class="form-select rounded-3">
                                        <option value="all" {{ $filterDeadlineStatus === 'all' ? 'selected' : '' }}>All</option>
                                        <option value="expired" {{ $filterDeadlineStatus === 'expired' ? 'selected' : '' }}>Expired</option>
                                        <option value="today" {{ $filterDeadlineStatus === 'today' ? 'selected' : '' }}>Today</option>
                                        <option value="less_than_7_days" {{ $filterDeadlineStatus === 'less_than_7_days' ? 'selected' : '' }}>Less than 7 Days</option>
                                        <option value="less_than_30_days" {{ $filterDeadlineStatus === 'less_than_30_days' ? 'selected' : '' }}>Less than 30 Days</option>
                                        <option value="more_than_30_days" {{ $filterDeadlineStatus === 'more_than_30_days' ? 'selected' : '' }}>More than 30 Days</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold" for="contentFilterSort">Sort by</label>
                                    <select name="sort" id="contentFilterSort" class="form-select rounded-3">
                                        <option value="all" {{ ($filterSort === 'all' || blank($filterSort)) ? 'selected' : '' }}>All</option>
                                        <option value="oldest" {{ ($filterSort === 'oldest' || $filterSort === 'oldest_course') ? 'selected' : '' }}>Oldest</option>
                                        <option value="newest" {{ ($filterSort === 'newest' || $filterSort === 'newest_course') ? 'selected' : '' }}>Newest</option>
                                        <option value="asc" {{ $filterSort === 'asc' ? 'selected' : '' }}>Ascending</option>
                                        <option value="desc" {{ $filterSort === 'desc' ? 'selected' : '' }}>Descending</option>
                                    </select>
                                </div>
                            </div>

                            <hr class="my-3">

                            {{-- Sort Views --}}
                            <div class="learning-filter-group">
                                <div class="fw-semibold mb-2">Sort Views</div>
                                <div class="d-flex flex-wrap gap-3">
                                    <label class="form-check">
                                        <input class="form-check-input" type="radio"
                                               name="view_order" value=""
                                               {{ blank($filterViewOrder) ? 'checked' : '' }}>
                                        <span class="form-check-label">Default</span>
                                    </label>
                                    <label class="form-check">
                                        <input class="form-check-input" type="radio"
                                               name="view_order" value="most_viewed"
                                               {{ $filterViewOrder === 'most_viewed' ? 'checked' : '' }}>
                                        <span class="form-check-label">Most Viewed</span>
                                    </label>
                                    <label class="form-check">
                                        <input class="form-check-input" type="radio"
                                               name="view_order" value="least_viewed"
                                               {{ $filterViewOrder === 'least_viewed' ? 'checked' : '' }}>
                                        <span class="form-check-label">Least Viewed</span>
                                    </label>
                                </div>
                            </div>

                            <hr class="my-3">

                            {{-- Duration --}}
                            <div class="learning-filter-group">
                                <div class="fw-semibold mb-2">Duration</div>
                                <div class="d-flex flex-wrap gap-3">
                                    <label class="form-check">
                                        <input class="form-check-input" type="radio"
                                               name="duration" value="all"
                                               {{ $filterDuration === 'all' ? 'checked' : '' }}>
                                        <span class="form-check-label">All Durations</span>
                                    </label>
                                    @foreach ($durationOptions as $value => $label)
                                        <label class="form-check">
                                            <input class="form-check-input" type="radio"
                                                   name="duration" value="{{ $value }}"
                                                   {{ $filterDuration === $value ? 'checked' : '' }}>
                                            <span class="form-check-label">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <hr class="my-3">

                            {{-- LEARNING HOUR --}}
                            <div class="learning-filter-group">
                                <div class="fw-semibold mb-2">LEARNING HOUR</div>
                                <div class="d-flex flex-wrap gap-3">
                                    <label class="form-check">
                                        <input class="form-check-input" type="radio"
                                               name="learning_hour" value="all"
                                               {{ $filterLearningHour === 'all' ? 'checked' : '' }}>
                                        <span class="form-check-label">All</span>
                                    </label>
                                    @foreach ($learningHourOptions as $value => $label)
                                        <label class="form-check">
                                            <input class="form-check-input" type="radio"
                                                   name="learning_hour" value="{{ $value }}"
                                                   {{ $filterLearningHour === $value ? 'checked' : '' }}>
                                            <span class="form-check-label">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer border-0 pt-0">
                            <a href="{{ route('content.user.index') }}"
                               class="btn content-reset-btn rounded-3 {{ $activeFilterCount > 0 ? 'is-active' : '' }}">
                                <i class="ti ti-refresh me-1"></i>
                                Reset
                            </a>
                            <button type="submit" class="btn btn-primary rounded-3">
                                <i class="ti ti-check me-1"></i>
                                Apply Filter
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
