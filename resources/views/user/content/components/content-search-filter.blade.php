@php
    $filterFromDate = request('from_date');
    $filterToDate = request('to_date');
    $filterDuration = request('duration', 'all');
    $filterTotalPages = array_values(array_filter((array) request('total_pages', [])));
    $selectedMonth = request('month', '');
    $selectedYear = request('year', '');

    if (blank($selectedMonth) && filled($filterFromDate)) {
        $selectedMonth = \Carbon\Carbon::parse($filterFromDate)->format('m');
    }

    if (blank($selectedYear) && filled($filterFromDate)) {
        $selectedYear = \Carbon\Carbon::parse($filterFromDate)->format('Y');
    }

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
    $modalFilterCount += filled($filterFromDate) ? 1 : 0;
    $modalFilterCount += filled($filterToDate) ? 1 : 0;
    $modalFilterCount += $filterDateOrder !== 'newest' ? 1 : 0;
    $modalFilterCount += filled($filterViewOrder) ? 1 : 0;
    $modalFilterCount += $filterDuration !== 'all' ? 1 : 0;
    $modalFilterCount += count($filterTotalPages);

    $activeFilterCount = $modalFilterCount;
    $activeFilterCount += filled($filterSearch) ? 1 : 0;
    $activeFilterCount += filled($filterClassification) ? 1 : 0;
    $activeFilterCount += filled($filterType) ? 1 : 0;

    $monthOptions = collect(range(1, 12))->mapWithKeys(function ($month) {
        $value = str_pad((string) $month, 2, '0', STR_PAD_LEFT);
        return [$value => \Carbon\Carbon::create(null, $month, 1)->format('F')];
    });

    $yearNow = (int) now()->format('Y');
    $yearOptions = range($yearNow, $yearNow - 5);
@endphp

<div class="card border-0 shadow-sm rounded-4 mb-4 learning-filter-card content-filter-card">
    <div class="card-body p-3 p-lg-4">
        <form method="GET" action="{{ route('content.user.index') }}" id="contentFilterForm">
            <input type="hidden" name="from_date" id="contentFilterFromDateHidden" value="{{ $filterFromDate }}">
            <input type="hidden" name="to_date" id="contentFilterToDateHidden" value="{{ $filterToDate }}">

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
                </div>
            </div>

            <div class="content-filter-inline-row">
                <div class="content-filter-field content-filter-field-category">
                    <label class="form-label small text-muted mb-1" for="contentFilterClassification">Category / Classification</label>
                    <select name="classification" id="contentFilterClassification" class="form-select rounded-3" onchange="this.form.submit()">
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
                    <select name="type" id="contentFilterType" class="form-select rounded-3" onchange="this.form.submit()">
                        <option value="">All Types</option>
                        <option value="Document" {{ $filterType == 'Document' ? 'selected' : '' }}>Document</option>
                        <option value="Video" {{ $filterType == 'Video' ? 'selected' : '' }}>Video</option>
                        <option value="YouTube" {{ $filterType == 'YouTube' ? 'selected' : '' }}>YouTube</option>
                    </select>
                </div>

                <div class="content-filter-field content-filter-field-month">
                    <label class="form-label small text-muted mb-1" for="contentFilterMonth">Month</label>
                    <select name="month" id="contentFilterMonth" class="form-select rounded-3">
                        <option value="">All Months</option>
                        @foreach ($monthOptions as $value => $label)
                            <option value="{{ $value }}" {{ $selectedMonth == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="content-filter-field content-filter-field-year">
                    <label class="form-label small text-muted mb-1" for="contentFilterYear">Year</label>
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
                                <div class="fw-semibold mb-2">Date Range</div>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small text-muted mb-1" for="contentFilterFromDate">From Date</label>
                                        <input type="date"
                                               id="contentFilterFromDate"
                                               value="{{ $filterFromDate }}"
                                               class="form-control rounded-3">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small text-muted mb-1" for="contentFilterToDate">To Date</label>
                                        <input type="date"
                                               id="contentFilterToDate"
                                               value="{{ $filterToDate }}"
                                               class="form-control rounded-3">
                                    </div>
                                </div>
                            </div>

                            <hr class="my-3">

                            <div class="learning-filter-group">
                                <div class="fw-semibold mb-2">Sort Date</div>
                                <div class="d-flex flex-wrap gap-3">
                                    <label class="form-check">
                                        <input class="form-check-input" type="radio"
                                               name="date_order" value="newest"
                                               {{ $filterDateOrder === 'newest' ? 'checked' : '' }}>
                                        <span class="form-check-label">Newest</span>
                                    </label>
                                    <label class="form-check">
                                        <input class="form-check-input" type="radio"
                                               name="date_order" value="oldest"
                                               {{ $filterDateOrder === 'oldest' ? 'checked' : '' }}>
                                        <span class="form-check-label">Oldest</span>
                                    </label>
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
        width: min(100%, 540px);
        flex: 0 1 540px;
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
    }
</style>

<script>
    $(function () {
        function syncContentMonthYearRange() {
            const month = $('#contentFilterMonth').val();
            const year = $('#contentFilterYear').val();

            if (month && year) {
                const lastDay = new Date(Number(year), Number(month), 0).getDate();
                $('#contentFilterFromDateHidden').val(year + '-' + month + '-01');
                $('#contentFilterToDateHidden').val(year + '-' + month + '-' + String(lastDay).padStart(2, '0'));
            } else {
                $('#contentFilterFromDateHidden').val('');
                $('#contentFilterToDateHidden').val('');
            }
        }

        $('#contentFilterMonth, #contentFilterYear').on('change', function () {
            syncContentMonthYearRange();
            $('#contentFilterForm').trigger('submit');
        });

        $('#contentMoreFiltersModal').on('show.bs.modal', function () {
            $('#contentFilterFromDate').attr('name', 'from_date');
            $('#contentFilterToDate').attr('name', 'to_date');
            $('#contentFilterFromDateHidden, #contentFilterToDateHidden').prop('disabled', true);
        });

        $('#contentMoreFiltersModal').on('hidden.bs.modal', function () {
            $('#contentFilterFromDate').removeAttr('name');
            $('#contentFilterToDate').removeAttr('name');
            $('#contentFilterFromDateHidden, #contentFilterToDateHidden').prop('disabled', false);
        });

        $('#contentFilterForm').on('submit', function () {
            if (!$('#contentMoreFiltersModal').hasClass('show')) {
                syncContentMonthYearRange();
            }
        });
    });
</script>
