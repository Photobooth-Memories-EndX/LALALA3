{{-- Quick Filter Toolbar Card --}}
<div class="card border border-light-subtle shadow-sm rounded-3 p-2 mb-3 bg-white quick-filter-box admin-card-bordered">
    <div class="d-flex align-items-center gap-2 flex-wrap flex-md-nowrap w-100">

        <!-- 1. Entries Select -->
        <div style="min-width: 110px; width: 110px;" class="flex-shrink-0">
            <select class="form-select rounded-3 shadow-none certificate-filter" id="certificate-entries">
                <option value="10">10 Entries</option>
                <option value="15" selected>15 Entries</option>
                <option value="25">25 Entries</option>
                <option value="50">50 Entries</option>
                <option value="100">100 Entries</option>
            </select>
        </div>

        <!-- 2. Type Select -->
        <div style="min-width: 130px; width: 130px;" class="flex-shrink-0">
            <select class="form-select rounded-3 shadow-none certificate-filter" id="certificate-type">
                <option value="all">All Types</option>
                <option value="content">Content</option>
                <option value="playlist">Playlist</option>
                <option value="global">Global</option>
                <option value="training_offline">Training Offline</option>
            </select>
        </div>

        <!-- 3. Status Select -->
        <div style="min-width: 120px; width: 120px;" class="flex-shrink-0">
            <select class="form-select rounded-3 shadow-none certificate-filter" id="certificate-status">
                <option value="all">All Status</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
        </div>

        <!-- 4. Search Box -->
        <div class="position-relative flex-grow-1 min-w-0">
            <input type="text" id="certificate-search" 
                   class="form-control rounded-3 shadow-none" 
                   placeholder="Search certificate title, issuer..." 
                   autocomplete="off">
        </div>

        <!-- 5. Search Submit Button -->
        <button type="button" id="btn-certificate-search" 
                class="btn btn-primary px-3 shadow-none flex-shrink-0" 
                style="background-color: #003b6d; border-color: #003b6d;" 
                title="Search">
            <i class="ti ti-search fs-2"></i>
        </button>

        <!-- 6. More Filters Button -->
        <button type="button" class="btn btn-outline-primary px-3 shadow-none flex-shrink-0" 
                style="color: #003b6d; border-color: #003b6d;" 
                data-bs-toggle="modal" data-bs-target="#moreCertificateFiltersModal"
                data-toggle="modal" data-target="#moreCertificateFiltersModal"
                title="More Filters">
            <i class="ti ti-adjustments-horizontal fs-2"></i>
        </button>

        <!-- 7. Reset Button -->
        <button type="button" id="certificate-filter-reset" 
                class="btn btn-outline-primary px-3 shadow-none flex-shrink-0" 
                style="color: #003b6d; border-color: #003b6d;" 
                title="Reset Filters">
            <i class="ti ti-refresh fs-2"></i>
        </button>
    </div>
</div>

{{-- More Certificate Filters Modal --}}
<div class="modal fade" id="moreCertificateFiltersModal" tabindex="-1" aria-labelledby="moreCertificateFiltersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-3">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="moreCertificateFiltersModalLabel">
                    <i class="ti ti-adjustments-horizontal me-2 text-primary"></i>More Certificate Filters
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Issuer Filter -->
                <div class="mb-3">
                    <label class="form-label fw-semibold text-dark small" for="certificate-issuer">Issuer Name</label>
                    <select id="certificate-issuer" class="form-select rounded-3 certificate-filter">
                        <option value="all">All Issuers</option>
                        @if(isset($issuers) && count($issuers) > 0)
                            @foreach($issuers as $iss)
                                <option value="{{ $iss }}">{{ $iss }}</option>
                            @endforeach
                        @else
                            <option value="OWL Training Management">OWL Training Management</option>
                            <option value="PT Krakatau Posco">PT Krakatau Posco</option>
                            <option value="HR Training Management">HR Training Management</option>
                        @endif
                    </select>
                </div>

                <!-- Assessment Requirement Filter -->
                <div class="mb-3">
                    <label class="form-label fw-semibold text-dark small" for="certificate-assessment-required">Assessment Requirement</label>
                    <select id="certificate-assessment-required" class="form-select rounded-3 certificate-filter">
                        <option value="all">All Requirements</option>
                        <option value="1">Required</option>
                        <option value="0">Optional</option>
                    </select>
                </div>

                <div class="row g-2">
                    <!-- Minimum Progress Filter -->
                    <div class="col-6 mb-3">
                        <label class="form-label fw-semibold text-dark small" for="certificate-minimum-progress">Min Progress</label>
                        <select id="certificate-minimum-progress" class="form-select rounded-3 certificate-filter">
                            <option value="all">All Progress</option>
                            <option value="80">80%</option>
                            <option value="90">90%</option>
                            <option value="100">100%</option>
                        </select>
                    </div>

                    <!-- Minimum Score Filter -->
                    <div class="col-6 mb-3">
                        <label class="form-label fw-semibold text-dark small" for="certificate-minimum-score">Min Score</label>
                        <select id="certificate-minimum-score" class="form-select rounded-3 certificate-filter">
                            <option value="all">All Scores</option>
                            <option value="70">70</option>
                            <option value="80">80</option>
                            <option value="90">90</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-secondary rounded-3" id="btn-reset-modal-filters">Reset</button>
                <button type="button" class="btn btn-primary rounded-3" id="btn-apply-certificate-filters" data-bs-dismiss="modal" data-dismiss="modal" style="background-color: #003b6d; border-color: #003b6d;">
                    <i class="ti ti-check me-1"></i> Apply Filter
                </button>
            </div>
        </div>
    </div>
</div>
