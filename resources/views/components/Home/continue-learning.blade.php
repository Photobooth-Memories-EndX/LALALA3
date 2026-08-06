<div class="home-modern-card">

    <div class="home-modern-card-header">
        <h2 class="home-modern-section-title">Continue Learning</h2>
        <a href="{{ url('/my-learning') }}" class="home-modern-view-all">
            View All <i class="ti ti-arrow-right ms-1"></i>
        </a>
    </div>

    <div class="home-modern-card-body">

        @if($continueCourse && $continueCourse->content)

        @php
            $clThumb = asset('default-video.jpg');
            $cl = $continueCourse->content;
            if ($cl->type === 'YouTube' && $cl->link) {
                $clThumb = 'https://img.youtube.com/vi/' . $cl->link . '/mqdefault.jpg';
            } else {
                $clSrc = 'storage/files/content/' . $cl->id . '/thumb.jpg';
                if (\File::exists(public_path($clSrc)) && !is_dir(public_path($clSrc))) {
                    $clThumb = asset($clSrc);
                }
            }

            $progressDetail = $continueProgressDetail ?? null;
        @endphp

        <div class="home-modern-continue-wrap">

            <div class="home-modern-continue-thumb">
                <img src="{{ $clThumb }}"
                     alt="{{ $cl->title }}"
                     onerror="this.src='{{ asset('default-video.jpg') }}'">
                <div class="home-modern-continue-play">
                    <i class="ti ti-player-play-filled"></i>
                </div>
            </div>

            <div class="home-modern-continue-info">

                @php
                    $continueProgress = (int) ($continueCourse->progress ?? 0);
                    $continueLearningStatus = strtolower((string) ($continueCourse->learning_status ?? ''));
                    $continueIsCompleted = $continueProgress >= 100 || $continueLearningStatus === 'completed';
                    $continueIsInProgress = $continueProgress > 0 && $continueProgress < 100;
                @endphp

                <span class="badge {{ $continueIsCompleted ? 'bg-success-lt text-success' : ($continueIsInProgress ? 'bg-primary-lt text-primary' : 'bg-secondary-lt text-secondary') }} mb-2">
                    {{ $continueIsCompleted ? 'Completed' : ($continueIsInProgress ? 'In Progress' : 'Not Started') }}
                </span>

                <h3 class="home-modern-continue-title">
                    {{ Str::limit($cl->title, 70) }}
                </h3>

                <p class="home-modern-continue-period">
                    Periode : {{ $continuePeriod ?? '-' }}
                </p>

                <div class="home-modern-progress-wrap">
                    <div class="d-flex justify-content-between mb-1">
                        <small>Progress</small>
                        <small class="fw-bold">{{ $continueCourse->progress ?? 0 }}%</small>
                    </div>
                    <div class="home-modern-progress">
                        <div class="home-modern-progress-bar"
                             style="width: {{ $continueCourse->progress ?? 0 }}%"></div>
                    </div>

                    @if ($progressDetail && $progressDetail['type'] === 'pages')
                        <small class="text-muted d-block mt-1">
                            {{ $progressDetail['read'] }}/{{ $progressDetail['total'] }} pages
                        </small>
                    @elseif ($progressDetail && $progressDetail['type'] === 'duration')
                        <small class="text-muted d-block mt-1">
                            {{ \App\Support\DurationFormatter::fromSeconds($progressDetail['watched']) }} /
                            {{ \App\Support\DurationFormatter::fromSeconds($progressDetail['total']) }} video
                        </small>
                    @endif
                </div>

                <div class="mt-2">
                    <a href="{{ route('content.user.view', $cl->slug) }}"
                       class="home-modern-btn-primary">
                        Continue <i class="ti ti-arrow-right ms-1"></i>
                    </a>
                </div>

            </div>

        </div>

        @else

        <div class="home-modern-empty">
            <i class="ti ti-book-off"></i>
            <p>No course in progress yet</p>
            <a href="{{ route('content.user.index') }}" class="home-modern-btn-outline mt-2">
                Browse Courses
            </a>
        </div>

        @endif

    </div>

</div>
