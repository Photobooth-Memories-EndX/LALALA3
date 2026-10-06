@php
    $now = \Carbon\Carbon::now();
    $todayDateStr = $now->format('Y-m-d');
    $currentTimeHM = $now->format('H:i');
@endphp

{{-- Right Column: Today's Agenda Panel (col-lg-4) --}}
<div class="col-lg-4">
    <div class="card border border-light-subtle shadow-sm rounded-4 h-100 bg-white">
        <div class="card-body p-4 d-flex flex-column">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-sm rounded-3 d-flex align-items-center justify-content-center"
                         style="background-color: #eef2ff; color: #003b6d;">
                        <i class="ti ti-calendar-time fs-2"></i>
                    </div>
                    <h4 class="fw-bold mb-0 text-dark">Today's Agenda</h4>
                </div>
                <span class="badge bg-primary text-white rounded-pill px-2 py-1 fw-semibold small">
                    Active Date
                </span>
            </div>
            
            <div class="text-primary fw-semibold small mb-3" id="todayAgendaDateLabel">
                <i class="ti ti-clock me-1"></i> {{ $activeCarbonDate->format('l, d F Y') }}
            </div>

            {{-- Timeline / Agenda List --}}
            <div class="timeline-schedule flex-grow-1" id="agendaTimelineList">
                @forelse ($todaysAgenda as $item)
                    @php
                        $title = $item->title ?? ($item->course_name ?? 'Training Schedule');
                        $startDate = \Carbon\Carbon::parse($item->start_date);
                        $startTime = substr($item->start_time, 0, 5);
                        $endTime = substr($item->end_time, 0, 5);

                        $sDate = \Carbon\Carbon::parse($item->start_date)->format('Y-m-d');
                        $eDate = \Carbon\Carbon::parse($item->end_date ?? $item->start_date)->format('Y-m-d');
                        $isDateToday = ($todayDateStr >= $sDate && $todayDateStr <= $eDate);
                        $isTimeActive = ($currentTimeHM >= $startTime && $currentTimeHM <= $endTime);
                        $isLive = ($isDateToday && $isTimeActive) || ($isDateToday && ($item->status ?? '') === 'Running');
                    @endphp
                    <div class="timeline-schedule-item mb-3 p-3 rounded-3 border border-light-subtle bg-light-subtle position-relative"
                         role="button"
                         data-id="{{ $item->id }}"
                         data-title="{{ $title }}"
                         data-category="{{ $item->category }}"
                         data-date="{{ $startDate->format('d F Y') }}, {{ $startTime }} - {{ $endTime }} WIB"
                         data-location="{{ $item->location }}"
                         data-instructor="{{ $item->instructor }}"
                         data-description="{{ $item->description ?? '' }}">
                        @if ($isLive)
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
                        @endif
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="badge bg-primary text-white rounded-pill px-2 py-0-5 small fw-semibold">{{ $startTime }} - {{ $endTime }} WIB</span>
                            <span class="badge bg-primary-lt text-primary small">{{ $item->category }}</span>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">{{ $title }}</h5>
                        <div class="text-muted small d-flex align-items-center gap-1 mb-1">
                            <i class="ti ti-map-pin text-primary"></i>
                            <span>{{ $item->location }}</span>
                        </div>
                        <div class="text-muted small d-flex align-items-center gap-1">
                            <i class="ti ti-user-check text-primary"></i>
                            <span>{{ $item->instructor }}</span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5 px-3">
                        <div class="mb-2 text-muted opacity-50">
                            <i class="ti ti-calendar-off fs-1"></i>
                        </div>
                        <h6 class="fw-semibold text-dark mb-1">No Training Scheduled</h6>
                        <p class="text-muted extra-small mb-0">There are no offline training sessions scheduled for this date.</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-auto pt-2 border-top border-light-subtle">
                <button type="button" class="btn btn-outline-primary w-100 rounded-3 py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-2 btn-switch-to-available">
                    <span>Explore All Available Training</span>
                    <i class="ti ti-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>
