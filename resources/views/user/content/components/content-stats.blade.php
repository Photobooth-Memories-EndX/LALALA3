@php
    $statCommentTotal = $totalCommentCount ?? $content->total_comments_count ?? (optional($content)->relationLoaded('allComments') ? $content->allComments->count() : (method_exists($content, 'allComments') ? $content->allComments()->count() : ($discussionCount ?? 0)));
@endphp

<div class="row g-3">
    <div class="col-6">
        <div class="sidebar-stat-card card border border-light-subtle rounded-4 p-3 bg-white h-100 shadow-sm text-center">
            <div class="text-muted small fw-medium mb-1">Views</div>
            <div class="fw-bold fs-3 text-dark">{{ number_format($content->views ?? 0) }}</div>
        </div>
    </div>

    <div class="col-6">
        <div class="sidebar-stat-card card border border-light-subtle rounded-4 p-3 bg-white h-100 shadow-sm text-center">
            <div class="text-muted small fw-medium mb-1">Comments</div>
            <div class="fw-bold fs-3 text-dark content-comment-total-count">{{ number_format($statCommentTotal) }}</div>
        </div>
    </div>

    <div class="col-6">
        <div class="sidebar-stat-card card border border-light-subtle rounded-4 p-3 bg-white h-100 shadow-sm text-center">
            <div class="text-muted small fw-medium mb-1">Favorite</div>
            <div class="fw-bold fs-3 text-dark">{{ number_format($favoritesCount ?? 0) }}</div>
        </div>
    </div>

    <div class="col-6">
        <div class="sidebar-stat-card card border border-light-subtle rounded-4 p-3 bg-white h-100 shadow-sm text-center">
            <div class="text-muted small fw-medium mb-1">Type</div>
            <div class="fw-bold fs-3 text-dark text-truncate">{{ \App\Support\ContentTypeHelper::label($content->type) }}</div>
        </div>
    </div>
</div>
