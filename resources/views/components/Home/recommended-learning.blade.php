<div class="modern-card mt-4">

    <div class="card-header border-0 d-flex justify-content-between align-items-center">

        <h2 class="section-title mb-0">
            Top Video Learning
        </h2>

        <a href="{{ route('content.user.index') }}">
            View All
        </a>

    </div>

    <div class="card-body">

        <div class="row g-4">

            @foreach($top_video->take(2) as $item)

            <div class="col-lg-6">

                <a
                    href="{{ route('content.user.view',$item->slug) }}"
                    class="learning-card">

                    <div class="learning-image-wrapper">

                        <img
                            src="{{ asset('default-video.jpg') }}"
                            class="learning-image"
                            alt="{{ $item->title }}">

                        <div class="play-button">
                            <i class="ti ti-player-play-filled"></i>
                        </div>

                    </div>

                    <div class="learning-content">

                        <span class="learning-category">
                            Video Learning
                        </span>

                        <h4 class="learning-title">

                            {{ Str::limit($item->title,70) }}

                        </h4>

                        <div class="learning-meta">

                            <span>

                                <i class="ti ti-clock me-1"></i>

                                {{ $item->duration ?? '15 Min' }}

                            </span>

                        </div>

                    </div>

                </a>

            </div>

            @endforeach

        </div>

    </div>

</div>
