@php
    $commentTotal = $totalCommentCount ?? $content->total_comments_count ?? (optional($content)->relationLoaded('allComments') ? $content->allComments->count() : (method_exists($content, 'allComments') ? $content->allComments()->count() : (method_exists($comment, 'total') ? $comment->total() : $comment->count())));
@endphp

<div class="card border-0 shadow-sm rounded-4 mb-3 view-content-info-card">
    <div class="card-body p-4">

        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
            <div class="min-w-0">
                <div class="text-muted small mb-1">
                    {{ date('d F Y', strtotime($content->created_at)) }}
                    created by {{ $content->author ?? '-' }}
                </div>

                <h2 class="mb-2 fs-2 fw-bold text-dark content-detail-title">
                    {{ $content->title }}
                </h2>

                <div class="overview-inline-meta text-muted">
                    <button type="button"
                            class="overview-meta-button"
                            data-bs-toggle="modal"
                            data-bs-target="#contentCommentModal">
                        <i class="far fa-comment-dots me-1"></i>
                        <span class="content-comment-total-count">{{ $commentTotal }}</span> Comments
                    </button>
                    <span class="overview-meta-separator">&bull;</span>
                    <span>
                        <i class="far fa-clock me-1"></i>
                        {{ ($content->type === 'Document' && (int) ($content->page_duration ?? 30) === 0) ? 'Self-paced' : \App\Support\DurationFormatter::format($content->duration) }}
                    </span>
                </div>
            </div>

            <button type="button"
                    class="btn btn-icon btn-outline-secondary rounded-3"
                    onclick="shareContent()"
                    title="Bagikan">
                <i class="fas fa-share-alt"></i>
            </button>
        </div>

        <div class="rounded-4 p-4 p-md-5 content-overview-box">
            <div class="text-muted small fw-bold mb-3 content-overview-heading">Overview</div>
            <div class="text-dark lh-lg fs-5 content-overview-text">{!! $content->description !!}</div>
        </div>

        @if (auth()->user()?->canAccessManageModule('playlist_debug_tools') && \App\Services\EngagementTime\EngagementTimeService::isTargetContent($content))
            @include('user.components.total-engagement-time', [
                'targetType'      => 'content',
                'targetId'        => $content->id,
                'initialDuration' => (int) ($content_user->afk_duration_seconds ?? 0),
            ])
        @endif

        @include('user.playlist.components.view-completion-requirements')

    </div>
</div>
