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

    {{-- AI RECOMMENDED --}}
    <div class="card border-0 owl-sidebar-card mb-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="avatar bg-info text-white rounded-circle p-2">
                    <i class="ti ti-ai-2"></i>
                </span>
                <div>
                    <div class="small text-white text-uppercase fw-semibold mb-1">AI Recommended</div>
                    <h5 class="mb-0 fw-bold text-white">Recommended for you</h5>
                </div>
            </div>
            @if(isset($trending) && $trending->count() > 0)
                @php $rec = $trending->first(); @endphp
                <div class="owl-recommended-card rounded-4 p-4 mb-3">
                    <small class="badge bg-info text-white rounded-pill mb-3">Most viewed</small>
                    <h6 class="fw-semibold mb-3">{{ Str::limit($rec->title, 70) }}</h6>
                    <p class="small text-white-75 mb-4">A concise insight tailored for enterprise learning and leadership.</p>
                    <a href="{{ route('news.user.view', $rec->slug) }}" class="btn btn-sm btn-primary rounded-pill w-100">
                        Read now
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- NEWSLETTER --}}
    <div class="card border-0 owl-subscribe-card mb-4">
        <div class="card-body p-4 position-relative text-white">
            <div class="owl-subscribe-glow"></div>
            <div class="mb-4">
                <div class="small text-white text-uppercase fw-semibold mb-2">Insider</div>
                <h5 class="fw-bold text-white mb-2">OWL Pulse Newsletter</h5>
            </div>
            <p class="small text-white-75 mb-4">Receive premium learning insights, leadership updates, and AI tips every week.</p>
            <form method="POST" action="#" class="owl-subscribe-form">
                <div class="mb-3">
                    <input type="email" class="form-control owl-input-subscribe" placeholder="Email address" required>
                </div>
                <button type="submit" class="btn btn-light w-100 rounded-pill fw-semibold">Subscribe</button>
            </form>
            <p class="tiny text-white-75 mt-3 mb-0">No spam. Unsubscribe anytime.</p>
        </div>
    </div>

</div>
