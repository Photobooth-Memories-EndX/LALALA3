@php
    $featured = $news->first();
@endphp

@if($featured)

<div class="card border-0 overflow-hidden mb-5 owl-hero-card">

    <div class="row g-0 align-items-center">

        <div class="col-lg-6">

            <div class="card-body p-5 text-white">

                <div class="mb-3">
                    <span class="badge bg-blue-lt text-blue">
                        Featured Update
                    </span>

                    <span class="ms-2 opacity-75">
                        {{ $featured->created_at->format('M d, Y') }}
                    </span>
                </div>

                <h1 class="display-5 fw-bold mb-4">
                    {{ $featured->title }}
                </h1>

                <p class="fs-4 opacity-75 mb-5">
                    {{ Str::limit(strip_tags($featured->description), 180) }}
                </p>

                <div class="d-flex gap-3">

                    <a href="{{ route('news.user.view', $featured->slug) }}"
                       class="btn btn-light rounded-pill px-4">

                        Read Article

                    </a>

                    <button class="btn btn-outline-light rounded-pill px-4">
                        Save For Later
                    </button>

                </div>

            </div>

        </div>

        <div class="col-lg-6">

            <img src="{{ asset('storage/news/'.$featured->image) }}"
                 class="w-100 h-100 object-cover hero-image"
                 alt="{{ $featured->title }}">

        </div>

    </div>

</div>

@endif
