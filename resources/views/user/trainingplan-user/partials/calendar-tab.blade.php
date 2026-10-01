@php
    $trainings = $trainings ?? \App\Models\OfflineTraining::withCount('participants')->orderBy('start_date', 'asc')->get();
    $firstDate = $trainings->first() ? \Carbon\Carbon::parse($trainings->first()->start_date) : \Carbon\Carbon::now();
@endphp
{{-- Tab Calendar & Agenda (Dynamic Interactive JS Calendar) --}}
<div class="row g-4">
    {{-- Left Column: Monthly Calendar Grid (col-lg-8) --}}
    <div class="col-lg-8">
        <div class="card border border-light-subtle shadow-sm rounded-4 h-100 bg-white">
            <div class="card-body p-4">
                {{-- Calendar Header Bar --}}
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                    <div class="d-flex align-items-center gap-2">
                        <h3 class="fw-bold mb-0 text-dark" id="calendarMonthYear">{{ $firstDate->format('F Y') }}</h3>
                        <div class="btn-group btn-group-sm ms-2" role="group">
                            <button type="button" class="btn btn-outline-secondary px-2 py-1" id="calPrevBtn" title="Previous Month">
                                <i class="ti ti-chevron-left"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary px-2 py-1" id="calNextBtn" title="Next Month">
                                <i class="ti ti-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-primary fw-semibold px-3 py-1-5 rounded-pill" id="calTodayBtn">
                            <i class="ti ti-calendar-event me-1"></i> Today
                        </button>
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
                            {{-- Dynamically populated by JavaScript --}}
                        </tbody>
                    </table>
                </div>

                {{-- Legend --}}
                <div class="d-flex flex-wrap align-items-center gap-3 mt-3 pt-2 text-muted small">
                    <span class="d-inline-flex align-items-center gap-2">
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0-5 rounded-pill fw-semibold">Open: X</span>
                        <span>Open Registration</span>
                    </span>
                    <span class="d-inline-flex align-items-center gap-2">
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-0-5 rounded-pill fw-semibold">Close: Y</span>
                        <span>Registration Closed</span>
                    </span>
                </div>
            </div>
        </div>
    </div>

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
                    <i class="ti ti-clock me-1"></i> {{ $firstDate->format('l, d F Y') }}
                </div>

                {{-- Timeline / Agenda List --}}
                <div class="timeline-schedule flex-grow-1" id="agendaTimelineList">
                    @forelse ($trainings as $item)
                        @php
                            $startDate = \Carbon\Carbon::parse($item->start_date);
                            $startTime = substr($item->start_time, 0, 5);
                            $endTime = substr($item->end_time, 0, 5);
                        @endphp
                        <div class="timeline-schedule-item mb-3 p-3 rounded-3 border border-light-subtle bg-light-subtle position-relative"
                             role="button"
                             data-id="{{ $item->id }}"
                             data-title="{{ $item->title }}"
                             data-category="{{ $item->category }}"
                             data-date="{{ $startDate->format('d F Y') }}, {{ $startTime }} - {{ $endTime }} WIB"
                             data-location="{{ $item->location }}"
                             data-instructor="{{ $item->instructor }}"
                             data-description="{{ $item->description ?? '' }}">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="badge bg-primary text-white rounded-pill px-2 py-0-5 small fw-semibold">{{ $startTime }} - {{ $endTime }} WIB</span>
                                <span class="badge bg-primary-lt text-primary small">{{ $item->category }}</span>
                            </div>
                            <h5 class="fw-bold text-dark mb-1">{{ $item->title }}</h5>
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
                        <div class="text-center py-5 text-muted">
                            <div class="avatar avatar-lg rounded-circle bg-light text-muted mb-2 mx-auto">
                                <i class="ti ti-calendar-off fs-1"></i>
                            </div>
                            <h5 class="fw-bold text-dark mb-1">No Scheduled Agenda</h5>
                            <p class="small text-muted mb-0">No training schedules found. Click '+ Add Training Schedule' in Manage page to publish new trainings.</p>
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
</div>
