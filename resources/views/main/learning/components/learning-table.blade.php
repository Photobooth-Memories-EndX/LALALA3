@php
    $isAllRecord = $isAllRecord ?? false;
@endphp
<!-- LEARNING RECORDS TABLE CARD -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h4 class="card-title fw-bold text-dark mb-0">{{ $cardTitle ?? 'Learning Records' }}</h4>
            <p class="text-muted small mb-0">{{ $cardSubtitle ?? 'Participant records for this category.' }}</p>
        </div>

        <!-- Length Menu / Entries Selector -->
        <div class="d-flex align-items-center gap-2">
            <label for="table-entries-select" class="text-muted small mb-0">Show</label>
            <select id="table-entries-select" class="form-select form-select-sm" style="width: 80px;">
                <option value="10" {{ ($entries ?? 25) == 10 ? 'selected' : '' }}>10</option>
                <option value="25" {{ ($entries ?? 25) == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ ($entries ?? 25) == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ ($entries ?? 25) == 100 ? 'selected' : '' }}>100</option>
            </select>
            <span class="text-muted small">entries</span>
        </div>
    </div>

    <div class="card-body p-0">
        <!-- Top Synchronized Horizontal Scrollbar -->
        <div class="table-scroll-top-wrapper" id="table-scroll-top-wrapper">
            <div class="table-scroll-top-inner" id="table-scroll-top-inner"></div>
        </div>

        <!-- Primary Table Container with Horizontal Scrollbar -->
        <div class="table-scroll-container" id="table-scroll-container">
            <table class="table table-hover align-middle mb-0" id="learning-table">
                <thead class="bg-light text-muted small text-uppercase">
                    <tr>
                        <th class="text-center" style="min-width: 45px; width: 45px;">NO</th>
                        @if ($isAllRecord)
                        <th class="text-center" style="min-width: 95px;">SOURCE</th>
                        @endif
                        <th class="text-center" style="min-width: 110px;">EMPLOYEE ID</th>
                        <th style="min-width: 160px;">EMPLOYEE NAME</th>
                        <th style="min-width: 130px;">TEAM</th>
                        <th style="min-width: 140px;">DEPARTMENT</th>
                        <th style="min-width: 130px;">COMPANY</th>
                        <th style="min-width: 220px;">COURSE TITLE</th>
                        <th class="text-center" style="min-width: 100px;">TYPE</th>
                        <th style="min-width: 110px;">CATEGORY</th>
                        <th style="min-width: 140px;">MAIN CLASSIFICATION</th>
                        <th class="text-center" style="min-width: 140px;">SUB CLASSIFICATION</th>
                        <th class="text-center" style="min-width: 120px;">DELIVERY METHOD</th>
                        <th style="min-width: 130px;">SUB DELIVERY METHOD</th>
                        <th style="min-width: 130px;">PROVIDER NAME</th>
                        <th style="min-width: 120px;">VENUE</th>
                        <th class="text-center" style="min-width: 110px;">COURSE DURATION</th>
                        <th class="text-center" style="min-width: 130px;">RECOGNIZED DURATION</th>
                        <th class="text-center" style="min-width: 110px;">LEARNING HOURS</th>
                        <th class="text-center" style="min-width: 110px;">% PROGRESS</th>
                        <th class="text-center" style="min-width: 110px;">PROGRESS STATUS</th>
                        <th style="min-width: 140px;">DATE TIME</th>
                        <th class="text-center" style="min-width: 110px;">RECORD MONTH</th>
                        <th class="text-center" style="min-width: 110px;">REQUIREMENT</th>
                        <th class="text-center" style="min-width: 100px;">ATTENDANCE</th>
                        <th class="text-center" style="min-width: 120px;">SCORE (L1) - COURSE</th>
                        <th class="text-center" style="min-width: 90px;">SCORE (L2)</th>
                        <th class="text-center" style="min-width: 90px;">SCORE (L3)</th>
                        <th style="min-width: 130px;">PERSON IN CHARGE (PIC)</th>
                        <th class="text-center" style="min-width: 130px;">{{ $isAllRecord ? 'TOTAL ENGAGEMENT TIME (LH)' : 'TOTAL ENGAGEMENT TIME' }}</th>
                        <th class="text-end" style="min-width: 80px; width: 80px;">ACTION</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- Custom Pagination Footer Container -->
    <div class="card-footer bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2" id="learning-table-footer">
        <div class="text-muted small" id="learning-table-info-container">
            Showing 0 to 0 of 0 entries
        </div>
        <div id="learning-table-pagination-container"></div>
    </div>
</div>
