<div class="modern-card mt-4">

    <div class="card-header border-0 d-flex justify-content-between align-items-center">

        <h2 class="section-title mb-0">
            Training Modules
        </h2>

        <div class="module-filter">

            <span class="active">
                Video
            </span>

            <span>
                Files
            </span>

        </div>

    </div>

    <div class="card-body pt-0">

        @foreach($playlist->take(5) as $item)

            @php

                $firstVideo =
                    $item->trainingplandetail
                        ->first();

            @endphp

            @if($firstVideo && $firstVideo->content)

            <a
                href="{{ route(
                    'playlist.user.view',
                    [
                        'slug'    => $item->slug,
                        'vidslug' => $firstVideo->content->slug
                    ]
                ) }}"
                class="module-row">

                <div class="module-left">

                    <img
                        src="{{ asset('default-video.jpg') }}"
                        class="module-image">

                    <div>

                        <h5>

                            {{ $item->title }}

                        </h5>

                        <small>

                            {{ $item->trainingplandetail->count() }}
                            Modules

                        </small>

                    </div>

                </div>

                <i class="ti ti-chevron-right"></i>

            </a>

            @endif

        @endforeach

    </div>

</div>
