@php
    $commentTotal = method_exists($comment, 'total') ? $comment->total() : $comment->count();
@endphp

<div class="card border-0 shadow-sm rounded-4 mb-3 view-content-info-card">
    <div class="card-body p-4">

        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
            <div class="min-w-0">
                <div class="text-muted small mb-1">
                    {{ date('d F Y', strtotime($content->created_at)) }}
                    by {{ $content->author ?? '-' }}
                </div>

                <h2 class="mb-2 fs-2 fw-bold">
                    {{ $content->title }}
                </h2>

                <div class="overview-inline-meta text-muted">
                    <button type="button"
                            class="overview-meta-button"
                            data-bs-toggle="modal"
                            data-bs-target="#contentCommentModal">
                        <i class="far fa-comment-dots me-1"></i>
                        {{ $commentTotal }} Comments
                    </button>
                    <span class="overview-meta-separator">&bull;</span>
                    <span>
                        <i class="far fa-clock me-1"></i>
                        {{ \App\Support\DurationFormatter::format($content->duration) }}
                    </span>
                </div>
            </div>

            <button type="button"
                    class="btn btn-icon btn-outline-secondary rounded-3"
                    onclick="navigator.clipboard.writeText(window.location.href);">
                <i class="fas fa-share-alt"></i>
            </button>
        </div>

        <div class="chapter-description-box mb-0">
            <h4 class="fs-6 fw-bold mb-2">Overview</h4>

            <div class="overview-text is-collapsed text-muted" id="contentOverviewText">
                {!! nl2br($content->description) !!}
            </div>

            <button type="button"
                    class="btn btn-link px-0 py-1 mt-2 overview-toggle"
                    data-overview-target="#contentOverviewText"
                    data-read-more="Read More"
                    data-read-less="Read Less">
                Read More
            </button>
        </div>

    </div>
</div>
