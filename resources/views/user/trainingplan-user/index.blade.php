@extends('layouts.sidebar-master')

@section('style')
    <link href="{{ asset('Tabler/dist/css/tabler-vendors.min.css?1684106062') }}" rel="stylesheet"/>
    @include('components.Home.styles')
    <style>
        /* Training Schedule Module Custom Styling */
        .training-schedule-nav-pills .nav-link {
            border-radius: 999px;
            padding: 0.6rem 1.4rem;
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--tblr-body-color, #475569);
            background-color: var(--tblr-bg-surface, #ffffff);
            border: 1px solid var(--tblr-border-color, #e2e8f0);
            transition: all 0.2s ease-in-out;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        .training-schedule-nav-pills .nav-link:hover {
            color: #003b6d;
            border-color: #003b6d;
            background-color: #f8fafc;
            transform: translateY(-1px);
        }

        .training-schedule-nav-pills .nav-link.active {
            background-color: #003b6d !important;
            color: #ffffff !important;
            border-color: #003b6d !important;
            box-shadow: 0 4px 12px rgba(0, 59, 109, 0.25);
        }

        /* Calendar grid styling */
        .cal-grid-table th {
            font-size: 0.82rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .cal-day-cell {
            height: 94px;
            vertical-align: top;
            transition: background-color 0.15s ease;
            position: relative;
        }

        .cal-day-cell:hover {
            background-color: rgba(0, 59, 109, 0.03);
        }

        .cal-day-num {
            font-size: 0.8rem;
            font-weight: 600;
            line-height: 1;
            margin-bottom: 4px;
        }

        .cal-today-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background-color: #003b6d;
            color: #ffffff;
            font-size: 0.78rem;
            font-weight: 700;
            box-shadow: 0 2px 5px rgba(0, 59, 109, 0.35);
        }

        /* Calendar Status Count Badges */
        .cal-status-badge {
            font-size: 0.72rem;
            line-height: 1.25;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
            user-select: none;
            display: block;
            margin-bottom: 3px;
        }

        .cal-status-badge:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
            filter: brightness(0.96);
        }

        .cal-status-badge:active {
            transform: translateY(0);
        }

        .cal-status-badge-open {
            background-color: #ecfdf5;
            color: #047857;
            border-color: #a7f3d0;
        }

        .cal-status-badge-closed {
            background-color: #fef2f2;
            color: #b91c1c;
            border-color: #fecaca;
        }

        .cal-status-badge-running {
            background-color: #fffbeb;
            color: #b45309;
            border-color: #fde68a;
        }

        /* Timeline Items */
        .timeline-schedule-item {
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .timeline-schedule-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            border-color: #003b6d !important;
        }

        /* Training Card hover */
        .training-card-hover {
            transition: all 0.25s ease;
        }

        .training-card-hover:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.08) !important;
        }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Dark Mode overrides */
        [data-bs-theme="dark"] .training-schedule-nav-pills .nav-link,
        body.dark-mode .training-schedule-nav-pills .nav-link {
            background-color: #182433;
            color: #dce6f1;
            border-color: #25384d;
        }

        [data-bs-theme="dark"] .training-schedule-nav-pills .nav-link:hover,
        body.dark-mode .training-schedule-nav-pills .nav-link:hover {
            background-color: #1f2d3d;
            color: #38bdf8;
            border-color: #38bdf8;
        }

        [data-bs-theme="dark"] .training-schedule-nav-pills .nav-link.active,
        body.dark-mode .training-schedule-nav-pills .nav-link.active {
            background-color: #0284c7 !important;
            border-color: #0284c7 !important;
            color: #ffffff !important;
        }

        [data-bs-theme="dark"] .cal-grid-table th,
        [data-bs-theme="dark"] .cal-grid-table td,
        body.dark-mode .cal-grid-table th,
        body.dark-mode .cal-grid-table td {
            border-color: #25384d !important;
        }

        [data-bs-theme="dark"] .cal-day-cell:hover,
        body.dark-mode .cal-day-cell:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        [data-bs-theme="dark"] .timeline-schedule-item,
        body.dark-mode .timeline-schedule-item {
            background-color: #182433 !important;
            border-color: #25384d !important;
        }

        [data-bs-theme="dark"] .card.bg-white,
        body.dark-mode .card.bg-white {
            background-color: #182433 !important;
            border-color: #25384d !important;
        }
    </style>
