<div class="col-md-6 mb-4">

    <div class="card owl-news-card h-100 border-0">

        <div class="img-responsive img-responsive-21x9 card-img-top"
             style="background-image:url('{{ asset('storage/news/'.$item->image) }}')">
        </div>

        <div class="card-body">

            <div class="d-flex align-items-center mb-3">

                <span class="badge bg-blue text-white">
                    Innovation
                </span>

                <span class="ms-2 text-secondary small">
                    {{ $item->created_at->format('M d, Y') }}
                </span>

            </div>

            <h3 class="card-title mb-3">

                <a href="{{ route('news.user.view', $item->slug) }}"
                   class="text-dark text-decoration-none">

                    {{ $item->title }}

                </a>

            </h3>

            <p class="text-secondary">

                {{ Str::limit(strip_tags($item->description), 120) }}

            </p>

        </div>

        <div class="card-footer bg-transparent border-0">

            <div class="d-flex align-items-center justify-content-between">

                <div class="d-flex align-items-center">

                    <span class="avatar avatar-sm me-2 bg-blue-lt">
                        {{ strtoupper(substr($item->user->name ?? 'U',0,1)) }}
                    </span>

                    <div>

                        <div class="fw-medium">
                            {{ $item->user->name ?? 'Unknown' }}
                        </div>

                    </div>

                </div>

                <a href="{{ route('news.user.view', $item->slug) }}"
                   class="btn btn-icon btn-outline-primary rounded-circle">

                    →

                </a>

            </div>

        </div>

    </div>

</div>
