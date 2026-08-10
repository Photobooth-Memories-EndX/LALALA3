<div class="home-modern-card">

    <div class="home-modern-card-header">
        <h2 class="home-modern-section-title">Upcoming Deadline Assessment</h2>
        <a href="{{ url('/my-learning?learning_tab=assessment') }}" class="home-modern-view-all view-all-link text-primary dark:text-white font-medium">
            View All &rarr;
        </a>
    </div>

    <div class="home-modern-card-body pt-0">

        @forelse($upcomingDeadlines->take(3) as $deadline)

            @php
                $latestResult = $deadline->scores()
                    ->where('user_id', auth()->id())
                    ->orderByDesc('id')
                    ->first();

                $toDate = filled($deadline->todate)
                    ? \Carbon\Carbon::parse($deadline->todate)
                    : null;
                $deadlineNow = \Carbon\Carbon::now();

                $isPassed = $latestResult
                    && $deadline->passing_grade !== null
                    && $latestResult->score !== null
                    && (float) $latestResult->score >= (float) $deadline->passing_grade;

                if ($isPassed) {
                    $deadlineStatus = 'PASSED';
                    $deadlineStatusClass = 'success';
                    $deadlineIconClass = 'primary';
                } elseif ($toDate && $deadlineNow->gt($toDate)) {
                    $deadlineStatus = 'OVERDUE';
                    $deadlineStatusClass = 'overdue';
                    $deadlineIconClass = 'danger';
                } else {
                    $deadlineStatus = 'PENDING';
                    $deadlineStatusClass = 'pending';
                    $deadlineIconClass = 'warning';
                }

                $deadlineLabel = $toDate
                    ? 'Deadline: ' . $toDate->format('d M Y, H:i')
                    : 'No Deadline';

                $deadlineRelativeLabel = null;

                if ($toDate) {
                    if (!$isPassed && $deadlineNow->gt($toDate)) {
                        $deadlineRelativeLabel = 'Overdue';
                    } elseif ($deadlineNow->lte($toDate)) {
                        $daysUntilDue = max(
                            0,
                            (int) $deadlineNow->copy()->startOfDay()
                                ->diffInDays($toDate->copy()->startOfDay(), false)
                        );

                        if ($daysUntilDue === 0) {
                            $deadlineRelativeLabel = 'Due today';
                        } elseif ($daysUntilDue === 1) {
                            $deadlineRelativeLabel = 'Due tomorrow';
                        } else {
                            $deadlineRelativeLabel = 'Due in ' . $daysUntilDue . ' days';
                        }
                    }
                }
            @endphp

            <div class="home-modern-deadline-row home-modern-deadline-row-static">

                <div class="home-modern-deadline-left">

                    <div class="home-modern-deadline-icon home-modern-deadline-{{ $deadlineIconClass }}">
                        <i class="ti ti-alarm"></i>
                    </div>

                    <div class="home-modern-deadline-content">
                        <div class="home-modern-deadline-title"
                             title="{{ $deadline->title }}">
                            {{ $deadline->title }}
                        </div>
                        <div class="home-modern-deadline-meta">
                            <span class="home-modern-priority-badge deadline-status {{ $deadlineStatusClass }}">
                                {{ $deadlineStatus }}
                            </span>
                            <span class="home-modern-deadline-date">
                                <i class="ti ti-calendar-event" aria-hidden="true"></i>
                                <span>{{ $deadlineLabel }}</span>
                                @if($deadlineRelativeLabel)
                                    <span class="home-modern-deadline-separator" aria-hidden="true">&middot;</span>
                                    <span class="home-modern-deadline-relative {{ $deadlineRelativeLabel === 'Overdue' ? 'is-overdue' : '' }}">
                                        {{ $deadlineRelativeLabel }}
                                    </span>
                                @endif
                            </span>
                        </div>
                    </div>

                </div>

            </div>

        @empty

        <div class="home-modern-empty">
            <i class="ti ti-calendar-check"></i>
            <p>No upcoming deadlines</p>
        </div>

        @endforelse

    </div>

</div>
