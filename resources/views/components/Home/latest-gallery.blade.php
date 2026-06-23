<div class="modern-card mt-4">

    <div class="card-header border-0 d-flex justify-content-between align-items-center">

        <h2 class="section-title mb-0">
            Recent Activity
        </h2>

        <a href="{{ route('gallery.user.index') }}">
            View All
        </a>

    </div>

    <div class="card-body">

        <div class="row g-4">

            @foreach($eventphoto->take(3) as $gallery)

            <div class="col-lg-4">

                <a
                    href="{{ route('gallery.user.view',$gallery->slug) }}"
                    class="gallery-card-modern">

                    <img
                        src="{{ asset('default-video.jpg') }}"
                        class="gallery-modern-image"
                        alt="{{ $gallery->title }}">

                    <div class="gallery-modern-body">

                        <span class="gallery-category">

                            Gallery

                        </span>

                        <h5 class="gallery-modern-title">

                            {{ Str::limit($gallery->title,50) }}

                        </h5>

                        <div class="gallery-footer">

                            <small>

                                <i class="ti ti-calendar me-1"></i>

                                {{ \Carbon\Carbon::parse($gallery->created_at)->format('d M Y') }}

                            </small>

                            <span>

                                View →

                            </span>

                        </div>

                    </div>

                </a>

            </div>

            @endforeach

        </div>

    </div>

</div>
