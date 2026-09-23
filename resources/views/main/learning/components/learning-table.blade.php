<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="learning-table" class="table table-vcenter table-striped card-table w-100">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">NO</th>
                        <th class="text-center">STATUS</th>
                        <th class="text-center">ID</th>
                        <th>EMPLOYEE</th>
                        <th>LEARNING TITLE</th>
                        <th class="text-center">TYPE</th>
                        <th>CLASSIFICATION</th>
                        <th class="text-center">CATEGORY</th>
                        <th class="text-center">DURATION</th>
                        <th class="text-center">DATE</th>
                        <th class="text-center">PROGRESS</th>
                        <th class="text-center" style="width: 100px;">ACTION</th>
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