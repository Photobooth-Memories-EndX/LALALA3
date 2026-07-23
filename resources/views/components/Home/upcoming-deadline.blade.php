<div class="home-modern-card">

    <div class="home-modern-card-header">
        <h2 class="home-modern-section-title">Upcoming Deadlines</h2>
        <a href="{{ route('assesment.user.index') }}" class="home-modern-view-all">
            View All <i class="ti ti-arrow-right ms-1"></i>
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

                $isPassed = $latestResult
                    && $deadline->passing_grade !== null
                    && $latestResult->score !== null
                    && (float) $latestResult->score >= (float) $deadline->passing_grade;

                if ($isPassed) {
                    $deadlineStatus = 'PASSED';
                    $deadlineStatusClass = 'success';
                    $deadlineIconClass = 'primary';
                } elseif ($toDate && \Carbon\Carbon::now()->gt($toDate)) {
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
            @endphp

            <a href="{{ route('assesment.user.view', $deadline->slug) }}"
               class="home-modern-deadline-row">

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
                                {{ $deadlineLabel }}
                            </span>
                        </div>
                    </div>

                </div>

                <i class="ti ti-chevron-right home-modern-module-arrow"></i>

            </a>

        @empty

        <div class="home-modern-empty">
            <i class="ti ti-calendar-check"></i>
            <p>No upcoming deadlines</p>
        </div>

        @endforelse

    </div>

</div>
