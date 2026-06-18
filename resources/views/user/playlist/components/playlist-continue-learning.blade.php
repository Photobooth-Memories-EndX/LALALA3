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

        {{-- CONTINUE LEARNING --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 playlist-continue-card h-100">
                <div class="card-body p-4">

                    <div class="d-flex justify-content-between gap-3 mb-4">

                        <div>
                            <div class="text-uppercase small text-white-75 fw-semibold mb-2">
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

                    <div class="progress playlist-white-progress mb-4">
                        <div class="progress-bar bg-white"
                             style="width: {{ $progress }}%">
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-4 text-white-75 mb-4">

                        <div>
                            <i class="ti ti-circle-check me-1"></i>
                            {{ $completedContent }} of {{ $totalContent }} materials completed
                        </div>

                        <div>
                            <i class="ti ti-player-play me-1"></i>
                            {{ $remainingContent }} materials remaining
                        </div>

                        <div>
                            <i class="ti ti-route me-1"></i>
                            Learning path in progress
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

        {{-- MY LEARNING PATH --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 playlist-path-card h-100">
                <div class="card-body p-4">

                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <div class="text-muted small">
                                My Learning Path
                            </div>

                            <h3 class="mb-0 mt-1">
                                Path Progress
                            </h3>
                        </div>

                        <span class="avatar bg-primary-lt text-primary">
                            <i class="ti ti-route"></i>
                        </span>
                    </div>

                    <div class="text-muted small mb-3">
                        Current path:
                        <strong class="text-body">
                            {{ Str::limit($featuredPlaylist->title, 38) }}
                        </strong>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small text-muted">
                            Completion
                        </span>

                        <span class="fw-bold text-primary">
                            {{ $progress }}%
                        </span>
                    </div>

                    <div class="progress progress-sm mb-3">
                        <div class="progress-bar bg-primary"
                             style="width: {{ $progress }}%">
                        </div>
                    </div>

                    <div class="text-muted small mb-3">
                        Next material:
                        <em>
                            {{ $nextContent->title ?? 'No next material available' }}
                        </em>
                    </div>

                    @if($nextContent)
                        <a href="{{ route('playlist.user.view', [$featuredPlaylist->slug, $nextContent->slug]) }}"
                           class="btn btn-outline-primary rounded-pill w-100">
                            Open Learning Path
                        </a>
                    @else
                        <button class="btn btn-outline-secondary rounded-pill w-100" disabled>
                            No Path Available
                        </button>
                    @endif

                </div>
            </div>
        </div>

    </div>
@endif
