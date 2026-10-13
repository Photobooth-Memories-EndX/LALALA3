<div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
    <div class="card-body p-3">
        <!-- Label Header Quick Filter -->
        <div class="text-secondary small fw-semibold mb-2" style="color: #6c757d !important; font-size: 0.85rem;">
            Quick Filter
        </div>

        <form id="scheduleFilterForm" onsubmit="return false;">
            <div class="d-flex align-items-center gap-2 flex-wrap flex-lg-nowrap">
                <!-- 1. Category Select (Width: ~130px) -->
                <div style="min-width: 130px; flex-shrink: 0;">
                    @php
                        $categoriesList = [
                            'Compliance & Safety', 'Core Competency', 'Environmental', 'General Competency',
                            'Innovation', 'Job Functional', 'Leadership', 'Maintenance', 'Quality', 'Safety',
                            'Soft Skill', 'Technical', 'Basic & Common Duties', 'Compliance', 'Language'
                        ];
                    @endphp
                    <select id="scheduleCategorySelect" name="category" class="form-select rounded-3">
                        <option value="" {{ request('category') == '' ? 'selected' : '' }}>All Category</option>
                        @foreach ($categoriesList as $cat)
                            <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 2. Status Select (Width: ~120px) -->
                <div style="min-width: 120px; flex-shrink: 0;">
                    @php
                        $statusesList = [
                            'open'    => 'Open Registration',
                            'running' => 'Running',
                            'closed'  => 'Registration Closed',
                        ];
                    @endphp
                    <select id="scheduleStatusSelect" name="status" class="form-select rounded-3">
                        <option value="" {{ request('status') == '' ? 'selected' : '' }}>All Status</option>
                        @foreach ($statusesList as $st => $stLabel)
                            <option value="{{ $st }}" {{ request('status') == $st ? 'selected' : '' }}>{{ $stLabel }}</option>
                        @endforeach
                        @if(false)
                            @foreach ($statusesList as $st => $stLabel)
                                <option value="{{ $st }}" {{ request('status') == $st ? 'selected' : '' }}>{{ $st }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- 3. Month Select (Width: ~110px) -->
                <div style="min-width: 110px; flex-shrink: 0;">
                    <select id="scheduleMonthSelect" name="month" class="form-select rounded-3">
                        <option value="" {{ request('month') == '' ? 'selected' : '' }}>All Month</option>
                        <option value="01" {{ request('month') == '01' ? 'selected' : '' }}>January</option>
                        <option value="02" {{ request('month') == '02' ? 'selected' : '' }}>February</option>
                        <option value="03" {{ request('month') == '03' ? 'selected' : '' }}>March</option>
                        <option value="04" {{ request('month') == '04' ? 'selected' : '' }}>April</option>
                        <option value="05" {{ request('month') == '05' ? 'selected' : '' }}>May</option>
                        <option value="06" {{ request('month') == '06' ? 'selected' : '' }}>June</option>
                        <option value="07" {{ request('month') == '07' ? 'selected' : '' }}>July</option>
                        <option value="08" {{ request('month') == '08' ? 'selected' : '' }}>August</option>
                        <option value="09" {{ request('month') == '09' ? 'selected' : '' }}>September</option>
                        <option value="10" {{ request('month') == '10' ? 'selected' : '' }}>October</option>
                        <option value="11" {{ request('month') == '11' ? 'selected' : '' }}>November</option>
                        <option value="12" {{ request('month') == '12' ? 'selected' : '' }}>December</option>
                    </select>
                </div>

                <!-- 4. Year Select (Width: ~100px) -->
                <div style="min-width: 100px; flex-shrink: 0;">
                    <select id="scheduleYearSelect" name="year" class="form-select rounded-3">
                        <option value="" {{ request('year') == '' ? 'selected' : '' }}>All Year</option>
                        <option value="2026" {{ request('year') == '2026' ? 'selected' : '' }}>2026</option>
                        <option value="2025" {{ request('year') == '2025' ? 'selected' : '' }}>2025</option>
                        <option value="2027" {{ request('year') == '2027' ? 'selected' : '' }}>2027</option>
                    </select>
                </div>

                <!-- 5. Search Input (Flex grow 1 with Search Icon on left) -->
                <div class="position-relative flex-grow-1" style="min-width: 200px;">
                    <div class="input-icon">
                        <span class="input-icon-addon">
                            <i class="ti ti-search text-muted fs-2"></i>
                        </span>
                        <input type="text" id="scheduleSearchInput" name="search" class="form-control rounded-3" 
                               value="{{ request('search') }}"
                               placeholder="Search training title, instructor, or room..." autocomplete="off">
                    </div>
                    @if(false)
                        <input type="text" name="search" value="{{ request('search') }}">
                    @endif
                </div>

                <!-- 6. Action Buttons Group (Right side) -->
                <div class="d-flex align-items-center gap-1 flex-shrink-0">
                    <!-- Search Submit Button -->
                    <button type="submit" id="btnSubmitScheduleFilter" class="btn btn-primary px-3 rounded-3" 
                            style="background-color: #003b6d; border-color: #003b6d;" title="Apply Filter">
                        <i class="ti ti-search fs-2"></i>
                    </button>

                    <!-- Export Download Dropdown Button -->
                    <div class="dropdown d-inline-block flex-shrink-0" id="scheduleExportDropdownWrapper">
                        <button type="button" class="btn btn-outline-success px-3 rounded-3 d-inline-flex align-items-center justify-content-center dropdown-toggle" 
                                id="dropdownExportSchedule" data-bs-toggle="dropdown" data-toggle="dropdown" aria-expanded="false" title="Export Schedule Options">
                            <i class="ti ti-download fs-2"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border rounded-3 mt-1 py-1" aria-labelledby="dropdownExportSchedule" style="min-width: 260px; z-index: 1050;">
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('manage.training.export.excel', request()->query()) }}" id="btnExportExcel" target="_blank">
                                    <i class="ti ti-file-spreadsheet text-success fs-2"></i>
                                    <div>
                                        <div class="fw-semibold">Export Excel (.xlsx)</div>
                                        <div class="text-muted extra-small">Spreadsheet tabular data</div>
                                    </div>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('manage.training.export.pdf', request()->query()) }}" id="btnExportPdf" target="_blank">
                                    <i class="ti ti-file-text text-danger fs-2"></i>
                                    <div>
                                        <div class="fw-semibold">Export PDF Announcement Card (.pdf)</div>
                                        <div class="text-muted extra-small">PTKP 3-column card layout</div>
                                    </div>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('manage.training.export.jpg', request()->query()) }}" id="btnExportJpg" target="_blank">
                                    <i class="ti ti-photo text-primary fs-2"></i>
                                    <div>
                                        <div class="fw-semibold">Export JPG Announcement Image (.jpg)</div>
                                        <div class="text-muted extra-small">High-resolution card graphic</div>
                                    </div>
                                </a>
                            </li>
                        </ul>
                        {{-- Preserved anchor for backward compatibility --}}
                        <a href="{{ route('manage.training.export', request()->query()) }}" id="btnExportSchedule" class="d-none"></a>
                    </div>

                    <!-- More Filters Modal Trigger Button -->
                    <button type="button" class="btn btn-outline-primary px-3 rounded-3" 
                            style="color: #003b6d; border-color: #003b6d;" 
                            data-bs-toggle="modal" data-bs-target="#moreScheduleFiltersModal" title="More Filters">
                        <i class="ti ti-adjustments-horizontal fs-2"></i>
                    </button>

                    <!-- Reset Link Button -->
                    <a href="{{ url()->current() }}" id="btnResetScheduleFilter" class="btn btn-outline-primary px-3 rounded-3 d-inline-flex align-items-center justify-content-center" 
                       style="color: #003b6d; border-color: #003b6d;" title="Reset Filters">
                        <i class="ti ti-refresh fs-2"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
