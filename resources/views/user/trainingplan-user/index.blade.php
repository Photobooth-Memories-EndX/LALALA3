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

        .cal-event-pill {
            font-size: 0.72rem;
            padding: 3px 6px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s ease;
            text-align: left;
            margin-bottom: 2px;
            border: 1px solid transparent;
        }

        .cal-event-pill:hover {
            transform: scale(1.02);
            filter: brightness(0.95);
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
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

{{-- Pop-up Modal --}}
@include('user.trainingplan-user.modals.schedule-detail-modal')

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- Sample Calendar Events Store ---
        const calendarEvents = [
            {
                id: '1',
                year: 2026,
                month: 8, // September (0-indexed: 8 = September)
                day: 18,
                title: 'Leadership Development Program',
                shortTitle: 'Leadership',
                category: 'Soft Skill',
                badgeClass: 'bg-primary-lt text-primary',
                time: '08:00 - 16:00 WIB',
                dateTime: '18 September 2026, 08:00 - 16:00 WIB',
                location: 'Training Room Building A, 2nd Fl.',
                instructor: 'Dr. Hendra Wijaya, M.M.',
                description: 'This leadership development course is designed to enhance managerial capabilities, team communication, strategic decision-making, and effective delegation skills for current supervisors and emerging managers.',
                quota: '32 / 40 Participants'
            },
            {
                id: '2',
                year: 2026,
                month: 8, // September
                day: 18,
                title: 'Excel Advanced for Data Analysis',
                shortTitle: 'Excel Adv',
                category: 'Technical Skill',
                badgeClass: 'bg-info-lt text-info',
                time: '09:00 - 15:00 WIB',
                dateTime: '18 September 2026, 09:00 - 15:00 WIB',
                location: 'Computer Lab 2, 3rd Fl.',
                instructor: 'Bambang Sujarwo, S.Kom.',
                description: 'Advanced formulas, XLOOKUP/INDEX-MATCH, dynamic array functions, PivotTables, Power Query integration, and automated dashboard visualization using Microsoft Excel.',
                quota: '28 / 30 Participants'
            },
            {
                id: '3',
                year: 2026,
                month: 8, // September
                day: 25,
                title: 'K3 & Safety Awareness in Factory',
                shortTitle: 'K3 Safety',
                category: 'Compliance & Safety',
                badgeClass: 'bg-warning-lt text-warning',
                time: '08:00 - 11:30 WIB',
                dateTime: '25 September 2026, 08:00 - 11:30 WIB',
                location: 'Auditorium Krakatau Posco',
                instructor: 'Ir. Agus Priyono, CSP',
                description: 'Factory occupational health and safety standards refresher, emergency procedures, and zero incident culture.',
                quota: '25 / 40 Participants'
            },
            {
                id: '4',
                year: 2026,
                month: 8, // September
                day: 29,
                title: 'Effective Business Communication',
                shortTitle: 'Business Comm',
                category: 'Soft Skill',
                badgeClass: 'bg-teal-lt text-teal',
                time: '13:00 - 17:00 WIB',
                dateTime: '29 September 2026, 13:00 - 17:00 WIB',
                location: 'Meeting Room Bintang 1',
                instructor: 'Citra Lestari, M.Psi.',
                description: 'Master persuasive executive presentation, professional negotiation, and high-impact corporate writing skills.',
                quota: '18 / 25 Participants'
            },
            {
                id: '5',
                year: 2026,
                month: 7, // August
                day: 14,
                title: 'Basic Fire Fighting & Safety Protocol',
                shortTitle: 'Fire Safety',
                category: 'Compliance & Safety',
                badgeClass: 'bg-warning-lt text-warning',
                time: '08:00 - 12:00 WIB',
                dateTime: '14 August 2026, 08:00 - 12:00 WIB',
                location: 'HSE Training Ground',
                instructor: 'HSE Team Krakatau Posco',
                description: 'Basic industrial fire emergency response, standard fire extinguisher usage, and factory evacuation procedures.',
                quota: '40 / 40 Participants'
            },
            {
                id: '6',
                year: 2026,
                month: 9, // October
                day: 8,
                title: 'PLC Siemens S7-1500 Advanced',
                shortTitle: 'PLC Siemens',
                category: 'Technical Skill',
                badgeClass: 'bg-info-lt text-info',
                time: '09:00 - 16:00 WIB',
                dateTime: '08 October 2026, 09:00 - 16:00 WIB',
                location: 'Automation Workshop',
                instructor: 'Ahmad Fauzi, S.T.',
                description: 'Advanced industrial automation ladder and structured text programming for Siemens S7-1500, PROFINET network diagnostic, and SCADA integration.',
                quota: '15 / 20 Participants'
            }
        ];

        const monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        // Anchor Date: 18 September 2026
        const activeDate = new Date(2026, 8, 18);
        let currentCalYear = activeDate.getFullYear();
        let currentCalMonth = activeDate.getMonth();

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
            const totalCells = 42; // 6 rows * 7 cols

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
                        const isToday = (year === activeDate.getFullYear() && month === activeDate.getMonth() && thisDay === activeDate.getDate());
                        
                        // Find events on this day
                        const dayEvents = calendarEvents.filter(e => e.year === year && e.month === month && e.day === thisDay);

                        let eventsHtml = '';
                        dayEvents.forEach(ev => {
                            eventsHtml += `
                                <div class="cal-event-pill ${ev.badgeClass} fw-semibold text-truncate"
                                     role="button"
                                     data-bs-toggle="modal" 
                                     data-bs-target="#eventDetailModal"
                                     data-id="${ev.id}"
                                     data-title="${ev.title}"
                                     data-category="${ev.category}"
                                     data-date="${ev.dateTime}"
                                     data-location="${ev.location}"
                                     data-instructor="${ev.instructor}"
                                     data-description="${ev.description}"
                                     data-quota="${ev.quota}">
                                    <i class="ti ti-point me-0 d-none d-sm-inline"></i> ${ev.shortTitle}
                                </div>
                            `;
                        });

                        const dayNumDisplay = isToday 
                            ? `<span class="cal-today-badge">${thisDay}</span>`
                            : `<span class="${isWeekend ? 'text-danger' : ''}">${thisDay}</span>`;

                        rowHtml += `<td class="p-1 cal-day-cell">
                            <div class="cal-day-num text-end pe-1">${dayNumDisplay}</div>
                            ${eventsHtml}
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

        // --- Modal Event Detail Populate Logic ---
        const eventDetailModalEl = document.getElementById('eventDetailModal');
        let currentSelectedTrainingId = null;

        if (eventDetailModalEl) {
            eventDetailModalEl.addEventListener('show.bs.modal', function (event) {
                const triggerEl = event.relatedTarget;
                if (!triggerEl) return;

                const id = triggerEl.getAttribute('data-id') || '1';
                const title = triggerEl.getAttribute('data-title') || 'Leadership Development Program';
                const category = triggerEl.getAttribute('data-category') || 'Soft Skill';
                const date = triggerEl.getAttribute('data-date') || '18 September 2026, 08:00 - 16:00 WIB';
                const location = triggerEl.getAttribute('data-location') || 'Training Room Building A, 2nd Fl.';
                const instructor = triggerEl.getAttribute('data-instructor') || 'Dr. Hendra Wijaya, M.M.';
                const description = triggerEl.getAttribute('data-description') || 'Training description is currently unavailable.';
                const quota = triggerEl.getAttribute('data-quota') || '25 / 40 Participants';

                currentSelectedTrainingId = id;

                // Update modal elements
                const modalTitle = document.getElementById('modalEventTitle');
                const modalCategory = document.getElementById('modalEventCategory');
                const modalDateTime = document.getElementById('modalEventDateTime');
                const modalLocation = document.getElementById('modalEventLocation');
                const modalInstructor = document.getElementById('modalEventInstructor');
                const modalDescription = document.getElementById('modalEventDescription');
                const modalQuota = document.getElementById('modalEventQuota');

                if (modalTitle) modalTitle.textContent = title;
                if (modalCategory) modalCategory.textContent = category;
                if (modalDateTime) modalDateTime.textContent = date;
                if (modalLocation) modalLocation.textContent = location;
                if (modalInstructor) modalInstructor.textContent = instructor;
                if (modalDescription) modalDescription.textContent = description;
                if (modalQuota) modalQuota.textContent = quota;
            });
        }

        // View Detail button click in modal: close modal & switch to Available Training tab
        const modalBtnViewDetail = document.getElementById('modalBtnViewDetail');
        if (modalBtnViewDetail) {
            modalBtnViewDetail.addEventListener('click', function () {
                const modalInstance = bootstrap.Modal.getInstance(eventDetailModalEl) || new bootstrap.Modal(eventDetailModalEl);
                modalInstance.hide();

                const availableTabBtn = document.getElementById('tab-available-btn');
                if (availableTabBtn) {
                    const tabTrigger = new bootstrap.Tab(availableTabBtn);
                    tabTrigger.show();

                    if (currentSelectedTrainingId) {
                        setTimeout(() => {
                            const targetCard = document.querySelector(`.training-card-item[data-id="${currentSelectedTrainingId}"]`);
                            if (targetCard) {
                                targetCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                targetCard.classList.add('border-primary', 'shadow');
                                setTimeout(() => {
                                    targetCard.classList.remove('border-primary', 'shadow');
                                }, 2000);
                            }
                        }, 300);
                    }
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
        const cards = document.querySelectorAll('.training-card-item');

        function filterCards() {
            const query = (searchInput?.value || '').toLowerCase().trim();
            const category = categorySelect?.value || '';
            const status = statusSelect?.value || '';

            cards.forEach(card => {
                const cardTitle = (card.getAttribute('data-title') || '').toLowerCase();
                const cardCat = card.getAttribute('data-category') || '';
                const cardStatus = card.getAttribute('data-status') || '';

                const matchesSearch = !query || cardTitle.includes(query) || card.textContent.toLowerCase().includes(query);
                const matchesCategory = !category || cardCat === category;
                const matchesStatus = !status || cardStatus === status;

                if (matchesSearch && matchesCategory && matchesStatus) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        if (searchInput) searchInput.addEventListener('input', filterCards);
        if (categorySelect) categorySelect.addEventListener('change', filterCards);
        if (statusSelect) statusSelect.addEventListener('change', filterCards);

        if (btnResetFilter) {
            btnResetFilter.addEventListener('click', function () {
                if (searchInput) searchInput.value = '';
                if (categorySelect) categorySelect.value = '';
                if (statusSelect) statusSelect.value = '';
                filterCards();
            });
        }
    });
</script>
@endpush