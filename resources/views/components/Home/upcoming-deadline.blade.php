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

                $deadlineStatus = 'NOT PASSED';
                $deadlineStatusClass = 'danger';
                $deadlineIconClass = 'danger';

                if ($latestResult && $deadline->passing_grade !== null && $latestResult->score !== null && (float) $latestResult->score >= (float) $deadline->passing_grade) {
                    $deadlineStatus = 'PASSED';
                    $deadlineStatusClass = 'success';
                    $deadlineIconClass = 'primary';
                }

                $now = \Carbon\Carbon::now()->startOfDay();
                $fromDate = $deadline->fromdate ? \Carbon\Carbon::parse($deadline->fromdate)->startOfDay() : null;
                $toDate = $deadline->todate ? \Carbon\Carbon::parse($deadline->todate)->startOfDay() : null;

                if ($fromDate && $now->lt($fromDate)) {
                    $daysDiff = $now->diffInDays($fromDate, false);
                    $deadlineLabel = 'Starts in ' . $daysDiff . ' days';
                } elseif ($toDate) {
                    $daysDiff = $now->diffInDays($toDate, false);
                    $deadlineLabel = $now->gt($toDate) ? 'Expired' : 'Due in ' . ($daysDiff < 0 ? 0 : $daysDiff) . ' days';
                } else {
                    $deadlineLabel = 'Expired';
                }
            @endphp

            <a href="{{ route('assesment.user.view', $deadline->slug) }}"
               class="home-modern-deadline-row">

                <div class="home-modern-deadline-left">

                    <div class="home-modern-deadline-icon home-modern-deadline-{{ $deadlineIconClass }}">
                        <i class="ti ti-alarm"></i>
                    </div>

                    <div>
                        <div class="home-modern-deadline-title">
                            {{ Str::limit($deadline->title, 55) }}
                        </div>
                        <div class="home-modern-deadline-meta">
                            <span class="home-modern-priority-badge deadline-status {{ $deadlineStatusClass }}">
                                {{ $deadlineStatus }}
                            </span>
                            <span class="ms-2">{{ $deadlineLabel }}</span>
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
