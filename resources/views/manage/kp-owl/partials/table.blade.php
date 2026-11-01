<div class="card border-0 shadow-sm rounded-4 mb-4 kp-owl-table-card">
    <!-- Standardized Card Header with Title and Subtitle (No Dropdown at Top) -->
    <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
        <h4 class="card-title fw-bold text-dark mb-0">KP OWL Records</h4>
        <p class="text-muted small mb-0">Participant records for KP OWL learning programs.</p>
    </div>

    <div class="card-body p-0">
        <!-- Top Synchronized Horizontal Scrollbar -->
        <div class="table-scroll-top-wrapper" id="kp-owl-scroll-top-wrapper">
            <div class="table-scroll-top-inner" id="kp-owl-scroll-top-inner"></div>
        </div>

        <!-- Primary Table Container with Horizontal Scrollbar -->
        <div class="table-scroll-container" id="kp-owl-scroll-container">
            <table id="kp-owl-datatable" class="table table-hover align-middle mb-0 text-nowrap w-100">
                <thead class="bg-light text-muted small text-uppercase kp-owl-thead">
                    <tr>
                        <th class="w-1 text-center" style="min-width: 45px; width: 45px;">NO</th>
                        <th class="text-center" style="min-width: 110px;">EMPLOYEE ID</th>
                        <th style="min-width: 160px;">EMPLOYEE NAME</th>
                        <th style="min-width: 130px;">TEAM</th>
                        <th style="min-width: 140px;">DEPARTMENT</th>
                        <th style="min-width: 130px;">COMPANY</th>
                        <th style="min-width: 220px;">COURSE TITLE</th>
                        <th class="text-center" style="min-width: 100px;">TYPE</th>
                        <th class="text-center" style="min-width: 110px;">CATEGORY</th>
                        <th style="min-width: 140px;">MAIN CLASSIFICATION</th>
                        <th class="text-center" style="min-width: 140px;">SUB CLASSIFICATION</th>
                        <th class="text-center" style="min-width: 120px;">DELIVERY METHOD</th>
                        <th class="text-center" style="min-width: 110px;">PROVIDER</th>
                        <th style="min-width: 130px;">PROVIDER NAME</th>
                        <th style="min-width: 120px;">VENUE</th>
                        <th class="text-center" style="min-width: 110px;">COURSE DURATION</th>
                        <th class="text-center" style="min-width: 130px;">TOTAL RECOGNIZED DURATION</th>
                        <th class="text-center" style="min-width: 110px;">LEARNING HOURS</th>
                        <th class="text-center" style="min-width: 110px;">% PROGRESS</th>
                        <th class="text-center" style="min-width: 110px;">PROGRESS STATUS</th>
                        <th style="min-width: 140px;">LEARNING PERIOD</th>
                        <th class="text-center" style="min-width: 110px;">RECORD MONTH</th>
                        <th class="text-center" style="min-width: 110px;">REQUIREMENT</th>
                        <th class="text-center" style="min-width: 100px;">ATTENDANCE</th>
                        <th class="text-center" style="min-width: 120px;">SCORE (L1) - COURSE</th>
                        <th class="text-center" style="min-width: 90px;">SCORE (L2)</th>
                        <th class="text-center" style="min-width: 90px;">SCORE (L3)</th>
                        <th class="text-center" style="min-width: 130px;">CERTIFICATE AVAILABILITY</th>
                        <th style="min-width: 130px;">PERSON IN CHARGE (PIC)</th>
                        <th class="text-center" style="min-width: 130px;">TOTAL ENGAGEMENT TIME</th>
                        <th class="text-center w-1 sticky-action-col" style="min-width: 80px; width: 80px;">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Populated by server-side AJAX DataTable --}}
                </tbody>
            </table>
        </div>
    </div>

    <!-- Custom Pagination Footer Container with Bottom-Left Show Entries Dropdown -->
    <div class="card-footer bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-3" id="kp-owl-table-footer">
        <!-- Bottom-Left: Show Entries Dropdown & Info Summary side-by-side -->
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="d-flex align-items-center">
                <select id="table-entries-select" class="form-select form-select-sm" style="width: auto; min-width: 125px;">
                    <option value="10">10 Entries</option>
                    <option value="25" selected>25 Entries</option>
                    <option value="50">50 Entries</option>
                    <option value="100">100 Entries</option>
                </select>
            </div>
            <div class="text-muted small" id="kp-owl-table-info-container">
                Showing 0 to 0 of 0 entries
            </div>
        </div>

        <!-- Bottom-Right: Pagination Controls -->
        <div id="kp-owl-table-pagination-container"></div>
    </div>
</div>