@endsection

@section('content')
<div class="page-body">
    <div class="container-xl py-4">

        {{-- Top-left Back Navigation Link --}}
        <a href="{{ url('/') }}" class="btn btn-link p-0 mb-3 text-decoration-none d-inline-flex align-items-center gap-1 fw-semibold text-primary">
            <i class="ti ti-arrow-left fs-2"></i>
            <span>Back</span>
        </a>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible rounded-4 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-check fs-2 text-success"></i>
                    <div class="fw-semibold">{{ session('success') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible rounded-4 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-alert-triangle fs-2 text-danger"></i>
                    <div class="fw-semibold">{{ session('error') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Header Hero Card: 72x72px icon box, bg #eef2ff, color #003b6d --}}
        <div class="card border border-light-subtle shadow-sm p-4 mb-4 rounded-4 bg-white">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-4 d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm" 
                         style="width: 72px; height: 72px; background-color: #eef2ff; color: #003b6d;">
                        <i class="ti ti-calendar-event fs-1" style="font-size: 2.2rem !important;"></i>
                    </div>
                    <div>
                        <h2 class="h2 fw-bold mb-1 text-dark">Training Schedule</h2>
                        <p class="text-muted small mb-0">View your training calendar, register for available sessions, and track your application history.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Top Pill/Tab Navigation (English) --}}
        <div class="mb-4">
            <ul class="nav nav-pills training-schedule-nav-pills gap-2" id="trainingScheduleTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-calendar-btn" data-bs-toggle="pill" data-bs-target="#tab-calendar" type="button" role="tab" aria-controls="tab-calendar" aria-selected="true">
                        <i class="ti ti-calendar"></i>
                        <span>Calendar</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-available-btn" data-bs-toggle="pill" data-bs-target="#tab-available" type="button" role="tab" aria-controls="tab-available" aria-selected="false">
                        <i class="ti ti-layout-grid"></i>
                        <span>Available Training</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-history-btn" data-bs-toggle="pill" data-bs-target="#tab-history" type="button" role="tab" aria-controls="tab-history" aria-selected="false">
                        <i class="ti ti-history"></i>
                        <span>My History</span>
                    </button>
                </li>
            </ul>
        </div>

        {{-- Tab Content Container --}}
        <div class="tab-content" id="trainingScheduleTabContent">
            {{-- Tab 1: Calendar & Agenda --}}
            <div class="tab-pane fade show active" id="tab-calendar" role="tabpanel" aria-labelledby="tab-calendar-btn">
                @include('user.trainingplan-user.partials.calendar-tab')
            </div>

            {{-- Tab 2: Available Training --}}
            <div class="tab-pane fade" id="tab-available" role="tabpanel" aria-labelledby="tab-available-btn">
                @include('user.trainingplan-user.partials.available-training-tab')
            </div>

            {{-- Tab 3: My History --}}
            <div class="tab-pane fade" id="tab-history" role="tabpanel" aria-labelledby="tab-history-btn">
                @include('user.trainingplan-user.partials.my-history-tab')
            </div>
        </div>

    </div>
</div>

{{-- Pop-up Modals --}}
@include('user.trainingplan-user.modals.schedule-detail-modal')
@include('user.trainingplan-user.modals.syllabus-modal')

@endsection

@push('scripts')
@php
    $dbTrainings = $trainings ?? \App\Models\OfflineTraining::forSchedule()->withCount('participants')->orderBy('start_date', 'asc')->get();
    $computedCalendarEvents = $calendarEvents ?? $dbTrainings->map(function ($t) {
        $startDate = \Carbon\Carbon::parse($t->start_date);
        $endDate = \Carbon\Carbon::parse($t->end_date ?? $t->start_date);
        $enrolled = $t->participants_count ?? 0;
        $quota = $t->quota ?: 1;
        $isClosed = $t->status === 'Closed' || $enrolled >= $quota;

        return [
            'id'          => (string) $t->id,
            'year'        => (int) $startDate->format('Y'),
            'month'       => (int) $startDate->format('n') - 1, // 0-indexed for JS Date
            'day'         => (int) $startDate->format('j'),
            'startDate'   => $startDate->format('Y-m-d'),
            'endDate'     => $endDate->format('Y-m-d'),
            'rawStatus'   => $t->status,
            'title'       => $t->title,
            'shortTitle'  => \Illuminate\Support\Str::limit($t->title, 16),
            'category'    => $t->category,
            'badgeClass'  => match($t->category) {
                'Technical Skill'     => 'bg-info-lt text-info',
                'Compliance & Safety' => 'bg-warning-lt text-warning',
                'Leadership'          => 'bg-purple-subtle text-purple',
                default               => 'bg-primary-lt text-primary',
            },
            'status'      => $isClosed ? 'closed' : 'open',
            'time'        => substr($t->start_time, 0, 5) . ' - ' . substr($t->end_time, 0, 5) . ' WIB',
            'dateTime'    => $startDate->format('d F Y') . ', ' . substr($t->start_time, 0, 5) . ' - ' . substr($t->end_time, 0, 5) . ' WIB',
            'location'    => $t->location,
            'instructor'  => $t->instructor,
            'description' => $t->description ?? '',
            'quota'       => "{$enrolled} / {$t->quota} Seats" . ($enrolled >= $t->quota ? ' (Full)' : ''),
        ];
    })->values()->toArray();

    $anchorDateObj = $dbTrainings->first() ? \Carbon\Carbon::parse($dbTrainings->first()->start_date) : \Carbon\Carbon::now();
@endphp
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- Live Calendar Events Store ---
        const calendarEvents = @json($computedCalendarEvents);

        const monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        const dayOfWeekNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        function formatCalendarDate(y, m, d) {
            const dateObj = new Date(y, m, d);
            return `${dayOfWeekNames[dateObj.getDay()]}, ${d} ${monthNames[m]} ${y}`;
        }

        // Anchor Date dynamically calculated from trainings
        const activeDate = new Date({{ $anchorDateObj->format('Y') }}, {{ (int)$anchorDateObj->format('n') - 1 }}, {{ (int)$anchorDateObj->format('j') }});
        let currentCalYear = activeDate.getFullYear();
        let currentCalMonth = activeDate.getMonth();
        let currentModalStatus = 'open';

        // --- Calendar Rendering Logic ---
        function renderCalendar(year, month) {
            const calGridBody = document.getElementById('calGridBody');
            const calendarMonthYear = document.getElementById('calendarMonthYear');
            if (!calGridBody || !calendarMonthYear) return;

            calendarMonthYear.textContent = `${monthNames[month]} ${year}`;

            // First day of month (0 = Sunday, 1 = Monday, ..., 6 = Saturday)
            const firstDayIndex = new Date(year, month, 1).getDay();
            // Convert Sunday (0) to 6, Monday (1) to 0, etc. for Monday-start grid
            const startCol = (firstDayIndex + 6) % 7;

            // Total days in current month & prev month
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const daysInPrevMonth = new Date(year, month, 0).getDate();

            let html = '';
            let dayCounter = 1;
            let nextMonthDayCounter = 1;

            for (let row = 0; row < 6; row++) {
                let rowHtml = '<tr>';
                for (let col = 0; col < 7; col++) {
                    const cellIndex = row * 7 + col;
                    const isWeekend = (col === 5 || col === 6);

                    if (cellIndex < startCol) {
                        // Previous Month Day
                        const prevDate = daysInPrevMonth - (startCol - cellIndex - 1);
                        rowHtml += `<td class="p-1 cal-day-cell text-muted bg-light-subtle opacity-50">
                            <div class="cal-day-num text-end pe-1">${prevDate}</div>
                        </td>`;
                    } else if (dayCounter <= daysInMonth) {
                        // Current Month Day
                        const thisDay = dayCounter;
                        const cellDateFormatted = `${year}-${String(month + 1).padStart(2, '0')}-${String(thisDay).padStart(2, '0')}`;
                        const todayDateFormatted = '{{ \Carbon\Carbon::today()->format('Y-m-d') }}';
                        const isToday = (cellDateFormatted === todayDateFormatted);
                        
                        // Find events on this day (spanning startDate to endDate inclusive)
                        const activeTrainingsForDay = calendarEvents.filter(e => {
                            const s = e.startDate || `${e.year}-${String(e.month + 1).padStart(2, '0')}-${String(e.day).padStart(2, '0')}`;
                            const end = e.endDate || s;
                            return cellDateFormatted >= s && cellDateFormatted <= end;
                        });

                        let badgesHtml = '';
                        if (activeTrainingsForDay.length > 0) {
                            if (cellDateFormatted < todayDateFormatted) {
                                badgesHtml = `
                                    <div class="mt-1 d-flex flex-column gap-1">
                                        <span class="badge bg-secondary-subtle text-secondary rounded-pill extra-small py-1 px-2 opacity-75 cal-status-badge cal-status-badge-closed"
                                              role="button"
                                              data-year="${year}"
                                              data-month="${month}"
                                              data-day="${thisDay}"
                                              data-status="closed"
                                              title="${activeTrainingsForDay.length} Closed Training(s)">
                                            Done: ${activeTrainingsForDay.length}
                                        </span>
                                    </div>
                                `;
                            } else if (cellDateFormatted === todayDateFormatted || activeTrainingsForDay.some(e => e.rawStatus === 'Running')) {
                                badgesHtml = `
                                    <div class="mt-1 d-flex flex-column gap-1">
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill extra-small py-1 px-2 fw-semibold cal-status-badge cal-status-badge-running"
                                              role="button"
                                              data-year="${year}"
                                              data-month="${month}"
                                              data-day="${thisDay}"
                                              data-status="running"
                                              title="${activeTrainingsForDay.length} Running Training(s)">
                                            Running: ${activeTrainingsForDay.length}
                                        </span>
                                    </div>
                                `;
                            } else {
                                badgesHtml = `
                                    <div class="mt-1 d-flex flex-column gap-1">
                                        <span class="badge bg-success-subtle text-success rounded-pill extra-small py-1 px-2 fw-semibold cal-status-badge cal-status-badge-open"
                                              role="button"
                                              data-year="${year}"
                                              data-month="${month}"
                                              data-day="${thisDay}"
                                              data-status="open"
                                              title="${activeTrainingsForDay.length} Open Training(s)">
                                            Open: ${activeTrainingsForDay.length}
                                        </span>
                                    </div>
                                `;
                            }
                        }

                        const dayNumDisplay = isToday 
                            ? `<span class="cal-today-badge">${thisDay}</span>`
                            : `<span class="${isWeekend ? 'text-danger' : ''}">${thisDay}</span>`;

                        rowHtml += `<td class="p-1 cal-day-cell">
                            <div class="cal-day-num text-end pe-1">${dayNumDisplay}</div>
                            ${badgesHtml}
                        </td>`;
                        dayCounter++;
                    } else {
                        // Next Month Day
                        rowHtml += `<td class="p-1 cal-day-cell text-muted bg-light-subtle opacity-50">
                            <div class="cal-day-num text-end pe-1">${nextMonthDayCounter}</div>
                        </td>`;
                        nextMonthDayCounter++;
                    }
                }
                rowHtml += '</tr>';
                html += rowHtml;

                // Stop early if all days of the current month have been rendered
                if (dayCounter > daysInMonth && row >= 4) {
                    break;
                }
            }

            calGridBody.innerHTML = html;
        }

        // Initialize Calendar
        renderCalendar(currentCalYear, currentCalMonth);

        // Previous Month Button
        const calPrevBtn = document.getElementById('calPrevBtn');
        if (calPrevBtn) {
            calPrevBtn.addEventListener('click', function () {
                currentCalMonth--;
                if (currentCalMonth < 0) {
                    currentCalMonth = 11;
                    currentCalYear--;
                }
                renderCalendar(currentCalYear, currentCalMonth);
            });
        }

        // Next Month Button
        const calNextBtn = document.getElementById('calNextBtn');
        if (calNextBtn) {
            calNextBtn.addEventListener('click', function () {
                currentCalMonth++;
                if (currentCalMonth > 11) {
                    currentCalMonth = 0;
                    currentCalYear++;
                }
                renderCalendar(currentCalYear, currentCalMonth);
            });
        }

        // Today Button
        const calTodayBtn = document.getElementById('calTodayBtn');
        if (calTodayBtn) {
            calTodayBtn.addEventListener('click', function () {
                currentCalYear = activeDate.getFullYear();
                currentCalMonth = activeDate.getMonth();
                renderCalendar(currentCalYear, currentCalMonth);
            });
        }

        // --- Open Training List Modal Function ---
        function openTrainingListModal(year, month, day, status, highlightId = null) {
            currentModalStatus = status;
            const formattedDate = formatCalendarDate(year, month, day);
            const targetDateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;

            let matchingEvents = calendarEvents.filter(e => {
                const s = e.startDate || `${e.year}-${String(e.month + 1).padStart(2, '0')}-${String(e.day).padStart(2, '0')}`;
                const end = e.endDate || s;
                return targetDateStr >= s && targetDateStr <= end;
            });

            // Fallback for agenda clicks if status differed
            if (matchingEvents.length === 0 && highlightId) {
                const specificItem = calendarEvents.find(e => e.id === highlightId);
                if (specificItem) {
                    currentModalStatus = specificItem.status;
                    status = specificItem.status;
                    matchingEvents = [specificItem];
                }
            }

            const isOpen = (status === 'open');
            const isRunning = (status === 'running');

            const modalStatusTitle = document.getElementById('modalStatusTitle');
            const modalStatusCountBadge = document.getElementById('modalStatusCountBadge');
            const modalStatusIconWrap = document.getElementById('modalStatusIconWrap');
            const modalStatusIcon = document.getElementById('modalStatusIcon');
            const modalSelectedDateText = document.getElementById('modalSelectedDateText');
            const modalTrainingItemsList = document.getElementById('modalTrainingItemsList');
            const modalEmptyState = document.getElementById('modalEmptyState');

            // Header Elements
            if (modalStatusTitle) {
                modalStatusTitle.textContent = isRunning ? 'Running Trainings' : (isOpen ? 'Open Trainings' : 'Completed / Past Trainings');
            }
            if (modalSelectedDateText) {
                modalSelectedDateText.textContent = formattedDate;
            }
            if (modalStatusCountBadge) {
                if (isRunning) {
                    modalStatusCountBadge.className = 'badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill fw-semibold px-2-5 py-1 fs-6';
                    modalStatusCountBadge.textContent = `${matchingEvents.length} Running`;
                } else if (isOpen) {
                    modalStatusCountBadge.className = 'badge bg-success-subtle text-success border border-success-subtle rounded-pill fw-semibold px-2-5 py-1 fs-6';
                    modalStatusCountBadge.textContent = `${matchingEvents.length} Open`;
                } else {
                    modalStatusCountBadge.className = 'badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill fw-semibold px-2-5 py-1 fs-6';
                    modalStatusCountBadge.textContent = `${matchingEvents.length} Done`;
                }
            }
            if (modalStatusIconWrap) {
                if (isRunning) {
                    modalStatusIconWrap.style.backgroundColor = '#fffbeb';
                    modalStatusIconWrap.style.color = '#d97706';
                } else if (isOpen) {
                    modalStatusIconWrap.style.backgroundColor = '#ecfdf5';
                    modalStatusIconWrap.style.color = '#059669';
                } else {
                    modalStatusIconWrap.style.backgroundColor = '#f1f5f9';
                    modalStatusIconWrap.style.color = '#64748b';
                }
            }
            if (modalStatusIcon) {
                modalStatusIcon.className = isRunning ? 'ti ti-player-play fs-2' : (isOpen ? 'ti ti-circle-check fs-2' : 'ti ti-check fs-2');
            }

            // Populate Items List (Compact Rows for High Scalability)
            if (modalTrainingItemsList) {
                if (matchingEvents.length === 0) {
                    modalTrainingItemsList.innerHTML = '';
                    if (modalEmptyState) modalEmptyState.classList.remove('d-none');
                } else {
                    if (modalEmptyState) modalEmptyState.classList.add('d-none');

                    let listHtml = '';
                    matchingEvents.forEach(item => {
                        const isCurrentItem = highlightId && item.id === highlightId;
                        const itemIsOpen = item.status === 'open';

                        listHtml += `
                            <div class="p-3 rounded-3 border ${isCurrentItem ? 'border-primary shadow-sm' : 'border-light-subtle'} bg-white hover-shadow-sm d-flex align-items-center justify-content-between gap-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="badge bg-primary-subtle text-primary rounded-2 px-2-5 py-2 fw-semibold text-nowrap" style="font-size: 0.8rem;">
                                        ${item.time}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark fs-3 mb-1">${item.title}</div>
                                        <div class="d-flex align-items-center gap-2 text-muted small">
                                            <span>${item.location}</span>
                                            <span>&bull;</span>
                                            <span class="badge rounded-pill bg-light text-secondary border px-2 py-0-5">${item.category}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end flex-shrink-0">
                                    <span class="badge ${itemIsOpen ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'} rounded-pill px-3 py-1-5 fw-semibold">
                                        ${itemIsOpen ? 'Open Registration' : 'Registration Closed'}
                                    </span>
                                </div>
                            </div>
                        `;
                    });
                    modalTrainingItemsList.innerHTML = listHtml;
                }
            }

            const eventDetailModalEl = document.getElementById('eventDetailModal');
            if (eventDetailModalEl) {
                const modalInstance = bootstrap.Modal.getOrCreateInstance(eventDetailModalEl);
                modalInstance.show();
            }
        }

        // Function to update Today's Agenda on the right panel
        function updateTodayAgendaPanel(year, month, day) {
            const formattedDate = formatCalendarDate(year, month, day);
            const targetDateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const labelEl = document.getElementById('todayAgendaDateLabel');
            if (labelEl) {
                labelEl.innerHTML = `<i class="ti ti-clock me-1"></i> ${formattedDate}`;
            }

            const matchingEvents = calendarEvents.filter(e => {
                const s = e.startDate || `${e.year}-${String(e.month + 1).padStart(2, '0')}-${String(e.day).padStart(2, '0')}`;
                const end = e.endDate || s;
                return targetDateStr >= s && targetDateStr <= end;
            });

            const timelineList = document.getElementById('agendaTimelineList');
            if (timelineList) {
                if (matchingEvents.length === 0) {
                    timelineList.innerHTML = `
                        <div class="text-center py-5 px-3">
                            <div class="mb-2 text-muted opacity-50">
                                <i class="ti ti-calendar-off fs-1"></i>
                            </div>
                            <h6 class="fw-semibold text-dark mb-1">No Training Scheduled</h6>
                            <p class="text-muted extra-small mb-0">There are no offline training sessions scheduled for this date.</p>
                        </div>
                    `;
                } else {
                    let html = '';
                    matchingEvents.forEach(item => {
                        html += `
                            <div class="timeline-schedule-item mb-3 p-3 rounded-3 border border-light-subtle bg-light-subtle position-relative"
                                 role="button"
                                 data-id="${item.id}"
                                 data-title="${item.title}"
                                 data-category="${item.category}"
                                 data-date="${item.dateTime}"
                                 data-location="${item.location}"
                                 data-instructor="${item.instructor}"
                                 data-description="${item.description}">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="badge bg-primary text-white rounded-pill px-2 py-0-5 small fw-semibold">${item.time}</span>
                                    <span class="badge bg-primary-lt text-primary small">${item.category}</span>
                                </div>
                                <h5 class="fw-bold text-dark mb-1">${item.title}</h5>
                                <div class="text-muted small d-flex align-items-center gap-1 mb-1">
                                    <i class="ti ti-map-pin text-primary"></i>
                                    <span>${item.location}</span>
                                </div>
                                <div class="text-muted small d-flex align-items-center gap-1">
                                    <i class="ti ti-user-check text-primary"></i>
                                    <span>${item.instructor}</span>
                                </div>
                            </div>
                        `;
                    });
                    timelineList.innerHTML = html;

                    // Re-attach modal trigger on new timeline items
                    timelineList.querySelectorAll('.timeline-schedule-item').forEach(item => {
                        item.addEventListener('click', function () {
                            const id = this.getAttribute('data-id');
                            const ev = calendarEvents.find(e => e.id === id);
                            if (ev) {
                                openTrainingListModal(ev.year, ev.month, ev.day, ev.status, ev.id);
                            }
                        });
                    });
                }
            }
        }

        // Delegate click on calendar status badges and date cells
        const calGridBody = document.getElementById('calGridBody');
        if (calGridBody) {
            calGridBody.addEventListener('click', function (e) {
                const cell = e.target.closest('.cal-day-cell');
                const badge = e.target.closest('.cal-status-badge');

                if (cell && !cell.classList.contains('opacity-50')) {
                    const y = parseInt(cell.getAttribute('data-year'), 10);
                    const m = parseInt(cell.getAttribute('data-month'), 10);
                    const d = parseInt(cell.getAttribute('data-day'), 10);

                    if (!isNaN(y) && !isNaN(m) && !isNaN(d)) {
                        updateTodayAgendaPanel(y, m, d);
                    }
                }

                if (badge) {
                    const y = parseInt(badge.getAttribute('data-year'), 10);
                    const m = parseInt(badge.getAttribute('data-month'), 10);
                    const d = parseInt(badge.getAttribute('data-day'), 10);
                    const status = badge.getAttribute('data-status');

                    openTrainingListModal(y, m, d, status);
                }
            });
        }

        // Today's Agenda timeline items click -> open modal
        document.querySelectorAll('.timeline-schedule-item').forEach(item => {
            item.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const ev = calendarEvents.find(e => e.id === id);
                if (ev) {
                    openTrainingListModal(ev.year, ev.month, ev.day, ev.status, ev.id);
                } else {
                    openTrainingListModal(2026, 8, 18, 'open', id);
                }
            });
        });

        // View More button click in modal: close modal & switch to Available Training tab with status filter
        const modalBtnViewMore = document.getElementById('btn-modal-view-more') || document.getElementById('modalBtnViewMore');
        if (modalBtnViewMore) {
            modalBtnViewMore.addEventListener('click', function () {
                const eventDetailModalEl = document.getElementById('eventDetailModal');
                if (eventDetailModalEl) {
                    const modalInstance = bootstrap.Modal.getInstance(eventDetailModalEl) || new bootstrap.Modal(eventDetailModalEl);
                    modalInstance.hide();
                }

                const availableTabBtn = document.getElementById('tab-available-btn');
                if (availableTabBtn) {
                    const tabTrigger = new bootstrap.Tab(availableTabBtn);
                    tabTrigger.show();

                    // Apply the status filter corresponding to the modal
                    if (statusSelect && currentModalStatus) {
                        statusSelect.value = currentModalStatus;
                        filterAvailableTraining();
                    }

                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });
        }

        // Switch to available tab from calendar sidebar button
        document.querySelectorAll('.btn-switch-to-available').forEach(btn => {
            btn.addEventListener('click', function () {
                const availableTabBtn = document.getElementById('tab-available-btn');
                if (availableTabBtn) {
                    const tabTrigger = new bootstrap.Tab(availableTabBtn);
                    tabTrigger.show();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });
        });

        // Quick Filter Logic on Available Training Tab
        const searchInput = document.getElementById('filterTrainingSearch');
        const categorySelect = document.getElementById('filterTrainingCategory');
        const statusSelect = document.getElementById('filterTrainingStatus');
        const btnResetFilter = document.getElementById('btnResetFilter');

        function filterAvailableTraining() {
            const query = (searchInput?.value || '').toLowerCase().trim();
            const category = categorySelect?.value || '';
            const status = statusSelect?.value || '';

            const rows = document.querySelectorAll('#availableTrainingTable tbody tr.training-table-row, .training-card-item');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const rowCat = row.getAttribute('data-category') || '';
                const rowStatus = row.getAttribute('data-status') || '';

                const rowInstructor = (row.getAttribute('data-instructor') || '').toLowerCase();
                const matchesSearch = !query || text.includes(query) || rowInstructor.includes(query);
                const matchesCategory = !category || rowCat === category;
                const matchesStatus = !status || rowStatus === status;

                if (matchesSearch && matchesCategory && matchesStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        if (searchInput) searchInput.addEventListener('input', filterAvailableTraining);
        if (categorySelect) categorySelect.addEventListener('change', filterAvailableTraining);
        if (statusSelect) statusSelect.addEventListener('change', filterAvailableTraining);

        if (btnResetFilter) {
            btnResetFilter.addEventListener('click', function () {
                if (searchInput) searchInput.value = '';
                if (categorySelect) categorySelect.value = '';
                if (statusSelect) statusSelect.value = '';
                filterAvailableTraining();
            });
        }
    });
</script>
@endpush