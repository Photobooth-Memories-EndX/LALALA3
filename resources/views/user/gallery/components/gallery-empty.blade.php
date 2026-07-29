@php
    $hasActiveGalleryFilters = request()->filled('q')
        || request()->filled('uploader')
        || request()->filled('category')
        || request()->filled('eventdate')
        || request()->filled('views')
        || request()->filled('sort');
@endphp

<div class="col-12">
    <div class="gallery-empty-state">
        <div class="gallery-empty-icon">
            <i class="ti ti-photo-off"></i>
        </div>
        <h4 class="mt-4 fw-bold">No Gallery Found</h4>
        <p class="text-muted mt-1">
            @if($hasActiveGalleryFilters)
                No results match your current filters. Try adjusting your search.
            @else
                No event galleries have been published yet.
            @endif
        </p>

        @if($hasActiveGalleryFilters)
            <a href="{{ route('gallery.user.index') }}" class="btn btn-outline-primary mt-2">
                <i class="ti ti-refresh me-1"></i> Reset Filters
            </a>
        @endif
    </div>
</div>
