<div class="col-12">
    <div class="gallery-empty-state">
        <div class="gallery-empty-icon">
            <i class="ti ti-photo-off"></i>
        </div>
        <h4 class="mt-4 fw-bold">No Gallery Found</h4>
        <p class="text-muted mt-1">
            @if($hasFilters)
                No results match your current filters. Try adjusting your search.
            @else
                No event galleries have been published yet.
            @endif
        </p>

        @if($hasFilters)
            <a href="{{ route('gallery.user.index') }}" class="btn btn-outline-primary mt-2" data-gallery-reset>
                <i class="ti ti-refresh me-1"></i> Reset Filters
            </a>
        @endif
    </div>
</div>
