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

        .cal-day-cell.is-today {
            background-color: #e0f2fe !important;
            border: 2px solid #0284c7 !important;
            position: relative;
            z-index: 1;
        }

        .cal-day-cell.is-today:hover {
            background-color: #bae6fd !important;
        }

        .cal-today-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background-color: #003b6d;
            color: #ffffff;
            font-size: 0.78rem;
            font-weight: 700;
            box-shadow: 0 2px 6px rgba(0, 59, 109, 0.35);
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

        .cal-status-badge-private,
        .cal-status-badge-closed {
            background-color: #e0f2fe !important;
            color: #0369a1 !important;
            border-color: #bae6fd !important;
        }

        .cal-status-badge-running {
            background-color: #fffbeb;
            color: #b45309;
            border-color: #fde68a;
        }

        .cal-status-badge-ended {
            background-color: #475569 !important;
            color: #ffffff !important;
            border-color: #334155 !important;
        }

        .bg-success-light {
            background-color: #ecfdf5 !important;
        }

        .bg-secondary-light {
            background-color: #f1f5f9 !important;
        }

        .bg-warning-light {
            background-color: #fef3c7 !important;
            color: #b45309 !important;
        }

        .bg-dark-light {
            background-color: #475569 !important;
            color: #ffffff !important;
        }

        /* Live YouTube-style Broadcast Animation & Utilities Fallback */
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: .35;
            }
        }
        .animate-pulse {
            animation: pulse 1.8s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        .w-3\.5, .w-3-5 {
            width: 0.875rem;
        }
        .h-3\.5, .h-3-5 {
            height: 0.875rem;
        }
        .text-\[11px\] {
            font-size: 11px;
        }
        .tracking-wider {
            letter-spacing: 0.05em;
        }
        .bg-\[\#cc0000\] {
            background-color: #cc0000;
        }
        .bg-gray-200 {
            background-color: #e5e7eb;
        }
        .text-gray-700 {
            color: #4b5563;
        }
        .w-4 {
            width: 1rem;
        }
        .h-4 {
            height: 1rem;
        }
        .shrink-0 {
            flex-shrink: 0;
        }
        .leading-none {
            line-height: 1;
        }
        .cursor-not-allowed {
            cursor: not-allowed !important;
        }
        .w-fit {
            width: fit-content !important;
            max-width: max-content !important;
        }
        .self-start {
            align-self: flex-start !important;
        }
        .w-auto {
            width: auto;
        }
        .min-w-max {
            min-width: max-content;
        }
        .whitespace-nowrap {
            white-space: nowrap;
        }
        .bg-sky-100 {
            background-color: #e0f2fe;
        }
        .text-sky-800 {
            color: #075985;
        }
        .text-xs {
            font-size: 0.75rem;
            line-height: 1rem;
        }
        .rounded-md {
            border-radius: 0.375rem;
        }
        .font-extrabold {
            font-weight: 800;
        }
        .tracking-widest {
            letter-spacing: 0.1em;
        }
        .bg-\[\#d92626\] {
            background-color: #d92626;
        }
        .bg-\[\#e60023\] {
            background-color: #e60023;
        }
        .tracking-tight {
            letter-spacing: -0.025em;
        }
        .rounded-full {
            border-radius: 9999px;
        }
        .bg-emerald-100 {
            background-color: #d1fae5 !important;
        }
        .text-emerald-700 {
            color: #047857 !important;
        }
        .bg-gray-100 {
            background-color: #f3f4f6 !important;
        }
        .text-gray-600 {
            color: #4b5563 !important;
        }
        .bg-gray-200 {
            background-color: #e5e7eb !important;
        }
        .text-gray-500 {
            color: #6b7280 !important;
        }
        .cursor-not-allowed {
            cursor: not-allowed !important;
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
        @php
            $activeTabParam = request('tab');
            $hasPageParam = request()->has('page');
            $isAvailableActive = $activeTabParam === 'available' || ($hasPageParam && !$activeTabParam);
            $isHistoryActive = $activeTabParam === 'history';
            $isCalendarActive = !$isAvailableActive && !$isHistoryActive;
        @endphp
        <div class="mb-4">
            <ul class="nav nav-pills training-schedule-nav-pills gap-2" id="trainingScheduleTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $isCalendarActive ? 'active' : '' }}" id="tab-calendar-btn" data-bs-toggle="pill" data-bs-target="#tab-calendar" type="button" role="tab" aria-controls="tab-calendar" aria-selected="{{ $isCalendarActive ? 'true' : 'false' }}">
                        <i class="ti ti-calendar"></i>
                        <span>Calendar</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $isAvailableActive ? 'active' : '' }}" id="tab-available-btn" data-bs-toggle="pill" data-bs-target="#tab-available" type="button" role="tab" aria-controls="tab-available" aria-selected="{{ $isAvailableActive ? 'true' : 'false' }}">
                        <i class="ti ti-layout-grid"></i>
                        <span>Available Training</span>
                    </button>
                </li>
                <!-- <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $isHistoryActive ? 'active' : '' }}" id="tab-history-btn" data-bs-toggle="pill" data-bs-target="#tab-history" type="button" role="tab" aria-controls="tab-history" aria-selected="{{ $isHistoryActive ? 'true' : 'false' }}">
                        <i class="ti ti-history"></i>
                        <span>My History</span>
                    </button>
                </li> -->
            </ul>
        </div>

        {{-- Tab Content Container --}}
        <div class="tab-content" id="trainingScheduleTabContent">
            {{-- Tab 1: Calendar & Agenda --}}
            <div class="tab-pane fade {{ $isCalendarActive ? 'show active' : '' }}" id="tab-calendar" role="tabpanel" aria-labelledby="tab-calendar-btn">
                @include('user.trainingplan-user.partials.calendar-tab')
            </div>

            {{-- Tab 2: Available Training --}}
            <div class="tab-pane fade {{ $isAvailableActive ? 'show active' : '' }}" id="tab-available" role="tabpanel" aria-labelledby="tab-available-btn">
                @include('user.trainingplan-user.partials.available-training-tab')
            </div>

            {{-- Tab 3: My History --}}
            <div class="tab-pane fade {{ $isHistoryActive ? 'show active' : '' }}" id="tab-history" role="tabpanel" aria-labelledby="tab-history-btn">
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
    $today = \Carbon\Carbon::today()->format('Y-m-d');
    $computedCalendarEvents = $calendarEvents ?? $dbTrainings->map(function ($t) use ($today) {
        $startDate = \Carbon\Carbon::parse($t->start_date);
        $endDate = $t->end_date ? \Carbon\Carbon::parse($t->end_date) : $startDate;
        $startDateStr = $startDate->format('Y-m-d');
        $endDateStr = $endDate->format('Y-m-d');
        $regCloseDate = $t->registration_close_date 
            ? \Carbon\Carbon::parse($t->registration_close_date) 
            : $startDate;
        $regCloseDateStr = $regCloseDate->format('Y-m-d');
        $regCloseTime = $t->registration_close_time ? substr($t->registration_close_time, 0, 5) : '23:59';
        $isPublished = $t->status !== 'Draft' && (!isset($t->is_published) || (bool) $t->is_published);

        // Calendar & List Status Classification Logic: Open, Private, Ended, Running
        if ($endDateStr < $today) {
            $status = 'Ended'; // Dark Gray badge
        } elseif ($startDateStr <= $today && $endDateStr >= $today && $regCloseDateStr < $today) {
            $status = 'Running'; // Orange badge (Currently running & registration closed)
        } elseif ($regCloseDateStr >= $today && $isPublished && !in_array(strtolower($t->status ?? ''), ['closed', 'private', 'draft'])) {
            $status = 'Open'; // Green badge (Registration open)
        } else {
            $status = 'Private'; // Soft Blue badge (Invitation-Only Training)
        }

        $enrolled = (int) ($t->participants_count ?? ($t->registered_seats_count ?? 0));
        $quota = (int) ($t->quota ?: 1);
        $isPast = ($endDateStr < $today);

        return [
            'id'                      => (string) $t->id,
            'year'                    => (int) $startDate->format('Y'),
            'month'                   => (int) $startDate->format('n') - 1, // 0-indexed for JS Date
            'day'                     => (int) $startDate->format('j'),
            'startDate'               => $startDateStr,
            'endDate'                 => $endDateStr,
            'registration_close_date' => $regCloseDateStr,
            'registrationCloseDate'   => $regCloseDateStr,
            'registration_close_time' => $regCloseTime,
            'registrationCloseTime'   => $regCloseTime,
            'rawStatus'   => $t->status,
            'title'       => $t->title,
            'shortTitle'  => \Illuminate\Support\Str::limit($t->title, 16),
            'category'    => $t->category,
            'badgeClass'  => match($t->category) {
                'Basic & Common Duties'             => 'bg-secondary-subtle text-secondary',
                'Compliance', 'Compliance & Safety' => 'bg-warning-lt text-warning',
                'Innovation'                        => 'bg-success-lt text-success',
                'Job Functional', 'Technical Skill' => 'bg-info-lt text-info',
                'Language'                          => 'bg-danger-lt text-danger',
                'Leadership'                        => 'bg-purple-subtle text-purple',
                default                             => 'bg-primary-lt text-primary',
            },
            'status'      => strtolower($status),
            'computedStatus' => $status,
            'is_published' => $isPublished,
            'isPast'      => $isPast,
            'startTime'   => substr($t->start_time ?? '', 0, 5),
            'endTime'     => substr($t->end_time ?? '', 0, 5),
            'time'        => substr($t->start_time ?? '', 0, 5) . ' - ' . substr($t->end_time ?? '', 0, 5) . ' WIB',
            'dateTime'    => $startDate->format('d F Y') . ', ' . substr($t->start_time ?? '', 0, 5) . ' - ' . substr($t->end_time ?? '', 0, 5) . ' WIB',
            'location'    => $t->location,
            'instructor'  => $t->instructor,
            'description' => $t->description ?? '',
            'quota'       => (int) $t->quota,
            'enrolled'    => (int) $enrolled,
            'isFull'      => $enrolled >= $quota,
            'quotaText'   => "{$enrolled} / {$t->quota} Seats" . ($enrolled >= $quota ? ' (Full)' : ''),
        ];
    })->values()->toArray();
