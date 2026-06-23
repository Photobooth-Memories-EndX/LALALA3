<div class="home-modern-card mt-4">

    <div class="home-modern-card-header">
        <h2 class="home-modern-section-title">Continue Learning</h2>
        <a href="{{ route('content.user.index') }}" class="home-modern-view-all">
            View All <i class="ti ti-arrow-right ms-1"></i>
        </a>
    </div>

    <div class="home-modern-card-body">

        @if($continueCourse && $continueCourse->content)

        <div class="home-modern-continue-wrap">

            <div class="home-modern-continue-thumb">
                <img src="{{ asset('default-video.jpg') }}" alt="{{ $continueCourse->content->title }}">
                <div class="home-modern-continue-play">
                    <i class="ti ti-player-play-filled"></i>
                </div>
            </div>

            <div class="home-modern-continue-info">

                <span class="badge bg-primary-lt mb-2">In Progress</span>

                <h3 class="home-modern-continue-title">
                    {{ Str::limit($continueCourse->content->title, 80) }}
                </h3>

                <p class="home-modern-continue-desc">
                    Continue your learning and complete this course to earn your progress.
                </p>

                <div class="home-modern-progress-wrap">
                    <div class="d-flex justify-content-between mb-1">
                        <small>Overall Progress</small>
                        <small class="fw-bold">{{ $continueCourse->progress ?? 0 }}%</small>
                    </div>
                    <div class="home-modern-progress">
                        <div class="home-modern-progress-bar"
                             style="width: {{ $continueCourse->progress ?? 0 }}%">
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <a href="{{ route('content.user.view', $continueCourse->content->slug) }}"
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
