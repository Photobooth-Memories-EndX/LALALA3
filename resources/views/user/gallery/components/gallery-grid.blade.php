<div class="row g-4 gallery-grid" id="gallery-list">
    @forelse($eventphoto as $list)
        @include('user.gallery.components.gallery-card', ['list' => $list])
    @empty
        @include('user.gallery.components.gallery-empty', ['hasFilters' => $hasFilters])
    @endforelse
</div>

@if($eventphoto->hasPages())
    <nav class="gallery-pagination d-flex justify-content-center mt-5 pb-3" aria-label="Gallery pagination">
        {{ $eventphoto->links('pagination::bootstrap-5') }}
    </nav>
@endif
