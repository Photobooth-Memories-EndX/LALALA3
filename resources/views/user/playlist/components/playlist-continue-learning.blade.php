{{--
@php
    $featuredPlaylist = $trainingplan->first();
@endphp

@if($featuredPlaylist && $featuredStats)
    @php
        $progress         = $featuredStats['progress'];
        $completedContent = $featuredStats['completedContent'];
        $totalContent     = $featuredStats['totalContent'];
        $remainingContent = $featuredStats['remainingContent'];
        $nextContent      = $featuredStats['nextContent'];
        $firstContent     = $featuredStats['firstContent'];
    @endphp

    <div class="row g-3 mb-4 playlist-learning-path-wrap">

        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 playlist-continue-card">
                <div class="card-body p-4">

                    <div class="d-flex justify-content-between gap-3 mb-3">

                        <div>
                            <div class="text-uppercase small text-white-75 fw-semibold mb-1">
                                Continue Learning
                            </div>

                            <h2 class="text-white mb-0 playlist-continue-title">
                                {{ $featuredPlaylist->title }}
                            </h2>
                        </div>

                        <div class="playlist-progress-badge">
                            <div class="h1 mb-0">
                                {{ $progress }}%
                            </div>

                            <div class="small">
                                Overall Progress
                            </div>
                        </div>

                    </div>

                    <div class="text-white-75 mb-3">
                        <i class="ti ti-circle-check me-1"></i>
                        {{ $completedContent }} / {{ $totalContent }} materials completed
                    </div>

                    <div class="progress playlist-white-progress mb-3">
                        <div class="progress-bar bg-white"
                             style="width: {{ $progress }}%">
                        </div>
                    </div>

                    @if($nextContent)
                        <a href="{{ route('playlist.user.view', [$featuredPlaylist->slug, $nextContent->slug]) }}"
                           class="btn btn-light rounded-pill px-4">
                            Continue Learning
                            <i class="ti ti-arrow-right ms-1"></i>
                        </a>
                    @else
                        <button class="btn btn-light rounded-pill px-4" disabled>
                            No Material Available
                        </button>
                    @endif

                </div>
            </div>
        </div>

    </div>
@endif
--}}
