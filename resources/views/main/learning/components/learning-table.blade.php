@php
    $isAllRecord = $isAllRecord ?? false;
@endphp
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive learning-table-responsive">
            <table id="learning-table" class="table table-vcenter table-striped card-table w-100">
                <thead>
                    <tr>
                        <th class="text-center" style="min-width: 45px; width: 45px;">NO</th>
                        @if ($isAllRecord)
                        <th class="text-center" style="min-width: 90px;">SOURCE</th>
                        @endif
                        <th class="text-center" style="min-width: 90px;">EMPLOYEE ID</th>
                        <th style="min-width: 180px;">EMPLOYEE NAME</th>
                        <th style="min-width: 140px;">TEAM</th>
                        <th style="min-width: 160px;">DEPARTMENT</th>
                        <th style="min-width: 160px;">COMPANY</th>
                        <th style="min-width: 220px;">COURSE TITLE</th>
                        <th class="text-center" style="min-width: 100px;">TYPE</th>
                        <th style="min-width: 150px;">CATEGORY</th>
                        <th style="min-width: 160px;">MAIN CLASSIFICATION</th>
                        <th style="min-width: 150px;">SUB CLASSIFICATION</th>
                        <th class="text-center" style="min-width: 120px;">DELIVERY METHOD</th>
                        <th style="min-width: 140px;">SUB DELIVERY METHOD</th>
                        <th style="min-width: 150px;">PROVIDER NAME</th>
                        <th style="min-width: 140px;">VENUE</th>
                        <th class="text-center" style="min-width: 110px;">COURSE DURATION</th>
                        <th class="text-center" style="min-width: 110px;">LEARNING HOURS</th>
                        <th class="text-center" style="min-width: 120px;">% PROGRESS</th>
                        <th class="text-center" style="min-width: 110px;">PROGRESS STATUS</th>
                        <th style="min-width: 170px;">DATE TIME</th>
                        <th class="text-center" style="min-width: 110px;">RECORD MONTH</th>
                        <th class="text-center" style="min-width: 110px;">REQUIREMENT</th>
                        <th class="text-center" style="min-width: 95px;">ATTENDANCE</th>
                        <th class="text-center" style="min-width: 130px;">SCORE (L1) - COURSE</th>
                        <th class="text-center" style="min-width: 105px;">SCORE (L2)</th>
                        <th class="text-center" style="min-width: 105px;">SCORE (L3)</th>
                        <th style="min-width: 140px;">PERSON IN CHARGE (PIC)</th>
                        <th class="text-center" style="min-width: 140px;">{{ $isAllRecord ? 'TOTAL ENGAGEMENT TIME (LH)' : 'TOTAL ENGAGEMENT TIME' }}</th>
                        <th class="text-center" style="min-width: 80px; width: 80px;">ACTION</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div class="card-footer d-flex flex-wrap align-items-center justify-content-between p-3 border-top">
            <div class="d-flex align-items-center gap-2">
                <select class="form-select form-select-sm w-auto rounded-3" id="table-entries-select">
                    <option value="10" {{ ($entries ?? 25) == 10 ? 'selected' : '' }}>10 Entries</option>
                    <option value="25" {{ ($entries ?? 25) == 25 ? 'selected' : '' }}>25 Entries</option>
                    <option value="50" {{ ($entries ?? 25) == 50 ? 'selected' : '' }}>50 Entries</option>
                    <option value="100" {{ ($entries ?? 25) == 100 ? 'selected' : '' }}>100 Entries</option>
                </select>
                <div id="learning-table-info-container"></div>
            </div>
            <div id="learning-table-pagination-container"></div>
        </div>
    </div>
</div>
