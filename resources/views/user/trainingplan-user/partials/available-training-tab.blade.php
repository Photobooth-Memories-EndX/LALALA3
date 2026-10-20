@php
    $availableTrainings = $availableTrainings ?? \App\Models\OfflineTraining::withCount('participants')->latest()->paginate(10)->appends(array_merge(request()->query(), ['tab' => 'available']));
@endphp
{{-- Tab Training Tersedia / Available Training (Table View Layout) --}}
<style>
    .btn-owl-register {
        color: #003b6d !important;
        border-color: #003b6d !important;
        background-color: transparent !important;
        transition: all 0.2s ease-in-out;
    }
    .btn-owl-register:hover {
        color: #ffffff !important;
        background-color: #003b6d !important;
        border-color: #003b6d !important;
        box-shadow: 0 4px 6px -1px rgba(0, 59, 109, 0.2);
    }
    .bg-purple-subtle {
        background-color: #f3e8ff !important;
    }
    .text-purple {
        color: #6f42c1 !important;
    }
    .font-semibold {
        font-weight: 600 !important;
    }
    .text-gray-900 {
        color: #111827 !important;
    }
    .text-gray-500 {
        color: #6b7280 !important;
    }
    .text-gray-400 {
        color: #9ca3af !important;
    }
    .text-sm {
        font-size: 0.875rem !important;
        line-height: 1.25rem !important;
    }
    .text-xs {
        font-size: 0.75rem !important;
        line-height: 1rem !important;
    }
    .mt-0\.5 {
        margin-top: 0.125rem !important;
    }
