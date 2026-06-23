<div class="home-modern-card mt-4">

    <div class="home-modern-card-header">
        <h2 class="home-modern-section-title">Training Modules</h2>
        <a href="{{ route('playlist.user.index') }}" class="home-modern-view-all">
            View All <i class="ti ti-arrow-right ms-1"></i>
        </a>
    </div>

    <div class="home-modern-card-body pt-0">

        @forelse($playlist->take(5) as $item)

            @php
                $firstVideo = $item->trainingplandetail->first();
            @endphp

            @if($firstVideo && $firstVideo->content)
            <a href="{{ route('playlist.user.view', ['slug' => $item->slug, 'vidslug' => $firstVideo->content->slug]) }}"
               class="home-modern-module-row">

                <div class="home-modern-module-left">
                    <div class="home-modern-module-icon">
                        <i class="ti ti-video"></i>
                    </div>
                    <div class="home-modern-module-text">
                        <div class="home-modern-module-title">{{ Str::limit($item->title, 55) }}</div>
                        <div class="home-modern-module-sub">
                            {{ $item->trainingplandetail->count() }} Modules
                        </div>
                    </div>
                </div>

                <i class="ti ti-chevron-right home-modern-module-arrow"></i>

            </a>
            @endif

        @empty

        <div class="home-modern-empty">
            <i class="ti ti-playlist-x"></i>
            <p>No training modules available</p>
        </div>

        @endforelse

    </div>

</div>
