@props([
    'userCertificates',
    'filterOptions' => [],
])

@php
    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];
    $types = ['all' => 'All', 'content' => 'Content', 'playlist' => 'Playlist', 'global' => 'Global'];
    $rawType = request('certificate_filter_type', request('type', 'all'));
    $type = strtolower(trim((string) $rawType));
    if (!array_key_exists($type, $types)) {
        $type = 'all';
    }
    $rawMonth = request('certificate_filter_month', request('month', 'all'));
    $resolvedMonth = \App\Support\LearningFilterHelper::resolveMonth($rawMonth) ?? $rawMonth;

    $active = [
        'type' => $type,
        'year' => request('certificate_filter_year', request('year', 'all')),
        'month' => (string) $resolvedMonth,
        'sort' => request('certificate_sort', request('sort', 'all')),
    ];
    $advancedKeys = [
        'certificate_issuer', 'certificate_date_from', 'certificate_date_to',
        'certificate_sort',
    ];
    $advancedCount = collect($advancedKeys)->filter(function ($key) {
        $val = request($key);
        if ($key === 'certificate_sort') {
            return $val && $val !== 'all';
        }
        return request()->filled($key);
    })->count();

    $exportParams = request()->except(['certificate_page', 'certificate_export']);
    $exportParams['certificate_export'] = '1';
    $exportUrl = route('user.learning.dashboard.partial', array_merge(['tab' => 'certificate'], $exportParams));
@endphp

