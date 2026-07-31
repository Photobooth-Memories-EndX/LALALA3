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
        'sort' => request('certificate_sort', 'issued_desc'),
    ];
    $advancedKeys = [
        'certificate_number', 'certificate_credential', 'certificate_participant',
        'certificate_issuer', 'certificate_date_from', 'certificate_date_to',
        'certificate_target',
    ];
    $advancedCount = collect($advancedKeys)->filter(fn ($key) => request()->filled($key))->count();
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
                        <button type="button" class="btn btn-outline-primary btn-learning-secondary record-filter-action-btn certificate-filter-action-btn"
                                data-toggle="modal" data-target="#certificateMoreFiltersModal"
                                title="More Filters" aria-label="More Filters">
                            <i class="ti ti-adjustments-horizontal"></i>
                            <span class="badge bg-primary text-white {{ $advancedCount ? '' : 'd-none' }}" id="certificateMoreFiltersCount">{{ $advancedCount ?: '' }}</span>
                        </button>
                        <button type="button" class="btn btn-primary btn-learning-primary record-filter-action-btn certificate-filter-action-btn"
                                id="certificateApplyFilter" title="Apply Filter" aria-label="Apply Filter">
                            <i class="ti ti-check"></i>
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

    <div class="modal fade" id="certificateMoreFiltersModal" tabindex="-1" role="dialog" aria-labelledby="certificateMoreFiltersTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content certificate-filter-modal">
                <div class="modal-header certificate-filter-modal-header">
                    <div class="d-flex align-items-center">
                        <span class="certificate-filter-modal-icon" aria-hidden="true"><i class="ti ti-adjustments-horizontal"></i></span>
                        <div>
                            <h5 class="modal-title mb-1" id="certificateMoreFiltersTitle">More Filters</h5>
                            <div class="text-muted small">Advanced Certificate Filters</div>
                        </div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body certificate-more-filters">
                    <section class="certificate-filter-section" aria-labelledby="certificateInformationHeading">
                        <div class="certificate-filter-section-heading">
                            <i class="ti ti-certificate" aria-hidden="true"></i>
                            <h6 id="certificateInformationHeading">Certificate Information</h6>
                        </div>
                        <div class="row g-4 certificate-filter-grid">
                            <div class="col-12 col-md-6 col-lg-6 certificate-filter-field">
                                <label class="form-label" for="certificate_number">Certificate Number</label>
                                <input class="form-control certificate-advanced-input" id="certificate_number" name="certificate_number" type="text"
                                       value="{{ request('certificate_number') }}" placeholder="OWL-CERT-...">
                            </div>
                            <div class="col-12 col-md-6 col-lg-6 certificate-filter-field">
                                <label class="form-label" for="certificate_credential">Credential ID</label>
                                <input class="form-control certificate-advanced-input" id="certificate_credential" name="certificate_credential" type="text"
                                       value="{{ request('certificate_credential') }}" placeholder="OWL-...">
                            </div>
                            <div class="col-12 col-md-6 col-lg-6 certificate-filter-field">
                                <label class="form-label" for="certificateAdvancedType">Certificate Type</label>
                                <select class="form-select" id="certificateAdvancedType">
                                    @foreach ($types as $value => $label)
                                        <option value="{{ $value }}" {{ $active['type'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </section>

                    <section class="certificate-filter-section" aria-labelledby="certificateParticipantHeading">
                        <div class="certificate-filter-section-heading">
                            <i class="ti ti-user" aria-hidden="true"></i>
                            <h6 id="certificateParticipantHeading">Participant</h6>
                        </div>
                        <div class="row g-4 certificate-filter-grid">
                            <div class="col-12 col-md-6 col-lg-6 certificate-filter-field">
                                <label class="form-label" for="certificate_participant">Participant Name</label>
                                <input class="form-control certificate-advanced-input" id="certificate_participant" name="certificate_participant" type="text"
                                       value="{{ request('certificate_participant') }}" placeholder="Participant name">
                            </div>
                            <div class="col-12 col-md-6 col-lg-6 certificate-filter-field">
                                <label class="form-label" for="certificate_issuer">Issuer</label>
                                <input class="form-control certificate-advanced-input" id="certificate_issuer" name="certificate_issuer" type="text"
                                       value="{{ request('certificate_issuer') }}" placeholder="Issuer name" list="certificateIssuerList">
                            </div>
                        </div>
                    </section>

                    <section class="certificate-filter-section" aria-labelledby="certificateTargetHeading">
                        <div class="certificate-filter-section-heading">
                            <i class="ti ti-focus-2" aria-hidden="true"></i>
                            <h6 id="certificateTargetHeading">Target</h6>
                        </div>
                        <div class="row g-4 certificate-filter-grid">
                            <div class="col-12 col-md-6 col-lg-6 certificate-filter-field">
                                <label class="form-label" for="certificate_target">Target Name</label>
                                <input class="form-control certificate-advanced-input" id="certificate_target" name="certificate_target" type="text"
                                       value="{{ request('certificate_target') }}" placeholder="Content, playlist or assessment">
                            </div>
                        </div>
                    </section>

                    <section class="certificate-filter-section" aria-labelledby="certificateIssuedDateHeading">
                        <div class="certificate-filter-section-heading">
                            <i class="ti ti-calendar" aria-hidden="true"></i>
                            <h6 id="certificateIssuedDateHeading">Issued Date</h6>
                        </div>
                        <div class="row g-4 certificate-filter-grid">
                            <div class="col-12 col-md-6 col-lg-6 certificate-filter-field">
                                <label class="form-label" for="certificate_date_from">Issued From</label>
                                <div class="input-icon certificate-date-input">
                                    <span class="input-icon-addon"><i class="ti ti-calendar"></i></span>
                                    <input class="form-control certificate-advanced-input" id="certificate_date_from" name="certificate_date_from" type="date"
                                           value="{{ request('certificate_date_from') }}">
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-6 certificate-filter-field">
                                <label class="form-label" for="certificate_date_to">Issued To</label>
                                <div class="input-icon certificate-date-input">
                                    <span class="input-icon-addon"><i class="ti ti-calendar"></i></span>
                                    <input class="form-control certificate-advanced-input" id="certificate_date_to" name="certificate_date_to" type="date"
                                           value="{{ request('certificate_date_to') }}">
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-6 certificate-filter-field">
                                <label class="form-label" for="certificateSort">Sorting</label>
                                <select class="form-select" id="certificateSort">
                                    @foreach (['issued_desc' => 'Newest', 'issued_asc' => 'Oldest', 'id_asc' => 'No Ascending', 'id_desc' => 'No Descending', 'target_asc' => 'Certificate A-Z', 'target_desc' => 'Certificate Z-A', 'type_asc' => 'Type A-Z', 'type_desc' => 'Type Z-A', 'number_asc' => 'Certificate Number A-Z', 'number_desc' => 'Certificate Number Z-A', 'participant_asc' => 'Participant A-Z', 'participant_desc' => 'Participant Z-A'] as $value => $label)
                                        <option value="{{ $value }}" {{ $active['sort'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </section>
                </div>
                <datalist id="certificateIssuerList">
                    @foreach (collect($filterOptions['issuers'] ?? []) as $issuer)<option value="{{ $issuer }}">@endforeach
                </datalist>
                <div class="modal-footer certificate-filter-modal-footer justify-content-end">
                    <button type="button" class="btn btn-outline-primary btn-learning-outline" id="certificateModalReset">
                        <i class="ti ti-refresh"></i><span>Reset</span>
                    </button>
                    <button type="button" class="btn btn-primary btn-learning-primary certificate-apply-filter"><i class="ti ti-check"></i><span>Apply Filter</span></button>
                </div>
            </div>
        </div>
    </div>
</div>
