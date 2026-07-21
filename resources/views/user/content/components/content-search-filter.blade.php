@php
    $filterDuration = request('duration', 'all');
    $filterTotalPages = array_values(array_filter((array) request('total_pages', [])));
    $selectedMonth = request('month', '');
    $selectedYear = request('year', '');

    $durationOptions = [
        'lt10'  => '< 10 Menit',
        '10-30' => '10 - 30 Menit',
        '30-60' => '30 - 60 Menit',
        'gt60'  => '> 1 Jam',
    ];

    $totalPageOptions = [
        'lt10'  => '< 10',
        '10-20' => '10 - 20',
        '21-50' => '21 - 50',
        'gt50'  => '> 50',
    ];

    $modalFilterCount = 0;
    $modalFilterCount += filled($filterAuthor) ? 1 : 0;
    $modalFilterCount += filled($filterDeadlineFromDate) ? 1 : 0;
    $modalFilterCount += filled($filterDeadlineToDate) ? 1 : 0;
    $modalFilterCount += filled($filterCreatedFromDate) ? 1 : 0;
    $modalFilterCount += filled($filterCreatedToDate) ? 1 : 0;
    $modalFilterCount += filled($filterUpdatedFromDate) ? 1 : 0;
    $modalFilterCount += filled($filterUpdatedToDate) ? 1 : 0;
    $modalFilterCount += $filterDeadlineStatus !== 'all' ? 1 : 0;
    $modalFilterCount += $filterSort !== 'newest_course' ? 1 : 0;
    $modalFilterCount += filled($filterViewOrder) ? 1 : 0;
    $modalFilterCount += $filterDuration !== 'all' ? 1 : 0;
    $modalFilterCount += count($filterTotalPages);

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
        <form method="GET" action="{{ route('content.user.index') }}" id="contentFilterForm">
            <div class="content-filter-top">
                <div class="content-filter-heading">
                    <h3 class="card-title mb-1">All Content</h3>
                    <div class="text-muted small">
                        Find learning materials by search, type, and category.
                    </div>
                </div>

                <div class="content-filter-search">
                    <div class="input-icon flex-grow-1">
                        <span class="input-icon-addon">
                            <i class="ti ti-search"></i>
                        </span>
                        <input type="text"
                               name="s"
                               id="contentSearch"
                               value="{{ $filterSearch }}"
                               class="form-control rounded-3"
                               placeholder="Search Content">
                    </div>

                    <button type="submit" class="btn btn-primary rounded-3 content-filter-icon-btn" title="Search">
                        <i class="ti ti-search"></i>
                    </button>

                    <button type="button"
                            class="btn btn-outline-primary rounded-3 content-filter-icon-btn position-relative"
                            data-bs-toggle="modal"
                            data-bs-target="#contentMoreFiltersModal"
                            title="More filters">
                        <i class="ti ti-adjustments-horizontal"></i>
                        @if ($modalFilterCount > 0)
                            <span class="badge bg-primary text-white content-filter-count">{{ $modalFilterCount }}</span>
                        @endif
                    </button>

                    <a href="{{ route('content.user.index') }}"
                       class="btn content-reset-btn rounded-3 content-filter-reset-btn {{ $activeFilterCount > 0 ? 'is-active' : '' }}"
                       title="Reset filters">
                        <i class="ti ti-refresh"></i>
                        <span>Reset</span>
                    </a>
                </div>
            </div>

            <div class="content-filter-inline-row">
                <div class="content-filter-field content-filter-field-category">
                    <label class="form-label small text-muted mb-1" for="contentFilterClassification">Category / Classification</label>
                    <select name="classification" id="contentFilterClassification" class="form-select rounded-3 content-auto-submit">
                        <option value="">All Categories</option>
                        @foreach ($classifications as $classification)
                            <option value="{{ $classification->id }}" {{ $filterClassification == $classification->id ? 'selected' : '' }}>
                                {{ $classification->classification_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="content-filter-field content-filter-field-type">
                    <label class="form-label small text-muted mb-1" for="contentFilterType">Type</label>
                    <select name="type" id="contentFilterType" class="form-select rounded-3 content-auto-submit">
                        <option value="">All Types</option>
                        <option value="Document" {{ $filterType == 'Document' ? 'selected' : '' }}>Document</option>
                        <option value="Video" {{ $filterType == 'Video' ? 'selected' : '' }}>Video</option>
                        <option value="YouTube" {{ $filterType == 'YouTube' ? 'selected' : '' }}>YouTube</option>
                    </select>
                </div>

                <div class="content-filter-field content-filter-field-month">
                    <label class="form-label small text-muted mb-1" for="contentFilterMonth">Created Month</label>
                    <select name="month" id="contentFilterMonth" class="form-select rounded-3">
                        <option value="">All Months</option>
                        @foreach ($monthOptions as $value => $label)
                            <option value="{{ $value }}" {{ $selectedMonth == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="content-filter-field content-filter-field-year">
                    <label class="form-label small text-muted mb-1" for="contentFilterYear">Created Year</label>
                    <select name="year" id="contentFilterYear" class="form-select rounded-3">
                        <option value="">All Years</option>
                        @foreach ($yearOptions as $year)
                            <option value="{{ $year }}" {{ (string) $selectedYear === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

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
                            {{-- <p class="text-muted small mb-4">
                                Semua filter di bawah hanya menggunakan data Content yang relevan.
                            </p> --}}

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

                            <div class="learning-filter-group">
                                <div class="fw-semibold mb-2">Deadline Date</div>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small text-muted mb-1" for="contentFilterDeadlineFromDate">From Date</label>
                                        <input type="date"
                                               name="deadline_from_date"
                                               id="contentFilterDeadlineFromDate"
                                               value="{{ $filterDeadlineFromDate }}"
                                               class="form-control rounded-3">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small text-muted mb-1" for="contentFilterDeadlineToDate">To Date</label>
                                        <input type="date"
                                               name="deadline_to_date"
                                               id="contentFilterDeadlineToDate"
                                               value="{{ $filterDeadlineToDate }}"
                                               class="form-control rounded-3">
                                    </div>
                                </div>
                            </div>

                            <hr class="my-3">

                            <div class="learning-filter-group">
                                <div class="fw-semibold mb-2">Created Date</div>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small text-muted mb-1" for="contentFilterCreatedFromDate">From Date</label>
                                        <input type="date"
                                               name="created_from_date"
                                               id="contentFilterCreatedFromDate"
                                               value="{{ $filterCreatedFromDate }}"
                                               class="form-control rounded-3">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small text-muted mb-1" for="contentFilterCreatedToDate">To Date</label>
                                        <input type="date"
                                               name="created_to_date"
                                               id="contentFilterCreatedToDate"
                                               value="{{ $filterCreatedToDate }}"
                                               class="form-control rounded-3">
                                    </div>
                                </div>
                            </div>

                            <hr class="my-3">

                            {{-- <div class="learning-filter-group">
                                <div class="fw-semibold mb-2">Updated Date</div>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small text-muted mb-1" for="contentFilterUpdatedFromDate">From Date</label>
                                        <input type="date"
                                               name="updated_from_date"
                                               id="contentFilterUpdatedFromDate"
                                               value="{{ $filterUpdatedFromDate }}"
                                               class="form-control rounded-3">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small text-muted mb-1" for="contentFilterUpdatedToDate">To Date</label>
                                        <input type="date"
                                               name="updated_to_date"
                                               id="contentFilterUpdatedToDate"
                                               value="{{ $filterUpdatedToDate }}"
                                               class="form-control rounded-3">
                                    </div>
                                </div>
                            </div> --}}

                            <hr class="my-3">

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
                                    <label class="form-label fw-semibold" for="contentFilterSort">Quick Filter</label>
                                    <select name="sort" id="contentFilterSort" class="form-select rounded-3">
                                        <option value="newest_course" {{ $filterSort === 'newest_course' ? 'selected' : '' }}>Newest Course</option>
                                        <option value="oldest_course" {{ $filterSort === 'oldest_course' ? 'selected' : '' }}>Oldest Course</option>
                                        <option value="recently_updated" {{ $filterSort === 'recently_updated' ? 'selected' : '' }}>Recently Updated</option>
                                        <option value="old_update" {{ $filterSort === 'old_update' ? 'selected' : '' }}>Old Update</option>
                                    </select>
                                </div>
                            </div>

                            <hr class="my-3">

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
                                <div class="form-hint mt-2">
                                    {{-- Mendukung format 04:38, 30:00, 60:00, dan 01:25:30. --}}
                                </div>
                            </div>

                            <hr class="my-3">

                            <div class="learning-filter-group">
                                <div class="fw-semibold mb-2">Total Pages</div>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach ($totalPageOptions as $value => $label)
                                        <label class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="total_pages[]" value="{{ $value }}"
                                                   {{ in_array($value, $filterTotalPages, true) ? 'checked' : '' }}>
                                            <span class="form-check-label">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <div class="form-hint mt-2">
                                    {{-- UI disiapkan tanpa mengubah struktur database atau query existing. --}}
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

<x-learning.ongoing-filter-style />

<style>
    .content-filter-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 14px;
    }

    .content-filter-heading {
        min-width: 0;
        padding-top: 2px;
    }

    .content-filter-inline-row {
        display: grid;
        grid-template-columns: minmax(240px, 1.45fr) minmax(160px, .95fr) minmax(160px, .95fr) minmax(130px, .75fr);
        gap: 10px;
        align-items: end;
    }

    .content-filter-field {
        min-width: 0;
    }

    .content-filter-search {
        display: flex;
        align-items: center;
        gap: 8px;
        width: min(100%, 620px);
        flex: 0 1 620px;
    }

    .content-filter-card .form-control,
    .content-filter-card .form-select {
        min-height: 42px;
    }

    .content-filter-icon-btn {
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }

    .content-filter-reset-btn {
        min-height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        flex: 0 0 auto;
        padding: 0 14px;
        white-space: nowrap;
    }

    .content-filter-count {
        position: absolute;
        top: -7px;
        right: -7px;
        min-width: 18px;
        height: 18px;
        padding: 2px 5px;
        border-radius: 999px;
        font-size: 10px;
        line-height: 14px;
    }

    .content-filter-inline-row .form-label {
        white-space: nowrap;
    }

    @media (max-width: 1199.98px) {
        .content-filter-inline-row {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 991.98px) {
        .content-filter-top {
            align-items: stretch;
            flex-direction: column;
        }

        .content-filter-search {
            width: 100%;
            flex-basis: auto;
        }
    }

    @media (max-width: 767.98px) {
        .content-filter-inline-row {
            grid-template-columns: 1fr;
        }

        .content-filter-inline-row .form-label {
            white-space: normal;
        }

        .content-filter-search {
            flex-wrap: wrap;
        }

        .content-filter-search .input-icon {
            flex-basis: 100%;
        }

        .content-filter-reset-btn {
            flex: 1 1 auto;
        }
    }
</style>

<script>
    $(function () {
        const defaultYear = '{{ $yearNow }}';

        $('#contentFilterMonth, #contentFilterYear').on('change', function () {
            if ($('#contentFilterMonth').val() && !$('#contentFilterYear').val()) {
                $('#contentFilterYear').val(defaultYear);
            }

            $('#contentFilterForm').trigger('submit');
        });

        $('.content-auto-submit').on('change', function () {
            $('#contentFilterForm').trigger('submit');
        });

    });
</script>
