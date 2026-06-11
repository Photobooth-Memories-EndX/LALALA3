<div class="owl-sidebar-sticky">

    {{-- TRENDING TOPICS --}}
    <div class="card border-0 owl-sidebar-card mb-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-2 mb-4">
                <span class="avatar bg-warning text-white rounded-circle p-2">
                    <i class="ti ti-flame"></i>
                </span>
                <div>
                    <div class="small text-secondary text-uppercase mb-1">Trending</div>
                    <h5 class="mb-0 fw-bold">Topics to watch</h5>
                </div>
            </div>

            <div class="owl-trending-list">
                @if(isset($trending) && $trending->count() > 0)
                    @foreach($trending as $index => $trend)
                        <a href="{{ route('news.user.view', $trend->slug) }}" class="owl-trending-item d-flex gap-3 align-items-start text-decoration-none mb-3">
                            <div class="owl-trending-number">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</div>
                            <div class="flex-grow-1 min-w-0">
                                <h6 class="mb-1 fw-semibold owl-trending-title">{{ Str::limit($trend->title, 58) }}</h6>
                                <div class="small text-secondary d-flex gap-2 align-items-center">
                                    <i class="ti ti-eye"></i>
                                    <span>{{ number_format($trend->views ?? 0) }} views</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                @endif
            </div>

            <div class="text-center mt-3">
                <a href="{{ route('news.user.index', ['sort' => 'most_viewed']) }}" class="btn btn-sm btn-outline-primary rounded-pill px-4">
                    View all trending
                </a>
            </div>
        </div>
    </div>

   

   

</div>
