<div class="modal fade" id="modal-assessment-access" tabindex="-1" role="dialog"
     aria-labelledby="assessment-access-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2" id="assessment-access-title">
                    <i class="ti ti-clipboard-list text-warning"></i>
                    Assessment Access
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="home-modern-assessment-schedules mb-3">
                    @forelse(($upcomingDeadlines ?? collect()) as $assessment)
                        @php
                            $assessmentNow = \Carbon\Carbon::now();
                            $assessmentFrom = filled($assessment->fromdate)
                                ? \Carbon\Carbon::parse($assessment->fromdate)
                                : null;
                            $assessmentDue = filled($assessment->todate)
                                ? \Carbon\Carbon::parse($assessment->todate)
                                : null;

                            if (!$assessmentDue) {
                                $assessmentStatus = 'Available';
                                $assessmentStatusClass = 'no-deadline';
                                $assessmentDueLabel = 'No Deadline';
                            } elseif ($assessmentNow->gt($assessmentDue)) {
                                $assessmentStatus = 'Overdue';
                                $assessmentStatusClass = 'overdue';
                                $assessmentDueLabel = 'Overdue';
                            } elseif ($assessmentFrom && $assessmentNow->lt($assessmentFrom)) {
                                $assessmentStatus = 'Upcoming';
                                $assessmentStatusClass = 'upcoming';
                                $assessmentDaysLeft = $assessmentNow->copy()->startOfDay()
                                    ->diffInDays($assessmentDue->copy()->startOfDay(), false);
                                $assessmentDueLabel = $assessmentDaysLeft > 0
                                    ? 'Due in ' . $assessmentDaysLeft . ' days'
                                    : 'Due: ' . $assessmentDue->format('d M Y H:i');
                            } else {
                                $assessmentStatus = 'Available';
                                $assessmentStatusClass = 'available';
                                $assessmentDaysLeft = $assessmentNow->copy()->startOfDay()
                                    ->diffInDays($assessmentDue->copy()->startOfDay(), false);
                                $assessmentDueLabel = $assessmentDaysLeft > 0
                                    ? 'Due in ' . $assessmentDaysLeft . ' days'
                                    : 'Due: ' . $assessmentDue->format('d M Y H:i');
                            }
                        @endphp

                        <div class="home-modern-assessment-schedule">
                            <div class="home-modern-assessment-schedule-icon" aria-hidden="true">
                                <i class="ti ti-calendar-event"></i>
                            </div>
                            <div class="home-modern-assessment-schedule-content">
                                <div class="home-modern-assessment-schedule-title">
                                    {{ $assessment->title ?? 'Untitled Assessment' }}
                                </div>
                                <div class="home-modern-assessment-schedule-meta">
                                    <span class="home-modern-assessment-status {{ $assessmentStatusClass }}">
                                        {{ $assessmentStatus }}
                                    </span>
                                    <span class="home-modern-assessment-due {{ $assessmentStatusClass === 'overdue' ? 'overdue' : '' }}">
                                        <i class="ti ti-clock" aria-hidden="true"></i>
                                        {{ $assessmentDueLabel }}
                                    </span>
                                </div>
                                @if($assessmentDue && $assessmentStatusClass !== 'overdue')
                                    <div class="home-modern-assessment-date">
                                        Due: {{ $assessmentDue->format('d M Y H:i') }}
                                    </div>
                                @elseif($assessmentDue)
                                    <div class="home-modern-assessment-date overdue">
                                        Due: {{ $assessmentDue->format('d M Y H:i') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="home-modern-assessment-schedule home-modern-assessment-schedule-empty">
                            <div class="home-modern-assessment-schedule-icon" aria-hidden="true">
                                <i class="ti ti-calendar-off"></i>
                            </div>
                            <div>
                                <div class="home-modern-assessment-schedule-title">No scheduled assessment</div>
                                <div class="home-modern-assessment-date">No Deadline</div>
                            </div>
                        </div>
                    @endforelse
                </div>

                <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
                    <i class="ti ti-info-circle flex-shrink-0"></i>
                    <span>Please input the unique code to access the assessment.</span>
                </div>
                <input type="text"
                       name="unique_code"
                       id="unique-code"
                       class="form-control"
                       placeholder="Input unique code"
                       autocomplete="off">
                <div class="invalid-feedback d-none" id="invalid-text-unique-code"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>Cancel
                </button>
                <button type="button" onclick="submitAssessmentAccess()" class="btn btn-primary">
                    <i class="ti ti-clipboard-check me-1"></i>Submit
                </button>
            </div>

        </div>
    </div>
</div>
