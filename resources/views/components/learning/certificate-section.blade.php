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
        'status' => request('certificate_filter_status', 'all'),
        'sort' => request('certificate_sort', 'issued_desc'),
    ];
    $advancedKeys = [
        'certificate_number', 'certificate_credential', 'certificate_participant',
        'certificate_issuer', 'certificate_date_from', 'certificate_date_to',
        'certificate_target',
    ];
    $advancedCount = collect($advancedKeys)->filter(fn ($key) => request()->filled($key))->count();
    // UI-only feature flag. Export handler and backend remain available.
    $showCertificateExportButton = false;
@endphp

<div class="card border-0 shadow-sm rounded-4 mb-3 certificate-card-shell">
    <div class="card-body p-4 border-bottom">
        <div class="record-filter-shell certificate-filter-shell" id="certificateFilterBar">
            <div class="row g-3 align-items-start">
                <div class="col-12 col-lg-6">
                    <h3 class="card-title mb-1">My Certificates</h3>
                    <div class="text-muted small">View all certificates that have been successfully generated.</div>
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label small text-muted mb-1" for="certificateSearch">Search</label>
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                        <input type="search" class="form-control" id="certificateSearch"
                               placeholder="Certificate number, credential, participant, target or type..."
                               value="{{ request('certificate_search') }}" autocomplete="off">
                    </div>
                </div>
            </div>

            <div class="learning-record-filter-row mt-3 mb-0">
                <div class="learning-record-fast-filter">
                    <label class="form-label small text-muted mb-2">Fast Filter</label>
                    <div class="d-flex flex-wrap gap-2 certificate-quick-dropdowns">
                        <div class="dropdown" data-certificate-filter="type" data-selected-value="{{ $active['type'] }}">
                            <button type="button" class="btn btn-outline-secondary rounded-3 dropdown-toggle {{ $active['type'] !== 'all' ? 'active' : '' }}" data-toggle="dropdown">
                                Type: <span class="fw-semibold">{{ $types[$active['type']] ?? 'All' }}</span>
                            </button>
                            <div class="dropdown-menu">
                                @foreach ($types as $value => $label)
                                    <a href="#" class="dropdown-item certificate-quick-item {{ $active['type'] === $value ? 'active' : '' }}" data-value="{{ $value }}">{{ $label }}</a>
                                @endforeach
                            </div>
                        </div>

                        <div class="dropdown" data-certificate-filter="year" data-selected-value="{{ $active['year'] }}">
                            <button type="button" class="btn btn-outline-secondary rounded-3 dropdown-toggle {{ $active['year'] !== 'all' ? 'active' : '' }}" data-toggle="dropdown">
                                Year: <span class="fw-semibold">{{ $active['year'] === 'all' ? 'All' : $active['year'] }}</span>
                            </button>
                            <div class="dropdown-menu certificate-dropdown-scroll">
                                <a href="#" class="dropdown-item certificate-quick-item {{ $active['year'] === 'all' ? 'active' : '' }}" data-value="all">All</a>
                                @foreach (collect($filterOptions['years'] ?? []) as $year)
                                    <a href="#" class="dropdown-item certificate-quick-item {{ (string) $active['year'] === (string) $year ? 'active' : '' }}" data-value="{{ $year }}">{{ $year }}</a>
                                @endforeach
                            </div>
                        </div>

                        <div class="dropdown" data-certificate-filter="month" data-selected-value="{{ $active['month'] }}">
                            <button type="button" class="btn btn-outline-secondary rounded-3 dropdown-toggle {{ $active['month'] !== 'all' ? 'active' : '' }}" data-toggle="dropdown">
                                Month: <span class="fw-semibold">{{ $active['month'] === 'all' ? 'All' : ($months[(int) $active['month']] ?? 'All') }}</span>
                            </button>
                            <div class="dropdown-menu certificate-dropdown-scroll">
                                <a href="#" class="dropdown-item certificate-quick-item {{ $active['month'] === 'all' ? 'active' : '' }}" data-value="all">All</a>
                                @foreach ($months as $number => $month)
                                    <a href="#" class="dropdown-item certificate-quick-item {{ (string) $active['month'] === (string) $number ? 'active' : '' }}" data-value="{{ $number }}">{{ $month }}</a>
                                @endforeach
                            </div>
                        </div>

                        <div class="dropdown" data-certificate-filter="status" data-selected-value="{{ $active['status'] }}">
                            <button type="button" class="btn btn-outline-secondary rounded-3 dropdown-toggle {{ $active['status'] !== 'all' ? 'active' : '' }}" data-toggle="dropdown">
                                Status: <span class="fw-semibold">{{ $active['status'] === 'generated' ? 'Generated' : 'All' }}</span>
                            </button>
                            <div class="dropdown-menu">
                                <a href="#" class="dropdown-item certificate-quick-item {{ $active['status'] === 'all' ? 'active' : '' }}" data-value="all">All</a>
                                <a href="#" class="dropdown-item certificate-quick-item {{ $active['status'] === 'generated' ? 'active' : '' }}" data-value="generated">Generated</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="record-filter-actions certificate-filter-actions">
                    @if ($showCertificateExportButton)
                        <button type="button" class="btn btn-outline-success record-filter-action-btn" id="certificateDownloadExcel">
                            <i class="ti ti-file-spreadsheet"></i><span>Download Excel</span>
                        </button>
                    @endif
                    <button type="button" class="btn btn-outline-primary record-filter-action-btn" data-toggle="modal" data-target="#certificateMoreFiltersModal">
                        <i class="ti ti-adjustments-horizontal"></i><span>More Filters</span>
                        <span class="badge bg-primary text-white {{ $advancedCount ? '' : 'd-none' }}" id="certificateMoreFiltersCount">{{ $advancedCount ?: '' }}</span>
                    </button>
                    <button type="button" class="btn btn-primary record-filter-action-btn" id="certificateApplyFilter">
                        <i class="ti ti-check"></i><span>Apply Filter</span>
                    </button>
                    <button type="button" class="btn btn-outline-primary record-filter-action-btn" id="certificateResetFilter">Reset</button>
                </div>
            </div>
        </div>
    </div>

    <x-learning.certificate.certificate-table :certificates="$userCertificates" :active-sort="$active['sort']" />

    <div class="modal fade" id="certificateMoreFiltersModal" tabindex="-1" role="dialog" aria-labelledby="certificateMoreFiltersTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content certificate-filter-modal">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0" id="certificateMoreFiltersTitle">More Filters</h5>
                        <div class="text-muted small">Advanced certificate filters</div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body certificate-more-filters">
                    @foreach ([
                        'certificate_number' => ['Certificate Number', 'text', 'OWL-CERT-...'],
                        'certificate_credential' => ['Credential ID', 'text', 'OWL-...'],
                        'certificate_participant' => ['Participant Name', 'text', 'Participant name'],
                        'certificate_target' => ['Target Name', 'text', 'Content, playlist or assessment'],
                        'certificate_issuer' => ['Issuer', 'text', 'Issuer name'],
                        'certificate_date_from' => ['Issued From', 'date', ''],
                        'certificate_date_to' => ['Issued To', 'date', ''],
                    ] as $name => [$label, $type, $placeholder])
                        <div class="certificate-filter-field">
                            <label class="form-label" for="{{ $name }}">{{ $label }}</label>
                            <input class="form-control certificate-advanced-input" id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
                                   value="{{ request($name) }}" placeholder="{{ $placeholder }}" @if($name === 'certificate_issuer') list="certificateIssuerList" @endif>
                        </div>
                    @endforeach

                    <div class="certificate-filter-field">
                        <label class="form-label" for="certificateAdvancedType">Certificate Type</label>
                        <select class="form-select" id="certificateAdvancedType">
                            @foreach ($types as $value => $label)
                                <option value="{{ $value }}" {{ $active['type'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="certificate-filter-field">
                        <label class="form-label" for="certificateSort">Sorting</label>
                        <select class="form-select" id="certificateSort">
                            @foreach (['issued_desc' => 'Newest', 'issued_asc' => 'Oldest', 'number_asc' => 'Certificate Number A-Z', 'number_desc' => 'Certificate Number Z-A', 'participant_asc' => 'Participant A-Z', 'participant_desc' => 'Participant Z-A', 'target_asc' => 'Target Name A-Z', 'target_desc' => 'Target Name Z-A'] as $value => $label)
                                <option value="{{ $value }}" {{ $active['sort'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <datalist id="certificateIssuerList">
                    @foreach (collect($filterOptions['issuers'] ?? []) as $issuer)<option value="{{ $issuer }}">@endforeach
                </datalist>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-primary" id="certificateModalReset">Reset</button>
                    <button type="button" class="btn btn-primary certificate-apply-filter">Apply Filter</button>
                </div>
            </div>
        </div>
    </div>
</div>