</style>
<div class="card border border-light-subtle shadow-sm rounded-4 bg-white p-4 mb-4">
    
    {{-- Quick Filter Bar matching schedule-filter.blade.php --}}
    <div class="mb-4 pb-3 border-bottom border-light-subtle">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="text-secondary small fw-semibold" style="color: #6c757d !important; font-size: 0.85rem;">
                Quick Filter
            </div>
            <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill fw-medium" id="availableTrainingTotalBadge">
                Total: {{ method_exists($availableTrainings, 'total') ? $availableTrainings->total() : $availableTrainings->count() }} Training{{ (method_exists($availableTrainings, 'total') ? $availableTrainings->total() : $availableTrainings->count()) === 1 ? '' : 's' }}
            </span>
        </div>

        <form id="availableFilterForm" method="GET" action="{{ route('trainingplan.user.index') }}">
            <input type="hidden" name="tab" value="available">
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
                    <select id="filterTrainingCategory" name="category" class="form-select rounded-3" onchange="this.form.submit()">
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
                    <select id="filterTrainingStatus" name="status" class="form-select rounded-3" onchange="this.form.submit()">
                        <option value="" {{ request('status') == '' ? 'selected' : '' }}>All Status</option>
                        @foreach ($statusesList as $st => $stLabel)
                            <option value="{{ $st }}" {{ request('status') == $st || request('status') == $stLabel ? 'selected' : '' }}>{{ $stLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 3. Month Select (Width: ~110px) -->
                <div style="min-width: 110px; flex-shrink: 0;">
                    @php
                        $monthsList = [
                            '01' => 'January', '02' => 'February', '03' => 'March',
                            '04' => 'April', '05' => 'May', '06' => 'June',
                            '07' => 'July', '08' => 'August', '09' => 'September',
                            '10' => 'October', '11' => 'November', '12' => 'December'
                        ];
                    @endphp
                    <select id="filterTrainingMonth" name="month" class="form-select rounded-3" onchange="this.form.submit()">
                        <option value="" {{ request('month') == '' ? 'selected' : '' }}>All Month</option>
                        @foreach ($monthsList as $m => $monthName)
                            <option value="{{ $m }}" {{ ((int) request('month') == (int) $m && request('month') != '') || request('month') == $m ? 'selected' : '' }}>{{ $monthName }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 4. Year Select (Width: ~100px) -->
                <div style="min-width: 100px; flex-shrink: 0;">
                    @php
                        $yearsList = ['2026', '2025', '2027'];
                    @endphp
                    <select id="filterTrainingYear" name="year" class="form-select rounded-3" onchange="this.form.submit()">
                        <option value="" {{ request('year') == '' ? 'selected' : '' }}>All Year</option>
                        @foreach ($yearsList as $y)
                            <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 5. Search Input (Flex grow 1 with Search Icon on left) -->
                <div class="position-relative flex-grow-1" style="min-width: 200px;">
                    <div class="input-icon">
                        <span class="input-icon-addon">
                            <i class="ti ti-search text-muted fs-2"></i>
                        </span>
                        <input type="text" id="filterTrainingSearch" name="search" class="form-control rounded-3" 
                               value="{{ request('search') ?? request('q') }}"
                               placeholder="Search training title, instructor, or room..." autocomplete="off">
                    </div>
                </div>

                <!-- 6. Action Buttons Group (Right side) -->
                <div class="d-flex align-items-center gap-1 flex-shrink-0">
                    <!-- Search Submit Button -->
                    <button type="submit" id="btnSubmitAvailableFilter" class="btn btn-primary px-3 rounded-3" 
                            style="background-color: #003b6d; border-color: #003b6d;" title="Apply Filter">
                        <i class="ti ti-search fs-2"></i>
                    </button>

                    <!-- Reset Link Button -->
                    <a href="{{ route('trainingplan.user.index', ['tab' => 'available']) }}" id="btnResetFilter" class="btn btn-outline-primary px-3 rounded-3 d-inline-flex align-items-center justify-content-center" 
                       style="color: #003b6d; border-color: #003b6d;" title="Reset Filters">
                        <i class="ti ti-refresh fs-2"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- Available Training Table --}}
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-hover align-middle mb-0" id="availableTrainingTable">
            <thead>
                <tr class="text-muted extra-small text-uppercase fw-bold" style="letter-spacing: 0.05em; background-color: #f8fafc;">
                    <th class="w-1 text-center align-middle py-3">NO</th>
                    <th class="text-start align-middle py-3" style="min-width: 200px;">TRAINING NAME</th>
                    <th class="text-center align-middle py-3" style="min-width: 140px;">DATE</th>
                    <th class="text-center align-middle py-3" style="min-width: 120px;">TIME</th>
                    <th class="text-center align-middle py-3">LOCATION</th>
                    <th class="text-center align-middle py-3">SYLLABUS</th>
                    <th class="text-center align-middle py-3">APPROVED PARTICIPANT</th>
                    <th class="text-center align-middle py-3">STATUS</th>
                    <th class="text-center align-middle py-3 pe-4">ACTION</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($availableTrainings as $item)
                    @php
                        $training = $item;
                        $title = $training->title ?? ($training->course_name ?? 'Training Schedule');
                        $enrolled = $item->registered_seats_count ?? ($item->relationLoaded('registrations') ? $item->registrations->whereIn('status', ['Pending', 'Approved', 'Completed'])->count() : 0);
                        $quota = $item->quota > 0 ? $item->quota : 1;
                        $remaining = max(0, $quota - $enrolled);
                        $percent = min(100, round(($enrolled / $quota) * 100));
                        $isFull = $remaining <= 0;
                        $barColor = $isFull ? '#ef4444' : ($remaining <= 10 ? '#f97316' : '#003b6d');

                        $myReg = $item->relationLoaded('registrations') ? $item->registrations->firstWhere('user_id', Auth::id()) : null;
                        $myRegStatus = $myReg ? $myReg->status : null;

                        $regCloseDate = \Carbon\Carbon::parse($item->registration_close_date ?? $item->start_date);
                        $regCloseTime = $item->registration_close_time ? substr($item->registration_close_time, 0, 5) : '23:59';
                        $closeDatetime = \Carbon\Carbon::parse($regCloseDate->format('Y-m-d') . ' ' . $regCloseTime . ':59');
                        $isRegDeadlinePassed = \Carbon\Carbon::now()->gt($closeDatetime);
                        $displayStatus = ($item->status === 'Open Registration' && $isRegDeadlinePassed) ? 'Closed' : $item->status;

                        $statusBadgeClass = match($displayStatus) {
                            'Open Registration' => 'bg-success-subtle text-success',
                            'Running'           => 'bg-warning-subtle text-warning border border-warning-subtle',
                            'Closed'            => 'bg-secondary-subtle text-secondary',
                            default             => 'bg-info-subtle text-info',
                        };

                        $dataStatus = match($displayStatus) {
                            'Open Registration' => 'open',
                            'Running'           => 'running',
                            'Closed'            => 'closed',
                            default             => strtolower($displayStatus ?? 'open'),
                        };

                        $startDate = \Carbon\Carbon::parse($item->start_date);
                        $startTime = substr($item->start_time, 0, 5);
                        $endTime = substr($item->end_time, 0, 5);
                    @endphp
                    <tr class="training-table-row" 
                        data-id="{{ $item->id }}" 
                        data-category="{{ $item->category }}" 
                        data-status="{{ $dataStatus }}" 
                        data-month="{{ (int)$startDate->format('n') }}" 
                        data-year="{{ $startDate->format('Y') }}"
                        data-location="{{ $item->location }}"
                        data-instructor="{{ $item->instructor }}">
                        <td class="text-center align-middle text-muted fw-semibold">{{ method_exists($availableTrainings, 'firstItem') && $availableTrainings->firstItem() ? ($availableTrainings->firstItem() + $loop->index) : $loop->iteration }}</td>
                        <td class="align-middle" style="min-width: 200px;">
                            <div class="font-semibold text-gray-900 text-sm">{{ $training->title }}</div>
                            @if($training->sub_course_name)
                                <div class="text-xs text-gray-500">{{ $training->sub_course_name }}</div>
                            @endif
                            <div class="text-xs text-gray-400 mt-0.5">Code: {{ $training->batch ?? '-' }}</div>
                        </td>
                        <td class="fw-semibold text-dark text-center align-middle" style="min-width: 140px;">
                            @if($item->end_date && $item->start_date != $item->end_date)
                                @if(\Carbon\Carbon::parse($item->start_date)->format('M Y') == \Carbon\Carbon::parse($item->end_date)->format('M Y'))
                                    {{ \Carbon\Carbon::parse($item->start_date)->format('d') }} - {{ \Carbon\Carbon::parse($item->end_date)->format('d M Y') }}
                                @else
                                    {{ \Carbon\Carbon::parse($item->start_date)->format('d M') }} - {{ \Carbon\Carbon::parse($item->end_date)->format('d M Y') }}
                                @endif
                            @else
                                {{ \Carbon\Carbon::parse($item->start_date)->format('d M Y') }}
                            @endif
                        </td>
                        <td class="text-center align-middle" style="min-width: 120px;">
                            <div class="text-muted small">{{ $startTime }} - {{ $endTime }} WIB</div>
                        </td>
                        <td class="text-center align-middle">
                            <span class="text-muted small">{{ $item->location }}</span>
                        </td>
                        <td class="text-center align-middle">
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-inline-flex align-items-center gap-1 btn-view-syllabus" 
                                    data-bs-toggle="modal" data-bs-target="#syllabusModal" 
                                    data-id="{{ $item->id }}" 
                                    data-title="{{ $title }}" 
                                    data-category="{{ $item->category }}" 
                                    data-instructor="{{ $item->instructor }}"
                                    data-duration="{{ $startTime }} - {{ $endTime }} WIB"
                                    data-start-time="{{ $startTime }}"
                                    data-end-time="{{ $endTime }}"
                                    data-duration-hours="{{ \Carbon\Carbon::parse($item->start_time)->diffInHours(\Carbon\Carbon::parse($item->end_time)) . ' Hours' }}"
                                    data-requirements="{{ $item->requirements ?? ($item->prerequisites ?? '') }}"
                                    data-target="{{ $item->target_employee ?? '' }}"
                                    data-description="{{ $item->description ?? '' }}"
                                    data-objectives="{{ $item->learning_objectives ?? '' }}"
                                    data-syllabus="{{ json_encode($item->syllabus ?? []) }}">
                                <i class="ti ti-list-check fs-2"></i>
                                <span>Syllabus</span>
                            </button>
                        </td>
                        <td class="text-center align-middle" style="min-width: 130px;">
                            <div class="fw-semibold text-dark small">{{ $enrolled }} / {{ $item->quota }} Seats {{ $isFull ? '(Full)' : '' }}</div>
                            <div class="progress progress-xs mt-1 mx-auto rounded-pill" style="height: 4px; max-width: 90px; background-color: #e2e8f0;">
                                <div class="progress-bar rounded-pill" role="progressbar" style="width: {{ $percent }}%; background-color: {{ $barColor }};" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </td>
                        <td class="text-center align-middle">
                            <span class="badge {{ $statusBadgeClass }} rounded-pill px-3 py-1 fw-semibold">
                                {{ $displayStatus }}
                            </span>
                        </td>
                        <!-- <td class="text-center align-middle pe-4">
                            @if ($myRegStatus === 'Pending')
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-1-5 fw-semibold d-inline-flex align-items-center gap-1">
                                    <i class="ti ti-clock"></i>
                                    <span>Pending Approval</span>
                                </span>
                            @elseif ($myRegStatus === 'Approved')
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1-5 fw-semibold d-inline-flex align-items-center gap-1">
                                    <i class="ti ti-check"></i>
                                    <span>Approved</span>
                                </span>
                            @elseif ($myRegStatus === 'Completed')
                                <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-3 py-1-5 fw-semibold d-inline-flex align-items-center gap-1">
                                    <i class="ti ti-certificate"></i>
                                    <span>Completed</span>
                                </span>
                            @elseif ($myRegStatus === 'Rejected')
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1-5 fw-semibold d-inline-flex align-items-center gap-1" title="{{ $myReg->rejection_reason ?? 'Registration was rejected' }}">
                                    <i class="ti ti-x"></i>
                                    <span>Rejected</span>
                                </span>
                            @elseif ($item->status === 'Open Registration' && !$isFull)
                                <form action="{{ route('trainingplan.register', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Register for {{ addslashes($title) }}?');">
                                    @csrf
                                    <button type="submit" class="btn btn-owl-register rounded-pill px-4 py-1-5 fw-semibold d-inline-flex align-items-center gap-1">
                                        <i class="ti ti-pencil-plus"></i>
                                        <span>Register</span>
                                    </button>
                                </form>
                            @elseif ($item->status === 'Running')
                                <button type="button" class="btn btn-secondary rounded-pill px-4 py-1-5 fw-semibold" disabled>
                                    <span>Running</span>
                                </button>
                            @else
                                <button type="button" class="btn btn-secondary rounded-pill px-4 py-1-5 fw-semibold opacity-75" disabled>
                                    <span>Full</span>
                                </button>
                            @endif
                        </td> -->
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <div class="avatar avatar-xl rounded-circle bg-light text-muted mb-3 mx-auto">
                                <i class="ti ti-calendar-off fs-1"></i>
                            </div>
                            <h4 class="fw-bold text-dark mb-1">No Training Schedules Available</h4>
                            <p class="text-muted small mb-0">No active training sessions open for registration right now. Click '+ Add Training Schedule' in Manage page to publish new trainings.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if (method_exists($availableTrainings, 'hasPages') && $availableTrainings->hasPages())
        <div class="card-footer bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="text-muted small">
                Showing {{ $availableTrainings->firstItem() ?? 0 }} to {{ $availableTrainings->lastItem() ?? 0 }} of {{ $availableTrainings->total() }} available training(s)
            </div>
            <div>
                {{ $availableTrainings->appends(array_merge(request()->query(), ['tab' => 'available']))->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @elseif (method_exists($availableTrainings, 'total') && $availableTrainings->total() > 0)
        <div class="card-footer bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="text-muted small">
                Showing {{ $availableTrainings->firstItem() ?? 0 }} to {{ $availableTrainings->lastItem() ?? 0 }} of {{ $availableTrainings->total() }} available training(s)
            </div>
        </div>
    @endif
</div>