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
    $active = [
        'type' => request('certificate_filter_type', 'all'),
        'year' => request('certificate_filter_year', 'all'),
        'month' => request('certificate_filter_month', 'all'),
        'sort' => request('certificate_sort', 'all'),
    ];
    $advancedKeys = [
        'certificate_issuer', 'certificate_date_from', 'certificate_date_to',
        'certificate_expiration_status', 'certificate_sort',
    ];
    $advancedCount = collect($advancedKeys)->filter(function ($key) {
        $val = request($key);
        if ($key === 'certificate_expiration_status' || $key === 'certificate_sort') {
            return $val && $val !== 'all';
        }
        return request()->filled($key);
    })->count();
@endphp

<div class="card border-0 shadow-sm rounded-4 mb-3 certificate-card-shell">
    <div class="card-body p-4 border-bottom">
        <div class="record-filter-shell certificate-filter-shell" id="certificateFilterBar">
            {{-- <h3 class="card-title mb-1">My Certificates</h3>
            <div class="text-muted small">View all certificates that have been successfully generated.</div> --}}

            <div class="certificate-fast-filter-section mt-3">
                <label class="form-label small text-muted mb-2 learning-fast-filter-label">Fast Filter</label>

                <div class="certificate-fast-filter-toolbar">
                    <div class="certificate-quick-dropdowns">
                        <div class="dropdown certificate-filter-dropdown certificate-filter-dropdown-type"
                             data-certificate-filter="type" data-selected-value="{{ $active['type'] }}">
                            <input type="hidden" class="certificate-quick-value" name="certificate_filter_type" value="{{ $active['type'] }}">
                            <button type="button" class="btn btn-filter-chip certificate-fast-filter-control dropdown-toggle {{ $active['type'] !== 'all' ? 'active' : '' }}"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Type: <span class="fw-semibold certificate-filter-value">{{ $types[$active['type']] ?? 'All' }}</span>
                            </button>
                            <div class="dropdown-menu">
                                @foreach ($types as $value => $label)
                                    <a href="#" class="dropdown-item certificate-quick-item {{ $active['type'] === $value ? 'active' : '' }}" data-value="{{ $value }}">{{ $label }}</a>
                                @endforeach
                            </div>
                        </div>

                        <div class="dropdown certificate-filter-dropdown certificate-filter-dropdown-year"
                             data-certificate-filter="year" data-selected-value="{{ $active['year'] }}">
                            <input type="hidden" class="certificate-quick-value" name="certificate_filter_year" value="{{ $active['year'] }}">
                            <button type="button" class="btn btn-filter-chip certificate-fast-filter-control dropdown-toggle {{ $active['year'] !== 'all' ? 'active' : '' }}"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Year: <span class="fw-semibold certificate-filter-value">{{ $active['year'] === 'all' ? 'All' : $active['year'] }}</span>
                            </button>
                            <div class="dropdown-menu certificate-dropdown-scroll">
                                <a href="#" class="dropdown-item certificate-quick-item {{ $active['year'] === 'all' ? 'active' : '' }}" data-value="all">All</a>
                                @foreach (collect($filterOptions['years'] ?? []) as $year)
                                    <a href="#" class="dropdown-item certificate-quick-item {{ (string) $active['year'] === (string) $year ? 'active' : '' }}" data-value="{{ $year }}">{{ $year }}</a>
                                @endforeach
                            </div>
                        </div>

                        <div class="dropdown certificate-filter-dropdown certificate-filter-dropdown-month"
                             data-certificate-filter="month" data-selected-value="{{ $active['month'] }}">
                            <input type="hidden" class="certificate-quick-value" name="certificate_filter_month" value="{{ $active['month'] }}">
                            <button type="button" class="btn btn-filter-chip certificate-fast-filter-control dropdown-toggle {{ $active['month'] !== 'all' ? 'active' : '' }}"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Month: <span class="fw-semibold certificate-filter-value">{{ $active['month'] === 'all' ? 'All' : ($months[(int) $active['month']] ?? 'All') }}</span>
                            </button>
                            <div class="dropdown-menu certificate-dropdown-scroll">
                                <a href="#" class="dropdown-item certificate-quick-item {{ $active['month'] === 'all' ? 'active' : '' }}" data-value="all">All</a>
                                @foreach ($months as $number => $month)
                                    <a href="#" class="dropdown-item certificate-quick-item {{ (string) $active['month'] === (string) $number ? 'active' : '' }}" data-value="{{ $number }}">{{ $month }}</a>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="certificate-toolbar-search">
                        <label class="visually-hidden" for="certificateSearch">Search certificates</label>
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                            <input type="search" class="form-control" id="certificateSearch"
                                   placeholder="Certificate number, credential, participant, target or type..."
                                   value="{{ request('certificate_search') }}" autocomplete="off">
                        </div>
                    </div>

                    <div class="record-filter-actions certificate-filter-actions">
                        <button type="button" class="btn btn-primary btn-learning-primary record-filter-action-btn certificate-filter-action-btn"
                                id="certificateApplyFilter" title="Apply Filter" aria-label="Apply Filter">
                            <i class="ti ti-search"></i>
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-learning-secondary record-filter-action-btn certificate-filter-action-btn"
                                data-toggle="modal" data-target="#certificateMoreFiltersModal"
                                title="More Filters" aria-label="More Filters">
                            <i class="ti ti-adjustments-horizontal"></i>
                            <span class="badge bg-primary text-white {{ $advancedCount ? '' : 'd-none' }}" id="certificateMoreFiltersCount">{{ $advancedCount ?: '' }}</span>
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-learning-outline record-filter-action-btn certificate-filter-action-btn"
                                id="certificateResetFilter" title="Reset Filter" aria-label="Reset Filter">
                            <i class="ti ti-refresh"></i>
                        </button>
                    </div>
                </div>
            </div>
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

                    <hr class="my-3">

                    {{-- 3. Expiration Status --}}
                    @php
                        $expStatus = request('certificate_expiration_status', request('certificate_status', 'all'));
                    @endphp
                    <div class="learning-filter-group">
                        <div class="fw-semibold mb-2">Expiration Status</div>
                        <div class="d-flex flex-wrap gap-3">
                            <label class="form-check">
                                <input class="form-check-input certificate-advanced-input" type="radio"
                                       name="certificate_expiration_status" value="all"
                                       {{ $expStatus === 'all' ? 'checked' : '' }}>
                                <span class="form-check-label">All</span>
                            </label>
                            <label class="form-check">
                                <input class="form-check-input certificate-advanced-input" type="radio"
                                       name="certificate_expiration_status" value="active"
                                       {{ $expStatus === 'active' ? 'checked' : '' }}>
                                <span class="form-check-label">Active / Valid</span>
                            </label>
                            <label class="form-check">
                                <input class="form-check-input certificate-advanced-input" type="radio"
                                       name="certificate_expiration_status" value="expired"
                                       {{ $expStatus === 'expired' ? 'checked' : '' }}>
                                <span class="form-check-label">Expired</span>
                            </label>
                        </div>
                    </div>

                    <hr class="my-3">

                    {{-- 4. Sorting --}}
                    @php
                        $curSort = request('certificate_sort', 'all');
                    @endphp
                    <div class="learning-filter-group">
                        <label class="form-label fw-semibold mb-2" for="certificateSort">Sorting</label>
                        <select class="form-select certificate-advanced-input" id="certificateSort" name="certificate_sort">
                            <option value="all" {{ $curSort === 'all' ? 'selected' : '' }}>All</option>
                            <option value="issued_desc" {{ $curSort === 'issued_desc' ? 'selected' : '' }}>Newest Issued</option>
                            <option value="issued_asc" {{ $curSort === 'issued_asc' ? 'selected' : '' }}>Oldest Issued</option>
                            <option value="expiration" {{ $curSort === 'expiration' ? 'selected' : '' }}>Expiration Date</option>
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
                    <button type="button" class="btn btn-primary btn-learning-primary certificate-apply-filter" id="certificateApplyFilter">
                        <i class="ti ti-search"></i><span>Apply Filter</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
