@php
    $trainings = $trainings ?? \App\Models\OfflineTraining::withCount('participants')->orderBy('start_date', 'asc')->get();
    $activeDateStr = $selectedDate ?? request('selected_date', \Carbon\Carbon::today()->format('Y-m-d'));
    $activeCarbonDate = request()->filled('selected_date') ? \Carbon\Carbon::parse(request('selected_date')) : \Carbon\Carbon::today();
    $todaysAgenda = $todaysAgenda ?? $trainings->filter(function($t) use ($activeDateStr) {
        $sDate = \Carbon\Carbon::parse($t->start_date)->format('Y-m-d');
        $eDate = \Carbon\Carbon::parse($t->end_date ?? $t->start_date)->format('Y-m-d');
        return $activeDateStr >= $sDate && $activeDateStr <= $eDate;
    })->values();
@endphp
<style>
    .cal-day-cell.is-today {
        background-color: #e0f2fe !important;
        border: 2px solid #0284c7 !important;
        position: relative;
        z-index: 1;
    }
    .cal-day-cell.is-today:hover {
        background-color: #bae6fd !important;
    }
</style>

{{-- Tab Calendar & Agenda (Dynamic Interactive JS Calendar) --}}
<div class="row g-4">
    {{-- Left Column: Monthly Calendar Grid (col-lg-8) --}}
    <div class="col-lg-8">
        <div class="card border border-light-subtle shadow-sm rounded-4 h-100 bg-white">
            <div class="card-body p-4">
                {{-- Calendar Header Bar --}}
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                    <div class="d-flex align-items-center gap-2">
                        <h3 class="fw-bold mb-0 text-dark" id="calendarMonthYear">{{ $activeCarbonDate->format('F Y') }}</h3>
                        <div class="btn-group btn-group-sm ms-2" role="group">
                            <button type="button" class="btn btn-outline-secondary px-2 py-1" id="calPrevBtn" title="Previous Month">
                                <i class="ti ti-chevron-left"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary px-2 py-1" id="calNextBtn" title="Next Month">
                                <i class="ti ti-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Calendar Grid Table --}}
                <div class="table-responsive">
                    <table class="table table-bordered border-light-subtle mb-0 text-center cal-grid-table" style="table-layout: fixed;">
                        <thead>
                            <tr class="bg-light-subtle text-muted text-uppercase fs-6">
                                <th class="py-2" style="width: 14.28%;">MON</th>
                                <th class="py-2" style="width: 14.28%;">TUE</th>
                                <th class="py-2" style="width: 14.28%;">WED</th>
                                <th class="py-2" style="width: 14.28%;">THU</th>
                                <th class="py-2" style="width: 14.28%;">FRI</th>
                                <th class="py-2 text-danger" style="width: 14.28%;">SAT</th>
                                <th class="py-2 text-danger" style="width: 14.28%;">SUN</th>
                            </tr>
                        </thead>
                        <tbody id="calGridBody">
                            @php
                                $year = (int)$activeCarbonDate->format('Y');
                                $month = (int)$activeCarbonDate->format('n');
                                $today = \Carbon\Carbon::today()->format('Y-m-d');

                                $firstDayOfMonth = \Carbon\Carbon::createFromDate($year, $month, 1);
                                $startCol = ($firstDayOfMonth->dayOfWeekIso - 1); // 0 = Mon, 6 = Sun
                                $daysInMonth = $firstDayOfMonth->daysInMonth;
                                $daysInPrevMonth = $firstDayOfMonth->copy()->subMonth()->daysInMonth;
                                $dayCounter = 1;
                                $nextMonthDayCounter = 1;
                            @endphp
                            @for ($row = 0; $row < 6; $row++)
                                <tr>
                                    @for ($col = 0; $col < 7; $col++)
                                        @php
                                            $cellIndex = $row * 7 + $col;
                                            $isWeekend = ($col === 5 || $col === 6);
                                        @endphp
                                        @if ($cellIndex < $startCol)
                                            @php
                                                $prevDate = $daysInPrevMonth - ($startCol - $cellIndex - 1);
                                            @endphp
                                            <td class="p-1 cal-day-cell text-muted bg-light-subtle opacity-50">
                                                <div class="cal-day-num text-end pe-1">{{ $prevDate }}</div>
                                            </td>
                                        @elseif ($dayCounter <= $daysInMonth)
                                            @php
                                                $day = $dayCounter;
                                                $cellDateFormatted = $year . '-' . sprintf('%02d', $month) . '-' . sprintf('%02d', $day);
                                                $isToday = ($cellDateFormatted === $today);

                                                $activeTrainingsForDay = $trainings->filter(function($t) use ($cellDateFormatted) {
                                                    $sDate = \Carbon\Carbon::parse($t->start_date)->format('Y-m-d');
                                                    $eDate = \Carbon\Carbon::parse($t->end_date ?? $t->start_date)->format('Y-m-d');
                                                    return $cellDateFormatted >= $sDate && $cellDateFormatted <= $eDate;
                                                });
                                            @endphp
                                            <td class="p-1 cal-day-cell {{ $isToday ? 'is-today' : '' }}" role="button" data-cal-date="{{ $cellDateFormatted }}" data-year="{{ $year }}" data-month="{{ $month - 1 }}" data-day="{{ $day }}">
                                                <div class="cal-day-num text-end pe-1">
                                                    @if ($isToday)
                                                        <span class="cal-today-badge">{{ $day }}</span>
                                                    @else
                                                        <span class="{{ $isWeekend ? 'text-danger' : '' }}">{{ $day }}</span>
                                                    @endif
                                                </div>
                                                @if ($activeTrainingsForDay->count() > 0)
                                                    <div class="mt-1 d-flex flex-column gap-1">
                                                        @if ($cellDateFormatted < $today)
                                                            <span class="badge rounded-pill extra-small py-1 px-2 cal-status-badge cal-status-badge-closed"
                                                                  style="background-color: #e5e7eb; color: #4b5563; border: 1px solid #d1d5db;"
                                                                  role="button"
                                                                  data-year="{{ $year }}"
                                                                  data-month="{{ $month - 1 }}"
                                                                  data-day="{{ $day }}"
                                                                  data-status="closed"
                                                                  title="{{ $activeTrainingsForDay->count() }} Ended Training(s)">
                                                                Ended: {{ $activeTrainingsForDay->count() }}
                                                            </span>
                                                        @elseif ($cellDateFormatted == $today)
                                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill extra-small py-1 px-2 fw-semibold cal-status-badge cal-status-badge-running"
                                                                  role="button"
                                                                  data-year="{{ $year }}"
                                                                  data-month="{{ $month - 1 }}"
                                                                  data-day="{{ $day }}"
                                                                  data-status="running"
                                                                  title="{{ $activeTrainingsForDay->count() }} Running Training(s)">
                                                                Running: {{ $activeTrainingsForDay->count() }}
                                                            </span>
                                                        @else
                                                            <span class="badge bg-success-subtle text-success rounded-pill extra-small py-1 px-2 fw-semibold cal-status-badge cal-status-badge-open"
                                                                  role="button"
                                                                  data-year="{{ $year }}"
                                                                  data-month="{{ $month - 1 }}"
                                                                  data-day="{{ $day }}"
                                                                  data-status="open"
                                                                  title="{{ $activeTrainingsForDay->count() }} Open Training(s)">
                                                                Open: {{ $activeTrainingsForDay->count() }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                @endif
                                            </td>
                                            @php $dayCounter++; @endphp
                                        @else
                                            <td class="p-1 cal-day-cell text-muted bg-light-subtle opacity-50">
                                                <div class="cal-day-num text-end pe-1">{{ $nextMonthDayCounter }}</div>
                                            </td>
                                            @php $nextMonthDayCounter++; @endphp
                                        @endif
                                    @endfor
                                </tr>
                                @if ($dayCounter > $daysInMonth && $row >= 4)
                                    @break
                                @endif
                            @endfor
                        </tbody>
                    </table>
                </div>

                {{-- Legend --}}
                <div class="d-flex flex-wrap align-items-center gap-3 mt-3 pt-2 text-muted small">
                    <span class="d-inline-flex align-items-center gap-2">
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0-5 rounded-pill fw-semibold">Open</span>
                        <span>Open Registration</span>
                    </span>
                    <span class="d-inline-flex align-items-center gap-2">
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-0-5 rounded-pill fw-semibold">Running</span>
                        <span>Running / Today</span>
                    </span>
                    <span class="d-inline-flex align-items-center gap-2">
                        <span class="badge rounded-pill px-2 py-0-5 cal-status-badge-closed" style="background-color: #e5e7eb; color: #4b5563; border: 1px solid #d1d5db;">Ended</span>
                        <span>Past Training Session</span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Right Column: Today's Agenda Panel (col-lg-4) --}}
    @include('user.trainingplan-user.partials.todays-agenda')
</div>
