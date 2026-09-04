@php
    $currentStatus = request('status', 'all');
    $currentYear = request('year', 'all');
    $currentEntries = (int) ($entries ?? 25);
    $currentEmployeeId = request('employee_id') ?? request('user_id');
    $currentEmployeeText = request('employee_text') ?? ($currentEmployeeId ? ($employeeName ?? $currentEmployeeId) : '');
    $currentLearningTitle = request('learning_title') ?? request('title');
    $routeSlug = request()->segment(4);
@endphp

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <!-- Label Header Quick Filter -->
        <div class="text-secondary small fw-semibold mb-2" style="color: #6c757d !important; font-size: 0.85rem;">
            Quick Filter
        </div>

        <form id="learning-filter-form" action="{{ url()->current() }}" method="GET">
            @csrf
            <input type="hidden" id="classification-slug" name="classification_slug" value="{{ request()->routeIs('learning.manage.category') ? $routeSlug : (request('classification_slug') ?? '') }}">
            <input type="hidden" id="content-slug" name="content_slug" value="{{ request()->routeIs('learning.manage.content') ? $routeSlug : (request('content_slug') ?? '') }}">
            <input type="hidden" name="downloadslug" value="{{ $downloadslug ?? '' }}">

            <!-- Hidden inputs from More Filters Modal to preserve in quick filter submit/export -->
            <input type="hidden" id="filter-from-date" name="from_date" value="{{ request('from_date') ?? request('date_from') }}">
            <input type="hidden" id="filter-to-date" name="to_date" value="{{ request('to_date') ?? request('date_to') }}">
            <input type="hidden" id="filter-program-type" name="program_type" value="{{ request('program_type') ?? (request('program') ?? 'all') }}">
            <input type="hidden" id="filter-progress-range" name="progress_range" value="{{ request('progress_range', 'all') }}">
            <input type="hidden" id="filter-duration" name="duration" value="{{ request('duration', 'all') }}">

            <div class="d-flex align-items-center gap-2 flex-nowrap overflow-x-auto pb-1" style="scrollbar-width: thin;">
                <!-- 1. Employee Search Dropdown (Select2 AJAX) -->
                <div class="flex-fill" style="min-width: 180px; max-width: 240px;">
                    <select class="form-select" id="learn-employee" name="employee_id" data-placeholder="Search employee or NIP...">
                        @if ($currentEmployeeId)
                            <option value="{{ $currentEmployeeId }}" selected>{{ $currentEmployeeText ?: $currentEmployeeId }}</option>
                        @endif
                    </select>
                </div>

                <!-- 2. Learning Title Search Dropdown (Select2 AJAX) -->
                <div class="flex-fill" style="min-width: 180px; max-width: 240px;">
                    <select class="form-select" id="learn-title" name="learning_title" data-placeholder="Search learning title...">
                        @if ($currentLearningTitle)
                            <option value="{{ $currentLearningTitle }}" selected>{{ $currentLearningTitle }}</option>
                        @endif
                    </select>
                </div>

                <!-- 3. Status Dropdown -->
                <div style="min-width: 120px; flex-shrink: 0;">
                    <select class="form-select rounded-3" id="learn-status" name="status">
                        <option value="all" {{ $currentStatus === 'all' ? 'selected' : '' }}>All Status</option>
                        <option value="finished" {{ ($currentStatus === 'finished' || $currentStatus === 'finish' || $currentStatus === '1') ? 'selected' : '' }}>Finished</option>
                        <option value="ongoing" {{ ($currentStatus === 'ongoing' || $currentStatus === '0') ? 'selected' : '' }}>Ongoing</option>
                        <option value="not_started" {{ $currentStatus === 'not_started' ? 'selected' : '' }}>Not Started</option>
                    </select>
                </div>

                <!-- 4. Year Dropdown -->
                <div style="min-width: 105px; flex-shrink: 0;">
                    <select class="form-select rounded-3" id="learn-year" name="year">
                        <option value="all" {{ $currentYear === 'all' ? 'selected' : '' }}>All Year</option>
                        @foreach ($years as $year)
                            <option value="{{ $year }}" {{ (string) $currentYear === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 5. Entries Dropdown -->
                <div style="min-width: 115px; flex-shrink: 0;">
                    <select class="form-select rounded-3" id="learn-entries" name="entries">
                        @foreach ([10, 25, 50, 100] as $option)
                            <option value="{{ $option }}" {{ $currentEntries === $option ? 'selected' : '' }}>{{ $option }} Entries</option>
                        @endforeach
                    </select>
                </div>

                <!-- 6. Icon Button: Apply Filter -->
                <button type="button" id="btn-apply-filter" class="btn btn-primary btn-icon px-3 flex-shrink-0" data-bs-toggle="tooltip" data-bs-title="Apply Filter" title="Apply Filter">
                    <i class="ti ti-search fs-2"></i>
                </button>

                <!-- 7. Icon Button: More Filters -->
                <button type="button" id="btn-more-filters" class="btn btn-outline-primary btn-icon px-3 position-relative btn-more-filters flex-shrink-0" data-bs-toggle="modal" data-bs-target="#moreLearningFiltersModal" title="More Filters">
                    <i class="ti ti-adjustments-horizontal fs-2"></i>
                    <span id="activeFilterBadge" class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle d-none">0</span>
                </button>

                <!-- 8. Icon Button: Reset -->
                <button type="button" id="btn-reset-filter" class="btn btn-outline-secondary btn-icon px-3 btn-quick-reset flex-shrink-0" data-bs-toggle="tooltip" data-bs-title="Reset Filters" title="Reset Filters">
                    <i class="ti ti-refresh fs-2"></i>
                </button>

                <!-- 9. Button: Download -->
                <button type="button" id="btn-download-export" class="btn btn-success rounded-3 px-3 btn-download flex-shrink-0" title="Download Excel">
                    <i class="ti ti-download me-1"></i> Download
                </button>
            </div>
        </form>
    </div>
</div>

<!-- More Filters Modal -->
<div class="modal fade" id="moreLearningFiltersModal" tabindex="-1" aria-labelledby="moreLearningFiltersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <form id="more-learning-filters-modal-form" onsubmit="return false;">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="moreLearningFiltersModalLabel">
                        <i class="ti ti-adjustments-horizontal text-primary"></i> More Filters
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <!-- Date Range (from_date & to_date) -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small"><i class="ti ti-calendar me-1"></i> Date Range (Learning Date)</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small text-muted mb-1">From Date</label>
                                <input type="text" id="modal-from-date" name="from_date" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'" class="form-control rounded-3" placeholder="dd/mm/yyyy" value="{{ request('from_date') ?? request('date_from') }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted mb-1">To Date</label>
                                <input type="text" id="modal-to-date" name="to_date" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'" class="form-control rounded-3" placeholder="dd/mm/yyyy" value="{{ request('to_date') ?? request('date_to') }}">
                            </div>
                        </div>
                    </div>

                    <!-- Learning Program (program_type) -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small d-block">Learning Program</label>
                        @php $currProgram = request('program_type') ?? (request('program') ?? 'all'); @endphp
                        <div class="d-flex flex-wrap gap-3 align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_program_type" id="progAll" value="all" {{ ($currProgram === 'all' || blank($currProgram)) ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="progAll">All Program</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_program_type" id="progMandatory" value="mandatory" {{ $currProgram === 'mandatory' ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="progMandatory">Mandatory Learning</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_program_type" id="progOptional" value="optional" {{ $currProgram === 'optional' ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="progOptional">Optional Learning</label>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Range (progress_range) -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small d-block">Progress Range</label>
                        @php $currProgress = request('progress_range', 'all'); @endphp
                        <div class="d-flex flex-wrap gap-3 align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_progress_range" id="prAll" value="all" {{ ($currProgress === 'all' || blank($currProgress)) ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="prAll">All</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_progress_range" id="pr0" value="0" {{ $currProgress === '0' ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="pr0">0%</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_progress_range" id="pr25" value="1-25" {{ $currProgress === '1-25' ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="pr25">1 - 25%</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_progress_range" id="pr50" value="26-50" {{ $currProgress === '26-50' ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="pr50">26 - 50%</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_progress_range" id="pr75" value="51-75" {{ $currProgress === '51-75' ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="pr75">51 - 75%</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_progress_range" id="pr99" value="76-99" {{ $currProgress === '76-99' ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="pr99">76 - 99%</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_progress_range" id="pr100" value="100" {{ $currProgress === '100' ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="pr100">100%</label>
                            </div>
                        </div>
                    </div>

                    <!-- Duration (duration) -->
                    <div class="mb-2">
                        <label class="form-label fw-bold text-dark small d-block">Duration</label>
                        @php $currDur = request('duration', 'all'); @endphp
                        <div class="d-flex flex-wrap gap-3 align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_duration" id="durAll" value="all" {{ ($currDur === 'all' || blank($currDur)) ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="durAll">All Durations</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_duration" id="dur10" value="lt10" {{ ($currDur === 'lt10' || $currDur === 'under_10' || $currDur === '< 10 Minutes') ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="dur10">&lt; 10 Minutes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_duration" id="dur30" value="10-30" {{ ($currDur === '10-30' || $currDur === '10_30' || $currDur === '10 - 30 Minutes') ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="dur30">10 - 30 Minutes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_duration" id="dur60" value="30-60" {{ ($currDur === '30-60' || $currDur === '30_60' || $currDur === '30 - 60 Minutes') ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="dur60">30 - 60 Minutes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modal_duration" id="durAbove60" value="gt60" {{ ($currDur === 'gt60' || $currDur === 'above_60' || $currDur === '> 1 Hour') ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="durAbove60">&gt; 1 Hour</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light d-flex justify-content-end align-items-center gap-2">
                    <!-- Reset Button (Placed on the left of Apply Filter) -->
                    <button type="button" id="btn-modal-reset" class="btn btn-outline-secondary px-3 d-inline-flex align-items-center gap-1" data-bs-dismiss="modal">
                        <i class="ti ti-rotate me-1"></i>
                        <span>Reset</span>
                    </button>

                    <!-- Apply Filter Button -->
                    <button type="button" id="btn-modal-apply" class="btn btn-primary px-3 d-inline-flex align-items-center gap-1" style="background-color: #0c5b9e; border-color: #0c5b9e;" data-bs-dismiss="modal">
                        <i class="ti ti-check me-1"></i>
                        <span>Apply Filter</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>