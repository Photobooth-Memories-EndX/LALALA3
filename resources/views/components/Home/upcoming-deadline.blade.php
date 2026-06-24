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
                $date = \Carbon\Carbon::parse($deadline->todate);
                $remaining = now()->diffInDays($date, false);

                if ($remaining <= 1) {
                    $pc = 'danger';
                    $pt = 'Critical';
                } elseif ($remaining <= 3) {
                    $pc = 'warning';
                    $pt = 'Medium';
                } else {
                    $pc = 'primary';
                    $pt = 'Normal';
                }
            @endphp

            <a href="{{ route('assesment.user.view', $deadline->slug) }}"
               class="home-modern-deadline-row">

                <div class="home-modern-deadline-left">

                    <div class="home-modern-deadline-icon home-modern-deadline-{{ $pc }}">
                        <i class="ti ti-alarm"></i>
                    </div>

                    <div>
                        <div class="home-modern-deadline-title">
                            {{ Str::limit($deadline->title, 55) }}
                        </div>
                        <div class="home-modern-deadline-meta">
                            <span class="home-modern-priority-badge home-modern-priority-{{ $pc }}">
                                {{ $pt }}
                            </span>
                            <span class="ms-2">
                                @if($remaining <= 0)
                                    Due Today
                                @elseif($remaining == 1)
                                    Due Tomorrow
                                @else
                                    Due in {{ $remaining }} days
                                @endif
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