@endphp
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Auto-switch tab based on URL parameters (e.g. ?tab=available or ?page=2)
        const urlParams = new URLSearchParams(window.location.search);
        const activeTab = urlParams.get('tab');
        const hasPageParam = urlParams.has('page');

        // If tab parameter is 'available' OR a page parameter is present without explicit tab
        if (activeTab === 'available' || (hasPageParam && !activeTab)) {
            const availableTabBtn = document.querySelector('[data-bs-target="#tab-available"], [data-bs-target="#available-training"], [href="#tab-available"], [href="#available-training"], #tab-available-btn, #available-tab');
            if (availableTabBtn) {
                if (typeof bootstrap !== 'undefined' && bootstrap.Tab) {
                    const tab = bootstrap.Tab.getInstance(availableTabBtn) || new bootstrap.Tab(availableTabBtn);
                    tab.show();
                } else {
                    availableTabBtn.click();
                }
            }
        } else if (activeTab === 'history') {
            const historyTabBtn = document.querySelector('[data-bs-target="#tab-history"], [href="#tab-history"], #tab-history-btn');
            if (historyTabBtn) {
                if (typeof bootstrap !== 'undefined' && bootstrap.Tab) {
                    const tab = bootstrap.Tab.getInstance(historyTabBtn) || new bootstrap.Tab(historyTabBtn);
                    tab.show();
                } else {
                    historyTabBtn.click();
                }
            }
        }

        // Sync URL with active tab when user manually changes tabs
        const tabButtons = document.querySelectorAll('#trainingScheduleTabs button[data-bs-toggle="pill"]');
        tabButtons.forEach(btn => {
            btn.addEventListener('shown.bs.tab', function (e) {
                const target = e.target.getAttribute('data-bs-target');
                const url = new URL(window.location.href);
                if (target === '#tab-available') {
                    url.searchParams.set('tab', 'available');
                } else if (target === '#tab-history') {
                    url.searchParams.set('tab', 'history');
                } else if (target === '#tab-calendar') {
                    url.searchParams.delete('tab');
                    url.searchParams.delete('page');
                }
                window.history.replaceState({}, '', url);
            });
        });

        // --- Live Calendar Events Store ---
        const calendarEvents = @json($computedCalendarEvents);
        const csrfToken = '{{ csrf_token() }}';

        const monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        const dayOfWeekNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        function formatCalendarDate(y, m, d) {
            const dateObj = new Date(y, m, d);
            return `${dayOfWeekNames[dateObj.getDay()]}, ${d} ${monthNames[m]} ${y}`;
        }

        // Calendar initial date defaults to current real-time date (new Date())
        const initialDate = new Date();
        let currentCalYear = initialDate.getFullYear();
        let currentCalMonth = initialDate.getMonth();
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
                        const realToday = new Date();
                        const todayDateFormatted = `${realToday.getFullYear()}-${String(realToday.getMonth() + 1).padStart(2, '0')}-${String(realToday.getDate()).padStart(2, '0')}`;
                        const isToday = (cellDateFormatted === todayDateFormatted);
                        
                        // Find events on this day (spanning startDate to endDate inclusive)
                        const activeTrainingsForDay = calendarEvents.filter(e => {
                            const s = e.startDate || `${e.year}-${String(e.month + 1).padStart(2, '0')}-${String(e.day).padStart(2, '0')}`;
                            const end = e.endDate || s;
                            return cellDateFormatted >= s && cellDateFormatted <= end;
                        });

                        let badgesHtml = '';
                        if (activeTrainingsForDay.length > 0) {
                            const nowTimeMs = new Date().getTime();
                            const counts = { open: 0, private: 0, running: 0, ended: 0 };

                            activeTrainingsForDay.forEach(e => {
                                const s = e.startDate || `${e.year}-${String(e.month + 1).padStart(2, '0')}-${String(e.day).padStart(2, '0')}`;
                                const end = e.endDate || s;
                                const regClose = e.registration_close_date || e.registrationCloseDate || s;
                                const isPublished = (e.is_published === undefined || e.is_published === null || e.is_published === true || e.is_published === 1);
                                const rawStatus = (e.rawStatus || e.status || '').toLowerCase().trim();
                                const isOpenStatus = !['closed', 'private', 'draft'].includes(rawStatus);

                                let cellStatus;
                                if (cellDateFormatted < todayDateFormatted) {
                                    // Past dates in calendar always show as Ended
                                    cellStatus = 'ended';
                                } else if (cellDateFormatted === todayDateFormatted) {
                                    // Current date ONLY shows as Running if the training execution overlaps today
                                    if (s <= todayDateFormatted && end >= todayDateFormatted) {
                                        cellStatus = 'running';
                                    } else {
                                        cellStatus = (regClose >= todayDateFormatted && isPublished && isOpenStatus) ? 'open' : 'private';
                                    }
                                } else {
                                    // Future dates in calendar show Open or Private based on registration date and status
                                    if (regClose >= todayDateFormatted && isPublished && isOpenStatus) {
                                        cellStatus = 'open';
                                    } else {
                                        cellStatus = 'private';
                                    }
                                }
                                counts[cellStatus] = (counts[cellStatus] || 0) + 1;
                            });

                            let pillsHtml = '';
                            if (counts.open > 0) {
                                pillsHtml += `
                                    <span class="badge bg-success-light text-success border border-success-subtle rounded-pill extra-small py-1 px-2 fw-semibold cal-status-badge cal-status-badge-open"
                                          role="button"
                                          data-year="${year}"
                                          data-month="${month}"
                                          data-day="${thisDay}"
                                          data-status="open"
                                          title="${counts.open} Open Training(s)">
                                        Open: ${counts.open}
                                    </span>
                                `;
                            }
                            if (counts.private > 0) {
                                pillsHtml += `
                                    <span class="badge rounded-pill extra-small py-1 px-2 fw-semibold cal-status-badge cal-status-badge-private"
                                          style="background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;"
                                          role="button"
                                          data-year="${year}"
                                          data-month="${month}"
                                          data-day="${thisDay}"
                                          data-status="private"
                                          title="${counts.private} Private Training(s)">
                                        Private: ${counts.private}
                                    </span>
                                `;
                            }
                            if (counts.running > 0) {
                                pillsHtml += `
                                    <span class="badge bg-warning-light text-warning border border-warning-subtle rounded-pill extra-small py-1 px-2 fw-semibold cal-status-badge cal-status-badge-running"
                                          role="button"
                                          data-year="${year}"
                                          data-month="${month}"
                                          data-day="${thisDay}"
                                          data-status="running"
                                          title="${counts.running} Running Training(s)">
                                        Running: ${counts.running}
                                    </span>
                                `;
                            }
                            if (counts.ended > 0) {
                                pillsHtml += `
                                    <span class="badge bg-dark-light text-dark rounded-pill extra-small py-1 px-2 fw-semibold cal-status-badge cal-status-badge-ended"
                                          style="background-color: #475569; color: #ffffff; border: 1px solid #334155;"
                                          role="button"
                                          data-year="${year}"
                                          data-month="${month}"
                                          data-day="${thisDay}"
                                          data-status="ended"
                                          title="${counts.ended} Ended Training(s)">
                                        Ended: ${counts.ended}
                                    </span>
                                `;
                            }

                            if (pillsHtml) {
                                badgesHtml = `<div class="mt-1 d-flex flex-column gap-1">${pillsHtml}</div>`;
                            }
                        }

                        const dayNumDisplay = isToday 
                            ? `<span class="cal-today-badge">${thisDay}</span>`
                            : `<span class="${isWeekend ? 'text-danger' : ''}">${thisDay}</span>`;

                        rowHtml += `<td class="p-1 cal-day-cell ${isToday ? 'is-today' : ''}" role="button" data-cal-date="${cellDateFormatted}" data-year="${year}" data-month="${month}" data-day="${thisDay}">
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


        // --- Helper to evaluate event status on a specific date ---
        function getEventStatusOnDate(item, targetDateStr, todayDateFormatted) {
            const s = item.startDate || `${item.year}-${String(item.month + 1).padStart(2, '0')}-${String(item.day).padStart(2, '0')}`;
            const end = item.endDate || s;
            const regClose = item.registration_close_date || item.registrationCloseDate || s;
            const isPublished = (item.is_published === undefined || item.is_published === null || item.is_published === true || item.is_published === 1);
            const rawStatus = (item.rawStatus || item.status || '').toLowerCase().trim();
            const isOpenStatus = !['closed', 'private', 'draft'].includes(rawStatus);

            if (targetDateStr < todayDateFormatted) {
                // Past dates in calendar always show as Ended
                return 'ended';
            } else if (targetDateStr === todayDateFormatted) {
                // Current date ONLY shows as Running if the training execution overlaps today
                if (s <= todayDateFormatted && end >= todayDateFormatted) {
                    return 'running';
                } else {
                    return (regClose >= todayDateFormatted && isPublished && isOpenStatus) ? 'open' : 'private';
                }
            } else {
                // Future dates in calendar show Open or Private based on registration date and status
                if (regClose >= todayDateFormatted && isPublished && isOpenStatus) {
                    return 'open';
                } else {
                    return 'private';
                }
            }
        }

        // --- Open Training List Modal Function ---
        function openTrainingListModal(year, month, day, status, highlightId = null) {
            currentModalStatus = status;
            const formattedDate = formatCalendarDate(year, month, day);
            const targetDateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const todayMidnight = new Date();
            todayMidnight.setHours(0, 0, 0, 0);
            const todayY = todayMidnight.getFullYear();
            const todayM = String(todayMidnight.getMonth() + 1).padStart(2, '0');
            const todayD = String(todayMidnight.getDate()).padStart(2, '0');
            const todayDateFormatted = `${todayY}-${todayM}-${todayD}`;

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
            } else if (status && ['open', 'private', 'closed', 'running', 'ended'].includes(status)) {
                const targetStatus = (status === 'closed' ? 'private' : status);
                const filteredByStatus = matchingEvents.filter(e => {
                    const st = getEventStatusOnDate(e, targetDateStr, todayDateFormatted);
                    return st === targetStatus || (targetStatus === 'private' && (st === 'private' || st === 'closed'));
                });
                if (filteredByStatus.length > 0) {
                    matchingEvents = filteredByStatus;
                }
            }

            const isOpen = (status === 'open');
            const isRunning = (status === 'running');
            const isEnded = (status === 'ended' || (targetDateStr < todayDateFormatted && !isOpen && !isRunning));
            const isPrivate = (status === 'private' || status === 'closed');

            const modalStatusTitle = document.getElementById('modalStatusTitle');
            const modalStatusCountBadge = document.getElementById('modalStatusCountBadge');
            const modalStatusIconWrap = document.getElementById('modalStatusIconWrap');
            const modalStatusIcon = document.getElementById('modalStatusIcon');
            const modalSelectedDateText = document.getElementById('modalSelectedDateText');
            const modalTrainingItemsList = document.getElementById('modalTrainingItemsList');
            const modalEmptyState = document.getElementById('modalEmptyState');

            // Header Elements
            if (modalStatusTitle) {
                if (isRunning) {
                    modalStatusTitle.textContent = 'Running Trainings';
                } else if (isEnded) {
                    modalStatusTitle.textContent = 'Completed / Past Trainings';
                } else if (isOpen) {
                    modalStatusTitle.textContent = 'Open Trainings';
                } else {
                    modalStatusTitle.textContent = 'Private / Invitation-Only Trainings';
                }
            }
            if (modalSelectedDateText) {
                modalSelectedDateText.textContent = formattedDate;
            }
            if (modalStatusCountBadge) {
                if (isRunning) {
                    modalStatusCountBadge.className = 'badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill fw-semibold px-2-5 py-1 fs-6';
                    modalStatusCountBadge.style.backgroundColor = '';
                    modalStatusCountBadge.style.color = '';
                    modalStatusCountBadge.style.border = '';
                    modalStatusCountBadge.textContent = `${matchingEvents.length} Running`;
                } else if (isEnded) {
                    modalStatusCountBadge.className = 'badge rounded-pill fw-semibold px-2-5 py-1 fs-6';
                    modalStatusCountBadge.style.backgroundColor = '#475569';
                    modalStatusCountBadge.style.color = '#ffffff';
                    modalStatusCountBadge.style.border = '1px solid #334155';
                    modalStatusCountBadge.textContent = `${matchingEvents.length} Ended`;
                } else if (isOpen) {
                    modalStatusCountBadge.className = 'badge bg-emerald-100 text-emerald-700 rounded-pill fw-semibold px-2-5 py-1 fs-6';
                    modalStatusCountBadge.style.backgroundColor = '#d1fae5';
                    modalStatusCountBadge.style.color = '#047857';
                    modalStatusCountBadge.style.border = '1px solid #a7f3d0';
                    modalStatusCountBadge.textContent = `${matchingEvents.length} Open`;
                } else {
                    modalStatusCountBadge.className = 'badge rounded-pill fw-semibold px-2-5 py-1 fs-6';
                    modalStatusCountBadge.style.backgroundColor = '#e0f2fe';
                    modalStatusCountBadge.style.color = '#0369a1';
                    modalStatusCountBadge.style.border = '1px solid #bae6fd';
                    modalStatusCountBadge.textContent = `${matchingEvents.length} Private`;
                }
            }
            if (modalStatusIconWrap) {
                if (isRunning) {
                    modalStatusIconWrap.style.backgroundColor = '#fffbeb';
                    modalStatusIconWrap.style.color = '#d97706';
                } else if (isEnded) {
                    modalStatusIconWrap.style.backgroundColor = '#f1f5f9';
                    modalStatusIconWrap.style.color = '#475569';
                } else if (isOpen) {
                    modalStatusIconWrap.style.backgroundColor = '#d1fae5';
                    modalStatusIconWrap.style.color = '#047857';
                } else {
                    modalStatusIconWrap.style.backgroundColor = '#e0f2fe';
                    modalStatusIconWrap.style.color = '#0369a1';
                }
            }
            if (modalStatusIcon) {
                modalStatusIcon.className = isRunning ? 'ti ti-player-play fs-2' : (isEnded ? 'ti ti-check fs-2' : (isOpen ? 'ti ti-circle-check fs-2' : 'ti ti-lock-access fs-2'));
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
                        const itemStartDateStr = item.startDate || `${item.year}-${String(item.month + 1).padStart(2, '0')}-${String(item.day).padStart(2, '0')}`;
                        const itemEndDateStr = item.endDate || item.startDate || itemStartDateStr;
                        const closeDateStr = item.registration_close_date || item.registrationCloseDate || itemStartDateStr;

                        // Exact boundary normalized date-time comparison
                        let isDeadlinePassed = false;
                        if (closeDateStr) {
                            const closeTimeStr = item.registration_close_time || item.registrationCloseTime || '23:59';
                            const [cHours, cMinutes] = closeTimeStr.split(':').map(Number);
                            const parts = closeDateStr.split('-');
                            if (parts.length === 3) {
                                const regCloseDate = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10), cHours || 23, cMinutes !== undefined ? cMinutes : 59, 59, 999);
                                isDeadlinePassed = regCloseDate.getTime() < new Date().getTime();
                            } else {
                                isDeadlinePassed = (closeDateStr < todayDateFormatted);
                            }
                        }

                        const isEndedSession = isEnded || (targetDateStr < todayDateFormatted) || (itemEndDateStr < todayDateFormatted) || (item.rawStatus === 'Completed') || (item.isPast === true);

                        // Active daytime session hours check
                        const now = new Date();
                        const currentHoursMinutes = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
                        const itemStart = (item.startTime || '').substring(0, 5);
                        const itemEnd = (item.endTime || '').substring(0, 5);
                        const isTimeActive = itemStart && itemEnd && (currentHoursMinutes >= itemStart && currentHoursMinutes <= itemEnd);

                        const rawStatusLower = (item.rawStatus || '').toLowerCase().trim();
                        const isRawRunning = (rawStatusLower === 'running') || (rawStatusLower === 'in progress');
                        const isRawClosed = (rawStatusLower === 'closed') || (rawStatusLower === 'completed');

                        // Running session check:
                        // Only running on this date if explicitly marked Running, OR clicked date is today and currently active within start/end dates & time
                        const isRunningToday = (targetDateStr === todayDateFormatted) && isTimeActive && (itemStartDateStr <= todayDateFormatted && itemEndDateStr >= todayDateFormatted);
                        const isRunningSession = isRawRunning || isRunningToday;

                        // Quota availability check
                        const enrolledCount = typeof item.enrolled === 'number' ? item.enrolled : (parseInt(item.enrolled, 10) || 0);
                        const quotaLimit = typeof item.quota === 'number' ? item.quota : (parseInt(item.quota, 10) || 1);
                        const isQuotaFull = item.isFull === true || (quotaLimit > 0 && enrolledCount >= quotaLimit);

                        // Open Training evaluation:
                        // now() <= registration_close_date AND registration_status === 'Open' AND quota available
                        const isStatusOpen = (rawStatusLower === 'open' || rawStatusLower === 'open registration' || item.status === 'open');
                        const isItemOpen = (!isDeadlinePassed) && (!isEndedSession) && isStatusOpen && (!isRunningSession) && (!isQuotaFull) && (!isRawClosed);

                        let badgeHtml = '';
                        let actionBtnHtml = '';

                        if (isItemOpen) {
                            badgeHtml = `
                                <span class="badge px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700" style="background-color: #d1fae5; color: #047857; border: 1px solid #a7f3d0;">
                                    Open Registration
                                </span>
                            `;
                            // Commented out register button per instruction
                            /*
                            actionBtnHtml = `
                                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 py-1-5 fw-semibold btn-register-action" data-id="${item.id}" data-title="${(item.title || '').replace(/"/g, '&quot;')}" style="background-color: #003b6d; border-color: #003b6d;">
                                    Register
                                </button>
                            `;
                            */
                        } else if ((isDeadlinePassed || isRawClosed || isQuotaFull) && !isEndedSession && !isRunningSession) {
                            badgeHtml = `
                                <span class="badge rounded-pill px-3 py-1-5 fw-semibold" style="background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;">
                                    Private (Invitation-Only)
                                </span>
                            `;
                            actionBtnHtml = '';
                        } else {
                            // For Ended / Running status: Keep this container empty so only essential training info is listed
                            badgeHtml = '';
                            actionBtnHtml = '';
                        }

                        listHtml += `
                            <div class="p-3 rounded-3 border ${isCurrentItem ? 'border-primary shadow-sm' : 'border-light-subtle'} bg-white hover-shadow-sm d-flex align-items-center justify-content-between gap-3">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="inline-flex items-center justify-center px-3 py-1.5 bg-sky-100 text-sky-800 text-xs font-semibold rounded-md whitespace-nowrap shrink-0 w-auto min-w-max" style="background-color: #e0f2fe; color: #075985; font-size: 0.75rem; line-height: 1rem; border-radius: 0.375rem; white-space: nowrap; flex-shrink: 0; width: auto; min-width: max-content;">
                                        ${item.time}
                                    </span>
                                    <div>
                                        <div class="fw-bold text-dark fs-3 mb-1">${item.title}</div>
                                        <div class="d-flex align-items-center gap-2 text-muted small">
                                            <span>${item.location}</span>
                                            <span>&bull;</span>
                                            <span class="badge rounded-pill bg-light text-secondary border px-2 py-0-5">${item.category}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end flex-shrink-0 d-flex align-items-center gap-2">
                                    ${badgeHtml}
                                    ${actionBtnHtml}
                                </div>
                            </div>
                        `;
                    });
                    modalTrainingItemsList.innerHTML = listHtml;

                    // Bind Register button click: prompt confirmation and submit POST request to registration route
                    modalTrainingItemsList.querySelectorAll('.btn-register-action').forEach(btn => {
                        btn.addEventListener('click', function () {
                            const id = this.getAttribute('data-id');
                            const title = this.getAttribute('data-title') || 'this training';
                            if (confirm(`Register for ${title}?`)) {
                                const form = document.createElement('form');
                                form.method = 'POST';
                                form.action = `/trainingplan/${id}/register`;
                                const csrfInput = document.createElement('input');
                                csrfInput.type = 'hidden';
                                csrfInput.name = '_token';
                                csrfInput.value = csrfToken;
                                form.appendChild(csrfInput);
                                document.body.appendChild(form);
                                form.submit();
                            }
                        });
                    });
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
                    const now = new Date();
                    const realTodayFormatted = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
                    const currentHoursMinutes = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;

                    let html = '';
                    matchingEvents.forEach(item => {
                        const isDateToday = (targetDateStr === realTodayFormatted);
                        const itemStart = (item.startTime || '').substring(0, 5);
                        const itemEnd = (item.endTime || '').substring(0, 5);
                        const isTimeActive = itemStart && itemEnd && (currentHoursMinutes >= itemStart && currentHoursMinutes <= itemEnd);
                        const isLiveNow = (isDateToday && isTimeActive) || (isDateToday && item.rawStatus === 'Running');

                        const liveBadgeHtml = isLiveNow ? `
                            <div class="mb-2">
                                <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-[#e60023] text-white rounded-full shadow-sm w-fit shrink-0" style="background-color: #e60023; border-radius: 9999px; width: fit-content; max-width: max-content;">
                                    <!-- YouTube Broadcast Signal Icon ((•)) -->
                                    <svg class="w-3.5 h-3.5 shrink-0 animate-pulse" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" style="width: 14px; height: 14px; overflow: visible;">
                                        <!-- Center Dot -->
                                        <circle cx="12" cy="12" r="1.5" fill="currentColor" stroke="none" />
                                        <!-- Inner Waves -->
                                        <path stroke-linecap="round" d="M8.5 9a4.5 4.5 0 0 0 0 6M15.5 9a4.5 4.5 0 0 1 0 6" />
                                        <!-- Outer Waves -->
                                        <path stroke-linecap="round" d="M6 6.5a8 8 0 0 0 0 11M18 6.5a8 8 0 0 1 0 11" />
                                    </svg>
                                    <span class="text-[11px] font-bold tracking-wider uppercase leading-none" style="font-size: 11px; line-height: 1;">LIVE</span>
                                </div>
                            </div>
                        ` : '';

                        const isRunning = isDateToday || (item.rawStatus || '').toLowerCase() === 'running' || item.status === 'running';
                        const runningBadgeHtml = isRunning ? `<span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2 py-0-5 small fw-semibold">Running</span>` : '';

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
                                ${liveBadgeHtml}
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="badge bg-primary text-white rounded-pill px-2 py-0-5 small fw-semibold">${item.time}</span>
                                    <div class="d-flex align-items-center gap-1">
                                        ${runningBadgeHtml}
                                        <span class="badge bg-primary-lt text-primary small">${item.category}</span>
                                    </div>
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
                    const now = new Date();
                    openTrainingListModal(now.getFullYear(), now.getMonth(), now.getDate(), 'open', id);
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
        const monthSelect = document.getElementById('filterTrainingMonth');
        const yearSelect = document.getElementById('filterTrainingYear');
        const btnResetFilter = document.getElementById('btnResetFilter');
        const availableTotalBadge = document.getElementById('availableTrainingTotalBadge');

        function filterAvailableTraining() {
            const query = (searchInput?.value || '').toLowerCase().trim();
            const category = categorySelect?.value || '';
            const status = statusSelect?.value || '';
            const month = monthSelect?.value || '';
            const year = yearSelect?.value || '';

            const rows = document.querySelectorAll('#availableTrainingTable tbody tr.training-table-row, .training-card-item');
            let visibleCount = 0;

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const rowCat = row.getAttribute('data-category') || '';
                const rowStatus = row.getAttribute('data-status') || '';
                const rowMonth = row.getAttribute('data-month') || '';
                const rowYear = row.getAttribute('data-year') || '';

                const rowInstructor = (row.getAttribute('data-instructor') || '').toLowerCase();
                const matchesSearch = !query || text.includes(query) || rowInstructor.includes(query);
                const matchesCategory = !category || rowCat === category;
                const matchesStatus = !status || rowStatus === status;
                const matchesMonth = !month || parseInt(rowMonth, 10) === parseInt(month, 10);
                const matchesYear = !year || rowYear === year;

                if (matchesSearch && matchesCategory && matchesStatus && matchesMonth && matchesYear) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (availableTotalBadge && (query || category || status || month || year)) {
                availableTotalBadge.textContent = `Total: ${visibleCount} Training${visibleCount === 1 ? '' : 's'}`;
            }
        }

        if (searchInput) searchInput.addEventListener('input', filterAvailableTraining);
        if (categorySelect) categorySelect.addEventListener('change', filterAvailableTraining);
        if (statusSelect) statusSelect.addEventListener('change', filterAvailableTraining);
        if (monthSelect) monthSelect.addEventListener('change', filterAvailableTraining);
        if (yearSelect) yearSelect.addEventListener('change', filterAvailableTraining);

        if (btnResetFilter) {
            btnResetFilter.addEventListener('click', function () {
                if (searchInput) searchInput.value = '';
                if (categorySelect) categorySelect.value = '';
                if (statusSelect) statusSelect.value = '';
                if (monthSelect) monthSelect.value = '';
                if (yearSelect) yearSelect.value = '';
                filterAvailableTraining();
            });
        }
    });
</script>
@endpush