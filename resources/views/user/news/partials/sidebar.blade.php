{{--
    sidebar.blade.php owns .owl-sidebar-sticky.
    Do NOT wrap this partial in .owl-sidebar-sticky in the parent — it causes double-sticky nesting.
--}}
<div class="owl-sidebar-sticky">

    {{-- TRENDING TOPICS --}}
    <div class="card border-0 owl-sidebar-card mb-4">
        <div class="card-body p-0">

            {{-- Header --}}
            <div class="d-flex align-items-center gap-2 p-4 pb-3">
                <span class="avatar bg-warning text-white rounded-circle p-2 flex-shrink-0">
                    <i class="ti ti-flame"></i>
                </span>
                <div class="min-w-0">
                    <div class="small text-uppercase owl-sidebar-label mb-1">Trending</div>
                    <h5 class="mb-0 fw-bold owl-sidebar-heading">Topics to watch</h5>
                </div>
            </div>

            {{-- Trending list --}}
            <div class="owl-trending-list px-3 pb-3">
                @if(isset($trending) && $trending->count() > 0)

                    @foreach($trending as $index => $trend)
                        <a
                            href="{{ route('news.user.view', $trend->slug) }}"
                            class="owl-trending-item d-flex gap-3 align-items-start text-decoration-none mb-2"
                        >
                            {{-- Thumbnail with ranking badge --}}
                            <div class="trending-thumb flex-shrink-0">
                                <div class="trending-thumb-placeholder">
                                    <i class="ti ti-news"></i>
                                </div>
                                @if(!empty($trend->image))
                                    <img
                                        src="{{ asset('storage/news/' . $trend->image) }}"
                                        alt=""
                                        class="trending-thumb-img"
                                        loading="lazy"
                                        onerror="this.style.display='none'"
                                    >
                                @endif
                                <span class="trending-thumb-num">
                                    {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}
                                </span>
                            </div>

                            {{-- Text — min-w-0 prevents flex item from blowing out --}}
                            <div class="flex-grow-1 min-w-0">
                                <h6 class="mb-1 fw-semibold owl-trending-title">
                                    {{ $trend->title ?? '-' }}
                                </h6>
                                <div class="small owl-trending-meta d-flex gap-1 align-items-center">
                                    <i class="ti ti-eye flex-shrink-0"></i>
                                    <span>{{ number_format($trend->views ?? 0) }} views</span>
                                </div>
                            </div>
                        </a>
                    @endforeach

                @else
                    <div class="text-center py-4">
                        <i class="ti ti-news text-secondary opacity-50 owl-sidebar-empty-icon"></i>
                        <p class="text-secondary small mt-2 mb-0">No trending articles yet.</p>
                    </div>
                @endif
            </div>

            {{-- Footer CTA --}}
            @if(isset($trending) && $trending->count() > 0)
                <div class="text-center px-4 pb-4">
                    <a
                        href="{{ route('news.user.index', ['sort' => 'most_viewed']) }}"
                        class="btn btn-sm btn-outline-primary rounded-pill px-4 w-100"
                    >
                        View all trending
                    </a>
                </div>
            @endif

        </div>
    </div>

</div>
