@extends('layouts.sidebar-master')

@section('content')
<div class="page-body">
    <div class="container-xl">
        {{-- Header Section with Back Navigation & Hero Card --}}
        @include('main.manage-training-schedule.components.schedule-header')

        @if (session('success'))
            <div class="alert alert-success alert-dismissible rounded-4 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-check fs-2 text-success"></i>
                    <div class="fw-semibold">{{ session('success') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning alert-dismissible rounded-4 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-alert-triangle fs-2 text-warning"></i>
                    <div class="fw-semibold">{{ session('warning') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible rounded-4 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-alert-circle fs-2 text-danger"></i>
                    <div class="fw-semibold">{{ session('error') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Top Navigation Toggle Pills --}}
        <div class="mb-4">
            <ul class="nav nav-pills gap-2 bg-white p-1-5 rounded-4 shadow-sm border border-light-subtle d-inline-flex" id="scheduleMainTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold px-4 py-2 rounded-3 d-inline-flex align-items-center gap-2" 
                            id="tab-manage-schedules-btn" data-bs-toggle="pill" data-bs-target="#tab-manage-schedules-pane" type="button" role="tab" aria-selected="true">
                        <i class="ti ti-calendar-event fs-2"></i>
                        <span>Manage Schedules</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold px-4 py-2 rounded-3 d-inline-flex align-items-center gap-2" 
                            id="tab-participant-tracking-btn" data-bs-toggle="pill" data-bs-target="#tab-participant-tracking-pane" type="button" role="tab" aria-selected="false">
                        <i class="ti ti-users-group fs-2"></i>
                        <span>Participant Tracking & Approvals</span>
                    </button>
                </li>
            </ul>
        </div>

        {{-- Main Tab Panes --}}
        <div class="tab-content" id="scheduleMainTabsContent">
            {{-- PANE 1: MANAGE SCHEDULES --}}
            <div class="tab-pane fade show active" id="tab-manage-schedules-pane" role="tabpanel">
                {{-- Quick Filter Bar --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                    <div class="card-body p-3">
                        <!-- Label Header Quick Filter -->
                        <div class="text-secondary small fw-semibold mb-2" style="color: #6c757d !important; font-size: 0.85rem;">
                            Quick Filter
                        </div>

                        <form id="scheduleFilterForm" method="GET" action="{{ route('manage.training-schedule.index') }}">
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
                                    <select id="scheduleCategorySelect" name="category" class="form-select rounded-3" onchange="this.form.submit()">
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
                                            'closed'  => 'Closed Registration',
                                            'draft'   => 'Draft',
                                        ];
                                    @endphp
                                    <select id="scheduleStatusSelect" name="status" class="form-select rounded-3" onchange="this.form.submit()">
                                        <option value="" {{ request('status') == '' ? 'selected' : '' }}>All Status</option>
                                        @foreach ($statusesList as $st => $stLabel)
                                            <option value="{{ $st }}" {{ request('status') == $st ? 'selected' : '' }}>{{ $stLabel }}</option>
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
                                    <select id="scheduleMonthSelect" name="month" class="form-select rounded-3" onchange="this.form.submit()">
                                        <option value="" {{ request('month') == '' ? 'selected' : '' }}>All Month</option>
                                        <option value="this_week" {{ request('month') == 'this_week' || request('month') == 'weekly' ? 'selected' : '' }}>This Week</option>
                                        {{-- <option value="03" {{ request('month') == '03' ? 'selected' : '' }}>March</option> --}}
                                        @foreach ($monthsList as $m => $monthName)
                                            <option value="{{ $m }}" {{ (is_numeric(request('month')) && (int) request('month') == (int) $m) || request('month') == $m ? 'selected' : '' }}>{{ $monthName }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- 4. Year Select (Width: ~100px) -->
                                <div style="min-width: 100px; flex-shrink: 0;">
                                    @php
                                        $yearsList = ['2026', '2025', '2027'];
                                    @endphp
                                    <select id="scheduleYearSelect" name="year" class="form-select rounded-3" onchange="this.form.submit()">
                                        <option value="" {{ request('year') == '' ? 'selected' : '' }}>All Year</option>
                                        {{-- <option value="2026" {{ request('year') == '2026' ? 'selected' : '' }}>2026</option> --}}
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
                                        {{-- <input type="text" name="search" value="{{ request('search') }}"> --}}
                                        <input type="text" id="scheduleSearchInput" name="search" class="form-control rounded-3" 
                                               value="{{ request('search') ?? request('q') }}"
                                               placeholder="Search training title, instructor, or room..." autocomplete="off">
                                    </div>
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

                {{-- Main Training Schedule Data Table --}}
                @php
                    $trainings = $trainings ?? \App\Models\OfflineTraining::forSchedule()->withCount('participants')->orderBy('start_date', 'asc')->paginate(10);
                @endphp
                <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-hover align-middle mb-0" id="manageScheduleTable">
                            <thead>
                                <tr class="text-muted extra-small text-uppercase fw-bold text-center align-middle" style="letter-spacing: 0.05em; background-color: #f8fafc;">
                                    <th class="w-1 py-3">NO</th>
                                    <th class="py-3" style="min-width: 200px;">TRAINING TITLE</th>
                                    <th class="py-3">CATEGORY</th>
                                    <th class="py-3" style="min-width: 140px;">DATE</th>
                                    <th class="py-3" style="min-width: 120px;">TIME</th>
                                    <th class="py-3">LOCATION</th>
                                    <th class="py-3">INSTRUCTOR</th>
                                    <th class="py-3">QUOTA / SEATS</th>
                                    <th class="py-3">STATUS</th>
                                    <th class="py-3" style="min-width: 170px;">ACTION</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y" id="scheduleTableBody">
                                @forelse ($trainings as $item)
                                    @php
                                        $enrolled = $item->active_seats_count ?? ($item->relationLoaded('registrations') ? $item->registrations->whereIn('status', ['Pending', 'Approved', 'Completed'])->count() : 0);
                                        $quota = $item->quota > 0 ? $item->quota : 1;
                                        $remaining = max(0, $quota - $enrolled);
                                        $percent = min(100, round(($enrolled / $quota) * 100));
                                        $isFull = $remaining <= 0 || $item->status === 'Closed';
                                        $barColor = $isFull ? '#ef4444' : ($remaining <= 10 ? '#f97316' : '#003b6d');

                                        $catStyle = match($item->category) {
                                            'Basic & Common Duties'             => 'background-color: #f1f5f9; color: #475569;',
                                            'Compliance', 'Compliance & Safety' => 'background-color: #fef3c7; color: #b45309;',
                                            'Innovation'                        => 'background-color: #ecfdf5; color: #047857;',
                                            'Job Functional', 'Technical', 'Technical Skill' => 'background-color: #e0f2fe; color: #0284c7;',
                                            'Language'                          => 'background-color: #fef2f2; color: #b91c1c;',
                                            'Leadership'                        => 'background-color: #f3e8ff; color: #7c3aed;',
                                            'Core Competency', 'General Competency' => 'background-color: #f0fdf4; color: #15803d;',
                                            'Maintenance'                       => 'background-color: #fff7ed; color: #c2410c;',
                                            'Quality'                           => 'background-color: #eff6ff; color: #1d4ed8;',
                                            'Safety'                            => 'background-color: #fef2f2; color: #dc2626;',
                                            'Environmental'                     => 'background-color: #f0fdf4; color: #166534;',
                                            'Soft Skill'                        => 'background-color: #faf5ff; color: #6b21a8;',
                                            default                             => 'background-color: #e0f2fe; color: #0369a1;',
                                        };

                                        $statusBadgeClass = match($item->status) {
                                            'Open Registration' => 'bg-success-subtle text-success',
                                            'Running'           => 'bg-warning-subtle text-warning border border-warning-subtle',
                                            'Closed'            => 'bg-secondary-subtle text-secondary',
                                            default             => 'bg-info-subtle text-info',
                                        };

                                        $dataStatus = match($item->status) {
                                            'Open Registration' => 'open',
                                            'Running'           => 'running',
                                            'Closed'            => 'closed',
                                            default             => strtolower($item->status),
                                        };

                                        $startDate = \Carbon\Carbon::parse($item->start_date);
                                        $startTime = substr($item->start_time, 0, 5);
                                        $endTime = substr($item->end_time, 0, 5);
                                    @endphp
                                    <tr class="schedule-table-row text-center align-middle" 
                                        data-id="{{ $item->id }}" 
                                        data-category="{{ $item->category }}" 
                                        data-status="{{ $dataStatus }}" 
                                        data-status-label="{{ $item->status }}"
                                        data-month="{{ (int)$startDate->format('n') }}" 
                                        data-year="{{ $startDate->format('Y') }}" 
                                        data-date="{{ $startDate->format('Y-m-d') }}" 
                                        data-instructor="{{ $item->instructor }}" 
                                        data-location="{{ $item->location }}">
                                        <td class="text-muted fw-semibold schedule-row-index">{{ method_exists($trainings, 'firstItem') && $trainings->firstItem() ? ($trainings->firstItem() + $loop->index) : $loop->iteration }}</td>
                                        <td class="fw-bold text-dark text-start" style="min-width: 200px;">
                                            <div class="fs-3 training-title-text">{{ $item->title }}</div>
                                            @if(!empty($item->sub_course_name))
                                                <div class="text-secondary small fw-normal">{{ $item->sub_course_name }}</div>
                                            @endif
                                            <div class="text-muted extra-small font-monospace">Code: {{ $item->training_code ?? 'PTKP-TR-' . $item->id }}</div>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill px-3 py-1-5 fw-semibold" style="{{ $catStyle }}">
                                                {{ $item->category }}
                                            </span>
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
                                            @if($item->registration_close_date)
                                                <div class="text-muted extra-small fw-normal" title="Registration Deadline">Reg Closes: {{ \Carbon\Carbon::parse($item->registration_close_date)->format('d M Y') }}{{ $item->registration_close_time ? ' ' . substr($item->registration_close_time, 0, 5) : '' }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="text-muted small" style="min-width: 120px;">{{ $startTime }} - {{ $endTime }} WIB</div>
                                        </td>
                                        <td class="text-muted small">
                                            {{ $item->location }}
                                        </td>
                                        <td class="text-muted small">
                                            <div class="fw-semibold text-dark">{{ $item->instructor }}</div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark small">{{ $enrolled }} / {{ $item->quota }} Seats {{ $isFull ? '(Full)' : '' }}</div>
                                            <div class="progress progress-xs mt-1 mx-auto rounded-pill" style="height: 4px; max-width: 90px; background-color: #e2e8f0;">
                                                <div class="progress-bar rounded-pill" role="progressbar" style="width: {{ $percent }}%; background-color: {{ $barColor }};"></div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge {{ $statusBadgeClass }} rounded-pill px-3 py-1 fw-semibold">
                                                {{ $item->status }}
                                            </span>
                                        </td>
                                        <td class="text-center py-3">
                                            <div class="d-flex align-items-center justify-content-center gap-1">
                                                <button type="button"
                                                        class="btn btn-sm btn-icon btn-outline-secondary rounded-3 btn-view-syllabus"
                                                        title="View Syllabus"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#syllabusModal"
                                                        data-id="{{ $item->id }}"
                                                        data-title="{{ $item->title }}"
                                                        data-category="{{ $item->category }}"
                                                        data-instructor="{{ $item->instructor }}"
                                                        data-duration="{{ $startTime }} - {{ $endTime }} WIB"
                                                        data-start-time="{{ $startTime }}"
                                                        data-end-time="{{ $endTime }}"
                                                        data-duration-hours="{{ \Carbon\Carbon::parse($item->start_time)->diffInHours(\Carbon\Carbon::parse($item->end_time)) . ' Hours' }}"
                                                        data-requirements="{{ $item->requirements ?? ($item->prerequisites ?? '') }}"
                                                        data-participant-requirement="{{ $item->prerequisites ?? ($item->requirements ?? '') }}"
                                                        data-course-completion-requirement="{{ $item->course_completion_requirement ?? '' }}"
                                                        data-target="{{ $item->target_employee ?? '' }}"
                                                        data-description="{{ $item->description ?? '' }}"
                                                        data-objectives="{{ $item->learning_objectives ?? '' }}"
                                                        data-syllabus="{{ json_encode($item->syllabus ?? []) }}">
                                                    <i class="ti ti-list-check fs-2"></i>
                                                </button>
                                                @php
                                                    $schedule = $item;
                                                @endphp
                                                <a href="{{ route('training-schedule.edit', $schedule->id) }}"
                                                   class="btn btn-sm btn-icon btn-outline-primary rounded-3 btn-view-edit-schedule"
                                                   title="View/Edit Schedule">
                                                    <i class="ti ti-edit fs-2"></i>
                                                </a>
                                                <a href="{{ route('manage.training-schedule.participants', $item->id) }}" class="btn btn-sm btn-icon btn-outline-info rounded-3 btn-participants-schedule"
                                                        title="View Participants"
                                                        data-id="{{ $item->id }}" data-title="{{ $item->title }}">
                                                    <i class="ti ti-users fs-2"></i>
                                                </a>
                                                <button type="button"
                                                        class="btn btn-sm btn-icon btn-outline-danger rounded-3 btn-delete-schedule"
                                                        title="Delete Schedule"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#deleteScheduleModal"
                                                        data-training-id="{{ $item->id }}"
                                                        data-training-title="{{ $item->title }}"
                                                        data-training-code="{{ $item->training_code ?? 'PTKP-TR-' . $item->id }}"
                                                        data-training-date="{{ $startDate->format('d M Y') }}"
                                                        data-delete-url="{{ route('manage.training-schedule.destroy', $item->id) }}">
                                                    <i class="ti ti-trash fs-2"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-5 text-muted">
                                            <div class="avatar avatar-xl rounded-circle bg-light text-muted mb-3 mx-auto">
                                                <i class="ti ti-calendar-off fs-1"></i>
                                            </div>
                                            <h4 class="fw-bold text-dark mb-1">No Training Schedules Found</h4>
                                            <p class="text-muted small mb-3">No training schedules available in the system yet. Click below to add and publish a new training schedule.</p>
                                            <a href="{{ url('/manage/training-schedule/create') }}" class="btn btn-primary px-4 py-2 rounded-3 fw-semibold d-inline-flex align-items-center gap-2" style="background-color: #003b6d; border-color: #003b6d;">
                                                <i class="ti ti-plus fs-2"></i>
                                                <span>Add Training Schedule</span>
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State for Table Filtering -->
                    <div class="p-5 text-center d-none" id="scheduleTableEmpty">
                        <div class="avatar avatar-xl rounded-circle bg-light text-muted mb-3 mx-auto">
                            <i class="ti ti-calendar-off fs-1"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-1">No Training Schedules Found</h4>
                        <p class="text-muted small mb-0">No PTKP training schedules match the selected filters. Try changing or clearing your filter criteria.</p>
                    </div>

                    @if (isset($schedules) && method_exists($schedules, 'hasPages') && $schedules->hasPages())
                        <div class="card-footer bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="text-muted small">
                                Showing {{ $schedules->firstItem() ?? 0 }} to {{ $schedules->lastItem() ?? 0 }} of {{ $schedules->total() }} results
                            </div>
                            <div>
                                {{ $schedules->appends(request()->query())->links() }}
                            </div>
                        </div>
                    @elseif (isset($schedules) && method_exists($schedules, 'total') && $schedules->total() > 0)
                        <div class="card-footer bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="text-muted small">
                                Showing {{ $schedules->firstItem() ?? 0 }} to {{ $schedules->lastItem() ?? 0 }} of {{ $schedules->total() }} results
                            </div>
                        </div>
                    @elseif (method_exists($trainings, 'hasPages') && $trainings->hasPages())
                        <div class="card-footer bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="text-muted small">
                                Showing {{ $trainings->firstItem() ?? 0 }} to {{ $trainings->lastItem() ?? 0 }} of {{ $trainings->total() }} results
                            </div>
                            <div>
                                {{ $trainings->appends(request()->query())->links() }}
                            </div>
                        </div>
                    @elseif (method_exists($trainings, 'total') && $trainings->total() > 0)
                        <div class="card-footer bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="text-muted small">
                                Showing {{ $trainings->firstItem() ?? 0 }} to {{ $trainings->lastItem() ?? 0 }} of {{ $trainings->total() }} results
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- PANE 2: PARTICIPANT TRACKING & APPROVALS --}}
            <div class="tab-pane fade" id="tab-participant-tracking-pane" role="tabpanel">
                @include('main.manage-training-schedule.partials.tracking-section')
            </div>
        </div>

        {{-- More Filters Modal --}}
        @include('main.manage-training-schedule.modals.schedule-filter-modal')

        {{-- Syllabus Details Modal --}}
        @include('main.manage-training-schedule.modals.syllabus-modal')

        {{-- Delete Confirmation Modal --}}
        @include('main.manage-training-schedule.modals.delete-confirm-modal')
    </div>
</div>
@endsection

@section('style')
<style>
    /* Manage Training Schedule Custom Styling */
    .schedule-table-row {
        transition: background-color 0.15s ease;
    }

    .schedule-table-row:hover {
        background-color: rgba(0, 59, 109, 0.02) !important;
    }

    .btn-icon {
        width: 34px;
        height: 34px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .training-title-text {
        letter-spacing: -0.01em;
        line-height: 1.3;
    }

    /* Modal Form Customizations */
    .required:after {
        content: " *";
        color: #dc3545;
        font-weight: bold;
    }
</style>
@endsection

@push('scripts')
    @include('main.manage-training-schedule.components.schedule-table-script')
@endpush
