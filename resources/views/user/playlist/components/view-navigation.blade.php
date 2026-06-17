@php
    /** @var \App\Models\Content $content */
    /** @var \Illuminate\Support\Collection $content_all */
    /** @var \App\Models\TrainingPlan $playlist */
    $content = $content ?? null;
    $contentItems = $content_all->values();

    $currentIndex = $contentItems->search(function ($item) use ($content) {
        return $content && optional($item->content)->slug === $content->slug;
    });

    $previousItem = $currentIndex !== false && $currentIndex > 0
        ? $contentItems[$currentIndex - 1]
        : null;

    $nextItem = $currentIndex !== false && $currentIndex < $contentItems->count() - 1
        ? $contentItems[$currentIndex + 1]
        : null;

    $previousUrl = $previousItem && optional($previousItem->content)->slug
        ? route('playlist.user.view', [$playlist->slug, $previousItem->content->slug])
        : '#';

    $nextUrl = $nextItem && optional($nextItem->content)->slug
        ? route('playlist.user.view', [$playlist->slug, $nextItem->content->slug])
        : '#';
@endphp

<div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">

            <a href="{{ $previousUrl }}"
               class="btn btn-outline-secondary {{ !$previousItem ? 'disabled' : '' }}">
                <i class="fas fa-chevron-left me-2"></i>
                Previous
            </a>

            <button type="button"
                    class="btn btn-primary px-4"
                    onclick="markContentComplete()">
                Mark as Complete
            </button>

            <a href="{{ $nextUrl }}"
               class="btn btn-outline-secondary {{ !$nextItem ? 'disabled' : '' }}">
                Next
                <i class="fas fa-chevron-right ms-2"></i>
            </a>

        </div>
    </div>
</div>