<div class="card border-0 shadow-sm rounded-4 mb-3 my-task-filter-card certificate-card-shell">
    <div class="card-body p-3 p-md-4">
        <div class="record-filter-shell certificate-filter-shell" id="certificateFilterBar">
            {{-- Header: Title & Subtitle --}}
            <div class="mb-3">
                <h2 class="fw-bold mb-1 my-task-filter-title">My Certificates</h2>
                <p class="text-muted small mb-0 my-task-filter-subtitle">View, download, and manage your earned learning credentials and certificates.</p>
            </div>

            {{-- Category Filter Pills (Type filter elevated to top-level pills) --}}
            <div class="d-flex align-items-center flex-wrap gap-2 mb-3 my-task-pills-row">
                @foreach ($types as $value => $label)
                    <button type="button"
                            class="my-task-category-pill certificate-category-pill {{ $active['type'] === $value ? 'active' : '' }}"
                            data-value="{{ $value }}"
                            title="Filter by {{ $label }}">
                        {{ $value === 'all' ? 'All Certificates' : $label }}
                    </button>
                @endforeach
            </div>

            {{-- Hidden input for certificate type to maintain JS state binding --}}
            <div class="dropdown certificate-filter-dropdown certificate-filter-dropdown-type d-none"
                 data-certificate-filter="type" data-selected-value="{{ $active['type'] }}">
                <input type="hidden" class="certificate-quick-value" name="certificate_filter_type" value="{{ $active['type'] }}">
            </div>

            {{-- Subtle Divider Line --}}
            <hr class="my-3 my-task-divider">

            {{-- Fast Filter Toolbar --}}
            <div class="d-flex align-items-center flex-wrap gap-2 w-100 my-task-toolbar certificate-quick-dropdowns">
                {{-- Dropdown Year --}}
                <div class="dropdown certificate-filter-dropdown certificate-filter-dropdown-year"
                     data-certificate-filter="year" data-selected-value="{{ $active['year'] }}">
                    <input type="hidden" class="certificate-quick-value" name="certificate_filter_year" value="{{ $active['year'] }}">
                    <button type="button" class="btn my-task-dropdown-btn dropdown-toggle {{ $active['year'] !== 'all' ? 'active' : '' }}"
                            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Year: <span class="fw-semibold certificate-filter-value">{{ $active['year'] === 'all' ? 'All' : $active['year'] }}</span>
                    </button>
                    <div class="dropdown-menu shadow-sm certificate-dropdown-scroll">
                        <a href="#" class="dropdown-item my-task-dropdown-item certificate-quick-item {{ $active['year'] === 'all' ? 'active' : '' }}" data-value="all">All</a>
                        @foreach (collect($filterOptions['years'] ?? []) as $year)
                            <a href="#" class="dropdown-item my-task-dropdown-item certificate-quick-item {{ (string) $active['year'] === (string) $year ? 'active' : '' }}" data-value="{{ $year }}">{{ $year }}</a>
                        @endforeach
                    </div>
                </div>

                {{-- Dropdown Month --}}
                <div class="dropdown certificate-filter-dropdown certificate-filter-dropdown-month"
                     data-certificate-filter="month" data-selected-value="{{ $active['month'] }}">
                    <input type="hidden" class="certificate-quick-value" name="certificate_filter_month" value="{{ $active['month'] }}">
                    <button type="button" class="btn my-task-dropdown-btn dropdown-toggle {{ $active['month'] !== 'all' ? 'active' : '' }}"
                            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Month: <span class="fw-semibold certificate-filter-value">{{ $active['month'] === 'all' ? 'All' : ($months[(int) $active['month']] ?? 'All') }}</span>
                    </button>
                    <div class="dropdown-menu shadow-sm certificate-dropdown-scroll" style="max-height: 280px; overflow-y: auto;">
                        <a href="#" class="dropdown-item my-task-dropdown-item certificate-quick-item {{ $active['month'] === 'all' ? 'active' : '' }}" data-value="all">All</a>
                        @foreach ($months as $number => $month)
                            <a href="#" class="dropdown-item my-task-dropdown-item certificate-quick-item {{ (string) $active['month'] === (string) $number ? 'active' : '' }}" data-value="{{ $number }}">{{ $month }}</a>
                        @endforeach
                    </div>
                </div>

                {{-- Search Input with Autocomplete --}}
                <div class="position-relative flex-grow-1 my-task-search-wrapper certificate-search-container" style="min-width: 220px;">
                    <span class="position-absolute top-50 start-0 translate-middle-y ps-3 text-muted pointer-events-none" style="z-index: 2;">
                        <i class="ti ti-search" style="font-size: 15px;"></i>
                    </span>
                    <input type="text"
                           class="form-control my-task-search-input module-search-input"
                           id="certificate-search-input"
                           name="certificate_search"
                           placeholder="Certificate number, credential, participant, target or type..."
                           value="{{ request('certificate_search', request('search', request('q'))) }}"
                           autocomplete="off">
                    @if (request()->filled('certificate_search') || request()->filled('search') || request()->filled('q'))
                        <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y pe-2.5 text-muted text-decoration-none my-task-search-clear" id="certificateClearSearch" title="Clear search" style="z-index: 2;">
                            <i class="ti ti-x"></i>
                        </button>
                    @endif
                    <div id="certificate-search-results" class="dropdown-menu shadow-sm w-100 position-absolute mt-1 module-search-dropdown" style="display: none; max-height: 380px; overflow-y: auto; z-index: 1050; padding: 0;">
                        <div class="autocomplete-list-group list-group list-group-flush"></div>
                        <div class="search-footer border-top bg-light p-2 text-center sticky-bottom"></div>
                    </div>
                </div>

                {{-- Square Action Buttons --}}
                <button type="button" class="btn btn-primary my-task-btn-square my-task-btn-search"
                        id="certificateApplyFilter" title="Apply Filter" aria-label="Apply Filter">
                    <i class="ti ti-search text-white fs-3"></i>
                </button>
                <button type="button" class="btn btn-white my-task-btn-square my-task-btn-filter position-relative"
                        data-bs-toggle="modal" data-bs-target="#certificateMoreFiltersModal"
                        title="More Filters" aria-label="More Filters">
                    <i class="ti ti-adjustments-horizontal fs-3 text-secondary"></i>
                    <span class="badge bg-primary text-white {{ $advancedCount ? '' : 'd-none' }}" id="certificateMoreFiltersCount">{{ $advancedCount ?: '' }}</span>
                </button>
                <button type="button" class="btn btn-white my-task-btn-square my-task-btn-reset"
                        id="certificateResetFilter" title="Reset Filter" aria-label="Reset Filter">
                    <i class="ti ti-refresh fs-3 text-secondary"></i>
                </button>
                <button type="button" class="btn btn-white my-task-btn-square my-task-btn-export"
                        id="certificateDownloadExcel"
                        data-export-url="{{ $exportUrl }}"
                        title="Download Excel" aria-label="Download Excel">
                    <i class="ti ti-download fs-3 text-secondary"></i>
                </button>
            </div>

            {{-- Active Filters indicator --}}
            @php
                $certSearch = request('certificate_search', request('search', request('q')));
                $hasActiveCertFilters = !empty($certSearch) || $active['type'] !== 'all' || $active['year'] !== 'all' || $active['month'] !== 'all' || $advancedCount > 0;
            @endphp
            @if ($hasActiveCertFilters)
                <div class="d-flex align-items-center flex-wrap gap-2 mt-2.5 pt-2.5 border-top">
                    <span class="text-muted small" style="font-size: 11.5px;">Active Filter:</span>
                    @if (!empty($certSearch))
                        <span class="badge bg-light text-dark border px-2.5 py-1 rounded-pill d-inline-flex align-items-center gap-1 shadow-none" style="font-size: 11.5px;">
                            <i class="ti ti-search text-muted"></i>
                            <span>"{{ $certSearch }}"</span>
                        </span>
                    @endif
                    @if ($active['type'] !== 'all')
                        <span class="badge bg-light text-dark border px-2.5 py-1 rounded-pill d-inline-flex align-items-center gap-1 shadow-none" style="font-size: 11.5px;">
                            <span>Type: {{ $types[$active['type']] ?? $active['type'] }}</span>
                        </span>
                    @endif
                    @if ($active['year'] !== 'all')
                        <span class="badge bg-light text-dark border px-2.5 py-1 rounded-pill d-inline-flex align-items-center gap-1 shadow-none" style="font-size: 11.5px;">
                            <span>Year: {{ $active['year'] }}</span>
                        </span>
                    @endif
                    @if ($active['month'] !== 'all')
                        <span class="badge bg-light text-dark border px-2.5 py-1 rounded-pill d-inline-flex align-items-center gap-1 shadow-none" style="font-size: 11.5px;">
                            <span>Month: {{ $months[(int) $active['month']] ?? $active['month'] }}</span>
                        </span>
                    @endif
                    @if ($advancedCount > 0)
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 rounded-pill d-inline-flex align-items-center gap-1 shadow-none" style="font-size: 11.5px;">
                            <i class="ti ti-adjustments-horizontal"></i>
                            <span>{{ $advancedCount }} More Filters</span>
                        </span>
                    @endif
                    <button type="button" class="btn btn-link text-primary small text-decoration-none ms-1 fw-semibold p-0 border-0" id="certificateActiveResetBtn" style="font-size: 11.5px;">
                        Reset all
                    </button>
                </div>
            @endif
        </div>
    </div>

    <x-learning.certificate.certificate-table :certificates="$userCertificates" :active-sort="$active['sort']" />

    <div class="modal fade" id="certificateMoreFiltersModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered learning-filter-modal">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h3 class="modal-title fw-bold">
                        <i class="ti ti-adjustments-horizontal me-1 text-primary"></i>
                        All Filters
                    </h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-2">
                    <p class="text-muted small mb-4">
                        All filters can be combined with the search. &amp; the filter chip above.
                    </p>

                    {{-- 1. Issuer / Provider --}}
                    <div class="learning-filter-group">
                        <label class="form-label fw-semibold mb-2" for="certificate_issuer">Issuer / Provider</label>
                        <input class="form-control certificate-advanced-input" id="certificate_issuer" name="certificate_issuer" type="text"
                               value="{{ request('certificate_issuer') }}" placeholder="Enter issuer or provider name..."
                               list="certificateIssuerList">
                    </div>

                    <hr class="my-3">

                    {{-- 2. Issued Date --}}
                    <div class="learning-filter-group">
                        <div class="fw-semibold mb-2">Issued Date</div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small text-muted mb-1" for="certificate_date_from">Issued From</label>
                                <div class="input-icon certificate-date-input">
                                    <span class="input-icon-addon"><i class="ti ti-calendar"></i></span>
                                    <input class="form-control certificate-advanced-input" id="certificate_date_from" name="certificate_date_from" type="date"
                                           value="{{ request('certificate_date_from') }}">
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small text-muted mb-1" for="certificate_date_to">Issued To</label>
                                <div class="input-icon certificate-date-input">
                                    <span class="input-icon-addon"><i class="ti ti-calendar"></i></span>
                                    <input class="form-control certificate-advanced-input" id="certificate_date_to" name="certificate_date_to" type="date"
                                           value="{{ request('certificate_date_to') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(false)
                    {{-- Reserved for future Certificate Expiration feature per advisor decision --}}
                    <hr class="my-3">
                    <div class="learning-filter-group">
                        <div class="fw-semibold mb-2">Expiration Status</div>
                        <div class="d-flex flex-wrap gap-3">
                            <label class="form-check">
                                <input class="form-check-input certificate-advanced-input" type="radio"
                                       name="certificate_expiration_status" value="all" checked>
                                <span class="form-check-label">All</span>
                            </label>
                            <label class="form-check">
                                <input class="form-check-input certificate-advanced-input" type="radio"
                                       name="certificate_expiration_status" value="active">
                                <span class="form-check-label">Active / Valid</span>
                            </label>
                            <label class="form-check">
                                <input class="form-check-input certificate-advanced-input" type="radio"
                                       name="certificate_expiration_status" value="expired">
                                <span class="form-check-label">Expired</span>
                            </label>
                        </div>
                    </div>
                    @endif

                    <hr class="my-3">

                    {{-- 3. Sorting --}}
                    @php
                        $curSort = request('certificate_sort', 'all');
                    @endphp
                    <div class="learning-filter-group">
                        <label class="form-label fw-semibold mb-2" for="certificateSort">Sorting</label>
                        <select class="form-select certificate-advanced-input" id="certificateSort" name="certificate_sort">
                            <option value="all" {{ $curSort === 'all' ? 'selected' : '' }}>All</option>
                            <option value="issued_desc" {{ $curSort === 'issued_desc' ? 'selected' : '' }}>Newest Issued</option>
                            <option value="issued_asc" {{ $curSort === 'issued_asc' ? 'selected' : '' }}>Oldest Issued</option>
                        </select>
                    </div>
                </div>

                <datalist id="certificateIssuerList">
                    @foreach (collect($filterOptions['issuers'] ?? []) as $issuer)<option value="{{ $issuer }}">@endforeach
                </datalist>

                <div class="modal-footer border-0 pt-0 justify-content-end">
                    <button type="button" class="btn btn-outline-secondary btn-learning-outline" id="certificateModalReset">
                        <i class="ti ti-refresh"></i><span>Reset</span>
                    </button>
                    <button type="button" class="btn btn-primary btn-learning-primary certificate-apply-filter" id="certificateModalApplyFilter">
                        <i class="ti ti-search"></i><span>Apply Filter</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
