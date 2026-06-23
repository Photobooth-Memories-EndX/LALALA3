<div class="modern-card mt-4">

    <div class="card-header border-0 d-flex justify-content-between align-items-center">

        <h2 class="section-title mb-0">
            Upcoming Deadlines
        </h2>

        <a href="#">
            View All
        </a>

    </div>

    <div class="card-body pt-0">

        @forelse($upcomingDeadlines->take(2) as $deadline)

            @php

                $date = \Carbon\Carbon::parse(
                    $deadline->todate
                );

                $remaining = now()->diffInDays(
                    $date,
                    false
                );

                if($remaining <= 1){

                    $priorityClass = 'danger';

                    $priorityText = 'Critical';

                }elseif($remaining <= 3){

                    $priorityClass = 'warning';

                    $priorityText = 'Medium';

                }else{

                    $priorityClass = 'primary';

                    $priorityText = 'Normal';
                }

            @endphp

            <a
                href="{{ route('assesment.user.view',$deadline->slug) }}"
                class="deadline-row">

                <div class="deadline-left">

                    <div class="deadline-icon {{ $priorityClass }}">

                        <i class="ti ti-alert-triangle"></i>

                    </div>

                    <div>

                        <div class="deadline-title">

                            {{ Str::limit($deadline->title,60) }}

                        </div>

                        <div class="deadline-subtitle">

                            {{ $priorityText }}

                            •

                            @if($remaining <= 0)

                                Due Today

                            @elseif($remaining == 1)

                                Due Tomorrow

                            @else

                                Due in {{ $remaining }} days

                            @endif

                        </div>

                    </div>

                </div>

                <div class="deadline-arrow">

                    <i class="ti ti-chevron-right"></i>

                </div>

            </a>

        @empty

            <div class="empty-state">

                <i class="ti ti-calendar-off"></i>

                <p>
                    No upcoming deadlines
                </p>

            </div>

        @endforelse

    </div>

</div>
