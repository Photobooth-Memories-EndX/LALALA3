<div class="row g-4" id="gallery-list">
    @forelse($eventphoto as $list)
        @include('user.gallery.components.gallery-card', ['list' => $list])
    @empty
        @include('user.gallery.components.gallery-empty')
    @endforelse
</div>

@if($eventphoto->hasPages())
    <div class="d-flex justify-content-center mt-5 pb-3">
        {{ $eventphoto->links() }}
    </div>
@endif
