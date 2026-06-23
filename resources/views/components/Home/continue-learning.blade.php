<div class="modern-card mt-4">

    <div class="card-header border-0 d-flex justify-content-between align-items-center">

        <h2 class="section-title mb-0">
            Continue Learning
        </h2>

        <a href="{{ route('content.user.index') }}">
            View All Courses
        </a>

    </div>

    <div class="card-body">

        @if($continueCourse && $continueCourse->content)

        {{-- DEBUG --}}
        {{--
        <pre>
ID Content :
{{ $continueCourse->content_id }}

Title :
{{ $continueCourse->content->title }}

Slug :
{{ $continueCourse->content->slug }}
        </pre>
        --}}

        <div class="row align-items-center">

            <div class="col-lg-4">

                <img
                    src="{{ asset('default-video.jpg') }}"
                    class="continue-image"
                    alt="{{ $continueCourse->content->title }}">

            </div>

            <div class="col-lg-8">

                <span class="badge bg-primary mb-3">
                    In Progress
                </span>

                <h3 class="fw-bold">

                    {{ $continueCourse->content->title }}

                </h3>

                <p class="text-muted">

                    Continue your learning journey and complete this course.

                </p>

                <div class="d-flex justify-content-between mb-2">

                    <small>
                        Overall Progress
                    </small>

                    <small>

                        {{ $continueCourse->progress ?? 0 }}%

                    </small>

                </div>

                <div class="progress progress-modern">

                    <div
                        class="progress-bar bg-primary"
                        role="progressbar"
                        style="width: {{ $continueCourse->progress ?? 0 }}%">
                    </div>

                </div>

                <div class="mt-4">

                    <a
                        href="{{ route('content.user.view',$continueCourse->content->slug) }}"
                        class="btn btn-primary">

                        Continue Learning
                        <i class="ti ti-arrow-right ms-2"></i>

                    </a>

                </div>

            </div>

        </div>

        @else

        <div class="text-center py-5">

            <i class="ti ti-book-off fs-1 text-muted"></i>

            <h5 class="mt-3">
                No Course In Progress
            </h5>

            <p class="text-muted">
                Start learning to see your progress here.
            </p>

        </div>

        @endif

    </div>

</div>
