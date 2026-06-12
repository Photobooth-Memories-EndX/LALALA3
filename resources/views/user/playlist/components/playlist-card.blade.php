@php
    $totalContent = count($playlist->trainingplandetail);
    $completedContent = 0;

    foreach ($playlist->trainingplandetail as $detail) {
        if ($detail->content && isset($detail->content->contentuser)) {
            $contentUser = $detail->content->contentuser
                ->where('user_id', auth()->id())
                ->first();

            if ($contentUser && (int) $contentUser->finish === 1) {
                $completedContent++;
            }
        }
    }

    $progress = $totalContent > 0
        ? round(($completedContent / $totalContent) * 100)
        : 0;

    $firstContent = optional($playlist->trainingplandetail->first())->content;
@endphp

<div class="col-xl-4 col-lg-6 mb-4 playlist-item">

    <div class="card playlist-card border-0 shadow-sm rounded-4 h-100 overflow-hidden">

        <div class="position-relative">

            @if (!empty($playlist->image))
                <img src="{{ asset($playlist->image) }}"
                     class="playlist-thumbnail w-100">
            @else
                <div class="playlist-thumbnail d-flex align-items-center justify-content-center">
                    <div class="text-center">
                        <span class="avatar avatar-xl bg-primary-lt text-primary mb-2">
                            <i class="ti ti-playlist"></i>
                        </span>

                        <div class="small text-muted">
                            Learning Playlist
                        </div>
                    </div>
                </div>
            @endif

            <span class="badge bg-primary position-absolute top-0 end-0 m-3">
                Playlist
            </span>

        </div>

        <div class="card-body">

            <div class="small text-muted mb-2">
                <i class="ti ti-book-2 me-1"></i>
                {{ $totalContent }} Learning Materials
            </div>

            <div class="playlist-title mb-2">
                {{ $playlist->title }}
            </div>

            <div class="text-muted small mb-3">
                <i class="ti ti-calendar me-1"></i>
                Published on {{ date('d M Y', strtotime($playlist->created_at)) }}
            </div>

            <div class="playlist-content-list small text-muted">

                @foreach($playlist->trainingplandetail->take(3) as $detail)
                    @if($detail->content)
                        <div class="mb-1">
                            • {{ Str::limit($detail->content->title, 45) }}
                        </div>
                    @endif
                @endforeach

                @if($totalContent > 3)
                    <div class="text-primary mt-1">
                        + {{ $totalContent - 3 }} more materials
                    </div>
                @endif

            </div>

            <div class="mt-3">
                <div class="progress progress-sm mb-2">
                    <div class="progress-bar bg-primary"
                         style="width: {{ $progress }}%">
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center small">
                    <span class="text-primary fw-semibold">
                        {{ $progress }}% Completed
                    </span>

                    <span class="text-muted">
                        {{ $completedContent }} of {{ $totalContent }} finished
                    </span>
                </div>
            </div>

        </div>

        <div class="card-footer bg-transparent border-0">

            @if($firstContent)
                <a href="{{ route('playlist.user.view', [$playlist->slug, $firstContent->slug]) }}"
                   class="btn btn-primary w-100 rounded-pill">
                    Continue Learning
                    <i class="ti ti-arrow-right ms-1"></i>
                </a>
            @else
                <button class="btn btn-secondary w-100 rounded-pill" disabled>
                    No Material Available
                </button>
            @endif

        </div>

    </div>

</div>
